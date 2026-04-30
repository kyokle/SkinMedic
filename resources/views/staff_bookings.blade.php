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
        <div class="date-box">
            <p>Today's Date</p>
            <strong>{{ now()->format('Y-m-d') }}</strong>
        </div>
    </div>

    {{-- ── Filter tabs ── --}}
    <div class="filter-tabs">
        <button class="{{ $activeFilter === 'all'       ? 'active' : '' }}" onclick="filterTable('all', this)">All</button>
        <button class="{{ $activeFilter === 'pending'   ? 'active' : '' }}" onclick="filterTable('pending', this)">Pending</button>
        <button class="{{ $activeFilter === 'approved'  ? 'active' : '' }}" onclick="filterTable('approved', this)">Approved</button>
        <button class="{{ $activeFilter === 'completed' ? 'active' : '' }}" onclick="filterTable('completed', this)">Completed</button>
        <button class="{{ $activeFilter === 'cancelled' ? 'active' : '' }}" onclick="filterTable('cancelled', this)">Cancelled</button>
    </div>

    {{-- ── Bookings table ── --}}
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

{{-- ── Booking detail modal ── --}}
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

            <form method="POST" action="{{ route('staff.bookings.update-status') }}">
                @csrf
                <input type="hidden" name="appointment_id" id="appointment_id">
                <input type="hidden" name="status" id="status_value">

                {{-- Remove class="modal-actions" entirely, put all styles inline --}}
                <div id="actionButtons" style="display:none; margin-top:18px; gap:8px; justify-content:center;">
                    <button type="submit" class="approve-btn"   onmousedown="setStatus('approved')">Approve</button>
                    <button type="submit" class="cancelled-btn" onmousedown="setStatus('cancelled')">Cancel</button>
                </div>

                <div id="actionButtonsApproved" style="display:none; margin-top:18px; gap:8px; justify-content:center;">
                    <button type="submit" class="complete-btn"  onmousedown="setStatus('completed')">Completed</button>
                    <button type="submit" class="cancelled-btn" onmousedown="setStatus('cancelled')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterTable(status, btn) {
    document.querySelectorAll('.filter-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#bookingsTable tbody tr[data-status]').forEach(row => {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
    });
}

function openModal(id, patient, service, doctor, date, time, status) {
    // Force hide with !important-equivalent via setAttribute
    document.getElementById('actionButtons').setAttribute('style', 'display:none; margin-top:18px; gap:8px; justify-content:center;');
    document.getElementById('actionButtonsApproved').setAttribute('style', 'display:none; margin-top:18px; gap:8px; justify-content:center;');

    // ... rest of your code ...
    document.getElementById('bookingModal').style.display = 'flex';
    document.getElementById('m_id').innerText      = id;
    document.getElementById('m_patient').innerText = patient;
    document.getElementById('m_service').innerText = service;
    document.getElementById('m_doctor').innerText  = doctor || 'Not Assigned';
    document.getElementById('m_date').innerText    = date;
    document.getElementById('m_time').innerText    = time;
    document.getElementById('m_status').innerText  = status;
    document.getElementById('appointment_id').value = id;

    const s = status.trim().toLowerCase();
    if (s === 'pending') {
        document.getElementById('actionButtons').setAttribute('style', 'display:flex; margin-top:18px; gap:8px; justify-content:center;');
    } else if (s === 'approved') {
        document.getElementById('actionButtonsApproved').setAttribute('style', 'display:flex; margin-top:18px; gap:8px; justify-content:center;');
    }
}

function closeModal() { document.getElementById('bookingModal').style.display = 'none'; }
function setStatus(s) { document.getElementById('status_value').value = s; }

window.onclick = e => {
    if (e.target === document.getElementById('bookingModal')) closeModal();
};

window.addEventListener('DOMContentLoaded', function () {
    const filter = '{{ $activeFilter }}';
    if (filter && filter !== 'all') {
        const activeBtn = document.querySelector('.filter-tabs button.active');
        if (activeBtn) filterTable(filter, activeBtn);
    }
});
</script>

</body>
</html>