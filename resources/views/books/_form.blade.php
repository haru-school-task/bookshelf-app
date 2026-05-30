@php
    $bookGenreIds = isset($book) ? $book->genres->pluck('id')->toArray() : [];
@endphp

@csrf
<div class="space-y-6">
    <!-- タイトル -->
    <div>
        <label for="title" class="block font-medium text-sm text-gray-700 mb-1">
            タイトル <span class="text-red-500">*</span>
        </label>
        <input type="text" name="title" id="title" value="{{ old('title', $book->title ?? '') }}"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
            placeholder="書籍のタイトルを入力">
        @error('title')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- 著者 -->
    <div>
        <label for="author" class="block font-medium text-sm text-gray-700 mb-1">
            著者 <span class="text-red-500">*</span>
        </label>
        <input type="text" name="author" id="author" value="{{ old('author', $book->author ?? '') }}"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
            placeholder="著者名を入力">
        @error('author')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- ISBN -->
    <div>
        <label for="isbn" class="block font-medium text-sm text-gray-700 mb-1">
            ISBN-13
        </label>
        <input type="text" name="isbn" id="isbn" value="{{ old('isbn', $book->isbn ?? '') }}"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
            placeholder="9784000000000">
        <p class="text-xs text-gray-500 mt-1">13桁のISBNコードを入力してください</p>
        @error('isbn')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- 出版日 -->
    <div>
        <label for="published_date" class="block font-medium text-sm text-gray-700 mb-1">
            出版日
        </label>
        <input type="date" name="published_date" id="published_date" {{-- 💡 修正ポイント：\Carbon\Carbon::parse
            を挟んで文字列エラーを完全に防ぎます --}}
            value="{{ old('published_date', isset($book->published_date) ? \Carbon\Carbon::parse($book->published_date)->format('Y-m-d') : '') }}"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full">
        @error('published_date')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- 説明 -->
    <div>
        <label for="description" class="block font-medium text-sm text-gray-700 mb-1">
            説明
        </label>
        <textarea name="description" id="description" rows="4"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
            placeholder="書籍の説明を入力（任意）">{{ old('description', $book->description ?? '') }}</textarea>
        @error('description')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- 画像URL -->
    <div>
        <label for="image_url" class="block font-medium text-sm text-gray-700 mb-1">
            画像URL
        </label>
        <input type="text" name="display_image_url" id="image_url"
            value="{{ old('display_image_url', $book->image_url ?? '') }}"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full"
            placeholder="https://example.com/image.jpg">
        <p class="text-xs text-gray-500 mt-1">書籍の表紙画像のURLを入力してください（任意）</p>
        @error('image_url')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- ジャンル -->
    <div>
        <label class="block font-medium text-sm text-gray-700 mb-2">
            ジャンル <span class="text-red-500">*</span>
        </label>
        <div class="bg-gray-50 rounded-md p-4">
            @if($genres->isEmpty())
                <p class="text-sm text-gray-500">ジャンルが登録されていません。先にジャンルを登録してください。</p>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($genres as $genre)
                        <label class="inline-flex items-center cursor-pointer hover:bg-gray-100 p-2 rounded">
                            <input type="checkbox" name="genre_ids[]" value="{{ $genre->id }}"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                @if(in_array($genre->id, old('genre_ids', $bookGenreIds))) checked @endif>
                            <span class="ml-2 text-sm text-gray-700">{{ $genre->name }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>
        @error('genre_ids')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
        @error('genre_ids.*')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>


<!-- 💡 【超重要】 -->
<!-- APIから届いた画像URLとタイトル（かな用）を、ブラウザが裏側で回収してコントローラーへ確実に送信するための隠しポストです！ -->
<input type="hidden" id="image_url" name="image_url" value="{{ old('image_url', $book->image_url ?? '') }}">
<input type="hidden" id="title_kana" name="title_kana" value="{{ old('title_kana', $book->title_kana ?? '') }}">

<script>
    // 🔒【絶対開通ハック】
    // 保存ボタンが押されてフォームが送信される「まさにその瞬間（submit）」に割り込み、
    // 隠しフィールドの無効化（disabled）を強制解除して、生URLデータを100%無傷でコントローラーへ直撃させます！
    document.querySelector('form').addEventListener('submit', function (e) {
        // 画面内の隠しフィールド（image_url）をピンポイントで強制ハント
        var hiddenUrl = document.getElementById('image_url');
        var hiddenKana = document.getElementById('title_kana');

        if (hiddenUrl) {
            hiddenUrl.disabled = false; // 門番（無効化）を完全に破壊
            hiddenUrl.name = 'image_url'; // 送信キーを完全固定
        }
        if (hiddenKana) {
            hiddenKana.disabled = false;
            hiddenKana.name = 'title_kana';
        }
    });
</script>