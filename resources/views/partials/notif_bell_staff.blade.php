<div style="position:relative;">
    <button id="notifBtn" onclick="toggleNotifDropdown()"
        style="background:#f9fff2;border:1px solid #d4edb3;border-radius:10px;
               padding:8px 14px;cursor:pointer;display:flex;align-items:center;
               gap:8px;font-family:inherit;font-size:14px;">
        🔔
        <span id="notifBadge"
              style="display:none;background:red;color:white;border-radius:50%;
                     font-size:10px;padding:2px 6px;">0</span>
    </button>

    <div id="notifDropdown"
         style="display:none;position:absolute;top:44px;right:0;width:320px;
                background:white;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,0.15);
                z-index:9999;">

        <div style="padding:10px 14px;font-weight:600;border-bottom:1px solid #eee;
                    display:flex;justify-content:space-between;align-items:center;">
            <span>🏥 Staff Notifications</span>
            <button onclick="markAllAdminRead()"
                    style="font-size:11px;color:#80a833;background:none;border:none;cursor:pointer;">
                Mark all read
            </button>
        </div>

        <div style="display:flex;border-bottom:1px solid #eee;background:#fafafa;">
            @foreach(['all' => 'All', 'booking' => 'Bookings', 'inventory' => 'Inventory'] as $key => $label)
            <button onclick="switchAdminTab('{{ $key }}', this)"
                    data-tab="{{ $key }}"
                    style="flex:1;padding:8px 4px;font-size:11px;border:none;background:none;
                           cursor:pointer;font-family:inherit;border-bottom:2px solid transparent;
                           {{ $key === 'all' ? 'border-bottom-color:#80a833;font-weight:600;color:#80a833;' : 'color:#888;' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>

        <div id="notifList" style="max-height:300px;overflow-y:auto;"></div>
    </div>
</div>

<script>
let currentAdminTab = 'all';

function loadAdminUnreadCount() {
    fetch('/notifications/unread-by-type?type=all')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('notifBadge');
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? 'inline-block' : 'none';
        });
}

function toggleNotifDropdown() {
    const dropdown = document.getElementById('notifDropdown');
    const isOpen = dropdown.style.display === 'block';
    dropdown.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) loadAdminNotifications(currentAdminTab);
}

function switchAdminTab(type, btn) {
    currentAdminTab = type;
    document.querySelectorAll('[data-tab]').forEach(b => {
        b.style.borderBottomColor = 'transparent';
        b.style.fontWeight = 'normal';
        b.style.color = '#888';
    });
    btn.style.borderBottomColor = '#80a833';
    btn.style.fontWeight = '600';
    btn.style.color = '#80a833';
    loadAdminNotifications(type);
}

function loadAdminNotifications(type) {
    let url = '/notifications/by-type?type=' + type;
    if (type === 'inventory') url = '/notifications/inventory';

    fetch(url)
        .then(r => r.json())
        .then(items => {
            const list = document.getElementById('notifList');
            if (!items.length) {
                list.innerHTML = '<p style="padding:16px;color:#999;text-align:center;font-size:13px;">No notifications</p>';
                return;
            }
            const icons = { booking: '📅', inventory: '📦', rescheduled: '🔄', general: '🔔' };
            list.innerHTML = items.map(n => `
                <div onclick="handleStaffNotifClick(${n.id}, '${n.type}', ${n.reference_id || 0})"
                     style="padding:12px 14px;border-bottom:1px solid #f0f0f0;cursor:pointer;
                            background:${n.is_read ? '#fff' : '#f9fff2'}">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span>${icons[n.type] || '🔔'}</span>
                        <span style="font-weight:${n.is_read ? '400' : '600'};font-size:13px;">${n.title}</span>
                    </div>
                    <div style="font-size:12px;color:#666;margin-top:3px;padding-left:20px;">${n.message}</div>
                    <div style="font-size:11px;color:#aaa;margin-top:3px;padding-left:20px;">${n.created_at}</div>
                </div>`).join('');
        });
}

function handleStaffNotifClick(id, type, referenceId) {
    fetch('/notifications/read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ id })
    }).then(() => {
        if (type === 'inventory') {
            window.location.href = '/staff/inventory';
        } else if (referenceId) {
            window.location.href = '/staff/bookings?open=' + referenceId;
        } else {
            loadAdminUnreadCount();
            loadAdminNotifications(currentAdminTab);
        }
    });
}

function markAllAdminRead() {
    fetch('/notifications/read-all-by-type?type=' + currentAdminTab, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(() => {
        loadAdminUnreadCount();
        loadAdminNotifications(currentAdminTab);
    });
}

document.addEventListener('click', function(e) {
    const btn = document.getElementById('notifBtn');
    const dropdown = document.getElementById('notifDropdown');
    if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

loadAdminUnreadCount();
setInterval(loadAdminUnreadCount, 30000);
</script>