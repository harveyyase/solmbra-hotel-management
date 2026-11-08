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
<title>SOLMBRA | Account Profile</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="assets/css/base.css">
<link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>

<div class="app">
<header>
 <a class="brand" href="dash.php" aria-label="SOLMBRA home">
   <span class="brand-icon" aria-hidden="true">
     <img src="assets/img/logo.png" alt="SOLMBRA" />
   </span>
   <span><h1>SOLMBRA</h1><p>HOTEL MANAGEMENT</p></span>
 </a>
 <a class="back-link" href="dash.php">← <span>Back to dashboard</span></a>
</header>

<section class="page-intro">
  <div class="eyebrow">Account settings</div>
  <h2>Account Profile</h2>
  <p>Review your membership and keep your contact details up to date.</p>
</section>

<main class="profile-layout">
 <section class="profile-summary" aria-label="Profile summary">
  <div class="profile-avatar" id="profileAvatar">A</div>
  <div class="profile-name" id="summaryName">Admin</div>
  <div class="profile-email" id="summaryEmail">admin@solmbra.com</div>
  <span class="profile-role">♙ <span id="summaryRole">Administrator</span></span>
  <span class="profile-status"><span class="status-dot"></span><span id="summaryStatus">Active</span></span>
  
  <button type="button" class="logout-btn" id="logoutBtn">
    <i class="fas fa-sign-out-alt"></i> Logout
  </button>
 </section>

 <section class="profile-details">
  <div class="details-head">
    <h3>Personal details</h3>
    <button type="button" class="edit-btn" id="editBtn">✎ Edit profile</button>
  </div>

  <!-- View Mode -->
  <div class="fields" id="viewFields">
   <div class="field"><div class="field-label">Full name</div><div class="field-value" id="vName">Admin</div></div>
   <div class="field"><div class="field-label">Account status</div><div class="field-value" id="vStatus">Active</div></div>
   <div class="field"><div class="field-label">Email address</div><div class="field-value" id="vEmail">admin@solmbra.com</div></div>
   <div class="field"><div class="field-label">Phone number</div><div class="field-value" id="vPhone"><span class="empty-value">Not provided</span></div></div>
   <div class="field"><div class="field-label">Member since</div><div class="field-value" id="vMember">October 2026</div></div>
   <div class="field"><div class="field-label">Access role</div><div class="field-value" id="vRole">Administrator</div></div>
  </div>

  <!-- Edit Mode -->
  <div class="edit-form" id="editFormWrap">
   <form id="profileForm" autocomplete="on">
    <div class="form-grid">
     <div class="form-group"><label for="nameInput">Full name</label><input id="nameInput" name="name" type="text" maxlength="80" required></div>
     <div class="form-group"><label for="emailInput">Email address</label><input id="emailInput" name="email" type="email" maxlength="120"></div>
     <div class="form-group"><label for="phoneInput">Phone number</label><input id="phoneInput" name="phone" type="tel" maxlength="40" placeholder="e.g. 0917 123 4567"></div>
     <div class="form-group"><label for="roleInput">Access role</label><input id="roleInput" type="text" disabled></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="save-btn">✓ Save changes</button>
      <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
    </div>
    <div class="message" id="message" role="status" aria-live="polite"></div>
   </form>
  </div>
 </section>
</main>

<p class="note">Your profile details are securely saved to the local database.</p>
<footer><span>© 2026 SOLMBRA</span><span>YOUR SPACE. YOUR SYSTEM.</span></footer>
</div>

<!-- Toast notifications container -->
<div class="toast-stack" id="toastStack" aria-live="polite"></div>

<!-- Load shared utilities then profile logic -->
<script src="assets/js/util.js"></script>
<script src="assets/js/profile.js"></script>
</body>
</html>