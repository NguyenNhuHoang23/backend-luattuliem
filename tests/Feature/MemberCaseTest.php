<?php

namespace Tests\Feature;

use App\Events\MemberChatMessageSent;
use App\Models\Conversation;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MemberCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_creates_case_group_and_members_can_chat(): void
    {
        Event::fake([MemberChatMessageSent::class]);

        $director = $this->makeMember([
            'name' => 'Giám đốc',
            'username' => 'giamdoc-case',
            'role' => 'director',
        ]);
        $ctv = $this->makeMember([
            'name' => 'Đỗ Thu Trang',
            'username' => 'thutrang-case',
            'email' => 'thutrang-case@luattuliem.vn',
        ]);
        $other = $this->makeMember([
            'name' => 'Nguyễn Văn A',
            'username' => 'vana-case',
            'email' => 'vana-case@luattuliem.vn',
        ]);

        $this->withToken($director->createMemberToken());

        $created = $this->postJson('/api/thanh-vien/vu-viec', [
            'name' => 'Hồ sơ tranh chấp đất',
            'memberIds' => [$ctv->id, $other->id],
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'case')
            ->assertJsonPath('data.name', 'Hồ sơ tranh chấp đất');

        $uuid = $created->json('data.uuid');
        $this->assertNotEmpty($uuid);
        $this->assertCount(3, $created->json('data.members'));

        $this->getJson('/api/thanh-vien/vu-viec')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Hồ sơ tranh chấp đất');

        $this->getJson('/api/thanh-vien/tin-nhan')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->postJson('/api/thanh-vien/tin-nhan/'.$uuid, [
            'content' => 'Phân công hồ sơ cho nhóm.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.content', 'Phân công hồ sơ cho nhóm.');

        Event::assertDispatched(MemberChatMessageSent::class);

        $this->flushHeaders();
        $this->withToken($ctv->createMemberToken());

        $this->getJson('/api/thanh-vien/vu-viec')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.unread', true);

        $this->postJson('/api/thanh-vien/tin-nhan/'.$uuid, [
            'content' => 'Em nhận hồ sơ ạ.',
        ])->assertCreated();

        $this->flushHeaders();
        $this->withToken($other->createMemberToken());
        $this->getJson('/api/thanh-vien/tin-nhan/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.messages.0.content', 'Phân công hồ sơ cho nhóm.')
            ->assertJsonPath('data.messages.1.content', 'Em nhận hồ sơ ạ.');
    }

    public function test_director_updates_case_members_and_outsider_is_blocked(): void
    {
        Event::fake([MemberChatMessageSent::class]);

        $director = $this->makeMember(['username' => 'giamdoc-case2', 'role' => 'director']);
        $keep = $this->makeMember(['username' => 'keep-case', 'email' => 'keep@luattuliem.vn']);
        $removed = $this->makeMember(['username' => 'removed-case', 'email' => 'removed@luattuliem.vn']);
        $added = $this->makeMember(['username' => 'added-case', 'email' => 'added@luattuliem.vn']);

        $conversation = Conversation::createCase($director, 'Vụ cũ', [$keep->id, $removed->id]);

        $this->withToken($director->createMemberToken());
        $this->patchJson('/api/thanh-vien/vu-viec/'.$conversation->uuid, [
            'name' => 'Vụ đã đổi tên',
            'memberIds' => [$keep->id, $added->id],
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Vụ đã đổi tên');

        $memberIds = collect($this->getJson('/api/thanh-vien/vu-viec/'.$conversation->uuid)->json('data.members'))
            ->pluck('id')
            ->sort()
            ->values()
            ->all();
        $this->assertSame(
            collect([$director->id, $keep->id, $added->id])->sort()->values()->all(),
            $memberIds,
        );

        $this->flushHeaders();
        $this->withToken($removed->createMemberToken());
        $this->getJson('/api/thanh-vien/vu-viec/'.$conversation->uuid)->assertForbidden();
        $this->postJson('/api/thanh-vien/tin-nhan/'.$conversation->uuid, [
            'content' => 'Tin sau khi bị gỡ.',
        ])->assertForbidden();

        $this->flushHeaders();
        $this->withToken($added->createMemberToken());
        $this->getJson('/api/thanh-vien/vu-viec')->assertJsonCount(1, 'data');
        $this->postJson('/api/thanh-vien/tin-nhan/'.$conversation->uuid, [
            'content' => 'Em mới được thêm vào nhóm.',
        ])->assertCreated();
    }

    public function test_ctv_cannot_create_case_and_cannot_open_direct_as_case(): void
    {
        $director = $this->makeMember(['username' => 'giamdoc-case3', 'role' => 'director']);
        $ctv = $this->makeMember(['username' => 'ctv-case3', 'email' => 'ctv3@luattuliem.vn']);
        $direct = Conversation::findOrCreateDirect($director, $ctv);

        $this->withToken($ctv->createMemberToken());
        $this->postJson('/api/thanh-vien/vu-viec', [
            'name' => 'Nhóm tự tạo',
            'memberIds' => [$ctv->id],
        ])->assertForbidden();

        $this->getJson('/api/thanh-vien/vu-viec/'.$direct->uuid)->assertNotFound();
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
