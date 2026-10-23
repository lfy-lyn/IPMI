# IPMI — Project Specification

## Version 1.1

- **Project:** Sistem Informasi Organisasi IPMI
- **Dokumen:** Project Specification
- **Versi:** 1.1
- **Status:** Draft — perlu finalisasi beberapa keputusan teknis
- **Stack:** Laravel 13, Blade, Livewire, Tailwind CSS, MySQL
- **Pendekatan:** MVP bertahap, keamanan sebagai prioritas, mudah dikembangkan

---

## 1. Ringkasan Proyek

IPMI membutuhkan website profil organisasi dan aplikasi internal untuk membantu pengelolaan anggota serta kegiatan organisasi.

Sistem dibagi menjadi dua bagian:

1. **Website publik:** menampilkan informasi profil organisasi dan menyediakan formulir pendaftaran anggota.
2. **Aplikasi internal:** digunakan oleh anggota dan pengurus untuk mengelola data serta menjalankan kegiatan sesuai kewenangan masing-masing.

Pengembangan dilakukan bertahap. MVP berfokus pada fitur inti yang benar-benar dibutuhkan, tanpa menambahkan fitur yang belum disepakati.

### 1.1 Tujuan

- Menyediakan informasi resmi mengenai organisasi.
- Mempermudah proses pendaftaran dan aktivasi anggota.
- Menyimpan data anggota beserta riwayat kelasnya.
- Mengelola jabatan dan hak akses pengurus.
- Mendukung pengelolaan program, sesi kegiatan, dan absensi.
- Menyediakan materi pembelajaran untuk setiap divisi.
- Menyebarkan pengumuman internal.
- Menjaga keamanan dan integritas data organisasi.

### 1.2 Prinsip Pengembangan

- Bangun fitur secara bertahap.
- Jangan menganggap kode atau struktur proyek lama sudah tersedia.
- Jangan menambahkan fitur di luar kebutuhan MVP tanpa persetujuan.
- Gunakan validasi dan otorisasi pada backend.
- Pisahkan jabatan organisasi dari role dan permission sistem.
- Pertahankan riwayat kegiatan meskipun akun anggota dinonaktifkan.
- Hindari duplikasi data dan logika bisnis.
- Setiap fitur harus dapat diuji secara terpisah.

---

## 2. Teknologi

| Komponen         | Teknologi                                                  |
| ---------------- | ---------------------------------------------------------- |
| Backend          | Laravel 13                                                 |
| Bahasa backend   | PHP                                                        |
| Template         | Blade                                                      |
| Interaktivitas   | Livewire                                                   |
| CSS              | Tailwind CSS                                               |
| Database         | MySQL                                                      |
| Autentikasi      | Laravel authentication yang dikonfigurasi sesuai kebutuhan |
| Pengiriman email | SMTP atau layanan email yang sesuai                        |
| Version control  | Git                                                        |

### 2.1 Batasan Teknologi

- Tidak menggunakan Filament.
- Tidak menggunakan CMS untuk mengelola halaman profil publik.
- Tidak menggunakan WhatsApp API.
- Tidak menggunakan QR Code untuk absensi pada MVP.
- Jangan menambahkan framework JavaScript terpisah jika Livewire sudah mencukupi kebutuhan.
- Versi PHP, Composer, Node.js, NPM, dan konfigurasi Laravel harus diverifikasi sebelum implementasi.

---

## 3. Arsitektur Sistem

Sistem terdiri dari dua area utama.

### 3.1 Website Publik

Halaman yang dapat diakses tanpa login:

- Beranda.
- Profil organisasi.
- Struktur organisasi yang disetujui untuk publikasi.
- Informasi divisi.
- Informasi program atau kegiatan yang ditujukan kepada publik.
- Formulir pendaftaran anggota.
- Halaman login.

Konten profil publik dikelola melalui kode untuk MVP. Tidak diperlukan dashboard CRUD khusus untuk mengubah konten halaman profil.

Formulir pendaftaran anggota tetap memerlukan backend, validasi, dan penyimpanan database.

### 3.2 Aplikasi Internal

Aplikasi internal hanya dapat digunakan setelah pengguna berhasil login.

Fitur utama:

