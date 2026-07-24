<?php
namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\PendingAction;
use App\Models\AuditLog;
use App\Models\Profile;
use App\Services\AuthService;
use App\Services\Meta\Client;
use App\Services\Meta\Currency;
use App\Services\Meta\ToolExecutor;
use App\Services\Anthropic\Agent;
use App\Services\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ChatController extends Controller {
    private function requireAuth(Request $request) {
        if (session('user_id')) return true;
        return false;
    }

    private function getProfile(): ?Profile {
        $profileId = Session::get('profile_id');
        $userId = Session::get('user_id');
        if (!$profileId || !$userId) return null;
        return AuthService::getProfile($userId, $profileId);
    }

    public function chat(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);

        $profile = $this->getProfile();
        if (!$profile) return response()->json(['error' => 'No profile selected'], 400);

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
            Conversation::create(['id' => $conversationId, 'title' => mb_substr($message, 0, 100), 'profile_id' => $profile->id]);
        }

        $client = new Client($credentials['meta_access_token'], $profile->meta_account_id);
        $account = $client->get($profile->meta_account_id, ['fields' => 'currency']);
        $currency = $account['currency'] ?? $profile->meta_currency ?? 'DZD';

        $agent = new Agent($credentials['anthropic_api_key']);
        $result = $agent->run($conversationId, $message, $client, $currency, $profile->id);

        $result['conversation_id'] = $conversationId;
        return response()->json($result);
    }

    public function approve(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);

        $profile = $this->getProfile();
        if (!$profile) return response()->json(['error' => 'No profile selected'], 400);

        $ip = $request->ip() ?? 'unknown';
        $rl = RateLimiter::checkApprove($ip);
        if (!$rl['allowed']) return response()->json(['error' => 'Rate limited. Try again later.'], 429);

        $credentials = AuthService::getCredentials();
        if (!$credentials) return response()->json(['error' => 'Credentials not configured'], 400);

        $request->validate(['pending_action_id' => 'required', 'approved' => 'required|boolean']);
        $action = PendingAction::where('profile_id', $profile->id)->find($request->pending_action_id);
        if (!$action || $action->status !== 'pending' || $action->expires_at->isPast()) {
            return response()->json(['error' => 'Action not found, already resolved, or expired'], 400);
        }

        $client = new Client($credentials['meta_access_token'], $profile->meta_account_id);
        $agent = new Agent($credentials['anthropic_api_key']);
        $result = $agent->resumeAfterApproval($action, $request->approved, $client, $profile->meta_currency ?? 'DZD');
        return response()->json($result);
    }

    public function status(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);

        $profile = $this->getProfile();
        if (!$profile) return response()->json(['configured' => false, 'connected' => false]);

        $credentials = AuthService::getCredentials();
        if (!$credentials) return response()->json(['configured' => false, 'connected' => false]);

        $client = new Client($credentials['meta_access_token'], $profile->meta_account_id);
        try {
            $account = $client->get($profile->meta_account_id, ['fields' => 'id,name,account_status,currency,timezone_name,spend_cap,balance,amount_spent']);
            if (isset($account['error'])) throw new \Exception($account['error']);
            $multiplier = Currency::getMultiplier($account['currency'] ?? 'USD');
            $campaigns = $client->get($profile->meta_account_id . '/campaigns', ['fields' => 'id,status', 'limit' => 100]);
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
                $insights = $client->get($profile->meta_account_id . '/insights', ['level' => 'account', 'date_preset' => 'last_7d', 'fields' => 'spend,actions']);
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

            if (isset($account['name']) && !$profile->meta_account_name) {
                $profile->update(['meta_account_name' => $account['name'], 'meta_currency' => $account['currency'] ?? null]);
            }

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
        $profile = $this->getProfile();
        if (!$profile) return response()->json(['entries' => []]);
        $limit = min((int)$request->input('limit', 50), 100);
        $offset = max((int)$request->input('offset', 0), 0);
        $entries = AuditLog::where('profile_id', $profile->id)->orderByDesc('created_at')->limit($limit)->offset($offset)->get();
        return response()->json(['entries' => $entries, 'limit' => $limit, 'offset' => $offset]);
    }

    public function pending(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);
        $profile = $this->getProfile();
        if (!$profile) return response()->json(['pending_actions' => []]);
        $pending = PendingAction::where('profile_id', $profile->id)->where('status', 'pending')->orderByDesc('created_at')->get();
        return response()->json(['pending_actions' => $pending]);
    }

    public function conversations(Request $request) {
        if (!$this->requireAuth($request)) return response()->json(['error' => 'Unauthorized'], 401);
        $profile = $this->getProfile();
        if (!$profile) return response()->json(['conversations' => []]);
        $convs = Conversation::where('profile_id', $profile->id)->orderByDesc('updated_at')->get();
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
