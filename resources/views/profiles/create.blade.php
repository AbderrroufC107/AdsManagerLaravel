<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add Ad Account</title><script src="https://cdn.tailwindcss.com"></script><style>body{background:#0a0e14;color:#c8d0dc;font-family:'SF Mono',monospace}input:focus{outline:2px solid #3b82f6}select:focus{outline:2px solid #3b82f6}</style></head><body class="min-h-screen flex items-center justify-center">
<div class="w-full max-w-lg rounded border border-[#1e2a3a] bg-[#111720] p-6">
<div class="flex items-center justify-between mb-6">
<h1 class="text-xl font-bold text-white">Add Ad Account</h1>
<a href="/profiles" class="text-xs text-[#6a7a8a] hover:text-white">Back</a>
</div>

<form method="POST" action="/profiles" class="space-y-4">
@csrf
<div>
<label class="block text-[10px] text-[#6a7a8a] mb-1">PROFILE NAME</label>
<input type="text" name="name" required placeholder="My Store" class="w-full rounded border border-[#1e2a3a] bg-[#0a0e14] px-3 py-2 text-sm text-white">
</div>

<div>
<label class="block text-[10px] text-[#6a7a8a] mb-1">AD ACCOUNT</label>
@if(!empty($accounts))
<select name="meta_account_id" required class="w-full rounded border border-[#1e2a3a] bg-[#0a0e14] px-3 py-2 text-sm text-white">
<option value="">Select an ad account...</option>
@foreach($accounts as $account)
<option value="{{ $account['id'] }}">{{ $account['name'] ?? $account['id'] }} ({{ $account['currency'] ?? 'N/A' }}) - {{ $account['id'] }}</option>
@endforeach
</select>
@else
<input type="text" name="meta_account_id" required placeholder="act_123456789" class="w-full rounded border border-[#1e2a3a] bg-[#0a0e14] px-3 py-2 text-sm text-white">
<p class="text-[10px] text-[#6a7a8a] mt-1">Couldn't fetch accounts automatically. Enter your Ad Account ID manually (format: act_123456789)</p>
@endif
</div>

<button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded text-sm font-bold">Add Account</button>
</form>
</div>
</body></html>
