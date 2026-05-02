/* ═══════════════════════════════════════════════════
   POPUP OPEN / CLOSE
═══════════════════════════════════════════════════ */
function closePopup() {
  document.getElementById('loginPopup').style.display = 'none';
  document.getElementById('loginError').textContent = '';
}
function openPopup() {
  document.getElementById('loginPopup').style.display = 'flex';
}

function closeAdminPopup() {
  document.getElementById('adminPopup').style.display = 'none';
  document.getElementById('adminError').textContent = '';
}
function openAdminPopup() {
  closePopup();
  document.getElementById('adminPopup').style.display = 'flex';
}

function closeSignupPopup() {
  document.getElementById('signupPopup').style.display = 'none';
  document.getElementById('signupError').textContent = '';
}
function openSignupPopup() {
  closePopup();
  document.getElementById('signupPopup').style.display = 'flex';
}

/* ═══════════════════════════════════════════════════
   SHOW / HIDE PASSWORD
═══════════════════════════════════════════════════ */
function togglePw(id, btn) {
  const inp  = document.getElementById(id);
  const show = inp.type === 'password';
  inp.type       = show ? 'text' : 'password';
  btn.textContent = show ? '🙈' : '👁';
}

/* ═══════════════════════════════════════════════════
   PASSWORD STRENGTH (Signup)
═══════════════════════════════════════════════════ */
function checkStrength(pw) {
  const rules = {
    len: pw.length >= 8,
    up:  /[A-Z]/.test(pw),
    lo:  /[a-z]/.test(pw),
    num: /[0-9]/.test(pw),
    sp:  /[\W_]/.test(pw),
  };
  const map = { len: 'req-len', up: 'req-up', lo: 'req-lo', num: 'req-num', sp: 'req-sp' };

  Object.entries(rules).forEach(([k, v]) => {
    const li = document.getElementById(map[k]);
    li.classList.toggle('ok', v);
    li.querySelector('.ci').textContent = v ? '✓' : '○';
  });

  const score  = Object.values(rules).filter(Boolean).length;
  const colors = ['#ef4444', '#f97316', '#eab308', '#80a833', '#22c55e'];
  const labels = ['Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
  const fill   = document.getElementById('sFill');
  const label  = document.getElementById('sLabel');

  fill.style.width      = (score * 20) + '%';
  fill.style.background = colors[score - 1] || '#e5e7eb';
  label.textContent     = pw ? (labels[score - 1] || '') : '';
  label.style.color     = colors[score - 1] || '#888';

  checkMatch();
}

/* ═══════════════════════════════════════════════════
   PASSWORD MATCH (Signup)
═══════════════════════════════════════════════════ */
function checkMatch() {
  const pw  = document.getElementById('signup_password').value;
  const cpw = document.getElementById('confirm_password').value;
  const el  = document.getElementById('matchMsg');

  if (!cpw) { el.textContent = ''; return; }
  if (pw === cpw) {
    el.textContent = '✓ Passwords match';
    el.className   = 'match-msg match-ok';
  } else {
    el.textContent = '✗ Passwords do not match';
    el.className   = 'match-msg match-no';
  }
}

/* ═══════════════════════════════════════════════════
   AJAX HELPER
═══════════════════════════════════════════════════ */
function getCsrf() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function doPost(url, formData, errElId, onSuccess) {
  fetch(url, {
    method:  'POST',
    body:    formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': getCsrf(),
    },
  })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        onSuccess(d);
      } else {
        document.getElementById(errElId).textContent = d.error;
      }
    })
    .catch(() => {
      document.getElementById(errElId).textContent = 'Network error. Please try again.';
    });
}

/* ═══════════════════════════════════════════════════
   CLIENT LOGIN
═══════════════════════════════════════════════════ */
document.getElementById('loginForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const fd = new FormData(this);
  doPost('/login', fd, 'loginError', d => { closePopup(); location.href = d.redirect; });
});

/* ═══════════════════════════════════════════════════
   ADMIN LOGIN
═══════════════════════════════════════════════════ */
document.getElementById('adminForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const fd = new FormData(this);
  doPost('/admin-login', fd, 'adminError', d => { closeAdminPopup(); location.href = d.redirect; });
});

