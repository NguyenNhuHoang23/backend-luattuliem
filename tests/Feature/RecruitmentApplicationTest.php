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

    public function test_requires_admin_authentication_for_list_detail_and_delete(): void
    {
        $application = $this->makeApplication();
        $this->flushHeaders();

        $this->getJson('/api/admin/ho-so-ung-tuyen')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);

        $this->getJson('/api/admin/ho-so-ung-tuyen/'.$application->id)
            ->assertUnauthorized();

        $this->deleteJson('/api/admin/ho-so-ung-tuyen/'.$application->id)
            ->assertUnauthorized();
    }

    public function test_lists_applications_newest_first(): void
    {
        $this->makeApplication(['full_name' => 'Ứng viên cũ', 'email' => 'cu@example.com']);
        $this->makeApplication(['full_name' => 'Ứng viên mới', 'email' => 'moi@example.com']);

        $this->getJson('/api/admin/ho-so-ung-tuyen')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.fullName', 'Ứng viên mới')
            ->assertJsonPath('data.1.fullName', 'Ứng viên cũ');
    }

    public function test_shows_application_detail(): void
    {
        $application = $this->makeApplication([
            'full_name' => 'Hoàng Minh Tuấn',
            'experience' => '8 năm kinh nghiệm tố tụng dân sự.',
            'cv_file_name' => 'CV_HoangMinhTuan.pdf',
        ]);

        $this->getJson('/api/admin/ho-so-ung-tuyen/'.$application->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $application->id)
            ->assertJsonPath('data.fullName', 'Hoàng Minh Tuấn')
            ->assertJsonPath('data.role', 'Luật sư cộng tác')
            ->assertJsonPath('data.experience', '8 năm kinh nghiệm tố tụng dân sự.')
            ->assertJsonPath('data.cvFileName', 'CV_HoangMinhTuan.pdf')
            ->assertJsonPath('data.status', 'moi');
    }

    public function test_updates_application_status(): void
    {
        $application = $this->makeApplication();

        $this->patchJson('/api/admin/ho-so-ung-tuyen/'.$application->id.'/status', [
            'status' => 'phong-van',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'phong-van');

        $this->assertDatabaseHas('recruitment_applications', [
            'id' => $application->id,
            'status' => 'phong-van',
        ]);
    }

    public function test_deletes_application(): void
    {
        Storage::disk('public')->put('applications/cv.pdf', '%PDF-1.4');
        $application = $this->makeApplication([
            'cv_path' => 'applications/cv.pdf',
        ]);

        $this->deleteJson('/api/admin/ho-so-ung-tuyen/'.$application->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('recruitment_applications', [
            'id' => $application->id,
        ]);
        Storage::disk('public')->assertMissing('applications/cv.pdf');
    }

    public function test_streams_application_cv_for_admin(): void
    {
        Storage::disk('public')->put('applications/cv.pdf', '%PDF-1.4 test');
        $application = $this->makeApplication([
            'cv_file_name' => 'CV_HoangMinhTuan.pdf',
            'cv_path' => 'applications/cv.pdf',
        ]);

        $this->get('/api/admin/ho-so-ung-tuyen/'.$application->id.'/cv')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->flushHeaders();
        $this->getJson('/api/admin/ho-so-ung-tuyen/'.$application->id.'/cv')
            ->assertUnauthorized();
    }

    public function test_returns_not_found_for_missing_application(): void
    {
        $this->getJson('/api/admin/ho-so-ung-tuyen/999')
            ->assertNotFound();

        $this->deleteJson('/api/admin/ho-so-ung-tuyen/999')
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
            ->assertJsonPath('data.status', 'moi');

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
