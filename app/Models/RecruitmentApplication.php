<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentApplication extends Model
{
    public const STATUSES = [
        'moi',
        'dang-xem',
        'phong-van',
        'nhan',
        'tu-choi',
    ];

    public const REVIEW_STATUSES = [
        'pending',
        'approved',
        'rejected',
    ];

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'role',
        'area',
        'experience',
        'cv_file_name',
        'cv_path',
        'status',
        'member_id',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'reviewed_by');
    }

    public function reviewStatus(): string
    {
        return match ($this->status) {
            'nhan' => 'approved',
            'tu-choi' => 'rejected',
            default => 'pending',
        };
    }

    public function isPending(): bool
    {
        return $this->reviewStatus() === 'pending';
    }

    public function scopePending($query)
    {
        return $query->whereNotIn('status', ['nhan', 'tu-choi']);
    }

    public function scopeReviewStatus($query, string $status)
    {
        return match ($status) {
            'approved' => $query->where('status', 'nhan'),
            'rejected' => $query->where('status', 'tu-choi'),
            'pending' => $query->pending(),
            default => $query,
        };
    }
}
