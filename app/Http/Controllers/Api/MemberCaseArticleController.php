<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaseArticleRequest;
use App\Http\Resources\CaseArticleResource;
use App\Models\CaseArticle;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberCaseArticleController extends Controller
{
    public function store(StoreCaseArticleRequest $request): JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        $article = CaseArticle::query()->create(
            CaseArticle::payloadFrom($request->validated())
        );

        return (new CaseArticleResource($article))
            ->additional([
                'success' => true,
                'message' => 'Đã đăng án lệ / án hay.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreCaseArticleRequest $request, CaseArticle $caseArticle): JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        $caseArticle->update(
            CaseArticle::payloadFrom($request->validated(), $caseArticle)
        );

        return (new CaseArticleResource($caseArticle->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật án lệ / án hay.',
            ])
            ->response();
    }

    public function destroy(Request $request, CaseArticle $caseArticle): JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        $caseArticle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa án lệ / án hay.',
        ]);
    }

    private function directorOrForbidden(Request $request): ?JsonResponse
    {
        $actor = $request->attributes->get('member');

        if (! $actor instanceof Member || $actor->role !== 'director') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ Giám đốc được quản lý án lệ / án hay.',
            ], 403);
        }

        return null;
    }
}
