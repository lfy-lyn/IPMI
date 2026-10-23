# Database Design — IPMI

## 1. Tujuan

Dokumen ini menjelaskan rancangan konseptual database untuk aplikasi IPMI.

Rancangan ini belum dianggap final sampai struktur tabel, relasi, dan keputusan yang masih terbuka disetujui.

Teknologi database: MySQL.

## 2. Prinsip Perancangan

- Gunakan relasi database yang jelas.
- Simpan riwayat yang dibutuhkan organisasi.
- Jangan menghapus riwayat kelas ketika anggota naik kelas.
- Pisahkan role sistem dari jabatan organisasi.
- Terapkan validasi dan otorisasi di backend.
- Jangan menyimpan data yang tidak diperlukan oleh MVP.
- Hindari duplikasi informasi yang dapat menyebabkan inkonsistensi.

## 3. Entitas Utama

### 3.1 users

Menyimpan informasi autentikasi akun.

Kolom:

- id (primary key)
- email (unique)
- password (hashed)
- email_verified_at (nullable)
- status (enum: pending, active, inactive)
- timestamps
- deleted_at (soft delete, nullable)

Ketentuan:

- Password harus disimpan menggunakan hashing Laravel.
- Akun tidak boleh aktif sebelum proses aktivasi selesai.
- Role sistem tidak boleh ditentukan sendiri oleh pengguna.
- Penonaktifan akun menggunakan soft delete atau status 'inactive'.
- Relasi 1:1 dengan tabel members melalui members.user_id (D-027).

### 3.2 members

Menyimpan profil anggota IPMI.

Kolom:

- id (primary key)
- user_id (foreign key ke users, unique)
- nis (varchar, unique)
- full_name
- division_id (foreign key ke divisions)
- phone (nullable)
- status (enum: active, inactive, alumni)
- timestamps

Ketentuan:

- NIS disimpan sebagai string untuk mempertahankan angka nol di awal.
- Setiap anggota memiliki tepat satu divisi (D-003).
- Anggota tidak dapat mengganti sendiri NIS atau divisinya.
- Kelas saat ini ditentukan melalui riwayat kelas, bukan kolom di tabel members.
- Data pribadi (NIS, phone, email) tidak boleh ditampilkan kepada pengguna yang tidak berwenang (D-012).
- Relasi 1:1 dengan users via user_id (D-027).

### 3.3 divisions

Menyimpan daftar divisi IPMI.

Divisi MVP:

- Kaligrafi
- MTQ
- Dakwah
- Hadroh

Kolom konseptual:

- id
- nama divisi
- deskripsi opsional
- status
- timestamps

Nama divisi harus dikelola sebagai data, bukan ditulis berulang kali di banyak bagian aplikasi.

## 4. Pengelolaan Tahun Ajaran dan Kelas

### 4.1 academic_years

Menyimpan tahun ajaran sekolah.

Kolom:

- id (primary key)
- name (contoh: "2026/2027")
- start_date
- end_date
- status (enum: upcoming, active, archived)
- timestamps

Ketentuan:

- Setiap kelas terdaftar pada satu tahun ajaran.
- Maksimal satu tahun ajaran boleh berstatus 'active' (D-028).
- Sistem menggunakan tahun ajaran active untuk menentukan kelas anggota saat ini.
- Validasi single active year diterapkan di level aplikasi.
- Partial unique index pada (status) WHERE status = 'active' jika MySQL mendukung.
- Riwayat kelas tahun sebelumnya (archived) tetap dipertahankan.

### 4.2 classes

Menyimpan daftar rombongan belajar yang tersedia pada suatu tahun ajaran.

Kolom konseptual:

- id
- academic_year_id
- grade_level
- major
- class_number
- name
- status
- timestamps

Contoh data:

| academic_year_id | grade_level | major | class_number | name      |
| ---------------- | ----------- | ----- | ------------ | --------- |
| Tahun 2026/2027  | X           | RPL   | 1            | X RPL 1   |
| Tahun 2026/2027  | X           | RPL   | 2            | X RPL 2   |
| Tahun 2026/2027  | XI          | RPL   | 1            | XI RPL 1  |
| Tahun 2026/2027  | XI          | TKJ   | 1            | XI TKJ 1  |
| Tahun 2026/2027  | XII         | TKJ   | 2            | XII TKJ 2 |

Nilai pada tabel di atas merupakan contoh, bukan daftar kelas resmi sekolah.

Ketentuan:

