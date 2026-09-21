<?php
require_once __DIR__ . '/../config/auth.php';
if (!isAdmin()) {
    redirect('/pos/index.php');
}
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card mb-4 d-print-none">
    <div class="card-body">
        <div id="filterForm" class="row g-2 align-items-center">
            <div class="col-12 col-md-5 col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-barcode text-danger me-1"></i><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control" id="searchInput" placeholder="Scan barcode or search name/code..." autocomplete="off">
                    <button type="button" class="btn btn-outline-secondary d-none" id="btnClearSearch" title="Clear Search"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                <select class="form-select" id="categoryFilter">
                    <option value="0">All Categories</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4 col-lg-4 text-end d-flex justify-content-sm-end justify-content-stretch gap-2">
                <a href="<?= url('/reports/product_velocity.php') ?>" class="btn btn-outline-danger text-nowrap flex-grow-1 flex-sm-grow-0" title="Analyze Dead Stock & Bestsellers"><i class="fas fa-cubes-stacked me-1"></i> Velocity</a>
                <button type="button" class="btn btn-primary text-nowrap flex-grow-1 flex-sm-grow-0" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="fas fa-plus me-1"></i> Add Product</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Product Catalog</h5>
        <div class="text-muted" style="font-size: 0.85rem;" id="paginationSummary">Showing 0 of 0 products</div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Barcode</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th class="text-end">Cost Price</th>
                        <th class="text-end">Selling Price</th>
                        <th class="text-center">Stock Quantity</th>
                        <th class="text-center">POS Visible</th>
                        <th class="text-end" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-5">Loading products...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center d-print-none">
        <button class="btn btn-sm btn-outline-secondary" id="prevPageBtn" disabled>Previous</button>
        <button class="btn btn-sm btn-outline-secondary" id="nextPageBtn" disabled>Next</button>
    </div>
</div>

