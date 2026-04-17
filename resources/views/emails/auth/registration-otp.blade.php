<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration OTP</title>
</head>
<body style="margin:0;padding:24px;background:#f6f7fb;font-family:Arial,sans-serif;color:#1f2937;">
    <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px;border:1px solid #e5e7eb;">
        <h2 style="margin:0 0 16px;font-size:24px;">Registration OTP</h2>
        <p style="margin:0 0 16px;line-height:1.6;">Hello {{ $name }},</p>
        <p style="margin:0 0 20px;line-height:1.6;">Use the following one-time password to complete your registration:</p>
        <div style="margin:0 0 20px;padding:18px 24px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;font-size:30px;font-weight:700;letter-spacing:8px;text-align:center;">
            {{ $otp }}
        </div>
        <p style="margin:0 0 12px;line-height:1.6;">This OTP will expire in {{ $expiryMinutes }} minutes.</p>
        <p style="margin:0;line-height:1.6;color:#6b7280;">If you did not try to create an account, you can ignore this email.</p>
    </div>
</body>
</html>