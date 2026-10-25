# Email Configuration

## Status Saat Ini

### Development Environment
- Email backend: `log` (emails ditulis ke `storage/logs/laravel.log`)
- SMTP: Tidak dikonfigurasi
- Queue: Tidak digunakan (synchronous sending)

### Implementasi Activation Email
File: `app/Http/Controllers/MembershipReviewController.php` (method `approve`)

**Flow:**
1. Admin menyetujui pendaftaran
2. User dan Member record dibuat dalam DB transaction
3. Activation token di-generate (48 jam expiry)
4. Email dikirim via `Mail::to()->send(new AccountActivationMail($token))`
5. Error handling: try-catch dengan fallback graceful

**Karakteristik:**
- Synchronous sending (blocking)
- Error tidak menggagalkan approval (user tetap dibuat)
- Flash message menginformasikan status pengiriman ke admin

## Production Configuration

### Required .env Variables

```env
# Mail Configuration untuk Production
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@ipmi-example.com
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ipmi-example.com
MAIL_FROM_NAME="${APP_NAME}"

# Alternatif: Gmail SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Testing Production SMTP

#### 1. Test Koneksi SMTP
```bash
# Masuk ke Tinker
php artisan tinker

# Test send email
Mail::raw('Test email dari IPMI', function($message) {
    $message->to('test@example.com')
            ->subject('Test Email Configuration');
});

# Check for errors
```

#### 2. Test Activation Email Flow
```bash
# Buat test application
php artisan tinker

$app = \App\Models\MembershipApplication::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'nis' => '123456789',
    'phone' => '081234567890',
    'address' => 'Test Address',
    'school' => 'Test School',
    'grade' => 'X',
    'division_preference_id' => 1,
    'motivation' => 'Test motivation untuk testing email activation',
    'status' => 'pending'
]);

# Login sebagai admin dan approve via web interface
# Check email diterima dengan activation link yang valid
```

#### 3. Verifikasi Email Template
File: `resources/views/emails/account-activation.blade.php`

Pastikan:
- Link aktivasi menggunakan URL production: `{{ url('/activate/' . $token->token) }}`
- APP_URL di .env production sudah benar
- Email readable dan tidak masuk spam

## Queue Implementation (Future Work)

### Alasan Tidak Diimplementasikan Sekarang
- Memerlukan queue worker (supervisor/systemd service)
- Memerlukan Redis atau database queue driver
- Deployment complexity meningkat
- Synchronous sending cukup untuk MVP

### Kapan Queue Diperlukan
- Volume pendaftaran tinggi (>100/hari)
- SMTP timeout sering terjadi
- UX admin terganggu oleh email sending delay
- Perlu retry mechanism yang robust

### Implementasi Queue (Panduan Singkat)

**1. Install Redis (recommended) atau gunakan database queue:**
```bash
# Ubuntu/Debian
sudo apt install redis-server
```

**2. Update .env:**
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**3. Update MembershipReviewController:**
```php
// Ganti Mail::to()->send()
// Dengan:
Mail::to($user->email)->queue(new AccountActivationMail($token));
```

**4. Setup Queue Worker:**
```bash
# Supervisor config: /etc/supervisor/conf.d/ipmi-worker.conf
[program:ipmi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ipmi/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/ipmi/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ipmi-worker:*
```

## Error Handling

### Current Implementation
```php
try {
    Mail::to($user->email)->send(new AccountActivationMail($token));
    session()->flash('success', 'Email aktivasi berhasil dikirim');
} catch (\Exception $e) {
    \Log::error('Failed to send activation email', [
        'user_id' => $user->id,
        'error' => $e->getMessage()
    ]);
    session()->flash('warning', 'Akun dibuat tetapi email gagal dikirim');
}
```

### Manual Activation (Fallback)
Jika email gagal dikirim, admin dapat:
1. Salin activation token dari database
2. Kirim link manual via WhatsApp/SMS: `https://ipmi-domain.com/activate/{token}`
3. Token valid selama 48 jam

## Security Considerations

### SMTP Credentials
- **JANGAN commit .env ke Git**
- Gunakan environment variables atau secret manager di production
- Rotate SMTP password secara berkala

### Activation Token
- Random 64 characters (Str::random(64))
- Single-use (used boolean flag)
- 48-hour expiry
- Stored hashed? **No** - perlu plaintext untuk URL
- Rate limiting? **No** - tapi bisa ditambahkan jika diperlukan

### Email Content
- Tidak mengandung password atau data sensitif
- Link activation one-time use
- Tidak ada user data selain email address

## Monitoring

### Log Locations
- Laravel log: `storage/logs/laravel.log`
- Queue worker log (jika queue): `/var/log/supervisor/ipmi-worker.log`
- Mail log (development): `storage/logs/laravel.log`

### Metrics to Monitor (Production)
- Email delivery rate
- Bounce rate
- SMTP connection errors
- Activation token expiry without activation
- Time from approval to activation

---

**Last Updated:** 2026-10-24  
**Status:** Development mode (log), Production SMTP configuration documented  
**Queue:** Not implemented (future work)
