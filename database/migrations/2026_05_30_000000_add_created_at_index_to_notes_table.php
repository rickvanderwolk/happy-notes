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
        Schema::table('notes', function (Blueprint $table) {
            // Speeds up the stats page: date-range counts and GROUP BY DATE(created_at),
            // all scoped by user_id. The existing index only covers updated_at.
            $table->index(['user_id', 'created_at'], 'notes_user_id_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex('notes_user_id_created_at_index');
        });
    }
};
