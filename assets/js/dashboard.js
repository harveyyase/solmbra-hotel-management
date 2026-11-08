// assets/js/dashboard.js

let STATE = { rooms: [], guests: [], reservations: [], staff: [], payments: [], activity: [], today: '', user: {} };
const PAGE_SIZE = 5;
const pageState = {reservations:1, rooms:1, guests:1, staff:1, payments:1, reports:1};
const searchState = {reservations:'', rooms:'', guests:'', staff:'', payments:''};
const archiveState = {reservations:false, guests:false, payments:false, staff:false};
let currentSection = 'dashboard';

async function bootstrap() {
  const { ok, data } = await api('bootstrap');
  if (!ok) { toast('Could not load data.', 'danger'); return; }
  STATE = data;
  CSRF = data.csrf;
  
  const todayObj = new Date(STATE.today + 'T00:00:00');
  document.getElementById('today').textContent = todayObj.toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'});
  document.getElementById('profileInitial').textContent = (STATE.user.name || 'A')[0].toUpperCase();
  
  // Remember current tab, default to dashboard
  // Check the URL hash first, then fallback to dashboard
  const hashPage = window.location.hash.replace('#', '');
  const validPages = ['dashboard', 'reservations', 'rooms', 'guests', 'staff', 'payments', 'reports'];
  
  if (hashPage && validPages.includes(hashPage)) {
    currentSection = hashPage;
  } else if (!currentSection) {
    currentSection = 'dashboard';
  }
  
  navigate(currentSection); 
}

// ---------- RENDER HELPERS ----------
function table(headers, rows) {
  return `<div class="table-wrap"><table><thead><tr>${headers.map(h => `<th${h === 'ACTIONS' ? ' class="th-actions"' : ''}>${h}</th>`).join('')}</tr></thead><tbody>${rows}</tbody></table></div>`;
}
function paginationBar(section, total) {
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  const page = Math.min(pageState[section] || 1, totalPages);
  pageState[section] = page;
  return `<div class="pagination-bar">
    <button class="btn-page" data-page-prev="${section}" ${page <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i> Prev</button>
    <span class="pagination-label">Page ${page} of ${totalPages} · ${total} items</span>
    <button class="btn-page" data-page-next="${section}" ${page >= totalPages ? 'disabled' : ''}>Next <i class="fas fa-chevron-right"></i></button>
  </div>`;
}
function filterBySearch(items, q, fields) {
  if (!q) return items;
  const lq = q.toLowerCase();
  return items.filter(it => fields.some(f => String(it[f] ?? '').toLowerCase().includes(lq)));
}
function pageSlice(items, section) {
  const start = ((pageState[section] || 1) - 1) * PAGE_SIZE;
  return items.slice(start, start + PAGE_SIZE);
}
function searchBox(section, placeholder) {
  return `<div class="section-search ${searchState[section] ? 'has-value' : ''}" id="search-${section}">
    <i class="fas fa-search" style="color:#8b7d9c;font-size:11px"></i>
    <input type="text" id="${section}SearchInput" placeholder="${placeholder}" value="${esc(searchState[section])}" />
    <button class="section-search-clear" data-clear="${section}" title="Clear"><i class="fas fa-times"></i></button>
  </div>`;
}
function archiveToggle(section) {
  return `<label class="archive-toggle">
    <input type="checkbox" id="${section}ArchiveToggle" ${archiveState[section] ? 'checked' : ''} />
    <span class="archive-toggle-track"><span class="archive-toggle-thumb"></span></span>
    <span class="archive-toggle-label"><i class="fas fa-box-archive"></i> Archive Mode</span>
  </label>`;
}

