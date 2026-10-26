<?php

namespace Tests\Feature;

use App\Models\HomePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_shows_homepage_content(): void
    {
        $this->getJson('/api/trang-chu')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.hero.primaryButton.href', '/lien-he')
            ->assertJsonPath('data.fields.items.0.buttonLabel', 'Gửi yêu cầu →')
            ->assertJsonPath('data.cooperate.roles.0.name', 'Luật sư cộng tác')
            ->assertJsonPath('data.contact.buttonLabel', 'Gửi yêu cầu ↗');

        $this->assertSame(1, HomePage::query()->count());
    }

    public function test_admin_updates_text_buttons_and_slide_image(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $payload = HomePage::defaults();
        $payload['hero']['title'] = "TIÊU ĐỀ MỚI\nTRANG CHỦ";
        $payload['hero']['primaryButton'] = [
            'label' => 'Liên hệ ngay',
            'href' => '/lien-he',
        ];
        $payload['fields']['items'][0]['buttonLabel'] = 'Gửi yêu cầu đất đai';
        $payload['slides'] = [
            ['caption' => 'Ảnh trụ sở mới'],
        ];

        $this->post('/api/admin/trang-chu', [
            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'slides' => [
                [
                    'image' => $this->fakeImage(),
                ],
            ],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.hero.title', "TIÊU ĐỀ MỚI\nTRANG CHỦ")
            ->assertJsonPath('data.hero.primaryButton.label', 'Liên hệ ngay')
            ->assertJsonPath('data.fields.items.0.buttonLabel', 'Gửi yêu cầu đất đai')
            ->assertJsonPath('data.slides.0.caption', 'Ảnh trụ sở mới');

        $image = $this->getJson('/api/trang-chu')->json('data.slides.0.image');
        $this->assertNotEmpty($image);
        $this->assertStringContainsString('/api/trang-chu-anh/', $image);

        $filename = basename(parse_url($image, PHP_URL_PATH) ?: '');
        Storage::disk('public')->assertExists('home/'.$filename);
        $this->get('/api/trang-chu-anh/'.$filename)->assertOk();

        $this->assertSame(1, HomePage::query()->count());
    }

    public function test_admin_update_requires_authentication(): void
    {
        $this->putJson('/api/admin/trang-chu', HomePage::defaults())
            ->assertUnauthorized();
    }

    private function fakeImage(string $name = 'hero.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
