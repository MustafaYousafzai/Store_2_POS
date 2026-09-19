<?php
require_once __DIR__ . '/../includes/header.php';
requirePermission('VIEW_PROFIT');
?>

<div class="card mb-4 d-print-none">
    <div class="card-body">
        <form id="pnlFilterForm" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="pnlStartDate" class="form-label font-weight-bold">Start Date</label>
                <input type="date" class="form-control" id="pnlStartDate" required>
            </div>
            <div class="col-md-4">
                <label for="pnlEndDate" class="form-label font-weight-bold">End Date</label>
                <input type="date" class="form-control" id="pnlEndDate" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100 py-2"><i class="fas fa-arrows-spin"></i> Generate P&L Report</button>
            </div>
        </form>
    </div>
</div>

<div class="card d-none" id="pnlReportCard">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-invoice-dollar me-2 text-danger"></i>Profit & Loss Statement</h5>
        <button class="btn btn-sm btn-outline-secondary d-print-none" onclick="printPnlReport()"><i class="fas fa-print"></i> Print P&L</button>
    </div>
    <div class="card-body p-4">
        <!-- Header details -->
        <div class="text-center mb-4">
            <h3 class="mb-1 text-dark font-weight-bold"><?= defined('STORE_NAME') ? strtoupper(STORE_NAME) : 'ONE DOLLAR SHOP' ?></h3>
            <p class="text-muted mb-0">Business Operating P&L Statement</p>
            <small class="text-danger fw-bold" id="reportPeriodText">Period: -- to --</small>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                    <tbody>
                        <!-- Revenue -->
                        <tr class="table-light font-weight-bold fs-6">
                            <td>REVENUE</td>
                            <td class="text-end">Amount (Rs.)</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Gross Sales Billing Revenue:</td>
                            <td class="text-end font-monospace text-success fw-bold" id="pnlGrossSales">Rs. 0.00</td>
                        </tr>
                        <tr>
                            <td class="ps-4 text-warning">Less: Sales Refund Reversals:</td>
                            <td class="text-end font-monospace text-danger fw-bold" id="pnlRefunds">-Rs. 0.00</td>
                        </tr>
                        <tr class="fw-bold fs-6 border-bottom-2">
                            <td>NET SALES REVENUE:</td>
                            <td class="text-end font-monospace text-success" id="pnlNetRevenue">Rs. 0.00</td>
                        </tr>
                        
                        <!-- COGS -->
                        <tr>
                            <td>Less: Cost of Goods Sold (COGS):</td>
                            <td class="text-end font-monospace text-danger fw-bold" id="pnlCogs">-Rs. 0.00</td>
                        </tr>
                        
                        <!-- Gross Profit -->
                        <tr class="table-dark fs-5 fw-bold">
                            <td>GROSS OPERATING PROFIT:</td>
                            <td class="text-end font-monospace text-danger" id="pnlGrossProfit">Rs. 0.00</td>
                        </tr>
                        
                        <!-- Operating Expenses -->
                        <tr class="table-light font-weight-bold fs-6">
                            <td>OPERATING BURDEN & EXPENSES (Prorated Accrual)</td>
                            <td class="text-end">Amount (Rs.)</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Prorated Shop Rent (<span id="pnlRentDays">0</span> days):</td>
                            <td class="text-end font-monospace fw-bold text-danger" id="pnlShopRent">-Rs. 0.00</td>
                        </tr>
                        <tr>
                            <td class="ps-4">Prorated Staff Salaries (<span id="pnlStaffCount">0</span> active staff):</td>
                            <td class="text-end font-monospace fw-bold text-danger" id="pnlStaffCosts">-Rs. 0.00</td>
                        </tr>
                        <!-- Categories list loaded dynamically -->
                        <tr class="fw-bold fs-6" id="expensesHeaderRow">
                            <td class="ps-4">Variable Operational Expenses (Khana, Chai, Petty):</td>
                            <td class="text-end font-monospace text-danger" id="pnlCategoryExpenses">-Rs. 0.00</td>
                        </tr>
                        <tbody id="dynamicExpenseCategoriesList">
                            <!-- Dynamic expense category line entries -->
                        </tbody>
                        <tr class="table-secondary fw-bold fs-6">
                            <td>TOTAL OPERATING BURDEN:</td>
                            <td class="text-end font-monospace text-danger" id="pnlTotalExpenses">-Rs. 0.00</td>
                        </tr>
                        
                        <!-- Net Profit -->
                        <tr class="table-dark fs-4 fw-bold">
                            <td id="pnlNetProfitLabel">NET TRADING PROFIT:</td>
                            <td class="text-end font-monospace" id="pnlNetProfit">Rs. 0.00</td>
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
    // Default date range: start of current month to today
    const now = new Date();
    const startOfMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-01';
    const today = now.toISOString().split('T')[0];
    
    $('#pnlStartDate').val(startOfMonth);
    $('#pnlEndDate').val(today);
    
    $('#pnlFilterForm').on('submit', function(e) {
        e.preventDefault();
        
        const start = $('#pnlStartDate').val();
        const end = $('#pnlEndDate').val();
        
        $('#pnlReportCard').addClass('d-none');
        
        $.ajax({
            url: (window.BASE_URL || '') + '/api/reports.php?action=dashboard_kpis',
            type: 'GET',
            data: {
                filter: 'custom',
                start_date: start,
                end_date: end
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#reportPeriodText').text(`Period: ${start} to ${end}`);
                    
                    $('#pnlGrossSales').text(`Rs. ${response.sales_revenue.toFixed(2)}`);
                    $('#pnlRefunds').text(`-Rs. ${response.refunded_amount.toFixed(2)}`);
                    $('#pnlNetRevenue').text(`Rs. ${response.net_revenue.toFixed(2)}`);
                    $('#pnlCogs').text(`-Rs. ${response.cogs.toFixed(2)}`);
                    $('#pnlGrossProfit').text(`Rs. ${response.gross_profit.toFixed(2)}`);
                    
                    if (response.fixed_overheads) {
                        const fo = response.fixed_overheads;
                        $('#pnlRentDays').text(fo.effective_days);
                        $('#pnlShopRent').text(`-Rs. ${fo.period_rent.toFixed(2)}`);
                        $('#pnlStaffCount').text(fo.active_staff_count);
                        $('#pnlStaffCosts').text(`-Rs. ${fo.period_payroll.toFixed(2)}`);
                    } else {
                        $('#pnlStaffCosts').text(`-Rs. ${response.employee_payroll.toFixed(2)}`);
                    }
                    
                    const varExpenses = response.variable_expenses !== undefined ? response.variable_expenses : response.expense_cash;
                    $('#pnlCategoryExpenses').text(`-Rs. ${varExpenses.toFixed(2)}`);
                    
                    const totalBurden = response.true_operating_expenses !== undefined ? response.true_operating_expenses : response.operating_expenses;
                    $('#pnlTotalExpenses').text(`-Rs. ${totalBurden.toFixed(2)}`);
                    
                    const netVal = response.true_net_profit !== undefined ? response.true_net_profit : response.net_profit;
                    $('#pnlNetProfit').text(`Rs. ${netVal.toFixed(2)}`);
                    
                    if (netVal < 0) {
                        $('#pnlNetProfitLabel').text("NET OPERATING LOSS:");
                        $('#pnlNetProfit').addClass('text-danger').removeClass('text-success');
                    } else {
                        $('#pnlNetProfitLabel').text("NET OPERATING PROFIT:");
                        $('#pnlNetProfit').addClass('text-success').removeClass('text-danger');
                    }
                    
                    // Load dynamic expense breakdown for that range
                    loadExpensesBreakdown(start, end);
                }
            }
        });
    });
    
    function loadExpensesBreakdown(start, end) {
        $.ajax({
            url: (window.BASE_URL || '') + '/api/expenses.php?action=list',
            type: 'GET',
            data: {
                start_date: start,
                end_date: end,
                status: 'active'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Accumulate by category
                    let categorySum = {};
                    response.expenses.forEach(function(e) {
                        const cat = e.category_name;
                        const amt = parseFloat(e.amount);
                        categorySum[cat] = (categorySum[cat] || 0) + amt;
                    });
                    
                    let html = '';
                    for (const [catName, sumAmt] of Object.entries(categorySum)) {
                        html += `
                            <tr>
                                <td class="ps-5 text-muted">${catName} Details:</td>
                                <td class="text-end font-monospace text-muted">-Rs. ${sumAmt.toFixed(2)}</td>
                            </tr>
                        `;
                    }
                    if (html === '') {
                        html = '<tr><td colspan="2" class="ps-5 text-muted">No categorized expenses logged in period.</td></tr>';
                    }
                    $('#dynamicExpenseCategoriesList').html(html);
                    $('#pnlReportCard').removeClass('d-none');
                }
            }
        });
    }
});

function printPnlReport() {
    $('body').addClass('printing-report');
    window.print();
    setTimeout(function() {
        $('body').removeClass('printing-report');
    }, 1000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