- `grade_level` menyimpan tingkat seperti X, XI, atau XII.
- `major` menyimpan kode jurusan seperti RPL atau TKJ.
- `class_number` membedakan rombel seperti 1 dan 2.
- `name` adalah nama kelas untuk ditampilkan, misalnya XI RPL 1.
- Nama kelas dapat dibentuk dari tingkat, jurusan, dan nomor rombel.
- Setiap kelas hanya berlaku untuk satu tahun ajaran.
- Kombinasi tahun ajaran, tingkat, jurusan, dan nomor rombel harus unik.
- Daftar kelas dikelola oleh pengurus yang berwenang.
- Sistem tidak boleh menganggap bahwa rombel pada tahun berikutnya pasti memiliki susunan yang sama.

Kelas yang sudah memiliki riwayat anggota tidak boleh dihapus sembarangan. Gunakan status nonaktif jika kelas tidak lagi digunakan.

### 4.3 member_class_histories

Mencatat kelas yang ditempati setiap anggota pada suatu tahun ajaran.

Kolom konseptual:

- id
- member_id
- class_id
- timestamps

Relasi:

- Satu anggota dapat memiliki banyak riwayat kelas.
- Satu kelas dapat memiliki banyak anggota.
- Setiap riwayat menghubungkan satu anggota dengan satu kelas.

Karena kelas sudah terhubung dengan tahun ajaran melalui `classes.academic_year_id`, informasi tahun ajaran tidak perlu diduplikasi di tabel riwayat kecuali terdapat kebutuhan khusus yang sudah disetujui.

Ketentuan:

- Seorang anggota hanya boleh memiliki satu kelas aktif untuk satu tahun ajaran.
- Riwayat kelas pada tahun ajaran sebelumnya harus dipertahankan.
- Perubahan kelas dilakukan dengan membuat atau memperbarui penempatan kelas sesuai aturan riwayat yang ditetapkan.
- Perubahan kelas harus diotorisasi dan dicatat dalam audit log.
- Sistem harus mencegah dua riwayat aktif untuk anggota yang sama pada tahun ajaran yang sama.

Ketentuan tambahan:

- Setiap anggota yang berstatus aktif wajib memiliki penempatan kelas pada tahun ajaran yang sedang berlaku.
- Satu anggota hanya boleh memiliki satu penempatan kelas dalam satu tahun ajaran.
- Perpindahan rombel di tengah tahun ajaran tidak diperbolehkan.
- Koreksi penempatan kelas hanya boleh dilakukan oleh pengurus berwenang, harus memiliki alasan yang jelas, dan dicatat dalam audit log.
- Validasi harus dilakukan di backend dan, jika memungkinkan, diperkuat dengan constraint database.

## 5. Proses Pengelolaan Kelas

### 5.1 Membuat daftar kelas

1. Sekretaris memilih atau membuat tahun ajaran.
2. Sekretaris memasukkan daftar kelas yang tersedia.
3. Sistem memvalidasi tingkat, jurusan, dan nomor rombel.
4. Sistem membentuk atau memvalidasi nama kelas.
5. Sistem mencegah kelas duplikat dalam tahun ajaran yang sama.

Sistem boleh menyediakan fitur menyalin daftar kelas dari tahun ajaran sebelumnya sebagai titik awal. Daftar hasil salinan harus diperiksa dan dikonfirmasi sebelum digunakan.

### 5.2 Menetapkan kelas anggota

1. Pengurus membuka data anggota.
2. Pengurus memilih kelas yang tersedia pada tahun ajaran terkait.
3. Sistem memvalidasi agar tidak ada penempatan kelas ganda.
4. Sistem menyimpan penempatan kelas.
5. Sistem mencatat perubahan sesuai kebijakan audit log.

Kelas dapat ditetapkan setelah pendaftaran anggota disetujui. Formulir pendaftaran publik tidak wajib meminta data kelas.

### 5.3 Kenaikan kelas massal

1. Sekretaris memilih tahun ajaran asal dan tahun ajaran tujuan.
2. Sistem menampilkan anggota beserta kelas mereka saat ini.
3. Sekretaris memetakan kelas asal ke kelas tujuan.
4. Sekretaris memeriksa anggota yang perlu dipindahkan ke rombel berbeda.
5. Sistem menampilkan pratinjau perubahan.
6. Sekretaris mengonfirmasi.
7. Sistem memproses perubahan secara konsisten menggunakan database transaction.
8. Sistem mempertahankan riwayat tahun ajaran sebelumnya.

Pemetaan kelas tidak boleh dilakukan secara otomatis hanya berdasarkan nama kelas. Contohnya, anggota XI RPL 1 tidak selalu harus ditempatkan di XII RPL 1.

Jika proses gagal, sistem harus menghindari kondisi ketika hanya sebagian anggota yang berubah tanpa penjelasan yang jelas.

## 6. Jabatan Organisasi dan Role Sistem

