<?php
require_once __DIR__ . '/../config/auth.php';

// Strict Admin-Only access control
if (!isAdmin()) {
    redirect('/pos/index.php');
}

require_once __DIR__ . '/../includes/header.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUsername = $_SESSION['username'] ?? 'User';
?>

<div class="container-fluid px-2 px-md-3 py-3">
    <!-- Top Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1 d-flex align-items-center">
                <i class="fas fa-users-gear text-danger me-2"></i> User Accounts Management
            </h3>
            <p class="text-muted mb-0 small">Create and manage Administrator and Cashier accounts, reset passwords, and control logins.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm px-3 py-2 fw-semibold rounded-pill shadow-sm" id="btnRefreshUsers" title="Refresh User List">
                <i class="fas fa-rotate me-1"></i> Refresh
            </button>
            <button class="btn btn-danger btn-sm px-3 py-2 fw-semibold rounded-pill shadow-sm" id="btnOpenAddUser">
                <i class="fas fa-user-plus me-1"></i> Add New User
            </button>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">TOTAL USERS</div>
                        <h3 class="fw-bold mb-0 text-dark" id="kpiTotalUsers">-</h3>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-4">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">ADMINISTRATORS</div>
                        <h3 class="fw-bold mb-0 text-danger" id="kpiAdminUsers">-</h3>
                    </div>
                    <div class="bg-danger-subtle text-danger p-3 rounded-circle fs-4">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">CASHIERS</div>
                        <h3 class="fw-bold mb-0 text-success" id="kpiStaffUsers">-</h3>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle fs-4">
                        <i class="fas fa-cash-register"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-secondary">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">INACTIVE / DISABLED</div>
                        <h3 class="fw-bold mb-0 text-secondary" id="kpiInactiveUsers">-</h3>
                    </div>
                    <div class="bg-secondary-subtle text-secondary p-3 rounded-circle fs-4">
                        <i class="fas fa-user-slash"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
            <div class="row g-2 align-items-center justify-content-between">
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control bg-light border-start-0" id="userSearchInput" placeholder="Search username, role, status...">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 text-sm-end">
                    <span class="badge bg-light text-muted border px-2 py-1 small" id="userCountBadge">Loading...</span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="usersTable">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-3 ps-md-4" style="width: 70px;"># ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Access Scope</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th class="text-end pe-3 pe-md-4" style="min-width: 170px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div> Loading user accounts...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: ADD NEW USER                                           -->
<!-- ============================================================== -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white rounded-top-4 py-3 px-4">
                <h5 class="modal-title fw-bold" id="addUserModalLabel">
                    <i class="fas fa-user-plus me-2"></i> Add New User Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addUserForm" autocomplete="off">
                <div class="modal-body p-4">
                    <div class="alert alert-danger d-none" id="addUserAlert"></div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" class="form-control" name="username" id="addUsername" required placeholder="e.g. cashier2" autocomplete="off">
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Letters, numbers, underscores (3-50 chars).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control" name="password" id="addPassword" required placeholder="Minimum 4 characters" autocomplete="new-password">
                            <button class="btn btn-outline-secondary btn-toggle-pwd" type="button" data-target="#addPassword" title="Show/Hide Password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Account Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" id="addRole" required>
                            <option value="staff" selected>Cashier (POS & Counter Operations)</option>
                            <option value="admin">Administrator (Full System Control)</option>
                        </select>
                    </div>

                    <!-- Role Explanation Box -->
                    <div id="addRoleDescStaff" class="alert alert-primary-subtle border border-primary-subtle small mb-3">
                        <i class="fas fa-cash-register me-1 text-primary"></i>
                        <strong>Cashier Role:</strong> Standard sales counter access only — POS Terminal, Sales History & Reprints, Customer Credit Khata, Returns & Refunds, and Daily Tea/Meal Expenses. Cannot view Dashboard, Inventory, or Admin settings.
                    </div>
                    <div id="addRoleDescAdmin" class="alert alert-danger-subtle border border-danger-subtle small mb-3 d-none">
                        <i class="fas fa-shield-halved me-1 text-danger"></i>
                        <strong>Administrator Role:</strong> Full system access — Profit & Loss Dashboard, Product Catalog, Inventory Management, Bill Voiding, Expenses, Employees, and User Accounts.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Account Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" id="addStatus" required>
                            <option value="active" selected>Active (Can Login)</option>
                            <option value="inactive">Inactive (Disabled)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4" id="btnAddUserSubmit">
                        <span class="btn-text"><i class="fas fa-check me-1"></i> Create User</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: EDIT USER                                              -->
