<?php

namespace App\Http\Requests;

use App\Models\Application;
use App\Models\JobListing;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ApplicationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'job_seeker';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'expected_salary' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'cover_letter' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'resume.required' => 'Resume file is required.',
            'resume.file' => 'Resume must be a file.',
            'resume.mimes' => 'Resume must be a PDF, DOC, or DOCX file.',
            'resume.max' => 'Resume file size must not exceed 5 MB.',
        ];
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $validator->after(function ($validator) {
            $jobListingParam = $this->route('jobListing');
            $jobListingId = $jobListingParam instanceof JobListing ? $jobListingParam->id : $jobListingParam;
            $jobListing = JobListing::find($jobListingId);

            if (! $jobListing) {
                $validator->errors()->add('job_listing', 'Job listing not found.');

                return;
            }

            if (! $jobListing->is_active) {
                $validator->errors()->add('job_listing', 'This job is no longer accepting applications.');
            }

            if ($jobListing->application_deadline && Carbon::parse($jobListing->application_deadline)->lt(now()->startOfDay())) {
                $validator->errors()->add('job_listing', 'The application deadline has passed.');
            }

            $existingApplication = Application::where('job_listing_id', $jobListingId)
                ->where('user_id', $this->user()->id)
                ->first();

            if ($existingApplication) {
                $validator->errors()->add('duplicate', 'You have already applied to this job.');
            }
        });
    }
}
