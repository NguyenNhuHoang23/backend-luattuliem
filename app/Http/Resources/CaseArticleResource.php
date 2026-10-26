<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attributes = $this->attributes ?? [];
        $court = $this->source_court;

        return [
            'id' => $this->slug,
            'key' => $this->id,
            'title' => $this->title,
            'caseNumber' => $this->case_number,
            'code' => $this->code ?: $this->case_number,
            'category' => $this->category,
            'tag' => $this->tag,
            'description' => $this->description,
            'overview' => $this->overview,
            'sourceCourt' => $court,
            'updatedAt' => $this->published_on?->format('d-m-Y'),
            'featured' => (bool) $this->featured,
            'publicationOrder' => $this->publication_order,
            'keywords' => $this->keywords ?? [],
            'legalProvisions' => $this->legal_provisions ?? [],
            'caseBody' => $this->case_body ?? [],
            'relatedDocs' => collect($this->related_docs ?? [])
                ->map(fn ($doc) => [
                    'title' => $doc['title'] ?? '',
                    'type' => $doc['type'] ?? '',
                    'issuingAgency' => $doc['issuing_agency'] ?? $doc['issuingAgency'] ?? '',
                ])
                ->values()
                ->all(),
            'attributes' => [
                'agency' => $attributes['agency'] ?? $court,
                'field' => $this->field ?: ($attributes['field'] ?? ''),
                'status' => $this->status ?: ($attributes['status'] ?? ''),
            ],
            'modal' => $this->modal ?? [
                'title' => $this->title,
                'topic' => $this->tag,
                'court' => $court,
                'result' => $this->overview,
            ],
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAtIso' => $this->updated_at?->toIso8601String(),
        ];
    }
}
