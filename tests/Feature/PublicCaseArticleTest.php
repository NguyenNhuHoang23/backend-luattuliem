<?php

namespace Tests\Feature;

use App\Models\CaseArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCaseArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_case_articles_without_authentication(): void
    {
        $this->makeCase([
            'slug' => 'an-thuong',
            'title' => 'Án thường',
            'featured' => false,
            'publication_order' => 2,
        ]);
        $this->makeCase([
            'slug' => 'an-noi-bat',
            'title' => 'Án nổi bật',
            'featured' => true,
            'publication_order' => 1,
        ]);

        $this->getJson('/api/an-le')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 'an-thuong')
            ->assertJsonPath('data.1.id', 'an-noi-bat');
    }

    public function test_shows_case_article_detail(): void
    {
        $this->makeCase([
            'slug' => '03-2016-al',
            'title' => 'Án lệ số 03/2016/AL',
            'overview' => 'Nội dung khái quát.',
            'related_docs' => [
                [
                    'title' => 'Luật Hôn nhân và gia đình năm 2014',
                    'type' => 'Luật',
                    'issuing_agency' => 'Quốc hội',
                ],
            ],
        ]);

        $this->getJson('/api/an-le/03-2016-al')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', '03-2016-al')
            ->assertJsonPath('data.title', 'Án lệ số 03/2016/AL')
            ->assertJsonPath('data.overview', 'Nội dung khái quát.')
            ->assertJsonPath('data.caseBody.0.heading', 'NỘI DUNG')
            ->assertJsonPath('data.relatedDocs.0.title', 'Luật Hôn nhân và gia đình năm 2014')
            ->assertJsonPath('data.relatedDocs.0.issuingAgency', 'Quốc hội');
    }

    public function test_returns_not_found_for_missing_case(): void
    {
        $this->getJson('/api/an-le/khong-ton-tai')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCase(array $overrides = []): CaseArticle
    {
        return CaseArticle::query()->create(array_merge([
            'slug' => 'an-le-dat-dai',
            'title' => 'Án lệ đất đai',
            'case_number' => '03/2016/AL',
            'code' => 'Án lệ số 03/2016/AL',
            'category' => 'dat-dai',
            'tag' => 'Án lệ Tòa án tối cao',
            'description' => 'Mô tả ngắn',
            'overview' => 'Khái quát',
            'source_court' => 'TAND tối cao',
            'published_on' => '2016-04-06',
            'featured' => false,
            'publication_order' => 1,
            'field' => 'Đất đai',
            'status' => 'Đang áp dụng',
            'keywords' => ['Đất đai'],
            'legal_provisions' => ['Điều 1'],
            'case_body' => [['heading' => 'NỘI DUNG', 'content' => 'Chi tiết']],
            'related_docs' => [],
            'attributes' => ['status' => 'Đang áp dụng', 'field' => 'Đất đai'],
            'modal' => ['title' => 'Án lệ đất đai'],
        ], $overrides));
    }
}
