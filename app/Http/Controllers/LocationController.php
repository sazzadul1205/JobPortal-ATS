<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationStoreRequest;
use App\Http\Requests\LocationUpdateRequest;
use App\Models\JobListing;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    /**
     * Handle index request.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Location::class);

        $query = Location::withTrashed()
            ->when($request->input('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
            )
            ->when($request->input('status'), function ($q, $status) {
                if ((string) $status === 'active') {
                    $q->where('is_active', true);
                } elseif ((string) $status === 'inactive') {
                    $q->where('is_active', false);
                } elseif ((string) $status === 'trashed') {
                    $q->onlyTrashed();
                }
            })
            ->latest();

        $locations = $query->paginate(15)->withQueryString();

        return Inertia::render('Backend/Locations/Index', [
            'locations' => $locations,
            'filters' => $request->only(['search', 'status']),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    /**
     * Handle store request.
     */
    public function store(LocationStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Location::class);

        Location::create([
            'name' => $request->name,
            'address' => $request->address,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('backend.locations.index')
            ->with('success', 'Location created successfully.');
    }

    /**
     * Handle update request.
     */
    public function update(LocationUpdateRequest $request, Location $location): RedirectResponse
    {
        $this->authorize('update', $location);

        $location->update([
            'name' => $request->name,
            'address' => $request->address,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('backend.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    /**
     * Handle destroy request.
     */
    public function destroy(Location $location): RedirectResponse
    {
        $this->authorize('delete', $location);

        if (JobListing::whereHas('locations', fn ($q) => $q->where('locations.id', $location->id))->exists()) {
            return back()->with('error', 'Cannot delete location with existing job listings.');
        }

        $location->delete();

        return redirect()->route('backend.locations.index')
            ->with('success', 'Location deleted successfully.');
    }

    /**
     * Handle toggleActive request.
     */
    public function toggleActive(Location $location): RedirectResponse
    {
        $this->authorize('update', $location);

        $location->update(['is_active' => ! $location->is_active]);

        return back()->with('success', $location->is_active ? 'Location activated.' : 'Location deactivated.');
    }

    /**
     * Handle restore request.
     */
    public function restore(string $id): RedirectResponse
    {
        $location = Location::withTrashed()->findOrFail($id);
        $this->authorize('restore', $location);

        $location->restore();

        return redirect()->route('backend.locations.index')
            ->with('success', 'Location restored successfully.');
    }

    /**
     * Handle forceDelete request.
     */
    public function forceDelete(string $id): RedirectResponse
    {
        $location = Location::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $location);

        if (JobListing::whereHas('locations', fn ($q) => $q->withTrashed()->where('locations.id', $location->id))->exists()) {
            return back()->with('error', 'Cannot force delete location with existing job listings.');
        }

        $location->forceDelete();

        return redirect()->route('backend.locations.index')
            ->with('success', 'Location permanently deleted.');
    }

    /**
     * Handle getActiveLocations request.
     */
    public function getActiveLocations(): JsonResponse
    {
        $locations = Location::where('is_active', true)
            ->get(['id', 'name', 'address']);

        return response()->json($locations);
    }
}
