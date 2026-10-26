<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentPage extends Model
{
    public const DEFAULT_TITLE = 'Tuyển luật sư và cộng tác viên';

    public const DEFAULT_INTRO = 'Công ty Luật TNHH MTV Từ Liêm tuyển luật sư cộng tác, cộng tác viên khách hàng và cộng tác viên pháp lý làm việc theo vụ việc tại các địa phương trên toàn quốc. Chúng tôi tìm kiếm người có trình độ, kinh nghiệm và thời gian phù hợp; ưu tiên luật sư thuộc Đoàn Luật sư thành phố Hà Nội.';

    protected $fillable = [
        'title',
        'intro',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([
            'title' => self::DEFAULT_TITLE,
            'intro' => self::DEFAULT_INTRO,
        ]);
    }
}