// ---------- DASHBOARD ----------
function renderDashboard() {
  const {rooms, reservations, today} = STATE;
  const occupied = rooms.filter(r => r.status === 'Occupied');
  const available = rooms.filter(r => r.status === 'Available').length;
  const occPct = rooms.length ? Math.round(occupied.length / rooms.length * 100) : 0;
  const arrivals = reservations.filter(r => r.check_in === today && ['Confirmed','Pending'].includes(r.status) && !r.archived);
  const departures = reservations.filter(r => r.check_out === today && r.status === 'Checked In' && !r.archived);
  const revenue = occupied.reduce((s, r) => s + Number(r.price), 0);
  
  const activityHtml = STATE.activity.slice(0, 6).map(a => `
    <div class="activity-item">
      <div class="activity-dot ${esc(a.type)}"></div>
      <div class="activity-text"><div>${esc(a.message)}</div><div class="activity-time">Recently</div></div>
    </div>`).join('') || '<p class="dash-empty">No activity yet.</p>';

  return `
  <div class="folder-heading">
    <div><h3>Welcome back, <span id="greetingName">${esc(STATE.user.name)}</span>.</h3><p>Here's what's happening at SOLMBRA today.</p></div>
    <span class="pill">✦ Live property overview</span>
  </div>
  <div class="stats">
    <div class="stat-card"><span class="stat-icon">▣</span><div class="stat-label">Total Rooms</div><div class="stat-value">${rooms.length}</div><div class="stat-change">${available} available</div></div>
    <div class="stat-card"><span class="stat-icon">◔</span><div class="stat-label">Occupancy</div><div class="stat-value">${occPct}%</div><div class="stat-change">${occupied.length} of ${rooms.length} rooms</div></div>
    <div class="stat-card"><span class="stat-icon">▧</span><div class="stat-label">Arrivals Today</div><div class="stat-value">${arrivals.length}</div><div class="stat-change">${arrivals.filter(r => r.status === 'Confirmed').length} confirmed</div></div>
    <div class="stat-card"><span class="stat-icon">↗</span><div class="stat-label">Revenue Tonight</div><div class="stat-value">${money(revenue)}</div></div>
  </div>
  <div class="dash-grid">
    <div class="dash-card">
      <h4><span>TODAY'S ARRIVALS</span></h4>
      ${arrivals.length ? arrivals.map(r => `<div class="task-row"><div class="task-avatar">${esc(initials(r.guest))}</div><div class="task-info"><strong>${esc(r.guest)}</strong><span>Room ${esc(r.room)}</span></div><button class="mini-btn success" data-action="checkin" data-id="${esc(r.id)}">Check in</button></div>`).join('') : '<p class="dash-empty">No arrivals today.</p>'}
    </div>
    <div class="dash-card">
      <h4><span>TODAY'S DEPARTURES</span></h4>
      ${departures.length ? departures.map(r => `<div class="task-row"><div class="task-avatar">${esc(initials(r.guest))}</div><div class="task-info"><strong>${esc(r.guest)}</strong><span>Room ${esc(r.room)}</span></div><button class="mini-btn warn" data-action="checkout" data-id="${esc(r.id)}">Check out</button></div>`).join('') : '<p class="dash-empty">No departures today.</p>'}
    </div>
  </div>
  <div class="dash-grid">
    <div class="dash-card"><h4><span>RECENT ACTIVITY</span></h4>${activityHtml}</div>
    <div class="dash-card"><h4><span>QUICK ACTIONS</span></h4>
      <div class="quick-actions">
        <a class="btn-glass" data-quick="reservation"><i class="fas fa-calendar-plus"></i> New Reservation</a>
        <a class="btn-glass" data-quick="guest"><i class="fas fa-user-plus"></i> Register Guest</a>
        <a class="btn-glass" data-quick="payment"><i class="fas fa-hand-holding-usd"></i> Record Payment</a>
      </div>
    </div>
  </div>`;
}

// ---------- RESERVATIONS ----------
function renderReservations() {
  let items = STATE.reservations.filter(r => !!r.archived === archiveState.reservations);
  items = filterBySearch(items, searchState.reservations, ['id','guest','room','status']);
  const page = pageSlice(items, 'reservations');
  
  const rows = page.length ? page.map(r => `<tr>
    <td>${esc(r.id)}</td><td>${esc(r.guest)}</td><td>${esc(r.room)}</td><td>${fmtDate(r.check_in)}</td><td>${fmtDate(r.check_out)}</td>
    <td><span class="status ${statusCls(r.status)}">${esc(r.status)}</span></td>
    <td><div class="icon-actions">
      ${r.status === 'Confirmed' || r.status === 'Pending' ? `<button class="mini-btn success" data-action="checkin" data-id="${esc(r.id)}"><i class="fas fa-check"></i></button>` : ''}
      ${r.status === 'Checked In' ? `<button class="mini-btn warn" data-action="checkout" data-id="${esc(r.id)}"><i class="fas fa-sign-out-alt"></i></button>` : ''}
      <button class="mini-btn" data-action="edit-reservation" data-id="${esc(r.id)}"><i class="fas fa-pen"></i></button>
      <button class="mini-btn danger" data-action="delete-reservation" data-id="${esc(r.id)}"><i class="fas fa-trash"></i></button>
    </div></td>
  </tr>`).join('') : `<tr><td colspan="7" class="empty">No reservations found.</td></tr>`;
  
  return `<div class="section-header">
    <h2>Reservations</h2>
    <div class="section-header-actions">${searchBox('reservations', 'Search...')}${archiveToggle('reservations')}</div>
  </div>
  ${table(['ID','GUEST','ROOM','IN','OUT','STATUS','ACTIONS'], rows)}
  ${paginationBar('reservations', items.length)}`;
}

