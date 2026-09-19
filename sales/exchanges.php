<?php
require_once __DIR__ . '/../includes/header.php';
requirePermission('EXCHANGE');
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 d-print-none">
    <h4 class="mb-0 text-dark fs-5 fs-md-4"><i class="fas fa-repeat me-2 text-info"></i>Exchanges Module</h4>
    <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#newExchangeModal"><i class="fas fa-plus me-1"></i> Process New Exchange</button>
</div>

<!-- Exchange Logs Table -->
<div class="card d-print-none">
    <div class="card-header bg-white font-weight-bold">
        <h5 class="mb-0"><i class="fas fa-clock-rotate-left me-2 text-danger"></i>Exchange Transactions Log</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Voucher #</th>
                        <th>Original Bill #</th>
                        <th>Date & Time</th>
                        <th>Cashier</th>
                        <th class="text-end">Returned Credit</th>
                        <th class="text-end">Replaced Value</th>
                        <th class="text-end">Net Difference</th>
                        <th>Settlement</th>
                        <th class="text-end" style="width: 110px;">Action</th>
                    </tr>
                </thead>
                <tbody id="exchangesTableBody">
                    <tr>
                        <td colspan="9" class="text-center py-5">Loading exchanges...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Process Exchange Modal -->
<div class="modal fade" id="newExchangeModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="newExchangeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="newExchangeModalLabel"><i class="fas fa-rotate-left me-2"></i>Create Product Exchange</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
                <!-- Search Original Bill -->
                <div class="row g-3 mb-4 align-items-end" id="exchBillLookupGroup">
                    <div class="col-md-9 position-relative">
                        <label for="exchangeBillInput" class="form-label font-weight-bold">Search Original Bill Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                            <input type="text" class="form-control" id="exchangeBillInput" placeholder="Type bill # or cashier name..." autocomplete="off">
                        </div>
                        <!-- Live Autocomplete Suggestions Panel -->
                        <div id="exchangeBillSearchPanel" class="search-results-panel w-100 mt-1 p-0 d-none" style="position: absolute; z-index: 2050;">
                            <div class="list-group list-group-flush" id="exchangeBillSearchList"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-danger w-100 py-2" id="lookupExchangeBillBtn"><i class="fas fa-search"></i> Load Bill</button>
                    </div>
                </div>
                
                <div id="exchangeErrorAlert" class="alert alert-danger d-none"></div>
                
                <!-- Main Exchange Workspace -->
                <div id="exchangeWorkspace" class="row g-3 d-none">
                    <input type="hidden" id="exchange_sale_id">
                    
                    <!-- Left: Returned Products -->
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-white font-weight-bold py-2 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-dark"><i class="fas fa-arrow-left text-danger me-1"></i> Step 1: Items Customer is Returning</h6>
                                <span class="badge bg-danger-subtle text-danger border border-danger">Wapis Ki Ashya</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                                    <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.85rem;">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>Product</th>
                                                <th class="text-end" style="width: 80px;">Unit Paid</th>
                                                <th class="text-center" style="width: 50px;">Bal</th>
                                                <th class="text-center" style="width: 125px;">Return Qty</th>
                                                <th class="text-end" style="width: 90px;">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody id="exchangeReturnItemsTableBody">
                                            <!-- Loaded dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right: Replacement Products Search & Cart -->
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-header bg-white font-weight-bold py-2 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-dark"><i class="fas fa-cart-plus text-success me-1"></i> Step 2: Add Replacement Items</h6>
                                <span class="badge bg-success-subtle text-success border border-success">Badle Mein Ashya</span>
                            </div>
                            <div class="card-body py-2">
                                <div class="position-relative" id="replSearchGroup">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="fas fa-search text-secondary"></i></span>
                                        <input type="text" class="form-control" id="replacementSearchInput" placeholder="Type product name, barcode, or scan..." autocomplete="off">
                                    </div>
                                    <!-- Search Results Dropdown Panel -->
                                    <div id="replSearchResultsPanel" class="search-results-panel w-100 mt-1 p-0 d-none" style="position: absolute; left: 0; right: 0; z-index: 2150; max-height: 260px; overflow-y: auto; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
                                        <div class="list-group list-group-flush" id="replSearchResultsList"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-white font-weight-bold py-2 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-dark">Replacement Cart</h6>
                                <span class="badge bg-light text-dark border font-monospace" id="replItemCountBadge">0 Items</span>
                            </div>
                            <div class="card-body p-0 table-responsive" style="max-height: 200px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Product Name</th>
                                            <th class="text-end" style="width: 80px;">Price</th>
                                            <th class="text-center" style="width: 125px;">Qty</th>
                                            <th class="text-end" style="width: 90px;">Total</th>
                                            <th class="text-center" style="width: 40px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="replacementCartTableBody">
                                        <tr class="repl-empty-row">
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="fas fa-barcode me-1"></i> Add replacement products by searching or scanning barcode above.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bottom: Compare & Settlement -->
                    <div class="col-12 mt-3 pt-3 border-top">
                        <div class="row align-items-center bg-light p-3 rounded border g-2">
                            <div class="col-6 col-md-3">
                                <span class="text-muted small">Total Returned Credit:</span><br>
                                <strong class="h5 font-monospace text-success" id="exchReturnedCreditText">Rs. 0.00</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted small">Total Replaced Charge:</span><br>
                                <strong class="h5 font-monospace text-danger" id="exchReplacedChargeText">Rs. 0.00</strong>
                            </div>
                            <div class="col-12 col-md-3 border-md-start">
                                <span class="text-muted small">Net Difference (Faraq):</span><br>
                                <strong class="h4 font-monospace" id="exchNetDifferenceText">Rs. 0.00</strong>
                                <div class="text-muted small fw-bold" id="exchCalculationDetail">No change</div>
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="exch_settlement_method" class="form-label font-weight-bold small mb-1">Settlement Method</label>
                                <select class="form-select form-select-sm" id="exch_settlement_method">
                                    <option value="cash" selected>Cash (Naqad)</option>
                                    <option value="bank">Bank Transfer</option>
                                </select>
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger btn-lg px-4" id="submitExchangeBtn">
                                <i class="fas fa-check-circle me-1"></i> Complete Exchange
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 80mm Thermal Exchange Receipt Modal -->
<div class="modal fade" id="exchangeVoucherModal" tabindex="-1" aria-labelledby="exchangeVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content">
            <div class="modal-header d-print-none bg-dark text-white py-2">
                <h6 class="modal-title mb-0" id="exchangeVoucherModalLabel"><i class="fas fa-receipt me-2"></i>Product Exchange Invoice</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body overflow-y-auto p-2" style="background: #f8fafc; max-height: 80vh;">
                <div id="exchangeVoucherPrintContainer" class="receipt-print mx-auto bg-white p-3 border shadow-sm" style="max-width: 380px; font-size: 0.85rem;">
                    <!-- Dynamic receipt rendering -->
                </div>
            </div>
            <div class="modal-footer d-print-none py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnPrintExchangeVoucher">
                    <i class="fas fa-print me-1"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Professional Exchange Confirmation Modal -->