- Dashboard.
- Direktori anggota.
- Pengelolaan pendaftaran anggota.
- Pengelolaan akun.
- Pengelolaan jabatan organisasi.
- Riwayat kelas anggota.
- Program dan sesi kegiatan.
- Absensi.
- Materi divisi.
- Pengumuman internal.
- Audit log.

Fitur yang tersedia bergantung pada permission pengguna.

---

## 4. Divisi dan Jabatan Organisasi

### 4.1 Divisi

Divisi yang tersedia pada MVP:

1. Kaligrafi.
2. MTQ.
3. Dakwah.
4. Hadroh.

Setiap anggota hanya memiliki satu divisi.

Anggota tidak dapat mengubah divisinya sendiri. Perubahan divisi oleh pengurus juga tidak termasuk alur normal MVP dan perlu keputusan tersendiri apabila nantinya dibutuhkan.

### 4.2 Jabatan Organisasi

Contoh jabatan:

- Ketua Umum.
- Wakil Ketua Umum.
- Sekretaris.
- Bendahara.
- Ketua Divisi.
- Anggota Divisi.

Satu orang dapat memiliki lebih dari satu jabatan organisasi.

Jabatan organisasi berbeda dari role sistem. Jabatan menjelaskan posisi seseorang di organisasi, sedangkan role dan permission menentukan tindakan yang boleh dilakukan dalam aplikasi.

### 4.3 Pengelolaan Jabatan

- Ketua Umum memiliki kewenangan tertinggi.
- Ketua Umum dapat mengelola jabatan organisasi.
- Riwayat jabatan tidak disimpan pada MVP.
- Sistem hanya menyimpan jabatan yang sedang berlaku.
- Penetapan jabatan tidak boleh secara otomatis memberikan akses yang tidak sesuai dengan aturan permission.

### 4.4 Posisi Intelektual

Intelektual merupakan posisi organisasi, bukan divisi.

Fitur khusus untuk posisi Intelektual tidak termasuk MVP. Namun, desain permission sebaiknya memungkinkan penambahan kewenangan baru tanpa perlu merombak seluruh sistem.

---

## 5. Role dan Permission

### 5.1 Role Sistem

Role konseptual pada MVP:

- `ketua_umum`
- `sekretaris`
- `bendahara`
- `ketua_divisi`
- `anggota`

Bendahara belum memiliki fitur khusus karena modul keuangan tidak termasuk MVP.

Tidak ada role `superadmin` terpisah pada MVP.

### 5.2 Ketua Umum

Ketua Umum memiliki akses penuh terhadap fitur pengelolaan internal, termasuk:

- Mengelola anggota.
- Menyetujui atau menolak pendaftaran anggota.
- Mengelola akun dan kewenangan.
- Mengelola jabatan organisasi.
- Mengelola program dan kegiatan lintas divisi.
- Mengelola absensi.
- Mengelola pengumuman lintas divisi.
- Melihat audit log.
- Menonaktifkan akun anggota.

### 5.3 Sekretaris

Sekretaris dapat:

- Mengelola data anggota.
- Menyetujui atau menolak pendaftaran anggota.
- Mengelola program dan kegiatan.
- Mengelola absensi.
- Mengelola akun dan role di bawah kewenangannya.
- Membuat pengumuman lintas divisi.
- Melihat data yang diperlukan untuk tugasnya.
- Menonaktifkan akun anggota sesuai kewenangannya.

Sekretaris tidak dapat memberikan role Ketua Umum kepada pengguna lain.

Sekretaris tidak otomatis memiliki kewenangan mengelola seluruh materi divisi.

### 5.4 Ketua Divisi

Ketua Divisi dapat:

- Melihat data anggota.
- Mengelola program yang khusus untuk divisinya.
- Mengelola materi divisinya.
- Mengelola absensi kegiatan divisinya.
- Membuat dan mengelola pengumuman untuk divisinya.
- Menonaktifkan anggota dalam divisinya sesuai aturan sistem.

Ketua Divisi tidak dapat:

- Mengelola data divisi lain.
- Mengubah role pengguna.
- Memberikan kewenangan yang lebih tinggi kepada dirinya sendiri.
- Mengakses tindakan administratif di luar kewenangannya.

