<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Carbon\Carbon;

final class InsightsController extends Controller
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
            return view('insights', ['totalNotes' => 0]);
        }

        // Three periods for the breakdown tabs; the headline grid stays lifetime.
        $keys = ['month', 'quarter', 'all'];
        $cutoffs = [
            'all' => null,
            'quarter' => $now->copy()->subMonths(3)->toDateTimeString(),
            'month' => $now->copy()->subDays(30)->toDateTimeString(),
        ];

        $notes = ['all' => 0, 'quarter' => 0, 'month' => 0];
        $weekday = ['all' => array_fill(1, 7, 0), 'quarter' => array_fill(1, 7, 0), 'month' => array_fill(1, 7, 0)];
        $daypart = ['all' => [0, 0, 0, 0], 'quarter' => [0, 0, 0, 0], 'month' => [0, 0, 0, 0]];
        $dailyMap = [];
        $monthMap = [];
        $dowCache = [];
        $newThisWeek = 0;
        $newThisMonth = 0;

        foreach ($timestamps as $ts) {
            $ts = (string) $ts;
            $day = substr($ts, 0, 10);
            $hour = (int) substr($ts, 11, 2);
            $bucket = intdiv($hour, 6);
            $dow = $dowCache[$day] ??= Carbon::parse($day)->dayOfWeekIso;

            $dailyMap[$day] = ($dailyMap[$day] ?? 0) + 1;
            $monthMap[substr($ts, 0, 7)] = ($monthMap[substr($ts, 0, 7)] ?? 0) + 1;
            if ($day >= $startOfWeekStr) {
                $newThisWeek++;
            }
            if ($day >= $startOfMonthStr) {
                $newThisMonth++;
            }

            foreach ($keys as $k) {
                if ($cutoffs[$k] === null || $ts >= $cutoffs[$k]) {
                    $notes[$k]++;
                    $weekday[$k][$dow]++;
                    $daypart[$k][$bucket]++;
                }
            }
        }

        // --- Emojis: load only the small emojis column (+ created_at to filter). ---
        $emojiRows = Note::query()->whereNotNull('emojis')->get(['emojis', 'created_at']);

        $emCounts = ['all' => [], 'quarter' => [], 'month' => []];
        $emTags = ['all' => 0, 'quarter' => 0, 'month' => 0];
        $comboCounts = ['all' => [], 'quarter' => [], 'month' => []];
        $comboLabels = [];

        foreach ($emojiRows as $row) {
            $tsR = (string) $row->created_at;
            $emojis = array_values(array_unique($row->emojis ?? []));
            $cnt = count($emojis);

            foreach ($keys as $k) {
                if ($cutoffs[$k] !== null && $tsR < $cutoffs[$k]) {
                    continue;
                }
                foreach ($emojis as $emoji) {
                    $emCounts[$k][$emoji] = ($emCounts[$k][$emoji] ?? 0) + 1;
                    $emTags[$k]++;
                }
                for ($a = 0; $a < $cnt; $a++) {
                    for ($b = $a + 1; $b < $cnt; $b++) {
                        $x = $emojis[$a];
                        $y = $emojis[$b];
                        if ($x > $y) {
                            [$x, $y] = [$y, $x];
                        }
                        $key = $x . '|' . $y;
                        $comboCounts[$k][$key] = ($comboCounts[$k][$key] ?? 0) + 1;
                        $comboLabels[$key] = $x . ' + ' . $y;
                    }
                }
            }
        }

        // --- Trend series. ---
        // All time: one bar per month across the entire history (first note -> now).
        // When that spans many months, only January bars are labelled (with the year).
        $range = [];
        $cursorMonth = Carbon::parse($timestamps->min())->startOfMonth();
        $nowMonth = $now->copy()->startOfMonth();
        while ($cursorMonth <= $nowMonth) {
            $range[] = $cursorMonth->copy();
            $cursorMonth->addMonth();
        }
        $denseMonths = count($range) > 14;
        $months = [];
        foreach ($range as $month) {
            $months[] = [
                'label' => $denseMonths
                    ? ($month->month === 1 ? $month->format('Y') : '')
                    : $month->format('M'),
                'count' => $monthMap[$month->format('Y-m')] ?? 0,
            ];
        }

        $weeks = [];
        for ($i = 12; $i >= 0; $i--) {
            $weekStart = $now->copy()->startOfWeek()->subWeeks($i);
            $count = 0;
            $cursor = $weekStart->copy();
            for ($d = 0; $d < 7; $d++) {
                $count += $dailyMap[$cursor->toDateString()] ?? 0;
                $cursor->addDay();
            }
            $weeks[] = ['label' => $weekStart->format('j/n'), 'count' => $count];
        }

        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->startOfDay()->subDays($i);
            $days[] = [
                'label' => $i % 5 === 0 ? $date->format('j/n') : '',
                'count' => $dailyMap[$date->toDateString()] ?? 0,
            ];
        }

        $trends = [
            'all' => [
                'title' => 'Notes per month',
                'list' => $months,
                'max' => max(collect($months)->max('count'), 1),
                'dense' => $denseMonths,
            ],
            'quarter' => [
                'title' => 'Notes per week',
                'list' => $weeks,
                'max' => max(collect($weeks)->max('count'), 1),
                'dense' => false,
            ],
            'month' => [
                'title' => 'Notes per day',
                'list' => $days,
                'max' => max(collect($days)->max('count'), 1),
                'dense' => true,
            ],
        ];

        // --- Lifetime headline values. ---
        $daysSinceFirst = (int) round(abs(
            Carbon::parse($timestamps->min())->startOfDay()->diffInDays($now->copy()->startOfDay())
        )) + 1;
        $uniqueEmojis = count($emCounts['all']);
        $totalEmojis = $emTags['all'];
        $notingSince = Carbon::parse($timestamps->min())->format('M Y');

        // Days in each window, capped by account age, for the per-period pace.
        $windowDays = [
            'all' => max(1, $daysSinceFirst),
            'quarter' => max(1, min($daysSinceFirst, (int) round($now->copy()->subMonths(3)->diffInDays($now)))),
            'month' => max(1, min($daysSinceFirst, 30)),
        ];

        $labels = ['all' => 'All time', 'quarter' => '3 months', 'month' => '30 days'];
        $periods = [];
        foreach ($keys as $k) {
            $periods[] = [
                'key' => $k,
                'label' => $labels[$k],
                'hasData' => $notes[$k] > 0,
                'notes' => $notes[$k],
                'avgPerDay' => round($notes[$k] / $windowDays[$k], 1),
                'avgEmojisPerNote' => $notes[$k] > 0 ? round($emTags[$k] / $notes[$k], 1) : 0,
                'totalEmojis' => $emTags[$k],
                'trend' => $trends[$k],
                'weekdays' => $this->buildWeekdayChart($weekday[$k]),
                'dayparts' => $this->buildDaypartChart($daypart[$k]),
                'topEmojis' => $this->topEmojis($emCounts[$k]),
                'emojiCombos' => $this->topCombos($comboCounts[$k], $comboLabels),
            ];
        }

        return view('insights', compact(
            'totalNotes',
            'newThisWeek',
            'newThisMonth',
            'uniqueEmojis',
            'totalEmojis',
            'notingSince',
            'periods',
        ));
    }

    /**
     * @param  array<int,int>  $totals  weekday-iso (1-7) => count
     * @return array{list:list<array{label:string,count:int}>,max:int}
     */
    private function buildWeekdayChart(array $totals): array
    {
        $short = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

        $list = [];
        foreach ($short as $iso => $label) {
            $list[] = ['label' => $label, 'count' => $totals[$iso]];
        }

        return ['list' => $list, 'max' => max(max($totals), 1)];
    }

    /**
     * @param  array<int,int>  $counts  daypart-bucket (0-3) => count
     * @return array{list:list<array{label:string,count:int}>,max:int}
     */
    private function buildDaypartChart(array $counts): array
    {
        $labels = ['Night', 'Morning', 'Afternoon', 'Evening'];

        $list = [];
        foreach ($labels as $i => $label) {
            $list[] = ['label' => $label, 'count' => $counts[$i]];
        }

        return ['list' => $list, 'max' => max(max($counts), 1)];
    }

    /**
     * @param  array<string,int>  $counts
     * @return list<array{emoji:string,count:int}>
     */
    private function topEmojis(array $counts): array
    {
        arsort($counts);
        $ranking = [];
        foreach (array_slice($counts, 0, 5, true) as $emoji => $count) {
            $ranking[] = ['emoji' => $emoji, 'count' => $count];
        }
        return $ranking;
    }

    /**
     * @param  array<string,int>  $counts  pair-key => count
     * @param  array<string,string>  $labels  pair-key => "a + b"
     * @return list<array{label:string,count:int}>
     */
    private function topCombos(array $counts, array $labels): array
    {
        arsort($counts);
        $combos = [];
        foreach ($counts as $key => $count) {
            if ($count < 2) {
                break;
            }
            $combos[] = ['label' => $labels[$key], 'count' => $count];
            if (count($combos) >= 5) {
                break;
            }
        }
        return $combos;
    }
}
