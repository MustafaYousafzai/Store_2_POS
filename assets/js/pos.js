$(document).ready(function() {
    const BASE_URL = (typeof window.BASE_URL !== 'undefined') ? window.BASE_URL : '';
    let cart = []; // Array of {product_id, name, barcode, selling_price, quantity, discount, stock_qty}
    let catalogProducts = []; // Local cache of catalog products
    let cartModalInstance = null;

    // Helper to open Cart Modal safely
    function openCartModal() {
        if (!cartModalInstance) {
            cartModalInstance = new bootstrap.Modal(document.getElementById('posCartModal'));
        }
        if (!$('#posCartModal').hasClass('show')) {
            cartModalInstance.show();
        }
    }

    // Auto-focus input fields on modal transitions
    $('#posCartModal').on('show.bs.modal', function () {
        $('#modalSearchResultsPanel').addClass('d-none');
    });
    $('#posCartModal').on('shown.bs.modal', function () {
        $('#modalSearchResultsPanel').addClass('d-none');
        $('#modalCartSearchInput').focus();
    });
    $('#posCartModal').on('hidden.bs.modal', function () {
        $('#posSearchInput').focus();
    });

    // High-speed POS Sound Engine (Web Audio API - 0 delay, 100% offline)
    let audioCtx = null;
    function getAudioContext() {
        if (!audioCtx) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (AudioCtx) audioCtx = new AudioCtx();
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    function playScanSound(success = true) {
        try {
            const ctx = getAudioContext();
            if (!ctx) return;

            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);

            if (success) {
                // Crisp retail scanner chime (1200 Hz, 65ms)
                osc.type = 'sine';
                osc.frequency.setValueAtTime(1200, ctx.currentTime);
                gain.gain.setValueAtTime(0.18, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.07);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.07);
            } else {
                // Warning buzz (240 Hz, 160ms)
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(240, ctx.currentTime);
                gain.gain.setValueAtTime(0.22, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.18);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.18);
            }
        } catch (e) {
            // Audio context not permitted yet
        }
    }

    // Load Product Catalog on Load
    function loadCatalog() {
        $.ajax({
            url: BASE_URL + '/api/products.php?action=list&limit=1000&status=active',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Filter to products visible in POS
                    catalogProducts = response.products.filter(p => p.show_in_pos == 1);
                    renderCatalog(catalogProducts);
                }
            }
        });
    }
    
    // Render catalog items to table
    function renderCatalog(products) {
        let html = '';
        if (products.length === 0) {
            html = '<tr><td colspan="5" class="text-center py-4 text-muted">No products found matching criteria.</td></tr>';
        } else {
            products.forEach(function(p) {
                const stock = parseInt(p.quantity);
                const threshold = parseInt(p.low_stock_threshold) || 10;
                const isLow = stock <= threshold;
                
                const stockBadge = isLow 
                    ? `<span class="badge bg-danger-subtle text-danger border border-danger fw-bold">${stock}</span>`
                    : `<span class="badge bg-success-subtle text-success border border-success fw-bold">${stock}</span>`;
                    
                html += `
                    <tr class="catalog-row" style="cursor: pointer;"
                        data-id="${p.id}"
                        data-name="${p.name}"
                        data-barcode="${p.barcode}"
                        data-price="${p.selling_price}"
                        data-stock="${p.quantity}">
                        <td class="font-monospace"><code>${p.barcode}</code></td>
                        <td><strong class="text-dark">${p.name}</strong></td>
                        <td>${p.category_name || '<span class="text-muted">Uncategorized</span>'}</td>
                        <td class="text-end font-monospace fw-bold">Rs. ${parseFloat(p.selling_price).toFixed(2)}</td>
                        <td class="text-center">${stockBadge}</td>
                    </tr>
                `;
            });
        }
        $('#catalogTableBody').html(html);
    }
    
    // Init catalog
    loadCatalog();
    
    // Filter/Search product catalog locally
    $('#catalogSearchInput').on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        if (query === '') {
            renderCatalog(catalogProducts);
        } else {
            const filtered = catalogProducts.filter(p => {
                const nameMatch = p.name.toLowerCase().includes(query);
                const barcodeMatch = p.barcode.toLowerCase().includes(query);
                const catMatch = p.category_name ? p.category_name.toLowerCase().includes(query) : false;
                return nameMatch || barcodeMatch || catMatch;
            });
            renderCatalog(filtered);
        }
    });

    // Catalog Row Click: Add to Cart & Open Modal
    $(document).on('click', '.catalog-row', function() {
        const item = {
            product_id: $(this).data('id'),
            name: $(this).data('name'),
            barcode: $(this).data('barcode'),
            selling_price: $(this).data('price'),
            stock_qty: $(this).data('stock')
        };
        const added = addToCart(item);
        if (added) playScanSound(true);
        openCartModal();
    });

    // High-speed Exact Barcode Scanner & Product Add Engine
    function performAddByQuery(query, callback) {
        query = (query || '').trim();
        if (!query) return;

        // Immediately close any autocomplete panels
        $('#mainSearchResultsPanel, #modalSearchResultsPanel').addClass('d-none');

        // STEP 1: FAST PATH - Search in-memory catalog cache (0ms instant lookup)
        const matched = catalogProducts.find(p => p.barcode === query);
        if (matched) {
            const added = addToCart({
                product_id: matched.id,
                name: matched.name,
                barcode: matched.barcode,
                selling_price: matched.selling_price,
                stock_qty: matched.quantity
            });
            if (added) {
                playScanSound(true);
            } else {
                playScanSound(false);
            }
            if (callback) callback(added);
            return;
        }

        // STEP 2: SLOW PATH FALLBACK - Query backend API if product was added recently
        $.ajax({
            url: `${BASE_URL}/api/products.php?action=get_product_by_barcode&barcode=${encodeURIComponent(query)}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.product && response.product.status === 'active' && response.product.show_in_pos == 1) {
                    const prod = response.product;
                    // Cache it for future zero-delay scans
                    if (!catalogProducts.some(p => p.id == prod.id)) {
                        catalogProducts.push(prod);
                    }
                    const added = addToCart({
                        product_id: prod.id,
                        name: prod.name,
                        barcode: prod.barcode,
                        selling_price: prod.selling_price,
                        stock_qty: prod.quantity
                    });
                    if (added) {
                        playScanSound(true);
                    } else {
                        playScanSound(false);
                    }
                    if (callback) callback(added);
                } else {
                    playScanSound(false);
                    showToast(`Barcode "${query}" not found or inactive in POS!`, 'warning');
                    if (callback) callback(false);
                }
            },
            error: function() {
                playScanSound(false);
                showToast(`Error looking up barcode "${query}".`, 'danger');
                if (callback) callback(false);
            }
        });
    }

    // POS Search Input (Main Screen scanner): instant clear & add
    $('#posSearchInput').on('keydown', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const query = $(this).val().trim();
            $(this).val(''); // Instantly clear input!
            if (query === '') return;
            performAddByQuery(query, function(success) {
                if (success) {
                    openCartModal();
                }
            });
        }
    });

    // Modal Search Input (Inside Modal scanner): instant clear, add & stay focused
    $('#modalCartSearchInput').on('keydown', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const query = $(this).val().trim();
            $(this).val(''); // Instantly clear input!
            if (query === '') return;
            performAddByQuery(query, function() {
                $('#modalCartSearchInput').focus();
            });
        }
    });

    // Suspended Bills keyboard shortcut (Ctrl+H)
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.key.toLowerCase() === 'h') {
            e.preventDefault();
            $('#heldCartsCollapse').collapse('toggle');
        }
    });

    // Add product to cart logic (Places newest/re-scanned items at the TOP of the cart)
    function addToCart(product) {
        const existingIndex = cart.findIndex(item => item.product_id == product.product_id);
        
        if (existingIndex !== -1) {
            const existing = cart[existingIndex];
            if (existing.quantity + 1 > product.stock_qty) {
                showToast(`Insufficient stock for "${product.name}"! Max available: ${product.stock_qty}`, 'danger');
                return false;
            }
            existing.quantity += 1;
            // Elevate the scanned item to the TOP of the cart for instant visual focus
            if (existingIndex > 0) {
                const [movedItem] = cart.splice(existingIndex, 1);
                cart.unshift(movedItem);
            }
        } else {
            if (product.stock_qty <= 0) {
                showToast(`"${product.name}" is out of stock! Available: 0`, 'danger');
                return false;
            }
            // Add NEW item to the very TOP of the cart
            cart.unshift({
                product_id: product.product_id,
                name: product.name,
                barcode: product.barcode,
                selling_price: parseFloat(product.selling_price),
                quantity: 1,
                discount: 0.00,
                stock_qty: product.stock_qty
            });
        }
        renderCart(product.product_id);
        return true;
    }

    // Render cart items to table with row highlight support
    function renderCart(highlightProductId = null) {
        // Compute stats for the floating View Cart button header
        const totalQty = cart.reduce((sum, item) => sum + item.quantity, 0);
        const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
        const itemDiscountsSum = cart.reduce((sum, item) => sum + item.discount, 0);
        const invoiceDiscount = parseFloat($('#invoiceDiscountInput').val()) || 0;
        const grandTotal = Math.max(0, subtotal - itemDiscountsSum - invoiceDiscount);
        
        $('#globalCartCount').text(totalQty);
        $('#globalCartTotal').text(`Rs. ${grandTotal.toFixed(2)}`);
        if (highlightProductId) {
            const $cartBtn = $('#btnOpenCartModal');
            $cartBtn.removeClass('cart-bounce-pulse');
            if ($cartBtn[0]) void $cartBtn[0].offsetWidth;
            $cartBtn.addClass('cart-bounce-pulse');
        }

        if (cart.length === 0) {
            $('#cartTableBody').html(`
                <tr class="cart-empty-row">
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fas fa-cart-shopping display-6 mb-2 text-danger"></i>
                        <p class="mb-0">Cart is empty. Scan items or select from catalog.</p>
                    </td>
                </tr>
            `);
            $('#cartSubtotalText').text('Rs. 0.00');
            $('#cartTotalDiscountText').text('Rs. 0.00');
            $('#cartTotalText').text('Rs. 0.00');
            $('#cashPaidInput').val('');
            $('#cashChangeText').text('Rs. 0.00');
            updateKhataSettlementCalculations();
            return;
        }
        
        let html = '';
        
        cart.forEach((item, index) => {
            const itemNet = (item.quantity * item.selling_price) - item.discount;
            const isHighlight = (highlightProductId && item.product_id == highlightProductId);
            
            html += `
                <tr data-index="${index}" data-product-id="${item.product_id}" class="${isHighlight ? 'table-success scan-highlight cart-row-new' : ''}">
                    <td>
                        <strong class="text-light">${item.name}</strong><br>
                        <small class="text-secondary font-monospace" style="font-size: 0.75rem;">${item.barcode}</small>
                    </td>
                    <td class="text-end">
                        <input type="number" class="form-control form-control-sm text-end cart-item-price-input font-monospace fw-bold py-0 px-1" value="${parseFloat(item.selling_price).toFixed(2)}" min="0" step="0.5" style="max-width: 85px; height: 28px; margin-left: auto;" title="Special Rate override">
                    </td>
                    <td class="text-center">
                        <div class="input-group input-group-sm justify-content-center flex-nowrap" style="width: 125px; margin: 0 auto;">
                            <button class="btn btn-outline-secondary btn-qty-minus py-0 px-2" type="button" style="height: 28px; line-height: 1;"><i class="fas fa-minus small"></i></button>
                            <input type="number" class="form-control text-center cart-qty-input font-monospace fw-bold py-0" value="${item.quantity}" min="0" max="${item.stock_qty}" style="max-width: 60px; min-width: 50px; height: 28px; font-size: 0.95rem;">
                            <button class="btn btn-outline-secondary btn-qty-plus py-0 px-2" type="button" style="height: 28px; line-height: 1;"><i class="fas fa-plus small"></i></button>
                        </div>
                    </td>
                    <td class="text-end">
                        <input type="number" class="form-control form-control-sm text-end cart-item-discount-input font-monospace py-0 px-1" value="${item.discount}" min="0" step="0.01" style="max-width: 70px; height: 26px; margin-left: auto;">
                    </td>
                    <td class="text-end font-monospace fw-bold">Rs. ${itemNet.toFixed(2)}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-link text-danger p-0 btn-remove-item" type="button"><i class="fas fa-trash-can"></i></button>
                    </td>
                </tr>
            `;
        });
        
        $('#cartTableBody').html(html);
        calculateTotals(subtotal);

        if (highlightProductId) {
            // Smoothly ensure the top of the cart table is visible for the newly added/re-scanned item
            const $wrapper = $('.pos-cart-table-wrapper');
            if ($wrapper.length) {
                $wrapper.scrollTop(0);
            }
            setTimeout(() => {
                $(`#cartTableBody tr[data-product-id="${highlightProductId}"]`).removeClass('table-success scan-highlight cart-row-new');
            }, 750);
        }
    }

    // Totals calculations
    function calculateTotals(subtotal = null) {
        if (subtotal === null) {
            subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
        }
        
        const itemDiscountsSum = cart.reduce((sum, item) => sum + item.discount, 0);
        const invoiceDiscount = parseFloat($('#invoiceDiscountInput').val()) || 0;
        
        const totalDiscount = itemDiscountsSum + invoiceDiscount;
        const grandTotal = Math.max(0, subtotal - totalDiscount);
        
        $('#cartSubtotalText').text(`Rs. ${subtotal.toFixed(2)}`);
        $('#cartTotalDiscountText').text(`Rs. ${totalDiscount.toFixed(2)}`);
        $('#cartTotalText').text(`Rs. ${grandTotal.toFixed(2)}`);
        
        // Return change logic (Cash Tendered is optional; if empty/zero, change is Rs. 0.00)
        const rawCashStr = ($('#cashPaidInput').val() || '').trim();
        const paid = parseFloat(rawCashStr) || 0;
        const paymentMethod = $('input[name="payment_method"]:checked').val();
        
        if (paymentMethod === 'cash') {
            if (rawCashStr === '' || paid === 0) {
                $('#cashChangeText').text('Rs. 0.00');
            } else {
                const change = Math.max(0, paid - grandTotal);
                $('#cashChangeText').text(`Rs. ${change.toFixed(2)}`);
            }
        } else {
            $('#cashChangeText').text('Rs. 0.00');
        }

        updateKhataSettlementCalculations();
    }

    // Qty click events
    $(document).on('click', '.btn-qty-plus', function() {
        const index = $(this).closest('tr').data('index');
        const item = cart[index];
        if (item.quantity + 1 > item.stock_qty) {
            showToast(`Insufficient stock! Max available is ${item.stock_qty}`, 'warning');
            return;
        }
        item.quantity += 1;
        renderCart();
    });
    
    $(document).on('click', '.btn-qty-minus', function() {
        const index = $(this).closest('tr').data('index');
        const item = cart[index];
        if (item.quantity > 1) {
            item.quantity -= 1;
        } else {
            cart.splice(index, 1);
        }
        renderCart();
    });
    
    $(document).on('change', '.cart-qty-input', function() {
        const index = $(this).closest('tr').data('index');
        const item = cart[index];
        const val = parseInt($(this).val()) || 0;
        
        if (val < 1) {
            cart.splice(index, 1);
        } else if (val > item.stock_qty) {
            showToast(`Insufficient stock! Setting to max available (${item.stock_qty})`, 'warning');
            item.quantity = item.stock_qty;
        } else {
            item.quantity = val;
        }
        renderCart();
    });

    // Discount edit events
    $(document).on('change', '.cart-item-discount-input', function() {
        const index = $(this).closest('tr').data('index');
        const item = cart[index];
        const discountVal = parseFloat($(this).val()) || 0.00;
        const maxVal = item.quantity * item.selling_price;
        
        if (discountVal < 0) {
            item.discount = 0.00;
        } else if (discountVal > maxVal) {
            showToast("Item discount cannot exceed item total price!", 'danger');
            item.discount = maxVal;
        } else {
            item.discount = discountVal;
        }
        renderCart();
    });

    // Custom / Special Price edit event (for friends / neighbor stores / wholesale)
    $(document).on('change', '.cart-item-price-input', function() {
        const index = $(this).closest('tr').data('index');
        const item = cart[index];
        const newPrice = parseFloat($(this).val());
        if (isNaN(newPrice) || newPrice < 0) {
            showToast("Invalid price entered!", 'danger');
            $(this).val(item.selling_price.toFixed(2));
            return;
        }
        item.selling_price = newPrice;
        renderCart();
    });

    // Remove single row item
    $(document).on('click', '.btn-remove-item', function() {
        const index = $(this).closest('tr').data('index');
        cart.splice(index, 1);
        renderCart();
    });

    // Clear entire cart
    $('#clearCartBtn').on('click', function() {
        if (cart.length > 0 && confirm("Are you sure you want to clear the active cart?")) {
            cart = [];
            renderCart();
        }
    });

    // Change invoice discount inputs
    $('#invoiceDiscountInput').on('input change', function() {
        calculateTotals();
        renderCart(); // Sync floating View Cart header values
    });
    
    // Cash Paid inputs & Enter to complete sale
    $('#cashPaidInput').on('input change', function() {
        calculateTotals();
    }).on('keydown', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#checkoutBtn').click();
        }
    });

    // Khata Customers Data and Live Calculation Logic
    let khataCustomers = [];

    function loadKhataCustomers(selectId = null) {
        $.ajax({
            url: BASE_URL + '/api/khata.php?action=list_customers',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.customers) {
                    khataCustomers = response.customers;
                    let opts = '<option value="">-- Choose Khata Customer --</option>';
                    khataCustomers.forEach(c => {
                        const bal = parseFloat(c.current_balance) || 0;
                        const balStr = bal > 0 ? ` [Due: Rs. ${bal.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2})}]` : ' [Cleared]';
                        const typeStr = c.customer_type ? ` (${c.customer_type.toUpperCase()})` : '';
                        opts += `<option value="${c.id}" data-phone="${c.phone || ''}" data-balance="${bal}" data-limit="${c.credit_limit || 0}" data-type="${c.customer_type || ''}" ${selectId == c.id ? 'selected' : ''}>${c.name}${typeStr}${balStr}</option>`;
                    });
                    $('#posKhataCustomerSelect').html(opts);
                    if (selectId) {
                        $('#posKhataCustomerSelect').val(selectId).trigger('change');
                    } else {
                        updateKhataSettlementCalculations();
                    }
                }
            }
        });
    }

    // Auto-load customers on page load for rapid offline/cache accessibility
    loadKhataCustomers();

    // Customer dropdown change listener
    $('#posKhataCustomerSelect').on('change', function() {
        const selectedId = $(this).val();
        if (!selectedId) {
            $('#posKhataCustInfoRibbon').addClass('d-none');
            updateKhataSettlementCalculations();
            return;
        }
        const opt = $(this).find('option:selected');
        const phone = opt.data('phone') || 'None';
        const type = opt.data('type') || 'General';
        const bal = parseFloat(opt.data('balance')) || 0;
        const limit = parseFloat(opt.data('limit')) || 0;

        $('#khataCustMetaPhone').text(`${phone} (${type.toUpperCase()})`);
        $('#khataCustPrevDue').text(`Rs. ${bal.toFixed(2)}`);
        $('#khataCustLimitText').text(limit > 0 ? `Rs. ${limit.toFixed(2)}` : 'No Limit');
        $('#posKhataCustInfoRibbon').removeClass('d-none');

        updateKhataSettlementCalculations();
    });

    // Down payment input change listener
    $('#posKhataDownPayInput').on('input change', function() {
        updateKhataSettlementCalculations();
    });

    function updateKhataSettlementCalculations() {
        const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
        const itemDiscountsSum = cart.reduce((sum, item) => sum + item.discount, 0);
        const invoiceDiscount = parseFloat($('#invoiceDiscountInput').val()) || 0;
        const grandTotal = Math.max(0, subtotal - itemDiscountsSum - invoiceDiscount);

        const downPayment = Math.max(0, parseFloat($('#posKhataDownPayInput').val()) || 0);
        const netKhataCharge = Math.max(0, grandTotal - downPayment);

        const opt = $('#posKhataCustomerSelect').find('option:selected');
        const prevBal = parseFloat(opt.data('balance')) || 0;
        const projectedBal = prevBal + netKhataCharge;

        $('#khataNetBillCharge').text(`Rs. ${netKhataCharge.toFixed(2)}`);
        $('#khataProjectedTotalDue').text(`Rs. ${projectedBal.toFixed(2)}`);
    }

    // Open Quick Customer Modal
    $('#btnPosNewKhataCust').on('click', function() {
        $('#posQuickKhataCustomerForm')[0].reset();
        $('#quickKhataAlert').addClass('d-none').text('');
        const quickModal = new bootstrap.Modal(document.getElementById('modalQuickKhataCustomer'));
        quickModal.show();
    });

    // Save Quick Khata Customer
    $('#posQuickKhataCustomerForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnSaveQuickKhataCust');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
        $('#quickKhataAlert').addClass('d-none');

        const formData = {
            name: $('#quick_cust_name').val().trim(),
            phone: $('#quick_cust_phone').val().trim(),
            customer_type: $('#quick_cust_type').val(),
            credit_limit: $('#quick_cust_limit').val(),
            address: $('#quick_cust_address').val().trim(),
            notes: $('#quick_cust_notes').val().trim()
        };

        $.ajax({
            url: BASE_URL + '/api/pos.php?action=quick_save_customer',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Save & Select Account');
                if (response.success) {
                    showToast(response.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalQuickKhataCustomer')).hide();
                    loadKhataCustomers(response.customer.id);
                } else {
                    $('#quickKhataAlert').removeClass('d-none').text(response.message || 'Failed to create Khata account.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Save & Select Account');
                let msg = 'Connection error.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $('#quickKhataAlert').removeClass('d-none').text(msg);
            }
        });
    });

    // Payment methods toggle
    $('input[name="payment_method"]').on('change', function() {
        const val = $(this).val();
        if (val === 'cash') {
            $('#cashCalculationGroup').removeClass('d-none');
            $('#khataCalculationGroup').addClass('d-none');
            $('#cashPaidInput').prop('required', false);
        } else if (val === 'khata') {
            $('#cashCalculationGroup').addClass('d-none');
            $('#khataCalculationGroup').removeClass('d-none');
            $('#cashPaidInput').prop('required', false);
            if (khataCustomers.length === 0) {
                loadKhataCustomers();
            } else {
                updateKhataSettlementCalculations();
            }
        } else {
            $('#cashCalculationGroup').addClass('d-none');
            $('#khataCalculationGroup').addClass('d-none');
            $('#cashPaidInput').prop('required', false);
        }
        calculateTotals();
    });

    // Hold (Suspend) Active Cart
    $('#holdCartBtn').on('click', function() {
        if (cart.length === 0) {
            showToast("Cannot suspend an empty cart.", 'warning');
            return;
        }
        
        const requestData = {
            items: cart.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity
            }))
        };
        
        $.ajax({
            url: BASE_URL + '/api/pos.php?action=hold',
            type: 'POST',
            data: requestData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast(`Cart suspended successfully. Code: ${response.hold_number}`, 'success');
                    cart = [];
                    renderCart();
                    loadHeldCarts();
                    bootstrap.Modal.getInstance(document.getElementById('posCartModal')).hide();
                } else {
                    showToast(response.message || 'Failed to suspend cart.', 'danger');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message 
                    ? xhr.responseJSON.message 
                    : 'Connection error occurred while holding cart.';
                showToast(msg, 'danger');
            }
        });
    });

    // Load Suspended Carts
    function loadHeldCarts() {
        $.ajax({
            url: BASE_URL + '/api/pos.php?action=list_held',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    $('#heldCartsCount').text(response.held.length);
                    
                    if (response.held.length === 0) {
                        html = '<tr><td colspan="4" class="text-center py-2 text-muted">No suspended bills active.</td></tr>';
                    } else {
                        response.held.forEach(function(hc) {
                            html += `
                                <tr>
                                    <td><strong class="text-danger">${hc.hold_number}</strong><br><small class="text-muted" style="font-size: 0.7rem;">${hc.created_at}</small></td>
                                    <td class="text-center fw-bold">${hc.item_count}</td>
                                    <td class="text-end font-monospace">Rs. ${parseFloat(hc.total_value).toFixed(2)}</td>
                                    <td class="text-end">
                                        <button class="btn btn-xs btn-success btn-resume-held" data-id="${hc.id}" title="Resume Bill"><i class="fas fa-play"></i></button>
                                        <button class="btn btn-xs btn-outline-danger btn-cancel-held" data-id="${hc.id}" title="Delete Bill"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#heldCartsTableBody').html(html);
                }
            }
        });
    }

    loadHeldCarts(); // Init loading

    // Resume Bill click with background hold
    $(document).on('click', '.btn-resume-held', function() {
        const id = $(this).data('id');
        
        if (cart.length > 0) {
            const requestData = {
                items: cart.map(item => ({
                    product_id: item.product_id,
                    quantity: item.quantity
                }))
            };
            
            // Step 1: Hold the current cart
            $.ajax({
                url: BASE_URL + '/api/pos.php?action=hold',
                type: 'POST',
                data: requestData,
                dataType: 'json',
                success: function(holdResponse) {
                    if (holdResponse.success) {
                        showToast(`Current cart automatically suspended. Code: ${holdResponse.hold_number}`, 'success');
                        // Step 2: Resume the selected cart
                        executeResume(id);
                    } else {
                        showToast('Failed to auto-suspend current active cart.', 'danger');
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.message 
                        ? xhr.responseJSON.message 
                        : 'Connection error during auto-suspend.';
                    showToast(msg, 'danger');
                }
            });
        } else {
            // If active cart is empty, resume directly
            executeResume(id);
        }
    });

    // Helper function to resume cart
    function executeResume(id) {
        $.ajax({
            url: `${BASE_URL}/api/pos.php?action=resume&id=${id}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    cart = response.items.map(item => ({
                        product_id: item.product_id,
                        name: item.name,
                        barcode: item.barcode,
                        selling_price: parseFloat(item.selling_price),
                        quantity: parseInt(item.quantity),
                        discount: 0.00,
                        stock_qty: parseInt(item.stock_qty)
                    }));
                    renderCart();
                    loadHeldCarts();
                    // Close held bills collapse for clean view
                    $('#heldCartsCollapse').collapse('hide');
                } else {
                    showToast(response.message || 'Failed to resume cart.', 'danger');
                }
            },
            error: function() {
                showToast('Connection error during resume.', 'danger');
            }
        });
    }

    // Delete Suspended Bill click
    $(document).on('click', '.btn-cancel-held', function() {
        const id = $(this).data('id');
        if (confirm("Are you sure you want to permanently delete this suspended cart?")) {
            $.ajax({
                url: BASE_URL + '/api/pos.php?action=cancel_held',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        loadHeldCarts();
                    } else {
                        showToast(response.message, 'danger');
                    }
                }
            });
        }
    });

    // Checkout Submit Click
    $('#checkoutBtn').on('click', function() {
        if (cart.length === 0) {
            showToast("Cannot checkout empty cart.", 'warning');
            return;
        }
        
        const discount = parseFloat($('#invoiceDiscountInput').val()) || 0.00;
        const rawCashStr = ($('#cashPaidInput').val() || '').trim();
        let paid = parseFloat(rawCashStr);
        const paymentMethod = $('input[name="payment_method"]:checked').val();
        
        const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
        const itemDiscountsSum = cart.reduce((sum, item) => sum + item.discount, 0);
        const grandTotal = Math.max(0, subtotal - itemDiscountsSum - discount);
        
        if (paymentMethod === 'cash') {
            // Cash Tendered is optional: if left blank or 0, default to exact cash payment
            if (isNaN(paid) || rawCashStr === '' || paid <= 0) {
                paid = grandTotal;
            } else if (paid < grandTotal) {
                showToast(`Entered cash (Rs. ${paid.toFixed(2)}) is less than total bill (Rs. ${grandTotal.toFixed(2)}). Leave empty for exact payment.`, 'warning');
                $('#cashPaidInput').focus();
                return;
            }
        }

        let customerId = 0;
        let khataDownPayment = 0.00;

        if (paymentMethod === 'khata') {
            customerId = parseInt($('#posKhataCustomerSelect').val()) || 0;
            if (customerId <= 0) {
                showToast("Please select a Khata customer or create a new account.", 'warning');
                $('#posKhataCustomerSelect').focus();
                return;
            }
            khataDownPayment = Math.max(0, parseFloat($('#posKhataDownPayInput').val()) || 0);
            if (khataDownPayment > grandTotal) {
                showToast(`Down payment (Rs. ${khataDownPayment.toFixed(2)}) cannot exceed bill total (Rs. ${grandTotal.toFixed(2)})!`, 'warning');
                return;
            }
        }
        
        $(this).prop('disabled', true);
        
        const requestData = {
            items: cart.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                unit_price: item.selling_price,
                discount_override: item.discount
            })),
            discount: discount,
            paid: (paymentMethod === 'khata') ? khataDownPayment : paid,
            payment_method: paymentMethod,
            customer_id: customerId,
            khata_down_payment: khataDownPayment
        };
        
        $.ajax({
            url: BASE_URL + '/api/pos.php?action=checkout',
            type: 'POST',
            data: requestData,
            dataType: 'json',
            success: function(response) {
                $('#checkoutBtn').prop('disabled', false);
                if (response.success) {
                    renderPrintTemplate(
                        response.bill_number,
                        subtotal,
                        discount,
                        grandTotal,
                        (paymentMethod === 'khata' ? khataDownPayment : paid),
                        paymentMethod,
                        response.khata
                    );
                    
                    // Close Cart modal
                    const cartModalEl = document.getElementById('posCartModal');
                    const cartModal = bootstrap.Modal.getInstance(cartModalEl);
                    if (cartModal) {
                        cartModal.hide();
                    }
                    
                    // Directly trigger clean thermal receipt print via isolated iframe (no blocking popup)
                    const receiptHtml = $('#receiptTemplateContainer').html();
                    if (window.printThermalReceipt) {
                        window.printThermalReceipt(receiptHtml);
                    } else {
                        window.print();
                    }
                    
                    // Direct continuous cashier workflow: Immediately clear cart and reset fields
                    cart = [];
                    renderCart();
                    $('#invoiceDiscountInput').val(0);
                    $('#cashPaidInput').val('');
                    $('#posKhataCustomerSelect').val('');
                    $('#posKhataDownPayInput').val(0);
                    $('#posKhataCustInfoRibbon').addClass('d-none');
                    $('#pay_cash').prop('checked', true).trigger('change');
                    loadHeldCarts();
                    loadCatalog(); // Refresh catalog stock levels
                    loadKhataCustomers(); // Refresh live Khata balances

                    showToast(`Bill #${response.bill_number} checked out & sent to printer.`, 'success');

                    // Immediately re-focus barcode search field so cashier can scan next customer right away
                    setTimeout(function() {
                        $('#productSearchInput').focus();
                    }, 200);
                } else {
                    showToast(response.message || 'Checkout failed.', 'danger');
                }
            },
            error: function(xhr) {
                $('#checkoutBtn').prop('disabled', false);
                let msg = 'Connection error.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
            }
        });
    });

    // Close invoice template print dialog
    $('#closePrintModalBtn').on('click', function() {
        cart = [];
        renderCart();
        $('#invoiceDiscountInput').val(0);
        $('#cashPaidInput').val('');
        $('#posKhataCustomerSelect').val('');
        $('#posKhataDownPayInput').val(0);
        $('#posKhataCustInfoRibbon').addClass('d-none');
        $('#pay_cash').prop('checked', true).trigger('change');
        const m = bootstrap.Modal.getInstance(document.getElementById('receiptPrintModal'));
        if (m) m.hide();
        loadHeldCarts();
        loadCatalog(); // Dynamic sync
        loadKhataCustomers();
    });

    // Manual reprint from modal
    $('#reprintThermalInvoiceBtn').on('click', function() {
        const receiptHtml = $('#receiptTemplateContainer').html();
        if (window.printThermalReceipt) {
            window.printThermalReceipt(receiptHtml);
        } else {
            window.print();
        }
    });

    // Render receipt template HTML
    function renderPrintTemplate(billNumber, subtotal, invoiceDiscount, grandTotal, paid, paymentMethod, khata = null) {
        const dateNow = new Date();
        const formattedDate = dateNow.getFullYear() + '-' + 
                              String(dateNow.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(dateNow.getDate()).padStart(2, '0') + ' ' + 
                              String(dateNow.getHours()).padStart(2, '0') + ':' + 
                              String(dateNow.getMinutes()).padStart(2, '0');
        
        let itemRows = '';
        const totalQty = cart.reduce((sum, item) => sum + item.quantity, 0);
        
        cart.forEach((item, idx) => {
            const rowTotal = (item.quantity * item.selling_price) - item.discount;
            itemRows += `
                <tr>
                    <td colspan="4" style="padding-top: 4px; font-weight: 900; font-size: 11.5px; color: #000;">
                        ${idx + 1}. ${item.name}
                    </td>
                </tr>
                <tr style="border-bottom: 1px dashed #000;">
                    <td style="padding-bottom: 3px; font-size: 9.5px; font-weight: 800; color: #000;">${item.barcode}</td>
                    <td align="center" style="padding-bottom: 3px; font-weight: 900; font-size: 11px; color: #000;">${item.quantity}</td>
                    <td class="right" style="padding-bottom: 3px; font-weight: 800; font-size: 10.5px; color: #000;">${parseFloat(item.selling_price).toFixed(2)}</td>
                    <td class="right" style="padding-bottom: 3px; font-weight: 900; font-size: 11px; color: #000;">${rowTotal.toFixed(2)}</td>
                </tr>
                ${item.discount > 0 ? `
                <tr>
                    <td colspan="4" class="right" style="font-size: 10px; font-weight: 800; color: #000; padding-bottom: 2px;">
                        Item Disc: -Rs. ${parseFloat(item.discount).toFixed(2)}
                    </td>
                </tr>` : ''}
            `;
        });
        
        const changeDue = paymentMethod === 'cash' ? Math.max(0, paid - grandTotal) : 0.00;
        const typeLabel = (paymentMethod === 'khata') ? 'KHATA / CREDIT' : paymentMethod.toUpperCase();
        
        const template = `
            <div class="center" style="margin-bottom: 6px; color: #000;">
                <img src="${BASE_URL}/logo/${window.STORE_LOGO || 'one_dollar_shop_logo.png'}" alt="${window.STORE_NAME || 'One Dollar Shop'}" class="receipt-logo">
                <h3 style="margin: 0; font-size: 16.5px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase; color: #000;">${(window.STORE_NAME || 'ONE DOLLAR SHOP').toUpperCase()}</h3>
                <p style="margin: 2px 0; font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #000;">${window.STORE_ADDRESS || 'McConaghey Road, Quetta'}</p>
                <div style="font-size: 12px; font-weight: 900; margin-top: 2px; color: #000;">Ph: ${window.STORE_PHONE || '0307-2681893'}</div>
            </div>
            <div class="receipt-divider"></div>
            <table style="width: 100%; font-size: 10.5px; font-weight: 800; line-height: 1.4; color: #000;">
                <tr>
                    <td><strong>Bill #:</strong> <span style="font-weight: 900;">${billNumber}</span></td>
                    <td class="right"><strong>Type:</strong> <span style="font-weight: 900;">${typeLabel} SALE</span></td>
                </tr>
                <tr>
                    <td><strong>Date:</strong> ${formattedDate}</td>
                    <td class="right"><strong>Cashier:</strong> Staff</td>
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
                    <td class="right" style="font-weight: 900;">${cart.length} / ${totalQty}</td>
                </tr>
                <tr>
                    <td style="font-weight: 800;">Subtotal:</td>
                    <td class="right" style="font-weight: 800;">Rs. ${subtotal.toFixed(2)}</td>
                </tr>
                ${invoiceDiscount > 0 ? `
                <tr>
                    <td style="font-weight: 800;">Bill Discount:</td>
                    <td class="right" style="font-weight: 900;">-Rs. ${invoiceDiscount.toFixed(2)}</td>
                </tr>` : ''}
                <tr style="font-size: 14px; font-weight: 900; border-top: 2px dashed #000; border-bottom: 2px dashed #000;">
                    <td style="padding: 5px 0;">NET PAYABLE:</td>
                    <td class="right" style="padding: 5px 0;">Rs. ${grandTotal.toFixed(2)}</td>
                </tr>
                ${khata ? `
                <tr>
                    <td style="padding-top: 4px; font-weight: 800;">Paid Now (Cash):</td>
                    <td class="right" style="padding-top: 4px; font-weight: 900;">Rs. ${parseFloat(khata.down_payment).toFixed(2)}</td>
                </tr>
                <tr style="font-weight: 900;">
                    <td>Added to Khata:</td>
                    <td class="right">+Rs. ${parseFloat(khata.net_charge).toFixed(2)}</td>
                </tr>
                <tr>
                    <td style="font-weight: 800;">Previous Due:</td>
                    <td class="right" style="font-weight: 800;">Rs. ${parseFloat(khata.previous_balance).toFixed(2)}</td>
                </tr>
                <tr style="font-size: 14px; font-weight: 900; border-top: 2px dashed #000; border-bottom: 2px dashed #000;">
                    <td style="padding: 5px 0;">TOTAL LEDGER DUE:</td>
                    <td class="right" style="padding: 5px 0;">Rs. ${parseFloat(khata.new_balance).toFixed(2)}</td>
                </tr>
                ` : `
                <tr>
                    <td style="padding-top: 4px; font-weight: 800;">Paid (${paymentMethod.toUpperCase()}):</td>
                    <td class="right" style="padding-top: 4px; font-weight: 900;">Rs. ${paid.toFixed(2)}</td>
                </tr>
                ${paymentMethod === 'cash' ? `
                <tr style="font-weight: 900;">
                    <td>Change Return:</td>
                    <td class="right">Rs. ${changeDue.toFixed(2)}</td>
                </tr>` : ''}
                `}
            </table>
            <div class="receipt-divider"></div>
            <div class="center" style="font-size: 10px; font-weight: 800; line-height: 1.4; margin-top: 4px; color: #000;">
                ${khata ? '<div style="font-size: 11px; font-weight: 900; margin-bottom: 2px;">*** CREDIT SALE RECORDED IN KHATA LEDGER ***</div><div>Please clear pending balance in timely manner.</div>' : ''}
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

    // Live Autocomplete Search inside Modal
    $('#modalCartSearchInput').on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        if (query.length === 0) {
            $('#modalSearchResultsPanel').addClass('d-none');
            return;
        }
        
        // Filter catalog cache locally
        const matches = catalogProducts.filter(p => {
            const nameMatch = p.name.toLowerCase().includes(query);
            const barcodeMatch = p.barcode.toLowerCase().includes(query);
            const catMatch = p.category_name ? p.category_name.toLowerCase().includes(query) : false;
            return nameMatch || barcodeMatch || catMatch;
        });
        
        if (matches.length > 0) {
            let html = '';
            matches.forEach(p => {
                html += `
                    <button type="button" class="list-group-item list-group-item-action modal-search-result-item d-flex justify-content-between align-items-center py-2 text-start"
                        data-id="${p.id}"
                        data-name="${p.name}"
                        data-barcode="${p.barcode}"
                        data-price="${p.selling_price}"
                        data-stock="${p.quantity}">
                        <div>
                            <strong class="text-dark">${p.name}</strong><br>
                            <small class="text-muted">Barcode: <code>${p.barcode}</code></small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-danger">Rs. ${parseFloat(p.selling_price).toFixed(2)}</span><br>
                            <small class="text-muted">Stock: ${p.quantity}</small>
                        </div>
                    </button>
                `;
            });
            $('#modalSearchResultsList').html(html);
            $('#modalSearchResultsPanel').removeClass('d-none');
        } else {
            $('#modalSearchResultsList').html('<div class="p-3 text-center text-muted">No matching items found.</div>');
            $('#modalSearchResultsPanel').removeClass('d-none');
        }
    });

    // Click handler for modal search results
    $(document).on('click', '.modal-search-result-item', function() {
        const item = {
            product_id: $(this).data('id'),
            name: $(this).data('name'),
            barcode: $(this).data('barcode'),
            selling_price: $(this).data('price'),
            stock_qty: $(this).data('stock')
        };
        const added = addToCart(item);
        if (added) playScanSound(true);
        $('#modalCartSearchInput').val('').focus();
        $('#modalSearchResultsPanel').addClass('d-none');
    });

    // Hide modal search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#modalCartSearchInput, #modalSearchResultsPanel').length) {
            $('#modalSearchResultsPanel').addClass('d-none');
        }
        if (!$(e.target).closest('#posSearchInput, #mainSearchResultsPanel').length) {
            $('#mainSearchResultsPanel').addClass('d-none');
        }
    });

    // Live Autocomplete Search on Main Screen
    $('#posSearchInput').on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        if (query.length === 0) {
            $('#mainSearchResultsPanel').addClass('d-none');
            return;
        }
        
        // Filter catalog cache locally
        const matches = catalogProducts.filter(p => {
            const nameMatch = p.name.toLowerCase().includes(query);
            const barcodeMatch = p.barcode.toLowerCase().includes(query);
            const catMatch = p.category_name ? p.category_name.toLowerCase().includes(query) : false;
            return nameMatch || barcodeMatch || catMatch;
        });
        
        if (matches.length > 0) {
            let html = '';
            matches.forEach(p => {
                html += `
                    <button type="button" class="list-group-item list-group-item-action main-search-result-item d-flex justify-content-between align-items-center py-2 text-start"
                        data-id="${p.id}"
                        data-name="${p.name}"
                        data-barcode="${p.barcode}"
                        data-price="${p.selling_price}"
                        data-stock="${p.quantity}">
                        <div>
                            <strong class="text-dark">${p.name}</strong><br>
                            <small class="text-muted">Barcode: <code>${p.barcode}</code></small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-danger">Rs. ${parseFloat(p.selling_price).toFixed(2)}</span><br>
                            <small class="text-muted">Stock: ${p.quantity}</small>
                        </div>
                    </button>
                `;
            });
            $('#mainSearchResultsList').html(html);
            $('#mainSearchResultsPanel').removeClass('d-none');
        } else {
            $('#mainSearchResultsList').html('<div class="p-3 text-center text-muted">No matching items found.</div>');
            $('#mainSearchResultsPanel').removeClass('d-none');
        }
    });

    // Click handler for main search results
    $(document).on('click', '.main-search-result-item', function() {
        const item = {
            product_id: $(this).data('id'),
            name: $(this).data('name'),
            barcode: $(this).data('barcode'),
            selling_price: $(this).data('price'),
            stock_qty: $(this).data('stock')
        };
        const added = addToCart(item);
        if (added) playScanSound(true);
        $('#posSearchInput').val('');
        $('#mainSearchResultsPanel').addClass('d-none');
        openCartModal();
    });

    // =========================================================================
    // QUICK EXPENSE SHORTCUT (FOR CASHIER CONVENIENCE)
    // =========================================================================
    let posExpenseCatsLoaded = false;

    $('#btnPosQuickExpense').on('click', function() {
        $('#posExpAlert').addClass('d-none');
        $('#pos_exp_amount').val('');
        $('#pos_exp_desc').val('');
        if ($('#pos_exp_cat option').length) {
            $('#pos_exp_cat').prop('selectedIndex', 0);
        }
        
        if (!posExpenseCatsLoaded) {
            $.ajax({
                url: BASE_URL + '/api/expenses.php?action=list_categories',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.success && res.categories) {
                        let opts = '';
                        res.categories.forEach((c, idx) => {
                            opts += `<option value="${c.id}" ${idx === 0 ? 'selected' : ''}>${c.name}</option>`;
                        });
                        $('#pos_exp_cat').html(opts);
                        posExpenseCatsLoaded = true;
                    }
                }
            });
        }
        
        $('#posQuickExpenseModal').modal('show');
        setTimeout(() => $('#pos_exp_amount').focus(), 400);
    });

    $('#posQuickExpenseForm').on('submit', function(e) {
        e.preventDefault();
        const catId = $('#pos_exp_cat').val();
        const amt = parseFloat($('#pos_exp_amount').val()) || 0;
        const desc = $('#pos_exp_desc').val().trim();

        if (!catId || amt <= 0) {
            $('#posExpAlert').text('Please select a category and enter amount.').removeClass('d-none');
            return;
        }

        $('#btnSavePosExpense').prop('disabled', true);

        $.ajax({
            url: BASE_URL + '/api/expenses.php?action=add',
            type: 'POST',
            data: {
                category_id: catId,
                amount: amt,
                description: desc,
                payment_source: 'drawer_cash',
                payment_method: 'cash'
            },
            dataType: 'json',
            success: function(res) {
                $('#btnSavePosExpense').prop('disabled', false);
                if (res.success) {
                    $('#posQuickExpenseModal').modal('hide');
                    showToast(`Expense recorded: Rs. ${amt.toFixed(2)} (Drawer Cash)`, 'warning');
                    setTimeout(() => $('#posSearchInput').focus(), 300);
                } else {
                    $('#posExpAlert').text(res.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#btnSavePosExpense').prop('disabled', false);
                $('#posExpAlert').text(xhr.responseJSON?.message || 'Error recording expense.').removeClass('d-none');
            }
        });
    });

    // =========================================================================
    // GLOBAL HARDWARE BARCODE SCANNER WEDGE DETECTOR & SHORTCUTS
    // =========================================================================
    let globalScanBuffer = '';
    let lastKeypressTimestamp = 0;

    $(document).on('keydown', function(e) {
        const activeEl = document.activeElement;
        const tagName = activeEl ? activeEl.tagName : '';
        const isEditable = (tagName === 'INPUT' || tagName === 'TEXTAREA' || (activeEl && activeEl.isContentEditable));
        const isBarcodeField = activeEl && (activeEl.id === 'posSearchInput' || activeEl.id === 'modalCartSearchInput');

        // Hotkey: F2 or Ctrl+Space toggles Cart Modal
        if (e.key === 'F2' || (e.ctrlKey && e.code === 'Space')) {
            e.preventDefault();
            if ($('#posCartModal').hasClass('show')) {
                bootstrap.Modal.getInstance(document.getElementById('posCartModal'))?.hide();
            } else {
                openCartModal();
            }
            return;
        }

        // Hotkey: F4 focuses Cash Tendered input when cart is open
        if (e.key === 'F4' && $('#posCartModal').hasClass('show')) {
            e.preventDefault();
            $('#cashPaidInput').focus().select();
            return;
        }

        // Hotkey: Ctrl+Enter completes sale immediately
        if (e.ctrlKey && e.which === 13) {
            e.preventDefault();
            if ($('#posCartModal').hasClass('show')) {
                $('#checkoutBtn').click();
            } else if (cart.length > 0) {
                openCartModal();
            }
            return;
        }

        // If focused inside an editable text/number input (like discount or expense amount), do not intercept
        if (isEditable && !isBarcodeField) {
            globalScanBuffer = '';
            return;
        }

        // Hardware scanner finishes barcode with Enter (code 13)
        if (e.which === 13) {
            if (!isEditable && globalScanBuffer.length >= 2) {
                e.preventDefault();
                const barcode = globalScanBuffer.trim();
                globalScanBuffer = '';
                performAddByQuery(barcode, function(success) {
                    if (success && !$('#posCartModal').hasClass('show')) {
                        openCartModal();
                    } else if ($('#posCartModal').hasClass('show')) {
                        $('#modalCartSearchInput').focus();
                    }
                });
                return;
            }
        }

        // Accumulate rapid keystrokes from barcode scanner wedge (key delta < 65ms)
        const now = Date.now();
        if (now - lastKeypressTimestamp > 65) {
            globalScanBuffer = ''; // Reset buffer if typing was slow (human typing)
        }

        if (e.key && e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
            globalScanBuffer += e.key;
            lastKeypressTimestamp = now;
        }
    });

    // Auto-refocus scanner input on click outside editable elements
    $(document).on('click', function(e) {
        const target = e.target;
        const isInteractive = $(target).closest('input, textarea, select, button, .modal, .dropdown-menu, a').length > 0;
        if (!isInteractive) {
            if ($('#posCartModal').hasClass('show')) {
                $('#modalCartSearchInput').focus();
            } else {
                $('#posSearchInput').focus();
            }
        }
    });

    // Prevent accidental scroll changes on any number input in POS
    $(document).on('wheel', 'input[type=number]', function() {
        $(this).blur();
    });
});

