<?php

namespace Modules\StaticPage\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\StaticPage\Http\Requests\Api\CreateStaticPageRequest;
use Modules\StaticPage\Http\Requests\Api\UpdateStaticPageRequest;
use Modules\StaticPage\Services\Api\StaticPageService;
use App\Traits\ApiResponse;

class StaticPageController extends Controller
{
    use ApiResponse;

    public function __construct(private StaticPageService $staticPageService)
    {
    }

    public function index(): JsonResponse
    {
        $pages = $this->staticPageService->getAll();

        return $this->success($pages->map(function ($page) {
            return [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title_ar,
                'order' => $page->order,
            ];
        }));
    }

    public function show(string $slug): JsonResponse
    {
        $page = $this->staticPageService->getBySlug($slug);

        if (!$page) {
            return $this->notFound('الصفحة غير موجودة', 'PAGE_NOT_FOUND');
        }

        return $this->success([
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->title_ar,
            'content' => $page->content_ar,
            'created_at' => $page->created_at,
        ]);
    }

    public function store(CreateStaticPageRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->isAdmin()) {
            return $this->forbidden('غير مصرح (Admin فقط)');
        }

        $page = $this->staticPageService->create($request->validated());

        return $this->created([
            'id' => $page->id,
            'slug' => $page->slug,
            'title_ar' => $page->title_ar,
            'title_en' => $page->title_en,
            'is_active' => $page->is_active,
            'order' => $page->order,
        ], 'تم إنشاء الصفحة بنجاح');
    }

    public function update(string $id, UpdateStaticPageRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->isAdmin()) {
            return $this->forbidden('غير مصرح (Admin فقط)');
        }

        $page = $this->staticPageService->update($id, $request->validated());

        if (!$page) {
            return $this->notFound('الصفحة غير موجودة', 'PAGE_NOT_FOUND');
        }

        return $this->success([
            'id' => $page->id,
            'slug' => $page->slug,
            'title_ar' => $page->title_ar,
            'title_en' => $page->title_en,
            'is_active' => $page->is_active,
            'order' => $page->order,
        ], 'تم تحديث الصفحة بنجاح');
    }

    public function destroy(string $id): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->isAdmin()) {
            return $this->forbidden('غير مصرح (Admin فقط)');
        }

        $deleted = $this->staticPageService->delete($id);

        if (!$deleted) {
            return $this->notFound('الصفحة غير موجودة', 'PAGE_NOT_FOUND');
        }

        return $this->success(null, 'تم حذف الصفحة بنجاح');
    }
}
