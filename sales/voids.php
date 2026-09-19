<?php
require_once __DIR__ . '/../config/auth.php';
if (!isAdmin()) {
    redirect('/pos/index.php');
}
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 d-print-none">
    <div>
        <h4 class="mb-1 text-dark fw-bold"><i class="fas fa-ban me-2 text-danger"></i>Deleted / Voided Bills</h4>
        <p class="text-secondary mb-0 small">Audit log of all cancelled transactions with full stock reversal history</p>
    </div>
    <button type="button" class="btn btn-danger px-3 py-2 shadow-sm font-weight-bold" data-bs-toggle="modal" data-bs-target="#processVoidModal">
        <i class="fas fa-trash-can me-1"></i> Cancel / Void a Bill
    </button>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4 d-print-none">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white p-3 h-100 border-start border-danger border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase">Total Voided Bills</span>
                    <h3 class="mb-0 fw-bold text-dark font-monospace mt-1" id="totalVoidCount">0</h3>
                </div>
                <div class="bg-danger-subtle p-3 rounded-circle text-danger">
                    <i class="fas fa-file-circle-xmark fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white p-3 h-100 border-start border-warning border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase">Total Voided Value</span>
                    <h3 class="mb-0 fw-bold text-danger font-monospace mt-1" id="totalVoidValue">Rs. 0.00</h3>
                </div>
                <div class="bg-warning-subtle p-3 rounded-circle text-warning-emphasis">
                    <i class="fas fa-hand-holding-dollar fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white p-3 h-100 border-start border-info border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-bold text-uppercase">Voided Today</span>
                    <h3 class="mb-0 fw-bold text-primary font-monospace mt-1" id="todayVoidCount">0</h3>
                </div>
                <div class="bg-info-subtle p-3 rounded-circle text-info">
                    <i class="fas fa-calendar-day fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="card mb-4 border-0 shadow-sm d-print-none">
    <div class="card-body py-3">
        <form id="voidFilterForm" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="voidSearchInput" class="form-label small fw-bold text-secondary mb-1">Search Bill #</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="voidSearchInput" placeholder="e.g. MELA-20260901-0001">
                </div>
            </div>
            <div class="col-md-3">
                <label for="voidDateFilter" class="form-label small fw-bold text-secondary mb-1">Time Period</label>
                <select class="form-select form-select-sm" id="voidDateFilter">
                    <option value="all" selected>All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="custom">Custom Date Range</option>
                </select>
            </div>
            <div class="col-md-5 d-none" id="voidCustomDateRangeGroup">
                <div class="row g-2">
                    <div class="col-6">
                        <label for="voidStartDate" class="form-label small fw-bold text-secondary mb-1">Start Date</label>
                        <input type="date" class="form-control form-control-sm" id="voidStartDate">
                    </div>
                    <div class="col-6">
                        <label for="voidEndDate" class="form-label small fw-bold text-secondary mb-1">End Date</label>
                        <input type="date" class="form-control form-control-sm" id="voidEndDate">
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Voided Bills Table -->
<div class="card border-0 shadow-sm d-print-none">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-list-ul me-2 text-danger"></i>Cancelled / Voided Bills Record</h6>
        <span class="badge bg-secondary-subtle text-secondary" id="voidCountBadge">0 records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr>
                        <th>Bill #</th>
                        <th>Original Sale Date</th>
                        <th>Voided At</th>
                        <th>Cashier</th>
                        <th>Voided By</th>
                        <th class="text-end">Cancelled Amount</th>
                        <th>Reason for Void</th>
                        <th class="text-end" style="width: 130px;">Action</th>
                    </tr>
                </thead>
                <tbody id="voidsTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-spinner fa-spin me-2"></i>Loading cancelled bills log...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Process Bill Void / Cancel Modal -->
