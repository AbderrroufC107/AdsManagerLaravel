<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Meta Ads Console</title><script src="https://cdn.tailwindcss.com"></script><style>body{background:#0a0e14;color:#c8d0dc;font-family:'SF Mono',monospace}input:focus{outline:2px solid #3b82f6}.msg-user{background:#3b82f6;color:white}.msg-assistant{background:#111720;color:#e8ecf0}.tool-chip{background:#1a2030;font-size:10px;padding:2px 8px;border-radius:4px;display:inline-flex;align-items:center;gap:4px}.approval{border:2px solid #f97316;background:#111720}.pulse{animation:pulse 2s infinite}@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}</style></head>
<body class="min-h-screen flex flex-col">
<header class="flex items-center justify-between border-b border-[#1e2a3a] px-4 py-2">
<div class="flex items-center gap-3"><span class="text-sm font-bold text-white">META ADS</span><span id="acct-name" class="text-xs text-[#6a7a8a]"></span><span id="acct-status" class="text-[10px] px-1.5 py-0.5 rounded"></span><span id="acct-currency" class="text-[10px] text-[#6a7a8a]"></span></div>
<div class="flex items-center gap-3"><a href="/profiles" class="text-xs text-blue-400 hover:text-blue-300">Switch Account</a><button onclick="showCredentials()" class="text-xs text-[#6a7a8a] hover:text-white">Settings</button><a href="/logout" class="text-xs text-[#6a7a8a] hover:text-white">Logout</a></div>
</header>
<div class="flex flex-1 overflow-hidden">
<aside class="hidden w-60 flex-col border-r border-[#1e2a3a] bg-[#111720] p-3 md:flex">
<h2 class="mb-3 text-[10px] uppercase tracking-wider text-[#6a7a8a]">Account Vitals</h2>
<div id="vitals" class="space-y-2 text-xs"><p class="text-[#6a7a8a]">Loading...</p></div>
<div class="mt-auto border-t border-[#1e2a3a] pt-3">
<h2 class="mb-2 text-[10px] uppercase tracking-wider text-[#6a7a8a]">Navigation</h2>
<a href="/console" class="block rounded px-2 py-1 text-xs bg-[#1a2030] text-white">Chat</a>
<a href="/audit" class="block rounded px-2 py-1 text-xs text-[#6a7a8a] hover:text-white">Audit Log</a>
</div>
</aside>
<main class="flex flex-1 flex-col overflow-hidden">
<div id="messages" class="flex-1 overflow-y-auto p-4 space-y-3"></div>
<div id="approvals"></div>
<div class="border-t border-[#1e2a3a] p-3">
<form id="chat-form" class="flex gap-2">
<input type="text" id="chat-input" placeholder="Ask about your ads..." class="flex-1 rounded border border-[#1e2a3a] bg-[#0a0e14] px-3 py-2.5 text-sm text-white placeholder-[#6a7a8a] focus:border-blue-500" autofocus>
<button type="submit" id="send-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded text-sm font-bold">Send</button>
</form>
</div>
</main>
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
<button onclick="hideCredentials()" class="rounded border border-[#1e2a3a] px-4 py-2 text-xs text-[#6a7a8a] hover:text-white">Cancel</button>
</div>
</div>
</div>
<script>
let currentConv=null;
document.getElementById('chat-form').onsubmit=async e=>{e.preventDefault();const input=document.getElementById('chat-input');if(!input.value.trim())return;addMsg('user',input.value);const msg=input.value;input.value='';try{const r=await fetch('/api/chat',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({message:msg,conversation_id:currentConv})});const d=await r.json();currentConv=d.conversation_id;if(d.type==='write_pending'){if(d.content)addMsg('assistant',d.content);showApproval(d)}else if(d.type==='complete'){addMsg('assistant',d.content||'')}else{addMsg('assistant','Error: '+(d.error||'Unknown'))}}catch(e){addMsg('assistant','Network error')}};
function addMsg(role,content){const el=document.getElementById('messages');const dir=/[\u0600-\u06FF]/.test(content)?'rtl':'ltr';el.innerHTML+=`<div class="${role==='user'?'flex justify-end':''}"><div class="${role==='user'?'msg-user':'msg-assistant'} inline-block max-w-[85%] rounded-lg px-4 py-2.5 text-sm leading-relaxed" dir="${dir}"><pre class="whitespace-pre-wrap break-words">${escHtml(content)}</pre></div></div>`;el.scrollTop=el.scrollHeight}
function showApproval(d){document.getElementById('approvals').innerHTML=`<div class="mx-4 mb-4 rounded-lg approval"><div class="flex items-center justify-between border-b border-orange-500/20 px-4 py-2"><span class="inline-block h-2 w-2 rounded-full bg-orange-500 pulse"></span><span class="text-xs font-bold text-orange-500">APPROVAL REQUIRED</span><span class="text-[10px] text-[#6a7a8a]">${d.tool_name}</span></div><div class="p-4"><pre class="whitespace-pre-wrap rounded bg-[#0a0e14] p-3 text-xs text-[#c8d0dc]">${escHtml(d.preview)}</pre></div><div class="flex gap-2 border-t border-orange-500/20 px-4 py-3"><button onclick="handleApprove('${d.pending_action_id}',true)" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-xs font-bold">APPROVE</button><button onclick="handleApprove('${d.pending_action_id}',false)" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-xs font-bold">REJECT</button></div></div>`}
async function handleApprove(id,approved){document.getElementById('approvals').innerHTML='';try{const r=await fetch('/api/approve',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({pending_action_id:id,approved})});const d=await r.json();if(d.type==='complete'&&d.content)addMsg('assistant',d.content)}catch(e){addMsg('assistant','Failed to process approval')}}
async function fetchStatus(){try{const r=await fetch('/api/status');const d=await r.json();if(d.connected){document.getElementById('acct-name').textContent=d.account.name;document.getElementById('acct-status').textContent='CONNECTED';document.getElementById('acct-status').className='text-[10px] px-1.5 py-0.5 rounded bg-green-500/20 text-green-500';document.getElementById('acct-currency').textContent=d.account.currency;document.getElementById('vitals').innerHTML=vitalRow('7D Spend',d.vitals.seven_day_spend)+vitalRow('Purchases',d.vitals.seven_day_purchases)+vitalRow('CPA',d.vitals.seven_day_cpa?d.account.currency+' '+d.vitals.seven_day_cpa:'N/A')+vitalRow('Active',d.vitals.active_campaigns+'/'+d.vitals.total_campaigns)+vitalRow('Multiplier','x'+d.account.multiplier)}else{document.getElementById('acct-status').textContent='DISCONNECTED';document.getElementById('acct-status').className='text-[10px] px-1.5 py-0.5 rounded bg-red-500/20 text-red-500';if(!d.configured)showCredentials()}}catch(e){}}
function vitalRow(l,v){return`<div class="flex justify-between"><span class="text-[10px] text-[#6a7a8a]">${l}</span><span class="text-xs font-medium text-white">${v}</span></div>`}
function showCredentials(){document.getElementById('cred-modal').classList.remove('hidden')}
function hideCredentials(){document.getElementById('cred-modal').classList.add('hidden')}
async function saveCredentials(){await fetch('/api/credentials',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({meta_access_token:document.getElementById('cred-token').value,anthropic_api_key:document.getElementById('cred-anthropic').value})});hideCredentials();fetchStatus()}
function escHtml(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}
fetchStatus();setInterval(fetchStatus,30000);
</script>
</body></html>
