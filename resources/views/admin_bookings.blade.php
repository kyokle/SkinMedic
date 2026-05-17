<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>All Appointments — SkinMedic</title>
    <link rel="stylesheet" href="{{ asset('asset/css/admin_bookings.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
</head>
<body>

@if(session('role') === 'admin')
    @include('partials.sidebar_admin')
@else
    @include('partials.sidebar_staff')
@endif

<div class="main">
    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-error">{{ session('error') }}</div>
    @endif

    <div class="topbar">
        <h2 style="font-size:1.4rem;font-weight:700;">All Appointments</h2>
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->format('Y-m-d') }}</strong>
            </div>
            @include('partials.notif_bell_admin')
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

    <table class="data-table" id="bookingsTable" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.06);">
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
                        '{{ $row->appointment_id }}',
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
                    <td><span class="badge {{ $cls }}">{{ $row->status }}</span></td>
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
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f3f3;font-size:0.86rem;">
                <span style="color:#888;font-weight:500;">ID</span>
                <span id="m_id" style="font-weight:600;"></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f3f3;font-size:0.86rem;">
                <span style="color:#888;font-weight:500;">Patient</span>
                <span id="m_patient" style="font-weight:600;"></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f3f3;font-size:0.86rem;">
                <span style="color:#888;font-weight:500;">Service</span>
                <span id="m_service" style="font-weight:600;"></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f3f3;font-size:0.86rem;">
                <span style="color:#888;font-weight:500;">Doctor</span>
                <span id="m_doctor" style="font-weight:600;"></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f3f3;font-size:0.86rem;">
                <span style="color:#888;font-weight:500;">Date</span>
                <span id="m_date" style="font-weight:600;"></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f3f3;font-size:0.86rem;">
                <span style="color:#888;font-weight:500;">Time</span>
                <span id="m_time" style="font-weight:600;"></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:0.86rem;">
                <span style="color:#888;font-weight:500;">Status</span>
                <span id="m_status" style="font-weight:600;"></span>
            </div>

            <form method="POST" action="{{ route('admin.bookings.update-status') }}" style="margin-top:18px;" id="statusForm">
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

@if(session('role') === 'admin')
<form method="POST" id="deleteForm" style="margin-top:10px;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="appointment_id" id="delete_appointment_id">
    <div id="deleteButtonArea" style="display:none; justify-content:center;">
        <button type="button" class="delete-btn" onclick="confirmDelete()">🗑 Delete Record</button>
    </div>
</form>
@endif
        </div>
    </div>
</div>

{{-- ── Delete confirm modal ── --}}
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content" style="max-width:380px;text-align:center;">
        <div class="modal-header" style="justify-content:center;border-bottom:1px solid #f3f3f3;padding-bottom:12px;">
            <h2 style="color:#ef4444;">Delete Appointment</h2>
        </div>
        <div class="modal-body" style="padding:20px 0 8px;">
            <p style="font-size:0.9rem;color:#555;margin-bottom:6px;">
                You are about to <strong>permanently delete</strong> appointment
                <strong id="confirm_delete_id"></strong>.
            </p>
            <p style="font-size:0.82rem;color:#ef4444;">This cannot be undone.</p>
        </div>
        <div style="display:flex;gap:10px;justify-content:center;margin-top:16px;">
            <button class="delete-btn" onclick="executeDelete()">Yes, Delete</button>
            <button class="reset-btn" style="height:36px;padding:0 18px;" onclick="closeDeleteConfirm()">Cancel</button>
        </div>
    </div>
</div>

<script>
let activeTab = '{{ $activeFilter }}';

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
    activeTab = 'all';
    document.querySelectorAll('.filter-tabs button')
        .forEach((b, i) => b.classList.toggle('active', i === 0));
    applyFilters();
}

function openModal(id, patient, service, doctor, date, time, status) {
    document.getElementById('actionButtons').setAttribute('style', 'display:none; margin-top:18px; gap:8px; justify-content:center;');
    document.getElementById('actionButtonsApproved').setAttribute('style', 'display:none; margin-top:18px; gap:8px; justify-content:center;');

    const deleteArea = document.getElementById('deleteButtonArea');
    if (deleteArea) deleteArea.setAttribute('style', 'display:none; justify-content:center;');

    document.getElementById('bookingModal').style.display = 'flex';
    document.getElementById('m_id').innerText       = id;
    document.getElementById('m_patient').innerText  = patient;
    document.getElementById('m_service').innerText  = service;
    document.getElementById('m_doctor').innerText   = doctor || 'Not Assigned';
    document.getElementById('m_date').innerText     = date;
    document.getElementById('m_time').innerText     = time;
    document.getElementById('m_status').innerText   = status;
    document.getElementById('appointment_id').value = id;

    const deleteIdField = document.getElementById('delete_appointment_id');
    if (deleteIdField) deleteIdField.value = id;

    const s = status.trim().toLowerCase();
    if (s === 'pending') {
        document.getElementById('actionButtons').setAttribute('style', 'display:flex; margin-top:18px; gap:8px; justify-content:center;');
    } else if (s === 'approved') {
        document.getElementById('actionButtonsApproved').setAttribute('style', 'display:flex; margin-top:18px; gap:8px; justify-content:center;');
    }

    // Show delete button for admin on cancelled/completed records
    if (deleteArea && (s === 'cancelled' || s === 'completed')) {
        deleteArea.setAttribute('style', 'display:flex; justify-content:center; margin-top:12px;');
    }
}

function confirmDelete() {
    const id = document.getElementById('delete_appointment_id').value;
    document.getElementById('confirm_delete_id').innerText = '#' + id;
    document.getElementById('bookingModal').style.display = 'none';
    document.getElementById('deleteConfirmModal').style.display = 'flex';
}

function closeDeleteConfirm() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
}

function executeDelete() {
    const id = document.getElementById('delete_appointment_id').value;
    const form = document.getElementById('deleteForm');
    form.action = `/admin/bookings/${id}`;
    form.submit();
}

function closeModal() { document.getElementById('bookingModal').style.display = 'none'; }
function submitStatus(s) {
    document.getElementById('status_value').value = s;
    document.getElementById('statusForm').submit();
}

window.onclick = e => {
    if (e.target === document.getElementById('bookingModal')) closeModal();
    if (e.target === document.getElementById('deleteConfirmModal')) closeDeleteConfirm();
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