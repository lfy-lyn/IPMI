<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Member;
use App\Models\Division;
use App\Models\Position;
use App\Models\MembershipApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MembershipReviewTest extends TestCase
{
    use RefreshDatabase;

    protected Division $division;
    protected Position $ketuaUmum;
    protected Position $sekretaris;
    protected Position $ketuaDivisi;
    protected User $kuUser;
    protected Member $kuMember;
    protected User $sekUser;
    protected Member $sekMember;
    protected User $kdUser;
    protected Member $kdMember;
    protected User $regularUser;
    protected Member $regularMember;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->division = Division::create(['name' => 'Kaligrafi', 'status' => 'active']);
        
        $this->ketuaUmum = Position::create(['name' => 'Ketua Umum', 'status' => 'active']);
        $this->sekretaris = Position::create(['name' => 'Sekretaris', 'status' => 'active']);
        $this->ketuaDivisi = Position::create(['name' => 'Ketua Divisi', 'status' => 'active']);
        
        // Ketua Umum
        $this->kuUser = User::create(['email' => 'ku@test.com', 'password' => Hash::make('password'), 'status' => 'active']);
        $this->kuMember = Member::create(['user_id' => $this->kuUser->id, 'nis' => '001', 'full_name' => 'Ketua Umum', 'division_id' => $this->division->id]);
        $this->kuMember->positions()->attach($this->ketuaUmum);
        
        // Sekretaris
        $this->sekUser = User::create(['email' => 'sek@test.com', 'password' => Hash::make('password'), 'status' => 'active']);
        $this->sekMember = Member::create(['user_id' => $this->sekUser->id, 'nis' => '002', 'full_name' => 'Sekretaris', 'division_id' => $this->division->id]);
        $this->sekMember->positions()->attach($this->sekretaris);
        
        // Ketua Divisi
        $this->kdUser = User::create(['email' => 'kd@test.com', 'password' => Hash::make('password'), 'status' => 'active']);
        $this->kdMember = Member::create(['user_id' => $this->kdUser->id, 'nis' => '003', 'full_name' => 'Ketua Divisi', 'division_id' => $this->division->id]);
        $this->kdMember->positions()->attach($this->ketuaDivisi);
        
        // Regular member
        $this->regularUser = User::create(['email' => 'member@test.com', 'password' => Hash::make('password'), 'status' => 'active']);
        $this->regularMember = Member::create(['user_id' => $this->regularUser->id, 'nis' => '004', 'full_name' => 'Member', 'division_id' => $this->division->id]);
    }

    public function test_ketua_umum_can_access_application_list()
    {
        $response = $this->actingAs($this->kuUser)->get(route('admin.membership.index'));
        $response->assertStatus(200);
        $response->assertSee('Daftar Pendaftaran Anggota');
    }

    public function test_sekretaris_can_access_application_list()
    {
        $response = $this->actingAs($this->sekUser)->get(route('admin.membership.index'));
        $response->assertStatus(200);
        $response->assertSee('Daftar Pendaftaran Anggota');
    }

    public function test_ketua_divisi_cannot_access_application_list()
    {
        $response = $this->actingAs($this->kdUser)->get(route('admin.membership.index'));
        $response->assertStatus(403);
    }

    public function test_regular_member_cannot_access_application_list()
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.membership.index'));
        $response->assertStatus(403);
    }

    public function test_application_list_shows_all_applications()
    {
        MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->kuUser)->get(route('admin.membership.index'));
        $response->assertSee('Test User');
        $response->assertSee('12345');
    }

    public function test_application_list_can_filter_by_status()
    {
        MembershipApplication::create([
            'nis' => '11111',
            'full_name' => 'Pending User',
            'email' => 'pending@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        MembershipApplication::create([
            'nis' => '22222',
            'full_name' => 'Approved User',
            'email' => 'approved@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->kuUser)->get(route('admin.membership.index', ['status' => 'pending']));
        $response->assertSee('Pending User');
        $response->assertDontSee('Approved User');
    }

    public function test_ketua_umum_can_view_application_detail()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar kaligrafi',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->kuUser)->get(route('admin.membership.show', $application));
        $response->assertStatus(200);
        $response->assertSee('Test User');
        $response->assertSee('12345');
        $response->assertSee('Saya ingin bergabung untuk belajar kaligrafi');
    }

    public function test_ketua_divisi_cannot_view_application_detail()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->kdUser)->get(route('admin.membership.show', $application));
        $response->assertStatus(403);
    }

    public function test_ketua_umum_can_approve_pending_application()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->kuUser)->post(route('admin.membership.approve', $application));
        
        $response->assertRedirect(route('admin.membership.show', $application));
        
        $application->refresh();
        $this->assertEquals('approved', $application->status);
        $this->assertEquals($this->kuUser->id, $application->reviewed_by);
        $this->assertNotNull($application->reviewed_at);
    }

    public function test_sekretaris_can_approve_pending_application()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->sekUser)->post(route('admin.membership.approve', $application));
        
        $application->refresh();
        $this->assertEquals('approved', $application->status);
        $this->assertEquals($this->sekUser->id, $application->reviewed_by);
    }

    public function test_ketua_divisi_cannot_approve_application()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->kdUser)->post(route('admin.membership.approve', $application));
        $response->assertStatus(403);
        
        $application->refresh();
        $this->assertEquals('pending', $application->status);
    }

    public function test_cannot_approve_already_approved_application()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'approved',
            'reviewed_by' => $this->kuUser->id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($this->kuUser)->post(route('admin.membership.approve', $application));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_cannot_approve_already_rejected_application()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'rejected',
            'rejection_reason' => 'Data tidak lengkap',
            'reviewed_by' => $this->kuUser->id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($this->kuUser)->post(route('admin.membership.approve', $application));
        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $application->refresh();
        $this->assertEquals('rejected', $application->status);
    }

    public function test_ketua_umum_can_reject_with_reason()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->kuUser)->post(route('admin.membership.reject', $application), [
            'rejection_reason' => 'Data yang disampaikan tidak lengkap',
        ]);
        
        $response->assertRedirect(route('admin.membership.show', $application));
        
        $application->refresh();
        $this->assertEquals('rejected', $application->status);
        $this->assertEquals('Data yang disampaikan tidak lengkap', $application->rejection_reason);
        $this->assertEquals($this->kuUser->id, $application->reviewed_by);
        $this->assertNotNull($application->reviewed_at);
    }

    public function test_rejection_requires_reason()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->kuUser)->post(route('admin.membership.reject', $application), [
            'rejection_reason' => '',
        ]);
        
        $response->assertSessionHasErrors('rejection_reason');
        
        $application->refresh();
        $this->assertEquals('pending', $application->status);
    }

    public function test_rejection_reason_must_be_at_least_10_characters()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->kuUser)->post(route('admin.membership.reject', $application), [
            'rejection_reason' => 'Pendek',
        ]);
        
        $response->assertSessionHasErrors('rejection_reason');
    }

    public function test_cannot_reject_already_approved_application()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'approved',
            'reviewed_by' => $this->kuUser->id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($this->kuUser)->post(route('admin.membership.reject', $application), [
            'rejection_reason' => 'Alasan penolakan yang valid',
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $application->refresh();
        $this->assertEquals('approved', $application->status);
        $this->assertNull($application->rejection_reason);
    }

    public function test_unauthenticated_user_cannot_access_review_pages()
    {
        $application = MembershipApplication::create([
            'nis' => '12345',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'division_id' => $this->division->id,
            'reason' => 'Saya ingin bergabung untuk belajar',
            'status' => 'pending',
        ]);

        $this->get(route('admin.membership.index'))->assertRedirect(route('login'));
        $this->get(route('admin.membership.show', $application))->assertRedirect(route('login'));
        $this->post(route('admin.membership.approve', $application))->assertRedirect(route('login'));
        $this->post(route('admin.membership.reject', $application))->assertRedirect(route('login'));
    }
}