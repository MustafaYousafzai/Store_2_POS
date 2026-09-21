<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();

// Strict Access Control: Dashboard is strictly restricted to Administrators only
if (!isAdmin()) {
    redirect('/pos/index.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* =========================================================
   100% MOBILE-FIRST & CLEAN ENTERPRISE STYLES (DARK THEME)
   ========================================================= */
:root {
    --brand-red: #ef4444;
    --brand-dark: #0f172a;
    --brand-green: #10b981;
    --brand-amber: #f59e0b;
    --brand-blue: #3b82f6;
    --card-radius: 16px;
}

body {
    background-color: var(--bg-canvas, #090d16);
    color: var(--text-main, #f8fafc);
    overflow-x: hidden;
}

/* Base Card Style */
.dash-card {
    background: var(--surface-card, #111827);
    border-radius: var(--card-radius);
    border: 1px solid var(--border-subtle, rgba(255,255,255,0.08));
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

/* Horizontal Scrollable Timeframe Pills on Mobile, Wrap on Desktop */
.timeframe-scroll-container {
    display: flex;
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none; /* Firefox */
    gap: 8px;
    padding-bottom: 4px;
}
.timeframe-scroll-container::-webkit-scrollbar {
    display: none; /* Safari & Chrome */
}
@media (min-width: 992px) {
    .timeframe-scroll-container {
        flex-wrap: wrap;
        white-space: normal;
    }
}

.pill-btn {
    border-radius: 30px;
    padding: 7px 16px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid var(--border-subtle, rgba(255,255,255,0.12));
    background: var(--surface-input, #0b1120);
    color: var(--text-secondary, #cbd5e1);
    flex-shrink: 0;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.pill-btn:hover {
    background: var(--surface-hover, rgba(255,255,255,0.06));
    color: #ffffff;
    border-color: var(--border-medium, rgba(255,255,255,0.2));
}
.pill-btn.active {
    background: var(--accent, #ef4444);
    color: #ffffff;
    border-color: var(--accent, #ef4444);
    box-shadow: 0 2px 10px rgba(239,68,68,0.35);
}

/* 4 Core KPI Cards */
.kpi-tile {
    border-radius: 16px;
    background: var(--surface-card, #111827);
    border: 1px solid var(--border-subtle, rgba(255,255,255,0.08));
    padding: 14px 14px 12px 14px;
    height: 100%;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}
.kpi-tile::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}
.tile-blue::before { background: var(--brand-blue); }
.tile-orange::before { background: #f97316; }
.tile-green::before { background: var(--brand-green); }
.tile-purple::before { background: #a855f7; }
.tile-amber::before { background: var(--brand-amber); }
.tile-teal::before { background: #06b6d4; }
.tile-red::before { background: var(--brand-red); }
.tile-emerald::before { background: #10b981; }

@media (min-width: 1400px) {
    .col-xxl-7th {
        flex: 0 0 auto;
        width: 14.285714%;
    }
}

.kpi-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-muted, #94a3b8);
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.kpi-value {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.5px;
    margin-bottom: 4px;
    color: var(--text-main, #f8fafc);
    font-family: system-ui, -apple-system, monospace;
}
@media (min-width: 768px) {
    .kpi-value { font-size: 1.7rem; }
    .kpi-tile { padding: 18px; }
}
.kpi-sub {
    font-size: 0.72rem;
    color: var(--text-muted, #94a3b8);
    line-height: 1.3;
}

/* Profit Waterfall Receipt Card */
.receipt-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px dashed var(--border-subtle, rgba(255,255,255,0.1));
    font-size: 0.88rem;
}
.receipt-line:last-child {
    border-bottom: none;
}

/* Mobile Quick Action Buttons */
.app-tile-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 14px 8px;
    border-radius: 14px;
    background: var(--surface-card, #111827);
    border: 1px solid var(--border-subtle, rgba(255,255,255,0.08));
    text-decoration: none;
    color: var(--text-main, #f8fafc);
    text-align: center;
    height: 100%;
    transition: all 0.2s ease;
}
.app-tile-btn:hover {
    transform: translateY(-2px);
    border-color: var(--accent, #ef4444);
    box-shadow: 0 6px 16px rgba(239,68,68,0.18);
    color: var(--accent, #ef4444);
}
.app-tile-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-bottom: 6px;
}
</style>

<div class="container-fluid px-0 py-1">

    <!-- 1. COMPACT MOBILE-FRIENDLY TOP HEADER -->
    <div class="dash-card mb-3 p-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger px-2 py-1 rounded-2"><i class="fas fa-crown text-white"></i></span>
                    <h5 class="mb-0 fw-bold text-dark" style="font-size: 1.15rem;"><?= defined('STORE_NAME') ? strtoupper(STORE_NAME) : 'ONE DOLLAR SHOP' ?></h5>
                </div>
                <span class="text-muted small" style="font-size: 0.78rem;">
                    Owner Dashboard • Rozana Hisab Kitab & Galla
                </span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <?php if (isAdmin()): ?>
                <button type="button" class="btn btn-sm btn-outline-dark fw-bold rounded-pill px-3" id="btnOpenOverheadModal">
                    <i class="fas fa-sliders text-danger me-1"></i> Rent Settings
                </button>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-light border rounded-circle p-2 text-secondary" id="btnManualRefresh" title="Refresh">
                    <i class="fas fa-arrows-rotate"></i>
                </button>
            </div>
        </div>

        <!-- Horizontal Scrollable Timeframe Buttons (Touch-Friendly) -->
        <div class="timeframe-scroll-container pt-1" id="timeframeButtonGroup">
            <button type="button" class="pill-btn active" data-filter="today">
                <i class="fas fa-sun text-warning"></i> <span>Today</span>
            </button>
            <button type="button" class="pill-btn" data-filter="yesterday">
                <i class="fas fa-clock-rotate-left text-info"></i> <span>Yesterday</span>
            </button>
            <button type="button" class="pill-btn" data-filter="week">
                <i class="fas fa-calendar-week text-primary"></i> <span>This Week</span>
            </button>
            <button type="button" class="pill-btn" data-filter="month">
                <i class="fas fa-calendar-alt text-success"></i> <span>This Month</span>
            </button>
            <button type="button" class="pill-btn" data-filter="last_2_months">
                <i class="fas fa-calendar-days text-purple" style="color: #a855f7;"></i> <span>Last 2 Months</span>
            </button>
            <button type="button" class="pill-btn" data-filter="lifetime">
                <i class="fas fa-infinity text-danger"></i> <span>Lifetime</span>
            </button>
            <button type="button" class="pill-btn" data-filter="custom">
                <i class="fas fa-sliders text-secondary"></i> <span>Custom</span>
            </button>
        </div>

        <!-- Custom Date Range Picker (Hidden unless Custom clicked) -->
        <div class="d-none pt-2 mt-2 border-top d-flex align-items-center justify-content-end gap-2 flex-wrap" id="customRangeBar">
            <span class="small fw-bold text-muted"><i class="fas fa-calendar-day me-1"></i>Custom Range:</span>
            <input type="date" class="form-control form-control-sm" id="custom_start_date" style="width: 140px;">
            <span class="text-muted small">to</span>
            <input type="date" class="form-control form-control-sm" id="custom_end_date" style="width: 140px;">
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" id="btnApplyCustomDate">Apply</button>
        </div>
    </div>

    <!-- 2. THE 7 CORE FINANCIAL SUMMARY CARDS (Mobile: 2 cols, Tablet: 3-4 cols, Desktop: 7 cols) -->
    <div class="row g-2 g-md-3 mb-3">
        
        <!-- CARD 1: Total Sale -->
        <div class="col-6 col-md-4 col-xl-3 col-xxl-7th">
            <div class="kpi-tile tile-blue">
                <div class="kpi-title text-primary">
                    <i class="fas fa-cash-register"></i> <span>Total Sale</span>
                </div>
                <div class="kpi-value text-primary" id="kpiNetRevenue">Rs. 0</div>
                <div class="kpi-sub">
                    <span id="kpiBillsCount">0</span> Bills • <span id="kpiItemsSold">0</span> Items
                </div>
            </div>
        </div>

        <!-- CARD 2: Credit Sales (Udhar Given) -->
        <div class="col-6 col-md-4 col-xl-3 col-xxl-7th">
            <div class="kpi-tile tile-orange">
                <div class="kpi-title" style="color: #ea580c;">
                    <i class="fas fa-hand-holding-dollar"></i> <span>Credit Sales</span>
                </div>
                <div class="kpi-value" style="color: #ea580c;" id="kpiCreditSales">Rs. 0</div>
                <div class="kpi-sub" id="kpiCreditSalesSub">
                    Goods Given on Credit
                </div>
            </div>
        </div>

        <!-- CARD 3: Cash in Drawer / Hand -->
        <div class="col-6 col-md-4 col-xl-3 col-xxl-7th">
            <div class="kpi-tile tile-green">
                <div class="kpi-title text-success">
                    <i class="fas fa-money-bill-wave"></i> <span>Cash</span>
                </div>
                <div class="kpi-value text-success" id="kpiDrawerCash">Rs. 0</div>
                <div class="kpi-sub" id="kpiDrawerCashSub">
                    Counter Cash in Hand
                </div>
            </div>
        </div>

        <!-- CARD 4: Bank / Online Accounts -->
        <div class="col-6 col-md-4 col-xl-3 col-xxl-7th">
            <div class="kpi-tile tile-purple">
                <div class="kpi-title" style="color: #7c3aed;">
                    <i class="fas fa-building-columns"></i> <span>Bank / Account</span>
                </div>
                <div class="kpi-value" style="color: #7c3aed;" id="kpiBankAccount">Rs. 0</div>
                <div class="kpi-sub" id="kpiBankSub">
                    Online & Bank Transfers
                </div>
            </div>
        </div>

        <!-- CARD 5: Total Expenses -->
        <div class="col-6 col-md-4 col-xl-3 col-xxl-7th">
            <div class="kpi-tile tile-amber">
                <div class="kpi-title text-warning-emphasis">
                    <i class="fas fa-receipt"></i> <span>Total Expenses</span>
                </div>
                <div class="kpi-value text-warning-emphasis" id="kpiExpenses">Rs. 0</div>
                <div class="kpi-sub" id="kpiExpensesSub">
                    Rent + Staff + Petty
                </div>
            </div>
        </div>

        <!-- CARD 6: Profit (Gross Margin) -->
        <div class="col-6 col-md-4 col-xl-3 col-xxl-7th">
            <div class="kpi-tile tile-teal">
                <div class="kpi-title" style="color: #0891b2;">
                    <i class="fas fa-coins"></i> <span>Profit</span>
                </div>
                <div class="kpi-value" style="color: #0891b2;" id="kpiGrossProfit">Rs. 0</div>
                <div class="kpi-sub" id="kpiGrossMarginSub">
                    Sale − Product Cost
                </div>
            </div>
        </div>

        <!-- CARD 7: Actual Profit (Net Take-Home) -->
        <div class="col-6 col-md-4 col-xl-3 col-xxl-7th">
            <div class="kpi-tile tile-red" id="kpiProfitTile">
                <div class="kpi-title" id="kpiProfitTitle">
                    <i class="fas fa-sack-dollar"></i> <span>Actual Profit</span>
                </div>
                <div class="kpi-value" id="kpiNetProfit">Rs. 0</div>
                <div class="kpi-sub fw-bold" id="kpiProfitStatusText">
                    Pocket Profit
                </div>
            </div>
        </div>

    </div>

    <!-- 3. SIMPLIFIED PROFIT WATERFALL & BREAKEVEN TARGET -->
    <div class="row g-2 g-md-3 mb-3">
        
        <!-- Left: Simple Receipt-Style Profit Breakdown -->
        <div class="col-lg-6">
            <div class="dash-card p-3 p-md-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="fw-bold text-dark small text-uppercase">
                        <i class="fas fa-calculator text-danger me-1"></i> Profit Breakdown (Hisab Kitab)
                    </span>
                    <span class="badge bg-light text-dark border font-monospace" id="headerDivisorBadge">
                        <span id="dispDivisorMonthName">September 2026</span> (<span id="dispDivisorDays">30</span>d Divisor)
                    </span>
                </div>

                <!-- Waterfall Lines -->
                <div class="receipt-line">
                    <span class="text-muted"><i class="fas fa-circle-plus text-primary me-1"></i> Total Sale (Net Revenue):</span>
                    <strong class="font-monospace text-primary" id="flowNetSales">Rs. 0.00</strong>
                </div>
                <div class="receipt-line bg-warning-subtle px-2 rounded py-1">
                    <span class="text-dark small"><i class="fas fa-hand-holding-dollar text-warning me-1"></i> Of which Credit Sales (Udhar):</span>
                    <strong class="font-monospace text-warning-emphasis" id="flowCreditSales">Rs. 0.00</strong>
                </div>
                <div class="receipt-line bg-success-subtle px-2 rounded py-1">
                    <span class="text-dark small"><i class="fas fa-money-bill-transfer text-success me-1"></i> Khata Recovered (Vasooli In):</span>
                    <strong class="font-monospace text-success" id="flowKhataRecovered">+Rs. 0.00</strong>
                </div>
                <div class="receipt-line">
                    <span class="text-muted"><i class="fas fa-circle-minus text-secondary me-1"></i> Product Cost (COGS):</span>
                    <strong class="font-monospace text-secondary" id="flowCogs">-Rs. 0.00</strong>
                </div>
                <div class="receipt-line bg-light-subtle px-2 rounded">
                    <span class="fw-semibold text-dark"><i class="fas fa-equals text-info me-1"></i> Profit (Gross Profit):</span>
                    <strong class="font-monospace text-info" id="flowGrossProfit">Rs. 0.00</strong>
                </div>
                <div class="receipt-line">
                    <span class="text-muted"><i class="fas fa-circle-minus text-warning me-1"></i> Shop Rent (Prorated):</span>
                    <strong class="font-monospace text-warning-emphasis" id="dispDailyRent">-Rs. 0.00</strong>
                </div>
                <div class="receipt-line">
                    <span class="text-muted"><i class="fas fa-circle-minus text-warning me-1"></i> Staff Salaries (Payroll):</span>
                    <strong class="font-monospace text-warning-emphasis" id="dispDailyPayroll">-Rs. 0.00</strong>
                </div>
                <div class="receipt-line" id="dispOtherFixedRow" style="display: none;">
                    <span class="text-muted"><i class="fas fa-circle-minus text-warning me-1"></i> Other Fixed Overheads:</span>
                    <strong class="font-monospace text-warning-emphasis" id="dispDailyOtherFixed">-Rs. 0.00</strong>
                </div>
                <div class="receipt-line">
                    <span class="text-muted"><i class="fas fa-circle-minus text-warning me-1"></i> Daily Expenses (Petty Cash):</span>
                    <strong class="font-monospace text-warning-emphasis" id="dispVariableExp">-Rs. 0.00</strong>
                </div>

                <!-- Final Net Profit Banner -->
                <div class="mt-3 p-3 rounded-3 d-flex justify-content-between align-items-center" id="profitReceiptBanner" style="background: #f1f5f9;">
                    <div>
                        <span class="text-uppercase fw-bold small text-muted d-block">Actual Profit (Net Pocket Profit)</span>
                        <small class="text-muted" id="flowMarginPct">Gross Margin: 0%</small>
                    </div>
                    <div class="text-end">
                        <h4 class="font-monospace fw-bold mb-0" id="flowNetProfit">Rs. 0.00</h4>
                        <span class="badge" id="flowProfitBadge">Calculating...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Breakeven Target Meter & Fixed Overheads -->
        <div class="col-lg-6">
            <div class="dash-card p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <span class="fw-bold text-dark small text-uppercase" id="breakevenMeterTitle">
                            <i class="fas fa-bullseye text-danger me-1"></i> Today's Target (Breakeven Meter)
                        </span>
                        <span class="badge bg-success-subtle text-success border">
                            <i class="fas fa-shield-check me-1"></i> Zero Double-Count
                        </span>
                    </div>

                    <!-- Breakeven Bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold text-dark">Breakeven Sales Needed:</span>
                            <span class="font-monospace fw-bold fs-6 text-dark" id="analyzerBreakevenTarget">Rs. 0.00</span>
                        </div>
                        <div class="progress rounded-pill" style="height: 14px; background: #e2e8f0;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success rounded-pill" role="progressbar" id="analyzerBreakevenBar" style="width: 0%"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                            <span id="analyzerBreakevenProgressText">0% Completed</span>
                            <span class="badge bg-secondary-subtle text-dark border" id="kpiBreakevenBadge">Target</span>
                        </div>
                    </div>

                    <!-- Fixed Rent, Salaries & Other Fixed Info Pills -->
                    <div class="row g-2 text-center pt-2">
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3 border h-100">
                                <span class="text-muted small d-block" style="font-size: 0.68rem;">Shop Rent</span>
                                <strong class="text-dark font-monospace" style="font-size: 0.8rem;" id="dispMonthlyRentSub">Rs. 50k / 30d</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3 border h-100">
                                <span class="text-muted small d-block" style="font-size: 0.68rem;">Staff Payroll</span>
                                <strong class="text-dark font-monospace" style="font-size: 0.8rem;" id="dispStaffCountSub">1 Staff / 30d</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3 border h-100">
                                <span class="text-muted small d-block" style="font-size: 0.68rem;">Other Fixed</span>
                                <strong class="text-dark font-monospace" style="font-size: 0.8rem;" id="dispOtherFixedSub">Rs. 0 / 30d</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Asset Valuation Bar -->
                <div class="mt-3 p-3 bg-dark text-white rounded-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase small text-light opacity-75" style="font-size: 0.72rem;">Active Stock Investment</span>
                            <h5 class="font-monospace text-warning fw-bold mb-0" id="kpiInventoryValue">Rs. 0.00</h5>
                        </div>
                        <div class="text-end text-light opacity-75 small" style="font-size: 0.72rem;">
                            Products: <strong id="kpiActiveProducts">0</strong><br>
                            Low Stock: <strong class="text-danger" id="kpiLowStockCount">0</strong>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- 4. OPERATIONAL DETAILS (CASHIERS, TOP PRODUCTS & 14-DAY VELOCITY) -->
    <div class="row g-2 g-md-3 mb-3">
        
        <!-- Cashier Billing Performance (Mobile-Friendly List) -->
        <div class="col-lg-6">
            <div class="dash-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="fw-bold text-dark small text-uppercase" id="cashierSectionTitle">
                        <i class="fas fa-users text-primary me-1"></i> Cashier & Staff Billing (Today)
                    </span>
                    <small class="text-muted">Who billed what</small>
                </div>
                <div id="cashierPerformanceList">
                    <div class="text-center py-3 text-muted small">Loading staff data...</div>
                </div>
            </div>
        </div>

        <!-- Top Selling Fast Moving Products -->
        <div class="col-lg-6">
            <div class="dash-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="fw-bold text-dark small text-uppercase">
                        <i class="fas fa-star text-warning me-1"></i> Top Fast-Selling Items
                    </span>
                    <a href="<?= url('/reports/product_velocity.php') ?>" class="btn btn-xs btn-outline-danger fw-bold" style="font-size: 0.72rem;">
                        <i class="fas fa-cubes-stacked me-1"></i> Dead & Velocity &rarr;
                    </a>
                </div>
                <div class="list-group list-group-flush" id="topSellingList">
                    <div class="text-center py-3 text-muted small">Loading top products...</div>
                </div>
            </div>
        </div>

    </div>

    <!-- 5. 14-DAY SALES TREND CHART & LOW STOCK RESTOCK ALERTS -->
    <div class="row g-2 g-md-3 mb-3">
        
        <!-- 14-Day Sales Velocity Chart -->
        <div class="col-lg-7">
            <div class="dash-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="fw-bold text-dark small text-uppercase">
                        <i class="fas fa-chart-line text-danger me-1"></i> 14-Day Sales Velocity Trend
                    </span>
                    <span class="badge bg-danger-subtle text-danger">Sales Trajectory</span>
                </div>
                <div style="position: relative; height: 220px; width: 100%;">
                    <canvas id="revenueTrendChart"></canvas>
                    <div id="chartPlaceholder" class="d-none position-absolute top-50 start-50 translate-middle text-muted text-center w-100 px-3">
                        <i class="fas fa-chart-line display-6 mb-2 d-block text-secondary"></i>
                        <span class="small">No sales logged in last 14 days.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Critical Low Stock Alerts -->
        <div class="col-lg-5">
            <div class="dash-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="fw-bold text-danger small text-uppercase">
                        <i class="fas fa-triangle-exclamation me-1"></i> Low Stock Warnings (Restock Alert)
                    </span>
                    <a href="<?= url('/inventory/index.php') ?>" class="btn btn-xs btn-outline-danger fw-bold">Inventory &rarr;</a>
                </div>
                <div class="list-group list-group-flush" id="lowStockList">
                    <div class="text-center py-3 text-muted small">Loading low stock items...</div>
                </div>
            </div>
        </div>

    </div>

    <!-- 6. OWNER FAST SHORTCUTS TOOLBAR (LARGE MOBILE APP TILES) -->
    <div class="dash-card mb-4 p-3">
        <span class="fw-bold text-dark small text-uppercase d-block mb-3">
            <i class="fas fa-bolt text-warning me-1"></i> Quick Store Shortcuts
        </span>
        <div class="row g-2">
            <div class="col-4 col-md-2">
                <a href="<?= url('/pos/index.php') ?>" class="app-tile-btn">
                    <div class="app-tile-icon bg-danger-subtle text-danger"><i class="fas fa-cash-register"></i></div>
                    <strong style="font-size: 0.8rem;">POS Counter</strong>
                    <small class="text-muted" style="font-size: 0.68rem;">Bikri Shuru</small>
                </a>
            </div>
            <div class="col-4 col-md-2">
                <a href="<?= url('/expenses/index.php') ?>" class="app-tile-btn">
                    <div class="app-tile-icon bg-warning-subtle text-warning-emphasis"><i class="fas fa-wallet"></i></div>
                    <strong style="font-size: 0.8rem;">Daily Expense</strong>
                    <small class="text-muted" style="font-size: 0.68rem;">Khana / Chai</small>
                </a>
            </div>
            <div class="col-4 col-md-2">
                <a href="<?= url('/employees/index.php') ?>" class="app-tile-btn">
                    <div class="app-tile-icon bg-info-subtle text-info"><i class="fas fa-users"></i></div>
                    <strong style="font-size: 0.8rem;">Staff Khata</strong>
                    <small class="text-muted" style="font-size: 0.68rem;">Advances</small>
                </a>
            </div>
            <div class="col-4 col-md-2">
                <a href="<?= url('/inventory/index.php') ?>" class="app-tile-btn">
                    <div class="app-tile-icon bg-success-subtle text-success"><i class="fas fa-boxes-stacked"></i></div>
                    <strong style="font-size: 0.8rem;">Inventory</strong>
                    <small class="text-muted" style="font-size: 0.68rem;">Stock List</small>
                </a>
            </div>
            <div class="col-4 col-md-2">
                <a href="<?= url('/reports/pnl.php') ?>" class="app-tile-btn">
                    <div class="app-tile-icon bg-primary-subtle text-primary"><i class="fas fa-file-invoice-dollar"></i></div>
                    <strong style="font-size: 0.8rem;">P&L Report</strong>
                    <small class="text-muted" style="font-size: 0.68rem;">Hisab Kitab</small>
                </a>
            </div>
            <div class="col-4 col-md-2">
                <button type="button" class="app-tile-btn w-100 bg-white" id="quickBtnSettings">
                    <div class="app-tile-icon bg-dark-subtle text-dark"><i class="fas fa-sliders"></i></div>
                    <strong style="font-size: 0.8rem;">Rent & Overheads</strong>
                    <small class="text-muted" style="font-size: 0.68rem;">Set Shop Rent</small>
                </button>
            </div>
            <div class="col-4 col-md-2">
                <a href="<?= url('/reports/product_velocity.php') ?>" class="app-tile-btn">
                    <div class="app-tile-icon bg-danger-subtle text-danger"><i class="fas fa-cubes-stacked"></i></div>
                    <strong style="font-size: 0.8rem;">Dead Items & Velocity</strong>
                    <small class="text-muted" style="font-size: 0.68rem;">Slow & Bestsellers</small>
                </a>
            </div>
        </div>
    </div>

</div>

<!-- MODAL: SET SHOP RENT & MONTHLY FIXED OVERHEADS (100% MOBILE RESPONSIVE) -->
<div class="modal fade" id="modalOverheadSettings" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger p-2 rounded-circle"><i class="fas fa-building text-white"></i></span>
                    <h5 class="modal-title fw-bold mb-0 fs-6">Set Shop Rent & Fixed Costs</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formOverheadSettings">
                <div class="modal-body p-3 p-md-4">
                    <p class="text-muted small mb-3">
                        Shop rent aur staff salaries actual month days (28, 29, 30, ya 31) par divide ho kar rozana ka asal munafa nikaalte hain.
                    </p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Monthly Shop Rent (Mahana Kiraya) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Rs.</span>
                            <input type="number" step="100" min="0" class="form-control form-control-lg font-monospace fw-bold" id="inputShopRent" name="monthly_shop_rent" required placeholder="50000">
                        </div>
                        <div class="form-text small">e.g. 50000. Rent tabdeel honay par yahan update karein.</div>
                    </div>

                    <!-- Active Staff Payroll Info -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-dark small"><i class="fas fa-users text-info me-1"></i> Active Staff Payroll:</span><br>
                                <small class="text-muted" id="modalStaffCount">1 Active Staff</small>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-0 font-monospace text-info fw-bold" id="modalTotalPayroll">Rs. 40,000.00</h6>
                                <small class="text-muted"><a href="<?= url('/employees/index.php') ?>" class="text-decoration-none">Manage Staff &rarr;</a></small>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Other Monthly Fixed Costs Section -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold small mb-0 text-dark">
                                <i class="fas fa-layer-group text-primary me-1"></i> Other Monthly Fixed Costs (Optional)
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 fw-bold shadow-sm" id="btnAddOtherFixedRow" style="font-size: 0.76rem;">
                                <i class="fas fa-plus-circle me-1"></i> Add Cost Row
                            </button>
                        </div>
                        <div class="form-text small mb-2">Mahana bijli, internet, generator diesel, committee, ya doosray bills.</div>

                        <!-- Quick Preset Tags -->
                        <div class="d-flex flex-wrap gap-1 mb-2 align-items-center">
                            <span class="text-muted" style="font-size: 0.7rem;"><i class="fas fa-magic me-1"></i>Quick Add:</span>
                            <button type="button" class="btn btn-sm py-0 px-2 btn-light border rounded-pill quick-cost-preset" data-title="Bijli / Electricity" style="font-size: 0.7rem;">+ Bijli</button>
                            <button type="button" class="btn btn-sm py-0 px-2 btn-light border rounded-pill quick-cost-preset" data-title="Internet & Wifi" style="font-size: 0.7rem;">+ Internet</button>
                            <button type="button" class="btn btn-sm py-0 px-2 btn-light border rounded-pill quick-cost-preset" data-title="Generator Fuel" style="font-size: 0.7rem;">+ Generator</button>
                            <button type="button" class="btn btn-sm py-0 px-2 btn-light border rounded-pill quick-cost-preset" data-title="Water / Utility" style="font-size: 0.7rem;">+ Water</button>
                            <button type="button" class="btn btn-sm py-0 px-2 btn-light border rounded-pill quick-cost-preset" data-title="Shop Municipal Tax" style="font-size: 0.7rem;">+ Shop Tax</button>
                        </div>

                        <!-- Rows Container -->
                        <div id="otherFixedRowsList" class="d-flex flex-column gap-2 mb-2" style="max-height: 200px; overflow-y: auto; padding-right: 2px;">
                            <!-- Injected dynamically via JS -->
                        </div>

                        <!-- Empty State Notice -->
                        <div id="otherFixedEmptyNotice" class="text-center py-3 px-2 bg-light rounded-3 border border-dashed text-muted small d-none">
                            <i class="fas fa-receipt d-block mb-1 text-secondary opacity-50" style="font-size: 1.25rem;"></i>
                            <span>Koi additional fixed cost darj nahi hai. Upar <strong>"+ Add Cost Row"</strong> ya quick tag par click karein.</span>
                        </div>

                        <!-- Subtotal Display -->
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-light rounded-3 border">
                            <span class="small text-muted fw-semibold" style="font-size: 0.75rem;">Total Other Fixed Overheads:</span>
                            <span class="font-monospace fw-bold text-dark small" id="dispOtherFixedSubtotal">Rs. 0.00</span>
                        </div>
                    </div>

                    <!-- Live Calculation Preview -->
                    <div class="p-3 bg-primary-subtle border border-primary rounded-3">
                        <div class="d-flex justify-content-between align-items-center text-primary fw-bold">
                            <span class="small"><i class="fas fa-calculator me-1"></i> Daily Fixed Burden:</span>
                            <span class="fs-6 font-monospace" id="modalPreviewDaily">Rs. 0.00 / day</span>
                        </div>
                        <small class="text-muted d-block mt-1" id="modalPreviewFormula" style="font-size: 0.72rem;">
                            (Rent + Salaries + Other) ÷ <span id="modalPreviewDays">30</span> days
                        </small>
                    </div>
                </div>
                <div class="modal-footer bg-light px-3 py-2">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold" id="btnSaveOverheads">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    let currentFilter = 'today';
    let customStart = '';
    let customEnd = '';
    let revenueChart = null;

    // Timeframe button click handler
    $('#timeframeButtonGroup button').on('click', function() {
        $('#timeframeButtonGroup button').removeClass('active');
        $(this).addClass('active');
        
        currentFilter = $(this).data('filter');
        if (currentFilter === 'custom') {
            $('#customRangeBar').removeClass('d-none');
            if (!$('#custom_start_date').val() || !$('#custom_end_date').val()) {
                const today = new Date();
                const todayStr = today.toISOString().split('T')[0];
                const startObj = new Date();
                startObj.setDate(startObj.getDate() - 7);
                const startStr = startObj.toISOString().split('T')[0];
                
                $('#custom_start_date').val(startStr);
                $('#custom_end_date').val(todayStr);
                customStart = startStr;
                customEnd = todayStr;
                loadDashboardData();
            } else {
                $('#custom_start_date').focus();
            }
        } else {
            $('#customRangeBar').addClass('d-none');
            loadDashboardData();
        }
    });

    $('#btnApplyCustomDate').on('click', function() {
        customStart = $('#custom_start_date').val();
        customEnd = $('#custom_end_date').val();
        if (!customStart || !customEnd) {
            alert('Please select both Start Date and End Date.');
            return;
        }
        loadDashboardData();
    });

    $('#btnManualRefresh').on('click', function() {
        $(this).find('i').addClass('fa-spin');
        loadDashboardData(function() {
            $('#btnManualRefresh i').removeClass('fa-spin');
        });
        loadExtraLists();
    });

    // Initial load
    loadDashboardData();
    loadExtraLists();

    function loadDashboardData(callback) {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/reports.php?action=dashboard_kpis',
            type: 'GET',
            data: {
                filter: currentFilter,
                start_date: customStart,
                end_date: customEnd
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    renderDashboard(res);
                }
                if (typeof callback === 'function') callback();
            },
            error: function() {
                if (typeof callback === 'function') callback();
            }
        });
    }

    function renderDashboard(data) {
        const netRev = data.net_revenue || 0;
        const cogs = data.cogs || 0;
        const grossProf = data.gross_profit || 0;
        const marginPct = data.gross_margin_pct || 0;

        const totalCost = data.true_operating_expenses !== undefined ? data.true_operating_expenses : (data.operating_expenses || 0);
        const varExp = data.variable_expenses !== undefined ? data.variable_expenses : (data.expense_cash || 0);
        const netProf = data.true_net_profit !== undefined ? data.true_net_profit : (data.net_profit || 0);

        // 1. The 7 Core KPI Tiles
        // CARD 1: Total Sale
        $('#kpiNetRevenue').text(`Rs. ${netRev.toLocaleString('en-PK', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`);
        $('#kpiBillsCount').text(data.sales_count || 0);
        $('#kpiItemsSold').text(data.items_sold || 0);

        // CARD 2: Credit Sales (Udhar Given)
        const creditSales = data.credit_sales || 0;
        const creditBills = data.credit_bills_count || 0;
        $('#kpiCreditSales').text(`Rs. ${Math.round(creditSales).toLocaleString('en-PK')}`);
        $('#kpiCreditSalesSub').text(`${creditBills} Bills • Udhar Given`);

        // CARD 3: Cash in Drawer / Hand
        const drawerBal = data.drawer_cash_balance !== undefined ? data.drawer_cash_balance : 0;
        $('#kpiDrawerCash').text(`Rs. ${Math.round(drawerBal).toLocaleString('en-PK')}`);
        const khataCashRec = data.khata_cash_recovered || 0;
        if (khataCashRec > 0) {
            $('#kpiDrawerCashSub').html(`Sales + Rs. ${Math.round(khataCashRec).toLocaleString()} Khata`);
        } else {
            $('#kpiDrawerCashSub').text('Counter Cash in Hand');
        }

        // CARD 4: Bank / Account
        const bankBal = data.bank_balance !== undefined ? data.bank_balance : (data.bank_inflow || 0);
        $('#kpiBankAccount').text(`Rs. ${Math.round(bankBal).toLocaleString('en-PK')}`);
        const khataBankRec = data.khata_bank_recovered || 0;
        if (khataBankRec > 0) {
            $('#kpiBankSub').html(`POS + Rs. ${Math.round(khataBankRec).toLocaleString()} Khata`);
        } else if (data.bank_outflow > 0) {
            $('#kpiBankSub').html(`In: ${Math.round(data.bank_inflow || 0)} • Out: ${Math.round(data.bank_outflow || 0)}`);
        } else {
            $('#kpiBankSub').text('Online & Bank Transfers');
        }

        // CARD 5: Total Expenses
        $('#kpiExpenses').text(`Rs. ${totalCost.toLocaleString('en-PK', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`);
        $('#kpiExpensesSub').text('Rent + Staff + Petty');

        // CARD 6: Profit (Gross Margin)
        $('#kpiGrossProfit').text(`Rs. ${grossProf.toLocaleString('en-PK', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`);
        $('#kpiGrossMarginSub').text(`Margin: ${marginPct}% (Gross)`);

        // CARD 7: Actual Profit (Net Take-Home)
        $('#kpiNetProfit').text(`Rs. ${netProf.toLocaleString('en-PK', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`);

        if (netProf < 0) {
            $('#kpiNetProfit').addClass('text-danger').removeClass('text-success');
            $('#kpiProfitTile').addClass('tile-red').removeClass('tile-emerald');
            $('#kpiProfitTitle').addClass('text-danger').removeClass('text-success');
            $('#kpiProfitStatusText').html(`<span class="text-danger"><i class="fas fa-circle-exclamation me-1"></i> Deficit</span>`);
        } else {
            $('#kpiNetProfit').addClass('text-success').removeClass('text-danger');
            $('#kpiProfitTile').addClass('tile-emerald').removeClass('tile-red');
            $('#kpiProfitTitle').addClass('text-success').removeClass('text-danger');
            $('#kpiProfitStatusText').html(`<span class="text-success"><i class="fas fa-circle-check me-1"></i> Pocket Surplus</span>`);
        }

        // 2. Waterfall Receipt Lines
        $('#flowNetSales').text(`Rs. ${netRev.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#flowCreditSales').text(`Rs. ${creditSales.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        const totalKhataRec = (data.khata_cash_recovered || 0) + (data.khata_bank_recovered || 0);
        $('#flowKhataRecovered').text(`+Rs. ${totalKhataRec.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#flowCogs').text(`-Rs. ${cogs.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#flowGrossProfit').text(`Rs. ${grossProf.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#flowMarginPct').text(`Gross Margin: ${marginPct}%`);
        $('#dispVariableExp').text(`-Rs. ${varExp.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#flowNetProfit').text(`Rs. ${netProf.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);

        if (netProf < 0) {
            $('#profitReceiptBanner').css('background', '#fef2f2');
            $('#flowNetProfit').addClass('text-danger').removeClass('text-success');
            $('#flowProfitBadge').addClass('bg-danger-subtle text-danger').removeClass('bg-success-subtle text-success').text(`Deficit: Rs. ${Math.abs(netProf).toFixed(0)}`);
        } else {
            $('#profitReceiptBanner').css('background', '#f0fdf4');
            $('#flowNetProfit').addClass('text-success').removeClass('text-danger');
            $('#flowProfitBadge').addClass('bg-success-subtle text-success').removeClass('bg-danger-subtle text-danger').text(`Net Profit: +Rs. ${netProf.toFixed(0)}`);
        }

        // 3. Fixed Overheads Hub
        if (data.fixed_overheads) {
            const fo = data.fixed_overheads;
            $('#dispDivisorMonthName').text(fo.month_name);
            $('#dispDivisorDays').text(fo.effective_days || fo.days_in_month);

            const isSingleDay = (currentFilter === 'today' || currentFilter === 'yesterday');
            const rentDisp = isSingleDay ? fo.daily_rent : fo.period_rent;
            const payrollDisp = isSingleDay ? fo.daily_payroll : fo.period_payroll;
            const otherDisp = isSingleDay ? (fo.daily_other_fixed || 0) : (fo.period_other_fixed || 0);

            $('#dispDailyRent').text(`-Rs. ${rentDisp.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
            $('#dispDailyPayroll').text(`-Rs. ${payrollDisp.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);

            if (fo.monthly_other_fixed > 0) {
                $('#dispOtherFixedRow').show();
                $('#dispDailyOtherFixed').text(`-Rs. ${otherDisp.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
            } else {
                $('#dispOtherFixedRow').hide();
            }

            $('#dispMonthlyRentSub').text(`Rs. ${fo.monthly_rent.toLocaleString()} ÷ ${fo.days_in_month}d`);
            $('#dispStaffCountSub').text(`${fo.active_staff_count} Staff ÷ ${fo.days_in_month}d`);
            $('#dispOtherFixedSub').text(`Rs. ${(fo.monthly_other_fixed || 0).toLocaleString()} ÷ ${fo.days_in_month}d`);

            // Dynamic section titles based on active filter
            const targetTitles = {
                'today': "Today's Target (Breakeven Meter)",
                'yesterday': "Yesterday's Target (Breakeven Meter)",
                'week': "This Week's Target (Breakeven Meter)",
                'month': "This Month's Target (Breakeven Meter)",
                'last_2_months': "Last 2 Months Target (Breakeven Meter)",
                'lifetime': "Lifetime Target (Breakeven Meter)",
                'custom': "Custom Period Target (Breakeven Meter)"
            };
            $('#breakevenMeterTitle').html(`<i class="fas fa-bullseye text-danger me-1"></i> ${targetTitles[currentFilter] || "Breakeven Target Meter"}`);

            const cashierTitles = {
                'today': "Cashier & Staff Billing (Today)",
                'yesterday': "Cashier & Staff Billing (Yesterday)",
                'week': "Cashier & Staff Billing (This Week)",
                'month': "Cashier & Staff Billing (This Month)",
                'last_2_months': "Cashier & Staff Billing (Last 2 Months)",
                'lifetime': "Cashier & Staff Billing (Lifetime)",
                'custom': "Cashier & Staff Billing (Selected Period)"
            };
            $('#cashierSectionTitle').html(`<i class="fas fa-users text-primary me-1"></i> ${cashierTitles[currentFilter] || "Cashier & Staff Billing"}`);
        }

        // 4. Breakeven Progress
        if (data.breakeven_sales !== undefined) {
            $('#analyzerBreakevenTarget').text(`Rs. ${data.breakeven_sales.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
            const progress = Math.min(100, Math.max(0, data.breakeven_progress_pct || 0));
            $('#analyzerBreakevenBar').css('width', `${progress}%`);
            $('#kpiBreakevenBadge').text(`${data.breakeven_progress_pct}% Target`);

            if (netRev >= data.breakeven_sales && data.breakeven_sales > 0) {
                $('#analyzerBreakevenBar').removeClass('bg-warning bg-danger').addClass('bg-success');
                $('#analyzerBreakevenProgressText').html(`<span class="text-success fw-bold"><i class="fas fa-check-circle"></i> Breakeven Passed! Profit Surplus</span>`);
            } else {
                $('#analyzerBreakevenBar').removeClass('bg-success').addClass('bg-warning');
                const diff = Math.max(0, data.breakeven_sales - netRev);
                $('#analyzerBreakevenProgressText').html(`${data.breakeven_progress_pct}% Covered (<span class="text-dark fw-bold">Rs. ${diff.toFixed(0)}</span> more needed)`);
            }
        }

        // 5. Cashier List (Clean Mobile Cards)
        if (data.cashier_stats && data.cashier_stats.length > 0) {
            let cHtml = '';
            data.cashier_stats.forEach(function(c) {
                const bAmt = parseFloat(c.total_sales || 0);
                const isStaff = c.role !== 'admin';
                cHtml += `
                    <div class="d-flex justify-content-between align-items-center p-2 mb-1 rounded bg-light border">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge ${isStaff ? 'bg-primary-subtle text-primary' : 'bg-dark-subtle text-dark'} p-2 rounded-circle">
                                <i class="fas ${isStaff ? 'fa-user' : 'fa-user-tie'}"></i>
                            </span>
                            <div>
                                <strong class="text-dark text-capitalize small">${c.username}</strong>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">${c.bills_count} bills cut</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="font-monospace fw-bold small ${bAmt > 0 ? 'text-success' : 'text-muted'}">
                                Rs. ${bAmt.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </span>
                        </div>
                    </div>
                `;
            });
            $('#cashierPerformanceList').html(cHtml);
        } else {
            $('#cashierPerformanceList').html('<div class="text-center text-muted py-2 small">No staff billing activity recorded.</div>');
        }

        // Assets
        $('#kpiInventoryValue').text(`Rs. ${(data.inventory_value || 0).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#kpiActiveProducts').text(data.active_products || 0);
        $('#kpiLowStockCount').text(data.low_stock_products || 0);
    }

    function loadExtraLists() {
        // Top Selling Products
        $.ajax({
            url: (window.BASE_URL || '') + '/api/reports.php?action=top_selling',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.top.length > 0) {
                    let html = '';
                    response.top.forEach(function(p, i) {
                        html += `
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <strong class="text-dark" style="font-size: 0.82rem;">${i+1}. ${p.name}</strong><br>
                                    <small class="text-muted" style="font-size: 0.72rem;">Sold: <strong class="text-dark">${p.total_qty}</strong> units</small>
                                </div>
                                <span class="badge bg-danger-subtle text-danger font-monospace" style="font-size: 0.82rem;">
                                    Rs. ${parseFloat(p.total_val).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                </span>
                            </div>
                        `;
                    });
                    $('#topSellingList').html(html);
                } else {
                    $('#topSellingList').html('<div class="p-2 text-center text-muted small">No fast-moving items recorded.</div>');
                }
            }
        });

        // Low Stock Warnings
        $.ajax({
            url: (window.BASE_URL || '') + '/api/reports.php?action=low_stock',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.low.length > 0) {
                    let html = '';
                    response.low.forEach(function(p) {
                        html += `
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <strong class="text-dark" style="font-size: 0.82rem;">${p.name}</strong><br>
                                    <small class="text-muted" style="font-size: 0.72rem;">Limit: ${p.low_stock_threshold} units</small>
                                </div>
                                <span class="badge bg-danger rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                                    ${p.quantity} Left
                                </span>
                            </div>
                        `;
                    });
                    $('#lowStockList').html(html);
                } else {
                    $('#lowStockList').html('<div class="p-2 text-center text-success small"><i class="fas fa-circle-check me-1"></i> All stock levels healthy.</div>');
                }
            }
        });

        // 14-Day Sales Trend Chart
        $.ajax({
            url: (window.BASE_URL || '') + '/api/reports.php?action=revenue_trend',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.success && response.trend.length > 0) {
                    $('#chartPlaceholder').addClass('d-none');
                    $('#revenueTrendChart').removeClass('d-none');
                    const labels = response.trend.map(t => t.date);
                    const values = response.trend.map(t => parseFloat(t.total_sales));
                    
                    if (revenueChart) revenueChart.destroy();
                    
                    const ctx = document.getElementById('revenueTrendChart').getContext('2d');
                    revenueChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Daily Sales',
                                data: values,
                                borderColor: '#dc2626',
                                backgroundColor: 'rgba(220, 38, 38, 0.08)',
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#dc2626',
                                pointRadius: 3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(0,0,0,0.05)' },
                                    ticks: {
                                        callback: function(v) { return 'Rs. ' + v.toLocaleString(); },
                                        font: { size: 10 }
                                    }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 9 } }
                                }
                            }
                        }
                    });
                } else {
                    $('#revenueTrendChart').addClass('d-none');
                    $('#chartPlaceholder').removeClass('d-none');
                }
            }
        });
    }

    // Helper to generate a dynamic row HTML for Other Fixed Costs
    function createOtherFixedRow(title = '', amount = '') {
        const safeTitle = $('<div>').text(title || '').html();
        const safeAmount = (amount !== '' && amount !== null && amount !== undefined) ? amount : '';
        return `
            <div class="other-fixed-row p-2 bg-white rounded-3 border shadow-sm d-flex align-items-center gap-2">
                <div class="flex-grow-1">
                    <input type="text" name="other_fixed_title[]" class="form-control form-control-sm row-fixed-title" placeholder="Cost Description (e.g. Bijli Bill, Wifi)" value="${safeTitle}">
                </div>
                <div style="width: 140px; min-width: 120px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text fw-bold px-2 text-muted" style="font-size: 0.72rem;">Rs.</span>
                        <input type="number" step="100" min="0" name="other_fixed_amount[]" class="form-control form-control-sm font-monospace fw-bold text-end row-fixed-amount" placeholder="0" value="${safeAmount}">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-fixed-row px-2 py-1 rounded-2" title="Delete Row">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
    }

    // Modal & Rent Settings Handlers
    $('#btnOpenOverheadModal, #quickBtnSettings').on('click', function() {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/reports.php?action=get_fixed_overheads',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#inputShopRent').val(res.monthly_shop_rent);
                    $('#modalStaffCount').text(`${res.active_staff_count} Active Staff`);
                    $('#modalTotalPayroll').text(`Rs. ${parseFloat(res.total_monthly_payroll).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#modalPreviewDays').text(res.days_in_month);

                    const $list = $('#otherFixedRowsList');
                    $list.empty();
                    const items = res.other_monthly_fixed_items || [];
                    if (items.length > 0) {
                        items.forEach(function(item) {
                            $list.append(createOtherFixedRow(item.title, item.amount));
                        });
                        $('#otherFixedEmptyNotice').addClass('d-none');
                    } else if (parseFloat(res.other_monthly_fixed) > 0) {
                        $list.append(createOtherFixedRow(res.other_fixed_title || 'Utilities & Bills', res.other_monthly_fixed));
                        $('#otherFixedEmptyNotice').addClass('d-none');
                    } else {
                        $('#otherFixedEmptyNotice').removeClass('d-none');
                    }

                    recalculateModalPreview(parseFloat(res.total_monthly_payroll), res.days_in_month);
                    $('#modalOverheadSettings').modal('show');
                }
            }
        });
    });

    function recalculateModalPreview(payroll, days) {
        if (payroll === undefined) {
            payroll = parseFloat($('#modalTotalPayroll').text().replace(/[^0-9.]/g, '')) || 0;
        }
        if (days === undefined) {
            days = parseInt($('#modalPreviewDays').text()) || 30;
        }
        const rent = parseFloat($('#inputShopRent').val()) || 0;

        let totalOther = 0;
        $('.row-fixed-amount').each(function() {
            const val = parseFloat($(this).val());
            if (!isNaN(val) && val > 0) {
                totalOther += val;
            }
        });

        $('#dispOtherFixedSubtotal').text(`Rs. ${totalOther.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);

        const totalMonthly = rent + payroll + totalOther;
        const daily = totalMonthly / (days || 30);
        $('#modalPreviewDaily').text(`Rs. ${daily.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})} / day`);
        $('#modalPreviewFormula').html(`(Rent: Rs. ${rent.toLocaleString()} + Staff: Rs. ${payroll.toLocaleString()} + Other: Rs. ${totalOther.toLocaleString()}) ÷ <strong>${days}</strong> days`);
    }

    // Add row button
    $('#btnAddOtherFixedRow').on('click', function() {
        const rowHtml = createOtherFixedRow('', '');
        $('#otherFixedRowsList').append(rowHtml);
        $('#otherFixedEmptyNotice').addClass('d-none');
        $('#otherFixedRowsList .other-fixed-row:last-child .row-fixed-title').focus();
        recalculateModalPreview();
    });

    // Quick add presets
    $(document).on('click', '.quick-cost-preset', function() {
        const title = $(this).data('title');
        const rowHtml = createOtherFixedRow(title, '');
        $('#otherFixedRowsList').append(rowHtml);
        $('#otherFixedEmptyNotice').addClass('d-none');
        $('#otherFixedRowsList .other-fixed-row:last-child .row-fixed-amount').focus();
        recalculateModalPreview();
    });

    // Remove row button
    $(document).on('click', '.btn-remove-fixed-row', function() {
        $(this).closest('.other-fixed-row').remove();
        if ($('#otherFixedRowsList .other-fixed-row').length === 0) {
            $('#otherFixedEmptyNotice').removeClass('d-none');
        }
        recalculateModalPreview();
    });

    // Live typing recalculation
    $(document).on('input', '#inputShopRent, .row-fixed-amount', function() {
        recalculateModalPreview();
    });

    $('#formOverheadSettings').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btnSaveOverheads');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/reports.php?action=save_fixed_overheads',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Changes');
                if (res.success) {
                    $('#modalOverheadSettings').modal('hide');
                    loadDashboardData();
                } else {
                    alert(res.message || 'Error saving settings.');
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Changes');
                alert('Server communication error.');
            }
        });
    });

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
