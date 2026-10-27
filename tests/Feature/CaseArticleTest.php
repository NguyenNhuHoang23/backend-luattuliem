<?php

namespace Tests\Feature;

use App\Models\CaseArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_admin_no_longer_manages_case_articles(): void
    {
        $article = $this->makeCase();

        $this->getJson('/api/admin/an-le')
            ->assertNotFound();

        $this->postJson('/api/admin/an-le', [
            'title' => 'Không còn ở admin',
            'caseNumber' => '01/2026/AL',
            'category' => 'dat-dai',
            'description' => 'Admin không quản lý án lệ.',
        ])->assertNotFound();

        $this->putJson('/api/admin/an-le/'.$article->slug, [
            'title' => 'Không sửa được',
            'caseNumber' => '03/2016/AL',
            'category' => 'dat-dai',
            'description' => 'Không sửa được.',
        ])->assertNotFound();

        $this->deleteJson('/api/admin/an-le/'.$article->slug)
            ->assertNotFound();
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
