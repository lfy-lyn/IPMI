# Catatan Perbaikan (sesi lanjutan)

## Cara menjalankan lokal (butuh PHP 8.4+, Composer, MySQL)
1. `composer install` dan `npm install && npm run build`
2. `cp .env.example .env`, isi DB_* dan MAIL_*, lalu `php artisan key:generate`
3. Buat database kosong `ipmi`, lalu `php artisan migrate` (JANGAN pakai migrate:fresh pada data asli)
4. `php artisan ipmi:buat-admin` -> membuat divisi, jabatan, dan Ketua Umum pertama
5. `php artisan serve`; test: `php artisan test`

## Yang diperbaiki
- Kelas: ClassRoomController dibuat (sebelumnya kosong; route error)
- Audit log: tabel dilengkapi + `AuditLog::record()`; dipakai di kelas, absensi, kegiatan, anggota, pendaftaran
- Penetapan kelas: koreksi wajib alasan + audit (D-025)
- Absensi: hanya pengurus berwenang; cegah IDOR; mode per_day (hari dibuat otomatis); halaman rekap dibuat
- Kegiatan: anggota hanya lihat kegiatan umum + divisinya; Ketua Divisi tidak bisa lintas divisi
- Anggota: data pribadi (NIS/email/telepon) hanya untuk yang berwenang; aturan nonaktifkan lebih aman
- Pendaftaran: akun TIDAK lagi ditautkan lewat email (cegah pengambilalihan akun); kolom member_id; throttle; token hanya untuk akun pending
- Materi: disk `private` ditambahkan di config/filesystems.php (sebelumnya tidak ada -> upload/download error)
- Pengumuman: draft/arsip/terjadwal tidak terlihat anggota; ada kolom waktu terbit
- Route /test-ketua-umum dan 2 migrasi stub kosong dihapus
- Perintah `php artisan ipmi:buat-admin` untuk Ketua Umum pertama
- Jabatan: Ketua Umum/Sekretaris dapat menetapkan & mencabut jabatan dari halaman detail anggota
  (Sekretaris hanya Bendahara & Ketua Divisi; 1 Sekretaris aktif; 1 Ketua Divisi per divisi; Ketua Umum terakhir tidak bisa dicabut)
- Kenaikan kelas: kelas kosong/lulus boleh tanpa tujuan, validasi kelas asal/tujuan, anggota yang sudah punya kelas dilewati, audit log
- Program Rutin (BARU): program + sesi + absensi sesi + rekap (tabel diisi lewat migrasi 2026_10_25_000300)
- Navigasi ditulis ulang: berbasis hak akses, ada menu HP, Kegiatan kini terlihat untuk semua anggota, pesan 'warning' tampil

## Pengujian
- 187 test lulus di sqlite DAN PostgreSQL 17 (migrasi + seluruh suite). MySQL belum diuji (driver tidak tersedia di lingkungan pengembangan);
  jalankan `php artisan migrate` di MySQL lokal Anda terlebih dahulu dan laporkan jika ada error.

## Belum dikerjakan / belum diuji
- Pengujian di MySQL asli dan di browser/HP (tampilan belum dilihat langsung)
- Halaman dashboard belum dipercantik (hanya dipastikan tidak error untuk semua peran)
- Ringkasan kehadiran pribadi untuk anggota (saat ini hanya pengurus berwenang yang melihat kehadiran)
- Email produksi (MAIL_*) belum dikonfigurasi; tanpa itu email aktivasi gagal (pengurus diberi peringatan)
- Fitur lupa/ubah password belum ada
