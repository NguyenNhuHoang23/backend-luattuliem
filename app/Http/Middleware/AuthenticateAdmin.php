<?php

namespace App\Http\Middleware;

use App\Models\AdminToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! $plain) {
            return $this->unauthenticated();
        }

        $record = AdminToken::query()
            ->with('user')
            ->where('token', hash('sha256', $plain))
            ->first();

        if (! $record || $record->isExpired() || ! $record->user?->is_admin) {
            $record?->delete();

            return $this->unauthenticated();
        }

        $record->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('adminToken', $record);
        auth()->setUser($record->user);

        return $next($request);
    }

    private function unauthenticated(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Bạn cần đăng nhập quản trị để dùng API này.',
        ], 401);
    }
}
