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

describe('LocationController', function () {
    describe('index', function () {
        it('allows admin to view locations', function () {
            Location::factory()->count(3)->create();
            actingAs($this->admin);
            $response = get(route('backend.locations.index'));
            $response->assertOk();
        });

        it('allows employer to view locations', function () {
            Location::factory()->count(3)->create();
            actingAs($this->employer);
            $response = get(route('backend.locations.index'));
            $response->assertOk();
        });

        it('denies job seeker access to locations', function () {
            actingAs($this->jobSeeker);
            $response = get(route('backend.locations.index'));
            $response->assertForbidden();
        });

        it('includes soft deleted locations', function () {
            $active = Location::factory()->create(['is_active' => true]);
            $inactive = Location::factory()->create(['is_active' => false]);
            $deleted = Location::factory()->create(['is_active' => true]);
            $deleted->delete();

            actingAs($this->admin);
            $response = get(route('backend.locations.index'));
            $response->assertOk();
        });

        it('filters by search', function () {
            Location::factory()->create(['name' => 'Dhaka']);
            Location::factory()->create(['name' => 'Chittagong']);

            actingAs($this->admin);
            $response = get(route('backend.locations.index', ['search' => 'Dhaka']));
            $response->assertOk();
        });

        it('filters by status', function () {
            Location::factory()->create(['is_active' => true]);
            Location::factory()->create(['is_active' => false]);

            actingAs($this->admin);
            $response = get(route('backend.locations.index', ['status' => 'active']));
            $response->assertOk();
        });
    });

    describe('store', function () {
        it('allows admin to create location', function () {
            actingAs($this->admin);
            $response = post(route('backend.locations.store'), ['name' => 'New Location', 'address' => 'Test Address', 'is_active' => true]);
            $response->assertRedirect(route('backend.locations.index'));
            assertDatabaseHas('locations', ['name' => 'New Location']);
        });

        it('allows employer to create location', function () {
            actingAs($this->employer);
            $response = post(route('backend.locations.store'), ['name' => 'Employer Location', 'address' => 'Test Address', 'is_active' => true]);
            $response->assertRedirect(route('backend.locations.index'));
            assertDatabaseHas('locations', ['name' => 'Employer Location']);
        });

        it('denies job seeker from creating location', function () {
            actingAs($this->jobSeeker);
            $response = post(route('backend.locations.store'), ['name' => 'New Location']);
            $response->assertForbidden();
        });

        it('validates required name', function () {
            actingAs($this->admin);
            $response = post(route('backend.locations.store'), []);
            $response->assertSessionHasErrors(['name']);
        });

        it('validates unique name', function () {
            Location::factory()->create(['name' => 'Existing Location']);
            actingAs($this->admin);
            $response = post(route('backend.locations.store'), ['name' => 'Existing Location']);
            $response->assertSessionHasErrors(['name']);
        });
    });

    describe('update', function () {
        it('allows admin to update location', function () {
            $location = Location::factory()->create(['name' => 'Old Name']);
            actingAs($this->admin);
            $response = put(route('backend.locations.update', $location), ['name' => 'New Name', 'address' => 'New Address', 'is_active' => true]);
            $response->assertRedirect(route('backend.locations.index'));
            assertDatabaseHas('locations', ['id' => $location->id, 'name' => 'New Name']);
        });

        it('allows employer to update location', function () {
            $location = Location::factory()->create(['name' => 'Old Name']);
            actingAs($this->employer);
            $response = put(route('backend.locations.update', $location), ['name' => 'New Name', 'address' => 'New Address', 'is_active' => true]);
            $response->assertRedirect(route('backend.locations.index'));
            assertDatabaseHas('locations', ['id' => $location->id, 'name' => 'New Name']);
        });

        it('denies job seeker from updating location', function () {
            $location = Location::factory()->create();
            actingAs($this->jobSeeker);
            $response = put(route('backend.locations.update', $location), ['name' => 'New Name']);
            $response->assertForbidden();
        });
    });

    describe('destroy', function () {
        it('allows admin to soft delete location', function () {
            $location = Location::factory()->create();
            actingAs($this->admin);
            $response = delete(route('backend.locations.destroy', $location));
            $response->assertRedirect(route('backend.locations.index'));
            $this->assertSoftDeleted('locations', ['id' => $location->id]);
        });

        it('allows employer to soft delete location', function () {
            $location = Location::factory()->create();
            actingAs($this->employer);
            $response = delete(route('backend.locations.destroy', $location));
            $response->assertRedirect(route('backend.locations.index'));
            $this->assertSoftDeleted('locations', ['id' => $location->id]);
        });

        it('denies job seeker from deleting location', function () {
            $location = Location::factory()->create();
            actingAs($this->jobSeeker);
            $response = delete(route('backend.locations.destroy', $location));
            $response->assertForbidden();
        });

        it('blocks deletion when job listings exist', function () {
            $location = Location::factory()->create();
            $category = JobCategory::factory()->create();
            $employer = User::factory()->employer()->create();
            JobListing::factory()->create(['location_id' => $location->id, 'category_id' => $category->id, 'user_id' => $employer->id]);
            actingAs($this->admin);
            $response = delete(route('backend.locations.destroy', $location));
            $response->assertRedirect();
            $response->assertSessionHas('error');
        });
    });

    describe('toggleActive', function () {
        it('allows admin to toggle location active status', function () {
            $location = Location::factory()->create(['is_active' => true]);
            actingAs($this->admin);
            $response = patch(route('backend.locations.toggle', $location));
            $response->assertRedirect();
            $location->refresh();
            expect($location->is_active)->toBeFalse();
        });

        it('allows employer to toggle location active status', function () {
            $location = Location::factory()->create(['is_active' => true]);
            actingAs($this->employer);
            $response = patch(route('backend.locations.toggle', $location));
            $response->assertRedirect();
            $location->refresh();
            expect($location->is_active)->toBeFalse();
        });
    });

    describe('restore', function () {
        it('allows admin to restore soft deleted location', function () {
            $location = Location::factory()->create();
            $location->delete();
            actingAs($this->admin);
            $response = patch(route('backend.locations.restore', $location->id));
            $response->assertRedirect(route('backend.locations.index'));
            $this->assertDatabaseHas('locations', ['id' => $location->id]);
        });

        it('denies employer from restoring location', function () {
            $location = Location::factory()->create();
            $location->delete();
            actingAs($this->employer);
            $response = patch(route('backend.locations.restore', $location->id));
            $response->assertForbidden();
        });
    });

    describe('forceDelete', function () {
        it('allows admin to force delete location', function () {
            $location = Location::factory()->create();
            $location->delete();
            actingAs($this->admin);
            $response = delete(route('backend.locations.force-delete', $location->id));
            $response->assertRedirect(route('backend.locations.index'));
            $this->assertDatabaseMissing('locations', ['id' => $location->id]);
        });

        it('denies employer from force deleting location', function () {
            $location = Location::factory()->create();
            $location->delete();
            actingAs($this->employer);
            $response = delete(route('backend.locations.force-delete', $location->id));
            $response->assertForbidden();
        });

        it('blocks force deletion when job listings exist', function () {
            $location = Location::factory()->create();
            $category = JobCategory::factory()->create();
            $employer = User::factory()->employer()->create();
            JobListing::factory()->create(['location_id' => $location->id, 'category_id' => $category->id, 'user_id' => $employer->id]);
            $location->delete();
            actingAs($this->admin);
            $response = delete(route('backend.locations.force-delete', $location->id));
            $response->assertRedirect();
            $response->assertSessionHas('error');
        });
    });

    describe('getActiveLocations', function () {
        it('returns active locations as JSON', function () {
            Location::factory()->create(['is_active' => true, 'name' => 'Active Location']);
            Location::factory()->create(['is_active' => false, 'name' => 'Inactive Location']);
            actingAs($this->admin);
            $response = get(route('backend.locations.active'));
            $response->assertOk();
            $response->assertJsonStructure([['id', 'name', 'address']]);
        });
    });
});
