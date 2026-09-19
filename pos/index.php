<?php
require_once __DIR__ . '/../includes/header.php';
requirePermission('CREATE_SALE');
?>

<style>
.scan-highlight {
    background-color: rgba(16, 185, 129, 0.25) !important;
    color: #34d399 !important;
    transition: background-color 0.4s ease;
}
</style>

<div class="container-fluid px-1 px-md-2 d-print-none">
    <!-- Main Screen: Full Width Product Selection Catalog -->
    <div class="row g-2 g-md-3">
        <div class="col-12 d-print-none">
            <div class="card d-flex flex-column pos-catalog-card" style="height: calc(100vh - 110px); min-height: 400px; overflow: hidden; border-radius: 8px; border: 1px solid var(--card-border);">
                <div class="card-header py-2 px-2 px-md-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2 flex-grow-1 flex-wrap">
                        <h5 class="mb-0 font-weight-bold text-light fs-6"><i class="fas fa-boxes me-1 text-danger"></i>Catalog</h5>
                        <!-- Global Scanner Input (focused by default) with Autocomplete -->
                        <div class="position-relative flex-grow-1" style="max-width: 260px; min-width: 170px;">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-secondary"><i class="fas fa-barcode"></i></span>
                                <input type="text" class="form-control" id="posSearchInput" placeholder="Scan Barcode here..." autofocus autocomplete="off">
                            </div>
                            <div id="mainSearchResultsPanel" class="search-results-panel w-100 mt-1 p-0 d-none">
                                <div class="list-group list-group-flush" id="mainSearchResultsList">
                                    <!-- Dynamic items load here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-1 gap-md-2 flex-wrap">
                        <!-- Filter Search Input -->
                        <div class="input-group input-group-sm" style="max-width: 240px; min-width: 150px;">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control" id="catalogSearchInput" placeholder="Search catalog...">
                        </div>
                        
                        <!-- Quick Expense Shortcut for Cashier -->
                        <button type="button" class="btn btn-outline-danger btn-sm font-weight-bold px-2 py-1 shadow-sm" id="btnPosQuickExpense" title="Record Petty Cash / Khana / Chai">
                            <i class="fas fa-wallet me-1"></i> Expense
                        </button>

                        <!-- Floating View Cart Button -->
                        <button type="button" class="btn btn-danger btn-sm font-weight-bold px-2.5 py-1" id="btnOpenCartModal" data-bs-toggle="modal" data-bs-target="#posCartModal">
                            <i class="fas fa-shopping-cart me-1"></i> Cart (<span id="globalCartCount">0</span>) - <span id="globalCartTotal">Rs. 0.00</span>
                        </button>
                    </div>
                </div>
                
                <div class="card-body p-0 flex-grow-1 overflow-y-auto" style="height: 100%;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Barcode</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th class="text-end" style="width: 140px;">Price</th>
                                    <th class="text-center" style="width: 120px;">Stock</th>
                                </tr>
                            </thead>
                            <tbody id="catalogTableBody">
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-spinner fa-spin display-6 mb-2"></i>
                                        <p class="mb-0">Loading product catalog...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Cart Popup Modal (Slide drawer style or Large modal) -->
