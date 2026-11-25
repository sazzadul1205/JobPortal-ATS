<?php

namespace App\Services;

use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class JobListingService
{
    public function getFilteredQuery(array $filters = [])
    {
        return JobListing::with(['category', 'locations', 'user'])
            ->withTrashed()
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category_id', $category))
            ->when($filters['location'] ?? null, fn ($q, $location) => $q->whereHas('locations', fn ($lq) => $lq->where('locations.id', $location)))
            ->when($filters['job_type'] ?? null, fn ($q, $type) => $q->where('job_type', $type))
            ->when($filters['experience_level'] ?? null, fn ($q, $level) => $q->where('experience_level', $level))
            ->when($filters['status'] ?? null, function ($q, $status) {
                if ((string) $status === 'active') {
                    $q->where('is_active', true);
                } elseif ((string) $status === 'inactive') {
                    $q->where('is_active', false);
                } elseif ((string) $status === 'trashed') {
                    $q->onlyTrashed();
                }
            })
            ->latest();
    }

    public function getIndexData(array $filters = []): array
    {
        $query = $this->getFilteredQuery($filters);
        $jobListings = $query->paginate(15)->withQueryString();

        return [
            'jobListings' => $jobListings,
            'filters' => $filters,
            'categories' => JobCategory::where('is_active', true)->get(['id', 'name']),
            'locations' => Location::where('is_active', true)->get(['id', 'name']),
            'jobTypes' => ['full-time', 'part-time', 'contract', 'internship', 'remote', 'hybrid', 'freelance'],
            'experienceLevels' => ['entry', 'junior', 'mid', 'senior', 'lead', 'principal', 'executive', 'intern'],
            'educationRequirements' => ['high-school', 'diploma', 'associate', 'bachelor', 'master', 'phd', 'any', 'none'],
        ];
    }

    public function create(array $data): JobListing
    {
        $slug = $this->generateUniqueSlug($data['title']);

        $jobListing = JobListing::create([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'],
            'requirements' => $data['requirements'],
            'category_id' => $data['category_id'],
            'job_type' => $data['job_type'],
            'experience_level' => $data['experience_level'],
            'education_requirement' => $data['education_requirement'] ?? null,
            'benefits' => $data['benefits'] ?? [],
            'skills' => $data['skills'] ?? [],
            'responsibilities' => $data['responsibilities'] ?? [],
            'keywords' => $data['keywords'] ?? [],
            'application_deadline' => $data['application_deadline'],
            'is_active' => $this->determineInitialStatus($data),
            'user_id' => Auth::id(),
        ]);

        $jobListing->locations()->attach($data['location_id']);

        return $jobListing;
    }

    public function update(JobListing $jobListing, array $data): JobListing
    {
        $slug = $jobListing->slug;
        if ($jobListing->title !== $data['title']) {
            $slug = $this->generateUniqueSlug($data['title'], $jobListing->id);
        }

        $isActive = $this->determineStatusFromDates($data);

        $jobListing->update([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'],
            'requirements' => $data['requirements'],
            'category_id' => $data['category_id'],
            'job_type' => $data['job_type'],
            'experience_level' => $data['experience_level'],
            'education_requirement' => $data['education_requirement'] ?? null,
            'benefits' => $data['benefits'] ?? [],
            'skills' => $data['skills'] ?? [],
            'responsibilities' => $data['responsibilities'] ?? [],
            'keywords' => $data['keywords'] ?? [],
            'application_deadline' => $data['application_deadline'],
            'is_active' => $isActive,
        ]);

        $jobListing->locations()->sync($data['location_id']);

        return $jobListing;
    }

    public function delete(JobListing $jobListing): bool
    {
        if ($jobListing->applications()->exists()) {
            return false;
        }

        $jobListing->delete();

        return true;
    }

    public function toggleActive(JobListing $jobListing): JobListing
    {
        $jobListing->update(['is_active' => ! $jobListing->is_active]);

        return $jobListing;
    }

    public function getApplications(JobListing $jobListing)
    {
        return $jobListing->applications()
            ->with(['user', 'applicantProfile'])
            ->latest()
            ->paginate(20);
    }

    public function getPublicFilteredQuery(array $filters = [])
    {
        return JobListing::with(['category', 'locations', 'user'])
            ->where('is_active', true)
            ->where('application_deadline', '>=', now())
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('locations', fn ($lq) => $lq->where('name', 'like', "%{$search}%")))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category_id', $category))
            ->when($filters['location'] ?? null, fn ($q, $location) => $q->whereHas('locations', fn ($lq) => $lq->where('locations.id', $location)))
            ->when($filters['job_type'] ?? null, fn ($q, $type) => $q->where('job_type', $type))
            ->when($filters['experience_level'] ?? null, fn ($q, $level) => $q->where('experience_level', $level))
            ->latest();
    }

    public function getPublicIndexData(array $filters = []): array
    {
        $query = $this->getPublicFilteredQuery($filters);
        $jobs = $query->paginate(12)->withQueryString();

        return [
            'jobs' => $jobs,
            'filters' => $filters,
            'categories' => JobCategory::where('is_active', true)->get(['id', 'name']),
            'locations' => Location::where('is_active', true)->get(['id', 'name']),
            'jobTypes' => ['full-time', 'part-time', 'contract', 'internship', 'remote', 'hybrid', 'freelance'],
            'experienceLevels' => ['entry', 'junior', 'mid', 'senior', 'lead', 'principal', 'executive', 'intern'],
        ];
    }

    public function getPublicShowData(JobListing $jobListing, ?int $userId = null): array
    {
        $hasApplied = false;
        $canApply = false;

        if ($userId) {
            $hasApplied = $jobListing->applications()->where('user_id', $userId)->exists();
            $user = User::find($userId);
            $canApply = $user ? $user->hasRole('job-seeker') && $jobListing->is_active && $jobListing->application_deadline >= now() && ! $hasApplied : false;
        }

        return [
            'jobListing' => $jobListing,
            'hasApplied' => $hasApplied,
            'canApply' => $canApply,
        ];
    }

    public function updateJobStatuses(): void
    {
        JobListing::where('is_active', false)
            ->whereNotNull('schedule_start_date')
            ->where('schedule_start_date', '<=', now())
            ->where('application_deadline', '>', now())
            ->update(['is_active' => true]);

        JobListing::where('is_active', true)
            ->where('application_deadline', '<', now())
            ->update(['is_active' => false]);
    }

    protected function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (JobListing::where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function determineInitialStatus(array $data): bool
    {
        $deadline = Carbon::parse($data['application_deadline']);
        $startDate = isset($data['schedule_start_date']) ? Carbon::parse($data['schedule_start_date']) : null;

        if ($startDate && $startDate->isFuture()) {
            return false;
        }

        if ($deadline->isPast()) {
            return false;
        }

        return true;
    }

    protected function determineStatusFromDates(array $data): bool
    {
        $deadline = Carbon::parse($data['application_deadline']);
        $startDate = isset($data['schedule_start_date']) && $data['schedule_start_date']
            ? Carbon::parse($data['schedule_start_date'])
            : null;

        if ($startDate && $startDate->isFuture()) {
            return false;
        }

        if ($deadline->isPast()) {
            return false;
        }

        return true;
    }
}
