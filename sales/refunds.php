<?php
require_once __DIR__ . '/../includes/header.php';
requirePermission('REFUND');
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 d-print-none">
    <h4 class="mb-0 text-dark fs-5 fs-md-4"><i class="fas fa-rotate-left me-2 text-warning"></i>Refund Management</h4>
    <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#newRefundModal"><i class="fas fa-plus me-1"></i> Process New Refund</button>
</div>

<!-- Refunds History Table -->
<div class="card d-print-none">
    <div class="card-header bg-white font-weight-bold">
        <h5 class="mb-0"><i class="fas fa-clock-rotate-left me-2 text-danger"></i>Refund Transactions Log</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Voucher #</th>
                        <th>Original Bill #</th>
                        <th>Date & Time</th>
                        <th>Processed By</th>
                        <th class="text-end">Amount Refunded</th>
                        <th>Reason</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="refundsTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-5">Loading refunds...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Process New Refund Modal -->
<div class="modal fade" id="newRefundModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="newRefundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="newRefundModalLabel"><i class="fas fa-rotate-left me-2"></i>Process Item Refund</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search Original Bill -->
                <div class="row g-3 mb-4 align-items-end" id="billLookupSection">
                    <div class="col-md-8 position-relative">
                        <label for="refundBillInput" class="form-label font-weight-bold">Search Original Bill Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                            <input type="text" class="form-control" id="refundBillInput" placeholder="Type bill # or cashier name..." autocomplete="off">
                        </div>
                        <!-- Live Autocomplete Suggestions Panel -->
                        <div id="refundBillSearchPanel" class="search-results-panel w-100 mt-1 p-0 d-none" style="position: absolute; z-index: 2050;">
                            <div class="list-group list-group-flush" id="refundBillSearchList"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-danger w-100 py-2" id="lookupBillBtn"><i class="fas fa-search"></i> Fetch Details</button>
                    </div>
                </div>
                
                <div id="refundErrorAlert" class="alert alert-danger d-none"></div>
                
                <!-- Bill Details & Return Form -->
                <form id="refundForm" class="d-none">
                    <input type="hidden" id="refund_sale_id" name="sale_id">
                    
                    <div class="bg-light p-3 rounded mb-3 border">
                        <div class="row">
                            <div class="col-6">
                                <strong>Bill Number:</strong> <span id="refBillNumText">--</span><br>
                                <strong>Date:</strong> <span id="refDateText">--</span>
                            </div>
                            <div class="col-6 text-end">
                                <strong>Cashier:</strong> <span id="refCashierText">--</span><br>
                                <strong>Bill Net Total:</strong> <span class="text-danger fw-bold" id="refNetTotalText">Rs. 0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="font-weight-bold mb-2">Select Items and Quantities to Refund</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product Name</th>
                                    <th class="text-end" style="width: 110px;">Unit Paid</th>
                                    <th class="text-center" style="width: 100px;">Sold Qty</th>
                                    <th class="text-center" style="width: 100px;">Refunded</th>
                                    <th class="text-center" style="width: 110px;">Remaining</th>
                                    <th class="text-center" style="width: 120px;">Qty to Refund</th>
                                    <th class="text-end" style="width: 120px;">Value Refund</th>
                                </tr>
                            </thead>
                            <tbody id="refundItemsTableBody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-4 align-items-center">
                        <div class="col-md-6">
                            <label for="refund_reason" class="form-label font-weight-bold">Reason for Refund *</label>
                            <select class="form-select" id="refund_reason" name="reason" required>
                                <option value="Customer Changed Mind" selected>Customer Changed Mind</option>
                                <option value="Wrong Product Purchase">Wrong Product Purchase</option>
                                <option value="Damaged / Defective Product">Damaged / Defective Product</option>
                                <option value="Duplicate Sale Bill">Duplicate Sale Bill</option>
                                <option value="Pricing / Overcharge Issue">Pricing / Overcharge Issue</option>
                            </select>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <div class="h5 mb-2 text-dark font-weight-bold">Refund Amount Due: <span class="text-danger font-monospace" id="refundValueDueText">Rs. 0.00</span></div>
                            <button type="submit" class="btn btn-danger btn-lg px-4" id="submitRefundBtn">Confirm Refund</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Refund Voucher Thermal Modal -->
