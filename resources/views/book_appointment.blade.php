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

  {{-- Flash Messages --}}
  @if(session('success'))
  <div class="alert alert-success">
    ✅ {{ session('success') }}
  </div>
  @endif

  @if($errors->any())
  <div class="alert alert-error">
    ⚠️ {{ $errors->first() }}
  </div>
  @endif

  {{-- Service Summary Card --}}
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
  <div class="regular-badge">⭐ Regular Customer — Preferred time reserved</div>
  @endif

  <form method="POST" action="{{ url('book-appointment') }}" id="bookingForm" novalidate>
    @csrf
    <input type="hidden" name="service_id" value="{{ $serviceId }}">

    {{-- Read-only patient info --}}
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

    {{-- Doctor --}}
    <div class="form-group">
      <label>Select Doctor <span class="req">*</span></label>
      <select name="doctor_id" id="doctor_id" required
              class="{{ $errors->has('doctor_id') ? 'is-invalid' : '' }}">
        <option value="">— Choose a doctor —</option>
        @foreach($doctors as $doc)
          <option value="{{ $doc->doctor_id }}"
            {{ old('doctor_id') == $doc->doctor_id ? 'selected' : '' }}>
            Dr. {{ $doc->firstName }} {{ $doc->lastName }}
          </option>
        @endforeach
      </select>
      @error('doctor_id')
        <span class="field-error">⚠ {{ $message }}</span>
      @enderror
    </div>

    {{-- Date + Time Preference --}}
    <div class="form-row">
      <div class="form-group">
        <label>Appointment Date <span class="req">*</span></label>
        <input type="date" name="appointment_date" id="appointment_date"
               min="{{ date('Y-m-d') }}"
               value="{{ old('appointment_date') }}"
               required
               class="{{ $errors->has('appointment_date') ? 'is-invalid' : '' }}">
        @error('appointment_date')
          <span class="field-error">⚠ {{ $message }}</span>
        @enderror
      </div>

      <div class="form-group">
        <label>Preferred Time of Day</label>
        @if($isRegular && $preferredTime)
          <div class="locked-time">
            🔒 {{ \Carbon\Carbon::createFromFormat('H:i', $preferredTime)->format('g:i A') }}
          </div>
          <input type="hidden" name="appointment_time" value="{{ $preferredTime }}">
        @else
          <select id="time_preference">
            <option value="">Any Time</option>
            <option value="AM" {{ old('time_preference') == 'AM' ? 'selected' : '' }}>AM (8am – 12pm)</option>
            <option value="PM" {{ old('time_preference') == 'PM' ? 'selected' : '' }}>PM (12pm – 7pm)</option>
          </select>
        @endif
      </div>
    </div>

    {{-- Available Time Slot --}}
    @if(!$isRegular || !$preferredTime)
    <div class="form-group">
      <label>Available Time <span class="req">*</span></label>
      <p class="time-loading" id="timeLoading">⏳ Fetching available slots…</p>
      <select name="appointment_time" id="appointment_time" required disabled
              class="{{ $errors->has('appointment_time') ? 'is-invalid' : '' }}">
        <option value="">Select date and doctor first</option>
      </select>
      @error('appointment_time')
        <span class="field-error">⚠ {{ $message }}</span>
      @enderror
    </div>
    @endif

    <button type="submit" id="submitBtn">Confirm Booking</button>
  </form>

  <a href="{{ url('patient/services') }}" class="back-btn">← Back to Services</a>
</div>

@if(!$isRegular || !$preferredTime)
<script>
const serviceId  = {{ (int)$serviceId }};
const doctorEl   = document.getElementById('doctor_id');
const dateEl     = document.getElementById('appointment_date');
const prefEl     = document.getElementById('time_preference');
const timeSelect = document.getElementById('appointment_time');
const timeLoad   = document.getElementById('timeLoading');
const submitBtn  = document.getElementById('submitBtn');
const form       = document.getElementById('bookingForm');

function loadTimes() {
  const date       = dateEl?.value   || '';
  const doctorId   = doctorEl?.value || '';
  const preference = prefEl?.value   || '';

  if (!date || !doctorId) {
    timeSelect.innerHTML = '<option value="">Select date and doctor first</option>';
    timeSelect.disabled  = true;
    return;
  }

  timeLoad.style.display = 'block';
  timeSelect.disabled    = true;
  timeSelect.innerHTML   = '<option value="">Loading…</option>';

  fetch(`/get-available-times?date=${date}&service_id=${serviceId}&doctor_id=${doctorId}&preference=${preference}`)
    .then(r => r.json())
    .then(times => {
      timeLoad.style.display = 'none';
      timeSelect.disabled    = false;

      if (!times.length) {
        timeSelect.innerHTML = '<option value="">No slots available</option>';
        timeSelect.disabled  = true;
        return;
      }

      const oldVal = '{{ old("appointment_time") }}';
      timeSelect.innerHTML = '<option value="">— Pick a time —</option>' +
        times.map(t => {
          const [h, m] = t.split(':');
          const hour   = parseInt(h);
          const label  = ((hour % 12) || 12) + ':' + m + (hour >= 12 ? ' PM' : ' AM');
          return `<option value="${t}"${t === oldVal ? ' selected' : ''}>${label}</option>`;
        }).join('');
    })
    .catch(() => {
      timeLoad.style.display = 'none';
      timeSelect.innerHTML   = '<option value="">Error loading. Try again.</option>';
    });
}

doctorEl?.addEventListener('change', loadTimes);
dateEl?.addEventListener('change', loadTimes);
prefEl?.addEventListener('change', loadTimes);

// ── Client-side validation ──
form.addEventListener('submit', function(e) {
  let valid = true;

  [doctorEl, dateEl, timeSelect].forEach(el => {
    if (!el) return;
    if (!el.value) {
      el.classList.add('is-invalid');
      valid = false;
    } else {
      el.classList.remove('is-invalid');
      el.classList.add('is-valid');
    }
  });

  if (!valid) {
    e.preventDefault();
    const first = form.querySelector('.is-invalid');
    first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }

  submitBtn.disabled    = true;
  submitBtn.textContent = '⏳ Booking…';
});

// ── Blur validation ──
[doctorEl, dateEl, timeSelect].forEach(el => {
  if (!el) return;
  el.addEventListener('blur', () => {
    if (!el.value) {
      el.classList.add('is-invalid');
      el.classList.remove('is-valid');
    } else {
      el.classList.remove('is-invalid');
      el.classList.add('is-valid');
    }
  });
});
</script>
@endif

</body>
</html>