<div class="modal fade d-print-none" id="posCartModal" tabindex="-1" aria-labelledby="posCartModalLabel" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered pos-cart-modal-dialog">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-slate text-white py-2 px-3">
                <h5 class="modal-title font-weight-bold fs-6" id="posCartModalLabel"><i class="fas fa-shopping-cart me-2 text-danger"></i>Active Cart Checkout</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-2 p-md-3 pos-cart-modal-body">
                <div class="row g-2 g-md-3">
                    <!-- Left Side: Cart Items list & Inline Search (col-lg-7) -->
                    <div class="col-lg-7 d-flex flex-column border-end-lg pe-lg-3" style="border-right: 1px solid var(--border-subtle, rgba(255,255,255,0.08));">
                        <!-- Cart Actions & Hold Bills -->
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: var(--border-subtle, rgba(255,255,255,0.08)) !important;">
                            <div class="d-flex gap-1 align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-warning font-weight-bold" id="btnShowHeldList" data-bs-toggle="collapse" data-bs-target="#heldCartsCollapse" aria-expanded="false" aria-controls="heldCartsCollapse">
                                    <i class="fas fa-pause-circle"></i> Held Bills (<span id="heldCartsCount">0</span>)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearCartBtn"><i class="fas fa-trash-can"></i> Clear</button>
                                <button type="button" class="btn btn-sm btn-warning font-weight-bold" id="holdCartBtn" title="Suspend Cart"><i class="fas fa-pause"></i> Hold</button>
                            </div>
                            <kbd class="bg-secondary text-white ms-1 small">Ctrl+H toggles Held Bills</kbd>
                        </div>

                        <!-- Held Bills Collapse drawer inside Modal -->
                        <div class="collapse mb-3" id="heldCartsCollapse">
                            <div class="card card-body p-2 border-warning" style="background: rgba(245, 158, 11, 0.08);">
                                <h6 class="font-weight-bold text-warning mb-2 small"><i class="fas fa-pause-circle"></i> Suspended Carts / Held Bills</h6>
                                <div class="table-responsive" style="max-height: 150px;">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Hold #</th>
                                                <th class="text-center">Items</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-end" style="width: 110px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="heldCartsTableBody">
                                            <tr>
                                                <td colspan="4" class="text-center py-2 text-muted">No suspended bills active.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Inline search/scan inside Cart Modal with Autocomplete Floating Panel -->
                        <div class="mb-3 position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="modalCartSearchInput" class="form-label small fw-bold text-secondary mb-0">Continuous Barcode Scanner / Product Search</label>
                                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 0.72rem;">
                                    <i class="fas fa-bolt text-warning me-1"></i>Continuous Scan Active (Beep On)
                                </span>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-secondary"><i class="fas fa-barcode"></i></span>
                                <input type="text" class="form-control" id="modalCartSearchInput" placeholder="Scan barcode or type product name to add directly..." autocomplete="off">
                            </div>
                            <!-- Autocomplete Panel -->
                            <div id="modalSearchResultsPanel" class="search-results-panel w-100 mt-1 p-0 d-none">
                                <div class="list-group list-group-flush" id="modalSearchResultsList">
                                    <!-- Dynamic items load here -->
                                </div>
                            </div>
                        </div>

                        <!-- Active Cart Items Grid -->
                        <div class="table-responsive mb-2 pos-cart-table-wrapper" style="max-height: 320px;">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end" style="width: 90px;">Rate (Rs.)</th>
                                        <th class="text-center" style="width: 120px;">Qty</th>
                                        <th class="text-end" style="width: 75px;">Disc.</th>
                                        <th class="text-end" style="width: 95px;">Total</th>
                                        <th style="width: 35px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="cartTableBody">
                                    <tr class="cart-empty-row">
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-cart-shopping display-6 mb-2 text-danger"></i>
                                            <p class="mb-0">Cart is empty. Scan items or select from catalog.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Right Side: Calculations & Payment Method & Complete (col-lg-5) -->
                    <div class="col-lg-5 d-flex flex-column justify-content-between">
                        <div class="bg-light p-3 rounded border flex-grow-1">
                            <h6 class="font-weight-bold text-slate mb-3 border-bottom pb-2"><i class="fas fa-calculator me-2 text-danger"></i>Settlement Calculations</h6>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary mb-1">Payment Method</label>
                                <div class="d-flex flex-wrap gap-1">
                                    <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="cash" checked autocomplete="off">
                                    <label class="btn btn-sm btn-outline-secondary py-2 px-2 flex-grow-1" for="pay_cash"><i class="fas fa-money-bill-wave me-1"></i> Cash</label>

                                    <input type="radio" class="btn-check" name="payment_method" id="pay_card" value="card" autocomplete="off">
                                    <label class="btn btn-sm btn-outline-secondary py-2 px-2 flex-grow-1" for="pay_card"><i class="fas fa-credit-card me-1"></i> Card</label>

                                    <input type="radio" class="btn-check" name="payment_method" id="pay_bank" value="bank" autocomplete="off">
                                    <label class="btn btn-sm btn-outline-secondary py-2 px-2 flex-grow-1" for="pay_bank"><i class="fas fa-building-columns me-1"></i> Bank</label>

                                    <input type="radio" class="btn-check" name="payment_method" id="pay_khata" value="khata" autocomplete="off">
                                    <label class="btn btn-sm btn-outline-danger py-2 px-2 flex-grow-1 fw-bold" for="pay_khata"><i class="fas fa-book-bookmark me-1"></i> Khata / Udhar</label>
                                </div>
                            </div>

                            <div class="border-top pt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal:</span>
                                    <span class="font-monospace fw-bold" id="cartSubtotalText">Rs. 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Discount:</span>
                                    <span class="font-monospace text-success fw-bold" id="cartTotalDiscountText">Rs. 0.00</span>
                                </div>

                                <div class="mb-3 bg-white p-2 rounded border">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label for="invoiceDiscountInput" class="form-label small fw-bold text-secondary mb-0">Bill Discount Override (Rs.):</label>
                                        <input type="number" class="form-control form-control-sm text-end font-monospace fw-bold" id="invoiceDiscountInput" value="0" min="0" placeholder="0.00" style="max-width: 140px; height: 34px;">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-2 rounded border border-danger-subtle">
                                    <span class="fw-bold text-slate h6 mb-0">Grand Total:</span>
                                    <span class="h4 mb-0 text-danger fw-bold font-monospace" id="cartTotalText">Rs. 0.00</span>
                                </div>
                                
                                <!-- Regular Cash Calculations -->
                                <div id="cashCalculationGroup">
                                    <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-2 rounded border">
                                        <div>
                                            <label for="cashPaidInput" class="form-label fw-bold text-dark mb-0 fs-6 me-2">Cash Tendered:</label>
                                            <span class="badge bg-secondary-subtle text-secondary border small fw-normal">Optional</span>
                                        </div>
                                        <input type="number" class="form-control text-end font-monospace fw-bold" id="cashPaidInput" placeholder="Exact / 0.00" min="0" style="max-width: 240px; height: 48px; font-size: 1.35rem; border: 2px solid #94a3b8;">
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-success-subtle rounded border border-success-subtle">
                                        <span class="fw-bold text-success-emphasis">Change Return Due:</span>
                                        <span class="h4 mb-0 text-success fw-bold font-monospace" id="cashChangeText">Rs. 0.00</span>
                                    </div>
                                </div>

                                <!-- Khata / Udhar Customer & Settlement Box -->
                                <div id="khataCalculationGroup" class="d-none">
                                    <div class="bg-white p-3 rounded border border-danger shadow-sm mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label small fw-bold text-danger mb-0">
                                                <i class="fas fa-user-tag me-1"></i> Customer / Khata Account *
                                            </label>
                                            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 fw-bold" id="btnPosNewKhataCust" title="Register New Khata Customer">
                                                <i class="fas fa-user-plus me-1"></i> + New Khata
                                            </button>
                                        </div>

                                        <select class="form-select form-select-sm fw-semibold mb-2" id="posKhataCustomerSelect">
                                            <option value="">-- Choose Khata Customer --</option>
                                        </select>

                                        <!-- Customer Ledger Info Ribbon -->
                                        <div id="posKhataCustInfoRibbon" class="d-none p-2 mb-2 rounded bg-light border" style="font-size: 0.8rem;">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted"><i class="fas fa-phone small me-1"></i> Contact:</span>
                                                <span class="fw-semibold text-dark" id="khataCustMetaPhone">-</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted"><i class="fas fa-wallet small me-1"></i> Previous Due:</span>
                                                <span class="fw-bold font-monospace text-danger" id="khataCustPrevDue">Rs. 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted"><i class="fas fa-shield-halved small me-1"></i> Credit Limit:</span>
                                                <span class="fw-semibold font-monospace text-secondary" id="khataCustLimitText">No Limit</span>
                                            </div>
                                        </div>

                                        <!-- Down Payment / Cash Paid Now -->
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label for="posKhataDownPayInput" class="form-label small fw-bold text-dark mb-0">Paid Now (Cash Rs.):</label>
                                                <input type="number" class="form-control form-control-sm text-end font-monospace fw-bold" id="posKhataDownPayInput" value="0" min="0" placeholder="0.00" style="max-width: 140px; height: 34px;">
                                            </div>
                                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Any cash received now; balance added to customer ledger.</small>
                                        </div>

                                        <!-- Summary Projection -->
                                        <div class="p-2 rounded bg-danger-subtle border border-danger-subtle" style="font-size: 0.82rem;">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-danger fw-semibold">Net Charged to Khata:</span>
                                                <span class="fw-bold font-monospace text-danger" id="khataNetBillCharge">Rs. 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between border-top border-danger-subtle pt-1 mt-1">
                                                <span class="text-dark fw-bold">New Ledger Balance:</span>
                                                <span class="fw-bold font-monospace text-dark h6 mb-0" id="khataProjectedTotalDue">Rs. 0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-2 pt-2 border-top">
                            <div class="row g-2">
                                <div class="col-4">
                                    <button type="button" class="btn btn-secondary w-100 py-2 py-md-2.5 font-weight-bold" data-bs-dismiss="modal">Close</button>
                                </div>
                                <div class="col-8">
                                    <button type="button" class="btn btn-danger btn-lg w-100 py-2 py-md-2.5 shadow font-weight-bold fs-6" id="checkoutBtn"><i class="fas fa-cart-shopping me-2"></i> COMPLETE SALE</button>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-1 justify-content-center text-muted small mt-1" style="font-size: 0.72rem;">
                                <span class="badge bg-white text-secondary border"><kbd class="bg-dark text-white">F4</kbd> Cash</span>
                                <span class="badge bg-white text-secondary border"><kbd class="bg-dark text-white">Enter</kbd> Complete</span>
                                <span class="badge bg-white text-secondary border"><kbd class="bg-dark text-white">Ctrl+Enter</kbd> Quick</span>
                                <span class="badge bg-white text-secondary border"><kbd class="bg-dark text-white">F2</kbd> Cart</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Thermal Print Preview Modal (Suppressed for direct cashier workflow) -->