### 5.5 Anggota

Anggota dapat:

- Melihat informasi anggota yang diizinkan.
- Melihat materi yang tersedia untuk divisinya.
- Melihat program dan sesi kegiatan.
- Melihat pengumuman yang ditujukan kepadanya.
- Mengubah nama dan informasi kontak miliknya sendiri.

Anggota tidak dapat:

- Mengubah NIS.
- Mengubah divisi sendiri.
- Mengubah role atau jabatan sendiri.
- Mengubah data anggota lain.
- Mengelola absensi atau data organisasi.

### 5.6 Aturan Otorisasi

Otorisasi wajib dilakukan pada backend.

Menyembunyikan tombol atau menu pada frontend tidak cukup untuk melindungi fitur.

Setiap tindakan harus memeriksa:

1. Apakah pengguna sudah login?
2. Apakah pengguna memiliki permission yang diperlukan?
3. Apakah data berada dalam lingkup kewenangannya?
4. Apakah tindakan tersebut diperbolehkan terhadap status data saat ini?

Pemeriksaan ini harus diterapkan pada route, controller, Livewire component, policy, atau lapisan backend yang relevan.

---

## 6. Pendaftaran dan Aktivasi Anggota

### 6.1 Formulir Pendaftaran Publik

Rute konseptual:

`/pendaftaran-anggota`

Data yang diminta:

- Nama lengkap.
- NIS.
- Email aktif.
- Pilihan divisi.
- Alasan bergabung.

Kelas tidak diisi sebagai teks bebas pada formulir awal. Riwayat kelas dikelola oleh sistem berdasarkan tahun ajaran.

WhatsApp tidak diwajibkan pada MVP.

### 6.2 Alur Pendaftaran

1. Calon anggota membuka formulir pendaftaran.
2. Calon anggota mengisi data yang diwajibkan.
3. Sistem memvalidasi data di backend.
4. Sistem memeriksa batas jumlah pendaftaran berdasarkan NIS.
5. Data pendaftaran disimpan.
6. Pengurus yang berwenang meninjau pendaftaran.
7. Pengurus menyetujui atau menolak pendaftaran.
8. Jika disetujui, sistem mengirim email berisi tautan aktivasi.
9. Calon anggota membuka tautan aktivasi.
10. Calon anggota membuat password sendiri.
11. Sistem mengaktifkan akun setelah proses valid.
12. Anggota dapat menggunakan fitur sesuai permission.

### 6.3 Aturan Pendaftaran

- Ketua Umum dan Sekretaris dapat menyetujui atau menolak pendaftaran.
- Satu NIS dibatasi maksimal tiga pendaftaran yang tercatat.
- Pengiriman ulang akibat gangguan teknis tidak boleh dihitung sebagai pendaftaran baru secara otomatis.
- Tautan aktivasi harus memiliki masa berlaku.
- Tautan aktivasi hanya dapat digunakan satu kali.
- Password disimpan menggunakan mekanisme hashing yang aman.
- Akun tidak boleh aktif sebelum proses aktivasi selesai.
- Calon anggota tidak dapat menentukan role atau jabatan sendiri.

### 6.4 Hal yang Masih Perlu Diputuskan

- Apakah pendaftaran yang dibatalkan secara manual tetap dihitung dalam batas tiga pendaftaran?
- Berapa lama tautan aktivasi berlaku?
- Bagaimana pengurus menangani email yang salah atau tidak dapat diakses?
- Bagaimana mekanisme pengiriman ulang email aktivasi?

### 6.5 Pemulihan Password

Sistem sebaiknya menyediakan pemulihan password melalui email.

Token pemulihan harus memiliki masa berlaku dan tidak dapat digunakan berulang kali.

---

## 7. Data Anggota

### 7.1 Profil Anggota

Data anggota minimal mencakup:

- Identitas anggota.
- NIS.
- Email.
- Informasi kontak.
- Divisi.
- Status akun.
- Riwayat kelas.
- Jabatan organisasi yang sedang berlaku.

Data sensitif tidak ditampilkan dalam direktori umum.

### 7.2 Direktori Anggota

Informasi yang dapat dilihat anggota lain:

