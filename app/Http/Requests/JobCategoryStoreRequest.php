<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobCategoryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ($this->user()->hasRole('admin') || $this->user()->hasRole('employer'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:job_categories,name'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.unique' => 'A category with this name already exists.',
        ];
    }
}
