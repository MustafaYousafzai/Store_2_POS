<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('STORE_NAME') ? STORE_NAME : 'One Dollar Shop' ?> - Business Management</title>
    <!-- Favicon (Browser Tab Icon) -->
    <link rel="icon" type="image/png" href="<?= logoUrl() ?>">
    <link rel="shortcut icon" type="image/png" href="<?= logoUrl() ?>">
    <link rel="apple-touch-icon" href="<?= logoUrl() ?>">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Style -->
    <link href="<?= asset('/assets/css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
    <!-- Global Base URL & Store Config for AJAX/Fetch across entire frontend -->
    <script>
        window.BASE_URL = '<?= url('') ?>';
        window.STORE_NAME = '<?= addslashes(defined('STORE_NAME') ? STORE_NAME : 'One Dollar Shop') ?>';
        window.STORE_TAGLINE = '<?= addslashes(defined('STORE_TAGLINE') ? STORE_TAGLINE : '') ?>';
        window.STORE_ADDRESS = '<?= addslashes(defined('STORE_ADDRESS') ? STORE_ADDRESS : 'McConaghey Road, Quetta') ?>';
        window.STORE_PHONE = '<?= addslashes(defined('STORE_PHONE') ? STORE_PHONE : '0307-2681893') ?>';
        window.STORE_CURRENCY = '<?= addslashes(defined('STORE_CURRENCY') ? STORE_CURRENCY : 'Rs.') ?>';
        window.STORE_LOGO = '<?= defined('STORE_LOGO') ? addslashes(STORE_LOGO) : 'one_dollar_shop_logo.png' ?>';
    </script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- JsBarcode (Standard Barcode Renderer) -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>
<body>

<!-- Global Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1200;">
    <div id="appToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center">
                <i id="toastIcon" class="me-2 fs-5"></i>
                <span id="toastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Hidden Print Area for Barcode Labels -->
<div id="barcodePrintArea" class="d-none"></div>

<!-- Hidden Direct Print Area for Receipts & Vouchers (Fallback & Direct Print) -->
<div id="thermalDirectPrintArea" class="d-none"></div>

<script>
// Enterprise Isolated Thermal Print Engine (78mm/80mm Thermal Roll Printers)
window.printThermalReceipt = function(htmlContent) {
    // 1. Sync content to fallback direct print container
    const directArea = document.getElementById('thermalDirectPrintArea');
    if (directArea) {
        directArea.innerHTML = htmlContent;
    }
    
    // 2. Isolate print stream inside a dedicated, pristine iframe
    let iframe = document.getElementById('thermalPrintIframe');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'thermalPrintIframe';
        iframe.name = 'thermalPrintIframe';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.style.visibility = 'hidden';
        document.body.appendChild(iframe);
    }
    
    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(`<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt</title>
    <style>
        @page {
            size: 78mm auto;
            margin: 0mm !important;
        }
        @media print {
            html, body {
                width: 78mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            color: #000000 !important;
            font-weight: 700 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        html, body {
            width: 78mm;
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size: 11px;
            font-weight: 700 !important;
            line-height: 1.35;
            color: #000000 !important;
        }
        .thermal-receipt {
            width: 78mm;
            max-width: 78mm;
            margin: 0 auto;
            padding: 3mm 2.5mm 6mm 2.5mm;
            box-sizing: border-box;
            color: #000000 !important;
            font-weight: 700 !important;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .left { text-align: left; }
        .fw-bold { font-weight: 900 !important; }
        h1, h2, h3, h4, h5, h6 {
            color: #000000 !important;
            font-weight: 900 !important;
        }
        p, span, div, strong, b, td, th {
            color: #000000 !important;
            font-weight: 700 !important;
        }
        .receipt-divider {
            border-top: 1.5px dashed #000000 !important;
            margin: 4px 0;
            width: 100%;
            height: 0;
        }
        .receipt-divider-double {
            border-top: 2.5px solid #000000 !important;
            margin: 5px 0;
            width: 100%;
            height: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 2px 0;
            vertical-align: top;
            color: #000000 !important;
            font-weight: 700 !important;
        }
        th {
            font-weight: 900 !important;
        }
        .receipt-logo {
            max-width: 110px;
            max-height: 110px;
            width: auto;
            height: auto;
            margin: 0 auto 4px auto;
            display: block;
            object-fit: contain;
            filter: grayscale(100%) contrast(200%);
        }
    </style>
</head>
<body>
    <div class="thermal-receipt">
        ${htmlContent}
    </div>
</body>
</html>`);
    doc.close();
    
    // Allow iframe rendering cycle & image load before invoking print
    const triggerPrint = function() {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } catch (e) {
            console.error('Thermal iframe print error, falling back:', e);
            window.print();
        }
    };

    const imgs = iframe.contentWindow.document.images;
    if (imgs && imgs.length > 0) {
        let loaded = 0;
        let fired = false;
        const total = imgs.length;
        const onDone = function() {
            if (fired) return;
            loaded++;
            if (loaded >= total) {
                fired = true;
                setTimeout(triggerPrint, 100);
            }
        };
        for (let i = 0; i < total; i++) {
            if (imgs[i].complete) {
                onDone();
            } else {
                imgs[i].onload = onDone;
                imgs[i].onerror = onDone;
            }
        }
        setTimeout(function() {
            if (!fired) {
                fired = true;
                triggerPrint();
            }
        }, 500);
    } else {
        setTimeout(triggerPrint, 200);
    }
};
</script>

