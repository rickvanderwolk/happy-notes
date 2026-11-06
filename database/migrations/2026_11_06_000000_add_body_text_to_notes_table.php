<?php

use App\Helpers\NoteBodyHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('notes', 'body_text')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->text('body_text')->nullable()->after('body');
            });
        }

        DB::table('notes')
            ->select(['id', 'body'])
            ->whereNotNull('body')
            ->whereNull('body_text')
            ->chunkById(200, function ($notes) {
                foreach ($notes as $note) {
                    DB::table('notes')->where('id', $note->id)->update([
                        'body_text' => NoteBodyHelper::toPlainText($note->body),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('body_text');
        });
    }
};
