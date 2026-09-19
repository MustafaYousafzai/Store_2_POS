<?php
require_once __DIR__ . '/../includes/header.php';
requirePermission('VIEW_SALES');
?>

<div class="card mb-4 d-print-none">
    <div class="card-body">
        <form id="salesFilterForm" class="row g-3">
            <div class="col-md-3">
                <label for="historySearchInput" class="form-label">Bill Number</label>
                <input type="text" class="form-control" id="historySearchInput" placeholder="Search bill # (e.g. MELA-...)">
            </div>
            <div class="col-md-3">
                <label for="dateFilter" class="form-label">Date Filter</label>
                <select class="form-select" id="dateFilter">
                    <option value="today" selected>Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="last_2_months">Last 2 Months</option>
                    <option value="all">Lifetime (All Time)</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            <div class="col-md-4 d-none" id="customDateRangeGroup">
                <div class="row">
                    <div class="col-6">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-6">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                </div>
            </div>
            <?php if(isAdmin()): ?>
            <div class="col-md-2" id="cashierFilterGroup">
                <label for="cashierFilter" class="form-label">Cashier</label>
                <select class="form-select" id="cashierFilter">
                    <option value="0">All Cashiers</option>
                </select>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card d-print-none">
    <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-receipt me-2 text-danger"></i>Sales Invoices Log</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 35px;"></th>
                        <th>Bill Number</th>
                        <th>Date & Time</th>
                        <th>Cashier</th>
                        <th style="min-width: 300px;">Items Purchased (Product, Qty, Amount)</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-5">Loading sales...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Invoice Details Modal -->
<div class="modal fade d-print-none" id="invoiceDetailsModal" tabindex="-1" aria-labelledby="invoiceDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceDetailsModalLabel"><i class="fas fa-file-invoice-dollar me-2"></i>Invoice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <small class="text-muted uppercase">Bill Number</small>
                        <h4 class="mb-0 text-danger" id="detailBillNumber">MELA-XXXX</h4>
                        <small class="text-muted" id="detailDateTime">Date: --/--/----</small>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <small class="text-muted uppercase">Invoice Status</small>
                        <div id="detailStatusBadge">--</div>
                        <small class="text-muted" id="detailCashierName">Cashier: --</small>
                    </div>
                </div>
                
                <h6 class="border-bottom pb-2 mb-2 font-weight-bold">Products List</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Barcode</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Discount Allocated</th>
                                <th class="text-end">Net Total</th>
                                <th class="text-center">Refunded Qty</th>
                            </tr>
                        </thead>
                        <tbody id="detailItemsTableBody">
                            <!-- Loaded dynamically -->
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6 bg-light p-3 rounded" id="voidReasonBox" style="display: none;">
                        <strong>Void Information:</strong><br>
                        <span class="text-muted">Voided by:</span> <span id="voidedByName">--</span><br>
                        <span class="text-muted">Reason:</span> <span id="voidReasonText">--</span>
                    </div>
                    <div class="col-md-6 ms-auto">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td>Subtotal:</td>
                                <td class="text-end font-monospace" id="detailSubtotal">Rs. 0.00</td>
                            </tr>
                            <tr>
                                <td>Discount:</td>
                                <td class="text-end font-monospace" id="detailDiscount">-Rs. 0.00</td>
                            </tr>
                            <tr class="fw-bold border-top">
                                <td>Grand Total:</td>
                                <td class="text-end font-monospace text-danger" id="detailGrandTotal">Rs. 0.00</td>
                            </tr>
                            <tr>
                                <td>Refunded:</td>
                                <td class="text-end font-monospace text-warning" id="detailRefunded">Rs. 0.00</td>
                            </tr>
                            <tr class="table-light">
                                <td id="detailPaymentMethod">Paid via Cash:</td>
                                <td class="text-end font-monospace" id="detailPaid">Rs. 0.00</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="reprintInvoiceBtn"><i class="fas fa-print"></i> Reprint Receipt</button>
            </div>
        </div>
    </div>
