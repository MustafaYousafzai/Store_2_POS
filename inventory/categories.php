<?php
require_once __DIR__ . '/../config/helper.php';
redirect('/inventory/index.php');
require_once __DIR__ . '/../includes/header.php';
requirePermission('MANAGE_INVENTORY');
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white font-weight-bold">
                <h5 class="mb-0" id="formTitle"><i class="fas fa-plus me-2"></i>Add New Category</h5>
            </div>
            <div class="card-body">
                <div id="formAlert" class="alert alert-danger d-none"></div>
                <div id="formSuccess" class="alert alert-success d-none">Category saved successfully.</div>
                
                <form id="categoryForm">
                    <input type="hidden" id="category_id" name="id" value="0">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3 d-none" id="statusGroup">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" id="saveBtn">Save Category</button>
                        <button type="button" class="btn btn-outline-secondary d-none" id="cancelEditBtn">Cancel Edit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Categories List</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th>Category Name</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 120px;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categoriesTableBody">
                            <tr>
                                <td colspan="4" class="text-center py-4">Loading categories...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadCategories();
    
    // Form submit
    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        
        const id = $('#category_id').val();
        const action = id == 0 ? 'add_category' : 'update_category';
        
        $('#formAlert').addClass('d-none');
        $('#formSuccess').addClass('d-none');
        
        $.ajax({
            url: `${(window.BASE_URL || "")}/api/products.php?action=${action}`,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#formSuccess').text(response.message).removeClass('d-none');
                    resetForm();
                    loadCategories();
                } else {
                    $('#formAlert').text(response.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                let msg = 'Failed to save category.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $('#formAlert').text(msg).removeClass('d-none');
            }
        });
    });
    
    // Edit click
    $(document).on('click', '.edit-category', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const status = $(this).data('status');
        
        $('#category_id').val(id);
        $('#name').val(name);
        $('#status').val(status);
        
        $('#formTitle').html('<i class="fas fa-edit me-2"></i>Edit Category');
        $('#statusGroup').removeClass('d-none');
        $('#cancelEditBtn').removeClass('d-none');
        $('#saveBtn').text('Update Category');
    });
    
    // Cancel Edit click
    $('#cancelEditBtn').on('click', function() {
        resetForm();
    });
    
    function resetForm() {
        $('#category_id').val(0);
        $('#name').val('');
        $('#status').val('active');
        $('#formTitle').html('<i class="fas fa-plus me-2"></i>Add New Category');
        $('#statusGroup').addClass('d-none');
        $('#cancelEditBtn').addClass('d-none');
        $('#saveBtn').text('Save Category');
        $('#formAlert').addClass('d-none');
        setTimeout(function() {
            $('#formSuccess').addClass('d-none');
        }, 3000);
    }
    
    function loadCategories() {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/products.php?action=get_categories',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (response.categories.length === 0) {
                        html = '<tr><td colspan="4" class="text-center py-4">No categories found.</td></tr>';
                    } else {
                        response.categories.forEach(function(cat) {
                            const statusBadge = cat.status === 'active' 
                                ? '<span class="badge bg-success-subtle text-success border border-success">Active</span>' 
                                : '<span class="badge bg-danger-subtle text-danger border border-danger">Inactive</span>';
                            
                            html += `
                                <tr>
                                    <td>${cat.id}</td>
                                    <td><strong>${cat.name}</strong></td>
                                    <td>${statusBadge}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary edit-category" 
                                            data-id="${cat.id}" 
                                            data-name="${cat.name}" 
                                            data-status="${cat.status}">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#categoriesTableBody').html(html);
                } else {
                    $('#categoriesTableBody').html('<tr><td colspan="4" class="text-center text-danger py-4">Failed to load categories.</td></tr>');
                }
            },
            error: function() {
                $('#categoriesTableBody').html('<tr><td colspan="4" class="text-center text-danger py-4">Error connecting to server.</td></tr>');
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
