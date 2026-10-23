# Laporan Finalisasi Desain Database — IPMI

**Tanggal:** 2026-10-23
**Status:** SELESAI
**Versi:** 1.0

---

## 1. RINGKASAN EKSEKUTIF

Finalisasi desain database untuk MVP aplikasi IPMI telah selesai dilaksanakan. Tiga keputusan CRITICAL (A1, A2, A3) yang menghambat pembuatan ERD telah diputuskan dan didokumentasikan.

**Keputusan yang Difinalisasi:**
- **D-027:** Relasi Akun dan Anggota (users 1:1 members)
- **D-028:** Status Tahun Ajaran (upcoming, active, archived)
- **D-029:** Struktur Presensi (explicit foreign keys, bukan polymorphic)

**Keputusan yang Diupgrade:**
- D-005, D-007, D-008, D-024: dari PROPOSED → CONFIRMED
- D-018: dari OPEN → CLOSED (dijawab oleh D-025)

**Output Utama:**
- ERD final dengan 18 tabel utama
- Dokumentasi lengkap untuk implementasi migrasi
- Konsistensi dokumentasi di seluruh file proyek

---

## 2. FILE YANG DIBUAT DAN DIPERBARUI

### 2.1 File Baru

#### docs/erd.md (586 baris)
- Diagram Mermaid lengkap dengan 18 tabel
- Penjelasan setiap tabel dan kolom
- Primary key, foreign key, unique constraints
- Indexes yang diperlukan
- Aturan integritas level aplikasi
- Validasi keputusan terhadap dokumentasi
- Catatan implementasi

### 2.2 File yang Diperbarui

#### docs/decisions.md (365 baris)
**Perubahan Status:**
- D-005: PROPOSED → CONFIRMED (Pengelolaan Daftar Kelas)
- D-007: PROPOSED → CONFIRMED (Kenaikan Kelas Massal)
- D-008: PROPOSED → CONFIRMED (Kelas pada Formulir Pendaftaran)
- D-018: OPEN → CLOSED (Perpindahan Rombel, dijawab oleh D-025)
- D-024: PROPOSED → CONFIRMED (Detail Implementasi Kelas)

**Keputusan Baru:**
- D-027: Relasi Akun dan Anggota (CONFIRMED, 2026-10-23)
- D-028: Status Tahun Ajaran (CONFIRMED, 2026-10-23)
- D-029: Struktur Presensi (CONFIRMED, 2026-10-23)

**Alasan Perubahan:**
- D-005, D-007, D-008: Tidak ada ambiguitas, isinya sudah jelas dan didukung keputusan lain
- D-018: Redundan dengan D-025 yang sudah menjawab aturan perpindahan rombel
- D-024: Struktur tiga tabel sudah detail dan konsisten

#### docs/database_design.md (536 baris)
**Perubahan:**
- Section 3.1 users: Struktur lengkap dengan kolom dan relasi (D-027)
- Section 3.2 members: Struktur lengkap dengan relasi 1:1 ke users
- Section 4.1 academic_years: Status dengan validasi single active year (D-028)
- Section 8.3-8.6: Struktur presensi eksplisit dengan 2 tabel terpisah (D-029)
  - program_session_attendances
  - activity_attendances
  - incidental_activities dengan attendance_mode
  - activity_days untuk kegiatan multi-hari
- Section 13-14: Update dari "Hal yang Harus Disetujui" → "Hal yang Sudah Diputuskan"

#### docs/architecture.md (245 baris)
**Perubahan:**
- Section 5: Autentikasi dan Otorisasi
  - Tambahan 5.1: Relasi Akun dan Anggota (D-027)
  - Update 5.2: Role Sistem vs Jabatan Organisasi
- Section 6.1: Data Kelas
  - Referensi ke D-024, D-027, D-028
  - Penjelasan status tahun ajaran
  - Unique constraint
- Section 6.2: Penetapan Kelas
  - Referensi D-008 dan D-026
  - Kewajiban kelas untuk anggota aktif
- Section 6.3: Kenaikan Kelas
  - Referensi D-007, D-025
  - Aturan tidak boleh pindah rombel
- Section 7: Modul Program dan Kehadiran
  - Sub-section 7.1: Program Rutin
  - Sub-section 7.2: Kegiatan Insidental dengan mode presensi
  - Sub-section 7.3: Status Kehadiran
  - Referensi D-029

