<?php

use App\Models\Application;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->employer = User::factory()->employer()->create();
    $this->jobSeeker = User::factory()->jobSeeker()->create();
    $this->category = JobCategory::factory()->create();
    $this->location = Location::factory()->create();
});

describe('JobListingController', function () {
    describe('index', function () {
        it('allows admin to view job listings', function () {
            actingAs($this->admin);
            $response = get(route('backend.listing.index'));
            $response->assertOk();
        });

        it('allows employer to view job listings', function () {
            actingAs($this->employer);
            $response = get(route('backend.listing.index'));
            $response->assertOk();
        });

        it('denies job seeker access to backend job listings', function () {
            actingAs($this->jobSeeker);
            $response = get(route('backend.listing.index'));
            $response->assertForbidden();
        });

        it('filters by search term', function () {
            actingAs($this->employer);
            JobListing::factory()->create(['title' => 'Senior PHP Developer', 'user_id' => $this->employer->id]);
            JobListing::factory()->create(['title' => 'Junior React Developer', 'user_id' => $this->employer->id]);

            $response = get(route('backend.listing.index', ['search' => 'PHP']));
            $response->assertOk();
        });

        it('filters by category', function () {
            actingAs($this->employer);
            $category2 = JobCategory::factory()->create();
            JobListing::factory()->create(['title' => 'Job 1', 'user_id' => $this->employer->id, 'category_id' => $this->category->id]);
            JobListing::factory()->create(['title' => 'Job 2', 'user_id' => $this->employer->id, 'category_id' => $category2->id]);

            $response = get(route('backend.listing.index', ['category' => $this->category->id]));
            $response->assertOk();
        });
    });

    describe('create', function () {
        it('allows admin to access create form', function () {
            actingAs($this->admin);
            $response = get(route('backend.listing.create'));
            $response->assertOk();
        });

        it('allows employer to access create form', function () {
            actingAs($this->employer);
            $response = get(route('backend.listing.create'));
            $response->assertOk();
        });

        it('denies job seeker access to create form', function () {
            actingAs($this->jobSeeker);
            $response = get(route('backend.listing.create'));
            $response->assertForbidden();
        });
    });

    describe('store', function () {
        it('allows admin to create job listing', function () {
            actingAs($this->admin);
            $data = [
                'title' => 'Test Job',
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'application_deadline' => now()->addDays(30)->format('Y-m-d'),
                'description' => 'This is a test job description that is long enough to pass validation.',
                'requirements' => 'These are test requirements that are long enough to pass validation.',
                'skills' => ['PHP', 'Laravel'],
                'responsibilities' => ['Develop features', 'Write tests'],
            ];

            $response = post(route('backend.listing.store'), $data);
            $response->assertRedirect(route('backend.listing.index'));
            assertDatabaseHas('job_listings', ['title' => 'Test Job', 'user_id' => $this->admin->id]);
        });

        it('allows employer to create job listing', function () {
            actingAs($this->employer);
            $data = [
                'title' => 'Employer Job',
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'application_deadline' => now()->addDays(30)->format('Y-m-d'),
                'description' => 'This is a test job description that is long enough to pass validation.',
                'requirements' => 'These are test requirements that are long enough to pass validation.',
                'skills' => ['PHP', 'Laravel'],
                'responsibilities' => ['Develop features', 'Write tests'],
            ];

            $response = post(route('backend.listing.store'), $data);
            $response->assertRedirect(route('backend.listing.index'));
            assertDatabaseHas('job_listings', ['title' => 'Employer Job', 'user_id' => $this->employer->id]);
        });

        it('denies job seeker from creating job listing', function () {
            actingAs($this->jobSeeker);
            $data = [
                'title' => 'Test Job',
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'application_deadline' => now()->addDays(30)->format('Y-m-d'),
                'description' => 'This is a test job description that is long enough to pass validation.',
                'requirements' => 'These are test requirements that are long enough to pass validation.',
                'skills' => ['PHP', 'Laravel'],
                'responsibilities' => ['Develop features', 'Write tests'],
            ];

            $response = post(route('backend.listing.store'), $data);
            $response->assertForbidden();
        });

        it('validates required fields', function () {
            actingAs($this->employer);
            $response = post(route('backend.listing.store'), []);
            $response->assertSessionHasErrors(['title', 'category_id', 'location_id', 'job_type', 'experience_level', 'application_deadline', 'description', 'requirements', 'skills', 'responsibilities']);
        });

        it('validates minimum description length', function () {
            actingAs($this->employer);
            $data = [
                'title' => 'Test Job',
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'application_deadline' => now()->addDays(30)->format('Y-m-d'),
                'description' => 'Short',
                'requirements' => 'These are test requirements that are long enough to pass validation.',
                'skills' => ['PHP', 'Laravel'],
                'responsibilities' => ['Develop features', 'Write tests'],
            ];

            $response = post(route('backend.listing.store'), $data);
            $response->assertSessionHasErrors(['description']);
        });

        it('generates unique slug', function () {
            actingAs($this->employer);
            $data = [
                'title' => 'Test Job',
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'application_deadline' => now()->addDays(30)->format('Y-m-d'),
                'description' => 'This is a test job description that is long enough to pass validation.',
                'requirements' => 'These are test requirements that are long enough to pass validation.',
                'skills' => ['PHP', 'Laravel'],
                'responsibilities' => ['Develop features', 'Write tests'],
            ];

            post(route('backend.listing.store'), $data);
            post(route('backend.listing.store'), $data);

            $listings = JobListing::where('title', 'Test Job')->get();
            expect($listings->count())->toBe(2);
            expect($listings->first()->slug)->not->toBe($listings->last()->slug);
        });

        it('sets is_active based on dates', function () {
            actingAs($this->employer);
            $data = [
                'title' => 'Future Job',
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'application_deadline' => now()->addDays(30)->format('Y-m-d'),
                'schedule_start_date' => now()->addDays(10)->format('Y-m-d'),
                'description' => 'This is a test job description that is long enough to pass validation.',
                'requirements' => 'These are test requirements that are long enough to pass validation.',
                'skills' => ['PHP', 'Laravel'],
                'responsibilities' => ['Develop features', 'Write tests'],
            ];

            $response = post(route('backend.listing.store'), $data);
            $response->assertRedirect();

            $job = JobListing::where('title', 'Future Job')->first();
            expect($job->is_active)->toBeFalse();
        });
    });

    describe('show', function () {
        it('allows admin to view job listing', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id]);
            actingAs($this->admin);
            $response = get(route('backend.listing.show', $job));
            $response->assertOk();
        });

        it('allows employer to view own job listing', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id]);
            actingAs($this->employer);
            $response = get(route('backend.listing.show', $job));
            $response->assertOk();
        });

        it('denies employer from viewing other employer job listing', function () {
            $otherEmployer = User::factory()->employer()->create();
            $job = JobListing::factory()->create(['user_id' => $otherEmployer->id]);
            actingAs($this->employer);
            $response = get(route('backend.listing.show', $job));
            $response->assertForbidden();
        });
    });

    describe('edit', function () {
        it('allows admin to edit job listing', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id]);
            actingAs($this->admin);
            $response = get(route('backend.listing.edit', $job));
            $response->assertOk();
        });

        it('allows employer to edit own job listing', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id]);
            actingAs($this->employer);
            $response = get(route('backend.listing.edit', $job));
            $response->assertOk();
        });

        it('denies employer from editing other employer job listing', function () {
            $otherEmployer = User::factory()->employer()->create();
            $job = JobListing::factory()->create(['user_id' => $otherEmployer->id]);
            actingAs($this->employer);
            $response = get(route('backend.listing.edit', $job));
            $response->assertForbidden();
        });
    });

    describe('update', function () {
        it('allows admin to update job listing', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id, 'title' => 'Original Title']);
            actingAs($this->admin);
            $data = [
                'title' => 'Updated Title',
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'application_deadline' => now()->addDays(30)->format('Y-m-d'),
                'description' => 'This is a test job description that is long enough to pass validation.',
                'requirements' => 'These are test requirements that are long enough to pass validation.',
                'skills' => ['PHP', 'Laravel'],
                'responsibilities' => ['Develop features', 'Write tests'],
            ];

            $response = put(route('backend.listing.update', $job), $data);
            $response->assertRedirect(route('backend.listing.index'));
            assertDatabaseHas('job_listings', ['title' => 'Updated Title', 'id' => $job->id]);
        });

        it('allows employer to update own job listing', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id, 'title' => 'Original Title']);
            actingAs($this->employer);
            $data = [
                'title' => 'Updated Title',
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'application_deadline' => now()->addDays(30)->format('Y-m-d'),
                'description' => 'This is a test job description that is long enough to pass validation.',
                'requirements' => 'These are test requirements that are long enough to pass validation.',
                'skills' => ['PHP', 'Laravel'],
                'responsibilities' => ['Develop features', 'Write tests'],
            ];

            $response = put(route('backend.listing.update', $job), $data);
            $response->assertRedirect(route('backend.listing.index'));
            assertDatabaseHas('job_listings', ['title' => 'Updated Title', 'id' => $job->id]);
        });

        it('denies employer from updating other employer job listing', function () {
            $otherEmployer = User::factory()->employer()->create();
            $job = JobListing::factory()->create(['user_id' => $otherEmployer->id]);
            actingAs($this->employer);
            $data = [
                'title' => 'Updated Title',
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'application_deadline' => now()->addDays(30)->format('Y-m-d'),
                'description' => 'This is a test job description that is long enough to pass validation.',
                'requirements' => 'These are test requirements that are long enough to pass validation.',
                'skills' => ['PHP', 'Laravel'],
                'responsibilities' => ['Develop features', 'Write tests'],
            ];

            $response = put(route('backend.listing.update', $job), $data);
            $response->assertForbidden();
        });

        it('regenerates slug when title changes', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id, 'title' => 'Original Title']);
            actingAs($this->employer);
            $data = [
                'title' => 'New Title',
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'application_deadline' => now()->addDays(30)->format('Y-m-d'),
                'description' => 'This is a test job description that is long enough to pass validation.',
                'requirements' => 'These are test requirements that are long enough to pass validation.',
                'skills' => ['PHP', 'Laravel'],
                'responsibilities' => ['Develop features', 'Write tests'],
            ];

            put(route('backend.listing.update', $job), $data);
            $job->refresh();
            expect($job->slug)->toContain('new-title');
        });
    });

    describe('destroy', function () {
        it('allows admin to delete job listing', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id]);
            actingAs($this->admin);
            $response = delete(route('backend.listing.destroy', $job));
            $response->assertRedirect(route('backend.listing.index'));
            $this->assertSoftDeleted('job_listings', ['id' => $job->id]);
        });

        it('allows employer to delete own job listing', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id]);
            actingAs($this->employer);
            $response = delete(route('backend.listing.destroy', $job));
            $response->assertRedirect(route('backend.listing.index'));
            $this->assertSoftDeleted('job_listings', ['id' => $job->id]);
        });

        it('denies employer from deleting other employer job listing', function () {
            $otherEmployer = User::factory()->employer()->create();
            $job = JobListing::factory()->create(['user_id' => $otherEmployer->id]);
            actingAs($this->employer);
            $response = delete(route('backend.listing.destroy', $job));
            $response->assertForbidden();
        });

        it('blocks deletion when applications exist', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id]);
            Application::factory()->create(['job_listing_id' => $job->id, 'user_id' => $this->jobSeeker->id]);
            actingAs($this->employer);
            $response = delete(route('backend.listing.destroy', $job));
            $response->assertRedirect();
            $response->assertSessionHas('error');
        });
    });

    describe('toggleActive', function () {
        it('allows admin to toggle job active status', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id, 'is_active' => true]);
            actingAs($this->admin);
            $response = patch(route('backend.listing.toggle-active', $job));
            $response->assertRedirect();
            $job->refresh();
            expect($job->is_active)->toBeFalse();
        });

        it('allows employer to toggle own job active status', function () {
            $job = JobListing::factory()->create(['user_id' => $this->employer->id, 'is_active' => true]);
            actingAs($this->employer);
            $response = patch(route('backend.listing.toggle-active', $job));
            $response->assertRedirect();
            $job->refresh();
            expect($job->is_active)->toBeFalse();
        });
    });

    describe('public index', function () {
        it('allows guest to view public job listings', function () {
            JobListing::factory()->create(['is_active' => true, 'application_deadline' => now()->addDays(30)]);
            $response = get(route('jobs.index'));
            $response->assertOk();
        });

        it('only shows active and non-expired jobs', function () {
            $activeJob = JobListing::factory()->create(['is_active' => true, 'application_deadline' => now()->addDays(30)]);
            $inactiveJob = JobListing::factory()->create(['is_active' => false, 'application_deadline' => now()->addDays(30)]);
            $expiredJob = JobListing::factory()->create(['is_active' => true, 'application_deadline' => now()->subDays(1)]);

            $response = get(route('jobs.index'));
            $response->assertOk();
        });

        it('filters by search', function () {
            JobListing::factory()->create(['title' => 'PHP Developer', 'is_active' => true, 'application_deadline' => now()->addDays(30)]);
            JobListing::factory()->create(['title' => 'React Developer', 'is_active' => true, 'application_deadline' => now()->addDays(30)]);

            $response = get(route('jobs.index', ['search' => 'PHP']));
            $response->assertOk();
        });
    });

    describe('public show', function () {
        it('allows guest to view job details', function () {
            $job = JobListing::factory()->create(['is_active' => true, 'application_deadline' => now()->addDays(30)]);
            $response = get(route('jobs.show', $job));
            $response->assertOk();
        });

        it('shows hasApplied for authenticated job seeker', function () {
            $job = JobListing::factory()->create(['is_active' => true, 'application_deadline' => now()->addDays(30)]);
            Application::factory()->create(['job_listing_id' => $job->id, 'user_id' => $this->jobSeeker->id]);
            actingAs($this->jobSeeker);
            $response = get(route('jobs.show', $job));
            $response->assertOk();
        });

        it('shows canApply for job seeker who has not applied', function () {
            $job = JobListing::factory()->create(['is_active' => true, 'application_deadline' => now()->addDays(30)]);
            actingAs($this->jobSeeker);
            $response = get(route('jobs.show', $job));
            $response->assertOk();
        });
    });
});
