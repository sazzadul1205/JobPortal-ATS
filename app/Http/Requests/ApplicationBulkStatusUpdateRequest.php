<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationBulkStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['exists:applications,id'],
            'status' => ['required', Rule::in(['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'])],
            'employer_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'application_ids.required' => 'At least one application must be selected.',
            'application_ids.array' => 'Invalid application selection.',
            'application_ids.min' => 'At least one application must be selected.',
            'application_ids.*.exists' => 'One or more selected applications do not exist.',
            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status selected.',
        ];
    }
}
