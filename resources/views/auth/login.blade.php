<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Meta Ads Console</title><script src="https://cdn.tailwindcss.com"></script><style>body{background:#0a0e14;color:#c8d0dc;font-family:'SF Mono',monospace}input:focus{outline:2px solid #3b82f6}</style></head><body class="min-h-screen flex items-center justify-center">
<div class="w-full max-w-sm rounded border border-[#1e2a3a] bg-[#111720] p-6">
<h1 class="text-center text-xl font-bold text-white mb-6">META ADS CONSOLE</h1>
@if($errors->any())<p class="text-red-500 text-xs mb-4">{{$errors->first()}}</p>@endif
<form method="POST" action="/login" class="space-y-4">
@csrf
<div><label class="block text-[10px] text-[#6a7a8a] mb-1">USERNAME</label><input type="text" name="username" required autofocus class="w-full rounded border border-[#1e2a3a] bg-[#0a0e14] px-3 py-2 text-sm text-white"></div>
<div><label class="block text-[10px] text-[#6a7a8a] mb-1">PASSWORD</label><input type="password" name="password" required class="w-full rounded border border-[#1e2a3a] bg-[#0a0e14] px-3 py-2 text-sm text-white"></div>
<button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded text-sm font-bold">LOGIN</button>
</form>
</div></body></html>
