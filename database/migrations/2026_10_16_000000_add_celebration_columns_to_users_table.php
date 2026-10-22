<?php

use App\Helpers\CelebrationHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('celebrated_milestone')->default(0);
            $table->unsignedSmallInteger('celebrated_anniversary')->default(0);
        });

        // Existing users start at what they have already reached, so nobody gets a
        // celebration for a milestone or anniversary that passed before this existed.
        $noteCounts = DB::table('notes')
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        DB::table('users')->select(['id', 'created_at'])->orderBy('id')->chunk(500, function ($users) use ($noteCounts) {
            foreach ($users as $user) {
                $years = $user->created_at
                    ? (int) floor(Carbon::parse($user->created_at)->diffInYears(now()))
                    : 0;

                DB::table('users')->where('id', $user->id)->update([
                    'celebrated_milestone' => CelebrationHelper::highestMilestoneUpTo((int) ($noteCounts[$user->id] ?? 0)),
                    'celebrated_anniversary' => max(0, $years),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['celebrated_milestone', 'celebrated_anniversary']);
        });
    }
};
