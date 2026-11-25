<?php

namespace App\Services;

use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationService
{
    public function getFilteredQuery(array $filters = [])
    {
        /** @var User $user */
        $user = Auth::user();
        $query = Application::with(['jobListing.category', 'jobListing.locations', 'user', 'applicantProfile']);

        if ($user->hasRole('job-seeker')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('employer')) {
            $query->whereHas('jobListing', fn($q) => $q->where('user_id', $user->id));
        }

        $query->when(
            $filters['status'] ?? null,
            fn($q, $status) => $status !== 'all' ? $q->where('status', $status) : $q
        );

        if ($user->hasRole('employer') || $user->hasRole('admin')) {
            $query->when(
                $filters['min_score'] ?? null,
                fn($q, $score) => $q->whereRaw('JSON_EXTRACT(ats_score, "$.percentage") >= ?', [$score])
            );
        }

        $query->when(
            $filters['search'] ?? null,
            fn($q, $search) => $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                ->orWhereHas('jobListing', fn($jq) => $jq->where('title', 'like', "%{$search}%"))
        );

        return $query->latest();
    }

    public function getIndexData(array $filters = []): array
    {
        /** @var User $user */
        $user = Auth::user();
        $query = $this->getFilteredQuery($filters);
        $applications = $query->paginate(15)->withQueryString();

        $statusCounts = $this->getStatusCounts($user);

        return [
            'applications' => $applications,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
            'userRole' => $user->getPrimaryRole()?->slug ?? 'guest',
        ];
    }

    protected function getStatusCounts(User $user): array
    {
        if ($user->hasRole('job-seeker')) {
            $baseQuery = Application::where('user_id', $user->id);
        } else {
            $baseQuery = Application::query();
            if ($user->hasRole('employer')) {
                $baseQuery->whereHas('jobListing', fn($q) => $q->where('user_id', $user->id));
            }
        }

        return [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'reviewed' => (clone $baseQuery)->where('status', 'reviewed')->count(),
            'shortlisted' => (clone $baseQuery)->where('status', 'shortlisted')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'hired' => (clone $baseQuery)->where('status', 'hired')->count(),
        ];
    }

    public function create(JobListing $jobListing, array $data, string $resumePath): Application
    {
        return Application::create([
            'job_listing_id' => $jobListing->id,
            'user_id' => Auth::id(),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'resume_path' => $resumePath,
            'expected_salary' => $data['expected_salary'] ?? null,
            'cover_letter' => $data['cover_letter'] ?? null,
            'status' => 'pending',
        ]);
    }

    public function updateStatus(Application $application, array $data): Application
    {
        $application->update([
            'status' => $data['status'],
            'employer_notes' => $data['employer_notes'] ?? null,
        ]);

        return $application;
    }

    public function bulkUpdateStatus(array $applicationIds, array $data, User $user): int
    {
        if ($user->hasRole('admin')) {
            // Admin can update all
        } elseif ($user->hasRole('employer')) {
            $allowedCount = Application::whereIn('id', $applicationIds)
                ->whereHas('jobListing', fn($q) => $q->where('user_id', $user->id))
                ->count();
            if ($allowedCount !== \count($applicationIds)) {
                abort(403, 'Unauthorized to update some of the selected applications.');
            }
        } else {
            abort(403, 'Unauthorized.');
        }

        $query = Application::whereIn('id', $applicationIds);

        if ($user->hasRole('employer')) {
            $query->whereHas('jobListing', fn($q) => $q->where('user_id', $user->id));
        }

        return $query->update([
            'status' => $data['status'],
            'employer_notes' => $data['employer_notes'] ?? null,
        ]);
    }

    public function getResumeDownload(Application $application): array|StreamedResponse
    {
        if (! $application->resume_path || ! Storage::disk('public')->exists($application->resume_path)) {
            return ['error' => 'Resume file not found.'];
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->download($application->resume_path);
    }

    public function delete(Application $application): bool
    {
        if ($application->resume_path && Storage::disk('public')->exists($application->resume_path)) {
            Storage::disk('public')->delete($application->resume_path);
        }

        $application->delete();

        return true;
    }

    public function recalculateScore(Application $application, ATSService $atsService): array
    {
        $application->load('jobListing');
        $score = $atsService->calculateScore($application, $application->jobListing);

        $application->update([
            'ats_score' => $score,
            'matched_keywords' => $score['analysis_details']['top_matched'] ?? [],
            'missing_keywords' => $score['analysis_details']['critical_missing'] ?? [],
        ]);

        return $score;
    }

    public function getATSStatsData(ATSService $atsService): array
    {
        return $atsService->getATSStatsData();
    }
}
