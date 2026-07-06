@props(['stage'])

@php
    // Colour + label come from the configured pipeline (config/crm.php), so the
    // badge stays in sync with the funnel chart and progress bar.
    $meta = config("crm.pipeline.{$stage}");
    $class = $meta['color'] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
    $label = $meta['label'] ?? ucfirst($stage);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {$class}"]) }}>
    {{ $label }}
</span>
