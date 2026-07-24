<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PendingAction;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PendingActionTest extends TestCase {
    use RefreshDatabase;

    public function test_is_expired_returns_false_for_pending_action(): void {
        $conv = Conversation::factory()->create();
        $action = PendingAction::create([
            'conversation_id' => $conv->id,
            'tool_name' => 'pause_object',
            'tool_input' => ['objectType' => 'campaign', 'objectId' => '123'],
            'preview' => 'Pause campaign',
            'conversation_state' => [],
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
        ]);
        $this->assertFalse($action->isExpired());
    }

    public function test_is_expired_returns_true_when_expired(): void {
        $conv = Conversation::factory()->create();
        $action = PendingAction::create([
            'conversation_id' => $conv->id,
            'tool_name' => 'pause_object',
            'tool_input' => ['objectType' => 'campaign', 'objectId' => '123'],
            'preview' => 'Pause campaign',
            'conversation_state' => [],
            'status' => 'pending',
            'expires_at' => now()->subMinutes(1),
        ]);
        $this->assertTrue($action->isExpired());
    }

    public function test_is_expired_returns_true_when_resolved(): void {
        $conv = Conversation::factory()->create();
        $action = PendingAction::create([
            'conversation_id' => $conv->id,
            'tool_name' => 'pause_object',
            'tool_input' => ['objectType' => 'campaign', 'objectId' => '123'],
            'preview' => 'Pause campaign',
            'conversation_state' => [],
            'status' => 'approved',
            'expires_at' => now()->addMinutes(10),
        ]);
        $this->assertTrue($action->isExpired());
    }
}
