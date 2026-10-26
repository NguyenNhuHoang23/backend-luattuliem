<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RecruitmentPost extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'description',
        'paragraphs',
        'role',
        'publication_order',
    ];

    protected function casts(): array
    {
        return [
            'paragraphs' => 'array',
            'publication_order' => 'integer',
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

    public static function uniqueSlug(string $source, ?string $requested = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($requested ?: $source) ?: 'tin-tuyen';
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
        return (int) static::query()->max('publication_order') + 1;
    }
}
