<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('leads.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&larr;</a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ $lead->name }}
                </h2>
                <x-lead-status-badge :status="$lead->status" />
            </div>
            <a href="{{ route('leads.edit', $lead) }}"
               class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Edit lead
            </a>
        </div>
    </x-slot>

    @php
        // wa.me deep link straight to this lead's WhatsApp.
        $waNumber = preg_replace('/\D+/', '', $lead->phone);
        $waUrl = $waNumber !== '' ? 'https://wa.me/'.$waNumber : null;
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-800 dark:text-green-300">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Lead summary --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Lead info</h3>
                    <dl class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500 dark:text-gray-400">Phone</dt>
                            <dd class="text-gray-900 dark:text-gray-100 font-mono">{{ $lead->phone }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $lead->email ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500 dark:text-gray-400">Source</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ config("crm.lead_sources.{$lead->source}", ucfirst($lead->source)) }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500 dark:text-gray-400">Captured</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $lead->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                        </div>
                        @if ($lead->notes)
                            <div class="py-2">
                                <dt class="text-gray-500 dark:text-gray-400 mb-1">Notes</dt>
                                <dd class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $lead->notes }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($waUrl)
                        <a href="{{ $waUrl }}" target="_blank" rel="noopener"
                           class="mt-4 inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500">
                            💬 Chat on WhatsApp
                        </a>
                    @endif
                </div>

                {{-- Money roll-up across this lead's projects --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Deal value</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Pipeline (all projects)</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100 tabular-nums"><x-money :amount="$lead->total_value" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Won revenue</dt>
                            <dd class="text-2xl font-semibold text-green-600 dark:text-green-400 tabular-nums"><x-money :amount="$lead->won_value" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Open projects</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100 tabular-nums">{{ $lead->open_projects_count }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Projects --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Projects</h3>
                    <a href="{{ route('projects.create', ['lead' => $lead->id]) }}"
                       class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        + Add project
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse ($lead->projects as $project)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $project->title }}</span>
                                        <x-stage-badge :stage="$project->stage" />
                                    </div>
                                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex flex-wrap gap-x-4 gap-y-1">
                                        <span>Value: <x-money :amount="$project->value" class="text-gray-700 dark:text-gray-300" /></span>
                                        <span>Cost: <x-money :amount="$project->cost" class="text-gray-700 dark:text-gray-300" /></span>
                                        <span>Profit:
                                            <x-money :amount="$project->profit"
                                                     class="{{ $project->profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" />
                                        </span>
                                        @if ($project->expected_close_date)
                                            <span>Close: {{ $project->expected_close_date->format('Y-m-d') }}</span>
                                        @endif
                                    </div>
                                    @if ($project->event)
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Guestbook:
                                            <a href="{{ route('events.show', $project->event) }}"
                                               class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $project->event->couple_name }}</a>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    {{-- Quick stage change --}}
                                    <form method="POST" action="{{ route('projects.stage', $project) }}" class="flex items-center gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <select name="stage" onchange="this.form.submit()"
                                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1">
                                            @foreach (config('crm.pipeline') as $key => $meta)
                                                <option value="{{ $key }}" @selected($project->stage === $key)>{{ $meta['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <a href="{{ route('projects.edit', $project) }}"
                                       class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Edit</a>
                                </div>
                            </div>

                            {{-- Progress bar --}}
                            <div class="mt-3">
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    <span>{{ $project->stage_label }}</span>
                                    <span class="tabular-nums">{{ $project->progress }}%</span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full {{ $project->stage === 'lost' ? 'bg-red-500' : 'bg-indigo-500' }}"
                                         style="width: {{ max($project->progress, 3) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                            No projects yet. <a href="{{ route('projects.create', ['lead' => $lead->id]) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Add the first project</a> for this lead.
                        </p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
