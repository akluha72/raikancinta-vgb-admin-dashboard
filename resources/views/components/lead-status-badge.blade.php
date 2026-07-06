@props(['status'])

@php
    // Lead lifecycle colours (keys match config/crm.php lead_statuses).
    $styles = [
        'new' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'contacted' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
        'qualified' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'won' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'lost' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    ];
    $class = $styles[$status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
    $label = config("crm.lead_statuses.{$status}", ucfirst($status));
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {$class}"]) }}>
    {{ $label }}
</span>
