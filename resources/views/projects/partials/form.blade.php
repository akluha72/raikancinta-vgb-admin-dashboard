{{-- Shared project fields for create + edit. Expects $project (nullable) and
     $events (collection of linkable events). --}}
<div>
    <x-input-label for="title" :value="__('Project title')" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                  :value="old('title', $project?->title)" required autofocus
                  placeholder="e.g. Wedding — Aisyah & Danial" />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
        <x-input-label for="stage" :value="__('Stage')" />
        <select id="stage" name="stage"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach (config('crm.pipeline') as $key => $meta)
                <option value="{{ $key }}" @selected(old('stage', $project?->stage ?? 'new') === $key)>{{ $meta['label'] }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('stage')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="value" :value="__('Value ('.config('crm.currency_symbol').')')" />
        <x-text-input id="value" name="value" type="number" step="0.01" min="0" class="mt-1 block w-full"
                      :value="old('value', $project?->value ?? '0')" required />
        <x-input-error :messages="$errors->get('value')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="cost" :value="__('Cost ('.config('crm.currency_symbol').')')" />
        <x-text-input id="cost" name="cost" type="number" step="0.01" min="0" class="mt-1 block w-full"
                      :value="old('cost', $project?->cost ?? '0')" required />
        <x-input-error :messages="$errors->get('cost')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="expected_close_date" :value="__('Expected close date (optional)')" />
        <x-text-input id="expected_close_date" name="expected_close_date" type="date" class="mt-1 block w-full"
                      :value="old('expected_close_date', $project?->expected_close_date?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('expected_close_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="event_id" :value="__('Linked guestbook event (optional)')" />
        <select id="event_id" name="event_id"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">— Not provisioned yet —</option>
            @foreach ($events as $event)
                <option value="{{ $event->id }}" @selected((string) old('event_id', $project?->event_id) === (string) $event->id)>
                    {{ $event->couple_name }}{{ $event->wedding_date ? ' ('.$event->wedding_date->format('Y-m-d').')' : '' }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Link this deal to the wedding event once you provision the guestbook.
            <a href="{{ route('events.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Create an event</a>.
        </p>
        <x-input-error :messages="$errors->get('event_id')" class="mt-2" />
    </div>
</div>

<div>
    <x-input-label for="notes" :value="__('Notes (optional)')" />
    <textarea id="notes" name="notes" rows="3"
              class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
              placeholder="Scope, package, deposit status…">{{ old('notes', $project?->notes) }}</textarea>
    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
</div>
