<?php

namespace Tests\Feature;

use App\Events\MemberChatMessageSent;
use App\Models\Conversation;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_opens_direct_chat_sends_file_and_reads_history(): void
    {
        Storage::fake('public');
        Event::fake([MemberChatMessageSent::class]);

        $director = $this->makeMember([
            'name' => 'Giám đốc',
            'username' => 'giamdoc',
            'role' => 'director',
        ]);
        $ctv = $this->makeMember([
            'name' => 'Đỗ Thu Trang',
            'username' => 'thutrang',
            'role' => 'ctv',
        ]);

        $this->withToken($director->createMemberToken());

        $open = $this->postJson('/api/thanh-vien/tin-nhan/voi/'.$ctv->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.peer.id', $ctv->id)
            ->assertJsonPath('data.peer.name', 'Đỗ Thu Trang');

        $uuid = $open->json('data.uuid');
        $this->assertNotEmpty($uuid);

        $this->post('/api/thanh-vien/tin-nhan/'.$uuid, [
            'content' => 'Gửi hồ sơ vụ việc giúp anh.',
            'files' => [UploadedFile::fake()->createWithContent('ho-so.pdf', '%PDF-1.4 test')],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Gửi hồ sơ vụ việc giúp anh.')
            ->assertJsonPath('data.attachments.0.name', 'ho-so.pdf');

        Event::assertDispatched(MemberChatMessageSent::class);

        $this->getJson('/api/thanh-vien/tin-nhan/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.messages.0.content', 'Gửi hồ sơ vụ việc giúp anh.')
            ->assertJsonCount(1, 'data.messages.0.attachments');

        $this->flushHeaders();
        $this->withToken($ctv->createMemberToken());

        $this->getJson('/api/thanh-vien/tin-nhan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.peer.id', $director->id)
            ->assertJsonPath('data.0.unread', true);

        $this->postJson('/api/thanh-vien/tin-nhan/'.$uuid.'/da-doc')
            ->assertOk()
            ->assertJsonPath('unread', false);

        $this->getJson('/api/thanh-vien/tin-nhan')
            ->assertOk()
            ->assertJsonPath('data.0.unread', false);

        $attachmentId = $this->getJson('/api/thanh-vien/tin-nhan/'.$uuid)
            ->json('data.messages.0.attachments.0.id');

        $this->get('/api/thanh-vien/tin-nhan/tep/'.$attachmentId)
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_messages_are_paginated_oldest_loaded_on_scroll(): void
    {
        Event::fake([MemberChatMessageSent::class]);

        $director = $this->makeMember(['username' => 'giamdoc3', 'role' => 'director']);
        $ctv = $this->makeMember(['username' => 'ctvpage', 'role' => 'ctv']);
        $conversation = Conversation::findOrCreateDirect($director, $ctv);

        foreach (range(1, 12) as $index) {
            $conversation->messages()->create([
                'member_id' => $director->id,
                'content' => 'Tin '.$index,
            ]);
        }

        $this->withToken($ctv->createMemberToken());

        $first = $this->getJson('/api/thanh-vien/tin-nhan/'.$conversation->uuid)
            ->assertOk()
            ->assertJsonPath('data.hasMore', true)
            ->assertJsonCount(10, 'data.messages');

        $this->assertSame('Tin 3', $first->json('data.messages.0.content'));
        $this->assertSame('Tin 12', $first->json('data.messages.9.content'));

        $oldestLoaded = $first->json('data.messages.0.id');

        $older = $this->getJson('/api/thanh-vien/tin-nhan/'.$conversation->uuid.'/tin?before='.$oldestLoaded)
            ->assertOk()
            ->assertJsonPath('hasMore', false)
            ->assertJsonCount(2, 'data');

        $this->assertSame('Tin 1', $older->json('data.0.content'));
        $this->assertSame('Tin 2', $older->json('data.1.content'));
    }

    public function test_outsider_cannot_read_or_send_chat(): void
    {
        Event::fake([MemberChatMessageSent::class]);
        $director = $this->makeMember(['username' => 'giamdoc2', 'role' => 'director']);
        $ctv = $this->makeMember(['username' => 'ctv2', 'role' => 'ctv']);
        $other = $this->makeMember(['username' => 'ctv3', 'role' => 'ctv']);

        $conversation = Conversation::findOrCreateDirect($director, $ctv);

        $this->withToken($other->createMemberToken());
        $this->getJson('/api/thanh-vien/tin-nhan/'.$conversation->uuid)
            ->assertForbidden();
        $this->postJson('/api/thanh-vien/tin-nhan/'.$conversation->uuid, [
            'content' => 'Tin lạ',
        ])->assertForbidden();
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
