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

    public function test_requires_admin_authentication(): void
    {
        $this->flushHeaders();

        $this->getJson('/api/admin/an-le')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_creates_case_article(): void
    {
        $response = $this->postJson('/api/admin/an-le', [
            'title' => 'Án lệ số 03/2016/AL về xác định quyền sở hữu đất',
            'caseNumber' => '03/2016/AL',
            'category' => 'thua-ke',
            'tag' => 'Án lệ Tòa án tối cao',
            'sourceCourt' => 'Tòa án nhân dân tối cao',
            'updatedAt' => '06-04-2016',
            'description' => 'Cha mẹ cho đất làm nhà kiên cố thì được xác định là tặng cho.',
            'overview' => 'Nội dung khái quát của án lệ.',
            'field' => 'Hôn nhân và gia đình',
            'status' => 'Đang áp dụng',
            'featured' => true,
            'keywords' => ['Ly hôn', 'Tặng cho tài sản'],
            'legalProvisions' => ['Điều 14 của Luật Hôn nhân và gia đình năm 1986'],
            'relatedDocs' => [
                [
                    'title' => 'Bộ luật Dân sự năm 2015',
                    'type' => 'Luật',
                    'issuingAgency' => 'Quốc hội',
                ],
            ],
            'caseBody' => [
                [
                    'heading' => 'NỘI DUNG VỤ ÁN',
                    'content' => 'Tóm tắt diễn biến vụ án.',
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Án lệ số 03/2016/AL về xác định quyền sở hữu đất')
            ->assertJsonPath('data.caseNumber', '03/2016/AL')
            ->assertJsonPath('data.category', 'thua-ke')
            ->assertJsonPath('data.featured', true)
            ->assertJsonPath('data.updatedAt', '06-04-2016')
            ->assertJsonPath('data.keywords.0', 'Ly hôn')
            ->assertJsonPath('data.relatedDocs.0.title', 'Bộ luật Dân sự năm 2015')
            ->assertJsonPath('data.relatedDocs.0.type', 'Luật')
            ->assertJsonPath('data.relatedDocs.0.issuingAgency', 'Quốc hội')
            ->assertJsonPath('data.caseBody.0.heading', 'NỘI DUNG VỤ ÁN')
            ->assertJsonPath('data.attributes.status', 'Đang áp dụng');

        $this->assertDatabaseHas('case_articles', [
            'case_number' => '03/2016/AL',
            'category' => 'thua-ke',
            'featured' => 1,
        ]);
    }

    public function test_rejects_empty_payload(): void
    {
        $this->postJson('/api/admin/an-le', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'case_number', 'category', 'description']);
    }

    public function test_lists_featured_cases_first(): void
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

        $this->getJson('/api/admin/an-le')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', 'an-noi-bat')
            ->assertJsonPath('data.1.id', 'an-thuong')
            ->assertJsonCount(2, 'data');
    }

    public function test_updates_case_article_by_slug(): void
    {
        $article = $this->makeCase();

        $this->putJson('/api/admin/an-le/an-le-dat-dai', [
            'title' => 'Án lệ đã cập nhật',
            'caseNumber' => '03/2016/AL',
            'category' => 'dat-dai',
            'description' => 'Mô tả mới.',
            'featured' => true,
            'status' => 'Ngừng áp dụng',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Án lệ đã cập nhật')
            ->assertJsonPath('data.featured', true)
            ->assertJsonPath('data.attributes.status', 'Ngừng áp dụng');

        $this->assertDatabaseHas('case_articles', [
            'id' => $article->id,
            'title' => 'Án lệ đã cập nhật',
            'featured' => 1,
        ]);
    }

    public function test_updates_case_article_by_id(): void
    {
        $article = $this->makeCase();

        $this->patchJson('/api/admin/an-le/'.$article->id, [
            'title' => 'Sửa theo id',
            'caseNumber' => '04/2017/AL',
            'category' => 'hop-dong',
            'description' => 'Mô tả theo id',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Sửa theo id')
            ->assertJsonPath('data.category', 'hop-dong');
    }

    public function test_deletes_case_article(): void
    {
        $this->makeCase();

        $this->deleteJson('/api/admin/an-le/an-le-dat-dai')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('case_articles', [
            'slug' => 'an-le-dat-dai',
        ]);
    }

    public function test_returns_not_found_for_missing_article(): void
    {
        $this->putJson('/api/admin/an-le/khong-ton-tai', [
            'title' => 'Không có',
            'caseNumber' => '00/0000/AL',
            'category' => 'dat-dai',
            'description' => 'Không có',
        ])->assertNotFound();

        $this->deleteJson('/api/admin/an-le/khong-ton-tai')->assertNotFound();
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
