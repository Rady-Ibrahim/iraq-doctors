<?php

namespace Modules\StaticPage\Services\Api;

use Modules\StaticPage\Models\StaticPage;

class StaticPageService
{
    public function getAll()
    {
        return StaticPage::active()
            ->ordered()
            ->get(['id', 'slug', 'title_ar', 'order']);
    }

    public function getBySlug(string $slug): ?StaticPage
    {
        return StaticPage::where('slug', $slug)
            ->active()
            ->first();
    }

    public function getById(string $id): ?StaticPage
    {
        return StaticPage::find($id);
    }

    public function getAllAdmin()
    {
        return StaticPage::orderBy('order', 'asc')->get();
    }

    public function create(array $data): StaticPage
    {
        return StaticPage::create([
            'slug' => $data['slug'],
            'title_ar' => $data['title_ar'],
            'title_en' => $data['title_en'],
            'content_ar' => $data['content_ar'],
            'content_en' => $data['content_en'],
            'is_active' => $data['is_active'] ?? true,
            'order' => $data['order'] ?? 0,
        ]);
    }

    public function update(string $id, array $data): ?StaticPage
    {
        $page = StaticPage::find($id);

        if (!$page) {
            return null;
        }

        $page->update([
            'slug' => $data['slug'] ?? $page->slug,
            'title_ar' => $data['title_ar'] ?? $page->title_ar,
            'title_en' => $data['title_en'] ?? $page->title_en,
            'content_ar' => $data['content_ar'] ?? $page->content_ar,
            'content_en' => $data['content_en'] ?? $page->content_en,
            'is_active' => $data['is_active'] ?? $page->is_active,
            'order' => $data['order'] ?? $page->order,
        ]);

        return $page->fresh();
    }

    public function delete(string $id): bool
    {
        $page = StaticPage::find($id);

        if (!$page) {
            return false;
        }

        return $page->delete();
    }
}