<!-- Stock History Modal -->
<div class="modal fade d-print-none" id="stockHistoryModal" tabindex="-1" aria-labelledby="stockHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockHistoryModalLabel"><i class="fas fa-clock-rotate-left me-2"></i>Stock Movement Ledger</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom">
                    <h6 class="mb-1" id="historyProductName">Product Name</h6>
                    <small class="text-muted" id="historyProductBarcode">Barcode: </small>
                </div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Date/Time</th>
                                <th>Movement Type</th>
                                <th class="text-center">Qty Shift</th>
                                <th>Performed By</th>
                                <th>Reason/Notes</th>
                            </tr>
                        </thead>
                        <tbody id="stockHistoryTableBody">
                            <tr>
                                <td colspan="5" class="text-center py-4">No movement history found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade d-print-none" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel"><i class="fas fa-plus-circle me-2 text-danger"></i>Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addProductForm">
                <div class="modal-body">
                    <div id="addAlert" class="alert alert-danger d-none"></div>
                    
                    <div class="row g-3">
                        <!-- Product Name -->
                        <div class="col-md-12">
                            <label for="modal_name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="modal_name" name="name" required placeholder="e.g. Glass Bowl Set">
                        </div>
                        
                        <!-- Barcode Field with Generate button -->
                        <div class="col-md-6">
                            <label for="modal_barcode" class="form-label">Barcode (Scan or Leave Empty to Generate)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="modal_barcode" name="barcode" placeholder="Manufacturer Barcode">
                                <button type="button" class="btn btn-outline-secondary" id="generateBarcodeBtn" title="Generate Barcode"><i class="fas fa-barcode"></i> Generate</button>
                                <button type="button" class="btn btn-outline-info" id="printModalBarcodeBtn" data-bs-toggle="modal" data-bs-target="#printBarcodeModal" title="Print Barcode Label"><i class="fas fa-print"></i> Print</button>
                            </div>
                            <small class="text-muted">Supports EAN-13, UPC-A, Code-128, etc.</small>
                            <div id="modalBarcodeFeedback" class="mt-1 small d-none"></div>
                        </div>
                        
                        <!-- Category with Inline Add Button -->
                        <div class="col-md-6">
                            <label for="modal_category_id" class="form-label">Category</label>
                            <div class="input-group">
                                <select class="form-select" id="modal_category_id" name="category_id">
                                    <option value="">Uncategorized</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="toggleQuickCatBtn" title="Quick Add Category"><i class="fas fa-plus"></i></button>
                            </div>
                            <div id="quickCategoryContainer" class="d-none mt-2 p-2 border rounded bg-light">
                                <label class="form-label small mb-1 fw-bold text-secondary">Quick Category Name</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control form-control-sm" id="quick_cat_name" placeholder="Category name...">
                                    <button class="btn btn-primary btn-sm" type="button" id="saveQuickCategoryBtn">Create</button>
                                </div>
                                <div id="quickCatAlert" class="alert alert-danger p-1 mt-1 small mb-0 d-none"></div>
                            </div>
                        </div>
                        
                        <!-- Cost Price -->
                        <div class="col-md-4">
                            <label for="modal_cost_price" class="form-label">Cost Price (Rs.) *</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" class="form-control" id="modal_cost_price" name="cost_price" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        
                        <!-- Selling Price -->
                        <div class="col-md-4">
                            <label for="modal_selling_price" class="form-label">Selling Price (Rs.) *</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" class="form-control" id="modal_selling_price" name="selling_price" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        
                        <!-- Initial Stock -->
                        <div class="col-md-4">
                            <label for="modal_quantity" class="form-label">Initial Stock Qty</label>
                            <input type="number" class="form-control" id="modal_quantity" name="quantity" min="0" value="0">
                        </div>
                        
                        <!-- POS Visibility -->
                        <div class="col-md-6">
                            <div class="form-check mt-4 pt-2">
                                <input class="form-check-input" type="checkbox" id="modal_show_in_pos" name="show_in_pos" value="1" checked>
                                <label class="form-check-label fw-bold" for="modal_show_in_pos">
                                    Show in POS Cart Search
                                </label>
                            </div>
                        </div>
                        
                        <!-- Low Stock Threshold -->
                        <div class="col-md-6">
                            <label for="modal_low_stock_threshold" class="form-label">Low Stock Threshold *</label>
                            <input type="number" class="form-control" id="modal_low_stock_threshold" name="low_stock_threshold" min="1" value="10" required>
                        </div>
                        
                        <!-- Description -->
                        <div class="col-md-12">
                            <label for="modal_description" class="form-label">Description / Shelf Location / Notes</label>
                            <textarea class="form-control" id="modal_description" name="description" rows="3" placeholder="Optional details..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveProductBtn">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Print Barcode Modal -->
