<?php
namespace App\Services\Meta;

use App\Models\AuditLog;

class ToolExecutor {
    private static array $tools = [
        ['name' => 'get_account', 'description' => 'Get Meta ad account details (name, status, currency, timezone, spend cap, balance, amount spent).', 'type' => 'read', 'input_schema' => ['type' => 'object', 'properties' => [], 'required' => []]],
        ['name' => 'list_campaigns', 'description' => 'List all campaigns. Optionally filter by ACTIVE/PAUSED status. Returns ID, name, status, objective, budget, dates.', 'type' => 'read', 'input_schema' => ['type' => 'object', 'properties' => ['status' => ['type' => 'string', 'enum' => ['ACTIVE', 'PAUSED']], 'limit' => ['type' => 'number']]]],
        ['name' => 'list_adsets', 'description' => 'List ad sets. Filter by status and/or campaign. Returns ID, name, campaign, status, budget, optimization goal, billing event.', 'type' => 'read', 'input_schema' => ['type' => 'object', 'properties' => ['status' => ['type' => 'string', 'enum' => ['ACTIVE', 'PAUSED']], 'campaignId' => ['type' => 'string'], 'limit' => ['type' => 'number']]]],
        ['name' => 'list_ads', 'description' => 'List ads. Filter by status and/or ad set. Returns ID, name, ad set, campaign, status, creative info.', 'type' => 'read', 'input_schema' => ['type' => 'object', 'properties' => ['status' => ['type' => 'string', 'enum' => ['ACTIVE', 'PAUSED']], 'adsetId' => ['type' => 'string'], 'limit' => ['type' => 'number']]]],
        ['name' => 'get_insights', 'description' => 'Get performance insights at account/campaign/adset/ad level. Returns impressions, clicks, spend, reach, frequency, CTR, CPC, CPM, actions, CPA, ROAS.', 'type' => 'read', 'input_schema' => ['type' => 'object', 'properties' => ['level' => ['type' => 'string', 'enum' => ['account', 'campaign', 'adset', 'ad']], 'datePreset' => ['type' => 'string'], 'campaignId' => ['type' => 'string'], 'adsetId' => ['type' => 'string'], 'adId' => ['type' => 'string'], 'timeRange' => ['type' => 'object', 'properties' => ['since' => ['type' => 'string'], 'until' => ['type' => 'string']]]], 'required' => ['level']]],
        ['name' => 'compare_periods', 'description' => 'Compare performance between two time periods for campaigns/adsets/ads. Useful for trend analysis.', 'type' => 'read', 'input_schema' => ['type' => 'object', 'properties' => ['level' => ['type' => 'string', 'enum' => ['campaign', 'adset', 'ad']], 'currentStart' => ['type' => 'string'], 'currentEnd' => ['type' => 'string'], 'previousStart' => ['type' => 'string'], 'previousEnd' => ['type' => 'string']], 'required' => ['level', 'currentStart', 'currentEnd', 'previousStart', 'previousEnd']]],
        ['name' => 'get_audit_log', 'description' => 'Get audit log of executed write actions. Shows changes, approvals, timestamps, results.', 'type' => 'read', 'input_schema' => ['type' => 'object', 'properties' => ['limit' => ['type' => 'number'], 'offset' => ['type' => 'number']]]],
        ['name' => 'pause_object', 'description' => 'Pause a campaign, ad set, or ad. REQUIRES APPROVAL before execution.', 'type' => 'write', 'input_schema' => ['type' => 'object', 'properties' => ['objectType' => ['type' => 'string', 'enum' => ['campaign', 'adset', 'ad']], 'objectId' => ['type' => 'string']], 'required' => ['objectType', 'objectId']]],
        ['name' => 'resume_object', 'description' => 'Resume a paused campaign, ad set, or ad. REQUIRES APPROVAL before execution.', 'type' => 'write', 'input_schema' => ['type' => 'object', 'properties' => ['objectType' => ['type' => 'string', 'enum' => ['campaign', 'adset', 'ad']], 'objectId' => ['type' => 'string']], 'required' => ['objectType', 'objectId']]],
        ['name' => 'set_adset_budget', 'description' => 'Set daily budget for an ad set. REQUIRES APPROVAL. Enforced by server-side spend caps.', 'type' => 'write', 'input_schema' => ['type' => 'object', 'properties' => ['adsetId' => ['type' => 'string'], 'dailyBudget' => ['type' => 'number']], 'required' => ['adsetId', 'dailyBudget']]],
        ['name' => 'set_campaign_budget', 'description' => 'Set daily budget for a campaign. REQUIRES APPROVAL. Enforced by server-side spend caps.', 'type' => 'write', 'input_schema' => ['type' => 'object', 'properties' => ['campaignId' => ['type' => 'string'], 'dailyBudget' => ['type' => 'number']], 'required' => ['campaignId', 'dailyBudget']]],
    ];

