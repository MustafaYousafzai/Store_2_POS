<?php
require_once __DIR__ . '/../config/auth.php';
if (!isAdmin()) {
    redirect('/pos/index.php');
}
require_once __DIR__ . '/../includes/header.php';

$isAdmin = isAdmin();
$today = date('Y-m-d');

// Preload categories directly from database for instant rendering
$db = getDBConnection();
$catStmt = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
$allCategories = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Preload store asset valuation summary for zero-delay initial rendering
$stockOverviewStmt = $db->query("
    SELECT 
        COUNT(*) as total_catalog,
        COUNT(CASE WHEN quantity > 0 THEN 1 END) as in_stock_count,
        COUNT(CASE WHEN quantity = 0 THEN 1 END) as out_of_stock_count,
        COALESCE(SUM(quantity), 0) as total_units,
        COALESCE(SUM(quantity * cost_price), 0) as total_cost_value,
        COALESCE(SUM(quantity * selling_price), 0) as total_retail_value
    FROM products 
    WHERE status = 'active'
");
$stockOverview = $stockOverviewStmt ? $stockOverviewStmt->fetch(PDO::FETCH_ASSOC) : [
    'total_catalog' => 0, 'in_stock_count' => 0, 'out_of_stock_count' => 0,
    'total_units' => 0, 'total_cost_value' => 0, 'total_retail_value' => 0
];
$initCostVal = (float)$stockOverview['total_cost_value'];
$initRetailVal = (float)$stockOverview['total_retail_value'];
$initProfit = $initRetailVal - $initCostVal;
$initMargin = ($initCostVal > 0) ? round(($initProfit / $initCostVal) * 100, 1) : 0.0;
?>

<style>
/* ==========================================================================
   ENTERPRISE INVENTORY VELOCITY & DECISION SUPPORT INTELLIGENCE (UI/UX 2.0)
   ========================================================================== */
:root {
    --vel-bg: #f8fafc;
    --vel-card-bg: #ffffff;
    --vel-border: #e2e8f0;
    --vel-dead: #dc2626;
    --vel-dead-bg: #fef2f2;
    --vel-dead-border: #fecaca;
    --vel-slow: #d97706;
    --vel-slow-bg: #fffbeb;
    --vel-slow-border: #fde68a;
    --vel-fast: #16a34a;
    --vel-fast-bg: #f0fdf4;
    --vel-fast-border: #bbf7d0;
    --vel-reorder: #0284c7;
    --vel-reorder-bg: #f0f9ff;
    --vel-reorder-border: #bae6fd;
    --vel-primary: #0f172a;
    --vel-radius: 16px;
    --vel-radius-sm: 10px;
}

body {
    background-color: var(--vel-bg);
}

/* Scrollable Container with Hidden Scrollbars */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* KPI Executive Cards */
.velocity-kpi-card {
    background: var(--vel-card-bg);
    border-radius: var(--vel-radius);
    border: 1px solid var(--vel-border);
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.velocity-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
}
.velocity-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}
.velocity-kpi-card.card-dead::before { background-color: var(--vel-dead); }
.velocity-kpi-card.card-slow::before { background-color: var(--vel-slow); }
.velocity-kpi-card.card-fast::before { background-color: var(--vel-fast); }
.velocity-kpi-card.card-reorder::before { background-color: var(--vel-reorder); }

.vel-icon-box {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

/* Executive Inventory Asset Valuation Banner */
.inventory-valuation-banner {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 16px;
    padding: 1.25rem 1.4rem;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #ffffff;
    margin-bottom: 1rem;
}
.val-stat-box {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 12px;
    padding: 12px 14px;
    transition: all 0.2s ease;
    height: 100%;
}
.val-stat-box:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}
.val-stat-box .val-icon-box {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

/* Date Range Pill Buttons */
.date-pill {
    border-radius: 30px;
    padding: 6px 16px;
    font-size: 0.82rem;
    font-weight: 600;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.date-pill:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
}
.date-pill.active {
    background: #0f172a !important;
    color: #ffffff !important;
    border-color: #0f172a !important;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
}

/* Segment Navigation Tabs */
.velocity-tabs .nav-link {
    border-radius: 30px;
    padding: 8px 18px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    border: 1px solid transparent;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.velocity-tabs .nav-link:hover {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.8);
}
.velocity-tabs .nav-link.active {
    color: #ffffff !important;
    font-weight: 700;
}
.velocity-tabs .nav-link.tab-all.active {
    background-color: #0f172a !important;
    border-color: #0f172a !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.25);
}
.velocity-tabs .nav-link.tab-dead.active {
    background-color: var(--vel-dead) !important;
    border-color: var(--vel-dead) !important;
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.25);
}
.velocity-tabs .nav-link.tab-slow.active {
    background-color: var(--vel-slow) !important;
    border-color: var(--vel-slow) !important;
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.25);
}
.velocity-tabs .nav-link.tab-fast.active {
    background-color: var(--vel-fast) !important;
    border-color: var(--vel-fast) !important;
    box-shadow: 0 4px 14px rgba(22, 163, 74, 0.25);
}
.velocity-tabs .nav-link.tab-reorder.active {
    background-color: var(--vel-reorder) !important;
    border-color: var(--vel-reorder) !important;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
}

/* High-Density Compact Enterprise Velocity Table */
#velocityTable {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}
#velocityTable thead th {
    background: #0f172a !important;
    color: #f8fafc !important;
    border-bottom: 2px solid #334155 !important;
    border-top: none;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.4px;
    padding: 10px 10px;
    white-space: nowrap;
    vertical-align: middle;
}
#velocityTable thead th.sortable-th {
    cursor: pointer;
    user-select: none;
    transition: background 0.15s ease;
}
#velocityTable thead th.sortable-th:hover {
    background: #1e293b !important;
}
#velocityTable thead th.sortable-th .sort-icon {
    font-size: 0.72rem;
    color: #64748b;
    margin-left: 4px;
}
#velocityTable thead th.sortable-th.active-sort {
    color: #38bdf8 !important;
}
#velocityTable thead th.sortable-th.active-sort .sort-icon {
    color: #38bdf8 !important;
}
#velocityTable tbody td {
    padding: 8px 10px;
    vertical-align: middle;
    font-size: 0.82rem;
    border-bottom: 1px solid #f1f5f9;
}
#velocityTable tbody tr:hover td {
    background-color: #f8fafc !important;
}
#velocityTable tbody tr.is-dead-row td {
    background-color: rgba(254, 242, 242, 0.55);
}
#velocityTable tbody tr.is-dead-row:hover td {
    background-color: rgba(254, 226, 226, 0.75) !important;
}
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 16px;
}

/* Mobile Product Card Grid */
.product-velocity-card {
    background: #ffffff;
    border-radius: var(--vel-radius);
    border: 1px solid var(--vel-border);
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    transition: all 0.2s ease;
    overflow: hidden;
}
.product-velocity-card:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,0.07);
    border-color: #cbd5e1;
}
.product-velocity-card.is-dead-card {
    border-color: var(--vel-dead-border);
    background: linear-gradient(180deg, #fffafa 0%, #ffffff 100%);
}

.metric-micro-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: var(--vel-radius-sm);
    padding: 8px 10px;
    text-align: center;
}
.metric-micro-box.highlight-dead {
    background: var(--vel-dead-bg);
    border-color: var(--vel-dead-border);
}
.metric-micro-box.highlight-slow {
    background: var(--vel-slow-bg);
    border-color: var(--vel-slow-border);
}
.metric-micro-box.highlight-fast {
    background: var(--vel-fast-bg);
    border-color: var(--vel-fast-border);
}
.metric-micro-box.highlight-reorder {
    background: var(--vel-reorder-bg);
    border-color: var(--vel-reorder-border);
}

/* Status Badges */
.badge-vel-dead {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
    font-weight: 700;
}
.badge-vel-slow {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
    font-weight: 700;
}
.badge-vel-fast {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
    font-weight: 700;
}
.badge-vel-out {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    font-weight: 600;
}

/* Order Planner Drawer & Modal */
.order-qty-input {
    width: 85px;
    font-weight: 700;
    text-align: center;
    border-radius: 8px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hide-on-mobile {
        display: none !important;
    }
    .date-pill {
        padding: 5px 12px;
        font-size: 0.78rem;
    }
    .velocity-tabs .nav-link {
        padding: 6px 14px;
        font-size: 0.8rem;
    }
}

/* Print Styles */
@media print {
    .d-print-none, .top-navbar, .sidebar, #viewModeGroup, #btnOpenOrderPlanner, #btnRefreshData, #btnExportCsv {
        display: none !important;
    }
    body, .main-content {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #viewContainerTable {
        display: block !important;
    }
    #viewContainerCards {
        display: none !important;
    }
    .card, .product-velocity-card {
        border: none !important;
        box-shadow: none !important;
    }
    table {
        font-size: 0.78rem !important;
    }
}
</style>

