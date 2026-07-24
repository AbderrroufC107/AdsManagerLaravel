<?php
namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\PendingAction;
use App\Models\AuditLog;
use App\Services\AuthService;
use App\Services\Meta\Client;
use App\Services\Meta\Currency;
use App\Services\Meta\ToolExecutor;
use App\Services\Anthropic\Agent;
use App\Services\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller {
    private function requireAuth(Request $request) {
        if (session('user_id')) return true;
        return false;
    }

    public function chat(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);

        $ip = $request->ip() ?? 'unknown';
        $rl = RateLimiter::checkChat($ip);
        if (!$rl['allowed']) return response()->json(['error' => 'Rate limited. Try again later.'], 429);

        $credentials = AuthService::getCredentials();
        if (!$credentials) return response()->json(['error' => 'Credentials not configured'], 400);

        $request->validate(['message' => 'required|max:10000']);
        $message = $request->message;
        $conversationId = $request->input('conversation_id');

        if (!$conversationId) {
            $conversationId = (string) Str::uuid();
            Conversation::create(['id' => $conversationId, 'title' => mb_substr($message, 0, 100)]);
        }

        $client = new Client($credentials['meta_access_token'], $credentials['meta_account_id']);
        $account = $client->get($client->getAccountId(), ['fields' => 'currency']);
        $currency = $account['currency'] ?? 'DZD';

        $agent = new Agent();
        $result = $agent->run($conversationId, $message, $client, $currency);

        $result['conversation_id'] = $conversationId;
        return response()->json($result);
    }

    public function approve(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);

        $ip = $request->ip() ?? 'unknown';
        $rl = RateLimiter::checkApprove($ip);
        if (!$rl['allowed']) return response()->json(['error' => 'Rate limited. Try again later.'], 429);

        $credentials = AuthService::getCredentials();
        if (!$credentials) return response()->json(['error' => 'Credentials not configured'], 400);

        $request->validate(['pending_action_id' => 'required', 'approved' => 'required|boolean']);
        $action = PendingAction::find($request->pending_action_id);
        if (!$action || $action->status !== 'pending' || $action->expires_at->isPast()) {
            return response()->json(['error' => 'Action not found, already resolved, or expired'], 400);
        }

        $client = new Client($credentials['meta_access_token'], $credentials['meta_account_id']);
        $agent = new Agent();
        $result = $agent->resumeAfterApproval($action, $request->approved, $client, 'DZD');
        return response()->json($result);
    }

    public function status(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);

        $credentials = AuthService::getCredentials();
        if (!$credentials) return response()->json(['configured' => false, 'connected' => false]);

        $client = new Client($credentials['meta_access_token'], $credentials['meta_account_id']);
        try {
            $account = $client->get($client->getAccountId(), ['fields' => 'id,name,account_status,currency,timezone_name,spend_cap,balance,amount_spent']);
            if (isset($account['error'])) throw new \Exception($account['error']);
            $multiplier = Currency::getMultiplier($account['currency'] ?? 'USD');
            $campaigns = $client->get($client->getAccountId() . '/campaigns', ['fields' => 'id,status', 'limit' => 100]);
            $activeCampaigns = 0;
            $totalCampaigns = 0;
            if (isset($campaigns['data'])) {
                $totalCampaigns = count($campaigns['data']);
                $activeCampaigns = count(array_filter($campaigns['data'], fn($c) => $c['status'] === 'ACTIVE'));
            }
            $sevenDaySpend = '0';
            $sevenDayPurchases = 0;
            $sevenDayCpa = null;
            try {
                $insights = $client->get($client->getAccountId() . '/insights', ['level' => 'account', 'date_preset' => 'last_7d', 'fields' => 'spend,actions']);
                if (!empty($insights['data'][0])) {
                    $row = $insights['data'][0];
                    $sevenDaySpend = $row['spend'] ?? '0';
                    if (!empty($row['actions'])) {
                        $purchase = array_filter($row['actions'], fn($a) => $a['action_type'] === 'offsite_conversion.fb_pixel_purchase');
                        if ($purchase) {
                            $purchase = reset($purchase);
                            $sevenDayPurchases = (int) $purchase['value'];
                            if ($sevenDayPurchases > 0) {
                                $sevenDayCpa = round(Currency::fromMeta((float)$sevenDaySpend, $account['currency'] ?? 'DZD') / $sevenDayPurchases, 2);
                            }
                        }
                    }
                }
            } catch (\Exception $e) { }
            return response()->json([
                'configured' => true, 'connected' => true,
                'account' => ['id' => $account['id'], 'name' => $account['name'], 'currency' => $account['currency'], 'multiplier' => $multiplier],
                'vitals' => ['seven_day_spend' => Currency::fromMeta((float)$sevenDaySpend, $account['currency'] ?? 'DZD'), 'seven_day_purchases' => $sevenDayPurchases, 'seven_day_cpa' => $sevenDayCpa, 'active_campaigns' => $activeCampaigns, 'total_campaigns' => $totalCampaigns],
            ]);
        } catch (\Exception $e) {
            return response()->json(['configured' => true, 'connected' => false, 'error' => $e->getMessage()]);
        }
    }

    public function audit(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);
        $limit = min((int)$request->input('limit', 50), 100);
        $offset = max((int)$request->input('offset', 0), 0);
        $entries = AuditLog::orderByDesc('created_at')->limit($limit)->offset($offset)->get();
        return response()->json(['entries' => $entries, 'limit' => $limit, 'offset' => $offset]);
    }

    public function pending(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);
        $pending = PendingAction::where('status', 'pending')->orderByDesc('created_at')->get();
        return response()->json(['pending_actions' => $pending]);
    }

    public function conversations(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);
        $convs = Conversation::orderByDesc('updated_at')->get();
        return response()->json(['conversations' => $convs]);
    }

    public function getMessages(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);
        $request->validate(['conversation_id' => 'required']);
        $msgs = Message::where('conversation_id', $request->conversation_id)->orderBy('created_at')->get();
        return response()->json(['messages' => $msgs]);
    }

    public function health() {
        return response()->json(['status' => 'ok', 'timestamp' => now()->timestamp]);
    }
}
