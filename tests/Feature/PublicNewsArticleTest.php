<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicNewsArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_published_news_without_authentication(): void
    {
        $this->makeNews([
            'slug' => 'tin-nhap',
            'title' => 'Tin nháp',
            'published' => false,
            'published_on' => '2026-10-20',
        ]);
        $this->makeNews([
            'slug' => 'tin-moi',
            'title' => 'Tin mới đăng',
            'published' => true,
            'published_on' => '2026-10-22',
        ]);
        $this->makeNews([
            'slug' => 'tin-cu',
            'title' => 'Tin cũ hơn',
            'published' => true,
            'published_on' => '2026-10-10',
        ]);

        $this->getJson('/api/tin-tuc')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 'tin-moi')
            ->assertJsonPath('data.1.id', 'tin-cu');
    }

    public function test_shows_published_news_detail(): void
    {
        $this->makeNews([
            'slug' => 'huong-dan-ho-so',
            'title' => 'Hướng dẫn hồ sơ',
            'content' => '<p>Nội dung chi tiết.</p>',
            'published' => true,
        ]);

        $this->getJson('/api/tin-tuc/huong-dan-ho-so')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 'huong-dan-ho-so')
            ->assertJsonPath('data.title', 'Hướng dẫn hồ sơ')
            ->assertJsonPath('data.content', '<p>Nội dung chi tiết.</p>');
    }

    public function test_hides_draft_news_from_detail(): void
    {
        $this->makeNews([
            'slug' => 'tin-nhap',
            'title' => 'Tin nháp',
            'published' => false,
        ]);

        $this->getJson('/api/tin-tuc/tin-nhap')
            ->assertNotFound()
            ->assertJsonPath('success', false);
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
            'content' => '<p>Nội dung</p>',
            'image' => 'news/demo.jpg',
            'published' => true,
        ], $overrides));
    }
}