#### README.md (145 baris)
**Perubahan:**
- Section Pengelolaan Kelas: Referensi D-005, D-006, D-007
- Sub-section Aturan Penting Kelas: Referensi D-025, D-026
- Section Dokumentasi Proyek: Tambahan link ke docs/erd.md
- Section Perintah Umum Laravel: Format perbaikan
- Section baru: Keputusan Desain Penting
  - D-027: Relasi Akun dan Anggota
  - D-028: Status Tahun Ajaran
  - D-029: Struktur Presensi

**Total Baris Diperbarui:** ~400 baris di 5 file

---

## 3. RINGKASAN DESAIN DATABASE

### 3.1 Struktur Keputusan

#### D-027: Relasi Akun dan Anggota ✅
**Keputusan:**
- Relasi 1:1 antara `users` dan `members`
- `users.id` ← `members.user_id` (foreign key, unique)
- users: autentikasi (email, password, status)
- members: data anggota (NIS, nama, divisi)
- Penonaktifan: soft delete atau status 'inactive'

**Implikasi:**
- Setiap anggota pasti punya akun
- Akun yang dinonaktifkan tetap menyimpan riwayat
- Foreign key constraint menjaga integritas

#### D-028: Status Tahun Ajaran ✅
**Keputusan:**
- Status: `upcoming`, `active`, `archived`
- Maksimal 1 tahun ajaran berstatus `active`
- Validasi single active year di aplikasi + database (partial index jika support)
- Tahun ajaran active digunakan untuk kelas saat ini

**Implikasi:**
- Sistem selalu tahu tahun ajaran yang sedang berjalan
- Kelas saat ini = member_class_histories WHERE class.academic_year.status = 'active'
- Riwayat tahun lalu (archived) tetap tersimpan

#### D-029: Struktur Presensi ✅
**Keputusan:**
- Tidak menggunakan polymorphic relation
- 2 tabel terpisah dengan foreign key eksplisit:
  1. `program_session_attendances` - presensi sesi program rutin
  2. `activity_attendances` - presensi kegiatan insidental
- Kegiatan multi-hari: kolom `attendance_mode` ('per_event' atau 'per_day')
- `activity_days` untuk hari pelaksanaan kegiatan multi-hari
- `activity_attendances.activity_day_id` nullable:
  - NULL = presensi untuk seluruh kegiatan (mode per_event)
  - NOT NULL = presensi untuk hari tertentu (mode per_day)

**Implikasi:**
- Foreign key constraint di database
- Mudah dipahami pengembang pemula
- Tidak ada tabel polymorphic yang kompleks
- Integritas data terjaga

### 3.2 Daftar 18 Tabel Utama

1. **users** - autentikasi akun
2. **members** - profil anggota
3. **divisions** - divisi organisasi (Kaligrafi, MTQ, Dakwah, Hadroh)
4. **positions** - jabatan organisasi (Ketua Umum, Sekretaris, dll)
5. **member_positions** - junction table anggota-jabatan
6. **academic_years** - tahun ajaran
7. **classes** - rombongan belajar
8. **member_class_histories** - riwayat kelas anggota
9. **membership_applications** - pendaftaran calon anggota
10. **activation_tokens** - token aktivasi akun
11. **programs** - program rutin/berulang
12. **program_sessions** - sesi pelaksanaan program
13. **program_session_attendances** - presensi sesi program
14. **incidental_activities** - kegiatan insidental
15. **activity_days** - hari kegiatan multi-hari
16. **activity_attendances** - presensi kegiatan insidental
17. **materials** - materi divisi
18. **announcements** - pengumuman internal
19. **audit_logs** - log tindakan penting

### 3.3 Relasi Kunci

```
users 1:1 members
members N:1 divisions
members 1:N member_class_histories
classes N:1 academic_years
member_class_histories N:1 classes
programs 1:N program_sessions
program_sessions 1:N program_session_attendances
incidental_activities 1:N activity_days
incidental_activities 1:N activity_attendances
activity_days 1:N activity_attendances (optional)
```

### 3.4 Unique Constraints

1. users: email
2. members: nis, user_id
3. divisions: name
4. positions: name
5. academic_years: name
6. classes: (academic_year_id, grade_level, major, class_number)
7. member_class_histories: (member_id, class_id)
8. member_positions: (member_id, position_id)
9. activation_tokens: token
10. program_session_attendances: (member_id, program_session_id)
11. activity_attendances: (member_id, incidental_activity_id, activity_day_id)

