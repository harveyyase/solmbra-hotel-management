<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0d0a12">
<title>SOLMBRA | Contact Support</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap');
:root{--bg:#0d0a12;--surface:#130e1c;--purple:#58327b;--purple-light:#8755b5;--accent:#c7a4f3;--text:#f4eff9;--muted:#a69bb2;--border:#352640;--success:#8de0b8;--danger:#ff9da9}

*{box-sizing:border-box;margin:0;padding:0}

/* Static Background */
body{
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  background-color: var(--bg);
  background-image:
    radial-gradient(ellipse at 18% 24%, #512b72 0%, transparent 45%),
    radial-gradient(ellipse at 82% 36%, #34204e 0%, transparent 45%),
    radial-gradient(ellipse at 55% 88%, #271638 0%, transparent 45%);
  background-attachment: fixed;
  background-size: cover;
  color:var(--text);
  font-family:'DM Sans',sans-serif;
  text-shadow:0 1px 2px #00000040;
  padding: 30px 20px;
}

.contact-page{width:100%;max-width:900px;animation:openFolder .4s ease}
@keyframes openFolder{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}

/* Brand */
.brand{text-align:center;margin-bottom:35px}
.brand-icon{width:90px;height:90px;margin:0 auto 15px;display:flex;align-items:center;justify-content:center;background:transparent}
.brand-icon img{width:100%;height:100%;object-fit:contain}
.brand h1{font:700 28px 'Outfit',sans-serif;letter-spacing:1px;margin-bottom:6px}
.tagline{font-size:13px;color:var(--muted);letter-spacing:1px}
.tagline span{color:var(--accent);font-weight:600}

/* Contact Grid */
.contact-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;margin-bottom:35px}

/* Contact Card */
.contact-card{background:#130e1c;border:1px solid #4a3160;border-radius:16px;padding:30px 24px;display:flex;flex-direction:column;align-items:center;text-align:center;box-shadow:0 15px 35px #00000050;transition:border-color .2s, box-shadow .2s}
.contact-card:hover{border-color:var(--purple-light);box-shadow:0 20px 40px #00000070}

.avatar-ring{width:90px;height:90px;border-radius:50%;border:2px solid var(--accent);padding:3px;margin-bottom:16px;box-shadow:0 0 15px #c7a4f340;overflow:hidden;background:#2a1c39}
.avatar-ring img{width:100%;height:100%;border-radius:50%;object-fit:cover}

.contact-name{font:600 20px 'Outfit',sans-serif;margin-bottom:8px}
.role-badge{display:inline-flex;align-items:center;gap:6px;background:#24182f;border:1px solid #49315d;color:var(--accent);padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;margin-bottom:24px}

.contact-details{width:100%;display:flex;flex-direction:column;gap:12px;margin-bottom:24px}
.detail-row{display:flex;align-items:center;gap:12px;background:#0a0710;border:1px solid #352640;padding:12px;border-radius:10px;text-decoration:none;color:inherit;transition:background .2s,border-color .2s}
.detail-row:hover{background:#1a1225;border-color:var(--purple-light)}
.detail-icon{width:32px;height:32px;display:grid;place-items:center;background:#24182f;border-radius:8px;color:var(--accent);font-size:14px;flex-shrink:0}
.detail-info{display:flex;flex-direction:column;text-align:left;overflow:hidden}
.detail-label{font-size:10px;color:var(--muted);margin-bottom:2px}
.detail-value{font-size:12px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

.github-btn{width:100%;padding:12px;border-radius:8px;background:#603984;border:1px solid #8054a6;color:white;text-decoration:none;font:600 13px 'DM Sans',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px;transition:filter .2s, box-shadow .2s}
.github-btn:hover{filter:brightness(1.15);box-shadow:0 4px 12px #60398460}

/* Back Link */
.back-link{display:flex;align-items:center;justify-content:center;gap:8px;width:max-content;margin:0 auto;padding:10px 18px;border:1px solid #4a3160;background:#1a1225;color:var(--muted);text-decoration:none;border-radius:8px;font-size:12px;font-weight:600;transition:background .2s, color .2s, border-color .2s}
.back-link:hover{background:#24182f;color:var(--text);border-color:var(--accent)}

@media(max-width:480px){
  .contact-grid{grid-template-columns:1fr}
  .brand h1{font-size:24px}
}
</style>
</head>
<body>

<div class="contact-page">

  <!-- Brand -->
  <div class="brand">
    <div class="brand-icon">
      <img src="https://raw.githubusercontent.com/CHROMAPSTR/GRAPHIC-DESIGNS/refs/heads/main/bailamoss.png" alt="SOLMBRA" />
    </div>
    <h1>SOLMBRA</h1>
    <div class="tagline">Get in <span>touch</span> · Development Team</div>
  </div>

  <!-- Contact Cards -->
  <div class="contact-grid">

    <!-- Developer 1 -->
    <div class="contact-card">
      <div class="avatar-ring">
        <img src="https://github.com/CHROMAPSTR.png" alt="Kintanar Matteo" />
      </div>
      <div class="contact-name">Kintanar Matteo</div>
      <span class="role-badge"><i class="fas fa-code"></i> Senior Developer</span>

      <div class="contact-details">
        <a class="detail-row" href="tel:09170000001">
          <div class="detail-icon"><i class="fas fa-phone-alt"></i></div>
          <div class="detail-info">
            <div class="detail-label">Phone</div>
            <div class="detail-value">0917 000 0001</div>
          </div>
        </a>
        <a class="detail-row" href="https://mail.google.com/mail/?view=cm&fs=1&to=kintanarfrancis693@gmail.com" target="_blank" rel="noopener noreferrer">
          <div class="detail-icon"><i class="fas fa-envelope"></i></div>
          <div class="detail-info">
            <div class="detail-label">Email</div>
            <div class="detail-value">kintanarfrancis693@gmail.com</div>
          </div>
        </a>
      </div>

      <a class="github-btn" href="https://github.com/CHROMAPSTR" target="_blank" rel="noopener noreferrer">
        <i class="fab fa-github"></i> View GitHub
      </a>
    </div>

    <!-- Developer 2 -->
    <div class="contact-card">
      <div class="avatar-ring">
        <img src="https://github.com/ryuu-script.png" alt="Christian J. Salang" />
      </div>
      <div class="contact-name">Christian J. Salang</div>
      <span class="role-badge"><i class="fas fa-code"></i> Junior Developer</span>

      <div class="contact-details">
        <a class="detail-row" href="tel:09170000002">
          <div class="detail-icon"><i class="fas fa-phone-alt"></i></div>
          <div class="detail-info">
            <div class="detail-label">Phone</div>
            <div class="detail-value">0917 000 0002</div>
          </div>
        </a>
        <a class="detail-row" href="https://mail.google.com/mail/?view=cm&fs=1&to=christian296112@gmail.com" target="_blank" rel="noopener noreferrer">
          <div class="detail-icon"><i class="fas fa-envelope"></i></div>
          <div class="detail-info">
            <div class="detail-label">Email</div>
            <div class="detail-value">christian296112@gmail.com</div>
          </div>
        </a>
      </div>

      <a class="github-btn" href="https://github.com/ryuu-script" target="_blank" rel="noopener noreferrer">
        <i class="fab fa-github"></i> View GitHub
      </a>
    </div>

  </div>

  <a class="back-link" href="index.html"><i class="fas fa-arrow-left"></i> Back to Login</a>

</div>

</body>
</html>