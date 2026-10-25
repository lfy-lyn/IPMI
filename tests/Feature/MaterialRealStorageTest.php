<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesIpmiUsers;
use Tests\TestCase;

/**
 * Menguji disk 'private' ASLI (bukan Storage::fake) pada folder sementara,
 * agar kesalahan konfigurasi disk ketahuan.
 */
class MaterialRealStorageTest extends TestCase
{
    use RefreshDatabase, CreatesIpmiUsers;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/ipmi-private-' . uniqid();
        config(['filesystems.disks.private.root' => $this->root]);
        Storage::forgetDisk('private');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_disk_private_terkonfigurasi_dan_tidak_punya_url_publik(): void
    {
        $this->assertNotNull(config('filesystems.disks.private'));
        $this->assertArrayNotHasKey('url', config('filesystems.disks.private'));
    }

    public function test_upload_dan_download_materi_lewat_disk_asli_dengan_hak_akses(): void
    {
        $kal = Division::factory()->create();
        $mtq = Division::factory()->create();
        $kadiv = $this->makeUser('Ketua Divisi', $kal);

        $this->actingAs($kadiv)->post('/admin/materi', [
            'division_id' => $kal->id, 'title' => 'Teknik Khat Naskhi', 'status' => 'active',
            'file' => UploadedFile::fake()->create('khat.pdf', 100, 'application/pdf'),
        ])->assertRedirect('/admin/materi');

        $material = Material::firstOrFail();
        $this->assertFileExists($this->root . '/' . $material->file_path);

        // anggota divisi sama: boleh; divisi lain: dilarang
        $this->actingAs($this->makeUser(null, $kal))->get("/admin/materi/{$material->id}/download")
            ->assertOk()->assertDownload('teknik-khat-naskhi.pdf');
        $this->actingAs($this->makeUser(null, $mtq))->get("/admin/materi/{$material->id}/download")->assertForbidden();
        $this->actingAs($this->makeUser('Ketua Divisi', $mtq))->get("/admin/materi/{$material->id}/download")->assertForbidden();

        // tamu
        auth()->logout();
        $this->get("/admin/materi/{$material->id}/download")->assertRedirect('/login');
    }

    public function test_materi_diarsipkan_tidak_bisa_diunduh_anggota_biasa_dan_file_berbahaya_ditolak(): void
    {
        $kal = Division::factory()->create();
        $kadiv = $this->makeUser('Ketua Divisi', $kal);

        foreach (['shell.php', 'run.html', 'x.exe'] as $bad) {
            $this->actingAs($kadiv)->post('/admin/materi', [
                'division_id' => $kal->id, 'title' => 'Bahaya', 'status' => 'active',
                'file' => UploadedFile::fake()->create($bad, 10),
            ])->assertSessionHasErrors('file');
        }
        $this->assertSame(0, Material::count());

        $this->actingAs($kadiv)->post('/admin/materi', [
            'division_id' => $kal->id, 'title' => 'Arsip', 'status' => 'archived',
            'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        ]);
        $m = Material::firstOrFail();
        $this->actingAs($this->makeUser(null, $kal))->get("/admin/materi/{$m->id}/download")->assertForbidden();
    }
}