<div class="modal fade" id="modalConfirmExchange" tabindex="-1" aria-labelledby="modalConfirmExchangeLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title mb-0 fw-bold" id="modalConfirmExchangeLabel">
                    <i class="fas fa-arrows-rotate text-warning me-2"></i>Confirm Exchange (Tabdili ki Tasdeeq)
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-muted small mb-3">
                    Baraye meharbani exchange transaction ki tafseelat verify karein aur tasdeeq karein:
                </p>

                <div class="d-flex justify-content-between p-2 mb-2 bg-light rounded border">
                    <span class="small text-muted"><i class="fas fa-rotate-left text-danger me-1"></i> Wapis items (Return):</span>
                    <strong class="font-monospace text-dark" id="confModalReturnQty">0 items</strong>
                </div>

                <div class="d-flex justify-content-between p-2 mb-3 bg-light rounded border">
                    <span class="small text-muted"><i class="fas fa-cart-plus text-primary me-1"></i> Badle mein items (Replace):</span>
                    <strong class="font-monospace text-dark" id="confModalReplaceQty">0 items</strong>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="border rounded p-2 text-center bg-white shadow-sm">
                            <div class="text-muted small">Total Return Credit</div>
                            <strong class="font-monospace text-primary" id="confModalReturnCredit">Rs. 0.00</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2 text-center bg-white shadow-sm">
                            <div class="text-muted small">Total Replacement</div>
                            <strong class="font-monospace text-dark" id="confModalReplaceCharge">Rs. 0.00</strong>
                        </div>
                    </div>
                </div>

                <!-- Net Difference Box with strict Green/Red color coding -->
                <div id="confModalDiffBox" class="p-3 rounded-3 text-center mb-2 border">
                    <div class="small fw-bold text-uppercase" id="confModalDiffTitle">Net Difference (Faraq)</div>
                    <div class="h3 fw-bold font-monospace my-1" id="confModalDiffAmount">Rs. 0.00</div>
                    <div class="fw-semibold small" id="confModalDiffSubtitle">No change</div>
                </div>

                <div class="text-center small text-muted mt-2">
                    Payment Method: <span class="badge bg-dark text-uppercase font-monospace px-2 py-1" id="confModalPayMethod">CASH</span>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary px-3 btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-1"></i> Wapis (Cancel)
                </button>
                <button type="button" class="btn btn-success px-4 fw-bold" id="btnExecuteExchangeFinal">
                    <i class="fas fa-check-circle me-1"></i> Tasdeeq Karein (Confirm)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadExchanges();
    
    let replacementCart = []; // Array of {product_id, name, barcode, selling_price, quantity, stock_qty}
    let originalItemsList = []; // Caches original items details
    
    // Live Autocomplete Search for Exchange Bills
    let exchangeSearchTimer = null;
    $('#exchangeBillInput').on('input', function() {
        const term = $(this).val().trim();
        clearTimeout(exchangeSearchTimer);
        
        if (term.length === 0) {
            $('#exchangeBillSearchPanel').addClass('d-none');
            return;
        }
        
        exchangeSearchTimer = setTimeout(function() {
            $.ajax({
                url: `${(window.BASE_URL || "")}/api/sales.php?action=search_bills&mode=exchange&term=${encodeURIComponent(term)}`,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.bills && res.bills.length > 0) {
                        let html = '';
                        res.bills.forEach(function(b) {
                            html += `
                                <a href="javascript:void(0)" class="list-group-item list-group-item-action py-2 px-3 select-exchange-bill-item" data-id="${b.id}" data-bill="${b.bill_number}">
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
                        $('#exchangeBillSearchList').html(html);
                        $('#exchangeBillSearchPanel').removeClass('d-none');
                    } else {
                        $('#exchangeBillSearchList').html(`
                            <div class="list-group-item py-2 text-center text-muted small">
                                <i class="fas fa-search me-1"></i> No matching bills found for exchange
                            </div>
                        `);
                        $('#exchangeBillSearchPanel').removeClass('d-none');
                    }
                }
            });
        }, 120);
    });

    // Handle suggestion click
    $(document).on('click', '.select-exchange-bill-item', function(e) {
        e.preventDefault();
        const saleId = $(this).data('id');
        const billNum = $(this).data('bill');
        $('#exchangeBillInput').val(billNum);
        $('#exchangeBillSearchPanel').addClass('d-none');
        $('#exchangeErrorAlert').addClass('d-none');
        $('#exchangeWorkspace').addClass('d-none');
        replacementCart = [];
        $('#exchange_sale_id').val(saleId);
        loadOriginalItemsForExchange(saleId);
    });

    // Close panel on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#exchBillLookupGroup').length) {
            $('#exchangeBillSearchPanel').addClass('d-none');
        }
    });

    // Enter key triggers search
    $('#exchangeBillInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#exchangeBillSearchPanel').addClass('d-none');
            $('#lookupExchangeBillBtn').trigger('click');
        }
    });

    // Fetch Original Bill button
    $('#lookupExchangeBillBtn').on('click', function() {
        const billNum = $('#exchangeBillInput').val().trim();
        $('#exchangeErrorAlert').addClass('d-none');
        $('#exchangeWorkspace').addClass('d-none');
        $('#exchangeBillSearchPanel').addClass('d-none');
        replacementCart = [];
        
        if(billNum === '') return;
        
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&bill_number=${encodeURIComponent(billNum)}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.sale) {
                    const sale = response.sale;
                    if (sale.status === 'voided') {
                        $('#exchangeErrorAlert').text("Cannot process exchanges on voided bills.").removeClass('d-none');
                        return;
                    }
                    if (sale.status === 'fully_refunded') {
                        $('#exchangeErrorAlert').text("Cannot process exchanges on fully refunded bills.").removeClass('d-none');
                        return;
                    }
                    
                    $('#exchange_sale_id').val(sale.id);
                    loadOriginalItemsForExchange(sale.id);
                } else {
                    $('#exchangeErrorAlert').text("Original bill number not found.").removeClass('d-none');
                }
            },
            error: function() {
                $('#exchangeErrorAlert').text("Original bill number not found.").removeClass('d-none');
            }
        });
    });
    
    function loadOriginalItemsForExchange(saleId) {
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get&id=${saleId}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    originalItemsList = response.items;
                    let html = '';
                    
                    response.items.forEach(function(item) {
                        const prevRefunded = parseInt(item.quantity_refunded) || 0;
                        const remaining = item.quantity - prevRefunded;
                        const unitPaid = item.net_amount / item.quantity;
                        
                        const disabled = remaining <= 0 ? 'disabled' : '';
                        
                        html += `
                            <tr class="${remaining <= 0 ? 'table-secondary opacity-50' : ''}">
                                <td>
                                    <strong class="text-dark">${item.product_name}</strong><br>
                                    <small class="text-muted"><i class="fas fa-barcode me-1"></i>${item.barcode}</small>
                                </td>
                                <td class="text-end font-monospace">Rs. ${unitPaid.toFixed(2)}</td>
                                <td class="text-center font-monospace fw-bold text-success">${remaining}</td>
                                <td class="text-center">
                                    <div class="qty-stepper">
                                        <button type="button" class="btn-qty-step btn-exch-return-dec" title="Kam karein (-)" ${disabled}>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" class="qty-stepper-input exch-return-qty-input font-monospace"
                                            data-item-id="${item.id}"
                                            data-unit-value="${unitPaid}"
                                            data-max="${remaining}"
                                            value="0" min="0" max="${remaining}" ${disabled}>
                                        <button type="button" class="btn-qty-step btn-exch-return-inc" title="Barhayein (+)" ${disabled}>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-end font-monospace fw-bold text-success item-credit-val-text">Rs. 0.00</td>
                            </tr>
                        `;
                    });
                    
                    $('#exchangeReturnItemsTableBody').html(html);
                    renderReplacementCart();
                    $('#exchangeWorkspace').removeClass('d-none');
                }
            }
        });
    }
    
    // Stepper Button Handlers for Step 1 Return Qty
    $(document).on('click', '.btn-exch-return-dec', function(e) {
        e.preventDefault();
        const $input = $(this).closest('.qty-stepper').find('.exch-return-qty-input');
        const min = parseInt($input.attr('min')) || 0;
        let val = parseInt($input.val()) || 0;
        if (val > min) {
            val -= 1;
            $input.val(val).trigger('change');
        }
    });

    $(document).on('click', '.btn-exch-return-inc', function(e) {
        e.preventDefault();
        const $input = $(this).closest('.qty-stepper').find('.exch-return-qty-input');
        const max = parseInt($input.data('max')) || 0;
        let val = parseInt($input.val()) || 0;
        if (val < max) {
            val += 1;
            $input.val(val).trigger('change');
        } else {
            showToast(`Bikri se ziyada wapis nahi ho sakta! Asal bachi hui taadad sirf ${max} hai.`, 'warning');
        }
    });

    // Return Qty trigger on manual edit/change
    $(document).on('input change', '.exch-return-qty-input', function() {
        const max = parseInt($(this).data('max')) || 0;
        let val = parseInt($(this).val());
        
        if (isNaN(val) || val < 0) {
            val = 0;
            $(this).val(0);
        } else if (val > max) {
            val = max;
            $(this).val(max);
            showToast(`Bikri se ziyada wapis nahi ho sakta! Asal bachi hui taadad sirf ${max} hai.`, 'warning');
        }
        
        const unitVal = parseFloat($(this).data('unit-value'));
        const credit = val * unitVal;
        $(this).closest('tr').find('.item-credit-val-text').text(`Rs. ${credit.toFixed(2)}`);
        
        recalculateExchangeTotals();
    });
    
    // Replacement Search Trigger (Debounced live search)
    let searchTimeout = null;
    $('#replacementSearchInput').on('input', function() {
        const query = $(this).val().trim();
        clearTimeout(searchTimeout);
        
        if (query.length === 0) {
            $('#replSearchResultsPanel').addClass('d-none');
            return;
        }
        
        searchTimeout = setTimeout(function() {
            performReplacementSearch(query);
        }, 150);
    });

    // Barcode scanner Enter key on replacement search
    $('#replacementSearchInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const query = $(this).val().trim();
            if (query === '') return;
            
            clearTimeout(searchTimeout);
            $.ajax({
                url: `${(window.BASE_URL || "")}/api/products.php?action=list&search=${encodeURIComponent(query)}&status=active`,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.products && res.products.length > 0) {
                        const exact = res.products.find(p => p.barcode === query && p.show_in_pos == 1);
                        const chosen = exact || res.products.find(p => p.show_in_pos == 1) || res.products[0];
                        if (chosen) {
                            addReplacementItemToCart({
                                product_id: chosen.id,
                                name: chosen.name,
                                barcode: chosen.barcode,
                                selling_price: parseFloat(chosen.selling_price),
                                quantity: 1,
                                stock_qty: parseInt(chosen.quantity) || 0
                            });
                            $('#replacementSearchInput').val('');
                            $('#replSearchResultsPanel').addClass('d-none');
                            return;
                        }
                    }
                    performReplacementSearch(query);
                }
            });
        }
    });

    function performReplacementSearch(query) {
        $('#replSearchResultsList').html(`
            <div class="list-group-item py-3 text-center text-muted small">
                <span class="spinner-border spinner-border-sm text-danger me-1"></span> Searching replacement products...
            </div>
        `);
        $('#replSearchResultsPanel').removeClass('d-none');
        
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/products.php?action=list&search=${encodeURIComponent(query)}&status=active&limit=25`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.products && response.products.length > 0) {
                    let html = '';
                    let matchCount = 0;
                    response.products.forEach(function(p) {
                        if (p.show_in_pos == 1) {
                            matchCount++;
                            const stock = parseInt(p.quantity) || 0;
                            const isOutOfStock = stock <= 0;
                            const stockBadge = isOutOfStock 
                                ? `<span class="badge bg-danger">Out of Stock (0)</span>`
                                : `<span class="badge bg-success-subtle text-success border border-success font-monospace">Stock: ${stock}</span>`;
                            
                            html += `
                                <button type="button" class="list-group-item list-group-item-action repl-search-result-item d-flex justify-content-between align-items-center py-2 px-3 ${isOutOfStock ? 'disabled opacity-50' : ''}"
                                    data-id="${p.id}"
                                    data-name="${p.name}"
                                    data-barcode="${p.barcode}"
                                    data-price="${p.selling_price}"
                                    data-stock="${stock}">
                                    <div class="text-start">
                                        <strong class="text-dark d-block">${p.name}</strong>
                                        <small class="text-muted"><i class="fas fa-barcode me-1"></i><code>${p.barcode}</code></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold text-danger font-monospace d-block">Rs. ${parseFloat(p.selling_price).toFixed(2)}</span>
                                        ${stockBadge}
                                    </div>
                                </button>
                            `;
                        }
                    });
                    
                    if (matchCount === 0) {
                        html = `
                            <div class="list-group-item py-3 text-center text-muted small">
                                <i class="fas fa-box-open me-1 text-secondary"></i> No POS-active products match "<strong>${query}</strong>"
                            </div>
                        `;
                    }
                    
                    $('#replSearchResultsList').html(html);
                    $('#replSearchResultsPanel').removeClass('d-none');
                } else {
                    $('#replSearchResultsList').html(`
                        <div class="list-group-item py-3 text-center text-muted small">
                            <i class="fas fa-box-open me-1 text-secondary"></i> No products match "<strong>${query}</strong>"
                        </div>
                    `);
                    $('#replSearchResultsPanel').removeClass('d-none');
                }
            },
            error: function() {
                $('#replSearchResultsList').html(`
                    <div class="list-group-item py-2 text-center text-danger small">
                        <i class="fas fa-exclamation-triangle me-1"></i> Product search failed. Please try again.
                    </div>
                `);
                $('#replSearchResultsPanel').removeClass('d-none');
            }
        });
    }
    
    // Click Search replacement item
    $(document).on('click', '.repl-search-result-item', function(e) {
        e.preventDefault();
        const item = {
            product_id: $(this).data('id'),
            name: $(this).data('name'),
            barcode: $(this).data('barcode'),
            selling_price: parseFloat($(this).data('price')),
            quantity: 1,
            stock_qty: parseInt($(this).data('stock')) || 0
        };
        
        addReplacementItemToCart(item);
        $('#replacementSearchInput').val('');
        $('#replSearchResultsPanel').addClass('d-none');
    });

    function addReplacementItemToCart(item) {
        if (item.stock_qty <= 0) {
            showToast(`Product "${item.name}" stock mein nahi hai (Out of stock)!`, 'warning');
            return;
        }
        
        const existing = replacementCart.find(r => r.product_id == item.product_id);
        if (existing) {
            if (existing.quantity + 1 > item.stock_qty) {
                showToast(`Stock limit reached! Available inventory: ${item.stock_qty}`, 'warning');
                return;
            }
            existing.quantity += 1;
        } else {
            replacementCart.push(item);
        }
        
        renderReplacementCart();
    }
    
    // Close floating search panel on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#replSearchGroup').length) {
            $('#replSearchResultsPanel').addClass('d-none');
        }
    });
    
    function renderReplacementCart() {
        $('#replItemCountBadge').text(`${replacementCart.length} Items`);
        
        if (replacementCart.length === 0) {
            $('#replacementCartTableBody').html(`
                <tr class="repl-empty-row">
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="fas fa-barcode me-1"></i> Add replacement products by searching or scanning barcode above.
                    </td>
                </tr>
            `);
            recalculateExchangeTotals();
            return;
        }
        
        let html = '';
        replacementCart.forEach(function(item, index) {
            const lineTotal = item.quantity * item.selling_price;
            html += `
                <tr data-index="${index}">
                    <td>
                        <strong class="text-dark">${item.name}</strong><br>
                        <small class="text-muted">${item.barcode}</small>
                    </td>
                    <td class="text-end font-monospace">Rs. ${item.selling_price.toFixed(2)}</td>
                    <td class="text-center">
                        <div class="qty-stepper">
                            <button type="button" class="btn-qty-step btn-repl-dec" title="Kam karein (-)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="qty-stepper-input repl-qty-input font-monospace" 
                                value="${item.quantity}" min="1" max="${item.stock_qty}">
                            <button type="button" class="btn-qty-step btn-repl-inc" title="Barhayein (+)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-end font-monospace fw-bold text-danger">Rs. ${lineTotal.toFixed(2)}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-link text-danger p-0 btn-remove-repl-item" type="button" title="Delete">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#replacementCartTableBody').html(html);
        recalculateExchangeTotals();
    }
    
    // Stepper Button Handlers for Replacement Cart Qty
    $(document).on('click', '.btn-repl-dec', function(e) {
        e.preventDefault();
        const index = $(this).closest('tr').data('index');
        const item = replacementCart[index];
        if (item.quantity > 1) {
            item.quantity -= 1;
            renderReplacementCart();
        }
    });

    $(document).on('click', '.btn-repl-inc', function(e) {
        e.preventDefault();
        const index = $(this).closest('tr').data('index');
        const item = replacementCart[index];
        if (item.quantity < item.stock_qty) {
            item.quantity += 1;
            renderReplacementCart();
        } else {
            showToast(`Stock limit reached! Available: ${item.stock_qty}`, 'warning');
        }
    });

    // Qty edit in replacement cart
    $(document).on('input change', '.repl-qty-input', function() {
        const index = $(this).closest('tr').data('index');
        const item = replacementCart[index];
        let val = parseInt($(this).val());
        
        if (isNaN(val) || val < 1) {
            val = 1;
            $(this).val(1);
        } else if (val > item.stock_qty) {
            val = item.stock_qty;
            $(this).val(item.stock_qty);
            showToast(`Stock limit reached! Available: ${item.stock_qty}`, 'warning');
        }
        item.quantity = val;
        renderReplacementCart();
    });
    
    // Remove item from replacement cart
    $(document).on('click', '.btn-remove-repl-item', function() {
        const index = $(this).closest('tr').data('index');
        replacementCart.splice(index, 1);
        renderReplacementCart();
    });
    
    let currentExchangePayload = null;

    function recalculateExchangeTotals() {
        // Compute return credits
        let totalReturnCredit = 0.00;
        $('.exch-return-qty-input').each(function() {
            const q = parseInt($(this).val()) || 0;
            const uv = parseFloat($(this).data('unit-value'));
            totalReturnCredit += (q * uv);
        });
        
        // Compute replacement charge
        const totalReplacementCharge = replacementCart.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
        
        const netDiff = totalReplacementCharge - totalReturnCredit;
        
        $('#exchReturnedCreditText').text(`Rs. ${totalReturnCredit.toFixed(2)}`);
        $('#exchReplacedChargeText').text(`Rs. ${totalReplacementCharge.toFixed(2)}`);
        
        if (netDiff > 0) {
            $('#exchNetDifferenceText').text(`+Rs. ${netDiff.toFixed(2)}`).addClass('text-success').removeClass('text-danger text-secondary text-muted');
            $('#exchCalculationDetail').text("Grahak se lene hain (Customer pays to shop)").addClass('text-success').removeClass('text-danger text-secondary text-muted');
            $('#exch_settlement_method').prop('disabled', false);
        } else if (netDiff < 0) {
            const absDiff = Math.abs(netDiff);
            $('#exchNetDifferenceText').text(`-Rs. ${absDiff.toFixed(2)}`).addClass('text-danger').removeClass('text-success text-secondary text-muted');
            $('#exchCalculationDetail').text("Grahak ko wapis dene hain (Shop refunds customer)").addClass('text-danger').removeClass('text-success text-secondary text-muted');
            $('#exch_settlement_method').prop('disabled', false);
        } else {
            $('#exchNetDifferenceText').text(`Rs. 0.00`).addClass('text-secondary').removeClass('text-danger text-success text-muted');
            $('#exchCalculationDetail').text("Barabar tabdili (Even exchange)").addClass('text-secondary').removeClass('text-danger text-success text-muted');
            $('#exch_settlement_method').prop('disabled', true);
        }
    }
    
    // Submit Exchange request -> Opens professional confirmation modal
    $('#submitExchangeBtn').on('click', function() {
        const saleId = $('#exchange_sale_id').val();
        
        // Build returned array
        let returnedItemsArr = [];
        let totalReturnQty = 0;
        let totalReturnCredit = 0.00;
        $('.exch-return-qty-input').each(function() {
            const q = parseInt($(this).val()) || 0;
            const itemId = $(this).data('item-id');
            const uv = parseFloat($(this).data('unit-value')) || 0;
            if (q > 0) {
                returnedItemsArr.push({
                    sale_item_id: itemId,
                    qty_returned: q
                });
                totalReturnQty += q;
                totalReturnCredit += (q * uv);
            }
        });
        
        if (returnedItemsArr.length === 0) {
            showToast("Baraye meharbani wapis ki jane wali kam az kam ek product select karein (Step 1).", "warning");
            return;
        }
        
        if (replacementCart.length === 0) {
            showToast("Baraye meharbani badle mein li jane wali kam az kam ek product cart mein add karein (Step 2).", "warning");
            return;
        }
        
        let totalReplaceQty = 0;
        let totalReplacementCharge = 0.00;
        replacementCart.forEach(item => {
            totalReplaceQty += item.quantity;
            totalReplacementCharge += (item.quantity * item.selling_price);
        });

        const netDiff = totalReplacementCharge - totalReturnCredit;
        const settlement = $('#exch_settlement_method').val();
        const settlementLabel = $('#exch_settlement_method option:selected').text();

        // Populate Confirmation Modal
        $('#confModalReturnQty').text(`${totalReturnQty} item(s)`);
        $('#confModalReplaceQty').text(`${totalReplaceQty} item(s)`);
        $('#confModalReturnCredit').text(`Rs. ${totalReturnCredit.toFixed(2)}`);
        $('#confModalReplaceCharge').text(`Rs. ${totalReplacementCharge.toFixed(2)}`);
        $('#confModalPayMethod').text(settlementLabel.toUpperCase());

        const $diffBox = $('#confModalDiffBox');
        $diffBox.removeClass('bg-success-subtle border-success text-success bg-danger-subtle border-danger text-danger bg-light border-secondary text-secondary');

        if (netDiff > 0) {
            $diffBox.addClass('bg-success-subtle border-success text-success');
            $('#confModalDiffTitle').text('Grahak se lene hain (Customer Pays to Shop)');
            $('#confModalDiffAmount').text(`+Rs. ${netDiff.toFixed(2)}`);
            $('#confModalDiffSubtitle').html('<i class="fas fa-arrow-down me-1"></i> Cashier grahak se raqam wasool karein');
        } else if (netDiff < 0) {
            const absDiff = Math.abs(netDiff);
            $diffBox.addClass('bg-danger-subtle border-danger text-danger');
            $('#confModalDiffTitle').text('Grahak ko wapis dene hain (Shop Refunds Customer)');
            $('#confModalDiffAmount').text(`-Rs. ${absDiff.toFixed(2)}`);
            $('#confModalDiffSubtitle').html('<i class="fas fa-arrow-up me-1"></i> Cashier grahak ko raqam wapis ada karein');
        } else {
            $diffBox.addClass('bg-light border-secondary text-secondary');
            $('#confModalDiffTitle').text('Barabar Tabdili (Even Exchange)');
            $('#confModalDiffAmount').text('Rs. 0.00');
            $('#confModalDiffSubtitle').text('Koi izafi raqam lena ya dena nahi hai');
        }

        const replacedItemsArr = replacementCart.map(item => ({
            product_id: item.product_id,
            qty_replaced: item.quantity
        }));

        currentExchangePayload = {
            sale_id: saleId,
            settlement_method: settlement,
            returned_items: returnedItemsArr,
            replaced_items: replacedItemsArr
        };

        const confModal = new bootstrap.Modal(document.getElementById('modalConfirmExchange'));
        confModal.show();
    });

    // Final Execution Handler inside Confirmation Modal
    $('#btnExecuteExchangeFinal').on('click', function() {
        if (!currentExchangePayload) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

        $.ajax({
            url: (window.BASE_URL || '') + '/api/sales.php?action=exchange',
            type: 'POST',
            data: currentExchangePayload,
            dataType: 'json',
            success: function(response) {
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Tasdeeq Karein (Confirm)');
                if (response.success) {
                    const confModalEl = document.getElementById('modalConfirmExchange');
                    const confModal = bootstrap.Modal.getInstance(confModalEl);
                    if (confModal) confModal.hide();

                    const newExchEl = document.getElementById('newExchangeModal');
                    const newExchModal = bootstrap.Modal.getInstance(newExchEl);
                    if (newExchModal) newExchModal.hide();

                    $('#exchangeWorkspace').addClass('d-none');
                    $('#exchangeBillInput').val('');
                    currentExchangePayload = null;
                    loadExchanges();

                    showToast("Exchange transaction ba-kamyabi mukammal ho gayi!", "success");
                    renderAndShowExchangeReceipt(response.voucher);
                } else {
                    showToast(response.message || 'Error processing exchange.', 'danger');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Tasdeeq Karein (Confirm)');
                showToast(xhr.responseJSON?.message || 'Error processing exchange.', 'danger');
            }
        });
    });
    
    // Thermal Receipt Rendering & Modal Show
    function renderAndShowExchangeReceipt(voucherOrId) {
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/sales.php?action=get_exchange&voucher=${encodeURIComponent(voucherOrId)}`,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.exchange) {
                    const ex = res.exchange;
                    const origItems = res.original_items || [];
                    const retItems = res.returned_items || [];
                    const repItems = res.replaced_items || [];
                    
                    const origTotal = parseFloat(ex.original_total) || 0.00;
                    const retCredit = parseFloat(ex.returned_value) || 0.00;
                    const repCharge = parseFloat(ex.replaced_value) || 0.00;
                    const diff = parseFloat(ex.net_difference);
                    const revisedBillTotal = origTotal - retCredit + repCharge;

                    let diffSign = diff > 0 ? '+' : (diff < 0 ? '-' : '');
                    let settlementActionText = '';
                    if (diff > 0) {
                        settlementActionText = `Customer Paid: +Rs. ${diff.toFixed(2)}`;
                    } else if (diff < 0) {
                        settlementActionText = `Shop Refunded: -Rs. ${Math.abs(diff).toFixed(2)}`;
                    } else {
                        settlementActionText = `Even Exchange (Zero Balance)`;
                    }

                    // 1. Build Original Bill Items Rows (with audit status of what was returned vs kept)
                    let origRowsHtml = '';
                    origItems.forEach(function(item, idx) {
                        const retEntry = retItems.find(r => r.sale_item_id == item.id);
                        let statusTag = '';
                        let displayQty = parseInt(item.quantity);
                        let displayRate = parseFloat(item.unit_price).toFixed(2);
                        let displayNet = parseFloat(item.net_amount).toFixed(2);

                        if (retEntry) {
                            const retQty = parseInt(retEntry.quantity);
                            if (retQty >= displayQty) {
                                statusTag = '<span style="font-weight: bold; color: #b91c1c;">[EXCHANGED / WAPIS]</span>';
                            } else {
                                statusTag = `<span style="font-weight: bold; color: #b91c1c;">[${retQty} Wapis, ${displayQty - retQty} Paas Hai]</span>`;
                            }
                        } else {
                            statusTag = '<span style="color: #15803d; font-weight: bold;">[KEPT / PAAS HAI]</span>';
                        }

                        origRowsHtml += `
                            <tr>
                                <td colspan="4" style="padding-top: 4px; font-weight: bold; font-size: 10.5px;">
                                    ${idx + 1}. ${item.product_name}
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px dotted #000;">
                                <td style="padding-bottom: 3px; font-size: 9px;">${item.barcode} ${statusTag}</td>
                                <td align="center" style="padding-bottom: 3px;">${displayQty}</td>
                                <td class="right" style="padding-bottom: 3px;">${displayRate}</td>
                                <td class="right" style="padding-bottom: 3px; font-weight: bold;">${displayNet}</td>
                            </tr>
                        `;
                    });

                    // 2. Build Replacement Items Rows
                    let repRowsHtml = '';
                    repItems.forEach(function(item, idx) {
                        const repQty = parseInt(item.quantity);
                        const charged = parseFloat(item.value_charged);
                        const rate = (charged / repQty).toFixed(2);

                        repRowsHtml += `
                            <tr>
                                <td colspan="4" style="padding-top: 4px; font-weight: bold; font-size: 10.5px;">
                                    + ${idx + 1}. ${item.product_name}
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px dotted #000;">
                                <td style="padding-bottom: 3px; font-size: 9px; font-weight: bold;">${item.barcode} [NEW / BADLE MEIN]</td>
                                <td align="center" style="padding-bottom: 3px;">${repQty}</td>
                                <td class="right" style="padding-bottom: 3px;">${rate}</td>
                                <td class="right" style="padding-bottom: 3px; font-weight: bold;">${charged.toFixed(2)}</td>
                            </tr>
                        `;
                    });

                    const receiptHtml = `
                        <div class="center" style="margin-bottom: 6px; color: #000;">
                            <img src="${(window.BASE_URL || "")}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="${window.STORE_NAME || 'One Dollar Shop'}" class="receipt-logo">
                            <h3 style="margin: 0; font-size: 16.5px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase; color: #000;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h3>
                            <p style="margin: 2px 0; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #000;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'}</p>
                            <div style="font-size: 12px; font-weight: 900; margin-top: 2px; color: #000;">Ph: ${window.STORE_PHONE || '0307-2681893'}</div>
                            <div style="font-size: 11px; font-weight: 900; margin-top: 4px; border: 1.5px solid #000; padding: 2px 6px; display: inline-block; letter-spacing: 0.5px; text-transform: uppercase; color: #000;">
                                *** PRODUCT EXCHANGE INVOICE ***
                            </div>
                        </div>
                        <div class="receipt-divider"></div>
                        <table style="width: 100%; font-size: 10.5px; font-weight: 800; line-height: 1.4; color: #000;">
                            <tr>
                                <td><strong>Exch Voucher:</strong> <span style="font-weight: 900;">${ex.exchange_voucher}</span></td>
                                <td class="right"><strong>Type:</strong> <span style="font-weight: 900;">${ex.settlement_method.toUpperCase()} EXCH</span></td>
                            </tr>
                            <tr>
                                <td><strong>Original Bill:</strong> <span style="font-weight: 900;">${ex.bill_number}</span></td>
                                <td class="right"><strong>Cashier:</strong> ${ex.cashier_name}</td>
                            </tr>
                            <tr>
                                <td><strong>Exch Date:</strong> ${ex.created_at}</td>
                                <td class="right"><strong>Orig Date:</strong> ${ex.original_bill_date || ''}</td>
                            </tr>
                        </table>
                        <div class="receipt-divider"></div>

                        <!-- SECTION 1: ORIGINAL INVOICE ITEMS (Asal Kharidari) -->
                        <div style="font-size: 11px; font-weight: 900; margin: 3px 0 2px 0; text-transform: uppercase; color: #000;">
                            1. Original Bill Items (Asal Kharidari):
                        </div>
                        <table cellpadding="0" cellspacing="0" style="width: 100%; font-size: 10.5px; line-height: 1.3; color: #000;">
                            <thead>
                                <tr style="border-bottom: 1.5px dashed #000;">
                                    <th align="left" style="padding-bottom: 3px; font-weight: 900; color: #000;">ITEM</th>
                                    <th align="center" style="padding-bottom: 3px; width: 35px; font-weight: 900; color: #000;">QTY</th>
                                    <th class="right" style="padding-bottom: 3px; width: 50px; font-weight: 900; color: #000;">RATE</th>
                                    <th class="right" style="padding-bottom: 3px; width: 55px; font-weight: 900; color: #000;">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${origRowsHtml}
                            </tbody>
                        </table>
                        <div class="receipt-divider"></div>

                        <!-- SECTION 2: NEW REPLACEMENT ITEMS (Badle Mein Nayi Ashya) -->
                        <div style="font-size: 11px; font-weight: 900; margin: 3px 0 2px 0; text-transform: uppercase; color: #000;">
                            2. Replacement Items (Badle Mein Li Gayi):
                        </div>
                        <table cellpadding="0" cellspacing="0" style="width: 100%; font-size: 10.5px; line-height: 1.3; color: #000;">
                            <thead>
                                <tr style="border-bottom: 1.5px dashed #000;">
                                    <th align="left" style="padding-bottom: 3px; font-weight: 900; color: #000;">ITEM</th>
                                    <th align="center" style="padding-bottom: 3px; width: 35px; font-weight: 900; color: #000;">QTY</th>
                                    <th class="right" style="padding-bottom: 3px; width: 50px; font-weight: 900; color: #000;">RATE</th>
                                    <th class="right" style="padding-bottom: 3px; width: 55px; font-weight: 900; color: #000;">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${repRowsHtml}
                            </tbody>
                        </table>
                        <div class="receipt-divider"></div>

                        <!-- SECTION 3: COMPLETE FINANCIAL RECONCILIATION -->
                        <table style="width: 100%; font-size: 11px; font-weight: 800; line-height: 1.4; color: #000;">
                            <tr>
                                <td style="font-weight: 800;">Original Bill Amount:</td>
                                <td class="right" style="font-weight: 900;">Rs. ${origTotal.toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 800;">Less: Wapis Ki Gayi Credit:</td>
                                <td class="right" style="font-weight: 900;">-Rs. ${retCredit.toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 800;">Add: Badle Mein Li Gayi Items:</td>
                                <td class="right" style="font-weight: 900;">+Rs. ${repCharge.toFixed(2)}</td>
                            </tr>
                            <tr style="font-size: 13.5px; font-weight: 900; border-top: 2px dashed #000; border-bottom: 2px dashed #000;">
                                <td style="padding: 5px 0;">NET FARAQ (DIFFERENCE):</td>
                                <td class="right" style="padding: 5px 0;">${diffSign}Rs. ${Math.abs(diff).toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td style="padding-top: 4px; font-weight: 800;"><strong>Settlement Action:</strong></td>
                                <td class="right" style="padding-top: 4px; font-weight: 900;"><strong>${settlementActionText}</strong></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 800;">Payment Mode:</td>
                                <td class="right" style="font-weight: 900;"><strong>${ex.settlement_method.toUpperCase()}</strong></td>
                            </tr>
                            <tr style="font-size: 13.5px; font-weight: 900; border-top: 2px solid #000;">
                                <td style="padding: 5px 0;">REVISED NET BILL VALUE:</td>
                                <td class="right" style="padding: 5px 0;">Rs. ${revisedBillTotal.toFixed(2)}</td>
                            </tr>
                        </table>
                        <div class="receipt-divider"></div>

                        <!-- Store Policy Footer -->
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

                    $('#exchangeVoucherPrintContainer').html(receiptHtml);
                    const exModal = new bootstrap.Modal(document.getElementById('exchangeVoucherModal'));
                    exModal.show();
                } else {
                    showToast("Failed to load exchange receipt details.", "danger");
                }
            },
            error: function() {
                showToast("Failed to connect to server for exchange receipt.", "danger");
            }
        });
    }

    // Print Receipt Trigger
    $('#btnPrintExchangeVoucher').on('click', function() {
        const html = $('#exchangeVoucherPrintContainer').html();
        if (window.printThermalReceipt) {
            window.printThermalReceipt(html);
        } else {
            const printWindow = window.open('', '', 'width=450,height=650');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                    <head>
                        <title>Print Exchange Voucher</title>
                        <link rel="stylesheet" href="${(window.BASE_URL || "")}/assets/css/bootstrap.min.css">
                        <style>
                            body { font-family: Arial, "Helvetica Neue", Helvetica, sans-serif; padding: 10px; margin: 0; color: #000; font-weight: 700; }
                            @media print { .no-print { display: none !important; } }
                        </style>
                    </head>
                    <body onload="window.print(); window.close();">
                        ${html}
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
    });

    // Reprint voucher button click on Exchange Transactions Log table
    $(document).on('click', '.btn-reprint-exchange-voucher', function() {
        const voucher = $(this).data('voucher');
        renderAndShowExchangeReceipt(voucher);
    });
    
    function loadExchanges() {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/sales.php?action=list_exchanges',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (response.exchanges.length === 0) {
                        html = '<tr><td colspan="9" class="text-center py-4 text-muted">No processed exchanges found.</td></tr>';
                    } else {
                        response.exchanges.forEach(function(ex) {
                            const diffVal = parseFloat(ex.net_difference);
                            let diffDisplay = '';
                            if (diffVal > 0) {
                                diffDisplay = `<span class="text-success fw-bold">+Rs. ${diffVal.toFixed(2)}</span>`;
                            } else if (diffVal < 0) {
                                diffDisplay = `<span class="text-danger fw-bold">-Rs. ${Math.abs(diffVal).toFixed(2)}</span>`;
                            } else {
                                diffDisplay = '<span class="text-muted">Rs. 0.00</span>';
                            }
                            
                            html += `
                                <tr>
                                    <td><code>${ex.exchange_voucher}</code></td>
                                    <td><strong>${ex.bill_number}</strong></td>
                                    <td>${ex.created_at}</td>
                                    <td>${ex.cashier_name}</td>
                                    <td class="text-end font-monospace">Rs. ${parseFloat(ex.returned_value).toFixed(2)}</td>
                                    <td class="text-end font-monospace">Rs. ${parseFloat(ex.replaced_value).toFixed(2)}</td>
                                    <td class="text-end font-monospace">${diffDisplay}</td>
                                    <td class="text-uppercase"><span class="badge bg-light text-dark border">${ex.settlement_method}</span></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-reprint-exchange-voucher" data-voucher="${ex.exchange_voucher}" title="Print Receipt">
                                            <i class="fas fa-print me-1"></i> Receipt
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#exchangesTableBody').html(html);
                } else {
                    $('#exchangesTableBody').html('<tr><td colspan="9" class="text-center text-danger py-4">Failed to load exchanges.</td></tr>');
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
