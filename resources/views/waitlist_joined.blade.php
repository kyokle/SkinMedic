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
      <h2 style="margin:0 0 8px;color:#333;font-size:20px;">You're on the Waitlist! ⭐</h2>
      <p style="margin:0 0 24px;color:#666;font-size:14px;line-height:1.6;">
        Hi {{ $firstName }}, we've added you to the waitlist. We'll notify you right away if your slot opens up.
      </p>

      {{-- Slot Details Card --}}
      <div style="background:#f9fff2;border:1px solid #d4edb3;border-radius:10px;padding:20px 24px;margin-bottom:24px;">
        <p style="margin:0 0 4px;font-size:11px;color:#80a833;font-weight:600;text-transform:uppercase;letter-spacing:0.8px;">Waitlisted Slot</p>

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

      {{-- Queue Position Badge --}}
      <div style="text-align:center;margin-bottom:24px;">
        <div style="display:inline-block;background:#fff8e1;border:1px solid #ffe082;
                    border-radius:50px;padding:12px 28px;">
          <span style="font-size:13px;color:#f59e0b;font-weight:600;">
            You are <span style="font-size:22px;color:#d97706;">#{{ $position }}</span> in the queue
          </span>
        </div>
      </div>

      <p style="margin:0 0 8px;color:#555;font-size:13px;line-height:1.7;">
        When a slot opens up, we'll send you another email with a <strong>claim link</strong>.
        You'll have <strong>30 minutes</strong> to confirm before it's offered to the next person.
      </p>
      <p style="margin:0;color:#999;font-size:12px;">
        You can also view your waitlist status from your patient dashboard.
      </p>
    </div>

    {{-- Footer --}}
    <div style="background:#f9f9f9;border-top:1px solid #eee;padding:16px 32px;text-align:center;">
      <p style="margin:0;font-size:11px;color:#aaa;">
        © {{ date('Y') }} SkinMedic. If you didn't request this, please ignore this email.
      </p>
    </div>

  </div>

</body>
</html>