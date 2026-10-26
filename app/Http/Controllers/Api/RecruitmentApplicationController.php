<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRecruitmentApplicationStatusRequest;
use App\Http\Resources\RecruitmentApplicationResource;
use App\Models\RecruitmentApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruitmentApplicationController extends Controller
{
    public function index(): JsonResponse
    {
        $applications = RecruitmentApplication::query()
            ->orderByDesc('id')
            ->get();

        return RecruitmentApplicationResource::collection($applications)
            ->additional(['success' => true])
            ->response();
    }

    public function show(RecruitmentApplication $recruitmentApplication): JsonResponse
    {
        return (new RecruitmentApplicationResource($recruitmentApplication))
            ->additional(['success' => true])
            ->response();
    }

    public function cv(RecruitmentApplication $recruitmentApplication): StreamedResponse
    {
        $path = $recruitmentApplication->cv_path;

        abort_unless(
            $path && str_starts_with($path, 'applications/') && Storage::disk('public')->exists($path),
            404,
            'Không tìm thấy file CV.',
        );

        $filename = $recruitmentApplication->cv_file_name ?: basename($path);
        $safeName = str_replace(['"', '\\', "\r", "\n"], '', $filename) ?: 'cv.pdf';

        return Storage::disk('public')->response($path, $safeName, [
            'Content-Type' => 'application/pdf',
        ], 'inline');
    }

    public function updateStatus(
        UpdateRecruitmentApplicationStatusRequest $request,
        RecruitmentApplication $recruitmentApplication,
    ): JsonResponse {
        $recruitmentApplication->update($request->validated());

        return (new RecruitmentApplicationResource($recruitmentApplication->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái hồ sơ.',
            ])
            ->response();
    }

    public function destroy(RecruitmentApplication $recruitmentApplication): JsonResponse
    {
        $this->deleteStoredCv($recruitmentApplication->cv_path);
        $recruitmentApplication->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa hồ sơ ứng tuyển.',
        ]);
    }

    private function deleteStoredCv(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'applications/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
