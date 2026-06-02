<x-guest-layout>
    <div class="text-center py-6">
        <div class="text-indigo-600 mb-4 flex justify-center">
            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://w3.org">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        
        <h1 class="text-xl font-bold text-gray-800 mb-2">
            リクエストが多すぎます
        </h1>
        
        <p class="text-gray-600 mb-6 text-sm leading-relaxed">
            短時間に何度も操作が行われたため、<br>
            一時的に制限がかかっています。<br>
            1分ほど時間をおいてから再度お試しください。
        </p>
        
        <a href="/login" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-black font-medium px-4 py-2 rounded shadow transition-colors text-sm">
            ログイン画面に戻る
        </a>
    </div>
</x-guest-layout>
