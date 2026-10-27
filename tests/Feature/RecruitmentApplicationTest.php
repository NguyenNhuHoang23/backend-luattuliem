<?php

namespace Tests\Feature;

use App\Models\RecruitmentApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecruitmentApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAsAdmin();
    }

    public function test_admin_no_longer_manages_recruitment_applications(): void
    {
        $application = $this->makeApplication();

        $this->getJson('/api/admin/ho-so-ung-tuyen')
            ->assertNotFound();

        $this->getJson('/api/admin/ho-so-ung-tuyen/'.$application->id)
            ->assertNotFound();

        $this->patchJson('/api/admin/ho-so-ung-tuyen/'.$application->id.'/status', [
            'status' => 'phong-van',
        ])->assertNotFound();

        $this->deleteJson('/api/admin/ho-so-ung-tuyen/'.$application->id)
            ->assertNotFound();
    }

    public function test_public_can_submit_application_without_login(): void
    {
        $this->flushHeaders();

        $this->post('/api/tuyen-dung/ho-so', [
            'fullName' => 'Nguyễn Thị Lan',
            'phone' => '0988 777 666',
            'email' => 'lan.nguyen@example.com',
            'role' => 'Cộng tác viên pháp lý',
            'area' => 'Hà Nội',
            'experience' => 'Cử nhân Luật, muốn hỗ trợ nghiên cứu hồ sơ.',
            'cv' => $this->fakePdf(),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.fullName', 'Nguyễn Thị Lan')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('recruitment_applications', [
            'email' => 'lan.nguyen@example.com',
            'role' => 'Cộng tác viên pháp lý',
            'status' => 'moi',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeApplication(array $overrides = []): RecruitmentApplication
    {
        return RecruitmentApplication::query()->create(array_merge([
            'full_name' => 'Hoàng Minh Tuấn',
            'phone' => '0912 888 999',
            'email' => 'tuan.hoang@example.com',
            'role' => 'Luật sư cộng tác',
            'area' => 'Hà Nội',
            'experience' => '8 năm kinh nghiệm tố tụng.',
            'cv_file_name' => 'CV.pdf',
            'cv_path' => null,
            'status' => 'moi',
        ], $overrides));
    }

    private function fakePdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('cv.pdf', '%PDF-1.4 test');
    }
}
