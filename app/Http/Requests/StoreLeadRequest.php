<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    /**
     * Public endpoint — the website capture form (raikancinta.com) posts here
     * before handing the visitor off to WhatsApp. Origin is restricted by CORS
     * (config/cors.php); no per-user authorization applies. Mirrors
     * StoreSubmissionRequest.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            // Free-form phone: the website form collects whatever the visitor
            // types; normalisation happens in prepareForValidation().
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'phone.required' => 'Please enter your phone number.',
            'email.email' => 'Please enter a valid email address.',
        ];
    }

    /**
     * Trim inputs and strip everything but digits and a leading "+" from the
     * phone, so the same person always dedups to one lead regardless of how
     * they typed spaces/dashes.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('name')) {
            $merge['name'] = trim((string) $this->input('name'));
        }

        if ($this->has('email')) {
            $email = trim((string) $this->input('email'));
            $merge['email'] = $email !== '' ? $email : null;
        }

        if ($this->has('phone')) {
            $raw = trim((string) $this->input('phone'));
            $plus = str_starts_with($raw, '+') ? '+' : '';
            $merge['phone'] = $plus.preg_replace('/\D+/', '', $raw);
        }

        $this->merge($merge);
    }
}
