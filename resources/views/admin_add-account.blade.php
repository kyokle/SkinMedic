{{-- resources/views/admin_add-account.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Account — SkinMedic</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('asset/css/admin_add_account.css') }}">
</head>
<body>

@if(session('role') === 'admin')
    @include('partials.sidebar_admin')
@else
    @include('partials.sidebar_staff')
@endif

<div class="main">
    <div class="page-title">Create Account</div>
    <div class="page-sub">Register a new doctor, staff member, or patient</div>

    {{-- Success / error alerts --}}
    @if(session('success'))
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <div>{!! session('success') !!}</div>
        </div>
    @endif

    @if(session('errors'))
        <div class="alert alert-error">
            <span class="alert-icon">⚠</span>
            <div>
                @foreach(session('errors') as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="create-layout">

        {{-- ── Main form card ── --}}
        <div class="card">
            <div class="card-head">
                <div class="card-head-icon">👤</div>
                <div><h3>Account Details</h3><p>Fill in the information below</p></div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.add-account.store') }}" method="POST" id="createForm">
                    @csrf

                    <div class="section-divider">👤 Personal Information</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="firstname" placeholder="e.g. Maria" required
                                   value="{{ old('firstname') }}">
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="lastname" placeholder="e.g. Santos" required
                                   value="{{ old('lastname') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="Not specified" {{ old('gender') === 'Not specified' ? 'selected' : '' }}>Prefer not to say</option>
                                <option value="male"          {{ old('gender') === 'male'          ? 'selected' : '' }}>Male</option>
                                <option value="female"        {{ old('gender') === 'female'        ? 'selected' : '' }}>Female</option>
                                <option value="others"        {{ old('gender') === 'others'        ? 'selected' : '' }}>Others</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone_no" placeholder="09XX XXX XXXX"
                                   value="{{ old('phone_no') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" placeholder="Street, City, Province"
                               value="{{ old('address') }}">
                    </div>

                    <div class="section-divider" style="margin-top:8px;">🔐 Login Credentials</div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" placeholder="e.g. maria@clinic.com" required
                               value="{{ old('email') }}">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password *</label>
                            <div class="pw-wrap">
                                <input type="password" name="password" id="pw"
                                       placeholder="Create password" required oninput="checkPw()">
                                <button type="button" class="pw-toggle" onclick="togglePw('pw',this)">👁</button>
                            </div>
                            <div class="strength-bar"><div class="strength-fill" id="sFill"></div></div>
                            <div class="strength-label" id="sLabel">—</div>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password *</label>
                            <div class="pw-wrap">
                                <input type="password" name="confirm_password" id="cpw"
                                       placeholder="Repeat password" required oninput="checkMatch()">
                                <button type="button" class="pw-toggle" onclick="togglePw('cpw',this)">👁</button>
                            </div>
                            <div class="strength-label" id="mLabel"></div>
                        </div>
                    </div>
                    <ul class="pw-checklist">
                        <li id="c-len"><span class="chk">○</span> 8+ characters</li>
                        <li id="c-up"><span class="chk">○</span> Uppercase (A–Z)</li>
                        <li id="c-lo"><span class="chk">○</span> Lowercase (a–z)</li>
                        <li id="c-nu"><span class="chk">○</span> Number (0–9)</li>
                        <li id="c-sp"><span class="chk">○</span> Special char (!@#…)</li>
                    </ul>

                    <div class="section-divider" style="margin-top:22px;">🏷 Select Role *</div>
                    <div class="role-grid">
                        <label class="role-card {{ old('role') === 'admin'  ? 'selected' : '' }}" onclick="pickRole(this)">
                            <input type="radio" name="role" value="admin"  {{ old('role') === 'admin'  ? 'checked' : '' }}>
                            <div class="role-icon">👑</div><div class="role-name">Admin</div>
                        </label>
                        <label class="role-card {{ old('role') === 'staff'  ? 'selected' : '' }}" onclick="pickRole(this)">
                            <input type="radio" name="role" value="staff"  {{ old('role') === 'staff'  ? 'checked' : '' }}>
                            <div class="role-icon">🧑‍💼</div><div class="role-name">Staff</div>
                        </label>
                        <label class="role-card {{ old('role') === 'doctor' ? 'selected' : '' }}" onclick="pickRole(this)">
                            <input type="radio" name="role" value="doctor" {{ old('role') === 'doctor' ? 'checked' : '' }}>
                            <div class="role-icon">👨‍⚕️</div><div class="role-name">Doctor</div>
                        </label>
                    </div>

                    <div id="fErr" style="color:#ef4444;font-size:13px;margin-top:12px;min-height:15px;"></div>
                    <button type="submit" class="submit-btn" style="margin-top:20px;">➕ Create Account</button>
                </form>
            </div>
        </div>

        {{-- ── Sidebar info ── --}}
        <div>
            <div class="info-card">
                <div class="info-card-head" style="background:var(--accent);color:#fff;">
                    <h3>Role Permissions</h3>
                    <p>What each role can do</p>
                </div>
                <div class="info-card-body">
                    <div class="info-rule"><span class="info-rule-icon">👑</span><div class="info-rule-text"><strong>Admin</strong>Full system access — users, reports, all modules.</div></div>
                    <div class="info-rule"><span class="info-rule-icon">🧑‍💼</span><div class="info-rule-text"><strong>Staff</strong>Manage bookings, services, and products.</div></div>
                    <div class="info-rule"><span class="info-rule-icon">👨‍⚕️</span><div class="info-rule-text"><strong>Doctor</strong>View and complete assigned appointments.</div></div>
                </div>
            </div>
            <div class="info-card">
                <div class="info-card-head" style="background:#3b82f6;color:#fff;">
                    <h3>Password Rules</h3>
                    <p>Must meet all requirements</p>
                </div>
                <div class="info-card-body">
                    <div class="info-rule"><span class="info-rule-icon">🔒</span><div class="info-rule-text">Minimum 8 characters</div></div>
                    <div class="info-rule"><span class="info-rule-icon">🔠</span><div class="info-rule-text">Uppercase AND lowercase letters</div></div>
                    <div class="info-rule"><span class="info-rule-icon">🔢</span><div class="info-rule-text">At least one number</div></div>
                    <div class="info-rule"><span class="info-rule-icon">✳️</span><div class="info-rule-text">At least one special character</div></div>
                </div>
            </div>
            <div class="card">
                <div style="padding:16px 20px;">
                    <div style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px;">Quick Links</div>
                    <a href="{{ route('admin.manage-users', ['tab' => 'doctor'])  }}" style="display:flex;align-items:center;gap:8px;padding:9px 0;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);font-size:13px;">👨‍⚕️ Manage Doctors</a>
                    <a href="{{ route('admin.manage-users', ['tab' => 'staff'])   }}" style="display:flex;align-items:center;gap:8px;padding:9px 0;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);font-size:13px;">🧑‍💼 Manage Staff</a>
                    <a href="{{ route('admin.manage-users', ['tab' => 'patient']) }}" style="display:flex;align-items:center;gap:8px;padding:9px 0;text-decoration:none;color:var(--text);font-size:13px;">🧑 Manage Patients</a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function pickRole(card) {
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    card.querySelector('input').checked = true;
}
function togglePw(id, btn) {
    const i = document.getElementById(id);
    i.type = i.type === 'password' ? 'text' : 'password';
    btn.textContent = i.type === 'password' ? '👁' : '🙈';
}
function checkPw() {
    const pw  = document.getElementById('pw').value;
    const c   = { len: pw.length >= 8, up: /[A-Z]/.test(pw), lo: /[a-z]/.test(pw), nu: /[0-9]/.test(pw), sp: /[\W_]/.test(pw) };
    const sc  = Object.values(c).filter(Boolean).length;
    const cols = ['#ef4444','#f97316','#eab308','#80a833','#22c55e'];
    const labs = ['Very Weak','Weak','Fair','Strong','Very Strong'];
    document.getElementById('sFill').style.width      = (sc * 20) + '%';
    document.getElementById('sFill').style.background = cols[sc - 1] || '#e8e6e0';
    const sl = document.getElementById('sLabel');
    sl.textContent = pw ? (labs[sc - 1] || '') : '—';
    sl.style.color = cols[sc - 1] || 'var(--muted)';
    const map = { len:'c-len', up:'c-up', lo:'c-lo', nu:'c-nu', sp:'c-sp' };
    Object.entries(c).forEach(([k, v]) => {
        const el = document.getElementById(map[k]);
        el.classList.toggle('ok', v);
        el.querySelector('.chk').textContent = v ? '✓' : '○';
    });
    checkMatch();
}
function checkMatch() {
    const pw  = document.getElementById('pw').value;
    const cpw = document.getElementById('cpw').value;
    const ml  = document.getElementById('mLabel');
    if (!cpw) { ml.textContent = ''; return; }
    if (pw === cpw) { ml.textContent = '✓ Match';    ml.style.color = 'var(--accent)'; }
    else            { ml.textContent = '✗ No match'; ml.style.color = '#ef4444'; }
}
document.getElementById('createForm').addEventListener('submit', function (e) {
    const pw   = document.getElementById('pw').value;
    const cpw  = document.getElementById('cpw').value;
    const role = document.querySelector('input[name="role"]:checked');
    const err  = document.getElementById('fErr');
    err.textContent = '';
    if (!role) { e.preventDefault(); err.textContent = 'Please select a role.'; return; }
    if (pw !== cpw) { e.preventDefault(); err.textContent = 'Passwords do not match.'; return; }
    if (pw.length < 8 || !/[A-Z]/.test(pw) || !/[a-z]/.test(pw) || !/[0-9]/.test(pw) || !/[\W_]/.test(pw)) {
        e.preventDefault(); err.textContent = 'Password does not meet all requirements.';
    }
});
</script>

</body>
</html>