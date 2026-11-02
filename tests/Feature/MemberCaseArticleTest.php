<?php

namespace Tests\Feature;

use App\Models\CaseArticle;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberCaseArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_publishes_case_article_at_the_top(): void
    {
        $this->makeCase([
            'slug' => 'an-cu',
            'title' => 'Án cũ',
            'featured' => true,
            'publication_order' => 5,
        ]);

        $director = $this->makeMember([
            'name' => 'Giám đốc',
            'username' => 'giamdoc-anle',
            'role' => 'director',
        ]);
        $this->withToken($director->createMemberToken());

        $created = $this->postJson('/api/thanh-vien/an-le', [
            'title' => 'Án lệ mới nhất',
            'caseNumber' => '99/2026/AL',
            'category' => 'dat-dai',
            'description' => 'Bài mới đăng lên đầu trang.',
            'publicationOrder' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Án lệ mới nhất')
            ->assertJsonPath('data.publicationOrder', 6);

        $slug = $created->json('data.id');
        $this->assertNotEmpty($slug);

        $this->flushHeaders();

        $this->getJson('/api/an-le')
            ->assertOk()
            ->assertJsonPath('data.0.id', $slug)
            ->assertJsonPath('data.0.title', 'Án lệ mới nhất')
            ->assertJsonPath('data.1.id', 'an-cu');
    }

    public function test_collaborator_cannot_publish_case_article(): void
    {
        $ctv = $this->makeMember([
            'name' => 'Cộng tác viên',
            'username' => 'ctv-anle',
            'role' => 'ctv',
        ]);
        $this->withToken($ctv->createMemberToken());

        $this->postJson('/api/thanh-vien/an-le', [
            'title' => 'Không được đăng',
            'caseNumber' => '01/2026/AL',
            'category' => 'dat-dai',
            'description' => 'CTV không được đăng án lệ.',
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('case_articles', 0);
    }

    public function test_guest_cannot_publish_case_article(): void
    {
        $this->postJson('/api/thanh-vien/an-le', [
            'title' => 'Khách',
            'caseNumber' => '02/2026/AL',
            'category' => 'dat-dai',
            'description' => 'Chưa đăng nhập.',
        ])->assertUnauthorized();
    }

    public function test_director_updates_and_deletes_case_article(): void
    {
        $article = $this->makeCase();
        $director = $this->makeMember([
            'name' => 'Giám đốc',
            'username' => 'giamdoc-sua-anle',
            'role' => 'director',
        ]);
        $this->withToken($director->createMemberToken());

        $this->putJson('/api/thanh-vien/an-le/'.$article->slug, [
            'title' => 'Án lệ đã cập nhật',
            'caseNumber' => '03/2016/AL',
            'category' => 'dat-dai',
            'description' => 'Mô tả mới.',
            'status' => 'Ngừng áp dụng',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Án lệ đã cập nhật')
            ->assertJsonPath('data.attributes.status', 'Ngừng áp dụng');

        $this->deleteJson('/api/thanh-vien/an-le/'.$article->slug)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('case_articles', [
            'slug' => 'an-le-dat-dai',
        ]);
    }

    public function test_collaborator_cannot_update_or_delete_case_article(): void
    {
        $article = $this->makeCase();
        $ctv = $this->makeMember([
            'username' => 'ctv-sua-anle',
            'role' => 'ctv',
        ]);
        $this->withToken($ctv->createMemberToken());

        $this->putJson('/api/thanh-vien/an-le/'.$article->slug, [
            'title' => 'Không sửa được',
            'caseNumber' => '03/2016/AL',
            'category' => 'dat-dai',
            'description' => 'CTV không được sửa.',
        ])->assertForbidden();

        $this->deleteJson('/api/thanh-vien/an-le/'.$article->slug)
            ->assertForbidden();

        $this->assertDatabaseHas('case_articles', [
            'slug' => 'an-le-dat-dai',
        ]);
    }

    public function test_director_attaches_and_serves_case_pdf(): void
    {
        Storage::fake('public');

        $director = $this->makeMember([
            'name' => 'Giám đốc',
            'username' => 'giamdoc-pdf-anle',
            'role' => 'director',
        ]);
        $this->withToken($director->createMemberToken());

        $created = $this->post('/api/thanh-vien/an-le', [
            'title' => 'Án lệ có PDF',
            'caseNumber' => '11/2026/AL',
            'category' => 'lao-dong',
            'description' => 'Bài có file PDF đính kèm.',
            'caseBody' => json_encode([
                ['heading' => 'NỘI DUNG', 'content' => 'In **đậm** phần quan trọng.'],
            ], JSON_UNESCAPED_UNICODE),
            'pdf_title' => 'Toàn văn án lệ',
            'pdf' => UploadedFile::fake()->create('toan-van.pdf', 80, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pdfTitle', 'Toàn văn án lệ')
            ->assertJsonPath('data.caseBody.0.content', 'In **đậm** phần quan trọng.');

        $pdfUrl = (string) $created->json('data.pdfUrl');
        $this->assertStringContainsString('/api/an-le-pdf/', $pdfUrl);

        $filename = basename(parse_url($pdfUrl, PHP_URL_PATH) ?: '');
        $this->get('/api/an-le-pdf/'.$filename)->assertOk();

        $slug = $created->json('data.id');
        $this->post('/api/thanh-vien/an-le/'.$slug, [
            'title' => 'Án lệ có PDF',
            'caseNumber' => '11/2026/AL',
            'category' => 'lao-dong',
            'description' => 'Bài có file PDF đính kèm.',
            'remove_pdf' => '1',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.pdfUrl', null);

        $this->get('/api/an-le-pdf/'.$filename)->assertNotFound();
    }

    public function test_director_attaches_and_serves_case_image(): void
    {
        Storage::fake('public');

        $director = $this->makeMember([
            'name' => 'Giám đốc',
            'username' => 'giamdoc-anh-anle',
            'role' => 'director',
        ]);
        $this->withToken($director->createMemberToken());

        $created = $this->post('/api/thanh-vien/an-le', [
            'title' => 'Án lệ có ảnh',
            'caseNumber' => '12/2026/AL',
            'category' => 'lao-dong',
            'description' => 'Bài có ảnh minh họa.',
            'image_caption' => 'Ảnh minh họa vụ việc',
            'image' => $this->fakePng('minh-hoa.png'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.imageCaption', 'Ảnh minh họa vụ việc');

        $imageUrl = (string) $created->json('data.image');
        $this->assertStringContainsString('/api/an-le-anh/', $imageUrl);

        $filename = basename(parse_url($imageUrl, PHP_URL_PATH) ?: '');
        $this->get('/api/an-le-anh/'.$filename)->assertOk();

        $inserted = $this->post('/api/thanh-vien/an-le-anh', [
            'image' => $this->fakePng('chen-noi-dung.png'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertStringContainsString('/api/an-le-anh/', (string) $inserted->json('data.url'));

        $slug = $created->json('data.id');
        $this->post('/api/thanh-vien/an-le/'.$slug, [
            'title' => 'Án lệ có ảnh',
            'caseNumber' => '12/2026/AL',
            'category' => 'lao-dong',
            'description' => 'Bài có ảnh minh họa.',
            'remove_image' => '1',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.image', null);

        $this->get('/api/an-le-anh/'.$filename)->assertNotFound();
    }

    private function fakePng(string $name = 'minh-hoa.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeMember(array $overrides = []): Member
    {
        return Member::query()->create(array_merge([
            'name' => 'Nguyễn Văn A',
            'username' => 'tv'.uniqid(),
            'password' => 'Matkhau@1',
            'role' => 'ctv',
            'phone' => '0987 111 222',
            'email' => 'tv@example.com',
            'area' => 'Hà Nội',
            'status' => 'active',
            'join_date' => '24-10-2026',
        ], $overrides));
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
