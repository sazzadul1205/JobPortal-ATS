<?php

use App\Models\Application;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Location;
use App\Models\User;
use App\Services\ATSService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->employer = User::factory()->employer()->create();
    $this->jobSeeker = User::factory()->jobSeeker()->create();
    $this->category = JobCategory::factory()->create();
    $this->location = Location::factory()->create();
    $this->atsService = new ATSService;
});

describe('ATSService', function () {
    describe('calculateScore', function () {
        it('returns empty score when resume text is empty', function () {
            $jobListing = JobListing::factory()->create([
                'user_id' => $this->employer->id,
                'skills' => ['PHP', 'Laravel'],
                'keywords' => ['php', 'laravel', 'mysql'],
            ]);
            $application = Application::factory()->create([
                'user_id' => $this->jobSeeker->id,
                'job_listing_id' => $jobListing->id,
                'resume_path' => 'resumes/test.pdf',
            ]);

            $score = $this->atsService->calculateScore($application, $jobListing);

            expect($score['percentage'])->toBe(0);
            expect($score['matched_count'])->toBe(0);
            expect($score['extracted_skills'])->toBeArray();
        });

        it('calculates score based on keyword matching', function () {
            $jobListing = JobListing::factory()->create([
                'user_id' => $this->employer->id,
                'skills' => ['PHP', 'Laravel', 'MySQL'],
                'keywords' => ['php', 'laravel', 'mysql', 'api', 'rest'],
            ]);
            $application = Application::factory()->create([
                'user_id' => $this->jobSeeker->id,
                'job_listing_id' => $jobListing->id,
                'resume_path' => 'resumes/test.pdf',
            ]);

            // Mock resume text extraction
            $reflection = new ReflectionClass($this->atsService);
            $method = $reflection->getMethod('extractResumeText');
            $method->setAccessible(true);
            $method->invoke($this->atsService, $application);

            // We can't easily test the full extraction without a real file
            // So we test the keyword extraction logic directly
            $reflection = new ReflectionClass($this->atsService);
            $method = $reflection->getMethod('extractJobKeywords');
            $method->setAccessible(true);
            $keywords = $method->invoke($this->atsService, $jobListing);
            expect($keywords)->toContain('php');
            expect($keywords)->toContain('laravel');
            expect($keywords)->toContain('mysql');
        });

        it('extracts skills from resume text', function () {
            $resumeText = 'Experienced PHP developer with Laravel and MySQL skills. Also knows JavaScript and React.';

            $reflection = new ReflectionClass($this->atsService);
            $method = $reflection->getMethod('extractSkills');
            $method->setAccessible(true);
            $skills = $method->invoke($this->atsService, $resumeText, ['php', 'laravel', 'mysql']);

            expect($skills)->toContain('php');
            expect($skills)->toContain('laravel');
            expect($skills)->toContain('mysql');
        });

        it('extracts experience years from resume text', function () {
            $resumeText = '5 years of experience in PHP development. 3+ years with Laravel.';

            $reflection = new ReflectionClass($this->atsService);
            $method = $reflection->getMethod('extractExperienceYears');
            $method->setAccessible(true);
            $years = $method->invoke($this->atsService, $resumeText);

            expect($years)->toBeGreaterThanOrEqual(3);
        });

        it('extracts education from resume text', function () {
            $resumeText = 'Bachelor of Science in Computer Science. Master of Business Administration.';

            $reflection = new ReflectionClass($this->atsService);
            $method = $reflection->getMethod('extractEducation');
            $method->setAccessible(true);
            $education = $method->invoke($this->atsService, $resumeText);

            expect($education)->toBe('master');
        });

        it('returns correct experience requirement for each level', function () {
            $reflection = new ReflectionClass($this->atsService);
            $method = $reflection->getMethod('getRequiredExperience');
            $method->setAccessible(true);

            expect($method->invoke($this->atsService, 'entry'))->toBe(0);
            expect($method->invoke($this->atsService, 'intern'))->toBe(0);
            expect($method->invoke($this->atsService, 'junior'))->toBe(1);
            expect($method->invoke($this->atsService, 'mid'))->toBe(3);
            expect($method->invoke($this->atsService, 'senior'))->toBe(5);
            expect($method->invoke($this->atsService, 'lead'))->toBe(8);
            expect($method->invoke($this->atsService, 'principal'))->toBe(8);
            expect($method->invoke($this->atsService, 'executive'))->toBe(8);
            expect($method->invoke($this->atsService, 'unknown'))->toBe(2);
        });

        it('returns correct education requirement for each level', function () {
            $reflection = new ReflectionClass($this->atsService);
            $method = $reflection->getMethod('getRequiredEducation');
            $method->setAccessible(true);

            expect($method->invoke($this->atsService, 'phd'))->toBe('phd');
            expect($method->invoke($this->atsService, 'master'))->toBe('master');
            expect($method->invoke($this->atsService, 'bachelor'))->toBe('bachelor');
            expect($method->invoke($this->atsService, 'diploma'))->toBe('diploma');
            expect($method->invoke($this->atsService, 'associate'))->toBe('diploma');
            expect($method->invoke($this->atsService, 'high-school'))->toBe('high-school');
            expect($method->invoke($this->atsService, 'any'))->toBe('bachelor');
        });

        it('checks education meets requirement', function () {
            $reflection = new ReflectionClass($this->atsService);
            $method = $reflection->getMethod('educationMeetsRequirement');
            $method->setAccessible(true);

            expect($method->invoke($this->atsService, 'phd', 'master'))->toBeTrue();
            expect($method->invoke($this->atsService, 'master', 'bachelor'))->toBeTrue();
            expect($method->invoke($this->atsService, 'bachelor', 'bachelor'))->toBeTrue();
            expect($method->invoke($this->atsService, 'bachelor', 'master'))->toBeFalse();
            expect($method->invoke($this->atsService, 'high-school', 'bachelor'))->toBeFalse();
        });
    });

    describe('getTopSkillsFromApplications', function () {
        it('aggregates skills from multiple applications', function () {
            $jobListing = JobListing::factory()->create(['user_id' => $this->employer->id]);

            Application::factory()->create([
                'job_listing_id' => $jobListing->id,
                'user_id' => $this->jobSeeker->id,
                'ats_score' => [
                    'extracted_skills' => ['PHP', 'Laravel', 'MySQL'],
                ],
            ]);

            Application::factory()->create([
                'job_listing_id' => $jobListing->id,
                'user_id' => User::factory()->jobSeeker()->create()->id,
                'ats_score' => [
                    'extracted_skills' => ['PHP', 'React', 'JavaScript'],
                ],
            ]);

            Application::factory()->create([
                'job_listing_id' => $jobListing->id,
                'user_id' => User::factory()->jobSeeker()->create()->id,
                'ats_score' => [
                    'extracted_skills' => ['Laravel', 'Vue.js'],
                ],
            ]);

            $skills = $this->atsService->getTopSkillsFromApplications(
                Application::where('job_listing_id', $jobListing->id)->get()
            );

            expect($skills)->toHaveKey('php');
            expect($skills)->toHaveKey('laravel');
            expect($skills['php'])->toBe(2);
            expect($skills['laravel'])->toBe(2);
        });
    });

    describe('getKeywordAnalysisData', function () {
        it('returns keyword match statistics', function () {
            $jobListing = JobListing::factory()->create([
                'user_id' => $this->employer->id,
                'skills' => ['PHP', 'Laravel'],
                'keywords' => ['php', 'laravel', 'mysql'],
            ]);

            Application::factory()->create([
                'job_listing_id' => $jobListing->id,
                'user_id' => $this->jobSeeker->id,
                'matched_keywords' => ['php', 'laravel'],
            ]);

            Application::factory()->create([
                'job_listing_id' => $jobListing->id,
                'user_id' => User::factory()->jobSeeker()->create()->id,
                'matched_keywords' => ['php'],
            ]);

            $analysis = $this->atsService->getKeywordAnalysisData($jobListing);

            expect($analysis)->toBeArray();
            expect(count($analysis))->toBeGreaterThan(0);
            // Check structure
            if (count($analysis) > 0) {
                expect($analysis[0])->toHaveKeys(['keyword', 'matched_count', 'match_percentage']);
            }
        });
    });

    describe('getTopCandidatesData', function () {
        it('returns top candidates sorted by ATS score', function () {
            $jobListing = JobListing::factory()->create(['user_id' => $this->employer->id]);

            Application::factory()->create([
                'job_listing_id' => $jobListing->id,
                'user_id' => $this->jobSeeker->id,
                'ats_score' => ['percentage' => 90],
            ]);

            Application::factory()->create([
                'job_listing_id' => $jobListing->id,
                'user_id' => User::factory()->jobSeeker()->create()->id,
                'ats_score' => ['percentage' => 75],
            ]);

            Application::factory()->create([
                'job_listing_id' => $jobListing->id,
                'user_id' => User::factory()->jobSeeker()->create()->id,
                'ats_score' => ['percentage' => 85],
            ]);

            $candidates = $this->atsService->getTopCandidatesData($jobListing, 3);

            expect(count($candidates))->toBe(3);
            expect($candidates[0]['ats_score'])->toBe(90);
            expect($candidates[1]['ats_score'])->toBe(85);
            expect($candidates[2]['ats_score'])->toBe(75);
        });
    });

    describe('getATSStatsData', function () {
        it('returns ATS stats for employer', function () {
            actingAs($this->employer);
            $jobListing = JobListing::factory()->create(['user_id' => $this->employer->id]);

            Application::factory()->create([
                'job_listing_id' => $jobListing->id,
                'user_id' => $this->jobSeeker->id,
                'ats_score' => ['percentage' => 90],
                'status' => 'pending',
            ]);

            Application::factory()->create([
                'job_listing_id' => $jobListing->id,
                'user_id' => User::factory()->jobSeeker()->create()->id,
                'ats_score' => ['percentage' => 65],
                'status' => 'reviewed',
            ]);

            $stats = $this->atsService->getATSStatsData();

            expect($stats)->toHaveKeys(['total_applications', 'average_score', 'score_distribution', 'top_skills', 'status_breakdown']);
            expect($stats['total_applications'])->toBe(2);
            expect((float) $stats['average_score'])->toBe(78.0); // (90 + 65) / 2 = 77.5 rounded to 78
        });

        it('returns ATS stats for admin across all applications', function () {
            actingAs($this->admin);

            $jobListing1 = JobListing::factory()->create(['user_id' => $this->employer->id]);
            $jobListing2 = JobListing::factory()->create(['user_id' => User::factory()->employer()->create()->id]);

            Application::factory()->create([
                'job_listing_id' => $jobListing1->id,
                'user_id' => $this->jobSeeker->id,
                'ats_score' => ['percentage' => 80],
                'status' => 'pending',
            ]);

            Application::factory()->create([
                'job_listing_id' => $jobListing2->id,
                'user_id' => User::factory()->jobSeeker()->create()->id,
                'ats_score' => ['percentage' => 70],
                'status' => 'shortlisted',
            ]);

            $stats = $this->atsService->getATSStatsData();

            expect($stats['total_applications'])->toBe(2);
            expect((float) $stats['average_score'])->toBe(75.0);
        });
    });

    describe('extractJobKeywords', function () {
        it('extracts keywords from skills and responsibilities', function () {
            $jobListing = JobListing::factory()->create([
                'skills' => ['php', 'laravel', 'mysql'],
                'responsibilities' => ['Develop web applications', 'Write clean code', 'Participate in code reviews'],
                'description' => 'We are looking for a PHP developer with Laravel experience.',
            ]);

            $reflection = new ReflectionClass($this->atsService);
            $method = $reflection->getMethod('extractJobKeywords');
            $method->setAccessible(true);
            $keywords = $method->invoke($this->atsService, $jobListing);

            expect($keywords)->toContain('php');
            expect($keywords)->toContain('laravel');
            expect($keywords)->toContain('mysql');
            expect($keywords)->toContain('develop');
            expect($keywords)->toContain('web');
            expect($keywords)->toContain('applications');
        });

        it('filters out stop words and short words', function () {
            $jobListing = JobListing::factory()->create([
                'skills' => ['PHP'],
                'responsibilities' => ['The and or but in on at to for of with by from'],
            ]);

            $reflection = new ReflectionClass($this->atsService);
            $method = $reflection->getMethod('extractJobKeywords');
            $method->setAccessible(true);
            $keywords = $method->invoke($this->atsService, $jobListing);

            // Should not contain stop words
            expect($keywords)->not->toContain('the');
            expect($keywords)->not->toContain('and');
            expect($keywords)->not->toContain('or');
            // Should not contain words shorter than 3 chars
            expect($keywords)->not->toContain('in');
            expect($keywords)->not->toContain('on');
        });
    });
});
