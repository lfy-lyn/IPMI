# Testing Strategy — IPMI

## 1. Tujuan

Memastikan fitur berjalan sesuai kebutuhan, aturan bisnis, dan batas akses.

Fitur tidak boleh dinyatakan selesai hanya karena halaman dapat dibuka atau kode berhasil dibuat. Pengujian yang relevan harus dijalankan dan hasilnya dilaporkan.

Gunakan pengujian Laravel, termasuk unit test jika sesuai dan feature test untuk alur aplikasi.

## 2. Autentikasi

Uji:

- Pengguna yang belum login tidak dapat membuka halaman internal.
- Login berhasil untuk akun yang valid.
- Login gagal untuk kredensial yang salah.
- Password disimpan dengan hashing.
- Akun yang belum diaktivasi tidak dapat menggunakan akses yang seharusnya belum tersedia.
- Pemulihan password mengikuti mekanisme yang aman.

## 3. Pendaftaran dan Aktivasi

Uji:

- Semua kolom wajib divalidasi.
- Email harus memiliki format yang valid.
- NIS disimpan tanpa menghilangkan angka nol di awal.
- Divisi harus berasal dari daftar yang tersedia.
- Permohonan disimpan dengan status yang benar.
- Pengguna yang tidak berwenang tidak dapat menyetujui permohonan.
- Permohonan yang disetujui dapat melanjutkan aktivasi melalui email.
- Token yang tidak valid atau kedaluwarsa ditolak.
- Token aktivasi tidak dapat digunakan berulang kali di luar aturan.
- Batas tiga permohonan per NIS diterapkan sesuai keputusan.
- Pengiriman ulang akibat error teknis tidak menggandakan permohonan.

## 4. Role dan Otorisasi

Uji:

- Anggota tidak dapat membuka fitur khusus pengurus.
- Ketua Divisi tidak dapat mengubah data divisi lain.
- Sekretaris tidak dapat menetapkan role Ketua Umum jika aturan tersebut melarangnya.
- Anggota tidak dapat mengubah role, NIS, atau divisi sendiri.
- Request langsung ke route yang tidak berwenang ditolak.
- Perubahan role dan izin tidak dapat dilakukan dengan memanipulasi input.
- Data pribadi hanya ditampilkan kepada pengguna yang berwenang.

## 5. Tahun Ajaran dan Kelas

Uji pembuatan daftar kelas:

- Tahun ajaran harus valid.
- Tingkat, jurusan, dan nomor rombel harus divalidasi.
- Nama kelas dibentuk atau divalidasi secara konsisten.
- Kombinasi tahun ajaran, tingkat, jurusan, dan nomor rombel yang sama tidak boleh dibuat dua kali.
- Kelas dari tahun ajaran berbeda dapat memiliki nama yang sama.
- Pengguna tanpa izin tidak dapat mengelola daftar kelas.

Uji penetapan kelas:

- Anggota dapat ditempatkan pada kelas yang tersedia.
- Kelas harus berasal dari tahun ajaran yang valid.
- Anggota tidak boleh memiliki dua penempatan kelas aktif dalam tahun ajaran yang sama.
- Penetapan kelas oleh pengguna yang tidak berwenang ditolak.
- Riwayat kelas lama tetap tersimpan.

Uji kenaikan kelas massal:

- Tahun ajaran asal dan tujuan divalidasi.
- Kelas tujuan harus tersedia.
- Pemetaan kelas asal ke tujuan dapat menangani perubahan susunan rombel.
- Anggota yang perlu pindah rombel dapat dipetakan ke kelas yang berbeda.
- Sistem menampilkan pratinjau sebelum konfirmasi.
- Pengguna tanpa izin tidak dapat menjalankan proses.
- Perubahan dicatat dalam audit log.
- Proses yang gagal tidak boleh meninggalkan data setengah berubah.
- Anggota tidak boleh memiliki penempatan ganda.
- Riwayat tahun ajaran sebelumnya tidak boleh dihapus atau ditimpa.

## 6. Perubahan Kelas dalam Tahun Ajaran

Uji:

