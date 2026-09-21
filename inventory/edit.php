<?php
require_once __DIR__ . '/../includes/header.php';
requirePermission('MANAGE_INVENTORY');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($id > 0) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
}

if (!$product) {
    echo '<div class="alert alert-danger">Product not found. <a href="' . url('/inventory/index.php') . '" class="alert-link">Return to Catalog</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Product: <?php echo htmlspecialchars($product['name']); ?></h5>
                <a href="<?= url('/inventory/index.php') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Catalog</a>
            </div>
            <div class="card-body">
                <div id="editAlert" class="alert alert-danger d-none"></div>
                <div id="editSuccess" class="alert alert-success d-none">Product updated successfully. Redirecting...</div>
                
                <form id="editProductForm">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    
                    <div class="row g-3">
                        <!-- Product Name -->
                        <div class="col-md-12">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        
                        <!-- Barcode Field -->
                        <div class="col-md-6">
                            <label for="barcode" class="form-label">Barcode *</label>
                            <input type="text" class="form-control" id="barcode" name="barcode" value="<?php echo htmlspecialchars($product['barcode']); ?>" required>
                            <small class="text-muted">Unique identifier for scanner lookups.</small>
                            <div id="barcodeFeedback" class="mt-1 small d-none"></div>
                        </div>
                        
                        <!-- Category -->
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Uncategorized</option>
                            </select>
                        </div>
                        
                        <!-- Cost Price -->
                        <div class="col-md-4">
                            <label for="cost_price" class="form-label">Cost Price (Rs.) *</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" class="form-control" id="cost_price" name="cost_price" step="0.01" min="0" value="<?php echo $product['cost_price']; ?>" required>
                            </div>
                        </div>
                        
                        <!-- Selling Price -->
                        <div class="col-md-4">
                            <label for="selling_price" class="form-label">Selling Price (Rs.) *</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" min="0" value="<?php echo $product['selling_price']; ?>" required>
                            </div>
                        </div>
                        
                        <!-- Quantity (Stock Ledger trigger) -->
                        <div class="col-md-4">
                            <label for="quantity" class="form-label fw-bold">Stock Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="0" value="<?php echo $product['quantity']; ?>">
                            <small class="text-muted">Original count: <strong id="origQtyText"><?php echo $product['quantity']; ?></strong></small>
                        </div>
                        
                        <!-- Stock Adjustment Reason (Dynamic with 3 Options) -->
                        <div class="col-md-12 d-none" id="adjustmentReasonGroup">
                            <div class="card border-warning bg-warning-subtle shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                                        <label class="form-label text-warning-emphasis fw-bold mb-0">
                                            <i class="fas fa-clipboard-check me-1"></i> Reason for Stock Quantity Change *
                                        </label>
                                        <span id="qtyChangeBadge" class="badge bg-secondary px-2 py-1">Stock Changed</span>
                                    </div>
                                    
                                    <!-- 3 Reason Options: Damage, New Stock, Other -->
                                    <div class="row g-2 mb-2">
                                        <!-- Option 1: Damage (Auto-selected if stock decreased) -->
                                        <div class="col-12 col-sm-4">
                                            <input type="radio" class="btn-check" name="reason_choice" id="reasonDamage" value="Damage" autocomplete="off">
                                            <label class="btn btn-outline-danger w-100 py-2 d-flex flex-column align-items-center justify-content-center fw-bold" for="reasonDamage" style="border-width: 2px;">
                                                <i class="fas fa-trash-can fa-lg mb-1"></i>
                                                <span>Damage</span>
                                                <small class="fw-normal" style="font-size: 0.72rem;">Stock Kam Hua</small>
                                            </label>
                                        </div>
                                        
                                        <!-- Option 2: New Stock (Auto-selected if stock increased) -->
                                        <div class="col-12 col-sm-4">
                                            <input type="radio" class="btn-check" name="reason_choice" id="reasonNewStock" value="New Stock" autocomplete="off">
                                            <label class="btn btn-outline-success w-100 py-2 d-flex flex-column align-items-center justify-content-center fw-bold" for="reasonNewStock" style="border-width: 2px;">
                                                <i class="fas fa-boxes-packing fa-lg mb-1"></i>
                                                <span>New Stock</span>
                                                <small class="fw-normal" style="font-size: 0.72rem;">Stock Barha</small>
                                            </label>
                                        </div>
                                        
                                        <!-- Option 3: Other (Custom reason or standalone Other) -->
                                        <div class="col-12 col-sm-4">
                                            <input type="radio" class="btn-check" name="reason_choice" id="reasonOther" value="Other" autocomplete="off">
                                            <label class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center justify-content-center fw-bold" for="reasonOther" style="border-width: 2px;">
                                                <i class="fas fa-pen-to-square fa-lg mb-1"></i>
                                                <span>Other</span>
                                                <small class="fw-normal" style="font-size: 0.72rem;">Custom Reason</small>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Other Custom Note Field (Shown when 'Other' is chosen) -->
                                    <div id="otherReasonContainer" class="d-none mt-2 p-2 bg-white rounded border border-primary-subtle">
                                        <label for="other_reason_text" class="form-label small fw-bold text-primary mb-1">
                                            <i class="fas fa-pencil me-1"></i> Specify Custom Reason (Optional):
                                        </label>
                                        <input type="text" class="form-control form-control-sm border-primary" id="other_reason_text" name="other_reason_text" placeholder="Type reason here, or leave blank to save simply as 'Other'...">
                                        <div class="form-text small text-muted">
                                            <i class="fas fa-info-circle me-1"></i>Agar aap yahan kuch nahi likhenge to record me sirf <strong>"Other"</strong> save hoga.
                                        </div>
                                    </div>
                                    
                                    <!-- Hidden input carrying finalized reason to server -->
                                    <input type="hidden" id="adjustment_reason" name="adjustment_reason" value="">
                                </div>
                            </div>
                        </div>
                        
                        <!-- POS Visibility -->
                        <div class="col-md-6">
                            <div class="form-check mt-4 pt-2">
                                <input class="form-check-input" type="checkbox" id="show_in_pos" name="show_in_pos" value="1" <?php echo $product['show_in_pos'] == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold" for="show_in_pos">
                                    Show in POS Cart Search
                                </label>
                            </div>
                        </div>
                        
                        <!-- Low Stock Threshold -->
                        <div class="col-md-6">
                            <label for="low_stock_threshold" class="form-label">Low Stock Threshold *</label>
                            <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" min="1" value="<?php echo $product['low_stock_threshold']; ?>" required>
                        </div>
                        
                        <!-- Description -->
                        <div class="col-md-12">
                            <label for="description" class="form-label">Description / Shelf Location / Notes</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-5">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const originalQty = parseInt($('#quantity').val());
    const selectedCategory = "<?php echo $product['category_id']; ?>";
    
    loadCategories(selectedCategory);
    
    // Handle quantity changes: toggle reason group and auto-select appropriate option
    function handleQtyChange() {
        const val = parseInt($('#quantity').val()) || 0;
        const diff = val - originalQty;
        
        if (diff !== 0) {
            $('#adjustmentReasonGroup').removeClass('d-none');
            
            const currentSelected = $('input[name="reason_choice"]:checked').val();
            
            if (diff < 0) {
                // Stock decreased -> Red badge & default to 'Damage'
                $('#qtyChangeBadge')
                    .removeClass('bg-success bg-secondary')
                    .addClass('bg-danger')
                    .html(`<i class="fas fa-arrow-trend-down me-1"></i>Stock Decreased: ${diff} pcs`);
                
                // If nothing selected or New Stock was selected, switch to Damage
                if (!currentSelected || currentSelected === 'New Stock') {
                    $('#reasonDamage').prop('checked', true);
                    $('#otherReasonContainer').addClass('d-none');
                }
            } else {
                // Stock increased -> Green badge & default to 'New Stock'
                $('#qtyChangeBadge')
                    .removeClass('bg-danger bg-secondary')
                    .addClass('bg-success')
                    .html(`<i class="fas fa-arrow-trend-up me-1"></i>Stock Increased: +${diff} pcs`);
                
                // If nothing selected or Damage was selected, switch to New Stock
                if (!currentSelected || currentSelected === 'Damage') {
                    $('#reasonNewStock').prop('checked', true);
                    $('#otherReasonContainer').addClass('d-none');
                }
            }
            
            // If Other is active, ensure container remains visible
            if ($('input[name="reason_choice"]:checked').val() === 'Other') {
                $('#otherReasonContainer').removeClass('d-none');
            }
        } else {
            // Reverted back to original count
            $('#adjustmentReasonGroup').addClass('d-none');
            $('input[name="reason_choice"]').prop('checked', false);
            $('#otherReasonContainer').addClass('d-none');
            $('#other_reason_text').val('');
            $('#adjustment_reason').val('');
        }
    }

    $('#quantity').on('input change', handleQtyChange);
    
    // Toggle Other custom input field when user changes reason choice
    $('input[name="reason_choice"]').on('change', function() {
        if ($(this).val() === 'Other') {
            $('#otherReasonContainer').removeClass('d-none');
            $('#other_reason_text').focus();
        } else {
            $('#otherReasonContainer').addClass('d-none');
        }
    });
    
    // Form submit
    $('#editProductForm').on('submit', function(e) {
        e.preventDefault();
        
        $('#editAlert').addClass('d-none');
        $('#editSuccess').addClass('d-none');
        
        const val = parseInt($('#quantity').val()) || 0;
        if (val !== originalQty) {
            const choice = $('input[name="reason_choice"]:checked').val();
            if (!choice) {
                $('#editAlert').text('Please select a reason for the stock change (Damage, New Stock, or Other).').removeClass('d-none');
                $('html, body').animate({ scrollTop: $('#adjustmentReasonGroup').offset().top - 100 }, 200);
                return false;
            }
            
            let finalReason = choice;
            if (choice === 'Other') {
                const customText = $('#other_reason_text').val().trim();
                // If custom text typed, combine; otherwise save just 'Other'
                finalReason = customText ? ('Other: ' + customText) : 'Other';
            }
            $('#adjustment_reason').val(finalReason);
        } else {
            $('#adjustment_reason').val('');
        }
        
        $('#editAlert').addClass('d-none').empty();
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=update',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editSuccess').removeClass('d-none');
                    setTimeout(function() {
                        window.location.href = (window.BASE_URL || '') + '/inventory/index.php';
                    }, 1500);
                } else {
                    $('#editAlert').text(response.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                let msg = 'Server error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.conflict_product) {
                    const c = xhr.responseJSON.conflict_product;
                    const cat = c.category_name || 'Uncategorized';
                    const price = parseFloat(c.selling_price || 0).toFixed(2);
                    $('#editAlert').html(`
                        <div class="d-flex align-items-start gap-2 text-start">
                            <i class="fas fa-triangle-exclamation text-danger fs-4 mt-1 flex-shrink-0"></i>
                            <div class="flex-grow-1">
                                <strong class="d-block text-danger fs-6 mb-1">Barcode Conflict Detected!</strong>
                                <p class="mb-2 text-dark">Barcode <code class="fw-bold px-1.5 py-0.5 rounded bg-light text-danger border">${window.escapeHtml(c.barcode)}</code> is already assigned to another product in your inventory:</p>
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
                                    <span class="small text-muted">or revert to the original barcode.</span>
                                </div>
                            </div>
                        </div>
                    `).removeClass('d-none');
                    $('#barcode').addClass('is-invalid');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    $('#editAlert').text(xhr.responseJSON.message).removeClass('d-none');
                } else {
                    $('#editAlert').text(msg).removeClass('d-none');
                }
            }
        });
    });

    // Live barcode check on edit form
    let barcodeCheckTimeout = null;
    function checkBarcodeAvailability() {
        const barcodeVal = $('#barcode').val().trim();
        const feedbackEl = $('#barcodeFeedback');
        const inputEl = $('#barcode');
        const currentProdId = parseInt($('input[name="id"]').val()) || 0;
        
        if (!barcodeVal) {
            inputEl.removeClass('is-invalid is-valid');
            feedbackEl.addClass('d-none').empty();
            return;
        }
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=check_barcode',
            type: 'GET',
            data: { barcode: barcodeVal, exclude_id: currentProdId },
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
    
    function loadCategories(selectId) {
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
