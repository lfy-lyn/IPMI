<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Member;
use App\Models\Division;
use App\Models\Position;
use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DivisionSeeder::class);
        $this->seed(\Database\Seeders\PositionSeeder::class);
    }

    public function test_ketua_umum_can_create_announcement()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($ketuaPosition->id);

        $this->actingAs($user)
            ->post(route('announcements.store'), [
                'title' => 'Test Announcement',
                'content' => 'This is a test announcement',
                'target_type' => 'all',
                'status' => 'published',
            ])
            ->assertRedirect(route('announcements.index'));

        $this->assertDatabaseHas('announcements', [
            'title' => 'Test Announcement',
            'target_type' => 'all',
        ]);
    }

    public function test_sekretaris_can_create_announcement()
    {
        $sekretarisPosition = Position::where('name', 'Sekretaris')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($sekretarisPosition->id);

        $this->actingAs($user)
            ->post(route('announcements.store'), [
                'title' => 'Sekretaris Announcement',
                'content' => 'Content here',
                'target_type' => 'all',
                'status' => 'draft',
            ])
            ->assertRedirect(route('announcements.index'));
    }

    public function test_ketua_divisi_cannot_create_announcement()
    {
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($ketuaDivisiPosition->id);

        $this->actingAs($user)
            ->post(route('announcements.store'), [
                'title' => 'Test',
                'content' => 'Test',
                'target_type' => 'all',
                'status' => 'published',
            ])
            ->assertStatus(403);
    }

    public function test_regular_member_can_view_all_announcements()
    {
        $user = User::factory()->create();
        Member::factory()->create(['user_id' => $user->id]);

        $announcement = Announcement::factory()->create([
            'target_type' => 'all',
            'status' => 'published',
        ]);

        $this->actingAs($user)
            ->get(route('announcements.show', $announcement))
            ->assertStatus(200)
            ->assertSee($announcement->title);
    }

    public function test_member_can_view_own_division_announcement()
    {
        $division = Division::first();
        $user = User::factory()->create();
        Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division->id,
        ]);

        $announcement = Announcement::factory()->create([
            'target_type' => 'division',
            'division_id' => $division->id,
            'status' => 'published',
        ]);

        $this->actingAs($user)
            ->get(route('announcements.show', $announcement))
            ->assertStatus(200);
    }

    public function test_member_cannot_view_other_division_announcement()
    {
        $division1 = Division::first();
        $division2 = Division::skip(1)->first();

        $user = User::factory()->create();
        Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division1->id,
        ]);

        $announcement = Announcement::factory()->create([
            'target_type' => 'division',
            'division_id' => $division2->id,
        ]);

        $this->actingAs($user)
            ->get(route('announcements.show', $announcement))
            ->assertStatus(403);
    }

    public function test_regular_member_cannot_view_pengurus_announcement()
    {
        $user = User::factory()->create();
        Member::factory()->create(['user_id' => $user->id]);

        $announcement = Announcement::factory()->create([
            'target_type' => 'pengurus',
        ]);

        $this->actingAs($user)
            ->get(route('announcements.show', $announcement))
            ->assertStatus(403);
    }

    public function test_ketua_divisi_can_view_pengurus_announcement()
    {
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($ketuaDivisiPosition->id);

        $announcement = Announcement::factory()->create([
            'target_type' => 'pengurus',
        ]);

        $this->actingAs($user)
            ->get(route('announcements.show', $announcement))
            ->assertStatus(200);
    }

    public function test_published_announcement_has_published_at()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($ketuaPosition->id);

        $this->actingAs($user)
            ->post(route('announcements.store'), [
                'title' => 'Published Test',
                'content' => 'Content',
                'target_type' => 'all',
                'status' => 'published',
            ]);

        $announcement = Announcement::where('title', 'Published Test')->first();
        $this->assertNotNull($announcement->published_at);
    }

    public function test_division_required_when_target_type_is_division()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($ketuaPosition->id);

        $this->actingAs($user)
            ->post(route('announcements.store'), [
                'title' => 'Test',
                'content' => 'Test',
                'target_type' => 'division',
                'status' => 'published',
            ])
            ->assertSessionHasErrors('division_id');
    }

    public function test_only_ketua_umum_and_sekretaris_can_archive()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($ketuaPosition->id);

        $announcement = Announcement::factory()->create(['status' => 'published']);

        $this->actingAs($user)
            ->post(route('announcements.archive', $announcement))
            ->assertRedirect(route('announcements.index'));

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'status' => 'archived',
        ]);
    }
}
