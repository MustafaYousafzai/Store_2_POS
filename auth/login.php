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
    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at 15% 15%, #1e1b4b 0%, transparent 40%),
                        radial-gradient(circle at 85% 85%, #311018 0%, transparent 40%),
                        radial-gradient(ellipse at 50% 50%, #0f172a 0%, #06090f 100%);
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
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.7), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            background-color: rgba(17, 24, 39, 0.82);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
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
            background-color: rgba(11, 17, 32, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #ffffff;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            background-color: rgba(13, 21, 39, 0.95);
            border-color: #ef4444;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25);
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
            background: rgba(17, 24, 39, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f8fafc;
            padding: 8px 16px;
            border-radius: 9999px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
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
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 11px;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
            transition: all 0.2s ease;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.45);
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
    
    <div id="errorAlert" class="alert alert-danger d-none" role="alert"></div>
    
    <form id="loginForm">
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

<!-- jQuery (Load via CDN) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Dual Theme Engine for Login
    window.applyTheme = function(theme) {
        if (!theme || (theme !== 'dark' && theme !== 'light')) {
            theme = localStorage.getItem('pos_theme') || 'dark';
        }
        localStorage.setItem('pos_theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        
        if (theme === 'light') {
            $('html, body').removeClass('dark-theme').addClass('light-theme');
            $('.theme-icon-sun').addClass('d-none');
            $('.theme-icon-moon').removeClass('d-none');
            $('#themeToggleBtn').attr('title', 'Switch to Dark Mode');
            $('.theme-text').text('Light');
        } else {
            $('html, body').removeClass('light-theme').addClass('dark-theme');
            $('.theme-icon-moon').addClass('d-none');
            $('.theme-icon-sun').removeClass('d-none');
            $('#themeToggleBtn').attr('title', 'Switch to Light Mode');
            $('.theme-text').text('Dark');
        }
    };

    const savedTheme = localStorage.getItem('pos_theme') || 'dark';
    window.applyTheme(savedTheme);

    $(document).on('click', '#themeToggleBtn', function(e) {
        e.preventDefault();
        const current = localStorage.getItem('pos_theme') || 'dark';
        const target = current === 'dark' ? 'light' : 'dark';
        window.applyTheme(target);
    });

    window.addEventListener('storage', function(e) {
        if (e.key === 'pos_theme' && e.newValue) {
            window.applyTheme(e.newValue);
        }
    });

    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        const username = $('#username').val();
        const password = $('#password').val();
        
        // UI feedback states
        $('#errorAlert').addClass('d-none');
        $('#btnText').addClass('d-none');
        $('#btnSpinner').removeClass('d-none');
        $('#loginBtn').prop('disabled', true);
        
        $.ajax({
            url: '<?= url('/api/auth.php?action=login') ?>',
            type: 'POST',
            data: {
                username: username,
                password: password
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect_url || '<?= url('/pos/index.php') ?>';
                } else {
                    showError(response.message || 'An error occurred.');
                }
            },
            error: function(xhr) {
                let msg = 'Failed to connect to the server.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showError(msg);
            }
        });
    });
    
    function showError(message) {
        $('#errorAlert').text(message).removeClass('d-none');
        $('#btnText').removeClass('d-none');
        $('#btnSpinner').addClass('d-none');
        $('#loginBtn').prop('disabled', false);
    }
});
</script>

</body>
</html>
