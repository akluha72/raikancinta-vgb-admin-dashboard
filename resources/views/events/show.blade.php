<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('events.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&larr;</a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ $event->couple_name }}
                </h2>
                <x-status-badge :status="$event->status" />
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-800 dark:text-green-300">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Shareable links --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Links</h3>
                <div class="flex flex-col sm:flex-row sm:items-start gap-6">
                    {{-- Left: both shareable URLs stacked --}}
                    <div class="flex-1 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Guest submission URL</label>
                            <div class="flex gap-2">
                                <input type="text" readonly value="{{ $guestUrl }}"
                                       class="flex-1 rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-sm font-mono text-gray-700 dark:text-gray-300" />
                                <button type="button" data-copy="{{ $guestUrl }}"
                                        class="js-copy rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Copy
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Gallery URL</label>
                            <div class="flex gap-2">
                                <input type="text" readonly value="{{ $galleryUrl }}"
                                       class="flex-1 rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-sm font-mono text-gray-700 dark:text-gray-300" />
                                <button type="button" data-copy="{{ $galleryUrl }}"
                                        class="js-copy rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Right: QR for the guest URL, with download buttons beneath --}}
                    <div class="shrink-0 self-center sm:self-start flex flex-col items-center gap-3">
                        <div class="rounded-lg bg-white p-2 inline-block">
                            {!! $qrSvg !!}
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('events.qr', $event) }}"
                               class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Download SVG
                            </a>
                            <a href="{{ route('events.qr', ['event' => $event, 'format' => 'png']) }}"
                               class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Download PNG
                            </a>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center max-w-[240px]">
                            Guests scan to open the submission page. SVG for signage; PNG for raster tools.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Submission summary --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Submissions</h3>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100 tabular-nums">{{ $total }}</div>
                    </div>
                </div>
            </div>

            {{-- Event info + gallery PIN --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Event info</h3>
                    <dl class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500 dark:text-gray-400">Couple</dt>
                            <dd class="text-gray-900 dark:text-gray-100 font-medium">{{ $event->couple_name }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500 dark:text-gray-400">Wedding date</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $event->wedding_date?->format('Y-m-d') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500 dark:text-gray-400">Plan tier</dt>
                            <dd class="text-gray-900 dark:text-gray-100 capitalize">{{ $event->plan_tier }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500 dark:text-gray-400">Slug</dt>
                            <dd class="text-gray-900 dark:text-gray-100 font-mono">{{ $event->slug }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $event->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Gallery PIN</h3>
                    <div class="flex items-center justify-between">
                        <span class="text-3xl font-mono font-semibold tracking-widest text-gray-900 dark:text-gray-100">
                            {{ $event->gallery_pin ?? '——————' }}
                        </span>
                        <form method="POST" action="{{ route('events.reset-pin', $event) }}"
                              onsubmit="return confirm('Generate a new gallery PIN? The old one will stop working.');">
                            @csrf
                            <button type="submit"
                                    class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Reset PIN
                            </button>
                        </form>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        The PIN protects gallery access. Resetting invalidates the previous code.
                    </p>
                </div>
            </div>

        </div>
    </div>

    {{-- Copy-to-clipboard (vanilla JS, no framework) --}}
    <script>
        document.querySelectorAll('.js-copy').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const text = btn.getAttribute('data-copy');
                try {
                    await navigator.clipboard.writeText(text);
                } catch (e) {
                    // Fallback for non-secure contexts where the Clipboard API is unavailable.
                    const tmp = document.createElement('textarea');
                    tmp.value = text;
                    document.body.appendChild(tmp);
                    tmp.select();
                    document.execCommand('copy');
                    document.body.removeChild(tmp);
                }
                const original = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(function () { btn.textContent = original; }, 1500);
            });
        });
    </script>
</x-app-layout>
