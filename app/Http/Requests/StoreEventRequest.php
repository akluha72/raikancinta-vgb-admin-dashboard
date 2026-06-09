<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    /**
     * Any authenticated owner may create an event (single-owner MVP).
     * Route-level `auth` middleware already gates access.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating an event. Slug and PIN are generated
     * server-side and are intentionally not accepted from input.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'couple_name' => ['required', 'string', 'max:150'],
            'wedding_date' => ['nullable', 'date'],
            'plan_tier' => ['nullable', 'in:basic,premium'],
        ];
    }

    /**
     * Normalise input before validation: default the plan tier to "basic"
     * and trim the couple name.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'couple_name' => trim((string) $this->input('couple_name')),
            'plan_tier' => $this->input('plan_tier') ?: 'basic',
        ]);
    }
}
