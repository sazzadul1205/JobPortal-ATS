<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobCategoryStoreRequest;
use App\Http\Requests\JobCategoryUpdateRequest;
use App\Models\JobCategory;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class JobCategoryController extends Controller
{
    /**
     * Handle index request.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JobCategory::class);

        $query = JobCategory::withTrashed()
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

        $categories = $query->paginate(15)->withQueryString();

        return Inertia::render('Backend/JobCategories/Index', [
            'categories' => $categories,
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
    public function store(JobCategoryStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', JobCategory::class);

        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $counter = 1;

        while (JobCategory::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        JobCategory::create([
            'name' => $request->name,
            'slug' => $slug,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('backend.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Handle update request.
     */
    public function update(JobCategoryUpdateRequest $request, JobCategory $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $slug = $category->slug;
        if ($category->name !== $request->name) {
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;

            while (JobCategory::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $originalSlug.'-'.$counter;
                $counter++;
            }
        }

        $category->update([
            'name' => $request->name,
            'slug' => $slug,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('backend.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Handle destroy request.
     */
    public function destroy(JobCategory $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if (JobListing::where('category_id', $category->id)->exists()) {
            return back()->with('error', 'Cannot delete category with existing job listings.');
        }

        $category->delete();

        return redirect()->route('backend.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Handle toggleActive request.
     */
    public function toggleActive(JobCategory $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('success', $category->is_active ? 'Category activated.' : 'Category deactivated.');
    }

    /**
     * Handle restore request.
     */
    public function restore(string $id): RedirectResponse
    {
        $category = JobCategory::withTrashed()->findOrFail($id);
        $this->authorize('restore', $category);

        $category->restore();

        return redirect()->route('backend.categories.index')
            ->with('success', 'Category restored successfully.');
    }

    /**
     * Handle forceDelete request.
     */
    public function forceDelete(string $id): RedirectResponse
    {
        $category = JobCategory::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $category);

        if (JobListing::where('category_id', $category->id)->exists()) {
            return back()->with('error', 'Cannot force delete category with existing job listings.');
        }

        $category->forceDelete();

        return redirect()->route('backend.categories.index')
            ->with('success', 'Category permanently deleted.');
    }

    /**
     * Handle getActiveCategories request.
     */
    public function getActiveCategories(): JsonResponse
    {
        $categories = JobCategory::where('is_active', true)
            ->get(['id', 'name', 'slug']);

        return response()->json($categories);
    }
}
