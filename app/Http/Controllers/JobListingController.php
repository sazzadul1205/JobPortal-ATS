<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobListingStoreRequest;
use App\Http\Requests\JobListingUpdateRequest;
use App\Models\JobListing;
use App\Models\User;
use App\Services\JobListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class JobListingController extends Controller
{
    protected JobListingService $jobListingService;

    public function __construct(JobListingService $jobListingService)
    {
        $this->jobListingService = $jobListingService;
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JobListing::class);

        $data = $this->jobListingService->getIndexData($request->only([
            'search', 'category', 'location', 'job_type', 'experience_level', 'status',
        ]));

        $data['flash'] = [
            'success' => session('success'),
            'error' => session('error'),
        ];

        return Inertia::render('Backend/JobListings/Index', $data);
    }

    public function create(): Response
    {
        $this->authorize('create', JobListing::class);

        $data = $this->jobListingService->getIndexData();

        return Inertia::render('Backend/JobListings/Create', [
            'categories' => $data['categories'],
            'locations' => $data['locations'],
            'jobTypes' => $data['jobTypes'],
            'experienceLevels' => $data['experienceLevels'],
            'educationRequirements' => $data['educationRequirements'],
        ]);
    }

    public function store(JobListingStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', JobListing::class);

        $this->jobListingService->create($request->validated());

        return redirect()->route('backend.listing.index')
            ->with('success', 'Job listing created successfully.');
    }

    public function show(JobListing $jobListing): Response
    {
        $this->authorize('view', $jobListing);

        $jobListing->load(['category', 'locations', 'user']);

        /** @var User|null $authUser */
        $authUser = Auth::user();

        return Inertia::render('Backend/JobListings/Show', [
            'jobListing' => $jobListing,
            'auth' => [
                'user' => $authUser,
            ],
            'applicationsCount' => $jobListing->applications()->count(),
        ]);
    }

    public function edit(JobListing $jobListing): Response
    {
        $this->authorize('update', $jobListing);

        $jobListing->load(['category', 'locations']);
        $data = $this->jobListingService->getIndexData();

        return Inertia::render('Backend/JobListings/Edit', [
            'jobListing' => $jobListing,
            'categories' => $data['categories'],
            'locations' => $data['locations'],
            'jobTypes' => $data['jobTypes'],
            'experienceLevels' => $data['experienceLevels'],
            'educationRequirements' => $data['educationRequirements'],
        ]);
    }

    public function update(JobListingUpdateRequest $request, JobListing $jobListing): RedirectResponse
    {
        $this->authorize('update', $jobListing);

        $this->jobListingService->update($jobListing, $request->validated());

        return redirect()->route('backend.listing.index')
            ->with('success', 'Job listing updated successfully.');
    }

    public function destroy(JobListing $jobListing): RedirectResponse
    {
        $this->authorize('delete', $jobListing);

        if (! $this->jobListingService->delete($jobListing)) {
            return back()->with('error', 'Cannot delete job listing with existing applications.');
        }

        return redirect()->route('backend.listing.index')
            ->with('success', 'Job listing deleted successfully.');
    }

    public function toggleActive(JobListing $jobListing): RedirectResponse
    {
        $this->authorize('update', $jobListing);

        $this->jobListingService->toggleActive($jobListing);

        return back()->with('success', $jobListing->is_active ? 'Job listing activated.' : 'Job listing deactivated.');
    }

    public function applications(JobListing $jobListing): Response
    {
        $this->authorize('viewApplications', $jobListing);

        $applications = $this->jobListingService->getApplications($jobListing);

        return Inertia::render('Backend/JobListings/Applications', [
            'jobListing' => $jobListing,
            'applications' => $applications,
        ]);
    }

    public function publicIndex(Request $request): Response
    {
        $data = $this->jobListingService->getPublicIndexData($request->only([
            'search', 'category', 'location', 'job_type', 'experience_level',
        ]));

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $data['userRole'] = $authUser?->getPrimaryRole()?->slug ?? 'guest';

        return Inertia::render('jobs/index', $data);
    }

    public function publicShow(JobListing $jobListing): Response
    {
        $jobListing->load(['category', 'locations', 'user']);

        $data = $this->jobListingService->getPublicShowData($jobListing, Auth::id());

        /** @var User|null $authUser */
        $authUser = Auth::user();

        return Inertia::render('jobs/show', [
            'job' => $jobListing,
            'hasApplied' => $data['hasApplied'],
            'canApply' => $data['canApply'],
            'userRole' => $authUser?->getPrimaryRole()?->slug ?? 'guest',
        ]);
    }

    public function updateJobStatuses(): void
    {
        $this->jobListingService->updateJobStatuses();
    }
}
