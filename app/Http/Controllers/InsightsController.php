<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Carbon\Carbon;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class InsightsController extends Controller
{
    public function index(Request $request): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        $timezone = $this->timezone($request);

        // Fingerprint the user's notes with two aggregates that the (user_id, updated_at)
        // index already covers. Any note that is added, changed or removed moves the key,
        // so the cached figures can never be stale; the TTL is only there to stop old
        // entries piling up. Bump the version whenever the shape of the cached data changes.
        $noteCount = Note::query()->count();
        $lastChange = Note::query()->max('updated_at') ?? 'never';

        $cacheKey = 'insights:v8:' . Auth::id() . ':' . sha1($noteCount . '|' . $lastChange . '|' . $timezone);

        $data = Cache::remember($cacheKey, now()->addDay(), fn (): array => $this->buildInsights($timezone));

        return view('insights', $data);
    }

    /**
     * The browser reports its timezone in the tz cookie (set in app.js); without a valid
     * one the figures fall back to the app timezone.
     */
    private function timezone(Request $request): string
    {
        $timezone = $request->cookie('tz');

        if (! is_string($timezone) || preg_match('/^[A-Za-z0-9_+\-\/]{1,64}$/', $timezone) !== 1) {
            return (string) config('app.timezone');
        }

        try {
            return (new DateTimeZone($timezone))->getName();
        } catch (\Exception) {
            return (string) config('app.timezone');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInsights(string $timezone): array
    {
        // Own-notes are scoped automatically via OwnNotesScope on every query below.
        // Timestamps are stored in the app timezone and converted to the viewer's local
        // time, so days, weekdays and dayparts match the clock the notes were written by.
        $now = Carbon::now($timezone);
        $storedZone = new DateTimeZone((string) config('app.timezone'));
        $localZone = new DateTimeZone($timezone);
        $toLocal = fn (mixed $ts): string => ($ts instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($ts)
            : new DateTimeImmutable((string) $ts, $storedZone))
            ->setTimezone($localZone)
            ->format('Y-m-d H:i:s');
        $startOfWeekStr = $now->copy()->startOfWeek()->toDateString();
        $startOfMonthStr = $now->copy()->startOfMonth()->toDateString();

        // Load only the bare created_at column (no body, no model hydration) and derive
        // everything from it in PHP. Stays light at 10k+ notes and is DB-independent.
        $timestamps = Note::query()->pluck('created_at')->map($toLocal);
        $totalNotes = $timestamps->count();

        if ($totalNotes === 0) {
            return ['totalNotes' => 0];
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

        // Rising and fading emojis, on the 3-month view only: the last three months against
        // the year before. A full year as baseline keeps seasonal emojis from looking like
        // they disappeared; thirty days held too few notes and too much season to say much.
        $shiftRecentFrom = $cutoffs['quarter'];
        $shiftBaseFrom = $now->copy()->subMonths(3)->subYear()->toDateTimeString();
        $shiftCounts = ['recent' => [], 'base' => []];
        $shiftNotes = ['recent' => 0, 'base' => 0];

        foreach ($emojiRows as $row) {
            $tsR = $toLocal($row->created_at);
            $emojis = array_values(array_unique($row->emojis ?? []));
            $cnt = count($emojis);

            if ($cnt === 0) {
                continue;
            }

            $side = $tsR >= $shiftRecentFrom ? 'recent' : ($tsR >= $shiftBaseFrom ? 'base' : null);
            if ($side !== null) {
                $shiftNotes[$side]++;
                foreach ($emojis as $emoji) {
                    $shiftCounts[$side][$emoji] = ($shiftCounts[$side][$emoji] ?? 0) + 1;
                }
            }

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
        $cursorMonth = Carbon::parse($timestamps->min(), $timezone)->startOfMonth();
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
            Carbon::parse($timestamps->min(), $timezone)->startOfDay()->diffInDays($now->copy()->startOfDay())
        )) + 1;
        $uniqueEmojis = count($emCounts['all']);
        $totalEmojis = $emTags['all'];
        $notingSince = Carbon::parse($timestamps->min(), $timezone)->format('M Y');

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
                'shiftNote' => $timestamps->min() > $shiftBaseFrom ? 'vs before' : 'vs the year before',
                'emojiShifts' => $k === 'quarter'
                    ? $this->emojiShifts($shiftCounts, $shiftNotes)
                    : ['rising' => [], 'fading' => []],
            ];
        }

        return compact(
            'totalNotes',
            'newThisWeek',
            'newThisMonth',
            'uniqueEmojis',
            'totalEmojis',
            'notingSince',
            'periods',
        );
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

        return ['list' => $list, 'max' => max([1, ...$totals])];
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

        return ['list' => $list, 'max' => max([1, ...$counts])];
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

    /**
     * Emojis whose share of notes grew or shrank the most between the two windows,
     * in percentage points so the emojis that matter most come first.
     *
     * @param  array{recent:array<string,int>,base:array<string,int>}  $counts
     * @param  array{recent:int,base:int}  $notes
     * @return array{rising:list<array{emoji:string,from:int,to:int,change:float}>,fading:list<array{emoji:string,from:int,to:int,change:float}>}
     */
    private function emojiShifts(array $counts, array $notes): array
    {
        $shifts = ['rising' => [], 'fading' => []];

        if ($notes['recent'] < 5 || $notes['base'] < 20) {
            return $shifts;
        }

        $emojis = array_keys($counts['recent'] + $counts['base']);
        foreach ($emojis as $emoji) {
            $emoji = (string) $emoji;
            $recent = $counts['recent'][$emoji] ?? 0;
            $base = $counts['base'][$emoji] ?? 0;
            $to = $recent / $notes['recent'] * 100;
            $from = $base / $notes['base'] * 100;
            $change = $to - $from;

            $row = ['emoji' => $emoji, 'from' => (int) round($from), 'to' => (int) round($to), 'change' => $change];
            if ($change >= 5 && $recent >= 3) {
                $shifts['rising'][] = $row;
            } elseif ($change <= -5 && $base >= 5) {
                $shifts['fading'][] = $row;
            }
        }

        usort($shifts['rising'], fn (array $x, array $y): int => $y['change'] <=> $x['change']);
        usort($shifts['fading'], fn (array $x, array $y): int => $x['change'] <=> $y['change']);

        return [
            'rising' => array_slice($shifts['rising'], 0, 5),
            'fading' => array_slice($shifts['fading'], 0, 5),
        ];
    }
}