---

## 4. VALIDASI DESAIN

### 4.1 Checklist Validasi ✅

- [x] Semua relasi memiliki alasan yang jelas
- [x] Constraint mencegah data duplikat yang tidak diperbolehkan
- [x] Aturan kelas wajib dapat diterapkan (validasi aplikasi)
- [x] Larangan pindah rombel dapat diterapkan (validasi aplikasi)
- [x] Histori kelas tetap tersedia setelah kenaikan kelas (foreign key preserve)
- [x] Struktur presensi mendukung sesi rutin dan kegiatan insidental
- [x] Struktur akun sesuai dengan proses persetujuan dan aktivasi
- [x] Rancangan hak akses konsisten dengan authorization matrix
- [x] Tidak ada keputusan yang menghambat migrasi

### 4.2 Konsistensi Dokumentasi ✅

**Keputusan vs Dokumentasi:**
- D-027 ↔ database_design.md section 3.1, 3.2 ✅
- D-028 ↔ database_design.md section 4.1 ✅
- D-029 ↔ database_design.md section 8.3-8.6 ✅
- D-025, D-026 ↔ architecture.md section 6 ✅
- D-003 ↔ divisions table (4 divisi) ✅
- D-006 ↔ member_class_histories (riwayat preserved) ✅
- D-024 ↔ 3 tabel (academic_years, classes, member_class_histories) ✅

**Dokumentasi Cross-Reference:**
- decisions.md ↔ database_design.md ✅
- decisions.md ↔ architecture.md ✅
- decisions.md ↔ erd.md ✅
- erd.md ↔ database_design.md ✅
- README.md ↔ decisions.md ✅

### 4.3 Aturan Integritas

**Level Database (Foreign Key):**
- ✅ users → members (1:1)
- ✅ members → divisions
- ✅ classes → academic_years
- ✅ member_class_histories → members, classes
- ✅ program_session_attendances → members, program_sessions
- ✅ activity_attendances → members, incidental_activities, activity_days

**Level Aplikasi (Validasi):**
- ⚠️ Single active academic_year (MySQL: partial index atau app validation)
- ⚠️ Anggota aktif wajib punya kelas pada tahun ajaran active
- ⚠️ Satu anggota maksimal satu kelas per tahun ajaran
- ⚠️ Tidak boleh pindah rombel di tengah tahun ajaran
- ⚠️ Validasi attendance_mode dengan activity_day_id di activity_attendances
- ⚠️ Maksimal 3 permohonan per NIS (WHERE status IN ('pending', 'approved'))

**Catatan:** ⚠️ = Tidak dapat dijamin hanya dengan constraint database, perlu validasi aplikasi

---

## 5. KEPUTUSAN YANG MASIH TERBUKA

### 5.1 Tidak Menghambat Migrasi (Kategori B)

#### D-019: Permohonan Dibatalkan atau Dihapus
**Pertanyaan:** Apakah permohonan yang dibatalkan/ditolak dihitung dalam batas 3 per NIS?
**Dampak:** Query counting permohonan
**Status:** Dapat diputuskan saat implementasi modul pendaftaran (Tahap 5)
**Struktur tabel:** Sudah cukup (membership_applications dengan kolom status)

#### D-020: Token Aktivasi
**Pertanyaan:** Berapa lama token valid? Bagaimana prosedur resend?
**Dampak:** Nilai expires_at dan logika resend
**Status:** Dapat diputuskan saat implementasi modul aktivasi (Tahap 5)
**Struktur tabel:** Sudah cukup (activation_tokens dengan kolom expires_at)

#### D-021: Kehadiran
**Pertanyaan:** 
- Bolehkah ubah target peserta setelah sesi selesai?
- Berapa lama batas waktu ubah kehadiran?
- Bolehkah hapus program yang sudah punya kehadiran?
**Dampak:** Validasi form dan soft delete
**Status:** Dapat diputuskan saat implementasi modul kehadiran (Tahap 9)
**Struktur tabel:** Sudah cukup

### 5.2 Tidak Menghambat MVP (Kategori C)

