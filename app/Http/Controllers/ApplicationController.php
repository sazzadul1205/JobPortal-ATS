<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplicationBulkStatusUpdateRequest;
use App\Http\Requests\ApplicationStatusUpdateRequest;
use App\Http\Requests\ApplicationStoreRequest;
use App\Jobs\CalculateAtsScore;
use App\Models\Application;
use App\Models\JobListing;
use App\Services\ATSService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    protected ATSService $atsService;

    /**
     * Handle __construct request.
     */
    public function __construct(ATSService $atsService)
    {
        $this->atsService = $atsService;
    }

    /**
     * Handle index request.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $query = Application::with(['jobListing.category', 'jobListing.locations', 'user', 'applicantProfile']);

        if ($user->hasRole('job-seeker')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('employer')) {
            $query->whereHas('jobListing', fn ($q) => $q->where('user_id', $user->id));
        }

        $query->when(
            $request->input('status'),
            fn ($q, $status) => $status !== 'all' ? $q->where('status', $status) : $q
        );

        if ($user->hasRole('employer') || $user->hasRole('admin')) {
            $query->when(
                $request->input('min_score'),
                fn ($q, $score) => $q->whereRaw('JSON_EXTRACT(ats_score, "$.percentage") >= ?', [$score])
            );
        }

        $query->when(
            $request->input('search'),
            fn ($q, $search) => $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                ->orWhereHas('jobListing', fn ($jq) => $jq->where('title', 'like', "%{$search}%"))
        );

        $applications = $query->latest()->paginate(15)->withQueryString();

        $filters = $request->only(['status', 'min_score', 'search']);

        if ($user->hasRole('job-seeker')) {
            $statusCounts = [
                'all' => Application::where('user_id', $user->id)->count(),
                'pending' => Application::where('user_id', $user->id)->where('status', 'pending')->count(),
                'reviewed' => Application::where('user_id', $user->id)->where('status', 'reviewed')->count(),
                'shortlisted' => Application::where('user_id', $user->id)->where('status', 'shortlisted')->count(),
                'rejected' => Application::where('user_id', $user->id)->where('status', 'rejected')->count(),
                'hired' => Application::where('user_id', $user->id)->where('status', 'hired')->count(),
            ];
        } else {
            $employerQuery = Application::query();
            if ($user->hasRole('employer')) {
                $employerQuery->whereHas('jobListing', fn ($q) => $q->where('user_id', $user->id));
            }
            $statusCounts = [
                'all' => (clone $employerQuery)->count(),
                'pending' => (clone $employerQuery)->where('status', 'pending')->count(),
                'reviewed' => (clone $employerQuery)->where('status', 'reviewed')->count(),
                'shortlisted' => (clone $employerQuery)->where('status', 'shortlisted')->count(),
                'rejected' => (clone $employerQuery)->where('status', 'rejected')->count(),
                'hired' => (clone $employerQuery)->where('status', 'hired')->count(),
            ];
        }

        return Inertia::render('applications/index', [
            'applications' => $applications,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
            'userRole' => $user->getPrimaryRole()?->slug ?? 'guest',
        ]);
    }

    /**
     * Handle create request.
     */
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

    /**
     * Handle store request.
     */
    public function store(ApplicationStoreRequest $request, JobListing $jobListing): RedirectResponse
    {
        $this->authorize('create', [Application::class, $jobListing]);

        $resume = $request->file('resume');
        $resumePath = $resume->store('resumes', 'public');

        $application = Application::create([
            'job_listing_id' => $jobListing->id,
            'user_id' => Auth::id(),
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'resume_path' => $resumePath,
            'expected_salary' => $request->expected_salary,
            'cover_letter' => $request->cover_letter,
            'status' => 'pending',
        ]);

        CalculateAtsScore::dispatch($application->id);

        return redirect()->route('backend.application.show', $application)
            ->with('success', 'Application submitted successfully. ATS score is being calculated.');
    }

    /**
     * Handle show request.
     */
    public function show(Application $application): Response
    {
        $this->authorize('view', $application);

        $application->load(['jobListing.category', 'jobListing.locations', 'user', 'applicantProfile']);

        return Inertia::render('applications/show', [
            'application' => $application,
            'userRole' => Auth::user()?->getPrimaryRole()?->slug ?? 'guest',
        ]);
    }

    /**
     * Handle updateStatus request.
     */
    public function updateStatus(ApplicationStatusUpdateRequest $request, Application $application): RedirectResponse
    {
        $this->authorize('updateStatus', $application);

        $application->update([
            'status' => $request->input('status'),
            'employer_notes' => $request->input('employer_notes'),
        ]);

        return back()->with('success', 'Application status updated successfully.');
    }

    /**
     * Handle bulkUpdateStatus request.
     */
    public function bulkUpdateStatus(ApplicationBulkStatusUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $applicationIds = $request->application_ids;

        // Manual authorization check for bulk update
        if ($user->hasRole('admin')) {
            // Admin can update all
        } elseif ($user->hasRole('employer')) {
            $allowedCount = Application::whereIn('id', $applicationIds)
                ->whereHas('jobListing', fn ($q) => $q->where('user_id', $user->id))
                ->count();
            if ($allowedCount !== \count($applicationIds)) {
                abort(403, 'Unauthorized to update some of the selected applications.');
            }
        } else {
            abort(403, 'Unauthorized.');
        }

        $query = Application::whereIn('id', $applicationIds);

        if ($user->hasRole('employer')) {
            $query->whereHas('jobListing', fn ($q) => $q->where('user_id', $user->id));
        }

        $updated = $query->update([
            'status' => $request->input('status'),
            'employer_notes' => $request->input('employer_notes'),
        ]);

        return back()->with('success', "{$updated} application(s) updated successfully.");
    }

    /**
     * Handle mergeResumes request.
     */
    public function mergeResumes(): Response
    {
        $this->authorize('mergeResumes', Application::class);

        return Inertia::render('applications/merge-resumes');
    }

    /**
     * Handle downloadResume request.
     */
    public function downloadResume(Application $application): RedirectResponse|StreamedResponse
    {
        $this->authorize('downloadResume', $application);

        if (! $application->resume_path || ! Storage::disk('public')->exists($application->resume_path)) {
            return back()->with('error', 'Resume file not found.');
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->download($application->resume_path);
    }

    /**
     * Handle destroy request.
     */
    public function destroy(Application $application): RedirectResponse
    {
        $this->authorize('delete', $application);

        if ($application->resume_path && Storage::disk('public')->exists($application->resume_path)) {
            Storage::disk('public')->delete($application->resume_path);
        }

        $application->delete();

        return redirect()->route('backend.application.index')
            ->with('success', 'Application deleted successfully.');
    }

    /**
     * Handle recalculateScore request.
     */
    public function recalculateScore(Application $application): RedirectResponse
    {
        $this->authorize('recalculateScore', $application);

        $application->load('jobListing');
        $score = $this->atsService->calculateScore($application, $application->jobListing);

        $application->update([
            'ats_score' => $score,
            'matched_keywords' => $score['analysis_details']['top_matched'] ?? [],
            'missing_keywords' => $score['analysis_details']['critical_missing'] ?? [],
        ]);

        return back()->with('success', 'ATS score recalculated successfully.');
    }

    /**
     * Handle analyticsDashboard request.
     */
    public function analyticsDashboard(): Response
    {
        $user = Auth::user();
        $stats = $this->atsService->getATSStatsData();

        return Inertia::render('analytics/dashboard', [
            'stats' => $stats,
            'userRole' => $user->getPrimaryRole()?->slug ?? 'guest',
        ]);
    }

    /**
     * Handle jobATSAnalysis request.
     */
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

    /**
     * Handle getATSStats request.
     */
    public function getATSStats(): JsonResponse
    {
        $user = Auth::user();

        if (! $user->hasRole('employer') && ! $user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = $this->atsService->getATSStatsData();

        return response()->json($stats);
    }
}