- Nama.
- Kelas saat ini.
- Divisi.
- Jabatan organisasi.

NIS, email, nomor telepon, dan informasi absensi tidak ditampilkan secara terbuka dalam direktori anggota.

### 7.3 Perubahan Profil

Anggota dapat mengubah:

- Nama.
- Informasi kontak miliknya sendiri.

Anggota tidak dapat mengubah:

- NIS.
- Divisi.
- Role sistem.
- Jabatan organisasi.

Perubahan data administratif dilakukan oleh pengurus yang berwenang.

### 7.4 Penonaktifan Akun

Akun anggota yang sudah tidak aktif atau lulus dinonaktifkan, bukan langsung dihapus.

Riwayat kegiatan, absensi, dan data historis harus tetap tersedia bagi pengurus yang berwenang.

Penonaktifan akun tidak boleh menghapus catatan historis.

---

## 8. Riwayat Kelas

Sistem menyimpan kelas anggota per tahun ajaran.

Contoh:

| Tahun Ajaran | Kelas     |
| ------------ | --------- |
| 2025/2026    | X RPL 1   |
| 2026/2027    | XI RPL 1  |
| 2027/2028    | XII RPL 1 |

Data di atas hanya contoh format riwayat.

### 8.1 Aturan Riwayat Kelas

- Riwayat kelas lama tidak ditimpa.
- Setiap catatan terhubung dengan anggota dan tahun ajaran.
- Kelas saat ini ditentukan dari catatan yang berlaku.
- Data riwayat digunakan untuk menampilkan kelas saat ini dan menelusuri kelas sebelumnya.

### 8.2 Promosi Kelas Massal

Sistem perlu mendukung pembaruan kelas anggota secara massal untuk tahun ajaran baru.

Alur yang disarankan:

1. Pengurus memilih tahun ajaran tujuan.
2. Sistem menampilkan daftar anggota dan perubahan kelas yang akan diterapkan.
3. Pengurus memeriksa pratinjau.
4. Pengurus mengonfirmasi perubahan.
5. Sistem menyimpan riwayat kelas baru.
6. Sistem mencatat tindakan administratif dalam audit log.

Perubahan harus divalidasi agar tidak membuat riwayat kelas ganda untuk kombinasi anggota dan tahun ajaran yang sama.

---

## 9. Program, Sesi Kegiatan, dan Absensi

Program rutin, sesi kegiatan, dan absensi merupakan entitas yang berbeda.

### 9.1 Program Rutin

Program merupakan kegiatan berulang atau kategori kegiatan.

Contoh:

- Pembiasaan membaca Al-Qur'an pagi.
- Ekstrakurikuler hari Sabtu.
- Latihan Hadroh.
- Pembelajaran materi MTQ.

Program dapat bersifat umum atau khusus untuk divisi tertentu.

Program umum tidak boleh dipaksa menjadi program salah satu divisi.

### 9.2 Sesi Program

Satu program dapat memiliki banyak sesi.

Setiap sesi dapat memiliki:

- Program induk.
- Tanggal.
- Waktu.
- Lokasi.
- Agenda.
- Catatan.
- Daftar peserta yang menjadi sasaran.
- Pengaturan absensi.

Contoh:

Program: Ekstrakurikuler Sabtu.

Sesi pertama: latihan dasar.

Sesi kedua: persiapan penampilan.

Peserta atau sasaran absensi kedua sesi tersebut dapat berbeda.

### 9.3 Jenis Program

Program dan sesi harus dapat membedakan setidaknya:

1. Kegiatan umum untuk seluruh anggota.
2. Kegiatan khusus divisi.
3. Kegiatan pembelajaran divisi.
4. Kegiatan insidental.

Contoh kegiatan umum:

- Pembiasaan membaca Al-Qur'an pagi.

Contoh kegiatan divisi:

- Latihan Hadroh.
- Pembelajaran materi MTQ.

Contoh kegiatan insidental:

- Maulid.
- Buka bersama.
- Acara antarsekolah.

### 9.4 Kegiatan Insidental

Kegiatan insidental tidak harus memiliki program rutin sebagai induk.

Kegiatan dapat berlangsung selama beberapa hari.

