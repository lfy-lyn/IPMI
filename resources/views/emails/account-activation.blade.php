<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Aktivasi Akun IPMI</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">Selamat Datang di IPMI, {{ $fullName }}!</h2>
        
        <p>Pendaftaran Anda telah disetujui oleh pengurus IPMI. Untuk melanjutkan, silakan aktifkan akun Anda dengan mengklik tombol di bawah ini:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $activationUrl }}" 
               style="display: inline-block; background-color: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Aktifkan Akun
            </a>
        </div>
        
        <p>Atau salin dan buka URL berikut di browser Anda:</p>
        <p style="background-color: #f3f4f6; padding: 10px; border-radius: 5px; word-break: break-all;">
            {{ $activationUrl }}
        </p>
        
        <p><strong>Penting:</strong></p>
        <ul>
            <li>Link aktivasi ini berlaku hingga {{ $expiresAt->format('d F Y, H:i') }} WIB</li>
            <li>Setelah mengklik link, Anda akan diminta membuat password untuk akun Anda</li>
            <li>Gunakan password yang kuat dan mudah Anda ingat</li>
        </ul>
        
        <p>Jika Anda tidak mendaftar di IPMI, abaikan email ini.</p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        
        <p style="font-size: 0.875rem; color: #6b7280;">
            Email ini dikirim secara otomatis. Mohon tidak membalas email ini.
        </p>
    </div>
</body>
</html>