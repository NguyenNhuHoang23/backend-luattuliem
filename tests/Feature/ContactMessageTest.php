<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactMessageTest extends TestCase
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
        $message = $this->makeContact();
        $this->flushHeaders();

        $this->getJson('/api/admin/lien-he')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);

        $this->getJson('/api/admin/lien-he/'.$message->id)
            ->assertUnauthorized();

        $this->deleteJson('/api/admin/lien-he/'.$message->id)
            ->assertUnauthorized();
    }

    public function test_lists_contacts_newest_first(): void
    {
        $this->makeContact(['full_name' => 'Người gửi cũ', 'phone' => '0911111111']);
        $this->makeContact(['full_name' => 'Người gửi mới', 'phone' => '0922222222']);

        $this->getJson('/api/admin/lien-he')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.fullName', 'Người gửi mới')
            ->assertJsonPath('data.1.fullName', 'Người gửi cũ');
    }

    public function test_shows_contact_detail(): void
    {
        $message = $this->makeContact([
            'full_name' => 'Nguyễn Văn An',
            'content' => 'Cần tư vấn tranh chấp đất đai.',
            'files' => [['name' => 'so-do-dat.jpg', 'path' => 'contacts/so-do-dat.jpg']],
        ]);

        $this->getJson('/api/admin/lien-he/'.$message->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.fullName', 'Nguyễn Văn An')
            ->assertJsonPath('data.content', 'Cần tư vấn tranh chấp đất đai.')
            ->assertJsonPath('data.files.0.name', 'so-do-dat.jpg')
            ->assertJsonPath('data.status', 'moi');
    }

    public function test_updates_and_deletes_contact(): void
    {
        Storage::disk('public')->put('contacts/so-do.pdf', 'pdf');
        $message = $this->makeContact([
            'files' => [['name' => 'so-do.pdf', 'path' => 'contacts/so-do.pdf']],
        ]);

        $this->patchJson('/api/admin/lien-he/'.$message->id.'/status', [
            'status' => 'da-lien-he',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'da-lien-he');

        $this->deleteJson('/api/admin/lien-he/'.$message->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
        Storage::disk('public')->assertMissing('contacts/so-do.pdf');
    }

    public function test_streams_contact_file_for_admin(): void
    {
        Storage::disk('public')->put('contacts/so-do.pdf', '%PDF-1.4 test');
        $message = $this->makeContact([
            'files' => [['name' => 'so-do.pdf', 'path' => 'contacts/so-do.pdf']],
        ]);

        $this->get('/api/admin/lien-he/'.$message->id.'/files/0')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->flushHeaders();
        $this->getJson('/api/admin/lien-he/'.$message->id.'/files/0')
            ->assertUnauthorized();
    }

    public function test_returns_not_found_for_missing_contact(): void
    {
        $this->getJson('/api/admin/lien-he/999')->assertNotFound();
        $this->deleteJson('/api/admin/lien-he/999')->assertNotFound();
    }

    public function test_public_can_submit_contact_without_login(): void
    {
        $this->flushHeaders();

        $this->post('/api/lien-he', [
            'fullName' => 'Trần Thị Hoa',
            'phone' => '0987 111 222',
            'email' => 'hoa.tran@example.com',
            'address' => 'Hải Dương',
            'content' => 'Muốn tư vấn hợp đồng tặng cho nhà đất.',
            'files' => [$this->fakePdf()],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.fullName', 'Trần Thị Hoa')
            ->assertJsonPath('data.files.0.name', 'tai-lieu.pdf')
            ->assertJsonPath('data.status', 'moi');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'hoa.tran@example.com',
            'status' => 'moi',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeContact(array $overrides = []): ContactMessage
    {
        return ContactMessage::query()->create(array_merge([
            'full_name' => 'Nguyễn Văn An',
            'phone' => '0912 345 678',
            'email' => 'an@example.com',
            'address' => 'Hà Nội',
            'content' => 'Cần tư vấn pháp lý.',
            'files' => [],
            'status' => 'moi',
        ], $overrides));
    }

    private function fakePdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('tai-lieu.pdf', '%PDF-1.4 test');
    }
}
