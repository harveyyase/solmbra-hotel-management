const NOTIF_TABS = { reservation: 'reservation', payment: 'payment' };

// Finds the folder tab by its title text and clicks it. Returns true if a tab was found.
function openTabFor(entity) {
  const word = NOTIF_TABS[entity];
  if (!word) return false;
  const tab = Array.from(document.querySelectorAll('.folder-tab'))
    .find(t => t.textContent.toLowerCase().includes(word));
  if (!tab) return false;
  tab.click();
  return true;
}


function highlightRow(entityId, tries = 0) {
  const row = Array.from(document.querySelectorAll('tbody tr'))
    .find(tr => tr.offsetParent !== null && tr.textContent.includes(entityId));
  if (row) {
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    row.classList.add('row-highlight');
    setTimeout(() => row.classList.remove('row-highlight'), 3000);
    return;
  }
  if (tries < 10) setTimeout(() => highlightRow(entityId, tries + 1), 250);
}


let lastSignature = '';

// NEW: status badge (Paid / Pending / Overdue / Confirmed...) from the live status
function statusBadge(status) {
  if (!status) return '';
  const cls = String(status).toLowerCase().replace(/[^a-z]/g, '');
  return `<span class="notif-status notif-status--${cls}">${esc(status)}</span>`;
}

async function loadNotifications() {
  const { ok, data } = await api('notifications.list');
  const badge = document.getElementById('inboxBadge');
  const list = document.getElementById('inboxList');

  if (!ok) return;
  const items = data || [];

  
  const unread = items.filter(n => !n.read_at).length;
  badge.textContent = unread;
  badge.style.display = unread ? 'grid' : 'none';


  const signature = items.map(n => `${n.id}:${n.read_at ? 1 : 0}:${n.live_status || ''}`).join('|');
  if (signature === lastSignature) return;
  lastSignature = signature;

  if (!items.length) {
    list.innerHTML = '<div class="inbox-empty">You\'re all caught up.</div>';
    return;
  }

  // NEW 
  const scrollTop = list.scrollTop;

  // CHANGED: each item is clickable, has an unread dot, and shows a status badge
  list.innerHTML = items.map(n => `
    <div class="inbox-item ${n.read_at ? 'read' : 'unread'}"
         data-id="${n.id}" data-entity="${esc(n.entity || '')}" data-entity-id="${esc(n.entity_id || '')}">
      <span class="notif-dot" aria-label="${n.read_at ? 'Read' : 'Unread'}"></span>
      <div class="notif-body">
        <strong>${esc(n.title)}</strong>
        <span>${esc(n.detail || '')}</span>
      </div>
      ${statusBadge(n.live_status)}
    </div>`).join('');

  list.scrollTop = scrollTop; // NEW
}

// CHANGED: click a notification -> mark read, then go to its page.
document.getElementById('inboxList').addEventListener('click', async e => {
  const item = e.target.closest('.inbox-item');
  if (!item) return;

  const id = Number(item.dataset.id);

  // CHANGED 
  const tabOpened = openTabFor(item.dataset.entity);

  // CHANGED: mark as read BEFORE leaving, so navigating away can't cancel it
  if (item.classList.contains('unread')) {
    try { await api('notifications.read', { id }, 'POST'); }
    catch (err) { console.error('mark read failed', err); }
  }

  if (tabOpened) {
    document.getElementById('inboxDropdown').classList.remove('open'); // NEW: close the dropdown
    highlightRow(item.dataset.entityId);                               // NEW
    loadNotifications();                                               // NEW: update the dot right away
  } else {
    // NEW 
    window.location.href = `dash.php?open=${encodeURIComponent(item.dataset.entity)}&highlight=${encodeURIComponent(item.dataset.entityId)}`;
  }
});

// Toggle dropdown
document.getElementById('inboxBtn').addEventListener('click', e => {
  e.stopPropagation();
  document.getElementById('inboxDropdown').classList.toggle('open');
  if (document.getElementById('inboxDropdown').classList.contains('open')) loadNotifications();
});

// NEW: clicking/scrolling inside the dropdown should not close it
document.getElementById('inboxDropdown').addEventListener('click', e => e.stopPropagation());

// Clear All
document.getElementById('inboxClearBtn').addEventListener('click', async () => {
  document.getElementById('inboxList').innerHTML = '<div class="inbox-empty">Inbox cleared.</div>';
  document.getElementById('inboxBadge').style.display = 'none';
  lastSignature = ''; // NEW: force a fresh render next time
  await api('notifications.clear', {}, 'POST');
});

// Close dropdown when clicking outside
document.addEventListener('click', () => {
  document.getElementById('inboxDropdown').classList.remove('open');
});

// CHANGED 
setInterval(() => { if (!document.hidden) loadNotifications(); }, 10000);

// NEW: refresh when returning to the tab
document.addEventListener('visibilitychange', () => { if (!document.hidden) loadNotifications(); });

// NEW: refresh the bell after any POST (delete, archive, confirm...)
(function () {
  if (typeof window.api !== 'function') return;
  const originalApi = window.api;
  let refreshTimer;
  window.api = async function (...args) {
    const result = await originalApi.apply(this, args);
    const action = String(args[0] || '');
    if (args[2] === 'POST' && !action.startsWith('notifications.')) {
      clearTimeout(refreshTimer);
      refreshTimer = setTimeout(loadNotifications, 400);
    }
    return result;
  };
})();


// attached its tab click handlers first.
window.addEventListener('load', () => {
  const params = new URLSearchParams(window.location.search);
  const entity = params.get('open');
  if (!entity) return;

  const entityId = params.get('highlight');
  let tries = 0;
  const attempt = () => {
    if (openTabFor(entity)) {
      if (entityId) highlightRow(entityId);
      history.replaceState(null, '', window.location.pathname);
    } else if (tries++ < 10) {
      setTimeout(attempt, 250);
    }
  };
  attempt();
});

// Initial load
loadNotifications();