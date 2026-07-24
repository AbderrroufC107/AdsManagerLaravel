<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Meta Ads Console</title><script src="https://cdn.tailwindcss.com"></script><style>body{background:#0a0e14;color:#c8d0dc;font-family:'SF Mono',monospace}input:focus{outline:2px solid #3b82f6}.profile-card{transition:all .2s}.profile-card:hover{border-color:#3b82f6;transform:translateY(-2px)}</style></head><body class="min-h-screen flex items-center justify-center">
<div class="w-full max-w-lg rounded border border-[#1e2a3a] bg-[#111720] p-6">
<div class="flex items-center justify-between mb-6">
<h1 class="text-xl font-bold text-white">META ADS CONSOLE</h1>
<div class="flex gap-3">
<a href="/logout" class="text-xs text-[#6a7a8a] hover:text-white">Logout</a>
</div>
</div>

@if(!$credentials || empty($credentials['meta_access_token']))
<div class="mb-6 p-4 rounded border border-yellow-500/30 bg-yellow-500/10">
<p class="text-xs text-yellow-500 mb-2">API credentials not configured yet.</p>
<a href="#" onclick="showCredentialsModal()" class="text-xs text-blue-400 hover:text-blue-300">Setup Credentials</a>
</div>
@endif

<div class="flex items-center justify-between mb-4">
<h2 class="text-sm font-bold text-white">Ad Accounts</h2>
@if($credentials && !empty($credentials['meta_access_token']))
<a href="/profiles/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-xs font-bold">+ Add Account</a>
@endif
</div>

@if($profiles->isEmpty())
<div class="text-center py-12 text-[#6a7a8a]">
<p class="text-sm mb-2">No ad accounts yet</p>
<p class="text-xs">Click "Add Account" to connect your first Meta ad account.</p>
</div>
@else
<div class="space-y-3">
@foreach($profiles as $profile)
<div class="profile-card rounded border border-[#1e2a3a] bg-[#0a0e14] p-4 cursor-pointer" onclick="selectProfile('{{$profile->id}}')">
<div class="flex items-center justify-between">
<div>
<h3 class="text-sm font-bold text-white">{{ $profile->name }}</h3>
<p class="text-[10px] text-[#6a7a8a] mt-1">{{ $profile->meta_account_id }}</p>
@if($profile->meta_account_name)
<p class="text-[10px] text-[#6a7a8a]">{{ $profile->meta_account_name }}</p>
@endif
</div>
<div class="flex items-center gap-2">
@if($profile->meta_currency)
<span class="text-[10px] px-1.5 py-0.5 rounded bg-[#1a2030] text-[#6a7a8a]">{{ $profile->meta_currency }}</span>
@endif
<form method="POST" action="/profiles/{{ $profile->id }}" onclick="event.stopPropagation()" onsubmit="return confirm('Delete this profile?')">
@csrf
@method('DELETE')
<button type="submit" class="text-[10px] text-red-500 hover:text-red-400">Delete</button>
</form>
</div>
</div>
</div>
@endforeach
</div>
@endif
</div>

<div id="cred-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60">
<div class="w-full max-w-md rounded border border-[#1e2a3a] bg-[#111720] p-6">
<h2 class="text-sm font-bold text-white mb-4">Setup Credentials</h2>
<div class="space-y-3">
<div><label class="block text-[10px] text-[#6a7a8a] mb-1">META ACCESS TOKEN</label><input type="password" id="cred-token" class="w-full rounded border border-[#1e2a3a] bg-[#0a0e14] px-3 py-2 text-xs text-white"></div>
<div><label class="block text-[10px] text-[#6a7a8a] mb-1">ANTHROPIC API KEY</label><input type="password" id="cred-anthropic" class="w-full rounded border border-[#1e2a3a] bg-[#0a0e14] px-3 py-2 text-xs text-white"></div>
</div>
<div class="mt-4 flex gap-2">
<button onclick="saveCredentials()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded text-xs font-bold">Save</button>
<button onclick="hideCredentialsModal()" class="rounded border border-[#1e2a3a] px-4 py-2 text-xs text-[#6a7a8a] hover:text-white">Cancel</button>
</div>
</div>
</div>

<script>
function selectProfile(id){document.cookie='profile_id='+id+';path=/';window.location.href='/profiles/'+id+'/select'}
function showCredentialsModal(){document.getElementById('cred-modal').classList.remove('hidden')}
function hideCredentialsModal(){document.getElementById('cred-modal').classList.add('hidden')}
async function saveCredentials(){
await fetch('/api/credentials',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content||''},body:JSON.stringify({meta_access_token:document.getElementById('cred-token').value,anthropic_api_key:document.getElementById('cred-anthropic').value})});
hideCredentialsModal();window.location.reload();
}
</script>
</body></html>
