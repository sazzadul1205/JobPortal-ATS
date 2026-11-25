<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobListingStoreRequest;
use App\Http\Requests\JobListingUpdateRequest;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class JobListingController extends Controller
{
    /**
     * Handle index request.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JobListing::class);

        $query = JobListing::with(['category', 'locations', 'user'])
            ->withTrashed()
            ->when(
                $request->input('search'),
                fn ($q, $search) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            )
            ->when(
                $request->input('category'),
                fn ($q, $category) => $q->where('category_id', $category)
            )
            ->when(
                $request->input('location'),
                fn ($q, $location) => $q->whereHas('locations', fn ($lq) => $lq->where('locations.id', $location))
            )
            ->when(
                $request->input('job_type'),
                fn ($q, $type) => $q->where('job_type', $type)
            )
            ->when(
                $request->input('experience_level'),
                fn ($q, $level) => $q->where('experience_level', $level)
            )
            ->when($request->input('status'), function ($q, $status) {
                if ((string) $status === 'active') {
                    $q->where('is_active', true);
                } elseif ((string) $status === 'inactive') {
                    $q->where('is_active', false);
                } elseif ((string) $status === 'trashed') {
                    $q->onlyTrashed();
                }
            })
            ->latest();

        $jobListings = $query->paginate(15)->withQueryString();

        return Inertia::render('Backend/JobListings/Index', [
            'jobListings' => $jobListings,
            'filters' => $request->only(['search', 'category', 'location', 'job_type', 'experience_level', 'status']),
            'categories' => JobCategory::where('is_active', true)->get(['id', 'name']),
            'locations' => Location::where('is_active', true)->get(['id', 'name']),
            'jobTypes' => ['full-time', 'part-time', 'contract', 'internship', 'remote', 'hybrid', 'freelance'],
            'experienceLevels' => ['entry', 'junior', 'mid', 'senior', 'lead', 'principal', 'executive', 'intern'],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    /**
     * Handle create request.
     */
    public function create(): Response
    {
        $this->authorize('create', JobListing::class);

        return Inertia::render('Backend/JobListings/Create', [
            'categories' => JobCategory::where('is_active', true)->get(['id', 'name']),
            'locations' => Location::where('is_active', true)->get(['id', 'name']),
            'jobTypes' => ['full-time', 'part-time', 'contract', 'internship', 'remote', 'hybrid', 'freelance'],
            'experienceLevels' => ['entry', 'junior', 'mid', 'senior', 'lead', 'principal', 'executive', 'intern'],
            'educationRequirements' => ['high-school', 'diploma', 'associate', 'bachelor', 'master', 'phd', 'any', 'none'],
        ]);
    }

    /**
     * Handle store request.
     */
    public function store(JobListingStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', JobListing::class);

        $validated = $request->validated();

        $slug = $this->generateUniqueSlug($validated['title']);

        $jobListing = JobListing::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'],
            'requirements' => $validated['requirements'],
            'category_id' => $validated['category_id'],
            'job_type' => $validated['job_type'],
            'experience_level' => $validated['experience_level'],
            'education_requirement' => $validated['education_requirement'] ?? null,
            'benefits' => $validated['benefits'] ?? [],
            'skills' => $validated['skills'] ?? [],
            'responsibilities' => $validated['responsibilities'] ?? [],
            'keywords' => $validated['keywords'] ?? [],
            'application_deadline' => $validated['application_deadline'],
            'is_active' => $this->determineInitialStatus($validated),
            'user_id' => Auth::id(),
        ]);

        $jobListing->locations()->attach($validated['location_id']);

        return redirect()->route('backend.listing.index')
            ->with('success', 'Job listing created successfully.');
    }

    /**
     * Handle show request.
     */
    public function show(JobListing $jobListing): Response
    {
        $this->authorize('view', $jobListing);

        $jobListing->load(['category', 'locations', 'user']);

        return Inertia::render('Backend/JobListings/Show', [
            'jobListing' => $jobListing,
            'auth' => [
                'user' => Auth::user(),
            ],
            'applicationsCount' => $jobListing->applications()->count(),
        ]);
    }

    /**
     * Handle edit request.
     */
    public function edit(JobListing $jobListing): Response
    {
        $this->authorize('update', $jobListing);

        $jobListing->load(['category', 'locations']);

        return Inertia::render('Backend/JobListings/Edit', [
            'jobListing' => $jobListing,
            'categories' => JobCategory::where('is_active', true)->get(['id', 'name']),
            'locations' => Location::where('is_active', true)->get(['id', 'name']),
            'jobTypes' => ['full-time', 'part-time', 'contract', 'internship', 'remote', 'hybrid', 'freelance'],
            'experienceLevels' => ['entry', 'junior', 'mid', 'senior', 'lead', 'principal', 'executive', 'intern'],
            'educationRequirements' => ['high-school', 'diploma', 'associate', 'bachelor', 'master', 'phd', 'any', 'none'],
        ]);
    }

    /**
     * Handle update request.
     */
    public function update(JobListingUpdateRequest $request, JobListing $jobListing): RedirectResponse
    {
        $this->authorize('update', $jobListing);

        $validated = $request->validated();

        $slug = $jobListing->slug;
        if ($jobListing->title !== $validated['title']) {
            $slug = $this->generateUniqueSlug($validated['title'], $jobListing->id);
        }

        $isActive = $this->determineStatusFromDates($validated);

        $jobListing->update([
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'],
            'requirements' => $validated['requirements'],
            'category_id' => $validated['category_id'],
            'job_type' => $validated['job_type'],
            'experience_level' => $validated['experience_level'],
            'education_requirement' => $validated['education_requirement'] ?? null,
            'benefits' => $validated['benefits'] ?? [],
            'skills' => $validated['skills'] ?? [],
            'responsibilities' => $validated['responsibilities'] ?? [],
            'keywords' => $validated['keywords'] ?? [],
            'application_deadline' => $validated['application_deadline'],
            'is_active' => $isActive,
        ]);

        $jobListing->locations()->sync($validated['location_id']);

        return redirect()->route('backend.listing.index')
            ->with('success', 'Job listing updated successfully.');
    }

    /**
     * Handle destroy request.
     */
    public function destroy(JobListing $jobListing): RedirectResponse
    {
        $this->authorize('delete', $jobListing);

        if ($jobListing->applications()->exists()) {
            return back()->with('error', 'Cannot delete job listing with existing applications.');
        }

        $jobListing->delete();

        return redirect()->route('backend.listing.index')
            ->with('success', 'Job listing deleted successfully.');
    }

    /**
     * Handle toggleActive request.
     */
    public function toggleActive(JobListing $jobListing): RedirectResponse
    {
        $this->authorize('update', $jobListing);

        $jobListing->update(['is_active' => ! $jobListing->is_active]);

        return back()->with('success', $jobListing->is_active ? 'Job listing activated.' : 'Job listing deactivated.');
    }

    /**
     * Handle applications request.
     */
    public function applications(JobListing $jobListing): Response
    {
        $this->authorize('viewApplications', $jobListing);

        $applications = $jobListing->applications()
            ->with(['user', 'applicantProfile'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Backend/JobListings/Applications', [
            'jobListing' => $jobListing,
            'applications' => $applications,
        ]);
    }

    /**
     * Handle publicIndex request.
     */
    public function publicIndex(Request $request): Response
    {
        $query = JobListing::with(['category', 'locations', 'user'])
            ->where('is_active', true)
            ->where('application_deadline', '>=', now())
            ->when(
                $request->input('search'),
                fn ($q, $search) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('locations', fn ($lq) => $lq->where('name', 'like', "%{$search}%"))
            )
            ->when(
                $request->input('category'),
                fn ($q, $category) => $q->where('category_id', $category)
            )
            ->when(
                $request->input('location'),
                fn ($q, $location) => $q->whereHas('locations', fn ($lq) => $lq->where('locations.id', $location))
            )
            ->when(
                $request->input('job_type'),
                fn ($q, $type) => $q->where('job_type', $type)
            )
            ->when(
                $request->input('experience_level'),
                fn ($q, $level) => $q->where('experience_level', $level)
            )
            ->latest();

        $jobs = $query->paginate(12)->withQueryString();

        return Inertia::render('jobs/index', [
            'jobs' => $jobs,
            'filters' => $request->only(['search', 'category', 'location', 'job_type', 'experience_level']),
            'categories' => JobCategory::where('is_active', true)->get(['id', 'name']),
            'locations' => Location::where('is_active', true)->get(['id', 'name']),
            'jobTypes' => ['full-time', 'part-time', 'contract', 'internship', 'remote', 'hybrid', 'freelance'],
            'experienceLevels' => ['entry', 'junior', 'mid', 'senior', 'lead', 'principal', 'executive', 'intern'],
            'userRole' => Auth::user()?->getPrimaryRole()?->slug ?? 'guest',
        ]);
    }

    /**
     * Handle publicShow request.
     */
    public function publicShow(JobListing $jobListing): Response
    {
        $jobListing->load(['category', 'locations', 'user']);

        $hasApplied = false;
        $canApply = false;

        if (Auth::check()) {
            $hasApplied = $jobListing->applications()->where('user_id', Auth::id())->exists();
            $canApply = Auth::user()->hasRole('job-seeker') && $jobListing->is_active && $jobListing->application_deadline >= now() && ! $hasApplied;
        }

        return Inertia::render('jobs/show', [
            'job' => $jobListing,
            'hasApplied' => $hasApplied,
            'canApply' => $canApply,
            'userRole' => Auth::user()?->getPrimaryRole()?->slug ?? 'guest',
        ]);
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

    /**
     * Handle updateJobStatuses request.
     */
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
}
