<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CaseArticle extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'case_number',
        'code',
        'category',
        'tag',
        'description',
        'overview',
        'source_court',
        'published_on',
        'featured',
        'publication_order',
        'field',
        'status',
        'keywords',
        'legal_provisions',
        'case_body',
        'related_docs',
        'attributes',
        'modal',
    ];

    protected function casts(): array
    {
        return [
            'published_on' => 'date',
            'featured' => 'boolean',
            'publication_order' => 'integer',
            'keywords' => 'array',
            'legal_provisions' => 'array',
            'case_body' => 'array',
            'related_docs' => 'array',
            'attributes' => 'array',
            'modal' => 'array',
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
        $base = Str::slug($requested ?: $source) ?: 'an-le';
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
