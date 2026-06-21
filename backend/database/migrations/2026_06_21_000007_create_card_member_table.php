<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_member', function (Blueprint $table) {
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['card_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_member');
    }
};
