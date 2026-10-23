<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Member;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed divisions and positions
        Division::create(['name' => 'Kaligrafi', 'status' => 'active']);
        Division::create(['name' => 'MTQ', 'status' => 'active']);
        Division::create(['name' => 'Dakwah', 'status' => 'active']);
        
        Position::create(['name' => 'Ketua Umum', 'status' => 'active']);
        Position::create(['name' => 'Sekretaris', 'status' => 'active']);
        Position::create(['name' => 'Ketua Divisi', 'status' => 'active']);
    }

    public function test_authenticated_member_can_access_member_list()
    {
        $user = User::factory()->create(['status' => 'active']);
        $member = Member::create([
            'user_id' => $user->id,
            'nis' => '2024001',
            'full_name' => 'Test Member',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('admin.members.index'));
        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_member_list()
    {
        $response = $this->get(route('admin.members.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_member_record_cannot_access_member_list()
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get(route('admin.members.index'));
        $response->assertStatus(403);
    }

    public function test_ketua_divisi_only_sees_own_division_members()
    {
        $kaligrafiDiv = Division::where('name', 'Kaligrafi')->first();
        $mtqDiv = Division::where('name', 'MTQ')->first();
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();

        // Ketua Divisi Kaligrafi
        $ketuaUser = User::factory()->create(['status' => 'active']);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'nis' => '2024001',
            'full_name' => 'Ketua Divisi Kaligrafi',
            'division_id' => $kaligrafiDiv->id,
            'status' => 'active',
        ]);
        $ketuaMember->positions()->attach($ketuaDivisiPosition->id);

        // Member in Kaligrafi
        $kaligrafiUser = User::factory()->create(['status' => 'active']);
        $kaligrafiMember = Member::create([
            'user_id' => $kaligrafiUser->id,
            'nis' => '2024002',
            'full_name' => 'Anggota Kaligrafi',
            'division_id' => $kaligrafiDiv->id,
            'status' => 'active',
        ]);

        // Member in MTQ
        $mtqUser = User::factory()->create(['status' => 'active']);
        $mtqMember = Member::create([
            'user_id' => $mtqUser->id,
            'nis' => '2024003',
            'full_name' => 'Anggota MTQ',
            'division_id' => $mtqDiv->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($ketuaUser)->get(route('admin.members.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Anggota Kaligrafi');
        $response->assertDontSee('Anggota MTQ');
    }

    public function test_ketua_umum_sees_all_members()
    {
        $kaligrafiDiv = Division::where('name', 'Kaligrafi')->first();
        $mtqDiv = Division::where('name', 'MTQ')->first();
        $ketuaUmumPosition = Position::where('name', 'Ketua Umum')->first();

        $ketuaUser = User::factory()->create(['status' => 'active']);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'nis' => '2024001',
            'full_name' => 'Ketua Umum',
            'division_id' => $kaligrafiDiv->id,
            'status' => 'active',
        ]);
        $ketuaMember->positions()->attach($ketuaUmumPosition->id);

        $member1 = Member::create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'nis' => '2024002',
            'full_name' => 'Anggota Kaligrafi',
            'division_id' => $kaligrafiDiv->id,
            'status' => 'active',
        ]);

        $member2 = Member::create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'nis' => '2024003',
            'full_name' => 'Anggota MTQ',
            'division_id' => $mtqDiv->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($ketuaUser)->get(route('admin.members.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Anggota Kaligrafi');
        $response->assertSee('Anggota MTQ');
    }

    public function test_search_by_name_works()
    {
        $user = User::factory()->create(['status' => 'active']);
        $member = Member::create([
            'user_id' => $user->id,
            'nis' => '2024001',
            'full_name' => 'Searcher',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $targetUser = User::factory()->create(['status' => 'active']);
        Member::create([
            'user_id' => $targetUser->id,
            'nis' => '2024002',
            'full_name' => 'Ahmad Zulfikar',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('admin.members.index', ['search' => 'Zulfikar']));
        
        $response->assertStatus(200);
        $response->assertSee('Ahmad Zulfikar');
    }

    public function test_search_by_nis_works()
    {
        $user = User::factory()->create(['status' => 'active']);
        $member = Member::create([
            'user_id' => $user->id,
            'nis' => '2024001',
            'full_name' => 'Searcher',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $targetUser = User::factory()->create(['status' => 'active']);
        Member::create([
            'user_id' => $targetUser->id,
            'nis' => '2024999',
            'full_name' => 'Target Member',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('admin.members.index', ['search' => '2024999']));
        
        $response->assertStatus(200);
        $response->assertSee('Target Member');
    }

    public function test_member_detail_page_shows_complete_info()
    {
        $ketuaUmumPosition = Position::where('name', 'Ketua Umum')->first();
        
        $user = User::factory()->create(['status' => 'active', 'email' => 'ketua@test.com']);
        $member = Member::create([
            'user_id' => $user->id,
            'nis' => '2024001',
            'full_name' => 'Test Member',
            'phone' => '081234567890',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);
        $member->positions()->attach($ketuaUmumPosition->id);

        $response = $this->actingAs($user)->get(route('admin.members.show', $member));
        
        $response->assertStatus(200);
        $response->assertSee('Test Member');
        $response->assertSee('2024001');
        $response->assertSee('ketua@test.com');
        $response->assertSee('081234567890');
    }

    public function test_ketua_divisi_cannot_view_other_division_member()
    {
        $kaligrafiDiv = Division::where('name', 'Kaligrafi')->first();
        $mtqDiv = Division::where('name', 'MTQ')->first();
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();

        $ketuaUser = User::factory()->create(['status' => 'active']);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'nis' => '2024001',
            'full_name' => 'Ketua Divisi Kaligrafi',
            'division_id' => $kaligrafiDiv->id,
            'status' => 'active',
        ]);
        $ketuaMember->positions()->attach($ketuaDivisiPosition->id);

        $mtqMemberUser = User::factory()->create(['status' => 'active']);
        $mtqMember = Member::create([
            'user_id' => $mtqMemberUser->id,
            'nis' => '2024002',
            'full_name' => 'Anggota MTQ',
            'division_id' => $mtqDiv->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($ketuaUser)->get(route('admin.members.show', $mtqMember));
        $response->assertStatus(403);
    }

    public function test_only_ketua_umum_and_sekretaris_can_edit_member()
    {
        $member = Member::create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'nis' => '2024001',
            'full_name' => 'Target Member',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $regularUser = User::factory()->create(['status' => 'active']);
        $regularMember = Member::create([
            'user_id' => $regularUser->id,
            'nis' => '2024002',
            'full_name' => 'Regular Member',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($regularUser)->get(route('admin.members.edit', $member));
        $response->assertStatus(403);
    }

    public function test_ketua_umum_can_edit_member()
    {
        $ketuaUmumPosition = Position::where('name', 'Ketua Umum')->first();
        
        $ketuaUser = User::factory()->create(['status' => 'active']);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'nis' => '2024001',
            'full_name' => 'Ketua Umum',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);
        $ketuaMember->positions()->attach($ketuaUmumPosition->id);

        $targetMember = Member::create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'nis' => '2024002',
            'full_name' => 'Target Member',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($ketuaUser)->get(route('admin.members.edit', $targetMember));
        $response->assertStatus(200);
    }

    public function test_update_member_validates_required_fields()
    {
        $ketuaUmumPosition = Position::where('name', 'Ketua Umum')->first();
        
        $ketuaUser = User::factory()->create(['status' => 'active']);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'nis' => '2024001',
            'full_name' => 'Ketua Umum',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);
        $ketuaMember->positions()->attach($ketuaUmumPosition->id);

        $targetMember = Member::create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'nis' => '2024002',
            'full_name' => 'Target Member',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($ketuaUser)->put(route('admin.members.update', $targetMember), [
            'full_name' => '',
            'nis' => '',
            'division_id' => '',
        ]);

        $response->assertSessionHasErrors(['full_name', 'nis', 'division_id']);
    }

    public function test_update_member_validates_unique_nis()
    {
        $ketuaUmumPosition = Position::where('name', 'Ketua Umum')->first();
        
        $ketuaUser = User::factory()->create(['status' => 'active']);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'nis' => '2024001',
            'full_name' => 'Ketua Umum',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);
        $ketuaMember->positions()->attach($ketuaUmumPosition->id);

        $existingMember = Member::create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'nis' => '2024999',
            'full_name' => 'Existing Member',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $targetMember = Member::create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'nis' => '2024002',
            'full_name' => 'Target Member',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($ketuaUser)->put(route('admin.members.update', $targetMember), [
            'full_name' => 'Updated Name',
            'nis' => '2024999', // Duplicate
            'division_id' => Division::first()->id,
        ]);

        $response->assertSessionHasErrors(['nis']);
    }

    public function test_update_member_successfully()
    {
        $ketuaUmumPosition = Position::where('name', 'Ketua Umum')->first();
        $mtqDiv = Division::where('name', 'MTQ')->first();
        
        $ketuaUser = User::factory()->create(['status' => 'active']);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'nis' => '2024001',
            'full_name' => 'Ketua Umum',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);
        $ketuaMember->positions()->attach($ketuaUmumPosition->id);

        $targetMember = Member::create([
            'user_id' => User::factory()->create(['status' => 'active'])->id,
            'nis' => '2024002',
            'full_name' => 'Old Name',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($ketuaUser)->put(route('admin.members.update', $targetMember), [
            'full_name' => 'New Name',
            'nis' => '2024002',
            'phone' => '081234567890',
            'division_id' => $mtqDiv->id,
        ]);

        $response->assertRedirect(route('admin.members.show', $targetMember));
        
        $targetMember->refresh();
        $this->assertEquals('New Name', $targetMember->full_name);
        $this->assertEquals('081234567890', $targetMember->phone);
        $this->assertEquals($mtqDiv->id, $targetMember->division_id);
    }

    public function test_ketua_umum_can_deactivate_any_member()
    {
        $ketuaUmumPosition = Position::where('name', 'Ketua Umum')->first();
        
        $ketuaUser = User::factory()->create(['status' => 'active']);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'nis' => '2024001',
            'full_name' => 'Ketua Umum',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);
        $ketuaMember->positions()->attach($ketuaUmumPosition->id);

        $targetUser = User::factory()->create(['status' => 'active']);
        $targetMember = Member::create([
            'user_id' => $targetUser->id,
            'nis' => '2024002',
            'full_name' => 'Target Member',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($ketuaUser)->post(route('admin.members.deactivate', $targetMember));

        $response->assertRedirect(route('admin.members.show', $targetMember));
        
        $targetMember->refresh();
        $targetUser->refresh();
        
        $this->assertEquals('inactive', $targetMember->status);
        $this->assertEquals('inactive', $targetUser->status);
    }

    public function test_ketua_divisi_can_deactivate_own_division_member()
    {
        $kaligrafiDiv = Division::where('name', 'Kaligrafi')->first();
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();

        $ketuaUser = User::factory()->create(['status' => 'active']);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'nis' => '2024001',
            'full_name' => 'Ketua Divisi',
            'division_id' => $kaligrafiDiv->id,
            'status' => 'active',
        ]);
        $ketuaMember->positions()->attach($ketuaDivisiPosition->id);

        $targetUser = User::factory()->create(['status' => 'active']);
        $targetMember = Member::create([
            'user_id' => $targetUser->id,
            'nis' => '2024002',
            'full_name' => 'Member in Same Division',
            'division_id' => $kaligrafiDiv->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($ketuaUser)->post(route('admin.members.deactivate', $targetMember));

        $response->assertRedirect(route('admin.members.show', $targetMember));
        
        $targetMember->refresh();
        $this->assertEquals('inactive', $targetMember->status);
    }

    public function test_ketua_divisi_cannot_deactivate_other_division_member()
    {
        $kaligrafiDiv = Division::where('name', 'Kaligrafi')->first();
        $mtqDiv = Division::where('name', 'MTQ')->first();
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();

        $ketuaUser = User::factory()->create(['status' => 'active']);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'nis' => '2024001',
            'full_name' => 'Ketua Divisi Kaligrafi',
            'division_id' => $kaligrafiDiv->id,
            'status' => 'active',
        ]);
        $ketuaMember->positions()->attach($ketuaDivisiPosition->id);

        $targetUser = User::factory()->create(['status' => 'active']);
        $targetMember = Member::create([
            'user_id' => $targetUser->id,
            'nis' => '2024002',
            'full_name' => 'Member in MTQ',
            'division_id' => $mtqDiv->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($ketuaUser)->post(route('admin.members.deactivate', $targetMember));
        $response->assertStatus(403);
    }

    public function test_regular_member_cannot_deactivate_anyone()
    {
        $regularUser = User::factory()->create(['status' => 'active']);
        $regularMember = Member::create([
            'user_id' => $regularUser->id,
            'nis' => '2024001',
            'full_name' => 'Regular Member',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $targetUser = User::factory()->create(['status' => 'active']);
        $targetMember = Member::create([
            'user_id' => $targetUser->id,
            'nis' => '2024002',
            'full_name' => 'Target Member',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($regularUser)->post(route('admin.members.deactivate', $targetMember));
        $response->assertStatus(403);
    }

    public function test_cannot_deactivate_already_inactive_member()
    {
        $ketuaUmumPosition = Position::where('name', 'Ketua Umum')->first();
        
        $ketuaUser = User::factory()->create(['status' => 'active']);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'nis' => '2024001',
            'full_name' => 'Ketua Umum',
            'division_id' => Division::first()->id,
            'status' => 'active',
        ]);
        $ketuaMember->positions()->attach($ketuaUmumPosition->id);

        $targetUser = User::factory()->create(['status' => 'inactive']);
        $targetMember = Member::create([
            'user_id' => $targetUser->id,
            'nis' => '2024002',
            'full_name' => 'Already Inactive',
            'division_id' => Division::first()->id,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($ketuaUser)->post(route('admin.members.deactivate', $targetMember));
        $response->assertSessionHas('error');
    }
}
