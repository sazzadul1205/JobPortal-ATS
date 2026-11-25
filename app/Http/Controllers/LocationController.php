<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationStoreRequest;
use App\Http\Requests\LocationUpdateRequest;
use App\Models\Location;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Location::class);

        $data = $this->locationService->getIndexData($request->only(['search', 'status']));

        return Inertia::render('Backend/Locations/Index', [
            'locations' => $data['locations'],
            'filters' => $data['filters'],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    public function store(LocationStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Location::class);

        $this->locationService->create($request->validated());

        return redirect()->route('backend.locations.index')
            ->with('success', 'Location created successfully.');
    }

    public function update(LocationUpdateRequest $request, Location $location): RedirectResponse
    {
        $this->authorize('update', $location);

        $this->locationService->update($location, $request->validated());

        return redirect()->route('backend.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $this->authorize('delete', $location);

        if (! $this->locationService->delete($location)) {
            return back()->with('error', 'Cannot delete location with existing job listings.');
        }

        return redirect()->route('backend.locations.index')
            ->with('success', 'Location deleted successfully.');
    }

    public function toggleActive(Location $location): RedirectResponse
    {
        $this->authorize('update', $location);

        $this->locationService->toggleActive($location);

        return back()->with('success', $location->is_active ? 'Location activated.' : 'Location deactivated.');
    }

    public function restore(string $id): RedirectResponse
    {
        $location = Location::withTrashed()->findOrFail($id);
        $this->authorize('restore', $location);

        $this->locationService->restore($id);

        return redirect()->route('backend.locations.index')
            ->with('success', 'Location restored successfully.');
    }

    public function forceDelete(string $id): RedirectResponse
    {
        $location = Location::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $location);

        if (! $this->locationService->forceDelete($id)) {
            return back()->with('error', 'Cannot force delete location with existing job listings.');
        }

        return redirect()->route('backend.locations.index')
            ->with('success', 'Location permanently deleted.');
    }

    public function getActiveLocations(): JsonResponse
    {
        $locations = $this->locationService->getActiveLocations();

        return response()->json($locations);
    }
}
