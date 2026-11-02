<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

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
        'details',
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
            'details' => 'array',
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

        $existingAttributes = $existing?->attributes ?? [];

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
                'agency' => $existingAttributes['agency'] ?? $court,
                'field' => $field,
                'status' => $status,
                'adoptionDate' => $data['adoption_date']
                    ?? $existingAttributes['adoptionDate']
                    ?? null,
                'promulgationDoc' => $data['promulgation_doc']
                    ?? $existingAttributes['promulgationDoc']
                    ?? null,
            ],
            'modal' => $data['modal'] ?? $existing?->modal ?? [
                'title' => $title,
                'topic' => $tag,
                'court' => $court,
                'result' => $overview,
            ],
            'details' => $data['details'] ?? $existing?->details,
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

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function attributesFromImported(array $item): array
    {
        $meta = is_array($item['attributes'] ?? null) ? $item['attributes'] : [];

        return [
            'slug' => $item['id'] ?? static::uniqueSlug((string) ($item['caseNumber'] ?? $item['title'] ?? 'an-le')),
            'title' => $item['title'],
            'case_number' => $item['caseNumber'] ?? $item['case_number'] ?? '',
            'code' => $item['code'] ?? $item['caseNumber'] ?? null,
            'category' => $item['category'],
            'tag' => $item['tag'] ?? null,
            'description' => $item['description'],
            'overview' => $item['overview'] ?? null,
            'source_court' => $item['sourceCourt'] ?? $item['source_court'] ?? ($meta['agency'] ?? null),
            'published_on' => static::parseImportedDate($item['updatedAt'] ?? $item['published_on'] ?? null),
            'featured' => (bool) ($item['featured'] ?? false),
            'publication_order' => (int) ($item['publicationOrder'] ?? $item['publication_order'] ?? static::nextOrder()),
            'field' => $meta['field'] ?? $item['field'] ?? null,
            'status' => $meta['status'] ?? $item['status'] ?? 'Đang áp dụng',
            'keywords' => $item['keywords'] ?? [],
            'legal_provisions' => $item['legalProvisions'] ?? $item['legal_provisions'] ?? [],
            'case_body' => $item['caseBody'] ?? $item['case_body'] ?? [],
            'related_docs' => collect($item['relatedDocs'] ?? $item['related_docs'] ?? [])
                ->map(fn ($doc) => [
                    'title' => $doc['title'] ?? '',
                    'type' => $doc['type'] ?? '',
                    'issuing_agency' => $doc['issuingAgency'] ?? $doc['issuing_agency'] ?? '',
                ])
                ->filter(fn ($doc) => $doc['title'] !== '')
                ->values()
                ->all(),
            'attributes' => [
                'agency' => $meta['agency'] ?? $item['sourceCourt'] ?? null,
                'adoptionDate' => $meta['adoptionDate'] ?? $meta['adoption_date'] ?? null,
                'promulgationDoc' => $meta['promulgationDoc'] ?? $meta['promulgation_doc'] ?? null,
                'field' => $meta['field'] ?? null,
                'status' => $meta['status'] ?? null,
            ],
            'modal' => $item['modal'] ?? null,
            'details' => array_filter([
                'decisionInfo' => $item['decisionInfo'] ?? $item['decision_info'] ?? null,
                'approvalInfo' => $item['approvalInfo'] ?? $item['approval_info'] ?? null,
                'sourceDetail' => $item['sourceDetail'] ?? $item['source_detail'] ?? null,
                'image' => $item['image'] ?? null,
                'imageCaption' => $item['imageCaption'] ?? $item['image_caption'] ?? null,
                'pdfUrl' => $item['pdfUrl'] ?? $item['pdf_url'] ?? null,
                'pdfTitle' => $item['pdfTitle'] ?? $item['pdf_title'] ?? null,
                'source' => $item['source'] ?? null,
            ]),
        ];
    }

    private static function parseImportedDate(mixed $raw): ?string
    {
        if (! filled($raw)) {
            return null;
        }

        $value = trim((string) $raw);
        $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }
}