### 6.1 positions

Menyimpan daftar jabatan organisasi, misalnya:

- Ketua Umum
- Wakil Ketua Umum
- Sekretaris
- Bendahara
- Ketua Divisi
- Anggota Divisi

### 6.2 member_positions

Menghubungkan anggota dengan jabatan organisasi.

Ketentuan:

- Satu anggota dapat memiliki beberapa jabatan.
- Jabatan organisasi berbeda dari role sistem.
- Riwayat jabatan belum menjadi kebutuhan MVP; simpan jabatan yang berlaku saat ini.

Role sistem digunakan untuk otorisasi fitur aplikasi dan harus dikelola secara terpisah.

## 7. Pendaftaran Anggota

### 7.1 membership_applications

Menyimpan permohonan menjadi anggota.

Kolom konseptual:

- id
- nama lengkap
- NIS
- email
- division_id
- alasan bergabung
- status permohonan
- reviewer_id
- reviewed_at
- catatan keputusan jika diperlukan
- timestamps

Ketentuan:

- Status permohonan harus mengikuti alur yang jelas.
- Pendaftaran harus divalidasi di backend.
- Maksimal tiga permohonan tercatat per NIS sesuai aturan MVP yang disepakati.
- Pengiriman ulang akibat error teknis tidak boleh dihitung sebagai permohonan baru.
- Pendaftar tidak dapat menetapkan role atau jabatan sendiri.
- Aturan penghitungan permohonan yang dibatalkan masih perlu diputuskan.

### 7.2 Aktivasi akun

Setelah permohonan disetujui:

- Sistem mengirim tautan aktivasi melalui email.
- Tautan harus memiliki token aman, masa berlaku, dan mekanisme sekali pakai.
- Pendaftar membuat password melalui tautan tersebut.
- Akun diaktifkan setelah proses aktivasi berhasil.

Detail masa berlaku token dan mekanisme pengiriman ulang masih perlu diputuskan.

## 8. Program, Sesi, dan Kehadiran

### 8.1 programs

Menyimpan program rutin atau kegiatan yang memiliki beberapa sesi.

Kolom konseptual:

- id
- nama
- deskripsi
- jenis program
- cakupan peserta
- division_id jika khusus divisi
- status
- created_by
- timestamps

### 8.2 program_sessions

Menyimpan sesi pelaksanaan program.

Kolom konseptual:

- id
- program_id
- tanggal dan waktu
- agenda
- status sesi
- timestamps

### 8.3 incidental_activities

Menyimpan kegiatan insidental yang tidak terikat program rutin.

Kolom:

- id
- name
- description
- start_date
- end_date
- division_id (nullable, jika khusus divisi)
- attendance_mode (enum: 'per_event', 'per_day')
- status
- created_by
- timestamps

### 8.4 activity_days

Menyimpan hari pelaksanaan kegiatan insidental multi-hari.

Kolom:

- id
- incidental_activity_id (FK ke incidental_activities)
- date
- description (nullable)
- timestamps

Ketentuan:
- Hanya digunakan untuk kegiatan dengan attendance_mode = 'per_day'
- Satu kegiatan dapat memiliki banyak activity_days

### 8.5 program_session_attendances

Mencatat kehadiran anggota pada sesi program rutin.

Kolom:

- id
- member_id (FK ke members)
- program_session_id (FK ke program_sessions)
- status (enum: 'hadir', 'izin', 'sakit', 'alpa')
- timestamps

Ketentuan:
- Unique constraint: (member_id, program_session_id)
- Satu anggota hanya memiliki satu catatan kehadiran per sesi

### 8.6 activity_attendances

Mencatat kehadiran anggota pada kegiatan insidental.

Kolom:

- id
- member_id (FK ke members)
- incidental_activity_id (FK ke incidental_activities)
- activity_day_id (FK ke activity_days, nullable)
- status (enum: 'hadir', 'izin', 'sakit', 'alpa')
- timestamps

Ketentuan:
- Unique constraint: (member_id, incidental_activity_id, activity_day_id)
- Jika activity_day_id NULL: presensi untuk seluruh kegiatan (mode per_event)
- Jika activity_day_id NOT NULL: presensi untuk hari tertentu (mode per_day)
- Validasi aplikasi memastikan konsistensi dengan attendance_mode dari kegiatan
- Foreign key eksplisit, tidak menggunakan polymorphic relation (D-029)

Status kehadiran yang valid:
- hadir
- izin
- sakit
- alpa

