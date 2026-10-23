# Architecture — IPMI

## 1. Tujuan

Dokumen ini menjelaskan arsitektur konseptual aplikasi IPMI untuk MVP.

Arsitektur harus sederhana, mudah dipelihara, dan aman. Hindari membuat abstraksi atau modul tambahan tanpa kebutuhan yang jelas.

## 2. Teknologi

- Laravel 13
- Blade
- Livewire
- Tailwind CSS
- MySQL

Filament tidak digunakan.

## 3. Area Aplikasi

### 3.1 Situs Publik

Berisi:

- Profil IPMI
- Informasi divisi
- Informasi kegiatan yang memang ditujukan untuk publik
- Formulir pendaftaran anggota

Situs publik tidak menyediakan dashboard untuk mengelola konten publik pada MVP.

Pendaftaran anggota tetap menggunakan backend untuk validasi dan penyimpanan data.

### 3.2 Aplikasi Internal

Hanya dapat diakses oleh akun yang telah terautentikasi dan berwenang.

Modul:

- Dashboard
- Data anggota
- Jabatan dan role sistem
- Tahun ajaran dan kelas
- Riwayat kelas anggota
- Program dan sesi
- Kehadiran
- Materi divisi
- Pengumuman internal
- Audit log sesuai hak akses

## 4. Pola Arsitektur

Gunakan fitur dan pola standar Laravel.

- Route menangani pemetaan URL.
- Controller atau Livewire component menangani alur permintaan sesuai kebutuhan.
- Model Eloquent merepresentasikan data dan relasi.
- Form Request atau validasi Livewire digunakan untuk validasi input.
- Policy, Gate, atau mekanisme otorisasi Laravel digunakan untuk memeriksa hak akses.
- Database transaction digunakan untuk proses yang perlu berhasil atau gagal sebagai satu kesatuan.

Jangan memindahkan seluruh logika bisnis ke Blade view.

Gunakan service class jika alur bisnis cukup kompleks atau digunakan ulang. Jangan membuat service hanya untuk membungkus operasi sederhana tanpa manfaat yang jelas.

## 5. Autentikasi dan Otorisasi

Autentikasi menentukan siapa pengguna yang masuk.

Otorisasi menentukan tindakan yang boleh dilakukan pengguna tersebut.

### 5.1 Relasi Akun dan Anggota

Gunakan relasi 1:1 antara `users` dan `members` (D-027):
- `users` menyimpan data autentikasi (email, password hash, status)
- `members` menyimpan data anggota (NIS, nama, divisi, informasi keanggotaan)
- Relasi via `members.user_id` (foreign key unique)
- Penonaktifan akun menggunakan soft delete atau kolom status

### 5.2 Role Sistem vs Jabatan Organisasi

Role sistem berbeda dari jabatan organisasi (D-003).

Contoh role sistem:
- Ketua Umum memiliki akses organisasi tertinggi.
- Sekretaris mengelola data anggota dan administrasi yang diizinkan.
- Ketua Divisi mengelola data dalam cakupan divisinya.
- Anggota menggunakan fitur yang diperuntukkan bagi anggota.

Semua pemeriksaan penting wajib dilakukan di backend. Menyembunyikan tombol di antarmuka tidak cukup.

## 6. Arsitektur Pengelolaan Kelas

### 6.1 Data Kelas

Pengelolaan kelas menggunakan tiga entitas (D-024, D-027, D-028):

- `academic_years` - tahun ajaran dengan status (upcoming, active, archived)
- `classes` - rombel yang tersedia pada tahun ajaran tertentu
- `member_class_histories` - riwayat kelas anggota per tahun ajaran

`academic_years` menyimpan tahun ajaran dengan status. Maksimal satu tahun ajaran boleh berstatus 'active' (D-028).

`classes` menyimpan rombel yang tersedia pada tahun ajaran tertentu. Setiap kelas memiliki tingkat, jurusan, nomor rombel, dan nama tampilan.

Contoh nama tampilan: XI RPL 1.

Unique constraint: kombinasi (academic_year_id, grade_level, major, class_number) harus unik.

`member_class_histories` menghubungkan anggota dengan kelas yang ditempati pada tahun ajaran tersebut. Satu anggota hanya boleh memiliki satu kelas aktif per tahun ajaran (D-006, D-026).

### 6.2 Penetapan Kelas

Kelas anggota dikelola oleh pengurus yang berwenang (Ketua Umum dan Sekretaris). Kelas tidak diminta pada formulir pendaftaran publik (D-008).

Setiap anggota aktif WAJIB memiliki kelas pada tahun ajaran active (D-026).

