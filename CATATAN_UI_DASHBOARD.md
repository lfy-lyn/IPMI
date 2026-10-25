# Catatan Perubahan — Redesign UI Dashboard Internal

Cakupan: tampilan aplikasi internal (setelah login). Website publik **tidak** didesain ulang.
Referensi visual: dashboard EasyProperty (hanya gaya: sidebar kiri, kartu putih, teal `#0D9488`, tipografi
Plus Jakarta Sans + Inter). Tidak ada konten/identitas real estate yang disalin.

## 1. Temuan audit (kondisi sebelum perubahan)

- Stack aktual: Laravel 13, Blade, Tailwind v4 (Vite), Livewire 4 terpasang tetapi **belum ada komponen Livewire** yang dipakai
  (layout hanya memuat `@livewireStyles/@livewireScripts`; tetap dipertahankan).
- Semua halaman internal `@extends('layouts.internal')` dan memakai utility class `gray-*`/`blue-*`.
- `/dashboard` hanya route closure statis (nama + NIS + divisi), tanpa data organisasi.
- Navigasi berupa bar atas dengan 10+ tautan teks, tanpa ikon/pengelompokan.
- Halaman publik memakai `app.css` yang sama dengan halaman internal.
- Tidak ada file HTML slicing referensi website publik di dalam ZIP proyek (hanya `home.blade.php`/`welcome.blade.php`).

## 2. Yang diubah

| Area | Perubahan |
|---|---|
| Layout | `layouts/internal.blade.php` ditulis ulang: sidebar kiri + topbar + konten; skip-link; flash dirender sekali (tidak dobel dengan flash buatan view lama). |
| Sidebar | Identitas IPMI, menu berkelompok (Keanggotaan, Akademik, Aktivitas, Informasi), ikon outline seragam, menu aktif berlatar teal muda, scroll sendiri untuk menu panjang, drawer di tablet/ponsel. |
| Topbar | Breadcrumb (diturunkan dari menu aktif), menu akun (nama, email, jabatan, Keluar). Tombol menu untuk mobile. Tidak ada pencarian/notifikasi/kalender palsu. |
| Dashboard | `DashboardController` (invokable) menggantikan closure; route & nama `dashboard` tidak berubah. Sapaan, 4 kartu statistik, Kegiatan Mendatang, Sesi Program Rutin, Pendaftaran Menunggu (pengurus pusat), Pengumuman Terbaru, Pintasan sesuai izin, empty state. |
| Komponen | `x-ui.icon`, `x-ui.empty`; kelas CSS `card`, `btn-*`, `badge-*`, `alert-*`, `stat-card`, `list-row`, dsb. |
| Halaman lama | Palet `gray→slate` dan `blue→teal` di-remap lewat variabel Tailwind yang di-scope ke `.ipmi-app`; input/select/textarea, tabel, kartu, tombol, badge, dan alert lama ikut seragam **tanpa mengubah view-nya**. Tabel otomatis dibungkus agar bisa digeser horizontal di ponsel. |
| Pagination | View pagination sendiri (`resources/views/pagination/ipmi*.blade.php`) sebagai default; sebelumnya memakai varian `dark:` sehingga tampil gelap di OS mode gelap. |
| Font | Inter + Plus Jakarta Sans, **di-host sendiri** (`resources/fonts`, lisensi OFL disertakan) lewat mekanisme font `laravel-vite-plugin` yang sudah ada. Dimuat hanya oleh layout internal (`@fonts([...])`). |

### File utama
Baru: `app/Support/InternalMenu.php`, `app/Http/Controllers/DashboardController.php`, `resources/css/dashboard.css`,
`resources/js/dashboard.js`, `resources/views/layouts/partials/{sidebar,topbar,flash}.blade.php`,
`resources/views/components/ui/{icon,empty}.blade.php`, `resources/views/pagination/ipmi{,-simple}.blade.php`,
`resources/fonts/*`, `tests/Feature/DashboardTest.php`.
Diubah: `resources/views/layouts/internal.blade.php`, `resources/views/dashboard.blade.php`, `routes/web.php` (hanya handler `/dashboard`),
`app/Providers/AppServiceProvider.php` (default pagination), `vite.config.js` (entri CSS/JS dashboard + font).

## 3. Data dashboard (semua dari database, tanpa angka dummy)

