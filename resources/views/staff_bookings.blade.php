<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>All Appointments — SkinMedic</title>
    <link rel="stylesheet" href="{{ asset('asset/css/staff_bookings.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css" rel="stylesheet">
    <style>
        /* ── Tom Select — match app style ── */
        .ts-wrapper { padding: 0 !important; border: none !important; box-shadow: none !important; }
        .ts-control {
            border: 1.5px solid #e0e0e0 !important;
            border-radius: 7px !important;
            font-family: 'DM Sans', sans-serif !important;
            font-size: 0.85rem !important;
            background: #fafaf8 !important;
            padding: 7px 11px !important;
            box-shadow: none !important;
            min-height: unset !important;
            cursor: pointer;
        }
        .ts-wrapper.focus .ts-control { border-color: #80a833 !important; background: #fff !important; box-shadow: 0 0 0 2px rgba(128,168,51,0.15) !important; }
        .ts-dropdown { font-family: 'DM Sans', sans-serif; font-size: 0.85rem; border: 1.5px solid #e0e0e0; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.10); margin-top: 2px; }
        .ts-dropdown .option { padding: 8px 12px; color: #333; }
        .ts-dropdown .option:hover, .ts-dropdown .option.active { background: #f0f7e6 !important; color: #3a5c00; }
        .ts-dropdown .option.selected { background: #80a833 !important; color: #fff; }
        .ts-dropdown .ts-dropdown-content { max-height: 200px; }
        /* Fu-select Tom Select sizing */
        .fu-field .ts-wrapper { width: 100% !important; }
        .fu-field .ts-control { padding: 8px 11px !important; }
        /* Slot styles */
        .fu-slot-grid { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 4px; }
        .fu-slot-btn {
            padding: 6px 12px;
            border: 1.5px solid #ddd;
            border-radius: 7px;
            background: #fafaf8;
            color: #555;
            font-size: 0.8rem;
            font-family: 'DM Sans', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
        }
        .fu-slot-btn:hover { border-color: #80a833; color: #80a833; background: #f0f7e6; }
        .fu-slot-btn.selected { background: #80a833; color: #fff; border-color: #80a833; font-weight: 600; }
        .fu-slots-hint { font-size: 0.8rem; color: #aaa; margin: 4px 0; }
        .fu-slots-loading { color: #80a833; }
        .fu-slots-none { color: #e05; }
        .fu-slots-warn { color: #b45309; background: #fffbe6; border-radius: 5px; padding: 4px 8px; }
    </style>
</head>
<body>

@if(session('role') === 'staff')
    @include('partials.sidebar_staff')
@else
    @include('partials.sidebar_admin')
@endif

<div class="main">
    <div class="topbar">
        <h2>All Appointments</h2>
        <div style="display:flex;align-items:center;gap:14px;">
            <button class="followup-trigger-btn" onclick="openFollowUpModal()" type="button">
                📅 Schedule Follow-up
            </button>
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->toDateString() }}</strong>
            </div>
            @include('partials.notif_bell_staff')
        </div>
    </div>

    @if(session('success'))
        <div class="flash flash-success">✔ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">✕ {{ session('error') }}</div>
    @endif

    <div class="filter-tabs">
        <button class="{{ $activeFilter === 'all'       ? 'active' : '' }}" onclick="setTab('all', this)">All</button>
        <button class="{{ $activeFilter === 'pending'   ? 'active' : '' }}" onclick="setTab('pending', this)">Pending</button>
        <button class="{{ $activeFilter === 'approved'  ? 'active' : '' }}" onclick="setTab('approved', this)">Approved</button>
        <button class="{{ $activeFilter === 'completed' ? 'active' : '' }}" onclick="setTab('completed', this)">Completed</button>
        <button class="{{ $activeFilter === 'cancelled' ? 'active' : '' }}" onclick="setTab('cancelled', this)">Cancelled</button>
    </div>

    <div class="search-bar">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="q" placeholder="Search patient or service…" oninput="applyFilters()">
        <label for="doctorFilter">Doctor</label>
        <select id="doctorFilter" onchange="applyFilters()">
            <option value="">All doctors</option>
            @foreach($doctors as $doc)
                <option value="Dr. {{ $doc->firstName }} {{ $doc->lastName }}">
                    Dr. {{ $doc->firstName }} {{ $doc->lastName }}
                </option>
            @endforeach
        </select>
        <label for="dateFrom">From</label>
        <input type="date" id="dateFrom" style="width:132px" onchange="applyFilters()">
        <label for="dateTo">To</label>
        <input type="date" id="dateTo" style="width:132px" onchange="applyFilters()">
        <button class="reset-btn" onclick="resetFilters()">↺ Reset</button>
        <span class="result-count" id="resultCount"></span>
    </div>

    <table class="data-table" id="bookingsTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Service</th>
                <th>Doctor</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $row)
            @php $cls = strtolower($row->status); @endphp
            <tr data-status="{{ $row->status }}"
                data-id="{{ $row->appointment_id }}"
                data-date="{{ $row->appointment_date }}"
                onclick="openModal(
                    {{ $row->appointment_id }},
                    '{{ addslashes($row->patient_name) }}',
                    '{{ addslashes($row->service_name) }}',
                    '{{ addslashes($row->doctor_name) }}',
                    '{{ $row->appointment_date }}',
                    '{{ $row->appointment_time }}',
                    '{{ $row->status }}'
                )"
                style="cursor:pointer;">
                <td>{{ $row->appointment_id }}</td>
                <td>{{ $row->patient_name }}</td>
                <td>{{ $row->service_name }}</td>
                <td>{{ $row->doctor_name ? 'Dr. ' . $row->doctor_name : 'Not Assigned' }}</td>
                <td>{{ $row->appointment_date }}</td>
                <td>{{ \Carbon\Carbon::parse($row->appointment_time)->format('g:i A') }}</td>
                <td><span class="badge {{ $cls }}">{{ ucfirst($row->status) }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ══════════════════════════════════════════
     BOOKING DETAIL MODAL
═══════════════════════════════════════════ --}}
<div id="bookingModal" class="modal">
    <div class="modal-content" style="max-width:440px;">
        <div class="modal-header">
            <h2>Booking Details</h2>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" style="gap:0;">
            <div class="modal-row"><span>ID</span><span id="m_id"></span></div>
            <div class="modal-row"><span>Patient</span><span id="m_patient"></span></div>
            <div class="modal-row"><span>Service</span><span id="m_service"></span></div>
            <div class="modal-row"><span>Doctor</span><span id="m_doctor"></span></div>
            <div class="modal-row"><span>Date</span><span id="m_date"></span></div>
            <div class="modal-row"><span>Time</span><span id="m_time"></span></div>
            <div class="modal-row" style="border-bottom:none;"><span>Status</span><span id="m_status"></span></div>

            <form method="POST" action="{{ route('staff.bookings.update-status') }}" id="statusForm">
                @csrf
                <input type="hidden" name="appointment_id" id="appointment_id">
                <input type="hidden" name="status"         id="status_value">
                <input type="hidden" name="cancel_reason"  id="cancel_reason_value">
            </form>

            <div id="actionButtons" style="display:none; margin-top:18px; gap:8px; justify-content:center;">
                <button type="button" class="approve-btn"   onclick="submitStatus('approved')">✅ Approve</button>
                <button type="button" class="cancelled-btn" onclick="openCancelReason()">✕ Cancel</button>
            </div>

            <div id="actionButtonsApproved" style="display:none; margin-top:18px; gap:8px; justify-content:center;">
                <button type="button" class="complete-btn"  onclick="submitStatus('completed')">✔ Completed</button>
                <button type="button" class="cancelled-btn" onclick="openCancelReason()">✕ Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════
     CANCEL REASON MODAL
═══════════════════════════════════════════ --}}
<div id="cancelReasonModal" class="modal">
    <div class="modal-content reason-modal-content">
        <div class="modal-header">
            <h2>Cancel Appointment</h2>
            <span class="close-btn" onclick="closeCancelReason()">&times;</span>
        </div>
        <div class="modal-body">
            <p class="reason-intro">Please provide a reason for cancelling this appointment. The patient will be notified.</p>

            <div class="reason-chips">
                <button type="button" class="reason-chip" onclick="selectChip(this, 'Patient did not show up')">No-show</button>
                <button type="button" class="reason-chip" onclick="selectChip(this, 'Doctor unavailable')">Doctor unavailable</button>
                <button type="button" class="reason-chip" onclick="selectChip(this, 'Patient requested cancellation')">Patient request</button>
                <button type="button" class="reason-chip" onclick="selectChip(this, 'Clinic emergency / closure')">Clinic emergency</button>
                <button type="button" class="reason-chip" onclick="selectChip(this, 'Rescheduled to another date')">Rescheduled</button>
            </div>

            <textarea id="cancelReasonText"
                      class="reason-textarea"
                      placeholder="Type or select a reason above…"
                      maxlength="300"
                      oninput="updateCharCount(this)"></textarea>
            <div class="reason-charcount"><span id="charCount">0</span> / 300</div>

            <p id="reasonError" class="reason-error" style="display:none;">⚠ A cancellation reason is required.</p>

            <div class="reason-actions">
                <button type="button" class="reason-back-btn" onclick="closeCancelReason()">← Go Back</button>
                <button type="button" class="reason-confirm-btn" onclick="confirmCancel()">Confirm Cancellation</button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════
     FOLLOW-UP APPOINTMENT MODAL
═══════════════════════════════════════════ --}}
<div id="followUpModal" class="modal">
    <div class="modal-content followup-modal-content">
        <div class="modal-header">
            <h2>📅 Schedule Follow-up</h2>
            <span class="close-btn" onclick="closeFollowUpModal()">&times;</span>
        </div>
        <p class="followup-intro">
            Book a follow-up appointment for a patient — defaulting to <strong>1 week</strong> after their last session.
        </p>

        <form method="POST" action="{{ route('staff.bookings.followup') }}" id="followUpForm">
            @csrf

            {{-- Patient --}}
            <div class="fu-field">
                <label class="fu-label">Patient</label>
                <select name="user_id" id="fu_patient" class="fu-select" required>
                    <option value="">— Search patient —</option>
                    @foreach($patients as $pt)
                        <option value="{{ $pt->user_id }}"
                                data-last-date="{{ $pt->last_appt_date ?? '' }}"
                                data-last-time="{{ $pt->last_appt_time ?? '' }}"
                                data-last-service="{{ $pt->last_service_id ?? '' }}"
                                data-last-doctor="{{ $pt->last_doctor_id ?? '' }}">
                            {{ $pt->firstName }} {{ $pt->lastName }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Last session info --}}
            <div id="fu_last_info" class="fu-last-info" style="display:none;">
                <span class="fu-last-label">Last session:</span>
                <span id="fu_last_text" class="fu-last-text"></span>
            </div>

            {{-- Service --}}
            <div class="fu-field">
                <label class="fu-label">Service</label>
                <select name="service_id" id="fu_service" class="fu-select" required>
                    <option value="">— Search service —</option>
                    @foreach($services as $svc)
                        <option value="{{ $svc->service_id }}">{{ $svc->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Doctor --}}
            <div class="fu-field">
                <label class="fu-label">Doctor</label>
                <select name="doctor_id" id="fu_doctor" class="fu-select" required>
                    <option value="">— Search doctor —</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc->doctor_id ?? $doc->user_id }}">
                            Dr. {{ $doc->firstName }} {{ $doc->lastName }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date & Time --}}
            <div class="fu-row">
                <div class="fu-field">
                    <label class="fu-label">Date</label>
                    <input type="date" name="appointment_date" id="fu_date" class="fu-input" required>
                </div>
                <div class="fu-field">
                    <label class="fu-label">Time Slot</label>
                    <div id="fu_slots_wrap">
                        <p class="fu-slots-hint">Select a doctor and date first.</p>
                    </div>
                    <input type="hidden" name="appointment_time" id="fu_time" required>
                </div>
            </div>

            {{-- Notes --}}
            <div class="fu-field">
                <label class="fu-label">Notes <span class="fu-optional">optional</span></label>
                <textarea name="notes" class="fu-textarea" placeholder="e.g. Patient to continue treatment, same slot preferred…" rows="2"></textarea>
            </div>

            <div class="fu-actions">
                <button type="button" class="fu-cancel-btn" onclick="closeFollowUpModal()">Cancel</button>
                <button type="submit" class="fu-confirm-btn">✓ Book Follow-up</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
