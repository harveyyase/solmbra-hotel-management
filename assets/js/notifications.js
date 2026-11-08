async function loadNotifications() {
  const { ok, data } = await api('notifications.list');
  const badge = document.getElementById('inboxBadge');
  const list = document.getElementById('inboxList');
  
  if (!ok) return;
  const items = data || [];
  
  // Update Badge
  badge.textContent = items.length;
  badge.style.display = items.length ? 'grid' : 'none';

  // Update List
  if (!items.length) {
    list.innerHTML = '<div class="inbox-empty">You\'re all caught up.</div>';
    return;
  }

  // Render items with a clear "X" button inside
  list.innerHTML = items.map(n => `
    <div class="inbox-item" style="display:flex; justify-content:space-between; align-items:flex-start;">
      <div style="flex:1;">
        <strong>${esc(n.title)}</strong>
        <span>${esc(n.detail || '')}</span>
      </div>
      <button class="clear-single-btn" data-id="${n.id}" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:14px; padding:4px;" title="Mark as read">
        <i class="fas fa-times"></i>
      </button>
    </div>`).join('');

  // Attach listeners to the "X" buttons
  list.querySelectorAll('.clear-single-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const id = Number(btn.dataset.id);
      const item = btn.closest('.inbox-item');
      if (item) item.remove();
      
      // Update badge
      const newCount = list.querySelectorAll('.inbox-item').length;
      badge.textContent = newCount;
      badge.style.display = newCount > 0 ? 'grid' : 'none';
      if (newCount === 0) list.innerHTML = '<div class="inbox-empty">You\'re all caught up.</div>';
      
      // Tell server
      await api('notifications.read', { id }, 'POST');
    });
  });
}

// Toggle dropdown
document.getElementById('inboxBtn').addEventListener('click', e => {
  e.stopPropagation();
  document.getElementById('inboxDropdown').classList.toggle('open');
  if (document.getElementById('inboxDropdown').classList.contains('open')) loadNotifications();
});

// Clear All
document.getElementById('inboxClearBtn').addEventListener('click', async () => {
  document.getElementById('inboxList').innerHTML = '<div class="inbox-empty">Inbox cleared.</div>';
  document.getElementById('inboxBadge').style.display = 'none';
  await api('notifications.clear', {}, 'POST');
});

// Close dropdown when clicking outside
document.addEventListener('click', () => {
  document.getElementById('inboxDropdown').classList.remove('open');
});

// Poll every 20s
setInterval(() => { if (!document.hidden) loadNotifications(); }, 20000);

// Initial load
loadNotifications();