<div class="wrapper">
    <!-- Sidebar Navigation -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div id="sidebarBackdrop" class="d-none"></div>
    
    <!-- Main Content Container -->
    <div id="content" class="w-100">
        <!-- Top Navbar -->
        <nav class="navbar navbar-light bg-white border-bottom px-2 px-md-3 py-1 py-md-2 d-print-none">
            <div class="container-fluid px-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center flex-wrap gap-2 me-auto">
                    <button type="button" id="sidebarCollapse" class="btn btn-outline-secondary px-2 py-1" title="Toggle Navigation Sidebar">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <h4 class="mb-0 text-dark font-weight-bold fs-6 fs-md-5 text-truncate" style="max-width: 280px;"><?= defined('STORE_NAME') ? STORE_NAME : 'One Dollar Shop' ?></h4>
                </div>
                <div class="d-flex align-items-center gap-1 gap-md-2 flex-wrap ms-auto">
                    <?php if (hasPermission('MANAGE_KHATA') || hasPermission('CREATE_SALE') || isAdmin()): ?>
                    <a href="<?= url('/khata/index.php') ?>" class="btn btn-outline-warning btn-sm fw-bold shadow-sm text-dark px-2 px-md-3 rounded-pill text-nowrap" title="Open Customer Khata Register">
                        <i class="fas fa-book-bookmark text-warning me-1"></i> <span class="d-none d-sm-inline">Khata (Udhar)</span><span class="d-inline d-sm-none">Khata</span>
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('CREATE_SALE')): ?>
                    <a href="<?= url('/pos/index.php') ?>" class="btn btn-danger btn-sm fw-bold shadow-sm px-2 px-md-3 rounded-pill text-white text-nowrap" title="Open POS Terminal">
                        <i class="fas fa-cash-register me-1"></i> POS
                    </a>
                    <?php endif; ?>
                    <div class="dropdown ms-1">
                        <a class="nav-link dropdown-toggle text-dark font-weight-bold py-1 px-1 px-md-2 text-nowrap" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1 text-secondary"></i> <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo ($_SESSION['role'] === 'admin') ? 'Admin' : 'Cashier'; ?>)</span><span class="d-inline d-md-none"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown" style="z-index: 1070;">
                            <?php if (isAdmin()): ?>
                            <li><a class="dropdown-item" href="<?= url('/users/index.php') ?>"><i class="fas fa-users-gear me-2 text-info"></i>Manage Users</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="fas fa-key me-2 text-muted"></i>Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= url('/logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <div class="container-fluid main-page-container p-2 p-md-3">
            <?php include __DIR__ . '/navbar.php'; ?>
