<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; padding: 30px;">
    <h2>Verify Your Email</h2>
    <p>Thank you for registering! Click the button below to verify your email address.</p>
    <a href="{{ $verifyUrl }}" 
       style="padding:12px 24px; background:#4CAF50; color:white; text-decoration:none; border-radius:6px;">
        Verify Email
    </a>
    <p style="margin-top:20px; color:#999; font-size:12px;">
        This link expires in 24 hours. If you didn't register, ignore this email.
    </p>
</body>
</html>