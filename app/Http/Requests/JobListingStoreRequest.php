<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobListingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:job_categories,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'job_type' => ['required', Rule::in(['full-time', 'part-time', 'contract', 'internship', 'remote', 'hybrid', 'freelance'])],
            'experience_level' => ['required', Rule::in(['entry', 'junior', 'mid', 'senior', 'lead', 'principal', 'executive', 'intern'])],
            'education_requirement' => ['nullable', Rule::in(['high-school', 'diploma', 'associate', 'bachelor', 'master', 'phd', 'any', 'none'])],
            'application_deadline' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addYear()->format('Y-m-d')],
            'description' => ['required', 'string', 'min:50'],
            'requirements' => ['required', 'string', 'min:50'],
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['string', 'max:100'],
            'responsibilities' => ['required', 'array', 'min:1'],
            'responsibilities.*' => ['string', 'max:255'],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['string', 'max:255'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Job title is required.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'location_id.required' => 'Location is required.',
            'location_id.exists' => 'Selected location does not exist.',
            'job_type.required' => 'Job type is required.',
            'job_type.in' => 'Invalid job type selected.',
            'experience_level.required' => 'Experience level is required.',
            'experience_level.in' => 'Invalid experience level selected.',
            'application_deadline.required' => 'Application deadline is required.',
            'application_deadline.after_or_equal' => 'Application deadline must be today or in the future.',
            'application_deadline.before_or_equal' => 'Application deadline cannot be more than 1 year ahead.',
            'description.required' => 'Job description is required.',
            'description.min' => 'Job description must be at least 50 characters.',
            'requirements.required' => 'Job requirements are required.',
            'requirements.min' => 'Job requirements must be at least 50 characters.',
            'skills.required' => 'At least one skill is required.',
            'skills.min' => 'At least one skill is required.',
            'responsibilities.required' => 'At least one responsibility is required.',
            'responsibilities.min' => 'At least one responsibility is required.',
        ];
    }
}
