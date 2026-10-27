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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function payloadFrom(array $data, ?self $existing = null): array
    {
        $title = $data['title'];
        $caseNumber = $data['case_number'];
        $court = $data['source_court'] ?? $existing?->source_court;
        $tag = $data['tag'] ?? $existing?->tag;
        $overview = $data['overview'] ?? $existing?->overview;
        $field = $data['field'] ?? $existing?->field;
        $status = $data['status'] ?? $existing?->status ?? 'Đang áp dụng';

        $attributes = [
            'title' => $title,
            'case_number' => $caseNumber,
            'code' => $data['code'] ?? $existing?->code ?? $caseNumber,
            'category' => $data['category'],
            'tag' => $tag,
            'description' => $data['description'],
            'overview' => $overview,
            'source_court' => $court,
            'published_on' => $data['published_on'] ?? $existing?->published_on,
            'featured' => $data['featured'] ?? $existing?->featured ?? false,
            'publication_order' => $existing
                ? ($data['publication_order'] ?? $existing->publication_order)
                : static::nextOrder(),
            'field' => $field,
            'status' => $status,
            'keywords' => $data['keywords'] ?? $existing?->keywords ?? [],
            'legal_provisions' => $data['legal_provisions'] ?? $existing?->legal_provisions ?? [],
            'case_body' => $data['case_body'] ?? $existing?->case_body ?? [],
            'related_docs' => $data['related_docs'] ?? $existing?->related_docs ?? [],
            'attributes' => [
                'agency' => $court,
                'field' => $field,
                'status' => $status,
            ],
            'modal' => [
                'title' => $title,
                'topic' => $tag,
                'court' => $court,
                'result' => $overview,
            ],
        ];

        if (! $existing || filled($data['slug'] ?? null)) {
            $attributes['slug'] = static::uniqueSlug(
                $caseNumber ?: $title,
                $data['slug'] ?? null,
                $existing?->id,
            );
        }

        return $attributes;
    }
}
