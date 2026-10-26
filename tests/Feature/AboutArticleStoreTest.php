<?php

namespace Tests\Feature;

use App\Models\AboutArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutArticleStoreTest extends TestCase
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

        $this->getJson('/api/admin/gioi-thieu')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_creates_about_article_from_content(): void
    {
        $response = $this->postJson('/api/admin/gioi-thieu', [
            'title' => 'Công ty Luật Từ Liêm',
            'show_title' => true,
            'content' => "Đoạn mở đầu.\n\n## Sứ mệnh\n\nNội dung sứ mệnh.\n\n>> Liên hệ | /lien-he",
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Công ty Luật Từ Liêm')
            ->assertJsonPath('data.showTitle', true)
            ->assertJsonPath('data.id', 'cong-ty-luat-tu-liem')
            ->assertJsonPath('data.publicationOrder', 1)
            ->assertJsonPath('data.blocks.0.type', 'paragraph')
            ->assertJsonPath('data.blocks.1.type', 'heading')
            ->assertJsonPath('data.blocks.1.text', 'Sứ mệnh')
            ->assertJsonPath('data.blocks.3.type', 'cta')
            ->assertJsonPath('data.blocks.3.link', '/lien-he');

        $this->assertDatabaseHas('about_articles', [
            'title' => 'Công ty Luật Từ Liêm',
            'slug' => 'cong-ty-luat-tu-liem',
        ]);
    }

    public function test_creates_about_article_from_blocks(): void
    {
        $response = $this->postJson('/api/admin/gioi-thieu', [
            'title' => 'Bài giới thiệu mới',
            'showTitle' => false,
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Nội dung giới thiệu.'],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.showTitle', false)
            ->assertJsonPath('data.blocks.0.text', 'Nội dung giới thiệu.');
    }

    public function test_rejects_empty_payload(): void
    {
        $this->postJson('/api/admin/gioi-thieu', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content', 'blocks']);
    }

    public function test_increments_publication_order_and_unique_slug(): void
    {
        AboutArticle::query()->create([
            'slug' => 've-chung-toi',
            'title' => 'Về chúng tôi',
            'show_title' => true,
            'publication_order' => 1,
            'content' => 'Nội dung',
            'blocks' => [['type' => 'paragraph', 'text' => 'Nội dung']],
        ]);

        $response = $this->postJson('/api/admin/gioi-thieu', [
            'title' => 'Về chúng tôi',
            'content' => 'Bài thứ hai.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.id', 've-chung-toi-2')
            ->assertJsonPath('data.publicationOrder', 2);
    }

    public function test_lists_about_articles_by_publication_order(): void
    {
        AboutArticle::query()->create([
            'slug' => 'bai-sau',
            'title' => 'Bài sau',
            'show_title' => true,
            'publication_order' => 2,
            'content' => 'Sau',
            'blocks' => [['type' => 'paragraph', 'text' => 'Sau']],
        ]);
        AboutArticle::query()->create([
            'slug' => 'bai-truoc',
            'title' => 'Bài trước',
            'show_title' => true,
            'publication_order' => 1,
            'content' => 'Trước',
            'blocks' => [['type' => 'paragraph', 'text' => 'Trước']],
        ]);

        $this->getJson('/api/admin/gioi-thieu')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', 'bai-truoc')
            ->assertJsonPath('data.0.status', false)
            ->assertJsonPath('data.1.id', 'bai-sau')
            ->assertJsonCount(2, 'data');
    }

    public function test_turning_one_article_on_turns_the_others_off(): void
    {
        $first = AboutArticle::query()->create([
            'slug' => 'bai-mot',
            'title' => 'Bài một',
            'show_title' => true,
            'publication_order' => 1,
            'status' => true,
            'content' => 'Một',
            'blocks' => [['type' => 'paragraph', 'text' => 'Một']],
        ]);
        $second = AboutArticle::query()->create([
            'slug' => 'bai-hai',
            'title' => 'Bài hai',
            'show_title' => true,
            'publication_order' => 2,
            'status' => false,
            'content' => 'Hai',
            'blocks' => [['type' => 'paragraph', 'text' => 'Hai']],
        ]);

        $this->patchJson('/api/admin/gioi-thieu/'.$second->slug.'/status', [
            'status' => true,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 'bai-hai')
            ->assertJsonPath('data.status', true);

        $this->assertDatabaseHas('about_articles', [
            'id' => $second->id,
            'status' => 1,
        ]);
        $this->assertDatabaseHas('about_articles', [
            'id' => $first->id,
            'status' => 0,
        ]);
    }

    public function test_creating_an_active_article_deactivates_the_current_one(): void
    {
        AboutArticle::query()->create([
            'slug' => 'bai-cu',
            'title' => 'Bài cũ',
            'show_title' => true,
            'publication_order' => 1,
            'status' => true,
            'content' => 'Cũ',
            'blocks' => [['type' => 'paragraph', 'text' => 'Cũ']],
        ]);

        $this->postJson('/api/admin/gioi-thieu', [
            'title' => 'Bài mới',
            'content' => 'Nội dung mới',
            'status' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', true)
            ->assertJsonPath('data.id', 'bai-moi');

        $this->assertDatabaseHas('about_articles', [
            'slug' => 'bai-cu',
            'status' => 0,
        ]);
        $this->assertDatabaseHas('about_articles', [
            'slug' => 'bai-moi',
            'status' => 1,
        ]);
    }

    public function test_turning_an_article_off_does_not_activate_others(): void
    {
        $article = AboutArticle::query()->create([
            'slug' => 'bai-dang-bat',
            'title' => 'Bài đang bật',
            'show_title' => true,
            'publication_order' => 1,
            'status' => true,
            'content' => 'Nội dung',
            'blocks' => [['type' => 'paragraph', 'text' => 'Nội dung']],
        ]);
        AboutArticle::query()->create([
            'slug' => 'bai-tat',
            'title' => 'Bài tắt',
            'show_title' => true,
            'publication_order' => 2,
            'status' => false,
            'content' => 'Tắt',
            'blocks' => [['type' => 'paragraph', 'text' => 'Tắt']],
        ]);

        $this->patchJson('/api/admin/gioi-thieu/'.$article->id.'/status', [
            'status' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', false);

        $this->assertDatabaseHas('about_articles', ['slug' => 'bai-dang-bat', 'status' => 0]);
        $this->assertDatabaseHas('about_articles', ['slug' => 'bai-tat', 'status' => 0]);
    }

    public function test_updates_about_article_by_slug(): void
    {
        $article = AboutArticle::query()->create([
            'slug' => 've-chung-toi',
            'title' => 'Về chúng tôi',
            'show_title' => true,
            'publication_order' => 1,
            'content' => 'Nội dung cũ',
            'blocks' => [['type' => 'paragraph', 'text' => 'Nội dung cũ']],
        ]);

        $this->putJson('/api/admin/gioi-thieu/ve-chung-toi', [
            'title' => 'Giới thiệu Luật Từ Liêm',
            'show_title' => false,
            'content' => "Nội dung mới.\n\n## Định hướng\n\nKết nối luật sư và cộng tác viên.",
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 've-chung-toi')
            ->assertJsonPath('data.title', 'Giới thiệu Luật Từ Liêm')
            ->assertJsonPath('data.showTitle', false)
            ->assertJsonPath('data.blocks.1.text', 'Định hướng');

        $this->assertDatabaseHas('about_articles', [
            'id' => $article->id,
            'title' => 'Giới thiệu Luật Từ Liêm',
            'show_title' => false,
        ]);
    }

    public function test_updates_about_article_by_id(): void
    {
        $article = AboutArticle::query()->create([
            'slug' => 've-chung-toi',
            'title' => 'Về chúng tôi',
            'show_title' => true,
            'publication_order' => 1,
            'content' => 'Nội dung',
            'blocks' => [['type' => 'paragraph', 'text' => 'Nội dung']],
        ]);

        $this->patchJson('/api/admin/gioi-thieu/'.$article->id, [
            'title' => 'Bài đã sửa',
            'content' => 'Nội dung đã sửa',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Bài đã sửa');
    }

    public function test_deletes_about_article(): void
    {
        AboutArticle::query()->create([
            'slug' => 've-chung-toi',
            'title' => 'Về chúng tôi',
            'show_title' => true,
            'publication_order' => 1,
            'content' => 'Nội dung',
            'blocks' => [['type' => 'paragraph', 'text' => 'Nội dung']],
        ]);

        $this->deleteJson('/api/admin/gioi-thieu/ve-chung-toi')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('about_articles', [
            'slug' => 've-chung-toi',
        ]);
    }

    public function test_returns_not_found_for_missing_article(): void
    {
        $this->putJson('/api/admin/gioi-thieu/khong-ton-tai', [
            'title' => 'Không có',
            'content' => 'Không có',
        ])->assertNotFound();

        $this->deleteJson('/api/admin/gioi-thieu/khong-ton-tai')->assertNotFound();
    }
}