// ---------- ROOMS ----------
function renderRooms() {
  let items = filterBySearch(STATE.rooms, searchState.rooms, ['number','type','status']);
  const page = pageSlice(items, 'rooms');
  const rows = page.length ? page.map(r => `<tr>
    <td>${esc(r.number)}</td><td>${esc(r.type)}</td><td>${money(r.price)}</td>
    <td><span class="status ${statusCls(r.status)}">${esc(r.status)}</span></td>
    <td><div class="icon-actions">
      <button class="mini-btn" data-action="edit-room" data-number="${esc(r.number)}"><i class="fas fa-pen"></i></button>
      <button class="mini-btn danger" data-action="delete-room" data-number="${esc(r.number)}"><i class="fas fa-trash"></i></button>
    </div></td>
  </tr>`).join('') : `<tr><td colspan="5" class="empty">No rooms found.</td></tr>`;
  return `<div class="section-header"><h2>Rooms</h2><div class="section-header-actions">${searchBox('rooms', 'Search...')}</div></div>
  ${table(['ROOM #','TYPE','RATE','STATUS','ACTIONS'], rows)}
  ${paginationBar('rooms', items.length)}`;
}

// ---------- GUESTS ----------
function renderGuests() {
  let items = STATE.guests.filter(g => !!g.archived === archiveState.guests);
  items = filterBySearch(items, searchState.guests, ['name','email','phone','room']);
  const page = pageSlice(items, 'guests');
  const rows = page.length ? page.map(g => `<tr>
    <td>${esc(g.name)}</td><td>${esc(g.email || '—')}</td><td>${esc(g.phone || '—')}</td><td>${esc(g.room || '—')}</td>
    <td><div class="icon-actions">
      <button class="mini-btn" data-action="edit-guest" data-id="${g.id}"><i class="fas fa-pen"></i></button>
      <button class="mini-btn danger" data-action="delete-guest" data-id="${g.id}"><i class="fas fa-trash"></i></button>
    </div></td>
  </tr>`).join('') : `<tr><td colspan="5" class="empty">No guests found.</td></tr>`;
  return `<div class="section-header"><h2>Guests</h2><div class="section-header-actions">${searchBox('guests', 'Search...')}${archiveToggle('guests')}</div></div>
  ${table(['NAME','EMAIL','PHONE','ROOM','ACTIONS'], rows)}
  ${paginationBar('guests', items.length)}`;
}

// ---------- STAFF ----------
function renderStaff() {
  let items = STATE.staff.filter(s => !!s.archived === archiveState.staff);
  items = filterBySearch(items, searchState.staff, ['name','role','shift','email','phone']);
  const page = pageSlice(items, 'staff');
  const rows = page.length ? page.map(s => `<tr>
    <td>${esc(s.name)}</td><td>${esc(s.role)}</td><td><span class="status occupied">${esc(s.shift)}</span></td>
    <td>${esc(s.email || '—')}</td><td>${esc(s.phone || '—')}</td>
    <td><div class="icon-actions">
      <button class="mini-btn" data-action="edit-staff" data-id="${s.id}"><i class="fas fa-pen"></i></button>
      <button class="mini-btn danger" data-action="delete-staff" data-id="${s.id}"><i class="fas fa-trash"></i></button>
    </div></td>
  </tr>`).join('') : `<tr><td colspan="6" class="empty">No staff found.</td></tr>`;
  return `<div class="section-header"><h2>Staff</h2><div class="section-header-actions">${searchBox('staff', 'Search...')}${archiveToggle('staff')}</div></div>
  ${table(['NAME','ROLE','SHIFT','EMAIL','PHONE','ACTIONS'], rows)}
  ${paginationBar('staff', items.length)}`;
}