Pengurus dapat memilih model absensi:

- Satu absensi untuk seluruh kegiatan.
- Absensi terpisah untuk setiap hari.

Pilihan tersebut ditentukan saat kegiatan dibuat.

### 9.5 Peserta dan Sasaran Absensi

Daftar sasaran absensi ditentukan berdasarkan kebutuhan setiap sesi.

Contoh:

- Pembiasaan pagi: seluruh anggota aktif.
- Latihan Hadroh: anggota divisi Hadroh.
- Ekstrakurikuler Sabtu: peserta yang ditentukan untuk sesi tersebut.
- Acara insidental: anggota atau kelompok yang ditentukan pengurus.

Sistem tidak boleh selalu menganggap seluruh anggota wajib hadir di setiap kegiatan.

### 9.6 Status Absensi

Status yang tersedia:

- Hadir.
- Izin.
- Sakit.
- Alpa.

Absensi bersifat opsional untuk setiap sesi.

Pengurus dapat membuat sesi tanpa mengaktifkan absensi jika tidak diperlukan.

### 9.7 Kewenangan Pengelolaan Program

- Ketua Umum dan Sekretaris dapat membuat program umum dan program lintas divisi.
- Ketua Divisi dapat membuat dan mengelola program khusus divisinya.
- Program umum tidak dimiliki oleh divisi tertentu hanya karena dibuat oleh pengurus tertentu.
- Kegiatan insidental lintas divisi dibuat oleh Ketua Umum atau Sekretaris.
- Ketua Divisi tidak dapat mengelola kegiatan divisi lain.

### 9.8 Kewenangan Absensi

- Ketua Umum: seluruh kegiatan.
- Sekretaris: seluruh kegiatan.
- Ketua Divisi: kegiatan dalam divisinya.

Perubahan absensi harus divalidasi dan dicatat dalam audit log.

---

## 10. Materi Divisi

### 10.1 Isi Materi

Materi dapat terdiri dari:

- Judul.
- Deskripsi.
- File.
- Tautan eksternal.
- Informasi waktu unggah.
- Pengunggah.

### 10.2 Kewenangan

Ketua Divisi dapat mengelola materi divisinya sendiri:

- Membuat materi.
- Mengubah materi.
- Menghapus materi.

Ketua Divisi tidak dapat mengelola materi divisi lain.

Ketua Umum dan Sekretaris tidak otomatis memperoleh kewenangan mengelola seluruh materi hanya karena memiliki akses administratif.

### 10.3 Akses Anggota

Anggota dapat melihat materi yang diperuntukkan bagi divisinya.

Sistem harus memeriksa otorisasi saat file diakses, bukan hanya menyembunyikan tautannya.

### 10.4 Keamanan File

- Validasi ekstensi dan tipe file.
- Batasi ukuran file.
- Gunakan nama penyimpanan yang aman.
- Jangan mempercayai nama file dari pengguna.
- File internal disimpan pada lokasi yang tidak dapat diakses secara publik tanpa otorisasi.
- Hapus file lama dengan aman ketika materi dihapus atau diganti, sesuai kebutuhan retensi.

### 10.5 Pengembangan Mendatang

Posisi Intelektual nantinya dapat memperoleh permission khusus untuk:

- Memantau aktivitas seluruh divisi.
- Mengelola materi lintas divisi jika disetujui.

Fitur ini tidak termasuk MVP.

---

## 11. Pengumuman Internal

### 11.1 Tujuan

Pengumuman digunakan untuk menyampaikan informasi kepada anggota melalui aplikasi internal.

Pengumuman bukan bagian dari halaman profil publik.

### 11.2 Sasaran Pengumuman

Pengumuman dapat ditujukan kepada:

- Seluruh anggota.
- Divisi tertentu.
- Pengurus atau kelompok pengurus yang ditentukan.

Sistem harus memastikan pengumuman hanya ditampilkan kepada pengguna yang termasuk sasaran.

### 11.3 Kewenangan

- Ketua Umum: pengumuman lintas divisi.
- Sekretaris: pengumuman lintas divisi.
- Ketua Divisi: pengumuman untuk divisinya sendiri.

