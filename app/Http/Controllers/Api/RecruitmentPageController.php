<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecruitmentPageRequest;
use App\Http\Resources\RecruitmentPageResource;
use App\Models\RecruitmentPage;
use Illuminate\Http\JsonResponse;

class RecruitmentPageController extends Controller
{
    public function show(): JsonResponse
    {
        return (new RecruitmentPageResource(RecruitmentPage::current()))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }

    public function update(StoreRecruitmentPageRequest $request): JsonResponse
    {
        $page = RecruitmentPage::current();
        $page->update($request->validated());

        return (new RecruitmentPageResource($page->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật mở đầu trang tuyển dụng.',
            ])
            ->response();
    }
}