    public static function getAllTools(): array {
        return self::$tools;
    }

    public static function getToolType(string $toolName): ?string {
        foreach (self::$tools as $tool) {
            if ($tool['name'] === $toolName) return $tool['type'];
        }
        return null;
    }

    public static function executeRead(Client $client, string $toolName, array $input): string {
        return match($toolName) {
            'get_account' => self::getAccount($client),
            'list_campaigns' => self::listCampaigns($client, $input),
            'list_adsets' => self::listAdSets($client, $input),
            'list_ads' => self::listAds($client, $input),
            'get_insights' => self::getInsights($client, $input),
            'compare_periods' => self::comparePeriods($client, $input),
            'get_audit_log' => self::getAuditLog($input),
            default => "Unknown tool: $toolName",
        };
    }

    public static function executeWrite(Client $client, string $toolName, array $input): array {
        return match($toolName) {
            'pause_object' => self::pauseObject($client, $input),
            'resume_object' => self::resumeObject($client, $input),
            'set_adset_budget' => self::setBudget($client, 'adset', $input['adsetId'] ?? '', $input['dailyBudget'] ?? 0),
            'set_campaign_budget' => self::setBudget($client, 'campaign', $input['campaignId'] ?? '', $input['dailyBudget'] ?? 0),
            default => ['error' => "Unknown write tool: $toolName"],
        };
    }

    public static function buildPreview(string $toolName, array $input, string $currency): string {
        return match($toolName) {
            'pause_object' => "⏸ PAUSE {$input['objectType']}: {$input['objectId']}\nStatus will change to PAUSED.",
            'resume_object' => "▶ RESUME {$input['objectType']}: {$input['objectId']}\nStatus will change to ACTIVE.",
            'set_adset_budget' => "💰 SET AD SET BUDGET\nAd Set: {$input['adsetId']}\nNew Daily Budget: {$currency} {$input['dailyBudget']}",
            'set_campaign_budget' => "💰 SET CAMPAIGN BUDGET\nCampaign: {$input['campaignId']}\nNew Daily Budget: {$currency} {$input['dailyBudget']}",
            default => "Unknown action: $toolName",
        };
    }

    private static function getAccount(Client $client): string {
        $fields = 'id,name,account_status,currency,timezone_name,spend_cap,balance,amount_spent,min_daily_budget';
        $account = $client->get($client->getAccountId(), ['fields' => $fields]);
        if (isset($account['error'])) return "Error: {$account['error']}";
        $multiplier = Currency::getMultiplier($account['currency'] ?? 'USD');
        $statusMap = [1=>'ACTIVE',2=>'DISABLED',3=>'UNSETTLED',5=>'PENDING_RISK_REVIEW',7=>'PENDING_SETTLEMENT',8=>'IN_GRACE_PERIOD',100=>'PENDING_CLOSURE',101=>'CLOSED',201=>'ANY_ACTIVE',202=>'ANY_CLOSED'];
        $status = $statusMap[$account['account_status'] ?? 0] ?? (string)($account['account_status'] ?? 'Unknown');
        $spent = Currency::fromMeta((float)($account['amount_spent'] ?? 0), $account['currency'] ?? 'USD');
        $balance = Currency::fromMeta((float)($account['balance'] ?? 0), $account['currency'] ?? 'USD');
        $cap = Currency::fromMeta((float)($account['spend_cap'] ?? 0), $account['currency'] ?? 'USD');
        return implode("\n", [
            "Account: {$account['name']} ({$account['id']})",
            "Status: $status",
            "Currency: {$account['currency']} (multiplier: $multiplier)",
            "Timezone: {$account['timezone_name']}",
            "Amount Spent: {$account['currency']} $spent",
            "Balance Owed: {$account['currency']} $balance",
            "Spend Cap: " . ($cap > 0 ? "{$account['currency']} $cap" : 'No cap set'),
            "Min Daily Budget: {$account['min_daily_budget']}",
        ]);
    }

