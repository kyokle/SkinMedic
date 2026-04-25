{{-- resources/views/partials/sidebar_doctor.blade.php --}}

<link rel="stylesheet" href="{{ asset('asset/css/sidebar_doctor.css') }}">

{{-- Hamburger button (mobile only) --}}
<button class="sb-hamburger" id="sbHamburger" aria-label="Toggle menu">
    <span></span>
    <span></span>
    <span></span>
</button>

{{-- Overlay --}}
<div class="sb-overlay" id="sbOverlay"></div>

<aside class="sidebar" id="sidebar">

    {{-- Profile Section --}}
    <div class="sb-profile">
        <div class="sb-pic-wrap"
             onclick="document.getElementById('doctorPicInput').click()"
             title="Click to change photo">
            <img src="{{ basename($sidebarProfile) }}" alt="Profile">
            <div class="sb-pic-overlay">📷</div>
        </div>

        {{-- Profile picture upload form --}}
        <form method="POST"
              action="{{ route('doctor.profile.upload-pic') }}"
              enctype="multipart/form-data"
              id="doctorPicForm"
              style="display:none">
            @csrf
            <input type="file" name="profile_pic" id="doctorPicInput" accept="image/*"
                   onchange="document.getElementById('doctorPicForm').submit();">
        </form>

        <div class="sb-name">Dr. {{ $sidebarFirstName . ' ' . $sidebarLastName }}</div>
        <div class="sb-email">{{ $sidebarEmail }}</div>
        <span class="sb-badge">Doctor</span>
    </div>

    {{-- Navigation --}}
    <nav class="sb-nav">
        <span class="sb-label">Overview</span>
        <a href="{{ route('doctor.home') }}"
           class="{{ request()->routeIs('doctor.home') ? 'active' : '' }}">
            <span class="ni">🏠</span> Home
        </a>

        <span class="sb-label">Clinic</span>
        <a href="{{ route('doctor.bookings') }}"
           class="{{ request()->routeIs('doctor.bookings') ? 'active' : '' }}">
            <span class="ni">🧾</span> My Bookings
        </a>

        <span class="sb-label">Account</span>
        <a href="{{ route('doctor.profile') }}"
           class="{{ request()->routeIs('doctor.profile') ? 'active' : '' }}">
            <span class="ni">👤</span> Profile
        </a>
    </nav>

    {{-- Footer --}}
    <div class="sb-footer">
        <button class="sb-logout" onclick="window.location.href='{{ route('logout') }}'">
            <span>🚪</span> Sign Out
        </button>
    </div>

</aside>

<script>
    const sidebar   = document.getElementById('sidebar');
    const hamburger = document.getElementById('sbHamburger');
    const overlay   = document.getElementById('sbOverlay');

    function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('active'); }
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); }

    hamburger.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
    overlay.addEventListener('click', closeSidebar);
</script>