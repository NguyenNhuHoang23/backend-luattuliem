<?php

namespace Tests\Feature;

use App\Models\RecruitmentPage;
use App\Models\RecruitmentPost;
use App\Models\RecruitmentRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentTest extends TestCase
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

        $this->getJson('/api/admin/tuyen-dung')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);

        $this->getJson('/api/admin/tuyen-dung/vi-tri')
            ->assertUnauthorized();

        $this->postJson('/api/admin/tuyen-dung/tin', [])
            ->assertUnauthorized();
    }

    public function test_shows_and_updates_recruitment_page(): void
    {
        $this->getJson('/api/admin/tuyen-dung')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Tuyển luật sư và cộng tác viên');

        $this->putJson('/api/admin/tuyen-dung', [
            'title' => 'Tuyển cộng tác viên pháp lý',
            'intro' => 'Đoạn giới thiệu mới.',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Tuyển cộng tác viên pháp lý')
            ->assertJsonPath('data.intro', 'Đoạn giới thiệu mới.');

        $this->assertDatabaseHas('recruitment_pages', [
            'title' => 'Tuyển cộng tác viên pháp lý',
        ]);
        $this->assertSame(1, RecruitmentPage::query()->count());
    }

    public function test_rejects_empty_page_payload(): void
    {
        $this->putJson('/api/admin/tuyen-dung', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'intro']);
    }

    public function test_creates_recruitment_role(): void
    {
        $response = $this->postJson('/api/admin/tuyen-dung/vi-tri', [
            'name' => 'Luật sư cộng tác',
            'title' => 'Tuyển luật sư cộng tác theo vụ việc',
            'applyLabel' => 'Gửi hồ sơ ứng tuyển Luật sư',
            'audience' => ['Có Chứng chỉ hành nghề luật sư.'],
            'audienceNote' => ['Hình thức hợp tác được thống nhất từng trường hợp.'],
            'work' => ['Nghiên cứu hồ sơ, tư vấn và đề xuất phương án.'],
            'workNote' => [],
            'income' => ['Thù lao thỏa thuận theo vụ việc.'],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Luật sư cộng tác')
            ->assertJsonPath('data.applyLabel', 'Gửi hồ sơ ứng tuyển Luật sư')
            ->assertJsonPath('data.audience.0', 'Có Chứng chỉ hành nghề luật sư.')
            ->assertJsonPath('data.sections.0.title', 'Đối tượng và tiêu chuẩn')
            ->assertJsonPath('data.sections.2.paragraphs.0', 'Thù lao thỏa thuận theo vụ việc.')
            ->assertJsonPath('data.id', 'luat-su-cong-tac');

        $this->assertDatabaseHas('recruitment_roles', [
            'slug' => 'luat-su-cong-tac',
            'name' => 'Luật sư cộng tác',
        ]);
    }

    public function test_rejects_empty_role_payload(): void
    {
        $this->postJson('/api/admin/tuyen-dung/vi-tri', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_lists_roles_by_sort_order(): void
    {
        $this->makeRole([
            'slug' => 'ctv-phap-ly',
            'name' => 'Cộng tác viên pháp lý',
            'sort_order' => 2,
        ]);
        $this->makeRole([
            'slug' => 'luat-su',
            'name' => 'Luật sư cộng tác',
            'sort_order' => 1,
        ]);

        $this->getJson('/api/admin/tuyen-dung/vi-tri')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 'luat-su')
            ->assertJsonPath('data.1.id', 'ctv-phap-ly');
    }

    public function test_updates_role_by_slug(): void
    {
        $role = $this->makeRole();

        $this->putJson('/api/admin/tuyen-dung/vi-tri/luat-su-cong-tac', [
            'name' => 'Luật sư cộng tác cập nhật',
            'applyLabel' => 'Nộp hồ sơ luật sư',
            'work' => ['Soạn thảo văn bản pháp lý.'],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Luật sư cộng tác cập nhật')
            ->assertJsonPath('data.applyLabel', 'Nộp hồ sơ luật sư')
            ->assertJsonPath('data.work.0', 'Soạn thảo văn bản pháp lý.');

        $this->assertDatabaseHas('recruitment_roles', [
            'id' => $role->id,
            'name' => 'Luật sư cộng tác cập nhật',
        ]);
    }

    public function test_deletes_role(): void
    {
        $this->makeRole();

        $this->deleteJson('/api/admin/tuyen-dung/vi-tri/luat-su-cong-tac')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('recruitment_roles', [
            'slug' => 'luat-su-cong-tac',
        ]);
    }

    public function test_creates_and_lists_recruitment_posts(): void
    {
        $this->postJson('/api/admin/tuyen-dung/tin', [
            'title' => 'Tuyển cộng tác viên tại Hà Nội',
            'description' => 'Cần CTV hỗ trợ hồ sơ đất đai.',
            'paragraphs' => ['Ưu tiên ứng viên có thời gian tại Hà Nội.'],
            'role' => 'Cộng tác viên pháp lý',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Tuyển cộng tác viên tại Hà Nội')
            ->assertJsonPath('data.role', 'Cộng tác viên pháp lý')
            ->assertJsonPath('data.paragraphs.0', 'Ưu tiên ứng viên có thời gian tại Hà Nội.')
            ->assertJsonPath('data.id', 'tuyen-cong-tac-vien-tai-ha-noi');

        $this->getJson('/api/admin/tuyen-dung/tin')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'tuyen-cong-tac-vien-tai-ha-noi');
    }

    public function test_updates_and_deletes_recruitment_post(): void
    {
        $post = $this->makePost();

        $this->putJson('/api/admin/tuyen-dung/tin/tin-tuyen-ha-noi', [
            'title' => 'Tin tuyển đã sửa',
            'description' => 'Mô tả mới.',
            'role' => 'Luật sư cộng tác',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Tin tuyển đã sửa')
            ->assertJsonPath('data.role', 'Luật sư cộng tác');

        $this->deleteJson('/api/admin/tuyen-dung/tin/'.$post->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('recruitment_posts', [
            'slug' => 'tin-tuyen-ha-noi',
        ]);
    }

    public function test_returns_not_found_for_missing_role_and_post(): void
    {
        $this->putJson('/api/admin/tuyen-dung/vi-tri/khong-ton-tai', [
            'name' => 'Không có',
        ])->assertNotFound();

        $this->deleteJson('/api/admin/tuyen-dung/tin/khong-ton-tai')->assertNotFound();
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
