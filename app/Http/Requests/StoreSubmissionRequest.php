<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

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
            //
            // iOS Safari/Chrome record AAC-in-MP4 (.m4a). PHP's finfo sniffs
            // those bytes as video/mp4 with a guessed extension of "mp4", so
            // we must allow the mp4/aac content forms here or valid iPhone
            // recordings 422. The bytes are valid AAC audio regardless.
            'audio' => [
                'required',
                'file',
                'max:20480',
                // Extensions Laravel guesses from CONTENT (iOS AAC-in-MP4 -> "mp4").
                'mimes:mp3,wav,m4a,webm,ogg,mp4,aac',
                // The real content types the sniffer / browser report.
                // video/mp4 is the one that catches iOS audio recordings.
                'mimetypes:audio/mpeg,audio/wav,audio/x-wav,audio/wave,audio/mp4,audio/aac,audio/x-m4a,audio/webm,audio/ogg,application/ogg,video/webm,video/mp4',
            ],

            // Stable per-device id the guest app sends so we can count
            // distinct participants ("Joined"). Optional and non-blocking:
            // never reject a submission over this field — sanitize instead
            // (see prepareForValidation()).
            'visitor_id' => ['nullable', 'string', 'max:64'],

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
            'audio.mimes' => 'The audio must be an mp3, wav, m4a, webm, ogg, or mp4 file.',
            'audio.mimetypes' => 'The audio must be an mp3, wav, m4a, webm, ogg, or mp4 file.',
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

        // visitor_id is best-effort device dedup, never a reason to reject a
        // submission. Truncate to the column width and null out anything that
        // isn't a usable string so validation can only pass or be ignored.
        if ($this->has('visitor_id')) {
            $raw = $this->input('visitor_id');
            $visitorId = is_string($raw) ? Str::limit(trim($raw), 64, '') : null;
            $this->merge(['visitor_id' => $visitorId !== '' ? $visitorId : null]);
        }
    }
}
