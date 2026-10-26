<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_shows_company_contact_info(): void
    {
        $this->getJson('/api/thong-tin')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone', '091 602 99 66')
            ->assertJsonPath('data.phoneHref', 'tel:0916029966')
            ->assertJsonPath('data.email', 'contact@luattuliem.vn')
            ->assertJsonPath('data.address', 'Tầng 5, số 73 đường Phú Diễn, phường Phú Diễn, quận Bắc Từ Liêm, Hà Nội.')
            ->assertJsonPath('data.workingHours', CompanySetting::DEFAULTS['working_hours']);

        $this->assertSame(1, CompanySetting::query()->count());
    }

    public function test_admin_updates_company_contact_info(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/admin/thong-tin', [
            'name' => 'Công ty Luật Từ Liêm',
            'phone' => '024 1234 5678',
            'email' => 'vanphong@luattuliem.vn',
            'address' => 'Số 1 phố Test, Hà Nội.',
            'workingHours' => "Thứ 2 – Thứ 6: 08:00 – 17:00\nNghỉ thứ 7, chủ nhật",
            'website' => 'luattuliem.vn',
            'slogan' => 'Đồng hành cùng khách hàng',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone', '024 1234 5678')
            ->assertJsonPath('data.phoneHref', 'tel:02412345678')
            ->assertJsonPath('data.email', 'vanphong@luattuliem.vn')
            ->assertJsonPath('data.workingHours', "Thứ 2 – Thứ 6: 08:00 – 17:00\nNghỉ thứ 7, chủ nhật");

        $this->getJson('/api/thong-tin')
            ->assertOk()
            ->assertJsonPath('data.address', 'Số 1 phố Test, Hà Nội.');

        $this->assertSame(1, CompanySetting::query()->count());
    }

    public function test_admin_update_requires_authentication(): void
    {
        $this->putJson('/api/admin/thong-tin', CompanySetting::DEFAULTS)
            ->assertUnauthorized();
    }

    public function test_rejects_invalid_payload(): void
    {
        $this->actingAsAdmin()
            ->putJson('/api/admin/thong-tin', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'email', 'address', 'workingHours']);
    }
}
