<x-app-layout>
    <div class="container max-w-md mx-auto">
        <div class="text-center mb-6">
            <h2 class="section-title"><i class="fa fa-chart-simple me-2"></i>Stats <span class="beta-badge">beta</span></h2>
            <p class="section-description-text">A look at your note-taking habits</p>
        </div>

        @if ($totalNotes === 0)
            <div class="empty-state">
                <div class="empty-state-icon">📊</div>
                <div class="empty-state-title">No stats yet</div>
                <div class="empty-state-subtitle">Create your first note and come back here!</div>
            </div>
        @else
            {{-- Number grid --}}
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">{{ $totalNotes }}</div>
                    <div class="stat-label">total notes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $newThisWeek }}</div>
                    <div class="stat-label">new this week</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $newThisMonth }}</div>
                    <div class="stat-label">new this month</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $distinctEmojiCount }}</div>
                    <div class="stat-label">unique emojis</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $totalEmojiCount }}</div>
                    <div class="stat-label">total emojis</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-value--text">{{ $notingSince }}</div>
                    <div class="stat-label">noting since</div>
                </div>
            </div>

            {{-- Notes per week --}}
            <div class="stats-section">
                <h3 class="stats-section-title">Notes per week <span class="period-badge period-badge--recent">last 10 weeks</span></h3>
                <div class="stat-chart">
                    @foreach ($weeks as $week)
                        <div class="stat-chart-col">
                            <div class="stat-chart-count">{{ $week['count'] }}</div>
                            <div class="stat-chart-bar-track">
                                <div class="stat-chart-bar" style="height: {{ $week['count'] > 0 ? max(8, round(($week['count'] / $maxWeek) * 100)) : 0 }}%"></div>
                            </div>
                            <div class="stat-chart-label">{{ $week['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Notes per weekday --}}
            <div class="stats-section">
                <h3 class="stats-section-title">Day of the week <span class="period-badge">all time</span></h3>
                <div class="stat-chart">
                    @foreach ($weekdays as $weekday)
                        <div class="stat-chart-col">
                            <div class="stat-chart-count">{{ $weekday['count'] }}</div>
                            <div class="stat-chart-bar-track">
                                <div class="stat-chart-bar" style="height: {{ $weekday['count'] > 0 ? max(8, round(($weekday['count'] / $maxWeekday) * 100)) : 0 }}%"></div>
                            </div>
                            <div class="stat-chart-label">{{ $weekday['label'] }}</div>
                        </div>
                    @endforeach
                </div>
                @if ($busiestWeekday)
                    <p class="stat-note-sub">Most active on {{ $busiestWeekday }}</p>
                @endif
            </div>

            {{-- Parts of the day (UTC) --}}
            <div class="stats-section">
                <h3 class="stats-section-title">Time of day <span class="period-badge">all time</span> <span class="stats-section-note">(UTC)</span></h3>
                <div class="emoji-rank-list">
                    @foreach ($dayparts as $part)
                        <div class="emoji-rank">
                            <span class="daypart-label">{{ $part['label'] }}</span>
                            <div class="emoji-rank-bar-track">
                                <div class="emoji-rank-bar" style="width: {{ $part['count'] > 0 ? max(6, round(($part['count'] / $maxDaypart) * 100)) : 0 }}%"></div>
                            </div>
                            <span class="emoji-rank-count">{{ $part['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Top emojis all-time --}}
            @if (count($topEmojis) > 0)
                <div class="stats-section">
                    <h3 class="stats-section-title">Most used emojis <span class="period-badge">all time</span></h3>
                    <div class="emoji-rank-list">
                        @foreach ($topEmojis as $item)
                            <div class="emoji-rank">
                                <span class="emoji-rank-emoji">{{ $item['emoji'] }}</span>
                                <div class="emoji-rank-bar-track">
                                    <div class="emoji-rank-bar" style="width: {{ max(6, round(($item['count'] / $topEmojis[0]['count']) * 100)) }}%"></div>
                                </div>
                                <span class="emoji-rank-count">{{ $item['count'] }}&times;</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Trending emojis --}}
            @if (count($trendingEmojis) > 0)
                <div class="stats-section">
                    <h3 class="stats-section-title"><i class="fa fa-arrow-trend-up me-2"></i>Trending emojis <span class="period-badge period-badge--recent">last 30 days</span></h3>
                    <div class="emoji-rank-list">
                        @foreach ($trendingEmojis as $item)
                            <div class="emoji-rank">
                                <span class="emoji-rank-emoji">{{ $item['emoji'] }}</span>
                                <div class="emoji-rank-bar-track">
                                    <div class="emoji-rank-bar" style="width: {{ max(6, round(($item['count'] / $trendingEmojis[0]['count']) * 100)) }}%"></div>
                                </div>
                                <span class="emoji-rank-count">{{ $item['count'] }}&times;</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
