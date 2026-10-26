<?php

namespace Tests\Feature;

use App\Models\AboutArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAboutArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_published_about_articles_without_authentication(): void
    {
        AboutArticle::query()->create([
            'slug' => 'bai-tat',
            'title' => 'Bài tắt',
            'show_title' => true,
            'publication_order' => 1,
            'status' => false,
            'content' => 'Không hiển thị',
            'blocks' => [['type' => 'paragraph', 'text' => 'Không hiển thị']],
        ]);
        AboutArticle::query()->create([
            'slug' => 'bai-dang-hien-thi',
            'title' => 'Bài đang hiển thị',
            'show_title' => true,
            'publication_order' => 2,
            'status' => true,
            'content' => 'Nội dung công khai',
            'blocks' => [['type' => 'paragraph', 'text' => 'Nội dung công khai']],
        ]);

        $this->getJson('/api/gioi-thieu')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'bai-dang-hien-thi')
            ->assertJsonPath('data.0.title', 'Bài đang hiển thị')
            ->assertJsonPath('data.0.status', true)
            ->assertJsonPath('data.0.blocks.0.text', 'Nội dung công khai');
    }

    public function test_returns_empty_list_when_no_about_article_is_published(): void
    {
        AboutArticle::query()->create([
            'slug' => 'bai-tat',
            'title' => 'Bài tắt',
            'show_title' => true,
            'publication_order' => 1,
            'status' => false,
            'content' => 'Không hiển thị',
            'blocks' => [['type' => 'paragraph', 'text' => 'Không hiển thị']],
        ]);

        $this->getJson('/api/gioi-thieu')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_shows_published_about_article_detail(): void
    {
        AboutArticle::query()->create([
            'slug' => 'bai-dang-hien-thi',
            'title' => 'Bài đang hiển thị',
            'show_title' => true,
            'publication_order' => 1,
            'status' => true,
            'content' => 'Nội dung công khai',
            'blocks' => [['type' => 'paragraph', 'text' => 'Nội dung công khai']],
        ]);

        $this->getJson('/api/gioi-thieu/bai-dang-hien-thi')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 'bai-dang-hien-thi')
            ->assertJsonPath('data.title', 'Bài đang hiển thị')
            ->assertJsonPath('data.content', 'Nội dung công khai');
    }

    public function test_hides_unpublished_about_article_detail(): void
    {
        AboutArticle::query()->create([
            'slug' => 'bai-tat',
            'title' => 'Bài tắt',
            'show_title' => true,
            'publication_order' => 1,
            'status' => false,
            'content' => 'Không hiển thị',
            'blocks' => [['type' => 'paragraph', 'text' => 'Không hiển thị']],
        ]);

        $this->getJson('/api/gioi-thieu/bai-tat')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
