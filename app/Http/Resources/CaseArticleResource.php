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
        $details = $this->details ?? [];
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
                'adoptionDate' => $attributes['adoptionDate'] ?? $attributes['adoption_date'] ?? '',
                'promulgationDoc' => $attributes['promulgationDoc'] ?? $attributes['promulgation_doc'] ?? '',
                'field' => $this->field ?: ($attributes['field'] ?? ''),
                'status' => $this->status ?: ($attributes['status'] ?? ''),
            ],
            'modal' => $this->modal ?? [
                'title' => $this->title,
                'topic' => $this->tag,
                'court' => $court,
                'result' => $this->overview,
            ],
            'decisionInfo' => $details['decisionInfo'] ?? null,
            'approvalInfo' => $details['approvalInfo'] ?? null,
            'sourceDetail' => $details['sourceDetail'] ?? null,
            'image' => $this->imageUrl($request, $details),
            'imageCaption' => $details['imageCaption'] ?? null,
            'pdfUrl' => $this->pdfUrl($request, $details),
            'pdfTitle' => $details['pdfTitle'] ?? null,
            'source' => $details['source'] ?? null,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAtIso' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function pdfUrl(Request $request, array $details): ?string
    {
        $stored = $details['pdfPath'] ?? null;
        if (is_string($stored) && str_starts_with($stored, 'cases/')) {
            return $request->getSchemeAndHttpHost().'/api/an-le-pdf/'.rawurlencode(basename($stored));
        }

        $legacy = $details['pdfUrl'] ?? null;
        if (! is_string($legacy) || $legacy === '') {
            return null;
        }

        if (preg_match('#^https?://(?:localhost|127\\.0\\.0\\.1)(?::\\d+)?(/api/.+)$#i', $legacy, $matches)) {
            return $request->getSchemeAndHttpHost().$matches[1];
        }

        return $legacy;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function imageUrl(Request $request, array $details): ?string
    {
        $stored = $details['imagePath'] ?? null;
        if (is_string($stored) && str_starts_with($stored, 'case-images/')) {
            return $request->getSchemeAndHttpHost().'/api/an-le-anh/'.rawurlencode(basename($stored));
        }

        $legacy = $details['image'] ?? null;
        if (! is_string($legacy) || $legacy === '') {
            return null;
        }

        if (preg_match('#^https?://(?:localhost|127\\.0\\.0\\.1)(?::\\d+)?(/api/.+)$#i', $legacy, $matches)) {
            return $request->getSchemeAndHttpHost().$matches[1];
        }

        return $legacy;
    }
}
