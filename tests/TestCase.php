<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function makeAdmin(array $overrides = []): User
    {
        return User::factory()->admin()->create($overrides);
    }

    protected function actingAsAdmin(?User $user = null): static
    {
        $user ??= $this->makeAdmin(['email' => 'admin-test@luattuliem.vn']);
        $token = $user->createAdminToken();

        return $this->withToken($token);
    }
}