### 11.4 Informasi Pengumuman

Data yang disarankan:

- Judul.
- Isi.
- Pembuat.
- Waktu dibuat.
- Waktu diperbarui.
- Sasaran penerima.
- Status publikasi.

### 11.5 Integrasi Eksternal

WhatsApp API tidak digunakan pada MVP.

Pengurus dapat membagikan informasi secara manual melalui WhatsApp atau saluran komunikasi lain.

---

## 12. Audit Log

Audit log digunakan untuk mencatat tindakan penting dalam aplikasi.

### 12.1 Tindakan yang Dicatat

Minimal:

- Persetujuan atau penolakan pendaftaran.
- Perubahan data anggota.
- Perubahan kelas massal.
- Perubahan jabatan atau permission.
- Penonaktifan akun.
- Perubahan absensi.
- Tindakan administratif penting lainnya.

### 12.2 Informasi Audit Log

Catatan audit sebaiknya memuat:

- Pengguna yang melakukan tindakan.
- Jenis tindakan.
- Waktu tindakan.
- Jenis dan ID data terkait.
- Ringkasan perubahan yang relevan.

Simpan hanya informasi yang diperlukan. Jangan memasukkan password, token aktivasi, atau rahasia autentikasi ke dalam audit log.

### 12.3 Integritas

- Anggota biasa tidak dapat mengubah audit log.
- Perubahan hak akses harus dicatat.
- Penghapusan atau perubahan data penting harus tetap dapat ditelusuri.
- Masa retensi audit log perlu ditentukan sebelum implementasi produksi.

---

## 13. Keamanan dan Privasi

Keamanan harus menjadi bagian dari implementasi setiap fitur.

### 13.1 Autentikasi

- Pengguna harus login sebelum mengakses aplikasi internal.
- Password disimpan menggunakan hashing yang aman.
- Proses aktivasi dan pemulihan password menggunakan token terbatas.
- Session harus dikelola dengan aman.
- Akun nonaktif tidak boleh menggunakan fitur anggota.

### 13.2 Validasi

- Validasi input dilakukan di backend.
- Jangan mengandalkan validasi browser saja.
- Validasi data relasi dan status sebelum menyimpan perubahan.
- Terapkan batas ukuran dan tipe file.
- Gunakan transaksi database untuk operasi multi-langkah yang harus konsisten.

### 13.3 Otorisasi

- Terapkan pemeriksaan permission pada backend.
- Terapkan pembatasan berdasarkan divisi.
- Cegah akses langsung ke data milik divisi lain.
- Cegah eskalasi hak akses melalui request yang dimodifikasi.
- Cegah IDOR, yaitu akses ke data pengguna lain hanya dengan mengubah ID pada URL atau request.

### 13.4 Perlindungan Aplikasi

- Gunakan proteksi CSRF yang sesuai.
- Terapkan rate limiting pada login dan formulir publik.
- Jangan menampilkan informasi sensitif pada halaman publik.
- Jangan menyimpan secret di repository.
- Gunakan konfigurasi environment untuk kredensial.
- Produksi harus menggunakan HTTPS.
- Sediakan backup dan prosedur pemulihan yang diuji.

### 13.5 Privasi Data

NIS, email, nomor telepon, dan informasi absensi tidak boleh ditampilkan dalam direktori umum.

Akses terhadap data internal harus dibatasi berdasarkan kebutuhan dan permission.

---

## 14. Fitur yang Tidak Termasuk MVP

Fitur berikut tidak dikerjakan pada tahap awal:

- CMS untuk halaman profil publik.
- Sistem keuangan.
- Pendaftaran peserta kegiatan.
- Absensi QR Code.
- Integrasi WhatsApp API.
- Modul khusus posisi Intelektual.
- Riwayat jabatan organisasi.
- Sistem promosi jabatan otomatis.
- Fitur lain yang belum disetujui.

Fitur di luar MVP dapat dipertimbangkan setelah kebutuhan inti stabil.

---

## 15. Rencana Pengembangan Bertahap

### Tahap 0 — Finalisasi Kebutuhan

