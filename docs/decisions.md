# Decision Log — IPMI

Dokumen ini mencatat keputusan desain, usulan, dan hal yang masih perlu dipastikan.

Status:

- **CONFIRMED**: sudah disepakati.
- **PROPOSED**: usulan yang digunakan sebagai dasar sementara.
- **OPEN**: belum diputuskan.

## D-001 — Tech Stack

Status: CONFIRMED

- Laravel 13
- Blade
- Livewire
- Tailwind CSS
- MySQL

Filament tidak digunakan.

## D-002 — Ruang Lingkup MVP

Status: CONFIRMED

MVP mencakup situs publik, pendaftaran anggota, autentikasi, pengelolaan anggota, riwayat kelas, program dan sesi, kehadiran, materi, serta pengumuman internal.

Modul keuangan, API WhatsApp, pendaftaran peserta acara, dan absensi QR tidak termasuk MVP.

## D-003 — Divisi dan Jabatan

Status: CONFIRMED

Divisi:

- Kaligrafi
- MTQ
- Dakwah
- Hadroh

Intelektual merupakan jabatan atau fungsi khusus yang ditunda dari MVP, bukan divisi.

Role sistem dan jabatan organisasi harus dipisahkan.

## D-004 — Data Kelas

Status: CONFIRMED

Kelas merupakan rombongan belajar, bukan hanya tingkat pendidikan.

Contoh nama kelas:

- X RPL 1
- X RPL 2
- XI RPL 1
- XI TKJ 2
- XII RPL 1

Setiap kelas memiliki:

- Tahun ajaran
- Tingkat
- Jurusan
- Nomor rombel
- Nama tampilan

Kombinasi tahun ajaran, tingkat, jurusan, dan nomor rombel harus unik.

## D-005 — Pengelolaan Daftar Kelas

Status: CONFIRMED

Sekretaris mengelola daftar kelas untuk setiap tahun ajaran.

Sistem boleh menyediakan fitur menyalin daftar kelas dari tahun sebelumnya sebagai titik awal, tetapi daftar hasil salinan harus diperiksa dan dikonfirmasi sebelum digunakan.

Sistem tidak boleh mengasumsikan bahwa susunan rombel tahun berikutnya selalu sama.

## D-006 — Riwayat Kelas Anggota

Status: CONFIRMED

Riwayat kelas disimpan per tahun ajaran.

Contoh:

- 2025/2026: X RPL 1
- 2026/2027: XI RPL 1
- 2027/2028: XII RPL 1

Riwayat lama dipertahankan dan tidak ditimpa ketika anggota naik kelas.

Satu anggota hanya boleh memiliki satu kelas aktif dalam satu tahun ajaran.

## D-007 — Kenaikan Kelas Massal

Status: CONFIRMED

Sekretaris dapat memetakan anggota dari kelas lama ke kelas tujuan.

Alur:

1. Pilih tahun ajaran asal dan tujuan.
2. Periksa daftar anggota.
3. Tentukan kelas tujuan.
4. Tinjau pratinjau perubahan.
5. Konfirmasi perubahan.
6. Simpan perubahan secara konsisten.

Anggota dapat dipindahkan ke rombel yang berbeda jika susunan kelas berubah.

## D-008 — Kelas pada Formulir Pendaftaran

Status: CONFIRMED

Kelas tidak wajib diminta dalam formulir pendaftaran publik.

Kelas dapat ditetapkan oleh pengurus saat verifikasi atau pengelolaan data anggota.

Formulir pendaftaran berisi nama lengkap, NIS, email aktif, pilihan divisi, dan alasan bergabung.

## D-009 — Aktivasi Akun

Status: PROPOSED

Setelah pendaftaran disetujui, pendaftar menerima tautan aktivasi melalui email dan membuat password sendiri.

Token harus aman, memiliki masa berlaku, dan hanya dapat digunakan sesuai aturan aktivasi.

## D-010 — Batas Permohonan per NIS

Status: CONFIRMED

Maksimal tiga permohonan tercatat per NIS.

Pengiriman ulang akibat error teknis tidak boleh dihitung sebagai permohonan baru.

## D-011 — Role dan Wewenang

Status: CONFIRMED

