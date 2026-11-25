<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Changed Confirmation</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f4f6f9; color: #1e293b; margin: 0; padding: 24px;">
<div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; overflow: hidden;">
    <div style="background-color: #0f766e; color: #ffffff; padding: 20px 24px;">
        <h2 style="margin: 0; font-size: 20px;">IGK School Management System</h2>
        <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.9;">Security Notification</p>
    </div>
    <div style="padding: 24px;">
        <p style="margin-top: 0;">Hello {{ $recipientName }},</p>
        <p>Your IGK School administrator account password was successfully changed.</p>
        <div style="margin: 18px 0; padding: 14px; background-color: #f8fafc; border-left: 4px solid #0f766e; border-radius: 4px; font-size: 14px;">
            <div><strong>Changed At:</strong> {{ $changedAt }}</div>
            @if (!empty($ipAddress))
                <div style="margin-top: 4px;"><strong>IP Address:</strong> {{ $ipAddress }}</div>
            @endif
        </div>
        <p style="font-size: 14px; color: #475569;">
            All active sessions have been signed out. You will need to log in with your new password and verify with Google Authenticator 2FA.
        </p>
        <p style="font-size: 14px; color: #b91c1c;">
            <strong>Didn't make this change?</strong> Contact the Super Administrator immediately to secure your account.
        </p>
    </div>
</div>
</body>
</html>
