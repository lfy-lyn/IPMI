<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Member;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed divisions
        $this->kaligrafi = Division::create(['name' => 'Kaligrafi', 'status' => 'active']);
        $this->mtq = Division::create(['name' => 'MTQ', 'status' => 'active']);
        
        // Seed positions
        $this->ketuaUmum = Position::create(['name' => 'Ketua Umum', 'status' => 'active']);
        $this->sekretaris = Position::create(['name' => 'Sekretaris', 'status' => 'active']);
        $this->ketuaDivisi = Position::create(['name' => 'Ketua Divisi', 'status' => 'active']);
    }

    public function test_ketua_umum_can_manage_all_divisions()
    {
        $user = User::create(['email' => 'ketua@test.com', 'password' => Hash::make('pass'), 'status' => 'active']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '001', 'full_name' => 'Ketua', 'division_id' => $this->kaligrafi->id]);
        $member->positions()->attach($this->ketuaUmum);

        $this->assertTrue($member->isKetuaUmum());
        $this->assertTrue($member->canAccessAllDivisions());
        $this->assertTrue($member->canManageDivision($this->kaligrafi->id));
        $this->assertTrue($member->canManageDivision($this->mtq->id));
    }

    public function test_sekretaris_can_manage_all_divisions()
    {
        $user = User::create(['email' => 'sekretaris@test.com', 'password' => Hash::make('pass'), 'status' => 'active']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '002', 'full_name' => 'Sekretaris', 'division_id' => $this->kaligrafi->id]);
        $member->positions()->attach($this->sekretaris);

        $this->assertTrue($member->isSekretaris());
        $this->assertTrue($member->canAccessAllDivisions());
        $this->assertTrue($member->canManageDivision($this->mtq->id));
    }

    public function test_ketua_divisi_can_only_manage_own_division()
    {
        $user = User::create(['email' => 'ketuadivisi@test.com', 'password' => Hash::make('pass'), 'status' => 'active']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '003', 'full_name' => 'Ketua Divisi', 'division_id' => $this->kaligrafi->id]);
        $member->positions()->attach($this->ketuaDivisi);

        $this->assertTrue($member->isKetuaDivisi());
        $this->assertFalse($member->canAccessAllDivisions());
        $this->assertTrue($member->canManageDivision($this->kaligrafi->id));
        $this->assertFalse($member->canManageDivision($this->mtq->id));
    }

    public function test_regular_member_cannot_manage_divisions()
    {
        $user = User::create(['email' => 'member@test.com', 'password' => Hash::make('pass'), 'status' => 'active']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '004', 'full_name' => 'Member', 'division_id' => $this->kaligrafi->id]);

        $this->assertFalse($member->isKetuaUmum());
        $this->assertFalse($member->isSekretaris());
        $this->assertFalse($member->isKetuaDivisi());
        $this->assertFalse($member->canAccessAllDivisions());
        $this->assertFalse($member->canManageDivision($this->kaligrafi->id));
    }

    public function test_role_middleware_blocks_unauthorized_access()
    {
        $user = User::create(['email' => 'member@test.com', 'password' => Hash::make('pass'), 'status' => 'active']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '005', 'full_name' => 'Member', 'division_id' => $this->kaligrafi->id]);

        \Illuminate\Support\Facades\Route::get('/_test-ketua-umum', fn () => 'ok')->middleware(['web', 'auth', 'active', 'role:Ketua Umum']);

        $response = $this->actingAs($user)->get('/_test-ketua-umum');
        $response->assertStatus(403);
    }

    public function test_member_policy_view_any()
    {
        $user = User::create(['email' => 'member@test.com', 'password' => Hash::make('pass'), 'status' => 'active']);
        $member = Member::create(['user_id' => $user->id, 'nis' => '006', 'full_name' => 'Member', 'division_id' => $this->kaligrafi->id]);

        $this->assertTrue($user->can('viewAny', Member::class));
    }

    public function test_only_ketua_umum_sekretaris_can_change_nis()
    {
        $kuUser = User::create(['email' => 'ku@test.com', 'password' => Hash::make('pass'), 'status' => 'active']);
        $kuMember = Member::create(['user_id' => $kuUser->id, 'nis' => '007', 'full_name' => 'KU', 'division_id' => $this->kaligrafi->id]);
        $kuMember->positions()->attach($this->ketuaUmum);

        $targetMember = Member::create(['user_id' => User::factory()->create(['status' => 'active'])->id, 'nis' => '008', 'full_name' => 'Target', 'division_id' => $this->kaligrafi->id]);

        $this->assertTrue($kuUser->can('changeNis', $targetMember));
    }
}