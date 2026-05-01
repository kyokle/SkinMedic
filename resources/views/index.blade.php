<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Skin Medic — Home</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('asset/css/index.css') }}">
</head>
<body>

<!-- Hamburger button -->
<button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
  <span></span><span></span><span></span>
</button>

<!-- Overlay backdrop -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- your existing .sidebar ... -->

  <!-- ═══════════════════ SIDEBAR ═══════════════════ -->
  <aside class="sidebar">
    <div class="logo-wrap">
      <img src="{{ asset('asset/image/skintransparent.png') }}" ...>
    </div>

    <nav class="menu">
      <a href="#book-appointment">Book Appointment</a>
      <a href="#ar-skin-analysis">AR Skin Analysis</a>
      <a href="#treatments">Treatment and Services</a>
      <a href="#shop">Products</a>
      <a href="#reviews">Reviews</a>
      <a href="#locations">Location</a>
    </nav>
  </aside>

  <!-- ═══════════════════ MAIN CONTENT ═══════════════════ -->
  <main class="main-content">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="top-actions"></div>
      <a class="login-pill" href="/?login=true">Log in / Sign up</a>
    </header>

    <!-- HERO -->
    <section id="book-appointment" class="hero">
      <h4 class="pre">Welcome to</h4>
      <h1 class="title">Skin Medic</h1>
      <p class="subtitle">Your journey to radiant, healthy skin begins here</p>

      <div class="hero-cta">
        <a class="login-pill" href="/?login=true">Book a Session</a>
        <a class="cta cta-secondary" href="{{ route('skin-analysis.index') }}">AR Skin Analysis</a>
      </div>
    </section>

    <!-- ═══════════════════ LOGIN POPUP ═══════════════════ -->
    <div id="loginPopup" class="popup">
      <div class="popup-content">
        <div class="popup-left">
          <img src="{{ asset('asset/image/skintransparent.png') }}" alt="Skin Medic Logo" class="logo" style="width:80px; height:auto;">
          <h2>Skin Medic</h2>
          <p>A Complete Skin Care Clinic</p>
        </div>
        <div class="popup-right">
          <span class="close" onclick="closePopup()">&times;</span>
          <h3>Login</h3>
          <div id="loginError" style="color:red;margin-bottom:10px;font-size:13px;"></div>
          <form id="loginForm">
            <div class="input-group">
              <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group pw-field">
              <input type="password" name="password" id="loginPw" placeholder="Password" required>
              <button type="button" class="pw-toggle" onclick="togglePw('loginPw',this)">👁</button>
            </div>
           <button type="submit" class="login-btn">Login</button>
<a href="#" class="create-link" onclick="openSignupPopup()">Create an Account</a>
<div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
    <a href="#" style="color:#888;font-size:12px;text-decoration:none;" onclick="closePopup();openForgotPopup();">Forgot Password?</a>
    <button class="admin-btn" onclick="openAdminPopup()" style="position:static;">Are you an admin?</button>
