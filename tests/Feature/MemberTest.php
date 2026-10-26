<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_requires_admin_authentication_for_member_crud(): void
    {
        $member = $this->makeMember();
        $this->flushHeaders();

        $this->getJson('/api/admin/thanh-vien')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);

        $this->postJson('/api/admin/thanh-vien', [])
            ->assertUnauthorized();

        $this->putJson('/api/admin/thanh-vien/'.$member->id, [])
            ->assertUnauthorized();

        $this->deleteJson('/api/admin/thanh-vien/'.$member->id)
            ->assertUnauthorized();
    }

    public function test_lists_members_newest_first(): void
    {
        $this->makeMember(['name' => 'Thành viên cũ', 'username' => 'ctv.cu']);
        $this->makeMember(['name' => 'Thành viên mới', 'username' => 'ctv.moi']);

        $this->getJson('/api/admin/thanh-vien')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Thành viên mới')
            ->assertJsonPath('data.1.name', 'Thành viên cũ')
            ->assertJsonMissingPath('data.0.password');
    }

    public function test_creates_member_with_login_and_role(): void
    {
        $this->postJson('/api/admin/thanh-vien', [
            'name' => 'Nguyễn Văn A',
            'username' => 'nguyenvana',
            'password' => 'Matkhau@1',
            'role' => 'ctv',
            'phone' => '0987 111 222',
            'email' => 'vana@luattuliem.vn',
            'area' => 'Hà Nội',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nguyễn Văn A')
            ->assertJsonPath('data.username', 'nguyenvana')
            ->assertJsonPath('data.role', 'ctv')
            ->assertJsonMissingPath('data.password');

        $member = Member::query()->where('username', 'nguyenvana')->first();
        $this->assertNotNull($member);
        $this->assertTrue(Hash::check('Matkhau@1', $member->password));
        $this->assertSame('active', $member->status);
    }

    public function test_creates_director_member(): void
    {
        $this->postJson('/api/admin/thanh-vien', [
            'name' => 'Giám đốc Từ Liêm',
            'username' => 'giamdoc',
            'password' => 'Giamdoc@1',
            'role' => 'director',
            'phone' => '091 602 99 66',
        ])
            ->assertCreated()
            ->assertJsonPath('data.role', 'director');
    }

    public function test_rejects_duplicate_username_and_invalid_role(): void
    {
        $this->makeMember(['username' => 'trunglap']);

        $this->postJson('/api/admin/thanh-vien', [
            'name' => 'Người khác',
            'username' => 'trunglap',
            'password' => 'Matkhau@1',
            'role' => 'ctv',
            'phone' => '0909 000 111',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username']);

        $this->postJson('/api/admin/thanh-vien', [
            'name' => 'Người khác',
            'username' => 'nguoikhac',
            'password' => 'Matkhau@1',
            'role' => 'lawyer',
            'phone' => '0909 000 111',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_updates_member_and_optional_password(): void
    {
        $member = $this->makeMember([
            'name' => 'CTV cũ',
            'username' => 'ctvcu',
            'role' => 'ctv',
        ]);
        $originalHash = $member->password;

        $this->putJson('/api/admin/thanh-vien/'.$member->id, [
            'name' => 'CTV đã sửa',
            'username' => 'ctvcu',
            'role' => 'director',
            'phone' => '0912 000 111',
            'area' => 'Bắc Ninh',
            'status' => 'inactive',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'CTV đã sửa')
            ->assertJsonPath('data.role', 'director')
            ->assertJsonPath('data.status', 'inactive');

        $this->assertSame($originalHash, $member->refresh()->password);

        $this->patchJson('/api/admin/thanh-vien/'.$member->id, [
            'name' => 'CTV đã sửa',
            'username' => 'ctvcu',
            'password' => 'MatkhauMoi@1',
            'role' => 'director',
            'phone' => '0912 000 111',
        ])->assertOk();

        $this->assertTrue(Hash::check('MatkhauMoi@1', $member->refresh()->password));
    }

    public function test_deletes_member(): void
    {
        $member = $this->makeMember();

        $this->deleteJson('/api/admin/thanh-vien/'.$member->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->getJson('/api/admin/thanh-vien/'.$member->id)->assertNotFound();
    }

    public function test_member_can_login_on_client_with_username_and_password(): void
    {
        $member = $this->makeMember([
            'name' => 'Đỗ Thu Trang',
            'username' => 'thutrang',
            'role' => 'ctv',
        ]);
        $this->flushHeaders();

        $response = $this->postJson('/api/thanh-vien/dang-nhap', [
            'username' => 'thutrang',
            'password' => 'Matkhau@1',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.username', 'thutrang')
            ->assertJsonPath('data.role', 'ctv')
            ->assertJsonPath('data.name', 'Đỗ Thu Trang');

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->getJson('/api/thanh-vien/me')
            ->assertOk()
            ->assertJsonPath('data.username', 'thutrang');

        $this->withToken($token)
            ->postJson('/api/thanh-vien/dang-xuat')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withToken($token)
            ->getJson('/api/thanh-vien/me')
            ->assertUnauthorized();
    }

    public function test_inactive_or_wrong_credentials_cannot_login(): void
    {
        $this->makeMember([
            'username' => 'khoa',
            'status' => 'inactive',
        ]);
        $this->flushHeaders();

        $this->postJson('/api/thanh-vien/dang-nhap', [
            'username' => 'khoa',
            'password' => 'Matkhau@1',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username']);

        $this->postJson('/api/thanh-vien/dang-nhap', [
            'username' => 'khoa',
            'password' => 'sai-mat-khau',
        ])
            ->assertUnprocessable();
    }

    public function test_director_lists_and_shows_team_members(): void
    {
        $director = $this->makeMember([
            'name' => 'Giám đốc',
            'username' => 'giamdoc',
            'role' => 'director',
        ]);
        $ctv = $this->makeMember([
            'name' => 'Đỗ Thu Trang',
            'username' => 'thutrang',
            'role' => 'ctv',
            'phone' => '098 765 43 02',
            'email' => 'thutrang@luattuliem.vn',
            'area' => 'Hải Dương',
        ]);
        $this->makeMember([
            'name' => 'Giám đốc khác',
            'username' => 'giamdockhac',
            'role' => 'director',
        ]);
        $this->flushHeaders();
        $this->withToken($director->createMemberToken());

        $this->getJson('/api/thanh-vien/doi-ngu')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Đỗ Thu Trang')
            ->assertJsonPath('data.0.username', 'thutrang')
            ->assertJsonPath('data.0.role', 'ctv')
            ->assertJsonMissingPath('data.0.password');

        $this->getJson('/api/thanh-vien/doi-ngu/'.$ctv->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $ctv->id)
            ->assertJsonPath('data.name', 'Đỗ Thu Trang')
            ->assertJsonPath('data.phone', '098 765 43 02')
            ->assertJsonPath('data.email', 'thutrang@luattuliem.vn')
            ->assertJsonPath('data.area', 'Hải Dương')
            ->assertJsonMissingPath('data.password');
    }

    public function test_ctv_cannot_list_team_members(): void
    {
        $ctv = $this->makeMember(['username' => 'ctv.xem', 'role' => 'ctv']);
        $this->flushHeaders();
        $this->withToken($ctv->createMemberToken());

        $this->getJson('/api/thanh-vien/doi-ngu')
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->getJson('/api/thanh-vien/doi-ngu/'.$ctv->id)
            ->assertForbidden();
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
}
