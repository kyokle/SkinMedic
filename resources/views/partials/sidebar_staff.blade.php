{{-- resources/views/partials/sidebar_staff.blade.php --}}

<link rel="stylesheet" href="{{ asset('asset/css/sidebar_staff.css') }}">

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
        {{-- Profile picture upload form --}}
        <form method="POST"
              action="{{ route('staff.profile.upload-pic') }}"
              enctype="multipart/form-data"
              id="staffPicForm"
              style="display:none">
            @csrf
            <input type="file" name="profile_pic" id="staffPicInput" accept="image/*"
                   onchange="document.getElementById('staffPicForm').submit();">
        </form>

        <div class="sb-pic-wrap"
             onclick="document.getElementById('staffPicInput').click()"
             title="Click to change photo">
            <img src="{{ asset('uploads/' . $sidebarProfile) }}" alt="Profile">
            <div class="sb-pic-overlay">📷</div>
        </div>

        <div class="sb-name">{{ $sidebarFirstName . ' ' . $sidebarLastName }}</div>
        <div class="sb-email">{{ $sidebarEmail }}</div>
        <span class="sb-badge">{{ $sidebarRole }}</span>
    </div>

    {{-- Navigation --}}
    <nav class="sb-nav">
        <span class="sb-label">Overview</span>
        <a href="{{ route('staff.home') }}"
           class="{{ request()->routeIs('staff.home') ? 'active' : '' }}">
            <span class="ni">🏠</span> Home
        </a>

        <span class="sb-label">Clinic</span>
        <a href="{{ route('staff.services') }}"
           class="{{ request()->routeIs('staff.services') ? 'active' : '' }}">
            <span class="ni">💆</span> Services
        </a>
        <a href="{{ route('staff.bookings') }}"
           class="{{ request()->routeIs('staff.bookings') ? 'active' : '' }}">
            <span class="ni">🧾</span> Bookings
        </a>

        <a href="{{ route('staff.reviews') }}"
           class="{{ request()->routeIs('staff.reviews') ? 'active' : '' }}">
            <span class="ni">⭐</span> Reviews
        </a>

        <span class="sb-label">Inventory</span>
        <a href="{{ route('staff.products') }}"
           class="{{ request()->routeIs('staff.products') ? 'active' : '' }}">
            <span class="ni">🧴</span> Products
        </a>
        <a href="{{ route('staff.inventory') }}"
           class="{{ request()->routeIs('staff.inventory') ? 'active' : '' }}">
            <span class="ni">📦</span> Inventory
        </a>

        <span class="sb-label">Account</span>
        <a href="{{ route('staff.profile') }}"
           class="{{ request()->routeIs('staff.profile') ? 'active' : '' }}">
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