/* ═══════════════════════════════════════════════════
   SIGNUP
═══════════════════════════════════════════════════ */
document.getElementById('signupForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const pw  = document.getElementById('signup_password').value;
  const cpw = document.getElementById('confirm_password').value;
  const err = document.getElementById('signupError');
  err.textContent = '';

  if (pw !== cpw)         { err.textContent = 'Passwords do not match.'; return; }
  if (pw.length < 8)      { err.textContent = 'Password must be at least 8 characters.'; return; }
  if (!/[A-Z]/.test(pw)) { err.textContent = 'Password needs an uppercase letter.'; return; }
  if (!/[a-z]/.test(pw)) { err.textContent = 'Password needs a lowercase letter.'; return; }
  if (!/[0-9]/.test(pw)) { err.textContent = 'Password needs a number.'; return; }
  if (!/[\W_]/.test(pw)) { err.textContent = 'Password needs a special character (!@#$…).'; return; }

  const fd = new FormData(this);
  doPost('/signup', fd, 'signupError', d => {
  const err = document.getElementById('signupError');
  err.style.color = 'green';
  err.textContent = '✅ Account created! Please check your email to verify your account.';
  // Close popup after 3 seconds
  setTimeout(() => {
    closeSignupPopup();
    err.style.color = '';
    err.textContent = '';
  }, 3000);
});
});

/* ═══════════════════════════════════════════════════
   AUTO-OPEN POPUP ON URL PARAM
═══════════════════════════════════════════════════ */
if (location.search.includes('login=true'))       openPopup();
else if (location.search.includes('admin=true'))  openAdminPopup();

/* ═══════════════════════════════════════════════════
   FORGOT PASSWORD
═══════════════════════════════════════════════════ */
let fpTimerInterval = null;
let fpCurrentEmail  = '';

function openForgotPopup() {
  document.getElementById('forgotPopup').style.display = 'flex';
  fpGoToStep(1);
}

function closeForgotPopup() {
  document.getElementById('forgotPopup').style.display = 'none';
  clearInterval(fpTimerInterval);
  ['fpError1', 'fpError2', 'fpError3'].forEach(id => {
    document.getElementById(id).textContent = '';
  });
  ['fpSuccess1', 'fpSuccess3'].forEach(id => {
    const el = document.getElementById(id);
    el.style.display = 'none';
    el.textContent   = '';
  });
  document.getElementById('fpForm1').reset();
  document.getElementById('fpForm3').reset();
}

function fpGoToStep(n) {
  [1, 2, 3].forEach(i => {
    document.getElementById('fpStep' + i).classList.toggle('active', i === n);
    document.getElementById('dot'   + i).classList.toggle('active', i <= n);
  });
}

/* Build OTP input boxes */
function fpBuildOtp() {
  const wrap = document.getElementById('fpOtpBoxes');
  wrap.innerHTML = '';
  for (let i = 0; i < 6; i++) {
    const inp = document.createElement('input');
    inp.type       = 'text';
    inp.maxLength  = 1;
    inp.inputMode  = 'numeric';
    inp.setAttribute('pattern', '[0-9]');
    inp.addEventListener('input', function () {
      this.classList.toggle('filled', this.value !== '');
      if (this.value && this.nextElementSibling) this.nextElementSibling.focus();
    });
    inp.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && !this.value && this.previousElementSibling)
        this.previousElementSibling.focus();
    });
    wrap.appendChild(inp);
  }
}

function fpGetOtp() {
  return [...document.querySelectorAll('#fpOtpBoxes input')].map(i => i.value).join('');
}

/* Resend timer */
function fpStartTimer() {
  clearInterval(fpTimerInterval);
  let t    = 60;
  const btn  = document.getElementById('fpResendBtn');
  const span = document.getElementById('fpTimer');
  btn.disabled      = true;
  span.textContent  = t;
  fpTimerInterval = setInterval(() => {
    span.textContent = --t;
    if (t <= 0) { clearInterval(fpTimerInterval); btn.disabled = false; }
  }, 1000);
}

function fpResendOTP() {
  const fd = new FormData();
  fd.append('forgot_password', '1');
  fd.append('fp_email', fpCurrentEmail);
  doPost('/forgot-password', fd, 'fpError2', () => {
    fpStartTimer();
    document.getElementById('fpError2').textContent = '';
  });
}