- Ketua Umum memiliki akses organisasi tertinggi.
- Sekretaris mengelola data anggota dan administrasi yang diizinkan.
- Ketua Divisi mengelola fitur dalam cakupan divisinya.
- Anggota tidak dapat menetapkan role atau jabatan sendiri.
- Tidak ada superadmin terpisah pada MVP.

Detail yang belum disepakati harus tetap ditandai OPEN.

## D-012 — Data Anggota yang Dapat Dilihat

Status: CONFIRMED

Anggota dapat melihat nama, kelas saat ini, divisi, dan jabatan anggota lain.

NIS, email, nomor telepon, dan catatan kehadiran tidak boleh terbuka untuk semua anggota.

## D-013 — Penonaktifan Akun

Status: CONFIRMED

Anggota yang tidak aktif atau lulus dinonaktifkan. Data historis penting tidak langsung dihapus.

Ketua Divisi dapat menonaktifkan anggota dalam divisinya sesuai aturan. Ketua Umum dan Sekretaris memiliki cakupan pengelolaan yang lebih luas.

## D-014 — Program dan Sesi

Status: CONFIRMED

Program rutin dapat memiliki banyak sesi. Setiap sesi dapat memiliki agenda, waktu, dan kebutuhan kehadiran yang berbeda.

Kegiatan insidental dapat berlangsung satu atau beberapa hari.

Target peserta dan pencatatan kehadiran harus sesuai kegiatan, bukan selalu seluruh anggota IPMI.

## D-015 — Materi Divisi

Status: CONFIRMED

Ketua Divisi mengelola materi divisinya sendiri.

Ketua Umum dan Sekretaris tidak otomatis mendapatkan hak mengelola seluruh materi divisi.

## D-016 — Pengumuman

Status: CONFIRMED

Pengumuman bersifat internal dan dapat ditujukan kepada semua anggota, divisi tertentu, atau pengurus.

Berbagi ke WhatsApp dilakukan secara manual. Integrasi WhatsApp API tidak termasuk MVP.

## D-017 — Audit Log

Status: CONFIRMED

Tindakan sensitif seperti perubahan kelas, data anggota, role, keputusan pendaftaran, penonaktifan akun, dan perubahan kehadiran perlu dicatat.

Password, token, dan rahasia autentikasi tidak boleh dicatat.

## D-018 — Perpindahan Rombel

Status: CLOSED (dijawab oleh D-025 dan D-026)

Keputusan ini telah dijawab secara lengkap oleh D-025 yang menetapkan:
- Anggota tidak boleh pindah rombel di tengah tahun ajaran.
- Perubahan hanya untuk kenaikan kelas atau koreksi data salah.
- Koreksi dicatat dalam audit log.

Detail pencatatan audit untuk koreksi kelas akan ditangani dalam implementasi modul audit log sesuai D-017.

## D-019 — Permohonan Dibatalkan atau Dihapus

Status: OPEN

Belum ditentukan apakah permohonan yang dibatalkan atau dihapus tetap dihitung dalam batas tiga permohonan per NIS.

## D-020 — Token Aktivasi

Status: OPEN

Belum ditentukan masa berlaku token dan prosedur pengiriman ulang tautan aktivasi.

## D-021 — Kehadiran

Status: OPEN

Belum ditentukan sepenuhnya:

- Perubahan target peserta setelah sesi selesai.
- Batas waktu perubahan kehadiran.
- Penanganan penghapusan program yang sudah memiliki catatan kehadiran.

## D-022 — Bendahara

Status: OPEN

Belum ada modul keuangan dalam MVP. Hak akses Bendahara di luar fitur yang tersedia perlu dipastikan.

## D-023 — Audit Log

Status: OPEN

Perlu menentukan siapa yang boleh membaca audit log dan berapa lama data disimpan.

## D-024 — Detail Implementasi Kelas

Status: CONFIRMED

Gunakan entitas `academic_years`, `classes`, dan `member_class_histories`.

Tabel `classes` terhubung ke satu tahun ajaran. Riwayat anggota mengacu pada kelas yang ditempati.

Unique constraint: kombinasi (academic_year_id, grade_level, major, class_number) harus unik.

## D-025 — Perpindahan Rombel

Status: CONFIRMED

Anggota tidak diperbolehkan berpindah rombel di tengah tahun ajaran.

Perubahan penempatan kelas hanya dilakukan untuk proses kenaikan kelas ke tahun ajaran berikutnya atau koreksi data yang memang salah.

