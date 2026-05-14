<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>All Appointments — SkinMedic</title>
    <link rel="stylesheet" href="{{ asset('asset/css/staff_bookings.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
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

            {{-- Hidden form — submitted by JS --}}
            <form method="POST" action="{{ route('staff.bookings.update-status') }}" id="statusForm">
                @csrf
                <input type="hidden" name="appointment_id" id="appointment_id">
                <input type="hidden" name="status"         id="status_value">
                <input type="hidden" name="cancel_reason"  id="cancel_reason_value">
            </form>

            {{-- Pending actions --}}
            <div id="actionButtons" style="display:none; margin-top:18px; gap:8px; justify-content:center;">
                <button type="button" class="approve-btn"   onclick="submitStatus('approved')">✅ Approve</button>
                <button type="button" class="cancelled-btn" onclick="openCancelReason()">✕ Cancel</button>
            </div>

            {{-- Approved actions --}}
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

            {{-- Quick reason chips --}}
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

<script>
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
    const q      = document.getElementById('q').value.toLowerCase().trim();
    const doc    = document.getElementById('doctorFilter').value;
    const from   = document.getElementById('dateFrom').value;
    const to     = document.getElementById('dateTo').value;
    let visible  = 0;
    const rows   = document.querySelectorAll('#bookingsTable tbody tr[data-status]');

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

/* ── SUBMIT (non-cancel) ── */
function submitStatus(s) {
    document.getElementById('status_value').value        = s;
    document.getElementById('cancel_reason_value').value = '';
    document.getElementById('statusForm').submit();
}

/* ── CANCEL REASON MODAL ── */
function openCancelReason() {
    // Reset state
    document.getElementById('cancelReasonText').value = '';
    document.getElementById('charCount').textContent  = '0';
    document.getElementById('reasonError').style.display = 'none';
    document.querySelectorAll('.reason-chip').forEach(c => c.classList.remove('selected'));
    // Show cancel modal, keep booking modal open in background
    document.getElementById('cancelReasonModal').style.display = 'flex';
}

function closeCancelReason() {
    document.getElementById('cancelReasonModal').style.display = 'none';
}

function selectChip(btn, text) {
    // Toggle selection
    document.querySelectorAll('.reason-chip').forEach(c => c.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('cancelReasonText').value = text;
    document.getElementById('charCount').textContent  = text.length;
    document.getElementById('reasonError').style.display = 'none';
}

function updateCharCount(el) {
    document.getElementById('charCount').textContent = el.value.length;
    if (el.value.trim()) {
        document.getElementById('reasonError').style.display = 'none';
    }
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

/* ── CLOSE ON BACKDROP CLICK ── */
window.onclick = e => {
    if (e.target === document.getElementById('bookingModal'))      closeModal();
    if (e.target === document.getElementById('cancelReasonModal')) closeCancelReason();
};

window.addEventListener('DOMContentLoaded', function () {
    applyFilters();

    // Auto-open modal from notification click
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