#### D-022: Bendahara
**Pertanyaan:** Hak akses Bendahara di aplikasi tanpa modul keuangan?
**Dampak:** Minimal (tidak ada fitur keuangan di MVP)
**Status:** Bisa ditunda sampai post-MVP

#### D-023: Audit Log
**Pertanyaan:** Siapa boleh baca audit log? Berapa lama disimpan?
**Dampak:** Policy dan retention
**Status:** Dapat diputuskan saat implementasi modul audit log (Tahap 13)
**Struktur tabel:** Sudah cukup (audit_logs)

---

## 6. REKOMENDASI LANGKAH IMPLEMENTASI BERIKUTNYA

### Tahap 3: Fondasi Aplikasi (Estimasi: 3-5 hari kerja)

#### 3.1 Setup Version Control
```bash
git init
git add .
git commit -m "Initial commit: Laravel 13 with documentation"
```

#### 3.2 Konfigurasi Database
1. Edit `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ipmi_dev
   DB_USERNAME=root
   DB_PASSWORD=
   ```
2. Buat database: `CREATE DATABASE ipmi_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
3. Test koneksi: `php artisan db:show`

#### 3.3 Setup Frontend Stack
```bash
npm install
npm run build
php artisan livewire:publish --config
```

#### 3.4 Buat Migrasi Database (Urutan Dependency)

**Batch 1 - Tabel Independen:**
```bash
php artisan make:migration create_divisions_table
php artisan make:migration create_positions_table
```

**Batch 2 - Users dan Members:**
```bash
# users sudah ada dari Laravel, modify migration yang ada
php artisan make:migration create_members_table
php artisan make:migration create_member_positions_table
```

**Batch 3 - Tahun Ajaran dan Kelas:**
```bash
php artisan make:migration create_academic_years_table
php artisan make:migration create_classes_table
php artisan make:migration create_member_class_histories_table
```

**Batch 4 - Pendaftaran:**
```bash
php artisan make:migration create_membership_applications_table
php artisan make:migration create_activation_tokens_table
```

**Batch 5 - Program dan Presensi:**
```bash
php artisan make:migration create_programs_table
php artisan make:migration create_program_sessions_table
php artisan make:migration create_program_session_attendances_table
php artisan make:migration create_incidental_activities_table
php artisan make:migration create_activity_days_table
php artisan make:migration create_activity_attendances_table
```

**Batch 6 - Materi, Pengumuman, Audit:**
```bash
php artisan make:migration create_materials_table
php artisan make:migration create_announcements_table
php artisan make:migration create_audit_logs_table
```

**Referensi:** Gunakan `docs/erd.md` section 3 untuk struktur lengkap setiap tabel.

#### 3.5 Buat Models
```bash
php artisan make:model Member
php artisan make:model Division
php artisan make:model AcademicYear
php artisan make:model ClassRoom  # Class adalah reserved word PHP
php artisan make:model MemberClassHistory
# dst...
```

**Catatan:** Setup relationships di masing-masing model sesuai erd.md

#### 3.6 Setup Authentication
- Modifikasi migration users yang sudah ada
- Buat middleware untuk role-based access
- Setup policy untuk authorization

#### 3.7 Buat Layout Blade
- `resources/views/layouts/public.blade.php`
- `resources/views/layouts/app.blade.php`

#### 3.8 Test Migrasi
```bash
php artisan migrate
php artisan migrate:rollback  # Test rollback
php artisan migrate  # Migrate lagi
```

#### 3.9 Seed Data Awal
```bash
php artisan make:seeder DivisionSeeder
```
Seed 4 divisi: Kaligrafi, MTQ, Dakwah, Hadroh

### Tahap 4-13: Implementasi Fitur (Setelah Tahap 3 Selesai)

Lihat `docs/implementation_plan.md` untuk detail urutan implementasi fitur.

**Keputusan Kategori B (D-019, D-020, D-021, D-023) dapat diputuskan sambil jalan** saat implementasi modul terkait.

---

## 7. CATATAN TEKNIS IMPLEMENTASI

### 7.1 Soft Delete
```php
// Di model User
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable {
    use SoftDeletes;
}
```

### 7.2 Enum di MySQL
```php
// Di migration
$table->enum('status', ['pending', 'active', 'inactive'])->default('pending');
```

Alternatif: string + validation jika lebih fleksibel.

### 7.3 Single Active Academic Year
```php
// Validasi di AcademicYearController atau Observer
if ($request->status === 'active') {
    AcademicYear::where('status', 'active')
        ->where('id', '!=', $academicYear->id)
        ->update(['status' => 'archived']);
}
```

MySQL 8.0+:
```sql
CREATE UNIQUE INDEX idx_active_year ON academic_years ((CASE WHEN status = 'active' THEN 1 END));
```

### 7.4 Unique Constraint Composite
```php
// Di migration classes
$table->unique(['academic_year_id', 'grade_level', 'major', 'class_number'], 'unique_class');
```

### 7.5 JSON Metadata
```php
// Di migration audit_logs
$table->json('metadata')->nullable();

