<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RecruitmentRole extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'title',
        'apply_label',
        'audience',
        'audience_note',
        'work',
        'work_note',
        'income',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'audience' => 'array',
            'audience_note' => 'array',
            'work' => 'array',
            'work_note' => 'array',
            'income' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->orWhere('id', $value)
            ->first();
    }

    /**
     * @return list<array{title: string, items?: list<string>, paragraphs?: list<string>}>
     */
    public function sections(): array
    {
        return [
            [
                'title' => 'Đối tượng và tiêu chuẩn',
                'items' => $this->audience ?? [],
                'paragraphs' => $this->audience_note ?? [],
            ],
            [
                'title' => 'Công việc',
                'items' => $this->work ?? [],
                'paragraphs' => $this->work_note ?? [],
            ],
            [
                'title' => 'Nguyên tắc thu nhập',
                'paragraphs' => $this->income ?? [],
            ],
        ];
    }

    public static function uniqueSlug(string $source, ?string $requested = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($requested ?: $source) ?: 'vi-tri';
        $slug = $base;
        $index = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$index;
            $index++;
        }

        return $slug;
    }

    public static function nextOrder(): int
    {
        return (int) static::query()->max('sort_order') + 1;
    }
}
