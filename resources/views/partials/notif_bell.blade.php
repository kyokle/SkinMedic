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
         style="display:none;position:absolute;top:44px;right:0;width:300px;
                background:white;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,0.15);
                z-index:9999;max-height:350px;overflow-y:auto;">
        <div style="padding:10px 14px;font-weight:600;border-bottom:1px solid #eee;
                    display:flex;justify-content:space-between;align-items:center;">
            <span>Notifications</span>
            <button onclick="markAllRead()"
                    style="font-size:11px;color:#80a833;background:none;border:none;cursor:pointer;">
                Mark all read
            </button>
        </div>
        <div id="notifList"></div>
    </div>
</div>

<script>
function loadUnreadCount() {
    fetch('/notifications/unread')
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
    if (!isOpen) loadNotifications();
}

function loadNotifications() {
    fetch('/notifications')
        .then(r => r.json())
        .then(items => {
            const list = document.getElementById('notifList');
            if (!items.length) {
                list.innerHTML = '<p style="padding:12px;color:#999;text-align:center;">No notifications</p>';
                return;
            }
            list.innerHTML = items.map(n => `
                <div onclick="markRead(${n.id}, this)"
                     style="padding:12px 14px;border-bottom:1px solid #f0f0f0;cursor:pointer;
                            background:${n.is_read ? '#fff' : '#f9fff2'}">
                    <div style="font-weight:${n.is_read ? '400' : '600'};font-size:13px;">${n.title}</div>
                    <div style="font-size:12px;color:#666;margin-top:2px;">${n.message}</div>
                    <div style="font-size:11px;color:#aaa;margin-top:4px;">${n.created_at}</div>
                </div>`).join('');
        });
}

function markRead(id, el) {
    fetch('/notifications/read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ id })
    }).then(() => {
        el.style.background = '#fff';
        el.querySelector('div').style.fontWeight = '400';
        loadUnreadCount();
    });
}

function markAllRead() {
    fetch('/notifications/read-all', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(() => {
        loadUnreadCount();
        document.querySelectorAll('#notifList > div').forEach(el => {
            el.style.background = '#fff';
        });
    });
}

document.addEventListener('click', function(e) {
    const btn = document.getElementById('notifBtn');
    const dropdown = document.getElementById('notifDropdown');
    if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

loadUnreadCount();
setInterval(loadUnreadCount, 30000);
</script>