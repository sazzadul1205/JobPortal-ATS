<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplicationBulkStatusUpdateRequest;
use App\Http\Requests\ApplicationStatusUpdateRequest;
use App\Http\Requests\ApplicationStoreRequest;
use App\Jobs\CalculateAtsScore;
use App\Models\Application;
use App\Models\JobListing;
use App\Services\ATSService;
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

    public function __construct(ATSService $atsService)
    {
        $this->atsService = $atsService;
    }

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $query = Application::with(['jobListing.category', 'jobListing.location', 'user', 'applicantProfile']);

        if ($user->role === 'job_seeker') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'employer') {
            $query->whereHas('jobListing', fn ($q) => $q->where('user_id', $user->id));
        }

        $query->when($request->input('status'), fn ($q, $status) => $status !== 'all' ? $q->where('status', $status) : $q
        );

        if ($user->role === 'employer' || $user->role === 'admin') {
            $query->when($request->input('min_score'), fn ($q, $score) => $q->whereRaw('JSON_EXTRACT(ats_score, "$.percentage") >= ?', [$score])
            );
        }

        $query->when($request->input('search'), fn ($q, $search) => $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
            ->orWhereHas('jobListing', fn ($jq) => $jq->where('title', 'like', "%{$search}%"))
        );

        $applications = $query->latest()->paginate(15)->withQueryString();

        $filters = $request->only(['status', 'min_score', 'search']);

        if ($user->role === 'job_seeker') {
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
            if ($user->role === 'employer') {
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
            'userRole' => $user->role,
        ]);
    }

    public function create(JobListing $jobListing): Response
    {
        $this->authorize('viewCreate', [Application::class, $jobListing]);

        $hasApplied = $jobListing->applications()->where('user_id', Auth::id())->exists();

        $jobListing->load(['category', 'location']);

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

    public function show(Application $application): Response
    {
        $this->authorize('view', $application);

        $application->load(['jobListing.category', 'jobListing.location', 'user', 'applicantProfile']);

        return Inertia::render('applications/show', [
            'application' => $application,
            'userRole' => Auth::user()->role,
        ]);
    }

    public function updateStatus(ApplicationStatusUpdateRequest $request, Application $application): RedirectResponse
    {
        $this->authorize('updateStatus', $application);

        $application->update([
            'status' => $request->status,
            'employer_notes' => $request->employer_notes,
        ]);

        return back()->with('success', 'Application status updated successfully.');
    }

    public function bulkUpdateStatus(ApplicationBulkStatusUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $applicationIds = $request->application_ids;

        // Manual authorization check for bulk update
        if ($user->role === 'admin') {
            // Admin can update all
        } elseif ($user->role === 'employer') {
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

        if ($user->role === 'employer') {
            $query->whereHas('jobListing', fn ($q) => $q->where('user_id', $user->id));
        }

        $updated = $query->update([
            'status' => $request->status,
            'employer_notes' => $request->employer_notes,
        ]);

        return back()->with('success', "{$updated} application(s) updated successfully.");
    }

    public function mergeResumes(Request $request): Response
    {
        $this->authorize('mergeResumes', Application::class);

        return Inertia::render('applications/merge-resumes');
    }

    public function downloadResume(Application $application): RedirectResponse|StreamedResponse
    {
        $this->authorize('downloadResume', $application);

        if (! $application->resume_path || ! Storage::disk('public')->exists($application->resume_path)) {
            return back()->with('error', 'Resume file not found.');
        }

        return Storage::disk('public')->download($application->resume_path);
    }

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

    public function analyticsDashboard(): Response
    {
        $user = Auth::user();
        $stats = $this->atsService->getATSStatsData();

        return Inertia::render('analytics/dashboard', [
            'stats' => $stats,
            'userRole' => $user->role,
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

        if ($user->role !== 'employer' && $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = $this->atsService->getATSStatsData();

        return response()->json($stats);
    }
}
