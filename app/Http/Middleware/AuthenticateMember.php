<?php

namespace App\Http\Middleware;

use App\Models\MemberToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! $plain) {
            return $this->unauthenticated();
        }

        $record = MemberToken::query()
            ->with('member')
            ->where('token', hash('sha256', $plain))
            ->first();

        if (! $record || $record->isExpired() || ! $record->member || ! $record->member->isActive()) {
            $record?->delete();

            return $this->unauthenticated();
        }

        $record->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('memberToken', $record);
        $request->attributes->set('member', $record->member);

        return $next($request);
    }

    private function unauthenticated(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Bạn cần đăng nhập thành viên để dùng API này.',
        ], 401);
    }
}
