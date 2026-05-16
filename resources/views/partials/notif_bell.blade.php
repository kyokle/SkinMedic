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
         style="display:none;position:fixed;top:64px;right:10px;
                width:calc(100vw - 20px);max-width:400px;
                background:white;border-radius:12px;
                box-shadow:0 4px 24px rgba(0,0,0,0.18);
                z-index:9999;">
        <div style="padding:12px 16px;font-weight:600;font-size:14px;border-bottom:1px solid #eee;
                    display:flex;justify-content:space-between;align-items:center;">
            <span>Notifications</span>
            <button onclick="markAllRead()"
                    style="font-size:12px;color:#80a833;background:none;border:none;cursor:pointer;">
                Mark all read
            </button>
        </div>
        <div id="notifList" style="max-height:60vh;overflow-y:auto;"></div>
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
                list.innerHTML = '<p style="padding:16px;color:#999;text-align:center;font-size:13px;">No notifications</p>';
                return;
            }
            list.innerHTML = items.map(n => `
                <div onclick="markRead(${n.id}, this)"
                     style="padding:14px 16px;border-bottom:1px solid #f0f0f0;cursor:pointer;
                            background:${n.is_read ? '#fff' : '#f9fff2'}">
                    <div style="display:flex;align-items:flex-start;gap:8px;">
                        <span style="font-size:16px;flex-shrink:0;">🔔</span>
                        <div style="min-width:0;flex:1;">
                            <div style="font-weight:${n.is_read ? '500' : '700'};font-size:13px;
                                        color:#1a1f16;line-height:1.3;">${n.title}</div>
                            <div style="font-size:12px;color:#555;margin-top:4px;line-height:1.5;
                                        word-break:break-word;white-space:normal;">${n.message}</div>
                            <div style="font-size:11px;color:#aaa;margin-top:4px;">${n.created_at}</div>
                        </div>
                    </div>
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
        el.querySelector('div').style.fontWeight = '500';
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