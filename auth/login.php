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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('STORE_NAME') ? STORE_NAME : 'One Dollar Shop' ?> - Login</title>
    <!-- Favicon (Browser Tab Icon) -->
    <link rel="icon" type="image/png" href="<?= logoUrl() ?>">
    <link rel="shortcut icon" type="image/png" href="<?= logoUrl() ?>">
    <link rel="apple-touch-icon" href="<?= logoUrl() ?>">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            background-color: #ffffff;
            width: 100%;
            max-width: 400px;
        }
        .btn-primary {
            background-color: #d9534f;
            border-color: #d9534f;
            transition: all 0.2s ease-in-out;
        }
        .btn-primary:hover {
            background-color: #c9302c;
            border-color: #ac2925;
        }
        .form-control:focus {
            border-color: #d9534f;
            box-shadow: 0 0 0 0.25rem rgba(217, 83, 79, 0.25);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h3 {
            color: #d9534f;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .login-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="card login-card p-4">
    <div class="login-header">
        <h3><?= defined('STORE_NAME') ? strtoupper(STORE_NAME) : 'ONE DOLLAR SHOP' ?></h3>
        <p><?= defined('STORE_TAGLINE') ? STORE_TAGLINE : 'Retail Inventory & POS System' ?></p>
    </div>
    
    <div id="errorAlert" class="alert alert-danger d-none" role="alert"></div>
    
    <form id="loginForm">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
        </div>
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
        </div>
        <div class="d-grid mb-2">
            <button type="submit" id="loginBtn" class="btn btn-primary btn-lg">
                <span id="btnText">Login</span>
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
