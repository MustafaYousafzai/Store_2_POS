<?php
require_once __DIR__ . '/../config/auth.php';

$current_page = $_SERVER['SCRIPT_NAME'];

// Helper to determine if a route is currently active
if (!function_exists('isPageActive')) {
    function isPageActive($pattern) {
        global $current_page;
        if (is_array($pattern)) {
            foreach ($pattern as $p) {
                if (strpos($current_page, $p) !== false) {
                    return true;
                }
            }
            return false;
        }
        return strpos($current_page, $pattern) !== false;
    }
}
?>

<!-- Universal Horizontal Quick Navigation Bar -->
<div class="card mb-2 mb-md-3 d-print-none shadow-sm quick-nav-card" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: 8px; overflow: visible !important;">
    <div class="card-body py-1 py-md-2 px-2 px-md-3" style="overflow: visible !important;">
        <ul class="nav nav-pills nav-horizontal-quick justify-content-start align-items-center flex-wrap gap-1" style="overflow: visible !important;">
            <?php if (isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo isPageActive('dashboard/') ? 'active bg-danger text-white' : 'text-dark fw-semibold'; ?>" href="<?= url('/dashboard/index.php') ?>">
                    <i class="fas fa-chart-line me-1 <?php echo isPageActive('dashboard/') ? 'text-white' : 'text-danger'; ?>"></i> Dashboard
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasPermission('CREATE_SALE') || isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo isPageActive('/pos/') ? 'active bg-danger text-white' : 'text-dark fw-semibold'; ?>" href="<?= url('/pos/index.php') ?>">
                    <i class="fas fa-cash-register me-1 <?php echo isPageActive('/pos/') ? 'text-white' : 'text-danger'; ?>"></i> POS Terminal
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasPermission('VIEW_SALES') || isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo isPageActive('sales/history.php') ? 'active bg-danger text-white' : 'text-dark fw-semibold'; ?>" href="<?= url('/sales/history.php') ?>">
                    <i class="fas fa-receipt me-1 <?php echo isPageActive('sales/history.php') ? 'text-white' : 'text-primary'; ?>"></i> Sales History
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasPermission('MANAGE_KHATA') || isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo isPageActive('khata/') ? 'active bg-danger text-white' : 'text-dark fw-semibold'; ?>" href="<?= url('/khata/index.php') ?>">
                    <i class="fas fa-book-bookmark me-1 <?php echo isPageActive('khata/') ? 'text-white' : 'text-warning'; ?>"></i> Customer Credit Khata
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (hasPermission('REFUND') || hasPermission('EXCHANGE') || isAdmin()): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle <?php echo isPageActive(['sales/refunds.php', 'sales/exchanges.php']) ? 'active bg-danger text-white' : 'text-dark fw-semibold'; ?>" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="fas fa-rotate-left me-1 <?php echo isPageActive(['sales/refunds.php', 'sales/exchanges.php']) ? 'text-white' : 'text-warning'; ?>"></i> Refunds & Exchanges
                </a>
                <ul class="dropdown-menu shadow border-0" style="z-index: 1060;">
                    <?php if (hasPermission('REFUND') || isAdmin()): ?>
                    <li>
                        <a class="dropdown-item py-2 <?php echo isPageActive('sales/refunds.php') ? 'bg-danger text-white fw-bold' : ''; ?>" href="<?= url('/sales/refunds.php') ?>">
                            <i class="fas fa-arrow-left-long me-2 <?php echo isPageActive('sales/refunds.php') ? 'text-white' : 'text-warning'; ?>"></i>Process Refunds (Wapsi)
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (hasPermission('EXCHANGE') || isAdmin()): ?>
                    <li>
                        <a class="dropdown-item py-2 <?php echo isPageActive('sales/exchanges.php') ? 'bg-danger text-white fw-bold' : ''; ?>" href="<?= url('/sales/exchanges.php') ?>">
                            <i class="fas fa-repeat me-2 <?php echo isPageActive('sales/exchanges.php') ? 'text-white' : 'text-info'; ?>"></i>Process Exchanges (Tabadla)
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo isPageActive('sales/voids.php') ? 'active bg-danger text-white' : 'text-dark fw-semibold'; ?>" href="<?= url('/sales/voids.php') ?>">
                    <i class="fas fa-ban me-1 <?php echo isPageActive('sales/voids.php') ? 'text-white' : 'text-danger'; ?>"></i> Deleted / Voided Bills
                </a>
            </li>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle <?php echo isPageActive(['inventory/', 'product_velocity.php']) ? 'active bg-danger text-white' : 'text-dark fw-semibold'; ?>" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="fas fa-boxes me-1 <?php echo isPageActive(['inventory/', 'product_velocity.php']) ? 'text-white' : 'text-success'; ?>"></i> Inventory
                </a>
                <ul class="dropdown-menu shadow border-0" style="z-index: 1060;">
                    <li>
                        <a class="dropdown-item py-2 <?php echo isPageActive('inventory/index.php') ? 'bg-danger text-white fw-bold' : ''; ?>" href="<?= url('/inventory/index.php') ?>">
                            <i class="fas fa-tags me-2 <?php echo isPageActive('inventory/index.php') ? 'text-white' : 'text-primary'; ?>"></i>Product Catalog & Stock
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 <?php echo isPageActive('reports/product_velocity.php') ? 'bg-danger text-white fw-bold' : ''; ?>" href="<?= url('/reports/product_velocity.php') ?>">
                            <i class="fas fa-cubes-stacked me-2 <?php echo isPageActive('reports/product_velocity.php') ? 'text-white' : 'text-danger'; ?>"></i>Velocity & Dead Stock Analysis
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
            
            <?php if (hasPermission('VIEW_EXPENSES') || isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo isPageActive('expenses/') ? 'active bg-danger text-white' : 'text-dark fw-semibold'; ?>" href="<?= url('/expenses/index.php') ?>">
                    <i class="fas fa-wallet me-1 <?php echo isPageActive('expenses/') ? 'text-white' : 'text-success'; ?>"></i> Expenses
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo isPageActive('employees/') ? 'active bg-danger text-white' : 'text-dark fw-semibold'; ?>" href="<?= url('/employees/index.php') ?>">
                    <i class="fas fa-users me-1 <?php echo isPageActive('employees/') ? 'text-white' : 'text-secondary'; ?>"></i> Employees & Khata
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo isPageActive('users/') ? 'active bg-danger text-white' : 'text-dark fw-semibold'; ?>" href="<?= url('/users/index.php') ?>">
                    <i class="fas fa-users-gear me-1 <?php echo isPageActive('users/') ? 'text-white' : 'text-info'; ?>"></i> Manage Users
                </a>
            </li>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo isPageActive('reports/audit_logs.php') ? 'active bg-danger text-white' : 'text-dark fw-semibold'; ?>" href="<?= url('/reports/audit_logs.php') ?>">
                    <i class="fas fa-shield-halved me-1 <?php echo isPageActive('reports/audit_logs.php') ? 'text-white' : 'text-dark'; ?>"></i> Security Logs
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
