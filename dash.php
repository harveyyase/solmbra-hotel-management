<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$u = require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0d0a12">
<title>SOLMBRA | Hotel Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="assets/css/base.css?v=99">
<link rel="stylesheet" href="assets/css/dashboard.css?v=99">
<link rel="stylesheet" href="assets/css/animations.css">
</head>
<body>

<div class="app">
  <header>
    <div class="brand">
      <div class="brand-icon"><img src="assets/img/logo.png" alt="SOLMBRA" /></div>
      <div><h1>SOLMBRA</h1><p>HOTEL MANAGEMENT</p></div>
    </div>
    <div class="header-right">
      <span class="date" id="today"></span>
      <div class="inbox-wrap" id="inboxWrap">
        <button class="icon-btn" id="inboxBtn" title="Notifications">
          <i class="fas fa-bell"></i>
          <span class="badge" id="inboxBadge" style="display:none">0</span>
        </button>
        <div class="inbox-dropdown" id="inboxDropdown">
          <div class="inbox-header"><span><i class="fas fa-inbox"></i> Notifications</span>
            <button id="inboxClearBtn">Clear all</button></div>
          <div class="inbox-list" id="inboxList"></div>
        </div>
      </div>
      <a class="avatar" id="profileShortcut" href="profile.php" title="Open profile">
        <span id="profileInitial"><?= htmlspecialchars(substr($u['display_name'] ?? 'A', 0, 1)) ?></span>
      </a>
    </div>
  </header>

  <section class="intro">
    <div class="eyebrow">Management workspace</div>
    <div class="hero-actions">
      <button class="primary-btn" id="heroActionBtn"><i class="fas fa-plus-circle"></i> <span id="heroActionText">New Reservation</span></button>
      <button class="secondary-btn" id="exportBtn"><i class="fas fa-file-export"></i> Export CSV</button>
      <button class="secondary-btn" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>
  </section>

  <section class="folder-area">
    <nav class="folder-tabs" aria-label="Hotel management sections">
      <button class="folder-tab active" data-page="dashboard"><span class="folder-icon">▤</span><span class="tab-title">Dashboard</span></button>
      <button class="folder-tab" data-page="reservations"><span class="folder-icon">▧</span><span class="tab-title">Reservations</span></button>
      <button class="folder-tab" data-page="rooms"><span class="folder-icon">▣</span><span class="tab-title">Rooms</span></button>
      <button class="folder-tab" data-page="guests"><span class="folder-icon">♙</span><span class="tab-title">Guests</span></button>
      <button class="folder-tab" data-page="staff"><span class="folder-icon">♧</span><span class="tab-title">Staff</span></button>
      <button class="folder-tab" data-page="payments"><span class="folder-icon">₱</span><span class="tab-title">Payments</span></button>
      <button class="folder-tab" data-page="reports"><span class="folder-icon">▥</span><span class="tab-title">Reports</span></button>
    </nav>
    <main class="folder-body" id="page-content" aria-live="polite"></main>
  </section>

  <footer><span>© 2026 SOLMBRA HOTEL</span><span>YOUR SPACE. YOUR SYSTEM.</span></footer>
</div>

<!-- Includes all 7 modals + the toast stack. Do NOT add another toast-stack below. -->
<?php include __DIR__ . '/includes/modals.php'; ?>

<script src="assets/js/util.js"></script>
<script src="assets/js/notifications.js"></script>
<script src="assets/js/dashboard.js?v=99"></script>
<script src="assets/js/interactions.js" defer></script>

</body>
</html>