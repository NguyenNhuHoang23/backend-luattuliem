<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const STATUSES = [
        'moi',
        'da-lien-he',
        'da-xu-ly',
    ];

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'address',
        'content',
        'files',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'files' => 'array',
        ];
    }
}
