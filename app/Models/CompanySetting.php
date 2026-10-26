<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    public const DEFAULTS = [
        'name' => 'Công ty Luật TNHH MTV Từ Liêm',
        'phone' => '091 602 99 66',
        'email' => 'contact@luattuliem.vn',
        'address' => 'Tầng 5, số 73 đường Phú Diễn, phường Phú Diễn, quận Bắc Từ Liêm, Hà Nội.',
        'working_hours' => "Thứ 2 – Thứ 6: 08:00 – 17:30\nThứ 7: 08:00 – 12:00\n(Các vụ việc khẩn cấp tiếp nhận 24/7)",
        'website' => 'luattuliem.vn',
        'slogan' => 'Đồng hành cùng quyền lợi của bạn',
    ];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'working_hours',
        'website',
        'slogan',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create(self::DEFAULTS);
    }

    public function phoneHref(): string
    {
        $digits = preg_replace('/[^\d+]/', '', (string) $this->phone);

        return $digits !== '' ? 'tel:'.$digits : '';
    }
}
