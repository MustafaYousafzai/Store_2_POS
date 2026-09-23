<?php
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/../config/auth.php';
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/dashboard/index.php');
    } else {
        redirect('/pos/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function() {
            var theme = localStorage.getItem('pos_theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', theme);
            document.documentElement.classList.add(theme + '-theme');
        })();
    </script>
    <title><?= defined('STORE_NAME') ? STORE_NAME : 'One Dollar Shop' ?> - Login</title>
    <!-- Favicon (Browser Tab Icon) -->
    <link rel="icon" type="image/png" href="<?= logoUrl() ?>">
    <link rel="shortcut icon" type="image/png" href="<?= logoUrl() ?>">
    <link rel="apple-touch-icon" href="<?= logoUrl() ?>">
    <!-- Inter Font (Offline First) -->
    <link href="<?= url('/assets/vendor/inter/inter.css') ?>" rel="stylesheet">
    <!-- Font Awesome (Offline First) -->
    <link href="<?= url('/assets/vendor/fontawesome/css/all.min.css') ?>" rel="stylesheet">
    <!-- Bootstrap 5 CSS (Offline First) -->
    <link href="<?= url('/assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at 10% 15%, #1e1b4b 0%, transparent 45%),
                        radial-gradient(circle at 90% 85%, #3f0d1a 0%, transparent 45%),
                        radial-gradient(ellipse at 50% 50%, #0c1220 0%, #05080f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #f8fafc;
            padding: 1.5rem;
            transition: background 0.4s ease, color 0.4s ease;
        }
        body.light-theme {
            background: radial-gradient(circle at 15% 15%, #e0e7ff 0%, transparent 40%),
                        radial-gradient(circle at 85% 85%, #fee2e2 0%, transparent 40%),
                        radial-gradient(ellipse at 50% 50%, #f8fafc 0%, #e2e8f0 100%);
            color: #0f172a;
        }
        .login-card {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 32px 64px -16px rgba(0, 0, 0, 0.85), inset 0 1px 0 rgba(255, 255, 255, 0.12);
            background-color: rgba(14, 20, 34, 0.82);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            width: 100%;
            max-width: 430px;
            padding: 2.5rem 2.25rem;
            position: relative;
        }
        .login-logo {
            max-width: 80px;
            max-height: 80px;
            object-fit: contain;
            margin-bottom: 0.75rem;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.4));
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h3 {
            color: #f8fafc;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
            font-size: 1.45rem;
        }
        .login-header p {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-bottom: 0;
        }
        .form-label {
            color: #cbd5e1;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }
        .form-control {
            background-color: rgba(9, 14, 26, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            background-color: rgba(13, 21, 38, 0.95);
            border-color: #ef4444;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3), inset 0 1px 2px rgba(0, 0, 0, 0.5);
        }
        body.light-theme .login-card {
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.9);
            background-color: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }
        body.light-theme .login-header h3 {
            color: #0f172a;
        }
        body.light-theme .login-header p {
            color: #64748b;
        }
        body.light-theme .form-label {
            color: #334155;
        }
        body.light-theme .form-control {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0f172a;
        }
        body.light-theme .form-control:focus {
            background-color: #ffffff;
            border-color: #ef4444;
            color: #0f172a;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }
        .theme-toggle-floating {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        .theme-toggle-floating .theme-toggle-btn {
            background: rgba(14, 20, 34, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #f8fafc;
            padding: 8px 16px;
            border-radius: 9999px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.08);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
            user-select: none;
        }
        body.light-theme .theme-toggle-floating .theme-toggle-btn {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #cbd5e1;
            color: #0f172a;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        }
        .theme-toggle-floating .theme-toggle-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        .theme-toggle-floating .theme-toggle-btn i {
            font-size: 1rem;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .theme-toggle-floating .theme-toggle-btn:hover i {
            transform: rotate(25deg) scale(1.15);
        }
        .btn-primary {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 12px;
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.38), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transition: all 0.2s ease;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(239, 68, 68, 0.48);
        }
    </style>
</head>
<body>
<script>
    (function() {
        var theme = localStorage.getItem('pos_theme') || 'dark';
        document.body.classList.remove('dark-theme', 'light-theme');
        document.body.classList.add(theme + '-theme');
    })();
</script>

<!-- Floating Theme Toggle Switcher -->
<div class="theme-toggle-floating">
    <button type="button" id="themeToggleBtn" class="theme-toggle-btn" title="Toggle Light / Dark Mode" aria-label="Toggle Theme">
        <i class="fas fa-sun theme-icon-sun text-warning d-none"></i>
        <i class="fas fa-moon theme-icon-moon text-info"></i>
        <span class="theme-text fw-bold fs-7">Theme</span>
    </button>
</div>

<div class="card login-card">
    <div class="login-header">
        <img src="<?= logoUrl() ?>" alt="Logo" class="login-logo">
        <h3><?= defined('STORE_NAME') ? strtoupper(STORE_NAME) : 'ONE DOLLAR SHOP' ?></h3>
        <p><?= defined('STORE_TAGLINE') ? STORE_TAGLINE : 'Retail Inventory & POS System' ?></p>
    </div>
    
    <?php if (isset($_GET['error']) && !empty($_GET['error'])): ?>
        <div id="errorAlert" class="alert alert-danger" role="alert"><?= sanitize($_GET['error']) ?></div>
    <?php else: ?>
        <div id="errorAlert" class="alert alert-danger d-none" role="alert"></div>
    <?php endif; ?>
    
    <form id="loginForm" method="POST" action="<?= url('/api/auth.php?action=login&redirect=1') ?>">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required autocomplete="username" placeholder="Enter username">
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        </div>
        <div class="d-grid mb-2">
            <button type="submit" id="loginBtn" class="btn btn-primary btn-lg">
                <span id="btnText">Sign In</span>
                <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            </button>
        </div>
    </form>
</div>

<!-- Local Vendor Libraries (Offline First - Zero External Network Reliance) -->
<script src="<?= url('/assets/vendor/jquery/jquery-3.6.0.min.js') ?>"></script>
<script src="<?= url('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>

<script>
// Zero-Dependency Dual Theme Engine
function applyTheme(theme) {
    if (!theme || (theme !== 'dark' && theme !== 'light')) {
        theme = localStorage.getItem('pos_theme') || 'dark';
    }
    localStorage.setItem('pos_theme', theme);
    document.documentElement.setAttribute('data-bs-theme', theme);
    
    var body = document.body;
    if (theme === 'light') {
        body.classList.remove('dark-theme');
        body.classList.add('light-theme');
        document.querySelectorAll('.theme-icon-sun').forEach(function(el) { el.classList.add('d-none'); });
        document.querySelectorAll('.theme-icon-moon').forEach(function(el) { el.classList.remove('d-none'); });
        var toggleBtn = document.getElementById('themeToggleBtn');
        if (toggleBtn) toggleBtn.setAttribute('title', 'Switch to Dark Mode');
        document.querySelectorAll('.theme-text').forEach(function(el) { el.textContent = 'Light'; });
    } else {
        body.classList.remove('light-theme');
        body.classList.add('dark-theme');
        document.querySelectorAll('.theme-icon-moon').forEach(function(el) { el.classList.add('d-none'); });
        document.querySelectorAll('.theme-icon-sun').forEach(function(el) { el.classList.remove('d-none'); });
        var toggleBtn = document.getElementById('themeToggleBtn');
        if (toggleBtn) toggleBtn.setAttribute('title', 'Switch to Light Mode');
        document.querySelectorAll('.theme-text').forEach(function(el) { el.textContent = 'Dark'; });
    }
}
window.applyTheme = applyTheme;

// Apply saved theme immediately
applyTheme(localStorage.getItem('pos_theme') || 'dark');

document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('themeToggleBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var current = localStorage.getItem('pos_theme') || 'dark';
            var target = current === 'dark' ? 'light' : 'dark';
            applyTheme(target);
        });
    }

    window.addEventListener('storage', function(e) {
        if (e.key === 'pos_theme' && e.newValue) {
            applyTheme(e.newValue);
        }
    });

    var loginForm = document.getElementById('loginForm');
    var errorAlert = document.getElementById('errorAlert');
    var btnText = document.getElementById('btnText');
    var btnSpinner = document.getElementById('btnSpinner');
    var loginBtn = document.getElementById('loginBtn');

    function showError(message) {
        if (errorAlert) {
            errorAlert.textContent = message;
            errorAlert.classList.remove('d-none');
        }
        if (btnText) btnText.classList.remove('d-none');
        if (btnSpinner) btnSpinner.classList.add('d-none');
        if (loginBtn) loginBtn.disabled = false;
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();

            var usernameInput = document.getElementById('username');
            var passwordInput = document.getElementById('password');
            var username = usernameInput ? usernameInput.value.trim() : '';
            var password = passwordInput ? passwordInput.value : '';

            if (!username || !password) {
                showError('Please enter both username and password.');
                return;
            }

            // Visual loading state
            if (errorAlert) errorAlert.classList.add('d-none');
            if (btnText) btnText.classList.add('d-none');
            if (btnSpinner) btnSpinner.classList.remove('d-none');
            if (loginBtn) loginBtn.disabled = true;

            var postData = new URLSearchParams();
            postData.append('username', username);
            postData.append('password', password);
            postData.append('ajax', '1');

            fetch('<?= url('/api/auth.php?action=login') ?>', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: postData.toString()
            })
            .then(function(res) {
                return res.json().then(function(data) {
                    return { status: res.status, ok: res.ok, data: data };
                }).catch(function() {
                    return { status: res.status, ok: false, data: { message: 'Unexpected server response.' } };
                });
            })
            .then(function(result) {
                if (result.ok && result.data && result.data.success) {
                    window.location.href = result.data.redirect_url || '<?= url('/pos/index.php') ?>';
                } else {
                    showError((result.data && result.data.message) ? result.data.message : 'Invalid credentials.');
                }
            })
            .catch(function(err) {
                console.warn('AJAX fetch failed, falling back to native form POST:', err);
                // Resilient fallback: submit form natively so user is never locked out
                loginForm.submit();
            });
        });
    }
});
</script>

</body>
</html>
