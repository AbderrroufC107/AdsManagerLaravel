<?php
namespace App\Services\Anthropic;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\PendingAction;
use App\Models\AuditLog;
use App\Services\Meta\Client;
use App\Services\Meta\ToolExecutor;
use App\Services\Meta\Currency;
use Illuminate\Support\Str;

class Agent {
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com';

    public function __construct() {
        $this->apiKey = env('ANTHROPIC_API_KEY', '');
    }

    public function run(string $conversationId, string $userMessage, Client $client, string $currency): array {
        Message::create(['conversation_id' => $conversationId, 'role' => 'user', 'content' => $userMessage]);
        $messages = Message::where('conversation_id', $conversationId)->orderBy('created_at')->get()->map(fn($m) => ['role' => $m->role, 'content' => $m->content])->toArray();
        $loopCount = 0;
        while ($loopCount++ < 10) {
            $response = $this->callClaude($messages);
            $textBlocks = array_filter($response['content'], fn($b) => $b['type'] === 'text');
            $toolBlocks = array_filter($response['content'], fn($b) => $b['type'] === 'tool_use');
            if (!empty($textBlocks)) {
                $text = implode("\n", array_map(fn($b) => $b['text'], $textBlocks));
                Message::create(['conversation_id' => $conversationId, 'role' => 'assistant', 'content' => $text]);
                $messages[] = ['role' => 'assistant', 'content' => $response['content']];
            }
            if (empty($toolBlocks)) {
                return ['type' => 'complete', 'content' => $text ?? 'No response.'];
            }
            foreach ($toolBlocks as $tool) {
                $toolName = $tool['name'];
                $toolInput = $tool['input'];
                if (ToolExecutor::getToolType($toolName) === 'write') {
                    $preview = ToolExecutor::buildPreview($toolName, $toolInput, $currency);
                    $pending = PendingAction::create(['conversation_id' => $conversationId, 'tool_name' => $toolName, 'tool_input' => $toolInput, 'preview' => $preview, 'conversation_state' => $messages, 'expires_at' => now()->addMinutes(15)]);
                    return ['type' => 'write_pending', 'content' => $text ?? '', 'pending_action_id' => $pending->id, 'preview' => $preview, 'tool_name' => $toolName, 'tool_input' => $toolInput];
                }
                try {
                    $result = ToolExecutor::executeRead($client, $toolName, $toolInput);
                } catch (\Exception $e) {
                    $result = "Error: " . $e->getMessage();
                }
                Message::create(['conversation_id' => $conversationId, 'role' => 'tool', 'content' => $result, 'tool_call_id' => $tool['id'], 'tool_name' => $toolName]);
                $messages[] = ['role' => 'user', 'content' => [['type' => 'tool_result', 'tool_use_id' => $tool['id'], 'content' => $result]]];
            }
        }
        return ['type' => 'error', 'error' => 'Agent loop exceeded max iterations'];
    }

    public function resumeAfterApproval(PendingAction $action, bool $approved, Client $client, string $currency): array {
        $state = $action->conversation_state;
        if ($approved) {
            try {
                $result = ToolExecutor::executeWrite($client, $action->tool_name, $action->tool_input);
                $toolResult = isset($result['error']) ? "Failed: {$result['error']}" : "Success: " . json_encode($result);
            } catch (\Exception $e) {
                $toolResult = "Error: " . $e->getMessage();
            }
        } else {
            $toolResult = "User rejected this action. Do not retry.";
        }
        $action->update(['status' => $approved ? 'approved' : 'rejected', 'resolved_at' => now()]);
        $state[] = ['role' => 'user', 'content' => [['type' => 'tool_result', 'tool_use_id' => '', 'content' => $toolResult]]];
        $response = $this->callClaude($state);
        $text = implode("\n", array_map(fn($b) => $b['text'] ?? '', array_filter($response['content'], fn($b) => $b['type'] === 'text')));
        Message::create(['conversation_id' => $action->conversation_id, 'role' => 'assistant', 'content' => $text ?: 'Action completed.']);
        AuditLog::create(['pending_action_id' => $action->id, 'tool_name' => $action->tool_name, 'tool_input' => $action->tool_input, 'preview' => $action->preview, 'approved' => $approved, 'meta_response' => $result ?? null, 'error' => $approved ? null : 'Rejected by user']);
        return ['type' => 'complete', 'content' => $text ?: 'Action completed.'];
    }

    private function callClaude(array $messages): array {
        $ch = curl_init($this->baseUrl . '/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-api-key: ' . $this->apiKey, 'anthropic-version: 2023-06-01'],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'claude-sonnet-4-20250514', 'max_tokens' => 4096,
                'system' => self::getSystemPrompt(), 'messages' => $messages,
                'tools' => array_map(fn($t) => ['name' => $t['name'], 'description' => $t['description'], 'input_schema' => ['type' => 'object', 'properties' => [], 'required' => []]], ToolExecutor::getAllTools()),
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public static function getSystemPrompt(): string {
        return "You are a Meta Ads management assistant for a COD business in Algeria and North Africa. Reply in the user's language (Arabic, French, English, or mixed). Always pull data before recommending. Never report figures tools didn't return. Below 1000 impressions, say data is too thin. CPA matters more than ROAS for COD. Ask for confirmation rate. Max one write per turn.";
    }
}