/* Step 1: Send OTP */
document.getElementById('fpForm1').addEventListener('submit', function (e) {
  e.preventDefault();
  fpCurrentEmail = document.getElementById('fpEmail').value.trim();
  const fd = new FormData();
  fd.append('forgot_password', '1');
  fd.append('fp_email', fpCurrentEmail);
  doPost('/forgot-password', fd, 'fpError1', d => {
    const suc = document.getElementById('fpSuccess1');
    suc.textContent  = d.message || 'OTP sent! Check your inbox.';
    suc.style.display = 'block';
    setTimeout(() => {
      document.getElementById('fpEmailDisplay').textContent = fpCurrentEmail;
      fpBuildOtp();
      fpStartTimer();
      fpGoToStep(2);
    }, 800);
  });
});

/* Step 2: Verify OTP */
document.getElementById('fpForm2').addEventListener('submit', function (e) {
  e.preventDefault();
  const otp = fpGetOtp();
  if (otp.length < 6) {
    document.getElementById('fpError2').textContent = 'Please enter all 6 digits.';
    return;
  }
  const fd = new FormData();
  fd.append('verify_reset_otp', '1');
  fd.append('otp', otp);
  doPost('/verify-otp', fd, 'fpError2', () => {
    clearInterval(fpTimerInterval);
    fpGoToStep(3);
  });
});

/* Step 3: Reset Password */
document.getElementById('fpForm3').addEventListener('submit', function (e) {
  e.preventDefault();
  const pw  = document.getElementById('fpNewPw').value;
  const cpw = document.getElementById('fpConfirmPw').value;
  const err = document.getElementById('fpError3');
  err.textContent = '';

  if (pw !== cpw)         { err.textContent = 'Passwords do not match.'; return; }
  if (pw.length < 8)      { err.textContent = 'Password must be at least 8 characters.'; return; }
  if (!/[A-Z]/.test(pw)) { err.textContent = 'Password needs an uppercase letter.'; return; }
  if (!/[a-z]/.test(pw)) { err.textContent = 'Password needs a lowercase letter.'; return; }
  if (!/[0-9]/.test(pw)) { err.textContent = 'Password needs a number.'; return; }
  if (!/[\W_]/.test(pw)) { err.textContent = 'Password needs a special character.'; return; }

  const fd = new FormData();
  fd.append('reset_password', '1');
  fd.append('password', pw);
  fd.append('confirm_password', cpw);
  doPost('/reset-password', fd, 'fpError3', d => {
    const suc = document.getElementById('fpSuccess3');
    suc.textContent   = d.message || '✓ Password reset! You can now log in.';
    suc.style.display = 'block';
    document.getElementById('fpForm3').style.display = 'none';
    setTimeout(() => { closeForgotPopup(); openPopup(); }, 2200);
  });
});

/* Strength checker for Forgot Password step 3 */
function checkStrengthFp(pw) {
  const rules = {
    len: pw.length >= 8,
    up:  /[A-Z]/.test(pw),
    lo:  /[a-z]/.test(pw),
    num: /[0-9]/.test(pw),
    sp:  /[\W_]/.test(pw),
  };
  const map = { len: 'fp-req-len', up: 'fp-req-up', lo: 'fp-req-lo', num: 'fp-req-num', sp: 'fp-req-sp' };

  Object.entries(rules).forEach(([k, v]) => {
    const li = document.getElementById(map[k]);
    li.classList.toggle('ok', v);
    li.querySelector('.ci').textContent = v ? '✓' : '○';
  });

  const score  = Object.values(rules).filter(Boolean).length;
  const colors = ['#ef4444', '#f97316', '#eab308', '#80a833', '#22c55e'];
  const labels = ['Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];

  document.getElementById('fpSFill').style.width       = (score * 20) + '%';
  document.getElementById('fpSFill').style.background  = colors[score - 1] || '#e5e7eb';
  document.getElementById('fpSLabel').textContent      = pw ? (labels[score - 1] || '') : '';

  fpCheckMatch();
}

function fpCheckMatch() {
  const pw  = document.getElementById('fpNewPw').value;
  const cpw = document.getElementById('fpConfirmPw').value;
  const el  = document.getElementById('fpMatchMsg');
  if (!cpw) { el.textContent = ''; return; }
  if (pw === cpw) {
    el.textContent = '✓ Passwords match';
    el.className   = 'match-msg match-ok';
  } else {
    el.textContent = '✗ Passwords do not match';
    el.className   = 'match-msg match-no';
  }
}


  const hamburger = document.getElementById('hamburgerBtn');
  const sidebar   = document.querySelector('.sidebar');
  const overlay   = document.getElementById('sidebarOverlay');

  hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
  });
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
  });