<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
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
            'lead_id' => ['required', Rule::exists(Lead::class, 'id')],
            'event_id' => ['nullable', Rule::exists(Event::class, 'id')],
            'title' => ['required', 'string', 'max:150'],
            'stage' => ['required', Rule::in(array_keys(config('crm.pipeline')))],
            'value' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'cost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
