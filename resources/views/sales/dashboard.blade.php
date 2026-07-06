<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Sales Dashboard') }}
            </h2>
            <a href="{{ route('leads.index') }}"
               class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                View all leads &rarr;
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- KPI tiles --}}
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Leads</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100 tabular-nums">{{ number_format($kpis['total_leads']) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Open Projects</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100 tabular-nums">{{ number_format($kpis['open_projects']) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pipeline Value</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100 tabular-nums"><x-money :amount="$kpis['pipeline_value']" /></div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Revenue (Won)</div>
                    <div class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400 tabular-nums"><x-money :amount="$kpis['revenue']" /></div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Profit</div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums {{ $kpis['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"><x-money :amount="$kpis['profit']" /></div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Win Rate</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100 tabular-nums">{{ $kpis['win_rate'] }}%</div>
                </div>
            </div>

            {{-- Charts row 1 --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Revenue &amp; Profit (last 6 months)</h3>
                    <div class="h-72"><canvas id="revenueChart"></canvas></div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Pipeline Funnel</h3>
                    <div class="h-72"><canvas id="funnelChart"></canvas></div>
                </div>
            </div>

            {{-- Charts row 2 --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Win / Loss</h3>
                    <div class="h-64"><canvas id="winLossChart"></canvas></div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Won value</div>
                            <div class="font-semibold text-green-600 dark:text-green-400 tabular-nums"><x-money :amount="$winLoss['won_value']" /></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Lost value</div>
                            <div class="font-semibold text-red-600 dark:text-red-400 tabular-nums"><x-money :amount="$winLoss['lost_value']" /></div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Lead Volume (last 6 months)</h3>
                    <div class="h-64"><canvas id="leadVolumeChart"></canvas></div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Lead Source</h3>
                    <div class="h-64"><canvas id="leadSourceChart"></canvas></div>
                </div>
            </div>

        </div>
    </div>

    {{-- Chart data (computed server-side) --}}
    @php
        $chartData = [
            'currency' => config('crm.currency_symbol', 'RM'),
            'revenue' => $revenueSeries,
            'funnel' => $funnel,
            'winLoss' => $winLoss,
            'leadVolume' => $leadVolume,
            'leadSource' => $leadSource,
        ];
    @endphp

    <script>
        window.__salesData = @json($chartData);

        // Dark-theme chart chrome (the app is dark-mode-forced). Colours are the
        // dataviz dark categorical steps, validated against the gray-800 surface.
        (function () {
            function boot() {
                if (!window.Chart) { return setTimeout(boot, 50); }

                const data = window.__salesData;
                const cur = data.currency;
                const ink = '#c3c2b7';          // secondary ink
                const grid = 'rgba(148,163,184,0.15)';
                const C = {
                    blue: '#3987e5', aqua: '#199e70', orange: '#d95926',
                    violet: '#9085e9', yellow: '#c98500', green: '#0ca30c', red: '#d03b3b',
                };

                Chart.defaults.color = ink;
                Chart.defaults.borderColor = grid;
                Chart.defaults.font.family = "Figtree, system-ui, -apple-system, 'Segoe UI', sans-serif";
                Chart.defaults.maintainAspectRatio = false;

                const money = (v) => cur + ' ' + Number(v).toLocaleString();

                // 1) Revenue & profit over time — bars (revenue, cost) + profit line.
                new Chart(document.getElementById('revenueChart'), {
                    data: {
                        labels: data.revenue.labels,
                        datasets: [
                            { type: 'bar', label: 'Revenue', data: data.revenue.revenue, backgroundColor: C.blue, borderRadius: 4 },
                            { type: 'bar', label: 'Cost', data: data.revenue.cost, backgroundColor: C.orange, borderRadius: 4 },
                            { type: 'line', label: 'Profit', data: data.revenue.profit, borderColor: C.aqua, backgroundColor: C.aqua, borderWidth: 2, tension: 0.3, pointRadius: 3 },
                        ],
                    },
                    options: {
                        plugins: { legend: { labels: { boxWidth: 12, boxHeight: 12, usePointStyle: true } },
                                   tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + money(c.parsed.y) } } },
                        scales: { y: { ticks: { callback: (v) => cur + ' ' + Number(v).toLocaleString() }, grid: { color: grid } },
                                  x: { grid: { display: false } } },
                    },
                });

                // 2) Pipeline funnel — horizontal bars, count per stage (value in tooltip).
                new Chart(document.getElementById('funnelChart'), {
                    type: 'bar',
                    data: {
                        labels: data.funnel.labels,
                        datasets: [{ label: 'Projects', data: data.funnel.counts, backgroundColor: data.funnel.colors, borderRadius: 4 }],
                    },
                    options: {
                        indexAxis: 'y',
                        plugins: { legend: { display: false },
                                   tooltip: { callbacks: { label: (c) => c.parsed.x + ' projects · ' + money(data.funnel.values[c.dataIndex]) } } },
                        scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } },
                                  y: { grid: { display: false } } },
                    },
                });

                // 3) Win / loss doughnut.
                new Chart(document.getElementById('winLossChart'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Won', 'Lost'],
                        datasets: [{ data: [data.winLoss.won_count, data.winLoss.lost_count],
                                     backgroundColor: [C.green, C.red], borderColor: '#1f2937', borderWidth: 2 }],
                    },
                    options: { cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } } } },
                });

                // 4) Lead volume line.
                new Chart(document.getElementById('leadVolumeChart'), {
                    type: 'line',
                    data: {
                        labels: data.leadVolume.labels,
                        datasets: [{ label: 'New leads', data: data.leadVolume.counts, borderColor: C.blue,
                                     backgroundColor: 'rgba(57,135,229,0.15)', borderWidth: 2, fill: true, tension: 0.3, pointRadius: 3 }],
                    },
                    options: { plugins: { legend: { display: false } },
                               scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }, x: { grid: { display: false } } } },
                });

                // 5) Lead source doughnut.
                new Chart(document.getElementById('leadSourceChart'), {
                    type: 'doughnut',
                    data: {
                        labels: data.leadSource.labels,
                        datasets: [{ data: data.leadSource.counts,
                                     backgroundColor: [C.blue, C.aqua, C.yellow, C.violet], borderColor: '#1f2937', borderWidth: 2 }],
                    },
                    options: { cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } } } },
                });
            }
            boot();
        })();
    </script>
</x-app-layout>
