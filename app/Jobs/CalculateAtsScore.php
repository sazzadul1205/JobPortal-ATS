<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\ATSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateAtsScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        protected int $applicationId
    ) {}

    public function handle(ATSService $atsService): void
    {
        $application = Application::with('jobListing')->find($this->applicationId);

        if (! $application) {
            Log::warning('Application not found for ATS calculation', ['application_id' => $this->applicationId]);

            return;
        }

        if (! $application->jobListing) {
            Log::warning('Job listing not found for application', ['application_id' => $this->applicationId]);

            return;
        }

        $score = $atsService->calculateScore($application, $application->jobListing);

        $application->update([
            'ats_score' => $score,
            'matched_keywords' => $this->extractMatchedKeywords($score),
            'missing_keywords' => $this->extractMissingKeywords($score),
        ]);
    }

    protected function extractMatchedKeywords(array $score): array
    {
        if (! isset($score['analysis_details']['top_matched'])) {
            return [];
        }

        return $score['analysis_details']['top_matched'];
    }

    protected function extractMissingKeywords(array $score): array
    {
        if (! isset($score['analysis_details']['critical_missing'])) {
            return [];
        }

        return $score['analysis_details']['critical_missing'];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ATS score calculation failed', [
            'application_id' => $this->applicationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