- Perubahan kelas dilakukan oleh pengguna yang berwenang.
- Kelas tujuan harus valid.
- Riwayat penempatan sebelumnya dipertahankan sesuai aturan yang disepakati.
- Sistem mencegah dua penempatan aktif pada tahun ajaran yang sama.
- Perubahan penting dicatat dalam audit log.

Detail hasil yang diharapkan mengikuti keputusan final pada `decisions.md`.

## 7. Program dan Sesi

Uji:

- Program dan sesi tersimpan dengan relasi yang benar.
- Sesi tidak dapat dikaitkan dengan program yang tidak valid.
- Ketua Divisi tidak dapat mengelola program divisi lain.
- Pengelolaan kegiatan lintas divisi mengikuti matriks otorisasi.
- Kegiatan yang memiliki riwayat tidak dihapus secara sembarangan.

## 8. Kehadiran

Uji:

- Status hanya menerima Hadir, Izin, Sakit, atau Alpa.
- Peserta harus sesuai cakupan sesi atau kegiatan.
- Pengguna tidak dapat mengubah kehadiran di luar kewenangannya.
- Kehadiran per sesi tidak tercampur dengan sesi lain.
- Kegiatan beberapa hari mengikuti aturan pencatatan yang dipilih.
- Perubahan penting dicatat dalam audit log.
- Perubahan target peserta dan penghapusan kegiatan mengikuti keputusan yang disepakati.

## 9. Materi dan File

Uji:

- Metadata materi divalidasi.
- Jenis dan ukuran file diperiksa.
- File privat tidak dapat diakses tanpa izin.
- Ketua Divisi tidak dapat mengelola materi divisi lain.
- Tautan eksternal divalidasi sesuai kebutuhan.
- Nama file dari pengguna tidak digunakan secara langsung sebagai nama penyimpanan yang tepercaya.

## 10. Pengumuman

Uji:

- Target pengumuman harus valid.
- Anggota hanya melihat pengumuman yang ditujukan kepadanya.
- Ketua Divisi tidak dapat mengelola pengumuman divisi lain.
- Pengumuman internal tidak tampil di situs publik.
- Pengguna tanpa izin tidak dapat mengubah pengumuman melalui request langsung.

## 11. Audit Log

Uji:

- Tindakan sensitif menghasilkan catatan yang sesuai.
- Pelaku, tindakan, dan entitas yang terdampak dicatat dengan benar.
- Password, token, dan rahasia autentikasi tidak dicatat.
- Pengguna yang tidak berwenang tidak dapat membaca atau memanipulasi audit log.

## 12. Integritas Data

Uji:

- Foreign key berfungsi sesuai rancangan.
- Unique constraint mencegah duplikasi yang tidak diperbolehkan.
- Penghapusan data tidak merusak riwayat penting.
- Database transaction menjaga konsistensi proses massal.
- Kegagalan operasi tidak meninggalkan relasi yang tidak valid.

## 13. Situs Publik

Uji:

- Halaman publik dapat diakses tanpa login.
- Formulir pendaftaran bekerja.
- Validasi input ditampilkan dengan jelas.
- Data internal tidak bocor ke halaman publik.
- Tampilan dasar berfungsi pada ukuran layar mobile.

## 14. Kriteria Selesai

Sebuah fitur dapat dinyatakan selesai jika:

- Implementasinya sesuai kebutuhan.
- Validasi backend berjalan.
- Otorisasi diuji.
- Pengujian relevan dijalankan.
- Tidak ada kegagalan yang diketahui dan dibiarkan tanpa penjelasan.
- Dokumentasi diperbarui jika keputusan atau perilaku sistem berubah.

Laporkan pengujian yang gagal atau belum dijalankan secara jujur.

## Pengujian Aturan Penempatan Kelas

- Data anggota tidak dapat dianggap lengkap jika belum memiliki kelas yang valid.
- Anggota tidak dapat memiliki dua penempatan kelas pada tahun ajaran yang sama.
- Perpindahan rombel di tengah tahun ajaran ditolak.
- Kenaikan kelas ke tahun ajaran berikutnya tetap dapat dilakukan.
- Koreksi data kelas hanya dapat dilakukan oleh pengguna berwenang.
- Koreksi kelas harus memiliki alasan dan menghasilkan audit log.
- Anggota tidak dapat mengubah kelas sendiri.