// Usage
AuditLog::create([
    'actor_id' => auth()->id(),
    'action' => 'member.class.updated',
    'entity_type' => 'Member',
    'entity_id' => $member->id,
    'metadata' => [
        'old_class' => $oldClass->name,
        'new_class' => $newClass->name,
        'reason' => 'Koreksi data salah input',
        'ip_address' => request()->ip(),
    ],
]);
```

---

## 8. PERUBAHAN DARI KONDISI AWAL

### 8.1 Status Keputusan

| ID | Keputusan | Status Awal | Status Akhir | Alasan |
|----|-----------|-------------|--------------|--------|
| D-005 | Pengelolaan Daftar Kelas | PROPOSED | CONFIRMED | Tidak ada ambiguitas |
| D-007 | Kenaikan Kelas Massal | PROPOSED | CONFIRMED | Alur sudah detail |
| D-008 | Kelas pada Formulir Pendaftaran | PROPOSED | CONFIRMED | Didukung D-026 |
| D-018 | Perpindahan Rombel | OPEN | CLOSED | Dijawab oleh D-025 |
| D-024 | Detail Implementasi Kelas | PROPOSED | CONFIRMED | Struktur sudah jelas |
| D-027 | Relasi Akun dan Anggota | - | CONFIRMED | Keputusan baru |
| D-028 | Status Tahun Ajaran | - | CONFIRMED | Keputusan baru |
| D-029 | Struktur Presensi | - | CONFIRMED | Keputusan baru |

### 8.2 Anomali yang Diperbaiki

1. **D-018 vs D-025:** Redundansi dihilangkan, D-018 ditutup
2. **database_design.md point 3:** Pertanyaan "apakah anggota boleh tanpa kelas" sudah dijawab D-026
3. **Status PROPOSED yang sebenarnya sudah jelas:** Upgrade ke CONFIRMED

### 8.3 Dokumentasi Baru

- `docs/erd.md` - 586 baris, diagram Mermaid + dokumentasi lengkap
- Referensi silang antar dokumen ditambahkan
- Section baru di README.md: Keputusan Desain Penting

---

## 9. KESIMPULAN

### 9.1 Target Tercapai ✅

- [x] Keputusan A1, A2, A3 diputuskan dan didokumentasikan
- [x] ERD final dengan 18 tabel dibuat
- [x] Dokumentasi diperbarui untuk konsistensi
- [x] Anomali dan redundansi diperbaiki
- [x] Tidak ada keputusan yang menghambat migrasi
- [x] Panduan implementasi disiapkan

### 9.2 Siap Implementasi

**Status Desain:** FINAL - Siap Implementasi

Proyek IPMI dapat melanjutkan ke Tahap 3 (Fondasi Aplikasi) dengan confidence tinggi bahwa desain database sudah solid dan konsisten dengan seluruh kebutuhan MVP.

**Keputusan yang masih terbuka (D-019, D-020, D-021, D-022, D-023) tidak menghambat implementasi** karena:
1. Struktur tabel sudah cukup
2. Aturan bisnis akan ditangani di level aplikasi
3. Dapat diputuskan sambil jalan saat implementasi modul terkait

### 9.3 Next Immediate Action

```bash
# 1. Init Git
git init
git add .
git commit -m "Initial commit with finalized database design"

# 2. Configure database
cp .env.example .env
# Edit .env: DB_CONNECTION=mysql, create database

# 3. Start implementing migrations
php artisan make:migration create_divisions_table
```

---

**Laporan ini merekam proses dan hasil finalisasi desain database IPMI untuk referensi masa depan dan audit trail keputusan.**

*Disusun oleh: Kiro (Hermes Agent)*
*Tanggal: 2026-10-23*