<div class="modal fade" id="refundVoucherModal" tabindex="-1" aria-labelledby="refundVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content">
            <div class="modal-header d-print-none">
                <h5 class="modal-title" id="refundVoucherModalLabel"><i class="fas fa-receipt me-2"></i>Refund Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body overflow-y-auto">
                <div id="refundVoucherPrintContainer" class="receipt-print mx-auto">
                    <!-- Dynamic rendering -->
                </div>
            </div>
            <div class="modal-footer d-print-none">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="reprintRefundVoucherBtn"><i class="fas fa-print"></i> Print Voucher</button>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Professional Refund Confirmation Modal -->
<div class="modal fade" id="modalConfirmRefund" tabindex="-1" aria-labelledby="modalConfirmRefundLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title mb-0 fw-bold" id="modalConfirmRefundLabel">
                    <i class="fas fa-hand-holding-dollar text-warning me-2"></i>Refund Confirmation (Wapisi ki Tasdeeq)
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-muted small mb-3">
                    Baraye meharbani refund transaction ki tafseelat verify karein aur tasdeeq karein:
                </p>

                <div class="d-flex justify-content-between p-2 mb-2 bg-light rounded border">
                    <span class="small text-muted"><i class="fas fa-rotate-left text-danger me-1"></i> Wapis ki jane wali items:</span>
                    <strong class="font-monospace text-dark" id="confRefundQty">0 items</strong>
                </div>

                <div class="d-flex justify-content-between p-2 mb-3 bg-light rounded border">
                    <span class="small text-muted"><i class="fas fa-comment text-secondary me-1"></i> Reason (Wajah):</span>
                    <span class="small fw-bold text-dark" id="confRefundReason">-</span>
                </div>

                <!-- Red refund cash out banner -->
                <div class="p-3 rounded-3 text-center mb-2 border bg-danger-subtle border-danger text-danger">
                    <div class="small fw-bold text-uppercase">Total Cash Refund to Customer</div>
                    <div class="h3 fw-bold font-monospace my-1" id="confRefundTotalAmount">Rs. 0.00</div>
                    <div class="fw-semibold small"><i class="fas fa-arrow-up me-1"></i> Grahak ko naqad raqam wapis ada karein</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary px-3 btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-1"></i> Wapis (Cancel)
                </button>
                <button type="button" class="btn btn-danger px-4 fw-bold" id="btnExecuteRefundFinal">
                    <i class="fas fa-check-circle me-1"></i> Refund Confirm Karein
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadRefunds();
    
    // Live Autocomplete Search for Refund Bills
    let refundSearchTimer = null;
    $('#refundBillInput').on('input', function() {
        const term = $(this).val().trim();
        clearTimeout(refundSearchTimer);
        
        if (term.length === 0) {
            $('#refundBillSearchPanel').addClass('d-none');
            return;
        }
        
        refundSearchTimer = setTimeout(function() {
            $.ajax({
                url: `${(window.BASE_URL || "")}/api/sales.php?action=search_bills&mode=refund&term=${encodeURIComponent(term)}`,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.bills && res.bills.length > 0) {
                        let html = '';
                        res.bills.forEach(function(b) {
                            html += `
                                <a href="javascript:void(0)" class="list-group-item list-group-item-action py-2 px-3 select-refund-bill-item" data-id="${b.id}" data-bill="${b.bill_number}">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-monospace fw-bold text-danger">${b.bill_number}</span>
                                        <span class="badge bg-success-subtle text-success font-monospace">Rs. ${parseFloat(b.total).toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span><i class="fas fa-calendar-day me-1"></i>${b.created_at}</span>
                                        <span><i class="fas fa-user me-1"></i>${b.cashier_name}</span>
                                    </div>
                                </a>
                            `;
                        });
                        $('#refundBillSearchList').html(html);
                        $('#refundBillSearchPanel').removeClass('d-none');
                    } else {
                        $('#refundBillSearchList').html(`
                            <div class="list-group-item py-2 text-center text-muted small">
                                <i class="fas fa-search me-1"></i> No matching refundable bills found
                            </div>
                        `);
                        $('#refundBillSearchPanel').removeClass('d-none');
                    }
                }
            });
        }, 120);
    });

    // Handle suggestion click
    $(document).on('click', '.select-refund-bill-item', function(e) {
        e.preventDefault();
        const saleId = $(this).data('id');
        const billNum = $(this).data('bill');
        $('#refundBillInput').val(billNum);
        $('#refundBillSearchPanel').addClass('d-none');
        $('#refundErrorAlert').addClass('d-none');
        loadSaleDetailsToRefund(saleId);
    });

    // Close panel on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#billLookupSection').length) {
            $('#refundBillSearchPanel').addClass('d-none');
        }
    });

    // Enter key triggers search/load
    $('#refundBillInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#refundBillSearchPanel').addClass('d-none');
            $('#lookupBillBtn').trigger('click');
        }
    });

    // Search Bill button trigger
    $('#lookupBillBtn').on('click', function() {
        const billNum = $('#refundBillInput').val().trim();
        $('#refundErrorAlert').addClass('d-none');
        $('#refundForm').addClass('d-none');
        $('#refundBillSearchPanel').addClass('d-none');
        
        if (billNum === '') return;
        
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&bill_number=${encodeURIComponent(billNum)}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.sale) {
                    const matchedSale = response.sale;
                    if (matchedSale.status === 'voided') {
                        $('#refundErrorAlert').text("Cannot refund a voided invoice.").removeClass('d-none');
                        return;
                    }
                    if (matchedSale.status === 'fully_refunded') {
                        $('#refundErrorAlert').text("Invoice is already fully refunded.").removeClass('d-none');
                        return;
                    }
                    
                    // Fetch full detail of invoice
                    loadSaleDetailsToRefund(matchedSale.id);
                } else {
                    $('#refundErrorAlert').text("Invoice bill number not found.").removeClass('d-none');
                }
            },
            error: function() {
                $('#refundErrorAlert').text("Invoice bill number not found.").removeClass('d-none');
            }
        });
    });
    
    function loadSaleDetailsToRefund(saleId) {
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&id=${saleId}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const sale = response.sale;
                    $('#refund_sale_id').val(sale.id);
                    $('#refBillNumText').text(sale.bill_number);
                    $('#refDateText').text(sale.created_at);
                    $('#refCashierText').text(sale.cashier_name);
                    $('#refNetTotalText').text(`Rs. ${parseFloat(sale.total).toFixed(2)}`);
                    $('#refund_reason').val('Customer Changed Mind');
                    
                    let itemsHtml = '';
                    response.items.forEach(function(item) {
                        const prevRefunded = parseInt(item.quantity_refunded) || 0;
                        const remaining = item.quantity - prevRefunded;
                        
                        // Calculate pro-rata unit paid
                        const unitPaidVal = item.net_amount / item.quantity;
                        
                        // Disables row if already fully refunded
                        const disabledAttr = remaining <= 0 ? 'disabled' : '';
                        
                        itemsHtml += `
                            <tr class="${remaining <= 0 ? 'table-secondary opacity-50' : ''}">
                                <td><strong>${item.product_name}</strong></td>
                                <td class="text-end font-monospace">Rs. ${unitPaidVal.toFixed(2)}</td>
                                <td class="text-center font-monospace">${item.quantity}</td>
                                <td class="text-center font-monospace text-warning">${prevRefunded}</td>
                                <td class="text-center font-monospace fw-bold text-success">${remaining}</td>
                                <td class="text-center">
                                    <div class="qty-stepper">
                                        <button type="button" class="btn-qty-step btn-ref-dec" title="Kam karein (-)" ${disabledAttr}>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" class="qty-stepper-input refund-qty-input font-monospace" 
                                            name="items[${item.id}][qty_to_refund]" 
                                            data-item-id="${item.id}"
                                            data-unit-value="${unitPaidVal}"
                                            data-max="${remaining}"
                                            value="0" min="0" max="${remaining}" ${disabledAttr}>
                                        <button type="button" class="btn-qty-step btn-ref-inc" title="Barhayein (+)" ${disabledAttr}>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="items[${item.id}][sale_item_id]" value="${item.id}">
                                </td>
                                <td class="text-end font-monospace fw-bold text-danger item-refund-val-text">Rs. 0.00</td>
                            </tr>
                        `;
                    });
                    
                    $('#refundItemsTableBody').html(itemsHtml);
                    $('#refundValueDueText').text('Rs. 0.00');
                    $('#refundForm').removeClass('d-none');
                }
            }
        });
    }
    
    // Stepper Button Handlers for Refund Qty
    $(document).on('click', '.btn-ref-dec', function(e) {
        e.preventDefault();
        const $input = $(this).closest('.qty-stepper').find('.refund-qty-input');
        const min = parseInt($input.attr('min')) || 0;
        let val = parseInt($input.val()) || 0;
        if (val > min) {
            val -= 1;
            $input.val(val).trigger('change');
        }
    });

    $(document).on('click', '.btn-ref-inc', function(e) {
        e.preventDefault();
        const $input = $(this).closest('.qty-stepper').find('.refund-qty-input');
        const max = parseInt($input.data('max')) || 0;
        let val = parseInt($input.val()) || 0;
        if (val < max) {
            val += 1;
            $input.val(val).trigger('change');
        } else {
            showToast(`Bikri se ziyada refund nahi ho sakta! Asal bachi hui taadad sirf ${max} hai.`, 'warning');
        }
    });

    // Live update of refund calculations
    $(document).on('input change', '.refund-qty-input', function() {
        const max = parseInt($(this).data('max')) || 0;
        let val = parseInt($(this).val());
        
        if (isNaN(val) || val < 0) {
            val = 0;
            $(this).val(0);
        } else if (val > max) {
            val = max;
            $(this).val(max);
            showToast(`Bikri se ziyada refund nahi ho sakta! Asal bachi hui taadad sirf ${max} hai.`, 'warning');
        }
        
        const unitVal = parseFloat($(this).data('unit-value'));
        const itemRefundVal = val * unitVal;
        $(this).closest('tr').find('.item-refund-val-text').text(`Rs. ${itemRefundVal.toFixed(2)}`);
        
        // Sum grand total
        let sumRefundTotal = 0.00;
        $('.refund-qty-input').each(function() {
            const q = parseInt($(this).val()) || 0;
            const uv = parseFloat($(this).data('unit-value'));
            sumRefundTotal += (q * uv);
        });
        
        $('#refundValueDueText').text(`Rs. ${sumRefundTotal.toFixed(2)}`);
    });
    
    let currentRefundPayload = null;

    // Submit refund transaction -> Opens professional confirmation modal
    $('#refundForm').on('submit', function(e) {
        e.preventDefault();
        
        const sale_id = $('#refund_sale_id').val();
        const reason = $('#refund_reason').val() || 'Customer Return';
        
        let itemsArr = [];
        let totalItemsCount = 0;
        let totalRefundAmount = 0.00;
        $('.refund-qty-input').each(function() {
            const q = parseInt($(this).val()) || 0;
            const itemId = $(this).data('item-id');
            const uv = parseFloat($(this).data('unit-value')) || 0;
            if (q > 0) {
                itemsArr.push({
                    sale_item_id: itemId,
                    qty_to_refund: q
                });
                totalItemsCount += q;
                totalRefundAmount += (q * uv);
            }
        });
        
        if (itemsArr.length === 0) {
            showToast("Baraye meharbani wapis ki jane wali kam az kam ek taadad darj karein.", "warning");
            return;
        }

        // Populate Refund Confirmation Modal
        $('#confRefundQty').text(`${totalItemsCount} item(s)`);
        $('#confRefundReason').text(reason);
        $('#confRefundTotalAmount').text(`Rs. ${totalRefundAmount.toFixed(2)}`);

        currentRefundPayload = {
            sale_id: sale_id,
            reason: reason,
            items: itemsArr,
            total_amount: totalRefundAmount
        };

        const confModal = new bootstrap.Modal(document.getElementById('modalConfirmRefund'));
        confModal.show();
    });

    // Final Execution Handler inside Refund Confirmation Modal
    $('#btnExecuteRefundFinal').on('click', function() {
        if (!currentRefundPayload) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

        $.ajax({
            url: (window.BASE_URL || '') + '/api/sales.php?action=refund',
            type: 'POST',
            data: {
                sale_id: currentRefundPayload.sale_id,
                reason: currentRefundPayload.reason,
                items: currentRefundPayload.items
            },
            dataType: 'json',
            success: function(response) {
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Refund Confirm Karein');
                if (response.success) {
                    const confModalEl = document.getElementById('modalConfirmRefund');
                    const confModal = bootstrap.Modal.getInstance(confModalEl);
                    if (confModal) confModal.hide();

                    const newRefEl = document.getElementById('newRefundModal');
                    const newRefModal = bootstrap.Modal.getInstance(newRefEl);
                    if (newRefModal) newRefModal.hide();

                    $('#refundForm').addClass('d-none');
                    $('#refundBillInput').val('');
                    loadRefunds();
                    showToast("Refund transaction ba-kamyabi mukammal ho gayi!", "success");
                    
                    // Render Refund voucher preview
                    renderRefundVoucherTemplate(response.voucher, currentRefundPayload.sale_id, currentRefundPayload.items, currentRefundPayload.reason);
                    currentRefundPayload = null;
                    const voucherModal = new bootstrap.Modal(document.getElementById('refundVoucherModal'));
                    voucherModal.show();
                } else {
                    showToast(response.message || 'Error executing refund.', 'danger');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Refund Confirm Karein');
                showToast(xhr.responseJSON?.message || 'Error executing refund.', 'danger');
            }
        });
    });
    
    function renderRefundVoucherTemplate(voucherNum, saleId, refundedItems, reason) {
        // Fetch details of original invoice for rendering receipt voucher
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&id=${saleId}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const sale = response.sale;
                    const dateNow = new Date();
                    const formattedDate = dateNow.getFullYear() + '-' + 
                                          String(dateNow.getMonth() + 1).padStart(2, '0') + '-' + 
                                          String(dateNow.getDate()).padStart(2, '0') + ' ' + 
                                          String(dateNow.getHours()).padStart(2, '0') + ':' + 
                                          String(dateNow.getMinutes()).padStart(2, '0');
                    
                    let rows = '';
                    let sumTotalRefund = 0.00;
                    refundedItems.forEach(ri => {
                        const originalItem = response.items.find(i => i.id == ri.sale_item_id);
                        const unitPaid = originalItem.net_amount / originalItem.quantity;
                        const itemTotal = unitPaid * ri.qty_to_refund;
                        sumTotalRefund += itemTotal;
                        
                        rows += `
                            <tr>
                                <td><strong>${originalItem.product_name}</strong><br>${ri.qty_to_refund} x Rs. ${unitPaid.toFixed(2)}</td>
                                <td class="right" valign="bottom">Rs. ${itemTotal.toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    
                    const template = `
                        <div class="center" style="margin-bottom: 6px; color: #000;">
                            <img src="${(window.BASE_URL || "")}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="${window.STORE_NAME || 'One Dollar Shop'}" class="receipt-logo">
                            <h3 style="margin: 0; font-size: 16.5px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase; color: #000;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h3>
                            <p style="margin: 2px 0; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #000;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'}</p>
                            <div style="font-size: 12px; font-weight: 900; margin-top: 2px; color: #000;">Ph: ${window.STORE_PHONE || '0307-2681893'}</div>
                            <div style="margin: 5px auto 2px auto; border: 1.5px solid #000; padding: 2px 6px; font-weight: 900; font-size: 12px; display: inline-block; text-transform: uppercase; color: #000;">*** REFUND VOUCHER ***</div>
                        </div>
                        <div class="receipt-divider"></div>
                        <div style="font-size: 10.5px; font-weight: 800; line-height: 1.4; color: #000;">
                            <div style="display: flex; justify-content: space-between;">
                                <span><strong>Voucher #:</strong> <span style="font-weight: 900;">${voucherNum}</span></span>
                                <span><strong>Orig Bill:</strong> <span style="font-weight: 900;">${sale.bill_number}</span></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span><strong>Date:</strong> ${formattedDate}</span>
                                <span><strong>Cashier:</strong> ${sale.cashier_name || 'Staff'}</span>
                            </div>
                        </div>
                        <div class="receipt-divider"></div>
                        <table style="width: 100%; font-size: 11px; line-height: 1.3; color: #000;">
                            <thead>
                                <tr style="border-bottom: 1.5px dashed #000;">
                                    <th align="left" style="padding-bottom: 3px; font-weight: 900; color: #000;">RETURNED ITEM</th>
                                    <th class="right" style="padding-bottom: 3px; font-weight: 900; color: #000;">AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rows}
                            </tbody>
                        </table>
                        <div class="receipt-divider"></div>
                        <table style="width: 100%; font-size: 11px; font-weight: 800; line-height: 1.4; color: #000;">
                            <tr style="font-size: 14px; font-weight: 900; border-top: 2px dashed #000; border-bottom: 2px dashed #000;">
                                <td style="padding: 5px 0;">CASH REFUNDED:</td>
                                <td class="right" style="padding: 5px 0; font-weight: 900;">Rs. ${sumTotalRefund.toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td style="padding-top: 4px; font-weight: 800;">Reason:</td>
                                <td class="right" style="padding-top: 4px; font-weight: 900;">${reason}</td>
                            </tr>
                        </table>
                        <div class="receipt-divider"></div>
                        <div class="center" style="font-size: 10px; font-weight: 800; line-height: 1.4; margin-top: 4px; color: #000;">
                            <div style="font-weight: 900;">Items Restocked to Inventory</div>
                            <div>Customer Copy / Accounts Record</div>
                        </div>
                        <div class="receipt-divider"></div>
                        <div class="center" style="font-size: 9.5px; font-weight: 800; line-height: 1.3; color: #000; margin-top: 4px;">
                            Software Developed by:<br>
                            <span style="font-weight: 900; font-size: 10.5px;">0319-7273908 | 0336-8176491</span>
                        </div>
                    `;
                    
                    $('#refundVoucherPrintContainer').html(template);
                    if ($('#thermalDirectPrintArea').length) {
                        $('#thermalDirectPrintArea').html(template);
                    }
                    if (window.printThermalReceipt) {
                        window.printThermalReceipt(template);
                    }
                }
            }
        });
    }

    // Bind reprint voucher button
    $('#reprintRefundVoucherBtn').on('click', function() {
        const html = $('#refundVoucherPrintContainer').html();
        if (window.printThermalReceipt) {
            window.printThermalReceipt(html);
        } else {
            window.print();
        }
    });
    
    function loadRefunds() {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/sales.php?action=list_refunds',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (response.refunds.length === 0) {
                        html = '<tr><td colspan="7" class="text-center py-4 text-muted">No processed refunds found.</td></tr>';
                    } else {
                        response.refunds.forEach(function(ref) {
                            html += `
                                <tr>
                                    <td><code>${ref.refund_voucher}</code></td>
                                    <td><strong>${ref.bill_number}</strong></td>
                                    <td>${ref.created_at}</td>
                                    <td>${ref.cashier_name}</td>
                                    <td class="text-end font-monospace text-danger fw-bold">Rs. ${parseFloat(ref.total_refunded).toFixed(2)}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary border">${ref.reason}</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary view-voucher-print" data-voucher="${ref.refund_voucher}">
                                            <i class="fas fa-print"></i> Receipt
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#refundsTableBody').html(html);
                } else {
                    $('#refundsTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load refunds.</td></tr>');
                }
            }
        });
    }
    
    // View voucher reprint click
    $(document).on('click', '.view-voucher-print', function() {
        const voucherNum = $(this).data('voucher');
        
        // Find in local array or query refund details
        $.ajax({
            url: (window.BASE_URL || '') + '/api/sales.php?action=list_refunds',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const match = response.refunds.find(r => r.refund_voucher == voucherNum);
                    if(match) {
                        // Gather items for voucher reprint
                        $.ajax({
                            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&id=${match.original_sale_id}`,
                            type: 'GET',
                            dataType: 'json',
                            success: function(res) {
                                if (res.success) {
                                    // Fetch the specific items returned in this refund ID
                                    // Let's use detailed helper queries or simple filter
                                    $.ajax({
                                        url: (window.BASE_URL || '') + '/api/reports.php?action=refund_items_details&refund_id=' + match.id,
                                        type: 'GET',
                                        dataType: 'json',
                                        success: function(retRes) {
                                            if (retRes.success) {
                                                const itemsFormat = retRes.items.map(i => ({
                                                    sale_item_id: i.sale_item_id,
                                                    qty_to_refund: i.quantity_refunded
                                                }));
                                                renderRefundVoucherTemplate(voucherNum, match.original_sale_id, itemsFormat, match.reason);
                                                const vModal = new bootstrap.Modal(document.getElementById('refundVoucherModal'));
                                                vModal.show();
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    }
                }
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
