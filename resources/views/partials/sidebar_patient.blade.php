{{-- resources/views/partials/sidebar_patient.blade.php --}}

<link rel="stylesheet" href="{{ asset('asset/css/sidebar_patient.css') }}">

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
        <div class="sb-pic-wrap" title="Profile Photo">
            <img src="{{ $sidebarProfile }}">
        </div>
        <div class="sb-name">{{ $sidebarFirstName . ' ' . $sidebarLastName }}</div>
        <div class="sb-email">{{ $sidebarEmail }}</div>
        <span class="sb-badge">{{ $sidebarRole }}</span>
    </div>

    {{-- Navigation --}}
    <nav class="sb-nav">
        <span class="sb-label">Overview</span>
        <a href="{{ route('patient.home') }}"
           class="{{ request()->routeIs('patient.home') ? 'active' : '' }}">
            <span class="ni">🏠</span> Home
        </a>

        <span class="sb-label">Clinic</span>
        <a href="{{ route('patient.services') }}"
           class="{{ request()->routeIs('patient.services') ? 'active' : '' }}">
            <span class="ni">💆</span> Services
        </a>

         <a href="{{ route('patient.skin-analysis') }}"
             class="{{ request()->routeIs('patient.skin-analysis*') ? 'active' : '' }}"
              <span class="ni">🤳🏻</span> AR Skin Analysis
        </a>

        <a href="{{ route('patient.bookings') }}"
           class="{{ request()->routeIs('patient.bookings') ? 'active' : '' }}">
            <span class="ni">🧾</span> My Bookings
        </a>

        <a href="{{ route('patient.reviews') }}"
           class="{{ request()->routeIs('patient.reviews') ? 'active' : '' }}">
            <span class="ni">⭐</span> Reviews
        </a>

        <span class="sb-label">Account</span>
        <a href="{{ route('patient.profile') }}"
           class="{{ request()->routeIs('patient.profile') ? 'active' : '' }}">
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