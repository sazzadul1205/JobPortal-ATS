<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplicationBulkStatusUpdateRequest;
use App\Http\Requests\ApplicationStatusUpdateRequest;
use App\Http\Requests\ApplicationStoreRequest;
use App\Jobs\CalculateAtsScore;
use App\Models\Application;
use App\Models\JobListing;
use App\Services\ApplicationService;
use App\Services\ATSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    protected ApplicationService $applicationService;

    protected ATSService $atsService;

    public function __construct(ApplicationService $applicationService, ATSService $atsService)
    {
        $this->applicationService = $applicationService;
        $this->atsService = $atsService;
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Application::class);

        $data = $this->applicationService->getIndexData($request->only(['status', 'min_score', 'search']));

        return Inertia::render('applications/index', $data);
    }

    public function create(JobListing $jobListing): Response
    {
        $this->authorize('viewCreate', [Application::class, $jobListing]);

        $hasApplied = $jobListing->applications()->where('user_id', Auth::id())->exists();

        $jobListing->load(['category', 'locations']);

        return Inertia::render('applications/create', [
            'job' => $jobListing,
            'hasApplied' => $hasApplied,
            'canApply' => ! $hasApplied && $jobListing->is_active && $jobListing->application_deadline >= now(),
        ]);
    }

    public function store(ApplicationStoreRequest $request, JobListing $jobListing): RedirectResponse
    {
        $this->authorize('create', [Application::class, $jobListing]);

        $resume = $request->file('resume');
        $resumePath = $resume->store('resumes', 'public');

        $application = $this->applicationService->create($jobListing, $request->validated(), $resumePath);

        CalculateAtsScore::dispatch($application->id);

        return redirect()->route('backend.application.show', $application)
            ->with('success', 'Application submitted successfully. ATS score is being calculated.');
    }

    public function show(Application $application): Response
    {
        $this->authorize('view', $application);

        $application->load(['jobListing.category', 'jobListing.locations', 'user', 'applicantProfile']);

        return Inertia::render('applications/show', [
            'application' => $application,
            'userRole' => Auth::user()?->getPrimaryRole()?->slug ?? 'guest',
        ]);
    }

    public function updateStatus(ApplicationStatusUpdateRequest $request, Application $application): RedirectResponse
    {
        $this->authorize('updateStatus', $application);

        $this->applicationService->updateStatus($application, $request->validated());

        return back()->with('success', 'Application status updated successfully.');
    }

    public function bulkUpdateStatus(ApplicationBulkStatusUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $updated = $this->applicationService->bulkUpdateStatus(
            $request->application_ids,
            $request->validated(),
            $user
        );

        return back()->with('success', "{$updated} application(s) updated successfully.");
    }

    public function mergeResumes(): Response
    {
        $this->authorize('mergeResumes', Application::class);

        return Inertia::render('applications/merge-resumes');
    }

    public function downloadResume(Application $application): RedirectResponse|StreamedResponse
    {
        $this->authorize('downloadResume', $application);

        $result = $this->applicationService->getResumeDownload($application);

        if (is_array($result)) {
            return back()->with('error', $result['error']);
        }

        return $result;
    }

    public function destroy(Application $application): RedirectResponse
    {
        $this->authorize('delete', $application);

        $this->applicationService->delete($application);

        return redirect()->route('backend.application.index')
            ->with('success', 'Application deleted successfully.');
    }

    public function recalculateScore(Application $application): RedirectResponse
    {
        $this->authorize('recalculateScore', $application);

        $this->applicationService->recalculateScore($application, $this->atsService);

        return back()->with('success', 'ATS score recalculated successfully.');
    }

    public function analyticsDashboard(): Response
    {
        $user = Auth::user();
        $stats = $this->applicationService->getATSStatsData($this->atsService);

        return Inertia::render('analytics/dashboard', [
            'stats' => $stats,
            'userRole' => $user->getPrimaryRole()?->slug ?? 'guest',
        ]);
    }

    public function jobATSAnalysis(JobListing $jobListing): Response
    {
        $this->authorize('viewApplications', $jobListing);

        $keywordAnalysis = $this->atsService->getKeywordAnalysisData($jobListing);
        $topCandidates = $this->atsService->getTopCandidatesData($jobListing);

        return Inertia::render('analytics/job-analysis', [
            'jobListing' => $jobListing,
            'keywordAnalysis' => $keywordAnalysis,
            'topCandidates' => $topCandidates,
        ]);
    }

    public function getATSStats(): JsonResponse
    {
        $user = Auth::user();

        if (! $user->hasRole('employer') && ! $user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = $this->applicationService->getATSStatsData($this->atsService);

        return response()->json($stats);
    }
}
