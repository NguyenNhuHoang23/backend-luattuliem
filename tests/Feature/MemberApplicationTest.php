<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\RecruitmentApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_director_lists_recruitment_applications(): void
    {
        $director = $this->makeMember(['username' => 'giamdoc', 'role' => 'director']);
        $this->makeApplication([
            'full_name' => 'Nguyễn Văn Bình',
            'email' => 'binh@example.com',
            'experience' => 'Muốn cộng tác pháp lý.',
            'status' => 'moi',
        ]);
        $this->withToken($director->createMemberToken());

        $this->getJson('/api/thanh-vien/de-nghi')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pendingCount', 1)
            ->assertJsonPath('data.0.name', 'Nguyễn Văn Bình')
            ->assertJsonPath('data.0.fullName', 'Nguyễn Văn Bình')
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.request', 'Muốn cộng tác pháp lý.');
    }

    public function test_ctv_cannot_list_or_approve_applications(): void
    {
        $ctv = $this->makeMember(['username' => 'ctv.xem', 'role' => 'ctv']);
        $application = $this->makeApplication();
        $this->withToken($ctv->createMemberToken());

        $this->getJson('/api/thanh-vien/de-nghi')
            ->assertForbidden();

        $this->postJson('/api/thanh-vien/de-nghi/'.$application->id.'/duyet', [
            'username' => 'ungvien',
            'password' => 'Matkhau@1',
        ])->assertForbidden();
    }

    public function test_director_approves_application_and_creates_member(): void
    {
        $director = $this->makeMember(['username' => 'giamdoc', 'role' => 'director']);
        $application = $this->makeApplication([
            'full_name' => 'Nguyễn Văn Bình',
            'phone' => '0912345678',
            'email' => 'binh@example.com',
            'area' => 'Hà Nội',
        ]);
        $this->withToken($director->createMemberToken());

        $this->postJson('/api/thanh-vien/de-nghi/'.$application->id.'/duyet', [
            'username' => 'nguyenvanbinh',
            'password' => 'Matkhau@1',
            'area' => 'Bắc Ninh',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.username', 'nguyenvanbinh')
            ->assertJsonPath('member.role', 'ctv')
            ->assertJsonPath('member.status', 'active');

        $member = Member::query()->where('username', 'nguyenvanbinh')->first();
        $this->assertNotNull($member);
        $this->assertTrue(Hash::check('Matkhau@1', $member->password));
        $this->assertSame('Bắc Ninh', $member->area);
        $this->assertDatabaseHas('recruitment_applications', [
            'id' => $application->id,
            'status' => 'nhan',
            'member_id' => $member->id,
        ]);

        $this->flushHeaders();
        $this->postJson('/api/thanh-vien/dang-nhap', [
            'username' => 'nguyenvanbinh',
            'password' => 'Matkhau@1',
        ])->assertOk();
    }

    public function test_director_rejects_application(): void
    {
        $director = $this->makeMember(['username' => 'giamdoc', 'role' => 'director']);
        $application = $this->makeApplication([
            'email' => 'tu.choi@example.com',
        ]);
        $this->withToken($director->createMemberToken());

        $this->postJson('/api/thanh-vien/de-nghi/'.$application->id.'/tu-choi', [
            'note' => 'Chưa phù hợp giai đoạn này.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.reviewNote', 'Chưa phù hợp giai đoạn này.');

        $this->postJson('/api/thanh-vien/de-nghi/'.$application->id.'/duyet', [
            'username' => 'tuchoi',
            'password' => 'Matkhau@1',
        ])->assertStatus(422);
    }

    public function test_director_streams_application_cv(): void
    {
        Storage::disk('public')->put('applications/cv.pdf', '%PDF-1.4 test');
        $director = $this->makeMember(['username' => 'giamdoc', 'role' => 'director']);
        $application = $this->makeApplication([
            'cv_file_name' => 'CV_HoangMinhTuan.pdf',
            'cv_path' => 'applications/cv.pdf',
        ]);
        $this->withToken($director->createMemberToken());

        $this->get('/api/thanh-vien/de-nghi/'.$application->id.'/cv')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_guest_cannot_submit_old_member_registration_form(): void
    {
        $this->postJson('/api/thanh-vien/dang-ky', [
            'fullName' => 'Nguyễn Văn Bình',
            'phone' => '0912345678',
            'email' => 'binh@example.com',
            'request' => 'Tôi đề nghị tham gia cộng tác viên.',
        ])->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeMember(array $overrides = []): Member
    {
        return Member::query()->create(array_merge([
            'name' => 'Nguyễn Văn A',
            'username' => 'nguyenvana'.uniqid(),
            'password' => 'Matkhau@1',
            'role' => 'ctv',
            'phone' => '0987 111 222',
            'email' => 'vana@example.com',
            'area' => 'Hà Nội',
            'status' => 'active',
            'join_date' => '24-10-2026',
        ], $overrides));
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
}
