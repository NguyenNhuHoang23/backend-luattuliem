<?php

namespace Tests\Feature;

use App\Events\MemberChatMessageSent;
use App\Models\Conversation;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MemberIncomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_sends_income_notice_separate_from_messages(): void
    {
        Event::fake([MemberChatMessageSent::class]);

        $director = $this->makeMember([
            'name' => 'Giám đốc',
            'username' => 'giamdoc-luong',
            'role' => 'director',
        ]);
        $ctv = $this->makeMember([
            'name' => 'Đỗ Thu Trang',
            'username' => 'thutrang-luong',
            'email' => 'thutrang-luong@luattuliem.vn',
        ]);

        Conversation::findOrCreateDirect($director, $ctv)
            ->messages()
            ->create([
                'member_id' => $director->id,
                'content' => 'Nhắn việc thường.',
            ]);

        $this->withToken($director->createMemberToken());

        $created = $this->postJson('/api/thanh-vien/thu-nhap', [
            'content' => 'Đã chuyển 10.000.000đ lương tháng 10.',
            'memberIds' => [$ctv->id],
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.type', 'income')
            ->assertJsonPath('data.0.peer.id', $ctv->id)
            ->assertJsonPath('data.0.messages.0.content', 'Đã chuyển 10.000.000đ lương tháng 10.');

        $uuid = $created->json('data.0.uuid');
        $this->assertNotEmpty($uuid);
        Event::assertDispatched(MemberChatMessageSent::class);

        $this->getJson('/api/thanh-vien/tin-nhan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'direct');

        $this->getJson('/api/thanh-vien/thu-nhap')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'income')
            ->assertJsonPath('data.0.uuid', $uuid);

        $this->flushHeaders();
        $this->withToken($ctv->createMemberToken());

        $this->getJson('/api/thanh-vien/tin-nhan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'direct')
            ->assertJsonMissing(['type' => 'income']);

        $this->getJson('/api/thanh-vien/thu-nhap')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.unread', true)
            ->assertJsonPath('data.0.peer.id', $director->id)
            ->assertJsonPath('data.0.type', 'income');

        $this->postJson('/api/thanh-vien/tin-nhan/'.$uuid, [
            'content' => 'Em chưa nhận được ạ.',
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->postJson('/api/thanh-vien/thu-nhap', [
            'content' => 'Nhân viên không được gửi.',
            'memberIds' => [$ctv->id],
        ])->assertForbidden();
    }

    public function test_guest_cannot_access_income_notices(): void
    {
        $this->getJson('/api/thanh-vien/thu-nhap')->assertUnauthorized();
        $this->postJson('/api/thanh-vien/thu-nhap', [
            'content' => 'Thông báo',
            'memberIds' => [1],
        ])->assertUnauthorized();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeMember(array $overrides = []): Member
    {
        return Member::query()->create(array_merge([
            'name' => 'Nguyễn Văn A',
            'username' => 'tv'.uniqid(),
            'password' => 'Matkhau@1',
            'role' => 'ctv',
            'phone' => '0987 111 222',
            'email' => 'tv@example.com',
            'area' => 'Hà Nội',
            'status' => 'active',
            'join_date' => '24-10-2026',
        ], $overrides));
    }
}
