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
        <button onclick="toggleWaitlistPanel(this)"
            id="waitlistToggleBtn"
            style="padding:7px 18px;border:2px solid #f59e0b;border-radius:20px;
                   background:#fff;color:#f59e0b;cursor:pointer;font-size:0.85rem;
                   font-weight:500;margin-left:auto;font-family:inherit;">
        🔔 My Waitlist
    </button>
    </div>

    {{-- Search & Date Range Row --}}
    <div class="search-range-bar">
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" id="searchInput" placeholder="Search by service, doctor, or appointment #…"
                   oninput="applyFilters()">
        </div>
        <div class="range-tabs">
            @foreach(['daily', 'weekly', 'monthly', 'yearly'] as $range)
            <button class="range-btn" data-range="{{ $range }}"
                    onclick="setRange('{{ $range }}', this)">
                {{ ucfirst($range) }}
            </button>
            @endforeach
            <button class="range-btn" data-range="all" onclick="setRange('all', this)">All Time</button>
        </div>
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

{{-- Waitlist Panel --}}
<div id="waitlistPanel" style="display:none;margin-top:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <h3 style="font-size:15px;font-weight:700;color:#1a1f16;margin:0;">
            🔔 My Waitlist Entries
        </h3>
        <span style="font-size:12px;color:#999;">
            You will be notified when a slot opens. You have 30 mins to claim it.
        </span>
    </div>
    <div id="waitlistEntries">
        <p style="color:#999;font-size:13px;">Loading...</p>
    </div>
</div>

{{-- Patient Appointment Detail Modal --}}
<div id="patientModal"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);
            z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px 24px;
                max-width:460px;width:90%;position:relative;max-height:90vh;overflow-y:auto;">

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
        <div style="display:flex;justify-content:space-between;padding:8px 0;
                    border-bottom:1px solid #f3f3f3;font-size:0.86rem;">
            <span style="color:#888;font-weight:500;">Status</span>
            <span id="pm_status" style="font-weight:600;"></span>
        </div>

        {{-- Cancel Button --}}
        <div id="pm_cancelSection" style="display:none;margin-top:14px;">
            <button type="button"
                    onclick="openPatientCancelConfirm()"
                    style="width:100%;padding:10px;background:#fee2e2;color:#991b1b;
                           border:1px solid #fca5a5;border-radius:8px;font-size:14px;
                           cursor:pointer;font-family:inherit;">
                ❌ Cancel Appointment
            </button>
        </div>

        {{-- Reschedule Form --}}
        <div id="pm_rescheduleSection" style="display:none;">
            <hr style="border:none;border-top:1px solid #eee;margin:18px 0 14px;">
            <p style="font-size:0.85rem;font-weight:600;color:#444;margin:0 0 12px;">
                📅 Propose New Schedule
            </p>
            <form method="POST" action="{{ route('patient.bookings.reschedule') }}">
                @csrf
                <input type="hidden" name="appointment_id" id="pm_appt_id">
                <div style="display:flex;flex-direction:column;gap:5px;margin-bottom:13px;">
                    <label style="font-size:0.75rem;font-weight:600;color:#666;
                                  text-transform:uppercase;letter-spacing:.5px;">New Date</label>
                    <input type="date" name="new_date" id="pm_new_date"
                           min="{{ now()->addDay()->toDateString() }}" required
                           style="padding:9px 12px;border:1.5px solid #ddd;border-radius:8px;
                                  font-size:0.9rem;outline:none;">
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;margin-bottom:13px;">
                    <label style="font-size:0.75rem;font-weight:600;color:#666;
                                  text-transform:uppercase;letter-spacing:.5px;">New Time</label>
                    <input type="time" name="new_time" id="pm_new_time" required
                           style="padding:9px 12px;border:1.5px solid #ddd;border-radius:8px;
                                  font-size:0.9rem;outline:none;">
                </div>
                <button type="submit"
                        style="width:100%;background:#80a833;color:#fff;border:none;
                               padding:11px;border-radius:8px;font-size:0.9rem;font-weight:600;
                               cursor:pointer;">
                    📤 Send Reschedule Request
                </button>
            </form>
        </div>

        <p id="pm_noActionMsg"
           style="font-size:0.8rem;color:#aaa;text-align:center;margin-top:16px;"></p>

        {{-- Cancel Confirmation Overlay --}}
        <div id="pm_cancelConfirm"
             style="display:none;position:absolute;inset:0;background:rgba(0,0,0,0.45);
                    border-radius:14px;z-index:10;align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:12px;padding:28px 24px;
                        max-width:280px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
                <div style="font-size:2rem;margin-bottom:10px;">⚠️</div>
                <p style="font-weight:700;font-size:15px;margin-bottom:6px;">Cancel Appointment?</p>
                <p style="font-size:13px;color:#666;margin-bottom:20px;">
                    This action cannot be undone. The doctor and clinic will be notified.
                </p>
                <div style="display:flex;gap:10px;justify-content:center;">
                    <button onclick="closePatientCancelConfirm()"
                            style="padding:8px 20px;border-radius:8px;border:1px solid #ddd;
                                   background:#f5f5f5;cursor:pointer;font-family:inherit;font-size:13px;">
                        Go Back
                    </button>
                    <form method="POST" action="{{ route('patient.bookings.cancel') }}" style="margin:0;">
                        @csrf
                        <input type="hidden" name="appointment_id" id="pm_cancel_appt_id">
                        <button type="submit"
                                style="padding:8px 20px;border-radius:8px;border:none;
                                       background:#dc2626;color:#fff;cursor:pointer;
                                       font-family:inherit;font-size:13px;font-weight:600;">
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
// ── Unified filter state ────────────────────────────────────
let activeStatus = '{{ $activeFilter }}' || 'all';
let activeRange  = 'all';