    private static function listCampaigns(Client $client, array $input): string {
        $params = ['fields' => 'id,name,status,objective,daily_budget,lifetime_budget,budget_remaining,bid_strategy,created_time,updated_time'];
        if (!empty($input['status'])) {
            $params['filtering'] = json_encode([['field' => 'effective_status', 'operator' => 'IN', 'value' => [$input['status']]]]);
        }
        $params['limit'] = $input['limit'] ?? 100;
        $response = $client->get($client->getAccountId() . '/campaigns', $params);
        if (isset($response['error'])) return "Error: {$response['error']}";
        if (empty($response['data'])) return "No campaigns found.";
        return array_map(fn($c) => "- {$c['name']} ({$c['id']}): {$c['status']} | {$c['objective']} | Budget: " . ($c['daily_budget'] ?? $c['lifetime_budget'] ?? 'N/A') . " | Remaining: " . ($c['budget_remaining'] ?? 'N/A'), $response['data']);
    }

    private static function listAdSets(Client $client, array $input): string {
        $params = ['fields' => 'id,name,campaign_id,status,daily_budget,lifetime_budget,budget_remaining,optimization_goal,billing_event,created_time,updated_time'];
        if (!empty($input['status'])) {
            $params['filtering'] = json_encode([['field' => 'effective_status', 'operator' => 'IN', 'value' => [$input['status']]]]);
        }
        if (!empty($input['campaignId'])) $params['campaign_id'] = $input['campaignId'];
        $params['limit'] = $input['limit'] ?? 100;
        $response = $client->get($client->getAccountId() . '/adsets', $params);
        if (isset($response['error'])) return "Error: {$response['error']}";
        if (empty($response['data'])) return "No ad sets found.";
        return array_map(fn($a) => "- {$a['name']} ({$a['id']}): {$a['status']} | Campaign: {$a['campaign_id']} | Goal: {$a['optimization_goal']} | Billing: {$a['billing_event']} | Budget: " . ($a['daily_budget'] ?? 'N/A'), $response['data']);
    }

    private static function listAds(Client $client, array $input): string {
        $params = ['fields' => 'id,name,adset_id,campaign_id,status,creative,created_time,updated_time'];
        if (!empty($input['status'])) {
            $params['filtering'] = json_encode([['field' => 'effective_status', 'operator' => 'IN', 'value' => [$input['status']]]]);
        }
        if (!empty($input['adsetId'])) $params['adset_id'] = $input['adsetId'];
        $params['limit'] = $input['limit'] ?? 100;
        $response = $client->get($client->getAccountId() . '/ads', $params);
        if (isset($response['error'])) return "Error: {$response['error']}";
        if (empty($response['data'])) return "No ads found.";
        return array_map(fn($a) => "- {$a['name']} ({$a['id']}): {$a['status']} | AdSet: {$a['adset_id']} | Campaign: {$a['campaign_id']}", $response['data']);
    }

