<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobCategoryStoreRequest;
use App\Http\Requests\JobCategoryUpdateRequest;
use App\Models\JobCategory;
use App\Services\JobCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobCategoryController extends Controller
{
    protected JobCategoryService $jobCategoryService;

    public function __construct(JobCategoryService $jobCategoryService)
    {
        $this->jobCategoryService = $jobCategoryService;
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JobCategory::class);

        $data = $this->jobCategoryService->getIndexData($request->only(['search', 'status']));

        return Inertia::render('Backend/JobCategories/Index', [
            'categories' => $data['categories'],
            'filters' => $data['filters'],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    public function store(JobCategoryStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', JobCategory::class);

        $this->jobCategoryService->create($request->validated());

        return redirect()->route('backend.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function update(JobCategoryUpdateRequest $request, JobCategory $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $this->jobCategoryService->update($category, $request->validated());

        return redirect()->route('backend.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(JobCategory $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if (! $this->jobCategoryService->delete($category)) {
            return back()->with('error', 'Cannot delete category with existing job listings.');
        }

        return redirect()->route('backend.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    public function toggleActive(JobCategory $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $this->jobCategoryService->toggleActive($category);

        return back()->with('success', $category->is_active ? 'Category activated.' : 'Category deactivated.');
    }

    public function restore(string $id): RedirectResponse
    {
        $category = JobCategory::withTrashed()->findOrFail($id);
        $this->authorize('restore', $category);

        $this->jobCategoryService->restore($id);

        return redirect()->route('backend.categories.index')
            ->with('success', 'Category restored successfully.');
    }

    public function forceDelete(string $id): RedirectResponse
    {
        $category = JobCategory::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $category);

        if (! $this->jobCategoryService->forceDelete($id)) {
            return back()->with('error', 'Cannot force delete category with existing job listings.');
        }

        return redirect()->route('backend.categories.index')
            ->with('success', 'Category permanently deleted.');
    }

    public function getActiveCategories(): JsonResponse
    {
        $categories = $this->jobCategoryService->getActiveCategories();

        return response()->json($categories);
    }
}