Ketentuan Umum Presensi:
- Peserta yang diharapkan hadir dapat berbeda untuk setiap sesi/kegiatan.
- Pencatatan kehadiran bersifat opsional per sesi.
- Hak akses pencatatan dan perubahan kehadiran mengikuti authorization matrix.
- Perubahan kehadiran harus divalidasi dan dicatat sesuai kebijakan audit.

## 9. Materi

### 9.1 materials

Menyimpan materi pembelajaran atau kegiatan divisi.

Kolom konseptual:

- id
- division_id
- judul
- deskripsi
- file_path jika ada
- external_url jika ada
- created_by
- timestamps

Ketentuan:

- Materi dapat berupa file atau tautan eksternal.
- Ketua Divisi hanya mengelola materi divisinya.
- Anggota hanya dapat melihat materi sesuai hak aksesnya.
- File harus divalidasi, dibatasi jenis dan ukurannya, serta disimpan secara aman.

## 10. Pengumuman

### 10.1 announcements

Menyimpan pengumuman internal IPMI.

Kolom konseptual:

- id
- judul
- isi
- target_type
- division_id jika ditujukan untuk divisi tertentu
- created_by
- published_at
- status
- timestamps

Target pengumuman:

- Semua anggota
- Divisi tertentu
- Pengurus

Pengumuman tetap bersifat internal dan tidak otomatis tampil pada situs publik.

## 11. Audit Log

### 11.1 audit_logs

Mencatat tindakan penting, antara lain:

- Persetujuan atau penolakan pendaftaran
- Perubahan data anggota
- Perubahan role atau jabatan
- Penempatan atau perubahan kelas anggota
- Kenaikan kelas massal
- Penonaktifan akun
- Perubahan kehadiran

Kolom konseptual:

- id
- actor_id
- action
- entity_type
- entity_id
- metadata yang aman
- timestamps

Jangan mencatat password, token aktivasi, atau rahasia autentikasi ke dalam log.

Retensi dan hak akses audit log masih perlu ditentukan.

## 12. Aturan Integritas Data

- Gunakan foreign key untuk relasi yang sesuai.
- Terapkan unique constraint pada identitas yang harus unik.
- Cegah duplikasi penempatan kelas anggota dalam satu tahun ajaran.
- Gunakan transaksi database untuk proses massal yang saling berkaitan.
- Jangan menghapus riwayat kelas, kehadiran, atau data penting hanya untuk menyederhanakan tampilan.
- Terapkan otorisasi backend pada setiap operasi sensitif.
- Jangan mengandalkan penyembunyian tombol sebagai satu-satunya perlindungan.

## 13. Hal yang Sudah Diputuskan untuk Migrasi

Keputusan berikut sudah CONFIRMED dan siap diimplementasikan:

1. ✅ Struktur autentikasi dan relasi `users` dengan `members` (D-027)
   - Relasi 1:1 via `members.user_id`
   - users: autentikasi, members: data anggota
   - Penonaktifan menggunakan soft delete atau status

2. ✅ Status tahun ajaran dan cara menetapkan tahun ajaran aktif (D-028)
   - Status: upcoming, active, archived
   - Maksimal satu tahun ajaran berstatus active
   - Validasi di aplikasi dan database

3. ✅ Kewajiban kelas untuk anggota (D-026)
   - Setiap anggota aktif WAJIB memiliki kelas pada tahun ajaran berlaku
   - Ditetapkan oleh pengurus, tidak diminta di formulir pendaftaran

4. ✅ Penanganan perpindahan rombel (D-025)
   - Tidak diperbolehkan di tengah tahun ajaran
   - Hanya untuk kenaikan kelas atau koreksi data salah
   - Koreksi dicatat dalam audit log

5. ✅ Struktur kehadiran (D-029)
   - program_session_attendances untuk sesi program rutin
   - activity_attendances untuk kegiatan insidental
   - Foreign key eksplisit, bukan polymorphic

## 14. Hal yang Masih Perlu Diputuskan (Tidak Menghambat Migrasi Dasar)

Keputusan berikut tidak menghambat pembuatan migrasi tabel inti:

1. Aturan permohonan yang dibatalkan atau dihapus (D-019)
   - Dapat diputuskan saat implementasi modul pendaftaran
   - Tidak mempengaruhi struktur tabel membership_applications

2. Masa berlaku token aktivasi dan mekanisme pengiriman ulang (D-020)
   - Dapat diputuskan saat implementasi modul aktivasi
   - Struktur tabel activation_tokens sudah cukup jelas

3. Retensi serta hak akses audit log (D-023)
   - Dapat diputuskan saat implementasi modul audit log
   - Struktur tabel audit_logs sudah cukup jelas

Migrasi dapat dibuat dengan struktur yang sudah disepakati. Detail aturan bisnis di atas akan ditangani di level aplikasi.