<div class="modal fade d-print-none" id="printBarcodeModal" tabindex="-1" aria-labelledby="printBarcodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-dark">
            <div class="modal-header">
                <h5 class="modal-title" id="printBarcodeModalLabel"><i class="fas fa-barcode me-2 text-info"></i>Print Product Barcode Sticker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3 text-center">
                    <label class="form-label fw-bold text-secondary d-block">Sticker Label Preview (50mm x 25mm)</label>
                    <div id="barcodePreviewCard" class="d-inline-block p-2 border rounded bg-white text-dark text-center" style="width: 250px; font-family: Arial, sans-serif; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <div id="previewLabelStore" class="fw-bold mb-1 text-uppercase text-dark" style="font-size: 10px; font-weight: 900 !important; letter-spacing: 0.05em;"><?= defined('STORE_NAME') ? strtoupper(STORE_NAME) : 'ONE DOLLAR SHOP' ?></div>
                        <div id="previewLabelName" class="text-truncate fw-semibold mb-1" style="font-size: 11px; font-weight: 800 !important;">Product Name</div>
                        <div class="d-flex justify-content-center my-2">
                            <svg id="previewBarcodeSVG" style="max-height: 50px; width: 100%;"></svg>
                        </div>
                        <div id="previewLabelPrice" class="fw-bold" style="font-size: 12px; font-weight: 900 !important; color: #000000;">Rs. 0.00</div>
                    </div>
                </div>
                
                <div class="mb-3 text-start">
                    <label for="barcodePrintCopies" class="form-label fw-bold">Number of Copies to Print</label>
                    <input type="number" class="form-control" id="barcodePrintCopies" min="1" value="1" required>
                    <small class="text-muted">Enter how many label stickers you want to print for this product.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info text-white fw-bold" id="triggerBarcodePrintBtn"><i class="fas fa-print me-1"></i> Print Label</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let limit = 20;
    let offset = 0;
    let total = 0;
    
    // Load categories filter
    $.ajax({
        url: (window.BASE_URL || '') + '/api/products.php?action=get_categories&status=active',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                response.categories.forEach(function(cat) {
                    $('#categoryFilter').append(`<option value="${cat.id}">${cat.name}</option>`);
                });
            }
        }
    });
    
    loadProducts();
    
    // Auto-focus search input so barcode scanner is immediately ready on page load
    $('#searchInput').focus();

    let searchTimer = null;
    
    // Comprehensive barcode scanner Enter protection on keydown, keypress, and keyup
    $('#searchInput').on('keydown keypress keyup', function(e) {
        if (e.which === 13 || e.which === 10 || e.keyCode === 13 || e.keyCode === 10 || e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            if (e.type === 'keydown') {
                clearTimeout(searchTimer);
                offset = 0;
                loadProducts();
            }
            return false;
        }
    });

    $('#searchInput').on('input', function() {
        const val = $(this).val().trim();
        $('#btnClearSearch').toggleClass('d-none', val.length === 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            offset = 0;
            loadProducts();
        }, 200);
    });

    $('#btnClearSearch').on('click', function() {
        $('#searchInput').val('').focus();
        $(this).addClass('d-none');
        offset = 0;
        loadProducts();
    });

    // Global Hardware Barcode Scanner Listener for Inventory page
    // Automatically captures barcode even if user didn't click inside the search box first!
    let invScanBuffer = '';
    let invLastKeyTime = 0;
    
    $(document).on('keydown', function(e) {
        // If a modal is open, let modal handle it
        if ($('.modal.show').length > 0) return;
        
        const now = Date.now();
        const target = e.target;
        const isSearchField = $(target).is('#searchInput');
        
        if (e.which === 13 || e.which === 10 || e.key === 'Enter') {
            if (!isSearchField && invScanBuffer.trim().length >= 2) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                const code = invScanBuffer.trim();
                invScanBuffer = '';
                $('#searchInput').val(code).focus();
                $('#btnClearSearch').removeClass('d-none');
                offset = 0;
                loadProducts();
                return false;
            }
            invScanBuffer = '';
            return;
        }
        
        // Rapid keystroke collection (hardware scanners deliver keystrokes < 65ms apart)
        if (now - invLastKeyTime > 65) {
            invScanBuffer = '';
        }
        if (e.key && e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
            invScanBuffer += e.key;
            invLastKeyTime = now;
        }
    });
    
    $('#categoryFilter').on('change', function() {
        offset = 0;
        loadProducts();
    });
    
    // Pagination clicks
    $('#prevPageBtn').on('click', function() {
        if (offset > 0) {
            offset -= limit;
            loadProducts();
        }
    });
    
    $('#nextPageBtn').on('click', function() {
        if (offset + limit < total) {
            offset += limit;
            loadProducts();
        }
    });
    
    // View stock history click
    $(document).on('click', '.view-stock-history', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const barcode = $(this).data('barcode');
        
        $('#historyProductName').text(name);
        $('#historyProductBarcode').html(`<strong>Barcode:</strong> ${barcode}`);
        $('#stockHistoryTableBody').html('<tr><td colspan="5" class="text-center py-4">Loading history...</td></tr>');
        
        const historyModal = new bootstrap.Modal(document.getElementById('stockHistoryModal'));
        historyModal.show();
        
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/reports.php?action=product_history&product_id=${id}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.history.length > 0) {
                    let html = '';
                    response.history.forEach(function(row) {
                        let badgeClass = 'bg-secondary';
                        let qtyPrefix = '';
                        if (row.quantity > 0) {
                            badgeClass = 'bg-success';
                            qtyPrefix = '+';
                        } else if (row.quantity < 0) {
                            badgeClass = 'bg-danger';
                        }
                        
                        // Map type keys to readable labels
                        const typeMap = {
                            'sale': 'Sale Deduct',
                            'refund': 'Refund Restock',
                            'exchange_out': 'Exchange Deduct',
                            'exchange_in': 'Exchange Restock',
                            'manual_add': 'Admin Added',
                            'manual_remove': 'Admin Removed',
                            'void_reversal': 'Void Reversal'
                        };
                        const readableType = typeMap[row.movement_type] || row.movement_type;
                        
                        // Format reason with intuitive visual styling
                        let reasonHtml = '<span class="text-muted">N/A</span>';
                        if (row.reason) {
                            const rawReason = String(row.reason);
                            if (rawReason === 'Damage') {
                                reasonHtml = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold"><i class="fas fa-trash-can me-1"></i>Damage</span>`;
                            } else if (rawReason === 'New Stock') {
                                reasonHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><i class="fas fa-boxes-packing me-1"></i>New Stock</span>`;
                            } else if (rawReason.startsWith('Other')) {
                                reasonHtml = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold"><i class="fas fa-pen-to-square me-1"></i>${escapeHtml(rawReason)}</span>`;
                            } else {
                                reasonHtml = escapeHtml(rawReason);
                            }
                        }
                        
                        html += `
                            <tr>
                                <td>${row.created_at}</td>
                                <td><span class="badge ${badgeClass}">${readableType}</span></td>
                                <td class="text-center fw-bold text-dark">${qtyPrefix}${row.quantity}</td>
                                <td>${row.username}</td>
                                <td>${reasonHtml}</td>
                            </tr>
                        `;
                    });
                    $('#stockHistoryTableBody').html(html);
                } else {
                    $('#stockHistoryTableBody').html('<tr><td colspan="5" class="text-center py-4">No stock movements recorded for this item.</td></tr>');
                }
            },
            error: function() {
                $('#stockHistoryTableBody').html('<tr><td colspan="5" class="text-center text-danger py-4">Failed to load history ledger.</td></tr>');
            }
        });
    });
    
    // HTML & Attribute sanitizers to prevent syntax crashes on special chars / quotes
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

    function loadProducts() {
        const search = $('#searchInput').val();
        const category_id = $('#categoryFilter').val();
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=list',
            type: 'GET',
            data: {
                search: search,
                category_id: category_id,
                limit: limit,
                offset: offset
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    total = parseInt(response.total, 10) || 0;
                    let html = '';
                    if (!response.products || response.products.length === 0) {
                        html = '<tr><td colspan="8" class="text-center py-5 text-muted">No products found matching filters.</td></tr>';
                    } else {
                        response.products.forEach(function(p) {
                            const showInPosBadge = p.show_in_pos == 1 
                                ? '<span class="badge bg-success-subtle text-success border border-success">Yes</span>' 
                                : '<span class="badge bg-warning-subtle text-warning border border-warning">No</span>';
                            
                            const isExactMatch = search && p.barcode && p.barcode.toString().toLowerCase() === search.trim().toLowerCase();
                            const rowClass = isExactMatch ? 'table-success border-success' : '';
                            
                            const catName = p.category_name ? escapeHtml(p.category_name) : '<span class="text-muted fst-italic">Uncategorized</span>';
                            const pName = escapeHtml(p.name);
                            const pBarcode = escapeHtml(p.barcode || 'N/A');
                            const pPrice = parseFloat(p.selling_price || 0).toFixed(2);
                            const pCost = parseFloat(p.cost_price || 0).toFixed(2);
                            const pQty = parseInt(p.quantity, 10) || 0;
                            const qtyColor = pQty <= 5 ? 'text-danger' : 'text-dark';

                            html += `
                                <tr class="${rowClass}">
                                    <td>
                                        <a href="javascript:void(0)" class="text-decoration-none font-monospace fw-bold text-danger" 
                                           data-bs-toggle="modal" data-bs-target="#printBarcodeModal"
                                           data-name="${escapeAttr(p.name)}" 
                                           data-barcode="${escapeAttr(p.barcode)}" 
                                           data-price="${pPrice}"
                                           title="Click to print barcode sticker">
                                           ${pBarcode}
                                        </a>
                                    </td>
                                    <td><strong>${pName}</strong></td>
                                    <td>${catName}</td>
                                    <td class="text-end font-monospace">Rs. ${pCost}</td>
                                    <td class="text-end font-monospace">Rs. ${pPrice}</td>
                                    <td class="text-center fw-bold ${qtyColor}">${pQty}</td>
                                    <td class="text-center">${showInPosBadge}</td>
                                    <td class="text-end text-nowrap">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info px-2" 
                                                data-bs-toggle="modal" data-bs-target="#printBarcodeModal"
                                                data-name="${escapeAttr(p.name)}" 
                                                data-barcode="${escapeAttr(p.barcode)}" 
                                                data-price="${pPrice}"
                                                title="Print Barcode Label">
                                                <i class="fas fa-print"></i><span class="d-none d-xxl-inline ms-1">Barcode</span>
                                            </button>
                                            <button class="btn btn-outline-secondary px-2 view-stock-history" 
                                                data-id="${p.id}" 
                                                data-name="${escapeAttr(p.name)}" 
                                                data-barcode="${escapeAttr(p.barcode)}" 
                                                title="View Ledger History">
                                                <i class="fas fa-clock-rotate-left"></i><span class="d-none d-xxl-inline ms-1">Ledger</span>
                                            </button>
                                            <a href="${(window.BASE_URL || "")}/inventory/edit.php?id=${p.id}" class="btn btn-outline-primary px-2" title="Edit Product">
                                                <i class="fas fa-edit"></i><span class="d-none d-xxl-inline ms-1">Edit</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#productsTableBody').html(html);
                    
                    // Handle pagination UI
                    const startCount = total > 0 ? (offset + 1) : 0;
                    const endCount = Math.min(offset + limit, total);
                    $('#paginationSummary').text(`Showing ${startCount} to ${endCount} of ${total} products`);
                    $('#prevPageBtn').prop('disabled', offset === 0);
                    $('#nextPageBtn').prop('disabled', offset + limit >= total);
                } else {
                    $('#productsTableBody').html('<tr><td colspan="8" class="text-center text-danger py-5">Failed to fetch product catalog.</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                $('#productsTableBody').html('<tr><td colspan="8" class="text-center text-danger py-5"><i class="fas fa-circle-exclamation me-2"></i>Error communicating with server: ' + (error || 'Network error') + '</td></tr>');
            }
        });
    }
    
    // Load categories for modal select
    function loadModalCategories(selectId = null) {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=get_categories&status=active',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '<option value="">Uncategorized</option>';
                    response.categories.forEach(function(cat) {
                        const selected = selectId == cat.id ? 'selected' : '';
                        html += `<option value="${cat.id}" ${selected}>${cat.name}</option>`;
                    });
                    $('#modal_category_id').html(html);
                }
            }
        });
    }

    // Load categories initially
    loadModalCategories();

    // Auto generate barcode click
    $('#generateBarcodeBtn').on('click', function() {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=generate_barcode',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modal_barcode').val(response.barcode);
                }
            }
        });
    });

    // Toggle Quick Category inline panel
    $('#toggleQuickCatBtn').on('click', function(e) {
        e.preventDefault();
        $('#quickCategoryContainer').toggleClass('d-none');
        if (!$('#quickCategoryContainer').hasClass('d-none')) {
            $('#quick_cat_name').val('').focus();
        }
    });

    // Save Quick Category inline click
    $('#saveQuickCategoryBtn').on('click', function(e) {
        e.preventDefault();
        const catName = $('#quick_cat_name').val().trim();
        $('#quickCatAlert').addClass('d-none');
        
        if (catName === '') {
            $('#quickCatAlert').text('Category name is required.').removeClass('d-none');
            return;
        }
        
        const saveBtn = $(this);
        saveBtn.prop('disabled', true).text('Creating...');
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=add_category',
            type: 'POST',
            data: { name: catName },
            dataType: 'json',
            success: function(response) {
                saveBtn.prop('disabled', false).text('Create');
                if (response.success) {
                    loadModalCategories(response.category_id);
                    $('#quick_cat_name').val('');
                    $('#quickCategoryContainer').addClass('d-none');
                    showToast('Category created successfully.', 'success');
                } else {
                    $('#quickCatAlert').text(response.message).removeClass('d-none');
                }
            },
            error: function() {
                saveBtn.prop('disabled', false).text('Create');
                $('#quickCatAlert').text('Failed to create category.').removeClass('d-none');
            }
        });
    });

    // Real-time Barcode Availability Verification for Modal
    let modalBarcodeCheckTimeout = null;
    function checkModalBarcodeAvailability() {
        const barcodeVal = $('#modal_barcode').val().trim();
        const feedbackEl = $('#modalBarcodeFeedback');
        const inputEl = $('#modal_barcode');
        
        if (!barcodeVal) {
            inputEl.removeClass('is-invalid is-valid');
            feedbackEl.addClass('d-none').empty();
            return;
        }
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=check_barcode',
            type: 'GET',
            data: { barcode: barcodeVal },
            dataType: 'json',
            success: function(res) {
                if (res.success && res.exists && res.product) {
                    inputEl.addClass('is-invalid').removeClass('is-valid');
                    const p = res.product;
                    const cat = p.category_name || 'Uncategorized';
                    const price = parseFloat(p.selling_price || 0).toFixed(2);
                    feedbackEl.html(`
                        <div class="p-2 rounded bg-danger-subtle border border-danger-subtle text-danger-emphasis">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                                <div>
                                    <i class="fas fa-triangle-exclamation me-1 text-danger"></i>
                                    <strong>Already in use:</strong> "${escapeHtml(p.name)}"
                                    <span class="badge bg-white text-dark border ms-1">${escapeHtml(cat)}</span>
                                    <span class="ms-1 font-monospace fw-bold text-danger">Rs. ${price}</span>
                                    <span class="ms-1 text-muted small">(Stock: ${p.quantity})</span>
                                </div>
                                <a href="${window.BASE_URL || ''}/inventory/edit.php?id=${p.id}" target="_blank" class="btn btn-xs btn-outline-danger py-0 px-2 fw-bold text-nowrap">
                                    <i class="fas fa-arrow-up-right-from-square me-1"></i>Edit Existing
                                </a>
                            </div>
                        </div>
                    `).removeClass('d-none');
                } else if (res.success && !res.exists) {
                    inputEl.addClass('is-valid').removeClass('is-invalid');
                    feedbackEl.html(`
                        <span class="text-success fw-semibold small">
                            <i class="fas fa-check-circle me-1"></i> Barcode is available
                        </span>
                    `).removeClass('d-none');
                }
            }
        });
    }

    $('#modal_barcode').on('input', function() {
        clearTimeout(modalBarcodeCheckTimeout);
        modalBarcodeCheckTimeout = setTimeout(checkModalBarcodeAvailability, 300);
    }).on('change blur', function() {
        clearTimeout(modalBarcodeCheckTimeout);
        checkModalBarcodeAvailability();
    });

    // Reset validation when modal opens
    $('#addProductModal').on('show.bs.modal', function() {
        $('#modalBarcodeFeedback').addClass('d-none').empty();
        $('#modal_barcode').removeClass('is-invalid is-valid');
        $('#addAlert').addClass('d-none').empty();
    });

    // Add product submit
    $('#addProductForm').on('submit', function(e) {
        e.preventDefault();
        $('#addAlert').addClass('d-none').empty();
        
        const saveBtn = $('#saveProductBtn');
        saveBtn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=add',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                saveBtn.prop('disabled', false).text('Save Product');
                if (response.success) {
                    showToast('Product created successfully.', 'success');
                    $('#addProductForm')[0].reset();
                    $('#modalBarcodeFeedback').addClass('d-none').empty();
                    $('#modal_barcode').removeClass('is-invalid is-valid');
                    bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
                    
                    // Reload dynamic product list table
                    offset = 0;
                    loadProducts();
                } else {
                    $('#addAlert').text(response.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                saveBtn.prop('disabled', false).text('Save Product');
                let msg = 'Server error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.conflict_product) {
                    const c = xhr.responseJSON.conflict_product;
                    const cat = c.category_name || 'Uncategorized';
                    const price = parseFloat(c.selling_price || 0).toFixed(2);
                    $('#addAlert').html(`
                        <div class="d-flex align-items-start gap-2 text-start">
                            <i class="fas fa-triangle-exclamation text-danger fs-4 mt-1 flex-shrink-0"></i>
                            <div class="flex-grow-1">
                                <strong class="d-block text-danger fs-6 mb-1">Barcode Conflict Detected!</strong>
                                <p class="mb-2 text-dark">Barcode <code class="fw-bold px-1.5 py-0.5 rounded bg-light text-danger border">${escapeHtml(c.barcode)}</code> is already associated with an existing product:</p>
                                <div class="p-2.5 rounded bg-white border border-danger-subtle text-dark shadow-sm">
                                    <div class="fw-bold text-danger fs-6 mb-1">${escapeHtml(c.name)}</div>
                                    <div class="small text-muted d-flex flex-wrap gap-3">
                                        <span><i class="fas fa-tag me-1 text-secondary"></i>Category: <strong class="text-dark">${escapeHtml(cat)}</strong></span>
                                        <span><i class="fas fa-money-bill-wave me-1 text-success"></i>Price: <strong class="text-dark font-monospace">Rs. ${price}</strong></span>
                                        <span><i class="fas fa-boxes-stacked me-1 text-primary"></i>Stock: <strong class="text-dark">${c.quantity} units</strong></span>
                                    </div>
                                </div>
                                <div class="mt-2.5 d-flex gap-2 align-items-center flex-wrap">
                                    <a href="${window.BASE_URL || ''}/inventory/edit.php?id=${c.id}" target="_blank" class="btn btn-sm btn-danger px-3 py-1 fw-bold rounded-pill shadow-sm">
                                        <i class="fas fa-pen-to-square me-1"></i> Edit "${escapeHtml(c.name)}"
                                    </a>
                                    <span class="small text-muted">or choose / generate a different barcode for the new product.</span>
                                </div>
                            </div>
                        </div>
                    `).removeClass('d-none');
                    $('#modal_barcode').addClass('is-invalid');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    $('#addAlert').text(xhr.responseJSON.message).removeClass('d-none');
                } else {
                    $('#addAlert').text(msg).removeClass('d-none');
                }
            }
        });
    });
    
    // Global barcode print variables
    let barcodePrintName = '';
    let barcodePrintValue = '';
    let barcodePrintPrice = '';

    // Handle Print Barcode Modal Loading (Dynamic attributes retrieval)
    $('#printBarcodeModal').on('show.bs.modal', function(event) {
        const trigger = $(event.relatedTarget);
        
        // Check if barcode data is directly attached (from catalog table list or buttons)
        const directBarcode = trigger.data('barcode');
        
        if (directBarcode !== undefined) {
            barcodePrintName = trigger.data('name') || 'New Product';
            barcodePrintValue = directBarcode;
            barcodePrintPrice = trigger.data('price') || '0.00';
        } else {
            // Read from Add Product Form fields (for print button inside modal)
            const formBarcode = $('#modal_barcode').val().trim();
            if (formBarcode === '') {
                showToast('Please enter or generate a barcode first.', 'warning');
                event.preventDefault(); // Stop modal from showing
                return;
            }
            barcodePrintName = $('#modal_name').val().trim() || 'New Product';
            barcodePrintValue = formBarcode;
            let priceVal = parseFloat($('#modal_selling_price').val()) || 0.00;
            barcodePrintPrice = priceVal.toFixed(2);
        }
        
        // Populate preview modal fields
        $('#previewLabelName').text(barcodePrintName);
        $('#previewLabelPrice').text(`Rs. ${barcodePrintPrice}`);
        $('#barcodePrintCopies').val(1);
        
        // Render preview barcode SVG
        JsBarcode("#previewBarcodeSVG", barcodePrintValue, {
            format: "CODE128",
            lineColor: "#000",
            width: 1.8,
            height: 40,
            displayValue: true,
            fontSize: 10,
            margin: 0
        });
    });

    // Handle Barcode Print Trigger
    $('#triggerBarcodePrintBtn').on('click', function() {
        const copies = parseInt($('#barcodePrintCopies').val()) || 1;
        if (copies < 1) return;
        
        // Hide preview modal
        bootstrap.Modal.getInstance(document.getElementById('printBarcodeModal')).hide();
        
        // Open a new print window
        const printWindow = window.open('', '_blank', 'width=600,height=600');
        if (!printWindow) {
            showToast('Popup blocker prevented printing. Please allow popups for this site.', 'danger');
            return;
        }
        
        // Build stickers content
        let stickersHTML = '';
        for (let i = 0; i < copies; i++) {
            stickersHTML += `
                <div class="barcode-sticker">
                    <div class="store-name">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</div>
                    <div class="product-name">${barcodePrintName}</div>
                    <svg class="sticker-barcode-${i}"></svg>
                    <div class="product-price">Rs. ${barcodePrintPrice}</div>
                </div>
            `;
        }
        
        const htmlContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print Barcodes - ${barcodePrintName}</title>
                <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
                <style>
                    @page {
                        size: 50mm 25mm;
                        margin: 0mm !important;
                    }
                    * {
                        box-sizing: border-box;
                        margin: 0;
                        padding: 0;
                        color: #000000 !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    html, body {
                        margin: 0 !important;
                        padding: 0 !important;
                        background: #ffffff !important;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
                        color: #000000 !important;
                    }
                    .barcode-sticker {
                        width: 50mm;
                        height: 25mm;
                        max-width: 50mm;
                        max-height: 25mm;
                        padding: 1.2mm 1.5mm;
                        box-sizing: border-box;
                        display: flex;
                        flex-direction: column;
                        justify-content: space-between;
                        align-items: center;
                        text-align: center;
                        font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
                        page-break-after: always;
                        page-break-inside: avoid;
                        overflow: hidden;
                        color: #000000 !important;
                    }
                    .store-name {
                        font-size: 8px;
                        font-weight: 900 !important;
                        text-transform: uppercase;
                        color: #000000 !important;
                        letter-spacing: 0.4px;
                        line-height: 1;
                        margin-bottom: 0.5mm;
                        width: 100%;
                    }
                    .product-name {
                        font-size: 9.5px;
                        font-weight: 800 !important;
                        width: 100%;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        line-height: 1.1;
                        color: #000000 !important;
                        margin-bottom: 0.5mm;
                    }
                    .barcode-sticker svg {
                        max-width: 100%;
                        height: 11.5mm !important;
                        display: block;
                        margin: 0 auto;
                    }
                    .product-price {
                        font-size: 11px;
                        font-weight: 900 !important;
                        line-height: 1;
                        color: #000000 !important;
                        margin-top: 0.5mm;
                    }
                </style>
            </head>
            <body>
                ${stickersHTML}
                <script>
                    window.onload = function() {
                        const barcodeValue = "${barcodePrintValue}";
                        for (let i = 0; i < ${copies}; i++) {
                            JsBarcode(".sticker-barcode-" + i, barcodeValue, {
                                format: "CODE128",
                                lineColor: "#000000",
                                width: 1.3,
                                height: 32,
                                displayValue: true,
                                fontSize: 9.5,
                                fontOptions: "bold",
                                font: "Arial",
                                margin: 0
                            });
                        }
                        
                        // Small delay to ensure rendering is complete before printing
                        setTimeout(function() {
                            window.print();
                            window.close();
                        }, 250);
                    };
                <\/script>
            </body>
            </html>
        `;
        
        printWindow.document.open();
        printWindow.document.write(htmlContent);
        printWindow.document.close();
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
