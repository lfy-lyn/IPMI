# Laporan Testing IPMI - 24 Oktober 2026

## Status Routes

Semua route utama **ACCESSIBLE** (HTTP 200):

- ✅ `/daftar` - Pendaftaran publik
- ✅ `/admin/tahun-ajaran` - Tahun ajaran
- ✅ `/admin/kelas` - Kelas
- ✅ `/admin/penetapan-kelas` - Penetapan kelas
- ✅ `/admin/kegiatan` - Kegiatan
- ✅ `/admin/materi` - Materi

## Issue yang Dilaporkan User

### 1. Pendaftaran 404

**Status:** FALSE - Route `/daftar` returns 200 OK
**Kemungkinan Penyebab:**

- User mencoba `/pendaftaran` (salah URL, seharusnya `/daftar`)
- Server tidak running saat test
- Browser cache old 404

**Solusi:**

```bash
# URL yang benar:
http://127.0.0.1:8000/daftar
```

### 2. Tahun Ajaran Error

**Status:** Route OK (200), perlu investigasi error spesifik
**Kemungkinan Issue:**

- Form validation error saat submit
- Database constraint error saat create/update
- Authorization error untuk role tertentu

**Perlu Test:**

- [ ] Create tahun ajaran baru
- [ ] Aktivasi tahun ajaran
- [ ] Arsipkan tahun ajaran
- [ ] Multiple active year (harus ditolak)

### 3. Kelas Error

**Status:** Route OK (200), perlu investigasi error spesifik
**Kemungkinan Issue:**

- Unique constraint error (academic_year_id, grade_level, major, class_number)
- Tahun ajaran tidak ada atau tidak active
- Form validation error

**Perlu Test:**

- [ ] Create kelas dengan tahun ajaran active
- [ ] Create kelas duplikat (harus ditolak)
- [ ] Create kelas tanpa tahun ajaran active

### 4. Penetapan Kelas Error

**Status:** Route OK (200), perlu investigasi error spesifik
**Kemungkinan Issue:**

- Tidak ada kelas dari tahun ajaran active
- Member sudah punya kelas
- Authorization error (bukan Ketua Umum/Sekretaris)

**Perlu Test:**

- [ ] Assign kelas ke member tanpa kelas
- [ ] Update kelas member yang sudah punya kelas
- [ ] Remove class assignment

### 5. Kegiatan Error

**Status:** Route OK (200), perlu investigasi error spesifik
**Kemungkinan Issue:**

- Form validation error (tanggal, division_id)
- Division tidak dipilih saat required
- Authorization error untuk Kadiv akses kegiatan divisi lain

**Perlu Test:**

- [ ] Create kegiatan All-IPMI (division_id null)
- [ ] Create kegiatan divisi spesifik
- [ ] Kadiv create kegiatan divisi sendiri
- [ ] Kadiv akses kegiatan divisi lain (harus 403)

### 6. Materi Error

**Status:** Route OK (200), perlu investigasi error spesifik
**Kemungkinan Issue:**

- File upload validation (size, type)
- File dan URL keduanya null (validation harus tolak)
- Storage path tidak writable
- Authorization error

**Perlu Test:**

- [ ] Upload file PDF < 10MB
- [ ] Upload file > 10MB (harus ditolak)
- [ ] Create materi dengan URL saja
- [ ] Create materi tanpa file dan tanpa URL (harus ditolak)
- [ ] Download file dengan authorization

## Data Testing Tersedia

Gunakan akun berikut untuk testing:

| Role              | Email                       | Password    |
| ----------------- | --------------------------- | ----------- |
| Ketua Umum        | ketua@ipmi.test             | password123 |
| Sekretaris        | sekretaris@ipmi.test        | password123 |
| Kadiv Kaligrafi   | kadiv.kaligrafi@ipmi.test   | password123 |
| Kadiv Hadroh      | kadiv.hadroh@ipmi.test      | password123 |
| Anggota Kaligrafi | anggota.kaligrafi@ipmi.test | password123 |
| Anggota MTQ       | anggota.mtq@ipmi.test       | password123 |

## Cara Testing Manual

### 1. Start Server

```bash
cd /home/lutfi/IPMI_V1.1
php artisan serve
```

### 2. Buka Browser

```
http://127.0.0.1:8000
```

### 3. Test Setiap Modul

#### A. Pendaftaran

1. Buka: http://127.0.0.1:8000/daftar
2. Isi form lengkap
3. Submit
4. Cek error validation atau success message

#### B. Tahun Ajaran (login sebagai Sekretaris)

1. Login: sekretaris@ipmi.test / password123
2. Menu: Tahun Ajaran
3. Klik "Tambah Tahun Ajaran"
4. Isi form (nama, tanggal mulai, tanggal akhir)
5. Submit → **catat error jika ada**

#### C. Kelas (login sebagai Sekretaris)

1. Pastikan ada tahun ajaran active
2. Menu: Kelas
3. Klik "Tambah Kelas"
4. Pilih tahun ajaran, tingkat, jurusan, nomor
5. Submit → **catat error jika ada**

#### D. Penetapan Kelas (login sebagai Ketua Umum)

1. Menu: Penetapan Kelas
2. Pilih member dari dropdown
3. Pilih kelas
4. Klik "Tetapkan" → **catat error jika ada**

#### E. Kegiatan (login sebagai Ketua Umum)

1. Menu: Kegiatan
2. Klik "Tambah Kegiatan"
3. Isi nama, tanggal, pilih divisi (atau kosongkan untuk All-IPMI)
4. Submit → **catat error jika ada**

#### F. Materi (login sebagai Ketua Umum)

1. Menu: Materi
2. Klik "Tambah Materi"
3. Pilih divisi
4. Upload file PDF atau isi URL
5. Submit → **catat error jika ada**

## Error yang Perlu Dicatat

Saat testing, catat informasi berikut jika terjadi error:

1. **URL halaman** (copy dari address bar)
2. **Role/user yang login** (email)
3. **Action yang dilakukan** (create, edit, delete, dll)
4. **Data yang diinput** (form fields)
5. **Error message** yang muncul di layar
6. **HTTP status** jika ada (404, 500, 403, dll)

## Langkah Selanjutnya

1. User melakukan testing manual mengikuti panduan di atas
2. User mencatat error spesifik yang ditemukan
3. Developer fix error berdasarkan laporan
4. Re-test setelah fix

---

**Catatan:** Semua route sudah accessible. Issue kemungkinan ada di:

- Form validation
- Database constraints
- Authorization logic
- Frontend rendering
- Business logic

Server berjalan di: http://127.0.0.1:8000 (port default Laravel)
