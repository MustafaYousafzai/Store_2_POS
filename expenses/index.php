<?php
require_once __DIR__ . '/../includes/header.php';
requirePermission('VIEW_EXPENSES');

$isAdmin = isAdmin();
$currentUsername = $_SESSION['username'] ?? 'User';
$currentRole = $_SESSION['role'] ?? 'staff';
?>

<style>
/* Modern Enterprise Expenses UI Styles */
:root {
    --exp-primary: #dc2626;
    --exp-primary-hover: #b91c1c;
    --exp-bg-subtle: #fef2f2;
}

.kpi-stat-card {
    border-radius: 16px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid rgba(0,0,0,0.06) !important;
}
.kpi-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important;
}

/* Tactile Quick Action Tiles */
.exp-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.1rem 0.8rem;
    border-radius: 16px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    text-align: center;
    height: 100%;
}
.exp-tile:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.09);
    border-color: currentColor;
}
.exp-tile-icon-box {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin-bottom: 0.6rem;
    transition: transform 0.2s ease;
}
.exp-tile:hover .exp-tile-icon-box {
    transform: scale(1.1);
}

/* Tile color themes */
.tile-meals { color: #d97706; }
.tile-meals .exp-tile-icon-box { background: #fef3c7; color: #b45309; }

.tile-tea { color: #0284c7; }
.tile-tea .exp-tile-icon-box { background: #e0f2fe; color: #0369a1; }

.tile-cleaning { color: #059669; }
.tile-cleaning .exp-tile-icon-box { background: #d1fae5; color: #047857; }

.tile-packaging { color: #7c3aed; }
.tile-packaging .exp-tile-icon-box { background: #ede9fe; color: #6d28d9; }

.tile-transport { color: #ea580c; }
.tile-transport .exp-tile-icon-box { background: #ffedd5; color: #c2410c; }

.tile-utilities { color: #2563eb; }
.tile-utilities .exp-tile-icon-box { background: #dbeafe; color: #1d4ed8; }

.tile-rent { color: #dc2626; }
.tile-rent .exp-tile-icon-box { background: #fee2e2; color: #b91c1c; }

.tile-custom { color: #4b5563; }
.tile-custom .exp-tile-icon-box { background: #f3f4f6; color: #374151; }

/* Filter Pills */
.filter-pill-btn {
    border-radius: 20px;
    padding: 0.35rem 0.9rem;
    font-size: 0.84rem;
    font-weight: 600;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.15s ease;
}
.filter-pill-btn:hover, .filter-pill-btn.active {
    background: #111827;
    color: #ffffff;
    border-color: #111827;
}

/* Quick Suggestion Chips */
.suggestion-chip {
    display: inline-block;
    padding: 0.25rem 0.65rem;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.78rem;
    cursor: pointer;
    transition: background 0.15s ease;
    margin: 2px 4px 2px 0;
    user-select: none;
}
.suggestion-chip:hover {
    background: #e5e7eb;
    color: #111827;
}
</style>

<div class="container-fluid px-0">
    <!-- PAGE TITLE & MAIN ACTIONS -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 d-print-none">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fas fa-wallet text-danger me-2"></i>Daily Expenses & Overheads
            </h3>
            <p class="text-muted small mb-0">
                Record staff meals, tea, packaging, and store utility bills with automated drawer cash tracking.
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary px-3 py-2 fw-semibold rounded-3" id="btnRefreshExpenses">
                <i class="fas fa-arrows-rotate me-1"></i> Refresh
            </button>
            <button type="button" class="btn btn-danger btn-lg px-4 py-2 fw-bold shadow-sm rounded-3 d-flex align-items-center gap-2" id="btnOpenExpenseModal">
                <i class="fas fa-plus fs-5"></i> Record Expense
            </button>
        </div>
    </div>

    <!-- TOP 3 KPI SUMMARY CARDS -->
    <div class="row g-3 mb-4 d-print-none">
        <!-- 1. Today's Expenses -->
        <div class="col-md-4">
            <div class="card kpi-stat-card p-3 bg-white h-100 shadow-sm" style="border-left: 5px solid #dc2626 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase"><?php echo $isAdmin ? "Today's Store Expenses" : "My Today's Expenses"; ?></span>
                        <h2 class="mb-0 fw-bold text-danger font-monospace mt-1" id="kpiTodayTotal">Rs. 0.00</h2>
                        <small class="text-muted" id="kpiTodayCount"><?php echo $isAdmin ? "All cashiers & store overheads" : "Recorded by you today"; ?></small>
                    </div>
                    <div class="p-3 rounded-circle bg-danger-subtle text-danger" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-calendar-day fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Today's Counter Cash Drawer Outflow -->
        <div class="col-md-4">
            <div class="card kpi-stat-card p-3 bg-white h-100 shadow-sm" style="border-left: 5px solid #f59e0b !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase"><?php echo $isAdmin ? "Drawer Cash Outflow" : "My Drawer Outflow"; ?></span>
                        <h2 class="mb-0 fw-bold text-warning-emphasis font-monospace mt-1" id="kpiDrawerCash">Rs. 0.00</h2>
                        <small class="text-warning fw-semibold"><?php echo $isAdmin ? "Store counter drawer cash outflow" : "Paid from your counter drawer"; ?></small>
                    </div>
                    <div class="p-3 rounded-circle bg-warning-subtle text-warning-emphasis" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-cash-register fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. This Month's Total Expenses -->
        <div class="col-md-4">
            <div class="card kpi-stat-card p-3 bg-white h-100 shadow-sm" style="border-left: 5px solid #2563eb !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase"><?php echo $isAdmin ? "This Month's Total" : "My This Month's Total"; ?></span>
                        <h2 class="mb-0 fw-bold text-primary font-monospace mt-1" id="kpiMonthTotal">Rs. 0.00</h2>
                        <small class="text-muted" id="kpiMonthSubtext"><?php echo $isAdmin ? "Monthly store operating outflow" : "Your logged monthly outflow"; ?></small>
                    </div>
                    <div class="p-3 rounded-circle bg-primary-subtle text-primary" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-chart-pie fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <!-- OWNER FIXED OVERHEADS INFO BANNER -->
    <div class="alert alert-light border shadow-sm d-flex justify-content-between align-items-center mb-4 py-2 px-3 rounded-3 d-print-none">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger"><i class="fas fa-crown me-1"></i> Owner Intelligence</span>
            <span class="small text-dark">
                <strong>Fixed Overheads Amortization:</strong> Monthly Rent + Staff Payroll are prorated by actual month days (28, 29, 30, or 31) on the dashboard for real daily net profit.
            </span>
        </div>
        <a href="<?= url('/dashboard/index.php') ?>" class="btn btn-sm btn-outline-danger fw-bold text-decoration-none">
            <i class="fas fa-chart-line me-1"></i> Open Dashboard Analyzer &rarr;
        </a>
    </div>
    <?php endif; ?>

    <!-- TACTILE QUICK ACTION TILES (1-CLICK EXPENSE SHORTCUTS) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-3 d-print-none">
        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-bolt text-warning me-1"></i> Quick 1-Click Expense Entries
            </h6>
            <small class="text-muted">Click any tile to instantly log counter expense</small>
        </div>
        <div class="row g-2" id="quickExpenseTilesContainer">
            <?php if (!$isAdmin): ?>
            <!-- CASHIER / EMPLOYEE VIEW: ONLY 3 TILES IN EXACT ORDER -->
            <!-- 1. Staff Meals (Pehla Option) -->
            <div class="col-12 col-md-4">
                <div class="exp-tile tile-meals py-3 px-3" data-category="Staff Meals (Khana)" data-suggestion="Staff lunch / dinner roti and salan">
                    <div class="exp-tile-icon-box mx-auto" style="width: 56px; height: 56px; font-size: 1.5rem;"><i class="fas fa-utensils"></i></div>
                    <strong class="fs-6 mb-1">1. Staff Meals (Khana)</strong>
                    <small class="text-muted" style="font-size: 0.78rem;">Lunch / Dinner / Roti Salan</small>
                </div>
            </div>
            <!-- 2. Daily Miscellaneous (Dosra Option) -->
            <div class="col-12 col-md-4">
                <div class="exp-tile tile-custom py-3 px-3" data-category="Daily Miscellaneous (Rozmarra Kharcha)" data-suggestion="Emergency petty cash / unexpected daily expense">
                    <div class="exp-tile-icon-box mx-auto" style="width: 56px; height: 56px; font-size: 1.5rem;"><i class="fas fa-receipt"></i></div>
                    <strong class="fs-6 mb-1">2. Miscellaneous (Kharcha)</strong>
                    <small class="text-muted" style="font-size: 0.78rem;">Rozmarra aam emergency kharcha</small>
                </div>
            </div>
            <!-- 3. Tea & Refreshments (Teesra Option) -->
            <div class="col-12 col-md-4">
                <div class="exp-tile tile-tea py-3 px-3" data-category="Tea & Refreshments (Chai)" data-suggestion="Chai, biscuits and mineral water">
                    <div class="exp-tile-icon-box mx-auto" style="width: 56px; height: 56px; font-size: 1.5rem;"><i class="fas fa-mug-hot"></i></div>
                    <strong class="fs-6 mb-1">3. Chai & Water</strong>
                    <small class="text-muted" style="font-size: 0.78rem;">Tea, biscuits & refreshments</small>
                </div>
            </div>
            <?php else: ?>
            <!-- ADMIN VIEW: 6 STREAMLINED MASTER TILES (Responsive for 11, 13, 16 inch displays) -->
            <!-- 1. Staff Meals -->
            <div class="col-6 col-sm-4 col-md-4 col-xl-2">
                <div class="exp-tile tile-meals" data-category="Staff Meals (Khana)" data-suggestion="Staff lunch / dinner roti and salan">
                    <div class="exp-tile-icon-box"><i class="fas fa-utensils"></i></div>
                    <strong class="fs-6 mb-0">Staff Meals</strong>
                    <small class="text-muted" style="font-size: 0.72rem;">Khana / Lunch / Dinner</small>
                </div>
            </div>
            <!-- 2. Daily Miscellaneous -->
            <div class="col-6 col-sm-4 col-md-4 col-xl-2">
                <div class="exp-tile tile-custom" data-category="Daily Miscellaneous (Rozmarra Kharcha)" data-suggestion="Emergency petty cash / unexpected daily expense">
                    <div class="exp-tile-icon-box"><i class="fas fa-receipt"></i></div>
                    <strong class="fs-6 mb-0">Miscellaneous</strong>
                    <small class="text-muted" style="font-size: 0.72rem;">Rozmarra aam kharcha</small>
                </div>
            </div>
            <!-- 3. Tea & Refreshments -->
            <div class="col-6 col-sm-4 col-md-4 col-xl-2">
                <div class="exp-tile tile-tea" data-category="Tea & Refreshments (Chai)" data-suggestion="Chai, biscuits and mineral water">
                    <div class="exp-tile-icon-box"><i class="fas fa-mug-hot"></i></div>
                    <strong class="fs-6 mb-0">Chai & Water</strong>
                    <small class="text-muted" style="font-size: 0.72rem;">Tea & refreshments</small>
                </div>
            </div>
            <!-- 4. Packaging & Shoppers -->
            <div class="col-6 col-sm-4 col-md-4 col-xl-2">
                <div class="exp-tile tile-packaging" data-category="Packaging & Shoppers" data-suggestion="Shopper rolls and packing tape">
                    <div class="exp-tile-icon-box"><i class="fas fa-bag-shopping"></i></div>
                    <strong class="fs-6 mb-0">Shoppers / Bags</strong>
                    <small class="text-muted" style="font-size: 0.72rem;">Packing & lifafay</small>
                </div>
            </div>
            <!-- 5. Utility Bills (Admin) -->
            <div class="col-6 col-sm-4 col-md-4 col-xl-2">
                <div class="exp-tile tile-utilities" data-category="Electricity Bill" data-suggestion="Monthly electricity / K-Electric bill">
                    <div class="exp-tile-icon-box"><i class="fas fa-bolt"></i></div>
                    <strong class="fs-6 mb-0">Utility Bills</strong>
                    <small class="text-muted" style="font-size: 0.72rem;">Electricity, Gas, Wi-Fi</small>
                </div>
            </div>
            <!-- 6. Shop Rent (Admin) -->
            <div class="col-6 col-sm-4 col-md-4 col-xl-2">
                <div class="exp-tile tile-rent" data-category="Shop Rent" data-suggestion="Monthly shop lease / premises rent">
                    <div class="exp-tile-icon-box"><i class="fas fa-building"></i></div>
                    <strong class="fs-6 mb-0">Shop Rent</strong>
                    <small class="text-muted" style="font-size: 0.72rem;">Premises lease</small>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN EXPENSES LEDGER CONTAINER -->
    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
        <!-- Filter Controls Toolbar -->
        <div class="row g-3 align-items-center mb-4 pb-3 border-bottom d-print-none">
            <!-- Timeframe Pills -->
            <div class="col-lg-6 d-flex gap-1 flex-wrap align-items-center">
                <span class="text-muted small fw-bold me-2">Timeframe:</span>
                <button type="button" class="filter-pill-btn active" data-timeframe="today">Today</button>
                <button type="button" class="filter-pill-btn" data-timeframe="yesterday">Yesterday</button>
                <button type="button" class="filter-pill-btn" data-timeframe="week">This Week</button>
                <button type="button" class="filter-pill-btn" data-timeframe="month">This Month</button>
                <button type="button" class="filter-pill-btn" data-timeframe="all">All Time</button>
                <button type="button" class="filter-pill-btn" data-timeframe="custom" id="btnCustomDates">Custom</button>
            </div>

            <!-- Custom Date Range (Hidden by default) -->
            <div class="col-lg-6 d-none" id="customDateRangeBox">
                <div class="d-flex gap-2 align-items-center justify-content-lg-end">
                    <input type="date" class="form-control form-control-sm" id="filterStartDate" style="max-width: 140px;">
                    <span class="text-muted small">to</span>
                    <input type="date" class="form-control form-control-sm" id="filterEndDate" style="max-width: 140px;">
                    <button type="button" class="btn btn-sm btn-dark px-3 fw-bold" id="btnApplyCustomDates">Apply</button>
                </div>
            </div>

            <!-- Category & Payment Source Filters -->
            <div class="col-12 mt-3 pt-2 border-top d-flex gap-2 flex-wrap align-items-center justify-content-between">
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <select class="form-select form-select-sm" id="filterCategory" style="min-width: 180px;">
                        <option value="0">All Categories</option>
                    </select>

                    <select class="form-select form-select-sm" id="filterPaymentSource" style="min-width: 160px;">
                        <option value="">All Payment Sources</option>
                        <option value="drawer_cash">Counter Cash Drawer</option>
                        <option value="bank_transfer">Bank Transfer / Online</option>
                        <option value="card">Card Payment</option>
                        <option value="owner_pocket">Owner Pocket</option>
                    </select>

                    <select class="form-select form-select-sm" id="filterStatus" style="min-width: 130px;">
                        <option value="">All Statuses</option>
                        <option value="active" selected>Active Only</option>
                        <option value="voided">Voided Only</option>
                    </select>

                    <?php if ($isAdmin): ?>
                    <select class="form-select form-select-sm" id="filterUser" style="min-width: 160px;">
                        <option value="0">All Staff / Cashiers</option>
                    </select>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <?php if (!$isAdmin): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                        <i class="fas fa-user-check me-1"></i> Your Counter Ledger (<?php echo htmlspecialchars($currentUsername); ?>)
                    </span>
                    <?php endif; ?>
                    <input type="text" class="form-control form-control-sm" id="searchExpenseInput" placeholder="Search notes / voucher..." style="width: 200px;">
                    <span class="badge bg-light text-dark border px-3 py-2 font-monospace" id="ledgerCountBadge">0 Records</span>
                </div>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="expensesTable" style="font-size: 0.92rem;">
                <thead class="table-light text-secondary">
                    <tr>
                        <th style="width: 110px;">Date</th>
                        <th style="width: 190px;">Category</th>
                        <th class="text-end" style="width: 130px;">Amount (Rs.)</th>
                        <th style="width: 140px;">Payment Source</th>
                        <th>Description / Details</th>
                        <th style="width: 120px;">Logged By</th>
                        <th class="text-center" style="width: 90px;">Status</th>
                        <th class="text-end d-print-none" style="width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="expensesTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div> Loading expenses ledger...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: RECORD EXPENSE (QUICK 2-FIELD & ENTERPRISE ADVANCED)             -->
<!-- ========================================================================= -->
<div class="modal fade" id="recordExpenseModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="expenseModalTitle">
                    <i class="fas fa-wallet text-danger me-2"></i>Record Store Expense
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="expenseForm">
                <div class="modal-body p-4">
                    <div id="expenseModalAlert" class="alert alert-danger d-none"></div>
                    <div id="expenseModalMonthlyNotice" class="alert alert-warning py-2 px-3 small d-none">
                        <i class="fas fa-triangle-exclamation me-1"></i> <span id="monthlyNoticeText"></span>
                    </div>

                    <!-- Category Selector -->
                    <div class="mb-3">
                        <label for="form_category_id" class="form-label fs-6 fw-bold text-dark">Select Expense Category *</label>
                        <select class="form-select form-select-lg fw-semibold" id="form_category_id" name="category_id" required>
                            <option value="" disabled selected>Choose category...</option>
                        </select>
                    </div>

                    <!-- Amount Input (Large Autofocus) -->
                    <div class="mb-3">
                        <label for="form_amount" class="form-label fs-6 fw-bold text-dark">Amount Spent (Rs.) *</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text font-monospace fs-4 bg-white fw-bold">Rs.</span>
                            <input type="number" class="form-control font-monospace fw-bold text-end fs-3" id="form_amount" name="amount" required min="0.5" step="0.5" placeholder="0.00">
                        </div>
                    </div>

                    <!-- Payment Source (Drawer Cash vs Bank) -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">Paid From (Source) *</label>
                        <div class="d-flex gap-2">
                            <div class="form-check p-3 border rounded-3 flex-fill bg-white">
                                <input class="form-check-input" type="radio" name="payment_source" id="sourceDrawerCash" value="drawer_cash" checked>
                                <label class="form-check-label fw-bold ms-1" for="sourceDrawerCash">
                                    <i class="fas fa-cash-register text-warning me-1"></i> Cash Drawer
                                    <small class="d-block text-muted fw-normal" style="font-size: 0.75rem;">Counter register cash</small>
                                </label>
                            </div>
                            <?php if ($isAdmin): ?>
                            <div class="form-check p-3 border rounded-3 flex-fill bg-white">
                                <input class="form-check-input" type="radio" name="payment_source" id="sourceBankTransfer" value="bank_transfer">
                                <label class="form-check-label fw-bold ms-1" for="sourceBankTransfer">
                                    <i class="fas fa-building-columns text-primary me-1"></i> Bank / Online
                                    <small class="d-block text-muted fw-normal" style="font-size: 0.75rem;">Direct account transfer</small>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Description / Notes -->
                    <div class="mb-3">
                        <label for="form_description" class="form-label fs-6 fw-bold text-dark">Description / Details</label>
                        <input type="text" class="form-control form-control-lg" id="form_description" name="description" placeholder="e.g. 4 rotian + salan for staff dinner">
                        <!-- Smart Suggestion Chips -->
                        <div class="mt-2" id="suggestionChipsBox">
                            <span class="suggestion-chip" data-text="Staff lunch / dinner">Staff lunch / dinner</span>
                            <span class="suggestion-chip" data-text="Chai and biscuits">Chai & biscuits</span>
                            <span class="suggestion-chip" data-text="Daily miscellaneous expense">Emergency petty expense</span>
                            <span class="suggestion-chip" data-text="Shoppers and tape roll">Shopper rolls & tape</span>
                        </div>
                    </div>

                    <!-- Optional Date & Receipt Voucher Reference -->
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="form_payment_date" class="form-label small fw-bold text-muted">Expense Date</label>
                            <input type="date" class="form-control" id="form_payment_date" name="payment_date">
                        </div>
                        <div class="col-md-6">
                            <label for="form_receipt_number" class="form-label small fw-bold text-muted">Bill / Receipt No. (Optional)</label>
                            <input type="text" class="form-control font-monospace" id="form_receipt_number" name="receipt_number" placeholder="e.g. INV-1042">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">Cancel</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-danger btn-lg fw-bold px-3" id="btnSaveAndPrint">
                            <i class="fas fa-print me-1"></i> Save & Print Slip
                        </button>
                        <button type="submit" class="btn btn-danger btn-lg fw-bold px-4" id="btnSaveExpense">
                            Save Expense
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: VOID / CANCEL EXPENSE (ADMIN ONLY)                                -->
<!-- ========================================================================= -->
<div class="modal fade" id="voidExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-ban me-2"></i>Void Expense Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="voidExpenseForm">
                <input type="hidden" id="void_expense_id">
                <div class="modal-body p-4">
                    <div id="voidAlert" class="alert alert-danger d-none"></div>
                    <p class="text-secondary small">
                        Are you sure you want to cancel this expense? The entry will be marked as voided in audit history.
                    </p>
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Category:</span>
                            <strong id="voidCatName">--</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="text-muted small">Amount:</span>
                            <strong class="font-monospace text-danger" id="voidAmount">Rs. 0.00</strong>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="void_reason" class="form-label fw-bold">Reason for Cancellation *</label>
                        <input type="text" class="form-control" id="void_reason" required placeholder="e.g. Duplicate entry, wrong amount entered">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4" id="btnConfirmVoid">Confirm Void</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Thermal Print Area Helper -->
<div id="thermalDirectPrintArea" class="d-none"></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- ========================================================================= -->
<!-- JAVASCRIPT: ENTERPRISE EXPENSES CLIENT LOGIC                             -->
<!-- ========================================================================= -->
<script>
$(document).ready(function() {
    let allCategoriesCache = [];
    let currentFilterTimeframe = 'today';
    const isAdminUser = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    const todayStr = new Date().toISOString().split('T')[0];

    // Initialize Default Dates
    $('#form_payment_date').val(todayStr);
    $('#filterStartDate').val(todayStr);
    $('#filterEndDate').val(todayStr);

    // Initial Data Fetch
    loadExpenseCategories();
    loadExpenseKPIs();
    loadExpensesList();

    // -------------------------------------------------------------
    // 1. Fetch Categories
    // -------------------------------------------------------------
    function loadExpenseCategories() {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/expenses.php?action=list_categories',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    allCategoriesCache = res.categories || [];
                    populateCategoryDropdowns(allCategoriesCache);
                }
            }
        });
    }

    function populateCategoryDropdowns(categories) {
        // Modal Dropdown
        let modalOptions = '';
        let filterOptions = '<option value="0">All Categories</option>';

        // Group categories (Staff Meals, Misc, Tea vs Admin Overheads)
        let staffCats = categories.filter(c => c.scope === 'all_staff');
        let overheadCats = categories.filter(c => c.scope === 'admin_only');

        if (staffCats.length > 0) {
            modalOptions += `<optgroup label="Daily Staff Expenses">`;
            staffCats.forEach((c, idx) => {
                modalOptions += `<option value="${c.id}" data-scope="${c.scope}" ${idx === 0 ? 'selected' : ''}>${c.name}</option>`;
                filterOptions += `<option value="${c.id}">${c.name}</option>`;
            });
            modalOptions += `</optgroup>`;
        }

        if (overheadCats.length > 0 && isAdminUser) {
            modalOptions += `<optgroup label="Monthly Overheads & Utilities (Admin)">`;
            overheadCats.forEach(c => {
                modalOptions += `<option value="${c.id}" data-scope="${c.scope}">${c.name}</option>`;
                filterOptions += `<option value="${c.id}">[Overhead] ${c.name}</option>`;
            });
            modalOptions += `</optgroup>`;
        }

        $('#form_category_id').html(modalOptions);
        $('#filterCategory').html(filterOptions);
    }

    // Load users list for Admin filter
    if (isAdminUser) {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/expenses.php?action=list_users',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.users) {
                    let opts = '<option value="0">All Staff / Cashiers</option>';
                    res.users.forEach(u => {
                        opts += `<option value="${u.id}">${u.username} (${u.role})</option>`;
                    });
                    $('#filterUser').html(opts);
                }
            }
        });
        $(document).on('change', '#filterUser', function() {
            loadExpensesList();
        });
    }

    // -------------------------------------------------------------
    // 2. Fetch KPI Stats
    // -------------------------------------------------------------
    function loadExpenseKPIs() {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/expenses.php?action=summary_kpi',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#kpiTodayTotal').text(`Rs. ${parseFloat(res.today_total).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#kpiTodayCount').text(`${res.today_count} entries recorded today`);
                    $('#kpiDrawerCash').text(`Rs. ${parseFloat(res.today_drawer_cash).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#kpiMonthTotal').text(`Rs. ${parseFloat(res.month_total).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 3. Fetch Expenses Ledger List
    // -------------------------------------------------------------
    function loadExpensesList() {
        let params = {
            category_id: $('#filterCategory').val() || 0,
            payment_source: $('#filterPaymentSource').val() || '',
            status: $('#filterStatus').val() || 'active',
            user_id: $('#filterUser').length ? ($('#filterUser').val() || 0) : 0
        };

        // Timeframe calculations
        const today = new Date();
        if (currentFilterTimeframe === 'today') {
            params.start_date = todayStr;
            params.end_date = todayStr;
        } else if (currentFilterTimeframe === 'yesterday') {
            const yest = new Date(today);
            yest.setDate(yest.getDate() - 1);
            const yestStr = yest.toISOString().split('T')[0];
            params.start_date = yestStr;
            params.end_date = yestStr;
        } else if (currentFilterTimeframe === 'week') {
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);
            params.start_date = weekAgo.toISOString().split('T')[0];
            params.end_date = todayStr;
        } else if (currentFilterTimeframe === 'month') {
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            params.start_date = firstDay;
            params.end_date = todayStr;
        } else if (currentFilterTimeframe === 'custom') {
            params.start_date = $('#filterStartDate').val();
            params.end_date = $('#filterEndDate').val();
        }

        $.ajax({
            url: (window.BASE_URL || '') + '/api/expenses.php?action=list',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    renderExpensesTable(res.expenses || []);
                }
            },
            error: function() {
                $('#expensesTableBody').html(`<tr><td colspan="8" class="text-center py-4 text-danger">Failed to load expenses ledger.</td></tr>`);
            }
        });
    }

    // Render Table Rows
    function renderExpensesTable(expenses) {
        const query = ($('#searchExpenseInput').val() || '').toLowerCase().trim();
        let filtered = expenses;
        if (query !== '') {
            filtered = expenses.filter(e => 
                (e.description || '').toLowerCase().includes(query) ||
                (e.category_name || '').toLowerCase().includes(query) ||
                (e.creator_name || '').toLowerCase().includes(query) ||
                (e.receipt_number || '').toLowerCase().includes(query) ||
                ('exp-' + e.id).includes(query)
            );
        }

        $('#ledgerCountBadge').text(`${filtered.length} Records`);

        if (filtered.length === 0) {
            $('#expensesTableBody').html(`
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fas fa-folder-open fs-2 mb-2 d-block opacity-40"></i>
                        No expenses match the selected filters.
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        filtered.forEach(e => {
            const isVoided = e.status === 'voided';
            const icon = e.category_icon || 'fa-receipt';
            
            // Payment Source Badge
            let sourceBadge = '';
            if (e.payment_source === 'drawer_cash') {
                sourceBadge = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning font-monospace px-2 py-1"><i class="fas fa-cash-register me-1"></i>Cash Drawer</span>`;
            } else if (e.payment_source === 'bank_transfer') {
                sourceBadge = `<span class="badge bg-primary-subtle text-primary border border-primary font-monospace px-2 py-1"><i class="fas fa-building-columns me-1"></i>Bank Transfer</span>`;
            } else if (e.payment_source === 'card') {
                sourceBadge = `<span class="badge bg-info-subtle text-info-emphasis border border-info font-monospace px-2 py-1"><i class="fas fa-credit-card me-1"></i>Card</span>`;
            } else {
                sourceBadge = `<span class="badge bg-light text-dark border font-monospace px-2 py-1">${escapeHtml(e.payment_source || 'Cash')}</span>`;
            }

            // Status Badge
            const statusBadge = isVoided ? 
                `<span class="badge bg-danger px-2 py-1"><i class="fas fa-ban me-1"></i>Voided</span>` : 
                `<span class="badge bg-success-subtle text-success border border-success px-2 py-1"><i class="fas fa-check me-1"></i>Paid</span>`;

            html += `
                <tr class="${isVoided ? 'table-secondary opacity-60 text-decoration-line-through' : ''}">
                    <td class="font-monospace text-nowrap">
                        <span class="fw-bold">${e.payment_date}</span>
                        <small class="d-block text-muted">${(e.created_at || '').substring(11, 16)}</small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle p-2 bg-light text-dark border" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                                <i class="fas ${icon}"></i>
                            </div>
                            <div>
                                <strong class="text-dark d-block">${escapeHtml(e.category_name)}</strong>
                                ${e.receipt_number ? `<small class="text-muted font-monospace">Ref: ${escapeHtml(e.receipt_number)}</small>` : ''}
                            </div>
                        </div>
                    </td>
                    <td class="text-end font-monospace">
                        <strong class="fs-6 text-danger">Rs. ${parseFloat(e.amount).toFixed(2)}</strong>
                    </td>
                    <td>${sourceBadge}</td>
                    <td>
                        <div class="text-dark">${escapeHtml(e.description || '--')}</div>
                        ${isVoided ? `<small class="text-danger fw-bold d-block">Void reason: ${escapeHtml(e.void_reason || 'Cancelled')} (by ${escapeHtml(e.voided_by_name || 'Admin')})</small>` : ''}
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">${escapeHtml(e.creator_name)}</span>
                    </td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-end d-print-none text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-dark me-1 btn-print-voucher" data-id="${e.id}" title="Print 80mm Slip">
                            <i class="fas fa-print"></i>
                        </button>
                        ${isAdminUser && !isVoided ? `
                        <button type="button" class="btn btn-sm btn-outline-danger btn-open-void" data-id="${e.id}" data-cat="${escapeHtml(e.category_name)}" data-amount="${parseFloat(e.amount).toFixed(2)}" title="Void Expense">
                            <i class="fas fa-trash-can"></i>
                        </button>` : ''}
                    </td>
                </tr>
            `;
        });

        $('#expensesTableBody').html(html);
    }

    // -------------------------------------------------------------
    // 4. Quick Action Tiles Trigger
    // -------------------------------------------------------------
    $(document).on('click', '.exp-tile', function() {
        const catName = $(this).data('category');
        const suggestion = $(this).data('suggestion');
        
        openRecordExpenseModal(catName, suggestion);
    });

    $('#btnOpenExpenseModal').on('click', function() {
        openRecordExpenseModal('', '');
    });

    function openRecordExpenseModal(preselectCatName, defaultNote) {
        $('#expenseModalAlert').addClass('d-none');
        $('#form_amount').val('');
        $('#form_description').val(defaultNote || '');
        $('#form_receipt_number').val('');
        $('#form_payment_date').val(todayStr);
        $('#sourceDrawerCash').prop('checked', true);

        // Pre-select category if specified, otherwise default to first category (Staff Meals)
        if (preselectCatName) {
            const foundCat = allCategoriesCache.find(c => c.name.toLowerCase() === preselectCatName.toLowerCase() || c.name.toLowerCase().includes(preselectCatName.toLowerCase()));
            if (foundCat) {
                $('#form_category_id').val(foundCat.id);
            }
        } else {
            const firstCat = allCategoriesCache.find(c => c.name.includes('Meals') || c.name.includes('Khana')) || allCategoriesCache[0];
            if (firstCat) {
                $('#form_category_id').val(firstCat.id);
            }
        }

        $('#recordExpenseModal').modal('show');
        $('#form_category_id').trigger('change');
        setTimeout(() => $('#form_amount').focus(), 400);
    }

    // Dynamic Monthly Suggestions & Anti-Duplicate Warning
    $('#form_category_id').on('change', function() {
        const catId = $(this).val();
        const selectedOpt = $(this).find('option:selected');
        const catScope = selectedOpt.data('scope');
        const catName = selectedOpt.text().trim();
        
        $('#expenseModalMonthlyNotice').addClass('d-none');
        
        const curDate = new Date();
        const monthName = curDate.toLocaleString('en-US', { month: 'long', year: 'numeric' });
        const curMonthYear = curDate.toISOString().slice(0, 7);
        
        const curDesc = $('#form_description').val().trim();
        const isAutoDesc = !curDesc || curDesc.includes('for ') || curDesc.includes('bill for') || curDesc.includes('rent for');
        
        if (isAutoDesc) {
            if (catName.includes('Shop Rent')) {
                $('#form_description').val(`Shop rent for ${monthName}`);
            } else if (catName.includes('Electricity')) {
                $('#form_description').val(`Electricity bill for ${monthName}`);
            } else if (catName.includes('Gas Bill')) {
                $('#form_description').val(`Gas bill for ${monthName}`);
            } else if (catName.includes('Internet')) {
                $('#form_description').val(`Internet & Wi-Fi bill for ${monthName}`);
            } else if (catName.includes('Water')) {
                $('#form_description').val(`Water supply bill for ${monthName}`);
            } else if (catName.includes('Generator')) {
                $('#form_description').val(`Generator fuel & maintenance for ${monthName}`);
            }
        }
        
        if (isAdminUser && catScope === 'admin_only' && catId) {
            $.ajax({
                url: (window.BASE_URL || '') + '/api/expenses.php?action=check_monthly_overhead',
                type: 'GET',
                data: { category_id: catId, month: curMonthYear },
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.exists && res.expense) {
                        const e = res.expense;
                        $('#monthlyNoticeText').html(`<strong>Duplicate Reminder:</strong> An active payment of <strong>Rs. ${parseFloat(e.amount).toFixed(2)}</strong> for <strong>${e.category_name}</strong> was already logged on <strong>${e.payment_date}</strong> (${e.payment_source.replace('_', ' ')}). Please verify before logging again.`);
                        $('#expenseModalMonthlyNotice').removeClass('d-none');
                    }
                }
            });
        }
    });

    // Suggestion chips click
    $(document).on('click', '.suggestion-chip', function() {
        const text = $(this).data('text');
        $('#form_description').val(text);
    });

    // Prevent accidental scroll change on amount input
    $('#form_amount').on('wheel', function() {
        $(this).blur();
    });

    // -------------------------------------------------------------
    // 5. Submit Expense Form
    // -------------------------------------------------------------
    let shouldPrintAfterSave = false;

    $('#btnSaveAndPrint').on('click', function() {
        shouldPrintAfterSave = true;
        $('#expenseForm').submit();
    });

    $('#btnSaveExpense').on('click', function() {
        shouldPrintAfterSave = false;
    });

    $('#expenseForm').on('submit', function(e) {
        e.preventDefault();
        
        const categoryId = $('#form_category_id').val();
        const amount = parseFloat($('#form_amount').val()) || 0;
        
        if (!categoryId || amount <= 0) {
            $('#expenseModalAlert').text('Please select a valid category and enter an amount.').removeClass('d-none');
            return;
        }

        $('#btnSaveExpense, #btnSaveAndPrint').prop('disabled', true);

        const formData = {
            category_id: categoryId,
            amount: amount,
            payment_date: $('#form_payment_date').val() || todayStr,
            payment_source: $('input[name="payment_source"]:checked').val() || 'drawer_cash',
            description: $('#form_description').val().trim(),
            receipt_number: $('#form_receipt_number').val().trim()
        };

        $.ajax({
            url: (window.BASE_URL || '') + '/api/expenses.php?action=add',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                $('#btnSaveExpense, #btnSaveAndPrint').prop('disabled', false);
                if (res.success) {
                    $('#recordExpenseModal').modal('hide');
                    showToast(res.message, 'success');
                    
                    loadExpenseKPIs();
                    loadExpensesList();

                    if (shouldPrintAfterSave) {
                        printThermalExpenseVoucher(res);
                    }
                } else {
                    $('#expenseModalAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnSaveExpense, #btnSaveAndPrint').prop('disabled', false);
                $('#expenseModalAlert').text(xhr.responseJSON?.message || 'Error recording expense.').removeClass('d-none');
            }
        });
    });

    // -------------------------------------------------------------
    // 6. Thermal 80mm Voucher Printing
    // -------------------------------------------------------------
    $(document).on('click', '.btn-print-voucher', function() {
        const expenseId = $(this).data('id');
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/expenses.php?action=get&id=${expenseId}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.expense) {
                    const e = res.expense;
                    printThermalExpenseVoucher({
                        voucher_code: 'EXP-' + e.payment_date.replace(/-/g, '') + '-' + String(e.id).padStart(4, '0'),
                        category_name: e.category_name,
                        amount: e.amount,
                        payment_date: e.payment_date,
                        payment_source: e.payment_source,
                        description: e.description,
                        receipt_number: e.receipt_number,
                        creator_name: e.creator_name
                    });
                }
            }
        });
    });

    function printThermalExpenseVoucher(data) {
        const sourceLabel = data.payment_source === 'drawer_cash' ? 'Cash Drawer (Counter)' : 'Bank / Online Transfer';
        const slipHtml = `
            <div class="center" style="margin-bottom: 6px; color: #000;">
                <img src="${(window.BASE_URL || "")}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="${window.STORE_NAME || 'One Dollar Shop'}" class="receipt-logo">
                <h3 style="margin: 0; font-size: 16px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase; color: #000;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h3>
                <p style="margin: 2px 0; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #000;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'}</p>
                <div style="font-size: 11.5px; font-weight: 900; margin-top: 1px; color: #000;">Ph: ${window.STORE_PHONE || '0307-2681893'}</div>
                <div class="receipt-divider"></div>
                <div style="font-size: 12.5px; font-weight: 900; text-transform: uppercase; color: #000;">*** STORE EXPENSE VOUCHER ***</div>
            </div>

            <table style="font-size: 11px; font-weight: 800; width: 100%; margin-bottom: 4px; color: #000;">
                <tr>
                    <td><strong>Voucher #:</strong></td>
                    <td class="right font-monospace" style="font-weight: 900;">${escapeHtml(data.voucher_code || 'EXP-' + data.expense_id)}</td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td class="right" style="font-weight: 800;">${escapeHtml(data.payment_date || todayStr)}</td>
                </tr>
                <tr>
                    <td><strong>Paid From:</strong></td>
                    <td class="right" style="font-weight: 900;">${escapeHtml(sourceLabel)}</td>
                </tr>
                ${data.receipt_number ? `
                <tr>
                    <td><strong>Bill / Ref:</strong></td>
                    <td class="right font-monospace" style="font-weight: 900;">${escapeHtml(data.receipt_number)}</td>
                </tr>` : ''}
            </table>

            <div class="receipt-divider"></div>

            <div style="padding: 6px 0; text-align: center; color: #000;">
                <span style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #000;">Category:</span>
                <div style="font-size: 13.5px; font-weight: 900; margin: 2px 0; color: #000;">${escapeHtml(data.category_name)}</div>
                <div style="font-size: 18px; font-weight: 900; font-family: Arial, sans-serif; margin-top: 4px; color: #000;">
                    Rs. ${parseFloat(data.amount).toFixed(2)}
                </div>
            </div>

            <div class="receipt-divider"></div>

            <div style="font-size: 11px; font-weight: 800; margin: 6px 0; color: #000;">
                <strong>Details:</strong>
                <div style="margin-top: 2px; font-weight: 800; color: #000;">${escapeHtml(data.description || 'Store operational expense')}</div>
            </div>

            <div class="receipt-divider"></div>

            <table style="font-size: 10.5px; font-weight: 800; width: 100%; margin-top: 15px; color: #000;">
                <tr>
                    <td style="width: 50%; text-align: center; border-top: 1.5px dashed #000; padding-top: 4px;">
                        Logged By<br><strong style="font-weight: 900;">${escapeHtml(data.creator_name || 'Staff')}</strong>
                    </td>
                    <td style="width: 50%; text-align: center; border-top: 1.5px dashed #000; padding-top: 4px;">
                        Receiver Sign<br>_______________
                    </td>
                </tr>
            </table>

            <div class="receipt-divider" style="margin-top: 10px;"></div>
            <div style="font-size: 9.5px; font-weight: 800; text-align: center; margin-top: 6px; color: #000;">
                System Generated Audit Voucher
            </div>
            <div class="center" style="font-size: 9.5px; font-weight: 800; line-height: 1.3; color: #000; margin-top: 4px;">
                Software Developed by:<br>
                <span style="font-weight: 900; font-size: 10.5px;">0319-7273908 | 0336-8176491</span>
            </div>
        `;

        if (window.printThermalReceipt) {
            window.printThermalReceipt(slipHtml);
        }
    }

    // -------------------------------------------------------------
    // 7. Void Expense Logic (Admin Only)
    // -------------------------------------------------------------
    $(document).on('click', '.btn-open-void', function() {
        const id = $(this).data('id');
        const cat = $(this).data('cat');
        const amt = $(this).data('amount');

        $('#void_expense_id').val(id);
        $('#voidCatName').text(cat);
        $('#voidAmount').text(`Rs. ${amt}`);
        $('#void_reason').val('');
        $('#voidAlert').addClass('d-none');
        $('#voidExpenseModal').modal('show');
    });

    $('#voidExpenseForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#void_expense_id').val();
        const reason = $('#void_reason').val().trim();

        if (!id || !reason) {
            $('#voidAlert').text('Please provide a reason for cancellation.').removeClass('d-none');
            return;
        }

        $('#btnConfirmVoid').prop('disabled', true);

        $.ajax({
            url: (window.BASE_URL || '') + '/api/expenses.php?action=void',
            type: 'POST',
            data: { id: id, reason: reason },
            dataType: 'json',
            success: function(res) {
                $('#btnConfirmVoid').prop('disabled', false);
                if (res.success) {
                    $('#voidExpenseModal').modal('hide');
                    showToast(res.message, 'success');
                    loadExpenseKPIs();
                    loadExpensesList();
                } else {
                    $('#voidAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnConfirmVoid').prop('disabled', false);
                $('#voidAlert').text(xhr.responseJSON?.message || 'Failed to void expense.').removeClass('d-none');
            }
        });
    });

    // -------------------------------------------------------------
    // 8. Filters & Search Handlers
    // -------------------------------------------------------------
    $('.filter-pill-btn').on('click', function() {
        $('.filter-pill-btn').removeClass('active');
        $(this).addClass('active');

        currentFilterTimeframe = $(this).data('timeframe');
        if (currentFilterTimeframe === 'custom') {
            $('#customDateRangeBox').removeClass('d-none');
        } else {
            $('#customDateRangeBox').addClass('d-none');
            loadExpensesList();
        }
    });

    $('#btnApplyCustomDates').on('click', function() {
        loadExpensesList();
    });

    $('#filterCategory, #filterPaymentSource, #filterStatus').on('change', function() {
        loadExpensesList();
    });

    $('#searchExpenseInput').on('keyup', function() {
        loadExpensesList();
    });

    $('#btnRefreshExpenses').on('click', function() {
        loadExpenseCategories();
        loadExpenseKPIs();
        loadExpensesList();
        showToast('Expenses refreshed.', 'info');
    });

    // Helper: HTML Escaping
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }
});
</script>
