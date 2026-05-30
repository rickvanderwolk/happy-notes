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
            {{-- Lifetime headline (above the period tabs) --}}
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
                    <div class="stat-value">{{ $uniqueEmojis }}</div>
                    <div class="stat-label">unique emojis</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $totalEmojis }}</div>
                    <div class="stat-label">total emojis</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-value--text">{{ $notingSince }}</div>
                    <div class="stat-label">noting since</div>
                </div>
            </div>

            {{-- Period tabs: breakdowns per period --}}
            <div class="stats-toggle">
                <input class="stats-toggle-input" type="radio" name="stats-period" id="period-all" checked>
                <input class="stats-toggle-input" type="radio" name="stats-period" id="period-quarter">
                <input class="stats-toggle-input" type="radio" name="stats-period" id="period-month">
                <div class="stats-toggle-bar">
                    <label class="stats-toggle-btn" for="period-all">All time</label>
                    <label class="stats-toggle-btn" for="period-quarter">3 months</label>
                    <label class="stats-toggle-btn" for="period-month">30 days</label>
                </div>

                @foreach ($periods as $p)
                    <div class="stats-toggle-panel" data-panel="{{ $p['key'] }}">
                        @if (! $p['hasData'])
                            <div class="empty-state">
                                <div class="empty-state-icon">🌱</div>
                                <div class="empty-state-subtitle">No notes in this period yet.</div>
                            </div>
                        @else
                            <p class="stat-note-strong">{{ $p['notes'] }} notes</p>
                            <p class="stat-note-sub">
                                ≈ {{ $p['avgPerDay'] }} notes per day
                                @if ($p['totalEmojis'] > 0) · ≈ {{ $p['avgEmojisPerNote'] }} emojis per note @endif
                            </p>

                            {{-- Trend over time --}}
                            <h4 class="stats-subsection-title">{{ $p['trend']['title'] }}</h4>
                            <div class="stat-chart{{ $p['trend']['dense'] ? ' stat-chart--dense' : '' }}">
                                @foreach ($p['trend']['list'] as $bar)
                                    <div class="stat-chart-col">
                                        <div class="stat-chart-count">{{ $bar['count'] }}</div>
                                        <div class="stat-chart-bar-track">
                                            <div class="stat-chart-bar" style="height: {{ $bar['count'] > 0 ? max(8, round(($bar['count'] / $p['trend']['max']) * 100)) : 0 }}%"></div>
                                        </div>
                                        <div class="stat-chart-label">{{ $bar['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Day of the week --}}
                            <h4 class="stats-subsection-title">Day of the week <span class="stats-section-note">(UTC)</span></h4>
                            <div class="stat-chart">
                                @foreach ($p['weekdays']['list'] as $weekday)
                                    <div class="stat-chart-col">
                                        <div class="stat-chart-count">{{ $weekday['count'] }}</div>
                                        <div class="stat-chart-bar-track">
                                            <div class="stat-chart-bar" style="height: {{ $weekday['count'] > 0 ? max(8, round(($weekday['count'] / $p['weekdays']['max']) * 100)) : 0 }}%"></div>
                                        </div>
                                        <div class="stat-chart-label">{{ $weekday['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                            @if ($p['weekdays']['busiest'])
                                <p class="stat-note-sub">Most active on {{ $p['weekdays']['busiest'] }}</p>
                            @endif

                            {{-- Time of day --}}
                            <h4 class="stats-subsection-title">Time of day <span class="stats-section-note">(UTC)</span></h4>
                            <div class="emoji-rank-list">
                                @foreach ($p['dayparts']['list'] as $part)
                                    <div class="emoji-rank">
                                        <span class="daypart-label">{{ $part['label'] }}</span>
                                        <div class="emoji-rank-bar-track">
                                            <div class="emoji-rank-bar" style="width: {{ $part['count'] > 0 ? max(6, round(($part['count'] / $p['dayparts']['max']) * 100)) : 0 }}%"></div>
                                        </div>
                                        <span class="emoji-rank-count">{{ $part['count'] }}</span>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Top emojis --}}
                            @if (count($p['topEmojis']) > 0)
                                <h4 class="stats-subsection-title">Most used emojis</h4>
                                <div class="emoji-rank-list">
                                    @foreach ($p['topEmojis'] as $item)
                                        <div class="emoji-rank">
                                            <span class="emoji-rank-emoji">{{ $item['emoji'] }}</span>
                                            <div class="emoji-rank-bar-track">
                                                <div class="emoji-rank-bar" style="width: {{ max(6, round(($item['count'] / $p['topEmojis'][0]['count']) * 100)) }}%"></div>
                                            </div>
                                            <span class="emoji-rank-count">{{ $item['count'] }}&times;</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Emoji combos --}}
                            @if (count($p['emojiCombos']) > 0)
                                <h4 class="stats-subsection-title">Often together</h4>
                                <div class="emoji-rank-list">
                                    @foreach ($p['emojiCombos'] as $combo)
                                        <div class="emoji-rank">
                                            <span class="combo-label">{{ $combo['label'] }}</span>
                                            <div class="emoji-rank-bar-track">
                                                <div class="emoji-rank-bar" style="width: {{ max(6, round(($combo['count'] / $p['emojiCombos'][0]['count']) * 100)) }}%"></div>
                                            </div>
                                            <span class="emoji-rank-count">{{ $combo['count'] }}&times;</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
