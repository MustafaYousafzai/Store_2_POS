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
    <title><?= defined('STORE_NAME') ? STORE_NAME : 'One Dollar Shop' ?> - Login</title>
    <!-- Favicon (Browser Tab Icon) -->
    <link rel="icon" type="image/png" href="<?= logoUrl() ?>">
    <link rel="shortcut icon" type="image/png" href="<?= logoUrl() ?>">
    <link rel="apple-touch-icon" href="<?= logoUrl() ?>">
    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(ellipse at 50% 20%, #1e293b 0%, #090d16 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #f8fafc;
            padding: 1.5rem;
        }
        .login-card {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.05);
            background-color: rgba(17, 24, 39, 0.92);
            backdrop-filter: blur(16px);
            width: 100%;
            max-width: 420px;
            padding: 2.25rem 2rem;
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
            background-color: #0b1120;
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #ffffff;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            background-color: #0d1527;
            border-color: #ef4444;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25);
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
