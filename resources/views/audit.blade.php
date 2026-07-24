<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Audit Log - Meta Ads Console</title><script src="https://cdn.tailwindcss.com"></script><style>body{background:#0a0e14;color:#c8d0dc;font-family:'SF Mono',monospace}</style></head>
<body class="min-h-screen flex flex-col">
<header class="flex items-center justify-between border-b border-[#1e2a3a] px-4 py-2">
<div class="flex items-center gap-3"><span class="text-sm font-bold text-white">META ADS</span></div>
<div class="flex items-center gap-3"><a href="/console" class="text-xs text-[#6a7a8a] hover:text-white">Chat</a><a href="/logout" class="text-xs text-[#6a7a8a] hover:text-white">Logout</a></div>
</header>
<div class="flex-1 overflow-y-auto p-4">
<div class="flex items-center justify-between mb-4">
<h2 class="text-sm font-bold text-white">Audit Log</h2>
<a href="/audit" class="rounded border border-[#1e2a3a] px-3 py-1 text-xs text-[#6a7a8a] hover:text-white">Refresh</a>
</div>
<div id="entries"><p class="text-sm text-[#6a7a8a]">Loading...</p></div>
</div>
<script>
async function load(){const r=await fetch('/api/audit?limit=50');const d=await r.json();const el=document.getElementById('entries');if(!d.entries||d.entries.length===0){el.innerHTML='<p class="text-sm text-[#6a7a8a]">No entries yet.</p>';return}el.innerHTML='<table class="w-full text-xs"><thead><tr class="border-b border-[#1e2a3a] text-left text-[#6a7a8a]"><th class="pb-2 pr-4">Time</th><th class="pb-2 pr-4">Action</th><th class="pb-2 pr-4">Status</th><th class="pb-2">Result</th></tr></thead><tbody>'+d.entries.map(e=>`<tr class="border-b border-[#1e2a3a]/50"><td class="py-2 pr-4 text-[#6a7a8a]">${new Date(e.created_at).toLocaleString()}</td><td class="py-2 pr-4 text-white">${e.tool_name}</td><td class="py-2 pr-4"><span class="rounded px-1.5 py-0.5 text-[10px] font-bold ${e.approved?'bg-green-500/20 text-green-500':'bg-red-500/20 text-red-500'}">${e.approved?'APPROVED':'REJECTED'}</span></td><td class="max-w-[200px] truncate py-2 text-[#6a7a8a]">${e.error||'Success'}</td></tr>`).join('')+'</tbody></table>'}
load();
</script>
</body></html>
