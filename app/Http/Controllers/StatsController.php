<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Carbon\Carbon;

final class StatsController extends Controller
{
    public function index(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        // Own-notes are scoped automatically via OwnNotesScope on every query below.
        $now = Carbon::now();
        $startOfWeekStr = $now->copy()->startOfWeek()->toDateString();
        $startOfMonthStr = $now->copy()->startOfMonth()->toDateString();

        // Load only the bare created_at column (no body, no model hydration) and derive
        // everything from it in PHP. Stays light at 10k+ notes and is DB-independent.
        // Note: timestamps are stored in UTC; time-of-day stats are reported in UTC.
        $timestamps = Note::query()->pluck('created_at');

        $totalNotes = $timestamps->count();

        if ($totalNotes === 0) {
            return view('stats', $this->emptyPayload());
        }

        $notingSince = Carbon::parse($timestamps->min())->format('M Y');

        // Single pass: count per day and per part-of-day (UTC).
        $dailyMap = [];        // 'Y-m-d' => count
        $daypartCounts = [0, 0, 0, 0]; // nacht, ochtend, middag, avond
        $newThisWeek = 0;
        $newThisMonth = 0;
        foreach ($timestamps as $ts) {
            $ts = (string) $ts;
            $day = substr($ts, 0, 10);
            $hour = (int) substr($ts, 11, 2);

            $dailyMap[$day] = ($dailyMap[$day] ?? 0) + 1;
            if ($day >= $startOfWeekStr) {
                $newThisWeek++;
            }
            if ($day >= $startOfMonthStr) {
                $newThisMonth++;
            }

            $daypartCounts[intdiv($hour, 6)]++;
        }

        // --- Notes per week (last 10 weeks) — 70 map lookups. ---
        $weeks = [];
        for ($i = 9; $i >= 0; $i--) {
            $weekStart = $now->copy()->startOfWeek()->subWeeks($i);
            $count = 0;
            $cursor = $weekStart->copy();
            for ($d = 0; $d < 7; $d++) {
                $count += $dailyMap[$cursor->toDateString()] ?? 0;
                $cursor->addDay();
            }
            $weeks[] = ['label' => $weekStart->format('j/n'), 'count' => $count];
        }
        $maxWeek = max(collect($weeks)->max('count'), 1);

        // --- Notes per weekday (one date-parse per active day, not per note). ---
        $weekdayTotals = array_fill(1, 7, 0);
        foreach ($dailyMap as $day => $cnt) {
            $weekdayTotals[Carbon::parse($day)->dayOfWeekIso] += $cnt;
        }
        $weekdayShort = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        $weekdays = [];
        foreach ($weekdayShort as $iso => $label) {
            $weekdays[] = ['label' => $label, 'count' => $weekdayTotals[$iso]];
        }
        $maxWeekday = max(max($weekdayTotals), 1);

        $weekdayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
        $busiestIso = array_keys($weekdayTotals, max($weekdayTotals))[0];
        $busiestWeekday = $weekdayNames[$busiestIso] ?? null;

        // --- Parts of the day (UTC). ---
        $daypartLabels = ['🌙 Night', '🌅 Morning', '☀️ Afternoon', '🌆 Evening'];
        $dayparts = [];
        foreach ($daypartLabels as $idx => $label) {
            $dayparts[] = ['label' => $label, 'count' => $daypartCounts[$idx]];
        }
        $maxDaypart = max(max($daypartCounts), 1);

        // --- Emojis: load only the small emojis column (+ created_at for trending). ---
        $emojiRows = Note::query()
            ->whereNotNull('emojis')
            ->get(['emojis', 'created_at']);

        $allEmojiCounts = [];
        $recentEmojiCounts = [];
        $cutoff = $now->copy()->subDays(30);
        foreach ($emojiRows as $row) {
            $isRecent = $row->created_at >= $cutoff;
            foreach (($row->emojis ?? []) as $emoji) {
                $allEmojiCounts[$emoji] = ($allEmojiCounts[$emoji] ?? 0) + 1;
                if ($isRecent) {
                    $recentEmojiCounts[$emoji] = ($recentEmojiCounts[$emoji] ?? 0) + 1;
                }
            }
        }
        $totalEmojiCount = array_sum($allEmojiCounts);
        $distinctEmojiCount = count($allEmojiCounts);
        arsort($allEmojiCounts);
        arsort($recentEmojiCounts);
        $topEmojis = $this->formatEmojiRanking(array_slice($allEmojiCounts, 0, 5, true));
        $trendingEmojis = $this->formatEmojiRanking(array_slice($recentEmojiCounts, 0, 5, true));

        return view('stats', compact(
            'totalNotes',
            'newThisWeek',
            'newThisMonth',
            'notingSince',
            'weeks',
            'maxWeek',
            'weekdays',
            'maxWeekday',
            'busiestWeekday',
            'dayparts',
            'maxDaypart',
            'totalEmojiCount',
            'distinctEmojiCount',
            'topEmojis',
            'trendingEmojis',
        ));
    }

    /**
     * @return array<string,mixed>
     */
    private function emptyPayload(): array
    {
        return [
            'totalNotes' => 0,
            'newThisWeek' => 0,
            'newThisMonth' => 0,
            'notingSince' => null,
            'weeks' => [],
            'maxWeek' => 1,
            'weekdays' => [],
            'maxWeekday' => 1,
            'busiestWeekday' => null,
            'dayparts' => [],
            'maxDaypart' => 1,
            'totalEmojiCount' => 0,
            'distinctEmojiCount' => 0,
            'topEmojis' => [],
            'trendingEmojis' => [],
        ];
    }

    /**
     * @param  array<string,int>  $counts
     * @return list<array{emoji:string,count:int}>
     */
    private function formatEmojiRanking(array $counts): array
    {
        $ranking = [];
        foreach ($counts as $emoji => $count) {
            $ranking[] = ['emoji' => $emoji, 'count' => $count];
        }
        return $ranking;
    }
}
