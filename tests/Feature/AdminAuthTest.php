<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_access_me(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@luattuliem.vn',
            'password' => 'Tuliem@2026',
            'name' => 'Quản trị viên',
        ]);

        $response = $this->postJson('/api/admin/dang-nhap', [
            'email' => 'admin@luattuliem.vn',
            'password' => 'Tuliem@2026',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'admin@luattuliem.vn')
            ->assertJsonStructure(['token']);

        $token = $response->json('token');

        $this->withToken($token)
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@luattuliem.vn');
    }

    public function test_non_admin_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'user@luattuliem.vn',
            'password' => 'Tuliem@2026',
            'is_admin' => false,
        ]);

        $this->postJson('/api/admin/dang-nhap', [
            'email' => 'user@luattuliem.vn',
            'password' => 'Tuliem@2026',
        ])->assertUnprocessable();
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@luattuliem.vn',
            'password' => 'Tuliem@2026',
        ]);

        $this->postJson('/api/admin/dang-nhap', [
            'email' => 'admin@luattuliem.vn',
            'password' => 'sai-mat-khau',
        ])->assertUnprocessable();
    }

    public function test_admin_can_logout_and_token_stops_working(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createAdminToken();

        $this->withToken($token)
            ->postJson('/api/admin/dang-xuat')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($token)
            ->getJson('/api/admin/me')
            ->assertUnauthorized();
    }
}
