<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="../img/logo.png" />
  <title>IHRRS — Admin Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --bg:           #f4f7fb;
      --surface:      #ffffff;
      --border:       rgba(0,60,140,0.1);
      --border-focus: rgba(21,96,212,0.4);
      --blue:         #1560d4;
      --blue-dim:     rgba(21,96,212,0.09);
      --cyan:         #0097a7;
      --text:         #111827;
      --muted:        #6b7280;
      --light:        #9ca3af;
      --error:        #dc2626;
      --error-dim:    rgba(220,38,38,0.08);
      --success:      #16a34a;
      --admin-accent: #7c3aed;
      --admin-dim:    rgba(124,58,237,0.09);
    }

    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    html, body {
      height: 100%;
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      overflow-x: hidden;
    }

    /* ── GRID BG ── */
    body::before {
      content: '';
      position: fixed; inset: 0;
      background-image:
        linear-gradient(rgba(21,96,212,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(21,96,212,0.04) 1px, transparent 1px);
      background-size: 48px 48px;
      z-index: 0; pointer-events: none;
    }

    /* ── BLOBS ── */
    .blob { position: fixed; border-radius: 50%; filter: blur(90px); pointer-events: none; z-index: 0; }
    .blob-1 {
      width: 600px; height: 600px;
      background: radial-gradient(circle, rgba(124,58,237,0.1) 0%, transparent 70%);
      top: -180px; left: -120px;
      animation: drift 22s ease-in-out infinite alternate;
    }
    .blob-2 {
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(21,96,212,0.1) 0%, transparent 70%);
      bottom: -100px; right: -80px;
      animation: drift 28s ease-in-out infinite alternate-reverse;
    }
    .blob-3 {
      width: 300px; height: 300px;
      background: radial-gradient(circle, rgba(0,151,167,0.08) 0%, transparent 70%);
      top: 50%; left: 55%;
      animation: drift 18s ease-in-out infinite alternate;
    }
    @keyframes drift {
      from { transform: translate(0,0) scale(1); }
      to   { transform: translate(30px,20px) scale(1.07); }
    }

    /* ── PAGE ── */
    .page {
      position: relative; z-index: 2;
      min-height: 100vh;
      display: flex; flex-direction: column;
    }

    /* ── NAVBAR ── */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 48px; height: 72px;
      background: rgba(244,247,251,0.85);
      backdrop-filter: blur(20px) saturate(180%);
      border-bottom: 1px solid var(--border);
      animation: slideDown 0.7s cubic-bezier(0.16,1,0.3,1) both;
    }
    @keyframes slideDown {
      from { transform: translateY(-100%); opacity: 0; }
      to   { transform: translateY(0); opacity: 1; }
    }
    .nav-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .nav-icon {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--blue), var(--cyan));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 14px rgba(21,96,212,0.25);
    }
    .nav-icon svg { width: 18px; height: 18px; fill: none; stroke: #fff; stroke-width: 2.4; stroke-linecap: round; }
    .nav-wordmark {
      font-family: 'Space Mono', monospace;
      font-size: 1rem; font-weight: 700; letter-spacing: 0.15em; color: var(--text);
    }
    .nav-back {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 0.78rem; font-weight: 500; color: var(--muted);
      text-decoration: none; padding: 7px 14px; border-radius: 8px;
      border: 1px solid var(--border); background: var(--surface);
      transition: color 0.2s, border-color 0.2s, background 0.2s;
    }
    .nav-back:hover { color: var(--blue); border-color: rgba(21,96,212,0.2); background: var(--blue-dim); }
    .nav-back svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; transition: transform 0.2s; }
    .nav-back:hover svg { transform: translateX(-3px); }

    /* ── MAIN ── */
    main {
      flex: 1; display: flex; align-items: center; justify-content: center;
      padding: 96px 24px 48px;
    }

    /* ── LOGIN CARD ── */
    .login-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 48px 44px;
      width: 100%; max-width: 460px;
      box-shadow: 0 8px 40px rgba(21,96,212,0.1), 0 1px 4px rgba(0,0,0,0.04);
      position: relative; overflow: hidden;
      opacity: 0; animation: riseIn 0.7s ease 0.3s both;
    }
    /* Admin purple accent bar */
    .login-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 3px;
      background: linear-gradient(90deg, var(--admin-accent), var(--blue), var(--cyan));
    }

    /* ── CARD HEADER ── */
    .card-header {
      display: flex; flex-direction: column; align-items: center;
      margin-bottom: 36px; text-align: center;
    }
    .role-badge {
      display: inline-flex; align-items: center; gap: 7px;
      font-family: 'Space Mono', monospace;
      font-size: 0.62rem; letter-spacing: 0.18em; text-transform: uppercase;
      color: var(--admin-accent); background: var(--admin-dim);
      border: 1px solid rgba(124,58,237,0.18);
      padding: 5px 14px; border-radius: 50px;
      margin-bottom: 18px;
    }
    .badge-dot {
      width: 5px; height: 5px; border-radius: 50%; background: var(--admin-accent);
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.6);opacity:0.5} }

    .card-logo {
      width: 56px; height: 56px;
      background: linear-gradient(135deg, var(--admin-accent), var(--blue));
      border-radius: 16px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 6px 20px rgba(124,58,237,0.28);
      margin-bottom: 16px;
    }
    .card-logo svg { width: 26px; height: 26px; fill: none; stroke: #fff; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }

    .card-title {
      font-family: 'DM Serif Display', serif;
      font-size: 1.75rem; font-weight: 400; color: var(--text);
      margin-bottom: 4px;
    }
    .card-title em {
      font-style: italic;
      background: linear-gradient(120deg, var(--admin-accent), var(--blue));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .card-sub { font-size: 0.82rem; color: var(--muted); }

    /* ── FORM ── */
    .form-group { margin-bottom: 20px; }
    .form-label {
      display: block;
      font-size: 0.78rem; font-weight: 600; color: var(--text);
      letter-spacing: 0.02em; margin-bottom: 7px;
    }
    .input-wrap { position: relative; }
    .input-icon {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      width: 16px; height: 16px;
      fill: none; stroke: var(--light); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
      pointer-events: none; transition: stroke 0.2s;
    }
    .input-wrap:focus-within .input-icon { stroke: var(--admin-accent); }

    .form-input {
      width: 100%; height: 48px;
      padding: 0 44px 0 44px;
      border: 1.5px solid var(--border); border-radius: 12px;
      background: var(--bg);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.9rem; color: var(--text);
      outline: none;
      transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
    }
    .form-input::placeholder { color: var(--light); }
    .form-input:focus {
      border-color: rgba(124,58,237,0.45);
      background: var(--surface);
      box-shadow: 0 0 0 4px rgba(124,58,237,0.08);
    }

    /* Password toggle */
    .toggle-btn {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; padding: 0;
      display: flex; align-items: center;
    }
    .toggle-btn svg { width: 17px; height: 17px; fill: none; stroke: var(--light); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; transition: stroke 0.2s; }
    .toggle-btn:hover svg { stroke: var(--admin-accent); }

    /* ── ERROR BOX ── */
    .error-box {
      display: none; align-items: center; gap: 8px;
      background: var(--error-dim); border: 1px solid rgba(220,38,38,0.2);
      border-radius: 10px; padding: 10px 14px; margin-bottom: 18px;
    }
    .error-box.visible { display: flex; }
    .error-box svg { width: 15px; height: 15px; fill: none; stroke: var(--error); stroke-width: 2; stroke-linecap: round; flex-shrink: 0; }
    .error-box span { font-size: 0.78rem; color: var(--error); }

    /* ── SUBMIT BUTTON ── */
    .submit-btn {
      width: 100%; height: 50px; margin-top: 8px;
      background: linear-gradient(135deg, var(--admin-accent) 0%, var(--blue) 100%);
      color: #fff; border: none; border-radius: 12px;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.92rem; font-weight: 600; letter-spacing: 0.03em;
      cursor: pointer;
      box-shadow: 0 6px 24px rgba(124,58,237,0.28);
      position: relative; overflow: hidden;
      transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s ease;
    }
    .submit-btn::after {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
      opacity: 0; transition: opacity 0.3s;
    }
    .submit-btn:hover { transform: translateY(-2px) scale(1.01); box-shadow: 0 10px 32px rgba(124,58,237,0.35); }
    .submit-btn:hover::after { opacity: 1; }
    .submit-btn:active { transform: scale(0.98); }

    /* ── FORGOT ── */
    .forgot-link {
      display: block; text-align: center; margin-top: 18px;
      font-size: 0.78rem; color: var(--muted); text-decoration: none;
      transition: color 0.2s;
    }
    .forgot-link:hover { color: var(--admin-accent); }

    /* ── SUCCESS MODAL ── */
    .modal-overlay {
      display: none; position: fixed; inset: 0; z-index: 200;
      background: rgba(17,24,39,0.4); backdrop-filter: blur(6px);
      align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: 20px; padding: 36px 32px;
      max-width: 360px; width: 90%; text-align: center;
      box-shadow: 0 24px 60px rgba(0,0,0,0.12);
      animation: riseIn 0.4s ease both;
    }
    .modal-icon {
      width: 52px; height: 52px; background: rgba(22,163,74,0.1);
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
      margin: 0 auto 16px;
    }
    .modal-icon svg { width: 24px; height: 24px; fill: none; stroke: var(--success); stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .modal-title { font-family: 'DM Serif Display', serif; font-size: 1.3rem; margin-bottom: 8px; }
    .modal-msg { font-size: 0.85rem; color: var(--muted); line-height: 1.6; margin-bottom: 24px; }
    .modal-close {
      width: 100%; height: 44px;
      background: var(--admin-accent); color: #fff; border: none; border-radius: 10px;
      font-family: 'DM Sans', sans-serif; font-size: 0.88rem; font-weight: 600;
      cursor: pointer; transition: background 0.2s;
    }
    .modal-close:hover { background: #6d28d9; }

    /* ── ANIMATIONS ── */
    @keyframes riseIn {
      from { transform: translateY(20px); opacity: 0; }
      to   { transform: translateY(0); opacity: 1; }
    }

    /* ── FOOTER ── */
    footer {
      position: relative; z-index: 2;
      display: flex; align-items: center; justify-content: space-between;
      padding: 18px 48px; border-top: 1px solid var(--border);
      background: rgba(244,247,251,0.9); backdrop-filter: blur(10px);
    }
    .footer-copy {
      font-family: 'Space Mono', monospace;
      font-size: 0.56rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--light);
    }
    .footer-links { display: flex; gap: 20px; }
    .footer-links a { font-size: 0.7rem; color: var(--light); text-decoration: none; transition: color 0.2s; }
    .footer-links a:hover { color: var(--admin-accent); }

    /* ── RESPONSIVE ── */
    @media (max-width: 520px) {
      .login-card { padding: 36px 24px; border-radius: 20px; }
      nav { padding: 0 20px; }
      footer { padding: 14px 20px; flex-direction: column; gap: 8px; text-align: center; }
    }
  </style>
</head>
<body>

  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>

  <div class="page">

    <!-- NAV -->
    <nav>
      <a href="../index" class="nav-brand">
        <div class="nav-icon">
          <svg viewBox="0 0 24 24"><path d="M12 3v18M3 12h18"/></svg>
        </div>
        <span class="nav-wordmark">IHRRS</span>
      </a>
      <a href="role" class="nav-back">
        <svg viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back
      </a>
    </nav>

    <!-- MAIN -->
    <main>
      <div class="login-card" id="login-form">

        <!-- Header -->
        <div class="card-header">
          <div class="role-badge">
            <span class="badge-dot"></span>
            Administrator
          </div>
          <div class="card-logo">
            <!-- Shield / admin icon -->
            <svg viewBox="0 0 24 24">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
          </div>
          <h1 class="card-title">Welcome, <em>Admin</em></h1>
          <p class="card-sub">Sign in to access the administration panel</p>
        </div>

        <!-- Error box -->
        <div class="error-box" id="error-box">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <span id="error-text">Invalid username or password.</span>
        </div>

        <!-- PHP inline error (server-side) -->
        <?php if (isset($_GET['error'])): ?>
        <div class="error-box visible" style="margin-bottom:18px">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <span><?php echo htmlspecialchars($_GET['error']); ?></span>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="../LOGIN/admin_login" method="POST">

          <!-- Username -->
          <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
              <input class="form-input" type="text" id="username" name="username"
                     placeholder="Enter your username" required autocomplete="username"
                     value="<?php echo isset($_COOKIE['admin_username']) ? htmlspecialchars($_COOKIE['admin_username']) : ''; ?>" />
            </div>
          </div>

          <!-- Password -->
          <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              <input class="form-input" type="password" id="password" name="password"
                     placeholder="Enter your password" required autocomplete="current-password" />
              <button type="button" class="toggle-btn" id="togglePassword" aria-label="Toggle password visibility">
                <svg id="eye-show" viewBox="0 0 24 24">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg id="eye-hide" viewBox="0 0 24 24" style="display:none">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                  <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                  <path d="m1 1 22 22"/>
                </svg>
              </button>
            </div>
          </div>

          <button type="submit" class="submit-btn">Sign In</button>
        </form>

        <a href="../LOGIN/admin_forgot_password" class="forgot-link">Forgot your password?</a>
      </div>
    </main>

    <!-- FOOTER -->
    <footer>
      <span class="footer-copy">© 2025 IHRRS — Integrated Health Records & Reporting System</span>
    </footer>
  </div>

  <!-- Success Modal -->
  <div class="modal-overlay" id="successModal">
    <div class="modal-box">
      <div class="modal-icon">
        <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
      </div>
      <h2 class="modal-title">Access Granted</h2>
      <p class="modal-msg" id="success-message">You have signed in successfully.</p>
      <button class="modal-close" id="modalCloseBtn">Continue to Dashboard</button>
    </div>
  </div>

  <script>
    // ── Password toggle ──
    const toggleBtn     = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeShow       = document.getElementById('eye-show');
    const eyeHide       = document.getElementById('eye-hide');

    toggleBtn.addEventListener('click', () => {
      const hidden = passwordInput.type === 'password';
      passwordInput.type = hidden ? 'text' : 'password';
      eyeShow.style.display = hidden ? 'none'  : 'block';
      eyeHide.style.display = hidden ? 'block' : 'none';
    });

    // ── Cookie helper ──
    function getCookie(name) {
      const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
      return match ? decodeURIComponent(match[2]) : null;
    }

    // ── Pre-fill username from cookie ──
    document.addEventListener('DOMContentLoaded', () => {
      const saved = getCookie('admin_username');
      const usernameInput = document.getElementById('username');
      if (saved && !usernameInput.value) {
        usernameInput.value = saved;
        document.getElementById('password').focus();
      }
    });

    // ── URL param handling ──
    const params = new URLSearchParams(window.location.search);

    // Success modal
    const successMsg = params.get('success');
    if (successMsg) {
      const modal = document.getElementById('successModal');
      const msgEl = document.getElementById('success-message');
      if (msgEl) msgEl.textContent = successMsg;
      modal.classList.add('open');
    }

    // Error via URL param (JS fallback)
    const errorMsg = params.get('error');
    if (errorMsg) {
      const box  = document.getElementById('error-box');
      const text = document.getElementById('error-text');
      if (text) text.textContent = errorMsg;
      if (box)  box.classList.add('visible');
    }

    // Close modal
    document.getElementById('modalCloseBtn').addEventListener('click', () => {
      document.getElementById('successModal').classList.remove('open');
    });
    document.getElementById('successModal').addEventListener('click', e => {
      if (e.target === e.currentTarget) e.currentTarget.classList.remove('open');
    });
  </script>

</body>
</html>