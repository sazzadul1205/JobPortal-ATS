<?php

namespace App\Services;

use App\Models\JobListing;
use App\Models\Location;

class LocationService
{
    public function getFilteredQuery(array $filters = [])
    {
        return Location::withTrashed()
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, function ($q, $status) {
                if ((string) $status === 'active') {
                    $q->where('is_active', true);
                } elseif ((string) $status === 'inactive') {
                    $q->where('is_active', false);
                } elseif ((string) $status === 'trashed') {
                    $q->onlyTrashed();
                }
            })
            ->latest();
    }

    public function getIndexData(array $filters = []): array
    {
        $query = $this->getFilteredQuery($filters);
        $locations = $query->paginate(15)->withQueryString();

        return [
            'locations' => $locations,
            'filters' => $filters,
        ];
    }

    public function create(array $data): Location
    {
        return Location::create([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Location $location, array $data): Location
    {
        $location->update([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $location;
    }

    public function delete(Location $location): bool
    {
        if (JobListing::whereHas('locations', fn ($q) => $q->where('locations.id', $location->id))->exists()) {
            return false;
        }

        $location->delete();

        return true;
    }

    public function toggleActive(Location $location): Location
    {
        $location->update(['is_active' => ! $location->is_active]);

        return $location;
    }

    public function restore(string $id): Location
    {
        $location = Location::withTrashed()->findOrFail($id);
        $location->restore();

        return $location;
    }

    public function forceDelete(string $id): bool
    {
        $location = Location::withTrashed()->findOrFail($id);

        if (JobListing::whereHas('locations', fn ($q) => $q->withTrashed()->where('locations.id', $location->id))->exists()) {
            return false;
        }

        $location->forceDelete();

        return true;
    }

    public function getActiveLocations()
    {
        return Location::where('is_active', true)
            ->get(['id', 'name', 'address']);
    }
}
