<!-- includes/modals.php -->
<!-- All 7 modals + toast stack, copied verbatim from the original dash.html. -->
<!-- Markup, classes, and IDs unchanged. -->

<!-- ============ RESERVATION MODAL ============ -->
<div class="modal-overlay" id="reservationModal">
  <div class="modal">
    <div class="modal-header"><h2><i class="fas fa-calendar-check"></i> <span id="reservationModalTitle">New Reservation</span></h2><button class="modal-close" data-close="reservationModal">&times;</button></div>
    <form id="reservationForm" autocomplete="off">
      <input type="hidden" id="reservationId" />
      <div class="form-group"><label>Guest Name</label><input type="text" id="reservationGuest" list="guestNames" placeholder="Type a name…" required /><datalist id="guestNames"></datalist></div>
      <div class="form-group"><label>Room</label><select id="reservationRoom" required></select></div>
      <div class="form-row">
        <div class="form-group"><label>Check-in</label><input type="date" id="reservationCheckIn" required /></div>
        <div class="form-group"><label>Check-out</label><input type="date" id="reservationCheckOut" required /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Adults</label><input type="number" id="reservationAdults" min="1" value="1" required /></div>
        <div class="form-group"><label>Source</label><select id="reservationSource"><option>Direct</option><option>Website</option><option>Phone</option><option>Agency</option><option>Walk-in</option></select></div>
      </div>
      <div class="form-group"><label>Status</label><select id="reservationStatus"><option>Pending</option><option>Confirmed</option><option>Checked In</option><option>Checked Out</option><option>Cancelled</option><option>Overdue</option></select></div>
      <div class="form-actions"><button type="button" class="btn-cancel" data-close="reservationModal">Cancel</button><button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Reservation</button></div>
    </form>
  </div>
</div>

<!-- ============ ROOM MODAL ============ -->
<div class="modal-overlay" id="roomModal">
  <div class="modal">
    <div class="modal-header"><h2><i class="fas fa-door-open"></i> <span id="roomModalTitle">Add Room</span></h2><button class="modal-close" data-close="roomModal">&times;</button></div>
    <form id="roomForm" autocomplete="off">
      <input type="hidden" id="roomFormId" />
      <div class="form-group"><label>Room Number</label><input type="text" id="roomNumber" placeholder="e.g. 101" required /></div>
      <div class="form-row">
        <div class="form-group"><label>Type</label><select id="roomType"><option>Standard</option><option>Deluxe</option><option>Suite</option><option>Penthouse</option></select></div>
        <div class="form-group"><label>Nightly Rate (₱)</label><input type="number" id="roomPrice" min="0" step="100" required /></div>
      </div>
      <div class="form-group"><label>Status</label><select id="roomStatus"><option>Available</option><option>Occupied</option><option>Maintenance</option></select></div>
      <div class="form-actions"><button type="button" class="btn-cancel" data-close="roomModal">Cancel</button><button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Room</button></div>
    </form>
  </div>
</div>

<!-- ============ GUEST MODAL ============ -->
<div class="modal-overlay" id="guestModal">
  <div class="modal">
    <div class="modal-header"><h2><i class="fas fa-user-edit"></i> <span id="guestModalTitle">Add Guest</span></h2><button class="modal-close" data-close="guestModal">&times;</button></div>
    <form id="guestForm" autocomplete="off">
      <input type="hidden" id="guestFormId" />
      <div class="form-group"><label>Full Name</label><input type="text" id="guestName" required /></div>
      <div class="form-group"><label>Email</label><input type="email" id="guestEmail" placeholder="guest@email.com" /></div>
      <div class="form-group"><label>Phone</label><input type="text" id="guestPhone" placeholder="0917 123 4567" /></div>
      <div class="form-row">
        <div class="form-group"><label>Assigned Room (optional)</label><select id="guestRoom"><option value="">— None —</option></select></div>
        <div class="form-group"><label>Check-in Date</label><input type="date" id="guestCheckIn" required /></div>
      </div>
      <div class="form-group"><label>Check-out Date</label><input type="date" id="guestCheckOut" required /></div>
      <div class="form-actions"><button type="button" class="btn-cancel" data-close="guestModal">Cancel</button><button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Guest</button></div>
    </form>
  </div>
</div>

