<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    public const ROLES = [
        'director',
        'ctv',
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'phone',
        'email',
        'area',
        'status',
        'join_date',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(MemberToken::class);
    }

    public function createMemberToken(): string
    {
        $plain = bin2hex(random_bytes(32));

        $this->tokens()->create([
            'token' => hash('sha256', $plain),
            'expires_at' => now()->addDays(7),
        ]);

        return $plain;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