Sistem harus mencegah anggota memiliki lebih dari satu penempatan kelas aktif pada tahun ajaran yang sama (validasi di aplikasi).

### 6.3 Kenaikan Kelas

Kenaikan kelas menggunakan alur (D-007):

1. Memilih tahun ajaran asal dan tujuan.
2. Memetakan kelas asal ke kelas tujuan.
3. Memeriksa daftar anggota.
4. Menampilkan pratinjau perubahan.
5. Meminta konfirmasi.
6. Memproses perubahan secara konsisten (gunakan database transaction).
7. Mempertahankan riwayat lama.

Pemetakan harus dapat menangani perubahan susunan rombel. Jangan mengandalkan pencocokan nama kelas semata (D-007).

Anggota tidak boleh pindah rombel di tengah tahun ajaran. Perubahan kelas hanya untuk kenaikan ke tahun ajaran berikutnya atau koreksi data yang salah (D-025).

## 7. Modul Program dan Kehadiran

Pisahkan konsep program dan sesi pelaksanaannya.

### 7.1 Program Rutin
- `programs` - program rutin/berulang
- `program_sessions` - sesi pelaksanaan program
- `program_session_attendances` - presensi per sesi

Satu program dapat memiliki beberapa sesi dengan agenda, jadwal, dan kebutuhan kehadiran berbeda.

### 7.2 Kegiatan Insidental
- `incidental_activities` - kegiatan tidak terikat program rutin
- `activity_days` - hari pelaksanaan (untuk kegiatan multi-hari)
- `activity_attendances` - presensi per kegiatan atau per hari

Kegiatan insidental dapat berlangsung beberapa hari dengan dua mode presensi (D-029):
- **per_event:** satu presensi untuk seluruh kegiatan
- **per_day:** presensi terpisah per hari

Mode ditentukan saat kegiatan dibuat via kolom `attendance_mode`.

### 7.3 Status Kehadiran
Status: hadir, izin, sakit, alpa (D-014).

Kehadiran hanya dicatat untuk peserta yang ditargetkan pada sesi atau kegiatan terkait.

Foreign key eksplisit digunakan (bukan polymorphic relation) untuk menjaga integritas data dan kemudahan pemahaman (D-029).

## 8. Modul Materi

Materi dimiliki oleh divisi tertentu dan dapat berisi:

- Judul
- Deskripsi
- File
- Tautan eksternal

Ketua Divisi hanya dapat mengelola materi divisinya.

Akses file harus diperiksa di backend. File privat tidak boleh dapat diakses hanya dengan menebak URL.

## 9. Modul Pengumuman

Pengumuman hanya untuk pengguna internal.

Target:

- Semua anggota
- Divisi tertentu
- Pengurus

Cakupan pengumuman ditentukan dan diperiksa oleh backend.

## 10. Email

Email digunakan untuk:

- Aktivasi akun setelah pendaftaran disetujui
- Pemulihan password
- Pemberitahuan penting jika dibutuhkan

Token aktivasi harus aman, memiliki masa berlaku, dan tidak boleh digunakan berulang kali di luar aturan yang ditetapkan.

Kegagalan pengiriman email harus ditangani tanpa mengaktifkan akun secara tidak sah.

## 11. File Upload

- Validasi jenis dan ukuran file.
- Gunakan penyimpanan yang sesuai.
- Jangan mempercayai nama file dari pengguna.
- File privat harus dilindungi oleh pemeriksaan otorisasi.
- Jangan menyimpan rahasia atau data sensitif di direktori publik.

## 12. Audit Log

Catat tindakan sensitif, termasuk:

- Keputusan pendaftaran
- Perubahan data anggota
- Perubahan role
- Penetapan dan perubahan kelas
- Kenaikan kelas massal
- Penonaktifan akun
- Perubahan kehadiran

Jangan mencatat password atau token autentikasi.

## 13. Testing

Gunakan pengujian Laravel untuk memeriksa:

- Autentikasi
- Validasi
- Batas akses setiap role
- Persetujuan dan aktivasi anggota
- Riwayat dan kenaikan kelas
- Program dan kehadiran
- Akses file
- Pengumuman
- Integritas data

## 14. Prinsip Implementasi

- Periksa struktur proyek sebelum mengubah kode.
- Implementasikan fitur secara bertahap.
- Jangan membuat migrasi final sebelum rancangan database disetujui.
- Jangan menganggap pengujian berhasil jika belum dijalankan.
- Jangan mengubah fitur di luar ruang lingkup tanpa alasan yang jelas.
- Pertahankan konsistensi dengan seluruh dokumen proyek.