- Memastikan kebutuhan MVP.
- Menyelesaikan pemetaan role dan permission.
- Menentukan aturan data anggota.
- Menentukan relasi program, sesi, dan absensi.
- Membuat ERD.
- Menentukan skema database awal.
- Memverifikasi lingkungan pengembangan.

**Output:** spesifikasi dan ERD yang disetujui.

### Tahap 1 — Fondasi Proyek

- Membuat proyek Laravel.
- Mengatur koneksi database.
- Menyiapkan Blade, Livewire, dan Tailwind CSS.
- Menyiapkan layout publik dan internal.
- Menyiapkan autentikasi.
- Membuat fondasi permission dan policy.
- Menyiapkan Git.

**Output:** aplikasi dasar yang dapat dijalankan.

### Tahap 2 — Profil Publik

- Beranda.
- Profil organisasi.
- Struktur organisasi yang boleh dipublikasikan.
- Informasi divisi.
- Informasi program publik.
- Halaman login.
- Halaman pendaftaran anggota.

**Output:** website publik yang berfungsi.

### Tahap 3 — Pendaftaran dan Anggota

- Penyimpanan pendaftaran.
- Validasi NIS dan batas pendaftaran.
- Dashboard peninjauan pendaftaran.
- Persetujuan dan penolakan.
- Email aktivasi.
- Pembuatan password.
- Direktori anggota.
- Pengelolaan data anggota.
- Penonaktifan akun.

**Output:** siklus pendaftaran anggota yang dapat diuji.

### Tahap 4 — Role dan Riwayat Kelas

- Role dan permission.
- Pengelolaan jabatan organisasi.
- Riwayat kelas.
- Pembaruan kelas massal.
- Pembatasan akses berbasis divisi.

**Output:** pengelolaan anggota dan kewenangan.

### Tahap 5 — Program dan Absensi

- Program rutin.
- Sesi program.
- Kegiatan insidental.
- Penentuan sasaran peserta.
- Absensi opsional.
- Absensi per kegiatan atau per hari.
- Pembatasan akses absensi berdasarkan role dan divisi.

**Output:** pengelolaan kegiatan dan absensi.

### Tahap 6 — Materi dan Pengumuman

- Pengelolaan materi divisi.
- Upload file dan tautan.
- Akses materi berdasarkan divisi.
- Pengumuman internal.
- Penentuan sasaran pengumuman.

**Output:** pusat informasi internal.

### Tahap 7 — Audit, Pengujian, dan Penyempurnaan

- Audit log.
- Pengujian permission.
- Pengujian alur aktivasi.
- Pengujian riwayat kelas.
- Pengujian absensi.
- Pengujian keamanan file.
- Perbaikan bug.
- Dokumentasi penggunaan.

**Output:** MVP yang sudah diuji berdasarkan kriteria penerimaan.

---

## 16. Strategi Pengujian

Setiap fitur harus diuji sebelum dianggap selesai.

### 16.1 Autentikasi

- Pengguna belum login tidak dapat mengakses halaman internal.
- Akun nonaktif tidak dapat menggunakan fitur anggota.
- Token aktivasi kedaluwarsa ditolak.
- Token aktivasi tidak dapat digunakan berulang kali.
- Password tidak disimpan dalam bentuk plaintext.

### 16.2 Permission

- Anggota tidak dapat mengubah data anggota lain.
- Anggota tidak dapat mengubah NIS atau divisinya sendiri.
- Ketua Divisi tidak dapat mengubah data divisi lain.
- Ketua Divisi tidak dapat mengubah role.
- Sekretaris tidak dapat menetapkan role Ketua Umum.
- Permintaan langsung ke endpoint terlarang harus ditolak.

### 16.3 Pendaftaran

- Formulir menolak input yang tidak valid.
- Batas pendaftaran berdasarkan NIS diterapkan.
- Pendaftaran yang disetujui memicu alur aktivasi.
- Pendaftaran yang ditolak tidak mengaktifkan akun.
- Pengiriman ulang karena gangguan teknis tidak otomatis dihitung sebagai pendaftaran baru.

### 16.4 Riwayat Kelas

- Riwayat lama tetap tersedia setelah promosi.
- Tidak ada riwayat ganda untuk anggota dan tahun ajaran yang sama.
- Pembaruan massal tidak dijalankan tanpa konfirmasi.
- Kegagalan operasi tidak boleh meninggalkan data setengah berubah.