<div class="modal fade" id="processVoidModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="processVoidModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title font-weight-bold" id="processVoidModalLabel">
                    <i class="fas fa-triangle-exclamation me-2"></i>Cancel & Void Customer Bill
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Search Original Bill -->
                <div class="mb-4 position-relative">
                    <label for="voidBillLookupInput" class="form-label small fw-bold text-secondary mb-1">Enter Bill Number to Cancel</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                        <input type="text" class="form-control" id="voidBillLookupInput" placeholder="Type bill # or cashier name..." autocomplete="off">
                        <button type="button" class="btn btn-outline-danger px-4" id="btnLookupVoidBill">
                            <i class="fas fa-search me-1"></i> Lookup Bill
                        </button>
                    </div>
                    <!-- Live Autocomplete Suggestions Panel -->
                    <div id="voidBillSearchPanel" class="search-results-panel w-100 mt-1 p-0 d-none" style="position: absolute; z-index: 2050;">
                        <div class="list-group list-group-flush" id="voidBillSearchList"></div>
                    </div>
                </div>

                <div id="voidLookupAlert" class="alert alert-danger d-none py-2 px-3 small"></div>

                <!-- Bill Details Preview -->
                <div id="voidBillPreviewSection" class="d-none">
                    <input type="hidden" id="targetVoidSaleId">
                    
                    <div class="bg-light p-3 rounded border mb-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-secondary small">Bill Number:</span><br>
                                <strong class="font-monospace text-dark fs-6" id="previewBillNumber">--</strong>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-secondary small">Bill Date:</span><br>
                                <strong class="text-dark" id="previewBillDate">--</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-secondary small">Cashier:</span><br>
                                <span class="text-dark fw-bold" id="previewCashier">--</span>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-secondary small">Grand Total:</span><br>
                                <span class="text-danger fw-bold fs-5 font-monospace" id="previewGrandTotal">Rs. 0.00</span>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold small text-secondary text-uppercase mb-2">Items to be Returned to Inventory:</h6>
                    <div class="table-responsive mb-3" style="max-height: 180px;">
                        <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center" style="width: 80px;">Qty</th>
                                    <th class="text-end" style="width: 100px;">Rate</th>
                                    <th class="text-end" style="width: 100px;">Total</th>
                                </tr>
                            </thead>
                            <tbody id="voidItemsTableBody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Void Reason Selection -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="voidReasonSelect" class="form-label small fw-bold text-dark mb-0">Reason for Cancellation</label>
                            <span class="badge bg-secondary-subtle text-secondary border small fw-normal">Optional</span>
                        </div>
                        <select class="form-select form-select-sm mb-2" id="voidReasonSelect">
                            <option value="">-- Select standard cancellation reason (Optional) --</option>
                            <option value="Customer walked away / declined payment">Customer walked away / declined payment</option>
                            <option value="Wrong items scanned / entered by cashier">Wrong items scanned / entered by cashier</option>
                            <option value="Duplicate bill generated in error">Duplicate bill generated in error</option>
                            <option value="Customer returned immediately before leaving counter">Customer returned immediately before leaving counter</option>
                            <option value="other">Other Reason (Specify below)</option>
                        </select>
                        <textarea class="form-control form-control-sm" id="voidReasonCustom" rows="2" placeholder="Optional: Describe the reason for voiding or leave blank..."></textarea>
                    </div>

                    <div class="alert alert-warning py-2 px-3 small mb-0 border-warning">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        <strong>Important:</strong> Voiding will permanently cancel this bill, restore all item quantities back to active inventory stock, and log an auditable security record.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger px-4 font-weight-bold d-none" id="btnConfirmVoid">
                    <i class="fas fa-ban me-1"></i> Confirm & Void Bill
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Void Details Modal -->
<div class="modal fade" id="voidDetailsModal" tabindex="-1" aria-labelledby="voidDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title font-weight-bold" id="voidDetailsModalLabel">
                    <i class="fas fa-file-circle-xmark me-2 text-danger"></i>Cancelled Bill Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3 bg-light p-3 rounded border">
                    <div class="col-sm-6">
                        <span class="text-secondary small">Bill Number:</span><br>
                        <strong class="font-monospace text-danger fs-5" id="viewDetailBillNum">--</strong><br>
                        <small class="text-muted" id="viewDetailSaleDate">Sale Date: --</small>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <span class="badge bg-danger fs-6 px-3 py-1 mb-1">VOIDED / CANCELLED</span><br>
                        <small class="text-dark" id="viewDetailVoidedBy">Voided By: --</small><br>
                        <small class="text-muted" id="viewDetailVoidedAt">Voided At: --</small>
                    </div>
                </div>

                <div class="bg-danger-subtle p-3 rounded border border-danger-subtle mb-3">
                    <strong class="text-danger small text-uppercase"><i class="fas fa-comment-dots me-1"></i>Void Reason:</strong>
                    <p class="mb-0 text-dark mt-1" id="viewDetailVoidReason">--</p>
                </div>

                <h6 class="fw-bold small text-secondary text-uppercase mb-2">Cancelled Items Breakdown:</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody id="viewDetailItemsBody">
                            <!-- Dynamically loaded -->
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Subtotal:</span>
                            <span class="font-monospace" id="viewDetailSubtotal">Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Bill Discount:</span>
                            <span class="font-monospace text-success" id="viewDetailDiscount">Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-1">
                            <strong class="text-dark">Cancelled Total:</strong>
                            <strong class="font-monospace text-danger fs-6" id="viewDetailGrandTotal">Rs. 0.00</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let voidedSalesCache = [];

    // Load Voided Sales List
    function loadVoidedSales() {
        const search = $('#voidSearchInput').val().trim();
        const date_filter = $('#voidDateFilter').val();
        const start_date = $('#voidStartDate').val();
        const end_date = $('#voidEndDate').val();
        
        let url = `${(window.BASE_URL || "")}/api/sales.php?action=list&status=voided&date_filter=${date_filter}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (date_filter === 'custom' && start_date && end_date) {
            url += `&start_date=${start_date}&end_date=${end_date}`;
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    voidedSalesCache = response.sales;
                    renderVoidedTable(response.sales);
                    updateKPIs(response.sales);
                } else {
                    $('#voidsTableBody').html(`
                        <tr>
                            <td colspan="8" class="text-center py-4 text-danger">${response.message || 'Failed to load voided sales.'}</td>
                        </tr>
                    `);
                }
            },
            error: function() {
                $('#voidsTableBody').html(`
                    <tr>
                        <td colspan="8" class="text-center py-4 text-danger">Connection error loading voided bills.</td>
                    </tr>
                `);
            }
        });
    }

    // Render Table
    function renderVoidedTable(sales) {
        if (!sales || sales.length === 0) {
            $('#voidsTableBody').html(`
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fas fa-check-circle display-6 mb-2 text-success"></i>
                        <p class="mb-0">No deleted or voided bills found for the selected filter.</p>
                    </td>
                </tr>
            `);
            $('#voidCountBadge').text('0 records');
            return;
        }

        $('#voidCountBadge').text(`${sales.length} records`);
        let html = '';
        sales.forEach(sale => {
            html += `
                <tr>
                    <td>
                        <strong class="font-monospace text-danger">${sale.bill_number}</strong>
                    </td>
                    <td><small class="text-secondary">${sale.created_at}</small></td>
                    <td><small class="text-dark fw-bold">${sale.voided_at || sale.created_at}</small></td>
                    <td><small>${sale.cashier_name || 'Staff'}</small></td>
                    <td><span class="badge bg-dark-subtle text-dark">${sale.voided_by_name || 'Admin'}</span></td>
                    <td class="text-end font-monospace fw-bold text-danger">Rs. ${parseFloat(sale.total).toFixed(2)}</td>
                    <td>
                        <small class="text-dark" title="${sale.void_reason || 'No reason provided'}">
                            ${sale.void_reason ? (sale.void_reason.length > 35 ? sale.void_reason.substring(0, 35) + '...' : sale.void_reason) : '<span class="text-muted">N/A</span>'}
                        </small>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-view-void" data-id="${sale.id}">
                            <i class="fas fa-eye me-1"></i> Details
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#voidsTableBody').html(html);
    }

    // Update KPI metrics
    function updateKPIs(sales) {
        const totalCount = sales.length;
        const totalValue = sales.reduce((sum, s) => sum + parseFloat(s.total || 0), 0);
        
        const todayStr = new Date().toISOString().slice(0, 10);
        const todayCount = sales.filter(s => (s.voided_at || s.created_at).startsWith(todayStr)).length;

        $('#totalVoidCount').text(totalCount);
        $('#totalVoidValue').text(`Rs. ${totalValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        $('#todayVoidCount').text(todayCount);
    }

    // Filter changes
    $('#voidSearchInput').on('keyup', function() {
        loadVoidedSales();
    });

    $('#voidDateFilter').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#voidCustomDateRangeGroup').removeClass('d-none');
        } else {
            $('#voidCustomDateRangeGroup').addClass('d-none');
            loadVoidedSales();
        }
    });

    $('#voidStartDate, #voidEndDate').on('change', function() {
        if ($('#voidStartDate').val() && $('#voidEndDate').val()) {
            loadVoidedSales();
        }
    });

    // Live Autocomplete Search for Voidable Bills
    let voidSearchTimer = null;
    $('#voidBillLookupInput').on('input', function() {
        const term = $(this).val().trim();
        clearTimeout(voidSearchTimer);
        
        if (term.length === 0) {
            $('#voidBillSearchPanel').addClass('d-none');
            return;
        }
        
        voidSearchTimer = setTimeout(function() {
            $.ajax({
                url: `${(window.BASE_URL || "")}/api/sales.php?action=search_bills&mode=void&term=${encodeURIComponent(term)}`,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.bills && res.bills.length > 0) {
                        let html = '';
                        res.bills.forEach(function(b) {
                            html += `
                                <a href="javascript:void(0)" class="list-group-item list-group-item-action py-2 px-3 select-void-bill-item" data-id="${b.id}" data-bill="${b.bill_number}">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-monospace fw-bold text-danger">${b.bill_number}</span>
                                        <span class="badge bg-danger-subtle text-danger font-monospace">Rs. ${parseFloat(b.total).toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span><i class="fas fa-calendar-day me-1"></i>${b.created_at}</span>
                                        <span><i class="fas fa-user me-1"></i>${b.cashier_name}</span>
                                    </div>
                                </a>
                            `;
                        });
                        $('#voidBillSearchList').html(html);
                        $('#voidBillSearchPanel').removeClass('d-none');
                    } else {
                        $('#voidBillSearchList').html(`
                            <div class="list-group-item py-2 text-center text-muted small">
                                <i class="fas fa-search me-1"></i> No matching voidable bills found
                            </div>
                        `);
                        $('#voidBillSearchPanel').removeClass('d-none');
                    }
                }
            });
        }, 120);
    });

    // Handle suggestion click
    $(document).on('click', '.select-void-bill-item', function(e) {
        e.preventDefault();
        const saleId = $(this).data('id');
        const billNum = $(this).data('bill');
        $('#voidBillLookupInput').val(billNum);
        $('#voidBillSearchPanel').addClass('d-none');
        $('#voidLookupAlert').addClass('d-none');
        fetchBillDetailsForVoid(saleId);
    });

    // Close panel on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.position-relative').length) {
            $('#voidBillSearchPanel').addClass('d-none');
        }
    });

    // Enter key triggers search
    $('#voidBillLookupInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#voidBillSearchPanel').addClass('d-none');
            $('#btnLookupVoidBill').trigger('click');
        }
    });

    // Lookup Bill for Voiding button
    $('#btnLookupVoidBill').on('click', function() {
        const billNo = $('#voidBillLookupInput').val().trim();
        $('#voidBillSearchPanel').addClass('d-none');
        if (!billNo) {
            $('#voidLookupAlert').text('Please enter a valid bill number.').removeClass('d-none');
            return;
        }

        $('#voidLookupAlert').addClass('d-none');
        $('#btnLookupVoidBill').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Searching...');

        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&bill_number=${encodeURIComponent(billNo)}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                $('#btnLookupVoidBill').prop('disabled', false).html('<i class="fas fa-search me-1"></i> Lookup Bill');
                if (res.success && res.sale) {
                    const exactMatch = res.sale;
                    
                    if (exactMatch.status === 'voided') {
                        $('#voidLookupAlert').text(`Bill '${exactMatch.bill_number}' is ALREADY voided/cancelled.`).removeClass('d-none');
                        $('#voidBillPreviewSection').addClass('d-none');
                        $('#btnConfirmVoid').addClass('d-none');
                        return;
                    }

                    if (exactMatch.status === 'fully_refunded') {
                        $('#voidLookupAlert').text(`Bill '${exactMatch.bill_number}' has already been fully refunded and cannot be voided.`).removeClass('d-none');
                        $('#voidBillPreviewSection').addClass('d-none');
                        $('#btnConfirmVoid').addClass('d-none');
                        return;
                    }

                    // Load item details
                    fetchBillDetailsForVoid(exactMatch.id);
                } else {
                    $('#voidLookupAlert').text(`No invoice found matching '${billNo}'.`).removeClass('d-none');
                    $('#voidBillPreviewSection').addClass('d-none');
                    $('#btnConfirmVoid').addClass('d-none');
                }
            },
            error: function() {
                $('#btnLookupVoidBill').prop('disabled', false).html('<i class="fas fa-search me-1"></i> Lookup Bill');
                $('#voidLookupAlert').text(`No invoice found matching '${billNo}'.`).removeClass('d-none');
            }
        });
    });

    function fetchBillDetailsForVoid(saleId) {
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&id=${saleId}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    const sale = res.sale;
                    const items = res.items;

                    $('#targetVoidSaleId').val(sale.id);
                    $('#previewBillNumber').text(sale.bill_number);
                    $('#previewBillDate').text(sale.created_at);
                    $('#previewCashier').text(sale.cashier_name || 'Staff');
                    $('#previewGrandTotal').text(`Rs. ${parseFloat(sale.total).toFixed(2)}`);

                    let itemsHtml = '';
                    items.forEach(item => {
                        itemsHtml += `
                            <tr>
                                <td><strong>${item.product_name}</strong><br><small class="text-muted font-monospace">${item.barcode}</small></td>
                                <td class="text-center font-monospace fw-bold">${item.quantity}</td>
                                <td class="text-end font-monospace">Rs. ${parseFloat(item.unit_price).toFixed(2)}</td>
                                <td class="text-end font-monospace fw-bold">Rs. ${parseFloat(item.net_amount).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    $('#voidItemsTableBody').html(itemsHtml);

                    $('#voidBillPreviewSection').removeClass('d-none');
                    $('#btnConfirmVoid').removeClass('d-none');
                }
            }
        });
    }

    // Reason dropdown helper
    $('#voidReasonSelect').on('change', function() {
        const val = $(this).val();
        if (val && val !== 'other') {
            $('#voidReasonCustom').val(val);
        } else if (val === 'other') {
            $('#voidReasonCustom').val('').focus();
        }
    });

    // Confirm Void Action
    $('#btnConfirmVoid').on('click', function() {
        const saleId = $('#targetVoidSaleId').val();
        const reason = $('#voidReasonCustom').val().trim() || 'No reason provided';

        if (!saleId) {
            showToast('Please lookup a valid bill first.', 'warning');
            return;
        }

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing Void...');

        $.ajax({
            url: (window.BASE_URL || '') + '/api/sales.php?action=void',
            type: 'POST',
            data: { id: saleId, reason: reason },
            dataType: 'json',
            success: function(res) {
                $('#btnConfirmVoid').prop('disabled', false).html('<i class="fas fa-ban me-1"></i> Confirm & Void Bill');
                if (res.success) {
                    showToast('Bill voided and inventory stock restored successfully!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('processVoidModal')).hide();
                    
                    // Reset modal
                    $('#voidBillLookupInput').val('');
                    $('#voidReasonSelect').val('');
                    $('#voidReasonCustom').val('');
                    $('#voidBillPreviewSection').addClass('d-none');
                    $('#btnConfirmVoid').addClass('d-none');
                    
                    loadVoidedSales();
                } else {
                    showToast(res.message || 'Failed to void bill.', 'danger');
                }
            },
            error: function(xhr) {
                $('#btnConfirmVoid').prop('disabled', false).html('<i class="fas fa-ban me-1"></i> Confirm & Void Bill');
                let msg = 'Connection error while voiding bill.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showToast(msg, 'danger');
            }
        });
    });

    // View Void Details Modal
    $(document).on('click', '.btn-view-void', function() {
        const saleId = $(this).data('id');
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&id=${saleId}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    const s = res.sale;
                    const items = res.items;

                    $('#viewDetailBillNum').text(s.bill_number);
                    $('#viewDetailSaleDate').text(`Sale Date: ${s.created_at}`);
                    $('#viewDetailVoidedBy').text(`Voided By: ${s.voided_by_name || 'Admin'}`);
                    $('#viewDetailVoidedAt').text(`Voided At: ${s.voided_at || s.created_at}`);
                    $('#viewDetailVoidReason').text(s.void_reason || 'No specific reason entered.');

                    let itemsHtml = '';
                    items.forEach(item => {
                        itemsHtml += `
                            <tr>
                                <td><strong>${item.product_name}</strong></td>
                                <td class="text-center font-monospace">${item.quantity}</td>
                                <td class="text-end font-monospace">Rs. ${parseFloat(item.unit_price).toFixed(2)}</td>
                                <td class="text-end font-monospace text-success">${parseFloat(item.discount) > 0 ? '-Rs. ' + parseFloat(item.discount).toFixed(2) : '-'}</td>
                                <td class="text-end font-monospace fw-bold">Rs. ${parseFloat(item.net_amount).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    $('#viewDetailItemsBody').html(itemsHtml);

                    $('#viewDetailSubtotal').text(`Rs. ${parseFloat(s.subtotal).toFixed(2)}`);
                    $('#viewDetailDiscount').text(`-Rs. ${parseFloat(s.discount).toFixed(2)}`);
                    $('#viewDetailGrandTotal').text(`Rs. ${parseFloat(s.total).toFixed(2)}`);

                    const modal = new bootstrap.Modal(document.getElementById('voidDetailsModal'));
                    modal.show();
                }
            }
        });
    });

    // Initialize
    loadVoidedSales();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
