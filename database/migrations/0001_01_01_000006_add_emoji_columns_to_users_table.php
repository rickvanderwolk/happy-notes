<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('all_emojis')->nullable();
            $table->json('selected_emojis')->nullable();
            $table->json('excluded_emojis')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['all_emojis', 'selected_emojis', 'excluded_emojis']);
        });
    }
};