</div>
</form>
        </div>
      </div>
    </div>

    <!-- ═══════════════════ ADMIN LOGIN POPUP ═══════════════════ -->
    <div id="adminPopup" class="popup">
      <div class="popup-content">
        <div class="popup-left">
          <img src="{{ asset('asset/image/skintransparent.png') }}" alt="Skin Medic Logo" class="logo" style="width:80px; height:auto;">
          <h2>Skin Medic</h2>
          <p>Admin Access</p>
        </div>
        <div class="popup-right">
          <span class="close" onclick="closeAdminPopup()">&times;</span>
          <h3>Admin Login</h3>
          <div id="adminError" style="color:red;margin-bottom:10px;font-size:13px;"></div>
          <form id="adminForm">
            <div class="input-group">
              <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group pw-field">
              <input type="password" name="password" id="adminPw" placeholder="Password" required>
              <button type="button" class="pw-toggle" onclick="togglePw('adminPw',this)">👁</button>
            </div>
            <button type="submit" class="login-btn">Login</button>
          </form>
          <button class="back-btn" onclick="closeAdminPopup(); openPopup();">← Back to Client Login</button>
        </div>
      </div>
    </div>

    <!-- ═══════════════════ FORGOT PASSWORD POPUP ═══════════════════ -->
    <div id="forgotPopup" class="popup">
      <div class="popup-content">
        <div class="popup-left">
          <img src="{{ asset('asset/image/skintransparent.png') }}" alt="Skin Medic Logo" class="logo" style="width:80px; height:auto;">
          <h2>Skin Medic</h2>
          <p>Password Recovery</p>
        </div>
        <div class="popup-right">
          <span class="close" onclick="closeForgotPopup()">&times;</span>

          <!-- Step dots -->
          <div class="step-dots">
            <div class="step-dot active" id="dot1"></div>
            <div class="step-dot" id="dot2"></div>
            <div class="step-dot" id="dot3"></div>
          </div>

          <!-- Step 1: Enter Email -->
          <div class="fp-step active" id="fpStep1">
            <h3 style="margin-bottom:6px;">Forgot Password?</h3>
            <p style="font-size:13px;color:#777;margin-bottom:16px;">Enter your registered email and we'll send you a 6-digit reset code.</p>
            <div id="fpError1" style="color:red;font-size:13px;margin-bottom:10px;"></div>
            <div class="fp-success" id="fpSuccess1"></div>
            <form id="fpForm1">
              <div class="input-group">
                <input type="email" name="fp_email" id="fpEmail" placeholder="your@email.com" required>
              </div>
              <button type="submit" class="login-btn">Send Reset Code</button>
            </form>
            <p style="text-align:center;font-size:12.5px;margin-top:12px;">
              <a href="#" onclick="closeForgotPopup();openPopup();" style="color:#80a833;font-weight:600;text-decoration:none;">← Back to Login</a>
            </p>
          </div>

          <!-- Step 2: Enter OTP -->
          <div class="fp-step" id="fpStep2">
            <h3 style="margin-bottom:6px;">Enter Reset Code</h3>
            <p style="font-size:13px;color:#777;margin-bottom:4px;">A 6-digit code was sent to <strong id="fpEmailDisplay"></strong></p>
            <div id="fpError2" style="color:red;font-size:13px;margin-bottom:10px;"></div>
            <form id="fpForm2">
              <div class="otp-inputs" id="fpOtpBoxes"></div>
              <div class="resend-row">
                Didn't receive it?
                <button type="button" id="fpResendBtn" onclick="fpResendOTP()" disabled>
                  Resend (<span id="fpTimer">60</span>s)
                </button>
              </div>
              <button type="submit" class="login-btn" style="margin-top:14px;">Verify Code</button>
            </form>
            <p style="text-align:center;font-size:12.5px;margin-top:12px;">
              <a href="#" onclick="fpGoToStep(1);" style="color:#80a833;font-weight:600;text-decoration:none;">← Back</a>
            </p>
          </div>

          <!-- Step 3: New Password -->
          <div class="fp-step" id="fpStep3">
            <h3 style="margin-bottom:6px;">Set New Password</h3>
            <p style="font-size:13px;color:#777;margin-bottom:14px;">Choose a strong password for your account.</p>
            <div id="fpError3" style="color:red;font-size:13px;margin-bottom:10px;"></div>
            <div class="fp-success" id="fpSuccess3"></div>
            <form id="fpForm3">
              <div class="sg-group" style="margin-bottom:10px;">
                <label>New Password</label>
                <div class="pw-field">
                  <input type="password" name="new_password" id="fpNewPw"
                         placeholder="New password" required
                         oninput="checkStrengthFp(this.value)">
                  <button type="button" class="pw-toggle" onclick="togglePw('fpNewPw',this)">👁</button>
                </div>
                <div class="strength-wrap">
                  <div class="strength-bar"><div class="strength-fill" id="fpSFill"></div></div>
                  <div class="strength-label" id="fpSLabel"></div>
                </div>
                <ul class="pw-checklist" style="margin-top:6px;">
                  <li id="fp-req-len"><span class="ci">○</span> 8+ characters</li>
                  <li id="fp-req-up"><span class="ci">○</span> Uppercase (A–Z)</li>
                  <li id="fp-req-lo"><span class="ci">○</span> Lowercase (a–z)</li>
                  <li id="fp-req-num"><span class="ci">○</span> Number (0–9)</li>
                  <li id="fp-req-sp"><span class="ci">○</span> Special (!@#$…)</li>
                </ul>
              </div>
              <div class="sg-group" style="margin-bottom:14px;">
                <label>Confirm New Password</label>
                <div class="pw-field">
                  <input type="password" name="confirm_new_password" id="fpConfirmPw"
                         placeholder="Confirm new password" required
                         oninput="fpCheckMatch()">
                  <button type="button" class="pw-toggle" onclick="togglePw('fpConfirmPw',this)">👁</button>
                </div>
                <div class="match-msg" id="fpMatchMsg"></div>
              </div>
              <button type="submit" class="login-btn">Reset Password</button>
            </form>
          </div>

        </div>
      </div>
    </div>

    <!-- ═══════════════════ SIGNUP POPUP ═══════════════════ -->
    <div id="signupPopup" class="popup">
      <div class="popup-content">
        <div class="popup-left">
          <img src="{{ asset('asset/image/skintransparent.png') }}" alt="Skin Medic Logo" class="logo" style="width:80px; height:auto;">
          <h2>Skin Medic</h2>
          <p>Join Our Community</p>
        </div>
        <div class="popup-right">
          <span class="close" onclick="closeSignupPopup()">&times;</span>
          <h3 style="margin-bottom:4px;">Create Account</h3>
          <p style="font-size:12px;color:#888;margin-bottom:14px;">Fill in your details to get started</p>
          <div id="signupError" style="color:red;margin-bottom:10px;font-size:13px;"></div>

          <form id="signupForm">
            <div class="signup-grid">

              <div class="signup-divider">👤 Personal Information</div>

              <div class="sg-group">
                <label>First Name *</label>
                <input type="text" name="firstname" placeholder="e.g. Maria" required>
              </div>
              <div class="sg-group">
                <label>Last Name *</label>
                <input type="text" name="lastname" placeholder="e.g. Santos" required>
              </div>
              <div class="sg-group">
                <label>Email Address *</label>
                <input type="email" name="email" placeholder="email@example.com" required>
              </div>
              <div class="sg-group">
                <label>Gender *</label>
                <select name="gender" required>
                  <option value="">Select gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="others">Others</option>
                </select>
              </div>
              <div class="sg-group">
                <label>Phone Number</label>
                <input type="text" name="phone_no" placeholder="09XXXXXXXXX">
              </div>
              <div class="sg-group">
                <label>Address</label>
                <input type="text" name="address" placeholder="City, Province">
              </div>

              <div class="signup-divider">🔒 Set Password</div>

              <div class="sg-group full">
                <label>Password *</label>
                <div class="pw-field">
                  <input type="password" name="password" id="signup_password"
                         placeholder="Create a strong password" required
                         oninput="checkStrength(this.value)">
                  <button type="button" class="pw-toggle" onclick="togglePw('signup_password',this)">👁</button>
                </div>
                <div class="strength-wrap">
                  <div class="strength-bar"><div class="strength-fill" id="sFill"></div></div>
                  <div class="strength-label" id="sLabel"></div>
                </div>
                <ul class="pw-checklist">
                  <li id="req-len"><span class="ci">○</span> 8+ characters</li>
                  <li id="req-up"><span class="ci">○</span> Uppercase (A–Z)</li>
                  <li id="req-lo"><span class="ci">○</span> Lowercase (a–z)</li>
                  <li id="req-num"><span class="ci">○</span> Number (0–9)</li>
                  <li id="req-sp"><span class="ci">○</span> Special (!@#$…)</li>
                </ul>
              </div>

              <div class="sg-group full">
                <label>Confirm Password *</label>
                <div class="pw-field">
                  <input type="password" name="password_confirmation" id="confirm_password"
                         placeholder="Re-enter your password" required
                         oninput="checkMatch()">
                  <button type="button" class="pw-toggle" onclick="togglePw('confirm_password',this)">👁</button>
                </div>
                <div class="match-msg" id="matchMsg"></div>
              </div>

              <button type="submit" class="sg-submit">Create Account →</button>
            </div>
          </form>

          <p style="text-align:center;font-size:12.5px;margin-top:14px;color:#666;">
            Already have an account?
            <a href="#" onclick="closeSignupPopup();openPopup();"
               style="color:#80a833;font-weight:600;text-decoration:none;">Log in</a>
          </p>
        </div>
      </div>
    </div>

    <!-- ═══════════════════ AR CARD ═══════════════════ -->
    <section id="ar-skin-analysis" class="ar-card">
      <div class="ar-left">
        <h2>AR Skin Analysis</h2>
        <p class="lead">
          Experience our advanced AR skin analysis technology. Get instant insights about your skin type,
          concerns, and personalized treatment recommendations.
        </p>
        <ul class="ar-list">
          <li>Instant skin type detection</li>
          <li>Identify skin concerns and issues</li>
          <li>Personalized treatment recommendations</li>
        </ul>
        <div class="ar-cta-row">
          <a class="small-pill" href="{{ route('skin-analysis.index') }}">Start Analysis</a>
        </div>
      </div>
      <div class="ar-right">
        <div class="ar-image">
          <img src="{{ asset('asset/image/skin-analysis.jpg') }}" ...>
        </div>
      </div>
    </section>

    <!-- ═══════════════════ TREATMENTS ═══════════════════ -->
    <section id="treatments" class="section treatments-section">
      <div class="section-header">
        <p class="kicker">OUR EXPERTISE</p>
        <h2 class="section-title">Signature Treatments</h2>
        <p class="section-sub">Discover transformative treatments designed for your unique skincare needs</p>
      </div>

      <div class="treatments-grid">
        @if($services->count() > 0)
          @foreach($services as $service)
            <article class="treatment-card">
              <div class="treatment-image">
                <img src="{{ str_starts_with($service->image, 'http') ? $service->image : asset('uploads/' . $service->image) }}"
     alt="{{ $service->name }}"
     onerror="this.src='https://i.ibb.co/2s7sG4v/placeholder.png'"/>
              </div>
              <h3>{{ $service->name }}</h3>
              <p>{{ $service->description }}</p>
              <p class="price">₱{{ number_format($service->price, 2) }}</p>
            </article>
          @endforeach
        @else
          <p style="text-align:center; color:#666;">No services found.</p>
        @endif
      </div>

      <div class="center-btn">
        <a class="view-all" href="#">View All Treatments</a>
      </div>
    </section>

    <!-- ═══════════════════ SHOP ═══════════════════ -->
    <section id="shop" class="shop-products">
      <div class="section-header">
        <p class="kicker">OUR PRODUCTS</p>
        <h2 class="section-title">PRODUCTS</h2>
        <p class="section-sub">Check out our latest skincare products available for purchase</p>
      </div>

      <div class="shop-grid">
        @if($products->count() > 0)
          @foreach($products as $product)
            <article class="shop-card">
              <div class="shop-image">
                <img src="{{ str_starts_with($product->image, 'http') ? $product->image : asset('uploads/' . $product->image) }}"
     alt="{{ $product->product_name }}"
     onerror="this.src='https://i.ibb.co/2s7sG4v/placeholder.png'"/>
              </div>
              <h3>{{ $product->product_name }}</h3>
              <p>₱{{ number_format($product->selling_price, 2) }}</p>
            </article>
          @endforeach
        @else
          <p style="text-align:center; color:#666;">No products available.</p>
        @endif
      </div>
    </section>

    <!-- ═══════════════════ REVIEWS ═══════════════════ -->
    <section id="reviews" class="section">
      <div class="section-header">
        <p class="kicker">PATIENT STORIES</p>
        <h2 class="section-title">What Our Patients Say</h2>
        <p class="section-sub">Real experiences from our valued patients</p>
      </div>
 
      @if(isset($reviews) && $reviews->count() > 0)
        <div class="reviews-grid">
          @foreach($reviews as $review)
            <article class="review-card">
              <div class="stars">
                @for($i = 1; $i <= 5; $i++)
                  {{ $i <= $review->rating ? '★' : '☆' }}
                @endfor
              </div>
              <p class="review-text">"{{ $review->comment }}"</p>
              @if($review->service_name)
                <p style="font-size:12px;color:#80a833;font-weight:600;margin:8px 0 0;">{{ $review->service_name }}</p>
              @endif
              <div class="author">
                <span class="avatar">{{ strtoupper(substr($review->patient_name, 0, 1)) }}</span>
                <div>
                  <strong style="font-size:14px;color:#2e2420;">{{ $review->patient_name }}</strong>
                  <p style="font-size:12px;color:#9c8f83;margin:0;">{{ \Carbon\Carbon::parse($review->created_at)->format('M Y') }}</p>
                </div>
              </div>
            </article>
          @endforeach
        </div>
 
        {{-- Overall rating summary --}}
        <div style="text-align:center;margin-top:24px;">
          <span style="font-size:28px;font-family:'Playfair Display',serif;color:#80a833;font-weight:700;">
            {{ number_format($reviews->avg('rating'), 1) }}
          </span>
          <span style="font-size:18px;color:#f2b84b;margin-left:6px;">
            @for($i = 1; $i <= 5; $i++){{ $i <= round($reviews->avg('rating')) ? '★' : '☆' }}@endfor
          </span>
          <span style="font-size:13px;color:#9c8f83;margin-left:8px;">from {{ $reviews->count() }} {{ Str::plural('review', $reviews->count()) }}</span>
        </div>
 
      @else
        <div style="text-align:center;padding:40px 0;color:#b5a898;">
          <p style="font-size:15px;">No reviews yet. Be the first to share your experience!</p>
          <a href="/?login=true" class="view-all" style="display:inline-block;margin-top:16px;">Leave a Review</a>
        </div>
      @endif
    </section>
 
    <!-- ═══════════════════ LOCATION ═══════════════════ -->
    <section id="locations" class="section">
      <div class="section-header">
        <p class="kicker">FIND US</p>
        <h2 class="section-title">Our Location</h2>
        <p class="section-sub">Come visit us at our clinic</p>
      </div>
 
      <div class="location-card">
        <div class="loc-left">
          <h3>
            Skin Medic Clinic
            <span class="badge">Main Branch</span>
          </h3>
          <div class="loc-meta">
            <p>📍 <strong>Address:</strong> Pacifica Plaza, Buhay Na Tubig, Imus, Cavite, Philippines</p>
            <p>🕐 <strong>Mon–Fri:</strong> 9:00 AM – 6:00 PM</p>
            <p>🕐 <strong>Saturday:</strong> 9:00 AM – 3:00 PM</p>
            <p>🕐 <strong>Sunday:</strong> Closed</p>
            <p>📞 <strong>Phone:</strong> 0968-619-5061 or 0995-882-2211</p>
            <p>✉️ <strong>Email:</strong> [Your email here]</p>
          </div>
          <div class="loc-actions">
            <a href="https://maps.google.com/?q=14.4086299,120.9544516" target="_blank" class="directions">Get Directions</a>
            <a href="tel:09686195061" class="call">Call Us</a>
          </div>
        </div>
 
        {{-- Google Maps embed - replace src with your actual embed URL --}}
        <div style="flex:1.2;border-radius:12px;overflow:hidden;min-height:280px;background:#e8e9eb;">
          <iframe
            src="https://maps.google.com/maps?q=14.4086299,120.9544516&z=17&output=embed"
            width="100%" height="280" style="border:0;display:block;"
            allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>
    </section>

  </main>
  <script src="{{ asset('asset/js/index.js') }}"></script>
</body>
</html>