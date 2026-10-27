<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberApplication extends Model
{
    public const STATUSES = [
        'pending',
        'approved',
        'rejected',
    ];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'area',
        'request_note',
        'status',
        'review_note',
        'reviewed_by',
        'reviewed_at',
        'member_id',
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