const TODAY = '{{ now()->toDateString() }}';
let activeTab = '{{ $activeFilter }}';

/* ── TAB FILTER ── */
function setTab(tab, btn) {
    activeTab = tab;
    document.querySelectorAll('.filter-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

/* ── SEARCH + FILTER ── */
function applyFilters() {
    const q     = document.getElementById('q').value.toLowerCase().trim();
    const doc   = document.getElementById('doctorFilter').value;
    const from  = document.getElementById('dateFrom').value;
    const to    = document.getElementById('dateTo').value;
    let visible = 0;
    const rows  = document.querySelectorAll('#bookingsTable tbody tr[data-status]');

    rows.forEach(tr => {
        const matchTab  = activeTab === 'all' || tr.dataset.status === activeTab;
        const text      = tr.innerText.toLowerCase();
        const matchQ    = !q   || text.includes(q);
        const matchDoc  = !doc || tr.querySelector('td:nth-child(4)').innerText.trim() === doc;
        const matchFrom = !from || tr.dataset.date >= from;
        const matchTo   = !to   || tr.dataset.date <= to;
        const show      = matchTab && matchQ && matchDoc && matchFrom && matchTo;
        tr.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const total = rows.length;
    document.getElementById('resultCount').textContent =
        visible === total ? `${total} appointments` : `${visible} of ${total}`;
}

function resetFilters() {
    document.getElementById('q').value = '';
    document.getElementById('doctorFilter').value = '';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    activeTab = 'all';
    document.querySelectorAll('.filter-tabs button')
        .forEach((b, i) => b.classList.toggle('active', i === 0));
    applyFilters();
}

/* ── BOOKING MODAL ── */
function openModal(id, patient, service, doctor, date, time, status) {
    document.getElementById('actionButtons').style.display         = 'none';
    document.getElementById('actionButtonsApproved').style.display = 'none';
    document.getElementById('m_id').innerText       = id;
    document.getElementById('m_patient').innerText  = patient;
    document.getElementById('m_service').innerText  = service;
    document.getElementById('m_doctor').innerText   = doctor || 'Not Assigned';
    document.getElementById('m_date').innerText     = date;
    document.getElementById('m_time').innerText     = time;
    document.getElementById('m_status').innerText   = status;
    document.getElementById('appointment_id').value = id;

    const s = status.trim().toLowerCase();
    if (s === 'pending') {
        document.getElementById('actionButtons').style.display = 'flex';
    } else if (s === 'approved') {
        document.getElementById('actionButtonsApproved').style.display = 'flex';
    }

    document.getElementById('bookingModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('bookingModal').style.display = 'none';
}

function submitStatus(s) {
    document.getElementById('status_value').value        = s;
    document.getElementById('cancel_reason_value').value = '';
    document.getElementById('statusForm').submit();
}

/* ── CANCEL REASON MODAL ── */
function openCancelReason() {
    document.getElementById('cancelReasonText').value    = '';
    document.getElementById('charCount').textContent     = '0';
    document.getElementById('reasonError').style.display = 'none';
    document.querySelectorAll('.reason-chip').forEach(c => c.classList.remove('selected'));
    document.getElementById('cancelReasonModal').style.display = 'flex';
}

function closeCancelReason() {
    document.getElementById('cancelReasonModal').style.display = 'none';
}

function selectChip(btn, text) {
    document.querySelectorAll('.reason-chip').forEach(c => c.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('cancelReasonText').value    = text;
    document.getElementById('charCount').textContent     = text.length;
    document.getElementById('reasonError').style.display = 'none';
}

function updateCharCount(el) {
    document.getElementById('charCount').textContent = el.value.length;
    if (el.value.trim()) document.getElementById('reasonError').style.display = 'none';
}

function confirmCancel() {
    const reason = document.getElementById('cancelReasonText').value.trim();
    if (!reason) {
        document.getElementById('reasonError').style.display = 'block';
        return;
    }
    document.getElementById('status_value').value        = 'cancelled';
    document.getElementById('cancel_reason_value').value = reason;
    document.getElementById('statusForm').submit();
}

/* ── FOLLOW-UP MODAL ── */
let tsPatient = null, tsService = null, tsDoctor = null;

function openFollowUpModal() {
    // Default date: today + 7 days
    const d = new Date();
    d.setDate(d.getDate() + 7);
    const defaultDate = d.toISOString().split('T')[0];

    document.getElementById('fu_date').value            = defaultDate;
    document.getElementById('fu_date').min              = TODAY; // ← block past dates
    document.getElementById('fu_time').value            = '';
    document.getElementById('fu_last_info').style.display = 'none';
    document.getElementById('fu_slots_wrap').innerHTML  = '<p class="fu-slots-hint">Select a doctor and date first.</p>';

    // Reset Tom Select values
    if (tsPatient) tsPatient.clear();
    if (tsService) tsService.clear();
    if (tsDoctor)  tsDoctor.clear();

    document.getElementById('followUpModal').style.display = 'flex';
}

function closeFollowUpModal() {
    document.getElementById('followUpModal').style.display = 'none';
}

function prefillFromLastAppt() {
    // Get the underlying select element value (Tom Select stores value there)
    const sel       = document.getElementById('fu_patient');
    const opt       = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    const lastDate    = opt.dataset.lastDate;
    const lastTime    = opt.dataset.lastTime;
    const lastService = opt.dataset.lastService;
    const lastDoctor  = opt.dataset.lastDoctor;
    const infoBox     = document.getElementById('fu_last_info');

    if (lastDate) {
        const d = new Date(lastDate + 'T00:00:00');
        d.setDate(d.getDate() + 7);
        const suggested = d.toISOString().split('T')[0];

        // Only set if suggested date is in the future
        const finalDate = suggested >= TODAY ? suggested : TODAY;
        document.getElementById('fu_date').value = finalDate;

        if (lastService && tsService) tsService.setValue(String(lastService));
        if (lastDoctor  && tsDoctor)  tsDoctor.setValue(String(lastDoctor));

        const label     = new Date(lastDate + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
        const timeLabel = lastTime ? ' at ' + new Date('1970-01-01T' + lastTime).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : '';
        document.getElementById('fu_last_text').textContent = label + timeLabel;
        infoBox.style.display = 'flex';

        fetchFollowUpSlots(lastTime ? lastTime.substring(0, 5) : null);
    } else {
        infoBox.style.display = 'none';
        const d = new Date();
        d.setDate(d.getDate() + 7);
        document.getElementById('fu_date').value           = d.toISOString().split('T')[0];
        document.getElementById('fu_slots_wrap').innerHTML = '<p class="fu-slots-hint">Select a doctor and date first.</p>';
        document.getElementById('fu_time').value           = '';
    }
}

function fetchFollowUpSlots(preferTime = null) {
    const doctor  = document.getElementById('fu_doctor').value;
    const date    = document.getElementById('fu_date').value;
    const service = document.getElementById('fu_service').value;
    const wrap    = document.getElementById('fu_slots_wrap');

    if (!doctor || !date) {
        wrap.innerHTML = '<p class="fu-slots-hint">Select a doctor and date first.</p>';
        document.getElementById('fu_time').value = '';
        return;
    }

    // ── Block past dates ──
    if (date < TODAY) {
        wrap.innerHTML = '<p class="fu-slots-hint fu-slots-none">⚠ Please select today or a future date.</p>';
        document.getElementById('fu_time').value = '';
        return;
    }

    wrap.innerHTML = '<p class="fu-slots-hint fu-slots-loading">⏳ Loading slots…</p>';
    document.getElementById('fu_time').value = '';

    // Uses /get-available-times which already filters by doctor's availability_schedule
    const url = `/get-available-times?doctor_id=${doctor}&date=${date}` + (service ? `&service_id=${service}` : '');

    fetch(url)
        .then(r => r.json())
        .then(slots => {
            if (!slots.length) {
                wrap.innerHTML = '<p class="fu-slots-hint fu-slots-none">No slots within this doctor\'s schedule on this date.</p>';
                return;
            }

            const available = slots.filter(s => !s.taken);
            if (!available.length) {
                wrap.innerHTML = '<p class="fu-slots-hint fu-slots-none">All slots are fully booked for this date.</p>';
                return;
            }

            wrap.innerHTML = '<div class="fu-slot-grid" id="fu_slot_grid"></div>';
            const grid = document.getElementById('fu_slot_grid');

            available.forEach(slot => {
                const btn     = document.createElement('button');
                btn.type      = 'button';
                btn.className = 'fu-slot-btn';
                btn.dataset.time = slot.time;

                const [h, m] = slot.time.split(':').map(Number);
                const ampm   = h >= 12 ? 'PM' : 'AM';
                const hour   = h % 12 || 12;
                btn.textContent = `${hour}:${String(m).padStart(2,'0')} ${ampm}`;

                btn.onclick = () => selectFollowUpSlot(slot.time, btn);

                if (preferTime && slot.time === preferTime) {
                    // defer so grid is in DOM first
                    setTimeout(() => selectFollowUpSlot(slot.time, btn), 0);
                }

                grid.appendChild(btn);
            });

            if (preferTime && !available.find(s => s.time === preferTime)) {
                const note       = document.createElement('p');
                note.className   = 'fu-slots-hint fu-slots-warn';
                note.textContent = `⚠ Previous slot (${preferTime}) is unavailable — please choose another.`;
                wrap.insertBefore(note, grid);
            }
        })
        .catch(() => {
            wrap.innerHTML = '<p class="fu-slots-hint fu-slots-none">Could not load slots. Check connection.</p>';
        });
}

function selectFollowUpSlot(time, btn) {
    document.querySelectorAll('.fu-slot-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('fu_time').value = time;
}

/* ── CLOSE ON BACKDROP ── */
window.onclick = e => {
    if (e.target === document.getElementById('bookingModal'))      closeModal();
    if (e.target === document.getElementById('cancelReasonModal')) closeCancelReason();
    if (e.target === document.getElementById('followUpModal'))     closeFollowUpModal();
};

/* ── INIT ── */
window.addEventListener('DOMContentLoaded', function () {
    applyFilters();

    // ── Tom Select — Patient ──
    tsPatient = new TomSelect('#fu_patient', {
        placeholder: '— Search patient —',
        allowEmptyOption: true,
        maxOptions: 300,
        onItemAdd() {
            prefillFromLastAppt();
        },
    });

    // ── Tom Select — Service ──
    tsService = new TomSelect('#fu_service', {
        placeholder: '— Search service —',
        allowEmptyOption: true,
        maxOptions: 100,
        onItemAdd() { fetchFollowUpSlots(); },
    });

    // ── Tom Select — Doctor ──
    tsDoctor = new TomSelect('#fu_doctor', {
        placeholder: '— Search doctor —',
        allowEmptyOption: true,
        maxOptions: 100,
        onItemAdd() { fetchFollowUpSlots(); },
    });

    // ── Set today as min date on fu_date ──
    document.getElementById('fu_date').min = TODAY;
    document.getElementById('fu_date').addEventListener('change', () => fetchFollowUpSlots());

    // ── Auto-open modal if ?open= param is set ──
    const params = new URLSearchParams(window.location.search);
    const openId = params.get('open');
    if (openId) {
        const row = document.querySelector(`#bookingsTable tbody tr[data-id="${openId}"]`);
        if (row) row.click();
    }
});
</script>

</body>
</html>