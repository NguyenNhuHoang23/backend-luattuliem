<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecruitmentRoleResource;
use App\Models\RecruitmentRole;
use Illuminate\Http\JsonResponse;

class PublicRecruitmentRoleController extends Controller
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

    public function show(string $recruitmentRole): JsonResponse
    {
        $role = RecruitmentRole::query()
            ->where(function ($query) use ($recruitmentRole) {
                $query->where('slug', $recruitmentRole)->orWhere('id', $recruitmentRole);
            })
            ->first();

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy vị trí tuyển dụng.',
            ], 404);
        }

        return (new RecruitmentRoleResource($role))
            ->additional(['success' => true])
            ->response();
    }
}
