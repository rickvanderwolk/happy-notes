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
            // Add composite index on user_id and updated_at for efficient sorting
            // This index optimizes the most common query: WHERE user_id = ? ORDER BY updated_at DESC
            $table->index(['user_id', 'updated_at'], 'notes_user_id_updated_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex('notes_user_id_updated_at_index');
        });
    }
};
