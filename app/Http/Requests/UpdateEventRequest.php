<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Any authenticated owner may edit an event (single-owner MVP).
     * Route-level `auth` middleware already gates access.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for editing an event. Slug and PIN are managed
     * elsewhere and are intentionally not accepted here. The media files are
     * optional on update — omitting one leaves the existing file untouched.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'couple_name' => ['required', 'string', 'max:150'],
            'wedding_date' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'plan_tier' => ['nullable', 'in:basic,premium'],

            'couple_photo' => [
                'nullable',
                'file',
                'max:10240',
                'mimetypes:image/jpeg,image/png,image/webp,image/heic,image/heif',
            ],
            'greeting_audio' => [
                'nullable',
                'file',
                'max:20480',
                'mimetypes:audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/webm,audio/ogg,video/webm',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'couple_photo.mimetypes' => 'The couple photo must be a jpg, png, webp, or heic image.',
            'couple_photo.max' => 'The couple photo may not be larger than 10MB.',
            'greeting_audio.mimetypes' => 'The greeting audio must be an mp3, wav, m4a, webm, or ogg file.',
            'greeting_audio.max' => 'The greeting audio may not be larger than 20MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'couple_name' => trim((string) $this->input('couple_name')),
            'plan_tier' => $this->input('plan_tier') ?: 'basic',
        ]);
    }
}
