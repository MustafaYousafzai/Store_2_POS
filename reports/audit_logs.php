<?php
require_once __DIR__ . '/../config/auth.php';
if (!isAdmin()) {
    redirect('/pos/index.php');
}
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header bg-white font-weight-bold py-3">
        <h5 class="mb-0"><i class="fas fa-shield-halved me-2 text-danger"></i>System Security Audit Trail</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th style="width: 170px;">Date & Time</th>
                        <th>Performed By</th>
                        <th>Action Code</th>
                        <th>Target Domain</th>
                        <th>Record ID</th>
                        <th>Old State Details</th>
                        <th>New State Details</th>
                        <th>Remarks / Reason</th>
                    </tr>
                </thead>
                <tbody id="auditLogsTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-5">Loading security logs...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadAuditLogs();
    
    function loadAuditLogs() {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/reports.php?action=audit_logs',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (response.logs.length === 0) {
                        html = '<tr><td colspan="8" class="text-center py-4 text-muted">No security logs recorded.</td></tr>';
                    } else {
                        response.logs.forEach(function(log) {
                            // Helper to parse and beautify JSON fields
                            let oldText = '--';
                            if (log.old_values) {
                                try {
                                    const parsed = JSON.parse(log.old_values);
                                    oldText = `<pre class="mb-0 p-1 bg-light border text-start rounded text-muted font-monospace" style="font-size: 0.75rem; max-width: 250px; overflow-x: auto;">${JSON.stringify(parsed, null, 2)}</pre>`;
                                } catch(e) {
                                    oldText = `<span class="text-muted font-monospace">${log.old_values}</span>`;
                                }
                            }
                            
                            let newText = '--';
                            if (log.new_values) {
                                try {
                                    const parsed = JSON.parse(log.new_values);
                                    newText = `<pre class="mb-0 p-1 bg-light border text-start rounded text-dark font-monospace" style="font-size: 0.75rem; max-width: 250px; overflow-x: auto;">${JSON.stringify(parsed, null, 2)}</pre>`;
                                } catch(e) {
                                    newText = `<span class="font-monospace">${log.new_values}</span>`;
                                }
                            }
                            
                            // Map codes to colors
                            let actionColor = 'bg-secondary';
                            if (log.action.includes('void') || log.action.includes('cancel')) actionColor = 'bg-danger';
                            else if (log.action.includes('create') || log.action.includes('add')) actionColor = 'bg-success';
                            else if (log.action.includes('login')) actionColor = 'bg-primary';
                            else if (log.action.includes('update')) actionColor = 'bg-info text-dark';
                            
                            html += `
                                <tr>
                                    <td>${log.created_at}</td>
                                    <td><strong>${log.username}</strong></td>
                                    <td><span class="badge ${actionColor}">${log.action.toUpperCase()}</span></td>
                                    <td class="text-uppercase">${log.entity_type}</td>
                                    <td class="font-monospace">#${log.entity_id}</td>
                                    <td>${oldText}</td>
                                    <td>${newText}</td>
                                    <td class="text-secondary">${log.reason || 'N/A'}</td>
                                </tr>
                            `;
                        });
                    }
                    $('#auditLogsTableBody').html(html);
                } else {
                    $('#auditLogsTableBody').html('<tr><td colspan="8" class="text-center text-danger py-4">Failed to load system logs.</td></tr>');
                }
            },
            error: function() {
                $('#auditLogsTableBody').html('<tr><td colspan="8" class="text-center text-danger py-4">Error connecting to server.</td></tr>');
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
