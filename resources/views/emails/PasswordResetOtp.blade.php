<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset Verification Code</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f4f6f9; color: #1e293b; margin: 0; padding: 24px;">
<div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; overflow: hidden;">
    <div style="background-color: #1d4ed8; color: #ffffff; padding: 20px 24px;">
        <h2 style="margin: 0; font-size: 20px;">IGK School Management System</h2>
        <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.9;">Administrator Password Reset Verification</p>
    </div>
    <div style="padding: 24px;">
        <p style="margin-top: 0;">Hello {{ $recipientName }},</p>
        <p>We received a request to reset the password for your administrator account. Use the 6-digit verification code below to continue:</p>
        <div style="margin: 24px 0; padding: 16px; background-color: #f8fafc; border: 1px dashed #94a3b8; border-radius: 8px; text-align: center;">
            <span style="font-family: 'Courier New', Courier, monospace; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #0f172a;">{{ $otp }}</span>
        </div>
        <p style="font-size: 14px; color: #475569;">
            This verification code will expire in <strong>{{ $expiryMinutes }} minutes</strong> and can only be used once.
        </p>
        <p style="font-size: 14px; color: #475569;">
            If you did not request a password reset, you can safely ignore this email. Your password and Two-Factor Authentication settings will remain unchanged.
        </p>
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
        <p style="font-size: 12px; color: #64748b; margin-bottom: 0;">
            Never share this code with anyone. IGK School staff will never ask for your verification code.
        </p>
    </div>
</div>
</body>
</html>