<!-- ============ STAFF MODAL ============ -->
<div class="modal-overlay" id="staffModal">
  <div class="modal">
    <div class="modal-header"><h2><i class="fas fa-user-tie"></i> <span id="staffModalTitle">Add Staff</span></h2><button class="modal-close" data-close="staffModal">&times;</button></div>
    <form id="staffForm" autocomplete="off">
      <input type="hidden" id="staffFormId" />
      <div class="form-group"><label>Full Name</label><input type="text" id="staffName" required /></div>
      <div class="form-row">
        <div class="form-group"><label>Role</label><select id="staffRole"><option>Hotel Manager</option><option>Front Desk</option><option>Housekeeping</option><option>Maintenance</option><option>Security</option><option>Other</option></select></div>
        <div class="form-group"><label>Shift</label><select id="staffShift"><option>Morning</option><option>Afternoon</option><option>Night</option></select></div>
      </div>
      <div class="form-group"><label>Email</label><input type="email" id="staffEmail" placeholder="staff@solmbra.com" /></div>
      <div class="form-group"><label>Phone</label><input type="text" id="staffPhone" placeholder="0917 123 4567" /></div>
      <div class="form-actions"><button type="button" class="btn-cancel" data-close="staffModal">Cancel</button><button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Staff</button></div>
    </form>
  </div>
</div>

<!-- ============ PAYMENT MODAL ============ -->
<div class="modal-overlay" id="paymentModal">
  <div class="modal">
    <div class="modal-header"><h2><i class="fas fa-coins"></i> <span id="paymentModalTitle">Record Payment</span></h2><button class="modal-close" data-close="paymentModal">&times;</button></div>
    <form id="paymentForm" autocomplete="off">
      <input type="hidden" id="paymentFormId" />
      <div class="form-group"><label>Guest Name</label><input type="text" id="paymentGuest" list="guestNames" placeholder="Type a name…" required /></div>
      <div class="form-group"><label>Room</label><select id="paymentRoom" required></select></div>
      <div class="form-row">
        <div class="form-group"><label>Date</label><input type="date" id="paymentDate" required /></div>
        <div class="form-group"><label>Amount (₱)</label><input type="number" id="paymentAmount" min="0" step="100" required /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Status</label><select id="paymentStatus"><option>Paid</option><option>Pending</option><option>Overdue</option></select></div>
        <div class="form-group"><label>Method</label><select id="paymentMethod"><option>Cash</option><option>Card</option><option>Bank Transfer</option><option>GCash</option></select></div>
      </div>
      <div class="form-actions"><button type="button" class="btn-cancel" data-close="paymentModal">Cancel</button><button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Payment</button></div>
    </form>
  </div>
</div>

<!-- ============ TIMER MODAL ============ -->
<div class="modal-overlay" id="timerModal">
  <div class="modal" style="max-width:420px">
    <div class="modal-header"><h2><i class="fas fa-stopwatch"></i> <span id="timerModalTitle">Timer</span></h2><button class="modal-close" data-close="timerModal">&times;</button></div>
    <p style="color:var(--muted);font-size:12px;line-height:1.6;margin-bottom:6px" id="timerModalDesc"></p>
    <div class="timer-display" id="timerDisplay">00:00</div>
    <p style="text-align:center;color:var(--muted);font-size:11px;margin-bottom:16px" id="timerStatusLine">No timer running</p>
    <div class="timer-presets" id="timerPresets"></div>
    <div class="form-actions">
      <button type="button" class="btn-cancel" id="timerCancelBtn">Cancel timer</button>
      <button type="button" class="btn-submit" id="timerStartBtn"><i class="fas fa-play"></i> Start</button>
    </div>
  </div>
</div>

<!-- ============ CONFIRM MODAL ============ -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal" style="max-width:400px">
    <div class="modal-header"><h2><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> <span id="confirmTitle">Confirm Action</span></h2><button class="modal-close" data-close="confirmModal">&times;</button></div>
    <p style="color:var(--muted);font-size:13px;line-height:1.6;margin-bottom:18px" id="confirmMessage">Are you sure?</p>
    <div class="form-actions"><button type="button" class="btn-cancel" data-close="confirmModal">Cancel</button><button type="button" class="btn-submit danger" id="confirmBtn"><i class="fas fa-trash"></i> Delete</button></div>
  </div>
</div>

<!-- ============ TOAST STACK ============ -->
<div class="toast-stack" id="toastStack" aria-live="polite"></div>