<?php
require_once __DIR__ . '/../includes/header.php';
requirePermission('MANAGE_INVENTORY');
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Product</h5>
                <a href="<?= url('/inventory/index.php') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Catalog</a>
            </div>
            <div class="card-body">
                <div id="addAlert" class="alert alert-danger d-none"></div>
                <div id="addSuccess" class="alert alert-success d-none">Product saved successfully. Redirecting...</div>
                
                <form id="addProductForm">
                    <div class="row g-3">
                        <!-- Product Name -->
                        <div class="col-md-12">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" required placeholder="Enter product name">
                        </div>
                        
                        <!-- Barcode Field with Generate button -->
                        <div class="col-md-6">
                            <label for="barcode" class="form-label">Barcode (Scan or Leave Empty to Generate)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="barcode" name="barcode" placeholder="Manufacturer Barcode">
                                <button type="button" class="btn btn-outline-secondary" id="generateBarcodeBtn"><i class="fas fa-barcode"></i> Generate</button>
                            </div>
                            <small class="text-muted">Supports EAN-13, UPC-A, Code-128, etc.</small>
                            <div id="barcodeFeedback" class="mt-1 small d-none"></div>
                        </div>
                        
                        <!-- Category with Inline Add Button -->
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category</label>
                            <div class="input-group">
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Uncategorized</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickCategoryModal"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                        
                        <!-- Cost Price -->
                        <div class="col-md-4">
                            <label for="cost_price" class="form-label">Cost Price (Rs.) *</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" class="form-control" id="cost_price" name="cost_price" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        
                        <!-- Selling Price -->
                        <div class="col-md-4">
                            <label for="selling_price" class="form-label">Selling Price (Rs.) *</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        
                        <!-- Initial Stock -->
                        <div class="col-md-4">
                            <label for="quantity" class="form-label">Initial Stock Qty</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="0" value="0">
                        </div>
                        
                        <!-- POS Visibility -->
                        <div class="col-md-4">
                            <label for="show_in_pos" class="form-label">POS Visibility</label>
                            <select class="form-select" id="show_in_pos" name="show_in_pos">
                                <option value="1">Show in POS Cart Search</option>
                                <option value="0">Hide (Inventory Only)</option>
                            </select>
                        </div>
                        
                        <!-- Low Stock Threshold -->
                        <div class="col-md-4">
                            <label for="low_stock_threshold" class="form-label">Low Stock Threshold *</label>
                            <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" min="1" value="10" required>
                        </div>
                        
                        <!-- Status -->
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <!-- Description -->
                        <div class="col-md-12">
                            <label for="description" class="form-label">Description / Shelf Location / Notes</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Optional details..."></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-5">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Quick Category Modal -->
<div class="modal fade" id="quickCategoryModal" tabindex="-1" aria-labelledby="quickCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickCategoryModalLabel">Quick Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickCategoryForm">
                <div class="modal-body">
                    <div id="quickCatAlert" class="alert alert-danger d-none"></div>
                    <div class="mb-3">
                        <label for="quick_cat_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="quick_cat_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadCategories();
    
    // Auto generate barcode click
    $('#generateBarcodeBtn').on('click', function() {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=generate_barcode',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#barcode').val(response.barcode);
                }
            }
        });
    });
    
    // Quick category creation
    $('#quickCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        const catName = $('#quick_cat_name').val();
        $('#quickCatAlert').addClass('d-none');
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=add_category',
            type: 'POST',
            data: { name: catName },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Refresh category drop down
                    loadCategories(response.category_id);
                    $('#quick_cat_name').val('');
                    bootstrap.Modal.getInstance(document.getElementById('quickCategoryModal')).hide();
                } else {
                    $('#quickCatAlert').text(response.message).removeClass('d-none');
                }
            },
            error: function() {
                $('#quickCatAlert').text('Failed to create category.').removeClass('d-none');
            }
        });
    });
    
    // Live barcode uniqueness check
    let barcodeCheckTimeout = null;
    function checkBarcodeAvailability() {
        const barcodeVal = $('#barcode').val().trim();
        const feedbackEl = $('#barcodeFeedback');
        const inputEl = $('#barcode');
        
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
                                    <strong>Already in use:</strong> "${window.escapeHtml(p.name)}" 
                                    <span class="badge bg-white text-dark border ms-1">${window.escapeHtml(cat)}</span>
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

    $('#barcode').on('input', function() {
        clearTimeout(barcodeCheckTimeout);
        barcodeCheckTimeout = setTimeout(checkBarcodeAvailability, 300);
    }).on('change blur', function() {
        clearTimeout(barcodeCheckTimeout);
        checkBarcodeAvailability();
    });

    // Add product submit
    $('#addProductForm').on('submit', function(e) {
        e.preventDefault();
        
        $('#addAlert').addClass('d-none').empty();
        $('#addSuccess').addClass('d-none');
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=add',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addSuccess').removeClass('d-none');
                    setTimeout(function() {
                        window.location.href = (window.BASE_URL || '') + '/inventory/index.php';
                    }, 1500);
                } else {
                    $('#addAlert').text(response.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
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
                                <p class="mb-2 text-dark">Barcode <code class="fw-bold px-1.5 py-0.5 rounded bg-light text-danger border">${window.escapeHtml(c.barcode)}</code> is already associated with an existing product:</p>
                                <div class="p-2.5 rounded bg-white border border-danger-subtle text-dark shadow-sm">
                                    <div class="fw-bold text-danger fs-6 mb-1">${window.escapeHtml(c.name)}</div>
                                    <div class="small text-muted d-flex flex-wrap gap-3">
                                        <span><i class="fas fa-tag me-1 text-secondary"></i>Category: <strong class="text-dark">${window.escapeHtml(cat)}</strong></span>
                                        <span><i class="fas fa-money-bill-wave me-1 text-success"></i>Price: <strong class="text-dark font-monospace">Rs. ${price}</strong></span>
                                        <span><i class="fas fa-boxes-stacked me-1 text-primary"></i>Stock: <strong class="text-dark">${c.quantity} units</strong></span>
                                    </div>
                                </div>
                                <div class="mt-2.5 d-flex gap-2 align-items-center flex-wrap">
                                    <a href="${window.BASE_URL || ''}/inventory/edit.php?id=${c.id}" target="_blank" class="btn btn-sm btn-danger px-3 py-1 fw-bold rounded-pill shadow-sm">
                                        <i class="fas fa-pen-to-square me-1"></i> Edit "${window.escapeHtml(c.name)}"
                                    </a>
                                    <span class="small text-muted">or choose a different barcode.</span>
                                </div>
                            </div>
                        </div>
                    `).removeClass('d-none');
                    $('#barcode').addClass('is-invalid');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    $('#addAlert').text(xhr.responseJSON.message).removeClass('d-none');
                } else {
                    $('#addAlert').text(msg).removeClass('d-none');
                }
            }
        });
    });
    
    function loadCategories(selectId = null) {
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
                    $('#category_id').html(html);
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