Koreksi data harus melalui pengurus yang berwenang dan dicatat dalam audit log. Koreksi tidak boleh digunakan untuk menyamarkan perpindahan rombel.

## D-026 — Kelas Wajib untuk Anggota

Status: CONFIRMED

Setiap data anggota wajib memiliki penempatan kelas yang valid pada tahun ajaran terkait.

Data anggota tidak boleh dianggap lengkap jika belum memiliki kelas.

Kelas anggota ditetapkan oleh pengurus yang berwenang. Formulir pendaftaran publik tidak harus menyediakan pilihan kelas.

Sebelum data anggota dianggap lengkap, pengurus harus memastikan kelasnya sudah ditetapkan.

## D-027 — Relasi Akun dan Anggota

Status: CONFIRMED

Tanggal: 2026-10-23

Gunakan relasi satu-ke-satu antara `users` dan `members`.

Ketentuan:
- `users` menyimpan data autentikasi: email, password hash, status akun.
- `members` menyimpan data anggota: NIS, nama, divisi, informasi keanggotaan.
- Setiap akun anggota berhubungan dengan satu data anggota melalui `user_id` di tabel members.
- Penonaktifan akun menggunakan soft delete atau kolom status, bukan penghapusan data.
- Riwayat anggota tetap tersimpan setelah akun dinonaktifkan.

Relasi:
- `users` 1:1 `members` via `members.user_id` (foreign key)

## D-028 — Status Tahun Ajaran

Status: CONFIRMED

Tanggal: 2026-10-23

Tabel `academic_years` menggunakan kolom status dengan nilai:
- `upcoming` - tahun ajaran yang akan datang
- `active` - tahun ajaran yang sedang berjalan
- `archived` - tahun ajaran yang sudah selesai

Ketentuan:
- Maksimal satu tahun ajaran boleh berstatus `active` pada satu waktu.
- Sistem menggunakan tahun ajaran `active` untuk menentukan kelas anggota saat ini.
- Validasi single active year diterapkan di level aplikasi dan database (unique partial index jika database mendukung).
- Kenaikan kelas harus melalui pratinjau dan konfirmasi sebelum diterapkan.
- Riwayat kelas tahun sebelumnya tetap dipertahankan.

Struktur tabel `academic_years`:
- id
- name (contoh: "2025/2026")
- start_date
- end_date
- status (enum: upcoming, active, archived)
- timestamps

## D-029 — Struktur Presensi

Status: CONFIRMED

Tanggal: 2026-10-23

Gunakan desain relasi presensi eksplisit dengan foreign key yang jelas, bukan polymorphic relation.

Struktur Kegiatan:
- `programs` - program rutin/berulang
- `program_sessions` - sesi pelaksanaan program
- `incidental_activities` - kegiatan insidental (dengan kolom `attendance_mode`: 'per_event' atau 'per_day')
- `activity_days` - hari dari kegiatan multi-hari

Struktur Presensi (2 tabel terpisah):

1. `program_session_attendances` - presensi sesi program rutin
   - id
   - member_id (FK ke members)
   - program_session_id (FK ke program_sessions)
   - status (enum: hadir, izin, sakit, alpa)
   - timestamps
   - Unique constraint: (member_id, program_session_id)

2. `activity_attendances` - presensi kegiatan insidental
   - id
   - member_id (FK ke members)
   - incidental_activity_id (FK ke incidental_activities)
   - activity_day_id (FK ke activity_days, nullable)
   - status (enum: hadir, izin, sakit, alpa)
   - timestamps
   - Unique constraint: (member_id, incidental_activity_id, activity_day_id)

Ketentuan:
- Jika `activity_day_id` NULL: presensi untuk seluruh kegiatan (mode per_event)
- Jika `activity_day_id` NOT NULL: presensi untuk hari tertentu (mode per_day)
- Validasi aplikasi memastikan konsistensi dengan `attendance_mode` dari kegiatan
- Status presensi: hadir, izin, sakit, alpa (sesuai D-014)

Alasan desain:
- Foreign key eksplisit di database untuk integritas data
- Struktur mudah dipahami pengembang pemula
- Tidak menggunakan polymorphic relation sesuai keputusan
- Mendukung presensi per-event dan per-day untuk kegiatan multi-hari
