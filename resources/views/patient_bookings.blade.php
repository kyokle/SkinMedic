{{-- resources/views/patient_bookings.blade.php --}}

@extends('layouts.app')

@section('title', 'SkinMedic - My Appointments')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/patient_bookings.css') }}">
@endpush

@section('content')

@include('partials.sidebar_patient')

<div class="main">

    <div class="topbar">
        <h2>My Appointments</h2>
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->toDateString() }}</strong>
            </div>
            @include('partials.notif_bell_patient')
        </div>
    </div>

    <div class="filter-tabs">
        @foreach(['all', 'pending', 'approved', 'completed', 'cancelled'] as $tab)
        <button class="{{ $activeFilter === $tab ? 'active' : '' }}"
                onclick="filterTable('{{ $tab }}', this)">
            {{ ucfirst($tab) }}
        </button>
        @endforeach
    </div>

    <table id="bookingsTable">
        <thead>
            <tr>
                <th>Appointment #</th>
                <th>Service</th>
                <th>Doctor</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments as $row)
            @php $cls = strtolower($row->status); @endphp
            <tr data-status="{{ $row->status }}"
                data-id="{{ $row->appointment_id }}"
                onclick="openPatientModal(
                    '{{ $row->appointment_id }}',
                    '{{ addslashes($row->service_name) }}',
                    '{{ addslashes($row->doctor_name ?? '') }}',
                    '{{ $row->appointment_date }}',
                    '{{ \Carbon\Carbon::parse($row->appointment_time)->format('H:i') }}',
                    '{{ $row->status }}'
                )"
                style="cursor:pointer;">
                <td>{{ $row->appointment_id }}</td>
                <td>{{ $row->service_name }}</td>
                <td>{{ $row->doctor_name ? 'Dr. ' . $row->doctor_name : 'Not Assigned' }}</td>
                <td>{{ $row->appointment_date }}</td>
                <td>{{ \Carbon\Carbon::parse($row->appointment_time)->format('g:i A') }}</td>
                <td><span class="badge {{ $cls }}">{{ $row->status }}</span></td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;color:#999;padding:32px;">
                    No appointments found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

</div>

{{-- Patient Appointment Detail Modal --}}
<div id="patientModal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);
            z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px 24px;
                max-width:420px;width:90%;position:relative;">
        <button onclick="closePatientModal()"
                style="position:absolute;top:14px;right:16px;background:none;
                       border:none;font-size:20px;cursor:pointer;color:#888;">×</button>
        <h3 style="font-weight:700;font-size:16px;margin-bottom:16px;">Appointment Details</h3>
        <div style="display:flex;justify-content:space-between;padding:8px 0;
                    border-bottom:1px solid #f3f3f3;font-size:0.86rem;">
            <span style="color:#888;font-weight:500;">Service</span>
            <span id="pm_service" style="font-weight:600;"></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;
                    border-bottom:1px solid #f3f3f3;font-size:0.86rem;">
            <span style="color:#888;font-weight:500;">Doctor</span>
            <span id="pm_doctor" style="font-weight:600;"></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;
                    border-bottom:1px solid #f3f3f3;font-size:0.86rem;">
            <span style="color:#888;font-weight:500;">Date</span>
            <span id="pm_date" style="font-weight:600;"></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;
                    border-bottom:1px solid #f3f3f3;font-size:0.86rem;">
            <span style="color:#888;font-weight:500;">Time</span>
            <span id="pm_time" style="font-weight:600;"></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:0.86rem;">
            <span style="color:#888;font-weight:500;">Status</span>
            <span id="pm_status" style="font-weight:600;"></span>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function filterTable(status, btn) {
    document.querySelectorAll('.filter-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#bookingsTable tbody tr[data-status]').forEach(row => {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
    });
}

function openPatientModal(id, service, doctor, date, time, status) {
    document.getElementById('patientModal').style.display = 'flex';
    document.getElementById('pm_service').innerText = service;
    document.getElementById('pm_doctor').innerText  = doctor ? 'Dr. ' + doctor : 'Not Assigned';
    document.getElementById('pm_date').innerText    = date;
    document.getElementById('pm_time').innerText    = formatTime(time);
    document.getElementById('pm_status').innerText  = status;
}

function closePatientModal() {
    document.getElementById('patientModal').style.display = 'none';
}

function formatTime(t) {
    if (!t) return '';
    const [h, m] = t.split(':');
    const hr = parseInt(h);
    return (hr % 12 || 12) + ':' + m + ' ' + (hr < 12 ? 'AM' : 'PM');
}

window.onclick = e => {
    if (e.target === document.getElementById('patientModal')) closePatientModal();
};

window.addEventListener('DOMContentLoaded', function () {
    const filter = '{{ $activeFilter }}';
    if (filter && filter !== 'all') {
        const btn = document.querySelector('.filter-tabs button.active');
        if (btn) filterTable(filter, btn);
    }

    // Auto-open modal from notification click
    const params = new URLSearchParams(window.location.search);
    const openId = params.get('open');
    if (openId) {
        const row = document.querySelector(`#bookingsTable tbody tr[data-id="${openId}"]`);
        if (row) row.click();
    }
});
</script>
@endpush