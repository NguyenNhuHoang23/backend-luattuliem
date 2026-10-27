<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveMemberApplicationRequest;
use App\Http\Resources\MemberResource;
use App\Http\Resources\RecruitmentApplicationResource;
use App\Models\Member;
use App\Models\RecruitmentApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        $query = RecruitmentApplication::query()->with('member');
        $status = $request->query('status');
        if (is_string($status) && in_array($status, RecruitmentApplication::REVIEW_STATUSES, true)) {
            $query->reviewStatus($status);
        }

        $applications = $query
            ->orderByRaw("CASE WHEN status IN ('nhan', 'tu-choi') THEN 1 ELSE 0 END")
            ->orderByDesc('id')
            ->get();

        $pendingCount = RecruitmentApplication::query()->pending()->count();

        return RecruitmentApplicationResource::collection($applications)
            ->additional([
                'success' => true,
                'pendingCount' => $pendingCount,
            ])
            ->response();
    }

    public function cv(Request $request, RecruitmentApplication $recruitmentApplication): StreamedResponse|JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

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

    public function approve(
        ApproveMemberApplicationRequest $request,
        RecruitmentApplication $recruitmentApplication,
    ): JsonResponse {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        if (! $recruitmentApplication->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Hồ sơ này đã được xử lý.',
            ], 422);
        }

        $data = $request->validated();
        $actor = $request->attributes->get('member');

        $member = DB::transaction(function () use ($recruitmentApplication, $data, $actor) {
            $member = Member::query()->create([
                'name' => $recruitmentApplication->full_name,
                'username' => $data['username'],
                'password' => $data['password'],
                'role' => 'ctv',
                'phone' => $recruitmentApplication->phone,
                'email' => $recruitmentApplication->email,
                'area' => $data['area'] ?? $recruitmentApplication->area,
                'status' => 'active',
                'join_date' => now()->timezone('Asia/Ho_Chi_Minh')->format('d-m-Y'),
            ]);

            $recruitmentApplication->update([
                'status' => 'nhan',
                'area' => $data['area'] ?? $recruitmentApplication->area,
                'reviewed_by' => $actor?->id,
                'reviewed_at' => now(),
                'member_id' => $member->id,
                'review_note' => null,
            ]);

            return $member;
        });

        $recruitmentApplication->refresh()->load('member');

        return (new RecruitmentApplicationResource($recruitmentApplication))
            ->additional([
                'success' => true,
                'message' => 'Đã duyệt hồ sơ và cấp tài khoản thành viên.',
                'member' => (new MemberResource($member))->resolve(),
            ])
            ->response();
    }

    public function reject(Request $request, RecruitmentApplication $recruitmentApplication): JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        if (! $recruitmentApplication->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Hồ sơ này đã được xử lý.',
            ], 422);
        }

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
            'reviewNote' => ['nullable', 'string', 'max:2000'],
        ]);

        $actor = $request->attributes->get('member');
        $recruitmentApplication->update([
            'status' => 'tu-choi',
            'review_note' => $data['note'] ?? $data['reviewNote'] ?? null,
            'reviewed_by' => $actor?->id,
            'reviewed_at' => now(),
        ]);

        return (new RecruitmentApplicationResource($recruitmentApplication->refresh()->load('member')))
            ->additional([
                'success' => true,
                'message' => 'Đã từ chối hồ sơ ứng tuyển.',
            ])
            ->response();
    }

    private function directorOrForbidden(Request $request): ?JsonResponse
    {
        $actor = $request->attributes->get('member');

        if (! $actor instanceof Member || $actor->role !== 'director') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ Giám đốc được duyệt hồ sơ ứng tuyển.',
            ], 403);
        }

        return null;
    }
}
