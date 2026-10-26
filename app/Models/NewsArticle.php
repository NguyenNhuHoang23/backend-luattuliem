<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsArticle extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'published_on',
        'excerpt',
        'content',
        'image',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'published_on' => 'date',
            'published' => 'boolean',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
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

    public static function uniqueSlug(string $title, ?string $requested = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($requested ?: $title) ?: 'tin-tuc';
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
}
