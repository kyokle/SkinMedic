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

{{-- ── Booking detail modal ── --}}
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

            <form method="POST" action="{{ route('admin.bookings.update-status') }}" style="margin-top:18px;">
                @csrf
                <input type="hidden" name="appointment_id" id="appointment_id">
                <input type="hidden" name="status"         id="status_value">
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