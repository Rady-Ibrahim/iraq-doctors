<?php

namespace Modules\StaticPage\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\StaticPage\Http\Requests\Api\CreateStaticPageRequest;
use Modules\StaticPage\Http\Requests\Api\UpdateStaticPageRequest;
use Modules\StaticPage\Services\Api\StaticPageService;

class StaticPageController extends Controller
{
    public function __construct(private StaticPageService $staticPageService)
    {
    }

    public function index(): JsonResponse
    {
        $pages = $this->staticPageService->getAll();

        return response()->json([
            'success' => true,
            'data' => $pages->map(function ($page) {
                return [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title_ar,
                    'order' => $page->order,
                ];
            }),
        ], 200);
    }

    public function show(string $slug): JsonResponse
    {
        $page = $this->staticPageService->getBySlug($slug);

        if (!$page) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAGE_NOT_FOUND',
                    'message' => 'الصفحة غير موجودة',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title_ar,
                'content' => $page->content_ar,
                'created_at' => $page->created_at,
            ],
        ], 200);
    }

    public function store(CreateStaticPageRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'غير مصرح (Admin فقط)',
                ],
            ], 403);
        }

        $page = $this->staticPageService->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title_ar' => $page->title_ar,
                'title_en' => $page->title_en,
                'is_active' => $page->is_active,
                'order' => $page->order,
            ],
            'message' => 'تم إنشاء الصفحة بنجاح',
        ], 201);
    }

    public function update(string $id, UpdateStaticPageRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'غير مصرح (Admin فقط)',
                ],
            ], 403);
        }

        $page = $this->staticPageService->update($id, $request->validated());

        if (!$page) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAGE_NOT_FOUND',
                    'message' => 'الصفحة غير موجودة',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title_ar' => $page->title_ar,
                'title_en' => $page->title_en,
                'is_active' => $page->is_active,
                'order' => $page->order,
            ],
            'message' => 'تم تحديث الصفحة بنجاح',
        ], 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'غير مصرح (Admin فقط)',
                ],
            ], 403);
        }

        $deleted = $this->staticPageService->delete($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PAGE_NOT_FOUND',
                    'message' => 'الصفحة غير موجودة',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الصفحة بنجاح',
        ], 200);
    }
}
