<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecruitmentRoleRequest;
use App\Http\Resources\RecruitmentRoleResource;
use App\Models\RecruitmentRole;
use Illuminate\Http\JsonResponse;

class RecruitmentRoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = RecruitmentRole::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return RecruitmentRoleResource::collection($roles)
            ->additional(['success' => true])
            ->response();
    }

    public function store(StoreRecruitmentRoleRequest $request): JsonResponse
    {
        $role = RecruitmentRole::query()->create(
            $this->roleAttributes($request->validated())
        );

        return (new RecruitmentRoleResource($role))
            ->additional([
                'success' => true,
                'message' => 'Đã tạo vị trí tuyển dụng.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreRecruitmentRoleRequest $request, RecruitmentRole $recruitmentRole): JsonResponse
    {
        $recruitmentRole->update(
            $this->roleAttributes($request->validated(), $recruitmentRole)
        );

        return (new RecruitmentRoleResource($recruitmentRole->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật vị trí tuyển dụng.',
            ])
            ->response();
    }

    public function destroy(RecruitmentRole $recruitmentRole): JsonResponse
    {
        $recruitmentRole->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa vị trí tuyển dụng.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function roleAttributes(array $data, ?RecruitmentRole $existing = null): array
    {
        $name = $data['name'];
        $attributes = [
            'name' => $name,
            'title' => $data['title'] ?? $existing?->title ?: $name,
            'apply_label' => $data['apply_label'] ?? $existing?->apply_label ?: 'Gửi hồ sơ ứng tuyển',
            'audience' => $data['audience'] ?? $existing?->audience ?? [],
            'audience_note' => $data['audience_note'] ?? $existing?->audience_note ?? [],
            'work' => $data['work'] ?? $existing?->work ?? [],
            'work_note' => $data['work_note'] ?? $existing?->work_note ?? [],
            'income' => $data['income'] ?? $existing?->income ?? [],
            'sort_order' => $data['sort_order'] ?? $existing?->sort_order ?? RecruitmentRole::nextOrder(),
        ];

        if (! $existing || filled($data['slug'] ?? null)) {
            $attributes['slug'] = RecruitmentRole::uniqueSlug(
                $name,
                $data['slug'] ?? null,
                $existing?->id,
            );
        }

        return $attributes;
    }
}
