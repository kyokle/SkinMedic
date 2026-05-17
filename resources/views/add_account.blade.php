{{-- This page has no HTML in the original file. --}}
{{-- Create your form pointing to the route below: --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
<form method="POST" action="{{ route('account.store') }}">
    @csrf

    @if(session('signup_error'))
        <div class="alert alert-danger">{{ session('signup_error') }}</div>
    @endif

    <input type="text"     name="firstname"        placeholder="First Name" required>
    <input type="text"     name="lastname"         placeholder="Last Name" required>
    <input type="email"    name="email"            placeholder="Email" required>
    <input type="password" name="password"         placeholder="Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <input type="text" name="phone_no" id="phone_no"
           placeholder="Phone Number (e.g. 09XXXXXXXXX)"
           maxlength="11"
           pattern="\d{11}"
           inputmode="numeric"
           oninput="this.value=this.value.replace(/\D/g,'')"
           title="Phone number must be exactly 11 digits">
    <span id="phone_error" style="color:#ef4444;font-size:0.8rem;display:none;">Phone number must be exactly 11 digits.</span>
    <input type="text"     name="address"          placeholder="Address">

    <select name="gender">
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Not specified">Prefer not to say</option>
    </select>

    <select name="role">
        <option value="patient">Patient</option>
        <option value="staff">Staff</option>
        <option value="doctor">Doctor</option>
    </select>

    <button type="submit" name="signup">Create Account</button>
</form>

<script>
document.querySelector('form').addEventListener('submit', function (e) {
    const phone = document.getElementById('phone_no');
    const err   = document.getElementById('phone_error');
    const val   = phone.value.trim();

    if (val !== '' && val.length !== 11) {
        e.preventDefault();
        err.style.display = 'block';
        phone.focus();
    } else {
        err.style.display = 'none';
    }
});

document.getElementById('phone_no').addEventListener('input', function () {
    const err = document.getElementById('phone_error');
    if (this.value.length === 11 || this.value.length === 0) {
        err.style.display = 'none';
    }
});
</script>