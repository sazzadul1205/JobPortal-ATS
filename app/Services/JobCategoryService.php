<?php

namespace App\Services;

use App\Models\JobCategory;
use App\Models\JobListing;
use Illuminate\Support\Str;

class JobCategoryService
{
    public function getFilteredQuery(array $filters = [])
    {
        return JobCategory::withTrashed()
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
        $categories = $query->paginate(15)->withQueryString();

        return [
            'categories' => $categories,
            'filters' => $filters,
        ];
    }

    public function create(array $data): JobCategory
    {
        $slug = $this->generateUniqueSlug($data['name']);

        return JobCategory::create([
            'name' => $data['name'],
            'slug' => $slug,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(JobCategory $category, array $data): JobCategory
    {
        $slug = $category->slug;
        if ($category->name !== $data['name']) {
            $slug = $this->generateUniqueSlug($data['name'], $category->id);
        }

        $category->update([
            'name' => $data['name'],
            'slug' => $slug,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $category;
    }

    public function delete(JobCategory $category): bool
    {
        if (JobListing::where('category_id', $category->id)->exists()) {
            return false;
        }

        $category->delete();

        return true;
    }

    public function toggleActive(JobCategory $category): JobCategory
    {
        $category->update(['is_active' => ! $category->is_active]);

        return $category;
    }

    public function restore(string $id): JobCategory
    {
        $category = JobCategory::withTrashed()->findOrFail($id);
        $category->restore();

        return $category;
    }

    public function forceDelete(string $id): bool
    {
        $category = JobCategory::withTrashed()->findOrFail($id);

        if (JobListing::where('category_id', $category->id)->exists()) {
            return false;
        }

        $category->forceDelete();

        return true;
    }

    public function getActiveCategories()
    {
        return JobCategory::where('is_active', true)
            ->get(['id', 'name', 'slug']);
    }

    protected function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (JobCategory::where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
