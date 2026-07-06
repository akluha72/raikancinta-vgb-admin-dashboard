<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadOwnerRequest extends FormRequest
{
    /**
     * Owner-side lead create/update (behind auth middleware). Distinct from the
     * public StoreLeadRequest: here the owner picks source/status from the
     * configured lists.
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
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'source' => ['required', Rule::in(array_keys(config('crm.lead_sources')))],
            'status' => ['required', Rule::in(array_keys(config('crm.lead_statuses')))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