// ---------- PAYMENTS ----------
function renderPayments() {
  let items = STATE.payments.filter(p => !!p.archived === archiveState.payments);
  items = filterBySearch(items, searchState.payments, ['id','guest','room','status','method']);
  const page = pageSlice(items, 'payments');
  const rows = page.length ? page.map(p => `<tr>
    <td>${esc(p.id)}</td><td>${fmtDate(p.date)}</td><td>${esc(p.guest)}</td><td>${esc(p.room)}</td>
    <td>${money(p.amount)}</td><td><span class="status ${statusCls(p.status)}">${esc(p.status)}</span></td>
    <td>${esc(p.method)}</td>
    <td><div class="icon-actions">
      <button class="mini-btn" data-action="edit-payment" data-id="${esc(p.id)}"><i class="fas fa-pen"></i></button>
      <button class="mini-btn danger" data-action="delete-payment" data-id="${esc(p.id)}"><i class="fas fa-trash"></i></button>
    </div></td>
  </tr>`).join('') : `<tr><td colspan="8" class="empty">No payments found.</td></tr>`;
  return `<div class="section-header"><h2>Payments</h2><div class="section-header-actions">${searchBox('payments', 'Search...')}${archiveToggle('payments')}</div></div>
  ${table(['ID','DATE','GUEST','ROOM','AMOUNT','STATUS','METHOD','ACTIONS'], rows)}
  ${paginationBar('payments', items.length)}`;
}

// ---------- REPORTS ----------
async function renderReports() {
  const res = await api('reports.summary');
  const d = res.data || {};
  return `<div class="section-header"><h2>Reports</h2></div>
  <div class="stats">
    <div class="stat-card"><div class="stat-label">Total Collected</div><div class="stat-value">${money(d.paid)}</div></div>
    <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value">${money(d.pending)}</div></div>
    <div class="stat-card"><div class="stat-label">Occupancy</div><div class="stat-value">${d.occupancy || 0}%</div></div>
    <div class="stat-card"><div class="stat-label">In-house</div><div class="stat-value">${d.in_house || 0}</div></div>
  </div>`;
}

// ---------- ROUTER ----------
const content = document.getElementById('page-content');
const renderers = {dashboard: renderDashboard, reservations: renderReservations, rooms: renderRooms, guests: renderGuests, staff: renderStaff, payments: renderPayments, reports: renderReports};
const heroLabels = {dashboard:'New Reservation', reservations:'New Reservation', rooms:'Add Room', guests:'Add Guest', staff:'Add Staff', payments:'Record Payment', reports:'Export CSV'};

async function navigate(page) {
  if (!renderers[page]) return;
  currentSection = page;
  // Save the page in the URL hash
  if (window.location.hash !== '#' + page) {
    history.replaceState(null, null, '#' + page);
  }
  currentSection = page;
  document.querySelectorAll('.folder-tab').forEach(t => t.classList.toggle('active', t.dataset.page === page));
  document.getElementById('heroActionText').textContent = heroLabels[page] || 'New';
  
  if (page === 'reports') {
    content.innerHTML = '<p class="dash-empty">Loading...</p>';
    content.innerHTML = await renderers[page]();
  } else {
    content.innerHTML = renderers[page]();
  }
}

// ---------- MODALS & FORMS ----------
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => closeModal(b.dataset.close)));
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); }));

function populateRoomSelect(selectId, currentValue) {
  const sel = document.getElementById(selectId);
  sel.innerHTML = `<option value="">— Select room —</option>` + STATE.rooms.map(r => `<option value="${esc(r.number)}" ${r.number === currentValue ? 'selected' : ''}>${esc(r.number)} · ${esc(r.type)} (${money(r.price)})</option>`).join('');
}

// Reservation Modal
function openReservationModal(id) {
  const form = document.getElementById('reservationForm'); form.reset();
  document.getElementById('reservationId').value = '';
  document.getElementById('reservationModalTitle').textContent = id ? 'Edit Reservation' : 'New Reservation';
  
  if (id) {
    const r = STATE.reservations.find(x => x.id === id);
    if (r) {
      document.getElementById('reservationId').value = r.id;
      document.getElementById('reservationGuest').value = r.guest;
      document.getElementById('reservationCheckIn').value = r.check_in;
      document.getElementById('reservationCheckOut').value = r.check_out;
      document.getElementById('reservationAdults').value = r.adults || 1;
      document.getElementById('reservationSource').value = r.source || 'Direct';
      document.getElementById('reservationStatus').value = r.status;
      populateRoomSelect('reservationRoom', r.room);
    }
  } else {
    document.getElementById('reservationCheckIn').value = STATE.today;
    populateRoomSelect('reservationRoom', '');
  }
  openModal('reservationModal');
}

