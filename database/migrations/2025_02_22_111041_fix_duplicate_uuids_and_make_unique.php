<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            DB::table('notes')->whereNull('uuid')->get()->each(function ($note) {
                do {
                    $newUuid = Str::uuid()->toString();
                } while (DB::table('notes')->where('uuid', $newUuid)->exists());

                DB::table('notes')->where('id', $note->id)->update(['uuid' => $newUuid]);
            });

            while (true) {
                $duplicates = DB::table('notes')
                    ->select('uuid')
                    ->groupBy('uuid')
                    ->havingRaw('COUNT(*) > 1')
                    ->pluck('uuid');

                if ($duplicates->isEmpty()) {
                    break;
                }

                foreach ($duplicates as $duplicateUuid) {
                    $duplicateNotes = DB::table('notes')->where('uuid', $duplicateUuid)->get();

                    $first = true;
                    foreach ($duplicateNotes as $note) {
                        if ($first) {
                            $first = false;
                            continue;
                        }

                        do {
                            $newUuid = Str::uuid()->toString();
                        } while (DB::table('notes')->where('uuid', $newUuid)->exists());

                        DB::table('notes')
                            ->where('id', $note->id)
                            ->update(['uuid' => $newUuid]);
                    }
                }
            }

            Schema::table('notes', function (Blueprint $table) {
                $table->unique('uuid');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropUnique('notes_uuid_unique');
        });
    }
};
