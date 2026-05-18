<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // schemaの定義（upメソッド内）の中に「title_kana」を追記します
        Schema::create('books', function ( Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            // ★この1行を新規追記！後から任意入力にできるよう nullable にしておきます
            $table->string('title_kana')->nullable(); 
            $table->string('author');

            // ★ここを修正！末尾に ->nullable() を追加して空でもOKにします [INDEX1]
            $table->string('isbn')->nullable();
            $table->date('published_date')->nullable();

            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