### 16.5 Program dan Absensi

- Sesi dapat memiliki sasaran peserta yang berbeda.
- Program umum tidak otomatis menjadi milik suatu divisi.
- Ketua Divisi hanya mengelola kegiatan yang menjadi kewenangannya.
- Status absensi harus sesuai pilihan yang diizinkan.
- Perubahan absensi dicatat dalam audit log.

### 16.6 Materi dan Pengumuman

- Ketua Divisi tidak dapat mengubah materi divisi lain.
- File internal tidak dapat diakses tanpa otorisasi.
- Pengumuman hanya ditampilkan kepada sasaran yang sesuai.
- File dengan tipe atau ukuran yang tidak diizinkan ditolak.

---

## 17. Kriteria Penerimaan MVP

MVP dianggap memenuhi kebutuhan inti apabila:

1. Website publik dapat diakses.
2. Calon anggota dapat mengirim pendaftaran.
3. Pengurus dapat meninjau dan memutuskan pendaftaran.
4. Aktivasi akun melalui email berfungsi.
5. Anggota dapat login dan mengakses fitur sesuai kewenangan.
6. Pengurus dapat mengelola data anggota.
7. Riwayat kelas dapat disimpan tanpa menimpa riwayat lama.
8. Program dan sesi kegiatan dapat dikelola.
9. Absensi dapat dicatat sesuai sasaran kegiatan.
10. Materi divisi dapat dikelola sesuai permission.
11. Pengumuman internal dapat ditargetkan kepada kelompok yang sesuai.
12. Tindakan administratif penting tercatat dalam audit log.
13. Pengujian permission dan alur utama lulus.
14. Data historis tetap tersedia setelah akun dinonaktifkan.

---

## 18. Keputusan yang Masih Terbuka

Sebelum implementasi terkait dimulai, putuskan:

- [ ] Tanggal resmi penyelesaian proyek.
- [ ] Informasi organisasi yang boleh ditampilkan secara publik.
- [ ] Kebijakan untuk pendaftaran yang dibatalkan dalam batas tiga pendaftaran per NIS.
- [ ] Masa berlaku tautan aktivasi.
- [ ] Prosedur pengiriman ulang email aktivasi.
- [ ] Detail akhir pemetaan role dan permission.
- [ ] Aturan perubahan divisi oleh pengurus, jika kelak diperlukan.
- [ ] Aturan sasaran peserta dan perubahan absensi setelah kegiatan selesai.
- [ ] Masa retensi audit log.
- [ ] ERD dan nama tabel final.

Keputusan yang belum ditetapkan tidak boleh dianggap sudah disepakati.

---

## 19. Aturan Kerja Pengembangan

- Kerjakan satu tahap pada satu waktu.
- Jangan membuat perubahan besar sekaligus tanpa menjelaskan dampaknya.
- Sebelum mengubah kode yang sudah ada, periksa kode terkait terlebih dahulu.
- Jangan mengarang isi file, struktur tabel, atau konfigurasi yang belum diperiksa.
- Jika ada error, identifikasi penyebabnya sebelum memberikan solusi.
- Jelaskan alasan perubahan secara ringkas.
- Sertakan lokasi file dan kode yang relevan.
- Gunakan migrasi untuk perubahan skema database.
- Gunakan Git untuk menyimpan checkpoint perubahan.
- Jangan menyatakan fitur selesai sebelum diuji.
- Jika ada keputusan bisnis yang belum jelas, tanyakan sebelum mengunci implementasi.
- Prioritaskan solusi sederhana yang aman dan mudah dipelihara.
- Jangan menambahkan fitur baru hanya karena secara teknis memungkinkan.

---

## 20. Status Dokumen

Dokumen ini merupakan spesifikasi kerja versi 1.1.

Dokumen ini menjadi acuan awal untuk finalisasi kebutuhan, penyusunan ERD, dan implementasi MVP.

Spesifikasi dapat diperbarui ketika ada keputusan baru yang disetujui. Perubahan kebutuhan harus dicatat agar implementasi tetap konsisten dengan tujuan proyek.
