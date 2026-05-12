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
        <h2 style="font-size:1.4rem;font-weight:700;">All Appointments</h2>
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->format('Y-m-d') }}</strong>
            </div>
            @include('partials.notif_bell_staff')
        </div>
    </div>

    <div class="filter-tabs">
        <button class="{{ $activeFilter === 'all'       ? 'active' : '' }}" onclick="setTab('all', this)">All</button>
        <button class="{{ $activeFilter === 'pending'   ? 'active' : '' }}" onclick="setTab('pending', this)">Pending</button>
        <button class="{{ $activeFilter === 'approved'  ? 'active' : '' }}" onclick="setTab('approved', this)">Approved</button>
        <button class="{{ $activeFilter === 'completed' ? 'active' : '' }}" onclick="setTab('completed', this)">Completed</button>
        <button class="{{ $activeFilter === 'cancelled' ? 'active' : '' }}" onclick="setTab('cancelled', this)">Cancelled</button>
    </div>

    {{-- ── Search / filter bar ── --}}
    <div class="search-bar">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="flex-shrink:0"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
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

    {{-- ── Period filter ── --}}
    <div class="period-tabs">
        <button class="period-btn active" id="periodAll"     onclick="setPeriod('all', this)">All Time</button>
        <button class="period-btn"        id="periodDaily"   onclick="setPeriod('daily', this)">Daily</button>
        <button class="period-btn"        id="periodWeekly"  onclick="setPeriod('weekly', this)">Weekly</button>
        <button class="period-btn"        id="periodMonthly" onclick="setPeriod('monthly', this)">Monthly</button>
        <button class="period-btn"        id="periodYearly"  onclick="setPeriod('yearly', this)">Yearly</button>
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

<div id="bookingModal" class="modal">
    <div class="modal-content" style="max-width:440px;text-align:center;">
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

            <form method="POST" action="{{ route('staff.bookings.update-status') }}" style="margin-top:18px;" id="statusForm">
    @csrf
    <input type="hidden" name="appointment_id" id="appointment_id">
    <input type="hidden" name="status" id="status_value">
    <div id="actionButtons" style="display:none; margin-top:18px; gap:8px; justify-content:center;">
        <button type="button" class="approve-btn"   onclick="submitStatus('approved')">Approve</button>
        <button type="button" class="cancelled-btn" onclick="submitStatus('cancelled')">Cancel</button>
    </div>
    <div id="actionButtonsApproved" style="display:none; margin-top:18px; gap:8px; justify-content:center;">
        <button type="button" class="complete-btn"  onclick="submitStatus('completed')">Completed</button>
        <button type="button" class="cancelled-btn" onclick="submitStatus('cancelled')">Cancel</button>
    </div>
</form>
        </div>
    </div>
</div>

<script>
let activeTab    = '{{ $activeFilter }}';
let activePeriod = 'all';

// ── Period helpers ────────────────────────────────────────
function getToday() {
    return new Date().toISOString().slice(0, 10);
}

function getPeriodRange(period) {
    const now   = new Date();
    const today = now.toISOString().slice(0, 10);

    if (period === 'all')     return { from: '', to: '' };
    if (period === 'daily')   return { from: today, to: today };

    if (period === 'weekly') {
        const day  = now.getDay(); // 0=Sun
        const diff = now.getDate() - day + (day === 0 ? -6 : 1); // Monday
        const mon  = new Date(now.setDate(diff));
        const sun  = new Date(new Date(mon).setDate(mon.getDate() + 6));
        return {
            from: mon.toISOString().slice(0, 10),
            to:   sun.toISOString().slice(0, 10),
        };
    }

    if (period === 'monthly') {
        const y = now.getFullYear(), m = now.getMonth();
        const first = new Date(y, m, 1).toISOString().slice(0, 10);
        const last  = new Date(y, m + 1, 0).toISOString().slice(0, 10);
        return { from: first, to: last };
    }

    if (period === 'yearly') {
        const y = now.getFullYear();
        return { from: `${y}-01-01`, to: `${y}-12-31` };
    }

    return { from: '', to: '' };
}

function setPeriod(period, btn) {
    activePeriod = period;
    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const range = getPeriodRange(period);
    document.getElementById('dateFrom').value = range.from;
    document.getElementById('dateTo').value   = range.to;
    applyFilters();
}

function setTab(tab, btn) {
    activeTab = tab;
    document.querySelectorAll('.filter-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

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
        const matchQ    = !q || text.includes(q);
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
    activeTab    = 'all';
    activePeriod = 'all';
    document.querySelectorAll('.filter-tabs button')
        .forEach((b, i) => b.classList.toggle('active', i === 0));
    document.querySelectorAll('.period-btn')
        .forEach((b, i) => b.classList.toggle('active', i === 0));
    applyFilters();
}

function openModal(id, patient, service, doctor, date, time, status) {
    document.getElementById('actionButtons').setAttribute('style', 'display:none; margin-top:18px; gap:8px; justify-content:center;');
    document.getElementById('actionButtonsApproved').setAttribute('style', 'display:none; margin-top:18px; gap:8px; justify-content:center;');
    document.getElementById('bookingModal').style.display = 'flex';
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
        document.getElementById('actionButtons').setAttribute('style', 'display:flex; margin-top:18px; gap:8px; justify-content:center;');
    } else if (s === 'approved') {
        document.getElementById('actionButtonsApproved').setAttribute('style', 'display:flex; margin-top:18px; gap:8px; justify-content:center;');
    }
}

function closeModal() { document.getElementById('bookingModal').style.display = 'none'; }
function submitStatus(s) {
    document.getElementById('status_value').value = s;
    document.getElementById('statusForm').submit();
}

window.onclick = e => {
    if (e.target === document.getElementById('bookingModal')) closeModal();
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