<div class="container-fluid px-0 py-1">
    <!-- Top Header Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 d-print-none">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-danger text-white px-2.5 py-1 rounded-pill fs-xs text-uppercase fw-bold">
                    <i class="fas fa-chart-pie me-1"></i> Inventory Intelligence
                </span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill fs-xs fw-semibold">
                    <i class="fas fa-cart-shopping me-1"></i> Purchasing &amp; Reorder Decision Support
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-0 mt-1 d-flex align-items-center gap-2">
                <span>Dead Stock &amp; Sales Velocity</span>
            </h3>
            <p class="text-muted fs-sm mb-0">
                Identify dead stock (trapped cash), run-rates, bestsellers, and auto-calculate purchase order quantities.
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Purchase Order Planner Assistant Button -->
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 shadow-sm d-flex align-items-center gap-2" id="btnOpenOrderPlanner" title="Open Purchase Order Assistant & Restock Planner">
                <i class="fas fa-cart-flatbed"></i>
                <span class="fw-bold">Order Planner</span>
                <span class="badge bg-white text-primary rounded-pill px-1.5 py-0.5 fs-xxs fw-bolder" id="badgeOrderCount">0</span>
            </button>

            <!-- View Mode Switcher: Table vs Cards -->
            <div class="btn-group btn-group-sm p-0.5 bg-white border rounded-pill shadow-sm" role="group" id="viewModeGroup">
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold active btn-dark text-white" id="btnViewTable" title="Data Table View (Compact Enterprise View)">
                    <i class="fas fa-table-list me-1"></i> Table
                </button>
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold text-muted" id="btnViewCards" title="Visual Cards View (Recommended for Mobile)">
                    <i class="fas fa-grip me-1"></i> Cards
                </button>
            </div>

            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm" id="btnRefreshData" title="Refresh Live Data">
                <i class="fas fa-arrows-rotate me-1"></i> Refresh
            </button>
            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm" id="btnExportCsv" title="Download Excel CSV">
                <i class="fas fa-file-excel me-1"></i> Export
            </button>
            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 shadow-sm" onclick="window.print();" title="Print this Report">
                <i class="fas fa-print me-1"></i> Print
            </button>
        </div>
    </div>

    <!-- Executive Store Inventory Asset Valuation & Capital Banner (Dukaan Ka Kul Sarmaya) -->
    <div class="inventory-valuation-banner shadow-sm">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 pb-2 mb-2 border-bottom border-white border-opacity-10">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill fw-bold fs-xxs text-uppercase">
                    <i class="fas fa-crown me-1"></i> Store Asset Valuation
                </span>
                <span class="fw-bold text-white fs-sm text-uppercase tracking-wide">
                    Dukaan Ka Kul Sarmaya Aur Inventory Worth (Total Capital &amp; Stock Value)
                </span>
            </div>
            <div class="d-flex align-items-center gap-2 fs-xs text-light text-opacity-75">
                <span id="valCatalogSummary"><i class="fas fa-boxes-stacked me-1 text-info"></i> Tracking <b class="text-white"><?= number_format($stockOverview['total_catalog']) ?></b> Products</span>
                <span class="text-white-50">|</span>
                <span id="valStockRatio"><i class="fas fa-cubes me-1 text-warning"></i> <b class="text-white" id="valStockRatioText"><?= number_format($stockOverview['total_units']) ?> Units</b></span>
            </div>
        </div>

        <div class="row g-2 g-md-3">
            <!-- 1. Total Cost Capital Invested (Mera Kitna Capital Laga Hai) -->
            <div class="col-6 col-lg-3">
                <div class="val-stat-box">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="overflow-hidden">
                            <div class="text-uppercase fw-bold text-warning fs-xxs d-flex align-items-center gap-1 text-truncate">
                                <i class="fas fa-coins"></i> <span>Kul Invested Capital (Cost)</span>
                            </div>
                            <h4 class="fw-bolder mb-0 text-warning mt-1 text-truncate" id="valTotalCost">
                                Rs. <?= number_format($initCostVal, 2) ?>
                            </h4>
                            <div class="text-light text-opacity-75 fs-xxs mt-1 text-truncate">
                                Inventory khareedne par laga sarmaya
                            </div>
                        </div>
                        <div class="val-icon-box bg-warning bg-opacity-25 text-warning hide-on-mobile">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Total Retail Selling Worth (Sale Price Ke Hisab Se Kitna Banta Hai) -->
            <div class="col-6 col-lg-3">
                <div class="val-stat-box">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="overflow-hidden">
                            <div class="text-uppercase fw-bold text-info fs-xxs d-flex align-items-center gap-1 text-truncate">
                                <i class="fas fa-cash-register"></i> <span>Retail Market Value (Sale)</span>
                            </div>
                            <h4 class="fw-bolder mb-0 text-info mt-1 text-truncate" id="valTotalRetail">
                                Rs. <?= number_format($initRetailVal, 2) ?>
                            </h4>
                            <div class="text-light text-opacity-75 fs-xxs mt-1 text-truncate">
                                Tamam maal biknay par wasool hone wali raqam
                            </div>
                        </div>
                        <div class="val-icon-box bg-info bg-opacity-25 text-info hide-on-mobile">
                            <i class="fas fa-store"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Gross Profit Potential / Markup Margin -->
            <div class="col-6 col-lg-3">
                <div class="val-stat-box">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="overflow-hidden">
                            <div class="text-uppercase fw-bold text-success fs-xxs d-flex align-items-center gap-1 text-truncate">
                                <i class="fas fa-chart-line"></i> <span>Gross Profit Potential</span>
                            </div>
                            <h4 class="fw-bolder mb-0 text-success mt-1 text-truncate" id="valProfitPotential">
                                Rs. <?= number_format($initProfit, 2) ?>
                            </h4>
                            <div class="text-light text-opacity-75 fs-xxs mt-1 text-truncate" id="valMarginBadge">
                                +<?= $initMargin ?>% Expected Markup Margin
                            </div>
                        </div>
                        <div class="val-icon-box bg-success bg-opacity-25 text-success hide-on-mobile">
                            <i class="fas fa-arrow-trend-up"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Total Physical Stock Units in Hand -->
            <div class="col-6 col-lg-3">
                <div class="val-stat-box">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="overflow-hidden">
                            <div class="text-uppercase fw-bold text-light fs-xxs d-flex align-items-center gap-1 text-truncate">
                                <i class="fas fa-layer-group text-primary"></i> <span>Physical Stock in Store</span>
                            </div>
                            <h4 class="fw-bolder mb-0 text-white mt-1 text-truncate" id="valTotalUnits">
                                <?= number_format($stockOverview['total_units']) ?> Pieces
                            </h4>
                            <div class="text-light text-opacity-75 fs-xxs mt-1 text-truncate" id="valStockBreakdown">
                                <?= number_format($stockOverview['in_stock_count']) ?> in-stock | <?= number_format($stockOverview['out_of_stock_count']) ?> out of stock
                            </div>
                        </div>
                        <div class="val-icon-box bg-primary bg-opacity-25 text-primary hide-on-mobile">
                            <i class="fas fa-boxes-stacked"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Executive KPI Metric Cards (Interactive click-to-filter) - Positioned at Top -->
    <div class="row g-2 g-md-3 mb-3">
        <!-- 1. Dead Capital (Paisa Phansa) -->
        <div class="col-6 col-lg-3">
            <div class="velocity-kpi-card card-dead p-3 h-100" data-target-segment="dead" title="Click to filter Dead Stock items">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="overflow-hidden">
                        <div class="text-uppercase fw-bold text-danger fs-xxs d-flex align-items-center gap-1">
                            <i class="fas fa-skull-crossbones"></i> <span>Dead Capital (Paisa Phansa)</span>
                        </div>
                        <h4 class="fw-bolder mb-0 text-danger mt-1 text-truncate" id="kpiDeadCapital">
                            Rs. 0.00
                        </h4>
                        <div class="text-muted fs-xs mt-1 text-truncate" id="kpiDeadCountSubtitle">
                            <span class="badge bg-danger text-white rounded-pill px-2" id="kpiDeadCountBadge">0 items</span> 0 sales (Stock &gt; 0)
                        </div>
                        <div class="fs-xxs text-black-50 mt-1 text-truncate" id="kpiDeadRetailSub">
                            Retail Potential: Rs. 0.00
                        </div>
                    </div>
                    <div class="vel-icon-box bg-danger-subtle text-danger hide-on-mobile">
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Slow Moving Inventory -->
        <div class="col-6 col-lg-3">
            <div class="velocity-kpi-card card-slow p-3 h-100" data-target-segment="slow" title="Click to filter Slow Moving items">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="overflow-hidden">
                        <div class="text-uppercase fw-bold text-warning-emphasis fs-xxs d-flex align-items-center gap-1">
                            <i class="fas fa-hourglass-half"></i> <span>Slow Moving (Thanda Maal)</span>
                        </div>
                        <h4 class="fw-bolder mb-0 text-warning-emphasis mt-1 text-truncate" id="kpiSlowCapital">
                            Rs. 0.00
                        </h4>
                        <div class="text-muted fs-xs mt-1 text-truncate">
                            <span class="badge bg-warning text-dark rounded-pill px-2" id="kpiSlowCountBadge">0 items</span> low / stalled sales
                        </div>
                        <div class="fs-xxs text-black-50 mt-1 text-truncate">
                            Avoid bulk reorders
                        </div>
                    </div>
                    <div class="vel-icon-box bg-warning-subtle text-warning hide-on-mobile">
                        <i class="fas fa-tortoise"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Top Bestsellers Revenue -->
        <div class="col-6 col-lg-3">
            <div class="velocity-kpi-card card-fast p-3 h-100" data-target-segment="fast" title="Click to filter Bestsellers">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="overflow-hidden">
                        <div class="text-uppercase fw-bold text-success fs-xxs d-flex align-items-center gap-1">
                            <i class="fas fa-fire"></i> <span>Bestsellers (Tez Maal)</span>
                        </div>
                        <h4 class="fw-bolder mb-0 text-success mt-1 text-truncate" id="kpiFastRevenue">
                            Rs. 0.00
                        </h4>
                        <div class="text-muted fs-xs mt-1 text-truncate">
                            <span class="badge bg-success text-white rounded-pill px-2" id="kpiFastCountBadge">0 items</span> Fast moving
                        </div>
                        <div class="fs-xxs text-black-50 mt-1 text-truncate">
                            Store revenue drivers
                        </div>
                    </div>
                    <div class="vel-icon-box bg-success-subtle text-success hide-on-mobile">
                        <i class="fas fa-bolt"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Stock Reorder Needed & Estimated Budget -->
        <div class="col-6 col-lg-3">
            <div class="velocity-kpi-card card-reorder p-3 h-100" data-target-segment="reorder" title="Click to view Reorder alerts and purchase requirements">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="overflow-hidden">
                        <div class="text-uppercase fw-bold text-primary fs-xxs d-flex align-items-center gap-1">
                            <i class="fas fa-cart-shopping"></i> <span>Reorder &amp; Budget</span>
                        </div>
                        <h4 class="fw-bolder mb-0 text-primary mt-1 text-truncate" id="kpiReorderCount">
                            0 Items
                        </h4>
                        <div class="text-primary fs-xs mt-1 text-truncate fw-bold" id="kpiReorderBudget">
                            Est. Budget: Rs. 0.00
                        </div>
                        <div class="fs-xxs text-muted mt-1 text-truncate" id="kpiReorderUnitsSub">
                            Suggested: 0 units to restock
                        </div>
                    </div>
                    <div class="vel-icon-box bg-primary-subtle text-primary hide-on-mobile">
                        <i class="fas fa-dolly"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Horizon Selector (Horizontal Scrollable) - Positioned Under KPIs -->
    <div class="card border-0 shadow-sm rounded-4 mb-3 d-print-none">
        <div class="card-body p-2 p-md-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-1.5 overflow-auto no-scrollbar py-1" id="datePillContainer">
                    <span class="text-dark fs-xs fw-bolder text-uppercase me-1 flex-shrink-0 d-flex align-items-center gap-1">
                        <i class="fas fa-calendar-check text-primary"></i> Timeline:
                    </span>
                    <button type="button" class="date-pill" data-period="7_days">
                        <i class="fas fa-bolt text-warning"></i> 7 Days
                    </button>
                    <button type="button" class="date-pill active" data-period="30_days">
                        <i class="fas fa-calendar text-primary"></i> 1 Month (30d)
                    </button>
                    <button type="button" class="date-pill" data-period="60_days">
                        <i class="fas fa-calendar-week text-info"></i> 2 Months (60d)
                    </button>
                    <button type="button" class="date-pill" data-period="90_days">
                        <i class="fas fa-calendar-alt text-secondary"></i> 3 Months (90d)
                    </button>
                    <button type="button" class="date-pill" data-period="all_time">
                        <i class="fas fa-infinity text-purple"></i> All Time
                    </button>
                    <button type="button" class="date-pill" data-period="custom" id="btnCustomPeriod">
                        <i class="fas fa-sliders text-dark"></i> Custom Range
                    </button>
                </div>

                <!-- Custom Date Range Sub-Bar (Hidden unless Custom is selected) -->
                <div class="d-none d-flex align-items-center gap-2" id="customDateRangeBox">
                    <input type="date" class="form-control form-control-sm rounded-3" id="customStartDate" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                    <span class="text-muted fs-xs">to</span>
                    <input type="date" class="form-control form-control-sm rounded-3" id="customEndDate" value="<?= $today ?>">
                    <button class="btn btn-sm btn-dark rounded-3 px-3 fw-bold" id="btnApplyCustomDate">Apply</button>
                </div>

                <!-- Active Date Horizon Indicator -->
                <div class="text-md-end flex-shrink-0">
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fs-xs shadow-xs" id="activePeriodLabel">
                        <i class="fas fa-clock me-1 text-primary"></i> Loading...
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Segment Quick Navigation Tabs -->
    <div class="overflow-auto no-scrollbar mb-3 d-print-none">
        <ul class="nav velocity-tabs flex-nowrap gap-1.5 p-1 bg-white border rounded-pill shadow-sm" id="velocityTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link tab-all active" data-segment="all">
                    <i class="fas fa-boxes-stacked me-1"></i> All Products (<span id="tabCountAll">0</span>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link tab-dead" data-segment="dead">
                    <i class="fas fa-skull text-danger"></i> Dead Stock / 0 Sold (<span id="tabCountDead">0</span>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link tab-slow" data-segment="slow">
                    <i class="fas fa-hourglass-half text-warning"></i> Slow Moving (<span id="tabCountSlow">0</span>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link tab-fast" data-segment="fast">
                    <i class="fas fa-fire text-success"></i> Most Selling / Bestsellers (<span id="tabCountFast">0</span>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link tab-reorder" data-segment="reorder">
                    <i class="fas fa-truck-ramp-box text-primary"></i> Reorder Urgently (<span id="tabCountReorder">0</span>)
                </button>
            </li>
        </ul>
    </div>

    <!-- Search, Filter & Sorting Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-3 d-print-none">
        <div class="card-body p-2 p-md-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="searchProductInput" 
                               placeholder="Search product name, barcode, or category..." autocomplete="off">
                        <button class="btn btn-outline-secondary d-none border-start-0" type="button" id="btnClearSearch">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Category Filter (Preloaded via PHP and synced with API) -->
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm rounded-3 fw-semibold" id="filterCategory">
                        <option value="0">All Categories (<?= count($allCategories) ?>)</option>
                        <?php foreach ($allCategories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Stock Status Filter -->
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm rounded-3 fw-semibold" id="filterStockStatus">
                        <option value="all">All Stock Status</option>
                        <option value="in_stock" selected>In Stock Only (&gt; 0)</option>
                        <option value="low_stock">Low Stock Warnings</option>
                        <option value="out_of_stock">Out of Stock (= 0)</option>
                    </select>
                </div>

                <!-- Sort By Dropdown -->
                <div class="col-7 col-md-2">
                    <select class="form-select form-select-sm rounded-3 fw-semibold" id="sortBySelect">
                        <option value="units_sold" selected>Sort: Units Sold</option>
                        <option value="stock_cost_value">Sort: Tied Capital</option>
                        <option value="suggested_order_qty">Sort: Order Qty Needed</option>
                        <option value="stock_quantity">Sort: Stock in Hand</option>
                        <option value="total_revenue">Sort: Revenue</option>
                        <option value="total_profit">Sort: Profit</option>
                        <option value="last_sold_at">Sort: Last Sold Date</option>
                        <option value="name">Sort: Product Name</option>
                    </select>
                </div>

                <!-- Sort Direction Toggle -->
                <div class="col-5 col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-1" id="btnSortDirection" data-dir="desc">
                        <i class="fas fa-arrow-down-wide-short"></i>
                        <span id="sortDirLabel">Descending</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Filter Chips Bar -->
    <div class="d-flex align-items-center justify-content-between mb-2 px-1 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap" id="activeFilterChips">
            <small class="text-muted fs-xs fw-semibold" id="recordsSummaryText">Showing 0 products</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white text-dark border px-2.5 py-1 rounded-pill fs-xs shadow-xs" id="recordsBadge">0 items</span>
        </div>
    </div>

    <!-- VIEW 1: MOBILE / RESPONSIVE CARDS GRID VIEW (Active on Mobile) -->
    <div id="viewContainerCards" class="row g-2 g-md-3 mb-4 d-none">
        <!-- Dynamically Rendered via JS -->
    </div>

    <!-- VIEW 2: FULL DATA TABLE VIEW (DESKTOP OPTIMIZED - ZERO HORIZONTAL SCROLL) -->
    <div id="viewContainerTable" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="velocityTable">
                <thead>
                    <tr class="fs-xs text-uppercase border-bottom">
                        <th class="ps-3 text-center" style="width: 38px;">#</th>
                        <th class="sortable-th" data-sort="name" style="min-width: 200px;">
                            Product Details <i class="fas fa-sort sort-icon"></i>
                        </th>
                        <th class="sortable-th text-end" data-sort="stock_quantity" style="width: 140px;">
                            Stock &amp; Capital <i class="fas fa-sort sort-icon"></i>
                        </th>
                        <th class="sortable-th text-end active-sort" data-sort="units_sold" style="width: 155px;">
                            Sales &amp; Profit <i class="fas fa-sort-down sort-icon"></i>
                        </th>
                        <th class="sortable-th text-center" data-sort="daily_run_rate" style="width: 120px;">
                            Speed &amp; Cover <i class="fas fa-sort sort-icon"></i>
                        </th>
                        <th class="sortable-th text-center" data-sort="suggested_order_qty" style="width: 135px;">
                            Restock Order <i class="fas fa-sort sort-icon"></i>
                        </th>
                        <th class="text-center" style="width: 145px;">
                            Status &amp; Advice
                        </th>
                        <th class="text-center pe-3 d-print-none" style="width: 105px;">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody id="velocityTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="mt-2 text-muted fs-sm">Analyzing sales velocity and dead inventory...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- =======================================================================
     MODAL 1: SMART PURCHASE ORDER & RESTOCK PLANNER
     ======================================================================= -->
<div class="modal fade d-print-none" id="modalOrderPlanner" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4 bg-light d-flex justify-content-between align-items-center">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-pill px-2.5 py-0.5 fs-xxs text-uppercase fw-bold">
                            <i class="fas fa-cart-shopping me-1"></i> Restock Assistant
                        </span>
                        <span class="badge bg-light text-dark border rounded-pill px-2 py-0.5 fs-xxs fw-semibold" id="plannerCategoryBadge">
                            All Categories
                        </span>
                    </div>
                    <h5 class="modal-title fw-bold mb-0 text-dark mt-1">
                        <i class="fas fa-clipboard-list text-primary me-1.5"></i> Purchase Order &amp; Restock Planner
                    </h5>
                    <small class="text-muted">
                        Auto-calculated from sales velocity to cover next 30 days buffer. Adjust quantities and copy directly for WhatsApp or print.
                    </small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-3 p-md-4">
                <!-- Guidance Banner -->
                <div class="alert alert-info border-0 rounded-3 py-2.5 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-lightbulb text-primary fs-5"></i>
                        <span class="fs-xs text-dark">
                            <b>Smart Ordering Logic:</b> Suggested order units account for daily sales run-rate, current stock on shelf, and minimum threshold. Dead stock items are automatically flagged with 0 quantity.
                        </span>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-sm btn-outline-primary active" id="btnFilterPlannerUrgent">Urgent Only</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnFilterPlannerAll">Show All in List</button>
                    </div>
                </div>

                <!-- Products to Order Table -->
                <div class="table-responsive border rounded-3 overflow-hidden mb-3">
                    <table class="table table-hover align-middle mb-0" id="plannerTable">
                        <thead class="table-light fs-xs text-uppercase text-muted">
                            <tr>
                                <th class="ps-3 py-2.5">Product Details</th>
                                <th>Category</th>
                                <th class="text-center">In Stock</th>
                                <th class="text-center">Run Rate</th>
                                <th class="text-end">Cost Price</th>
                                <th class="text-center" style="width: 140px;">Order Qty (pcs)</th>
                                <th class="text-end">Line Total</th>
                                <th class="text-center" style="width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="plannerTableBody">
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No items currently selected for reordering.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Order Grand Totals Summary Card -->
                <div class="card border-0 bg-primary-subtle rounded-3 p-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <div class="text-uppercase fs-xxs fw-bold text-primary">Purchase Summary</div>
                            <div class="d-flex align-items-center gap-3 mt-1">
                                <div>
                                    <small class="text-muted fs-xs">Products:</small>
                                    <span class="fw-bold text-dark fs-6 ms-1" id="summaryPlannerItems">0</span>
                                </div>
                                <div>
                                    <small class="text-muted fs-xs">Total Units:</small>
                                    <span class="fw-bold text-dark fs-6 ms-1" id="summaryPlannerUnits">0 pcs</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-md-end">
                            <div class="text-uppercase fs-xxs fw-bold text-muted">Estimated Restock Budget</div>
                            <h3 class="fw-bolder text-primary mb-0" id="summaryPlannerCost">Rs. 0.00</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top py-2.5 px-4 bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Copy WhatsApp Order -->
                    <button type="button" class="btn btn-success rounded-pill px-3.5 shadow-sm fw-bold" id="btnCopyWhatsAppOrder" title="Copy formatted order list ready for WhatsApp">
                        <i class="fab fa-whatsapp me-1.5 fs-6"></i> Copy WhatsApp Order
                    </button>
                    <!-- Print PO Slip -->
                    <button type="button" class="btn btn-dark rounded-pill px-3 shadow-sm fw-bold" id="btnPrintPurchaseOrder" title="Print Purchase Order Sheet">
                        <i class="fas fa-print me-1.5"></i> Print PO Slip
                    </button>
                    <!-- Export PO CSV -->
                    <button type="button" class="btn btn-outline-success rounded-pill px-3 shadow-sm fw-bold" id="btnExportPlannerCsv" title="Download Excel Order Sheet">
                        <i class="fas fa-file-csv me-1.5"></i> Export PO
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =======================================================================
     MODAL 2: STOCK MOVEMENT LEDGER
     ======================================================================= -->
<div class="modal fade d-print-none" id="modalStockHistory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4 bg-light">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-dark"><i class="fas fa-timeline text-primary me-2"></i>Product Stock Ledger</h5>
                    <small class="text-muted" id="modalStockProductName">Product Name</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light fs-xs text-uppercase text-muted sticky-top">
                            <tr>
                                <th class="ps-3 py-2.5">Date &amp; Time</th>
                                <th>Movement</th>
                                <th class="text-center">Quantity Shift</th>
                                <th>Reason / Ref</th>
                                <th>Staff</th>
                            </tr>
                        </thead>
                        <tbody id="modalStockHistoryBody">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Loading movement history...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let currentPeriod = '30_days';
    let currentSegment = 'all';
    let currentSortBy = 'units_sold';
    let currentSortDir = 'desc';
    let currentViewMode = window.innerWidth < 992 ? 'cards' : 'table';
    let cachedCategories = [];
    let activeProductsData = [];
    let plannerItemsMap = {}; // id -> { product, order_qty }

    // -------------------------------------------------------------------------
    // 1. INITIALIZATION & VIEW MODE
    // -------------------------------------------------------------------------
    init();

    function init() {
        updateViewModeUI();
        loadCategories();
        loadVelocityData();
    }

    function updateViewModeUI() {
        if (currentViewMode === 'cards') {
            $('#btnViewCards').addClass('active btn-dark text-white').removeClass('text-muted');
            $('#btnViewTable').removeClass('active btn-dark text-white').addClass('text-muted');
            $('#viewContainerCards').removeClass('d-none');
            $('#viewContainerTable').addClass('d-none');
        } else {
            $('#btnViewTable').addClass('active btn-dark text-white').removeClass('text-muted');
            $('#btnViewCards').removeClass('active btn-dark text-white').addClass('text-muted');
            $('#viewContainerTable').removeClass('d-none');
            $('#viewContainerCards').addClass('d-none');
        }
    }

    $('#btnViewCards').on('click', function() {
        currentViewMode = 'cards';
        updateViewModeUI();
    });

    $('#btnViewTable').on('click', function() {
        currentViewMode = 'table';
        updateViewModeUI();
    });

    // -------------------------------------------------------------------------
    // 2. LOAD CATEGORIES (Sync with API & Keep PHP Preloads)
    // -------------------------------------------------------------------------
    function loadCategories() {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=get_categories&status=active',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.categories) {
                    cachedCategories = res.categories;
                    const $sel = $('#filterCategory');
                    // If select has only default option, populate it
                    if ($sel.children('option').length <= 1) {
                        $sel.empty().append('<option value="0">All Categories (' + cachedCategories.length + ')</option>');
                        cachedCategories.forEach(c => {
                            $sel.append(`<option value="${c.id}">${escapeHtml(c.name)}</option>`);
                        });
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // 3. FETCH VELOCITY DATA
    // -------------------------------------------------------------------------
    function loadVelocityData() {
        const loadingHtml = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-danger" role="status"></div>
                <div class="mt-2 text-muted fs-sm fw-semibold">Analyzing product velocity and tied capital...</div>
            </div>
        `;
        $('#viewContainerCards').html(loadingHtml);
        $('#velocityTableBody').html(`<tr><td colspan="8" class="text-center py-5">${loadingHtml}</td></tr>`);

        let params = {
            action: 'product_velocity',
            period: currentPeriod,
            segment: currentSegment,
            category_id: $('#filterCategory').val() || 0,
            stock_filter: $('#filterStockStatus').val() || 'all',
            search: $('#searchProductInput').val() || '',
            sort_by: currentSortBy,
            sort_dir: currentSortDir
        };

        if (currentPeriod === 'custom') {
            params.start_date = $('#customStartDate').val();
            params.end_date = $('#customEndDate').val();
        }

        $.ajax({
            url: (window.BASE_URL || '') + '/api/reports.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    activeProductsData = res.products || [];
                    
                    // Sync Order Planner Map with newly fetched items
                    syncPlannerMap(activeProductsData);

                    renderKPIs(res.summary, res.period, res.start_date, res.end_date);
                    renderActiveFilterChips(params);
                    renderAllViews(activeProductsData);
                } else {
                    const err = `<div class="col-12 text-center text-danger py-4">${escapeHtml(res.message || 'Error loading data.')}</div>`;
                    $('#viewContainerCards').html(err);
                    $('#velocityTableBody').html(`<tr><td colspan="8">${err}</td></tr>`);
                }
            },
            error: function() {
                const err = `<div class="col-12 text-center text-danger py-4">Unable to connect to server.</div>`;
                $('#viewContainerCards').html(err);
                $('#velocityTableBody').html(`<tr><td colspan="8">${err}</td></tr>`);
            }
        });
    }

    // -------------------------------------------------------------------------
    // 4. RENDER EXECUTIVE KPIS & HEADER STATS
    // -------------------------------------------------------------------------
    function renderKPIs(summary, period, sDate, eDate) {
        if (!summary) return;

        // Dynamic Store Asset Valuation Banner
        if (summary.overall_stock_cost_value !== undefined) {
            $('#valTotalCost').text('Rs. ' + formatNumber(summary.overall_stock_cost_value));
            $('#valTotalRetail').text('Rs. ' + formatNumber(summary.overall_stock_retail_value));
            
            const profit = (summary.profit_potential !== undefined) 
                ? summary.profit_potential 
                : (summary.overall_stock_retail_value - summary.overall_stock_cost_value);
            $('#valProfitPotential').text('Rs. ' + formatNumber(profit));
            
            const marginPct = (summary.markup_margin_pct !== undefined) 
                ? summary.markup_margin_pct 
                : (summary.overall_stock_cost_value > 0 ? ((profit / summary.overall_stock_cost_value) * 100).toFixed(1) : 0);
            $('#valMarginBadge').text('+' + marginPct + '% Expected Markup Margin');
            
            const units = (summary.total_stock_units !== undefined) ? summary.total_stock_units : 0;
            $('#valTotalUnits').text(formatNumber(units).split('.')[0] + ' Pieces');
            $('#valStockRatioText').text(formatNumber(units).split('.')[0] + ' Units');
            $('#valStockBreakdown').text(`${summary.total_in_stock || 0} in-stock | ${summary.total_out_of_stock || 0} out of stock`);
            $('#valCatalogSummary b').text(summary.total_products_tracked || 0);
        }

        $('#kpiDeadCapital').text('Rs. ' + formatNumber(summary.dead_capital_tied));
        $('#kpiDeadCountBadge').text(summary.dead_items_count + ' items');
        $('#kpiDeadRetailSub').text('Retail Potential: Rs. ' + formatNumber(summary.dead_retail_tied));

        $('#kpiSlowCapital').text('Rs. ' + formatNumber(summary.slow_capital_tied));
        $('#kpiSlowCountBadge').text(summary.slow_items_count + ' items');

        $('#kpiFastRevenue').text('Rs. ' + formatNumber(summary.fast_revenue_total));
        $('#kpiFastCountBadge').text(summary.fast_items_count + ' items');

        $('#kpiReorderCount').text(summary.reorder_needed_count + ' Items');
        $('#kpiReorderBudget').text('Est. Budget: Rs. ' + formatNumber(summary.estimated_reorder_budget || 0));
        $('#kpiReorderUnitsSub').text('Suggested: ' + (summary.suggested_reorder_units || 0) + ' units to restock');

        // Header Order Planner button badge
        $('#badgeOrderCount').text(summary.suggested_reorder_items_count || summary.reorder_needed_count || 0);

        // Tab counts
        $('#tabCountAll').text(summary.total_products_tracked || 0);
        $('#tabCountDead').text(summary.dead_items_count || 0);
        $('#tabCountSlow').text(summary.slow_items_count || 0);
        $('#tabCountFast').text(summary.fast_items_count || 0);
        $('#tabCountReorder').text(summary.reorder_needed_count || 0);

        // Active Period Label
        let pLabel = '';
        if (period === '7_days') pLabel = `Past 7 Days (${sDate} to ${eDate})`;
        else if (period === '30_days') pLabel = `Past 30 Days (${sDate} to ${eDate})`;
        else if (period === '60_days') pLabel = `Past 60 Days (${sDate} to ${eDate})`;
        else if (period === '90_days') pLabel = `Past 90 Days (${sDate} to ${eDate})`;
        else if (period === 'custom') pLabel = `Custom Range (${sDate} to ${eDate})`;
        else if (period === 'all_time') pLabel = `All Time Historical Velocity`;

        $('#activePeriodLabel').html(`<i class="fas fa-clock me-1 text-primary"></i> ${pLabel}`);
    }

    // -------------------------------------------------------------------------
    // 5. RENDER ACTIVE FILTER CHIPS
    // -------------------------------------------------------------------------
    function renderActiveFilterChips(params) {
        const $chips = $('#activeFilterChips');
        $chips.empty();

        let countText = `Showing <b>${activeProductsData.length}</b> analyzed products`;
        $chips.append(`<small class="text-muted fs-xs me-2">${countText}</small>`);

        // Category Chip
        const catVal = parseInt($('#filterCategory').val() || 0);
        if (catVal > 0) {
            const catName = $('#filterCategory option:selected').text().split('(')[0].trim();
            $chips.append(`
                <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 fs-xxs d-inline-flex align-items-center gap-1">
                    <span>Category: ${escapeHtml(catName)}</span>
                    <i class="fas fa-times cursor-pointer" id="chipRemoveCategory" title="Clear category filter" style="cursor:pointer;"></i>
                </span>
            `);
        }

        // Segment Chip
        if (currentSegment !== 'all') {
            const segLabels = {
                'dead': 'Dead Stock (0 Sold)',
                'slow': 'Slow Moving',
                'fast': 'Bestsellers',
                'reorder': 'Reorder Urgently'
            };
            $chips.append(`
                <span class="badge bg-dark text-white rounded-pill px-2.5 py-1 fs-xxs d-inline-flex align-items-center gap-1">
                    <span>Segment: ${segLabels[currentSegment] || currentSegment}</span>
                    <i class="fas fa-times cursor-pointer" id="chipRemoveSegment" title="Show all items" style="cursor:pointer;"></i>
                </span>
            `);
        }

        // Search Chip
        const searchVal = $('#searchProductInput').val().trim();
        if (searchVal) {
            $chips.append(`
                <span class="badge bg-secondary text-white rounded-pill px-2.5 py-1 fs-xxs d-inline-flex align-items-center gap-1">
                    <span>Search: "${escapeHtml(searchVal)}"</span>
                    <i class="fas fa-times cursor-pointer" id="chipRemoveSearch" title="Clear search" style="cursor:pointer;"></i>
                </span>
            `);
        }
    }

    $(document).on('click', '#chipRemoveCategory', function() {
        $('#filterCategory').val('0').trigger('change');
    });

    $(document).on('click', '#chipRemoveSegment', function() {
        $('#velocityTabs .nav-link[data-segment="all"]').click();
    });

    $(document).on('click', '#chipRemoveSearch', function() {
        $('#btnClearSearch').click();
    });

    // -------------------------------------------------------------------------
    // 6. RENDER BOTH VIEWS (CARDS & TABLE)
    // -------------------------------------------------------------------------
    function renderAllViews(products) {
        $('#recordsBadge').text(`${products.length} products`);

        if (!products || products.length === 0) {
            const emptyHtml = `
                <div class="col-12 text-center py-5">
                    <div class="text-muted fs-4 mb-1"><i class="fas fa-inbox me-2"></i>No matching products found</div>
                    <div class="text-black-50 fs-xs">Try choosing another category, segment tab, or time horizon.</div>
                </div>
            `;
            $('#viewContainerCards').html(emptyHtml);
            $('#velocityTableBody').html(`<tr><td colspan="8">${emptyHtml}</td></tr>`);
            return;
        }

        renderCardsView(products);
        renderTableView(products);
    }

    // 6A. RENDER MOBILE-FIRST CARDS VIEW
    function renderCardsView(products) {
        const $grid = $('#viewContainerCards');
        $grid.empty();

        products.forEach(p => {
            // Velocity status badge
            let velBadge = '';
            if (p.is_dead) {
                velBadge = `<span class="badge badge-vel-dead rounded-pill px-2.5 py-1 fs-xxs"><i class="fas fa-skull me-1"></i>DEAD STOCK</span>`;
            } else if (p.is_slow) {
                velBadge = `<span class="badge badge-vel-slow rounded-pill px-2.5 py-1 fs-xxs"><i class="fas fa-hourglass-half me-1"></i>SLOW MOVING</span>`;
            } else if (p.is_fast) {
                velBadge = `<span class="badge badge-vel-fast rounded-pill px-2.5 py-1 fs-xxs"><i class="fas fa-fire me-1"></i>BESTSELLER</span>`;
            } else if (p.velocity_status === 'out_of_stock') {
                velBadge = `<span class="badge badge-vel-out rounded-pill px-2.5 py-1 fs-xxs"><i class="fas fa-box-open me-1"></i>OUT OF STOCK</span>`;
            }

            // Stock in hand chip
            let stockChip = '';
            if (p.stock_quantity > p.low_stock_threshold) {
                stockChip = `<span class="badge bg-success-subtle text-success border rounded-pill px-2 py-1 fs-xxs fw-bold"><i class="fas fa-cubes me-1"></i>${p.stock_quantity} in stock</span>`;
            } else if (p.stock_quantity > 0) {
                stockChip = `<span class="badge bg-warning-subtle text-warning-emphasis border rounded-pill px-2 py-1 fs-xxs fw-bold"><i class="fas fa-triangle-exclamation me-1"></i>Low: ${p.stock_quantity}</span>`;
            } else {
                stockChip = `<span class="badge bg-secondary text-white rounded-pill px-2 py-1 fs-xxs"><i class="fas fa-ban me-1"></i>0 in Stock</span>`;
            }

            // Smart Purchasing Decision Advice Box
            let decisionAdviceHtml = '';
            if (p.is_dead) {
                decisionAdviceHtml = `
                    <div class="p-2 mb-2 rounded-3 bg-danger-subtle border border-danger-subtle">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-xxs fw-bold text-danger text-uppercase"><i class="fas fa-ban me-1"></i>Purchasing Advice</span>
                            <span class="badge bg-danger text-white rounded-pill fs-xxs">0 Pcs (Do Not Reorder)</span>
                        </div>
                        <div class="fs-xs text-danger fw-semibold mt-1">
                            ${escapeHtml(p.order_advice)}
                        </div>
                    </div>
                `;
            } else if (p.suggested_order_qty > 0) {
                const isUrgent = p.order_urgency === 'urgent';
                decisionAdviceHtml = `
                    <div class="p-2 mb-2 rounded-3 ${isUrgent ? 'bg-primary-subtle border border-primary-subtle' : 'bg-light border'}">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-xxs fw-bold ${isUrgent ? 'text-primary' : 'text-dark'} text-uppercase">
                                <i class="fas fa-cart-shopping me-1"></i>Suggested Reorder
                            </span>
                            <span class="badge ${isUrgent ? 'bg-primary' : 'bg-secondary'} text-white rounded-pill px-2 py-0.5 fs-xs fw-bold">
                                +${p.suggested_order_qty} pcs (Est: Rs. ${formatNumber(p.estimated_order_cost)})
                            </span>
                        </div>
                        <div class="fs-xs ${isUrgent ? 'text-primary' : 'text-dark'} fw-semibold mt-1">
                            <i class="fas fa-circle-info me-1"></i>${escapeHtml(p.order_advice)}
                        </div>
                    </div>
                `;
            } else {
                decisionAdviceHtml = `
                    <div class="p-2 mb-2 rounded-3 bg-light border">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-xxs fw-bold text-muted text-uppercase">
                                <i class="fas fa-shield-halved me-1 text-success"></i>Stock Health
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fs-xxs">
                                ${p.days_of_stock < 999 ? '~' + p.days_of_stock + ' days cover' : 'Healthy'}
                            </span>
                        </div>
                        <div class="fs-xs text-muted mt-1">
                            ${escapeHtml(p.order_advice)}
                        </div>
                    </div>
                `;
            }

            const card = `
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="product-velocity-card p-3 h-100 d-flex flex-column justify-content-between ${p.is_dead ? 'is-dead-card' : ''}">
                        <div>
                            <!-- Header: Category & Barcode -->
                            <div class="d-flex justify-content-between align-items-center mb-1.5 gap-2">
                                <span class="badge bg-light text-dark border rounded-pill px-2 py-0.5 fs-xxs fw-semibold">
                                    ${escapeHtml(p.category_name)}
                                </span>
                                <span class="font-monospace fs-xxs text-muted">
                                    <i class="fas fa-barcode me-1"></i>${escapeHtml(p.barcode)}
                                </span>
                            </div>

                            <!-- Title -->
                            <h5 class="fw-bold text-dark mb-1 fs-6">${escapeHtml(p.name)}</h5>

                            <!-- Badges row -->
                            <div class="d-flex align-items-center gap-1.5 flex-wrap mb-2">
                                ${velBadge}
                                ${stockChip}
                            </div>

                            <!-- Decision Advice Box -->
                            ${decisionAdviceHtml}

                            <!-- Metrics 4-Box Grid -->
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <div class="metric-micro-box ${p.is_dead ? 'highlight-dead' : (p.is_slow ? 'highlight-slow' : '')}">
                                        <div class="fs-xxs text-muted fw-semibold text-uppercase">Tied Capital</div>
                                        <div class="fw-bold ${p.is_dead ? 'text-danger' : (p.is_slow ? 'text-warning-emphasis' : 'text-dark')} fs-6 mt-0.5">
                                            Rs. ${formatNumber(p.stock_cost_value)}
                                        </div>
                                        <div class="fs-xxs text-black-50">Cost: Rs. ${formatNumber(p.cost_price)}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-micro-box ${p.is_fast ? 'highlight-fast' : ''}">
                                        <div class="fs-xxs text-muted fw-semibold text-uppercase">Units Sold</div>
                                        <div class="fw-bold ${p.units_sold > 0 ? 'text-success' : 'text-muted'} fs-6 mt-0.5">
                                            ${p.units_sold} units
                                        </div>
                                        <div class="fs-xxs text-black-50">Rate: Rs. ${formatNumber(p.selling_price)}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-micro-box">
                                        <div class="fs-xxs text-muted fw-semibold text-uppercase">Revenue</div>
                                        <div class="fw-bold text-dark fs-sm mt-0.5">Rs. ${formatNumber(p.total_revenue)}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-micro-box">
                                        <div class="fs-xxs text-muted fw-semibold text-uppercase">Gross Profit</div>
                                        <div class="fw-bold ${p.total_profit > 0 ? 'text-success' : (p.total_profit < 0 ? 'text-danger' : 'text-muted')} fs-sm mt-0.5">
                                            Rs. ${formatNumber(p.total_profit)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer: Last Sold & Actions -->
                        <div class="pt-2 border-top d-flex justify-content-between align-items-center gap-2 mt-1">
                            <div class="fs-xs text-muted">
                                <i class="fas fa-clock-rotate-left me-1"></i> <b class="text-dark">${escapeHtml(p.last_sold_text)}</b>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-2.5 py-1 fs-xs btn-view-stock-history" 
                                        data-id="${p.id}" data-name="${escapeHtml(p.name)}" data-barcode="${escapeHtml(p.barcode)}" 
                                        title="View Stock Movement Ledger">
                                    <i class="fas fa-history me-1"></i> Ledger
                                </button>
                                <button type="button" class="btn btn-sm ${p.suggested_order_qty > 0 ? 'btn-primary' : 'btn-outline-primary'} rounded-pill px-2.5 py-1 fs-xs btn-add-single-order" 
                                        data-id="${p.id}" title="Open this product in Order Planner">
                                    <i class="fas fa-cart-plus me-1"></i> Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $grid.append(card);
        });
    }

    // 6B. RENDER TABLE VIEW (COMPACT HIGH-DENSITY ENTERPRISE LAYOUT)
    function renderTableView(products) {
        const $tbody = $('#velocityTableBody');
        $tbody.empty();

        products.forEach((p, idx) => {
            let velBadge = '';
            if (p.is_dead) {
                velBadge = `<span class="badge badge-vel-dead rounded-pill px-2 py-0.5 fs-xxs"><i class="fas fa-skull me-1"></i>DEAD</span>`;
            } else if (p.is_slow) {
                velBadge = `<span class="badge badge-vel-slow rounded-pill px-2 py-0.5 fs-xxs"><i class="fas fa-hourglass-half me-1"></i>SLOW</span>`;
            } else if (p.is_fast) {
                velBadge = `<span class="badge badge-vel-fast rounded-pill px-2 py-0.5 fs-xxs"><i class="fas fa-fire me-1"></i>FAST</span>`;
            } else if (p.velocity_status === 'out_of_stock') {
                velBadge = `<span class="badge badge-vel-out rounded-pill px-2 py-0.5 fs-xxs">OUT</span>`;
            }

            let stockBadge = `<span class="fw-bold fs-sm ${p.stock_quantity <= p.low_stock_threshold ? 'text-danger' : 'text-dark'}">${p.stock_quantity}</span> <span class="text-muted fs-xxs">pcs</span>`;
            if (p.stock_quantity <= p.low_stock_threshold && p.stock_quantity > 0) {
                stockBadge += ` <span class="badge bg-warning-subtle text-warning-emphasis border rounded-pill px-1 fs-xxs">Low</span>`;
            } else if (p.stock_quantity === 0) {
                stockBadge = `<span class="badge bg-secondary text-white rounded-pill px-1.5 fs-xxs">0 Out</span>`;
            }

            let tiedClass = p.is_dead ? 'text-danger fw-bolder' : (p.is_slow ? 'text-warning-emphasis fw-bold' : 'text-dark');
            let profitClass = p.total_profit > 0 ? 'text-success fw-bold' : (p.total_profit < 0 ? 'text-danger fw-bold' : 'text-muted');

            // Suggested Order Box in Table
            let orderCellHtml = '';
            if (p.is_dead) {
                orderCellHtml = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5 fs-xxs" title="Do not reorder dead inventory"><i class="fas fa-ban me-1"></i>Do Not Order</span>`;
            } else if (p.suggested_order_qty > 0) {
                orderCellHtml = `
                    <div class="fw-bold text-primary fs-xs">+${p.suggested_order_qty} pcs</div>
                    <div class="fs-xxs text-muted">Est: Rs. ${formatNumber(p.estimated_order_cost)}</div>
                `;
            } else {
                orderCellHtml = `<span class="badge bg-light text-muted border rounded-pill px-2 py-0.5 fs-xxs">0 Needed</span>`;
            }

            // Decision Sub-text
            let decisionSubText = '';
            if (p.is_dead) {
                decisionSubText = `<span class="text-danger fs-xxs d-block text-truncate" style="max-width: 140px;" title="${escapeHtml(p.order_advice)}">Trapped Capital</span>`;
            } else if (p.is_slow) {
                decisionSubText = `<span class="text-warning-emphasis fs-xxs d-block text-truncate" style="max-width: 140px;" title="${escapeHtml(p.slow_reason || p.order_advice)}">${escapeHtml(p.slow_reason || 'Slow Mover')}</span>`;
            } else if (p.is_fast) {
                decisionSubText = `<span class="text-success fs-xxs d-block text-truncate" style="max-width: 140px;" title="${escapeHtml(p.order_advice)}">Top Revenue Driver</span>`;
            } else {
                decisionSubText = `<span class="text-muted fs-xxs d-block text-truncate" style="max-width: 140px;">Stock Balanced</span>`;
            }

            const row = `
                <tr class="${p.is_dead ? 'is-dead-row' : ''}">
                    <!-- 1. Index -->
                    <td class="ps-3 text-center text-muted fs-xxs">${idx + 1}</td>
                    
                    <!-- 2. Product Details & Barcode -->
                    <td>
                        <div class="fw-bold text-dark fs-sm text-truncate" style="max-width: 250px;" title="${escapeHtml(p.name)}">${escapeHtml(p.name)}</div>
                        <div class="fs-xxs text-muted d-flex align-items-center gap-1.5 mt-0.5">
                            <span class="font-monospace text-black-50">${escapeHtml(p.barcode)}</span>
                            <span class="badge bg-light text-dark border px-1.5 py-0.2 rounded-pill">${escapeHtml(p.category_name)}</span>
                        </div>
                    </td>

                    <!-- 3. Current Stock & Cost Capital -->
                    <td class="text-end">
                        <div>${stockBadge}</div>
                        <div class="fs-xxs ${tiedClass} mt-0.5">Cost: Rs. ${formatNumber(p.stock_cost_value)}</div>
                    </td>

                    <!-- 4. Units Sold & Revenue / Profit -->
                    <td class="text-end">
                        <div>
                            <span class="badge ${p.units_sold > 0 ? 'bg-primary' : 'bg-light text-muted border'} rounded-pill px-2 py-0.5 fs-xxs fw-bold">
                                ${p.units_sold} sold
                            </span>
                        </div>
                        <div class="fs-xxs text-muted mt-0.5">
                            Rev: <b class="text-dark">Rs. ${formatNumber(p.total_revenue)}</b>
                            <span class="${profitClass} ms-0.5">(${p.total_profit >= 0 ? '+' : ''}${formatNumber(p.total_profit)})</span>
                        </div>
                    </td>

                    <!-- 5. Velocity Speed & Stock Cover Days -->
                    <td class="text-center fs-xs">
                        <div class="fw-bold text-dark fs-xs">${p.daily_run_rate} / day</div>
                        <div class="fs-xxs text-muted mt-0.5">${p.days_of_stock < 999 ? '~' + p.days_of_stock + 'd cover' : '<span class="text-black-50">No sales</span>'}</div>
                    </td>

                    <!-- 6. Restock Order Advice & Budget -->
                    <td class="text-center">
                        ${orderCellHtml}
                    </td>

                    <!-- 7. Status & Decision Advice -->
                    <td class="text-center fs-xs">
                        <div>${velBadge}</div>
                        <div class="mt-0.5">${decisionSubText}</div>
                    </td>

                    <!-- 8. Actions (Dual Compact Buttons) -->
                    <td class="text-center pe-3 d-print-none">
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm ${p.suggested_order_qty > 0 ? 'btn-primary' : 'btn-outline-primary'} rounded-pill px-2.5 py-1 fs-xs fw-semibold btn-add-single-order" 
                                    data-id="${p.id}" title="Open this product in Order Planner">
                                <i class="fas fa-cart-plus me-1"></i> Order
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-dark rounded-circle btn-view-stock-history" 
                                    data-id="${p.id}" data-name="${escapeHtml(p.name)}" data-barcode="${escapeHtml(p.barcode)}" 
                                    style="width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center;"
                                    title="View Stock Movement Ledger">
                                <i class="fas fa-history fs-xxs"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            $tbody.append(row);
        });
    }

    // -------------------------------------------------------------------------
    // 7. ORDER PLANNER STATE & MODAL MANAGEMENT
    // -------------------------------------------------------------------------
    function syncPlannerMap(products) {
        // Initialize or update planner map with products needing restock
        products.forEach(p => {
            if (!plannerItemsMap[p.id]) {
                plannerItemsMap[p.id] = {
                    product: p,
                    order_qty: p.suggested_order_qty || 0
                };
            } else {
                // Update product metadata while preserving user edits if made
                plannerItemsMap[p.id].product = p;
                if (plannerItemsMap[p.id].order_qty === undefined) {
                    plannerItemsMap[p.id].order_qty = p.suggested_order_qty || 0;
                }
            }
        });
    }

    function renderOrderPlannerModal(filterMode = 'urgent') {
        const $tbody = $('#plannerTableBody');
        $tbody.empty();

        const catText = $('#filterCategory option:selected').text();
        $('#plannerCategoryBadge').text(catText);

        let itemsToDisplay = Object.values(plannerItemsMap);

        if (filterMode === 'urgent') {
            itemsToDisplay = itemsToDisplay.filter(item => item.order_qty > 0 || item.product.is_reorder || item.product.order_urgency === 'urgent');
        }

        if (itemsToDisplay.length === 0) {
            $tbody.html('<tr><td colspan="8" class="text-center py-4 text-muted">No items found matching the order criteria.</td></tr>');
            updatePlannerGrandTotals();
            return;
        }

        itemsToDisplay.forEach(item => {
            const p = item.product;
            const lineTotal = (item.order_qty || 0) * p.cost_price;

            const row = `
                <tr data-id="${p.id}">
                    <td class="ps-3">
                        <div class="fw-bold text-dark fs-sm">${escapeHtml(p.name)}</div>
                        <div class="font-monospace fs-xxs text-muted">${escapeHtml(p.barcode)}</div>
                    </td>
                    <td><span class="badge bg-light text-dark border">${escapeHtml(p.category_name)}</span></td>
                    <td class="text-center fw-bold ${p.stock_quantity <= p.low_stock_threshold ? 'text-danger' : 'text-dark'}">
                        ${p.stock_quantity}
                        ${p.stock_quantity <= p.low_stock_threshold ? '<div class="fs-xxs text-danger">Low</div>' : ''}
                    </td>
                    <td class="text-center fs-xs">${p.daily_run_rate} / d</td>
                    <td class="text-end font-monospace fs-sm">Rs. ${formatNumber(p.cost_price)}</td>
                    <td class="text-center">
                        <input type="number" class="form-control form-control-sm order-qty-input mx-auto" 
                               data-id="${p.id}" value="${item.order_qty}" min="0" step="1">
                    </td>
                    <td class="text-end fw-bold text-primary font-monospace fs-sm order-line-total">
                        Rs. ${formatNumber(lineTotal)}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle btn-remove-planner-item" 
                                data-id="${p.id}" title="Remove from Order">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;
            $tbody.append(row);
        });

        updatePlannerGrandTotals();
    }

    function updatePlannerGrandTotals() {
        let totalItems = 0;
        let totalUnits = 0;
        let grandCost = 0.0;

        $('#plannerTableBody tr').each(function() {
            const pid = $(this).data('id');
            if (pid && plannerItemsMap[pid]) {
                const qty = parseInt(plannerItemsMap[pid].order_qty || 0);
                if (qty > 0) {
                    totalItems++;
                    totalUnits += qty;
                    grandCost += qty * parseFloat(plannerItemsMap[pid].product.cost_price || 0);
                }
            }
        });

        $('#summaryPlannerItems').text(totalItems);
        $('#summaryPlannerUnits').text(totalUnits + ' pcs');
        $('#summaryPlannerCost').text('Rs. ' + formatNumber(grandCost));
    }

    // Modal Qty Input Change Listener
    $(document).on('input change', '.order-qty-input', function() {
        const pid = $(this).data('id');
        const newQty = Math.max(0, parseInt($(this).val() || 0));
        $(this).val(newQty);

        if (plannerItemsMap[pid]) {
            plannerItemsMap[pid].order_qty = newQty;
            const lineTotal = newQty * parseFloat(plannerItemsMap[pid].product.cost_price || 0);
            $(this).closest('tr').find('.order-line-total').text('Rs. ' + formatNumber(lineTotal));
            updatePlannerGrandTotals();
        }
    });

    // Remove item from planner
    $(document).on('click', '.btn-remove-planner-item', function() {
        const pid = $(this).data('id');
        if (plannerItemsMap[pid]) {
            plannerItemsMap[pid].order_qty = 0;
            $(this).closest('tr').fadeOut(200, function() {
                $(this).remove();
                updatePlannerGrandTotals();
            });
        }
    });

    // Toggle planner filters inside modal
    $('#btnFilterPlannerUrgent').on('click', function() {
        $('#btnFilterPlannerUrgent').addClass('active');
        $('#btnFilterPlannerAll').removeClass('active');
        renderOrderPlannerModal('urgent');
    });

    $('#btnFilterPlannerAll').on('click', function() {
        $('#btnFilterPlannerAll').addClass('active');
        $('#btnFilterPlannerUrgent').removeClass('active');
        renderOrderPlannerModal('all');
    });

    // Open Order Planner Modal
    $('#btnOpenOrderPlanner').on('click', function() {
        renderOrderPlannerModal('urgent');
        const modalEl = document.getElementById('modalOrderPlanner');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    });

    // Open single item in planner modal
    $(document).on('click', '.btn-add-single-order', function() {
        const pid = $(this).data('id');
        if (plannerItemsMap[pid] && plannerItemsMap[pid].order_qty <= 0) {
            plannerItemsMap[pid].order_qty = plannerItemsMap[pid].product.suggested_order_qty || 10;
        }
        $('#btnOpenOrderPlanner').click();
    });

    // -------------------------------------------------------------------------
    // 8. WHATSAPP ORDER GENERATOR (1-CLICK COPY)
    // -------------------------------------------------------------------------
    $('#btnCopyWhatsAppOrder').on('click', function() {
        const catName = $('#filterCategory option:selected').text().split('(')[0].trim();
        const dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

        let lines = [];
        lines.push(`*PURCHASE ORDER - ${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}*`);
        lines.push(`📅 Date: ${dateStr}`);
        lines.push(`📂 Category: ${catName}`);
        lines.push(`----------------------------------------`);

        let count = 0;
        let totalUnits = 0;
        let totalAmount = 0.0;

        $('#plannerTableBody tr').each(function() {
            const pid = $(this).data('id');
            if (pid && plannerItemsMap[pid]) {
                const item = plannerItemsMap[pid];
                const qty = parseInt(item.order_qty || 0);
                if (qty > 0) {
                    count++;
                    totalUnits += qty;
                    const pCost = parseFloat(item.product.cost_price || 0);
                    const lineCost = qty * pCost;
                    totalAmount += lineCost;

                    lines.push(`${count}. *${item.product.name}*`);
                    lines.push(`   Barcode: ${item.product.barcode}`);
                    lines.push(`   Stock: ${item.product.stock_quantity} | *Order Qty: ${qty} pcs*`);
                    lines.push(`   Cost: Rs. ${formatNumber(pCost)} | Line Total: Rs. ${formatNumber(lineCost)}`);
                    lines.push(``);
                }
            }
        });

        if (count === 0) {
            alert('No items with order quantity > 0 to send.');
            return;
        }

        lines.push(`----------------------------------------`);
        lines.push(`*TOTAL ITEMS:* ${count}`);
        lines.push(`*TOTAL QUANTITY:* ${totalUnits} pcs`);
        lines.push(`*ESTIMATED AMOUNT:* Rs. ${formatNumber(totalAmount)}`);
        lines.push(`----------------------------------------`);
        lines.push(`Please confirm receipt and expected delivery date. Thank you!`);

        const orderText = lines.join('\n');

        // Copy to clipboard
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(orderText).then(onCopiedSuccess).catch(fallbackCopy);
        } else {
            fallbackCopy();
        }

        function fallbackCopy() {
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(orderText).select();
            document.execCommand('copy');
            $temp.remove();
            onCopiedSuccess();
        }

        function onCopiedSuccess() {
            const $btn = $('#btnCopyWhatsAppOrder');
            const origHtml = $btn.html();
            $btn.removeClass('btn-success').addClass('btn-dark').html('<i class="fas fa-check me-1"></i> Copied to Clipboard!');
            setTimeout(() => {
                $btn.removeClass('btn-dark').addClass('btn-success').html(origHtml);
            }, 3000);
        }
    });

    // -------------------------------------------------------------------------
    // 9. PRINT PURCHASE ORDER SLIP
    // -------------------------------------------------------------------------
    $('#btnPrintPurchaseOrder').on('click', function() {
        const catName = $('#filterCategory option:selected').text().split('(')[0].trim();
        const dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

        let rowsHtml = '';
        let count = 0;
        let totalUnits = 0;
        let totalAmount = 0.0;

        $('#plannerTableBody tr').each(function() {
            const pid = $(this).data('id');
            if (pid && plannerItemsMap[pid]) {
                const item = plannerItemsMap[pid];
                const qty = parseInt(item.order_qty || 0);
                if (qty > 0) {
                    count++;
                    totalUnits += qty;
                    const pCost = parseFloat(item.product.cost_price || 0);
                    const lineCost = qty * pCost;
                    totalAmount += lineCost;

                    rowsHtml += `
                        <tr>
                            <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;">${count}</td>
                            <td style="border: 1px solid #cbd5e1; padding: 6px;">
                                <b>${escapeHtml(item.product.name)}</b>
                                <div style="font-size: 10px; color: #64748b;">Barcode: ${escapeHtml(item.product.barcode)}</div>
                            </td>
                            <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;">${escapeHtml(item.product.category_name)}</td>
                            <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center;">${item.product.stock_quantity}</td>
                            <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center; font-weight: bold;">${qty} pcs</td>
                            <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: right;">Rs. ${formatNumber(pCost)}</td>
                            <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: right; font-weight: bold;">Rs. ${formatNumber(lineCost)}</td>
                            <td style="border: 1px solid #cbd5e1; padding: 6px; text-align: center; width: 50px;">[ &nbsp; ]</td>
                        </tr>
                    `;
                }
            }
        });

        if (count === 0) {
            alert('No items to print.');
            return;
        }

        const printWindow = window.open('', '_blank', 'width=900,height=700');
        printWindow.document.write(`
            <html>
            <head>
                <title>Purchase Order - ${window.STORE_NAME || 'One Dollar Shop'}</title>
                <style>
                    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; margin: 20px; color: #0f172a; }
                    .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 15px; }
                    .title { font-size: 20px; font-weight: bold; margin: 0; text-transform: uppercase; }
                    .subtitle { font-size: 13px; color: #475569; margin-top: 4px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                    th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 6px; text-align: left; font-size: 11px; text-transform: uppercase; }
                    .totals { margin-top: 20px; float: right; width: 320px; border: 1px solid #cbd5e1; padding: 10px; background: #f8fafc; }
                    .signatures { margin-top: 100px; display: flex; justify-content: space-between; }
                    .sig-line { width: 220px; border-top: 1px solid #000; text-align: center; padding-top: 5px; font-size: 11px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="title">${window.STORE_NAME || 'One Dollar Shop'}</div>
                    <div class="subtitle">Official Inventory Restock &amp; Purchase Order Slip</div>
                    <div style="font-size: 11px; margin-top: 6px;"><b>Date:</b> ${dateStr} &nbsp; | &nbsp; <b>Category:</b> ${catName}</div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="text-align: center; width: 30px;">#</th>
                            <th>Product Name &amp; Barcode</th>
                            <th style="text-align: center;">Category</th>
                            <th style="text-align: center;">Stock</th>
                            <th style="text-align: center;">Order Qty</th>
                            <th style="text-align: right;">Unit Cost</th>
                            <th style="text-align: right;">Total Cost</th>
                            <th style="text-align: center;">Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                </table>

                <div class="totals">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Total Items:</span><b>${count}</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Total Quantity:</span><b>${totalUnits} pcs</b>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px solid #cbd5e1; padding-top: 6px; font-size: 14px;">
                        <span>Estimated Total:</span><b>Rs. ${formatNumber(totalAmount)}</b>
                    </div>
                </div>

                <div style="clear: both;"></div>

                <div class="signatures">
                    <div class="sig-line">Prepared By (Store Manager)</div>
                    <div class="sig-line">Vendor / Supplier Acknowledgement</div>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(function() {
            printWindow.print();
        }, 500);
    });

    // -------------------------------------------------------------------------
    // 10. EXPORT PLANNER CSV
    // -------------------------------------------------------------------------
    $('#btnExportPlannerCsv').on('click', function() {
        let rows = [
            ['Product Name', 'Barcode', 'Category', 'Current Stock', 'Daily Run Rate', 'Cost Price', 'Order Qty', 'Line Total Cost']
        ];

        $('#plannerTableBody tr').each(function() {
            const pid = $(this).data('id');
            if (pid && plannerItemsMap[pid]) {
                const item = plannerItemsMap[pid];
                const qty = parseInt(item.order_qty || 0);
                if (qty > 0) {
                    const pCost = parseFloat(item.product.cost_price || 0);
                    rows.push([
                        `"${item.product.name.replace(/"/g, '""')}"`,
                        `"${item.product.barcode}"`,
                        `"${item.product.category_name}"`,
                        item.product.stock_quantity,
                        item.product.daily_run_rate,
                        pCost,
                        qty,
                        (qty * pCost).toFixed(2)
                    ]);
                }
            }
        });

        if (rows.length <= 1) {
            alert('No items in the order planner to export.');
            return;
        }

        let csv = '\uFEFF'; // BOM
        rows.forEach(r => { csv += r.join(',') + '\r\n'; });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `Purchase_Order_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // -------------------------------------------------------------------------
    // 11. EVENT LISTENERS
    // -------------------------------------------------------------------------
    // Date Pills
    $('.date-pill').on('click', function() {
        $('.date-pill').removeClass('active');
        $(this).addClass('active');
        currentPeriod = $(this).data('period');

        if (currentPeriod === 'custom') {
            $('#customDateRangeBox').removeClass('d-none');
        } else {
            $('#customDateRangeBox').addClass('d-none');
            loadVelocityData();
        }
    });

    $('#btnApplyCustomDate').on('click', function() {
        loadVelocityData();
    });

    // Segment Tabs
    $('#velocityTabs .nav-link').on('click', function() {
        $('#velocityTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        currentSegment = $(this).data('segment');

        if (currentSegment === 'dead') {
            currentSortBy = 'stock_cost_value';
            currentSortDir = 'desc';
            $('#sortBySelect').val('stock_cost_value');
        } else if (currentSegment === 'fast') {
            currentSortBy = 'units_sold';
            currentSortDir = 'desc';
            $('#sortBySelect').val('units_sold');
        } else if (currentSegment === 'slow') {
            currentSortBy = 'units_sold';
            currentSortDir = 'asc';
            $('#sortBySelect').val('units_sold');
        } else if (currentSegment === 'reorder') {
            currentSortBy = 'suggested_order_qty';
            currentSortDir = 'desc';
            $('#sortBySelect').val('suggested_order_qty');
        }
        updateSortIcons();
        loadVelocityData();
    });

    // KPI Card Click-to-filter
    $('.velocity-kpi-card').on('click', function() {
        const seg = $(this).data('target-segment');
        if (seg) {
            $(`#velocityTabs .nav-link[data-segment="${seg}"]`).click();
            window.scrollTo({ top: $('#velocityTabs').offset().top - 20, behavior: 'smooth' });
        }
    });

    // Category Filter Change
    $('#filterCategory').on('change', function() {
        loadVelocityData();
    });

    // Stock Status Filter Change
    $('#filterStockStatus').on('change', function() {
        loadVelocityData();
    });

    // Search Box with Debounce & Clear
    let searchTimer;
    $('#searchProductInput').on('input', function() {
        const val = $(this).val();
        if (val) $('#btnClearSearch').removeClass('d-none');
        else $('#btnClearSearch').addClass('d-none');

        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            loadVelocityData();
        }, 280);
    });

    $('#btnClearSearch').on('click', function() {
        $('#searchProductInput').val('').trigger('input');
        $(this).addClass('d-none');
    });

    // Sort Dropdown Change
    $('#sortBySelect').on('change', function() {
        currentSortBy = $(this).val();
        updateSortIcons();
        loadVelocityData();
    });

    // Sort Direction Button
    $('#btnSortDirection').on('click', function() {
        currentSortDir = (currentSortDir === 'desc') ? 'asc' : 'desc';
        $(this).data('dir', currentSortDir);
        $('#sortDirLabel').text(currentSortDir === 'desc' ? 'Descending' : 'Ascending');
        $(this).find('i').attr('class', currentSortDir === 'desc' ? 'fas fa-arrow-down-wide-short' : 'fas fa-arrow-up-wide-short');
        updateSortIcons();
        loadVelocityData();
    });

    // Table Header Click Sorting
    $('.sortable-th').on('click', function() {
        const clickedSort = $(this).data('sort');
        if (currentSortBy === clickedSort) {
            currentSortDir = (currentSortDir === 'desc') ? 'asc' : 'desc';
        } else {
            currentSortBy = clickedSort;
            currentSortDir = (clickedSort === 'name' || clickedSort === 'category_name') ? 'asc' : 'desc';
        }
        $('#sortBySelect').val(currentSortBy);
        $('#sortDirLabel').text(currentSortDir === 'desc' ? 'Descending' : 'Ascending');
        $('#btnSortDirection').find('i').attr('class', currentSortDir === 'desc' ? 'fas fa-arrow-down-wide-short' : 'fas fa-arrow-up-wide-short');
        updateSortIcons();
        loadVelocityData();
    });

    function updateSortIcons() {
        $('.sortable-th').removeClass('active-sort').find('.sort-icon').attr('class', 'fas fa-sort sort-icon');
        const $activeTh = $(`.sortable-th[data-sort="${currentSortBy}"]`);
        $activeTh.addClass('active-sort');
        const iconClass = (currentSortDir === 'desc') ? 'fas fa-sort-down sort-icon' : 'fas fa-sort-up sort-icon';
        $activeTh.find('.sort-icon').attr('class', iconClass);
    }

    $('#btnRefreshData').on('click', function() {
        loadVelocityData();
    });

    // -------------------------------------------------------------------------
    // 12. STOCK MOVEMENT LEDGER MODAL
    // -------------------------------------------------------------------------
    $(document).on('click', '.btn-view-stock-history', function() {
        const pid = $(this).data('id');
        const name = $(this).data('name');
        const barcode = $(this).data('barcode');

        $('#modalStockProductName').text(`${name} (Barcode: ${barcode})`);
        const $tbody = $('#modalStockHistoryBody');
        $tbody.html('<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-1"></div> Loading history...</td></tr>');

        const modalEl = document.getElementById('modalStockHistory');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        $.ajax({
            url: `${window.BASE_URL || ''}/api/reports.php?action=product_history&product_id=${pid}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.history && res.history.length > 0) {
                    $tbody.empty();
                    res.history.forEach(h => {
                        const isPlus = parseInt(h.quantity) > 0;
                        const qtyClass = isPlus ? 'text-success fw-bold' : 'text-danger fw-bold';
                        const qtySign = isPlus ? '+' : '';
                        $tbody.append(`
                            <tr>
                                <td class="ps-3 fs-xs text-muted">${h.created_at}</td>
                                <td><span class="badge bg-light text-dark border">${escapeHtml(h.movement_type)}</span></td>
                                <td class="text-center ${qtyClass}">${qtySign}${h.quantity}</td>
                                <td class="fs-xs text-muted">${escapeHtml(h.reason || '-')}</td>
                                <td class="fs-xs text-dark fw-semibold">${escapeHtml(h.username || 'System')}</td>
                            </tr>
                        `);
                    });
                } else {
                    $tbody.html('<tr><td colspan="5" class="text-center py-4 text-muted">No movement history logs recorded.</td></tr>');
                }
            },
            error: function() {
                $tbody.html('<tr><td colspan="5" class="text-center py-4 text-danger">Failed to load movement logs.</td></tr>');
            }
        });
    });

    // -------------------------------------------------------------------------
    // 13. CSV EXPORT
    // -------------------------------------------------------------------------
    $('#btnExportCsv').on('click', function() {
        if (!activeProductsData || activeProductsData.length === 0) {
            alert('No data available to export.');
            return;
        }

        let csv = '\uFEFF'; // UTF-8 BOM
        const headers = [
            'Product ID', 'Barcode', 'Product Name', 'Category', 
            'Cost Price', 'Selling Price', 'Stock in Hand', 'Low Threshold',
            'Units Sold', 'Total Revenue', 'Net COGS', 'Gross Profit',
            'Tied Cost Capital', 'Retail Potential', 'Daily Run Rate', 
            'Stock Days Cover', 'Suggested Order Qty', 'Est Reorder Budget',
            'Velocity Status', 'Recommendation'
        ];
        csv += headers.join(',') + '\r\n';

        activeProductsData.forEach(p => {
            const row = [
                p.id,
                `"${p.barcode}"`,
                `"${(p.name || '').replace(/"/g, '""')}"`,
                `"${(p.category_name || '').replace(/"/g, '""')}"`,
                p.cost_price,
                p.selling_price,
                p.stock_quantity,
                p.low_stock_threshold,
                p.units_sold,
                p.total_revenue,
                p.total_cogs,
                p.total_profit,
                p.stock_cost_value,
                p.stock_retail_value,
                p.daily_run_rate,
                p.days_of_stock,
                p.suggested_order_qty,
                p.estimated_order_cost,
                `"${p.velocity_status}"`,
                `"${(p.order_advice || '').replace(/"/g, '""')}"`
            ];
            csv += row.join(',') + '\r\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `Product_Velocity_Restock_${currentPeriod}_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Helper formatting functions
    function formatNumber(num) {
        return parseFloat(num || 0).toLocaleString('en-PK', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
