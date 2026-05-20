<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reading_plans', function (Blueprint $table) {
            $table->id();
            // 💡 複数ユーザーにまたがる認可判定を検証するため、ユーザーIDと書籍IDを外部キーとしてガチッと結合
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            
            // 💡 指示書にある「期日入力フォーム」に対応する日付型
            $table->date('target_date');
            
            // 💡 各状態（1:未着手, 2:読書中, 3:読了）を綺麗に揃えるためのステータスカラム
            $table->tinyInteger('status')->default(1); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_plans');
    }
};
