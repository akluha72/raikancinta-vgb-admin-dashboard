<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Click-to-Chat
    |--------------------------------------------------------------------------
    |
    | After a website visitor's lead is captured (POST /api/leads), the API
    | returns a wa.me URL the embed widget redirects to. The number is in
    | international format WITHOUT the leading "+" or spaces (e.g. a Malaysian
    | number 011-1234 5678 becomes 60111234 5678 -> "601112345678"). The message
    | is prefilled; {name} is replaced with the lead's name.
    |
    */

    'whatsapp_number' => env('WHATSAPP_NUMBER', ''),

    'whatsapp_message' => env(
        'WHATSAPP_MESSAGE',
        'Hi, saya {name}. Saya berminat dengan Raikan Cinta.'
    ),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | All project money values (value, cost, profit) are stored and displayed
    | in this currency. Malaysia Ringgit by default.
    |
    */

    'currency' => env('CRM_CURRENCY', 'MYR'),

    'currency_symbol' => env('CRM_CURRENCY_SYMBOL', 'RM'),

    /*
    |--------------------------------------------------------------------------
    | Project Pipeline Stages
    |--------------------------------------------------------------------------
    |
    | The ordered deal pipeline. Order matters: it drives the funnel chart and
    | the per-project progress bar (a stage's index / last-index = progress).
    | "won" and "lost" are terminal. Only "open" stages count toward the funnel
    | before a deal closes. Each stage has a Tailwind colour scheme (light +
    | dark) reused by the x-stage-badge component and the funnel chart palette.
    |
    | 'closed' => true marks a terminal stage (sets projects.closed_at on entry).
    | 'won'    => true marks the revenue-recognising stage (value counts as won).
    |
    */

    'pipeline' => [
        'new' => [
            'label' => 'New',
            'color' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
            'hex' => '#9ca3af',
        ],
        'contacted' => [
            'label' => 'Contacted',
            'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
            'hex' => '#60a5fa',
        ],
        'quote' => [
            'label' => 'Quote Sent',
            'color' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
            'hex' => '#818cf8',
        ],
        'negotiation' => [
            'label' => 'Negotiation',
            'color' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
            'hex' => '#fbbf24',
        ],
        'won' => [
            'label' => 'Won',
            'color' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
            'hex' => '#34d399',
            'closed' => true,
            'won' => true,
        ],
        'lost' => [
            'label' => 'Lost',
            'color' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
            'hex' => '#f87171',
            'closed' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead Sources & Statuses
    |--------------------------------------------------------------------------
    |
    | Sources feed the "lead source breakdown" chart. "website" is what the
    | public capture endpoint records. Statuses are the lightweight lead
    | lifecycle shown on the leads list (distinct from per-project stages).
    |
    */

    'lead_sources' => [
        'website' => 'Website',
        'whatsapp' => 'WhatsApp',
        'referral' => 'Referral',
        'manual' => 'Manual',
    ],

    'lead_statuses' => [
        'new' => 'New',
        'contacted' => 'Contacted',
        'qualified' => 'Qualified',
        'won' => 'Won',
        'lost' => 'Lost',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead Capture De-duplication Window
    |--------------------------------------------------------------------------
    |
    | A second submission from the same phone number within this many minutes
    | reuses the existing lead instead of creating a duplicate (guards against
    | double-taps / retries on the website form).
    |
    */

    'dedup_window_minutes' => (int) env('CRM_DEDUP_WINDOW_MINUTES', 60),

];