<div class="modal fade d-none" id="receiptPrintModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="receiptPrintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content">
            <div class="modal-header d-print-none">
                <h5 class="modal-title" id="receiptPrintModalLabel"><i class="fas fa-print me-2"></i>Invoice Created</h5>
            </div>
            <div class="modal-body overflow-y-auto">
                <div id="receiptTemplateContainer" class="receipt-print mx-auto">
                    <!-- Loaded dynamically at runtime -->
                </div>
            </div>
            <div class="modal-footer d-print-none">
                <button type="button" class="btn btn-secondary" id="closePrintModalBtn">Close & Clear Cart</button>
                <button type="button" class="btn btn-primary" id="reprintThermalInvoiceBtn"><i class="fas fa-print"></i> Print Invoice</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Register Khata Customer Modal from POS screen -->
<div class="modal fade d-print-none" id="modalQuickKhataCustomer" tabindex="-1" aria-labelledby="modalQuickKhataCustomerLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="modalQuickKhataCustomerLabel">
                    <i class="fas fa-user-plus text-danger me-2"></i>New Khata Account (POS)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="posQuickKhataCustomerForm">
                <div class="modal-body p-4">
                    <div id="quickKhataAlert" class="alert alert-danger d-none"></div>

                    <div class="mb-3">
                        <label for="quick_cust_name" class="form-label small fw-bold text-dark">Customer / Shop Name *</label>
                        <input type="text" class="form-control" id="quick_cust_name" required placeholder="e.g. Bilal Store / Asif Bhai / Haji Sb">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="quick_cust_phone" class="form-label small fw-bold text-dark">Phone Number</label>
                            <input type="text" class="form-control" id="quick_cust_phone" placeholder="03XX-XXXXXXX">
                        </div>
                        <div class="col-6">
                            <label for="quick_cust_type" class="form-label small fw-bold text-dark">Customer Type</label>
                            <select class="form-select" id="quick_cust_type">
                                <option value="store">Store / Dukandar</option>
                                <option value="neighbor">Neighbor / Hamsaya</option>
                                <option value="friend">Friend / Dost</option>
                                <option value="general" selected>General Khata</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="quick_cust_limit" class="form-label small fw-bold text-dark">Credit Limit (Rs.)</label>
                            <input type="number" class="form-control font-monospace" id="quick_cust_limit" min="0" step="100" placeholder="0 = Unlimited" value="0">
                        </div>
                        <div class="col-6">
                            <label for="quick_cust_address" class="form-label small fw-bold text-dark">Address / Shop No</label>
                            <input type="text" class="form-control" id="quick_cust_address" placeholder="e.g. Shop # 4, Main Bazar">
                        </div>
                    </div>

                    <div class="mb-1">
                        <label for="quick_cust_notes" class="form-label small fw-bold text-dark">Note / Remarks</label>
                        <input type="text" class="form-control" id="quick_cust_notes" placeholder="Optional reference notes...">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4" id="btnSaveQuickKhataCust">
                        <i class="fas fa-check-circle me-1"></i> Save & Select Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Quick Expense Modal on POS screen -->
