<?php
require_once __DIR__ . '/../config/auth.php';
if (!isAdmin()) {
    redirect('/pos/index.php');
}
require_once __DIR__ . '/../includes/header.php';

// Check URL query parameters for direct employee pre-selection
$selectedEmpId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$initialTab = isset($_GET['tab']) ? sanitize($_GET['tab']) : '';
?>

<style>
/* ==========================================================================
   ENTERPRISE-GRADE STAFF KHATA & PAYROLL STYLING
   ========================================================================== */
.staff-card-simple {
    border-radius: 16px;
    border: 1px solid var(--border-subtle);
    background: var(--surface-card);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    overflow: hidden;
}
.staff-card-simple:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.25);
    border-color: var(--border-medium);
}
.staff-avatar-circle {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #ffffff;
    font-weight: 800;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
    flex-shrink: 0;
}

/* Quick Action Buttons on Cards */
.btn-card-advance {
    background: #dc2626;
    color: #ffffff;
    border: none;
    font-weight: 700;
    border-radius: 8px;
    padding: 7px 10px;
    font-size: 0.82rem;
    transition: all 0.15s ease;
}
.btn-card-advance:hover {
    background: #b91c1c;
    color: #ffffff;
    transform: translateY(-1px);
}

.btn-card-deduction {
    background: #ea580c;
    color: #ffffff;
    border: none;
    font-weight: 700;
    border-radius: 8px;
    padding: 7px 10px;
    font-size: 0.82rem;
    transition: all 0.15s ease;
}
.btn-card-deduction:hover {
    background: #c2410c;
    color: #ffffff;
    transform: translateY(-1px);
}

.btn-card-bonus {
    background: #7c3aed;
    color: #ffffff;
    border: none;
    font-weight: 700;
    border-radius: 8px;
    padding: 7px 10px;
    font-size: 0.82rem;
    transition: all 0.15s ease;
}
.btn-card-bonus:hover {
    background: #6d28d9;
    color: #ffffff;
    transform: translateY(-1px);
}

.btn-card-detail {
    background: #0f172a;
    color: #ffffff;
    border: none;
    font-weight: 700;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.85rem;
    transition: all 0.15s ease;
}
.btn-card-detail:hover {
    background: #1e293b;
    color: #ffffff;
    transform: translateY(-1px);
}

