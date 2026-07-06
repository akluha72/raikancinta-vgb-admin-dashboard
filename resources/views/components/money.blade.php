@props(['amount' => 0, 'decimals' => 0])

{{-- Formats a money value with the configured currency symbol (RM by default). --}}
<span {{ $attributes }}>{{ config('crm.currency_symbol', 'RM') }} {{ number_format((float) $amount, $decimals) }}</span>
