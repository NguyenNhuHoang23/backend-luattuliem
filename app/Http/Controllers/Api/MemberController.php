<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        $members = Member::query()
            ->orderByDesc('id')
            ->get();

        return MemberResource::collection($members)
            ->additional(['success' => true])
            ->response();
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $member = Member::query()->create($this->memberAttributes($request->validated()));

        return (new MemberResource($member))
            ->additional([
                'success' => true,
                'message' => 'Đã tạo tài khoản thành viên.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Member $member): JsonResponse
    {
        return (new MemberResource($member))
            ->additional(['success' => true])
            ->response();
    }

    public function update(StoreMemberRequest $request, Member $member): JsonResponse
    {
        $member->update($this->memberAttributes($request->validated(), $member));

        return (new MemberResource($member->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật thành viên.',
            ])
            ->response();
    }

    public function destroy(Member $member): JsonResponse
    {
        $member->tokens()->delete();
        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa thành viên.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function memberAttributes(array $data, ?Member $existing = null): array
    {
        $attributes = [
            'name' => $data['name'],
            'username' => $data['username'],
            'role' => $data['role'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'area' => $data['area'] ?? null,
            'status' => $data['status'] ?? $existing?->status ?? 'active',
            'join_date' => $data['join_date'] ?? $existing?->join_date ?? now()->timezone('Asia/Ho_Chi_Minh')->format('d-m-Y'),
        ];

        if (filled($data['password'] ?? null)) {
            $attributes['password'] = $data['password'];
        }

        return $attributes;
    }
}