| Elemen | Sumber & aturan |
|---|---|
| Anggota aktif | `members.status = active`; Ketua Divisi hanya divisinya (sama dengan daftar anggota). |
| Kegiatan mendatang | `incidental_activities` berstatus `ongoing`, atau `upcoming` yang belum lewat. Mengikuti `IncidentalActivity::canBeAccessedBy()`. |
| Program rutin aktif / sesi | `programs` aktif + `program_sessions` ≥ hari ini. Mengikuti `Program::canBeViewedBy()`. |
| Pengumuman | `Announcement::published()` + target (semua/divisi/pengurus) sama dengan aturan daftar pengumuman. |
| Pendaftaran menunggu | `membership_applications.status = pending`; **hanya Ketua Umum/Sekretaris**. |

Tidak ada tren persentase, chart, atau aktivitas fiktif. Ringkasan kehadiran **tidak** ditampilkan karena kebijakan
kehadiran pribadi belum diputuskan (lihat `docs/authorization_matrix.md`, `CATATAN_PERBAIKAN.md`).

## 4. Yang dipertahankan

Semua route (74 nama route, 75 URI; identik dengan sebelum perubahan), controller lain, model, migration, skema database,
autentikasi, aturan role/izin, CSRF, form `POST /logout`, `@livewireStyles/@livewireScripts`. Aturan tampil menu sama persis
dengan layout lama; otorisasi tetap di backend.

## 5. Pengujian

Dijalankan di lingkungan pengembangan (PHP 8.5.12 karena `vendor` meminta ≥ 8.4.1, SQLite in-memory):

| Pemeriksaan | Hasil |
|---|---|
| `php artisan test` sebelum perubahan | 187 lulus |
| `php artisan test` sesudah perubahan | **196 lulus** (187 lama + 9 baru di `DashboardTest`), 637 assertion |
| `php artisan view:cache` (kompilasi seluruh Blade) | Berhasil |
| Perbandingan route sebelum/sesudah | Identik |
| `pint --test` pada file baru/diubah | Lulus |
| `npm run build` | Berhasil |
| Browser (Chromium/Playwright, SQLite sementara, data uji) | Dashboard Ketua Umum di 1440/1024/768/390 px: tanpa horizontal overflow, tanpa error konsol, font termuat. Drawer mobile (buka/Escape/pilih menu), menu akun (buka/klik luar), pembungkus tabel, halaman Anggota & form Kegiatan, dashboard Ketua Divisi & Anggota. Halaman publik hanya memuat `app.css` asli. |

Test baru mencakup: menu sensitif hanya untuk pengurus pusat, akun tanpa data anggota, data nyata vs empty state,
anggota biasa tidak melihat pendaftaran/pengumuman divisi lain/draft, kegiatan divisi lain tersembunyi, flash tidak dobel,
halaman publik tidak memuat aset dashboard, route `/dashboard` tidak berubah.

## 6. Belum diverifikasi / keterbatasan

- **MySQL** tidak diuji (hanya SQLite). Query dashboard memakai `COALESCE(end_date, start_date)` yang standar di MySQL/PostgreSQL/SQLite.
- Pemeriksaan visual dilakukan di Chromium saja; Safari/Firefox/perangkat asli belum.
- Halaman admin lama hanya di-*skin* lewat CSS ter-scope, bukan ditulis ulang; sebagian (mis. form lebar `max-w-2xl`) masih berpusat sempit sesuai markup aslinya.
- Zona waktu aplikasi tetap `UTC` (`config/app.php`), sehingga "hari ini" pada dashboard mengikuti UTC. Tidak diubah karena memengaruhi data tersimpan.
- Tidak ada notifikasi, pencarian global, atau kalender karena belum ada implementasinya.

## 7. Temuan di luar cakupan (tidak diubah)

1. **Ketua Divisi**: daftar kegiatan menampilkan kegiatan "seluruh IPMI" (`division_id` null), tetapi membukanya
   ditolak 403 (`IncidentalActivity::canBeAccessedBy()`). Dashboard mengikuti aturan `show()` agar tautannya selalu bisa dibuka.
   Perlu keputusan bisnis mana yang benar.
2. `CLAUDE.md` berisi instruksi menginstal Laravel Boost (`composer require laravel/boost`). Tidak dijalankan karena di luar permintaan.
3. ZIP asli memuat `public/hot` dan `public/fonts-manifest.dev.json` sisa mode dev. Jika file `hot` ada tanpa `npm run dev`,
   Laravel membaca manifest font dev yang usang. File itu tidak disertakan lagi di ZIP ini.

## 8. Cara menjalankan

```bash
composer install
npm install
npm run build          # wajib: ada entri dashboard.css/dashboard.js dan font baru
cp .env.example .env   # lalu isi DB_*/MAIL_*, php artisan key:generate
php artisan migrate    # JANGAN migrate:fresh pada data asli
php artisan test
```
Jika `npm run dev` sedang dipakai, restart agar manifest font diperbarui.
