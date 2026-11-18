<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (current_user()) {
    header('Location: dash.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Session expired. Reload the page.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $res = attempt_login($username, $password, $_SERVER['REMOTE_ADDR'] ?? '');
        if ($res['ok']) {
            header('Location: dash.php');
            exit;
        }
        $error = $res['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0D0E20">
<title>SOLMBRA | Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="assets/css/base.css?v=100">
<link rel="stylesheet" href="assets/css/login.css?v=100">
<link rel="stylesheet" href="assets/css/animations.css">
</head>
<body style="display:flex; align-items:center; justify-content:center; min-height:100vh; padding:20px;">

<!-- ============ LOADING SCREEN ============ -->
<div class="loading-screen" id="loadingScreen">
  <img src="https://raw.githubusercontent.com/CHROMAPSTR/GRAPHIC-DESIGNS/refs/heads/main/Untitled%20design%20(2).gif" alt="Loading..." />
</div>

<div class="auth-container">
  <div class="demo-badge"><i class="fas fa-shield-alt"></i> Secure Login</div>
  <div class="brand">
    <div class="brand-icon">
      <img src="assets/img/logo.png" alt="SOLMBRA" />
    </div>
    <h1>SOLMBRA</h1>
    <div class="tagline">Hotel management <span>made elegant</span></div>
  </div>

  <div class="form-panel">
    <form id="loginForm" method="post" autocomplete="on">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <div class="form-group">
        <label for="loginName"><i class="fas fa-user"></i> Username</label>
        <div class="input-wrap">
          <i class="fas fa-user input-icon"></i>
          <input type="text" id="loginName" name="username" placeholder="Enter your username" required autofocus />
        </div>
      </div>

      <div class="form-group">
        <label for="loginPass"><i class="fas fa-key"></i> Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" id="loginPass" name="password" placeholder="••••••••" required />
          <button type="button" class="toggle-eye" id="togglePin" tabindex="-1">
            <i class="fas fa-eye-slash"></i>
          </button>
        </div>
        <div class="pin-dots-wrap">
          <div class="pin-dot"></div><div class="pin-dot"></div>
          <div class="pin-dot"></div><div class="pin-dot"></div>
        </div>
      </div>

      <button type="submit" class="submit-btn" id="loginBtn">
        <span class="btn-text"><i class="fas fa-bolt"></i> Login</span>
      </button>

      <div class="form-message <?= $error ? 'show error' : '' ?>"><?= htmlspecialchars($error) ?></div>
      <a href="contact.php" class="forgot-link">Forgot password?</a>
    </form>
    <div class="auth-footer">
      <i class="fas fa-lock"></i> Authorized user access only
    </div>
  </div>
</div>

<script src="assets/js/login.js?v=100"></script>
<script src="assets/js/interactions.js" defer></script>

<!-- ============ LOADING SCREEN TIMER ============ -->
<script>
  // Wait 4.5 seconds, then trigger the fade out transition
  setTimeout(() => {
    const loader = document.getElementById('loadingScreen');
    if (loader) {
      loader.classList.add('hidden');
      // Remove from DOM after the 0.8s fade-out transition finishes
      setTimeout(() => loader.remove(), 800);
    }
  }, 4500);
</script>

</body>
</html>
