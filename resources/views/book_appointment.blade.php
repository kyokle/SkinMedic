{{-- resources/views/book_appointment.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Book Appointment</title>
  <link rel="stylesheet" href="{{ asset('/asset/css/book_appointment.css') }}">
</head>
<body>

<div class="container">

  <h2>Book Your Appointment</h2>

  @if(session('success'))
  <div class="alert alert-success">✅ {{ session('success') }}</div>
  @endif

  @if($errors->any())
  <div class="alert alert-error">⚠️ {{ $errors->first() }}</div>
  @endif

  @if($service)
  <div class="service-card">
    <div class="service-card-icon">💆</div>
    <div class="service-card-info">
      <span class="service-card-label">Selected Service</span>
      <span class="service-card-name">{{ $service->name }}</span>
      <span class="service-card-price">₱{{ number_format($service->price, 2) }}</span>
    </div>
  </div>
  @endif

  @if($isRegular && $preferredTime)
  <div class="regular-badge">⭐ Regular Customer — Preferred time: {{ \Carbon\Carbon::createFromFormat('H:i', $preferredTime)->format('g:i A') }}</div>
  @endif

  <form method="POST" action="{{ url('book-appointment') }}" id="bookingForm" novalidate>
    @csrf
    <input type="hidden" name="service_id"       value="{{ $serviceId }}">
    <input type="hidden" name="appointment_time" id="appointment_time_hidden">

    <div class="form-row">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" value="{{ $user->firstName }} {{ $user->lastName }}" readonly>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" value="{{ $user->email }}" readonly>
      </div>
    </div>

    <div class="form-group">
      <label>Select Doctor <span class="req">*</span></label>
      <select name="doctor_id" id="doctor_id" required>
        <option value="">— Choose a doctor —</option>
        @foreach($doctors as $doc)
          <option value="{{ $doc->doctor_id }}" {{ old('doctor_id') == $doc->doctor_id ? 'selected' : '' }}>
            Dr. {{ $doc->firstName }} {{ $doc->lastName }}
          </option>
        @endforeach
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Appointment Date <span class="req">*</span></label>
        <input type="date" name="appointment_date" id="appointment_date"
               min="{{ date('Y-m-d') }}"
               value="{{ old('appointment_date') }}" required>
      </div>

      @if(!$isRegular || !$preferredTime)
      <div class="form-group">
        <label>Preferred Time of Day</label>
        <select id="time_preference">
          <option value="">Any Time</option>
          <option value="AM">AM (8am – 12pm)</option>
          <option value="PM">PM (12pm – 7pm)</option>
        </select>
      </div>
      @endif
    </div>

    {{-- Time slot section --}}
    <div class="form-group">
      <label>Available Time <span class="req">*</span></label>
      <p class="time-loading" id="timeLoading" style="display:none;">⏳ Fetching available slots…</p>
      <div id="slotGrid" class="slot-grid"></div>
      <p id="noSlotsMsg" style="display:none;color:#999;font-size:13px;">No slots available for this date.</p>
    </div>

    {{-- Conflict box — shown when regular customer's preferred time is taken --}}
    <div class="conflict-box" id="conflictBox" style="display:none;">
      <h4>⏰ Your preferred time is taken</h4>
      <p id="conflictMsg">The <strong id="conflictTime"></strong> slot is already booked.
         You can join the waitlist and be notified if it opens, or pick another time below.</p>
      <div class="conflict-actions">
        <button type="button" class="btn-waitlist" onclick="joinWaitlist()">
          🔔 Join Waitlist (<span id="waitlistPos">?</span> in queue)
        </button>
        <button type="button" class="btn-pick-other" onclick="pickOtherTime()">
          📅 Pick Another Time
        </button>
      </div>
    </div>

    <button type="submit" id="submitBtn">Confirm Booking</button>
  </form>

  <a href="{{ url('patient/services') }}" class="back-btn">← Back to Services</a>
</div>



{{-- Waitlist success toast --}}
<div class="waitlist-toast" id="waitlistToast">
  ✅ <span id="waitlistToastMsg"></span>
</div>

<script>
const serviceId    = {{ (int)$serviceId }};
const isRegular    = {{ $isRegular ? 'true' : 'false' }};
const preferredTime = {{ $preferredTime ? '"' . $preferredTime . '"' : 'null' }};

const doctorEl   = document.getElementById('doctor_id');
const dateEl     = document.getElementById('appointment_date');
const prefEl     = document.getElementById('time_preference');
const slotGrid   = document.getElementById('slotGrid');
const timeLoad   = document.getElementById('timeLoading');
const noSlotsMsg = document.getElementById('noSlotsMsg');
const hiddenTime = document.getElementById('appointment_time_hidden');
const submitBtn  = document.getElementById('submitBtn');
const form       = document.getElementById('bookingForm');
const conflictBox = document.getElementById('conflictBox');

let allSlots         = [];   // full slot data from server
let pickedOtherTime  = false; // true once patient clicks "Pick Another Time"
let waitlistSlot     = null;  // the slot data for the conflict

// ── Load slots ───────────────────────────────────────────────
function loadTimes() {
  const date     = dateEl?.value   || '';
  const doctorId = doctorEl?.value || '';
  const pref     = prefEl?.value   || '';

  slotGrid.innerHTML   = '';
  noSlotsMsg.style.display = 'none';
  conflictBox.style.display = 'none';
  hiddenTime.value     = '';
  pickedOtherTime      = false;

  if (!date || !doctorId) return;

  timeLoad.style.display = 'block';

  fetch(`/get-available-times?date=${date}&service_id=${serviceId}&doctor_id=${doctorId}&preference=${pref}`)
    .then(r => r.json())
    .then(slots => {
      timeLoad.style.display = 'none';
      allSlots = slots;

      if (!slots.length) {
        noSlotsMsg.style.display = 'block';
        return;
      }

      // For regular customers: auto-select preferred, show conflict if taken
      if (isRegular && preferredTime && !pickedOtherTime) {
        const prefSlot = slots.find(s => s.time === preferredTime);

        if (prefSlot && prefSlot.taken) {
          // Show conflict box, render ALL slots but preferred highlighted as taken
          waitlistSlot = prefSlot;
          showConflict(prefSlot);
          renderSlots(slots, null, true); // show all, nothing pre-selected
          return;
        } else if (prefSlot && !prefSlot.taken) {
          // Preferred slot is free — auto-select it
          hiddenTime.value = preferredTime;
          renderSlots(slots, preferredTime, false);
          return;
        }
      }

      // Non-regular or pickedOtherTime — show all available slots normally
      renderSlots(slots, null, false);
    })
    .catch(() => {
      timeLoad.style.display = 'none';
      slotGrid.innerHTML = '<p style="color:#ef4444;font-size:13px;">Error loading slots. Please try again.</p>';
    });
}

// ── Render slot grid ─────────────────────────────────────────
function renderSlots(slots, preselect, showTaken) {
  slotGrid.innerHTML = '';

  slots.forEach(slot => {
    // Skip taken slots unless showTaken mode (conflict view)
    if (!showTaken && slot.taken) return;

    const btn   = document.createElement('button');
    btn.type    = 'button';
    const label = formatTime(slot.time);

    const isPreferred = isRegular && preferredTime === slot.time;

    if (slot.taken) {
      btn.className   = 'slot-btn taken';
      btn.disabled    = true;
      btn.innerHTML   = label +
        (slot.waitlist_count > 0
          ? `<span class="waitlist-count">${slot.waitlist_count} waiting</span>`
          : '<span class="waitlist-count">Fully booked</span>');
    } else {
      btn.className = 'slot-btn' + (isPreferred ? ' preferred-locked' : '');
      btn.innerHTML = label +
        (isPreferred ? '<span class="slot-star">⭐</span>' : '');

      if (preselect === slot.time) {
        btn.classList.add('selected');
      }

      btn.addEventListener('click', () => selectSlot(slot.time, btn));
    }

   slotGrid.appendChild(btn);
  });

  // Show "no slots" message if nothing was rendered
  if (!slotGrid.children.length) {
    noSlotsMsg.style.display = 'block';
  }
}

// ── Select a slot ─────────────────────────────────────────────
function selectSlot(time, btn) {
  document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  hiddenTime.value = time;
}

// ── Show conflict UI ──────────────────────────────────────────
function showConflict(slot) {
  const timeLabel = formatTime(slot.time);
  const pos       = (slot.waitlist_count || 0) + 1;

  document.getElementById('conflictTime').textContent = timeLabel;
  document.getElementById('waitlistPos').textContent  = pos;
  conflictBox.style.display = 'block';
}

// ── Patient clicks "Pick Another Time" ───────────────────────
function pickOtherTime() {
  pickedOtherTime = true;
  conflictBox.style.display = 'none';
  hiddenTime.value = '';

  // Re-render showing only available slots (no preferred lock)
  const available = allSlots.filter(s => !s.taken);
  if (!available.length) {
    noSlotsMsg.style.display = 'block';
    slotGrid.innerHTML = '';
    return;
  }
  renderSlots(allSlots, null, false);
}

// ── Join waitlist ─────────────────────────────────────────────
async function joinWaitlist() {
  const date = dateEl.value;
  if (!date || !waitlistSlot) return;

  const res = await fetch('{{ route("waitlist.join") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify({
      service_id:     serviceId,
      preferred_date: date,
      preferred_time: waitlistSlot.time,
    }),
  });

  const data = await res.json();

  conflictBox.style.display = 'none';

  const toast = document.getElementById('waitlistToast');
  document.getElementById('waitlistToastMsg').textContent = data.success
    ? data.message
    : (data.error || 'Could not join waitlist.');
  toast.style.background = data.success ? '#80a833' : '#ef4444';
  toast.style.display    = 'block';
  setTimeout(() => toast.style.display = 'none', 5000);
}

// ── Format time ───────────────────────────────────────────────
function formatTime(t) {
  if (!t) return '';
  const [h, m] = t.split(':');
  const hr = parseInt(h);
  return ((hr % 12) || 12) + ':' + m + ' ' + (hr < 12 ? 'AM' : 'PM');
}

// ── Event listeners ───────────────────────────────────────────
doctorEl?.addEventListener('change', loadTimes);
dateEl?.addEventListener('change',   loadTimes);
prefEl?.addEventListener('change',   loadTimes);

// ── Auto-load if values already filled (after validation error redirect) ──
window.addEventListener('DOMContentLoaded', function () {
    if (doctorEl?.value && dateEl?.value) {
        loadTimes();
    }
});

// ── Form submit guard ─────────────────────────────────────────
form.addEventListener('submit', function(e) {
  let valid = true;

  if (!doctorEl.value) { doctorEl.classList.add('is-invalid'); valid = false; }
  else doctorEl.classList.remove('is-invalid');

  if (!dateEl.value) { dateEl.classList.add('is-invalid'); valid = false; }
  else dateEl.classList.remove('is-invalid');

  if (!hiddenTime.value) {
    slotGrid.style.outline = '2px solid #ef4444';
    valid = false;
  } else {
    slotGrid.style.outline = 'none';
  }

  if (!valid) {
    e.preventDefault();
    form.querySelector('.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }

  submitBtn.disabled    = true;
  submitBtn.textContent = '⏳ Booking…';
});
</script>

</body>
</html>