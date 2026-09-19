<?php
require_once __DIR__ . '/../includes/header.php';
if (!hasPermission('MANAGE_KHATA') && !hasPermission('CREATE_SALE') && !isAdmin()) {
    requirePermission('MANAGE_KHATA');
}

$isAdmin = isAdmin();
$canVoid = $isAdmin;
$preselectedCustomerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$currentUsername = $_SESSION['username'] ?? 'Staff';
?>

<style>
/* ==========================================================================
   CUSTOMER CREDIT KHATA & RECOVERY LEDGER (ENTERPRISE UX)
   ========================================================================== */
:root {
    --khata-primary: #0f172a;       /* Deep Slate */
    --khata-debit: #dc2626;         /* Crimson Red (Credit Sale / Debit) */
    --khata-debit-bg: #fef2f2;
    --khata-credit: #059669;        /* Emerald Green (Recovery / Credit) */
    --khata-credit-bg: #ecfdf5;
    --khata-accent: #3b82f6;        /* Royal Blue */
    --khata-warning: #d97706;       /* Amber */
    --khata-card-border: #e2e8f0;
}

/* KPI Summary Cards */
.khata-kpi-card {
    border-radius: 16px;
    border: 1px solid var(--khata-card-border);
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
    position: relative;
}
.khata-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.08);
}
.khata-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}

/* Quick Action Buttons */
.btn-khata-action {
    font-weight: 700;
    border-radius: 12px;
    padding: 10px 18px;
    font-size: 0.92rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    border: none;
}
.btn-khata-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}
.btn-khata-debit {
    background: #dc2626;
    color: #ffffff;
}
.btn-khata-debit:hover {
    background: #b91c1c;
    color: #ffffff;
}
.btn-khata-credit {
    background: #059669;
    color: #ffffff;
}
.btn-khata-credit:hover {
    background: #047857;
    color: #ffffff;
}
.btn-khata-new {
    background: #0f172a;
    color: #ffffff;
}
.btn-khata-new:hover {
    background: #1e293b;
    color: #ffffff;
}

