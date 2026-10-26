<?php

namespace Tests\Feature;

use App\Models\RecruitmentPage;
use App\Models\RecruitmentPost;
use App\Models\RecruitmentRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRecruitmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_recruitment_page_without_authentication(): void
    {
        RecruitmentPage::query()->create([
            'title' => 'Tuyển cộng tác viên',
            'intro' => 'Giới thiệu công khai.',
        ]);

        $this->getJson('/api/tuyen-dung')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Tuyển cộng tác viên')
            ->assertJsonPath('data.intro', 'Giới thiệu công khai.');
    }

    public function test_lists_and_shows_recruitment_roles(): void
    {
        $this->makeRole([
            'slug' => 'ctv-phap-ly',
            'name' => 'Cộng tác viên pháp lý',
            'sort_order' => 2,
        ]);
        $this->makeRole([
            'slug' => 'luat-su',
            'name' => 'Luật sư cộng tác',
            'title' => 'Tuyển luật sư cộng tác theo vụ việc',
            'audience' => ['Có chứng chỉ hành nghề luật sư.'],
            'sort_order' => 1,
        ]);

        $this->getJson('/api/tuyen-dung/vi-tri')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 'luat-su')
            ->assertJsonPath('data.1.id', 'ctv-phap-ly');

        $this->getJson('/api/tuyen-dung/vi-tri/luat-su')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 'luat-su')
            ->assertJsonPath('data.name', 'Luật sư cộng tác')
            ->assertJsonPath('data.audience.0', 'Có chứng chỉ hành nghề luật sư.')
            ->assertJsonPath('data.sections.0.title', 'Đối tượng và tiêu chuẩn');
    }

    public function test_lists_and_shows_recruitment_posts(): void
    {
        $this->makePost([
            'slug' => 'tin-cu',
            'title' => 'Tin cũ',
            'publication_order' => 1,
        ]);
        $this->makePost([
            'slug' => 'tin-moi',
            'title' => 'Tin mới tại Hà Nội',
            'description' => 'Cần CTV hỗ trợ hồ sơ.',
            'paragraphs' => ['Ưu tiên ứng viên tại Hà Nội.'],
            'publication_order' => 2,
        ]);

        $this->getJson('/api/tuyen-dung/tin')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 'tin-moi')
            ->assertJsonPath('data.1.id', 'tin-cu');

        $this->getJson('/api/tuyen-dung/tin/tin-moi')
            ->assertOk()
            ->assertJsonPath('data.title', 'Tin mới tại Hà Nội')
            ->assertJsonPath('data.paragraphs.0', 'Ưu tiên ứng viên tại Hà Nội.');
    }

    public function test_returns_not_found_for_missing_role_and_post(): void
    {
        $this->getJson('/api/tuyen-dung/vi-tri/khong-ton-tai')
            ->assertNotFound()
            ->assertJsonPath('success', false);

        $this->getJson('/api/tuyen-dung/tin/khong-ton-tai')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRole(array $overrides = []): RecruitmentRole
    {
        return RecruitmentRole::query()->create(array_merge([
            'slug' => 'luat-su-cong-tac',
            'name' => 'Luật sư cộng tác',
            'title' => 'Tuyển luật sư cộng tác theo vụ việc',
            'apply_label' => 'Gửi hồ sơ ứng tuyển Luật sư',
            'audience' => ['Có chứng chỉ hành nghề.'],
            'audience_note' => [],
            'work' => ['Tư vấn pháp lý.'],
            'work_note' => [],
            'income' => ['Thù lao theo vụ việc.'],
            'sort_order' => 1,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makePost(array $overrides = []): RecruitmentPost
    {
        return RecruitmentPost::query()->create(array_merge([
            'slug' => 'tin-tuyen-ha-noi',
            'title' => 'Tin tuyển Hà Nội',
            'description' => 'Mô tả tin tuyển',
            'paragraphs' => ['Nội dung thêm'],
            'role' => 'Luật sư cộng tác',
            'publication_order' => 1,
        ], $overrides));
    }
}
