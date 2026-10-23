# Authorization Matrix — IPMI

## 1. Prinsip

- Autentikasi dan otorisasi merupakan dua hal berbeda.
- Semua tindakan sensitif harus diperiksa di backend.
- Role sistem berbeda dari jabatan organisasi.
- Ketua Umum memiliki akses organisasi tertinggi.
- Sekretaris memiliki akses administrasi sesuai tanggung jawabnya.
- Ketua Divisi hanya mengelola data dalam cakupan divisinya, kecuali aturan tertentu menyatakan lain.
- Anggota tidak boleh mengubah role, jabatan, NIS, atau divisinya sendiri.
- Hak akses yang belum diputuskan harus tetap ditandai OPEN.

Keterangan:

- **Ya**: diperbolehkan sesuai aturan.
- **Terbatas**: hanya dalam cakupan yang ditentukan.
- **Tidak**: tidak diperbolehkan.
- **OPEN**: belum diputuskan.

## 2. Matriks Hak Akses

| Fitur                                       | Ketua Umum                  | Sekretaris                                | Ketua Divisi                 | Anggota                                       |
| ------------------------------------------- | --------------------------- | ----------------------------------------- | ---------------------------- | --------------------------------------------- |
| Melihat dashboard internal                  | Ya                          | Ya                                        | Terbatas                     | Terbatas                                      |
| Melihat daftar anggota                      | Ya                          | Ya                                        | Terbatas                     | Terbatas                                      |
| Mengubah data anggota                       | Ya                          | Ya                                        | Terbatas                     | Terbatas                                      |
| Mengubah NIS                                | Ya                          | Ya                                        | OPEN                         | Tidak                                         |
| Mengubah divisi anggota                     | Ya                          | Ya                                        | OPEN                         | Tidak                                         |
| Menonaktifkan anggota                       | Ya                          | Ya                                        | Terbatas pada divisi sendiri | Tidak                                         |
| Menetapkan role sistem                      | Ya                          | Terbatas, tanpa hak menetapkan Ketua Umum | Tidak                        | Tidak                                         |
| Mengelola jabatan organisasi                | Ya                          | Sesuai kewenangan administrasi            | Terbatas                     | Tidak                                         |
| Mengelola tahun ajaran                      | Ya                          | Ya                                        | Tidak                        | Tidak                                         |
| Mengelola daftar kelas                      | Ya                          | Ya                                        | Tidak                        | Tidak                                         |
| Menetapkan kelas anggota                    | Ya                          | Ya                                        | OPEN                         | Tidak                                         |
| Melakukan kenaikan kelas massal             | Ya                          | Ya                                        | Tidak                        | Tidak                                         |
| Melihat riwayat kelas                       | Ya                          | Ya                                        | Terbatas                     | Hanya kelas yang ditampilkan sesuai kebijakan |
| Membuat program lintas divisi               | Ya                          | Ya                                        | Tidak                        | Tidak                                         |
| Mengelola program divisi sendiri            | Ya                          | Ya                                        | Ya                           | Tidak                                         |
| Mengelola kegiatan insidental lintas divisi | Ya                          | Ya                                        | Tidak                        | Tidak                                         |
| Mengelola kehadiran lintas divisi           | Ya                          | Ya                                        | Tidak                        | Tidak                                         |
| Mengelola kehadiran divisi sendiri          | Ya                          | Ya                                        | Ya                           | Tidak                                         |
| Mengelola materi lintas divisi              | Tidak otomatis              | Tidak otomatis                            | Tidak                        | Tidak                                         |
| Mengelola materi divisi sendiri             | Ya, sesuai kebijakan divisi | Ya, sesuai kebijakan divisi               | Ya                           | Tidak                                         |
| Melihat materi divisi sendiri               | Ya                          | Ya                                        | Ya                           | Ya                                            |
| Mengelola pengumuman lintas divisi          | Ya                          | Ya                                        | Tidak                        | Tidak                                         |
| Mengelola pengumuman divisi sendiri         | Ya                          | Ya                                        | Ya                           | Tidak                                         |
| Melihat pengumuman yang ditargetkan         | Ya                          | Ya                                        | Ya                           | Ya                                            |
| Melihat audit log                           | OPEN                        | OPEN                                      | Tidak secara default         | Tidak                                         |

Catatan:

- Hak akses untuk NIS, perpindahan divisi, dan penetapan kelas oleh Ketua Divisi harus diputuskan sebelum implementasi fitur terkait.
- Ketua Umum dan Sekretaris dapat mengelola materi hanya jika kebijakan divisi mengizinkannya; akses lintas divisi tidak otomatis diberikan.
- Matriks ini tidak menggantikan validasi kepemilikan data dan cakupan divisi pada backend.

