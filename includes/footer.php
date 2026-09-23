        </div> <!-- End of container-fluid -->
    </div> <!-- End of content -->
</div> <!-- End of wrapper -->

<!-- Change Password Modal -->
<div class="modal fade d-print-none" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel"><i class="fas fa-key me-2"></i>Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <div id="changePasswordAlert" class="alert alert-danger d-none"></div>
                    <div id="changePasswordSuccess" class="alert alert-success d-none">Password changed successfully.</div>
                    
                    <div class="mb-3">
                        <label for="old_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="old_password" name="old_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle (Offline First) -->
<script src="<?= url('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>

<script>
// Global HTML & Attribute sanitizers
window.escapeHtml = function(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
};

window.escapeAttr = function(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
};

// Global UI/UX Toast Notification Helper
function showToast(message, type = 'success') {
    const toastEl = document.getElementById('appToast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.getElementById('toastIcon');
    
    if (!toastEl || !toastMessage || !toastIcon) return;
    
    // Clear existing alert styles
    toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-dark');
    toastIcon.className = 'me-2 fs-5';
    
    if (type === 'success') {
        toastEl.classList.add('bg-success');
        toastIcon.className += ' fas fa-circle-check';
    } else if (type === 'danger' || type === 'error') {
        toastEl.classList.add('bg-danger');
        toastIcon.className += ' fas fa-circle-xmark';
    } else if (type === 'warning') {
        toastEl.classList.add('bg-warning', 'text-dark');
        toastIcon.className += ' fas fa-triangle-exclamation';
    } else {
        toastEl.classList.add('bg-info');
        toastIcon.className += ' fas fa-circle-info';
    }
    
    toastMessage.textContent = message;
    
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
}

$(document).ready(function() {
    // Standardized Global Sidebar: 100% Closed by default on all screens
    $('#sidebarCollapse').on('click', function(e) {
        e.preventDefault();
        $('#sidebar').toggleClass('open');
        $('body').toggleClass('sidebar-open');
        const isOpen = $('#sidebar').hasClass('open');
        if ($(window).width() < 992) {
            $('#sidebarBackdrop').toggleClass('d-none', !isOpen);
        }
    });
    
    $('#sidebarBackdrop').on('click', function() {
        $('#sidebar').removeClass('open');
        $('body').removeClass('sidebar-open');
        $(this).addClass('d-none');
    });
    
    // Change password handler
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        const old_password = $('#old_password').val();
        const new_password = $('#new_password').val();
        const confirm_password = $('#confirm_password').val();
        
        $('#changePasswordAlert').addClass('d-none');
        $('#changePasswordSuccess').addClass('d-none');
        
        if(new_password !== confirm_password) {
            $('#changePasswordAlert').text("New passwords do not match.").removeClass('d-none');
            return;
        }
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/auth.php?action=change_password',
            type: 'POST',
            data: {
                old_password: old_password,
                new_password: new_password,
                confirm_password: confirm_password
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#changePasswordSuccess').text(response.message).removeClass('d-none');
                    $('#changePasswordForm')[0].reset();
                    setTimeout(function() {
                        const modalEl = document.getElementById('changePasswordModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        modal.hide();
                        $('#changePasswordSuccess').addClass('d-none');
                    }, 1500);
                } else {
                    $('#changePasswordAlert').text(response.message).removeClass('d-none');
                }
            },
            error: function(xhr) {
                let msg = 'Server error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $('#changePasswordAlert').text(msg).removeClass('d-none');
            }
        });
    });

    // Universal UX Guard: Prevent mouse wheel scroll from unintentionally changing number inputs
    document.addEventListener('wheel', function(e) {
        if (document.activeElement && document.activeElement.type === 'number') {
            document.activeElement.blur();
        }
    }, { passive: false });

    $(document).on('wheel', 'input[type=number]', function(e) {
        $(this).blur();
    });

    // ==========================================================================
    // DUAL THEME ENGINE (Dark & Light) WITH MULTI-TAB SYNCHRONIZATION
    // ==========================================================================
    window.applyTheme = function(theme) {
        if (!theme || (theme !== 'dark' && theme !== 'light')) {
            theme = localStorage.getItem('pos_theme') || 'dark';
        }
        localStorage.setItem('pos_theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
        
        if (theme === 'light') {
            $('html, body').removeClass('dark-theme').addClass('light-theme');
            $('.theme-icon-sun').addClass('d-none');
            $('.theme-icon-moon').removeClass('d-none');
            $('#themeToggleBtn, .theme-toggle-btn').attr('title', 'Switch to Dark Mode');
            $('.theme-text').text('Light');
        } else {
            $('html, body').removeClass('light-theme').addClass('dark-theme');
            $('.theme-icon-moon').addClass('d-none');
            $('.theme-icon-sun').removeClass('d-none');
            $('#themeToggleBtn, .theme-toggle-btn').attr('title', 'Switch to Light Mode');
            $('.theme-text').text('Dark');
        }
        
        window.dispatchEvent(new CustomEvent('posThemeChanged', { detail: { theme: theme } }));
    };

    // Initialize toggle button state on page ready
    const savedTheme = localStorage.getItem('pos_theme') || 'dark';
    window.applyTheme(savedTheme);

    // Toggle click handler
    $(document).on('click', '#themeToggleBtn, .theme-toggle-btn', function(e) {
        e.preventDefault();
        const currentTheme = localStorage.getItem('pos_theme') || 'dark';
        const targetTheme = currentTheme === 'dark' ? 'light' : 'dark';
        window.applyTheme(targetTheme);
    });

    // Cross-tab theme synchronization
    window.addEventListener('storage', function(e) {
        if (e.key === 'pos_theme' && e.newValue) {
            window.applyTheme(e.newValue);
        }
    });
});
</script>
</body>
</html>
