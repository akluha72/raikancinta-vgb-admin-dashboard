<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Create Event') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('events.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    {{-- Couple name --}}
                    <div>
                        <x-input-label for="couple_name" :value="__('Couple name')" />
                        <x-text-input id="couple_name" name="couple_name" type="text"
                                      class="mt-1 block w-full"
                                      :value="old('couple_name')"
                                      required autofocus
                                      placeholder="e.g. Sarah &amp; Ali" />
                        <x-input-error :messages="$errors->get('couple_name')" class="mt-2" />
                    </div>

                    {{-- Wedding date (optional) --}}
                    <div>
                        <x-input-label for="wedding_date" :value="__('Wedding date (optional)')" />
                        <x-text-input id="wedding_date" name="wedding_date" type="date"
                                      class="mt-1 block w-full"
                                      :value="old('wedding_date')" />
                        <x-input-error :messages="$errors->get('wedding_date')" class="mt-2" />
                    </div>

                    {{-- Venue (optional) --}}
                    <div>
                        <x-input-label for="venue" :value="__('Venue (optional)')" />
                        <x-text-input id="venue" name="venue" type="text"
                                      class="mt-1 block w-full"
                                      :value="old('venue')"
                                      placeholder="e.g. The Grand Ballroom, Kuala Lumpur" />
                        <x-input-error :messages="$errors->get('venue')" class="mt-2" />
                    </div>

                    {{-- Couple photo (optional) --}}
                    <div>
                        <x-input-label for="couple_photo" :value="__('Couple photo (optional)')" />
                        <input id="couple_photo" name="couple_photo" type="file"
                               accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
                               class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 dark:file:bg-gray-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 dark:file:text-gray-100 hover:file:bg-indigo-100 dark:hover:file:bg-gray-600" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG, PNG, WebP, or HEIC. Max 10MB. Shown on the guest app.</p>
                        <x-input-error :messages="$errors->get('couple_photo')" class="mt-2" />
                    </div>

                    {{-- Greeting audio (optional) --}}
                    <div>
                        <x-input-label for="greeting_audio" :value="__('Greeting audio (optional)')" />
                        <input id="greeting_audio" name="greeting_audio" type="file"
                               accept="audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/webm,audio/ogg"
                               class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 dark:file:bg-gray-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 dark:file:text-gray-100 hover:file:bg-indigo-100 dark:hover:file:bg-gray-600" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">MP3, WAV, M4A, WebM, or OGG. Max 20MB. Played on the guest app.</p>
                        <x-input-error :messages="$errors->get('greeting_audio')" class="mt-2" />
                    </div>

                    {{-- Plan tier --}}
                    <div>
                        <x-input-label for="plan_tier" :value="__('Plan tier')" />
                        <select id="plan_tier" name="plan_tier"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="basic" @selected(old('plan_tier', 'basic') === 'basic')>Basic</option>
                            <option value="premium" @selected(old('plan_tier') === 'premium')>Premium</option>
                        </select>
                        <x-input-error :messages="$errors->get('plan_tier')" class="mt-2" />
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        A unique slug and a 6-digit gallery PIN are generated automatically.
                    </p>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('events.index') }}"
                           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">Cancel</a>
                        <button type="submit"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            Create Event
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