</div>

<!-- Void Invoice Modal -->
<div class="modal fade d-print-none" id="voidInvoiceModal" tabindex="-1" aria-labelledby="voidInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="voidInvoiceModalLabel">
                    <i class="fas fa-triangle-exclamation me-2"></i>Void Sales Invoice <span id="voidBillNumberBadge" class="badge bg-white text-danger ms-2 font-monospace"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="voidInvoiceForm">
                <input type="hidden" id="void_sale_id" name="id">
                <div class="modal-body">
                    <div class="alert alert-warning border-warning d-flex align-items-start gap-2">
                        <i class="fas fa-triangle-exclamation text-warning-emphasis fs-4 mt-1"></i>
                        <div>
                            <strong>Permanent Action Warning:</strong>
                            <ul class="mb-0 mt-1 ps-3 small">
                                <li>Restores all item quantities back to active inventory stock.</li>
                                <li>Reverses sales revenue, cash drawer, and P&L ledger records.</li>
                                <li>This transaction cannot be undone.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="void_reason" class="form-label font-weight-bold mb-0">Reason for Voiding</label>
                            <span class="badge bg-secondary-subtle text-secondary border small fw-normal">Optional</span>
                        </div>
                        <input type="text" class="form-control" id="void_reason" name="reason" placeholder="e.g. Cashier error, duplicate billing (Optional - leave blank if not needed)">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold" id="btnConfirmVoidAction">
                        <i class="fas fa-trash-can me-1"></i> Yes, Void This Bill
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Thermal Print Preview Modal -->
<div class="modal fade" id="receiptPrintModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="receiptPrintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content">
            <div class="modal-header d-print-none">
                <h5 class="modal-title" id="receiptPrintModalLabel"><i class="fas fa-print me-2"></i>Invoice Printing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body overflow-y-auto">
                <div id="receiptTemplateContainer" class="receipt-print mx-auto">
                    <!-- Loaded dynamically at runtime -->
                </div>
            </div>
            <div class="modal-footer d-print-none">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="historyReprintBtn"><i class="fas fa-print"></i> Print Invoice</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const isAdminUser = <?php echo isAdmin() ? 'true' : 'false'; ?>;
    
    // Load cashiers list for filters if permitted
    if ($('#cashierFilter').length > 0) {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/reports.php?action=list_cashiers',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    response.cashiers.forEach(function(u) {
                        $('#cashierFilter').append(`<option value="${u.id}">${u.username}</option>`);
                    });
                }
            }
        });
    }

    loadSales();
    
    // Filters listeners
    $('#historySearchInput').on('keyup', loadSales);
    $('#dateFilter, #cashierFilter').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customDateRangeGroup').removeClass('d-none');
        } else {
            $('#customDateRangeGroup').addClass('d-none');
            loadSales();
        }
    });
    $('#startDate, #endDate').on('change', loadSales);
    
    // View invoice click
    $(document).on('click', '.btn-view-invoice', function() {
        const id = $(this).data('id');
        $('#detailItemsTableBody').html('<tr><td colspan="7" class="text-center">Loading details...</td></tr>');
        $('#reprintInvoiceBtn').data('id', id);
        
        const detailsModal = new bootstrap.Modal(document.getElementById('invoiceDetailsModal'));
        detailsModal.show();
        
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&id=${id}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const sale = response.sale;
                    $('#detailBillNumber').text(sale.bill_number);
                    $('#detailDateTime').text(`Date: ${sale.created_at}`);
                    $('#detailCashierName').text(`Cashier: ${sale.cashier_name}`);
                    
                    let statusBadge = '';
                    if (sale.status === 'completed') {
                        statusBadge = '<span class="badge bg-success">Completed</span>';
                    } else if (sale.status === 'voided') {
                        statusBadge = '<span class="badge bg-danger">Voided</span>';
                    } else if (sale.status === 'fully_refunded') {
                        statusBadge = '<span class="badge bg-warning text-dark">Fully Refunded</span>';
                    } else {
                        statusBadge = '<span class="badge bg-info text-dark">Partially Refunded</span>';
                    }
                    $('#detailStatusBadge').html(statusBadge);
                    
                    if (sale.status === 'voided') {
                        $('#voidedByName').text(sale.voided_by_name || 'System');
                        $('#voidReasonText').text(sale.void_reason || 'N/A');
                        $('#voidReasonBox').show();
                    } else {
                        $('#voidReasonBox').hide();
                    }
                    
                    $('#detailSubtotal').text(`Rs. ${parseFloat(sale.subtotal).toFixed(2)}`);
                    $('#detailDiscount').text(`-Rs. ${parseFloat(sale.discount).toFixed(2)}`);
                    $('#detailGrandTotal').text(`Rs. ${parseFloat(sale.total).toFixed(2)}`);
                    $('#detailRefunded').text(`Rs. ${parseFloat(sale.total_refunded_amount).toFixed(2)}`);
                    
                    const pay = response.payments[0] || { payment_method: 'Cash', amount: sale.total };
                    $('#detailPaymentMethod').text(`Paid via ${pay.payment_method.toUpperCase()}:`);
                    $('#detailPaid').text(`Rs. ${parseFloat(sale.paid).toFixed(2)}`);
                    
                    // Render items
                    let itemsHtml = '';
                    response.items.forEach(function(item) {
                        itemsHtml += `
                            <tr>
                                <td><strong>${item.product_name}</strong></td>
                                <td><code>${item.barcode}</code></td>
                                <td class="text-end font-monospace">Rs. ${parseFloat(item.unit_price).toFixed(2)}</td>
                                <td class="text-center font-monospace">${item.quantity}</td>
                                <td class="text-end font-monospace text-muted">-Rs. ${parseFloat(item.discount).toFixed(2)}</td>
                                <td class="text-end font-monospace fw-bold">Rs. ${parseFloat(item.net_amount).toFixed(2)}</td>
                                <td class="text-center font-monospace text-warning">${item.quantity_refunded}</td>
                            </tr>
                        `;
                    });
                    $('#detailItemsTableBody').html(itemsHtml);
                } else {
                    $('#detailItemsTableBody').html('<tr><td colspan="7" class="text-center text-danger">Failed to load invoice items.</td></tr>');
                }
            }
        });
    });
    
    // Open void modal click
    $(document).on('click', '.btn-void-invoice', function() {
        const id = $(this).data('id');
        const bill = $(this).data('bill') || '';
        $('#void_sale_id').val(id);
        $('#voidBillNumberBadge').text(bill ? bill : '');
        $('#void_reason').val('');
        $('#btnConfirmVoidAction').prop('disabled', false).html('<i class="fas fa-trash-can me-1"></i> Yes, Void This Bill');
        const voidModal = new bootstrap.Modal(document.getElementById('voidInvoiceModal'));
        voidModal.show();
    });
    
    // Submit void request without browser native confirm or alert
    $('#voidInvoiceForm').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#btnConfirmVoidAction');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Voiding Bill...');
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/sales.php?action=void',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $btn.prop('disabled', false).html('<i class="fas fa-trash-can me-1"></i> Yes, Void This Bill');
                if (response.success) {
                    const modalEl = document.getElementById('voidInvoiceModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();
                    
                    showToast('Bill successfully voided and inventory stock restored.', 'success');
                    loadSales();
                } else {
                    showToast(response.message || 'Failed to void invoice.', 'danger');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-trash-can me-1"></i> Yes, Void This Bill');
                showToast(xhr.responseJSON?.message || 'Failed to void invoice.', 'danger');
            }
        });
    });
    
    // Reprint click
    $('#reprintInvoiceBtn').on('click', function() {
        const id = $(this).data('id');
        bootstrap.Modal.getInstance(document.getElementById('invoiceDetailsModal')).hide();
        
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&id=${id}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    renderPrintTemplate(response.sale, response.items, response.payments[0]);
                    const printModal = new bootstrap.Modal(document.getElementById('receiptPrintModal'));
                    printModal.show();
                }
            }
        });
    });
    
    function renderPrintTemplate(sale, items, payment) {
        let itemRows = '';
        const totalQty = items.reduce((sum, item) => sum + parseInt(item.quantity), 0);
        
        items.forEach((item, idx) => {
            const itemNet = parseFloat(item.total_price);
            itemRows += `
                <tr>
                    <td colspan="4" style="padding-top: 4px; font-weight: 900; font-size: 11.5px; color: #000;">
                        ${idx + 1}. ${item.product_name}
                    </td>
                </tr>
                <tr style="border-bottom: 1px dashed #000;">
                    <td style="padding-bottom: 3px; font-size: 9.5px; font-weight: 800; color: #000;">${item.barcode}</td>
                    <td align="center" style="padding-bottom: 3px; font-weight: 900; font-size: 11px; color: #000;">${item.quantity}</td>
                    <td class="right" style="padding-bottom: 3px; font-weight: 800; font-size: 10.5px; color: #000;">${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td class="right" style="padding-bottom: 3px; font-weight: 900; font-size: 11px; color: #000;">${itemNet.toFixed(2)}</td>
                </tr>
                ${item.discount > 0 ? `
                <tr>
                    <td colspan="4" class="right" style="font-size: 10px; font-weight: 800; color: #000; padding-bottom: 2px;">
                        Disc: -Rs. ${parseFloat(item.discount).toFixed(2)}
                    </td>
                </tr>` : ''}
            `;
        });
        
        const payMethod = payment ? payment.payment_method : 'Cash';
        const changeDue = payMethod === 'cash' ? Math.max(0, parseFloat(sale.paid) - parseFloat(sale.total)) : 0.00;
        
        const template = `
            <div class="center" style="margin-bottom: 6px; color: #000;">
                <img src="${(window.BASE_URL || "")}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="${window.STORE_NAME || 'One Dollar Shop'}" class="receipt-logo">
                <h3 style="margin: 0; font-size: 16.5px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase; color: #000;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h3>
                <p style="margin: 2px 0; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #000;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'}</p>
                <div style="font-size: 12px; font-weight: 900; margin-top: 2px; color: #000;">Ph: ${window.STORE_PHONE || '0307-2681893'}</div>
            </div>
            <div class="receipt-divider"></div>
            <table style="width: 100%; font-size: 10.5px; font-weight: 800; line-height: 1.4; color: #000;">
                <tr>
                    <td><strong>Bill #:</strong> <span style="font-weight: 900;">${sale.bill_number} (REPRINT)</span></td>
                    <td class="right"><strong>Type:</strong> <span style="font-weight: 900;">${payMethod.toUpperCase()} SALE</span></td>
                </tr>
                <tr>
                    <td><strong>Date:</strong> ${sale.created_at}</td>
                    <td class="right"><strong>Cashier:</strong> ${sale.cashier_name || 'Staff'}</td>
                </tr>
            </table>
            <div class="receipt-divider"></div>
            <table cellpadding="0" cellspacing="0" style="width: 100%; font-size: 11px; line-height: 1.3; color: #000;">
                <thead>
                    <tr style="border-bottom: 1.5px dashed #000;">
                        <th align="left" style="padding-bottom: 3px; font-weight: 900; color: #000;">ITEM</th>
                        <th align="center" style="padding-bottom: 3px; width: 35px; font-weight: 900; color: #000;">QTY</th>
                        <th class="right" style="padding-bottom: 3px; width: 55px; font-weight: 900; color: #000;">RATE</th>
                        <th class="right" style="padding-bottom: 3px; width: 60px; font-weight: 900; color: #000;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemRows}
                </tbody>
            </table>
            <div class="receipt-divider"></div>
            <table style="width: 100%; font-size: 11px; font-weight: 800; line-height: 1.4; color: #000;">
                <tr>
                    <td style="font-weight: 800;">Total Items / Qty:</td>
                    <td class="right" style="font-weight: 900;">${items.length} / ${totalQty}</td>
                </tr>
                <tr>
                    <td style="font-weight: 800;">Subtotal:</td>
                    <td class="right" style="font-weight: 800;">Rs. ${parseFloat(sale.subtotal).toFixed(2)}</td>
                </tr>
                ${parseFloat(sale.discount) > 0 ? `
                <tr>
                    <td style="font-weight: 800;">Bill Discount:</td>
                    <td class="right" style="font-weight: 900;">-Rs. ${parseFloat(sale.discount).toFixed(2)}</td>
                </tr>` : ''}
                <tr style="font-size: 14px; font-weight: 900; border-top: 2px dashed #000; border-bottom: 2px dashed #000;">
                    <td style="padding: 5px 0;">NET PAYABLE:</td>
                    <td class="right" style="padding: 5px 0;">Rs. ${parseFloat(sale.total).toFixed(2)}</td>
                </tr>
                <tr>
                    <td style="padding-top: 4px; font-weight: 800;">Paid (${payMethod.toUpperCase()}):</td>
                    <td class="right" style="padding-top: 4px; font-weight: 900;">Rs. ${parseFloat(sale.paid).toFixed(2)}</td>
                </tr>
                ${payMethod === 'cash' ? `
                <tr style="font-weight: 900;">
                    <td>Change Return:</td>
                    <td class="right">Rs. ${changeDue.toFixed(2)}</td>
                </tr>` : ''}
            </table>
            <div class="receipt-divider"></div>
            <div class="center" style="font-size: 10px; font-weight: 800; line-height: 1.4; margin-top: 4px; color: #000;">
                <div style="font-size: 11.5px; font-weight: 900; text-transform: uppercase;">Thank you for shopping with us!</div>
                <div style="font-size: 10.5px; font-weight: 800; margin: 2px 0;">Exchange possible within 3 days with bill.</div>
                <div style="font-size: 11px; font-weight: 900; text-transform: uppercase; border: 1.5px solid #000; padding: 3px 6px; margin: 3px auto; display: inline-block;">
                    Jewellery &amp; Cosmetics No Return / Exchange
                </div>
            </div>
            <div class="receipt-divider"></div>
            <div class="center" style="font-size: 9.5px; font-weight: 800; line-height: 1.3; color: #000; margin-top: 4px;">
                Software Developed by:<br>
                <span style="font-weight: 900; font-size: 10.5px;">0319-7273908 | 0336-8176491</span>
            </div>
        `;
        $('#receiptTemplateContainer').html(template);
        if ($('#thermalDirectPrintArea').length) {
            $('#thermalDirectPrintArea').html(template);
        }
    }

    // Bind reprint button in Sales History
    $('#historyReprintBtn').on('click', function() {
        const html = $('#receiptTemplateContainer').html();
        if (window.printThermalReceipt) {
            window.printThermalReceipt(html);
        } else {
            window.print();
        }
    });
    
    // HTML and Attribute sanitizers to prevent XSS and quotes syntax errors
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function escapeAttr(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function loadSales() {
        const search = $('#historySearchInput').val();
        const date_filter = $('#dateFilter').val();
        const start_date = $('#startDate').val();
        const end_date = $('#endDate').val();
        const cashier_id = $('#cashierFilter').val() || 0;
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/sales.php?action=list',
            type: 'GET',
            data: {
                search: search,
                date_filter: date_filter,
                start_date: start_date,
                end_date: end_date,
                cashier_id: cashier_id
            },
            dataType: 'json',
            success: function(response) {
                try {
                    if (response.success) {
                        let html = '';
                        if (!response.sales || response.sales.length === 0) {
                            html = '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-inbox display-6 mb-2"></i><br>No invoices found in log history.</td></tr>';
                        } else {
                        response.sales.forEach(function(sale) {
                            let statusBadge = '';
                            if (sale.status === 'completed') {
                                statusBadge = '<span class="badge bg-success-subtle text-success border border-success">Completed</span>';
                            } else if (sale.status === 'voided') {
                                statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger">Voided</span>';
                            } else if (sale.status === 'fully_refunded') {
                                statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning">Fully Refunded</span>';
                            } else {
                                statusBadge = '<span class="badge bg-info-subtle text-info border border-info">Partially Refunded</span>';
                            }
                            
                            // Check void permission (Admin Only)
                            const canVoid = isAdminUser && (sale.status !== 'voided' && sale.status !== 'fully_refunded');
                            const voidButton = canVoid 
                                ? `<button class="btn btn-sm btn-outline-danger btn-void-invoice" data-id="${sale.id}" data-bill="${escapeAttr(sale.bill_number)}" title="Void Bill"><i class="fas fa-trash"></i></button>`
                                : '';
                            
                            // Build inline items preview
                            let itemsListHtml = '';
                            let childRowsHtml = '';
                            if (sale.items && sale.items.length > 0) {
                                itemsListHtml = '<div class="d-flex flex-column gap-1 py-1">';
                                sale.items.forEach(function(item, idx) {
                                    const refQty = parseInt(item.quantity_refunded) || 0;
                                    const refBadge = refQty > 0 
                                        ? `<span class="badge bg-warning text-dark border ms-1" style="font-size: 0.7rem;">Ref: ${refQty}</span>`
                                        : '';
                                    
                                    itemsListHtml += `
                                        <div class="d-flex justify-content-between align-items-center py-1 px-2 rounded bg-light border border-light-subtle" style="font-size: 0.83rem;">
                                            <div class="text-truncate me-2" style="max-width: 250px;" title="${item.product_name}">
                                                <strong class="text-dark">${item.product_name}</strong>
                                                <span class="badge bg-white text-dark border ms-1 font-monospace fw-bold">x${item.quantity}</span>
                                                ${refBadge}
                                            </div>
                                            <div class="font-monospace fw-bold text-dark text-nowrap">
                                                Rs. ${parseFloat(item.net_amount).toFixed(2)}
                                            </div>
                                        </div>
                                    `;
                                    
                                    childRowsHtml += `
                                        <tr>
                                            <td class="text-muted">${idx + 1}</td>
                                            <td><strong>${item.product_name}</strong></td>
                                            <td><code class="text-muted">${item.barcode}</code></td>
                                            <td class="text-end font-monospace">Rs. ${parseFloat(item.unit_price).toFixed(2)}</td>
                                            <td class="text-center font-monospace fw-bold">${item.quantity}</td>
                                            <td class="text-end font-monospace text-muted">${parseFloat(item.discount) > 0 ? '-Rs. ' + parseFloat(item.discount).toFixed(2) : '-'}</td>
                                            <td class="text-end font-monospace fw-bold text-dark">Rs. ${parseFloat(item.net_amount).toFixed(2)}</td>
                                            <td class="text-center font-monospace">${refQty > 0 ? '<span class="badge bg-warning text-dark">Refunded: ' + refQty + '</span>' : '<span class="text-muted">-</span>'}</td>
                                        </tr>
                                    `;
                                });
                                itemsListHtml += '</div>';
                            } else {
                                itemsListHtml = '<span class="text-muted small">No items recorded</span>';
                                childRowsHtml = '<tr><td colspan="8" class="text-center text-muted py-2">No item records found.</td></tr>';
                            }
                            
                            // Subtotal & Discount preview below total
                            let subInfo = '';
                            if (parseFloat(sale.discount) > 0) {
                                subInfo += `<div class="small text-danger" style="font-size: 0.75rem;">Disc: -Rs. ${parseFloat(sale.discount).toFixed(2)}</div>`;
                            }
                            if (parseFloat(sale.total_refunded_amount) > 0) {
                                subInfo += `<div class="small text-warning-emphasis" style="font-size: 0.75rem;">Refund: Rs. ${parseFloat(sale.total_refunded_amount).toFixed(2)}</div>`;
                            }

                            html += `
                                <tr>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary p-0 px-1 btn-toggle-expand" data-target="#expand-${sale.id}" title="Toggle detailed table view">
                                            <i class="fas fa-chevron-right" style="font-size: 0.75rem;"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <strong class="font-monospace text-danger">${sale.bill_number}</strong>
                                        <div class="small text-muted font-monospace" style="font-size: 0.75rem;">${sale.items ? sale.items.length : 0} items</div>
                                    </td>
                                    <td><small class="text-secondary">${sale.created_at}</small></td>
                                    <td><span class="badge bg-light text-dark border">${sale.cashier_name}</span></td>
                                    <td>${itemsListHtml}</td>
                                    <td class="text-end">
                                        <div class="font-monospace fw-bold text-dark fs-6">Rs. ${parseFloat(sale.total).toFixed(2)}</div>
                                        ${subInfo}
                                    </td>
                                    <td class="text-center">${statusBadge}</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-sm btn-outline-primary btn-view-invoice" data-id="${sale.id}"><i class="fas fa-eye me-1"></i> View</button>
                                            ${voidButton}
                                        </div>
                                    </td>
                                </tr>
                                <tr id="expand-${sale.id}" class="d-none bg-light border-top-0">
                                    <td colspan="8" class="p-3 bg-light">
                                        <div class="card border shadow-sm">
                                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                                <strong class="small text-secondary text-uppercase"><i class="fas fa-list-check me-1 text-danger"></i> Itemized Sales Breakdown: ${sale.bill_number}</strong>
                                                <button class="btn btn-sm btn-outline-secondary py-0 btn-reprint-direct" data-id="${sale.id}"><i class="fas fa-print me-1"></i> Reprint Receipt</button>
                                            </div>
                                            <div class="card-body p-0 table-responsive">
                                                <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.85rem;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Product</th>
                                                            <th>Barcode</th>
                                                            <th class="text-end">Unit Price</th>
                                                            <th class="text-center">Sold Qty</th>
                                                            <th class="text-end">Discount</th>
                                                            <th class="text-end">Net Total</th>
                                                            <th class="text-center">Refund Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        ${childRowsHtml}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        }
                        $('#salesTableBody').html(html);
                    } else {
                        $('#salesTableBody').html('<tr><td colspan="8" class="text-center text-danger">Failed to fetch sales history log.</td></tr>');
                    }
                } catch (err) {
                    console.error('Error rendering sales table:', err);
                    $('#salesTableBody').html('<tr><td colspan="8" class="text-center text-danger py-4"><i class="fas fa-circle-exclamation me-1"></i> Render error: ' + (err.message || 'Data error') + '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                $('#salesTableBody').html('<tr><td colspan="8" class="text-center text-danger py-4"><i class="fas fa-triangle-exclamation me-1"></i> Error connecting to server: ' + (error || 'Network error') + '</td></tr>');
            }
        });
    }

    // Toggle child accordion row
    $(document).on('click', '.btn-toggle-expand', function() {
        const target = $(this).data('target');
        const icon = $(this).find('i');
        $(target).toggleClass('d-none');
        if ($(target).hasClass('d-none')) {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
        } else {
            icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
        }
    });

    // Direct reprint from inline accordion
    $(document).on('click', '.btn-reprint-direct', function() {
        const id = $(this).data('id');
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&id=${id}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    renderPrintTemplate(response.sale, response.items, response.payments[0]);
                    const printModal = new bootstrap.Modal(document.getElementById('receiptPrintModal'));
                    printModal.show();
                }
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
