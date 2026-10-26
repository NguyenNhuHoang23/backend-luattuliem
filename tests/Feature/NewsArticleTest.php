<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAsAdmin();
    }

    public function test_requires_admin_authentication(): void
    {
        $this->flushHeaders();

        $this->getJson('/api/admin/tin-tuc')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_creates_news_article_with_uploaded_image_and_markdown(): void
    {
        $response = $this->post('/api/admin/tin-tuc', [
            'title' => 'Công ty Luật Từ Liêm tham gia bảo vệ thân chủ',
            'date' => '13-10-2026',
            'content' => "## Hồ sơ địa chính\n\nLuật sư phối hợp thu thập hồ sơ.",
            'published' => '1',
            'image' => $this->fakeImage('avatar.png'),
        ], ['Accept' => 'application/json']);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Công ty Luật Từ Liêm tham gia bảo vệ thân chủ')
            ->assertJsonPath('data.date', '13-10-2026')
            ->assertJsonPath('data.content', "## Hồ sơ địa chính\n\nLuật sư phối hợp thu thập hồ sơ.")
            ->assertJsonPath('data.published', true)
            ->assertJsonPath('data.id', 'cong-ty-luat-tu-liem-tham-gia-bao-ve-than-chu');

        $this->assertStringContainsString('/api/tin-tuc-anh/', (string) $response->json('data.image'));
        $this->assertNotEmpty($response->json('data.excerpt'));

        $article = NewsArticle::query()->first();
        $this->assertNotNull($article);
        Storage::disk('public')->assertExists($article->image);
    }

    public function test_rejects_empty_payload(): void
    {
        $this->postJson('/api/admin/tin-tuc', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'date', 'content', 'image']);
    }

    public function test_makes_duplicate_slugs_unique(): void
    {
        $this->makeNews([
            'slug' => 'huong-dan-ho-so',
            'title' => 'Hướng dẫn hồ sơ',
        ]);

        $this->post('/api/admin/tin-tuc', [
            'title' => 'Hướng dẫn hồ sơ',
            'date' => '10-10-2026',
            'content' => 'Bài thứ hai.',
            'image' => $this->fakeImage('second.png'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.id', 'huong-dan-ho-so-2');
    }

    public function test_lists_news_newest_first(): void
    {
        $this->makeNews([
            'slug' => 'tin-cu',
            'title' => 'Tin cũ',
            'published_on' => '2026-10-01',
        ]);
        $this->makeNews([
            'slug' => 'tin-moi',
            'title' => 'Tin mới',
            'published_on' => '2026-10-13',
        ]);

        $this->getJson('/api/admin/tin-tuc')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', 'tin-moi')
            ->assertJsonPath('data.1.id', 'tin-cu')
            ->assertJsonCount(2, 'data');
    }

    public function test_updates_news_article_by_slug(): void
    {
        $article = $this->makeNews();

        $this->putJson('/api/admin/tin-tuc/tin-dat-dai', [
            'title' => 'Cập nhật vụ việc đất đai',
            'date' => '14-10-2026',
            'content' => 'Nội dung markdown đã sửa.',
            'published' => false,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 'tin-dat-dai')
            ->assertJsonPath('data.title', 'Cập nhật vụ việc đất đai')
            ->assertJsonPath('data.date', '14-10-2026')
            ->assertJsonPath('data.content', 'Nội dung markdown đã sửa.')
            ->assertJsonPath('data.published', false);

        $this->assertDatabaseHas('news_articles', [
            'id' => $article->id,
            'title' => 'Cập nhật vụ việc đất đai',
            'published' => 0,
        ]);
    }

    public function test_replaces_avatar_on_update(): void
    {
        Storage::disk('public')->put('news/cu.jpg', 'old');
        $article = $this->makeNews(['image' => 'news/cu.jpg']);

        $this->post('/api/admin/tin-tuc/'.$article->id, [
            'title' => $article->title,
            'date' => '13-10-2026',
            'content' => 'Nội dung mới.',
            'image' => $this->fakeImage('moi.png'),
        ], ['Accept' => 'application/json'])
            ->assertOk();

        Storage::disk('public')->assertMissing('news/cu.jpg');
        $article->refresh();
        $this->assertNotSame('news/cu.jpg', $article->image);
        Storage::disk('public')->assertExists($article->image);
    }

    public function test_updates_news_article_by_id(): void
    {
        $article = $this->makeNews();

        $this->patchJson('/api/admin/tin-tuc/'.$article->id, [
            'title' => 'Tin đã sửa',
            'date' => '2026-10-15',
            'content' => 'Tóm tắt mới',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Tin đã sửa')
            ->assertJsonPath('data.date', '15-10-2026');
    }

    public function test_deletes_news_article_and_image(): void
    {
        Storage::disk('public')->put('news/xoa.jpg', 'file');
        $this->makeNews(['image' => 'news/xoa.jpg']);

        $this->deleteJson('/api/admin/tin-tuc/tin-dat-dai')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('news_articles', [
            'slug' => 'tin-dat-dai',
        ]);
        Storage::disk('public')->assertMissing('news/xoa.jpg');
    }

    public function test_serves_uploaded_image_without_authentication(): void
    {
        Storage::disk('public')->put('news/public.jpg', 'image-bytes');
        $this->flushHeaders();

        $this->get('/api/tin-tuc-anh/public.jpg')
            ->assertOk();
    }

    public function test_returns_not_found_for_missing_article(): void
    {
        $this->putJson('/api/admin/tin-tuc/khong-ton-tai', [
            'title' => 'Không có',
            'date' => '01-10-2026',
            'content' => 'Không có',
        ])->assertNotFound();

        $this->deleteJson('/api/admin/tin-tuc/khong-ton-tai')->assertNotFound();
    }

    private function fakeImage(string $name = 'avatar.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeNews(array $overrides = []): NewsArticle
    {
        return NewsArticle::query()->create(array_merge([
            'slug' => 'tin-dat-dai',
            'title' => 'Tin đất đai',
            'published_on' => '2026-10-13',
            'excerpt' => 'Tóm tắt',
            'content' => 'Nội dung markdown',
            'image' => 'assets/cover.jpg',
            'published' => true,
        ], $overrides));
    }
}
