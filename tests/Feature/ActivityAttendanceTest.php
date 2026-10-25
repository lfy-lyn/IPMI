<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Member;
use App\Models\Division;
use App\Models\Position;
use App\Models\IncidentalActivity;
use App\Models\ActivityAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DivisionSeeder::class);
        $this->seed(\Database\Seeders\PositionSeeder::class);
    }

    public function test_ketua_umum_can_record_attendance()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $member->positions()->attach($ketuaPosition->id);

        $activity = IncidentalActivity::factory()->create([
            'division_id' => null,
            'created_by' => $user->id,
        ]);

        $attendee = Member::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->post(route('activities.attendance.record', $activity), [
                'attendances' => [
                    [
                        'member_id' => $attendee->id,
                        'status' => 'hadir',
                        'notes' => 'Tepat waktu',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_attendances', [
            'member_id' => $attendee->id,
            'incidental_activity_id' => $activity->id,
            'status' => 'hadir',
        ]);
    }

    public function test_ketua_divisi_can_record_attendance_for_own_division()
    {
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();
        $division = Division::first();
        
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division->id,
            'status' => 'active'
        ]);
        $member->positions()->attach($ketuaDivisiPosition->id);

        $activity = IncidentalActivity::factory()->create([
            'division_id' => $division->id,
            'created_by' => $user->id,
        ]);

        $attendee = Member::factory()->create([
            'division_id' => $division->id,
            'status' => 'active'
        ]);

        $this->actingAs($user)
            ->post(route('activities.attendance.record', $activity), [
                'attendances' => [
                    [
                        'member_id' => $attendee->id,
                        'status' => 'hadir',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_attendances', [
            'member_id' => $attendee->id,
            'incidental_activity_id' => $activity->id,
        ]);
    }

    public function test_ketua_divisi_cannot_record_attendance_for_other_division()
    {
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();
        $division1 = Division::first();
        $division2 = Division::skip(1)->first();
        
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division1->id,
            'status' => 'active'
        ]);
        $member->positions()->attach($ketuaDivisiPosition->id);

        $otherUser = User::factory()->create();
        $activity = IncidentalActivity::factory()->create([
            'division_id' => $division2->id,
            'created_by' => $otherUser->id,
        ]);

        $attendee = Member::factory()->create([
            'division_id' => $division2->id,
            'status' => 'active'
        ]);

        $this->actingAs($user)
            ->post(route('activities.attendance.record', $activity), [
                'attendances' => [
                    [
                        'member_id' => $attendee->id,
                        'status' => 'hadir',
                    ],
                ],
            ])
            ->assertStatus(403);
    }

    public function test_regular_member_cannot_record_attendance()
    {
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $activity = IncidentalActivity::factory()->create([
            'division_id' => null,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('activities.attendance.record', $activity), [
                'attendances' => [
                    [
                        'member_id' => $member->id,
                        'status' => 'hadir',
                    ],
                ],
            ])
            ->assertStatus(403);
    }

    public function test_attendance_can_be_updated()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $member->positions()->attach($ketuaPosition->id);

        $activity = IncidentalActivity::factory()->create(['created_by' => $user->id]);
        $attendee = Member::factory()->create(['status' => 'active']);

        $attendance = ActivityAttendance::create([
            'member_id' => $attendee->id,
            'incidental_activity_id' => $activity->id,
            'status' => 'hadir',
        ]);

        $this->actingAs($user)
            ->put(route('activities.attendance.update', $attendance), [
                'status' => 'izin',
                'notes' => 'Ada keperluan keluarga',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_attendances', [
            'id' => $attendance->id,
            'status' => 'izin',
            'notes' => 'Ada keperluan keluarga',
        ]);
    }

    public function test_attendance_prevents_duplicate_for_same_member_and_activity()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $member->positions()->attach($ketuaPosition->id);

        $activity = IncidentalActivity::factory()->create(['created_by' => $user->id]);
        $attendee = Member::factory()->create(['status' => 'active']);

        // First attendance
        $this->actingAs($user)
            ->post(route('activities.attendance.record', $activity), [
                'attendances' => [
                    [
                        'member_id' => $attendee->id,
                        'status' => 'hadir',
                    ],
                ],
            ]);

        // Second attendance (should update, not duplicate)
        $this->actingAs($user)
            ->post(route('activities.attendance.record', $activity), [
                'attendances' => [
                    [
                        'member_id' => $attendee->id,
                        'status' => 'izin',
                    ],
                ],
            ]);

        $this->assertEquals(1, ActivityAttendance::where([
            'member_id' => $attendee->id,
            'incidental_activity_id' => $activity->id,
        ])->count());

        $this->assertDatabaseHas('activity_attendances', [
            'member_id' => $attendee->id,
            'incidental_activity_id' => $activity->id,
            'status' => 'izin',
        ]);
    }

    public function test_attendance_list_shows_only_eligible_members()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $member->positions()->attach($ketuaPosition->id);

        $division = Division::first();
        $activity = IncidentalActivity::factory()->create([
            'division_id' => $division->id,
            'created_by' => $user->id,
        ]);

        $divisionMember = Member::factory()->create([
            'division_id' => $division->id,
            'status' => 'active',
            'full_name' => 'Division Member',
        ]);

        $otherDivision = Division::skip(1)->first();
        $otherMember = Member::factory()->create([
            'division_id' => $otherDivision->id,
            'status' => 'active',
            'full_name' => 'Other Division Member',
        ]);

        $this->actingAs($user)
            ->get(route('activities.attendance.index', $activity))
            ->assertStatus(200)
            ->assertSee('Division Member')
            ->assertDontSee('Other Division Member');
    }

    public function test_attendance_status_must_be_valid()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $member->positions()->attach($ketuaPosition->id);

        $activity = IncidentalActivity::factory()->create(['created_by' => $user->id]);
        $attendee = Member::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->post(route('activities.attendance.record', $activity), [
                'attendances' => [
                    [
                        'member_id' => $attendee->id,
                        'status' => 'invalid_status',
                    ],
                ],
            ])
            ->assertSessionHasErrors();
    }

    public function test_regular_member_cannot_view_attendance_page()
    {
        // D-012: catatan kehadiran tidak boleh terbuka untuk semua anggota.
        $user = User::factory()->create();
        Member::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $activity = IncidentalActivity::factory()->create([
            'division_id' => null,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('activities.attendance.index', $activity))->assertStatus(403);
        $this->actingAs($user)->get(route('activities.attendance.summary', $activity))->assertStatus(403);
    }

    public function test_member_cannot_view_attendance_for_other_division_activity()
    {
        $division1 = Division::first();
        $division2 = Division::skip(1)->first();

        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division1->id,
            'status' => 'active'
        ]);

        $otherUser = User::factory()->create();
        $activity = IncidentalActivity::factory()->create([
            'division_id' => $division2->id,
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($user)
            ->get(route('activities.attendance.index', $activity))
            ->assertStatus(403);
    }
}
