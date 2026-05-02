<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; padding: 30px; background:#f9f9f9;">
    <div style="max-width:480px; margin:auto; background:white; border-radius:10px; padding:30px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="color:#80a833;">SkinMedic Email Verification</h2>
        <p>Hi {{ $firstName }}, thank you for registering!</p>
        <p>Use the code below to verify your email address:</p>
        <div style="font-size:36px; font-weight:bold; letter-spacing:10px; text-align:center; color:#80a833; padding:20px; background:#f0f7e6; border-radius:8px; margin:20px 0;">
            {{ $otp }}
        </div>
        <p style="color:#666;">This code expires in <strong>10 minutes</strong>.</p>
        <p style="color:#999; font-size:12px;">If you didn't register on SkinMedic, please ignore this email.</p>
    </div>
</body>
</html>