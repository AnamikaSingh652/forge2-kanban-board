<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_member', function (Blueprint $table) {
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['board_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_member');
    }
};
