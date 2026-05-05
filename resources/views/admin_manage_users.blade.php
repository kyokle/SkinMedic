{{-- resources/views/admin/admin_manage_users.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Manage Users — SkinMedic</title>
  <link rel="stylesheet" href="{{ asset('asset/css/admin_manage_users.css') }}">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
</head>
<body>

  @if(session('role') === 'admin')
    @include('partials.sidebar_admin')
  @else
    @include('partials.sidebar_staff')
  @endif

  <div class="main">
    <h2>Manage Users</h2>
    <div class="page-sub">View, edit, and manage doctors, staff, and patients</div>

    {{-- Tab Bar --}}
    <div class="tab-bar">
      <a href="?tab=doctor"  class="tab-pill {{ $tab === 'doctor'  ? 'active' : '' }}">👨‍⚕️ Doctors  <span class="count">{{ $counts['doctor'] }}</span></a>
      <a href="?tab=staff"   class="tab-pill {{ $tab === 'staff'   ? 'active' : '' }}">🧑‍💼 Staff   <span class="count">{{ $counts['staff'] }}</span></a>
      <a href="?tab=patient" class="tab-pill {{ $tab === 'patient' ? 'active' : '' }}">🧑 Patients <span class="count">{{ $counts['patient'] }}</span></a>
    </div>

    {{-- Table Card --}}
    <div class="card">
      <div class="card-head">
        <h3>
          @if($tab === 'doctor') 👨‍⚕️ Doctors
          @elseif($tab === 'staff') 🧑‍💼 Staff Members
          @else 🧑 Patients
          @endif
        </h3>
        <input class="search-input" type="text" id="searchInput" placeholder="Search by name or email…" oninput="filterTable()">
      </div>

      {{-- Wrap table in scroll div for mobile --}}
      <div class="table-scroll">
        <table id="usersTable">
          <thead>
            <tr>
              <th>User</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Gender</th>
              @if($tab === 'patient')
                <th>Visits</th>
                <th>Last Visit</th>
                <th>Regular</th>
              @endif
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($users as $u)
              @php
                $img = !empty($u->profile_image)
                  ? asset('uploads/profiles/' . $u->profile_image)
                  : 'https://ui-avatars.com/api/?name=' . urlencode($u->firstName . ' ' . $u->lastName) . '&background=80a833&color=fff&size=64';
              @endphp
              <tr>
                <td>
                  <div class="avatar-cell">
                    <img src="{{ $img }}" class="mini-avatar" alt="">
                    <div>
                      <div style="font-weight:500">{{ $u->firstName }} {{ $u->lastName }}</div>
                      <div class="info-tag">#{{ $u->user_id }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->phone_no ?? '—' }}</td>
                <td>{{ $u->gender ?? '—' }}</td>

                @if($tab === 'patient')
                  <td>{{ $u->total_visits }}</td>
                  <td>{{ $u->last_visit ?? '—' }}</td>
                  <td>
                    @if($u->is_regular)
                      <span class="regular-star">⭐</span>
                      <span class="badge badge-regular">Regular</span>
                      @if($u->preferred_time)
                        <div class="info-tag">Pref: {{ $u->preferred_time }}</div>
                      @endif
                    @else
                      <span style="color:var(--muted);font-size:12px;">—</span>
                    @endif
                  </td>
                @endif

                <td style="white-space:nowrap">
                  <button class="btn btn-edit" onclick='openEdit({{ json_encode($u) }})'>✏ Edit</button>

                  @if($tab === 'patient')
                    <button class="btn btn-pref" onclick="openPref({{ $u->user_id }}, '{{ $u->preferred_time ?? '' }}')">⭐ Preferred Time</button>
                    @if($u->is_regular)
                      <form method="POST" action="{{ url('admin/manage-users/remove-regular') }}" style="display:inline">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $u->user_id }}">
                        <button type="submit" class="btn" style="background:#f3e8ff;color:#6b21a8;font-size:11px"
                                onclick="return confirm('Remove regular status?')">Remove Regular</button>
                      </form>
                    @endif
                  @endif

                  <form method="POST" action="{{ url('admin/manage-users/delete') }}" style="display:inline">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $u->user_id }}">
                    <input type="hidden" name="tab"     value="{{ $tab }}">
                    <button type="submit" class="btn btn-del"
                            onclick="return confirm('Delete this user permanently?')">🗑</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>{{-- end table-scroll --}}

    </div>
  </div>{{-- end .main --}}

  {{-- Edit User Modal --}}
  <div class="modal" id="editModal">
    <div class="modal-box">
      <div class="modal-head">
        <h3>Edit User</h3>
        <button class="modal-close" onclick="closeEdit()">&times;</button>
      </div>
      <form method="POST" action="{{ url('admin/manage-users/update') }}">
        @csrf
        <div class="modal-body">
          <input type="hidden" name="user_id" id="e_id">
          <input type="hidden" name="tab"     value="{{ $tab }}">
          <label>First Name</label>
          <input type="text"  name="firstName" id="e_first" required>
          <label>Last Name</label>
          <input type="text"  name="lastName"  id="e_last"  required>
          <label>Email</label>
          <input type="email" name="email"     id="e_email" required>
          <label>Phone</label>
          <input type="text"  name="phone_no"  id="e_phone">
          <label>Gender</label>
          <select name="gender" id="e_gender">
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="others">Others</option>
            <option value="Not specified">Not specified</option>
          </select>
          <label>Role</label>
          <select name="role" id="e_role">
            <option value="doctor">Doctor</option>
            <option value="staff">Staff</option>
            <option value="patient">Patient</option>
          </select>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
          <button type="submit" class="btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Preferred Time Modal --}}
  <div class="modal" id="prefModal">
    <div class="modal-box">
      <div class="modal-head">
        <h3>⭐ Set Regular Customer Preferred Time</h3>
        <button class="modal-close" onclick="closePref()">&times;</button>
      </div>
      <form method="POST" action="{{ url('admin/manage-users/set-preferred-time') }}">
        @csrf
        <div class="modal-body">
          <input type="hidden" name="user_id" id="pref_uid">
          <p style="font-size:13px;color:var(--muted);margin-bottom:12px;">
            Setting a preferred time marks this patient as a <strong>Regular Customer</strong>.
            Their preferred time slot will be automatically pre-filled when booking.
          </p>
          <label>Preferred Appointment Time</label>
          <input type="time" name="preferred_time" id="pref_time" required>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn-cancel" onclick="closePref()">Cancel</button>
          <button type="submit" class="btn-primary">Save & Mark Regular</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function openEdit(u) {
    document.getElementById('e_id').value     = u.user_id;
    document.getElementById('e_first').value  = u.firstName;
    document.getElementById('e_last').value   = u.lastName;
    document.getElementById('e_email').value  = u.email;
    document.getElementById('e_phone').value  = u.phone_no  || '';
    document.getElementById('e_gender').value = u.gender    || 'Not specified';
    document.getElementById('e_role').value   = u.role;
    document.getElementById('editModal').classList.add('open');
  }
  function closeEdit() { document.getElementById('editModal').classList.remove('open'); }

  function openPref(uid, time) {
    document.getElementById('pref_uid').value  = uid;
    document.getElementById('pref_time').value = time || '';
    document.getElementById('prefModal').classList.add('open');
  }
  function closePref() { document.getElementById('prefModal').classList.remove('open'); }

  document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
  });

  function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
      row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
  }
  </script>
</body>
</html>