## 3. Data Pribadi Anggota

Anggota dapat melihat informasi anggota lain yang memang diperbolehkan:

- Nama
- Kelas saat ini
- Divisi
- Jabatan organisasi

Informasi berikut tidak boleh ditampilkan kepada semua anggota:

- NIS
- Email
- Nomor telepon
- Catatan kehadiran pribadi

Anggota hanya dapat mengubah data profil yang diperbolehkan, misalnya nama atau kontak. Perubahan tetap harus divalidasi.

## 4. Hak Akses Pengelolaan Kelas

### Ketua Umum

- Melihat daftar tahun ajaran dan kelas.
- Mengelola daftar kelas.
- Menetapkan atau mengubah kelas anggota.
- Menjalankan kenaikan kelas massal.
- Melihat riwayat kelas.

### Sekretaris

- Melihat daftar tahun ajaran dan kelas.
- Mengelola daftar kelas.
- Menetapkan atau mengubah kelas anggota.
- Menjalankan kenaikan kelas massal.
- Melihat riwayat kelas.

### Ketua Divisi

- Tidak mengelola daftar tahun ajaran atau daftar kelas secara global.
- Tidak menjalankan kenaikan kelas massal.
- Hak untuk menetapkan kelas anggota belum diputuskan.

### Anggota

- Tidak mengelola daftar kelas.
- Tidak mengubah kelas sendiri.
- Dapat melihat kelas saat ini yang ditampilkan pada profil anggota sesuai kebijakan privasi.

## Aturan Tambahan Pengelolaan Kelas

- Ketua Umum dan Sekretaris dapat menetapkan kelas anggota.
- Penetapan kelas wajib dilakukan agar data anggota lengkap.
- Perubahan kelas untuk kenaikan kelas hanya dilakukan pada tahun ajaran tujuan.
- Perpindahan rombel di tengah tahun ajaran tidak diperbolehkan.
- Koreksi data kelas yang salah harus memiliki alasan dan dicatat dalam audit log.
- Anggota tidak dapat mengubah kelasnya sendiri.

## 5. Kenaikan Kelas Massal

Proses kenaikan kelas hanya boleh dijalankan oleh role yang berwenang.

Sistem harus:

- Memvalidasi tahun ajaran asal dan tujuan.
- Memvalidasi setiap kelas tujuan.
- Mencegah penempatan kelas ganda.
- Menampilkan pratinjau perubahan.
- Meminta konfirmasi.
- Memproses perubahan secara konsisten.
- Mempertahankan riwayat lama.
- Mencatat tindakan penting dalam audit log.

Pengguna yang tidak berwenang tidak boleh dapat menjalankan proses melalui request langsung meskipun tombolnya tidak terlihat di antarmuka.

## 6. Pendaftaran Anggota

- Pengunjung dapat mengirim formulir pendaftaran publik.
- Ketua Umum dan Sekretaris dapat meninjau, menyetujui, atau menolak permohonan.
- Pendaftar tidak dapat menyetujui permohonannya sendiri.
- Persetujuan tidak memberi hak kepada pendaftar untuk menetapkan role.
- Aktivasi akun dilakukan melalui mekanisme aktivasi yang ditentukan.

## 7. Program dan Kehadiran

- Ketua Umum dan Sekretaris dapat mengelola program lintas divisi.
- Ketua Divisi hanya dapat mengelola program dan kehadiran dalam cakupan divisinya.
- Cakupan peserta harus diperiksa pada setiap sesi.
- Pengguna tidak boleh melihat atau mengubah catatan kehadiran di luar kewenangannya.

## 8. Penegakan Otorisasi

Implementasi harus:

- Memeriksa pengguna yang sedang login.
- Memeriksa role atau izin yang relevan.
- Memeriksa cakupan divisi jika diperlukan.
- Memeriksa kepemilikan atau hubungan data.
- Menolak request yang tidak sah.
- Menguji akses langsung ke route dan endpoint, bukan hanya tampilan antarmuka.

## 9. Hal yang Belum Diputuskan

1. Apakah Ketua Divisi boleh mengubah NIS anggota dalam divisinya.
2. Apakah Ketua Divisi boleh memindahkan anggota antar-divisi.
3. Apakah Ketua Divisi boleh menetapkan kelas anggota dalam divisinya.
4. Apakah Sekretaris boleh melihat dan mengelola semua audit log.
5. Hak akses Bendahara di luar fitur yang tersedia.