    private static function getInsights(Client $client, array $input): string {
        $fields = 'id,name,impressions,clicks,spend,reach,frequency,ctr,cpc,cpm,actions,cost_per_action_type,purchase_roas';
        $params = ['level' => $input['level'], 'fields' => $fields];
        if (!empty($input['datePreset'])) $params['date_preset'] = $input['datePreset'];
        if (!empty($input['timeRange'])) $params['time_range'] = json_encode($input['timeRange']);
        if (!empty($input['campaignId'])) $params['filtering'] = json_encode([['field' => 'campaign.id', 'operator' => 'IN', 'value' => [$input['campaignId']]]]);
        elseif (!empty($input['adsetId'])) $params['filtering'] = json_encode([['field' => 'adset.id', 'operator' => 'IN', 'value' => [$input['adsetId']]]]);
        elseif (!empty($input['adId'])) $params['filtering'] = json_encode([['field' => 'ad.id', 'operator' => 'IN', 'value' => [$input['adId']]]]);
        $response = $client->get($client->getAccountId() . '/insights', $params);
        if (isset($response['error'])) return "Error: {$response['error']}";
        if (empty($response['data'])) return "No insights data available for the selected period.";
        return implode("\n\n", array_map(function ($row) {
            $impressions = (int)($row['impressions'] ?? 0);
            $lines = [
                "Name: {$row['name']} ({$row['id']})",
                "Impressions: $impressions",
                "Clicks: " . ($row['clicks'] ?? 0),
                "Spend: " . ($row['spend'] ?? 0),
                "Reach: " . ($row['reach'] ?? 0),
                "Frequency: " . ($row['frequency'] ?? 0),
                "CTR: " . ($row['ctr'] ?? 0) . '%',
                "CPC: " . ($row['cpc'] ?? 0),
                "CPM: " . ($row['cpm'] ?? 0),
            ];
            if (!empty($row['actions'])) {
                $lines[] = 'Actions:';
                foreach ($row['actions'] as $a) $lines[] = "  {$a['action_type']}: {$a['value']}";
            }
            if (!empty($row['cost_per_action_type'])) {
                $lines[] = 'Cost Per Action:';
                foreach ($row['cost_per_action_type'] as $c) $lines[] = "  {$c['action_type']}: {$c['value']}";
            }
            if (!empty($row['purchase_roas'])) {
                $lines[] = 'Purchase ROAS:';
                foreach ($row['purchase_roas'] as $r) $lines[] = "  {$r['action_type']}: {$r['value']} ({$r['currency_unit']})";
            }
            if ($impressions < 1000) $lines[] = '⚠ LOW DATA: Less than 1000 impressions. Results may not be statistically significant.';
            return implode("\n", $lines);
        }, $response['data']));
    }

    private static function comparePeriods(Client $client, array $input): string {
        $fields = 'id,name,impressions,clicks,spend,reach,frequency,ctr,cpc,cpm';
        $current = $client->get($client->getAccountId() . '/insights', ['level' => $input['level'], 'fields' => $fields, 'time_range' => json_encode(['since' => $input['currentStart'], 'until' => $input['currentEnd']])]);
        $previous = $client->get($client->getAccountId() . '/insights', ['level' => $input['level'], 'fields' => $fields, 'time_range' => json_encode(['since' => $input['previousStart'], 'until' => $input['previousEnd']])]);
        $formatRows = function ($data, $label) {
            if (empty($data)) return "$label: No data";
            return implode("\n", array_map(fn($r) => "  {$r['name']}: " . ($r['impressions'] ?? 0) . ' impr, ' . ($r['spend'] ?? 0) . ' spend, ' . ($r['clicks'] ?? 0) . ' clicks, CTR ' . ($r['ctr'] ?? 0) . '%, CPC ' . ($r['cpc'] ?? 0) . ', CPM ' . ($r['cpm'] ?? 0), $data));
        };
        return "=== CURRENT PERIOD ({$input['currentStart']} to {$input['currentEnd']}) ===\n" . $formatRows($current['data'] ?? [], 'Current') . "\n\n=== PREVIOUS PERIOD ({$input['previousStart']} to {$input['previousEnd']}) ===\n" . $formatRows($previous['data'] ?? [], 'Previous');
    }

    private static function getAuditLog(array $input): string {
        $limit = $input['limit'] ?? 50;
        $offset = $input['offset'] ?? 0;
        $entries = AuditLog::orderByDesc('created_at')->limit($limit)->offset($offset)->get();
        if ($entries->isEmpty()) return "No audit log entries found.";
        return $entries->map(fn($e) => "[{$e->created_at}] {$e->tool_name} | " . ($e->approved ? 'APPROVED' : 'REJECTED') . " | Input: " . json_encode($e->tool_input) . " | " . ($e->error ? "Error: {$e->error}" : 'Success'))->implode("\n");
    }

    private static function pauseObject(Client $client, array $input): array {
        $objectId = $input['objectId'] ?? '';
        return $client->post($objectId, ['status' => 'PAUSED']);
    }

    private static function resumeObject(Client $client, array $input): array {
        $objectId = $input['objectId'] ?? '';
        return $client->post($objectId, ['status' => 'ACTIVE']);
    }

    private static function setBudget(Client $client, string $type, string $objectId, int $dailyBudget): array {
        $cap = (int)(env('ADSET_DAILY_BUDGET_CEILING', 50000)) * 100;
        if ($dailyBudget > $cap) return ['error' => "Budget $dailyBudget exceeds ceiling of $cap"];
        return $client->post($objectId, ['daily_budget' => (string)$dailyBudget]);
    }
}