<!-- ============================================================== -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white rounded-top-4 py-3 px-4">
                <h5 class="modal-title fw-bold" id="editUserModalLabel">
                    <i class="fas fa-user-pen me-2 text-warning"></i> Edit User Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm" autocomplete="off">
                <input type="hidden" name="id" id="editUserId">
                <div class="modal-body p-4">
                    <div class="alert alert-danger d-none" id="editUserAlert"></div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" class="form-control" name="username" id="editUsername" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">New Password <span class="text-muted fw-normal">(Optional - leave blank to keep current)</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control" name="password" id="editPassword" placeholder="Leave blank to keep unchanged" autocomplete="new-password">
                            <button class="btn btn-outline-secondary btn-toggle-pwd" type="button" data-target="#editPassword" title="Show/Hide Password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Account Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" id="editRole" required>
                            <option value="staff">Cashier (POS & Counter Operations)</option>
                            <option value="admin">Administrator (Full System Control)</option>
                        </select>
                    </div>

                    <!-- Role Explanation Box -->
                    <div id="editRoleDescStaff" class="alert alert-primary-subtle border border-primary-subtle small mb-3">
                        <i class="fas fa-cash-register me-1 text-primary"></i>
                        <strong>Cashier Role:</strong> Counter access only: POS, Sales History, Customer Khata, Refunds & Exchanges, Counter Expenses.
                    </div>
                    <div id="editRoleDescAdmin" class="alert alert-danger-subtle border border-danger-subtle small mb-3 d-none">
                        <i class="fas fa-shield-halved me-1 text-danger"></i>
                        <strong>Administrator Role:</strong> Full system access across all modules, inventory, reports, and settings.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Account Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" id="editStatus" required>
                            <option value="active">Active (Can Login)</option>
                            <option value="inactive">Inactive (Disabled)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4" id="btnEditUserSubmit">
                        <span class="btn-text"><i class="fas fa-save me-1"></i> Save Changes</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: QUICK RESET PASSWORD                                   -->
<!-- ============================================================== -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning-subtle text-dark rounded-top-4 py-3 px-4 border-bottom">
                <h5 class="modal-title fw-bold" id="resetPasswordModalLabel">
                    <i class="fas fa-key text-warning me-2"></i> Reset User Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="resetPasswordForm" autocomplete="off">
                <input type="hidden" name="id" id="resetUserId">
                <div class="modal-body p-4">
                    <div class="alert alert-danger d-none" id="resetPasswordAlert"></div>

                    <div class="mb-3">
                        <span class="text-muted small">Target User:</span>
                        <h5 class="fw-bold text-dark mt-1" id="resetUserDisplay">-</h5>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control" name="new_password" id="resetNewPassword" required placeholder="Minimum 4 characters" autocomplete="new-password">
                            <button class="btn btn-outline-secondary btn-toggle-pwd" type="button" data-target="#resetNewPassword" title="Show/Hide Password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control" name="confirm_new_password" id="resetConfirmPassword" required placeholder="Repeat password" autocomplete="new-password">
                            <button class="btn btn-outline-secondary btn-toggle-pwd" type="button" data-target="#resetConfirmPassword" title="Show/Hide Password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold" id="btnResetPasswordSubmit">
                        <span class="btn-text"><i class="fas fa-check me-1"></i> Update Password</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: DELETE USER CONFIRMATION                               -->
