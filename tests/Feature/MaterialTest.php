<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Member;
use App\Models\Division;
use App\Models\Position;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MaterialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DivisionSeeder::class);
        $this->seed(\Database\Seeders\PositionSeeder::class);
        Storage::fake('private');
    }

    public function test_ketua_umum_can_create_material()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($ketuaPosition->id);

        $division = Division::first();

        $this->actingAs($user)
            ->post(route('materials.store'), [
                'division_id' => $division->id,
                'title' => 'Test Material',
                'description' => 'Test Description',
                'external_url' => 'https://example.com/material',
                'status' => 'active',
            ])
            ->assertRedirect(route('materials.index'));

        $this->assertDatabaseHas('materials', [
            'title' => 'Test Material',
            'division_id' => $division->id,
        ]);
    }

    public function test_ketua_divisi_can_create_material_for_own_division()
    {
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();
        $division = Division::first();
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division->id,
        ]);
        $member->positions()->attach($ketuaDivisiPosition->id);

        $this->actingAs($user)
            ->post(route('materials.store'), [
                'division_id' => $division->id,
                'title' => 'Division Material',
                'external_url' => 'https://example.com',
                'status' => 'active',
            ])
            ->assertRedirect(route('materials.index'));
    }

    public function test_ketua_divisi_cannot_create_material_for_other_division()
    {
        $ketuaDivisiPosition = Position::where('name', 'Ketua Divisi')->first();
        $division1 = Division::first();
        $division2 = Division::skip(1)->first();
        
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division1->id,
        ]);
        $member->positions()->attach($ketuaDivisiPosition->id);

        $this->actingAs($user)
            ->post(route('materials.store'), [
                'division_id' => $division2->id,
                'title' => 'Hacked Material',
                'external_url' => 'https://example.com',
                'status' => 'active',
            ])
            ->assertStatus(403);
    }

    public function test_regular_member_cannot_create_material()
    {
        $user = User::factory()->create();
        Member::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('materials.store'), [
                'division_id' => Division::first()->id,
                'title' => 'Test',
                'external_url' => 'https://example.com',
                'status' => 'active',
            ])
            ->assertStatus(403);
    }

    public function test_material_must_have_file_or_url()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($ketuaPosition->id);

        $this->actingAs($user)
            ->post(route('materials.store'), [
                'division_id' => Division::first()->id,
                'title' => 'Test',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_member_can_view_material_from_own_division()
    {
        $division = Division::first();
        $user = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division->id,
        ]);

        $material = Material::factory()->create([
            'division_id' => $division->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('materials.show', $material))
            ->assertStatus(200)
            ->assertSee($material->title);
    }

    public function test_member_cannot_view_material_from_other_division()
    {
        $division1 = Division::first();
        $division2 = Division::skip(1)->first();

        $user = User::factory()->create();
        Member::factory()->create([
            'user_id' => $user->id,
            'division_id' => $division1->id,
        ]);

        $material = Material::factory()->create([
            'division_id' => $division2->id,
        ]);

        $this->actingAs($user)
            ->get(route('materials.show', $material))
            ->assertStatus(403);
    }

    public function test_file_upload_validation()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($ketuaPosition->id);

        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $this->actingAs($user)
            ->post(route('materials.store'), [
                'division_id' => Division::first()->id,
                'title' => 'Test with File',
                'file' => $file,
                'status' => 'active',
            ])
            ->assertRedirect(route('materials.index'));

        Storage::disk('private')->assertExists('materials/' . $file->hashName());
    }

    public function test_only_ketua_umum_and_sekretaris_can_archive()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($ketuaPosition->id);

        $material = Material::factory()->create();

        $this->actingAs($user)
            ->post(route('materials.archive', $material))
            ->assertRedirect(route('materials.index'));

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'status' => 'archived',
        ]);
    }

    public function test_url_validation()
    {
        $ketuaPosition = Position::where('name', 'Ketua Umum')->first();
        $user = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $user->id]);
        $member->positions()->attach($ketuaPosition->id);

        $this->actingAs($user)
            ->post(route('materials.store'), [
                'division_id' => Division::first()->id,
                'title' => 'Test',
                'external_url' => 'invalid-url',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('external_url');
    }
}