document.getElementById('reservationForm').addEventListener('submit', async e => {
  e.preventDefault();
  const payload = { data: {
    id: document.getElementById('reservationId').value,
    guest: document.getElementById('reservationGuest').value.trim(),
    room: document.getElementById('reservationRoom').value,
    checkIn: document.getElementById('reservationCheckIn').value,
    checkOut: document.getElementById('reservationCheckOut').value,
    adults: Number(document.getElementById('reservationAdults').value),
    source: document.getElementById('reservationSource').value,
    status: document.getElementById('reservationStatus').value
  }};
  const res = await api('reservation.save', payload, 'POST');
  if (!res.ok) { toast(res.error, 'danger'); return; }
  toast('Reservation saved.'); closeModal('reservationModal'); bootstrap();
});

// Room Modal
function openRoomModal(number) {
  const form = document.getElementById('roomForm'); form.reset();
  document.getElementById('roomFormId').value = '';
  document.getElementById('roomModalTitle').textContent = number ? 'Edit Room' : 'Add Room';
  if (number) {
    const r = STATE.rooms.find(x => x.number === number);
    if (r) {
      document.getElementById('roomFormId').value = r.number;
      document.getElementById('roomNumber').value = r.number;
      document.getElementById('roomType').value = r.type;
      document.getElementById('roomPrice').value = r.price;
      document.getElementById('roomStatus').value = r.status;
    }
  }
  openModal('roomModal');
}

document.getElementById('roomForm').addEventListener('submit', async e => {
  e.preventDefault();
  const payload = { data: {
    number: document.getElementById('roomNumber').value.trim(),
    type: document.getElementById('roomType').value,
    price: Number(document.getElementById('roomPrice').value),
    status: document.getElementById('roomStatus').value
  }};
  const res = await api('room.save', payload, 'POST');
  if (!res.ok) { toast(res.error, 'danger'); return; }
  toast('Room saved.'); closeModal('roomModal'); bootstrap();
});

// Guest Modal
function openGuestModal(id) {
  const form = document.getElementById('guestForm'); form.reset();
  document.getElementById('guestFormId').value = '';
  document.getElementById('guestModalTitle').textContent = id ? 'Edit Guest' : 'Add Guest';
  if (id) {
    const g = STATE.guests.find(x => x.id === id);
    if (g) {
      document.getElementById('guestFormId').value = g.id;
      document.getElementById('guestName').value = g.name;
      document.getElementById('guestEmail').value = g.email || '';
      document.getElementById('guestPhone').value = g.phone || '';
    }
  }
  openModal('guestModal');
}

document.getElementById('guestForm').addEventListener('submit', async e => {
  e.preventDefault();
  const payload = { data: {
    id: document.getElementById('guestFormId').value,
    name: document.getElementById('guestName').value.trim(),
    email: document.getElementById('guestEmail').value.trim(),
    phone: document.getElementById('guestPhone').value.trim()
  }};
  const res = await api('guest.save', payload, 'POST');
  if (!res.ok) { toast(res.error, 'danger'); return; }
  toast('Guest saved.'); closeModal('guestModal'); bootstrap();
});

// Staff Modal
function openStaffModal(id) {
  const form = document.getElementById('staffForm'); form.reset();
  document.getElementById('staffFormId').value = '';
  document.getElementById('staffModalTitle').textContent = id ? 'Edit Staff' : 'Add Staff';
  if (id) {
    const s = STATE.staff.find(x => x.id === id);
    if (s) {
      document.getElementById('staffFormId').value = s.id;
      document.getElementById('staffName').value = s.name;
      document.getElementById('staffRole').value = s.role;
      document.getElementById('staffShift').value = s.shift;
      document.getElementById('staffEmail').value = s.email || '';
      document.getElementById('staffPhone').value = s.phone || '';
    }
  }
  openModal('staffModal');
}

