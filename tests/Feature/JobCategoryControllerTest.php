<?php

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
});

describe('JobCategoryController', function () {
    describe('index', function () {
        it('allows admin to view categories', function () {
            JobCategory::factory()->count(3)->create();
            actingAs($this->admin);
            $response = get(route('backend.categories.index'));
            $response->assertOk();
        });

        it('allows employer to view categories', function () {
            JobCategory::factory()->count(3)->create();
            actingAs($this->employer);
            $response = get(route('backend.categories.index'));
            $response->assertOk();
        });

        it('denies job seeker access to categories', function () {
            actingAs($this->jobSeeker);
            $response = get(route('backend.categories.index'));
            $response->assertForbidden();
        });

        it('includes soft deleted categories', function () {
            $active = JobCategory::factory()->create(['is_active' => true]);
            $inactive = JobCategory::factory()->create(['is_active' => false]);
            $deleted = JobCategory::factory()->create(['is_active' => true]);
            $deleted->delete();

            actingAs($this->admin);
            $response = get(route('backend.categories.index'));
            $response->assertOk();
        });

        it('filters by search', function () {
            JobCategory::factory()->create(['name' => 'Information Technology']);
            JobCategory::factory()->create(['name' => 'Healthcare']);

            actingAs($this->admin);
            $response = get(route('backend.categories.index', ['search' => 'Information']));
            $response->assertOk();
        });

        it('filters by status', function () {
            JobCategory::factory()->create(['is_active' => true]);
            JobCategory::factory()->create(['is_active' => false]);

            actingAs($this->admin);
            $response = get(route('backend.categories.index', ['status' => 'active']));
            $response->assertOk();
        });
    });

    describe('store', function () {
        it('allows admin to create category', function () {
            actingAs($this->admin);
            $response = post(route('backend.categories.store'), ['name' => 'New Category', 'is_active' => true]);
            $response->assertRedirect(route('backend.categories.index'));
            assertDatabaseHas('job_categories', ['name' => 'New Category']);
        });

        it('allows employer to create category', function () {
            actingAs($this->employer);
            $response = post(route('backend.categories.store'), ['name' => 'Employer Category', 'is_active' => true]);
            $response->assertRedirect(route('backend.categories.index'));
            assertDatabaseHas('job_categories', ['name' => 'Employer Category']);
        });

        it('denies job seeker from creating category', function () {
            actingAs($this->jobSeeker);
            $response = post(route('backend.categories.store'), ['name' => 'New Category']);
            $response->assertForbidden();
        });

        it('validates required name', function () {
            actingAs($this->admin);
            $response = post(route('backend.categories.store'), []);
            $response->assertSessionHasErrors(['name']);
        });

        it('validates unique name', function () {
            JobCategory::factory()->create(['name' => 'Existing Category']);
            actingAs($this->admin);
            $response = post(route('backend.categories.store'), ['name' => 'Existing Category']);
            $response->assertSessionHasErrors(['name']);
        });

        it('generates slug from name', function () {
            actingAs($this->admin);
            $response = post(route('backend.categories.store'), ['name' => 'Test Category']);
            $response->assertRedirect();
            $category = JobCategory::where('name', 'Test Category')->first();
            expect($category->slug)->toBe('test-category');
        });
    });

    describe('update', function () {
        it('allows admin to update category', function () {
            $category = JobCategory::factory()->create(['name' => 'Old Name']);
            actingAs($this->admin);
            $response = put(route('backend.categories.update', $category), ['name' => 'New Name', 'is_active' => true]);
            $response->assertRedirect(route('backend.categories.index'));
            assertDatabaseHas('job_categories', ['id' => $category->id, 'name' => 'New Name']);
        });

        it('allows employer to update category', function () {
            $category = JobCategory::factory()->create(['name' => 'Old Name']);
            actingAs($this->employer);
            $response = put(route('backend.categories.update', $category), ['name' => 'New Name', 'is_active' => true]);
            $response->assertRedirect(route('backend.categories.index'));
            assertDatabaseHas('job_categories', ['id' => $category->id, 'name' => 'New Name']);
        });

        it('denies job seeker from updating category', function () {
            $category = JobCategory::factory()->create();
            actingAs($this->jobSeeker);
            $response = put(route('backend.categories.update', $category), ['name' => 'New Name']);
            $response->assertForbidden();
        });

        it('regenerates slug when name changes', function () {
            $category = JobCategory::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);
            actingAs($this->admin);
            $response = put(route('backend.categories.update', $category), ['name' => 'New Name', 'is_active' => true]);
            $response->assertRedirect();
            $category->refresh();
            expect($category->slug)->toContain('new-name');
        });
    });

    describe('destroy', function () {
        it('allows admin to soft delete category', function () {
            $category = JobCategory::factory()->create();
            actingAs($this->admin);
            $response = delete(route('backend.categories.destroy', $category));
            $response->assertRedirect(route('backend.categories.index'));
            $this->assertSoftDeleted('job_categories', ['id' => $category->id]);
        });

        it('allows employer to soft delete category', function () {
            $category = JobCategory::factory()->create();
            actingAs($this->employer);
            $response = delete(route('backend.categories.destroy', $category));
            $response->assertRedirect(route('backend.categories.index'));
            $this->assertSoftDeleted('job_categories', ['id' => $category->id]);
        });

        it('denies job seeker from deleting category', function () {
            $category = JobCategory::factory()->create();
            actingAs($this->jobSeeker);
            $response = delete(route('backend.categories.destroy', $category));
            $response->assertForbidden();
        });

        it('blocks deletion when job listings exist', function () {
            $category = JobCategory::factory()->create();
            $location = Location::factory()->create();
            $employer = User::factory()->employer()->create();
            JobListing::factory()->withLocation($location)->create(['category_id' => $category->id, 'user_id' => $employer->id]);
            actingAs($this->admin);
            $response = delete(route('backend.categories.destroy', $category));
            $response->assertRedirect();
            $response->assertSessionHas('error');
        });
    });

    describe('toggleActive', function () {
        it('allows admin to toggle category active status', function () {
            $category = JobCategory::factory()->create(['is_active' => true]);
            actingAs($this->admin);
            $response = patch(route('backend.categories.toggle', $category));
            $response->assertRedirect();
            $category->refresh();
            expect($category->is_active)->toBeFalse();
        });

        it('allows employer to toggle category active status', function () {
            $category = JobCategory::factory()->create(['is_active' => true]);
            actingAs($this->employer);
            $response = patch(route('backend.categories.toggle', $category));
            $response->assertRedirect();
            $category->refresh();
            expect($category->is_active)->toBeFalse();
        });
    });

    describe('restore', function () {
        it('allows admin to restore soft deleted category', function () {
            $category = JobCategory::factory()->create();
            $category->delete();
            actingAs($this->admin);
            $response = patch(route('backend.categories.restore', $category->id));
            $response->assertRedirect(route('backend.categories.index'));
            $this->assertDatabaseHas('job_categories', ['id' => $category->id]);
        });

        it('denies employer from restoring category', function () {
            $category = JobCategory::factory()->create();
            $category->delete();
            actingAs($this->employer);
            $response = patch(route('backend.categories.restore', $category->id));
            $response->assertForbidden();
        });
    });

    describe('forceDelete', function () {
        it('allows admin to force delete category', function () {
            $category = JobCategory::factory()->create();
            $category->delete();
            actingAs($this->admin);
            $response = delete(route('backend.categories.force-delete', $category->id));
            $response->assertRedirect(route('backend.categories.index'));
            $this->assertDatabaseMissing('job_categories', ['id' => $category->id]);
        });

        it('denies employer from force deleting category', function () {
            $category = JobCategory::factory()->create();
            $category->delete();
            actingAs($this->employer);
            $response = delete(route('backend.categories.force-delete', $category->id));
            $response->assertForbidden();
        });

        it('blocks force deletion when job listings exist', function () {
            $category = JobCategory::factory()->create();
            $location = Location::factory()->create();
            $employer = User::factory()->employer()->create();
            JobListing::factory()->withLocation($location)->create(['category_id' => $category->id, 'user_id' => $employer->id]);
            $category->delete();
            actingAs($this->admin);
            $response = delete(route('backend.categories.force-delete', $category->id));
            $response->assertRedirect();
            $response->assertSessionHas('error');
        });
    });

    describe('getActiveCategories', function () {
        it('returns active categories as JSON', function () {
            JobCategory::factory()->create(['is_active' => true, 'name' => 'Active Category']);
            JobCategory::factory()->create(['is_active' => false, 'name' => 'Inactive Category']);
            actingAs($this->admin);
            $response = get(route('backend.categories.active'));
            $response->assertOk();
            $response->assertJsonStructure([['id', 'name', 'slug']]);
        });
    });
});