/* Customer Card */
.customer-khata-card {
    border-radius: 16px;
    border: 1px solid var(--khata-card-border);
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    transition: all 0.2s ease;
    overflow: hidden;
    position: relative;
    cursor: pointer;
}
.customer-khata-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.07);
    border-color: #cbd5e1;
}
.customer-avatar-box {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #0f172a;
    color: #ffffff;
    font-weight: 800;
    font-size: 1.15rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.customer-type-badge {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.badge-type-store { background: #fef3c7; color: #92400e; }
.badge-type-neighbor { background: #e0e7ff; color: #3730a3; }
.badge-type-friend { background: #fce7f3; color: #9d174d; }
.badge-type-general { background: #f1f5f9; color: #334155; }

/* Balance Highlight Badges */
.balance-badge-debit {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    padding: 6px 12px;
    border-radius: 10px;
    font-weight: 800;
    font-size: 1.05rem;
    display: inline-block;
}
.balance-badge-settled {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    padding: 6px 12px;
    border-radius: 10px;
    font-weight: 800;
    font-size: 0.95rem;
    display: inline-block;
}
.balance-badge-advance {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 6px 12px;
    border-radius: 10px;
    font-weight: 800;
    font-size: 1.05rem;
    display: inline-block;
}

/* Filter Pills */
.khata-filter-pill {
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    transition: all 0.15s ease;
    user-select: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.khata-filter-pill:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #94a3b8;
}
.khata-filter-pill.active {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 2px 6px rgba(15,23,42,0.25);
}

/* Items Dynamic Table in Modal */
.khata-item-row {
    background: #ffffff;
    transition: background-color 0.15s ease;
}
.khata-item-row:hover {
    background-color: #f8fafc;
}
.item-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    border: 1px solid #e2e8f0;
    max-height: 220px;
    overflow-y: auto;
}
.item-search-item {
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background 0.15s ease;
}
.item-search-item:hover {
    background: #f1f5f9;
}
.item-search-item:last-child {
    border-bottom: none;
}

/* Quick Amount Chips in Recovery Modal */
.quick-chip-btn {
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #1e293b;
    font-weight: 600;
    font-size: 0.8rem;
    padding: 5px 12px;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.quick-chip-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.quick-chip-btn.active {
    background: #059669;
    color: #ffffff;
    border-color: #059669;
}

/* Ledger Passbook Styles */
.passbook-banner {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    border-radius: 18px;
    padding: 1.5rem;
    box-shadow: 0 8px 24px rgba(15,23,42,0.12);
}
.ledger-table th {
    background-color: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-bottom: 2px solid #e2e8f0;
}
.ledger-row-bill {
    border-left: 4px solid var(--khata-debit);
}
.ledger-row-pay {
    border-left: 4px solid var(--khata-credit);
}

/* Navigation Tabs in Passbook */
.khata-nav-pills .nav-link {
    color: #475569;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    font-size: 0.88rem;
    transition: all 0.2s ease;
}
.khata-nav-pills .nav-link:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.khata-nav-pills .nav-link.active {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 4px 12px rgba(15,23,42,0.18);
}
.khata-inline-items-list {
    max-height: 190px;
    overflow-y: auto;
}
.card-goods-bill {
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}
.card-goods-bill:hover {
    border-color: #cbd5e1;
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
}
.goods-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 7px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #334155;
}

/* Pulse Animation for Live Updates */
@keyframes spin-refresh {
    100% { transform: rotate(360deg); }
}
.spin-anim {
    animation: spin-refresh 0.6s linear infinite;
}
</style>

<!-- ==========================================================================
     MAIN KHATA MODULE CONTAINER
     ========================================================================== -->
<div id="khataApp" class="fade-in pb-5">

    <!-- 1. Header & Title -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h3 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-book-bookmark text-danger me-2"></i>Customer Credit Khata & Ledger
                </h3>
                <span class="badge bg-danger rounded-pill px-2 py-1 fs-xs">Live Ledger</span>
            </div>
            <p class="text-muted mb-0 mt-1">
                Track daily credit sales, neighboring stores, recovery payments, and customer balances
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="btnRefreshKhata" title="Reload Khata Records">
                <i class="fas fa-arrows-rotate me-1" id="refreshIcon"></i> Refresh
            </button>
            <button type="button" class="btn btn-khata-action btn-khata-new" id="btnOpenNewCustomerModal">
                <i class="fas fa-user-plus"></i> + New Customer
            </button>
            <button type="button" class="btn btn-khata-action btn-khata-debit" id="btnOpenCreditSaleModal">
                <i class="fas fa-cart-arrow-down"></i> + Credit Sale (Udhar)
            </button>
            <button type="button" class="btn btn-khata-action btn-khata-credit" id="btnOpenRecoveryModal">
                <i class="fas fa-hand-holding-dollar"></i> + Receive Payment (Jama)
            </button>
        </div>
    </div>

    <!-- 2. Live Top KPI Metrics Bar -->
    <div class="row g-3 mb-4 <?php echo $preselectedCustomerId > 0 ? 'd-none' : ''; ?>" id="khataDirectoryKpiBar">
        <!-- Metric 1: Total Market Receivables -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="khata-kpi-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                            Total Receivables (Market Due)
                        </div>
                        <h3 class="fw-bolder mb-0 text-danger mt-1" id="kpiTotalReceivables">
                            Rs. 0.00
                        </h3>
                        <div class="text-muted fs-xs mt-1" id="kpiDebtorsCount">
                            <span class="badge bg-danger-subtle text-danger fw-bold rounded-pill px-2">0 debtors</span> with pending balance
                        </div>
                    </div>
                    <div class="khata-kpi-icon bg-danger-subtle text-danger">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 2: Today's Credit Sales -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="khata-kpi-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                            Today's Credit Sales
                        </div>
                        <h3 class="fw-bolder mb-0 text-warning mt-1" id="kpiTodayCredit">
                            Rs. 0.00
                        </h3>
                        <div class="text-muted fs-xs mt-1">
                            Total goods given on credit today
                        </div>
                    </div>
                    <div class="khata-kpi-icon bg-warning-subtle text-warning">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 3: Today's Recovery Payments -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="khata-kpi-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                            Today's Recovery (Collected)
                        </div>
                        <h3 class="fw-bolder mb-0 text-success mt-1" id="kpiTodayRecovery">
                            Rs. 0.00
                        </h3>
                        <div class="text-muted fs-xs mt-1">
                            Cash recovered into drawer today
                        </div>
                    </div>
                    <div class="khata-kpi-icon bg-success-subtle text-success">
                        <i class="fas fa-money-bill-trend-up"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric 4: Total Registered Customers -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="khata-kpi-card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                            Total Khata Accounts
                        </div>
                        <h3 class="fw-bolder mb-0 text-primary mt-1" id="kpiTotalCustomers">
                            0
                        </h3>
                        <div class="text-muted fs-xs mt-1">
                            Registered stores, neighbors & friends
                        </div>
                    </div>
                    <div class="khata-kpi-icon bg-primary-subtle text-primary">
                        <i class="fas fa-users-viewfinder"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================================
         VIEW 1: DIRECTORY / CUSTOMER LIST VIEW
         ====================================================================== -->
    <div id="viewCustomerDirectory" class="<?php echo $preselectedCustomerId > 0 ? 'd-none' : ''; ?>">
        <!-- Search, Filter & View Controls -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="row g-3 align-items-center">
                    <!-- Search Input -->
                    <div class="col-12 col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="searchCustomerInput" class="form-control border-start-0 ps-0" 
                                   placeholder="Search by name, phone number, or address..." autocomplete="off">
                            <button class="btn btn-outline-secondary d-none" type="button" id="btnClearSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Status Filter Pills -->
                    <div class="col-12 col-md-7 d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                        <span class="text-muted fs-xs fw-bold me-1">Balance Filter:</span>
                        <button class="khata-filter-pill active" data-filter="all">All Accounts</button>
                        <button class="khata-filter-pill" data-filter="pending">
                            <i class="fas fa-circle-exclamation text-danger me-1"></i>Pending Balance Only
                        </button>
                        <button class="khata-filter-pill" data-filter="cleared">
                            <i class="fas fa-circle-check text-success me-1"></i>Settled / Zero (0)
                        </button>

                        <div class="vr mx-2 d-none d-md-block"></div>

                        <!-- View Toggle: Cards vs Table -->
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-dark active" id="btnViewCards" title="Card View">
                                <i class="fas fa-grip"></i>
                            </button>
                            <button type="button" class="btn btn-outline-dark" id="btnViewTable" title="Table View">
                                <i class="fas fa-table-list"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Customer Type Filter Pills (Row 2) -->
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top">
                    <span class="text-muted fs-xs fw-bold me-1">Category:</span>
                    <button class="khata-filter-pill type-pill active" data-type="">All Categories</button>
                    <button class="khata-filter-pill type-pill" data-type="store">
                        <span>🏪</span> Other Stores
                    </button>
                    <button class="khata-filter-pill type-pill" data-type="neighbor">
                        <span>🏡</span> Neighbors
                    </button>
                    <button class="khata-filter-pill type-pill" data-type="friend">
                        <span>🤝</span> Friends & Family
                    </button>
                    <button class="khata-filter-pill type-pill" data-type="general">
                        <span>👤</span> General Customers
                    </button>
                </div>
            </div>
        </div>

        <!-- Cards View Container -->
        <div id="customersCardsContainer" class="row g-3">
            <!-- Dynamically Rendered Via JS -->
        </div>

        <!-- Table View Container (Initially Hidden) -->
        <div id="customersTableContainer" class="card border-0 shadow-sm rounded-4 overflow-hidden d-none">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th class="ps-3 py-3">Customer / Shop Name</th>
                            <th>Category</th>
                            <th>Phone & Address</th>
                            <th>Last Goods Taken (Saman)</th>
                            <th>Last Payment (Vasooli)</th>
                            <th class="text-end">Total Credit Sales</th>
                            <th class="text-end">Total Recovered</th>
                            <th class="text-end">Current Balance</th>
                            <th class="text-center pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customersTableBody">
                        <!-- Dynamically Rendered Via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State Container -->
        <div id="customersEmptyState" class="text-center py-5 d-none">
            <div class="mb-3">
                <i class="fas fa-address-book text-muted" style="font-size: 3.5rem; opacity: 0.4;"></i>
            </div>
            <h5 class="fw-bold text-dark">No Khata Accounts Found</h5>
            <p class="text-muted fs-sm">Change your search terms or click "+ New Customer" to register an account.</p>
            <button class="btn btn-khata-action btn-khata-new" onclick="$('#btnOpenNewCustomerModal').click();">
                <i class="fas fa-user-plus"></i> + New Customer
            </button>
        </div>

        <!-- Loading Skeleton -->
        <div id="customersLoadingState" class="text-center py-5">
            <div class="spinner-border text-danger" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted fs-sm mt-2">Loading Khata accounts...</p>
        </div>
    </div>


    <!-- ======================================================================
         VIEW 2: INDIVIDUAL CUSTOMER PASSBOOK & LEDGER
         ====================================================================== -->
    <div id="viewCustomerPassbook" class="<?php echo $preselectedCustomerId > 0 ? '' : 'd-none'; ?>">
        <!-- Back Navigation & Action Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" id="btnBackToDirectory">
                <i class="fas fa-arrow-left me-1"></i> Back to Accounts List
            </button>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-success rounded-pill px-3" id="btnPassbookWhatsApp">
                    <i class="fab fa-whatsapp me-1 fs-6"></i> WhatsApp Statement
                </button>
                <button type="button" class="btn btn-outline-dark rounded-pill px-3" id="btnPassbookPrintSlip">
                    <i class="fas fa-print me-1"></i> Print Statement (80mm)
                </button>
                <button type="button" class="btn btn-khata-action btn-khata-debit py-2" id="btnPassbookAddCredit">
                    <i class="fas fa-cart-arrow-down"></i> + Credit Sale
                </button>
                <button type="button" class="btn btn-khata-action btn-khata-credit py-2" id="btnPassbookAddPayment">
                    <i class="fas fa-hand-holding-dollar"></i> + Receive Payment
                </button>
            </div>
        </div>

        <!-- Passbook Customer Banner -->
        <div class="passbook-banner mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-7">
                    <div class="d-flex align-items-start gap-3">
                        <div class="customer-avatar-box bg-danger fs-3" style="width: 58px; height: 58px;" id="pbAvatar">
                            ?
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h3 class="fw-bold mb-0 text-white" id="pbCustomerName">Customer Name</h3>
                                <span class="customer-type-badge badge-type-store text-dark" id="pbCustomerTypeBadge">
                                    Category
                                </span>
                            </div>
                            <div class="text-white-50 mt-1 fs-sm d-flex flex-wrap gap-3">
                                <span><i class="fas fa-phone me-1"></i> <span id="pbCustomerPhone">-</span></span>
                                <span><i class="fas fa-location-dot me-1"></i> <span id="pbCustomerAddress">No address provided</span></span>
                                <span><i class="fas fa-shield-halved me-1"></i> Credit Limit: <b class="text-white" id="pbCreditLimit">Rs. 0.00</b></span>
                            </div>
                            <div class="text-white-50 fs-xs mt-1" id="pbCustomerNotes"></div>
                        </div>
                    </div>
                </div>

                <!-- Financial Highlight Box -->
                <div class="col-12 col-md-5">
                    <div class="bg-white text-dark rounded-4 p-3 shadow-sm">
                        <div class="row text-center g-2">
                            <div class="col-4 border-end">
                                <div class="text-muted fs-xs fw-bold">Total Credit Sales</div>
                                <div class="fw-bold text-danger fs-6" id="pbLifetimeCredit">Rs. 0.00</div>
                            </div>
                            <div class="col-4 border-end">
                                <div class="text-muted fs-xs fw-bold">Total Recovered</div>
                                <div class="fw-bold text-success fs-6" id="pbLifetimePaid">Rs. 0.00</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted fs-xs fw-bold">Net Balance Due</div>
                                <div class="fw-bolder fs-5 text-danger" id="pbCurrentBalance">Rs. 0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs: Enterprise Ledger Views -->
        <ul class="nav nav-pills khata-nav-pills mb-3 gap-2" id="passbookTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill px-3 py-2 fw-bold" id="tab-passbook-all" data-bs-toggle="pill" data-bs-target="#pane-passbook-all" type="button" role="tab">
                    <i class="fas fa-book-open me-2"></i>1. Full Passbook (All Bills & Payments)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-3 py-2 fw-bold" id="tab-passbook-goods" data-bs-toggle="pill" data-bs-target="#pane-passbook-goods" type="button" role="tab">
                    <i class="fas fa-cart-shopping me-2"></i>2. Goods Taken (Saman Ki Tafseel)
                    <span class="badge bg-danger rounded-pill ms-1" id="pbGoodsCountBadge">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-3 py-2 fw-bold" id="tab-passbook-payments" data-bs-toggle="pill" data-bs-target="#pane-passbook-payments" type="button" role="tab">
                    <i class="fas fa-hand-holding-dollar me-2"></i>3. Payments Made (Vasooli History)
                    <span class="badge bg-success rounded-pill ms-1" id="pbPaymentsCountBadge">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-3 py-2 fw-bold" id="tab-passbook-pending" data-bs-toggle="pill" data-bs-target="#pane-passbook-pending" type="button" role="tab">
                    <i class="fas fa-file-invoice-dollar me-2"></i>4. Pending Balance & Unpaid Goods
                </button>
            </li>
        </ul>

        <div class="tab-content" id="passbookTabContent">
            <!-- PANE 1: FULL PASSBOOK CHRONOLOGICAL LEDGER -->
            <div class="tab-pane fade show active" id="pane-passbook-all" role="tabpanel">
                <!-- Ledger Filter Bar & Date Selector -->
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center justify-content-between">
                            <div class="col-12 col-md-auto d-flex flex-wrap align-items-center gap-2">
                                <span class="text-muted fs-xs fw-bold">Period:</span>
                                <button class="khata-filter-pill pb-period-pill active" data-period="all">All Time</button>
                                <button class="khata-filter-pill pb-period-pill" data-period="month">This Month</button>
                                <button class="khata-filter-pill pb-period-pill" data-period="last30">Last 30 Days</button>
                            </div>

                            <div class="col-12 col-md-auto d-flex flex-wrap align-items-center gap-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted">From:</span>
                                    <input type="date" id="pbFilterStartDate" class="form-control form-control-sm">
                                    <span class="input-group-text bg-light text-muted">To:</span>
                                    <input type="date" id="pbFilterEndDate" class="form-control form-control-sm">
                                    <button class="btn btn-dark btn-sm" id="btnApplyPbCustomDate">Filter</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ledger Statement Summary Chips -->
                <div class="row g-2 mb-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-white rounded-3 border shadow-xs">
                            <span class="text-muted fs-xs d-block">Opening Balance</span>
                            <b class="fs-6 text-dark" id="pbPeriodOpening">Rs. 0.00</b>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-white rounded-3 border shadow-xs">
                            <span class="text-muted fs-xs d-block">Total Bills Amount (+)</span>
                            <b class="fs-6 text-danger" id="pbPeriodDebit">Rs. 0.00</b>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-white rounded-3 border shadow-xs">
                            <span class="text-muted fs-xs d-block">Total Paid / Received (-)</span>
                            <b class="fs-6 text-success" id="pbPeriodCredit">Rs. 0.00</b>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 bg-white rounded-3 border shadow-xs">
                            <span class="text-muted fs-xs d-block">Remaining Balance Due</span>
                            <b class="fs-6 text-danger" id="pbPeriodClosing">Rs. 0.00</b>
                        </div>
                    </div>
                </div>

                <!-- Ledger Transactions Table -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 ledger-table">
                            <thead class="table-light text-muted fs-xs">
                                <tr>
                                    <th class="ps-3 py-3" style="width: 140px;">Date & Time</th>
                                    <th style="min-width: 250px;">Description & Items (Saman / Vasooli)</th>
                                    <th class="text-end" style="width: 125px;">Bill Amount</th>
                                    <th class="text-end" style="width: 125px;">Paid Amount</th>
                                    <th class="text-end" style="width: 135px;">Net Udhar / Change</th>
                                    <th class="text-end" style="width: 145px;">Total Balance</th>
                                    <th class="text-center pe-3" style="width: 90px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="pbLedgerTableBody">
                                <!-- Dynamically Rendered Via JS -->
                            </tbody>
                        </table>
                    </div>
                    <div id="pbEmptyLedger" class="text-center py-5 d-none">
                        <i class="fas fa-file-invoice text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                        <h6 class="fw-bold mt-2 text-dark">No Transactions in this Period</h6>
                        <p class="text-muted fs-xs">Use the buttons above to record a new credit bill or payment.</p>
                    </div>
                </div>
            </div>

            <!-- PANE 2: GOODS TAKEN (SAMAN KI TAFSEEL) -->
            <div class="tab-pane fade" id="pane-passbook-goods" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 mb-3 p-3 bg-light">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-cart-shopping text-danger me-2"></i>Complete Record of Goods Taken on Credit (Udhar Saman)</h6>
                            <span class="text-muted fs-xs">Every credit bill with exact item names, quantities, unit prices, and bill totals.</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end">
                                <div class="text-muted fs-xs fw-bold">Total Goods Value:</div>
                                <div class="fw-bolder text-danger fs-5" id="pbGoodsTotalVal">Rs. 0.00</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="pbGoodsContainer">
                    <!-- Dynamically Rendered Via JS -->
                </div>
                <div id="pbEmptyGoods" class="text-center py-5 d-none">
                    <i class="fas fa-shopping-basket text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                    <h6 class="fw-bold mt-2 text-dark">No Credit Goods Taken</h6>
                    <p class="text-muted fs-xs">This customer has not taken any goods on credit yet.</p>
                </div>
            </div>

            <!-- PANE 3: PAYMENTS MADE (VASOOLI HISTORY) -->
            <div class="tab-pane fade" id="pane-passbook-payments" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 mb-3 p-3 bg-light">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-hand-holding-dollar text-success me-2"></i>Complete Record of Payments Received (Vasooli)</h6>
                            <span class="text-muted fs-xs">Every payment receipt with date, amount, payment method, and cashier notes.</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end">
                                <div class="text-muted fs-xs fw-bold">Total Recovered:</div>
                                <div class="fw-bolder text-success fs-5" id="pbPaymentsTotalVal">Rs. 0.00</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="ps-3 py-3">Date & Time</th>
                                    <th>Receipt Number</th>
                                    <th>Payment Method</th>
                                    <th class="text-end">Amount Received</th>
                                    <th>Received By (Cashier)</th>
                                    <th>Cashier Notes</th>
                                    <th class="text-center pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="pbPaymentsTableBody">
                                <!-- Dynamically Rendered Via JS -->
                            </tbody>
                        </table>
                    </div>
                    <div id="pbEmptyPayments" class="text-center py-5 d-none">
                        <i class="fas fa-money-check-dollar text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                        <h6 class="fw-bold mt-2 text-dark">No Payments Recorded</h6>
                        <p class="text-muted fs-xs">No recovery payments have been received from this customer yet.</p>
                    </div>
                </div>
            </div>

            <!-- PANE 4: PENDING BALANCE & UNPAID BREAKDOWN -->
            <div class="tab-pane fade" id="pane-passbook-pending" role="tabpanel">
                <div id="pbPendingStatusAlert" class="alert alert-danger rounded-4 p-3 mb-3">
                    <!-- Dynamically Rendered Via JS -->
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-white rounded-4 border shadow-xs text-center">
                            <div class="text-muted fs-xs fw-bold mb-1">Total Goods Value Taken</div>
                            <div class="fw-bolder text-dark fs-5" id="pbPendingTotalCredit">Rs. 0.00</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-white rounded-4 border shadow-xs text-center">
                            <div class="text-muted fs-xs fw-bold mb-1">Total Payments Received</div>
                            <div class="fw-bolder text-success fs-5" id="pbPendingTotalPaid">Rs. 0.00</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-white rounded-4 border shadow-xs text-center">
                            <div class="text-muted fs-xs fw-bold mb-1">Net Balance Due (Baqaya)</div>
                            <div id="pbPendingNetDue">Rs. 0.00</div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-list-check text-primary me-2"></i>Outstanding Credit Invoices & Item Breakdown</h6>
                        <button type="button" class="btn btn-sm btn-khata-action btn-khata-credit" onclick="$('#btnPassbookAddPayment').click();">
                            <i class="fas fa-hand-holding-dollar"></i> + Receive Payment / Settle
                        </button>
                    </div>
                    <div id="pbPendingBillsContainer">
                        <!-- Dynamically Rendered Via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

</div> <!-- End khataApp -->


<!-- ==========================================================================
     MODAL 1: ADD / EDIT CUSTOMER
     ========================================================================== -->
<div class="modal fade" id="modalCustomer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalCustomerTitle">
                    <i class="fas fa-user-plus text-primary me-2"></i>Register New Customer Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCustomer">
                <input type="hidden" id="custInputId" name="customer_id" value="">
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-sm">Customer / Shop Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="custInputName" name="name" 
                               placeholder="e.g. Madina Sweets, Haji Aslam, or Tariq Bhai" required>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold fs-sm">Mobile Phone Number</label>
                            <input type="text" class="form-control rounded-3" id="custInputPhone" name="phone" 
                                   placeholder="03001234567">
                            <small class="text-muted fs-xs">Required for WhatsApp statements</small>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold fs-sm">Customer Category <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="custInputType" name="customer_type" required>
                                <option value="store">🏪 Other Store (Neighbor Shop)</option>
                                <option value="neighbor">🏡 Neighbor (Local Resident)</option>
                                <option value="friend">🤝 Friend / Relative</option>
                                <option value="general" selected>👤 General Customer</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold fs-sm">Shop Address / Location</label>
                        <input type="text" class="form-control rounded-3" id="custInputAddress" name="address" 
                               placeholder="e.g. Main Bazar Shop #4, or Street 2">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold fs-sm">Credit Limit (Rs.)</label>
                            <input type="number" class="form-control rounded-3" id="custInputLimit" name="credit_limit" 
                                   value="0" min="0" step="100">
                            <small class="text-muted fs-xs">0 means Unlimited credit</small>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold fs-sm">Notes / Reference</label>
                            <input type="text" class="form-control rounded-3" id="custInputNotes" name="notes" 
                                   placeholder="Additional details or reference">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="btnSaveCustomer">
                        <i class="fas fa-check me-1"></i> Save Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ==========================================================================
     MODAL 2: RECORD CREDIT SALE (GOODS ON CREDIT - UDHAR)
     ========================================================================== -->
<div class="modal fade" id="modalCreditSale" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom bg-danger-subtle py-3 px-4">
                <h5 class="modal-title fw-bold text-danger mb-0">
                    <i class="fas fa-cart-arrow-down me-2"></i>Record Credit Sale (Goods Given on Udhar)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formCreditSale">
                <div class="modal-body p-4">
                    <!-- Customer Selection & Date -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label fw-bold fs-sm">Select Customer Account <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3 fw-bold" id="saleSelectCustomer" required>
                                <option value="">-- Choose Customer --</option>
                                <!-- Populated dynamically -->
                            </select>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted fs-xs" id="saleCustBalanceHint">Current Balance: <b>Rs. 0.00</b></small>
                                <small class="text-muted fs-xs" id="saleCustLimitHint"></small>
                            </div>
                        </div>

                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold fs-sm">Bill Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control rounded-3" id="saleInputDate" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="card border rounded-3 mb-3">
                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark fs-xs text-uppercase">Items List (Goods Taken)</span>
                            <span class="badge bg-secondary-subtle text-dark fs-xs" id="saleItemsCountBadge">1 Item</span>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-borderless table-sm align-middle mb-0" id="saleItemsTable">
                                    <thead>
                                        <tr class="text-muted fs-xs border-bottom">
                                            <th style="width: 45%;">Item Name (Search or Type)</th>
                                            <th style="width: 18%;" class="text-center">Quantity</th>
                                            <th style="width: 22%;" class="text-end">Unit Price (Rate)</th>
                                            <th style="width: 20%;" class="text-end">Total Amount</th>
                                            <th style="width: 5%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="saleItemsTableBody">
                                        <!-- Item Rows Inserted Dynamically -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="pt-2 d-flex justify-content-between align-items-center border-top mt-2">
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" id="btnAddSaleItemRow">
                                    <i class="fas fa-plus me-1"></i> + Add More Items
                                </button>
                                <div class="text-end">
                                    <span class="text-muted fs-sm me-2">Grand Total:</span>
                                    <span class="fw-bolder fs-4 text-danger" id="saleGrandTotalText">Rs. 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Deduction Checkbox & Notes -->
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="form-check form-switch p-2 bg-light rounded-3 border ps-5">
                                <input class="form-check-input" type="checkbox" id="saleCheckDeductStock" checked>
                                <label class="form-check-label fw-bold fs-xs text-dark" for="saleCheckDeductStock">
                                    Deduct from Store POS Inventory Stock
                                </label>
                                <small class="d-block text-muted fs-xs">
                                    Automatically subtracts catalog items from quantity
                                </small>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold fs-sm mb-1">Notes / Sent With</label>
                            <input type="text" class="form-control rounded-3 form-control-sm" id="saleInputNotes" 
                                   placeholder="e.g. Sent with shop boy Bilal, or picked up personally">
                        </div>
                    </div>

                    <!-- Expected New Balance Preview Box -->
                    <div class="alert alert-secondary py-2 px-3 mt-3 mb-0 rounded-3 d-flex justify-content-between align-items-center">
                        <span class="fs-xs text-muted">Expected New Balance After this Bill:</span>
                        <b class="fs-5 text-danger" id="saleNewBalancePreview">Rs. 0.00</b>
                    </div>
                </div>

                <div class="modal-footer border-top py-3 px-4 bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-danger rounded-pill px-4 fw-bold" id="btnSaveSaleOnly">
                        <i class="fas fa-save me-1"></i> Save Only
                    </button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" id="btnSaveSaleAndPrint">
                        <i class="fas fa-print me-1"></i> Save & Print 80mm Slip
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ==========================================================================
     MODAL 3: RECORD RECOVERY PAYMENT (VASOOLI / JAMA)
     ========================================================================== -->
<div class="modal fade" id="modalRecoveryPayment" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom bg-success-subtle py-3 px-4">
                <h5 class="modal-title fw-bold text-success mb-0">
                    <i class="fas fa-hand-holding-dollar me-2"></i>Receive Recovery Payment (Jama Receipt)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formRecoveryPayment">
                <div class="modal-body p-4">
                    <!-- Customer Select -->
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-sm">Select Customer Account <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3 fw-bold" id="paySelectCustomer" required>
                            <option value="">-- Choose Customer --</option>
                            <!-- Populated dynamically -->
                        </select>
                        <div class="p-2 bg-danger-subtle text-danger rounded-3 mt-2 d-flex justify-content-between align-items-center">
                            <span class="fs-xs fw-bold">Current Outstanding Balance:</span>
                            <span class="fs-5 fw-bolder" id="payCustCurrentBalance">Rs. 0.00</span>
                        </div>
                    </div>

                    <!-- Quick Amount Fill Chips -->
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-xs text-muted mb-1">Quick Amount Chips:</label>
                        <div class="d-flex flex-wrap gap-2" id="payQuickChipsContainer">
                            <button type="button" class="quick-chip-btn" id="chipFullBalance">Full Balance</button>
                            <button type="button" class="quick-chip-btn" data-val="500">Rs. 500</button>
                            <button type="button" class="quick-chip-btn" data-val="1000">Rs. 1,000</button>
                            <button type="button" class="quick-chip-btn" data-val="2000">Rs. 2,000</button>
                            <button type="button" class="quick-chip-btn" data-val="5000">Rs. 5,000</button>
                            <button type="button" class="quick-chip-btn" data-val="10000">Rs. 10,000</button>
                        </div>
                    </div>

                    <!-- Amount & Date -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label fw-bold fs-sm">Amount Received (Rs.) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white fw-bold">Rs.</span>
                                <input type="number" class="form-control form-control-lg rounded-end-3 fw-bolder text-success" 
                                       id="payInputAmount" min="1" step="1" placeholder="0" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold fs-sm">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control rounded-3 mt-1" id="payInputDate" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <!-- Payment Method & Received By Staff -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold fs-sm">Payment Method</label>
                            <select class="form-select rounded-3" id="paySelectMethod">
                                <option value="cash" selected>💵 Cash in Drawer</option>
                                <option value="bank">🏦 Bank Transfer</option>
                                <option value="easypaisa_jazzcash">📱 EasyPaisa / JazzCash</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold fs-sm">Received By (Staff / Employee)</label>
                            <select class="form-select rounded-3" id="paySelectEmployee">
                                <option value="">-- Dukan Counter / Owner (Direct) --</option>
                                <!-- Populated dynamically from API -->
                            </select>
                        </div>
                    </div>

                    <!-- Notes / Reference -->
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-sm">Notes / Reference</label>
                        <input type="text" class="form-control rounded-3" id="payInputNotes" 
                               placeholder="e.g. Paid cash at counter, or online transaction ID">
                    </div>

                    <!-- Remaining Balance Live Preview -->
                    <div class="p-2 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                        <span class="fs-xs text-muted">Remaining Balance After Payment:</span>
                        <b class="fs-5 text-dark" id="payNewBalancePreview">Rs. 0.00</b>
                    </div>
                </div>

                <div class="modal-footer border-top py-3 px-4 bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-success rounded-pill px-4 fw-bold" id="btnSavePaymentOnly">
                        <i class="fas fa-save me-1"></i> Save Payment Only
                    </button>
                    <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" id="btnSavePaymentAndPrint">
                        <i class="fas fa-print me-1"></i> Save & Print 80mm Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ==========================================================================
     MODAL 4: VIEW BILL ITEM DETAILS
     ========================================================================== -->
<div class="modal fade" id="modalBillDetails" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="billDetailsTitle">
                    <i class="fas fa-receipt text-danger me-2"></i>Credit Bill Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="billDetailsContent">
                <!-- Dynamically injected -->
            </div>
            <div class="modal-footer border-top py-3 px-4">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-dark rounded-pill px-4 fw-bold" id="btnPrintBillFromModal">
                    <i class="fas fa-print me-1"></i> Print 80mm Slip
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ==========================================================================
     MODAL 5: VOID TRANSACTION CONFIRMATION
     ========================================================================== -->
<?php if ($canVoid): ?>
<div class="modal fade" id="modalVoidTx" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4 text-center d-block">
                <div class="mb-2 text-danger">
                    <i class="fas fa-triangle-exclamation fa-3x"></i>
                </div>
                <h5 class="modal-title fw-bold text-danger">Confirm Transaction Void</h5>
                <p class="text-muted fs-xs mt-1">Voiding will reverse customer balance and return any deducted stock.</p>
            </div>
            <form id="formVoidTx">
                <input type="hidden" id="voidTxType" value="">
                <input type="hidden" id="voidTxId" value="">
                <div class="modal-body px-4 py-2">
                    <label class="form-label fw-bold fs-xs">Reason for Voiding: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3 form-control-sm" id="voidInputReason" 
                           placeholder="e.g. Entry mistake or customer returned goods" required>
                </div>
                <div class="modal-footer border-0 pt-2 pb-4 px-4 justify-content-center">
                    <button type="button" class="btn btn-light rounded-pill px-3 btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 btn-sm fw-bold" id="btnConfirmVoid">
                        Yes, Void Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- ==========================================================================
     JAVASCRIPT LOGIC & INTERACTIONS
     ========================================================================== -->
<script>
$(document).ready(function() {
    'use strict';

    // Current State
    let customersList = [];
    let currentFilter = 'all';     // 'all', 'pending', 'cleared'
    let currentType = '';          // '', 'store', 'neighbor', 'friend', 'general'
    let searchQuery = '';
    let activeViewMode = 'cards';   // 'cards', 'table'
    let activeCustomerId = <?php echo $preselectedCustomerId; ?>;
    let currentLedgerData = null;
    let lastLoadedBillForModal = null;
    let allCustomersMaster = [];

    // Permissions
    const canVoidTx = <?php echo $canVoid ? 'true' : 'false'; ?>;
    const currentCashierName = '<?php echo addslashes($currentUsername); ?>';

    // -------------------------------------------------------------------------
    // FAIL-PROOF BOOTSTRAP 5 MODAL CONTROLLERS
    // -------------------------------------------------------------------------
    function showKhataModal(modalId) {
        const rawId = modalId.replace('#', '');
        const el = document.getElementById(rawId);
        if (!el) {
            console.warn('Modal element not found: ' + modalId);
            return;
        }
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                const inst = bootstrap.Modal.getOrCreateInstance(el);
                inst.show();
                return;
            } catch(e) {
                console.warn('bootstrap.Modal.show error:', e);
            }
        }
        if (window.jQuery && typeof $(el).modal === 'function') {
            $(el).modal('show');
        }
    }
    window.showKhataModal = showKhataModal;

    function hideKhataModal(modalId) {
        const rawId = modalId.replace('#', '');
        const el = document.getElementById(rawId);
        if (!el) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                const inst = bootstrap.Modal.getInstance(el);
                if (inst) inst.hide();
                return;
            } catch(e) {}
        }
        if (window.jQuery && typeof $(el).modal === 'function') {
            $(el).modal('hide');
        }
    }
    window.hideKhataModal = hideKhataModal;

    // -------------------------------------------------------------------------
    // 1. INITIALIZATION & REFRESH
    // -------------------------------------------------------------------------
    function init() {
        loadKPIs();
        loadAllCustomersForSelects();
        if (activeCustomerId > 0) {
            loadCustomerPassbook(activeCustomerId);
        } else {
            loadCustomersList();
        }
    }

    // Refresh Button Handler
    $('#btnRefreshKhata').on('click', function() {
        const $icon = $('#refreshIcon');
        $icon.addClass('spin-anim');
        loadKPIs();
        loadAllCustomersForSelects();
        if ($('#viewCustomerPassbook').is(':visible') && activeCustomerId > 0) {
            loadCustomerPassbook(activeCustomerId, function() {
                $icon.removeClass('spin-anim');
                showToast('Khata ledger refreshed successfully.', 'info');
            });
        } else {
            loadCustomersList(function() {
                $icon.removeClass('spin-anim');
                showToast('All accounts updated.', 'info');
            });
        }
    });

    // -------------------------------------------------------------------------
    // 2. LOAD SUMMARY KPIS
    // -------------------------------------------------------------------------
    function loadKPIs() {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/khata.php?action=summary_kpi',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#kpiTotalReceivables').text('Rs. ' + formatNumber(res.total_receivables));
                    $('#kpiTodayCredit').text('Rs. ' + formatNumber(res.today_credit));
                    $('#kpiTodayRecovery').text('Rs. ' + formatNumber(res.today_recovery));
                    $('#kpiTotalCustomers').text(res.total_customers);
                    $('#kpiDebtorsCount').html(
                        '<span class="badge bg-danger-subtle text-danger fw-bold rounded-pill px-2">' + 
                        res.debtors_count + ' debtors</span> with pending balance'
                    );
                }
            },
            error: function(err) {
                console.error('KPI error:', err);
            }
        });
    }

    // -------------------------------------------------------------------------
    // 3. LOAD CUSTOMERS LIST
    // -------------------------------------------------------------------------
    function loadCustomersList(callback) {
        $('#customersLoadingState').removeClass('d-none');
        $('#customersEmptyState').addClass('d-none');
        $('#customersCardsContainer').empty();
        $('#customersTableBody').empty();

        let url = (window.BASE_URL || '') + '/api/khata.php?action=list_customers';
        if (currentFilter !== 'all') url += '&filter=' + encodeURIComponent(currentFilter);
        if (currentType !== '') url += '&customer_type=' + encodeURIComponent(currentType);
        if (searchQuery.trim() !== '') url += '&search=' + encodeURIComponent(searchQuery.trim());

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                $('#customersLoadingState').addClass('d-none');
                if (res.success) {
                    customersList = res.customers || [];
                    renderCustomerDirectory();
                } else {
                    showToast(res.message || 'Could not load customer accounts.', 'danger');
                }
                if (typeof callback === 'function') callback();
            },
            error: function(xhr) {
                $('#customersLoadingState').addClass('d-none');
                showToast('Unable to connect to server.', 'danger');
                if (typeof callback === 'function') callback();
            }
        });
    }

    // Load master customer list for modals
    function loadAllCustomersForSelects(callback) {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/khata.php?action=list_customers',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.customers) {
                    allCustomersMaster = res.customers;
                    populateCustomerSelects(allCustomersMaster);
                }
                if (typeof callback === 'function') callback();
            }
        });
    }

    // Populate dropdowns in modals
    function populateCustomerSelects(list) {
        const $saleSelect = $('#saleSelectCustomer');
        const $paySelect = $('#paySelectCustomer');
        
        const currentSaleVal = $saleSelect.val();
        const currentPayVal = $paySelect.val();

        $saleSelect.find('option:not(:first)').remove();
        $paySelect.find('option:not(:first)').remove();

        list.forEach(c => {
            const optText = `${c.name} (${getTypeLabel(c.customer_type)}) - Due: Rs. ${formatNumber(c.current_balance)}`;
            $saleSelect.append(`<option value="${c.id}" data-balance="${c.current_balance}" data-limit="${c.credit_limit}">${optText}</option>`);
            $paySelect.append(`<option value="${c.id}" data-balance="${c.current_balance}">${optText}</option>`);
        });

        if (currentSaleVal) $saleSelect.val(currentSaleVal);
        if (currentPayVal) $paySelect.val(currentPayVal);
    }

    // -------------------------------------------------------------------------
    // 4. RENDER CUSTOMER DIRECTORY (CARDS & TABLE)
    // -------------------------------------------------------------------------
    function renderCustomerDirectory() {
        if (!customersList || customersList.length === 0) {
            $('#customersEmptyState').removeClass('d-none');
            $('#customersCardsContainer').addClass('d-none');
            $('#customersTableContainer').addClass('d-none');
            return;
        }

        $('#customersEmptyState').addClass('d-none');

        if (activeViewMode === 'cards') {
            $('#customersCardsContainer').removeClass('d-none').empty();
            $('#customersTableContainer').addClass('d-none');

            customersList.forEach(c => {
                const cardHtml = buildCustomerCardHtml(c);
                $('#customersCardsContainer').append(cardHtml);
            });
        } else {
            $('#customersCardsContainer').addClass('d-none');
            $('#customersTableContainer').removeClass('d-none');
            const $tbody = $('#customersTableBody').empty();

            customersList.forEach(c => {
                const rowHtml = buildCustomerTableRowHtml(c);
                $tbody.append(rowHtml);
            });
        }
    }

    function buildCustomerCardHtml(c) {
        const bal = parseFloat(c.current_balance) || 0;
        let balBadge = '';
        if (bal > 0) {
            balBadge = `<div class="balance-badge-debit"><i class="fas fa-triangle-exclamation me-1"></i>Balance Due: Rs. ${formatNumber(bal)}</div>`;
        } else if (bal < 0) {
            balBadge = `<div class="balance-badge-advance">Advance: Rs. ${formatNumber(Math.abs(bal))}</div>`;
        } else {
            balBadge = `<div class="balance-badge-settled"><i class="fas fa-check-circle me-1"></i>All Settled (Rs. 0)</div>`;
        }

        const typeBadge = getTypeBadgeHtml(c.customer_type);
        const avatarLetter = (c.name || 'C').trim().charAt(0).toUpperCase();

        // Recent Goods Taken Info
        let lastGoodsHtml = '';
        if (c.last_bill) {
            lastGoodsHtml = `
                <div class="p-2 rounded-3 bg-light border mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fs-xs fw-bold text-danger"><i class="fas fa-cart-shopping me-1"></i>Last Saman Taken:</span>
                        <span class="fs-xs fw-bolder text-danger">Rs. ${formatNumber(c.last_bill.amount)}</span>
                    </div>
                    <div class="fs-xs text-dark fw-semibold text-truncate" title="${escapeHtml(c.last_bill.items_summary || '')}">
                        📦 ${escapeHtml(c.last_bill.items_summary || 'Goods recorded')}
                    </div>
                    <div class="fs-xs text-muted mt-1">
                        📅 ${formatDate(c.last_bill.bill_date)} <span class="ms-1">${formatTime(c.last_bill.created_at)}</span>
                    </div>
                </div>
            `;
        } else {
            lastGoodsHtml = `
                <div class="p-2 rounded-3 bg-light border mb-2 text-muted fs-xs">
                    <i class="fas fa-box-open me-1"></i>No credit goods taken yet
                </div>
            `;
        }

        // Recent Payment Info
        let lastPayHtml = '';
        if (c.last_payment) {
            lastPayHtml = `
                <div class="p-2 rounded-3 bg-success-subtle border border-success-subtle mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fs-xs fw-bold text-success"><i class="fas fa-hand-holding-dollar me-1"></i>Last Vasooli Paid:</span>
                        <span class="fs-xs fw-bolder text-success">Rs. ${formatNumber(c.last_payment.amount)}</span>
                    </div>
                    <div class="fs-xs text-dark">
                        💳 ${getPaymentMethodLabel(c.last_payment.method)} • 📅 ${formatDate(c.last_payment.payment_date)}
                    </div>
                </div>
            `;
        } else {
            lastPayHtml = `
                <div class="p-2 rounded-3 bg-light border mb-2 text-muted fs-xs">
                    <i class="fas fa-money-bill me-1"></i>No recovery payments made yet
                </div>
            `;
        }

        return `
        <div class="col-12 col-md-6 col-lg-4">
            <div class="customer-khata-card p-3 h-100 d-flex flex-column justify-content-between" data-id="${c.id}">
                <div>
                    <!-- Header: Avatar, Name & Type -->
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="customer-avatar-box">
                                ${avatarLetter}
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">${escapeHtml(c.name)}</h5>
                                <div class="mt-1">${typeBadge}</div>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-light rounded-circle text-muted btn-edit-customer" data-id="${c.id}" onclick="openEditCustomerModal(${c.id}); event.stopPropagation();" title="Edit Customer">
                            <i class="fas fa-pen-to-square"></i>
                        </button>
                    </div>

                    <!-- Contact & Address -->
                    <div class="fs-xs text-muted mb-2 d-flex flex-wrap gap-2">
                        ${c.phone ? `
                            <a href="tel:${escapeHtml(c.phone)}" class="text-decoration-none text-muted" onclick="event.stopPropagation();">
                                <i class="fas fa-phone text-success me-1"></i>${escapeHtml(c.phone)}
                            </a>
                            <a href="https://wa.me/${cleanPhone(c.phone)}" target="_blank" class="text-success text-decoration-none" onclick="event.stopPropagation();" title="Chat on WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        ` : '<span class="text-black-50">No phone provided</span>'}
                        ${c.address ? `<span class="border-start ps-2"><i class="fas fa-location-dot text-danger me-1"></i>${escapeHtml(c.address)}</span>` : ''}
                    </div>

                    <!-- Balance Highlight -->
                    <div class="d-flex align-items-center justify-content-between py-2 border-top border-bottom mb-2">
                        <span class="fs-xs text-muted fw-bold">Current Status:</span>
                        ${balBadge}
                    </div>

                    <!-- Last Transactions Micro Preview -->
                    ${lastGoodsHtml}
                    ${lastPayHtml}

                    <!-- Lifetime Micro Stats -->
                    <div class="d-flex justify-content-between text-muted fs-xs mb-2 pt-1 border-top">
                        <span>Total Credit: <b class="text-dark">Rs. ${formatNumber(c.total_credit)}</b></span>
                        <span>Total Paid: <b class="text-dark">Rs. ${formatNumber(c.total_paid)}</b></span>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-dark flex-grow-1 rounded-pill fw-bold btn-view-passbook" data-id="${c.id}" onclick="openCustomerPassbook(${c.id}); event.stopPropagation();">
                        <i class="fas fa-book-open me-1"></i> Passbook
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill fw-bold btn-card-give-credit" data-id="${c.id}" onclick="openCreditSaleModalForCustomer(${c.id}); event.stopPropagation();" title="Credit Sale">
                        <i class="fas fa-plus me-1"></i> Credit
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill fw-bold btn-card-take-recovery" data-id="${c.id}" onclick="openRecoveryModalForCustomer(${c.id}); event.stopPropagation();" title="Receive Payment">
                        <i class="fas fa-money-bill me-1"></i> Receive
                    </button>
                </div>
            </div>
        </div>`;
    }

    function buildCustomerTableRowHtml(c) {
        const bal = parseFloat(c.current_balance) || 0;
        let balClass = bal > 0 ? 'text-danger fw-bolder' : (bal < 0 ? 'text-primary fw-bolder' : 'text-success fw-bold');
        let balText = bal > 0 ? `Rs. ${formatNumber(bal)}` : (bal < 0 ? `Rs. ${formatNumber(Math.abs(bal))} (Adv)` : 'Settled (0)');

        let lastBillStr = '<span class="text-muted fs-xs">None</span>';
        if (c.last_bill) {
            lastBillStr = `
                <div><b class="text-danger">Rs. ${formatNumber(c.last_bill.amount)}</b> <small class="text-muted">(${formatDate(c.last_bill.bill_date)})</small></div>
                <div class="fs-xs text-muted text-truncate" style="max-width: 170px;" title="${escapeHtml(c.last_bill.items_summary || '')}">📦 ${escapeHtml(c.last_bill.items_summary || '')}</div>
            `;
        }

        let lastPayStr = '<span class="text-muted fs-xs">None</span>';
        if (c.last_payment) {
            lastPayStr = `
                <div><b class="text-success">Rs. ${formatNumber(c.last_payment.amount)}</b> <small class="text-muted">(${formatDate(c.last_payment.payment_date)})</small></div>
                <div class="fs-xs text-muted">${getPaymentMethodLabel(c.last_payment.method)}</div>
            `;
        }

        return `
        <tr data-id="${c.id}" class="customer-table-row" style="cursor: pointer;">
            <td class="ps-3 py-3">
                <div class="fw-bold text-dark">${escapeHtml(c.name)}</div>
                ${c.notes ? `<div class="fs-xs text-muted">${escapeHtml(c.notes)}</div>` : ''}
            </td>
            <td>${getTypeBadgeHtml(c.customer_type)}</td>
            <td>
                <div>
                    ${c.phone ? `
                        <span class="fs-sm">${escapeHtml(c.phone)}</span>
                        <a href="https://wa.me/${cleanPhone(c.phone)}" target="_blank" class="text-success ms-1" onclick="event.stopPropagation();">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    ` : '<span class="text-muted fs-xs">-</span>'}
                </div>
                ${c.address ? `<div class="fs-xs text-muted text-truncate" style="max-width: 150px;">${escapeHtml(c.address)}</div>` : ''}
            </td>
            <td>${lastBillStr}</td>
            <td>${lastPayStr}</td>
            <td class="text-end fw-bold text-dark">Rs. ${formatNumber(c.total_credit)}</td>
            <td class="text-end fw-bold text-success">Rs. ${formatNumber(c.total_paid)}</td>
            <td class="text-end ${balClass} fs-6">${balText}</td>
            <td class="text-center pe-3">
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-dark btn-view-passbook" data-id="${c.id}" onclick="openCustomerPassbook(${c.id}); event.stopPropagation();" title="Open Passbook">
                        <i class="fas fa-book-open"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-card-give-credit" data-id="${c.id}" onclick="openCreditSaleModalForCustomer(${c.id}); event.stopPropagation();" title="Record Credit Sale">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-outline-success btn-card-take-recovery" data-id="${c.id}" onclick="openRecoveryModalForCustomer(${c.id}); event.stopPropagation();" title="Receive Payment">
                        <i class="fas fa-money-bill"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-edit-customer" data-id="${c.id}" onclick="openEditCustomerModal(${c.id}); event.stopPropagation();" title="Edit Customer">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }

    // -------------------------------------------------------------------------
    // 5. VIEW TOGGLES & SEARCH FILTERS
    // -------------------------------------------------------------------------
    // Filter Pills (All / Pending / Cleared)
    $('.khata-filter-pill:not(.type-pill):not(.pb-period-pill)').on('click', function() {
        $('.khata-filter-pill:not(.type-pill):not(.pb-period-pill)').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        loadCustomersList();
    });

    // Customer Type Pills
    $('.type-pill').on('click', function() {
        $('.type-pill').removeClass('active');
        $(this).addClass('active');
        currentType = $(this).data('type');
        loadCustomersList();
    });

    // Real-time Search Input with Debounce
    let searchDebounceTimer;
    $('#searchCustomerInput').on('input', function() {
        const val = $(this).val();
        searchQuery = val;
        $('#btnClearSearch').toggleClass('d-none', val.length === 0);

        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function() {
            loadCustomersList();
        }, 300);
    });

    $('#btnClearSearch').on('click', function() {
        $('#searchCustomerInput').val('').trigger('input');
    });

    // View Toggle Buttons (Cards vs Table)
    $('#btnViewCards').on('click', function() {
        activeViewMode = 'cards';
        $('#btnViewCards').addClass('active');
        $('#btnViewTable').removeClass('active');
        renderCustomerDirectory();
    });

    $('#btnViewTable').on('click', function() {
        activeViewMode = 'table';
        $('#btnViewTable').addClass('active');
        $('#btnViewCards').removeClass('active');
        renderCustomerDirectory();
    });

    // Click anywhere on Card / Table Row opens Passbook
    $(document).on('click', '.customer-khata-card, .customer-table-row', function(e) {
        if ($(e.target).closest('button, a, input').length === 0) {
            const custId = $(this).data('id');
            openCustomerPassbook(custId);
        }
    });

    $(document).on('click', '.btn-view-passbook', function(e) {
        e.stopPropagation();
        const custId = $(this).data('id');
        openCustomerPassbook(custId);
    });

    // -------------------------------------------------------------------------
    // 6. CUSTOMER PASSBOOK & LEDGER (VIEW 2)
    // -------------------------------------------------------------------------
    function openCustomerPassbook(customerId) {
        activeCustomerId = customerId;
        $('#khataDirectoryKpiBar').addClass('d-none');
        $('#viewCustomerDirectory').addClass('d-none');
        $('#viewCustomerPassbook').removeClass('d-none');
        window.scrollTo(0, 0);

        // Update URL state without page reload
        const newUrl = window.location.pathname + '?customer_id=' + customerId;
        window.history.pushState({ customer_id: customerId }, '', newUrl);

        loadCustomerPassbook(customerId);
    }
    window.openCustomerPassbook = openCustomerPassbook;

    $('#btnBackToDirectory').on('click', function() {
        activeCustomerId = 0;
        $('#viewCustomerPassbook').addClass('d-none');
        $('#khataDirectoryKpiBar').removeClass('d-none');
        $('#viewCustomerDirectory').removeClass('d-none');

        const cleanUrl = window.location.pathname;
        window.history.pushState({}, '', cleanUrl);
        window.scrollTo(0, 0);

        loadKPIs();
        loadAllCustomersForSelects();
        loadCustomersList();
    });

    // Passbook Period Filter Pills
    $('.pb-period-pill').on('click', function() {
        $('.pb-period-pill').removeClass('active');
        $(this).addClass('active');
        const period = $(this).data('period');
        $('#pbFilterStartDate').val('');
        $('#pbFilterEndDate').val('');
        loadCustomerPassbook(activeCustomerId, null, period);
    });

    $('#btnApplyPbCustomDate').on('click', function() {
        const s = $('#pbFilterStartDate').val();
        const e = $('#pbFilterEndDate').val();
        if (!s || !e) {
            showToast('Please select both From and To dates.', 'warning');
            return;
        }
        $('.pb-period-pill').removeClass('active');
        loadCustomerPassbook(activeCustomerId, null, 'custom', s, e);
    });

    function loadCustomerPassbook(customerId, callback, period = 'all', startDate = '', endDate = '') {
        let url = `${(window.BASE_URL || "")}/api/khata.php?action=customer_ledger&customer_id=${customerId}&period=${period}`;
        if (startDate && endDate) {
            url += `&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
        }

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    currentLedgerData = res;
                    renderPassbookHeader(res.customer);
                    renderPassbookSummary(res);
                    renderPassbookTransactions(res.transactions);
                    renderPassbookGoodsTab(res.bills || []);
                    renderPassbookPaymentsTab(res.payments || []);
                    renderPassbookPendingTab(res);
                } else {
                    showToast(res.message || 'Could not load customer ledger.', 'danger');
                }
                if (typeof callback === 'function') callback();
            },
            error: function() {
                showToast('Unable to connect to server.', 'danger');
                if (typeof callback === 'function') callback();
            }
        });
    }

    function renderPassbookHeader(c) {
        $('#pbCustomerName').text(c.name);
        $('#pbAvatar').text((c.name || 'C').charAt(0).toUpperCase());
        $('#pbCustomerTypeBadge').html(getTypeBadgeHtml(c.customer_type));
        $('#pbCustomerPhone').text(c.phone || 'No phone registered');
        $('#pbCustomerAddress').text(c.address || 'No address provided');
        $('#pbCreditLimit').text(c.credit_limit > 0 ? 'Rs. ' + formatNumber(c.credit_limit) : 'Unlimited');
        $('#pbCustomerNotes').text(c.notes ? 'Notes: ' + c.notes : '');

        $('#pbLifetimeCredit').text('Rs. ' + formatNumber(c.lifetime_credit));
        $('#pbLifetimePaid').text('Rs. ' + formatNumber(c.lifetime_paid));

        const netBal = parseFloat(c.lifetime_balance) || 0;
        if (netBal > 0) {
            $('#pbCurrentBalance').html(`<span class="text-danger">Rs. ${formatNumber(netBal)} (Due)</span>`);
        } else if (netBal < 0) {
            $('#pbCurrentBalance').html(`<span class="text-primary">Rs. ${formatNumber(Math.abs(netBal))} (Advance)</span>`);
        } else {
            $('#pbCurrentBalance').html(`<span class="text-success"><i class="fas fa-check-circle me-1"></i>Settled (Rs. 0)</span>`);
        }
    }

    function renderPassbookSummary(data) {
        $('#pbPeriodOpening').text('Rs. ' + formatNumber(data.opening_balance));
        $('#pbPeriodDebit').text('Rs. ' + formatNumber(data.period_debit));
        $('#pbPeriodCredit').text('Rs. ' + formatNumber(data.period_credit));
        $('#pbPeriodClosing').text('Rs. ' + formatNumber(data.closing_balance));
    }

    function renderPassbookTransactions(txList) {
        const $tbody = $('#pbLedgerTableBody').empty();

        if (!txList || txList.length === 0) {
            $('#pbEmptyLedger').removeClass('d-none');
            return;
        }
        $('#pbEmptyLedger').addClass('d-none');

        txList.forEach(tx => {
            const isBill = tx.tx_type === 'bill';
            const rowClass = isBill ? 'ledger-row-bill' : 'ledger-row-pay';

            // 1. Transaction Details (Saman / Payment)
            let detailsHtml = '';
            if (isBill) {
                const items = tx.items || [];
                const itemsCount = items.length;
                let itemsSummary = tx.items_summary || (itemsCount + ' item(s)');

                detailsHtml = `
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5 fs-xxs fw-bold">
                                <i class="fas fa-cart-shopping me-1"></i>Credit Bill
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-dark py-0 px-2 fw-bold fs-xs btn-view-bill-breakdown" data-id="${tx.id}" title="Click to view full bill details">
                                <i class="fas fa-file-invoice text-danger me-1"></i>${escapeHtml(tx.ref_no)}
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-xs py-0 px-2 rounded-pill fs-xxs btn-view-bill-breakdown" data-id="${tx.id}">
                                <i class="fas fa-search me-1"></i>View Items (${itemsCount})
                            </button>
                        </div>
                        <div class="text-dark fs-xs">
                            <i class="fas fa-boxes-stacked me-1 text-danger"></i><b>Saman:</b> ${escapeHtml(itemsSummary)}
                        </div>
                        ${tx.notes ? `<div class="text-muted fs-xxs fst-italic mt-0.5"><i class="fas fa-comment-dots me-1"></i>${escapeHtml(tx.notes)}</div>` : ''}
                    </div>
                `;
            } else {
                detailsHtml = `
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 fs-xxs fw-bold">
                                <i class="fas fa-hand-holding-dollar me-1"></i>Payment Received
                            </span>
                            <code class="fw-bold text-dark fs-xs">${escapeHtml(tx.ref_no)}</code>
                            <span class="badge bg-light text-dark border fs-xxs"><i class="fas fa-money-bill-wave text-success me-1"></i>${getPaymentMethodLabel(tx.payment_method)}</span>
                        <div class="text-muted fs-xs">
                            <i class="fas fa-user-check me-1 text-success"></i>Received by: <b>${escapeHtml(tx.received_by_employee_name ? tx.received_by_employee_name + ' (Staff)' : (tx.created_by_name || 'Counter'))}</b>
                        </div>
                        ${tx.notes ? `<div class="text-muted fs-xxs fst-italic mt-0.5"><i class="fas fa-comment-dots me-1"></i>${escapeHtml(tx.notes)}</div>` : ''}
                    </div>
                `;
            }

            // 2. Bill Amount (Bill kitne ka bana)
            const billAmount = parseFloat(tx.bill_amount) || 0;
            const billAmountHtml = isBill 
                ? `<span class="fw-bolder fs-6 text-dark">Rs. ${formatNumber(billAmount)}</span>`
                : `<span class="text-muted fs-6">-</span>`;

            // 3. Paid Amount (Kitna us time diya)
            const paidAmount = parseFloat(tx.paid_amount) || 0;
            const paidAmountHtml = paidAmount > 0 
                ? `<span class="fw-bold fs-6 text-success"><i class="fas fa-check-circle me-1 fs-xxs"></i>Rs. ${formatNumber(paidAmount)}</span>`
                : `<span class="text-muted fs-xs">Rs. 0.00</span>`;

            // 4. Net Udhar / Change (Kitna bakaya raha iska)
            const netChange = parseFloat(tx.net_change) || 0;
            let netChangeHtml = '';
            if (isBill) {
                if (netChange > 0) {
                    netChangeHtml = `<span class="fw-bolder fs-6 text-danger">+ Rs. ${formatNumber(netChange)}</span>`;
                } else {
                    netChangeHtml = `<span class="fw-bold fs-xs text-success"><i class="fas fa-check me-1"></i>Paid Full</span>`;
                }
            } else {
                netChangeHtml = `<span class="fw-bolder fs-6 text-success">- Rs. ${formatNumber(Math.abs(netChange))}</span>`;
            }

            // 5. Total Balance (Abhi total ispe kitna ho gaya)
            const balAfter = parseFloat(tx.balance_after) || 0;
            let balAfterHtml = '';
            if (balAfter > 0) {
                balAfterHtml = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 fw-bolder px-2.5 py-1">Rs. ${formatNumber(balAfter)}</span>`;
            } else if (balAfter < 0) {
                balAfterHtml = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 fw-bold px-2.5 py-1">Rs. ${formatNumber(Math.abs(balAfter))} (Adv)</span>`;
            } else {
                balAfterHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle fs-6 fw-bold px-2.5 py-1"><i class="fas fa-check-circle me-1"></i>Rs. 0.00 (Cleared)</span>`;
            }

            const trHtml = `
            <tr class="${rowClass}">
                <td class="ps-3 py-3">
                    <div class="fw-bold fs-xs text-dark">${formatDate(tx.tx_date)}</div>
                    <div class="text-muted fs-xs">${formatTime(tx.created_at)}</div>
                </td>
                <td>${detailsHtml}</td>
                <td class="text-end">${billAmountHtml}</td>
                <td class="text-end">${paidAmountHtml}</td>
                <td class="text-end">${netChangeHtml}</td>
                <td class="text-end">${balAfterHtml}</td>
                <td class="text-center pe-3">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-dark btn-print-single-tx" data-type="${tx.tx_type}" data-id="${tx.id}" title="Print Slip">
                            <i class="fas fa-print"></i>
                        </button>
                        ${canVoidTx ? `
                            <button type="button" class="btn btn-outline-danger btn-open-void-modal" data-type="${tx.tx_type}" data-id="${tx.id}" data-ref="${escapeHtml(tx.ref_no)}" title="Void Entry">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>`;

            $tbody.append(trHtml);
        });
    }

    // -------------------------------------------------------------------------
    // TAB 2: GOODS TAKEN (SAMAN KI TAFSEEL)
    // -------------------------------------------------------------------------
    function renderPassbookGoodsTab(bills) {
        const $container = $('#pbGoodsContainer').empty();
        const activeBills = (bills || []).filter(b => b.status === 'active');
        
        $('#pbGoodsCountBadge').text(activeBills.length);
        const totalGoodsVal = activeBills.reduce((acc, b) => acc + (parseFloat(b.total_amount) || parseFloat(b.debit) || 0), 0);
        $('#pbGoodsTotalVal').text('Rs. ' + formatNumber(totalGoodsVal));

        if (activeBills.length === 0) {
            $('#pbEmptyGoods').removeClass('d-none');
            return;
        }
        $('#pbEmptyGoods').addClass('d-none');

        activeBills.forEach(b => {
            const billAmount = parseFloat(b.total_amount) || parseFloat(b.debit) || 0;
            const downPay = parseFloat(b.down_payment) || 0;
            const netCredit = (parseFloat(b.debit) > 0) ? parseFloat(b.debit) : Math.max(0, billAmount - downPay);
            const items = b.items || [];
            let itemsRowsHtml = '';

            items.forEach((it, idx) => {
                const rowTotal = parseFloat(it.total_price) || (parseFloat(it.quantity) * parseFloat(it.unit_price));
                const barcodeBadge = it.barcode ? `<span class="badge bg-light text-muted border me-1"><code>${escapeHtml(it.barcode)}</code></span>` : '';
                itemsRowsHtml += `
                    <tr>
                        <td class="ps-3 text-muted">${idx + 1}</td>
                        <td class="fw-bold text-dark">${barcodeBadge}${escapeHtml(it.item_name)}</td>
                        <td class="text-center"><span class="badge bg-secondary-subtle text-dark px-2">${it.quantity}</span></td>
                        <td class="text-end">Rs. ${formatNumber(it.unit_price)}</td>
                        <td class="text-end pe-3 fw-bold text-dark">Rs. ${formatNumber(rowTotal)}</td>
                    </tr>
                `;
            });

            const cardHtml = `
            <div class="card card-goods-bill mb-3 shadow-xs">
                <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center py-2 px-3 gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-danger rounded-pill"><i class="fas fa-cart-shopping me-1"></i>Credit Bill</span>
                        <code class="fw-bold text-dark">${escapeHtml(b.bill_number || b.ref_no)}</code>
                        <span class="text-muted fs-xs">• 📅 <b>${formatDate(b.bill_date || b.tx_date)}</b> ${formatTime(b.created_at)}</span>
                        <span class="badge bg-light text-muted border">Cashier: ${escapeHtml(b.created_by_name || 'Staff')}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-xs text-muted">Bill Amount:</span>
                        <span class="fs-6 fw-bolder text-danger">Rs. ${formatNumber(billAmount)}</span>
                        ${downPay > 0 ? `<span class="badge bg-success-subtle text-success border border-success-subtle py-1"><i class="fas fa-hand-holding-dollar me-1"></i>Advance: Rs. ${formatNumber(downPay)}</span>` : ''}
                        <button class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 btn-print-single-tx" data-type="bill" data-id="${b.id}" title="Print 80mm Receipt">
                            <i class="fas fa-print me-1"></i>Print Slip
                        </button>
                        ${canVoidTx ? `
                            <button class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 btn-open-void-modal" data-type="bill" data-id="${b.id}" data-ref="${escapeHtml(b.bill_number || b.ref_no)}" title="Void Bill">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="ps-3" style="width: 40px;">#</th>
                                    <th>Item / Product Name</th>
                                    <th class="text-center" style="width: 130px;">Quantity Taken</th>
                                    <th class="text-end" style="width: 140px;">Unit Price</th>
                                    <th class="text-end pe-3" style="width: 150px;">Total Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsRowsHtml || '<tr><td colspan="5" class="text-center py-2 text-muted">No individual items recorded</td></tr>'}
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end py-2">Total Items Bill:</th>
                                    <th class="text-end pe-3 py-2 text-dark fw-bold fs-6">Rs. ${formatNumber(billAmount)}</th>
                                </tr>
                                ${downPay > 0 ? `
                                <tr>
                                    <th colspan="4" class="text-end py-1 text-success"><i class="fas fa-hand-holding-dollar me-1"></i>Down Payment (Cash Paid):</th>
                                    <th class="text-end pe-3 py-1 text-success fw-bold">- Rs. ${formatNumber(downPay)}</th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end py-2 text-danger"><i class="fas fa-book-open me-1"></i>Net Added to Khata Balance:</th>
                                    <th class="text-end pe-3 py-2 text-danger fw-bolder fs-6">+ Rs. ${formatNumber(netCredit)}</th>
                                </tr>
                                ` : ''}
                            </tfoot>
                        </table>
                    </div>
                </div>
                ${b.notes ? `<div class="card-footer bg-white text-muted fs-xs py-2 px-3 fst-italic"><i class="fas fa-info-circle me-1"></i>Note: ${escapeHtml(b.notes)}</div>` : ''}
            </div>`;

            $container.append(cardHtml);
        });
    }

    // -------------------------------------------------------------------------
    // TAB 3: PAYMENTS MADE (VASOOLI HISTORY)
    // -------------------------------------------------------------------------
    function renderPassbookPaymentsTab(payments) {
        const $tbody = $('#pbPaymentsTableBody').empty();
        const activePays = (payments || []).filter(p => p.status === 'active');

        $('#pbPaymentsCountBadge').text(activePays.length);
        const totalRecovered = activePays.reduce((acc, p) => acc + (parseFloat(p.amount) || parseFloat(p.credit) || 0), 0);
        $('#pbPaymentsTotalVal').text('Rs. ' + formatNumber(totalRecovered));

        if (activePays.length === 0) {
            $('#pbEmptyPayments').removeClass('d-none');
            return;
        }
        $('#pbEmptyPayments').addClass('d-none');

        activePays.forEach((p) => {
            const payAmount = parseFloat(p.amount) || parseFloat(p.credit) || 0;
            const trHtml = `
                <tr>
                    <td class="ps-3 py-3">
                        <div class="fw-bold fs-xs text-dark">${formatDate(p.payment_date || p.tx_date)}</div>
                        <div class="text-muted fs-xs">${formatTime(p.created_at)}</div>
                    </td>
                    <td><code class="fw-bold text-dark fs-xs">${escapeHtml(p.receipt_number || p.ref_no)}</code></td>
                    <td>
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">
                            <i class="fas fa-wallet me-1"></i>${getPaymentMethodLabel(p.payment_method)}
                        </span>
                    </td>
                    <td class="text-end fw-bolder fs-6 text-success">Rs. ${formatNumber(payAmount)}</td>
                    <td class="fs-xs text-muted">${escapeHtml(p.received_by_employee_name ? p.received_by_employee_name + ' (Staff)' : (p.created_by_name || 'Counter'))}</td>
                    <td class="fs-xs text-muted">${escapeHtml(p.notes || '-')}</td>
                    <td class="text-center pe-3">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-dark btn-print-single-tx" data-type="payment" data-id="${p.id}" title="Print Receipt">
                                <i class="fas fa-print"></i>
                            </button>
                            ${canVoidTx ? `
                                <button class="btn btn-outline-danger btn-open-void-modal" data-type="payment" data-id="${p.id}" data-ref="${escapeHtml(p.receipt_number || p.ref_no)}" title="Void Receipt">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
            $tbody.append(trHtml);
        });
    }

    // -------------------------------------------------------------------------
    // TAB 4: PENDING BALANCE & UNPAID BREAKDOWN (KIA REHTA HAI)
    // -------------------------------------------------------------------------
    function renderPassbookPendingTab(data) {
        const c = data.customer;
        const lifetimeCredit = parseFloat(c.lifetime_credit) || 0;
        const lifetimePaid = parseFloat(c.lifetime_paid) || 0;
        const netBal = parseFloat(c.lifetime_balance) || 0;

        $('#pbPendingTotalCredit').text('Rs. ' + formatNumber(lifetimeCredit));
        $('#pbPendingTotalPaid').text('Rs. ' + formatNumber(lifetimePaid));
        
        if (netBal > 0) {
            $('#pbPendingNetDue').html(`<span class="text-danger fw-bolder fs-4">Rs. ${formatNumber(netBal)} (Due / Baqaya)</span>`);
            $('#pbPendingStatusAlert').attr('class', 'alert alert-danger rounded-4 p-3 mb-4').html(`
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-circle-exclamation fs-2 text-danger"></i>
                    <div>
                        <h6 class="fw-bold mb-1 text-danger">Payment Outstanding (Baqaya Rehta Hai)</h6>
                        <div class="fs-sm text-dark">This customer currently owes a balance of <b>Rs. ${formatNumber(netBal)}</b>. Below are the credit bills and goods items taken that account for this amount.</div>
                    </div>
                </div>
            `);
        } else if (netBal < 0) {
            $('#pbPendingNetDue').html(`<span class="text-primary fw-bolder fs-4">Rs. ${formatNumber(Math.abs(netBal))} (Advance Paid)</span>`);
            $('#pbPendingStatusAlert').attr('class', 'alert alert-primary rounded-4 p-3 mb-4').html(`
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-circle-info fs-2 text-primary"></i>
                    <div>
                        <h6 class="fw-bold mb-1 text-primary">Advance Payment Available</h6>
                        <div class="fs-sm text-dark">This customer has paid an advance balance of <b>Rs. ${formatNumber(Math.abs(netBal))}</b>.</div>
                    </div>
                </div>
            `);
        } else {
            $('#pbPendingNetDue').html(`<span class="text-success fw-bolder fs-4">Rs. 0.00 (All Settled)</span>`);
            $('#pbPendingStatusAlert').attr('class', 'alert alert-success rounded-4 p-3 mb-4').html(`
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-circle-check fs-2 text-success"></i>
                    <div>
                        <h6 class="fw-bold mb-1 text-success">Khata Fully Settled</h6>
                        <div class="fs-sm text-dark">All credit purchases have been fully paid. No pending dues on this account.</div>
                    </div>
                </div>
            `);
        }

        const $container = $('#pbPendingBillsContainer').empty();
        const activeBills = (data.bills || []).filter(b => b.status === 'active');

        if (activeBills.length === 0 || netBal <= 0) {
            $container.html(`
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-check-double text-success mb-2" style="font-size: 2.5rem;"></i>
                    <h6 class="fw-bold text-dark">No Pending Credit Bills</h6>
                    <p class="fs-xs text-muted mb-0">Either all bills are fully settled or no credit purchases exist.</p>
                </div>
            `);
            return;
        }

        activeBills.forEach(b => {
            const billAmount = parseFloat(b.total_amount) || parseFloat(b.debit) || 0;
            const items = b.items || [];
            let itemsBadges = '';
            items.forEach(it => {
                itemsBadges += `<span class="badge bg-light text-dark border me-1 mb-1 p-2 fs-xs"><b>${escapeHtml(it.item_name)}</b> (${it.quantity} pcs @ Rs. ${formatNumber(it.unit_price)} = Rs. ${formatNumber(it.total_price)})</span>`;
            });

            $container.append(`
                <div class="p-3 border rounded-3 bg-white mb-2 shadow-xs">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div>
                            <span class="badge bg-danger me-1">Bill #${escapeHtml(b.bill_number || b.ref_no)}</span>
                            <span class="text-muted fs-xs">📅 Date: <b>${formatDate(b.bill_date || b.tx_date)}</b> ${formatTime(b.created_at)}</span>
                        </div>
                        <div>
                            <span class="fw-bolder text-danger fs-6">Bill Total: Rs. ${formatNumber(billAmount)}</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="fs-xs fw-bold text-muted mb-1">Items Taken in this Bill:</div>
                        <div class="d-flex flex-wrap">${itemsBadges}</div>
                    </div>
                    ${b.notes ? `<div class="fs-xs text-muted fst-italic"><i class="fas fa-info-circle me-1"></i>Notes: ${escapeHtml(b.notes)}</div>` : ''}
                </div>
            `);
        });
    }

    // Direct action buttons in Passbook banner
    $('#btnPassbookAddCredit').on('click', function() {
        openCreditSaleModalForCustomer(activeCustomerId);
    });

    $('#btnPassbookAddPayment').on('click', function() {
        openRecoveryModalForCustomer(activeCustomerId);
    });

    // -------------------------------------------------------------------------
    // 7. MODAL: ADD / EDIT CUSTOMER
    // -------------------------------------------------------------------------
    $('#btnOpenNewCustomerModal').on('click', function() {
        $('#modalCustomerTitle').html('<i class="fas fa-user-plus text-primary me-2"></i>Register New Customer Account');
        $('#formCustomer')[0].reset();
        $('#custInputId').val('');
        $('#custInputType').val('general');
        $('#custInputLimit').val('0');
        showKhataModal('#modalCustomer');
    });

    function openEditCustomerModal(custId) {
        const id = parseInt(custId, 10);
        let c = customersList.find(x => x.id == id);
        if (!c && allCustomersMaster && allCustomersMaster.length > 0) {
            c = allCustomersMaster.find(x => x.id == id);
        }
        if (!c) return;

        $('#modalCustomerTitle').html('<i class="fas fa-pen-to-square text-primary me-2"></i>Edit Customer Account');
        $('#custInputId').val(c.id);
        $('#custInputName').val(c.name);
        $('#custInputPhone').val(c.phone || '');
        $('#custInputType').val(c.customer_type || 'general');
        $('#custInputAddress').val(c.address || '');
        $('#custInputLimit').val(c.credit_limit || 0);
        $('#custInputNotes').val(c.notes || '');

        showKhataModal('#modalCustomer');
    }
    window.openEditCustomerModal = openEditCustomerModal;

    $(document).on('click', '.btn-edit-customer', function(e) {
        e.stopPropagation();
        const custId = $(this).data('id');
        openEditCustomerModal(custId);
    });

    $('#formCustomer').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#btnSaveCustomer');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: (window.BASE_URL || '') + '/api/khata.php?action=save_customer',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save Customer');
                if (res.success) {
                    hideKhataModal('#modalCustomer');
                    showToast(res.message || 'Customer saved successfully.', 'success');
                    loadKPIs();
                    loadCustomersList();
                    loadAllCustomersForSelects();
                    if ($('#viewCustomerPassbook').is(':visible') && activeCustomerId == $('#custInputId').val()) {
                        loadCustomerPassbook(activeCustomerId);
                    }
                } else {
                    showToast(res.message || 'An error occurred.', 'danger');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> Save Customer');
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to connect to server.';
                showToast(msg, 'danger');
            }
        });
    });

    // -------------------------------------------------------------------------
    // 8. MODAL: RECORD CREDIT SALE (GOODS ON CREDIT)
    // -------------------------------------------------------------------------
    $('#btnOpenCreditSaleModal').on('click', function() {
        openCreditSaleModalForCustomer(activeCustomerId > 0 ? activeCustomerId : null);
    });

    $(document).on('click', '.btn-card-give-credit', function(e) {
        e.stopPropagation();
        const custId = $(this).data('id');
        openCreditSaleModalForCustomer(custId);
    });

    function openCreditSaleModalForCustomer(custId) {
        $('#formCreditSale')[0].reset();
        $('#saleItemsTableBody').empty();
        $('#saleInputDate').val(new Date().toISOString().split('T')[0]);
        $('#saleCheckDeductStock').prop('checked', true);

        if (custId && custId > 0) {
            if ($('#saleSelectCustomer option[value="' + custId + '"]').length === 0) {
                const c = (customersList || []).find(x => x.id == custId) || (allCustomersMaster || []).find(x => x.id == custId);
                if (c) {
                    const optText = `${c.name} (${getTypeLabel(c.customer_type)}) - Due: Rs. ${formatNumber(c.current_balance)}`;
                    $('#saleSelectCustomer').append(`<option value="${c.id}" data-balance="${c.current_balance}" data-limit="${c.credit_limit}">${optText}</option>`);
                }
            }
            $('#saleSelectCustomer').val(custId).trigger('change');
        } else {
            $('#saleSelectCustomer').val('').trigger('change');
        }

        // Add 2 initial item rows
        addCreditSaleItemRow();
        addCreditSaleItemRow();

        recalcCreditSaleTotals();
        showKhataModal('#modalCreditSale');
    }
    window.openCreditSaleModalForCustomer = openCreditSaleModalForCustomer;

    // Customer change updates balance hints & limits
    $('#saleSelectCustomer').on('change', function() {
        const $opt = $(this).find(':selected');
        const bal = parseFloat($opt.data('balance')) || 0;
        const limit = parseFloat($opt.data('limit')) || 0;

        $('#saleCustBalanceHint').html(`Current Balance: <b class="${bal > 0 ? 'text-danger' : 'text-success'}">Rs. ${formatNumber(bal)}</b>`);
        if (limit > 0) {
            $('#saleCustLimitHint').html(`Credit Limit: <b>Rs. ${formatNumber(limit)}</b>`);
        } else {
            $('#saleCustLimitHint').text('');
        }
        recalcCreditSaleTotals();
    });

    // Add Row Button
    $('#btnAddSaleItemRow').on('click', function() {
        addCreditSaleItemRow();
    });

    function addCreditSaleItemRow(initialItem = {}) {
        const rowId = 'itemRow_' + Math.random().toString(36).substr(2, 9);
        const name = initialItem.name || '';
        const qty = initialItem.qty || 1;
        const price = initialItem.price || 0;
        const prodId = initialItem.product_id || '';
        const rowTotal = (qty * price).toFixed(2);

        const html = `
        <tr class="khata-item-row" id="${rowId}">
            <td class="position-relative">
                <input type="hidden" class="row-product-id" value="${prodId}">
                <input type="text" class="form-control form-control-sm rounded-2 row-item-name" 
                       placeholder="Type item name or search catalog..." value="${escapeHtml(name)}" autocomplete="off" required>
                <div class="item-search-results d-none"></div>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm rounded-2 text-center row-item-qty" 
                       value="${qty}" min="0.01" step="any" required>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm rounded-2 text-end row-item-price" 
                       value="${price}" min="0" step="any" placeholder="0" required>
            </td>
            <td class="text-end fw-bold text-dark fs-sm row-item-total">
                Rs. ${formatNumber(rowTotal)}
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-link text-danger p-0 fs-5 btn-remove-sale-row" title="Delete Row">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>`;

        $('#saleItemsTableBody').append(html);
        recalcCreditSaleTotals();
    }

    // Remove item row
    $(document).on('click', '.btn-remove-sale-row', function() {
        if ($('#saleItemsTableBody tr').length <= 1) {
            showToast('Credit bill must contain at least 1 item.', 'warning');
            return;
        }
        $(this).closest('tr').remove();
        recalcCreditSaleTotals();
    });

    // Recalculate totals on row input
    $(document).on('input', '.row-item-qty, .row-item-price', function() {
        const $tr = $(this).closest('tr');
        const qty = parseFloat($tr.find('.row-item-qty').val()) || 0;
        const price = parseFloat($tr.find('.row-item-price').val()) || 0;
        const total = (qty * price).toFixed(2);
        $tr.find('.row-item-total').text('Rs. ' + formatNumber(total));
        recalcCreditSaleTotals();
    });

    function recalcCreditSaleTotals() {
        let grandTotal = 0;
        let count = 0;

        $('#saleItemsTableBody tr').each(function() {
            const name = $(this).find('.row-item-name').val().trim();
            const qty = parseFloat($(this).find('.row-item-qty').val()) || 0;
            const price = parseFloat($(this).find('.row-item-price').val()) || 0;
            if (name.length > 0) {
                count++;
                grandTotal += (qty * price);
            }
        });

        $('#saleItemsCountBadge').text(`${count} Items`);
        $('#saleGrandTotalText').text('Rs. ' + formatNumber(grandTotal));

        // Customer new balance preview
        const $custOpt = $('#saleSelectCustomer').find(':selected');
        const curBal = parseFloat($custOpt.data('balance')) || 0;
        const newBal = curBal + grandTotal;
        $('#saleNewBalancePreview').text('Rs. ' + formatNumber(newBal));
    }

    // Live Product Search Dropdown inside Item Rows
    let productSearchDebounce;
    $(document).on('input', '.row-item-name', function() {
        const $input = $(this);
        const query = $input.val().trim();
        const $results = $input.siblings('.item-search-results');
        const $prodId = $input.siblings('.row-product-id');

        $prodId.val(''); // clear linked product id if user types manually

        if (query.length < 2) {
            $results.addClass('d-none').empty();
            return;
        }

        clearTimeout(productSearchDebounce);
        productSearchDebounce = setTimeout(function() {
            $.ajax({
                url: `${(window.BASE_URL || "")}/api/products.php?action=list&search=${encodeURIComponent(query)}&limit=8`,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.products && res.products.length > 0) {
                        $results.empty().removeClass('d-none');
                        res.products.forEach(p => {
                            const pPrice = parseFloat(p.selling_price || p.price || 0);
                            const pItem = `
                            <div class="item-search-item" data-id="${p.id}" data-name="${escapeHtml(p.name)}" data-price="${pPrice}" data-stock="${p.quantity}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark fs-xs">${escapeHtml(p.name)}</span>
                                    <span class="fw-bold text-danger fs-xs">Rs. ${formatNumber(pPrice)}</span>
                                </div>
                                <div class="d-flex justify-content-between text-muted fs-xxs">
                                    <span>Barcode: ${escapeHtml(p.barcode || '-')}</span>
                                    <span>In Stock: <b>${p.quantity}</b></span>
                                </div>
                            </div>`;
                            $results.append(pItem);
                        });
                    } else {
                        $results.addClass('d-none').empty();
                    }
                },
                error: function() {
                    $results.addClass('d-none').empty();
                }
            });
        }, 250);
    });

    // Close search dropdown on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.position-relative').length) {
            $('.item-search-results').addClass('d-none');
        }
    });

    // Select product from search dropdown
    $(document).on('click', '.item-search-item', function() {
        const $item = $(this);
        const $tr = $item.closest('tr');
        const pId = $item.data('id');
        const name = $item.data('name');
        const price = $item.data('price');

        $tr.find('.row-product-id').val(pId);
        $tr.find('.row-item-name').val(name);
        $tr.find('.row-item-price').val(price);
        $tr.find('.item-search-results').addClass('d-none').empty();

        const qty = parseFloat($tr.find('.row-item-qty').val()) || 1;
        $tr.find('.row-item-total').text('Rs. ' + formatNumber(qty * price));
        recalcCreditSaleTotals();
    });

    // Save Credit Sale Handlers
    $('#btnSaveSaleOnly').on('click', function() {
        submitCreditSale(false);
    });
    $('#btnSaveSaleAndPrint').on('click', function() {
        submitCreditSale(true);
    });

    function submitCreditSale(shouldPrintReceipt) {
        const custId = $('#saleSelectCustomer').val();
        if (!custId) {
            showToast('Please select a customer account.', 'warning');
            $('#saleSelectCustomer').focus();
            return;
        }

        const items = [];
        $('#saleItemsTableBody tr').each(function() {
            const name = $(this).find('.row-item-name').val().trim();
            const qty = parseFloat($(this).find('.row-item-qty').val()) || 0;
            const price = parseFloat($(this).find('.row-item-price').val()) || 0;
            const prodId = $(this).find('.row-product-id').val();

            if (name.length > 0 && qty > 0) {
                items.push({
                    item_name: name,
                    quantity: qty,
                    unit_price: price,
                    product_id: prodId ? parseInt(prodId) : null
                });
            }
        });

        if (items.length === 0) {
            showToast('Please add at least one valid item to the bill.', 'warning');
            return;
        }

        const payload = {
            customer_id: custId,
            bill_date: $('#saleInputDate').val(),
            deduct_stock: $('#saleCheckDeductStock').is(':checked') ? 1 : 0,
            notes: $('#saleInputNotes').val().trim(),
            items: JSON.stringify(items)
        };

        const $btns = $('#btnSaveSaleOnly, #btnSaveSaleAndPrint');
        $btns.prop('disabled', true);

        $.ajax({
            url: (window.BASE_URL || '') + '/api/khata.php?action=save_credit_sale',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(res) {
                $btns.prop('disabled', false);
                if (res.success) {
                    hideKhataModal('#modalCreditSale');
                    showToast(res.message || 'Credit bill recorded successfully.', 'success');

                    if (shouldPrintReceipt) {
                        printCreditBillThermalReceipt(res);
                    }

                    loadKPIs();
                    loadCustomersList();
                    loadAllCustomersForSelects();
                    if ($('#viewCustomerPassbook').is(':visible') && activeCustomerId == custId) {
                        loadCustomerPassbook(activeCustomerId);
                    }
                } else {
                    showToast(res.message || 'Could not save credit bill.', 'danger');
                }
            },
            error: function(xhr) {
                $btns.prop('disabled', false);
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to connect to server.';
                showToast(msg, 'danger');
            }
        });
    }

    // -------------------------------------------------------------------------
    // 9. MODAL: RECORD RECOVERY PAYMENT (VASOOLI / JAMA)
    // -------------------------------------------------------------------------
    $('#btnOpenRecoveryModal').on('click', function() {
        openRecoveryModalForCustomer(activeCustomerId > 0 ? activeCustomerId : null);
    });

    $(document).on('click', '.btn-card-take-recovery', function(e) {
        e.stopPropagation();
        const custId = $(this).data('id');
        openRecoveryModalForCustomer(custId);
    });

    let cachedEmployeesForPay = null;
    function loadEmployeesForPaymentSelect() {
        if (cachedEmployeesForPay) {
            renderEmployeeSelectOptions(cachedEmployeesForPay);
            return;
        }
        $.ajax({
            url: (window.BASE_URL || '') + '/api/khata.php?action=list_employees_for_select',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.employees) {
                    cachedEmployeesForPay = res.employees;
                    renderEmployeeSelectOptions(res.employees);
                }
            }
        });
    }

    function renderEmployeeSelectOptions(employees) {
        const $sel = $('#paySelectEmployee');
        const curVal = $sel.val();
        $sel.empty().append('<option value="">-- Dukan Counter / Owner (Direct) --</option>');
        (employees || []).forEach(emp => {
            const desig = emp.designation ? ` (${emp.designation})` : '';
            $sel.append(`<option value="${emp.id}">👤 ${escapeHtml(emp.name)}${escapeHtml(desig)}</option>`);
        });
        if (curVal) $sel.val(curVal);
    }

    function openRecoveryModalForCustomer(custId) {
        $('#formRecoveryPayment')[0].reset();
        $('#payInputDate').val(new Date().toISOString().split('T')[0]);
        $('#paySelectMethod').val('cash');
        $('#paySelectEmployee').val('');
        $('#payQuickChipsContainer .quick-chip-btn').removeClass('active');
        loadEmployeesForPaymentSelect();

        if (custId && custId > 0) {
            if ($('#paySelectCustomer option[value="' + custId + '"]').length === 0) {
                const c = (customersList || []).find(x => x.id == custId) || (allCustomersMaster || []).find(x => x.id == custId);
                if (c) {
                    const optText = `${c.name} (${getTypeLabel(c.customer_type)}) - Due: Rs. ${formatNumber(c.current_balance)}`;
                    $('#paySelectCustomer').append(`<option value="${c.id}" data-balance="${c.current_balance}">${optText}</option>`);
                }
            }
            $('#paySelectCustomer').val(custId).trigger('change');
        } else {
            $('#paySelectCustomer').val('').trigger('change');
        }

        showKhataModal('#modalRecoveryPayment');
    }
    window.openRecoveryModalForCustomer = openRecoveryModalForCustomer;

    $('#paySelectCustomer').on('change', function() {
        const $opt = $(this).find(':selected');
        const bal = parseFloat($opt.data('balance')) || 0;
        $('#payCustCurrentBalance').text('Rs. ' + formatNumber(bal));
        $('#chipFullBalance').data('val', bal > 0 ? bal : 0);
        recalcPaymentPreview();
    });

    // Quick chips
    $('#payQuickChipsContainer').on('click', '.quick-chip-btn', function() {
        $('#payQuickChipsContainer .quick-chip-btn').removeClass('active');
        $(this).addClass('active');
        const amt = parseFloat($(this).data('val')) || 0;
        $('#payInputAmount').val(amt > 0 ? amt : '');
        recalcPaymentPreview();
    });

    $('#payInputAmount').on('input', function() {
        $('#payQuickChipsContainer .quick-chip-btn').removeClass('active');
        recalcPaymentPreview();
    });

    function recalcPaymentPreview() {
        const $custOpt = $('#paySelectCustomer').find(':selected');
        const curBal = parseFloat($custOpt.data('balance')) || 0;
        const paidAmt = parseFloat($('#payInputAmount').val()) || 0;
        const newBal = curBal - paidAmt;

        if (newBal > 0) {
            $('#payNewBalancePreview').html(`<span class="text-danger">Rs. ${formatNumber(newBal)} (Due)</span>`);
        } else if (newBal < 0) {
            $('#payNewBalancePreview').html(`<span class="text-primary">Rs. ${formatNumber(Math.abs(newBal))} (Advance)</span>`);
        } else {
            $('#payNewBalancePreview').html(`<span class="text-success"><i class="fas fa-check-circle me-1"></i>Settled (Rs. 0.00)</span>`);
        }
    }

    // Save Payment Handlers
    $('#btnSavePaymentOnly').on('click', function() {
        submitRecoveryPayment(false);
    });
    $('#btnSavePaymentAndPrint').on('click', function() {
        submitRecoveryPayment(true);
    });

    function submitRecoveryPayment(shouldPrintReceipt) {
        const custId = $('#paySelectCustomer').val();
        const amt = parseFloat($('#payInputAmount').val()) || 0;

        if (!custId) {
            showToast('Please select a customer account.', 'warning');
            $('#paySelectCustomer').focus();
            return;
        }
        if (amt <= 0) {
            showToast('Please enter a valid payment amount.', 'warning');
            $('#payInputAmount').focus();
            return;
        }

        const payload = {
            customer_id: custId,
            amount: amt,
            payment_date: $('#payInputDate').val(),
            payment_method: $('#paySelectMethod').val(),
            received_by_employee_id: $('#paySelectEmployee').val() || '',
            notes: $('#payInputNotes').val().trim()
        };

        const $btns = $('#btnSavePaymentOnly, #btnSavePaymentAndPrint');
        $btns.prop('disabled', true);

        $.ajax({
            url: (window.BASE_URL || '') + '/api/khata.php?action=save_payment',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(res) {
                $btns.prop('disabled', false);
                if (res.success) {
                    hideKhataModal('#modalRecoveryPayment');
                    showToast(res.message || 'Payment received successfully.', 'success');

                    if (shouldPrintReceipt) {
                        printRecoveryPaymentThermalReceipt(res);
                    }

                    loadKPIs();
                    loadCustomersList();
                    loadAllCustomersForSelects();
                    if ($('#viewCustomerPassbook').is(':visible') && activeCustomerId == custId) {
                        loadCustomerPassbook(activeCustomerId);
                    }
                } else {
                    showToast(res.message || 'Could not record payment.', 'danger');
                }
            },
            error: function(xhr) {
                $btns.prop('disabled', false);
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to connect to server.';
                showToast(msg, 'danger');
            }
        });
    }

    // -------------------------------------------------------------------------
    // 10. MODAL: VIEW BILL BREAKDOWN & ITEMS
    // -------------------------------------------------------------------------
    $(document).on('click', '.btn-view-bill-breakdown', function(e) {
        e.stopPropagation();
        const billId = $(this).data('id');

        $.ajax({
            url: `${(window.BASE_URL || "")}/api/khata.php?action=get_bill_details&bill_id=${billId}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.bill) {
                    lastLoadedBillForModal = res.bill;
                    const b = res.bill;
                    const grandTotal = parseFloat(b.total_amount) || 0;
                    const downPay = parseFloat(b.down_payment) || 0;
                    const netCredit = Math.max(0, grandTotal - downPay);

                    let itemsHtml = '';
                    (b.items || []).forEach((it, idx) => {
                        const barcodeHtml = it.barcode ? `<div class="fs-xxs text-muted"><code>${escapeHtml(it.barcode)}</code></div>` : '';
                        itemsHtml += `
                        <tr>
                            <td class="ps-2 text-muted">${idx + 1}</td>
                            <td>
                                <div class="fw-bold text-dark">${escapeHtml(it.item_name)}</div>
                                ${barcodeHtml}
                            </td>
                            <td class="text-center"><span class="badge bg-secondary-subtle text-dark px-2">${it.quantity}</span></td>
                            <td class="text-end">Rs. ${formatNumber(it.unit_price)}</td>
                            <td class="text-end fw-bold text-dark pe-2">Rs. ${formatNumber(it.total_price)}</td>
                        </tr>`;
                    });

                    const content = `
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">${escapeHtml(b.customer_name)}</h6>
                                <small class="text-muted"><i class="fas fa-phone me-1"></i>${escapeHtml(b.customer_phone || '-')}</small>
                            </div>
                            <div class="text-end">
                                <code class="fw-bold text-danger fs-6">${escapeHtml(b.bill_number)}</code>
                                <div class="fs-xs text-muted">📅 ${formatDate(b.bill_date)}</div>
                            </div>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light fs-xs text-muted">
                                    <tr>
                                        <th class="ps-2" style="width: 35px;">#</th>
                                        <th>Item / Product</th>
                                        <th class="text-center" style="width: 70px;">Qty</th>
                                        <th class="text-end" style="width: 95px;">Rate</th>
                                        <th class="text-end pe-2" style="width: 110px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>${itemsHtml}</tbody>
                                <tfoot class="border-top table-light">
                                    <tr class="fw-bold">
                                        <td colspan="4" class="text-end">Total Bill Amount:</td>
                                        <td class="text-end pe-2 text-dark">Rs. ${formatNumber(grandTotal)}</td>
                                    </tr>
                                    ${downPay > 0 ? `
                                    <tr class="text-success fw-bold">
                                        <td colspan="4" class="text-end py-1"><i class="fas fa-hand-holding-dollar me-1"></i>Down Payment (Cash Paid):</td>
                                        <td class="text-end pe-2 py-1">- Rs. ${formatNumber(downPay)}</td>
                                    </tr>
                                    <tr class="text-danger fw-bolder fs-6">
                                        <td colspan="4" class="text-end py-1"><i class="fas fa-book-open me-1"></i>Net Added to Khata (Udhar):</td>
                                        <td class="text-end pe-2 py-1">+ Rs. ${formatNumber(netCredit)}</td>
                                    </tr>
                                    ` : ''}
                                </tfoot>
                            </table>
                        </div>

                        ${b.notes ? `<div class="p-2 bg-light rounded-2 fs-xs text-muted mb-2"><i class="fas fa-comment-dots me-1"></i><b>Notes:</b> ${escapeHtml(b.notes)}</div>` : ''}
                        <div class="d-flex justify-content-between align-items-center fs-xxs text-muted border-top pt-2">
                            <span>Cashier: <b>${escapeHtml(b.cashier_name || 'Staff')}</b></span>
                            <span>Recorded: ${formatDate(b.bill_date)}</span>
                        </div>
                    `;

                    $('#billDetailsContent').html(content);
                    showKhataModal('#modalBillDetails');
                } else {
                    showToast('Bill details not found.', 'danger');
                }
            },
            error: function() {
                showToast('Unable to connect to server.', 'danger');
            }
        });
    });

    $('#btnPrintBillFromModal').on('click', function() {
        if (!lastLoadedBillForModal) return;
        const b = lastLoadedBillForModal;
        printCreditBillThermalReceipt({
            bill_number: b.bill_number,
            bill_date: b.bill_date,
            customer_name: b.customer_name,
            customer_phone: b.customer_phone,
            total_amount: b.total_amount,
            down_payment: b.down_payment,
            notes: b.notes,
            items: b.items
        });
    });

    // -------------------------------------------------------------------------
    // 11. MODAL: VOID TRANSACTION (MANAGER / ADMIN ONLY)
    // -------------------------------------------------------------------------
    <?php if ($canVoid): ?>
    $(document).on('click', '.btn-open-void-modal', function(e) {
        e.stopPropagation();
        const type = $(this).data('type');
        const id = $(this).data('id');
        const ref = $(this).data('ref');

        $('#voidTxType').val(type);
        $('#voidTxId').val(id);
        $('#voidInputReason').val('');
        showKhataModal('#modalVoidTx');
    });

    $('#formVoidTx').on('submit', function(e) {
        e.preventDefault();
        const type = $('#voidTxType').val();
        const id = $('#voidTxId').val();
        const reason = $('#voidInputReason').val().trim();

        if (!reason) {
            showToast('Please provide a reason for voiding.', 'warning');
            return;
        }

        const action = (type === 'bill') ? 'void_bill' : 'void_payment';
        const data = (type === 'bill') ? { bill_id: id, void_reason: reason } : { payment_id: id, void_reason: reason };

        const $btn = $('#btnConfirmVoid');
        $btn.prop('disabled', true).text('Voiding...');

        $.ajax({
            url: `${(window.BASE_URL || "")}/api/khata.php?action=${action}`,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).text('Yes, Void Transaction');
                if (res.success) {
                    hideKhataModal('#modalVoidTx');
                    showToast(res.message || 'Transaction voided successfully.', 'success');
                    loadKPIs();
                    loadAllCustomersForSelects();
                    if ($('#viewCustomerPassbook').is(':visible') && activeCustomerId > 0) {
                        loadCustomerPassbook(activeCustomerId);
                    }
                } else {
                    showToast(res.message || 'Could not void transaction.', 'danger');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).text('Yes, Void Transaction');
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to connect to server.';
                showToast(msg, 'danger');
            }
        });
    });
    <?php endif; ?>

    // -------------------------------------------------------------------------
    // 12. 80MM THERMAL RECEIPT PRINTING ENGINE
    // -------------------------------------------------------------------------
    function printCreditBillThermalReceipt(data) {
        let itemsRows = '';
        (data.items || []).forEach((it, idx) => {
            const rowTotal = parseFloat(it.total_price) || (parseFloat(it.quantity) * parseFloat(it.unit_price));
            itemsRows += `
            <tr style="border-bottom: 1px dashed #000;">
                <td style="text-align: left; padding: 4px 2px; font-size: 11.5px; color: #000; font-weight: 900;">
                    ${escapeHtml(it.item_name)}<br>
                    <span style="font-size: 10px; font-weight: 800; color: #000;">${it.quantity} x Rs. ${formatNumber(it.unit_price)}</span>
                </td>
                <td style="text-align: right; padding: 4px 2px; font-size: 11.5px; font-weight: 900; color: #000;">
                    Rs. ${formatNumber(rowTotal)}
                </td>
            </tr>`;
        });

        const newBalHtml = data.new_balance !== undefined ? `
            <tr style="border-top: 1.5px dashed #000;">
                <td style="padding: 4px 2px; font-size: 11.5px; font-weight: 900; color: #000;">Net Balance Due:</td>
                <td style="text-align: right; padding: 4px 2px; font-size: 13px; font-weight: 900; color: #000;">Rs. ${formatNumber(data.new_balance)}</td>
            </tr>
        ` : '';

        const downPayVal = parseFloat(data.down_payment) || 0;
        const totalVal = parseFloat(data.total_amount) || 0;
        const netUdharVal = Math.max(0, totalVal - downPayVal);
        const downPayHtml = downPayVal > 0 ? `
            <tr>
                <td style="padding: 2px; font-weight: 800; color: #000;">Advance / Down Pay:</td>
                <td style="text-align: right; padding: 2px; font-weight: 900; color: #000;">- Rs. ${formatNumber(downPayVal)}</td>
            </tr>
            <tr>
                <td style="padding: 2px; font-weight: 900; color: #000;">Net Udhar (Credit):</td>
                <td style="text-align: right; padding: 2px; font-weight: 900; color: #000;">Rs. ${formatNumber(netUdharVal)}</td>
            </tr>
        ` : '';

        const slipHtml = `
        <div style="font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; width: 78mm; max-width: 78mm; margin: 0 auto; color: #000; font-size: 12px; font-weight: 700; line-height: 1.35;">
            <div style="text-align: center; margin-bottom: 6px; color: #000;">
                <img src="${(window.BASE_URL || "")}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="${window.STORE_NAME || 'One Dollar Shop'}" class="receipt-logo">
                <h3 style="margin: 0; font-size: 16.5px; font-weight: 900; text-transform: uppercase; color: #000;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h3>
                <p style="margin: 2px 0; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #000;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'}</p>
                <div style="font-size: 12px; font-weight: 900; margin-top: 2px; color: #000;">Ph: ${window.STORE_PHONE || '0307-2681893'}</div>
                <div style="font-weight: 900; border-top: 1.5px dashed #000; border-bottom: 1.5px dashed #000; padding: 3px 0; margin: 4px 0; font-size: 13px; text-transform: uppercase; color: #000;">
                    *** CREDIT SALE RECEIPT (UDHAR) ***
                </div>
            </div>

            <table style="width: 100%; font-size: 11px; font-weight: 800; line-height: 1.4; margin-bottom: 4px; color: #000;">
                <tr>
                    <td><strong>Bill #:</strong> <span style="font-weight: 900;">${escapeHtml(data.bill_number)}</span></td>
                    <td style="text-align: right;"><strong>Date:</strong> ${formatDate(data.bill_date)}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Customer:</strong> <span style="font-weight: 900;">${escapeHtml(data.customer_name)}</span></td>
                </tr>
                ${data.customer_phone ? `<tr><td colspan="2"><strong>Phone:</strong> ${escapeHtml(data.customer_phone)}</td></tr>` : ''}
            </table>

            <table style="width: 100%; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; margin: 4px 0; color: #000;">
                <thead>
                    <tr style="border-bottom: 1.5px dashed #000; font-size: 11px;">
                        <th style="text-align: left; padding: 3px 2px; font-weight: 900;">ITEM DESCRIPTION</th>
                        <th style="text-align: right; padding: 3px 2px; font-weight: 900;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows}
                </tbody>
            </table>

            <table style="width: 100%; font-size: 11.5px; font-weight: 800; line-height: 1.4; margin-top: 4px; color: #000;">
                <tr style="font-size: 13.5px; font-weight: 900; border-top: 1.5px dashed #000; border-bottom: 1.5px dashed #000;">
                    <td style="padding: 4px 2px;">This Bill Total:</td>
                    <td style="text-align: right; padding: 4px 2px;">Rs. ${formatNumber(data.total_amount)}</td>
                </tr>
                ${downPayHtml}
                ${newBalHtml}
            </table>

            ${data.notes ? `<div style="font-size: 10.5px; font-weight: 800; margin-top: 4px; border-top: 1px dashed #000; padding-top: 2px; color: #000;"><b>Note:</b> ${escapeHtml(data.notes)}</div>` : ''}

            <div style="text-align: center; margin-top: 8px; border-top: 1.5px dashed #000; padding-top: 4px; font-size: 10px; font-weight: 800; color: #000;">
                <p style="margin: 2px 0; font-weight: 900;">Thank you! Please clear your credit balance on time.</p>
                <div style="font-size: 10.5px; font-weight: 900; text-transform: uppercase; border: 1.5px solid #000; padding: 2px 6px; margin: 3px auto; display: inline-block;">
                    Jewellery &amp; Cosmetics No Return / Exchange
                </div>
                <div style="font-size: 10px; font-weight: 800; margin-top: 2px;">Cashier: ${escapeHtml(currentCashierName)} | Printed: ${new Date().toLocaleTimeString()}</div>
                <div style="margin-top: 4px; font-size: 9px; font-weight: 800; border-top: 1px dashed #000; padding-top: 3px;">
                    Software Developed by:<br>
                    <span style="font-weight: 900; font-size: 10px;">0319-7273908 | 0336-8176491</span>
                </div>
            </div>
        </div>`;

        if (typeof window.printThermalReceipt === 'function') {
            window.printThermalReceipt(slipHtml);
        } else {
            window.print();
        }
    }

    function printRecoveryPaymentThermalReceipt(data) {
        const slipHtml = `
        <div style="font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; width: 78mm; max-width: 78mm; margin: 0 auto; color: #000; font-size: 12px; font-weight: 700; line-height: 1.35;">
            <div style="text-align: center; margin-bottom: 6px; color: #000;">
                <img src="${(window.BASE_URL || "")}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="${window.STORE_NAME || 'One Dollar Shop'}" class="receipt-logo">
                <h3 style="margin: 0; font-size: 16.5px; font-weight: 900; text-transform: uppercase; color: #000;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h3>
                <p style="margin: 2px 0; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #000;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'}</p>
                <div style="font-size: 12px; font-weight: 900; margin-top: 2px; color: #000;">Ph: ${window.STORE_PHONE || '0307-2681893'}</div>
                <div style="font-weight: 900; border-top: 1.5px dashed #000; border-bottom: 1.5px dashed #000; padding: 3px 0; margin: 4px 0; font-size: 13px; text-transform: uppercase; color: #000;">
                    *** PAYMENT RECOVERY RECEIPT ***
                </div>
            </div>

            <table style="width: 100%; font-size: 11px; font-weight: 800; line-height: 1.4; margin-bottom: 6px; color: #000;">
                <tr>
                    <td><strong>Receipt #:</strong> <span style="font-weight: 900;">${escapeHtml(data.receipt_number)}</span></td>
                    <td style="text-align: right;"><strong>Date:</strong> ${formatDate(data.payment_date)}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Customer:</strong> <span style="font-weight: 900;">${escapeHtml(data.customer_name)}</span></td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Method:</strong> <span style="font-weight: 900;">${getPaymentMethodLabel(data.payment_method)}</span></td>
                </tr>
                ${data.received_by_employee_name ? `<tr><td colspan="2"><strong>Received By:</strong> ${escapeHtml(data.received_by_employee_name)} (Staff)</td></tr>` : ''}
            </table>

            <table style="width: 100%; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 6px 0; margin: 6px 0; font-size: 11.5px; font-weight: 800; line-height: 1.4; color: #000;">
                <tr>
                    <td style="padding: 2px;">Previous Balance Due:</td>
                    <td style="text-align: right; padding: 2px; font-weight: 900;">Rs. ${formatNumber(data.previous_balance || 0)}</td>
                </tr>
                <tr style="font-size: 14px; font-weight: 900; border-top: 1.5px dashed #000; border-bottom: 1.5px dashed #000;">
                    <td style="padding: 4px 2px;">Amount Received:</td>
                    <td style="text-align: right; padding: 4px 2px;">Rs. ${formatNumber(data.amount_paid)}</td>
                </tr>
                <tr style="font-weight: 900;">
                    <td style="padding: 2px;">Remaining Balance:</td>
                    <td style="text-align: right; padding: 2px; font-size: 13px;">Rs. ${formatNumber(data.new_balance || 0)}</td>
                </tr>
            </table>

            ${data.notes ? `<div style="font-size: 10.5px; font-weight: 800; margin-top: 4px; color: #000;"><b>Note:</b> ${escapeHtml(data.notes)}</div>` : ''}

            <div style="text-align: center; margin-top: 8px; border-top: 1.5px dashed #000; padding-top: 4px; font-size: 10px; font-weight: 800; color: #000;">
                <p style="margin: 2px 0; font-weight: 900;">Payment received with thanks.</p>
                <div style="font-size: 10px; font-weight: 800; margin-top: 2px;">Cashier: ${escapeHtml(currentCashierName)} | Printed: ${new Date().toLocaleTimeString()}</div>
                <div style="margin-top: 4px; font-size: 9px; font-weight: 800; border-top: 1px dashed #000; padding-top: 3px;">
                    Software Developed by:<br>
                    <span style="font-weight: 900; font-size: 10px;">0319-7273908 | 0336-8176491</span>
                </div>
            </div>
        </div>`;

        if (typeof window.printThermalReceipt === 'function') {
            window.printThermalReceipt(slipHtml);
        } else {
            window.print();
        }
    }

    // Print Single Transaction from Passbook Table
    $(document).on('click', '.btn-print-single-tx', function(e) {
        e.stopPropagation();
        const type = $(this).data('type');
        const id = $(this).data('id');

        if (type === 'bill') {
            $.ajax({
                url: `${(window.BASE_URL || "")}/api/khata.php?action=get_bill_details&bill_id=${id}`,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.bill) {
                        printCreditBillThermalReceipt(res.bill);
                    }
                }
            });
        } else {
            // Find payment in currentLedgerData
            if (currentLedgerData && currentLedgerData.transactions) {
                const pay = currentLedgerData.transactions.find(t => t.tx_type === 'payment' && t.id == id);
                if (pay) {
                    printRecoveryPaymentThermalReceipt({
                        receipt_number: pay.ref_no,
                        payment_date: pay.tx_date,
                        customer_name: currentLedgerData.customer.name,
                        customer_phone: currentLedgerData.customer.phone,
                        amount_paid: pay.credit,
                        previous_balance: pay.balance_after + pay.credit,
                        new_balance: pay.balance_after,
                        payment_method: pay.payment_method,
                        notes: pay.notes
                    });
                }
            }
        }
    });

    // Print Complete Passbook Statement Slip (80mm)
    $('#btnPassbookPrintSlip').on('click', function() {
        if (!currentLedgerData) return;
        const c = currentLedgerData.customer;
        const txs = currentLedgerData.transactions || [];

        let txRows = '';
        txs.forEach(t => {
            const isBill = t.tx_type === 'bill';
            const dr = isBill ? formatNumber(t.debit) : '-';
            const cr = !isBill ? formatNumber(t.credit) : '-';
            txRows += `
            <tr style="border-bottom: 1px dashed #000; font-size: 10.5px; color: #000;">
                <td style="padding: 3px 2px; font-weight: 800;">${formatDate(t.tx_date)}</td>
                <td style="padding: 3px 2px; font-weight: 800;">${escapeHtml(t.ref_no)}</td>
                <td style="text-align: right; padding: 3px 2px; font-weight: 900;">${isBill ? 'Rs. ' + dr : '-'}</td>
                <td style="text-align: right; padding: 3px 2px; font-weight: 900;">${!isBill ? 'Rs. ' + cr : '-'}</td>
                <td style="text-align: right; padding: 3px 2px; font-weight: 900;">Rs. ${formatNumber(t.balance_after)}</td>
            </tr>`;
        });

        const statementHtml = `
        <div style="font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; width: 78mm; max-width: 78mm; margin: 0 auto; color: #000; font-size: 12px; font-weight: 700; line-height: 1.35;">
            <div style="text-align: center; margin-bottom: 6px; color: #000;">
                <img src="${(window.BASE_URL || "")}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="${window.STORE_NAME || 'One Dollar Shop'}" class="receipt-logo">
                <h3 style="margin: 0; font-size: 16.5px; font-weight: 900; text-transform: uppercase; color: #000;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h3>
                <p style="margin: 2px 0; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #000;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'}</p>
                <div style="font-size: 12px; font-weight: 900; margin-top: 2px; color: #000;">Ph: ${window.STORE_PHONE || '0307-2681893'}</div>
                <div style="font-weight: 900; border-top: 1.5px dashed #000; border-bottom: 1.5px dashed #000; padding: 3px 0; margin: 4px 0; font-size: 13px; text-transform: uppercase; color: #000;">
                    *** CUSTOMER KHATA PASSBOOK ***
                </div>
            </div>

            <table style="width: 100%; font-size: 11px; font-weight: 800; line-height: 1.4; margin-bottom: 4px; color: #000;">
                <tr>
                    <td colspan="2"><strong>Customer:</strong> <span style="font-weight: 900;">${escapeHtml(c.name)}</span></td>
                </tr>
                ${c.phone ? `<tr><td colspan="2"><strong>Phone:</strong> <span style="font-weight: 900;">${escapeHtml(c.phone)}</span></td></tr>` : ''}
                <tr>
                    <td><strong>Statement Date:</strong> ${new Date().toLocaleDateString()}</td>
                    <td style="text-align: right;"><strong>Time:</strong> ${new Date().toLocaleTimeString()}</td>
                </tr>
            </table>

            <table style="width: 100%; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; margin: 4px 0; color: #000;">
                <thead>
                    <tr style="border-bottom: 1.5px dashed #000; font-size: 10px;">
                        <th style="text-align: left; padding: 3px 1px; font-weight: 900;">DATE</th>
                        <th style="text-align: left; padding: 3px 1px; font-weight: 900;">REF #</th>
                        <th style="text-align: right; padding: 3px 1px; font-weight: 900;">DEBIT</th>
                        <th style="text-align: right; padding: 3px 1px; font-weight: 900;">CREDIT</th>
                        <th style="text-align: right; padding: 3px 1px; font-weight: 900;">BAL</th>
                    </tr>
                </thead>
                <tbody>
                    ${txRows}
                </tbody>
            </table>

            <table style="width: 100%; font-size: 11.5px; font-weight: 800; line-height: 1.4; margin-top: 4px; color: #000;">
                <tr>
                    <td style="padding: 2px;">Total Udhar (Debit):</td>
                    <td style="text-align: right; padding: 2px; font-weight: 900;">Rs. ${formatNumber(currentLedgerData.period_debit)}</td>
                </tr>
                <tr>
                    <td style="padding: 2px;">Total Recovered (Jama):</td>
                    <td style="text-align: right; padding: 2px; font-weight: 900;">Rs. ${formatNumber(currentLedgerData.period_credit)}</td>
                </tr>
                <tr style="border-top: 1.5px dashed #000; border-bottom: 1.5px dashed #000; font-size: 13.5px; font-weight: 900;">
                    <td style="padding: 4px 2px;">Net Outstanding Balance:</td>
                    <td style="text-align: right; padding: 4px 2px;">Rs. ${formatNumber(currentLedgerData.closing_balance)}</td>
                </tr>
            </table>

            <div style="text-align: center; margin-top: 8px; border-top: 1.5px dashed #000; padding-top: 4px; font-size: 10px; font-weight: 800; color: #000;">
                <p style="margin: 2px 0; font-weight: 900;">Please settle your outstanding balance on time. Thank you!</p>
                <div style="font-size: 10px; font-weight: 800; margin-top: 2px;">Cashier: ${escapeHtml(currentCashierName)} | Printed: ${new Date().toLocaleTimeString()}</div>
                <div style="margin-top: 4px; font-size: 9px; font-weight: 800; border-top: 1px dashed #000; padding-top: 3px;">
                    Software Developed by:<br>
                    <span style="font-weight: 900; font-size: 10px;">0319-7273908 | 0336-8176491</span>
                </div>
            </div>
        </div>`;

        if (typeof window.printThermalReceipt === 'function') {
            window.printThermalReceipt(statementHtml);
        } else {
            window.print();
        }
    });

    // -------------------------------------------------------------------------
    // 13. WHATSAPP STATEMENT GENERATOR
    // -------------------------------------------------------------------------
    $('#btnPassbookWhatsApp').on('click', function() {
        if (!currentLedgerData) return;
        const c = currentLedgerData.customer;
        if (!c.phone) {
            showToast('No phone number registered for this customer.', 'warning');
            return;
        }

        const phone = cleanPhone(c.phone);
        const today = new Date().toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
        const bal = parseFloat(c.lifetime_balance) || 0;

        // Recent items breakdown from latest bill
        let lastSamanText = '';
        const bills = (currentLedgerData.bills || []).filter(b => b.status === 'active');
        if (bills.length > 0) {
            const lastBill = bills[0]; // newest bill
            const items = lastBill.items || [];
            if (items.length > 0) {
                lastSamanText = `\n📦 *Pichla Saman (${formatDate(lastBill.bill_date || lastBill.tx_date)}):*\n`;
                items.forEach(it => {
                    const rowTot = parseFloat(it.total_price) || (parseFloat(it.quantity) * parseFloat(it.unit_price));
                    lastSamanText += `  • ${it.item_name} (${it.quantity} x Rs. ${formatNumber(it.unit_price)} = Rs. ${formatNumber(rowTot)})\n`;
                });
            }
        }

        // Recent payment
        let lastPayText = '';
        const pays = (currentLedgerData.payments || []).filter(p => p.status === 'active');
        if (pays.length > 0) {
            const lastPay = pays[0]; // newest pay
            lastPayText = `\n💵 *Pichli Vasooli (${formatDate(lastPay.payment_date || lastPay.tx_date)}):* Rs. ${formatNumber(lastPay.amount || lastPay.credit)} (${getPaymentMethodLabel(lastPay.payment_method)})\n`;
        }

        const msg = 
`*Salam ${c.name} Bhai,*
${window.STORE_NAME || 'One Dollar Shop'} ki taraf se aapka Khata Hisab:

📅 *Date:* ${today}
🛒 *Kul Saman (Total Credit):* Rs. ${formatNumber(c.lifetime_credit)}
💵 *Kul Vasooli (Total Paid):* Rs. ${formatNumber(c.lifetime_paid)}
━━━━━━━━━━━━━━━━━━━
*Baqaya Rakam (Balance Due):* Rs. ${formatNumber(bal)}
━━━━━━━━━━━━━━━━━━━${lastSamanText}${lastPayText}
Meharbani farma kar apna baqaya hisab check kar k ada farma dain.
Shukriya!
*${window.STORE_NAME || 'One Dollar Shop'} - ${window.STORE_ADDRESS || 'McConaghey Road, Quetta'}*`;

        const waUrl = `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(msg)}`;
        window.open(waUrl, '_blank');
    });

    // -------------------------------------------------------------------------
    // 14. HELPER UTILITIES
    // -------------------------------------------------------------------------
    function formatNumber(num) {
        if (num === null || num === undefined || isNaN(num)) return '0.00';
        return parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function formatTime(dateTimeStr) {
        if (!dateTimeStr) return '';
        const d = new Date(dateTimeStr);
        if (isNaN(d.getTime())) return '';
        return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    function cleanPhone(phone) {
        if (!phone) return '';
        let cleaned = phone.replace(/[^0-9]/g, '');
        if (cleaned.startsWith('03')) {
            cleaned = '92' + cleaned.substring(1);
        }
        return cleaned;
    }

    function getTypeBadgeHtml(type) {
        switch (type) {
            case 'store':
                return '<span class="customer-type-badge badge-type-store"><span>🏪</span> Other Store</span>';
            case 'neighbor':
                return '<span class="customer-type-badge badge-type-neighbor"><span>🏡</span> Neighbor</span>';
            case 'friend':
                return '<span class="customer-type-badge badge-type-friend"><span>🤝</span> Friend / Relative</span>';
            default:
                return '<span class="customer-type-badge badge-type-general"><span>👤</span> General Customer</span>';
        }
    }

    function getTypeLabel(type) {
        switch (type) {
            case 'store': return 'Other Store';
            case 'neighbor': return 'Neighbor';
            case 'friend': return 'Friend';
            default: return 'General';
        }
    }

    function getPaymentMethodLabel(method) {
        switch (method) {
            case 'bank': return 'Bank Transfer';
            case 'easypaisa_jazzcash': return 'EasyPaisa / JazzCash';
            default: return 'Cash in Drawer';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    // Run on startup
    init();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
