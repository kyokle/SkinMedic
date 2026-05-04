{{-- resources/views/doctor_bookings.blade.php --}}

@extends('layouts.app')

@section('title', 'SkinMedic - My Bookings')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/doctor_bookings.css') }}">
@endpush

@section('content')

@include('partials.sidebar_doctor')

<div class="main">

    <div class="topbar">
        <h2>My Bookings</h2>
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->toDateString() }}</strong>
            </div>
            @include('partials.notif_bell_doctor')
        </div>
    </div>

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #86efac;color:#166534;padding:12px 16px;
                border-radius:8px;margin-bottom:16px;">
        ✅ {{ session('success') }}
    </div>
    @endif

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
                <th>#</th>
                <th>Patient</th>
                <th>Service</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $row)
            @php $cls = strtolower($row->status); @endphp
            <tr data-status="{{ $row->status }}"
                data-id="{{ $row->appointment_id }}"
                onclick="openModal(
                    '{{ $row->appointment_id }}',
                    '{{ addslashes($row->patient_name) }}',
                    '{{ addslashes($row->service_name) }}',
                    '{{ $row->appointment_date }}',
                    '{{ \Carbon\Carbon::parse($row->appointment_time)->format('H:i') }}',
                    '{{ $row->status }}'
                )" style="cursor:pointer;">
                <td>{{ $row->appointment_id }}</td>
                <td>{{ $row->patient_name }}</td>
                <td>{{ $row->service_name }}</td>
                <td>{{ $row->appointment_date }}</td>
                <td>{{ \Carbon\Carbon::parse($row->appointment_time)->format('g:i A') }}</td>
                <td><span class="badge {{ $cls }}">{{ ucfirst($row->status) }}</span></td>
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

<div id="bookingModal" class="bk-modal">
    <div class="bk-modal-content" style="position:relative;">
        <button class="bk-close" onclick="closeModal()">×</button>
        <h3 class="bk-modal-title">Appointment Details</h3>

        <div class="bk-row"><span>Patient</span><span id="m_patient"></span></div>
        <div class="bk-row"><span>Service</span><span id="m_service"></span></div>
        <div class="bk-row"><span>Date</span><span id="m_date"></span></div>
        <div class="bk-row"><span>Time</span><span id="m_time"></span></div>
        <div class="bk-row"><span>Status</span><span id="m_status"></span></div>

        <div id="cancelSection" style="display:none; margin-top:14px;">
            <button type="button" class="bk-cancel-btn" onclick="openCancelConfirm()">
                ❌ Cancel Appointment
            </button>
        </div>

        <div id="rescheduleSection">
            <hr class="bk-divider">
            <p class="bk-reschedule-label">📅 Propose New Schedule</p>
            <form method="POST" action="{{ route('doctor.bookings.reschedule') }}">
                @csrf
                <input type="hidden" name="appointment_id" id="modal_appt_id">
                <div class="bk-form-row">
                    <label>New Date</label>
                    <input type="date" name="new_date" id="new_date"
                           min="{{ now()->addDay()->toDateString() }}" required>
                </div>
                <div class="bk-form-row">
                    <label>New Time</label>
                    <input type="time" name="new_time" id="new_time" required>
                </div>
                <button type="submit" class="bk-reschedule-btn">
                    📤 Send Reschedule Request
                </button>
            </form>
        </div>

        <p id="noRescheduleMsg" class="bk-no-action"></p>

        <div id="cancelConfirm"
             style="display:none; position:absolute; inset:0; background:rgba(0,0,0,0.45);
                    border-radius:14px; z-index:10; align-items:center; justify-content:center;">
            <div style="background:#fff; border-radius:12px; padding:28px 24px;
                        max-width:280px; text-align:center; box-shadow:0 8px 32px rgba(0,0,0,0.18);">
                <div style="font-size:2rem; margin-bottom:10px;">⚠️</div>
                <p style="font-weight:700; font-size:15px; margin-bottom:6px;">Cancel Appointment?</p>
                <p style="font-size:13px; color:#666; margin-bottom:20px;">
                    This action cannot be undone. The patient and clinic will be notified.
                </p>
                <div style="display:flex; gap:10px; justify-content:center;">
                    <button onclick="closeCancelConfirm()"
                            style="padding:8px 20px; border-radius:8px; border:1px solid #ddd;
                                   background:#f5f5f5; cursor:pointer; font-family:inherit; font-size:13px;">
                        Go Back
                    </button>
                    <form method="POST" action="{{ route('doctor.bookings.cancel') }}" style="margin:0;">
                        @csrf
                        <input type="hidden" name="appointment_id" id="cancel_appt_id">
                        <button type="submit"
                                style="padding:8px 20px; border-radius:8px; border:none;
                                       background:#dc2626; color:#fff; cursor:pointer;
                                       font-family:inherit; font-size:13px; font-weight:600;">
                            Yes, Cancel
                        </button>
                    </form>
                </div>
            </div>
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

function openModal(id, patient, service, date, time, status) {
    document.getElementById('bookingModal').style.display = 'flex';
    document.getElementById('m_patient').innerText = patient;
    document.getElementById('m_service').innerText = service;
    document.getElementById('m_date').innerText    = date;
    document.getElementById('m_time').innerText    = formatTime(time);
    document.getElementById('m_status').innerText  = status;
    document.getElementById('modal_appt_id').value  = id;
    document.getElementById('cancel_appt_id').value = id;

    document.getElementById('new_date').value = date;
    document.getElementById('new_time').value = time;
    document.getElementById('cancelConfirm').style.display = 'none';

    const canAct = (status === 'pending' || status === 'approved');
    document.getElementById('rescheduleSection').style.display = canAct ? 'block' : 'none';
    document.getElementById('cancelSection').style.display     = canAct ? 'block' : 'none';
    document.getElementById('noRescheduleMsg').textContent     = canAct
        ? '' : 'Actions are only available for pending or approved appointments.';
}

function closeModal() {
    document.getElementById('cancelConfirm').style.display = 'none';
    document.getElementById('bookingModal').style.display  = 'none';
}

function openCancelConfirm() {
    document.getElementById('cancelConfirm').style.display = 'flex';
}

function closeCancelConfirm() {
    document.getElementById('cancelConfirm').style.display = 'none';
}

function formatTime(t) {
    if (!t) return '';
    const [h, m] = t.split(':');
    const hr = parseInt(h);
    return (hr % 12 || 12) + ':' + m + ' ' + (hr < 12 ? 'AM' : 'PM');
}

window.onclick = e => {
    if (e.target === document.getElementById('bookingModal')) closeModal();
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