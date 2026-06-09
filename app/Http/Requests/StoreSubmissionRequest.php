<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    /**
     * Public endpoint — anyone with a valid event slug may submit. Event
     * scoping is enforced by the slug→event lookup in the controller, never
     * by trusting client input, so no per-user authorization is needed here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Server-side validation of file mime types and sizes (max sizes in KB).
     * Audio is required (the product's emotional core); photo is optional.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guest_name' => ['required', 'string', 'max:100'],

            // ~20MB. mimetypes checks the real content type, not just the
            // extension, so a renamed file is rejected.
            'audio' => [
                'required',
                'file',
                'max:20480',
                'mimetypes:audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/webm,audio/ogg,video/webm',
            ],

            // ~10MB, optional. HEIC included for iPhone uploads.
            'photo' => [
                'nullable',
                'file',
                'max:10240',
                'mimetypes:image/jpeg,image/png,image/webp,image/heic,image/heif',
            ],

            'guest_message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'audio.required' => 'A voice recording is required.',
            'audio.mimetypes' => 'The audio must be an mp3, wav, m4a, webm, or ogg file.',
            'audio.max' => 'The audio file may not be larger than 20MB.',
            'photo.mimetypes' => 'The photo must be a jpg, png, webp, or heic image.',
            'photo.max' => 'The photo may not be larger than 10MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('guest_name')) {
            $this->merge(['guest_name' => trim((string) $this->input('guest_name'))]);
        }
    }
}
