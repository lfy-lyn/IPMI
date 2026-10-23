<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Division;
use App\Models\MembershipApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;

    protected function setUp(): void
    {
        parent::setUp();
        $this->division = Division::create(['name' => 'Kaligrafi', 'status' => 'active']);
    }

    public function test_registration_form_is_accessible()
    {
        $response = $this->get(route('membership.create'));
        
        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Anggota IPMI');
        $response->assertSee('NIS');
        $response->assertSee('Nama Lengkap');
        $response->assertSee('Email Aktif');
        $response->assertSee('Pilihan Divisi');
        $response->assertSee('Alasan Bergabung');
    }

    public function test_can_submit_valid_application()
    {
        $data = [
            'nis' => '12345',
            'full_name' => 'Ahmad Rizki',
            'email' => 'ahmad@example.com',
            'phone' => '081234567890',
            'address' => 'Jl. Test No. 123',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar kaligrafi dan mengembangkan kemampuan seni Islam',
        ];

        $response = $this->post(route('membership.store'), $data);

        $response->assertRedirect(route('membership.success'));
        $this->assertDatabaseHas('membership_applications', [
            'nis' => '12345',
            'email' => 'ahmad@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_nis_is_required()
    {
        $data = [
            'full_name' => 'Ahmad Rizki',
            'email' => 'ahmad@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar kaligrafi',
        ];

        $response = $this->post(route('membership.store'), $data);
        $response->assertSessionHasErrors('nis');
    }

    public function test_email_must_be_valid()
    {
        $data = [
            'nis' => '12345',
            'full_name' => 'Ahmad Rizki',
            'email' => 'invalid-email',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar kaligrafi',
        ];

        $response = $this->post(route('membership.store'), $data);
        $response->assertSessionHasErrors('email');
    }

    public function test_reason_must_be_at_least_20_characters()
    {
        $data = [
            'nis' => '12345',
            'full_name' => 'Ahmad Rizki',
            'email' => 'ahmad@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Terlalu pendek',
        ];

        $response = $this->post(route('membership.store'), $data);
        $response->assertSessionHasErrors('reason');
    }

    public function test_division_id_must_exist()
    {
        $data = [
            'nis' => '12345',
            'full_name' => 'Ahmad Rizki',
            'email' => 'ahmad@example.com',
            'division_id' => 999,
            'reason' => 'Saya ingin bergabung untuk belajar kaligrafi',
        ];

        $response = $this->post(route('membership.store'), $data);
        $response->assertSessionHasErrors('division_id');
    }

    public function test_cannot_submit_more_than_3_applications_per_nis()
    {
        $nis = '12345';

        // Buat 3 pendaftaran pending/approved
        for ($i = 1; $i <= 3; $i++) {
            MembershipApplication::create([
                'nis' => $nis,
                'full_name' => "Test User $i",
                'email' => "test$i@example.com",
                'division_id' => $this->division->id,
                'reason' => 'Saya ingin bergabung untuk belajar',
                'status' => $i <= 2 ? 'pending' : 'approved',
            ]);
        }

        // Coba submit yang ke-4
        $data = [
            'nis' => $nis,
            'full_name' => 'Test User 4',
            'email' => 'test4@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar kaligrafi',
        ];

        $response = $this->post(route('membership.store'), $data);
        $response->assertSessionHasErrors('nis');
        $this->assertDatabaseCount('membership_applications', 3);
    }

    public function test_rejected_applications_do_not_count_toward_limit()
    {
        $nis = '12345';

        // Buat 3 pendaftaran rejected
        for ($i = 1; $i <= 3; $i++) {
            MembershipApplication::create([
                'nis' => $nis,
                'full_name' => "Test User $i",
                'email' => "test$i@example.com",
                'division_id' => $this->division->id,
                'reason' => 'Saya ingin bergabung untuk belajar',
                'status' => 'rejected',
            ]);
        }

        // Submit yang baru harus berhasil
        $data = [
            'nis' => $nis,
            'full_name' => 'Test User New',
            'email' => 'testnew@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar kaligrafi',
        ];

        $response = $this->post(route('membership.store'), $data);
        $response->assertRedirect(route('membership.success'));
        $this->assertDatabaseCount('membership_applications', 4);
    }

    public function test_success_page_is_accessible()
    {
        $response = $this->get(route('membership.success'));
        
        $response->assertStatus(200);
        $response->assertSee('Pendaftaran Berhasil Dikirim');
    }

    public function test_optional_fields_are_nullable()
    {
        $data = [
            'nis' => '12345',
            'full_name' => 'Ahmad Rizki',
            'email' => 'ahmad@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar kaligrafi dan mengembangkan kemampuan',
        ];

        $response = $this->post(route('membership.store'), $data);
        
        $response->assertRedirect(route('membership.success'));
        $this->assertDatabaseHas('membership_applications', [
            'nis' => '12345',
            'phone' => null,
            'address' => null,
        ]);
    }
}