function filterTable(status, btn) {
    document.querySelectorAll('.filter-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeStatus = status;
    applyFilters();
}

function setRange(range, btn) {
    document.querySelectorAll('.range-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeRange = range;
    applyFilters();
}

function applyFilters() {
    const query   = (document.getElementById('searchInput').value || '').toLowerCase().trim();
    const now     = new Date();
    const today   = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    document.querySelectorAll('#bookingsTable tbody tr[data-status]').forEach(row => {
        // 1. Status filter
        if (activeStatus !== 'all' && row.dataset.status !== activeStatus) {
            row.style.display = 'none'; return;
        }

        // 2. Date range filter
        if (activeRange !== 'all') {
            const rawDate = row.querySelector('td:nth-child(4)')?.textContent?.trim();
            const rowDate = rawDate ? new Date(rawDate) : null;
            if (!rowDate || isNaN(rowDate)) { row.style.display = 'none'; return; }

            const rowDay = new Date(rowDate.getFullYear(), rowDate.getMonth(), rowDate.getDate());
            let inRange  = false;

            if (activeRange === 'daily') {
                inRange = rowDay.getTime() === today.getTime();
            } else if (activeRange === 'weekly') {
                const weekStart = new Date(today);
                weekStart.setDate(today.getDate() - today.getDay());
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekStart.getDate() + 6);
                inRange = rowDay >= weekStart && rowDay <= weekEnd;
            } else if (activeRange === 'monthly') {
                inRange = rowDate.getFullYear() === now.getFullYear()
                       && rowDate.getMonth()    === now.getMonth();
            } else if (activeRange === 'yearly') {
                inRange = rowDate.getFullYear() === now.getFullYear();
            }

            if (!inRange) { row.style.display = 'none'; return; }
        }

        // 3. Search filter
        if (query) {
            const text = row.textContent.toLowerCase();
            if (!text.includes(query)) { row.style.display = 'none'; return; }
        }

        row.style.display = '';
    });
}

function openPatientModal(id, service, doctor, date, time, status) {
    document.getElementById('patientModal').style.display = 'flex';
    document.getElementById('pm_service').innerText  = service;
    document.getElementById('pm_doctor').innerText   = doctor ? 'Dr. ' + doctor : 'Not Assigned';
    document.getElementById('pm_date').innerText     = date;
    document.getElementById('pm_time').innerText     = formatTime(time);
    document.getElementById('pm_status').innerText   = status;
    document.getElementById('pm_appt_id').value      = id;
    document.getElementById('pm_cancel_appt_id').value = id;
    document.getElementById('pm_new_date').value     = date;
    document.getElementById('pm_new_time').value     = time;
    document.getElementById('pm_cancelConfirm').style.display = 'none';

    const canAct = (status === 'pending' || status === 'approved');
    document.getElementById('pm_rescheduleSection').style.display = canAct ? 'block' : 'none';
    document.getElementById('pm_cancelSection').style.display     = canAct ? 'block' : 'none';
    document.getElementById('pm_noActionMsg').textContent         = canAct
        ? '' : 'Actions are only available for pending or approved appointments.';
}

function closePatientModal() {
    document.getElementById('pm_cancelConfirm').style.display = 'none';
    document.getElementById('patientModal').style.display     = 'none';
}

function openPatientCancelConfirm() {
    document.getElementById('pm_cancelConfirm').style.display = 'flex';
}

function closePatientCancelConfirm() {
    document.getElementById('pm_cancelConfirm').style.display = 'none';
}

function formatTime(t) {
    if (!t) return '';
    const [h, m] = t.split(':');
    const hr = parseInt(h);
    return (hr % 12 || 12) + ':' + m + ' ' + (hr < 12 ? 'AM' : 'PM');
}

// ── Waitlist Panel ────────────────────────────────────────
async function toggleWaitlistPanel(btn) {
    const panel = document.getElementById('waitlistPanel');
    const isOpen = panel.style.display !== 'none';

    if (isOpen) {
        panel.style.display = 'none';
        btn.style.background = '#fff';
        btn.style.color = '#f59e0b';
    } else {
        panel.style.display = 'block';
        btn.style.background = '#f59e0b';
        btn.style.color = '#fff';
        loadWaitlist();
    }
}

async function loadWaitlist() {
    const container = document.getElementById('waitlistEntries');
    container.innerHTML = '<p style="color:#999;font-size:13px;">Loading...</p>';

    try {
        const res  = await fetch('{{ route("waitlist.mine") }}');
        const data = await res.json();

        if (!data.entries || !data.entries.length) {
            container.innerHTML = '<p style="color:#999;font-size:13px;padding:16px 0;">You have no active waitlist entries.</p>';
            return;
        }

        container.innerHTML = data.entries.map(e => {
            const time    = formatTime(e.preferred_time);
            const date    = e.preferred_date;

            const expiresHtml = e.claim_expires_at
                ? `<span style="color:#ef4444;font-size:11px;display:block;margin-top:2px;">
                       ⏰ Claim by ${new Date(e.claim_expires_at).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}
                   </span>`
                : '';

            const statusBadge = e.status === 'notified'
    ? `<a href="/waitlist/claim/${e.claim_token}"
          style="background:#fef9c3;color:#854d0e;padding:3px 10px;
                 border-radius:10px;font-size:11px;font-weight:600;
                 text-decoration:none;display:inline-block;cursor:pointer;
                 border:1px solid #fde68a;">
           🔔 SLOT OPEN — Click to Claim
       </a>`
    : `<span style="background:#e5e7eb;color:#666;padding:3px 10px;
                   border-radius:10px;font-size:11px;">
           #${e.queue_position} in queue
       </span>`;

            return `
            <div style="background:#fff;border:1px solid #f0f0ee;border-radius:10px;
                        padding:14px 16px;margin-bottom:10px;display:flex;
                        align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div>
                    <p style="font-weight:600;font-size:13.5px;margin:0 0 3px;color:#1a1f16;">
                        ${e.service_name}
                    </p>
                    <p style="font-size:12px;color:#888;margin:0;">
                        ${date} at ${time}
                    </p>
                    ${expiresHtml}
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    ${statusBadge}
                    <button onclick="leaveWaitlist(${e.waitlist_id}, this)"
                            style="padding:5px 12px;background:#fee2e2;color:#991b1b;
                                   border:1px solid #fca5a5;border-radius:6px;
                                   font-size:12px;cursor:pointer;font-family:inherit;">
                        Remove
                    </button>
                </div>
            </div>`;
        }).join('');

    } catch (err) {
        container.innerHTML = '<p style="color:#ef4444;font-size:13px;">Failed to load waitlist. Please try again.</p>';
    }
}

async function leaveWaitlist(id, btn) {
    if (!confirm('Remove yourself from this waitlist?')) return;

    btn.disabled    = true;
    btn.textContent = '...';

    try {
        const res  = await fetch('{{ route("waitlist.leave") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ waitlist_id: id }),
        });
        const data = await res.json();
        if (data.success) loadWaitlist();
        else { btn.disabled = false; btn.textContent = 'Remove'; }
    } catch (err) {
        btn.disabled = false;
        btn.textContent = 'Remove';
    }
}

window.onclick = e => {
    if (e.target === document.getElementById('patientModal')) closePatientModal();
};

window.addEventListener('DOMContentLoaded', function () {
    // Initialise "All Time" range button as active
    const allTimeBtn = document.querySelector('.range-btn[data-range="all"]');
    if (allTimeBtn) allTimeBtn.classList.add('active');

    applyFilters();

    const params = new URLSearchParams(window.location.search);
    const openId = params.get('open');
    if (openId) {
        const row = document.querySelector(`#bookingsTable tbody tr[data-id="${openId}"]`);
        if (row) row.click();
    }
});
</script>
@endpush