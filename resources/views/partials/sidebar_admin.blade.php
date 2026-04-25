{{-- resources/views/partials/sidebar_admin.blade.php --}}

<link rel="stylesheet" href="{{ asset('asset/css/sidebar_admin.css') }}">

{{-- Hamburger button (visible on mobile only) --}}
<button class="sb-hamburger" id="sbHamburger" aria-label="Toggle menu">
    <span></span>
    <span></span>
    <span></span>
</button>

{{-- Overlay (closes sidebar when tapped outside) --}}
<div class="sb-overlay" id="sbOverlay"></div>

<aside class="sidebar" id="sidebar">

    {{-- Profile Section --}}
    <div class="sb-profile">
        {{-- Profile picture upload form --}}
        <form method="POST"
              action="{{ route('admin.profile.upload-pic') }}"
              enctype="multipart/form-data"
              id="adminPicForm"
              style="display:none">
            @csrf
            <input type="file" name="profile_pic" id="adminPicInput" accept="image/*"
                   onchange="document.getElementById('adminPicForm').submit();">
        </form>

        <div class="sb-pic-wrap"
             onclick="document.getElementById('adminPicInput').click()"
             title="Click to change photo">
            <img src="{{ basename($sidebarProfile) }}" alt="Profile">
            <div class="sb-pic-overlay">📷</div>
        </div>

        <div class="sb-name">{{ $sidebarFirstName . ' ' . $sidebarLastName }}</div>
        <div class="sb-email">{{ $sidebarEmail }}</div>
        <span class="sb-badge">{{ $sidebarRole }}</span>
    </div>

    {{-- Navigation --}}
    <nav class="sb-nav">
        <span class="sb-label">Overview</span>
        <a href="{{ route('admin.home') }}"
           class="{{ request()->routeIs('admin.home') ? 'active' : '' }}">
            <span class="ni">🏠</span> Dashboard
        </a>

        <span class="sb-label">Clinic</span>
        <a href="{{ route('admin.services') }}"
           class="{{ request()->routeIs('admin.services') ? 'active' : '' }}">
            <span class="ni">💆</span> Services
        </a>
        <a href="{{ route('admin.bookings') }}"
           class="{{ request()->routeIs('admin.bookings') ? 'active' : '' }}">
            <span class="ni">🗓</span> Bookings
        </a>

        <a href="{{ route('admin.reviews') }}"
           class="{{ request()->routeIs('admin.reviews') ? 'active' : '' }}">
            <span class="ni">⭐</span> Reviews
        </a>

        <span class="sb-label">Inventory</span>
        <a href="{{ route('admin.products') }}"
           class="{{ request()->routeIs('admin.products') ? 'active' : '' }}">
            <span class="ni">🧴</span> Products
        </a>
        <a href="{{ route('admin.inventory') }}"
           class="{{ request()->routeIs('admin.inventory') ? 'active' : '' }}">
            <span class="ni">📦</span> Inventory
        </a>

        <span class="sb-label">People</span>
        <a href="{{ route('admin.manage-users') }}"
           class="{{ request()->routeIs('admin.manage-users') ? 'active' : '' }}">
            <span class="ni">👥</span> Manage Users
        </a>
        <a href="{{ route('admin.add-account') }}"
           class="{{ request()->routeIs('admin.add-account') ? 'active' : '' }}">
            <span class="ni">➕</span> Add Account
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
    const sidebar    = document.getElementById('sidebar');
    const hamburger  = document.getElementById('sbHamburger');
    const overlay    = document.getElementById('sbOverlay');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    }

    hamburger.addEventListener('click', () => {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    overlay.addEventListener('click', closeSidebar);
</script>