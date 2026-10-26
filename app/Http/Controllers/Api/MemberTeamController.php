<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberTeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        $members = Member::query()
            ->where('role', '!=', 'director')
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return MemberResource::collection($members)
            ->additional(['success' => true])
            ->response();
    }

    public function show(Request $request, Member $member): JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        return (new MemberResource($member))
            ->additional(['success' => true])
            ->response();
    }

    private function directorOrForbidden(Request $request): ?JsonResponse
    {
        $actor = $request->attributes->get('member');

        if (! $actor instanceof Member || $actor->role !== 'director') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ Giám đốc được xem danh sách thành viên.',
            ], 403);
        }

        return null;
    }
}
