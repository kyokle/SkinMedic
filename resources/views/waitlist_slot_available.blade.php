<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">

  <div style="max-width:520px;margin:40px auto;background:white;border-radius:12px;
              box-shadow:0 2px 12px rgba(0,0,0,0.08);overflow:hidden;">

    {{-- Header --}}
    <div style="background:#80a833;padding:28px 32px;text-align:center;">
      <h1 style="margin:0;color:white;font-size:22px;letter-spacing:0.5px;">SkinMedic</h1>
      <p style="margin:6px 0 0;color:#e8f5c8;font-size:13px;">Skin Care &amp; Wellness Clinic</p>
    </div>

    {{-- Body --}}
    <div style="padding:32px;">
      <h2 style="margin:0 0 8px;color:#333;font-size:20px;">Your Slot Just Opened Up! 🎉</h2>
      <p style="margin:0 0 24px;color:#666;font-size:14px;line-height:1.6;">
        Hi {{ $firstName }}, great news — a slot you were waiting for is now available. Claim it before it's gone!
      </p>

      {{-- Slot Details Card --}}
      <div style="background:#f9fff2;border:1px solid #d4edb3;border-radius:10px;padding:20px 24px;margin-bottom:24px;">
        <p style="margin:0 0 4px;font-size:11px;color:#80a833;font-weight:600;text-transform:uppercase;letter-spacing:0.8px;">Available Slot</p>

        <p style="margin:10px 0 0;font-size:15px;color:#333;">
          <strong>Service:</strong> {{ $serviceName }}
        </p>
        <p style="margin:8px 0 0;font-size:15px;color:#333;">
          <strong>Date:</strong> {{ \Carbon\Carbon::parse($preferredDate)->format('F j, Y') }}
        </p>
        <p style="margin:8px 0 0;font-size:15px;color:#333;">
          <strong>Time:</strong> {{ \Carbon\Carbon::createFromFormat('H:i', $preferredTime)->format('g:i A') }}
        </p>
      </div>

      {{-- Urgency Warning --}}
      <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;
                  padding:12px 16px;margin-bottom:24px;display:flex;align-items:center;gap:10px;">
        <span style="font-size:20px;">⏰</span>
        <p style="margin:0;font-size:13px;color:#92400e;line-height:1.5;">
          This offer expires at <strong>{{ $expiresAt }}</strong>. 
          After 30 minutes, the slot will be offered to the next person in line.
        </p>
      </div>

      {{-- CTA Button --}}
      <div style="text-align:center;margin-bottom:24px;">
        <a href="{{ $claimUrl }}"
           style="display:inline-block;background:#80a833;color:white;text-decoration:none;
                  font-size:15px;font-weight:600;padding:14px 36px;border-radius:8px;
                  letter-spacing:0.3px;">
          ✅ Claim My Slot Now
        </a>
      </div>

      <p style="margin:0;color:#999;font-size:12px;text-align:center;line-height:1.6;">
        If the button doesn't work, copy and paste this link into your browser:<br>
        <span style="color:#80a833;word-break:break-all;">{{ $claimUrl }}</span>
      </p>
    </div>

    {{-- Footer --}}
    <div style="background:#f9f9f9;border-top:1px solid #eee;padding:16px 32px;text-align:center;">
      <p style="margin:0;font-size:11px;color:#aaa;">
        © {{ date('Y') }} SkinMedic. If you didn't sign up for a waitlist, please ignore this email.
      </p>
    </div>

  </div>

</body>
</html>