document.getElementById('staffForm').addEventListener('submit', async e => {
  e.preventDefault();
  const payload = { data: {
    id: document.getElementById('staffFormId').value,
    name: document.getElementById('staffName').value.trim(),
    role: document.getElementById('staffRole').value,
    shift: document.getElementById('staffShift').value,
    email: document.getElementById('staffEmail').value.trim(),
    phone: document.getElementById('staffPhone').value.trim()
  }};
  const res = await api('staff.save', payload, 'POST');
  if (!res.ok) { toast(res.error, 'danger'); return; }
  toast('Staff saved.'); closeModal('staffModal'); bootstrap();
});

// Payment Modal
function openPaymentModal(id) {
  const form = document.getElementById('paymentForm'); form.reset();
  document.getElementById('paymentFormId').value = '';
  document.getElementById('paymentModalTitle').textContent = id ? 'Edit Payment' : 'Record Payment';
  if (id) {
    const p = STATE.payments.find(x => x.id === id);
    if (p) {
      document.getElementById('paymentFormId').value = p.id;
      document.getElementById('paymentGuest').value = p.guest;
      document.getElementById('paymentDate').value = p.date;
      document.getElementById('paymentAmount').value = p.amount;
      document.getElementById('paymentStatus').value = p.status;
      document.getElementById('paymentMethod').value = p.method;
      populateRoomSelect('paymentRoom', p.room);
    }
  } else {
    document.getElementById('paymentDate').value = STATE.today;
    populateRoomSelect('paymentRoom', '');
  }
  openModal('paymentModal');
}

document.getElementById('paymentForm').addEventListener('submit', async e => {
  e.preventDefault();
  const payload = { data: {
    id: document.getElementById('paymentFormId').value,
    guest: document.getElementById('paymentGuest').value.trim(),
    room: document.getElementById('paymentRoom').value,
    date: document.getElementById('paymentDate').value,
    amount: Number(document.getElementById('paymentAmount').value),
    status: document.getElementById('paymentStatus').value,
    method: document.getElementById('paymentMethod').value
  }};
  const res = await api('payment.save', payload, 'POST');
  if (!res.ok) { toast(res.error, 'danger'); return; }
  toast('Payment saved.'); closeModal('paymentModal'); bootstrap();
});

// Confirm Modal
let confirmCallback = null;
function confirmDelete(title, msg, cb, btnText='Delete') {
  document.getElementById('confirmTitle').textContent = title;
  document.getElementById('confirmMessage').textContent = msg;
  document.getElementById('confirmBtn').innerHTML = `<i class="fas fa-trash"></i> ${btnText}`;
  confirmCallback = cb;
  openModal('confirmModal');
}
document.getElementById('confirmBtn').addEventListener('click', () => {
  if (confirmCallback) confirmCallback();
  closeModal('confirmModal');
});