/* 4 Action Tiles on Detail View */
.action-tile-btn {
    border-radius: 14px;
    padding: 16px 14px;
    text-align: center;
    border: none;
    color: #ffffff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.action-tile-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(0,0,0,0.14);
    color: #ffffff;
}
.tile-advance-grad { background: linear-gradient(135deg, #ef4444, #dc2626); }
.tile-deduct-grad { background: linear-gradient(135deg, #f97316, #ea580c); }
.tile-task-grad { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.tile-bonus-grad { background: linear-gradient(135deg, #10b981, #059669); }

/* Quick Amount Chips */
.quick-chip {
    cursor: pointer;
    font-size: 0.78rem;
    font-weight: 700;
    border-radius: 20px;
    padding: 4px 10px;
    background: var(--surface-input);
    border: 1px solid var(--border-medium);
    color: var(--text-secondary);
    transition: all 0.15s ease;
}
.quick-chip:hover {
    background: var(--surface-hover);
    color: var(--text-main);
    border-color: var(--border-strong);
    transform: translateY(-1px);
}

/* Filter Pills */
.filter-pill {
    cursor: pointer;
    border-radius: 20px;
    padding: 6px 14px;
    font-weight: 600;
    font-size: 0.85rem;
    border: 1px solid var(--border-subtle);
    background: var(--surface-input);
    color: var(--text-secondary);
    transition: all 0.15s ease;
}
.filter-pill:hover {
    background: var(--surface-hover);
    color: var(--text-main);
    border-color: var(--border-medium);
}
.filter-pill.active {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    color: #ffffff !important;
    border-color: #ef4444 !important;
    box-shadow: 0 4px 14px var(--accent-glow) !important;
}

/* View Switcher */
.view-btn {
    border: 1px solid var(--border-medium);
    background: var(--surface-input);
    color: var(--text-secondary);
    padding: 6px 12px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.15s ease;
}
.view-btn:hover {
    background: var(--surface-hover);
    color: var(--text-main);
}
.view-btn.active {
    background: #1e293b;
    color: #ffffff;
    border-color: #334155;
}
</style>

<div class="container-fluid px-0 py-1">
    <!-- Top Header & Primary Actions -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white border">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-danger text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-users fs-4"></i>
                </div>
                <div>
                    <h4 class="mb-0 fw-bold text-dark">Staff Khata & Payroll Management</h4>
                    <p class="text-muted small mb-0">Track cash advances, deductions (chuti/fines), extra duties, and monthly salary clearance.</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-danger fw-bold px-3 py-2 shadow-sm rounded-3 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newEmployeeModal">
                    <i class="fas fa-user-plus"></i>
                    <span>Add New Employee</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Month Navigation Bar (Haji Dairy Financial Architecture) -->
    <div class="card border-0 shadow-sm rounded-4 p-2 mb-3 bg-white border">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3" id="btnNavPrevMonth">
                <i class="fas fa-chevron-left me-1"></i> <span id="labelNavPrevMonth">Prev Month</span>
            </button>
            
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small fw-bold text-uppercase"><i class="fas fa-calendar-alt text-danger me-1"></i> Month:</span>
                <input type="month" id="navMonthPicker" class="form-control form-control-sm fw-bold border-danger text-danger text-center shadow-sm" style="width: 170px;" value="<?= date('Y-m') ?>">
                <button type="button" class="btn btn-outline-danger btn-sm fw-bold rounded-pill px-3" id="btnNavCurrentMonth">
                    Current Month
                </button>
            </div>
            
            <button type="button" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3" id="btnNavNextMonth">
                <span id="labelNavNextMonth">Next Month</span> <i class="fas fa-chevron-right ms-1"></i>
            </button>
        </div>
    </div>

    <!-- 4 Executive Metric Cards (Haji Dairy Architecture) -->
    <div class="row g-3 mb-3">
        <!-- 1. Dukan Ne Dena Hai (Store Owes Staff) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border h-100" style="border-left: 5px solid #0d6efd !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase"><i class="fas fa-hand-holding-dollar text-primary me-1"></i> Dukan Ne Dena Hai</span>
                        <h2 class="mb-0 fw-bold text-primary font-monospace mt-1" id="kpiStoreOwes">Rs. 0</h2>
                        <small class="text-muted" id="kpiStoreOwesSubtext">Accrued wages & net due till today</small>
                    </div>
                    <div class="bg-primary-subtle p-3 rounded-circle text-primary" style="width: 50px; height: 50px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-hand-holding-dollar fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Staff Ne Dena Hai (Staff Owes Store) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border h-100" style="border-left: 5px solid #dc2626 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase"><i class="fas fa-file-invoice-dollar text-danger me-1"></i> Staff Ne Dena Hai</span>
                        <h2 class="mb-0 fw-bold text-danger font-monospace mt-1" id="kpiStaffOwes">Rs. 0</h2>
                        <small class="text-danger fw-semibold" id="kpiStaffOwesSubtext">Excess advances & peshi balance</small>
                    </div>
                    <div class="bg-danger-subtle p-3 rounded-circle text-danger" style="width: 50px; height: 50px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-file-invoice-dollar fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Monthly Salary Budget -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border h-100" style="border-left: 5px solid #475569 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase"><i class="fas fa-wallet text-secondary me-1"></i> Monthly Salary Budget</span>
                        <h2 class="mb-0 fw-bold text-dark font-monospace mt-1" id="kpiTotalPayroll">Rs. 0</h2>
                        <small class="text-muted" id="kpiActiveSubtext">0 active on duty</small>
                    </div>
                    <div class="bg-light p-3 rounded-circle text-dark" style="width: 50px; height: 50px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-wallet fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Month Advances & Clearance Status -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border h-100" style="border-left: 5px solid #16a34a !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase"><i class="fas fa-check-circle text-success me-1"></i> Advances & Clearance</span>
                        <h2 class="mb-0 fw-bold text-success font-monospace mt-1" id="kpiMonthAdvances">Rs. 0</h2>
                        <small class="text-muted" id="kpiSettledSubtext">0 / 0 cleared for this month</small>
                    </div>
                    <div class="bg-success-subtle p-3 rounded-circle text-success" style="width: 50px; height: 50px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-check-circle fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 1: MASTER STAFF DIRECTORY                                            -->
    <!-- ========================================================================= -->
    <div id="sectionStaffDirectory">
        <!-- Search, Filter & View Controls -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white border">
            <div class="row g-3 align-items-center justify-content-between">
                <!-- Search Bar -->
                <div class="col-lg-5">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="staffSearchInput" placeholder="Search by name, phone number, or role...">
                        <button class="btn btn-outline-secondary border-start-0 bg-white text-muted d-none" type="button" id="btnClearSearch"><i class="fas fa-times"></i></button>
                    </div>
                </div>

                <!-- Filter Pills & View Toggler -->
                <div class="col-lg-7 d-flex justify-content-lg-end align-items-center gap-2 flex-wrap">
                    <span class="filter-pill active" data-filter="all">All Staff</span>
                    <span class="filter-pill" data-filter="active">Active Only</span>
                    <span class="filter-pill" data-filter="advances">Has Advances (Peshi)</span>
                    <span class="filter-pill" data-filter="inactive">Left / Inactive</span>

                    <!-- View Switcher -->
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn view-btn active rounded-start-pill" id="btnViewCards" title="Cards Grid View">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button type="button" class="btn view-btn rounded-end-pill" id="btnViewTable" title="Compact Table View">
                            <i class="fas fa-table-list"></i>
                        </button>
                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle p-2 ms-1" id="btnRefreshStaff" title="Refresh">
                        <i class="fas fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Cards View Container -->
        <div class="row g-3" id="staffCardsGrid">
            <div class="col-12 text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin display-5 text-danger mb-3"></i>
                <h5>Loading employees...</h5>
            </div>
        </div>

        <!-- Compact Table View Container -->
        <div class="card border-0 shadow-sm rounded-4 border bg-white d-none" id="staffTableCard">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                    <thead class="table-light">
                        <tr class="text-secondary small fw-bold text-uppercase">
                            <th>Employee</th>
                            <th>Role / Job</th>
                            <th>Contact</th>
                            <th class="text-end">Monthly Salary</th>
                            <th class="text-end">Earned Today</th>
                            <th class="text-end">Advances (Peshi)</th>
                            <th class="text-end">Net Due Today</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Pay Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staffTableBody">
                        <!-- Rendered via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 2: DEDICATED EMPLOYEE KHATA & SALARY VIEW                            -->
    <!-- ========================================================================= -->
    <div id="sectionEmployeeDetail" class="d-none">
        <!-- Top Executive Bar: Navigation + Quick Switcher + Status Controls -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 bg-white px-3 py-2 rounded-4 border shadow-sm">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-dark btn-sm fw-bold px-3 py-1.5 rounded-3 shadow-sm" id="btnBackToDirectory">
                    <i class="fas fa-arrow-left me-1"></i> Back to Directory
                </button>
                <div class="d-flex align-items-center gap-2 ms-2">
                    <span class="text-muted small fw-bold d-none d-md-inline"><i class="fas fa-user-gear me-1"></i> Switch:</span>
                    <select class="form-select form-select-sm fw-bold border font-monospace" id="detailEmpSwitcher" style="min-width: 190px;">
                        <!-- Injected dynamically via JS -->
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-dark fw-bold rounded-pill px-3 shadow-sm btn-detail-edit" id="btnEditSelectedEmp" title="Edit Profile">
                    <i class="fas fa-pen text-primary me-1"></i> Edit Profile
                </button>
                <button type="button" class="btn btn-sm btn-warning fw-bold rounded-pill px-3 shadow-sm" id="btnHeaderPausePay" title="Pause Salary Accrual">
                    <i class="fas fa-pause me-1"></i> Pause Pay
                </button>
                <button type="button" class="btn btn-sm btn-success text-white fw-bold rounded-pill px-3 shadow-sm d-none" id="btnHeaderResumePay" title="Resume Salary Accrual">
                    <i class="fas fa-play me-1"></i> Resume Pay
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnHeaderPauseLog" title="View Pay Pause History">
                    <i class="fas fa-history me-1"></i> Pause Log
                </button>
            </div>
        </div>

        <!-- Sleek Compact Employee Identity Strip (Zero-Waste, Laptop-Optimized) -->
        <div class="card border-0 shadow-sm mb-3 rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff;">
            <div class="card-body py-2 px-3">
                <div class="row align-items-center g-2">
                    <!-- Left: Staff Avatar & Primary Info -->
                    <div class="col-12 col-md-5 col-xl-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="staff-avatar-circle bg-danger border border-2 border-white flex-shrink-0" id="detailAvatar" style="width: 46px; height: 46px; font-size: 1.15rem;">
                                --
                            </div>
                            <div class="min-w-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h5 class="mb-0 fw-bold text-white text-truncate" id="detailName">Staff Name</h5>
                                    <span class="badge bg-light text-dark px-2 py-0.5 rounded-pill small" id="detailRole">Role</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mt-0.5" style="font-size: 0.8rem;">
                                    <span id="detailStatusBadge"></span>
                                    <span id="detailPayStatusBadge"></span>
                                    <span class="text-white-50 font-monospace"><i class="fas fa-phone me-1 text-warning"></i><span id="detailPhone">--</span></span>
                                    <span class="text-white-50 font-monospace d-none d-sm-inline"><i class="fas fa-calendar me-1 text-info"></i><span id="detailJoin">Joined</span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Center: Monthly Base Salary & Daily Rate -->
                    <div class="col-12 col-md-4 col-xl-4">
                        <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                            <div class="px-3 py-1 rounded-3 bg-black bg-opacity-50 border border-secondary border-opacity-25 text-center flex-fill">
                                <span class="text-white-50 small d-block text-uppercase" style="font-size:0.64rem; letter-spacing:0.5px;">Monthly Base Salary</span>
                                <strong class="text-white font-monospace fs-6" id="detailSalary">Rs. 0</strong>
                            </div>
                            <div class="px-3 py-1 rounded-3 bg-black bg-opacity-50 border border-secondary border-opacity-25 text-center flex-fill">
                                <span class="text-white-50 small d-block text-uppercase" style="font-size:0.64rem; letter-spacing:0.5px;">Daily Rate (1 Day)</span>
                                <strong class="text-info font-monospace fs-6" id="detailDailyWage">Rs. 0</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Khata Net Position -->
                    <div class="col-12 col-md-3 col-xl-4 text-md-end text-center">
                        <div class="d-inline-block bg-white text-dark px-3 py-1 rounded-3 shadow-sm text-start border" style="min-width: 200px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Khata Net Position</span>
                                <span id="detailNetPillBadge"></span>
                            </div>
                            <h4 class="mb-0 font-monospace fw-bold" id="detailBalance" style="font-size: 1.25rem;">Rs. 0.00</h4>
                            <small class="fw-bold d-block text-truncate" id="detailBalanceSubtext" style="font-size: 0.7rem;">Account is clear</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Sub-Tabs: 1. Khata Ledger | 2. Salary Settlement | 3. Past Vouchers History | 4. Attendance & Logs -->
        <ul class="nav nav-pills mb-3 bg-white p-2 rounded-4 shadow-sm border gap-2" role="tablist">
            <li class="nav-item flex-fill text-center" role="presentation">
                <button class="nav-link w-100 fw-bold active py-2" id="pill-ledger-tab" data-bs-toggle="pill" data-bs-target="#pill-ledger" type="button" role="tab">
                    <i class="fas fa-receipt me-2"></i> 1. Full Khata Ledger & Passbook
                </button>
            </li>
            <li class="nav-item flex-fill text-center" role="presentation">
                <button class="nav-link w-100 fw-bold py-2" id="pill-salary-tab" data-bs-toggle="pill" data-bs-target="#pill-salary" type="button" role="tab">
                    <i class="fas fa-calculator me-2"></i> 2. Monthly Salary Clearance & Slip
                </button>
            </li>
            <li class="nav-item flex-fill text-center" role="presentation">
                <button class="nav-link w-100 fw-bold py-2" id="pill-history-tab" data-bs-toggle="pill" data-bs-target="#pill-history" type="button" role="tab">
                    <i class="fas fa-history me-2"></i> 3. Past Salary Vouchers
                </button>
            </li>
            <li class="nav-item flex-fill text-center" role="presentation">
                <button class="nav-link w-100 fw-bold py-2" id="pill-pauses-tab" data-bs-toggle="pill" data-bs-target="#pill-pauses" type="button" role="tab">
                    <i class="fas fa-clipboard-user me-2 text-warning"></i> 4. Attendance, Chuti & Pay Pause Log
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- TAB A: KHATA LEDGER & PASSBOOK -->
            <div class="tab-pane fade show active" id="pill-ledger" role="tabpanel">
                <!-- 5 Fast Action Touch Tiles (Inside Tab 1) -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-sm-4 col-md-4 col-xl">
                        <button type="button" class="w-100 action-tile-btn tile-advance-grad py-2 px-3" id="btnTileAdvance">
                            <i class="fas fa-hand-holding-dollar fs-4"></i>
                            <strong class="fs-6 mt-1 d-block">Give Advance</strong>
                            <small class="opacity-90 d-block" style="font-size:0.72rem;">Naqad peshi cash</small>
                        </button>
                    </div>
                    <div class="col-6 col-sm-4 col-md-4 col-xl">
                        <button type="button" class="w-100 action-tile-btn tile-deduct-grad py-2 px-3" id="btnTileAbsent">
                            <i class="fas fa-calendar-xmark fs-4"></i>
                            <strong class="fs-6 mt-1 d-block">Mark Absent</strong>
                            <small class="opacity-90 d-block" style="font-size:0.72rem;">Chuti pay deduction</small>
                        </button>
                    </div>
                    <div class="col-6 col-sm-4 col-md-4 col-xl">
                        <button type="button" class="w-100 action-tile-btn py-2 px-3 text-white" id="btnTileDeduct" style="background: linear-gradient(135deg, #d97706, #b45309);">
                            <i class="fas fa-gavel fs-4"></i>
                            <strong class="fs-6 mt-1 d-block">Fine / Penalty</strong>
                            <small class="opacity-90 d-block" style="font-size:0.72rem;">Jurmana or damage</small>
                        </button>
                    </div>
                    <div class="col-6 col-sm-4 col-md-4 col-xl">
                        <button type="button" class="w-100 action-tile-btn tile-task-grad py-2 px-3" id="btnTileTask">
                            <i class="fas fa-list-check fs-4"></i>
                            <strong class="fs-6 mt-1 d-block">Assign Task</strong>
                            <small class="opacity-90 d-block" style="font-size:0.72rem;">Extra duty / overtime</small>
                        </button>
                    </div>
                    <div class="col-12 col-sm-4 col-md-4 col-xl">
                        <button type="button" class="w-100 action-tile-btn tile-bonus-grad py-2 px-3" id="btnTileBonus">
                            <i class="fas fa-gift fs-4"></i>
                            <strong class="fs-6 mt-1 d-block">Incentive Bonus</strong>
                            <small class="opacity-90 d-block" style="font-size:0.72rem;">Reward cash bonus</small>
                        </button>
                    </div>
                </div>
                <!-- 1. Executive Khata Metrics Strip -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border h-100">
                            <span class="text-muted small fw-bold text-uppercase d-block"><i class="fas fa-hand-holding-dollar text-danger me-1"></i> Total Advances</span>
                            <h4 class="fw-bold font-monospace text-danger mb-0 mt-1" id="khataTotAdvances">Rs. 0.00</h4>
                            <small class="text-muted" style="font-size:0.75rem;">Naqad peshi taken</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border h-100">
                            <span class="text-muted small fw-bold text-uppercase d-block"><i class="fas fa-money-bill text-warning me-1"></i> Daily Cash</span>
                            <h4 class="fw-bold font-monospace text-warning-emphasis mb-0 mt-1" id="khataTotDaily">Rs. 0.00</h4>
                            <small class="text-muted" style="font-size:0.75rem;">Rozana kharcha taken</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border h-100">
                            <span class="text-muted small fw-bold text-uppercase d-block"><i class="fas fa-gift text-success me-1"></i> Bonuses & Duties</span>
                            <h4 class="fw-bold font-monospace text-success mb-0 mt-1" id="khataTotBonuses">+Rs. 0.00</h4>
                            <small class="text-muted" style="font-size:0.75rem;">Extra tasks & rewards</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border h-100">
                            <span class="text-muted small fw-bold text-uppercase d-block"><i class="fas fa-scale-balanced text-primary me-1"></i> Net Khata Balance</span>
                            <h4 class="fw-bold font-monospace text-dark mb-0 mt-1" id="khataNetBalCard">Rs. 0.00</h4>
                            <small class="fw-bold text-muted" id="khataNetBalStatus" style="font-size:0.75rem;">Account clear</small>
                        </div>
                    </div>
                </div>

                <!-- 2. Real-Time Pro-Rata Position Banner inside Khata -->
                <div class="card border-0 bg-light rounded-4 p-3 mb-3 border shadow-sm" id="khataPositionBanner">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="badge bg-dark rounded-pill px-3 py-1 me-2 font-monospace"><i class="fas fa-clock me-1"></i> Live Till Today Position</span>
                            <strong class="text-dark" id="khataPositionText">Loading current earned status...</strong>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-sm btn-primary rounded-pill fw-bold px-3 btn-jump-to-salary">
                                <i class="fas fa-calculator me-1"></i> View Salary Slip & Settle &rarr;
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Left: Transactions Table with Running Balance (col-lg-8) -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 border h-100">
                            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <h5 class="mb-0 fw-bold text-dark">
                                        <i class="fas fa-book-bookmark me-2 text-danger"></i>Transaction Passbook
                                    </h5>
                                    <small class="text-muted">Chronological ledger with opening balance & live running balance</small>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <input type="text" class="form-control form-control-sm font-monospace border-2 rounded-pill px-3" id="searchLedgerInput" placeholder="Filter remarks / date..." style="width: 160px;">
                                    <select class="form-select form-select-sm font-monospace border-2 rounded-pill" id="filterLedgerMonth" style="width: 130px;">
                                        <option value="all">All Time</option>
                                        <!-- Months populated dynamically -->
                                    </select>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-dark fw-bold rounded-start-pill px-3" id="btnPrintKhata" title="Print 80mm Thermal Receipt">
                                            <i class="fas fa-print me-1 text-warning"></i> Thermal
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-dark fw-bold rounded-end-pill px-3" id="btnPrintKhataA4" title="Print A4 Full Statement">
                                            <i class="fas fa-file-lines me-1 text-primary"></i> A4
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Direct Quick Action Chips Row inside Passbook -->
                            <div class="px-3 py-2 bg-light border-bottom d-flex align-items-center gap-2 flex-wrap">
                                <span class="small fw-bold text-muted text-uppercase" style="font-size:0.75rem;"><i class="fas fa-bolt me-1 text-warning"></i>Quick Action:</span>
                                <button type="button" class="btn btn-xs btn-danger fw-bold rounded-pill px-2 py-1 btn-quick-cash" data-id="<?php echo $selectedEmpId; ?>" style="font-size:0.78rem;">
                                    <i class="fas fa-hand-holding-dollar me-1"></i> + Give Advance
                                </button>
                                <button type="button" class="btn btn-xs btn-warning fw-bold rounded-pill px-2 py-1 btn-quick-daily" data-id="<?php echo $selectedEmpId; ?>" style="font-size:0.78rem;">
                                    <i class="fas fa-money-bill me-1"></i> + Daily Cash
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-danger fw-bold rounded-pill px-2 py-1 btn-quick-deduct" data-id="<?php echo $selectedEmpId; ?>" style="font-size:0.78rem;">
                                    <i class="fas fa-calendar-xmark me-1"></i> + Chuti / Fine
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-success fw-bold rounded-pill px-2 py-1 btn-quick-bonus" data-id="<?php echo $selectedEmpId; ?>" style="font-size:0.78rem;">
                                    <i class="fas fa-gift me-1"></i> + Bonus
                                </button>
                            </div>
                            <div class="card-body p-0 table-responsive" style="max-height: 520px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                    <thead class="table-light sticky-top">
                                        <tr class="text-secondary small fw-bold text-uppercase">
                                            <th>Date & Time</th>
                                            <th>Type</th>
                                            <th>Remarks / Reason</th>
                                            <th class="text-end">Debit (- Taken)</th>
                                            <th class="text-end">Credit (+ Earned)</th>
                                            <th class="text-end">Balance (Peshi)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detailLedgerBody">
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">No transactions recorded yet.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Extra Tasks Widget (col-lg-4) -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 border h-100">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-bold text-dark">
                                        <i class="fas fa-tasks me-2 text-primary"></i>Assigned Tasks
                                    </h5>
                                    <small class="text-muted">Auto-credited when marked done</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3" id="btnQuickAddTask">
                                    <i class="fas fa-plus me-1"></i> New Task
                                </button>
                            </div>
                            <div class="card-body p-0" style="max-height: 520px; overflow-y: auto;">
                                <div class="list-group list-group-flush" id="detailTasksGroup">
                                    <div class="p-4 text-center text-muted">No active tasks assigned.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB B: MONTHLY SALARY SETTLEMENT & PRINT SLIP -->
            <div class="tab-pane fade" id="pill-salary" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 border p-4 bg-white">
                    <!-- Controls Bar: Month Picker, Quick Switchers & Actions -->
                    <div class="row g-3 align-items-center mb-4 border-bottom pb-4">
                        <div class="col-lg-5">
                            <label class="form-label fs-6 fw-bold text-dark mb-1">Select Pay Month *</label>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-outline-secondary btn-sm fw-bold px-2 py-2" id="btnDetailPrevMonth" title="Previous Month">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <input type="month" class="form-control form-control-lg border-2 shadow-sm font-monospace text-center" id="detailMonthInput" value="<?= date('Y-m') ?>">
                                <button type="button" class="btn btn-outline-secondary btn-sm fw-bold px-2 py-2" id="btnDetailNextMonth" title="Next Month">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger fw-bold text-nowrap ms-1" id="btnSetCurrentMonth" title="Go to Current Month">
                                    This Month
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-3 text-center">
                            <span class="text-muted small d-block mb-1">Clearance Status:</span>
                            <div id="sheetStatusPill">
                                <span class="badge bg-secondary fs-6 px-3 py-2 rounded-pill"><i class="fas fa-spinner fa-spin me-1"></i> Loading...</span>
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end d-flex gap-2 justify-content-lg-end">
                            <button type="button" class="btn btn-dark btn-lg fw-bold px-3 py-2 rounded-3 shadow-sm flex-fill" id="btnPrintSlip" title="Print 80mm Thermal Receipt">
                                <i class="fas fa-print me-1 text-warning"></i> Thermal Slip
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-lg fw-bold px-3 py-2 rounded-3 shadow-sm flex-fill" id="btnPrintSlipA4" title="Print Formal A4 Sheet">
                                <i class="fas fa-file-invoice me-1 text-primary"></i> A4 Voucher
                            </button>
                        </div>
                    </div>

                    <!-- Loading State Spinner -->
                    <div id="salaryLoadingBox" class="text-center py-5 d-none">
                        <div class="spinner-border text-danger" style="width: 3rem; height: 3rem;" role="status"></div>
                        <p class="text-muted mt-2 fw-semibold">Calculating official salary voucher & deductions...</p>
                    </div>

                    <!-- Visual Receipt Sheet Preview -->
                    <div id="salarySheetBox" class="p-4 rounded-4 border bg-light">
                        <!-- Voucher Store & Staff Header Banner -->
                        <div class="p-3 mb-3 bg-white rounded-4 border shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="staff-avatar-circle" id="voucherEmpAvatar" style="width: 52px; height: 52px; font-size: 1.25rem;">--</div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h4 class="mb-0 fw-bold text-dark" id="voucherEmpName">Employee Name</h4>
                                        <span class="badge bg-primary" id="voucherEmpRole">Role</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3 align-items-center mt-1 text-muted small">
                                        <span id="voucherEmpPhone"><i class="fas fa-phone me-1 text-success"></i>--</span>
                                        <span id="voucherEmpJoin"><i class="fas fa-calendar me-1 text-secondary"></i>Joined: --</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small d-block text-uppercase fw-bold">Settlement Month:</span>
                                <h4 class="mb-0 fw-bold text-dark font-monospace" id="voucherPayMonthText">September 2026</h4>
                            </div>
                        </div>

                        <!-- 1. Calculation Mode Switcher & Daily Rate Bar -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 bg-white p-2 rounded-4 border shadow-sm">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="small fw-bold text-dark text-uppercase px-2"><i class="fas fa-sliders me-1 text-primary"></i> Calculation Mode:</span>
                                <div class="btn-group p-1 bg-light rounded-pill border" role="group">
                                    <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 btn-mode-toggle active btn-primary" id="btnModeTillToday" data-mode="till_date">
                                        <i class="fas fa-calendar-day me-1"></i> Aaj Ki Tareekh Tak (<span id="btnModeDaysText">-- Din</span>)
                                    </button>
                                    <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 btn-mode-toggle btn-light text-secondary" id="btnModeFullMonth" data-mode="full_month">
                                        <i class="fas fa-calendar-check me-1 text-primary"></i> Pura Mahina (30 Din)
                                    </button>
                                </div>
                            </div>
                            <div class="text-muted small px-2 font-monospace">
                                <i class="fas fa-calculator me-1 text-info"></i> Daily Rate: <strong class="text-dark" id="voucherDailyRateText">Rs. 0.00/day</strong>
                            </div>
                        </div>

                        <!-- 2. Pro-Rata Formula Banner -->
                        <div class="card border-0 bg-primary-subtle rounded-4 p-3 mb-3 border shadow-sm" id="voucherFormulaBanner">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <strong class="text-primary-emphasis fs-6"><i class="fas fa-circle-info me-1"></i> <span id="formulaBannerTitle">Pro-Rata Salary Till Today (Aaj Ki Tareekh Tak)</span></strong>
                                    <div class="small text-muted mt-1" id="formulaBannerSubtext">
                                        Staff worked <strong id="formulaBannerDays">-- days</strong> (<span id="formulaBannerPeriod">--</span>). Earned Base = <span id="formulaBannerMath">--</span> = <strong id="formulaBannerEarned">Rs. 0.00</strong>.
                                    </div>
                                </div>
                                <div class="text-end font-monospace">
                                    <span class="badge bg-white text-primary border fs-6 px-3 py-2 rounded-pill shadow-sm" id="formulaBannerEarnedBadge">Earned Base: Rs. 0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pay Pause Impact Notice Banner -->
                        <div id="voucherPauseNotice" class="alert alert-warning d-none rounded-4 border-warning shadow-sm p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <strong class="fs-6 text-warning-emphasis"><i class="fas fa-pause-circle me-2"></i> Pay Status: <span id="voucherPauseTitle">Pay Paused</span></strong>
                                    <div class="small text-dark mt-1" id="voucherPauseDetails">Salary calculation excludes paused days.</div>
                                </div>
                                <span class="badge bg-white text-dark border border-warning fs-6 px-3 py-2 rounded-pill shadow-sm" id="voucherPauseBadge">0 Days Paused</span>
                            </div>
                        </div>

                        <!-- Already Settled Notice Alert Banner -->
                        <div id="voucherSettledNotice" class="alert alert-success d-none rounded-4 border-0 shadow-sm p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <strong class="fs-6 text-success"><i class="fas fa-check-circle me-2"></i> This Month's Salary is Settled & Paid</strong>
                                    <div class="small text-muted mt-1" id="voucherSettledDetails">Settled on -- by --</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger fw-bold px-3 py-2 rounded-pill shadow-sm" id="btnVoidSettlement">
                                    <i class="fas fa-undo me-1"></i> Re-Open / Void Settlement
                                </button>
                            </div>
                        </div>

                        <!-- Side-by-Side Detailed Breakdown (Gross Earnings vs Deductions) -->
                        <div class="row g-3 mb-4">
                            <!-- Gross Earnings Box (Green) -->
                            <div class="col-lg-6">
                                <div class="card border-success border-2 bg-white h-100 rounded-4 shadow-sm overflow-hidden">
                                    <div class="card-header bg-success text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-circle-plus me-2"></i> 1. GROSS EARNINGS & BONUSES</span>
                                        <span class="badge bg-white text-success font-monospace" id="headerGrossBadge">Rs. 0.00</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <!-- Base Salary Row -->
                                        <div class="p-2 mb-3 bg-light rounded-3 d-flex justify-content-between align-items-center border">
                                            <div>
                                                <strong class="text-dark d-block" id="sheetBaseSalaryLabel">Earned Base Salary (<span id="sheetDaysCountBadge">-- Days</span>)</strong>
                                                <small class="text-muted" id="sheetBaseSalarySubtext">Calculated per active working day</small>
                                            </div>
                                            <div class="text-end">
                                                <strong class="font-monospace text-dark fs-5" id="sheetBaseSalary">Rs. 0.00</strong>
                                                <small class="text-muted d-block font-monospace" id="sheetMonthlyBaseRef">(Monthly Contract: Rs. 0)</small>
                                            </div>
                                        </div>

                                        <!-- Extra Tasks Completed -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center px-1 mb-1">
                                                <span class="small fw-bold text-secondary text-uppercase">Completed Extra Tasks / Duties:</span>
                                                <strong class="font-monospace text-success" id="sheetExtraTasks">+Rs. 0.00</strong>
                                            </div>
                                            <div id="voucherTasksList" class="small bg-light p-2 rounded-3 border" style="max-height: 140px; overflow-y: auto;">
                                                <span class="text-muted">No extra tasks recorded for this month.</span>
                                            </div>
                                        </div>

                                        <!-- Incentive Bonuses -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center px-1 mb-1">
                                                <span class="small fw-bold text-secondary text-uppercase">Incentive Bonuses Awarded:</span>
                                                <strong class="font-monospace text-success" id="sheetIncentives">+Rs. 0.00</strong>
                                            </div>
                                            <div id="voucherIncentivesList" class="small bg-light p-2 rounded-3 border" style="max-height: 140px; overflow-y: auto;">
                                                <span class="text-muted">No incentive bonus recorded for this month.</span>
                                            </div>
                                        </div>

                                        <!-- Subtotal Banner -->
                                        <div class="p-3 mt-3 bg-success-subtle border border-success-subtle rounded-3 d-flex justify-content-between align-items-center">
                                            <strong class="text-success fs-6">TOTAL GROSS EARNINGS:</strong>
                                            <strong class="font-monospace text-success fs-4" id="sheetGrossEarnings">Rs. 0.00</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Deductions Box (Red) -->
                            <div class="col-lg-6">
                                <div class="card border-danger border-2 bg-white h-100 rounded-4 shadow-sm overflow-hidden">
                                    <div class="card-header bg-danger text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-circle-minus me-2"></i> 2. DEDUCTIONS & ADVANCES</span>
                                        <span class="badge bg-white text-danger font-monospace" id="headerDeductBadge">-Rs. 0.00</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <!-- Cash Advances List -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center px-1 mb-1">
                                                <span class="small fw-bold text-secondary text-uppercase">Cash Advances (Naqad Peshi):</span>
                                                <strong class="font-monospace text-danger" id="sheetAdvancesTotal">-Rs. 0.00</strong>
                                            </div>
                                            <div id="voucherAdvancesList" class="small bg-light p-2 rounded-3 border" style="max-height: 110px; overflow-y: auto;">
                                                <span class="text-muted">No cash advances taken this month.</span>
                                            </div>
                                        </div>

                                        <!-- Daily Payments List -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center px-1 mb-1">
                                                <span class="small fw-bold text-secondary text-uppercase">Daily Cash (Rozana Kharcha):</span>
                                                <strong class="font-monospace text-danger" id="sheetDailyPaymentsTotal">-Rs. 0.00</strong>
                                            </div>
                                            <div id="voucherDailyPaymentsList" class="small bg-light p-2 rounded-3 border" style="max-height: 110px; overflow-y: auto;">
                                                <span class="text-muted">No daily cash taken this month.</span>
                                            </div>
                                        </div>

                                        <!-- Official Absences (Chuti) List -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center px-1 mb-1">
                                                <span class="small fw-bold text-secondary text-uppercase">Marked Absences (Chuti Deductions):</span>
                                                <strong class="font-monospace text-danger" id="sheetAbsencesTotal">-Rs. 0.00</strong>
                                            </div>
                                            <div id="voucherAbsencesList" class="small bg-light p-2 rounded-3 border" style="max-height: 110px; overflow-y: auto;">
                                                <span class="text-muted">No absences logged this month.</span>
                                            </div>
                                        </div>

                                        <!-- Fines & Penalties List -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center px-1 mb-1">
                                                <span class="small fw-bold text-secondary text-uppercase">Fines & Penalties (Jurmana / Damage):</span>
                                                <strong class="font-monospace text-danger" id="sheetFinesTotal">-Rs. 0.00</strong>
                                            </div>
                                            <div id="voucherFinesList" class="small bg-light p-2 rounded-3 border" style="max-height: 110px; overflow-y: auto;">
                                                <span class="text-muted">No fines or penalties recorded this month.</span>
                                            </div>
                                        </div>

                                        <!-- Prior Unsettled Balance Row (if any) -->
                                        <div id="voucherPriorBox" class="d-none mb-3">
                                            <div class="p-2 rounded-3 bg-warning-subtle border border-warning-subtle d-flex justify-content-between align-items-center small">
                                                <span class="text-warning-emphasis fw-bold"><i class="fas fa-clock-rotate-left me-1"></i> Carried Forward Past Balance:</span>
                                                <strong class="font-monospace text-danger" id="sheetPriorTotal">-Rs. 0.00</strong>
                                            </div>
                                        </div>

                                        <!-- Subtotal Banner -->
                                        <div class="p-3 mt-3 bg-danger-subtle border border-danger-subtle rounded-3 d-flex justify-content-between align-items-center">
                                            <strong class="text-danger fs-6">TOTAL DEDUCTIONS:</strong>
                                            <strong class="font-monospace text-danger fs-4" id="sheetTotalPaid">-Rs. 0.00</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Final Net Cash Amount to Pay (Giant Dark Box or Warning Box) -->
                        <div class="card border-0 bg-dark text-white rounded-4 shadow-lg p-4 text-center mb-4 position-relative" id="sheetNetPayableCard">
                            <span class="fs-6 opacity-75 text-uppercase fw-semibold tracking-wide" id="sheetNetTitle">Final Net Cash Amount to Pay to Staff:</span>
                            <h1 class="display-3 fw-bold font-monospace text-warning mb-0 mt-1" id="sheetNetPayable">Rs. 0.00</h1>
                            <div class="small opacity-75 mt-2 font-monospace" id="sheetFormulaBreakdown">(Gross Earnings: Rs. 0.00 − Total Deductions: Rs. 0.00)</div>
                            <div class="alert alert-warning border-0 rounded-3 mt-3 mb-0 d-none text-dark fw-bold text-start p-3 shadow" id="sheetAdvanceExceededAlert">
                                <i class="fas fa-triangle-exclamation me-2 fs-5 text-danger"></i>
                                <span>Advance Exceeds Earned Salary! Staff has taken more in advances & daily cash than earned salary till today. Staff owes the store <strong class="font-monospace text-danger fs-6" id="sheetExceededAmt">Rs. 0.00</strong>.</span>
                            </div>
                        </div>

                        <!-- Action Box -->
                        <div class="text-center" id="sheetActionBox">
                            <button type="button" class="btn btn-success btn-lg px-5 py-3 fw-bold rounded-4 shadow fs-4" id="btnOpenApproveModal">
                                <i class="fas fa-check-double me-2"></i> Approve & Settle Salary Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB C: PAST SALARY VOUCHERS HISTORY -->
            <div class="tab-pane fade" id="pill-history" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 border bg-white p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-history me-2 text-info"></i>Past Salary Vouchers</h5>
                            <small class="text-muted">Record of previously approved and settled monthly payrolls</small>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                            <thead class="table-light">
                                <tr class="text-secondary small fw-bold text-uppercase">
                                    <th>Month</th>
                                    <th>Settled Date</th>
                                    <th class="text-end">Base Salary</th>
                                    <th class="text-end">Extra / Bonus</th>
                                    <th class="text-end">Deductions</th>
                                    <th class="text-end">Net Paid</th>
                                    <th>Settled By</th>
                                    <th class="text-center">Voucher</th>
                                </tr>
                            </thead>
                            <tbody id="pastVouchersBody">
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No past settlements recorded yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 4: ATTENDANCE, CHUTI & PAY PAUSE AUDIT LOG -->
            <div class="tab-pane fade" id="pill-pauses" role="tabpanel">
                <!-- Section 1: Marked Absences (Chuti) Log -->
                <div class="card border-0 shadow-sm rounded-4 border p-3 bg-white mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 border-bottom pb-2">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-calendar-xmark me-2 text-danger"></i>Absences & Chuti Log (Mulazim Ki Chutiyan)
                            </h5>
                            <small class="text-muted">Record of marked absences with automatic daily wage deductions</small>
                        </div>
                        <div>
                            <button type="button" class="btn btn-danger btn-sm fw-bold rounded-pill px-3 shadow-sm" id="btnTabMarkAbsent">
                                <i class="fas fa-plus me-1"></i> Mark Absent (Chuti) Now
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                            <thead class="table-light text-secondary small fw-bold text-uppercase">
                                <tr>
                                    <th>Absence Date</th>
                                    <th>Deduction Amount</th>
                                    <th>Status</th>
                                    <th>Reason / Remarks</th>
                                    <th>Logged By</th>
                                    <th>Created At</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tabAbsenceLogBody">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Loading absence records...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section 2: Pay Pause & Resume Log -->
                <div class="card border-0 shadow-sm rounded-4 border p-3 bg-white">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 border-bottom pb-2">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-pause-circle me-2 text-warning"></i>Pay Pause & Resume Audit Log
                            </h5>
                            <small class="text-muted">Salary accrual freeze intervals (Staff leave / put on hold)</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-warning btn-sm fw-bold rounded-pill px-3 shadow-sm btn-tab-pause-pay">
                                <i class="fas fa-pause me-1"></i> Pause Pay Now
                            </button>
                            <button type="button" class="btn btn-success btn-sm fw-bold rounded-pill px-3 shadow-sm btn-tab-resume-pay">
                                <i class="fas fa-play me-1"></i> Resume Pay Now
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                            <thead class="table-light text-secondary small fw-bold text-uppercase">
                                <tr>
                                    <th>Pause Date</th>
                                    <th>Resume Date</th>
                                    <th>Status</th>
                                    <th>Reason / Remarks</th>
                                    <th>Recorded By</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody id="tabPauseLogBody">
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Loading pause records...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- ========================================================================= -->
<!-- MODAL 1: ADD NEW EMPLOYEE                                                 -->
<!-- ========================================================================= -->
<div class="modal fade" id="newEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-user-plus me-2 text-danger"></i>Add New Employee
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="newEmployeeForm">
                <div class="modal-body p-4">
                    <div id="newEmpAlert" class="alert alert-danger d-none"></div>
                    
                    <div class="mb-3">
                        <label for="new_name" class="form-label fs-6 fw-bold text-dark">Full Name *</label>
                        <input type="text" class="form-control form-control-lg" id="new_name" name="name" required placeholder="e.g. Muhammad Ali">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="new_contact" class="form-label fs-6 fw-bold text-dark">Phone Number (Optional)</label>
                            <input type="text" class="form-control form-control-lg font-monospace" id="new_contact" name="contact" placeholder="0300-1234567 (Optional)">
                        </div>
                        <div class="col-6">
                            <label for="new_designation" class="form-label fs-6 fw-bold text-dark">Role / Job *</label>
                            <input type="text" class="form-control form-control-lg" id="new_designation" name="designation" required placeholder="Cashier, Salesman...">
                        </div>
                    </div>

                    <!-- Role Quick Suggestion Chips -->
                    <div class="d-flex flex-wrap gap-1 mb-3 align-items-center">
                        <span class="text-muted small" style="font-size: 0.72rem;">Quick Role:</span>
                        <span class="quick-chip role-preset-chip" data-role="Cashier">+ Cashier</span>
                        <span class="quick-chip role-preset-chip" data-role="Salesman">+ Salesman</span>
                        <span class="quick-chip role-preset-chip" data-role="Helper">+ Helper</span>
                        <span class="quick-chip role-preset-chip" data-role="Manager">+ Manager</span>
                        <span class="quick-chip role-preset-chip" data-role="Cleaner">+ Cleaner</span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="new_salary" class="form-label fs-6 fw-bold text-dark">Monthly Base Salary (Rs.) *</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text font-monospace">Rs.</span>
                                <input type="number" class="form-control font-monospace fw-bold" id="new_salary" name="salary" required min="0" placeholder="30000">
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="new_joining_date" class="form-label fs-6 fw-bold text-dark">Joining Date *</label>
                            <input type="date" class="form-control form-control-lg font-monospace" id="new_joining_date" name="joining_date" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="new_notes" class="form-label fs-6 fw-bold text-dark">CNIC / Address / Remarks (Optional)</label>
                        <textarea class="form-control" id="new_notes" name="notes" rows="2" placeholder="CNIC, home address, or identification notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-lg fw-bold px-5" id="btnSaveNewEmployee">Save Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: EDIT EMPLOYEE PROFILE                                            -->
<!-- ========================================================================= -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-user-pen me-2 text-danger"></i>Edit Employee Profile
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editEmployeeForm">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="modal-body p-4">
                    <div id="editEmpAlert" class="alert alert-danger d-none"></div>
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label fs-6 fw-bold text-dark">Full Name *</label>
                        <input type="text" class="form-control form-control-lg" id="edit_name" name="name" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_contact" class="form-label fs-6 fw-bold text-dark">Phone Number (Optional)</label>
                            <input type="text" class="form-control form-control-lg font-monospace" id="edit_contact" name="contact" placeholder="Optional">
                        </div>
                        <div class="col-6">
                            <label for="edit_designation" class="form-label fs-6 fw-bold text-dark">Role / Job *</label>
                            <input type="text" class="form-control form-control-lg" id="edit_designation" name="designation" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_salary" class="form-label fs-6 fw-bold text-dark">Monthly Base Salary (Rs.) *</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text font-monospace">Rs.</span>
                                <input type="number" class="form-control font-monospace fw-bold" id="edit_salary" name="salary" required min="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="edit_status" class="form-label fs-6 fw-bold text-dark">Employment Status</label>
                            <select class="form-select form-select-lg" id="edit_status" name="status">
                                <option value="active">Active (Working)</option>
                                <option value="inactive">Inactive (Left / Resigned)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_joining_date" class="form-label fs-6 fw-bold text-dark">Joining Date *</label>
                        <input type="date" class="form-control form-control-lg font-monospace" id="edit_joining_date" name="joining_date" required>
                    </div>
                    <div class="mb-2">
                        <label for="edit_notes" class="form-label fs-6 fw-bold text-dark">CNIC / Address / Remarks (Optional)</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-danger fw-bold" id="btnDeleteEmployeeFromEdit">
                        <i class="fas fa-trash-can me-1"></i> Delete Profile
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4" id="btnUpdateEmployee">Update Profile</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PAUSE EMPLOYEE PAY                                                 -->
<!-- ========================================================================= -->
<div class="modal fade" id="pausePayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold mb-0">
                    <i class="fas fa-pause-circle me-2 text-danger"></i>Pause Employee Pay (Salary Rokna)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="pausePayForm">
                <input type="hidden" id="pause_emp_id" name="employee_id">
                <div class="modal-body p-4">
                    <div id="pauseAlert" class="alert alert-danger d-none"></div>
                    
                    <div class="p-3 mb-3 rounded-3 bg-light border d-flex align-items-center gap-3">
                        <div class="staff-avatar-circle bg-warning text-dark fw-bold" style="width: 46px; height: 46px; font-size: 1.1rem;" id="pauseAvatar">--</div>
                        <div>
                            <span class="small text-muted d-block">Mulazim ka hisaab rokein:</span>
                            <strong class="text-dark fs-5" id="pauseEmpName">Employee Name</strong>
                        </div>
                    </div>

                    <div class="alert alert-warning small border-0 py-2 mb-3">
                        <i class="fas fa-info-circle me-1"></i> <strong>Important:</strong> Is tareekh ke baad se is employee ki mahana salary count nahi hogi jab tak aap dobara "Resume Pay" na karein.
                    </div>

                    <div class="mb-3">
                        <label for="pause_date" class="form-label fs-6 fw-bold text-dark">Effective Pause Date (Kis tareekh se pay roki jaye) *</label>
                        <input type="date" class="form-control form-control-lg font-monospace" id="pause_date" name="pause_date" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-2">
                        <label for="pause_reason" class="form-label fs-6 fw-bold text-dark">Reason / Remarks (Waja) *</label>
                        <textarea class="form-control" id="pause_reason" name="reason" rows="3" placeholder="e.g. Employee chutti par gaon gaya hai / Absent without notice / Sick leave..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-lg fw-bold px-5" id="btnConfirmPausePay">Confirm Pause Pay</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: RESUME EMPLOYEE PAY                                                -->
<!-- ========================================================================= -->
<div class="modal fade" id="resumePayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold mb-0">
                    <i class="fas fa-play-circle me-2 text-white"></i>Resume Employee Pay (Salary Dobara Shuru)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="resumePayForm">
                <input type="hidden" id="resume_emp_id" name="employee_id">
                <div class="modal-body p-4">
                    <div id="resumeAlert" class="alert alert-danger d-none"></div>
                    
                    <div class="p-3 mb-3 rounded-3 bg-light border d-flex align-items-center gap-3">
                        <div class="staff-avatar-circle bg-success text-white fw-bold" style="width: 46px; height: 46px; font-size: 1.1rem;" id="resumeAvatar">--</div>
                        <div>
                            <span class="small text-muted d-block">Mulazim ki salary dobara shuru karein:</span>
                            <strong class="text-dark fs-5" id="resumeEmpName">Employee Name</strong>
                        </div>
                    </div>

                    <div class="alert alert-success small border-0 py-2 mb-3">
                        <i class="fas fa-circle-check me-1"></i> <strong>Salary Shuru:</strong> Is tareekh se employee ki daily wages dobara calculate hona shuru ho jayein gi.
                    </div>

                    <div class="mb-3">
                        <label for="resume_date" class="form-label fs-6 fw-bold text-dark">Effective Resume Date (Wapsi ki tareekh) *</label>
                        <input type="date" class="form-control form-control-lg font-monospace" id="resume_date" name="resume_date" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-2">
                        <label for="resume_notes" class="form-label fs-6 fw-bold text-dark">Remarks / Notes</label>
                        <textarea class="form-control" id="resume_notes" name="notes" rows="2" placeholder="e.g. Duty resume kar li hai / Wapis duty par aa gaya..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-lg fw-bold px-5" id="btnConfirmResumePay">Confirm Resume Pay</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: PAY PAUSE & RESUME AUDIT LOG                                       -->
<!-- ========================================================================= -->
<div class="modal fade" id="pauseHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold mb-0">
                    <i class="fas fa-history me-2 text-warning"></i>Pay Pause & Resume Audit Log
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <strong class="fs-5 text-dark" id="pauseHistEmpName">Employee Log</strong>
                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" id="pauseHistCount">0 Records</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0" style="font-size:0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Pause Date</th>
                                <th>Resume Date</th>
                                <th>Status</th>
                                <th>Reason / Remarks</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody id="pauseHistTableBody">
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Loading history...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4">
                <button type="button" class="btn btn-dark px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 3: QUICK CASH ADVANCE (NAQAD PESHI)                                 -->
<!-- ========================================================================= -->
<div class="modal fade" id="quickCashModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-hand-holding-dollar me-2 text-danger"></i>Give Cash Advance (Naqad Peshi)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickCashForm">
                <input type="hidden" id="qc_employee_id" name="employee_id">
                <input type="hidden" id="qc_type" name="type" value="advance">
                
                <div class="modal-body p-4">
                    <div id="qcAlert" class="alert alert-danger d-none"></div>
                    
                    <!-- Selected Employee Banner -->
                    <div class="p-3 mb-3 rounded-3 bg-light border d-flex align-items-center gap-3">
                        <div class="staff-avatar-circle" style="width: 44px; height: 44px; font-size: 1.1rem;" id="qcAvatar">--</div>
                        <div>
                            <span class="small text-muted d-block">Giving advance money to:</span>
                            <strong class="text-dark fs-5" id="qcName">Employee Name</strong>
                        </div>
                    </div>
                    
                    <!-- Advance Date & Amount Row (Haji Dairy Architecture) -->
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label for="qc_date" class="form-label fs-6 fw-bold text-dark">Date *</label>
                            <input type="date" class="form-control form-control-lg bg-light" id="qc_date" name="transaction_date" value="<?= date('Y-m-d') ?>" required onchange="onAdvanceDateChange(this.value)">
                        </div>
                        <div class="col-6">
                            <label for="qc_amount" class="form-label fs-6 fw-bold text-dark">Advance (Rs.) *</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text font-monospace fs-4 bg-white fw-bold">Rs.</span>
                                <input type="number" class="form-control font-monospace fw-bold text-end fs-3" id="qc_amount" name="amount" required min="1" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Month Switch Warning Hint Container -->
                    <div id="qcMonthHintContainer" class="mb-2"></div>

                    <!-- Quick Amount Chips -->
                    <div class="d-flex flex-wrap gap-1 mb-3 align-items-center">
                        <span class="text-muted small" style="font-size: 0.72rem;">Quick Chips:</span>
                        <span class="quick-chip advance-chip" data-amt="500">+Rs. 500</span>
                        <span class="quick-chip advance-chip" data-amt="1000">+Rs. 1,000</span>
                        <span class="quick-chip advance-chip" data-amt="2000">+Rs. 2,000</span>
                        <span class="quick-chip advance-chip" data-amt="5000">+Rs. 5,000</span>
                        <span class="quick-chip advance-chip" data-amt="10000">+Rs. 10,000</span>
                    </div>
                    
                    <!-- Description / Remarks -->
                    <div class="mb-2">
                        <label for="qc_desc" class="form-label fs-6 fw-bold text-dark">Reason / Remarks (Optional)</label>
                        <input type="text" class="form-control form-control-lg" id="qc_desc" name="description" placeholder="e.g. Personal kharcha, emergency, lunch...">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-lg fw-bold px-5" id="btnSaveQuickCash">Save Advance Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 4: QUICK CHUTI / FINE (DEDUCTION) - WITH 1-DAY WAGE AUTO CALCULATOR -->
<!-- ========================================================================= -->
<div class="modal fade" id="quickDeductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-calendar-xmark me-2 text-warning"></i>Deduct for Absence / Fine (Chuti / Jurmana)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickDeductForm">
                <input type="hidden" id="qd_employee_id" name="employee_id">
                <input type="hidden" name="type" value="deduction">
                
                <div class="modal-body p-4">
                    <div id="qdAlert" class="alert alert-danger d-none"></div>
                    
                    <!-- Selected Employee Banner with Daily Wage -->
                    <div class="p-3 mb-3 rounded-3 bg-light border d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="staff-avatar-circle" style="width: 44px; height: 44px; font-size: 1.1rem;" id="qdAvatar">--</div>
                            <div>
                                <strong class="text-dark fs-5 d-block" id="qdName">Employee Name</strong>
                                <small class="text-muted" id="qdSalaryInfo">Monthly Salary: Rs. 0</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-warning-subtle text-warning-emphasis border font-monospace px-2 py-1" id="qdDailyWageBadge">1 Day = Rs. 0</span>
                        </div>
                    </div>

                    <!-- Deduction Reason Type Selection -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">Deduction Reason *</label>
                        <select class="form-select form-select-lg" id="qd_reason_type">
                            <option value="Chuti (Unpaid Leave / Absence)">Chuti (Unpaid Leave / Absence)</option>
                            <option value="Late Arrival (Takhir)">Late Arrival (Takhir)</option>
                            <option value="Loss / Damage (Nuqsan)">Loss / Damage (Nuqsan)</option>
                            <option value="Disciplinary Fine (Jurmana)">Disciplinary Fine (Jurmana)</option>
                            <option value="Other Deduction">Other Deduction</option>
                        </select>
                    </div>

                    <!-- Amount Input -->
                    <div class="mb-2">
                        <label for="qd_amount" class="form-label fs-6 fw-bold text-dark">Deduction Amount (Rs.) *</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text font-monospace fs-4 bg-white fw-bold">Rs.</span>
                            <input type="number" class="form-control font-monospace fw-bold text-end fs-3 text-danger" id="qd_amount" name="amount" required min="1" step="0.01" placeholder="0.00">
                        </div>
                    </div>

                    <!-- 1-Day, Half-Day Wage Auto Quick Chips -->
                    <div class="d-flex flex-wrap gap-1 mb-3 align-items-center">
                        <span class="text-muted small" style="font-size: 0.72rem;">Quick Chips:</span>
                        <span class="quick-chip wage-chip" id="chipHalfDay" data-factor="0.5">Half Day Wage</span>
                        <span class="quick-chip wage-chip" id="chipOneDay" data-factor="1.0">1 Full Day Wage</span>
                        <span class="quick-chip wage-chip" id="chipTwoDays" data-factor="2.0">2 Days Wage</span>
                    </div>

                    <!-- Description / Remarks -->
                    <div class="mb-2">
                        <label for="qd_desc" class="form-label fs-6 fw-bold text-dark">Details / Remarks (Optional)</label>
                        <input type="text" class="form-control form-control-lg" id="qd_desc" name="description" placeholder="e.g. 1 din chuti ki thi, baghair bataye leave...">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-lg fw-bold px-5" id="btnSaveQuickDeduct">Save Deduction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: MARK ABSENT (CHUTI DEDUCTION)                                      -->
<!-- ========================================================================= -->
<div class="modal fade" id="markAbsentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-calendar-xmark me-2"></i>Mark Employee Absent (Chuti Katoti)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="markAbsentForm">
                <input type="hidden" id="abs_employee_id" name="employee_id">
                
                <div class="modal-body p-4">
                    <div id="absAlert" class="alert alert-danger d-none"></div>
                    
                    <!-- Selected Employee Banner with Daily Wage -->
                    <div class="p-3 mb-3 rounded-3 bg-light border d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="staff-avatar-circle bg-danger text-white" style="width: 44px; height: 44px; font-size: 1.1rem;" id="absAvatar">--</div>
                            <div>
                                <strong class="text-dark fs-5 d-block" id="absName">Employee Name</strong>
                                <small class="text-muted" id="absSalaryInfo">Monthly Salary: Rs. 0</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-danger text-white font-monospace px-2 py-1" id="absDailyWageBadge">1 Day = Rs. 0</span>
                        </div>
                    </div>

                    <!-- Absence Date -->
                    <div class="mb-3">
                        <label for="abs_date" class="form-label fs-6 fw-bold text-dark">Absence Date (Chuti Ki Tareekh) *</label>
                        <input type="date" class="form-control form-control-lg font-monospace fw-bold" id="abs_date" name="absence_date" required value="<?= date('Y-m-d') ?>">
                        <small class="text-muted" style="font-size:0.75rem;">Tareekh muntakhib karein jis din mulazim ghair-hazir tha.</small>
                    </div>

                    <!-- Absence Duration Quick Chips -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">Chuti Type (Select Duration) *</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="abs_duration_type" id="absRadioFull" value="1.0" checked autocomplete="off">
                            <label class="btn btn-outline-danger fw-bold py-2" for="absRadioFull"><i class="fas fa-calendar-day me-1"></i> Full Day (100%)</label>

                            <input type="radio" class="btn-check" name="abs_duration_type" id="absRadioHalf" value="0.5" autocomplete="off">
                            <label class="btn btn-outline-danger fw-bold py-2" for="absRadioHalf"><i class="fas fa-adjust me-1"></i> Half Day (50%)</label>

                            <input type="radio" class="btn-check" name="abs_duration_type" id="absRadioCustom" value="custom" autocomplete="off">
                            <label class="btn btn-outline-secondary fw-bold py-2" for="absRadioCustom"><i class="fas fa-pen me-1"></i> Custom</label>
                        </div>
                    </div>

                    <!-- Amount Input -->
                    <div class="mb-3">
                        <label for="abs_amount" class="form-label fs-6 fw-bold text-dark">Katoti / Deduction Amount (Rs.) *</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text font-monospace fs-4 bg-white fw-bold text-danger">Rs.</span>
                            <input type="number" class="form-control font-monospace fw-bold text-end fs-3 text-danger" id="abs_amount" name="deduction_amount" required min="0" step="0.01" placeholder="0.00">
                        </div>
                        <small class="text-muted" id="absAmountHelp">Auto-calculated: Monthly Salary ÷ 30</small>
                    </div>

                    <!-- Reason / Remarks -->
                    <div class="mb-2">
                        <label for="abs_reason" class="form-label fs-6 fw-bold text-dark">Reason / Remarks (Wajah)</label>
                        <input type="text" class="form-control form-control-lg" id="abs_reason" name="reason" placeholder="e.g. Bila Itla Chuti, Tabiyat kharab, Shadi..." value="Bila Itla Chuti">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-lg fw-bold px-5 text-white" id="btnSaveMarkAbsent">
                        <i class="fas fa-check me-1"></i> Confirm & Mark Absent
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 5: QUICK WORK / BONUS                                               -->
<!-- ========================================================================= -->
<div class="modal fade" id="quickWorkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="quickWorkTitle">
                    <i class="fas fa-list-check me-2 text-danger"></i>Add Extra Work or Bonus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickWorkForm">
                <input type="hidden" id="qw_employee_id" name="employee_id">
                
                <div class="modal-body p-4">
                    <div id="qwAlert" class="alert alert-danger d-none"></div>
                    
                    <!-- Selected Employee Banner -->
                    <div class="p-3 mb-3 rounded-3 bg-light border d-flex align-items-center gap-3">
                        <div class="staff-avatar-circle" style="width: 44px; height: 44px; font-size: 1.1rem;" id="qwAvatar">--</div>
                        <div>
                            <span class="small text-muted d-block">Crediting to:</span>
                            <strong class="text-dark fs-5" id="qwName">Employee Name</strong>
                        </div>
                    </div>

                    <!-- Work Type Switcher -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">Select Credit Type *</label>
                        <div class="d-flex gap-2">
                            <div class="form-check p-3 border rounded-3 flex-fill bg-white">
                                <input class="form-check-input" type="radio" name="credit_type" id="qwTypeTask" value="task" checked>
                                <label class="form-check-label fw-bold ms-1" for="qwTypeTask">
                                    <i class="fas fa-list-check text-purple me-1"></i> Extra Task (Duty)
                                    <small class="d-block text-muted fw-normal" style="font-size: 0.75rem;">Unloading, cleaning, extra shift</small>
                                </label>
                            </div>
                            <div class="form-check p-3 border rounded-3 flex-fill bg-white">
                                <input class="form-check-input" type="radio" name="credit_type" id="qwTypeBonus" value="incentive">
                                <label class="form-check-label fw-bold ms-1" for="qwTypeBonus">
                                    <i class="fas fa-gift text-success me-1"></i> Incentive Bonus
                                    <small class="d-block text-muted fw-normal" style="font-size: 0.75rem;">Performance reward</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Amount Input -->
                    <div class="mb-3">
                        <label for="qw_amount" class="form-label fs-6 fw-bold text-dark">Compensation / Bonus (Rs.) *</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text font-monospace fs-4 bg-white fw-bold">Rs.</span>
                            <input type="number" class="form-control font-monospace fw-bold text-end fs-3" id="qw_amount" name="amount" required min="1" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    
                    <!-- Description / Remarks -->
                    <div class="mb-2">
                        <label for="qw_desc" class="form-label fs-6 fw-bold text-dark">Description (Optional)</label>
                        <input type="text" class="form-control form-control-lg" id="qw_desc" name="description" placeholder="e.g. Sunday unloading, warehouse deep cleaning, sales bonus...">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-lg fw-bold px-5 text-white" style="background: #7c3aed;" id="btnSaveQuickWork">Save Entry</button>
                </div>
            </form>
        </div>
    <!-- ========================================================================= -->
<!-- MODAL 6: APPROVE & SETTLE SALARY (WITH PAYMENT METHOD & CASH FLOW)        -->
<!-- ========================================================================= -->
<div class="modal fade" id="approveSettlementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-check-double me-2"></i>Approve & Settle Monthly Salary
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveSettlementForm">
                <div class="modal-body p-4">
                    <div id="settleAlert" class="alert alert-danger d-none"></div>

                    <!-- Summary Card -->
                    <div class="p-3 mb-3 rounded-3 bg-light border">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div>
                                <small class="text-muted d-block">Employee:</small>
                                <strong class="text-dark fs-5" id="smEmpName">--</strong>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">Pay Month:</small>
                                <span class="badge bg-dark font-monospace fs-6" id="smPayMonth">--</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-secondary small py-1">
                            <span id="smBaseSalaryLabel">Earned Base Salary:</span>
                            <span class="font-monospace text-dark fw-bold" id="smBaseSalary">Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-secondary small py-1">
                            <span>Extra Duties & Bonus:</span>
                            <span class="font-monospace text-success" id="smAdditions">+Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-secondary small py-1">
                            <span>Advances & Deductions:</span>
                            <span class="font-monospace text-danger" id="smDeductions">-Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 mt-1 border-top">
                            <strong class="text-dark fs-6" id="smNetLabel">Net Cash to Hand Over:</strong>
                            <strong class="font-monospace fs-3 text-success" id="smNetPayable">Rs. 0.00</strong>
                        </div>
                    </div>

                    <!-- Settlement Period Mode Radio -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">Clearance Period / Mode *</label>
                        <div class="d-flex gap-2">
                            <div class="form-check p-3 border rounded-3 flex-fill bg-white">
                                <input class="form-check-input" type="radio" name="settle_modal_mode" id="settleModalModeTillToday" value="till_date" checked>
                                <label class="form-check-label fw-bold ms-1" for="settleModalModeTillToday">
                                    <i class="fas fa-calendar-day text-success me-1"></i> Till Today (<span id="modalTillDaysText">14 Days</span>)
                                    <small class="d-block text-muted fw-normal font-monospace" id="modalTillPayableText">Net: Rs. 0.00</small>
                                </label>
                            </div>
                            <div class="form-check p-3 border rounded-3 flex-fill bg-white">
                                <input class="form-check-input" type="radio" name="settle_modal_mode" id="settleModalModeFullMonth" value="full_month">
                                <label class="form-check-label fw-bold ms-1" for="settleModalModeFullMonth">
                                    <i class="fas fa-calendar-check text-primary me-1"></i> Full Month (30 Days)
                                    <small class="d-block text-muted fw-normal font-monospace" id="modalFullPayableText">Net: Rs. 0.00</small>
                                </label>
                            </div>
                        </div>
                    </div>
                        <label class="form-label small fw-bold text-secondary mb-1">Select Payment Method / Source *</label>
                        <div class="d-flex gap-2">
                            <div class="form-check p-3 border rounded-3 flex-fill bg-white">
                                <input class="form-check-input" type="radio" name="settle_payment_method" id="settleMethodCash" value="cash" checked>
                                <label class="form-check-label fw-bold ms-1" for="settleMethodCash">
                                    <i class="fas fa-money-bill-wave text-success me-1"></i> Cash Drawer
                                    <small class="d-block text-muted fw-normal" style="font-size: 0.75rem;">Paid from Dukan Galla</small>
                                </label>
                            </div>
                            <div class="form-check p-3 border rounded-3 flex-fill bg-white">
                                <input class="form-check-input" type="radio" name="settle_payment_method" id="settleMethodBank" value="bank">
                                <label class="form-check-label fw-bold ms-1" for="settleMethodBank">
                                    <i class="fas fa-building-columns text-primary me-1"></i> Bank Account
                                    <small class="d-block text-muted fw-normal" style="font-size: 0.75rem;">Online / Bank Transfer</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Date -->
                    <div class="mb-3">
                        <label for="settle_payment_date" class="form-label small fw-bold text-secondary mb-1">Payment Date *</label>
                        <input type="date" class="form-control form-control-lg font-monospace" id="settle_payment_date" name="payment_date" required>
                    </div>

                    <!-- Settlement Notes / Reference -->
                    <div class="mb-2">
                        <label for="settle_notes" class="form-label small fw-bold text-secondary mb-1">Payment Remarks / Voucher Note (Optional)</label>
                        <input type="text" class="form-control form-control-lg" id="settle_notes" name="notes" placeholder="e.g. Paid in full cash by Store Owner">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-lg fw-bold px-5 shadow" id="btnConfirmSettlement">
                        <i class="fas fa-check-circle me-1"></i> Confirm & Settle Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
function showBootstrapModal(modalId) {
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

function hideBootstrapModal(modalId) {
    const rawId = modalId.replace('#', '');
    const el = document.getElementById(rawId);
    if (!el) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        try {
            const inst = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
            inst.hide();
            return;
        } catch(e) {}
    }
    if (window.jQuery && typeof $(el).modal === 'function') {
        $(el).modal('hide');
    }
}

window.showBootstrapModal = showBootstrapModal;
window.hideBootstrapModal = hideBootstrapModal;

$(document).ready(function() {
    let allEmployeesCache = [];
    let currentEmpId = <?php echo $selectedEmpId; ?>;
    let currentFilterMode = 'all';
    let currentCompiledSettlement = null;
    let currentLedgerData = null;
    let currentSalaryMode = 'till_date';
    let activeViewMode = localStorage.getItem('empViewMode') || 'cards'; // 'cards' or 'table'

    const today = new Date().toISOString().split('T')[0];
    const currentYearMonth = today.substring(0, 7);
    let currentGlobalMonth = $('#navMonthPicker').val() || '<?= date('Y-m') ?>';

    $('#new_joining_date').val(today);
    $('#detailMonthInput').val(currentYearMonth);

    // Initialize View Mode
    if (activeViewMode === 'table') {
        $('#btnViewCards').removeClass('active');
        $('#btnViewTable').addClass('active');
        $('#staffCardsGrid').addClass('d-none');
        $('#staffTableCard').removeClass('d-none');
    }

    // View Switcher Handlers
    $('#btnViewCards').on('click', function() {
        activeViewMode = 'cards';
        localStorage.setItem('empViewMode', 'cards');
        $(this).addClass('active');
        $('#btnViewTable').removeClass('active');
        $('#staffCardsGrid').removeClass('d-none');
        $('#staffTableCard').addClass('d-none');
    });

    $('#btnViewTable').on('click', function() {
        activeViewMode = 'table';
        localStorage.setItem('empViewMode', 'table');
        $(this).addClass('active');
        $('#btnViewCards').removeClass('active');
        $('#staffCardsGrid').addClass('d-none');
        $('#staffTableCard').removeClass('d-none');
    });

    // Populate past 12 months in Ledger month filter
    populateMonthOptions();
    function populateMonthOptions() {
        const $sel = $('#filterLedgerMonth');
        const now = new Date();
        for (let i = 0; i < 12; i++) {
            const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            const val = d.toISOString().substring(0, 7);
            const label = d.toLocaleString('en-US', { month: 'short', year: 'numeric' });
            $sel.append(`<option value="${val}" ${i === 0 ? 'selected' : ''}>${label}</option>`);
        }
    }

    // Load initial data
    updateMonthNavLabels(currentGlobalMonth);
    loadKPIs();
    loadStaffDirectory();

    // Refresh button
    $('#btnRefreshStaff').on('click', function() {
        $(this).find('i').addClass('fa-spin');
        loadKPIs();
        loadStaffDirectory(function() {
            $('#btnRefreshStaff i').removeClass('fa-spin');
        });
    });

    // Filter Pills Click
    $('.filter-pill').on('click', function() {
        $('.filter-pill').removeClass('active');
        $(this).addClass('active');
        currentFilterMode = $(this).data('filter');
        renderStaffViews();
    });

    // Search Input
    $('#staffSearchInput').on('input', function() {
        if ($(this).val().trim().length > 0) {
            $('#btnClearSearch').removeClass('d-none');
        } else {
            $('#btnClearSearch').addClass('d-none');
        }
        renderStaffViews();
    });

    $('#btnClearSearch').on('click', function() {
        $('#staffSearchInput').val('');
        $(this).addClass('d-none');
        renderStaffViews();
    });

    // Back to Directory (Clear URL state so reload stays on directory)
    $('#btnBackToDirectory').on('click', function() {
        currentEmpId = 0;
        const url = new URL(window.location.href);
        url.searchParams.delete('employee_id');
        url.searchParams.delete('tab');
        window.history.replaceState({}, '', url);
        $('#sectionEmployeeDetail').addClass('d-none');
        $('#sectionStaffDirectory').removeClass('d-none');
        $('html, body').animate({ scrollTop: 0 }, 200);
        loadStaffDirectory();
        loadKPIs();
    });

    // Employee Switcher Dropdown in Detail View (Preserve Active Tab)
    $('#detailEmpSwitcher').on('change', function() {
        const empId = parseInt($(this).val());
        if (empId > 0) {
            let activeTab = 'settlement';
            if ($('#pill-ledger-tab').hasClass('active')) activeTab = 'ledger';
            else if ($('#pill-pauses-tab').hasClass('active')) activeTab = 'pauses';
            else if ($('#pill-history-tab').hasClass('active')) activeTab = 'history';
            openEmployeeDetail(empId, activeTab);
        }
    });

    // -------------------------------------------------------------------------
    // 1. GLOBAL MONTH NAVIGATION & DATA LOADING (HAJI DAIRY ARCHITECTURE)
    // -------------------------------------------------------------------------

    function updateMonthNavLabels(monthStr) {
        if (!monthStr || !monthStr.includes('-')) return;
        const parts = monthStr.split('-');
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        
        const prevDate = new Date(y, m - 1, 1);
        const nextDate = new Date(y, m + 1, 1);
        
        const prevStr = prevDate.getFullYear() + '-' + String(prevDate.getMonth() + 1).padStart(2, '0');
        const nextStr = nextDate.getFullYear() + '-' + String(nextDate.getMonth() + 1).padStart(2, '0');
        
        const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        $('#labelNavPrevMonth').text(monthNames[prevDate.getMonth()] + ' ' + prevDate.getFullYear());
        $('#labelNavNextMonth').text(monthNames[nextDate.getMonth()] + ' ' + nextDate.getFullYear());
        
        $('#btnNavPrevMonth').data('month', prevStr);
        $('#btnNavNextMonth').data('month', nextStr);
        $('#btnDetailPrevMonth').data('month', prevStr);
        $('#btnDetailNextMonth').data('month', nextStr);
    }

    function changeGlobalMonth(newMonth) {
        if (!newMonth) return;
        currentGlobalMonth = newMonth;
        $('#navMonthPicker').val(newMonth);
        $('#detailMonthInput').val(newMonth);
        $('#qc_date').val(newMonth === '<?= date('Y-m') ?>' ? '<?= date('Y-m-d') ?>' : (newMonth + '-01'));
        $('#qcMonthHintContainer').empty();
        updateMonthNavLabels(newMonth);
        
        loadKPIs();
        loadStaffDirectory();
        if (currentEmpId > 0) {
            loadSalarySettlement(currentEmpId, newMonth, currentSalaryMode);
        }
    }

    $('#btnNavPrevMonth, #btnDetailPrevMonth').on('click', function() {
        const target = $(this).data('month');
        if (target) changeGlobalMonth(target);
    });

    $('#btnNavNextMonth, #btnDetailNextMonth').on('click', function() {
        const target = $(this).data('month');
        if (target) changeGlobalMonth(target);
    });

    $('#btnNavCurrentMonth, #btnSetCurrentMonth').on('click', function() {
        changeGlobalMonth('<?= date('Y-m') ?>');
    });

    $('#navMonthPicker').on('change', function() {
        changeGlobalMonth($(this).val());
    });

    $('#detailMonthInput').on('change', function() {
        changeGlobalMonth($(this).val());
    });

    // Helper for checking date-month mismatch in advance form
    window.onAdvanceDateChange = function(dateVal) {
        if (!dateVal) return;
        const dateMonth = dateVal.substring(0, 7);
        const $hint = $('#qcMonthHintContainer');
        if (dateMonth !== currentGlobalMonth) {
            $hint.html(`
                <div class="alert alert-warning py-2 px-3 small rounded-3 mb-0 d-flex align-items-center gap-2">
                    <i class="fas fa-info-circle text-warning fs-5"></i>
                    <div>
                        <strong>Notice:</strong> This date falls in <strong>${dateMonth}</strong>. It will be recorded in <strong>${dateMonth}</strong>'s ledger (different from active view: ${currentGlobalMonth}).
                    </div>
                </div>
            `);
        } else {
            $hint.empty();
        }
    };

    function loadKPIs() {
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/employees.php?action=summary_kpi&month=${currentGlobalMonth}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#kpiTotalStaff').text(res.total_staff);
                    $('#kpiActiveSubtext').text(`${res.active_staff} active on duty`);
                    $('#kpiTotalPayroll').text(`Rs. ${parseFloat(res.total_payroll).toLocaleString('en-PK', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`);
                    $('#kpiMonthAdvances').text(`Rs. ${parseFloat(res.month_advances).toLocaleString('en-PK', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`);
                    $('#kpiSettledCount').text(`${res.settled_staff_count} / ${res.active_staff}`);
                    $('#kpiSettledSubtext').text(`${res.settled_staff_count} / ${res.active_staff} cleared for ${res.current_month}`);
                    
                    $('#kpiStoreOwes').text(`Rs. ${parseFloat(res.total_store_owes || 0).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#kpiStoreOwesSubtext').text(`Accrued wages & net balances till day ${res.elapsed_days}`);
                    
                    $('#kpiStaffOwes').text(`Rs. ${parseFloat(res.total_staff_owes || 0).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#kpiStaffOwesSubtext').text(parseFloat(res.total_staff_owes || 0) > 0 ? 'Staff owes excess advance money' : 'No excess advances owed');
                }
            }
        });
    }

    function loadStaffDirectory(callback) {
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/employees.php?action=list&month=${currentGlobalMonth}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    allEmployeesCache = res.employees;
                    updateEmpSwitcherDropdown();
                    renderStaffViews();
                    
                    // Check URL for employee_id and tab to preserve state on reload
                    const urlParams = new URLSearchParams(window.location.search);
                    const urlEmpId = parseInt(urlParams.get('employee_id') || 0, 10);
                    const urlTab = urlParams.get('tab') || '<?php echo $initialTab ?: "settlement"; ?>' || 'settlement';
                    if (urlEmpId > 0 && !currentEmpId) {
                        currentEmpId = urlEmpId;
                    }
                    
                    if (currentEmpId > 0) {
                        openEmployeeDetail(currentEmpId, urlTab);
                        if (urlTab === 'settlement') {
                            $('#pill-salary-tab').tab('show');
                            $('#btnCalculateSalary').trigger('click');
                        }
                    }
                }
                if (typeof callback === 'function') callback();
            },
            error: function() {
                if (typeof callback === 'function') callback();
            }
        });
    }

    function updateEmpSwitcherDropdown() {
        const $sel = $('#detailEmpSwitcher');
        $sel.empty();
        allEmployeesCache.forEach(emp => {
            $sel.append(`<option value="${emp.id}">${emp.name} (${emp.designation})</option>`);
        });
    }

    // -------------------------------------------------------------------------
    // 2. RENDER STAFF VIEWS (CARDS & COMPACT TABLE)
    // -------------------------------------------------------------------------
    function renderStaffViews() {
        const query = $('#staffSearchInput').val().toLowerCase().trim();

        let filtered = allEmployeesCache.filter(emp => {
            const matchesQuery = emp.name.toLowerCase().includes(query) || 
                                 emp.contact.toLowerCase().includes(query) || 
                                 emp.designation.toLowerCase().includes(query);
            
            let matchesFilter = true;
            if (currentFilterMode === 'active') {
                matchesFilter = emp.status === 'active';
            } else if (currentFilterMode === 'inactive') {
                matchesFilter = emp.status === 'inactive';
            } else if (currentFilterMode === 'advances') {
                matchesFilter = parseFloat(emp.month_advances || 0) > 0 || parseFloat(emp.net_advance_balance || 0) > 0;
            }

            return matchesQuery && matchesFilter;
        });

        // 1. Render Cards View
        if (filtered.length === 0) {
            $('#staffCardsGrid').html(`
                <div class="col-12 text-center py-5 text-muted bg-white rounded-4 border">
                    <i class="fas fa-users-slash display-5 mb-2"></i>
                    <h5>No employees found</h5>
                    <p class="small text-muted mb-0">Try changing your search terms or filter.</p>
                </div>
            `);
            $('#staffTableBody').html(`
                <tr><td colspan="9" class="text-center py-4 text-muted">No employees found.</td></tr>
            `);
            return;
        }

        let cardsHtml = '';
        let tableHtml = '';

        filtered.forEach((emp, idx) => {
            const salary = parseFloat(emp.current_salary || 0);
            const dailyWage = parseFloat(emp.daily_wage || 0);
            const daysWorked = parseInt(emp.days_worked_till_today || 1);
            const earnedGrossTillToday = parseFloat(emp.gross_till_today || 0);
            const monthAdv = parseFloat(emp.month_advances || 0);
            const netDueToday = parseFloat(emp.net_due_till_today || 0);
            const fullMonthNet = parseFloat(emp.full_month_net_payable || 0);
            const isSettled = emp.is_settled_month;

            let statusBanner = '';
            if (isSettled) {
                statusBanner = `
                    <div class="p-2 rounded-3 mb-3 d-flex justify-content-between align-items-center bg-success-subtle border border-success-subtle">
                        <span class="text-success fw-bold small"><i class="fas fa-check-circle me-1"></i> Current Month Settled</span>
                        <span class="badge bg-success font-monospace">Closed</span>
                    </div>
                `;
            } else if (netDueToday >= 0) {
                statusBanner = `
                    <div class="p-2 rounded-3 mb-3 bg-success-subtle border border-success-subtle">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-success small fw-bold"><i class="fas fa-hand-holding-dollar me-1"></i> Dukan Ne Dena Hai:</span>
                            <strong class="font-monospace text-success fs-5">Rs. ${netDueToday.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top border-success-subtle small text-muted" style="font-size: 0.75rem;">
                            <span>Full Month Est: Rs. ${fullMonthNet.toLocaleString('en-PK', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</span>
                            <span>Day ${daysWorked} / 30</span>
                        </div>
                    </div>
                `;
            } else {
                statusBanner = `
                    <div class="p-2 rounded-3 mb-3 bg-danger-subtle border border-danger-subtle">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-danger small fw-bold"><i class="fas fa-triangle-exclamation me-1"></i> Staff Ne Dena Hai:</span>
                            <strong class="font-monospace text-danger fs-5">-Rs. ${Math.abs(netDueToday).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top border-danger-subtle small text-danger" style="font-size: 0.75rem;">
                            <span>Advance Exceeds Earned</span>
                            <span>Day ${daysWorked} / 30</span>
                        </div>
                    </div>
                `;
            }

            const isPaused = emp.pay_status === 'paused';
            const statusBadge = emp.status === 'active' ? 
                '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>' : 
                '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inactive</span>';

            const payBadge = isPaused ? 
                `<span class="badge bg-warning text-dark border px-2 py-1" title="Pay paused since ${emp.pay_pause_date || ''}"><i class="fas fa-pause-circle me-1"></i>Paused</span>` : 
                '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-circle-check me-1"></i>Pay Active</span>';

            // Card Item (Clicking card body opens Passbook/Ledger; Buttons open instant action)
            cardsHtml += `
                <div class="col-md-6 col-xl-4">
                    <div class="staff-card-simple p-3 shadow-sm h-100 d-flex flex-column justify-content-between">
                        <!-- Clickable Upper Card Body -->
                        <div class="staff-card-clickable" style="cursor: pointer;" onclick="openEmployeeDetail(${emp.id}, 'ledger')" title="Click to view full Ledger Passbook">
                            <!-- Header of Card -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="staff-avatar-circle position-relative">
                                        ${emp.name.substring(0, 2).toUpperCase()}
                                        <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" style="font-size: 0.65rem;">#${idx + 1}</span>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 fw-bold text-dark">${escapeHtml(emp.name)}</h5>
                                        <div class="d-flex gap-1 align-items-center mt-1 flex-wrap">
                                            <span class="badge bg-light text-dark border">${escapeHtml(emp.designation)}</span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 font-monospace fw-bold">
                                                Rs. ${salary.toLocaleString('en-PK')}/mo
                                            </span>
                                            ${statusBadge}
                                            ${payBadge}
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1 shadow-sm" onclick="openEditModal(${emp.id}); event.stopPropagation();" title="Edit Profile">
                                    <i class="fas fa-user-pen text-primary me-1"></i> Edit
                                </button>
                            </div>

                            <!-- Info Box -->
                            <div class="bg-light p-2 rounded-3 mb-3 small">
                                <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle">
                                    <span class="text-muted"><i class="fas fa-wallet me-1 text-primary"></i>Monthly Base:</span>
                                    <strong class="font-monospace text-primary fw-bold">Rs. ${salary.toLocaleString('en-PK')} <span class="text-muted fw-normal">(Rs. ${dailyWage.toFixed(2)}/day)</span></strong>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle">
                                    <span class="text-muted"><i class="fas fa-calendar-check me-1 text-secondary"></i>Earned Till Today (${daysWorked}d):</span>
                                    <strong class="font-monospace text-dark">Rs. ${earnedGrossTillToday.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                                </div>
                                <div class="d-flex justify-content-between py-1">
                                    <span class="text-muted"><i class="fas fa-hand-holding-dollar me-1 text-danger"></i>Advances (Peshi):</span>
                                    <strong class="font-monospace text-danger">-Rs. ${monthAdv.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                                </div>
                            </div>

                            <!-- Live Net Financial Status Banner -->
                            ${statusBanner}
                        </div>

                        <!-- Direct Action Buttons -->
                        <div class="d-flex gap-2 mt-2 pt-2 border-top flex-wrap">
                            <button type="button" class="btn btn-outline-primary btn-sm fw-bold flex-fill rounded-3 py-1 btn-card-edit" onclick="openEditModal(${emp.id}); event.stopPropagation();" title="Edit Employee Profile">
                                <i class="fas fa-user-pen me-1"></i> Edit
                            </button>
                            ${isPaused ? `
                                <button type="button" class="btn btn-success btn-sm fw-bold flex-fill rounded-3 py-1 btn-card-resume" onclick="openResumePayModal(${emp.id}); event.stopPropagation();" title="Resume Pay">
                                    <i class="fas fa-play me-1"></i> Resume
                                </button>
                            ` : `
                                <button type="button" class="btn btn-outline-warning btn-sm fw-bold flex-fill rounded-3 py-1 btn-card-pause" onclick="openPausePayModal(${emp.id}); event.stopPropagation();" title="Pause Pay">
                                    <i class="fas fa-pause me-1"></i> Pause
                                </button>
                            `}
                            <button type="button" class="btn btn-outline-danger btn-sm fw-bold flex-fill rounded-3 py-1 btn-card-kharcha" data-id="${emp.id}" onclick="openQuickCashModal(${emp.id}, 'advance'); event.stopPropagation();" title="Give Cash Advance (Naqad Peshi)">
                                <i class="fas fa-hand-holding-dollar me-1"></i> Kharcha
                            </button>
                            <button type="button" class="btn btn-dark btn-sm fw-bold flex-fill rounded-3 py-1 btn-card-hissaab" data-id="${emp.id}" onclick="openEmployeeDetail(${emp.id}, 'settlement'); event.stopPropagation();" title="View Full Khata & Salary Slip">
                                <i class="fas fa-file-invoice-dollar me-1 text-warning"></i> Hissaab
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Table Row Item
            tableHtml += `
                <tr style="cursor: pointer;" onclick="openEmployeeDetail(${emp.id}, 'ledger')">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="staff-avatar-circle" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                ${emp.name.substring(0, 2).toUpperCase()}
                            </div>
                            <strong class="text-dark">${escapeHtml(emp.name)}</strong>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark border">${escapeHtml(emp.designation)}</span></td>
                    <td class="font-monospace small">${escapeHtml(emp.contact || '--')}</td>
                    <td class="text-end font-monospace fw-bold text-primary">Rs. ${salary.toLocaleString('en-PK', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
                    <td class="text-end font-monospace text-dark">Rs. ${earnedGrossTillToday.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end font-monospace fw-bold ${monthAdv > 0 ? 'text-danger' : 'text-muted'}">
                        ${monthAdv > 0 ? '-Rs. ' + monthAdv.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'Rs. 0.00'}
                    </td>
                    <td class="text-end font-monospace fw-bold ${netDueToday >= 0 ? 'text-success' : 'text-danger'}">
                        ${netDueToday >= 0 ? 'Rs. ' + netDueToday.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-Rs. ' + Math.abs(netDueToday).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    </td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">${payBadge}</td>
                    <td class="text-end" onclick="event.stopPropagation();">
                        <div class="btn-group btn-group-sm shadow-sm">
                            <button type="button" class="btn btn-outline-primary btn-card-edit" onclick="openEditModal(${emp.id}); event.stopPropagation();" title="Edit Profile"><i class="fas fa-user-pen"></i></button>
                            ${isPaused ? `
                                <button type="button" class="btn btn-outline-success" onclick="openResumePayModal(${emp.id}); event.stopPropagation();" title="Resume Pay"><i class="fas fa-play"></i></button>
                            ` : `
                                <button type="button" class="btn btn-outline-warning btn-card-pause" onclick="openPausePayModal(${emp.id}); event.stopPropagation();" title="Pause Pay"><i class="fas fa-pause"></i></button>
                            `}
                            <button type="button" class="btn btn-outline-danger btn-card-kharcha" data-id="${emp.id}" onclick="openQuickCashModal(${emp.id}, 'advance'); event.stopPropagation();" title="Kharcha"><i class="fas fa-hand-holding-dollar"></i></button>
                            <button type="button" class="btn btn-dark btn-card-hissaab" data-id="${emp.id}" onclick="openEmployeeDetail(${emp.id}, 'settlement'); event.stopPropagation();" title="Hissaab"><i class="fas fa-file-invoice-dollar text-warning"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#staffCardsGrid').html(cardsHtml);
        $('#staffTableBody').html(tableHtml);
    }

    // -------------------------------------------------------------------------
    // 3. OPEN EMPLOYEE DETAIL VIEW (FAIL-PROOF GLOBAL HANDLER)
    // -------------------------------------------------------------------------
    function openEmployeeDetail(empId, tabToActivate = 'settlement') {
        currentEmpId = parseInt(empId, 10);
        let emp = allEmployeesCache.find(e => e.id == currentEmpId);

        const doOpen = function(targetEmp) {
            // Persist current employee & tab into URL
            const url = new URL(window.location.href);
            url.searchParams.set('employee_id', currentEmpId);
            url.searchParams.set('tab', tabToActivate);
            window.history.replaceState({}, '', url);

            $('#detailEmpSwitcher').val(currentEmpId);
            $('#detailAvatar').text(targetEmp.name.substring(0, 2).toUpperCase());
            $('#detailName').text(targetEmp.name);
            $('#detailRole').text(targetEmp.designation);
            $('#detailPhone').text(targetEmp.contact || '--');
            $('#detailSalary').text(`Rs. ${parseFloat(targetEmp.current_salary || 0).toLocaleString('en-PK', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`);
            $('#detailJoin').text(targetEmp.joining_date);
            $('#detailDailyWage').text(`Rs. ${parseFloat(targetEmp.daily_wage || 0).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);

            const statusBadge = targetEmp.status === 'active' ? 
                '<span class="badge bg-success px-2 py-1">Active</span>' : 
                '<span class="badge bg-secondary px-2 py-1">Inactive</span>';
            $('#detailStatusBadge').html(statusBadge);

            if (targetEmp.pay_status === 'paused') {
                $('#detailPayStatusBadge').html(`<span class="badge bg-warning text-dark px-3 py-1 border border-warning" title="Reason: ${escapeHtml(targetEmp.pay_pause_reason || '')}"><i class="fas fa-pause-circle me-1"></i>Pay Paused (${targetEmp.pay_pause_date || ''})</span>`);
                $('#btnHeaderPausePay').addClass('d-none');
                $('#btnHeaderResumePay').removeClass('d-none');
            } else {
                $('#detailPayStatusBadge').html('<span class="badge bg-success px-3 py-1"><i class="fas fa-circle-check me-1"></i>Pay Active</span>');
                $('#btnHeaderPausePay').removeClass('d-none');
                $('#btnHeaderResumePay').addClass('d-none');
            }

            // Switch Views
            $('#sectionStaffDirectory').addClass('d-none');
            $('#sectionEmployeeDetail').removeClass('d-none');
            $('html, body').animate({ scrollTop: 0 }, 200);

            // Tab Activation (Clear & Set show active)
            $('#pill-ledger-tab, #pill-salary-tab, #pill-history-tab, #pill-pauses-tab').removeClass('active').attr('aria-selected', 'false');
            $('#pill-ledger, #pill-salary, #pill-history, #pill-pauses').removeClass('show active');

            if (tabToActivate === 'ledger') {
                $('#pill-ledger-tab').addClass('active').attr('aria-selected', 'true');
                $('#pill-ledger').addClass('show active');
            } else if (tabToActivate === 'pauses') {
                $('#pill-pauses-tab').addClass('active').attr('aria-selected', 'true');
                $('#pill-pauses').addClass('show active');
            } else if (tabToActivate === 'history') {
                $('#pill-history-tab').addClass('active').attr('aria-selected', 'true');
                $('#pill-history').addClass('show active');
            } else {
                $('#pill-salary-tab').addClass('active').attr('aria-selected', 'true');
                $('#pill-salary').addClass('show active');
            }

            // Load Ledger, Tasks, Settlement History, Salary Calculation, Absences & Pauses Log
            loadEmployeeLedger(currentEmpId);
            loadEmployeeTasks(currentEmpId);
            loadSettlementHistory(currentEmpId);
            loadSalarySettlement(currentEmpId, $('#detailMonthInput').val() || currentYearMonth);
            loadEmployeeAbsences(currentEmpId);
            loadEmployeePauses(currentEmpId);
        };

        if (!emp) {
            loadStaffDirectory(function() {
                const retryEmp = allEmployeesCache.find(e => e.id == currentEmpId);
                if (retryEmp) doOpen(retryEmp);
            });
            return;
        }

        doOpen(emp);
    }
    window.openEmployeeDetail = openEmployeeDetail;

    // Track tab switching to synchronize URL query parameters
    $(document).on('shown.bs.tab', 'button[data-bs-toggle="pill"]', function(e) {
        if (!currentEmpId) return;
        const targetId = $(e.target).attr('id');
        let tab = 'settlement';
        if (targetId === 'pill-ledger-tab') tab = 'ledger';
        else if (targetId === 'pill-salary-tab') tab = 'settlement';
        else if (targetId === 'pill-history-tab') tab = 'history';
        else if (targetId === 'pill-pauses-tab') tab = 'pauses';

        const url = new URL(window.location.href);
        url.searchParams.set('employee_id', currentEmpId);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    });

    $(document).on('click', '.btn-open-detail', function(e) {
        e.stopPropagation();
        const empId = $(this).data('id');
        openEmployeeDetail(empId, 'ledger');
    });

    $(document).on('click', '.btn-card-hissaab', function(e) {
        e.stopPropagation();
        const empId = $(this).data('id');
        openEmployeeDetail(empId, 'settlement');
    });

    $(document).on('click', '.btn-card-kharcha', function(e) {
        e.stopPropagation();
        const empId = $(this).data('id');
        openQuickCashModal(empId, 'advance');
    });

    $(document).on('click', '.btn-card-task', function(e) {
        e.stopPropagation();
        const empId = $(this).data('id');
        openQuickWorkModal(empId, 'task');
    });

    $('#btnEditSelectedEmp').on('click', function() {
        if (currentEmpId > 0) {
            openEditModal(currentEmpId);
        }
    });

    // -------------------------------------------------------------------------
    // 4. LOAD LEDGER & RUNNING BALANCE PASSBOOK
    // -------------------------------------------------------------------------
    $('#filterLedgerMonth').on('change', function() {
        if (currentEmpId > 0) {
            loadEmployeeLedger(currentEmpId);
        }
    });

    // Filter Passbook table by search text
    $('#searchLedgerInput').on('input keyup', function() {
        const query = $(this).val().toLowerCase().trim();
        $('#detailLedgerBody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(query) > -1);
        });
    });

    // Jump from Khata to Salary Voucher Tab
    $(document).on('click', '.btn-jump-to-salary', function() {
        const triggerEl = document.querySelector('#pill-salary-tab');
        if (triggerEl) {
            bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        }
        loadSalarySettlement(currentEmpId, $('#detailMonthInput').val() || currentYearMonth, currentSalaryMode);
    });

    function loadEmployeeLedger(empId) {
        const month = $('#filterLedgerMonth').val();
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/employees.php?action=ledger&employee_id=${empId}&month=${month}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    currentLedgerData = res;

                    // 1. Update Executive Metric Cards
                    const breakdown = res.category_breakdown || {};
                    const totAdv = parseFloat(breakdown.total_advances || 0);
                    const totDaily = parseFloat(breakdown.total_daily_cash || 0);
                    const totBonuses = parseFloat(breakdown.total_bonuses || 0) + parseFloat(breakdown.total_tasks || 0);

                    $('#khataTotAdvances').text(`Rs. ${totAdv.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#khataTotDaily').text(`Rs. ${totDaily.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    $('#khataTotBonuses').text(`+Rs. ${totBonuses.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);

                    const bal = parseFloat(res.net_balance);
                    if (bal > 0) {
                        $('#khataNetBalCard').text(`Rs. ${bal.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`).addClass('text-danger').removeClass('text-success text-dark');
                        $('#khataNetBalStatus').html('<span class="badge bg-danger px-2 py-1"><i class="fas fa-hand-holding-dollar me-1"></i>Staff Owes Peshi</span>');

                        $('#detailBalance').text(`-Rs. ${bal.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`).addClass('text-danger').removeClass('text-success text-dark');
                        $('#detailBalanceSubtext').text("Mulazim par advance / peshi baqaya hai").addClass('text-danger').removeClass('text-success text-muted');
                        $('#detailNetPillBadge').html('<span class="badge bg-danger text-white px-2 py-1"><i class="fas fa-triangle-exclamation me-1"></i>Peshi Baqaya</span>');
                    } else if (bal < 0) {
                        $('#khataNetBalCard').text(`Rs. ${Math.abs(bal).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`).addClass('text-success').removeClass('text-danger text-dark');
                        $('#khataNetBalStatus').html('<span class="badge bg-success px-2 py-1"><i class="fas fa-gift me-1"></i>Store Credit</span>');

                        $('#detailBalance').text(`+Rs. ${Math.abs(bal).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`).addClass('text-success').removeClass('text-danger text-dark');
                        $('#detailBalanceSubtext').text("Dukan ne mulazim ko credit ada karna hai").addClass('text-success').removeClass('text-danger text-muted');
                        $('#detailNetPillBadge').html('<span class="badge bg-success text-white px-2 py-1"><i class="fas fa-check-circle me-1"></i>Dukan Ne Dena Hai</span>');
                    } else {
                        $('#khataNetBalCard').text(`Rs. 0.00`).addClass('text-dark').removeClass('text-danger text-success');
                        $('#khataNetBalStatus').html('<span class="badge bg-secondary px-2 py-1"><i class="fas fa-check me-1"></i>Account Clear</span>');

                        $('#detailBalance').text(`Rs. 0.00`).addClass('text-dark').removeClass('text-danger text-success');
                        $('#detailBalanceSubtext').text("Khata mukammal saaf aur barabar hai").addClass('text-muted').removeClass('text-danger text-success');
                        $('#detailNetPillBadge').html('<span class="badge bg-secondary text-white px-2 py-1"><i class="fas fa-check me-1"></i>Clear</span>');
                    }

                    // 2. Real-Time Pro-Rata Position Banner
                    const cCalc = res.current_month_calc;
                    const emp = allEmployeesCache.find(e => e.id == empId) || {};
                    if (cCalc && cCalc.is_current_month) {
                        $('#khataPositionBanner').removeClass('d-none');
                        if (cCalc.advance_exceeded) {
                            $('#khataPositionText').html(`
                                <span class="text-danger fw-bold"><i class="fas fa-triangle-exclamation me-1"></i> Advance Exceeds Earned Salary!</span>
                                Till today (Day ${cCalc.days_worked}): <strong>${escapeHtml(emp.name || 'Staff')}</strong> has earned <strong>Rs. ${parseFloat(cCalc.gross_earnings).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong> but taken <strong>Rs. ${parseFloat(cCalc.total_deductions).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong> in advances & deductions. Staff owes store <strong class="text-danger">Rs. ${parseFloat(cCalc.deficit_amount).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong>.
                            `);
                        } else {
                            $('#khataPositionText').html(`
                                Aaj Ki Tareekh Tak (Day ${cCalc.days_worked}): <strong>${escapeHtml(emp.name || 'Staff')}</strong> has earned <strong>Rs. ${parseFloat(cCalc.gross_earnings).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong> pro-rata salary. Advances taken: <strong>Rs. ${parseFloat(cCalc.total_deductions).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong>. Net Payable Till Today: <strong class="text-success font-monospace">Rs. ${parseFloat(cCalc.remaining_payable).toLocaleString('en-PK', {minimumFractionDigits: 2})}</strong>.
                            `);
                        }
                    } else {
                        $('#khataPositionBanner').addClass('d-none');
                    }

                    // 3. Transactions Table Rendering
                    let html = '';
                    const openBal = parseFloat(res.opening_balance || 0);
                    if (openBal !== 0 && month !== 'all') {
                        const openClass = openBal > 0 ? 'text-danger' : 'text-success';
                        const openLabel = openBal > 0 ? `Rs. ${openBal.toFixed(2)} (Peshi Baqaya)` : `Rs. ${Math.abs(openBal).toFixed(2)} (Store Credit)`;
                        html += `
                            <tr class="table-warning border-bottom border-2">
                                <td><span class="badge bg-secondary font-monospace"><i class="fas fa-clock-rotate-left me-1"></i>Sabqa Baqaya</span></td>
                                <td><span class="badge bg-dark">Opening Balance</span></td>
                                <td><strong>Previous Month(s) Carried Forward Balance</strong></td>
                                <td class="text-end font-monospace">${openBal > 0 ? 'Rs. ' + openBal.toFixed(2) : '-'}</td>
                                <td class="text-end font-monospace">${openBal < 0 ? 'Rs. ' + Math.abs(openBal).toFixed(2) : '-'}</td>
                                <td class="text-end font-monospace fw-bold ${openClass}">${openLabel}</td>
                                <td class="text-center text-muted small">--</td>
                            </tr>
                        `;
                    }

                    const transactions = (res.success && Array.isArray(res.transactions)) ? res.transactions : [];
                    if (transactions.length === 0 && openBal === 0) {
                        html = '<tr><td colspan="7" class="text-center py-4 text-muted">No transactions recorded yet for this period.</td></tr>';
                    } else {
                        transactions.forEach(t => {
                            let typeBadge = '';
                            const isSettlement = t.type === 'salary_settlement';
                            const isVoided = (t.status === 'voided' || t.is_voided === true);
                            const isDeletable = !isSettlement && (!t.reference_type || t.reference_type !== 'employee_settlements');

                            switch(t.type) {
                                case 'advance':
                                    typeBadge = '<span class="badge bg-danger px-2 py-1"><i class="fas fa-hand-holding-dollar me-1"></i>Advance</span>';
                                    break;
                                case 'daily_payment':
                                    typeBadge = '<span class="badge bg-danger px-2 py-1"><i class="fas fa-money-bill me-1"></i>Daily Cash</span>';
                                    break;
                                case 'deduction':
                                    typeBadge = '<span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-calendar-xmark me-1"></i>Fine / Katoti</span>';
                                    break;
                                case 'incentive':
                                    typeBadge = '<span class="badge bg-success px-2 py-1"><i class="fas fa-gift me-1"></i>Incentive</span>';
                                    break;
                                case 'extra_task':
                                    typeBadge = '<span class="badge text-white px-2 py-1" style="background:#7c3aed;"><i class="fas fa-list-check me-1"></i>Extra Duty</span>';
                                    break;
                                case 'salary_settlement':
                                    typeBadge = '<span class="badge bg-dark px-2 py-1"><i class="fas fa-receipt me-1"></i>Salary Settlement</span>';
                                    break;
                                default:
                                    typeBadge = `<span class="badge bg-secondary">${t.type}</span>`;
                            }

                            let descContent = `<strong class="text-dark">${escapeHtml(t.description || 'Record')}</strong>`;
                            let debitText = t.debit > 0 ? `<span class="text-danger font-monospace fw-bold">-Rs. ${parseFloat(t.debit).toFixed(2)}</span>` : '-';
                            let creditText = t.credit > 0 ? `<span class="text-success font-monospace fw-bold">+Rs. ${parseFloat(t.credit).toFixed(2)}</span>` : '-';
                            let runningText = `<span class="font-monospace fw-bold ${t.running_balance > 0 ? 'text-danger' : (t.running_balance < 0 ? 'text-success' : 'text-dark')}">Rs. ${parseFloat(t.running_balance).toFixed(2)}</span>`;

                            let actionBtn = '<span class="text-muted small">-</span>';

                            if (isVoided) {
                                descContent = `
                                    <span class="text-decoration-line-through text-muted">${escapeHtml(t.description || 'Record')}</span>
                                    <span class="badge bg-danger text-white ms-1"><i class="fas fa-ban me-1"></i>VOIDED</span>
                                    <small class="d-block text-danger font-monospace">Voided: ${escapeHtml(t.void_reason || 'Cancelled')}${t.voided_by_name ? ' by ' + escapeHtml(t.voided_by_name) : ''}</small>
                                `;
                                debitText = t.debit > 0 ? `<span class="text-decoration-line-through text-muted font-monospace">-Rs. ${parseFloat(t.debit).toFixed(2)}</span>` : '-';
                                creditText = t.credit > 0 ? `<span class="text-decoration-line-through text-muted font-monospace">+Rs. ${parseFloat(t.credit).toFixed(2)}</span>` : '-';
                                actionBtn = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-ban me-1"></i>Voided</span>`;
                            } else if (isDeletable) {
                                actionBtn = `<button type="button" class="btn btn-xs btn-outline-danger btn-void-tx" data-id="${t.id}" data-desc="${escapeHtml(t.description || t.type)}" data-amount="${t.amount}" title="Cancel / Void Entry"><i class="fas fa-ban me-1"></i>Void</button>`;
                            }

                            html += `
                                <tr class="${isVoided ? 'table-danger bg-opacity-10 opacity-75' : (isSettlement ? 'table-success' : '')}">
                                    <td><small class="text-muted font-monospace">${t.created_at}</small></td>
                                    <td>${typeBadge}</td>
                                    <td>${descContent}</td>
                                    <td class="text-end">${debitText}</td>
                                    <td class="text-end">${creditText}</td>
                                    <td class="text-end">${runningText}</td>
                                    <td class="text-center">${actionBtn}</td>
                                </tr>
                            `;
                        });
                    }
                    $('#detailLedgerBody').html(html);

                    // Re-apply search filter if input has text
                    const currentFilter = $('#searchLedgerInput').val().toLowerCase().trim();
                    if (currentFilter.length > 0) {
                        $('#detailLedgerBody tr').each(function() {
                            const text = $(this).text().toLowerCase();
                            $(this).toggle(text.indexOf(currentFilter) > -1);
                        });
                    }
                }
            }
        });
    }

    // Void Transaction (Soft-Void with Audit Trail & Recalculation)
    $(document).on('click', '.btn-void-tx, .btn-delete-tx', function() {
        const txId = $(this).data('id');
        const desc = $(this).data('desc') || 'entry';
        const amt = $(this).data('amount') || '';
        const amtLabel = amt ? ` (Rs. ${parseFloat(amt).toFixed(2)})` : '';
        
        const reason = prompt(`Khata entry void / cancel karne ki wajah darj karein${amtLabel}:\n(${desc})`, 'Ghalti se enter ho gaya tha');
        if (reason === null) return;
        const cleanReason = reason.trim() || 'Ghalti se enter ho gaya tha';

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=void_transaction',
            type: 'POST',
            data: { transaction_id: txId, void_reason: cleanReason },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    loadEmployeeLedger(currentEmpId);
                    loadSalarySettlement(currentEmpId, $('#detailMonthInput').val() || currentYearMonth);
                    loadStaffDirectory();
                    loadKPIs();
                } else {
                    alert(res.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error voiding transaction.');
            }
        });
    });

    // -------------------------------------------------------------------------
    // 5. LOAD TASKS WIDGET
    // -------------------------------------------------------------------------
    function loadEmployeeTasks(empId) {
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/employees.php?action=list_tasks&employee_id=${empId}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    let html = '';
                    const tasks = Array.isArray(res.tasks) ? res.tasks : [];
                    if (tasks.length === 0) {
                        html = '<div class="p-4 text-center text-muted small">No extra tasks assigned.</div>';
                    } else {
                        tasks.forEach(task => {
                            const isCompleted = task.status === 'completed';
                            const badge = isCompleted ? 
                                '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Completed</span>' : 
                                '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>';
                            
                            html += `
                                <div class="list-group-item py-3">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong class="text-dark">${escapeHtml(task.description || 'Extra Duty')}</strong>
                                        ${badge}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="font-monospace text-success fw-bold">Payout: Rs. ${parseFloat(task.compensation_amount).toFixed(2)}</span>
                                        ${!isCompleted ? `
                                            <button type="button" class="btn btn-sm btn-success fw-bold py-1 px-3 rounded-pill btn-complete-task" data-id="${task.id}">
                                                <i class="fas fa-check me-1"></i> Mark Done
                                            </button>
                                        ` : `
                                            <small class="text-muted font-monospace">${task.completed_at || 'Completed'}</small>
                                        `}
                                    </div>
                                </div>
                            `;
                        });
                    }
                    $('#detailTasksGroup').html(html);
                }
            }
        });
    }

    $(document).on('click', '.btn-complete-task', function() {
        const taskId = $(this).data('id');
        if (!confirm("Confirm marking this task completed? The compensation payout will automatically be credited to the khata ledger.")) {
            return;
        }

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=complete_task',
            type: 'POST',
            data: { task_id: taskId },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    loadEmployeeLedger(currentEmpId);
                    loadEmployeeTasks(currentEmpId);
                    loadStaffDirectory();
                } else {
                    alert(res.message);
                }
            }
        });
    });

    // -------------------------------------------------------------------------
    // 6. QUICK ACTION MODALS (ADVANCE, CHUTI/DEDUCT, TASK/BONUS)
    // -------------------------------------------------------------------------
    // Open Quick Cash Modal (Advance / Daily Cash)
    $(document).on('click', '.btn-quick-cash', function() {
        const empId = $(this).data('id') || currentEmpId;
        openQuickCashModal(empId, 'advance');
    });
    $(document).on('click', '.btn-quick-daily', function() {
        const empId = $(this).data('id') || currentEmpId;
        openQuickCashModal(empId, 'daily_payment');
    });
    $(document).on('click', '.btn-quick-bonus', function() {
        const empId = $(this).data('id') || currentEmpId;
        openQuickWorkModal(empId, 'incentive');
    });
    $('#btnTileAdvance').on('click', function() {
        openQuickCashModal(currentEmpId, 'advance');
    });

    function openQuickCashModal(empId, txType = 'advance') {
        const id = parseInt(empId, 10);
        let emp = allEmployeesCache.find(e => e.id == id);

        const doOpenModal = function(targetEmp) {
            $('#qc_employee_id').val(targetEmp.id);
            $('#qc_type').val(txType);
            $('#qcAvatar').text(targetEmp.name.substring(0, 2).toUpperCase());
            $('#qcName').text(`${targetEmp.name} (${targetEmp.designation})`);

            if (txType === 'daily_payment') {
                $('#quickCashModal .modal-title').html('<i class="fas fa-money-bill me-2 text-warning"></i>Give Daily Cash (Rozana Kharcha)');
                $('#btnSaveQuickCash').text('Save Daily Payment').removeClass('btn-danger').addClass('btn-warning');
                $('#qc_desc').attr('placeholder', 'e.g. Daily cash, kharcha, lunch...');
            } else {
                $('#quickCashModal .modal-title').html('<i class="fas fa-hand-holding-dollar me-2 text-danger"></i>Give Cash Advance (Naqad Peshi)');
                $('#btnSaveQuickCash').text('Save Advance Payment').removeClass('btn-warning').addClass('btn-danger');
                $('#qc_desc').attr('placeholder', 'e.g. Personal kharcha, emergency, advance...');
            }

            const defaultDate = (currentGlobalMonth === '<?= date('Y-m') ?>') ? '<?= date('Y-m-d') ?>' : (currentGlobalMonth + '-01');
            $('#qc_date').val(defaultDate);
            $('#qcMonthHintContainer').empty();

            $('#qc_amount').val('');
            $('#qc_desc').val('');
            $('#qcAlert').addClass('d-none');
            showBootstrapModal('#quickCashModal');
            setTimeout(() => $('#qc_amount').focus(), 400);
        };

        if (!emp) {
            loadStaffDirectory(function() {
                const retryEmp = allEmployeesCache.find(e => e.id == id);
                if (retryEmp) doOpenModal(retryEmp);
            });
            return;
        }

        doOpenModal(emp);
    }
    window.openQuickCashModal = openQuickCashModal;

    $('.advance-chip').on('click', function() {
        $('#qc_amount').val($(this).data('amt'));
    });

    // Submit Quick Cash
    $('#quickCashForm').on('submit', function(e) {
        e.preventDefault();
        $('#qcAlert').addClass('d-none');
        $('#btnSaveQuickCash').prop('disabled', true);

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=add_transaction',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $('#btnSaveQuickCash').prop('disabled', false);
                if (res.success) {
                    hideBootstrapModal('#quickCashModal');
                    showToast(res.message, 'success');
                    loadStaffDirectory();
                    loadKPIs();
                    if (currentEmpId > 0) {
                        loadEmployeeLedger(currentEmpId);
                    }
                } else {
                    $('#qcAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnSaveQuickCash').prop('disabled', false);
                $('#qcAlert').text(xhr.responseJSON?.message || 'Error saving payment.').removeClass('d-none');
            }
        });
    });

    // Open Quick Chuti / Deduction Modal
    $(document).on('click', '.btn-quick-deduct', function() {
        const empId = $(this).data('id') || currentEmpId;
        openQuickDeductModal(empId);
    });
    $('#btnTileDeduct').on('click', function() {
        openQuickDeductModal(currentEmpId);
    });

    function openQuickDeductModal(empId) {
        const id = parseInt(empId, 10);
        let emp = allEmployeesCache.find(e => e.id == id);

        const doOpenModal = function(targetEmp) {
            const salary = parseFloat(targetEmp.current_salary || 0);
            const dailyWage = salary > 0 ? (salary / 30) : 0;

            $('#qd_employee_id').val(targetEmp.id);
            $('#qdAvatar').text(targetEmp.name.substring(0, 2).toUpperCase());
            $('#qdName').text(`${targetEmp.name} (${targetEmp.designation})`);
            $('#qdSalaryInfo').text(`Monthly Salary: Rs. ${salary.toLocaleString()} (${targetEmp.designation})`);
            $('#qdDailyWageBadge').text(`1 Day Wage = Rs. ${dailyWage.toFixed(2)}`);

            // Update wage chips data
            $('#chipHalfDay').data('amt', (dailyWage * 0.5).toFixed(0));
            $('#chipOneDay').data('amt', dailyWage.toFixed(0));
            $('#chipTwoDays').data('amt', (dailyWage * 2.0).toFixed(0));

            $('#qd_amount').val(dailyWage > 0 ? dailyWage.toFixed(0) : '');
            $('#qd_desc').val('');
            $('#qdAlert').addClass('d-none');
            showBootstrapModal('#quickDeductModal');
            setTimeout(() => $('#qd_amount').focus(), 400);
        };

        if (!emp) {
            loadStaffDirectory(function() {
                const retryEmp = allEmployeesCache.find(e => e.id == id);
                if (retryEmp) doOpenModal(retryEmp);
            });
            return;
        }

        doOpenModal(emp);
    }
    window.openQuickDeductModal = openQuickDeductModal;

    $('.wage-chip').on('click', function() {
        $('#qd_amount').val($(this).data('amt'));
    });

    $('#qd_reason_type').on('change', function() {
        const val = $(this).val();
        if (!$('#qd_desc').val() || $('#qd_desc').val().includes('Chuti') || $('#qd_desc').val().includes('Late') || $('#qd_desc').val().includes('Loss') || $('#qd_desc').val().includes('Fine')) {
            $('#qd_desc').val(val);
        }
    });

    // Submit Quick Deduction
    $('#quickDeductForm').on('submit', function(e) {
        e.preventDefault();
        $('#qdAlert').addClass('d-none');
        $('#btnSaveQuickDeduct').prop('disabled', true);

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=add_transaction',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $('#btnSaveQuickDeduct').prop('disabled', false);
                if (res.success) {
                    hideBootstrapModal('#quickDeductModal');
                    showToast(res.message, 'success');
                    loadStaffDirectory();
                    loadKPIs();
                    if (currentEmpId > 0) {
                        loadEmployeeLedger(currentEmpId);
                    }
                } else {
                    $('#qdAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnSaveQuickDeduct').prop('disabled', false);
                $('#qdAlert').text(xhr.responseJSON?.message || 'Error saving deduction.').removeClass('d-none');
            }
        });
    });

    // -------------------------------------------------------------------------
    // MARK ABSENT (CHUTI DEDUCTION) MODAL & ATTENDANCE LOGIC
    // -------------------------------------------------------------------------
    $(document).on('click', '#btnTileAbsent, #btnTabMarkAbsent, .btn-quick-absent', function() {
        const empId = $(this).data('id') || currentEmpId;
        openMarkAbsentModal(empId);
    });

    function openMarkAbsentModal(empId) {
        const id = parseInt(empId, 10);
        let emp = allEmployeesCache.find(e => e.id == id);

        const doOpenModal = function(targetEmp) {
            const salary = parseFloat(targetEmp.current_salary || 0);
            const dailyWage = salary > 0 ? (salary / 30) : 0;

            $('#abs_employee_id').val(targetEmp.id);
            $('#absAvatar').text(targetEmp.name.substring(0, 2).toUpperCase());
            $('#absName').text(`${targetEmp.name} (${targetEmp.designation})`);
            $('#absSalaryInfo').text(`Monthly Salary: Rs. ${salary.toLocaleString()} (${targetEmp.designation})`);
            $('#absDailyWageBadge').text(`1 Day = Rs. ${dailyWage.toFixed(2)}`);

            $('#abs_date').val(new Date().toISOString().split('T')[0]);
            $('#absRadioFull').prop('checked', true);
            $('#abs_amount').val(dailyWage > 0 ? dailyWage.toFixed(2) : '0.00');
            $('#absAmountHelp').text(`Daily wage: Rs. ${dailyWage.toFixed(2)} (Monthly Base ÷ 30)`);
            $('#abs_reason').val('Bila Itla Chuti');
            $('#absAlert').addClass('d-none');
            $('#btnSaveMarkAbsent').prop('disabled', false);

            showBootstrapModal('#markAbsentModal');
            setTimeout(() => $('#abs_amount').focus(), 400);
        };

        if (!emp) {
            loadStaffDirectory(function() {
                const retryEmp = allEmployeesCache.find(e => e.id == id);
                if (retryEmp) doOpenModal(retryEmp);
            });
            return;
        }

        doOpenModal(emp);
    }
    window.openMarkAbsentModal = openMarkAbsentModal;

    // Absence Duration Radio Handler
    $(document).on('change', 'input[name="abs_duration_type"]', function() {
        const val = $(this).val();
        const empId = $('#abs_employee_id').val();
        const emp = allEmployeesCache.find(e => e.id == empId) || {};
        const salary = parseFloat(emp.current_salary || 0);
        const dailyWage = salary > 0 ? (salary / 30) : 0;

        if (val === '1.0') {
            $('#abs_amount').val(dailyWage.toFixed(2));
            $('#absAmountHelp').text(`Full day: 100% daily wage (Rs. ${dailyWage.toFixed(2)})`);
        } else if (val === '0.5') {
            $('#abs_amount').val((dailyWage * 0.5).toFixed(2));
            $('#absAmountHelp').text(`Half day: 50% daily wage (Rs. ${(dailyWage * 0.5).toFixed(2)})`);
        } else {
            $('#absAmountHelp').text('Custom amount for special absence deduction');
            $('#abs_amount').focus();
        }
    });

    // Submit Mark Absent Form
    $('#markAbsentForm').on('submit', function(e) {
        e.preventDefault();
        $('#absAlert').addClass('d-none');
        $('#btnSaveMarkAbsent').prop('disabled', true);

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=mark_absence',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $('#btnSaveMarkAbsent').prop('disabled', false);
                if (res.success) {
                    hideBootstrapModal('#markAbsentModal');
                    showToast(res.message, 'success');
                    if (currentEmpId > 0) {
                        loadEmployeeAbsences(currentEmpId);
                        loadSalarySettlement(currentEmpId, $('#detailMonthInput').val() || currentYearMonth);
                        loadEmployeeLedger(currentEmpId);
                    }
                    loadStaffDirectory();
                    loadKPIs();
                } else {
                    $('#absAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnSaveMarkAbsent').prop('disabled', false);
                $('#absAlert').text(xhr.responseJSON?.message || 'Error marking absence.').removeClass('d-none');
            }
        });
    });

    function loadEmployeeAbsences(empId) {
        if (!empId) return;
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/employees.php?action=list_absences&employee_id=${empId}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    const list = res.absences || [];
                    if (list.length === 0) {
                        $('#tabAbsenceLogBody').html('<tr><td colspan="7" class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success me-2"></i>No absences logged for this employee. Full attendance record.</td></tr>');
                        return;
                    }
                    let html = '';
                    list.forEach(a => {
                        const isVoided = a.status === 'voided';
                        const badge = isVoided ? 
                            '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-ban me-1"></i>Voided</span>' : 
                            '<span class="badge bg-danger text-white"><i class="fas fa-calendar-xmark me-1"></i>Absent (Chuti)</span>';
                        
                        const actionBtn = !isVoided ? 
                            `<button type="button" class="btn btn-xs btn-outline-danger btn-void-absence" data-id="${a.id}" data-date="${a.absence_date}" data-amt="${a.deduction_amount}" title="Cancel / Void Chuti"><i class="fas fa-ban me-1"></i>Cancel Chuti</button>` : 
                            `<small class="text-muted font-monospace">${escapeHtml(a.void_reason || 'Cancelled')}</small>`;
                        
                        html += `
                            <tr class="${isVoided ? 'table-danger bg-opacity-10 text-muted opacity-75' : ''}">
                                <td class="font-monospace fw-bold ${isVoided ? 'text-decoration-line-through text-muted' : 'text-danger'}"><i class="fas fa-calendar-day me-1"></i>${a.absence_date}</td>
                                <td class="font-monospace fw-bold text-danger ${isVoided ? 'text-decoration-line-through' : ''}">-Rs. ${parseFloat(a.deduction_amount).toFixed(2)}</td>
                                <td>${badge}</td>
                                <td><strong>${escapeHtml(a.reason || 'Bila Itla Chuti')}</strong></td>
                                <td class="small text-muted">${escapeHtml(a.created_by_name || 'Admin')}</td>
                                <td class="small font-monospace text-muted">${a.created_at}</td>
                                <td class="text-center">${actionBtn}</td>
                            </tr>
                        `;
                    });
                    $('#tabAbsenceLogBody').html(html);
                }
            }
        });
    }
    window.loadEmployeeAbsences = loadEmployeeAbsences;

    // Void / Cancel Absence Handler
    $(document).on('click', '.btn-void-absence', function(e) {
        e.stopPropagation();
        const absId = $(this).data('id');
        const absDate = $(this).data('date');
        const amt = $(this).data('amt');
        
        const reason = prompt(`Chuti Cancel / Void karne ki wajah darj karein (Date: ${absDate}, Rs. ${amt}):`, 'Ghalti se mark ho gaya / Mulazim hazir tha');
        if (reason === null) return;
        const cleanReason = reason.trim() || 'Ghalti se mark ho gaya';

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=void_absence',
            type: 'POST',
            data: { absence_id: absId, void_reason: cleanReason },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    if (currentEmpId > 0) {
                        loadEmployeeAbsences(currentEmpId);
                        loadSalarySettlement(currentEmpId, $('#detailMonthInput').val() || currentYearMonth);
                        loadEmployeeLedger(currentEmpId);
                    }
                    loadStaffDirectory();
                    loadKPIs();
                } else {
                    alert(res.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error cancelling absence.');
            }
        });
    });

    // Open Quick Work / Bonus Modal
    $(document).on('click', '.btn-quick-work', function() {
        const empId = $(this).data('id') || currentEmpId;
        openQuickWorkModal(empId, 'incentive');
    });
    $('#btnTileTask, #btnQuickAddTask').on('click', function() {
        openQuickWorkModal(currentEmpId, 'task');
    });
    $('#btnTileBonus').on('click', function() {
        openQuickWorkModal(currentEmpId, 'incentive');
    });

    function openQuickWorkModal(empId, preselectedType = 'task') {
        const id = parseInt(empId, 10);
        let emp = allEmployeesCache.find(e => e.id == id);

        const doOpenModal = function(targetEmp) {
            $('#qw_employee_id').val(targetEmp.id);
            $('#qwAvatar').text(targetEmp.name.substring(0, 2).toUpperCase());
            $('#qwName').text(`${targetEmp.name} (${targetEmp.designation})`);

            if (preselectedType === 'incentive') {
                $('#qwTypeBonus').prop('checked', true);
            } else {
                $('#qwTypeTask').prop('checked', true);
            }

            $('#qw_amount').val('');
            $('#qw_desc').val('');
            $('#qwAlert').addClass('d-none');
            showBootstrapModal('#quickWorkModal');
            setTimeout(() => $('#qw_amount').focus(), 400);
        };

        if (!emp) {
            loadStaffDirectory(function() {
                const retryEmp = allEmployeesCache.find(e => e.id == id);
                if (retryEmp) doOpenModal(retryEmp);
            });
            return;
        }

        doOpenModal(emp);
    }
    window.openQuickWorkModal = openQuickWorkModal;

    // Submit Quick Work / Bonus
    $('#quickWorkForm').on('submit', function(e) {
        e.preventDefault();
        $('#qwAlert').addClass('d-none');
        $('#btnSaveQuickWork').prop('disabled', true);

        const creditType = $('input[name="credit_type"]:checked').val();
        let postUrl = '';
        let postData = {};

        if (creditType === 'task') {
            postUrl = (window.BASE_URL || '') + '/api/employees.php?action=add_task';
            postData = {
                employee_id: $('#qw_employee_id').val(),
                description: $('#qw_desc').val(),
                compensation: $('#qw_amount').val()
            };
        } else {
            postUrl = (window.BASE_URL || '') + '/api/employees.php?action=add_transaction';
            postData = {
                employee_id: $('#qw_employee_id').val(),
                type: 'incentive',
                amount: $('#qw_amount').val(),
                description: $('#qw_desc').val()
            };
        }

        $.ajax({
            url: postUrl,
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(res) {
                $('#btnSaveQuickWork').prop('disabled', false);
                if (res.success) {
                    hideBootstrapModal('#quickWorkModal');
                    showToast(res.message, 'success');
                    loadStaffDirectory();
                    loadKPIs();
                    if (currentEmpId > 0) {
                        loadEmployeeLedger(currentEmpId);
                        loadEmployeeTasks(currentEmpId);
                    }
                } else {
                    $('#qwAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnSaveQuickWork').prop('disabled', false);
                $('#qwAlert').text(xhr.responseJSON?.message || 'Error saving entry.').removeClass('d-none');
            }
        });
    });

    // -------------------------------------------------------------------------
    // 7. MONTHLY SALARY SETTLEMENT & CLEARANCE
    // -------------------------------------------------------------------------
    function loadSalarySettlement(empId, month, mode) {
        if (!empId || !month) return;
        if (mode) currentSalaryMode = mode;

        $('#salaryLoadingBox').removeClass('d-none');
        $('#salarySheetBox').addClass('d-none');

        $.ajax({
            url: `${(window.BASE_URL || "")}/api/employees.php?action=calculate_settlement&employee_id=${empId}&month=${month}&calc_mode=${currentSalaryMode}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                $('#salaryLoadingBox').addClass('d-none');
                $('#salarySheetBox').removeClass('d-none');

                if (res.success) {
                    currentCompiledSettlement = {
                        ...res,
                        employee_id: empId,
                        month: month,
                        employee: res.employee || allEmployeesCache.find(e => e.id == empId) || {}
                    };

                    displaySalarySheet(currentCompiledSettlement);
                } else {
                    $('#sheetStatusPill').html('<span class="badge bg-danger fs-6 px-3 py-2 rounded-pill"><i class="fas fa-exclamation-triangle me-1"></i> Calculation Error</span>');
                    alert(res.message);
                }
            },
            error: function(xhr) {
                $('#salaryLoadingBox').addClass('d-none');
                $('#salarySheetBox').removeClass('d-none');
                $('#sheetStatusPill').html('<span class="badge bg-danger fs-6 px-3 py-2 rounded-pill"><i class="fas fa-exclamation-triangle me-1"></i> Error</span>');
            }
        });
    }

    function formatMonthYearLabel(monthStr) {
        if (!monthStr) return '';
        const parts = monthStr.split('-');
        if (parts.length < 2) return monthStr;
        const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, 1);
        return d.toLocaleString('en-US', { month: 'long', year: 'numeric' });
    }

    function displaySalarySheet(data) {
        const emp = data.employee || {};
        const mode = data.calculation_mode || currentSalaryMode || 'till_date';
        
        // Header info
        $('#voucherEmpAvatar').text((emp.name || 'EM').substring(0, 2).toUpperCase());
        $('#voucherEmpName').text(emp.name || 'Employee');
        $('#voucherEmpRole').text(emp.designation || 'Staff');
        $('#voucherEmpPhone').html(`<i class="fas fa-phone me-1 text-success"></i>${escapeHtml(emp.contact || '--')}`);
        $('#voucherEmpJoin').html(`<i class="fas fa-calendar me-1 text-secondary"></i>Joined: ${emp.joining_date || '--'}`);
        $('#voucherPayMonthText').text(formatMonthYearLabel(data.month));

        // 1. Calculation Mode Switcher & Daily Rate Bar
        $('#btnModeDaysText').text(`${data.days_worked || 30} Din`);
        $('#voucherDailyRateText').text(`Rs. ${parseFloat(data.daily_wage || 0).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}/day`);
        
        // Pay Pause Notice Handling
        if (data.paused_days > 0 || data.pay_status === 'paused') {
            $('#voucherPauseNotice').removeClass('d-none');
            const isCurrentlyPaused = (data.pay_status === 'paused');
            if (isCurrentlyPaused) {
                $('#voucherPauseTitle').text(`Currently on Pay Pause (Since ${data.pay_pause_date || ''})`);
                $('#voucherPauseDetails').html(`Pay paused on <strong>${data.pay_pause_date || ''}</strong>${data.pay_pause_reason ? ` (Reason: <em>${escapeHtml(data.pay_pause_reason)}</em>)` : ''}. Total <strong>${data.paused_days} day(s)</strong> frozen in this month.`);
            } else {
                $('#voucherPauseTitle').text(`Pay Pause Logged in this Month`);
                $('#voucherPauseDetails').html(`This employee had <strong>${data.paused_days} day(s)</strong> on pay pause during this month. Salary accrual was frozen during those days.`);
            }
            $('#voucherPauseBadge').text(`${data.paused_days} Day(s) Paused`);
        } else {
            $('#voucherPauseNotice').addClass('d-none');
        }

        if (mode === 'till_date') {
            $('#btnModeTillToday').addClass('active btn-primary').removeClass('btn-light text-secondary');
            $('#btnModeFullMonth').removeClass('active btn-primary').addClass('btn-light text-secondary');
            
            $('#formulaBannerTitle').text('Pro-Rata Salary Till Today (Aaj Ki Tareekh Tak)');
            const daysText = data.paused_days > 0 ? `${data.days_worked} active days (${data.paused_days} paused)` : `${data.days_worked} days`;
            $('#formulaBannerDays').text(daysText);
            $('#formulaBannerPeriod').text(`01 to ${data.days_worked + (data.paused_days || 0)} ${formatMonthYearLabel(data.month)}`);
            $('#formulaBannerMath').text(`${data.days_worked} days × Rs. ${parseFloat(data.daily_wage || 0).toFixed(2)}/day`);
            $('#formulaBannerEarned').text(`Rs. ${parseFloat(data.earned_base_salary || 0).toLocaleString('en-PK', {minimumFractionDigits: 2})}`);
            $('#formulaBannerEarnedBadge').text(`Earned Base: Rs. ${parseFloat(data.earned_base_salary || 0).toLocaleString('en-PK', {minimumFractionDigits: 2})}`);
            
            $('#sheetBaseSalaryLabel').html(`Earned Base Salary (<span id="sheetDaysCountBadge">${data.days_worked} Days</span>)`);
            $('#sheetBaseSalarySubtext').text(data.paused_days > 0 ? `Calculated on ${data.days_worked} active working days (${data.paused_days} paused)` : `Calculated at Rs. ${parseFloat(data.daily_wage || 0).toFixed(2)} / day`);
        } else {
            $('#btnModeFullMonth').addClass('active btn-primary').removeClass('btn-light text-secondary');
            $('#btnModeTillToday').removeClass('active btn-primary').addClass('btn-light text-secondary');
            
            $('#formulaBannerTitle').text('Full Month Salary (Pura Mahina)');
            const fullDaysText = data.paused_days > 0 ? `${data.days_worked} active days (30 minus ${data.paused_days} paused)` : `30 days (Full Month)`;
            $('#formulaBannerDays').text(fullDaysText);
            $('#formulaBannerPeriod').text(`Full Calendar Month`);
            $('#formulaBannerMath').text(data.paused_days > 0 ? `${data.days_worked} days × Rs. ${parseFloat(data.daily_wage || 0).toFixed(2)}/day` : `Standard Full Monthly Contract Base`);
            $('#formulaBannerEarned').text(`Rs. ${parseFloat(data.earned_base_salary || data.base_salary || 0).toLocaleString('en-PK', {minimumFractionDigits: 2})}`);
            $('#formulaBannerEarnedBadge').text(`Earned Base: Rs. ${parseFloat(data.earned_base_salary || data.base_salary || 0).toLocaleString('en-PK', {minimumFractionDigits: 2})}`);
            
            $('#sheetBaseSalaryLabel').html(`Full Month Base Salary (<span id="sheetDaysCountBadge">${data.days_worked} Days</span>)`);
            $('#sheetBaseSalarySubtext').text(data.paused_days > 0 ? `Net active days after pause deduction` : `Monthly contractual salary rate`);
        }

        $('#sheetDaysCountBadge').text(`${data.days_worked} Days`);
        $('#sheetBaseSalary').text(`Rs. ${parseFloat(data.earned_base_salary || data.base_salary).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#sheetMonthlyBaseRef').text(`(Contract Rate: Rs. ${parseFloat(data.base_salary).toLocaleString('en-PK', {minimumFractionDigits: 0})}/mo)`);

        // Gross Additions
        $('#sheetExtraTasks').text(`+Rs. ${parseFloat(data.extra_tasks).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#sheetIncentives').text(`+Rs. ${parseFloat(data.incentives).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#headerGrossBadge').text(`Rs. ${parseFloat(data.gross_earnings).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#sheetGrossEarnings').text(`Rs. ${parseFloat(data.gross_earnings).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);

        // Itemized Tasks
        let tasksHtml = '';
        if (data.tasks_list && data.tasks_list.length > 0) {
            tasksHtml = '<div class="list-group list-group-flush">';
            data.tasks_list.forEach(tk => {
                tasksHtml += `
                    <div class="list-group-item px-2 py-1 d-flex justify-content-between align-items-center bg-transparent">
                        <div>
                            <strong class="text-dark">${escapeHtml(tk.description)}</strong>
                            <small class="text-muted d-block font-monospace">${tk.completed_at}</small>
                        </div>
                        <span class="font-monospace text-success fw-bold">+Rs. ${parseFloat(tk.compensation_amount).toFixed(2)}</span>
                    </div>
                `;
            });
            tasksHtml += '</div>';
        } else {
            tasksHtml = '<span class="text-muted small">No extra tasks recorded for this month.</span>';
        }
        $('#voucherTasksList').html(tasksHtml);

        // Itemized Incentives
        let incHtml = '';
        if (data.incentives_list && data.incentives_list.length > 0) {
            incHtml = '<div class="list-group list-group-flush">';
            data.incentives_list.forEach(inc => {
                incHtml += `
                    <div class="list-group-item px-2 py-1 d-flex justify-content-between align-items-center bg-transparent">
                        <div>
                            <strong class="text-dark">${escapeHtml(inc.description || 'Bonus')}</strong>
                            <small class="text-muted d-block font-monospace">${inc.created_at}</small>
                        </div>
                        <span class="font-monospace text-success fw-bold">+Rs. ${parseFloat(inc.amount).toFixed(2)}</span>
                    </div>
                `;
            });
            incHtml += '</div>';
        } else {
            incHtml = '<span class="text-muted small">No incentive bonus recorded for this month.</span>';
        }
        $('#voucherIncentivesList').html(incHtml);

        // 2. Deductions Card
        $('#sheetAdvancesTotal').text(`-Rs. ${parseFloat(data.advances || 0).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#sheetDailyPaymentsTotal').text(`-Rs. ${parseFloat(data.daily_payments || 0).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#sheetFinesTotal').text(`-Rs. ${parseFloat(data.fines_deducted || 0).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#headerDeductBadge').text(`-Rs. ${parseFloat(data.total_deductions).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#sheetTotalPaid').text(`-Rs. ${parseFloat(data.total_deductions).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);

        // Itemized Advances
        let advHtml = '';
        if (data.advances_list && data.advances_list.length > 0) {
            advHtml = '<div class="list-group list-group-flush">';
            data.advances_list.forEach(ad => {
                advHtml += `
                    <div class="list-group-item px-2 py-1 d-flex justify-content-between align-items-center bg-transparent">
                        <div>
                            <strong class="text-dark">${escapeHtml(ad.description || 'Advance')}</strong>
                            <small class="text-muted d-block font-monospace">${ad.created_at}</small>
                        </div>
                        <span class="font-monospace text-danger fw-bold">-Rs. ${parseFloat(ad.amount).toFixed(2)}</span>
                    </div>
                `;
            });
            advHtml += '</div>';
        } else {
            advHtml = '<span class="text-muted small">No cash advances taken this month.</span>';
        }
        $('#voucherAdvancesList').html(advHtml);

        // Itemized Daily Payments
        let dpHtml = '';
        if (data.daily_payments_list && data.daily_payments_list.length > 0) {
            dpHtml = '<div class="list-group list-group-flush">';
            data.daily_payments_list.forEach(dp => {
                dpHtml += `
                    <div class="list-group-item px-2 py-1 d-flex justify-content-between align-items-center bg-transparent">
                        <div>
                            <strong class="text-dark">${escapeHtml(dp.description || 'Daily Cash')}</strong>
                            <small class="text-muted d-block font-monospace">${dp.created_at}</small>
                        </div>
                        <span class="font-monospace text-danger fw-bold">-Rs. ${parseFloat(dp.amount).toFixed(2)}</span>
                    </div>
                `;
            });
            dpHtml += '</div>';
        } else {
            dpHtml = '<span class="text-muted small">No daily cash taken this month.</span>';
        }
        $('#voucherDailyPaymentsList').html(dpHtml);

        // Itemized Fines / Absence Deductions
        let fnHtml = '';
        if (data.fines_list && data.fines_list.length > 0) {
            fnHtml = '<div class="list-group list-group-flush">';
            data.fines_list.forEach(fn => {
                fnHtml += `
                    <div class="list-group-item px-2 py-1 d-flex justify-content-between align-items-center bg-transparent">
                        <div>
                            <strong class="text-dark">${escapeHtml(fn.description || 'Absence / Fine')}</strong>
                            <small class="text-muted d-block font-monospace">${fn.created_at}</small>
                        </div>
                        <span class="font-monospace text-danger fw-bold">-Rs. ${parseFloat(fn.amount).toFixed(2)}</span>
                    </div>
                `;
            });
            fnHtml += '</div>';
        } else {
            fnHtml = '<span class="text-muted small">No fines or chuti deductions this month.</span>';
        }
        $('#voucherFinesList').html(fnHtml);

        // Itemized Absences (Chuti Deductions)
        let absHtml = '';
        const totAbsDeduct = parseFloat(data.total_absent_deductions || 0);
        $('#sheetAbsencesTotal').text(`-Rs. ${totAbsDeduct.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        if (data.absences_list && data.absences_list.length > 0) {
            absHtml = '<div class="list-group list-group-flush">';
            data.absences_list.forEach(ab => {
                absHtml += `
                    <div class="list-group-item px-2 py-1 d-flex justify-content-between align-items-center bg-transparent">
                        <div>
                            <strong class="text-dark"><i class="fas fa-calendar-xmark text-danger me-1"></i>Chuti: ${ab.absence_date}</strong>
                            <small class="text-muted d-block">${escapeHtml(ab.reason || 'Bila Itla Chuti')}</small>
                        </div>
                        <span class="font-monospace text-danger fw-bold">-Rs. ${parseFloat(ab.deduction_amount).toFixed(2)}</span>
                    </div>
                `;
            });
            absHtml += '</div>';
        } else {
            absHtml = '<span class="text-muted small">No absences logged this month.</span>';
        }
        $('#voucherAbsencesList').html(absHtml);

        // Prior Unsettled Balance
        if (parseFloat(data.prior_unsettled || 0) > 0) {
            $('#voucherPriorBox').removeClass('d-none');
            $('#sheetPriorTotal').text(`-Rs. ${parseFloat(data.prior_unsettled).toFixed(2)}`);
        } else {
            $('#voucherPriorBox').addClass('d-none');
        }

        // 3. Final Net Cash Amount & Advance Exceeded Handling
        $('#sheetNetPayable').text(`Rs. ${parseFloat(data.remaining_payable).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#sheetFormulaBreakdown').text(`(Gross Earnings: Rs. ${parseFloat(data.gross_earnings).toFixed(2)} − Total Deductions: Rs. ${parseFloat(data.total_deductions).toFixed(2)})`);

        if (data.advance_exceeded) {
            $('#sheetAdvanceExceededAlert').removeClass('d-none');
            $('#sheetExceededAmt').text(`Rs. ${parseFloat(data.deficit_amount).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
            $('#sheetNetTitle').html('<span class="text-danger fw-bold"><i class="fas fa-triangle-exclamation me-1"></i> ADVANCE EXCEEDS EARNED SALARY (STAFF OWES STORE):</span>');
            $('#sheetNetPayable').removeClass('text-warning text-success').addClass('text-danger');
        } else {
            $('#sheetAdvanceExceededAlert').addClass('d-none');
            $('#sheetNetTitle').text('Final Net Cash Amount to Pay to Staff:');
            $('#sheetNetPayable').removeClass('text-danger').addClass('text-warning');
        }

        // 4. Status Pill and Action Buttons
        if (data.already_settled) {
            const sRec = data.settlement_record || {};
            const methodLabel = (sRec.payment_method === 'bank') ? 'Bank Transfer' : 'Cash Drawer (Galla)';
            const noteText = sRec.notes ? ` (${escapeHtml(sRec.notes)})` : '';
            const typeLabel = (sRec.settlement_type === 'till_date' || sRec.days_worked < 30) ? `Till Date (${sRec.days_worked} Days)` : 'Full Month (30 Days)';
            
            $('#sheetStatusPill').html('<span class="badge bg-success fs-6 px-3 py-2 rounded-pill"><i class="fas fa-check-circle me-1"></i> Settled & Closed</span>');
            $('#voucherSettledDetails').html(`Approved & paid <strong>Rs. ${parseFloat(sRec.paid_amount || data.remaining_payable).toFixed(2)}</strong> [${typeLabel}] via <strong>${methodLabel}</strong> on <strong>${sRec.created_at || 'Recorded Date'}</strong> by <strong>${escapeHtml(sRec.settled_by_name || 'Admin')}</strong>${noteText}.`);
            $('#voucherSettledNotice').removeClass('d-none');
            
            $('#sheetActionBox').html(`
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-dark btn-lg px-4 py-3 fw-bold rounded-4 shadow" id="btnReprintCurrentSlip">
                        <i class="fas fa-print me-2 text-warning"></i> Thermal Slip
                    </button>
                    <button type="button" class="btn btn-outline-dark btn-lg px-4 py-3 fw-bold rounded-4 shadow" id="btnReprintA4Slip">
                        <i class="fas fa-file-invoice me-2 text-primary"></i> A4 Voucher
                    </button>
                </div>
            `);
        } else {
            $('#sheetStatusPill').html('<span class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill"><i class="fas fa-clock me-1"></i> Pending Clearance</span>');
            $('#voucherSettledNotice').addClass('d-none');
            
            $('#sheetActionBox').html(`
                <button type="button" class="btn btn-success btn-lg px-5 py-3 fw-bold rounded-4 shadow fs-4" id="btnOpenApproveModal">
                    <i class="fas fa-check-double me-2"></i> Approve & Settle Salary Now
                </button>
            `);
        }
    }

    // Auto-load on month input change
    $('#detailMonthInput').on('change', function() {
        if (currentEmpId > 0) {
            loadSalarySettlement(currentEmpId, $(this).val(), currentSalaryMode);
        }
    });

    // Quick Month Buttons
    $('#btnSetCurrentMonth').on('click', function() {
        $('#detailMonthInput').val(currentYearMonth);
        if (currentEmpId > 0) {
            loadSalarySettlement(currentEmpId, currentYearMonth, currentSalaryMode);
        }
    });

    $('#btnSetLastMonth').on('click', function() {
        const now = new Date();
        const lastMonthDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const lastMonthStr = lastMonthDate.toISOString().substring(0, 7);
        $('#detailMonthInput').val(lastMonthStr);
        if (currentEmpId > 0) {
            loadSalarySettlement(currentEmpId, lastMonthStr, currentSalaryMode);
        }
    });

    // Auto-load on Tab Click
    $('#pill-salary-tab').on('click', function() {
        if (currentEmpId > 0) {
            loadSalarySettlement(currentEmpId, $('#detailMonthInput').val() || currentYearMonth, currentSalaryMode);
        }
    });

    // Calculation Mode Switcher
    $(document).on('click', '.btn-mode-toggle', function() {
        currentSalaryMode = $(this).data('mode') || 'till_date';
        if (currentEmpId > 0) {
            loadSalarySettlement(currentEmpId, $('#detailMonthInput').val() || currentYearMonth, currentSalaryMode);
        }
    });

    // Open Settlement Approval Modal
    $(document).on('click', '#btnOpenApproveModal', function() {
        if (!currentCompiledSettlement) {
            alert("Voucher is still loading, please wait...");
            return;
        }

        const emp = currentCompiledSettlement.employee || {};
        $('#smEmpName').text(emp.name || 'Employee');
        $('#smPayMonth').text(currentCompiledSettlement.month);
        
        const tillDays = currentCompiledSettlement.days_elapsed_till_today || currentCompiledSettlement.days_worked || 14;
        $('#modalTillDaysText').text(`${tillDays} Days`);
        
        const tillPayable = parseFloat(currentCompiledSettlement.remaining_payable_till_today !== undefined ? currentCompiledSettlement.remaining_payable_till_today : currentCompiledSettlement.remaining_payable);
        const fullPayable = parseFloat(currentCompiledSettlement.remaining_payable_full_month !== undefined ? currentCompiledSettlement.remaining_payable_full_month : currentCompiledSettlement.remaining_payable);
        
        $('#modalTillPayableText').text(`Net: Rs. ${tillPayable.toLocaleString('en-PK', {minimumFractionDigits: 2})}`);
        $('#modalFullPayableText').text(`Net: Rs. ${fullPayable.toLocaleString('en-PK', {minimumFractionDigits: 2})}`);

        if (currentSalaryMode === 'full_month') {
            $('#settleModalModeFullMonth').prop('checked', true);
            $('#smBaseSalaryLabel').text('Base Salary (Full Month):');
            $('#smBaseSalary').text(`Rs. ${parseFloat(currentCompiledSettlement.full_month_base_salary || currentCompiledSettlement.base_salary).toFixed(2)}`);
            $('#smNetPayable').text(`Rs. ${fullPayable.toFixed(2)}`);
        } else {
            $('#settleModalModeTillToday').prop('checked', true);
            $('#smBaseSalaryLabel').text(`Base Salary (Earned Till Today - ${tillDays} Days):`);
            $('#smBaseSalary').text(`Rs. ${parseFloat(currentCompiledSettlement.earned_base_salary_till_today || currentCompiledSettlement.earned_base_salary).toFixed(2)}`);
            $('#smNetPayable').text(`Rs. ${tillPayable.toFixed(2)}`);
        }

        const additions = parseFloat(currentCompiledSettlement.extra_tasks || 0) + parseFloat(currentCompiledSettlement.incentives || 0);
        $('#smAdditions').text(`+Rs. ${additions.toFixed(2)}`);
        $('#smDeductions').text(`-Rs. ${parseFloat(currentCompiledSettlement.total_deductions || 0).toFixed(2)}`);
        
        $('#settle_payment_date').val(today);
        $('#settle_notes').val('');
        $('#settleAlert').addClass('d-none');
        $('#settleMethodCash').prop('checked', true);
        
        showBootstrapModal('#approveSettlementModal');
    });

    $('input[name="settle_modal_mode"]').on('change', function() {
        if (!currentCompiledSettlement) return;
        const mode = $(this).val();
        const tillDays = currentCompiledSettlement.days_elapsed_till_today || currentCompiledSettlement.days_worked || 14;
        const tillPayable = parseFloat(currentCompiledSettlement.remaining_payable_till_today !== undefined ? currentCompiledSettlement.remaining_payable_till_today : currentCompiledSettlement.remaining_payable);
        const fullPayable = parseFloat(currentCompiledSettlement.remaining_payable_full_month !== undefined ? currentCompiledSettlement.remaining_payable_full_month : currentCompiledSettlement.remaining_payable);

        if (mode === 'full_month') {
            $('#smBaseSalaryLabel').text('Base Salary (Full Month):');
            $('#smBaseSalary').text(`Rs. ${parseFloat(currentCompiledSettlement.full_month_base_salary || currentCompiledSettlement.base_salary).toFixed(2)}`);
            $('#smNetPayable').text(`Rs. ${fullPayable.toFixed(2)}`);
        } else {
            $('#smBaseSalaryLabel').text(`Base Salary (Earned Till Today - ${tillDays} Days):`);
            $('#smBaseSalary').text(`Rs. ${parseFloat(currentCompiledSettlement.earned_base_salary_till_today || currentCompiledSettlement.earned_base_salary).toFixed(2)}`);
            $('#smNetPayable').text(`Rs. ${tillPayable.toFixed(2)}`);
        }
    });

    // Submit Settlement Approval
    $('#approveSettlementForm').on('submit', function(e) {
        e.preventDefault();
        if (!currentCompiledSettlement) return;

        $('#settleAlert').addClass('d-none');
        $('#btnConfirmSettlement').prop('disabled', true);

        const selMode = $('input[name="settle_modal_mode"]:checked').val() || 'till_date';
        const postData = {
            employee_id: currentCompiledSettlement.employee_id,
            month: currentCompiledSettlement.month,
            settlement_type: selMode,
            payment_method: $('input[name="settle_payment_method"]:checked').val(),
            payment_source: $('input[name="settle_payment_method"]:checked').val() === 'bank' ? 'bank_transfer' : 'drawer_cash',
            payment_date: $('#settle_payment_date').val(),
            notes: $('#settle_notes').val().trim()
        };

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=save_settlement',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(res) {
                $('#btnConfirmSettlement').prop('disabled', false);
                if (res.success) {
                    hideBootstrapModal('#approveSettlementModal');
                    showToast(res.message, 'success');

                    // Automatically print voucher upon settlement
                    renderAndPrintSalaryVoucher({
                        ...currentCompiledSettlement,
                        created_at: postData.payment_date,
                        payment_method: postData.payment_method,
                        notes: postData.notes,
                        settlement_type: postData.settlement_type,
                        already_settled: true,
                        remaining_payable: res.remaining_payable || currentCompiledSettlement.remaining_payable
                    });

                    // Refresh all views
                    loadSalarySettlement(currentEmpId, currentCompiledSettlement.month, selMode);
                    loadStaffDirectory();
                    loadKPIs();
                    loadEmployeeLedger(currentEmpId);
                    loadSettlementHistory(currentEmpId);
                } else {
                    $('#settleAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnConfirmSettlement').prop('disabled', false);
                $('#settleAlert').text(xhr.responseJSON?.message || 'Error saving settlement.').removeClass('d-none');
            }
        });
    });

    // Void / Re-open Settlement
    $(document).on('click', '#btnVoidSettlement', function() {
        if (!currentCompiledSettlement || !currentCompiledSettlement.month) return;
        const month = currentCompiledSettlement.month;
        const empName = currentCompiledSettlement.employee?.name || 'this employee';

        if (!confirm(`Are you sure you want to void / re-open the monthly settlement for ${empName} (${month})?\n\nThis will restore the payroll to Pending Clearance so you can make adjustments or recalculate.`)) {
            return;
        }

        const settId = currentCompiledSettlement.settlement_record ? currentCompiledSettlement.settlement_record.id : 0;

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=void_settlement',
            type: 'POST',
            data: {
                settlement_id: settId,
                employee_id: currentEmpId,
                month: month
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    loadSalarySettlement(currentEmpId, month, currentSalaryMode);
                    loadStaffDirectory();
                    loadKPIs();
                    loadEmployeeLedger(currentEmpId);
                    loadSettlementHistory(currentEmpId);
                } else {
                    alert(res.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error voiding settlement.');
            }
        });
    });

    // Settlement History Loader
    function loadSettlementHistory(empId) {
        if (!empId) return;
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/employees.php?action=settlement_history&employee_id=${empId}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.history) {
                    const tbody = $('#pastVouchersBody');
                    if (res.history.length === 0) {
                        tbody.html('<tr><td colspan="8" class="text-center py-4 text-muted">No past settlements recorded yet for this employee.</td></tr>');
                        return;
                    }
                    let html = '';
                    res.history.forEach(item => {
                        const extraBonus = parseFloat(item.extra_tasks_amount || 0) + parseFloat(item.incentives_amount || 0);
                        const totalDeductions = parseFloat(item.advances_deducted || 0) + parseFloat(item.daily_payments_deducted || 0) + parseFloat(item.fines_deducted || 0);
                        const methodBadge = item.payment_method === 'bank' 
                            ? '<span class="badge bg-primary-subtle text-primary border border-primary px-2">Bank</span>' 
                            : '<span class="badge bg-success-subtle text-success border border-success px-2">Cash</span>';
                        
                        html += `
                            <tr>
                                <td>
                                    <strong class="text-dark">${escapeHtml(item.month_year)}</strong>
                                    <div class="small mt-1">${methodBadge}</div>
                                </td>
                                <td><span class="font-monospace text-muted small">${(item.created_at || '').substring(0, 10)}</span></td>
                                <td class="text-end font-monospace">Rs. ${parseFloat(item.base_salary || 0).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
                                <td class="text-end font-monospace text-success">+Rs. ${extraBonus.toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
                                <td class="text-end font-monospace text-danger">-Rs. ${totalDeductions.toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
                                <td class="text-end font-monospace fw-bold text-dark fs-6">Rs. ${parseFloat(item.paid_amount || item.remaining_payable || 0).toLocaleString('en-PK', {minimumFractionDigits: 2})}</td>
                                <td><span class="badge bg-light text-dark border">${escapeHtml(item.settled_by_name || 'Admin')}</span></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-dark fw-bold btn-view-history-slip" data-month="${item.month_year}">
                                        <i class="fas fa-file-invoice-dollar me-1 text-primary"></i> View & Slip
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    tbody.html(html);
                }
            },
            error: function() {
                $('#pastVouchersBody').html('<tr><td colspan="8" class="text-center py-3 text-danger">Failed to load past settlements.</td></tr>');
            }
        });
    }

    // Tab C Click
    $('#pill-history-tab').on('click', function() {
        if (currentEmpId > 0) {
            loadSettlementHistory(currentEmpId);
        }
    });

    // View past slip click
    $(document).on('click', '.btn-view-history-slip', function() {
        const month = $(this).data('month');
        if (!month) return;
        $('#detailMonthInput').val(month);
        const triggerEl = document.querySelector('#pill-salary-tab');
        if (triggerEl) {
            bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        }
        loadSalarySettlement(currentEmpId, month, currentSalaryMode);
    });

    // -------------------------------------------------------------------------
    // 8. PRINTING ENGINES (THERMAL 80MM & FORMAL A4 PRINTERS)
    // -------------------------------------------------------------------------
    // Universal A4 Document Print Stream (Pristine Hidden Iframe)
    function printA4Document(htmlContent, title = 'Document') {
        let iframe = document.getElementById('a4PrintIframe');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'a4PrintIframe';
            iframe.name = 'a4PrintIframe';
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
    <title>${escapeHtml(title)}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 14mm 12mm 14mm;
        }
        @media print {
            html, body {
                width: 210mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 12px;
            color: #0f172a;
            line-height: 1.45;
            background: #ffffff;
            padding: 5px;
        }
        .a4-container {
            width: 100%;
            max-width: 185mm;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
        .fw-bold { font-weight: bold; }
        .text-danger { color: #dc2626 !important; }
        .text-success { color: #16a34a !important; }
        .text-primary { color: #2563eb !important; }
        .text-muted { color: #64748b !important; }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 12px;
        }
        .table-data th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10.5px;
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
        }
        .table-data td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            font-size: 11px;
            vertical-align: middle;
        }
        .table-data tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 600;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
        }
        .badge-danger { background: #fef2f2; color: #991b1b; border-color: #fca5a5; }
        .badge-success { background: #f0fdf4; color: #166534; border-color: #86efac; }
        .badge-warning { background: #fffbeb; color: #92400e; border-color: #fde68a; }
        .badge-dark { background: #0f172a; color: #ffffff; border-color: #0f172a; }
    </style>
</head>
<body>
    <div class="a4-container">
        ${htmlContent}
    </div>
</body>
</html>`);
        doc.close();

        const triggerPrint = function() {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch(e) {
                console.error('A4 print error:', e);
                window.print();
            }
        };

        if (iframe.contentWindow.document.readyState === 'complete') {
            setTimeout(triggerPrint, 250);
        } else {
            iframe.contentWindow.onload = function() {
                setTimeout(triggerPrint, 250);
            };
        }
    }

    // Print Salary Slip Triggers
    $(document).on('click', '#btnPrintSlip, #btnReprintCurrentSlip', function() {
        if (!currentCompiledSettlement) {
            alert("Please wait for the salary voucher to load.");
            return;
        }
        renderAndPrintSalaryVoucher(currentCompiledSettlement);
    });

    $(document).on('click', '#btnPrintSlipA4, #btnReprintA4Slip', function() {
        if (!currentCompiledSettlement) {
            alert("Please wait for the salary voucher to load.");
            return;
        }
        renderAndPrintSalaryVoucherA4(currentCompiledSettlement);
    });

    // Print Khata Statement Triggers
    $('#btnPrintKhata').on('click', function() {
        const emp = allEmployeesCache.find(e => e.id == currentEmpId);
        if (!emp) {
            alert("Please select an employee first.");
            return;
        }
        renderAndPrintKhataThermal(emp, currentLedgerData);
    });

    $('#btnPrintKhataA4').on('click', function() {
        const emp = allEmployeesCache.find(e => e.id == currentEmpId);
        if (!emp) {
            alert("Please select an employee first.");
            return;
        }
        renderAndPrintKhataA4(emp, currentLedgerData);
    });

    // 1. Enhanced Thermal Salary Voucher Print (80mm)
    function renderAndPrintSalaryVoucher(data) {
        const emp = data.employee || {};
        const dateNow = new Date();
        const formattedDate = data.created_at || (dateNow.getFullYear() + '-' + 
                              String(dateNow.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(dateNow.getDate()).padStart(2, '0'));
        const methodLabel = (data.payment_method === 'bank' || data.settlement_record?.payment_method === 'bank') ? 'BANK TRANSFER' : 'CASH (DRAWER)';
        const modeLabel = data.settlement_type === 'full_month' || data.calculation_mode === 'full_month' ? 'Full Month (30 Days)' : `Till Today (${data.days_worked || 14} Days)`;

        let itemizedTasksHtml = '';
        if (data.tasks_list && data.tasks_list.length > 0) {
            data.tasks_list.forEach(t => {
                itemizedTasksHtml += `
                    <tr>
                        <td style="padding-left: 8px; font-size: 10px; font-weight: 800; color: #000;">• ${escapeHtml(t.description)}</td>
                        <td class="right font-monospace" style="font-weight: 900; color: #000;">+Rs. ${parseFloat(t.compensation_amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        let itemizedIncentivesHtml = '';
        if (data.incentives_list && data.incentives_list.length > 0) {
            data.incentives_list.forEach(inc => {
                itemizedIncentivesHtml += `
                    <tr>
                        <td style="padding-left: 8px; font-size: 10px; font-weight: 800; color: #000;">• ${escapeHtml(inc.description || 'Bonus')}</td>
                        <td class="right font-monospace" style="font-weight: 900; color: #000;">+Rs. ${parseFloat(inc.amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        let itemizedAdvancesHtml = '';
        if (data.advances_list && data.advances_list.length > 0) {
            data.advances_list.forEach(ad => {
                itemizedAdvancesHtml += `
                    <tr>
                        <td style="padding-left: 8px; font-size: 10px; font-weight: 800; color: #000;">• ${escapeHtml(ad.description || 'Advance')} (${ad.created_at ? ad.created_at.substring(5,10) : ''})</td>
                        <td class="right font-monospace" style="font-weight: 900; color: #000;">-Rs. ${parseFloat(ad.amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        let itemizedDailyHtml = '';
        if (data.daily_payments_list && data.daily_payments_list.length > 0) {
            data.daily_payments_list.forEach(dp => {
                itemizedDailyHtml += `
                    <tr>
                        <td style="padding-left: 8px; font-size: 10px; font-weight: 800; color: #000;">• Daily Cash: ${escapeHtml(dp.description || 'Cash')} (${dp.created_at ? dp.created_at.substring(5,10) : ''})</td>
                        <td class="right font-monospace" style="font-weight: 900; color: #000;">-Rs. ${parseFloat(dp.amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        let itemizedFinesHtml = '';
        if (data.fines_list && data.fines_list.length > 0) {
            data.fines_list.forEach(fn => {
                itemizedFinesHtml += `
                    <tr>
                        <td style="padding-left: 8px; font-size: 10px; font-weight: 800; color: #000;">• Deduction: ${escapeHtml(fn.description || 'Fine')}</td>
                        <td class="right font-monospace" style="font-weight: 900; color: #000;">-Rs. ${parseFloat(fn.amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        let itemizedAbsencesHtml = '';
        if (data.absences_list && data.absences_list.length > 0) {
            data.absences_list.forEach(ab => {
                itemizedAbsencesHtml += `
                    <tr>
                        <td style="padding-left: 8px; font-size: 10px; font-weight: 800; color: #000;">• Chuti (${ab.absence_date}): ${escapeHtml(ab.reason || 'Absent')}</td>
                        <td class="right font-monospace" style="font-weight: 900; color: #000;">-Rs. ${parseFloat(ab.deduction_amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        const voucherHtml = `
            <div class="center" style="margin-bottom: 6px; color: #000;">
                <img src="${(window.BASE_URL || "")}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="${window.STORE_NAME || 'One Dollar Shop'}" class="receipt-logo">
                <h3 style="margin: 0; font-size: 16.5px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase; color: #000;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h3>
                <p style="margin: 2px 0; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #000;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'}</p>
                <div style="font-size: 12px; font-weight: 900; margin-top: 1px; color: #000;">Ph: ${window.STORE_PHONE || '0307-2681893'}</div>
                <div style="font-weight: 900; border-top: 1.5px dashed #000; border-bottom: 1.5px dashed #000; padding: 3px 0; margin: 4px 0; font-size: 12.5px; text-transform: uppercase; color: #000;">
                    *** OFFICIAL SALARY CLEARANCE VOUCHER ***
                </div>
            </div>
            <table style="width: 100%; font-size: 11px; font-weight: 800; line-height: 1.4; color: #000;">
                <tr>
                    <td><strong>Staff:</strong> <span style="font-weight: 900;">${escapeHtml(emp.name || 'Employee')}</span></td>
                    <td class="right"><strong>Month:</strong> ${data.month}</td>
                </tr>
                <tr>
                    <td><strong>Role:</strong> ${escapeHtml(emp.designation || 'Staff')}</td>
                    <td class="right"><strong>Mode:</strong> ${modeLabel}</td>
                </tr>
                <tr>
                    <td><strong>Daily Rate:</strong> Rs. ${parseFloat(data.daily_wage || 0).toFixed(2)}/d</td>
                    <td class="right"><strong>Method:</strong> ${methodLabel}</td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong> ${escapeHtml(emp.contact || '--')}</td>
                    <td class="right"><strong>Date:</strong> ${formattedDate}</td>
                </tr>
            </table>
            <div class="receipt-divider"></div>
            <table style="width: 100%; font-size: 11px; font-weight: 800; line-height: 1.35; color: #000;">
                <tr style="font-weight: 900;">
                    <td>Earned Base (${data.days_worked || 30} Days):</td>
                    <td class="right font-monospace">Rs. ${parseFloat(data.earned_base_salary || data.base_salary).toFixed(2)}</td>
                </tr>
                ${itemizedTasksHtml}
                ${itemizedIncentivesHtml}
                <tr style="border-top: 1.5px dashed #000; font-weight: 900;">
                    <td>Total Gross Earnings:</td>
                    <td class="right font-monospace">Rs. ${parseFloat(data.gross_earnings).toFixed(2)}</td>
                </tr>
                ${parseFloat(data.total_deductions) > 0 ? `
                <tr style="font-weight: 900; padding-top: 3px;">
                    <td colspan="2" style="font-size: 10.5px; color: #000; text-transform: uppercase; padding-top: 4px;">Total Deductions & Advances:</td>
                </tr>
                ${itemizedAdvancesHtml}
                ${itemizedDailyHtml}
                ${itemizedAbsencesHtml}
                ${itemizedFinesHtml}
                ${parseFloat(data.prior_unsettled || 0) > 0 ? `
                <tr>
                    <td style="padding-left: 8px; font-size: 10px; font-weight: 800;">• Prior Carried Forward Balance</td>
                    <td class="right font-monospace" style="font-weight: 900;">-Rs. ${parseFloat(data.prior_unsettled).toFixed(2)}</td>
                </tr>` : ''}
                <tr style="border-top: 1.5px dashed #000; font-weight: 900;">
                    <td>Total Deductions:</td>
                    <td class="right font-monospace">-Rs. ${parseFloat(data.total_deductions).toFixed(2)}</td>
                </tr>` : ''}
                <tr style="font-size: 14px; font-weight: 900; border-top: 2px solid #000; border-bottom: 2px solid #000;">
                    <td style="padding: 6px 0;">NET AMOUNT ${data.already_settled ? 'PAID' : 'PAYABLE'}:</td>
                    <td class="right font-monospace" style="padding: 6px 0; font-weight: 900;">
                        Rs. ${parseFloat(data.remaining_payable).toFixed(2)}
                    </td>
                </tr>
                ${data.advance_exceeded ? `
                <tr>
                    <td colspan="2" style="color: #000; font-size: 10px; font-weight: 900; padding-top: 3px; text-align: center;">
                        * ADVANCE EXCEEDS EARNED SALARY *<br>
                        Staff owes Rs. ${parseFloat(data.deficit_amount).toFixed(2)} to store.
                    </td>
                </tr>` : ''}
            </table>
            <div class="receipt-divider"></div>
            <div style="font-size: 10px; font-weight: 800; margin-top: 20px; color: #000;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%; padding-top: 25px; border-top: 1.5px dashed #000;" class="center">
                            Employee Signature
                        </td>
                        <td style="width: 50%; padding-top: 25px; border-top: 1.5px dashed #000;" class="center">
                            Authorized Manager
                        </td>
                    </tr>
                </table>
            </div>
            <div class="receipt-divider" style="margin-top: 15px;"></div>
            <div class="center" style="font-size: 9.5px; font-weight: 800; line-height: 1.3; color: #000;">
                ${window.STORE_NAME || 'One Dollar Shop'} • POS System<br>
                Software Developed by:<br>
                <span style="font-weight: 900; font-size: 10.5px;">0319-7273908 | 0336-8176491</span>
            </div>
        `;

        if (window.printThermalReceipt) {
            window.printThermalReceipt(voucherHtml);
        } else {
            window.print();
        }
    }

    // 2. Formal A4 Salary Voucher Print
    function renderAndPrintSalaryVoucherA4(data) {
        const emp = data.employee || {};
        const dateNow = new Date();
        const formattedDate = data.created_at || (dateNow.getFullYear() + '-' + 
                              String(dateNow.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(dateNow.getDate()).padStart(2, '0'));
        const methodLabel = (data.payment_method === 'bank' || data.settlement_record?.payment_method === 'bank') ? 'Bank Transfer' : 'Cash Drawer (Galla)';
        const modeLabel = data.settlement_type === 'full_month' || data.calculation_mode === 'full_month' ? 'Full Month (30 Days)' : `Pro-Rata Till Today (${data.days_worked || 14} Days)`;
        const statusBadge = data.already_settled 
            ? '<span class="badge badge-success">SETTLED & PAID</span>' 
            : '<span class="badge badge-warning">PENDING CLEARANCE</span>';

        let itemizedTasksHtml = '';
        if (data.tasks_list && data.tasks_list.length > 0) {
            data.tasks_list.forEach(t => {
                itemizedTasksHtml += `
                    <tr>
                        <td><strong style="color:#0f172a;">${escapeHtml(t.description)}</strong> <span class="text-muted small">(${t.completed_at || ''})</span></td>
                        <td class="text-end font-mono text-success fw-bold">+Rs. ${parseFloat(t.compensation_amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        let itemizedIncentivesHtml = '';
        if (data.incentives_list && data.incentives_list.length > 0) {
            data.incentives_list.forEach(inc => {
                itemizedIncentivesHtml += `
                    <tr>
                        <td><strong style="color:#0f172a;">${escapeHtml(inc.description || 'Bonus')}</strong> <span class="text-muted small">(${inc.created_at || ''})</span></td>
                        <td class="text-end font-mono text-success fw-bold">+Rs. ${parseFloat(inc.amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        let itemizedAdvancesHtml = '';
        if (data.advances_list && data.advances_list.length > 0) {
            data.advances_list.forEach(ad => {
                itemizedAdvancesHtml += `
                    <tr>
                        <td><span class="badge badge-danger" style="margin-right:5px;">Advance</span> ${escapeHtml(ad.description || 'Advance')} <span class="text-muted small">(${ad.created_at || ''})</span></td>
                        <td class="text-end font-mono text-danger fw-bold">-Rs. ${parseFloat(ad.amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        let itemizedDailyHtml = '';
        if (data.daily_payments_list && data.daily_payments_list.length > 0) {
            data.daily_payments_list.forEach(dp => {
                itemizedDailyHtml += `
                    <tr>
                        <td><span class="badge badge-warning" style="margin-right:5px;">Daily Cash</span> ${escapeHtml(dp.description || 'Daily Cash')} <span class="text-muted small">(${dp.created_at || ''})</span></td>
                        <td class="text-end font-mono text-danger fw-bold">-Rs. ${parseFloat(dp.amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        let itemizedFinesHtml = '';
        if (data.fines_list && data.fines_list.length > 0) {
            data.fines_list.forEach(fn => {
                itemizedFinesHtml += `
                    <tr>
                        <td><span class="badge badge-warning" style="margin-right:5px;">Chuti / Fine</span> ${escapeHtml(fn.description || 'Absence')} <span class="text-muted small">(${fn.created_at || ''})</span></td>
                        <td class="text-end font-mono text-danger fw-bold">-Rs. ${parseFloat(fn.amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        let itemizedAbsencesA4Html = '';
        if (data.absences_list && data.absences_list.length > 0) {
            data.absences_list.forEach(ab => {
                itemizedAbsencesA4Html += `
                    <tr>
                        <td><span class="badge badge-warning" style="margin-right:5px; background:#dc2626; color:#fff;">Chuti (Absent)</span> ${escapeHtml(ab.reason || 'Bila Itla')} <span class="text-muted small">(${ab.absence_date || ''})</span></td>
                        <td class="text-end font-mono text-danger fw-bold">-Rs. ${parseFloat(ab.deduction_amount).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        const a4Html = `
            <!-- Document Header -->
            <table style="width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 12px;">
                <tr>
                    <td style="width: 25%; vertical-align: middle;">
                        <img src="${(window.BASE_URL || "")}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="Logo" style="max-height: 70px; max-width: 180px; object-fit: contain;">
                    </td>
                    <td style="width: 50%; text-align: center; vertical-align: middle;">
                        <h2 style="margin: 0; font-size: 20px; font-weight: 900; text-transform: uppercase; color: #0f172a;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h2>
                        <p style="margin: 3px 0; font-size: 11px; color: #475569;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'} • Contact: ${window.STORE_PHONE || '0307-2681893'}</p>
                        <h4 style="margin: 4px 0 0 0; font-size: 14px; font-weight: 800; color: #dc2626; letter-spacing: 0.5px;">OFFICIAL SALARY CLEARANCE VOUCHER & PAYSLIP</h4>
                    </td>
                    <td style="width: 25%; text-align: right; vertical-align: middle;">
                        <div style="font-size: 10.5px; color: #475569;">Voucher Ref:</div>
                        <strong class="font-mono" style="font-size: 12px; color: #0f172a;">VCHR-${data.month.replace('-', '')}-${data.employee_id}</strong>
                        <div style="margin-top: 4px;">${statusBadge}</div>
                    </td>
                </tr>
            </table>

            <!-- Employee & Settlement Metadata Table -->
            <table class="table-data" style="margin-bottom: 14px;">
                <tr>
                    <td style="width: 18%; background: #f8fafc; font-weight: bold; color: #475569;">Staff Name:</td>
                    <td style="width: 32%; font-weight: bold; color: #0f172a; font-size: 12px;">${escapeHtml(emp.name || 'Employee')}</td>
                    <td style="width: 18%; background: #f8fafc; font-weight: bold; color: #475569;">Pay Period Month:</td>
                    <td style="width: 32%; font-weight: bold; font-family: monospace;">${formatMonthYearLabel(data.month)}</td>
                </tr>
                <tr>
                    <td style="background: #f8fafc; font-weight: bold; color: #475569;">Designation / Role:</td>
                    <td>${escapeHtml(emp.designation || 'Staff')}</td>
                    <td style="background: #f8fafc; font-weight: bold; color: #475569;">Calculation Mode:</td>
                    <td><strong>${modeLabel}</strong></td>
                </tr>
                <tr>
                    <td style="background: #f8fafc; font-weight: bold; color: #475569;">Contact / Phone:</td>
                    <td class="font-mono">${escapeHtml(emp.contact || '--')}</td>
                    <td style="background: #f8fafc; font-weight: bold; color: #475569;">Daily Wage Rate:</td>
                    <td class="font-mono">Rs. ${parseFloat(data.daily_wage || 0).toFixed(2)} / day</td>
                </tr>
                <tr>
                    <td style="background: #f8fafc; font-weight: bold; color: #475569;">Joining Date:</td>
                    <td class="font-mono">${emp.joining_date || '--'}</td>
                    <td style="background: #f8fafc; font-weight: bold; color: #475569;">Payment Method:</td>
                    <td><strong>${methodLabel}</strong> (${formattedDate})</td>
                </tr>
            </table>

            <!-- Earnings & Deductions Breakdown Tables (Side-by-Side) -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
                <tr>
                    <!-- LEFT COLUMN: GROSS EARNINGS -->
                    <td style="width: 49%; vertical-align: top; padding-right: 5px;">
                        <table class="table-data" style="margin-top: 0;">
                            <thead>
                                <tr style="background: #ecfdf5;">
                                    <th style="color: #065f46; border-color: #a7f3d0;"><i class="fas fa-plus-circle"></i> 1. Gross Earnings & Additions</th>
                                    <th class="text-end" style="color: #065f46; border-color: #a7f3d0;">Amount (Rs.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong>Base Salary (${data.days_worked || 30} Days)</strong><br>
                                        <small class="text-muted">Rate: Rs. ${parseFloat(data.daily_wage || 0).toFixed(2)} × ${data.days_worked || 30} days</small>
                                    </td>
                                    <td class="text-end font-mono fw-bold">Rs. ${parseFloat(data.earned_base_salary || data.base_salary).toFixed(2)}</td>
                                </tr>
                                ${itemizedTasksHtml}
                                ${itemizedIncentivesHtml}
                                <tr style="background: #f0fdf4; border-top: 2px solid #16a34a; font-weight: bold;">
                                    <td style="color: #166534; font-size: 11.5px;">TOTAL GROSS EARNINGS:</td>
                                    <td class="text-end font-mono text-success" style="font-size: 12.5px;">Rs. ${parseFloat(data.gross_earnings).toFixed(2)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>

                    <!-- RIGHT COLUMN: DEDUCTIONS -->
                    <td style="width: 49%; vertical-align: top; padding-left: 5px;">
                        <table class="table-data" style="margin-top: 0;">
                            <thead>
                                <tr style="background: #fef2f2;">
                                    <th style="color: #991b1b; border-color: #fecaca;"><i class="fas fa-minus-circle"></i> 2. Deductions & Advances</th>
                                    <th class="text-end" style="color: #991b1b; border-color: #fecaca;">Amount (Rs.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${parseFloat(data.total_deductions) === 0 ? `
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No advances or deductions recorded for this month.</td>
                                </tr>` : `
                                ${itemizedAdvancesHtml}
                                ${itemizedDailyHtml}
                                ${itemizedAbsencesA4Html}
                                ${itemizedFinesHtml}
                                ${parseFloat(data.prior_unsettled || 0) > 0 ? `
                                <tr>
                                    <td><strong>Carried Forward Balance</strong></td>
                                    <td class="text-end font-mono text-danger fw-bold">-Rs. ${parseFloat(data.prior_unsettled).toFixed(2)}</td>
                                </tr>` : ''}`}
                                <tr style="background: #fef2f2; border-top: 2px solid #dc2626; font-weight: bold;">
                                    <td style="color: #991b1b; font-size: 11.5px;">TOTAL DEDUCTIONS:</td>
                                    <td class="text-end font-mono text-danger" style="font-size: 12.5px;">-Rs. ${parseFloat(data.total_deductions).toFixed(2)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Giant Net Cash Amount Card -->
            <div style="background: #0f172a; color: #ffffff; padding: 14px 20px; border-radius: 8px; margin-bottom: 14px; text-align: center;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 600;">
                    ${data.advance_exceeded ? 'ADVANCE EXCEEDS EARNED SALARY (STAFF OWES STORE):' : 'FINAL NET CASH AMOUNT TO HAND OVER / SETTLED:'}
                </div>
                <div class="font-mono" style="font-size: 28px; font-weight: 900; color: ${data.advance_exceeded ? '#f87171' : '#facc15'}; margin: 4px 0;">
                    Rs. ${parseFloat(data.remaining_payable).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                </div>
                <div style="font-size: 11px; color: #cbd5e1; font-family: monospace;">
                    (Total Gross Earnings: Rs. ${parseFloat(data.gross_earnings).toFixed(2)} − Total Deductions: Rs. ${parseFloat(data.total_deductions).toFixed(2)})
                </div>
                ${data.advance_exceeded ? `
                <div style="background: #7f1d1d; color: #fecaca; padding: 6px 12px; border-radius: 4px; font-size: 11px; margin-top: 8px; font-weight: bold;">
                    ⚠️ Warning: Staff has taken Rs. ${parseFloat(data.deficit_amount).toFixed(2)} more in advances & daily cash than earned salary up to today. Staff owes this deficit balance to the store.
                </div>` : ''}
            </div>

            <!-- Notes & Audit Trail -->
            ${data.notes || data.settlement_record?.notes ? `
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 6px; margin-bottom: 16px; font-size: 11px;">
                <strong>Payment / Settlement Remarks:</strong> ${escapeHtml(data.notes || data.settlement_record?.notes)}
            </div>` : ''}

            <!-- Formal Signatures & Certification Block -->
            <div style="margin-top: 30px; padding-top: 10px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 32%; text-align: center; vertical-align: bottom;">
                            <div style="border-top: 1.5px dashed #475569; padding-top: 6px; font-weight: bold; font-size: 11px;">
                                Employee / Staff Signature<br>
                                <span style="font-size: 9.5px; color: #64748b; font-weight: normal;">(Received cash in full settlement)</span>
                            </div>
                        </td>
                        <td style="width: 36%; text-align: center; vertical-align: bottom;">
                            <div style="border-top: 1.5px dashed #475569; padding-top: 6px; font-weight: bold; font-size: 11px;">
                                Cashier / Prepared By<br>
                                <span style="font-size: 9.5px; color: #64748b; font-weight: normal;">Account Verification</span>
                            </div>
                        </td>
                        <td style="width: 32%; text-align: center; vertical-align: bottom;">
                            <div style="border-top: 1.5px dashed #475569; padding-top: 6px; font-weight: bold; font-size: 11px;">
                                Authorized Store Owner / Stamp<br>
                                <span style="font-size: 9.5px; color: #64748b; font-weight: normal;">${window.STORE_NAME || 'One Dollar Shop'}</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="border-top: 1px solid #e2e8f0; margin-top: 25px; padding-top: 8px; text-align: center; font-size: 9px; color: #64748b;">
                Enterprise POS Payroll Module • ${window.STORE_NAME || 'One Dollar Shop'} • Software Support: 0319-7273908 | 0336-8176491
            </div>
        `;

        printA4Document(a4Html, `Salary-Voucher-${emp.name || 'Staff'}-${data.month}`);
    }

    // 3. Thermal Khata Statement Print (80mm)
    function renderAndPrintKhataThermal(emp, ledgerData) {
        const dateNow = new Date().toLocaleDateString();
        let rowsHtml = '';
        
        if (ledgerData && ledgerData.transactions) {
            const openBal = parseFloat(ledgerData.opening_balance || 0);
            if (openBal !== 0) {
                rowsHtml += `
                    <tr style="font-weight: 900; color: #000; border-bottom: 1px dashed #000;">
                        <td style="font-size: 10px; padding: 2px 1px;">Sabqa Baqaya</td>
                        <td style="font-size: 10px; padding: 2px 1px;">Opening</td>
                        <td class="right font-monospace" style="font-size: 10px; padding: 2px 1px;">${openBal > 0 ? openBal.toFixed(2) : '-'}</td>
                        <td class="right font-monospace" style="font-size: 10px; padding: 2px 1px;">${openBal < 0 ? Math.abs(openBal).toFixed(2) : '-'}</td>
                        <td class="right font-monospace" style="font-size: 10px; padding: 2px 1px;">${openBal.toFixed(2)}</td>
                    </tr>
                `;
            }
            ledgerData.transactions.forEach(t => {
                rowsHtml += `
                    <tr style="color: #000; border-bottom: 1px dashed #000;">
                        <td style="font-size: 9.5px; font-weight: 800; padding: 2px 1px;">${t.created_at ? t.created_at.substring(5, 16) : ''}</td>
                        <td style="font-size: 9.5px; font-weight: 800; padding: 2px 1px;">${escapeHtml(t.description || t.type)}</td>
                        <td class="right font-monospace" style="font-size: 9.5px; font-weight: 900; padding: 2px 1px;">${t.debit > 0 ? parseFloat(t.debit).toFixed(2) : '-'}</td>
                        <td class="right font-monospace" style="font-size: 9.5px; font-weight: 900; padding: 2px 1px;">${t.credit > 0 ? parseFloat(t.credit).toFixed(2) : '-'}</td>
                        <td class="right font-monospace" style="font-size: 9.5px; font-weight: 900; padding: 2px 1px;">${parseFloat(t.running_balance).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        const khataReceipt = `
            <div class="center" style="margin-bottom: 6px; color: #000;">
                <img src="${(window.BASE_URL || "")}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="${window.STORE_NAME || 'One Dollar Shop'}" class="receipt-logo">
                <h3 style="margin: 0; font-size: 16.5px; font-weight: 900; text-transform: uppercase; color: #000;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h3>
                <p style="margin: 2px 0; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #000;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'}</p>
                <div style="font-size: 12px; font-weight: 900; margin-top: 1px; color: #000;">Ph: ${window.STORE_PHONE || '0307-2681893'}</div>
                <div style="font-weight: 900; border-top: 1.5px dashed #000; border-bottom: 1.5px dashed #000; padding: 3px 0; margin: 4px 0; font-size: 12.5px; text-transform: uppercase; color: #000;">
                    *** STAFF ADVANCE KHATA STATEMENT ***
                </div>
                <div style="font-size: 11.5px; font-weight: 900; color: #000; margin-top: 2px;">Staff: ${escapeHtml(emp.name)} (${escapeHtml(emp.designation)})</div>
                <div style="font-size: 10px; font-weight: 800; color: #000;">Date: ${dateNow} | Base: Rs. ${parseFloat(emp.current_salary).toFixed(0)}/mo</div>
            </div>
            <div class="receipt-divider"></div>
            <table style="width: 100%; font-size: 10px; font-weight: 800; line-height: 1.3; color: #000;">
                <thead>
                    <tr style="border-bottom: 1.5px dashed #000; font-weight: 900; font-size: 10px;">
                        <td style="padding: 2px 1px;">DATE</td>
                        <td style="padding: 2px 1px;">REMARKS</td>
                        <td class="right" style="padding: 2px 1px;">DEBIT</td>
                        <td class="right" style="padding: 2px 1px;">CREDIT</td>
                        <td class="right" style="padding: 2px 1px;">BAL</td>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>
            <div class="receipt-divider"></div>
            <div style="font-size: 13px; font-weight: 900; display: flex; justify-content: space-between; padding: 4px 0; color: #000; border-bottom: 1.5px dashed #000;">
                <span>Net Khata Balance:</span>
                <span>${$('#detailBalance').text()}</span>
            </div>
            <div class="receipt-divider" style="margin-top: 15px;"></div>
            <div class="center" style="font-size: 9.5px; font-weight: 800; line-height: 1.3; color: #000;">
                ${window.STORE_NAME || 'One Dollar Shop'} • POS Payroll<br>
                Software Developed by:<br>
                <span style="font-weight: 900; font-size: 10.5px;">0319-7273908 | 0336-8176491</span>
            </div>
        `;

        if (window.printThermalReceipt) {
            window.printThermalReceipt(khataReceipt);
        } else {
            window.print();
        }
    }

    // 4. Formal A4 Khata Statement Print
    function renderAndPrintKhataA4(emp, ledgerData) {
        const dateNow = new Date().toLocaleDateString();
        const breakdown = ledgerData?.category_breakdown || {};
        const monthLabel = $('#filterLedgerMonth option:selected').text() || 'All Time';

        let rowsHtml = '';
        const openBal = parseFloat(ledgerData?.opening_balance || 0);
        if (openBal !== 0 && $('#filterLedgerMonth').val() !== 'all') {
            const openClass = openBal > 0 ? 'text-danger' : 'text-success';
            rowsHtml += `
                <tr style="background: #fffbeb; font-weight: bold;">
                    <td>Sabqa Baqaya</td>
                    <td><span class="badge badge-dark">Opening Balance</span></td>
                    <td>Previous Month(s) Carried Forward Balance</td>
                    <td class="text-end font-mono">${openBal > 0 ? 'Rs. ' + openBal.toFixed(2) : '-'}</td>
                    <td class="text-end font-mono">${openBal < 0 ? 'Rs. ' + Math.abs(openBal).toFixed(2) : '-'}</td>
                    <td class="text-end font-mono fw-bold ${openClass}">${openBal.toFixed(2)}</td>
                </tr>
            `;
        }

        if (ledgerData && ledgerData.transactions && ledgerData.transactions.length > 0) {
            ledgerData.transactions.forEach(t => {
                let badgeClass = 'badge-dark';
                if (t.type === 'advance' || t.type === 'daily_payment') badgeClass = 'badge-danger';
                if (t.type === 'incentive' || t.type === 'salary_settlement') badgeClass = 'badge-success';
                if (t.type === 'deduction') badgeClass = 'badge-warning';

                const debitText = t.debit > 0 ? `<span class="text-danger font-mono fw-bold">-Rs. ${parseFloat(t.debit).toFixed(2)}</span>` : '-';
                const creditText = t.credit > 0 ? `<span class="text-success font-mono fw-bold">+Rs. ${parseFloat(t.credit).toFixed(2)}</span>` : '-';
                const balClass = t.running_balance > 0 ? 'text-danger' : (t.running_balance < 0 ? 'text-success' : '');

                rowsHtml += `
                    <tr>
                        <td class="font-mono small">${t.created_at || ''}</td>
                        <td><span class="badge ${badgeClass}">${t.type}</span></td>
                        <td><strong style="color:#0f172a;">${escapeHtml(t.description || '')}</strong></td>
                        <td class="text-end">${debitText}</td>
                        <td class="text-end">${creditText}</td>
                        <td class="text-end font-mono fw-bold ${balClass}">Rs. ${parseFloat(t.running_balance).toFixed(2)}</td>
                    </tr>
                `;
            });
        } else if (openBal === 0) {
            rowsHtml = '<tr><td colspan="6" class="text-center text-muted py-4">No transactions recorded for this period.</td></tr>';
        }

        const a4KhataHtml = `
            <!-- Header -->
            <table style="width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 12px;">
                <tr>
                    <td style="width: 25%; vertical-align: middle;">
                        <img src="${(window.BASE_URL || "")}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="Logo" style="max-height: 70px; max-width: 180px; object-fit: contain;">
                    </td>
                    <td style="width: 50%; text-align: center; vertical-align: middle;">
                        <h2 style="margin: 0; font-size: 20px; font-weight: 900; text-transform: uppercase; color: #0f172a;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h2>
                        <p style="margin: 3px 0; font-size: 11px; color: #475569;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'} • Ph: ${window.STORE_PHONE || '0307-2681893'}</p>
                        <h4 style="margin: 4px 0 0 0; font-size: 14px; font-weight: 800; color: #dc2626; letter-spacing: 0.5px;">STAFF KHATA ADVANCE STATEMENT & PASSBOOK</h4>
                    </td>
                    <td style="width: 25%; text-align: right; vertical-align: middle;">
                        <div style="font-size: 11px; color: #475569;">Statement Date:</div>
                        <strong class="font-mono" style="font-size: 12px; color: #0f172a;">${dateNow}</strong>
                        <div style="font-size: 11px; color: #475569; margin-top: 4px;">Period: <strong>${monthLabel}</strong></div>
                    </td>
                </tr>
            </table>

            <!-- Employee Info -->
            <table class="table-data" style="margin-bottom: 12px;">
                <tr>
                    <td style="width: 18%; background: #f8fafc; font-weight: bold; color: #475569;">Staff Name:</td>
                    <td style="width: 32%; font-weight: bold; color: #0f172a; font-size: 12px;">${escapeHtml(emp.name)}</td>
                    <td style="width: 18%; background: #f8fafc; font-weight: bold; color: #475569;">Monthly Salary:</td>
                    <td style="width: 32%; font-weight: bold; font-family: monospace;">Rs. ${parseFloat(emp.current_salary).toLocaleString()} / mo</td>
                </tr>
                <tr>
                    <td style="background: #f8fafc; font-weight: bold; color: #475569;">Role / Designation:</td>
                    <td>${escapeHtml(emp.designation)}</td>
                    <td style="background: #f8fafc; font-weight: bold; color: #475569;">Phone Number:</td>
                    <td class="font-mono">${escapeHtml(emp.contact || '--')}</td>
                </tr>
            </table>

            <!-- 4 Overview Metric Cards -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
                <tr>
                    <td style="width: 25%; padding: 3px;">
                        <div style="border: 1px solid #fca5a5; background: #fef2f2; padding: 8px 10px; border-radius: 6px;">
                            <div style="font-size: 10px; text-transform: uppercase; color: #991b1b; font-weight: bold;">Total Advances</div>
                            <div class="font-mono" style="font-size: 14px; font-weight: bold; color: #dc2626;">Rs. ${parseFloat(breakdown.total_advances || 0).toFixed(2)}</div>
                        </div>
                    </td>
                    <td style="width: 25%; padding: 3px;">
                        <div style="border: 1px solid #fde68a; background: #fffbeb; padding: 8px 10px; border-radius: 6px;">
                            <div style="font-size: 10px; text-transform: uppercase; color: #92400e; font-weight: bold;">Daily Cash</div>
                            <div class="font-mono" style="font-size: 14px; font-weight: bold; color: #d97706;">Rs. ${parseFloat(breakdown.total_daily_cash || 0).toFixed(2)}</div>
                        </div>
                    </td>
                    <td style="width: 25%; padding: 3px;">
                        <div style="border: 1px solid #86efac; background: #f0fdf4; padding: 8px 10px; border-radius: 6px;">
                            <div style="font-size: 10px; text-transform: uppercase; color: #166534; font-weight: bold;">Bonuses & Tasks</div>
                            <div class="font-mono" style="font-size: 14px; font-weight: bold; color: #16a34a;">+Rs. ${(parseFloat(breakdown.total_bonuses || 0) + parseFloat(breakdown.total_tasks || 0)).toFixed(2)}</div>
                        </div>
                    </td>
                    <td style="width: 25%; padding: 3px;">
                        <div style="border: 1px solid #cbd5e1; background: #0f172a; color: #ffffff; padding: 8px 10px; border-radius: 6px;">
                            <div style="font-size: 10px; text-transform: uppercase; color: #94a3b8; font-weight: bold;">Net Khata Owed</div>
                            <div class="font-mono" style="font-size: 14px; font-weight: bold; color: #facc15;">Rs. ${parseFloat(ledgerData?.net_balance || 0).toFixed(2)}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Transactions Table -->
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 17%;">Date & Time</th>
                        <th style="width: 15%;">Type</th>
                        <th style="width: 32%;">Description / Remarks</th>
                        <th class="text-end" style="width: 12%;">Debit (- Taken)</th>
                        <th class="text-end" style="width: 12%;">Credit (+ Earned)</th>
                        <th class="text-end" style="width: 12%;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>

            <!-- Signatures -->
            <div style="margin-top: 35px; padding-top: 10px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 45%; text-align: center; vertical-align: bottom;">
                            <div style="border-top: 1.5px dashed #475569; padding-top: 6px; font-weight: bold; font-size: 11px;">
                                Employee Signature<br>
                                <span style="font-size: 9.5px; color: #64748b; font-weight: normal;">Acknowledgement of Ledger</span>
                            </div>
                        </td>
                        <td style="width: 10%;"></td>
                        <td style="width: 45%; text-align: center; vertical-align: bottom;">
                            <div style="border-top: 1.5px dashed #475569; padding-top: 6px; font-weight: bold; font-size: 11px;">
                                Authorized Store Owner / Manager<br>
                                <span style="font-size: 9.5px; color: #64748b; font-weight: normal;">${window.STORE_NAME || 'One Dollar Shop'}</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="border-top: 1px solid #e2e8f0; margin-top: 25px; padding-top: 8px; text-align: center; font-size: 9px; color: #64748b;">
                Enterprise Staff Khata Ledger • ${window.STORE_NAME || 'One Dollar Shop'} • Software Support: 0319-7273908 | 0336-8176491
            </div>
        `;

        printA4Document(a4KhataHtml, `Staff-Khata-${emp.name || 'Statement'}`);
    }

    // -------------------------------------------------------------------------
    // 9. ADD & EDIT EMPLOYEE PROFILES
    // -------------------------------------------------------------------------
    $('.role-preset-chip').on('click', function() {
        $('#new_designation').val($(this).data('role'));
    });

    $('#newEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        $('#newEmpAlert').addClass('d-none');
        $('#btnSaveNewEmployee').prop('disabled', true);

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=add',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $('#btnSaveNewEmployee').prop('disabled', false);
                if (res.success) {
                    hideBootstrapModal('#newEmployeeModal');
                    $('#newEmployeeForm')[0].reset();
                    $('#new_joining_date').val(today);
                    showToast(res.message, 'success');
                    loadStaffDirectory();
                    loadKPIs();
                } else {
                    $('#newEmpAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnSaveNewEmployee').prop('disabled', false);
                $('#newEmpAlert').text(xhr.responseJSON?.message || 'Error saving employee.').removeClass('d-none');
            }
        });
    });

    function openEditModal(empId) {
        const id = parseInt(empId, 10);
        if (id <= 0) return;

        // Immediate pre-fill from cache to eliminate UI lag
        const cached = allEmployeesCache.find(e => e.id == id);
        if (cached) {
            $('#edit_id').val(cached.id);
            $('#edit_name').val(cached.name);
            $('#edit_contact').val(cached.contact);
            $('#edit_designation').val(cached.designation);
            $('#edit_salary').val(cached.current_salary || cached.salary || 0);
            $('#edit_joining_date').val(cached.joining_date);
            $('#edit_status').val(cached.status);
            $('#edit_notes').val(cached.notes || '');
            $('#editEmpAlert').addClass('d-none');
            showBootstrapModal('#editEmployeeModal');
        }

        $.ajax({
            url: `${(window.BASE_URL || "")}/api/employees.php?action=get&id=${id}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    const emp = res.employee;
                    $('#edit_id').val(emp.id);
                    $('#edit_name').val(emp.name);
                    $('#edit_contact').val(emp.contact);
                    $('#edit_designation').val(emp.designation);
                    $('#edit_salary').val(emp.salary);
                    $('#edit_joining_date').val(emp.joining_date);
                    $('#edit_status').val(emp.status);
                    $('#edit_notes').val(emp.notes || '');
                    $('#editEmpAlert').addClass('d-none');
                    if (!cached) {
                        showBootstrapModal('#editEmployeeModal');
                    }
                }
            }
        });
    }
    window.openEditModal = openEditModal;

    $('#editEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        $('#editEmpAlert').addClass('d-none');
        $('#btnUpdateEmployee').prop('disabled', true);

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=update',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $('#btnUpdateEmployee').prop('disabled', false);
                if (res.success) {
                    hideBootstrapModal('#editEmployeeModal');
                    showToast(res.message, 'success');
                    loadStaffDirectory(function() {
                        if (currentEmpId == $('#edit_id').val()) {
                            openEmployeeDetail(currentEmpId, 'ledger');
                        }
                    });
                    loadKPIs();
                } else {
                    $('#editEmpAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnUpdateEmployee').prop('disabled', false);
                $('#editEmpAlert').text(xhr.responseJSON?.message || 'Error updating employee.').removeClass('d-none');
            }
        });
    });

    $('#btnDeleteEmployeeFromEdit').on('click', function() {
        const empId = $('#edit_id').val();
        const empName = $('#edit_name').val() || 'This employee';
        if (!empId) return;

        if (!confirm(`Are you sure you want to permanently delete "${empName}"?\n\nThis will permanently delete this employee profile, tasks, pay pauses, salary history, and unlink transactions. This action cannot be undone.`)) {
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Deleting...');

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=delete',
            type: 'POST',
            data: { id: empId },
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="fas fa-trash-can me-1"></i> Delete Profile');
                if (res.success) {
                    hideBootstrapModal('#editEmployeeModal');
                    showToast(res.message || 'Employee profile deleted successfully.', 'success');
                    if (currentEmpId == empId) {
                        $('#sectionEmployeeDetail').addClass('d-none');
                        $('#sectionStaffDirectory').removeClass('d-none');
                        currentEmpId = null;
                    }
                    loadStaffDirectory();
                    loadKPIs();
                } else {
                    alert(res.message || 'Failed to delete employee profile.');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-trash-can me-1"></i> Delete Profile');
                alert(xhr.responseJSON?.message || 'Error deleting employee profile.');
            }
        });
    });

    // -------------------------------------------------------------------------
    // 10. PAY PAUSE & RESUME HANDLERS (AUDIT TRAIL & LOG)
    // -------------------------------------------------------------------------
    function openPausePayModal(empId) {
        const id = parseInt(empId, 10);
        const emp = allEmployeesCache.find(e => e.id == id);
        if (!emp) return;

        $('#pause_emp_id').val(emp.id);
        $('#pauseEmpName').text(`${emp.name} (${emp.designation})`);
        $('#pauseAvatar').text(emp.name.substring(0, 2).toUpperCase());
        $('#pause_date').val(new Date().toISOString().split('T')[0]);
        $('#pause_reason').val('');
        $('#pauseAlert').addClass('d-none');
        $('#btnConfirmPausePay').prop('disabled', false);
        showBootstrapModal('#pausePayModal');
    }
    window.openPausePayModal = openPausePayModal;

    $('#pausePayForm').on('submit', function(e) {
        e.preventDefault();
        $('#pauseAlert').addClass('d-none');
        $('#btnConfirmPausePay').prop('disabled', true);

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=pause_pay',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $('#btnConfirmPausePay').prop('disabled', false);
                if (res.success) {
                    hideBootstrapModal('#pausePayModal');
                    showToast(res.message, 'warning');
                    loadStaffDirectory(function() {
                        if (currentEmpId == $('#pause_emp_id').val()) {
                            openEmployeeDetail(currentEmpId, 'ledger');
                        }
                    });
                    loadKPIs();
                } else {
                    $('#pauseAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnConfirmPausePay').prop('disabled', false);
                $('#pauseAlert').text(xhr.responseJSON?.message || 'Failed to pause pay.').removeClass('d-none');
            }
        });
    });

    function openResumePayModal(empId) {
        const id = parseInt(empId, 10);
        const emp = allEmployeesCache.find(e => e.id == id);
        if (!emp) return;

        $('#resume_emp_id').val(emp.id);
        $('#resumeEmpName').text(`${emp.name} (${emp.designation})`);
        $('#resumeAvatar').text(emp.name.substring(0, 2).toUpperCase());
        $('#resume_date').val(new Date().toISOString().split('T')[0]);
        $('#resume_notes').val('');
        $('#resumeAlert').addClass('d-none');
        $('#btnConfirmResumePay').prop('disabled', false);
        showBootstrapModal('#resumePayModal');
    }
    window.openResumePayModal = openResumePayModal;

    $('#resumePayForm').on('submit', function(e) {
        e.preventDefault();
        $('#resumeAlert').addClass('d-none');
        $('#btnConfirmResumePay').prop('disabled', true);

        $.ajax({
            url: (window.BASE_URL || '') + '/api/employees.php?action=resume_pay',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $('#btnConfirmResumePay').prop('disabled', false);
                if (res.success) {
                    hideBootstrapModal('#resumePayModal');
                    showToast(res.message, 'success');
                    loadStaffDirectory(function() {
                        if (currentEmpId == $('#resume_emp_id').val()) {
                            openEmployeeDetail(currentEmpId, 'ledger');
                        }
                    });
                    loadKPIs();
                } else {
                    $('#resumeAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnConfirmResumePay').prop('disabled', false);
                $('#resumeAlert').text(xhr.responseJSON?.message || 'Failed to resume pay.').removeClass('d-none');
            }
        });
    });

    function openPauseHistoryModal(empId) {
        const id = parseInt(empId, 10);
        const emp = allEmployeesCache.find(e => e.id == id);
        if (emp) {
            $('#pauseHistEmpName').text(`${emp.name} (${emp.designation})`);
        }
        $('#pauseHistTableBody').html('<tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading history...</td></tr>');
        showBootstrapModal('#pauseHistoryModal');

        $.ajax({
            url: `${(window.BASE_URL || "")}/api/employees.php?action=pause_history&employee_id=${id}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    const list = res.history || [];
                    $('#pauseHistCount').text(`${list.length} Records`);
                    if (list.length === 0) {
                        $('#pauseHistTableBody').html('<tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success me-1"></i>No pay pause records found. Employee salary is continuous.</td></tr>');
                        return;
                    }
                    let html = '';
                    list.forEach(p => {
                        const isPaused = p.status === 'paused' || !p.resume_date;
                        const badge = isPaused ? 
                            '<span class="badge bg-warning text-dark"><i class="fas fa-pause-circle me-1"></i>Currently Paused</span>' : 
                            '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Resumed</span>';
                        html += `
                            <tr>
                                <td class="font-monospace fw-bold text-danger"><i class="fas fa-calendar-xmark me-1"></i>${p.pause_date}</td>
                                <td class="font-monospace fw-bold text-success">${p.resume_date ? '<i class="fas fa-calendar-check me-1"></i>' + p.resume_date : '<span class="badge bg-warning text-dark">Still Paused</span>'}</td>
                                <td>${badge}</td>
                                <td><strong>${escapeHtml(p.reason || '--')}</strong></td>
                                <td class="small text-muted">${escapeHtml(p.created_by_name || 'Admin')}</td>
                            </tr>
                        `;
                    });
                    $('#pauseHistTableBody').html(html);
                }
            }
        });
    }
    window.openPauseHistoryModal = openPauseHistoryModal;

    function loadEmployeePauses(empId) {
        if (!empId) return;
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/employees.php?action=pause_history&employee_id=${empId}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    const list = res.history || [];
                    if (list.length === 0) {
                        $('#tabPauseLogBody').html('<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-check-circle text-success me-2"></i>No pay pause records for this staff. Salary is active and continuous.</td></tr>');
                        return;
                    }
                    let html = '';
                    list.forEach(p => {
                        const isPaused = p.status === 'paused' || !p.resume_date;
                        const badge = isPaused ? 
                            '<span class="badge bg-warning text-dark"><i class="fas fa-pause-circle me-1"></i>Currently Paused</span>' : 
                            '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Resumed</span>';
                        html += `
                            <tr>
                                <td class="font-monospace fw-bold text-danger"><i class="fas fa-calendar-xmark me-1"></i>${p.pause_date}</td>
                                <td class="font-monospace fw-bold text-success">${p.resume_date ? '<i class="fas fa-calendar-check me-1"></i>' + p.resume_date : '<span class="badge bg-warning text-dark">Still Paused</span>'}</td>
                                <td>${badge}</td>
                                <td><strong>${escapeHtml(p.reason || '--')}</strong></td>
                                <td class="small text-muted">${escapeHtml(p.created_by_name || 'Admin')}</td>
                                <td class="small font-monospace text-muted">${p.created_at}</td>
                            </tr>
                        `;
                    });
                    $('#tabPauseLogBody').html(html);
                }
            }
        });
    }
    window.loadEmployeePauses = loadEmployeePauses;

    // Delegated Global Click Handlers
    $(document).on('click', '.btn-card-edit, .btn-detail-edit', function(e) {
        e.stopPropagation();
        const id = $(this).closest('[data-id]').data('id') || currentEmpId;
        if (id > 0) openEditModal(id);
    });

    $(document).on('click', '.btn-card-pause, #btnHeaderPausePay, .btn-tab-pause-pay', function(e) {
        e.stopPropagation();
        const id = $(this).closest('[data-id]').data('id') || currentEmpId;
        if (id > 0) openPausePayModal(id);
    });

    $(document).on('click', '.btn-card-resume, #btnHeaderResumePay, .btn-tab-resume-pay', function(e) {
        e.stopPropagation();
        const id = $(this).closest('[data-id]').data('id') || currentEmpId;
        if (id > 0) openResumePayModal(id);
    });

    $(document).on('click', '#btnHeaderPauseLog', function(e) {
        e.stopPropagation();
        if (currentEmpId > 0) openPauseHistoryModal(currentEmpId);
    });

    $('#pill-pauses-tab').on('shown.bs.tab', function() {
        if (currentEmpId > 0) loadEmployeePauses(currentEmpId);
    });

    // Helper: Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
