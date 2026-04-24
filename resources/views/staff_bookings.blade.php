{{-- staff_bookings.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<title>SkinMedic - All Appointments</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('asset/css/staff_bookings.css') }}">
</head>

<body>
@include('partials.sidebar_staff')

<div class="main">
    <div class="topbar">
        <h2>All Appointments</h2>
        <div class="date-box">
            <p>Today's Date</p>
            <strong>{{ date("Y-m-d") }}</strong>
        </div>
    </div>

    <div class="filter-tabs">
        <button class="{{ $activeFilter === 'all'       ? 'active' : '' }}" onclick="filterTable('all',this)">All</button>
        <button class="{{ $activeFilter === 'pending'   ? 'active' : '' }}" onclick="filterTable('pending',this)">Pending</button>
        <button class="{{ $activeFilter === 'approved'  ? 'active' : '' }}" onclick="filterTable('approved',this)">Approved</button>
        <button class="{{ $activeFilter === 'completed' ? 'active' : '' }}" onclick="filterTable('completed',this)">Completed</button>
        <button class="{{ $activeFilter === 'cancelled' ? 'active' : '' }}" onclick="filterTable('cancelled',this)">Cancelled</button>
    </div>

    <table id="bookingsTable">
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
        @foreach ($bookings as $row)
            @php $cls = strtolower($row->status); @endphp
            <tr data-status="{{ $row->status }}" onclick="openModal(
                '{{ $row->appointment_id }}',
                '{{ addslashes(htmlspecialchars($row->patient_name)) }}',
                '{{ addslashes(htmlspecialchars($row->service_name)) }}',
                '{{ addslashes(htmlspecialchars($row->doctor_name))  }}',
                '{{ $row->appointment_date }}',
                '{{ $row->appointment_time }}',
                '{{ $row->status }}'
            )">
                <td>{{ $row->appointment_id }}</td>
                <td>{{ $row->patient_name }}</td>
                <td>{{ $row->service_name }}</td>
                <td>{{ $row->doctor_name ? 'Dr. ' . $row->doctor_name : 'Not Assigned' }}</td>
                <td>{{ $row->appointment_date }}</td>
                <td>{{ date("g:i A", strtotime($row->appointment_time)) }}</td>
                <td><span class="badge {{ $cls }}">{{ $row->status }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- Booking Detail Modal --}}
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Booking Details</h3>
        <div class="modal-row"><span>ID</span><span id="m_id"></span></div>
        <div class="modal-row"><span>Patient</span><span id="m_patient"></span></div>
        <div class="modal-row"><span>Service</span><span id="m_service"></span></div>
        <div class="modal-row"><span>Doctor</span><span id="m_doctor"></span></div>
        <div class="modal-row"><span>Date</span><span id="m_date"></span></div>
        <div class="modal-row"><span>Time</span><span id="m_time"></span></div>
        <div class="modal-row"><span>Status</span><span id="m_status"></span></div>
        <form method="POST" action="{{ route('staff.bookings.update-status') }}">
            @csrf
            <input type="hidden" name="appointment_id" id="appointment_id">
            <input type="hidden" name="status" id="status_value">
            <div id="actionButtons" class="modal-actions">
                <button type="submit" name="update_status" class="approve-btn"   onclick="setStatus('approved')">✔ Approve</button>
                <button type="submit" name="update_status" class="cancelled-btn" onclick="setStatus('cancelled')">✖ Cancel</button>
            </div>
        </form>
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
    document.getElementById('bookingModal').style.display = 'flex';
    document.getElementById('m_id').innerText      = id;
    document.getElementById('m_patient').innerText = patient;
    document.getElementById('m_service').innerText = service;
    document.getElementById('m_doctor').innerText  = doctor || 'Not Assigned';
    document.getElementById('m_date').innerText    = date;
    document.getElementById('m_time').innerText    = time;
    document.getElementById('m_status').innerText  = status;
    document.getElementById('appointment_id').value = id;
    document.getElementById('actionButtons').style.display =
        (status === 'cancelled' || status === 'completed') ? 'none' : 'flex';
}

function closeModal() {
    document.getElementById('bookingModal').style.display = 'none';
}

function setStatus(s) {
    document.getElementById('status_value').value = s;
}

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