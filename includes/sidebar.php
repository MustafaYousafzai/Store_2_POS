<?php
require_once __DIR__ . '/../config/auth.php';
?>
<!-- Sidebar -->
<nav id="sidebar" class="d-print-none">
    <div class="sidebar-header p-3 text-center border-bottom" style="border-color: rgba(255,255,255,0.08) !important;">
        <img src="<?= logoUrl() ?>" alt="Logo" style="max-height: 48px; max-width: 140px; object-fit: contain; margin-bottom: 6px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));">
        <h6 class="mb-0 text-center text-danger fw-bold" style="letter-spacing: 0.5px;"><?= defined('STORE_NAME') ? strtoupper(STORE_NAME) : 'ONE DOLLAR SHOP' ?></h6>
    </div>
    
    <ul class="list-unstyled components p-3">
        <?php if (isAdmin()): ?>
        <li class="mb-2">
            <a href="<?= url('/dashboard/index.php') ?>" class="nav-link text-white py-2 px-3 rounded d-block <?php echo isPageActive('dashboard/') ? 'active bg-danger' : ''; ?>">
                <i class="fas fa-chart-line me-2"></i> Dashboard
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('CREATE_SALE') || isAdmin()): ?>
        <li class="mb-2">
            <a href="<?= url('/pos/index.php') ?>" class="nav-link text-white py-2 px-3 rounded d-block <?php echo isPageActive('pos/') ? 'active bg-danger' : ''; ?>">
                <i class="fas fa-cash-register me-2"></i> Point of Sale (POS)
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (isAdmin()): ?>
        <li class="mb-2">
            <a href="#inventorySubmenu" data-bs-toggle="collapse" aria-expanded="false" class="nav-link text-white dropdown-toggle py-2 px-3 rounded d-block <?php echo isPageActive(['inventory/', 'product_velocity.php']) ? 'active bg-danger' : ''; ?>">
                <i class="fas fa-boxes me-2"></i> Inventory
            </a>
            <ul class="collapse list-unstyled ps-3 bg-secondary-dark rounded mt-1" id="inventorySubmenu">
                <li><a href="<?= url('/inventory/index.php') ?>" class="text-white py-2 px-3 d-block"><i class="fas fa-circle-dot me-2 font-size-xs"></i>Products</a></li>
                <li><a href="<?= url('/reports/product_velocity.php') ?>" class="text-white py-2 px-3 d-block"><i class="fas fa-cubes-stacked me-2 text-danger"></i>Velocity & Dead Stock</a></li>
            </ul>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('VIEW_SALES') || isAdmin()): ?>
        <li class="mb-2">
            <a href="<?= url('/sales/history.php') ?>" class="nav-link text-white py-2 px-3 rounded d-block <?php echo isPageActive('sales/history.php') ? 'active bg-danger' : ''; ?>">
                <i class="fas fa-history me-2"></i> Sales History
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('MANAGE_KHATA') || isAdmin()): ?>
        <li class="mb-2">
            <a href="<?= url('/khata/index.php') ?>" class="nav-link text-white py-2 px-3 rounded d-block <?php echo isPageActive('khata/') ? 'active bg-danger' : ''; ?>">
                <i class="fas fa-book-bookmark me-2 text-warning"></i> Customer Credit Khata
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('REFUND') || hasPermission('EXCHANGE') || isAdmin()): ?>
        <li class="mb-2">
            <a href="#returnsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="nav-link text-white dropdown-toggle py-2 px-3 rounded d-block">
                <i class="fas fa-rotate-left me-2"></i> Returns & Refunds
            </a>
            <ul class="collapse list-unstyled ps-3 bg-secondary-dark rounded mt-1" id="returnsSubmenu">
                <?php if (hasPermission('REFUND') || isAdmin()): ?>
                <li><a href="<?= url('/sales/refunds.php') ?>" class="text-white py-2 px-3 d-block"><i class="fas fa-circle-dot me-2 font-size-xs"></i>Refunds</a></li>
                <?php endif; ?>
                <?php if (hasPermission('EXCHANGE') || isAdmin()): ?>
                <li><a href="<?= url('/sales/exchanges.php') ?>" class="text-white py-2 px-3 d-block"><i class="fas fa-circle-dot me-2 font-size-xs"></i>Exchanges</a></li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>
        
        <?php if (isAdmin()): ?>
        <li class="mb-2">
            <a href="<?= url('/sales/voids.php') ?>" class="nav-link text-white py-2 px-3 rounded d-block <?php echo isPageActive('sales/voids.php') ? 'active bg-danger' : ''; ?>">
                <i class="fas fa-ban me-2"></i> Deleted / Voided Bills
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (hasPermission('VIEW_EXPENSES') || isAdmin()): ?>
        <li class="mb-2">
            <a href="<?= url('/expenses/index.php') ?>" class="nav-link text-white py-2 px-3 rounded d-block <?php echo isPageActive('expenses/') ? 'active bg-danger' : ''; ?>">
                <i class="fas fa-wallet me-2"></i> Expenses
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (isAdmin()): ?>
        <li class="mb-2">
            <a href="<?= url('/employees/index.php') ?>" class="nav-link text-white py-2 px-3 rounded d-block <?php echo isPageActive('employees/') ? 'active bg-danger' : ''; ?>">
                <i class="fas fa-users me-2"></i> Employees & Khata
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (isAdmin()): ?>
        <li class="mb-2">
            <a href="<?= url('/reports/product_velocity.php') ?>" class="nav-link text-white py-2 px-3 rounded d-block <?php echo isPageActive('reports/product_velocity.php') ? 'active bg-danger' : ''; ?>">
                <i class="fas fa-cubes-stacked me-2 text-warning"></i> Dead Items & Velocity
            </a>
        </li>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <li class="mb-2">
            <a href="<?= url('/users/index.php') ?>" class="nav-link text-white py-2 px-3 rounded d-block <?php echo isPageActive('users/') ? 'active bg-danger' : ''; ?>">
                <i class="fas fa-users-gear me-2"></i> Manage Users
            </a>
        </li>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <li class="mb-2">
            <a href="<?= url('/reports/audit_logs.php') ?>" class="nav-link text-white py-2 px-3 rounded d-block <?php echo isPageActive('reports/audit_logs.php') ? 'active bg-danger' : ''; ?>">
                <i class="fas fa-shield-halved me-2"></i> Audit Logs
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
