@props(['status'])

@php
    // Map the derived event status to a Tailwind colour scheme.
    $styles = [
        'Live' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'Upcoming' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'Past' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'Draft' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    ];
    $class = $styles[$status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {$class}"]) }}>
    {{ $status }}
</span>
