{{-- Shared lead fields for create + edit. Expects $lead (nullable). --}}
<div>
    <x-input-label for="name" :value="__('Name')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                  :value="old('name', $lead?->name)" required autofocus
                  placeholder="e.g. Nurul Aisyah" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="phone" :value="__('Phone (WhatsApp)')" />
    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                  :value="old('phone', $lead?->phone)" required
                  placeholder="e.g. 60123456789" />
    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
</div>

<div>
    <x-input-label for="email" :value="__('Email (optional)')" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                  :value="old('email', $lead?->email)"
                  placeholder="name@example.com" />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="source" :value="__('Source')" />
        <select id="source" name="source"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach (config('crm.lead_sources') as $key => $label)
                <option value="{{ $key }}" @selected(old('source', $lead?->source ?? 'manual') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('source')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" name="status"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach (config('crm.lead_statuses') as $key => $label)
                <option value="{{ $key }}" @selected(old('status', $lead?->status ?? 'new') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
</div>

<div>
    <x-input-label for="notes" :value="__('Notes (optional)')" />
    <textarea id="notes" name="notes" rows="3"
              class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
              placeholder="Anything worth remembering about this lead…">{{ old('notes', $lead?->notes) }}</textarea>
    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
</div>
