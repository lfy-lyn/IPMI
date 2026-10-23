# IPMI — Sistem Informasi Organisasi

Aplikasi web untuk mendukung informasi publik dan pengelolaan internal organisasi IPMI.

## Teknologi

- Laravel 13
- Blade
- Livewire
- Tailwind CSS
- MySQL

Filament tidak digunakan.

## Fitur MVP

### Situs Publik
- Profil IPMI
- Informasi divisi
- Informasi kegiatan yang ditujukan untuk publik
- Formulir pendaftaran anggota

### Aplikasi Internal
- Autentikasi dan pengelolaan akun
- Persetujuan pendaftaran anggota
- Pengelolaan anggota
- Pengelolaan jabatan dan role sistem
- Pengelolaan tahun ajaran dan kelas
- Riwayat kelas anggota
- Kenaikan kelas massal
- Program dan sesi kegiatan
- Kehadiran
- Materi divisi
- Pengumuman internal
- Audit log untuk tindakan penting

## Divisi

Divisi IPMI pada MVP:
- Kaligrafi
- MTQ
- Dakwah
- Hadroh

Intelektual merupakan jabatan atau fungsi khusus yang belum termasuk dalam MVP.

## Pengelolaan Kelas

Kelas merepresentasikan rombongan belajar, misalnya:
- X RPL 1
- XI RPL 1
- XI TKJ 2
- XII RPL 1

Setiap kelas terhubung dengan tahun ajaran dan memiliki tingkat, jurusan, serta nomor rombel.

Daftar kelas dikelola oleh pengurus yang berwenang (Sekretaris dan Ketua Umum). Sistem dapat membantu menyalin daftar kelas dari tahun sebelumnya, tetapi daftar tersebut harus diperiksa dan dikonfirmasi (D-005).

Riwayat kelas setiap anggota disimpan per tahun ajaran. Kenaikan kelas dapat dilakukan secara massal setelah pemetaan kelas tujuan diperiksa dan dikonfirmasi (D-007).

Riwayat kelas sebelumnya harus dipertahankan (D-006).

### Aturan Penting Kelas (D-025, D-026):
- Setiap anggota aktif wajib memiliki kelas pada tahun ajaran yang berlaku.
- Perpindahan rombel di tengah tahun ajaran tidak diperbolehkan.
- Bedakan koreksi data yang salah dari perpindahan rombel.
- Jangan membuat mekanisme yang memungkinkan perpindahan rombel dengan menyamarkannya sebagai koreksi.
- Kenaikan kelas hanya dilakukan untuk tahun ajaran berikutnya dan harus mempertahankan riwayat sebelumnya.

## Dokumentasi Proyek

Baca dokumentasi berikut sebelum mengubah implementasi:

- `AGENTS.md` — instruksi untuk agen pengembangan.
- `IPMI_project_spec_v1.1.md` — spesifikasi utama proyek.
- `docs/architecture.md` — arsitektur aplikasi.
- `docs/database_design.md` — rancangan database.
- `docs/erd.md` — entity relationship diagram final.
- `docs/authorization_matrix.md` — matriks hak akses.
- `docs/implementation_plan.md` — urutan implementasi.
- `docs/testing_strategy.md` — strategi pengujian.
- `docs/decisions.md` — catatan keputusan dan hal yang masih terbuka.

Jika implementasi bertentangan dengan keputusan yang sudah disepakati, jangan mengubah perilaku secara diam-diam. Periksa dokumentasi dan laporkan konflik terlebih dahulu.

## Persiapan Pengembangan

Pastikan PHP, Composer, Node.js, dan MySQL sudah tersedia.

Periksa versi serta struktur proyek sebelum menjalankan perintah yang mengubah file atau database.

Jika proyek Laravel sudah tersedia, periksa `composer.json`, `package.json`, `.env.example`, dan status Git sebelum melakukan perubahan.

Jangan menampilkan atau membagikan isi `.env` yang mengandung rahasia.

## Perintah Umum Laravel

Instal dependensi PHP:

```bash
composer install
```

Instal dependensi Node.js:

```bash
npm install
```

Build assets:

```bash
npm run build
```

Jalankan migrasi database (setelah konfigurasi .env):

```bash
php artisan migrate
```

Jalankan server development:

```bash
php artisan serve
```

## Keputusan Desain Penting

### Relasi Akun dan Anggota (D-027)
- users 1:1 members via members.user_id
- users: autentikasi, members: data anggota
- Penonaktifan menggunakan soft delete atau status

### Status Tahun Ajaran (D-028)
- Status: upcoming, active, archived
- Maksimal satu tahun ajaran berstatus active
- Sistem menggunakan tahun ajaran active untuk kelas saat ini

### Struktur Presensi (D-029)
- program_session_attendances untuk sesi program rutin
- activity_attendances untuk kegiatan insidental
- Foreign key eksplisit, bukan polymorphic relation
- Kegiatan multi-hari: mode per_event atau per_day