// ---------- EVENT DELEGATION ----------
content.addEventListener('click', async e => {
  const actionEl = e.target.closest('[data-action]');
  if (!actionEl) {
    if (e.target.closest('[data-quick="reservation"]')) return openReservationModal();
    if (e.target.closest('[data-quick="guest"]')) return openGuestModal();
    if (e.target.closest('[data-quick="payment"]')) return openPaymentModal();
    const prev = e.target.closest('[data-page-prev]');
    if (prev) { pageState[prev.dataset.pagePrev]--; navigate(currentSection); return; }
    const next = e.target.closest('[data-page-next]');
    if (next) { pageState[next.dataset.pageNext]++; navigate(currentSection); return; }
    const clear = e.target.closest('[data-clear]');
    if (clear) { searchState[clear.dataset.clear] = ''; pageState[clear.dataset.clear] = 1; navigate(currentSection); return; }
    return;
  }

  const a = actionEl.dataset.action;
  const id = actionEl.dataset.id;
  const number = actionEl.dataset.number;

  if (a === 'checkin') {
    await api('reservation.checkin', { id }, 'POST'); bootstrap();
  } else if (a === 'checkout') {
    await api('reservation.checkout', { id }, 'POST'); bootstrap();
  } else if (a === 'edit-reservation') openReservationModal(id);
  else if (a === 'edit-room') openRoomModal(number);
  else if (a === 'edit-guest') openGuestModal(Number(id));
  else if (a === 'edit-staff') openStaffModal(Number(id));
  else if (a === 'edit-payment') openPaymentModal(id);
  else if (a === 'delete-reservation') {
    const r = STATE.reservations.find(x => String(x.id) === String(id));
    if (r.archived) {
      confirmDelete('Delete Permanently', 'This cannot be undone.', async () => {
        await api('reservation.delete', { id }, 'POST'); bootstrap();
      });
    } else {
      confirmDelete('Archive', 'Move to archive?', async () => {
        await api('reservation.archive', { id }, 'POST'); bootstrap();
      }, 'Archive');
    }
  }
  else if (a === 'delete-room') {
    confirmDelete('Delete Room', 'Delete room permanently?', async () => {
      const res = await api('room.delete', { number }, 'POST');
      if (!res.ok) toast(res.error, 'danger'); else bootstrap();
    });
  }
  else if (a === 'delete-guest') {
    const g = STATE.guests.find(x => String(x.id) === String(id));
    if (g.archived) {
      confirmDelete('Delete Guest', 'Permanently delete?', async () => {
        await api('guest.delete', { id: Number(id) }, 'POST'); bootstrap();
      });
    } else {
      confirmDelete('Archive Guest', 'Move to archive?', async () => {
        await api('guest.archive', { id: Number(id) }, 'POST'); bootstrap();
      }, 'Archive');
    }
  }
  else if (a === 'delete-staff') {
    const s = STATE.staff.find(x => String(x.id) === String(id));
    if (s.archived) {
      confirmDelete('Delete Staff', 'Permanently delete?', async () => {
        await api('staff.delete', { id: Number(id) }, 'POST'); bootstrap();
      });
    } else {
      confirmDelete('Archive Staff', 'Move to archive?', async () => {
        await api('staff.archive', { id: Number(id) }, 'POST'); bootstrap();
      }, 'Archive');
    }
  }
  else if (a === 'delete-payment') {
    const p = STATE.payments.find(x => String(x.id) === String(id));
    if (p.archived) {
      confirmDelete('Delete Payment', 'Permanently delete?', async () => {
        await api('payment.delete', { id }, 'POST'); bootstrap();
      });
    } else {
      confirmDelete('Archive Payment', 'Move to archive?', async () => {
        await api('payment.archive', { id }, 'POST'); bootstrap(); 
      }, 'Archive');
    }
  }
});

// Fix the search focus bug
content.addEventListener('input', e => {
  const map = {reservationsSearchInput:'reservations', roomsSearchInput:'rooms', guestsSearchInput:'guests', staffSearchInput:'staff', paymentsSearchInput:'payments'};
  if (!map[e.target.id]) return;
  const section = map[e.target.id];
  searchState[section] = e.target.value;
  pageState[section] = 1;
  navigate(currentSection);
  const fresh = document.getElementById(e.target.id);
  if (fresh) { fresh.focus(); fresh.setSelectionRange(fresh.value.length, fresh.value.length); }
});

content.addEventListener('change', e => {
  if (e.target.id === 'reservationsArchiveToggle') { archiveState.reservations = e.target.checked; navigate('reservations'); }
  if (e.target.id === 'guestsArchiveToggle') { archiveState.guests = e.target.checked; navigate('guests'); }
  if (e.target.id === 'staffArchiveToggle') { archiveState.staff = e.target.checked; navigate('staff'); }
  if (e.target.id === 'paymentsArchiveToggle') { archiveState.payments = e.target.checked; navigate('payments'); }
});

// Header Controls
document.querySelectorAll('.folder-tab').forEach(t => t.addEventListener('click', () => navigate(t.dataset.page)));
document.getElementById('refreshBtn').addEventListener('click', bootstrap);
document.getElementById('exportBtn').addEventListener('click', () => {
  toast('Preparing export...');
  // We use window.open to trigger the download without navigating away from the page
  window.open(`api/index.php?action=export.csv&section=${currentSection}`, '_blank');
});

document.getElementById('heroActionBtn').addEventListener('click', () => {
  if (currentSection === 'dashboard' || currentSection === 'reservations') openReservationModal();
  if (currentSection === 'rooms') openRoomModal();
  if (currentSection === 'guests') openGuestModal();
  if (currentSection === 'staff') openStaffModal();
  if (currentSection === 'payments') openPaymentModal();
  if (currentSection === 'reports') toast('Export feature is in the top-right button.', 'warn');
});

// Boot
bootstrap();