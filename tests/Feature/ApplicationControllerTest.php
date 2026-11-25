<?php

use App\Jobs\CalculateAtsScore;
use App\Models\Application;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
    $this->employer = User::factory()->employer()->create();
    $this->otherEmployer = User::factory()->employer()->create();
    $this->jobSeeker = User::factory()->jobSeeker()->create();
    $this->otherJobSeeker = User::factory()->jobSeeker()->create();
    $this->category = JobCategory::factory()->create();
    $this->location = Location::factory()->create();
    $this->jobListing = JobListing::factory()->create([
        'user_id' => $this->employer->id,
        'category_id' => $this->category->id,
        'location_id' => $this->location->id,
        'is_active' => true,
        'application_deadline' => now()->addDays(30),
    ]);
});

describe('ApplicationController', function () {
    describe('index', function () {
        it('allows job seeker to view own applications', function () {
            Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            Application::factory()->create(['user_id' => $this->otherJobSeeker->id, 'job_listing_id' => $this->jobListing->id]);

            actingAs($this->jobSeeker);
            $response = get(route('backend.application.index'));
            $response->assertOk();
        });

        it('allows employer to view applications for own jobs', function () {
            Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            $otherJob = JobListing::factory()->create(['user_id' => $this->otherEmployer->id]);
            Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $otherJob->id]);

            actingAs($this->employer);
            $response = get(route('backend.application.index'));
            $response->assertOk();
        });

        it('allows admin to view all applications', function () {
            Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            $otherJob = JobListing::factory()->create(['user_id' => $this->otherEmployer->id]);
            Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $otherJob->id]);

            actingAs($this->admin);
            $response = get(route('backend.application.index'));
            $response->assertOk();
        });

        it('filters by status', function () {
            Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'status' => 'pending']);
            Application::factory()->create(['user_id' => $this->otherJobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'status' => 'rejected']);

            actingAs($this->jobSeeker);
            $response = get(route('backend.application.index', ['status' => 'pending']));
            $response->assertOk();
        });
    });

    describe('create', function () {
        it('allows job seeker to view application form', function () {
            actingAs($this->jobSeeker);
            $response = get(route('applications.create', $this->jobListing));
            $response->assertOk();
        });

        it('denies employer access to application form', function () {
            actingAs($this->employer);
            $response = get(route('applications.create', $this->jobListing));
            $response->assertForbidden();
        });

        it('shows hasApplied when user already applied', function () {
            Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->jobSeeker);
            $response = get(route('applications.create', $this->jobListing));
            $response->assertOk();
        });

        it('denies access when job is inactive', function () {
            $this->jobListing->update(['is_active' => false]);
            actingAs($this->jobSeeker);
            $response = get(route('applications.create', $this->jobListing));
            $response->assertForbidden();
        });

        it('denies access when deadline passed', function () {
            $this->jobListing->update(['application_deadline' => now()->subDays(1)]);
            actingAs($this->jobSeeker);
            $response = get(route('applications.create', $this->jobListing));
            $response->assertForbidden();
        });
    });

    describe('store', function () {
        it('allows job seeker to submit application with resume', function () {
            actingAs($this->jobSeeker);
            $resume = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

            $response = post(route('applications.store', $this->jobListing), [
                'name' => $this->jobSeeker->name,
                'email' => $this->jobSeeker->email,
                'phone' => '1234567890',
                'resume' => $resume,
                'expected_salary' => 50000,
            ]);

            $response->assertRedirect(route('backend.application.show', Application::latest()->first()));
            assertDatabaseHas('applications', [
                'user_id' => $this->jobSeeker->id,
                'job_listing_id' => $this->jobListing->id,
                'status' => 'pending',
            ]);
        });

        it('validates required fields', function () {
            actingAs($this->jobSeeker);
            $response = post(route('applications.store', $this->jobListing), []);
            $response->assertSessionHasErrors(['name', 'email', 'resume']);
        });

        it('validates resume file type', function () {
            actingAs($this->jobSeeker);
            $resume = UploadedFile::fake()->create('resume.txt', 100, 'text/plain');

            $response = post(route('applications.store', $this->jobListing), [
                'name' => $this->jobSeeker->name,
                'email' => $this->jobSeeker->email,
                'resume' => $resume,
            ]);

            $response->assertSessionHasErrors(['resume']);
        });

        it('validates resume file size', function () {
            actingAs($this->jobSeeker);
            $resume = UploadedFile::fake()->create('resume.pdf', 6000, 'application/pdf');

            $response = post(route('applications.store', $this->jobListing), [
                'name' => $this->jobSeeker->name,
                'email' => $this->jobSeeker->email,
                'resume' => $resume,
            ]);

            $response->assertSessionHasErrors(['resume']);
        });

        it('prevents duplicate applications', function () {
            Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->jobSeeker);
            $resume = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

            $response = post(route('applications.store', $this->jobListing), [
                'name' => $this->jobSeeker->name,
                'email' => $this->jobSeeker->email,
                'resume' => $resume,
            ]);

            $response->assertSessionHasErrors(['duplicate']);
        });

        it('dispatches CalculateAtsScore job', function () {
            actingAs($this->jobSeeker);
            $resume = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

            Bus::fake();

            post(route('applications.store', $this->jobListing), [
                'name' => $this->jobSeeker->name,
                'email' => $this->jobSeeker->email,
                'resume' => $resume,
            ]);

            Bus::assertDispatched(CalculateAtsScore::class);
        });
    });

    describe('show', function () {
        it('allows job seeker to view own application', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->jobSeeker);
            $response = get(route('backend.application.show', $application));
            $response->assertOk();
        });

        it('allows employer to view application for own job', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->employer);
            $response = get(route('backend.application.show', $application));
            $response->assertOk();
        });

        it('allows admin to view any application', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->admin);
            $response = get(route('backend.application.show', $application));
            $response->assertOk();
        });

        it('denies job seeker from viewing other application', function () {
            $application = Application::factory()->create(['user_id' => $this->otherJobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->jobSeeker);
            $response = get(route('backend.application.show', $application));
            $response->assertForbidden();
        });

        it('denies employer from viewing application for other employer job', function () {
            $otherJob = JobListing::factory()->create(['user_id' => $this->otherEmployer->id]);
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $otherJob->id]);
            actingAs($this->employer);
            $response = get(route('backend.application.show', $application));
            $response->assertForbidden();
        });
    });

    describe('updateStatus', function () {
        it('allows admin to update application status', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'status' => 'pending']);
            actingAs($this->admin);
            $response = patch(route('backend.application.update-status', $application), ['status' => 'shortlisted']);
            $response->assertRedirect();
            $application->refresh();
            expect($application->status)->toBe('shortlisted');
        });

        it('allows employer to update status for own job application', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'status' => 'pending']);
            actingAs($this->employer);
            $response = patch(route('backend.application.update-status', $application), ['status' => 'reviewed']);
            $response->assertRedirect();
            $application->refresh();
            expect($application->status)->toBe('reviewed');
        });

        it('denies employer from updating status for other employer job application', function () {
            $otherJob = JobListing::factory()->create(['user_id' => $this->otherEmployer->id]);
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $otherJob->id, 'status' => 'pending']);
            actingAs($this->employer);
            $response = patch(route('backend.application.update-status', $application), ['status' => 'reviewed']);
            $response->assertForbidden();
        });

        it('validates status', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->admin);
            $response = patch(route('backend.application.update-status', $application), ['status' => 'invalid']);
            $response->assertSessionHasErrors(['status']);
        });
    });

    describe('bulkUpdateStatus', function () {
        it('allows admin to bulk update applications', function () {
            $app1 = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'status' => 'pending']);
            $app2 = Application::factory()->create(['user_id' => $this->otherJobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'status' => 'pending']);
            actingAs($this->admin);
            $response = patch(route('backend.application.bulk-status'), [
                'application_ids' => [$app1->id, $app2->id],
                'status' => 'shortlisted',
            ]);
            $response->assertRedirect();
            $app1->refresh();
            $app2->refresh();
            expect($app1->status)->toBe('shortlisted');
            expect($app2->status)->toBe('shortlisted');
        });

        it('allows employer to bulk update applications for own jobs', function () {
            $app1 = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'status' => 'pending']);
            $app2 = Application::factory()->create(['user_id' => $this->otherJobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'status' => 'pending']);
            actingAs($this->employer);
            $response = patch(route('backend.application.bulk-status'), [
                'application_ids' => [$app1->id, $app2->id],
                'status' => 'reviewed',
            ]);
            $response->assertRedirect();
            $app1->refresh();
            $app2->refresh();
            expect($app1->status)->toBe('reviewed');
            expect($app2->status)->toBe('reviewed');
        });

        it('denies employer from bulk updating applications for other employer jobs', function () {
            $otherJob = JobListing::factory()->create(['user_id' => $this->otherEmployer->id]);
            $app1 = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $otherJob->id, 'status' => 'pending']);
            actingAs($this->employer);
            $response = patch(route('backend.application.bulk-status'), [
                'application_ids' => [$app1->id],
                'status' => 'reviewed',
            ]);
            $response->assertForbidden();
        });
    });

    describe('downloadResume', function () {
        it('allows admin to download resume', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'resume_path' => 'resumes/test.pdf']);
            Storage::disk('public')->put('resumes/test.pdf', 'fake pdf content');
            actingAs($this->admin);
            $response = get(route('backend.application.download-resume', $application));
            $response->assertOk();
        });

        it('allows job seeker to download own resume', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'resume_path' => 'resumes/test.pdf']);
            Storage::disk('public')->put('resumes/test.pdf', 'fake pdf content');
            actingAs($this->jobSeeker);
            $response = get(route('backend.application.download-resume', $application));
            $response->assertOk();
        });

        it('allows employer to download resume for own job application', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'resume_path' => 'resumes/test.pdf']);
            Storage::disk('public')->put('resumes/test.pdf', 'fake pdf content');
            actingAs($this->employer);
            $response = get(route('backend.application.download-resume', $application));
            $response->assertOk();
        });

        it('denies access when file not found', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'resume_path' => 'resumes/missing.pdf']);
            actingAs($this->admin);
            $response = get(route('backend.application.download-resume', $application));
            $response->assertRedirect();
            $response->assertSessionHas('error');
        });
    });

    describe('destroy', function () {
        it('allows admin to delete application', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->admin);
            $response = delete(route('backend.application.destroy', $application));
            $response->assertRedirect(route('backend.application.index'));
            $this->assertSoftDeleted('applications', ['id' => $application->id]);
        });

        it('allows job seeker to delete own application', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->jobSeeker);
            $response = delete(route('backend.application.destroy', $application));
            $response->assertRedirect(route('backend.application.index'));
            $this->assertSoftDeleted('applications', ['id' => $application->id]);
        });

        it('denies employer from deleting application', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->employer);
            $response = delete(route('backend.application.destroy', $application));
            $response->assertForbidden();
        });

        it('deletes resume file when application deleted', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id, 'resume_path' => 'resumes/test.pdf']);
            Storage::disk('public')->put('resumes/test.pdf', 'fake pdf content');
            actingAs($this->admin);
            delete(route('backend.application.destroy', $application));
            expect(Storage::disk('public')->exists('resumes/test.pdf'))->toBeFalse();
        });
    });

    describe('recalculateScore', function () {
        it('allows admin to recalculate ATS score', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->admin);
            $response = post(route('backend.application.recalculate-score', $application));
            $response->assertRedirect();
            $response->assertSessionHas('success');
        });

        it('allows employer to recalculate score for own job application', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->employer);
            $response = post(route('backend.application.recalculate-score', $application));
            $response->assertRedirect();
            $response->assertSessionHas('success');
        });

        it('denies job seeker from recalculating score', function () {
            $application = Application::factory()->create(['user_id' => $this->jobSeeker->id, 'job_listing_id' => $this->jobListing->id]);
            actingAs($this->jobSeeker);
            $response = post(route('backend.application.recalculate-score', $application));
            $response->assertForbidden();
        });
    });

    describe('analyticsDashboard', function () {
        it('allows admin to view analytics dashboard', function () {
            actingAs($this->admin);
            $response = get(route('backend.analytics.dashboard'));
            $response->assertOk();
        });

        it('allows employer to view analytics dashboard', function () {
            actingAs($this->employer);
            $response = get(route('backend.analytics.dashboard'));
            $response->assertOk();
        });

        it('allows job seeker to view analytics dashboard', function () {
            actingAs($this->jobSeeker);
            $response = get(route('backend.analytics.dashboard'));
            $response->assertOk();
        });
    });

    describe('jobATSAnalysis', function () {
        it('allows admin to view job ATS analysis', function () {
            actingAs($this->admin);
            $response = get(route('backend.analytics.job-analysis', $this->jobListing));
            $response->assertOk();
        });

        it('allows employer to view ATS analysis for own job', function () {
            actingAs($this->employer);
            $response = get(route('backend.analytics.job-analysis', $this->jobListing));
            $response->assertOk();
        });

        it('denies employer from viewing ATS analysis for other employer job', function () {
            $otherJob = JobListing::factory()->create(['user_id' => $this->otherEmployer->id]);
            actingAs($this->employer);
            $response = get(route('backend.analytics.job-analysis', $otherJob));
            $response->assertForbidden();
        });
    });

    describe('getATSStats', function () {
        it('allows employer to get ATS stats', function () {
            actingAs($this->employer);
            $response = get(route('backend.analytics.ats-stats'));
            $response->assertOk();
            $response->assertJsonStructure(['total_applications', 'average_score', 'score_distribution', 'top_skills', 'status_breakdown']);
        });

        it('allows admin to get ATS stats', function () {
            actingAs($this->admin);
            $response = get(route('backend.analytics.ats-stats'));
            $response->assertOk();
        });

        it('denies job seeker from getting ATS stats', function () {
            actingAs($this->jobSeeker);
            $response = get(route('backend.analytics.ats-stats'));
            $response->assertForbidden();
        });
    });
});