<!-- ============================================================== -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white rounded-top-4 py-3 px-4">
                <h5 class="modal-title fw-bold" id="deleteUserModalLabel">
                    <i class="fas fa-trash-can me-2"></i> Delete User Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="deleteUserId">
                <div class="text-center mb-3">
                    <div class="avatar-lg bg-danger-subtle text-danger rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-2 fs-1" style="width: 70px; height: 70px;">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Are you sure you want to remove this user?</h5>
                    <p class="text-muted" id="deleteUserPromptText">User: <strong id="deleteUsernameDisplay"></strong></p>
                </div>

                <div class="alert alert-warning small border-0 bg-warning-subtle text-dark">
                    <i class="fas fa-shield-halved me-1 text-warning"></i>
                    <strong>Accounting Protection:</strong> If this account has recorded historical transactions (e.g. sales, receipts, inventory adjustments, or expenses), it will be <strong>safely disabled/deactivated</strong> instead of deleted to protect business financial records.
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-3 border-top">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="btnConfirmDeleteUser">
                    <span class="btn-text"><i class="fas fa-trash me-1"></i> Confirm Delete</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let allUsers = [];
    const currentUserId = <?= $currentUserId ?>;

    // -------------------------------------------------------------
    // 1. Password Visibility Toggle
    // -------------------------------------------------------------
    $(document).on('click', '.btn-toggle-pwd', function() {
        const targetInput = $($(this).data('target'));
        const icon = $(this).find('i');
        if (targetInput.attr('type') === 'password') {
            targetInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            targetInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Role helper descriptions toggle
    $('#addRole').on('change', function() {
        const role = $(this).val();
        if (role === 'admin') {
            $('#addRoleDescAdmin').removeClass('d-none');
            $('#addRoleDescStaff').addClass('d-none');
        } else {
            $('#addRoleDescStaff').removeClass('d-none');
            $('#addRoleDescAdmin').addClass('d-none');
        }
    });

    $('#editRole').on('change', function() {
        const role = $(this).val();
        if (role === 'admin') {
            $('#editRoleDescAdmin').removeClass('d-none');
            $('#editRoleDescStaff').addClass('d-none');
        } else {
            $('#editRoleDescStaff').removeClass('d-none');
            $('#editRoleDescAdmin').addClass('d-none');
        }
    });

    // -------------------------------------------------------------
    // 2. Load All Users
    // -------------------------------------------------------------
    function loadUsers() {
        $('#usersTableBody').html(`
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div> Loading user accounts...
                </td>
            </tr>
        `);

        $.ajax({
            url: window.BASE_URL + '/api/users.php?action=list',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.users) {
                    allUsers = res.users;
                    updateKPIs(allUsers);
                    renderUsersTable(allUsers);
                } else {
                    $('#usersTableBody').html(`<tr><td colspan="7" class="text-center py-4 text-danger">${res.message || 'Failed to load users.'}</td></tr>`);
                }
            },
            error: function() {
                $('#usersTableBody').html(`<tr><td colspan="7" class="text-center py-4 text-danger">Error connecting to server. Please try again.</td></tr>`);
                showToast('Failed to connect to users API.', 'danger');
            }
        });
    }

    // Update KPI counters
    function updateKPIs(users) {
        const total = users.length;
        const admins = users.filter(u => u.role === 'admin' && u.status === 'active').length;
        const staff = users.filter(u => u.role === 'staff' && u.status === 'active').length;
        const inactive = users.filter(u => u.status === 'inactive').length;

        $('#kpiTotalUsers').text(total);
        $('#kpiAdminUsers').text(admins);
        $('#kpiStaffUsers').text(staff);
        $('#kpiInactiveUsers').text(inactive);
        $('#userCountBadge').text(`${total} Total User${total === 1 ? '' : 's'}`);
    }

    // Render Users Table
    function renderUsersTable(users) {
        if (!users || users.length === 0) {
            $('#usersTableBody').html(`
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="fas fa-users-slash fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        No users found.
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        users.forEach(u => {
            const isSelf = (u.id === currentUserId);
            const isAdminRole = (u.role === 'admin');
            const isActive = (u.status === 'active');

            // Role Badge
            const roleBadge = isAdminRole 
                ? `<span class="badge bg-danger text-white rounded-pill px-3 py-1"><i class="fas fa-shield-halved me-1"></i> Administrator</span>`
                : `<span class="badge bg-primary text-white rounded-pill px-3 py-1"><i class="fas fa-cash-register me-1"></i> Cashier</span>`;

            // Access Scope Description
            const accessScope = isAdminRole
                ? `<span class="fw-semibold text-danger small"><i class="fas fa-crown me-1"></i> Full System Access</span>`
                : `<span class="text-muted small"><i class="fas fa-cart-shopping me-1 text-primary"></i> POS, Khata & Counter</span>`;

            // Status Badge
            const statusBadge = isActive
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1"><i class="fas fa-circle-check me-1"></i> Active</span>`
                : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1"><i class="fas fa-circle-xmark me-1"></i> Inactive</span>`;

            // Self Badge
            const selfBadge = isSelf ? `<span class="badge bg-dark text-white rounded-pill ms-2" style="font-size: 0.65rem;">You</span>` : '';

            // Toggle Status Button
            const statusToggleBtn = isSelf 
                ? '' 
                : `<button class="btn btn-sm btn-outline-${isActive ? 'warning' : 'success'} btn-toggle-status" data-id="${u.id}" data-username="${escapeAttr(u.username)}" data-status="${u.status}" title="${isActive ? 'Deactivate User' : 'Activate User'}">
                    <i class="fas fa-power-off"></i>
                   </button>`;

            // Delete Button
            const deleteBtn = isSelf
                ? `<button class="btn btn-sm btn-light text-muted border-0" disabled title="Cannot delete logged-in account"><i class="fas fa-trash opacity-25"></i></button>`
                : `<button class="btn btn-sm btn-outline-danger btn-delete-user" data-id="${u.id}" data-username="${escapeAttr(u.username)}" data-role="${u.role}" title="Delete User">
                    <i class="fas fa-trash"></i>
                   </button>`;

            html += `
                <tr id="userRow_${u.id}">
                    <td class="ps-3 ps-md-4 fw-bold text-muted">#${u.id}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm ${isAdminRole ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary'} rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                ${escapeHtml(u.username.substring(0, 2).toUpperCase())}
                            </div>
                            <div>
                                <span class="fw-bold text-dark">${escapeHtml(u.username)}</span>
                                ${selfBadge}
                            </div>
                        </div>
                    </td>
                    <td>${roleBadge}</td>
                    <td>${accessScope}</td>
                    <td>${statusBadge}</td>
                    <td class="text-muted small">${escapeHtml(u.created_at || '-')}</td>
                    <td class="text-end pe-3 pe-md-4">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-sm btn-outline-dark btn-edit-user" data-id="${u.id}" title="Edit User">
                                <i class="fas fa-pen-to-square"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary btn-reset-pwd" data-id="${u.id}" data-username="${escapeAttr(u.username)}" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                            ${statusToggleBtn}
                            ${deleteBtn}
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#usersTableBody').html(html);
    }

    // -------------------------------------------------------------
    // 3. Search Filter
    // -------------------------------------------------------------
    $('#userSearchInput').on('keyup', function() {
        const query = $(this).val().toLowerCase().trim();
        if (!query) {
            renderUsersTable(allUsers);
            return;
        }

        const filtered = allUsers.filter(u => {
            return (u.username && u.username.toLowerCase().includes(query)) ||
                   (u.role && u.role.toLowerCase().includes(query)) ||
                   (u.status && u.status.toLowerCase().includes(query)) ||
                   (String(u.id).includes(query));
        });

        renderUsersTable(filtered);
    });

    $('#btnRefreshUsers').on('click', function() {
        loadUsers();
        showToast('User accounts refreshed.', 'info');
    });

    // -------------------------------------------------------------
    // 4. Add User Modal & Form Logic
    // -------------------------------------------------------------
    $('#btnOpenAddUser').on('click', function() {
        $('#addUserForm')[0].reset();
        $('#addUserAlert').addClass('d-none').text('');
        $('#addRole').val('staff').trigger('change');
        $('#addStatus').val('active');
        $('#addUserModal').modal('show');
    });

    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#btnAddUserSubmit');
        const alertEl = $('#addUserAlert');

        alertEl.addClass('d-none').text('');
        submitBtn.prop('disabled', true).find('.btn-text').addClass('d-none');
        submitBtn.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: window.BASE_URL + '/api/users.php?action=create',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#addUserModal').modal('hide');
                    showToast(res.message || 'User created successfully.', 'success');
                    loadUsers();
                } else {
                    alertEl.removeClass('d-none').text(res.message || 'Failed to create user.');
                }
            },
            error: function(xhr) {
                let msg = 'Failed to create user.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alertEl.removeClass('d-none').text(msg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).find('.btn-text').removeClass('d-none');
                submitBtn.find('.spinner-border').addClass('d-none');
            }
        });
    });

    // -------------------------------------------------------------
    // 5. Edit User Modal & Form Logic
    // -------------------------------------------------------------
    $(document).on('click', '.btn-edit-user', function() {
        const id = $(this).data('id');
        const user = allUsers.find(u => u.id === id);
        if (!user) return;

        $('#editUserForm')[0].reset();
        $('#editUserAlert').addClass('d-none').text('');
        $('#editUserId').val(user.id);
        $('#editUsername').val(user.username);
        $('#editRole').val(user.role).trigger('change');
        $('#editStatus').val(user.status);
        $('#editPassword').val('');

        $('#editUserModal').modal('show');
    });

    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#btnEditUserSubmit');
        const alertEl = $('#editUserAlert');

        alertEl.addClass('d-none').text('');
        submitBtn.prop('disabled', true).find('.btn-text').addClass('d-none');
        submitBtn.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: window.BASE_URL + '/api/users.php?action=update',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#editUserModal').modal('hide');
                    showToast(res.message || 'User updated successfully.', 'success');
                    loadUsers();
                } else {
                    alertEl.removeClass('d-none').text(res.message || 'Failed to update user.');
                }
            },
            error: function(xhr) {
                let msg = 'Failed to update user.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alertEl.removeClass('d-none').text(msg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).find('.btn-text').removeClass('d-none');
                submitBtn.find('.spinner-border').addClass('d-none');
            }
        });
    });

    // -------------------------------------------------------------
    // 6. Reset Password Modal & Form Logic
    // -------------------------------------------------------------
    $(document).on('click', '.btn-reset-pwd', function() {
        const id = $(this).data('id');
        const username = $(this).data('username');

        $('#resetPasswordForm')[0].reset();
        $('#resetPasswordAlert').addClass('d-none').text('');
        $('#resetUserId').val(id);
        $('#resetUserDisplay').text(username);
        $('#resetPasswordModal').modal('show');
    });

    $('#resetPasswordForm').on('submit', function(e) {
        e.preventDefault();
        const newPwd = $('#resetNewPassword').val();
        const confirmPwd = $('#resetConfirmPassword').val();
        const alertEl = $('#resetPasswordAlert');

        if (newPwd !== confirmPwd) {
            alertEl.removeClass('d-none').text('New password and confirmation do not match.');
            return;
        }

        const submitBtn = $('#btnResetPasswordSubmit');
        alertEl.addClass('d-none').text('');
        submitBtn.prop('disabled', true).find('.btn-text').addClass('d-none');
        submitBtn.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: window.BASE_URL + '/api/users.php?action=reset_password',
            type: 'POST',
            data: {
                id: $('#resetUserId').val(),
                new_password: newPwd
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#resetPasswordModal').modal('hide');
                    showToast(res.message || 'Password reset successfully.', 'success');
                } else {
                    alertEl.removeClass('d-none').text(res.message || 'Failed to reset password.');
                }
            },
            error: function(xhr) {
                let msg = 'Failed to reset password.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alertEl.removeClass('d-none').text(msg);
            },
            complete: function() {
                submitBtn.prop('disabled', false).find('.btn-text').removeClass('d-none');
                submitBtn.find('.spinner-border').addClass('d-none');
            }
        });
    });

    // -------------------------------------------------------------
    // 7. Toggle Status Directly
    // -------------------------------------------------------------
    $(document).on('click', '.btn-toggle-status', function() {
        const id = $(this).data('id');
        const username = $(this).data('username');
        const btn = $(this);

        btn.prop('disabled', true);

        $.ajax({
            url: window.BASE_URL + '/api/users.php?action=toggle_status',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showToast(res.message || 'Status updated.', 'success');
                    loadUsers();
                } else {
                    showToast(res.message || 'Failed to toggle status.', 'danger');
                    btn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                let msg = 'Failed to toggle status.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showToast(msg, 'danger');
                btn.prop('disabled', false);
            }
        });
    });

    // -------------------------------------------------------------
    // 8. Delete User Modal & Action
    // -------------------------------------------------------------
    $(document).on('click', '.btn-delete-user', function() {
        const id = $(this).data('id');
        const username = $(this).data('username');

        $('#deleteUserId').val(id);
        $('#deleteUsernameDisplay').text(username);
        $('#deleteUserModal').modal('show');
    });

    $('#btnConfirmDeleteUser').on('click', function() {
        const id = $('#deleteUserId').val();
        const submitBtn = $(this);

        submitBtn.prop('disabled', true).find('.btn-text').addClass('d-none');
        submitBtn.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: window.BASE_URL + '/api/users.php?action=delete',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(res) {
                $('#deleteUserModal').modal('hide');
                if (res.success) {
                    if (res.deactivated) {
                        showToast(res.message, 'warning');
                    } else {
                        showToast(res.message || 'User deleted successfully.', 'success');
                    }
                    loadUsers();
                } else {
                    showToast(res.message || 'Failed to delete user.', 'danger');
                }
            },
            error: function(xhr) {
                $('#deleteUserModal').modal('hide');
                let msg = 'Failed to delete user.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showToast(msg, 'danger');
            },
            complete: function() {
                submitBtn.prop('disabled', false).find('.btn-text').removeClass('d-none');
                submitBtn.find('.spinner-border').addClass('d-none');
            }
        });
    });

    // Initialize: load users
    loadUsers();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
