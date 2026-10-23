# Implementation Plan — IPMI

## Prinsip Pelaksanaan

- Implementasikan secara bertahap.
- Periksa proyek dan dokumentasi sebelum mengubah kode.
- Jangan membangun semua modul sekaligus.
- Jalankan pengujian setelah perubahan yang relevan.
- Jangan membuat migrasi final sebelum desain database dan keputusan penting disetujui.
- Laporkan pengujian yang belum dijalankan secara jujur.

## Tahap 1 — Pemeriksaan Proyek

- Periksa struktur folder dan file yang sudah tersedia.
- Periksa versi PHP, Composer, Node.js, dan kebutuhan Laravel.
- Periksa konfigurasi environment tanpa menampilkan rahasia.
- Periksa status Git dan perubahan pengguna.
- Identifikasi bagian yang sudah ada sebelum membuat atau mengubah file.

Hasil: laporan kondisi proyek dan rencana kerja yang sesuai kondisi nyata.

## Tahap 2 — Finalisasi Desain

- Periksa `architecture.md`.
- Periksa `database_design.md`.
- Periksa `authorization_matrix.md`.
- Periksa `decisions.md`.
- Pastikan rancangan tahun ajaran, kelas, dan riwayat kelas konsisten.
- Tandai keputusan yang masih terbuka.

Hasil: desain yang cukup jelas untuk diimplementasikan.

## Tahap 3 — Fondasi Aplikasi

- Siapkan Laravel dan MySQL.
- Siapkan Blade, Livewire, dan Tailwind CSS.
- Siapkan layout publik dan layout internal.
- Pastikan konfigurasi dasar berjalan.
- Siapkan mekanisme pengujian.

## Tahap 4 — Database dan Autentikasi

- Implementasikan tabel inti yang sudah disetujui.
- Implementasikan autentikasi.
- Implementasikan role sistem dan otorisasi backend.
- Implementasikan validasi serta perlindungan route.
- Uji akses tanpa login dan akses berdasarkan role.

## Tahap 5 — Pendaftaran dan Aktivasi Anggota

- Buat formulir pendaftaran publik.
- Validasi nama, NIS, email, divisi, dan alasan bergabung.
- Simpan permohonan dengan status yang benar.
- Buat alur persetujuan dan penolakan.
- Implementasikan aktivasi akun melalui email.
- Implementasikan pemulihan password.
- Uji batas permohonan per NIS dan penanganan error.

## Tahap 6 — Pengelolaan Anggota

- Implementasikan profil anggota.
- Implementasikan pengelolaan divisi dan jabatan.
- Implementasikan perubahan data sesuai hak akses.
- Implementasikan penonaktifan akun.
- Pastikan data pribadi tidak terbuka kepada pengguna yang tidak berwenang.

## Tahap 7 — Tahun Ajaran dan Kelas

Urutan pengerjaan:

1. Implementasikan `academic_years`.
2. Implementasikan `classes`.
3. Implementasikan `member_class_histories`.
4. Buat pengelolaan daftar tahun ajaran.
5. Buat pengelolaan daftar kelas.
6. Validasi kombinasi tahun ajaran, tingkat, jurusan, dan nomor rombel.
7. Buat penetapan kelas untuk anggota.
8. Pastikan anggota hanya memiliki satu kelas aktif per tahun ajaran.
9. Buat pratinjau perubahan kelas.
10. Implementasikan kenaikan kelas massal.
11. Implementasikan pemetaan rombel asal ke rombel tujuan.
12. Pastikan riwayat lama tidak terhapus.
13. Catat perubahan penting dalam audit log.

Pengelolaan kelas harus diuji sebelum digunakan oleh modul lain yang bergantung pada data kelas.

## Tahap 8 — Program dan Sesi

- Implementasikan program rutin.
- Implementasikan sesi program.
- Tentukan cakupan peserta.
- Implementasikan kegiatan insidental sesuai desain yang disetujui.
- Validasi hak akses pengelolaan program dan sesi.
- Lindungi data kegiatan yang telah memiliki riwayat.

## Tahap 9 — Kehadiran

- Implementasikan status Hadir, Izin, Sakit, dan Alpa.
- Terapkan cakupan peserta sesuai sesi.
- Terapkan hak akses pencatatan dan perubahan kehadiran.
- Tangani kegiatan yang berlangsung beberapa hari.
- Catat perubahan penting.
- Uji integritas data kehadiran.

## Tahap 10 — Materi Divisi

- Implementasikan metadata materi.
- Implementasikan unggah file dan tautan eksternal.
- Terapkan validasi file.
- Terapkan akses berdasarkan divisi.
- Uji akses file oleh pengguna yang tidak berwenang.

## Tahap 11 — Pengumuman Internal

- Implementasikan pengumuman.
- Implementasikan target semua anggota, divisi tertentu, dan pengurus.
- Terapkan otorisasi pembuatan, perubahan, dan penghapusan.
- Pastikan pengumuman internal tidak tampil di situs publik.

## Tahap 12 — Situs Publik

- Implementasikan halaman profil IPMI.
- Implementasikan informasi divisi.
- Implementasikan informasi kegiatan yang ditujukan untuk publik.
- Integrasikan formulir pendaftaran.
- Periksa tampilan mobile dan aksesibilitas dasar.

## Tahap 13 — Audit dan Pengujian Menyeluruh

- Jalankan seluruh test suite.
- Uji batas akses setiap role.
- Uji pendaftaran hingga aktivasi.
- Uji riwayat dan kenaikan kelas.
- Uji program dan kehadiran.
- Uji akses file dan pengumuman.
- Periksa validasi, keamanan, dan integritas data.
- Perbaiki kegagalan sebelum menyatakan fitur selesai.

## Tahap 14 — Persiapan Deployment

Dilakukan setelah MVP stabil.

- Tentukan lingkungan hosting.
- Konfigurasi HTTPS.
- Amankan environment dan kredensial.
- Siapkan backup database.
- Uji pemulihan backup.
- Jalankan pemeriksaan keamanan dan konfigurasi produksi.

Jangan mengklaim aplikasi siap produksi sebelum pemeriksaan yang diperlukan benar-benar selesai.
