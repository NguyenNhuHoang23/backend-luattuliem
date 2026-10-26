<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MemberAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $member = Member::query()
            ->where('username', strtolower(trim($credentials['username'])))
            ->first();

        if (! $member || ! Hash::check($credentials['password'], $member->password)) {
            throw ValidationException::withMessages([
                'username' => 'Tên đăng nhập hoặc mật khẩu không đúng.',
            ]);
        }

        if (! $member->isActive()) {
            throw ValidationException::withMessages([
                'username' => 'Tài khoản thành viên đang tạm khóa.',
            ]);
        }

        $token = $member->createMemberToken();

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công.',
            'token' => $token,
            'token_type' => 'Bearer',
            'data' => (new MemberResource($member))->resolve(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $member = $request->attributes->get('member');

        return (new MemberResource($member))
            ->additional(['success' => true])
            ->response();
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('memberToken')?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã đăng xuất.',
        ]);
    }
}
