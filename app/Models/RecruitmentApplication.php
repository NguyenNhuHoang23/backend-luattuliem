<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentApplication extends Model
{
    public const STATUSES = [
        'moi',
        'dang-xem',
        'phong-van',
        'nhan',
        'tu-choi',
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
    ];
}