<div class="modal fade d-print-none" id="posQuickExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-wallet text-danger me-2"></i>Quick Counter Expense
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="posQuickExpenseForm">
                <div class="modal-body p-4">
                    <div id="posExpAlert" class="alert alert-danger d-none"></div>

                    <div class="mb-3">
                        <label for="pos_exp_cat" class="form-label fs-6 fw-bold text-dark">Category *</label>
                        <select class="form-select form-select-lg fw-semibold" id="pos_exp_cat" required>
                            <option value="" disabled selected>Select category...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="pos_exp_amount" class="form-label fs-6 fw-bold text-dark">Amount (Rs.) *</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text font-monospace fs-4 bg-white fw-bold">Rs.</span>
                            <input type="number" class="form-control font-monospace fw-bold text-end fs-3" id="pos_exp_amount" required min="0.5" step="0.5" placeholder="0.00">
                        </div>
                        <small class="text-muted mt-1 d-block"><i class="fas fa-cash-register text-warning me-1"></i> Deducted automatically from counter cash drawer.</small>
                    </div>

                    <div class="mb-2">
                        <label for="pos_exp_desc" class="form-label fs-6 fw-bold text-dark">Note / Details</label>
                        <input type="text" class="form-control" id="pos_exp_desc" placeholder="e.g. Staff lunch, morning chai, shoppers">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 px-4">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4" id="btnSavePosExpense">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= asset('/assets/js/pos.js') ?>?v=<?php echo time(); ?>"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
