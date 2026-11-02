<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    public const ROLES = [
        'director',
        'lawyer',
        'support',
        'legal',
        'ctv',
    ];

    public const STAFF_ROLES = [
        'lawyer',
        'support',
        'legal',
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

    public function caseConversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_members')
            ->where('conversations.type', Conversation::TYPE_CASE)
            ->withTimestamps();
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

    public function isDirector(): bool
    {
        return $this->role === 'director';
    }

    public static function roleFromLabel(mixed $label): string
    {
        $value = mb_strtolower(trim((string) $label));

        return match (true) {
            $value === 'director',
            str_contains($value, 'giám đốc'),
            str_contains($value, 'giam doc') => 'director',
            $value === 'lawyer',
            str_contains($value, 'luật sư'),
            str_contains($value, 'luat su') => 'lawyer',
            $value === 'legal',
            str_contains($value, 'pháp lý'),
            str_contains($value, 'phap ly') => 'legal',
            $value === 'support',
            str_contains($value, 'hỗ trợ'),
            str_contains($value, 'ho tro') => 'support',
            default => 'ctv',
        };
    }
}
