<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class LeadCaptureController extends Controller
{
    /**
     * Capture a website lead, then hand back a WhatsApp click-to-chat URL the
     * embed widget redirects the visitor to. The whole point of the feature:
     * the lead is saved to the CRM *before* the WhatsApp conversation starts,
     * so nothing is keyed in by hand.
     *
     * De-dup: a repeat submission from the same phone within the configured
     * window reuses the existing lead (guards double-taps / retries) rather
     * than creating a duplicate row.
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $data = $request->validated();

        $window = (int) Config::get('crm.dedup_window_minutes', 60);

        $lead = Lead::query()
            ->where('phone', $data['phone'])
            ->where('created_at', '>=', now()->subMinutes($window))
            ->latest('id')
            ->first();

        if ($lead) {
            // Fill in details the visitor may have added on a second try, but
            // never downgrade a known status back to "new".
            $lead->fill([
                'name' => $data['name'] ?: $lead->name,
                'email' => $data['email'] ?? $lead->email,
            ])->save();
        } else {
            $lead = Lead::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'source' => 'website',
                'status' => 'new',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lead captured',
            'whatsapp_url' => $this->whatsappUrl($lead),
        ], 201);
    }

    /**
     * Build the wa.me deep link from config: the business number plus a
     * prefilled message with the lead's name interpolated. Returns null when
     * no WhatsApp number is configured (the widget then just closes).
     */
    private function whatsappUrl(Lead $lead): ?string
    {
        $number = preg_replace('/\D+/', '', (string) Config::get('crm.whatsapp_number', ''));

        if ($number === '') {
            return null;
        }

        $template = (string) Config::get('crm.whatsapp_message', '');
        $message = Str::of($template)->replace('{name}', $lead->name)->toString();

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}
