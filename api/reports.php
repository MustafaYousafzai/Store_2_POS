<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

// Enforce login
if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';
$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list_cashiers') {
        // Return active/inactive cashiers
        $stmt = $db->query("SELECT id, username FROM users ORDER BY username ASC");
        sendJSON(['success' => true, 'cashiers' => $stmt->fetchAll()]);
    }
    
    else if ($action === 'refund_items_details') {
        $refId = (int)$_GET['refund_id'];
        $stmt = $db->prepare("SELECT * FROM refund_items WHERE refund_id = ?");
        $stmt->execute([$refId]);
        sendJSON(['success' => true, 'items' => $stmt->fetchAll()]);
    }
    
    else if ($action === 'product_history') {
        requirePermission('MANAGE_INVENTORY');
        
        $pid = (int)$_GET['product_id'];
        
        $stmt = $db->prepare("
            SELECT sm.*, u.username 
            FROM stock_movements sm
            JOIN users u ON sm.user_id = u.id
            WHERE sm.product_id = ?
            ORDER BY sm.created_at DESC, sm.id DESC
        ");
        $stmt->execute([$pid]);
        $history = $stmt->fetchAll();
        
        sendJSON(['success' => true, 'history' => $history]);
    }
    
    else if ($action === 'dashboard_kpis') {
        requirePermission('VIEW_PROFIT');
        
        $filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'today';
        $start = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
        $end = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';
        
        $dateWhere = "1=1";
        $refDateWhere = "1=1";
        $expDateWhere = "1=1";
        $settDateWhere = "1=1";
        $khataBillDateWhere = "1=1";
        $khataPayDateWhere = "1=1";
        $params = [];
        $refParams = [];
        $expParams = [];
        $settParams = [];
        $khataBillParams = [];
        $khataPayParams = [];
        
        if ($filter === 'today') {
            $dateWhere = "DATE(s.created_at) = CURDATE()";
            $refDateWhere = "DATE(r.created_at) = CURDATE()";
            $expDateWhere = "e.payment_date = CURDATE()";
            $settDateWhere = "DATE(es.created_at) = CURDATE()";
            $khataBillDateWhere = "b.bill_date = CURDATE()";
            $khataPayDateWhere = "p.payment_date = CURDATE()";
        } else if ($filter === 'yesterday') {
            $dateWhere = "DATE(s.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            $refDateWhere = "DATE(r.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            $expDateWhere = "e.payment_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            $settDateWhere = "DATE(es.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            $khataBillDateWhere = "b.bill_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            $khataPayDateWhere = "p.payment_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        } else if ($filter === 'week') {
            $dateWhere = "YEARWEEK(s.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            $refDateWhere = "YEARWEEK(r.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            $expDateWhere = "YEARWEEK(e.payment_date, 1) = YEARWEEK(CURDATE(), 1)";
            $settDateWhere = "YEARWEEK(es.created_at, 1) = YEARWEEK(CURDATE(), 1)";
            $khataBillDateWhere = "YEARWEEK(b.bill_date, 1) = YEARWEEK(CURDATE(), 1)";
            $khataPayDateWhere = "YEARWEEK(p.payment_date, 1) = YEARWEEK(CURDATE(), 1)";
        } else if ($filter === 'month') {
            $dateWhere = "MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE())";
            $refDateWhere = "MONTH(r.created_at) = MONTH(CURDATE()) AND YEAR(r.created_at) = YEAR(CURDATE())";
            $expDateWhere = "MONTH(e.payment_date) = MONTH(CURDATE()) AND YEAR(e.payment_date) = YEAR(CURDATE())";
            $settDateWhere = "MONTH(es.created_at) = MONTH(CURDATE()) AND YEAR(es.created_at) = YEAR(CURDATE())";
            $khataBillDateWhere = "MONTH(b.bill_date) = MONTH(CURDATE()) AND YEAR(b.bill_date) = YEAR(CURDATE())";
            $khataPayDateWhere = "MONTH(p.payment_date) = MONTH(CURDATE()) AND YEAR(p.payment_date) = YEAR(CURDATE())";
        } else if ($filter === 'last_2_months') {
            $dateWhere = "DATE(s.created_at) >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND DATE(s.created_at) <= CURDATE()";
            $refDateWhere = "DATE(r.created_at) >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND DATE(r.created_at) <= CURDATE()";
            $expDateWhere = "e.payment_date >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND e.payment_date <= CURDATE()";
            $settDateWhere = "DATE(es.created_at) >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND DATE(es.created_at) <= CURDATE()";
            $khataBillDateWhere = "b.bill_date >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND b.bill_date <= CURDATE()";
            $khataPayDateWhere = "p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND p.payment_date <= CURDATE()";
        } else if ($filter === 'lifetime' || $filter === 'all') {
            $dateWhere = "1=1";
            $refDateWhere = "1=1";
            $expDateWhere = "1=1";
            $settDateWhere = "1=1";
            $khataBillDateWhere = "1=1";
            $khataPayDateWhere = "1=1";
        } else if ($filter === 'custom' && !empty($start) && !empty($end)) {
            $dateWhere = "DATE(s.created_at) BETWEEN ? AND ?";
            $refDateWhere = "DATE(r.created_at) BETWEEN ? AND ?";
            $expDateWhere = "e.payment_date BETWEEN ? AND ?";
            $settDateWhere = "DATE(es.created_at) BETWEEN ? AND ?";
            $khataBillDateWhere = "b.bill_date BETWEEN ? AND ?";
            $khataPayDateWhere = "p.payment_date BETWEEN ? AND ?";
            
            $params = [$start, $end];
            $refParams = [$start, $end];
            $expParams = [$start, $end];
            $settParams = [$start, $end];
            $khataBillParams = [$start, $end];
            $khataPayParams = [$start, $end];
        }
        
        // 1. Sales & Revenue
        $stmtSales = $db->prepare("
            SELECT COALESCE(SUM(s.total), 0) as revenue,
                   COUNT(s.id) as trans_count,
                   COALESCE(SUM(s.discount), 0) as discount,
                   (SELECT COALESCE(SUM(si.quantity), 0) FROM sale_items si JOIN sales s2 ON si.sale_id = s2.id WHERE s2.status != 'voided' AND " . str_replace('s.', 's2.', $dateWhere) . ") as items_sold
            FROM sales s 
            WHERE s.status != 'voided' AND $dateWhere
        ");
        $salesParams = !empty($params) ? array_merge($params, $params) : [];
        $stmtSales->execute($salesParams);
        $salesKpi = $stmtSales->fetch();

        // 1b. Direct Khata Credit Bills (Created directly in Khata module, without POS sale_id)
        $stmtDirectKhata = $db->prepare("
            SELECT 
                COALESCE(SUM(b.total_amount), 0) as direct_revenue,
                COUNT(b.id) as direct_bills_count,
                COALESCE((
                    SELECT SUM(bi.quantity) 
                    FROM customer_khata_bill_items bi 
                    JOIN customer_khata_bills b2 ON bi.bill_id = b2.id 
                    WHERE b2.sale_id IS NULL AND b2.status = 'active' AND " . str_replace('b.', 'b2.', $khataBillDateWhere) . "
                ), 0) as direct_items_sold
            FROM customer_khata_bills b
            WHERE b.sale_id IS NULL AND b.status = 'active' AND $khataBillDateWhere
        ");
        $directKhataParams = !empty($khataBillParams) ? array_merge($khataBillParams, $khataBillParams) : [];
        $stmtDirectKhata->execute($directKhataParams);
        $directKhataData = $stmtDirectKhata->fetch();
        $directKhataRevenue = (float)($directKhataData['direct_revenue'] ?? 0);
        $directKhataCount = (int)($directKhataData['direct_bills_count'] ?? 0);
        $directKhataItems = (int)($directKhataData['direct_items_sold'] ?? 0);

        // 1c. Credit Sales (Goods given on Udhar/Credit in this period)
        $payDateWhere = str_replace('s.', 's2.', $dateWhere);
        $stmtPosCredit = $db->prepare("
            SELECT COALESCE(SUM(p.amount), 0) as pos_credit,
                   COUNT(DISTINCT s2.id) as pos_credit_bills
            FROM payments p
            JOIN sales s2 ON p.sale_id = s2.id
            WHERE s2.status != 'voided' AND p.payment_method = 'khata' AND $payDateWhere
        ");
        $stmtPosCredit->execute(!empty($params) ? $params : []);
        $posCreditData = $stmtPosCredit->fetch();
        $posCreditSales = (float)($posCreditData['pos_credit'] ?? 0);
        $posCreditBillsCount = (int)($posCreditData['pos_credit_bills'] ?? 0);

        $totalCreditSales = $posCreditSales + $directKhataRevenue;
        $totalCreditBillsCount = $posCreditBillsCount + $directKhataCount;

        $totalGrossRevenue = (float)$salesKpi['revenue'] + $directKhataRevenue;
        $totalSalesCount = (int)$salesKpi['trans_count'] + $directKhataCount;
        $totalItemsSold = (int)$salesKpi['items_sold'] + $directKhataItems;
        
        // 2. Refunds
        $stmtRefunds = $db->prepare("
            SELECT COALESCE(SUM(r.total_refunded), 0) as total_refunded 
            FROM refunds r 
            WHERE $refDateWhere
        ");
        $stmtRefunds->execute($refParams);
        $refundKpi = $stmtRefunds->fetch();
        
        // 3. COGS (non-voided sales item cost - refunded cost)
        $stmtCogs = $db->prepare("
            SELECT COALESCE(SUM(si.cost_price_snapshot * si.quantity), 0) as raw_cogs
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            WHERE s.status != 'voided' AND $dateWhere
        ");
        $stmtCogs->execute($params);
        $rawCogs = (float)$stmtCogs->fetchColumn();

        // Direct Khata COGS (for catalog products sold on direct khata bills)
        $stmtDirectKhataCogs = $db->prepare("
            SELECT COALESCE(SUM(p.cost_price * bi.quantity), 0)
            FROM customer_khata_bill_items bi
            JOIN customer_khata_bills b ON bi.bill_id = b.id
            JOIN products p ON bi.product_id = p.id
            WHERE b.sale_id IS NULL AND b.status = 'active' AND $khataBillDateWhere
        ");
        $stmtDirectKhataCogs->execute(!empty($khataBillParams) ? $khataBillParams : []);
        $directKhataCogs = (float)$stmtDirectKhataCogs->fetchColumn();
        
        // Subtract reversed COGS for refunds in period
        $stmtRefundCogs = $db->prepare("
            SELECT COALESCE(SUM(si.cost_price_snapshot * ri.quantity_refunded), 0)
            FROM refund_items ri
            JOIN sale_items si ON ri.sale_item_id = si.id
            JOIN refunds r ON ri.refund_id = r.id
            WHERE $refDateWhere
        ");
        $stmtRefundCogs->execute($refParams);
        $reversedCogs = (float)$stmtRefundCogs->fetchColumn();

        // Account for exchanges in period
        $excDateWhere = str_replace('s.', 'e.', $dateWhere);
        $stmtExchData = $db->prepare("
            SELECT 
                COALESCE(SUM(e.net_difference), 0) as net_exchange_diff,
                (SELECT COALESCE(SUM(erp.cost_price_snapshot * erp.quantity), 0) 
                 FROM exchange_replaced_items erp 
                 JOIN exchanges e2 ON erp.exchange_id = e2.id 
                 WHERE " . str_replace('s.', 'e2.', $dateWhere) . ") as added_exch_cogs,
                (SELECT COALESCE(SUM(si2.cost_price_snapshot * eri.quantity), 0) 
                 FROM exchange_returned_items eri 
                 JOIN sale_items si2 ON eri.sale_item_id = si2.id 
                 JOIN exchanges e3 ON eri.exchange_id = e3.id 
                 WHERE " . str_replace('s.', 'e3.', $dateWhere) . ") as reversed_exch_cogs
            FROM exchanges e
            WHERE $excDateWhere
        ");
        $exchParams = !empty($params) ? array_merge($params, $params, $params) : [];
        $stmtExchData->execute($exchParams);
        $exchData = $stmtExchData->fetch();
        $netExchangeDiff = (float)($exchData['net_exchange_diff'] ?? 0);
        $addedExchCogs = (float)($exchData['added_exch_cogs'] ?? 0);
        $reversedExchCogs = (float)($exchData['reversed_exch_cogs'] ?? 0);

        $netCogs = max(0.00, $rawCogs - $reversedCogs + $addedExchCogs - $reversedExchCogs + $directKhataCogs);
        
        // Net Revenue = Total Gross Revenue (POS + Direct Khata) - Refunds + Exchange Difference
        $netRevenue = max(0.00, $totalGrossRevenue - (float)$refundKpi['total_refunded'] + $netExchangeDiff);
        $grossProfit = $netRevenue - $netCogs;
        
        // 4. Fixed Overheads & Accrual Proration (Rent + Active Payroll + Other Fixed)
        $refDate = date('Y-m-d');
        if ($filter === 'custom' && !empty($start)) {
            $refDate = $start;
        } else if ($filter === 'yesterday') {
            $refDate = date('Y-m-d', strtotime('-1 day'));
        }
        $daysInMonth = (int)date('t', strtotime($refDate));
        
        $effectiveDays = 1;
        $periodLabel = date('F Y', strtotime($refDate));

        if ($filter === 'today') {
            $effectiveDays = 1;
            $periodLabel = 'Today (' . date('d M Y') . ')';
        } else if ($filter === 'yesterday') {
            $effectiveDays = 1;
            $periodLabel = 'Yesterday (' . date('d M Y', strtotime('-1 day')) . ')';
        } else if ($filter === 'week') {
            $effectiveDays = 7;
            $periodLabel = 'This Week';
        } else if ($filter === 'month') {
            $effectiveDays = $daysInMonth;
            $periodLabel = date('F Y');
        } else if ($filter === 'last_2_months') {
            $effectiveDays = 60;
            $periodLabel = 'Last 2 Months (' . date('M Y', strtotime('-1 month')) . ' - ' . date('M Y') . ')';
        } else if ($filter === 'lifetime' || $filter === 'all') {
            $firstDateStmt = $db->query("SELECT MIN(DATE(created_at)) FROM sales WHERE status != 'voided'");
            $firstSaleDate = $firstDateStmt->fetchColumn();
            if (!empty($firstSaleDate)) {
                $dStart = new DateTime($firstSaleDate);
                $dEnd = new DateTime();
                $effectiveDays = max(1, $dStart->diff($dEnd)->days + 1);
            } else {
                $effectiveDays = $daysInMonth;
            }
            $periodLabel = 'Lifetime (All Time)';
        } else if ($filter === 'custom' && !empty($start) && !empty($end)) {
            $dStart = new DateTime($start);
            $dEnd = new DateTime($end);
            $effectiveDays = max(1, $dStart->diff($dEnd)->days + 1);
            $periodLabel = date('d M Y', strtotime($start)) . ' to ' . date('d M Y', strtotime($end));
        }

        // Fetch settings
        $settingsStmt = $db->query("SELECT setting_key, setting_value FROM store_settings WHERE setting_key IN ('monthly_shop_rent', 'other_monthly_fixed', 'other_fixed_title', 'other_monthly_fixed_items')");
        $settingsMap = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $monthlyRent = isset($settingsMap['monthly_shop_rent']) ? (float)$settingsMap['monthly_shop_rent'] : 50000.00;
        $otherMonthlyFixed = isset($settingsMap['other_monthly_fixed']) ? (float)$settingsMap['other_monthly_fixed'] : 0.00;
        $otherFixedTitle = isset($settingsMap['other_fixed_title']) && !empty($settingsMap['other_fixed_title']) ? $settingsMap['other_fixed_title'] : 'Estimated Utilities & Bills';
        $otherFixedItems = [];
        if (!empty($settingsMap['other_monthly_fixed_items'])) {
            $decoded = json_decode($settingsMap['other_monthly_fixed_items'], true);
            if (is_array($decoded)) {
                $otherFixedItems = $decoded;
            }
        }
        if (empty($otherFixedItems) && $otherMonthlyFixed > 0) {
            $otherFixedItems[] = [
                'title' => $otherFixedTitle,
                'amount' => $otherMonthlyFixed
            ];
        }

        // Fetch active staff count and monthly salaries
        $activeStaffCount = (int)$db->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn();
        $salStmt = $db->query("
            SELECT COALESCE(SUM(
                COALESCE((SELECT salary_amount FROM employee_salary_history esh WHERE esh.employee_id = e.id ORDER BY esh.effective_from DESC, esh.id DESC LIMIT 1), 0)
            ), 0) as total_payroll
            FROM employees e
            WHERE e.status = 'active'
        ");
        $monthlyPayroll = (float)$salStmt->fetchColumn();

        $dailyRent = $monthlyRent / $daysInMonth;
        $dailyPayroll = $monthlyPayroll / $daysInMonth;
        $dailyOtherFixed = $otherMonthlyFixed / $daysInMonth;
        $dailyTotalFixed = ($monthlyRent + $monthlyPayroll + $otherMonthlyFixed) / $daysInMonth;

        $periodRent = $dailyRent * $effectiveDays;
        $periodPayroll = $dailyPayroll * $effectiveDays;
        $periodOtherFixed = $dailyOtherFixed * $effectiveDays;
        $periodFixedBurden = $dailyTotalFixed * $effectiveDays;

        // 5. Operating Expenses & Strict Double-Counting Prevention
        // Variable expenses (Khana, Chai, Rozmarra Kharcha, Packaging - excluding lump-sum rent payments)
        $stmtVarExp = $db->prepare("
            SELECT COALESCE(SUM(e.amount), 0) as var_expenses
            FROM expenses e
            JOIN expense_categories c ON e.category_id = c.id
            WHERE e.status = 'active' AND $expDateWhere AND LOWER(c.name) NOT LIKE '%rent%'
        ");
        $stmtVarExp->execute($expParams);
        $variableExpenses = (float)$stmtVarExp->fetchColumn();

        // Rent lump-sum disbursements (actual cash outflows recorded under rent)
        $stmtRentExp = $db->prepare("
            SELECT COALESCE(SUM(e.amount), 0) as rent_disbursed
            FROM expenses e
            JOIN expense_categories c ON e.category_id = c.id
            WHERE e.status = 'active' AND $expDateWhere AND LOWER(c.name) LIKE '%rent%'
        ");
        $stmtRentExp->execute($expParams);
        $rentDisbursed = (float)$stmtRentExp->fetchColumn();

        $totalCashExpenses = $variableExpenses + $rentDisbursed;

        // Employee cash payouts (settlements)
        $stmtEmp = $db->prepare("
            SELECT COALESCE(SUM(es.remaining_payable), 0) as employee_settled
            FROM employee_settlements es
            WHERE $settDateWhere
        ");
        $stmtEmp->execute($settParams);
        $employeeSettledCash = (float)$stmtEmp->fetchColumn();

        // Total True Operating Burden (Accrual / Economic: Variable expenses + Prorated Fixed Burden)
        $trueOperatingExpenses = $variableExpenses + $periodFixedBurden;
        $trueNetProfit = $grossProfit - $trueOperatingExpenses;

        // Breakeven metrics
        $grossMarginPct = ($netRevenue > 0) ? round(($grossProfit / $netRevenue) * 100, 1) : 0.0;
        $breakevenSales = ($grossMarginPct > 0) ? round($trueOperatingExpenses / ($grossMarginPct / 100), 2) : round($trueOperatingExpenses * 1.33, 2);
        $breakevenProgressPct = ($breakevenSales > 0 && $netRevenue > 0) ? min(300, round(($netRevenue / $breakevenSales) * 100, 1)) : 0.0;

        // 6. Cash Drawer (Galla) Inflow & Outflow Tracking
        $payDateWhere = str_replace('s.', 's2.', $dateWhere);
        $stmtCash = $db->prepare("
            SELECT COALESCE(SUM(p.amount), 0)
            FROM payments p
            JOIN sales s2 ON p.sale_id = s2.id
            WHERE s2.status != 'voided' AND p.payment_method = 'cash' AND $payDateWhere
        ");
        $stmtCash->execute(!empty($params) ? $params : []);
        $cashFromSales = (float)$stmtCash->fetchColumn();

        // Cash difference collected on exchanges (when customer pays cash difference to shop)
        $stmtExchCashIn = $db->prepare("
            SELECT COALESCE(SUM(e.net_difference), 0)
            FROM exchanges e
            WHERE e.settlement_method = 'cash' AND e.net_difference > 0 AND $excDateWhere
        ");
        $stmtExchCashIn->execute(!empty($params) ? $params : []);
        $exchangeCashIn = (float)$stmtExchCashIn->fetchColumn();

        // Standalone cash recoveries made in khata/index.php (excluding Counter Cash Down Payments already recorded in POS payments table)
        $stmtKhataCash = $db->prepare("
            SELECT COALESCE(SUM(p.amount), 0)
            FROM customer_khata_payments p
            WHERE p.payment_method = 'cash' 
              AND p.status = 'active' 
              AND (p.notes IS NULL OR p.notes NOT LIKE 'Counter Cash Down Payment%') 
              AND $khataPayDateWhere
        ");
        $stmtKhataCash->execute(!empty($khataPayParams) ? $khataPayParams : []);
        $khataCashRecovered = (float)$stmtKhataCash->fetchColumn();

        $totalDrawerInflow = $cashFromSales + $exchangeCashIn + $khataCashRecovered;

        // Drawer Expenses
        $stmtDrawerExp = $db->prepare("
            SELECT COALESCE(SUM(e.amount), 0)
            FROM expenses e
            WHERE e.status = 'active' AND e.payment_source = 'drawer_cash' AND $expDateWhere
        ");
        $stmtDrawerExp->execute($expParams);
        $drawerExpOut = (float)$stmtDrawerExp->fetchColumn();

        // Staff Advances
        $etDateWhere = str_replace('es.created_at', 'et.created_at', $settDateWhere);
        $stmtDrawerAdv = $db->prepare("
            SELECT COALESCE(SUM(et.amount), 0)
            FROM employee_transactions et
            WHERE et.type IN ('advance', 'daily_payment') AND $etDateWhere
        ");
        $stmtDrawerAdv->execute($settParams);
        $drawerAdvOut = (float)$stmtDrawerAdv->fetchColumn();

        // Cash Refunds Outflow
        $stmtRefCashOut = $db->prepare("
            SELECT COALESCE(SUM(r.total_refunded), 0)
            FROM refunds r
            WHERE $refDateWhere
        ");
        $stmtRefCashOut->execute($refParams);
        $refundCashOut = (float)$stmtRefCashOut->fetchColumn();

        // Cash difference refunded on exchanges (when shop refunds cash difference to customer)
        $stmtExchCashOut = $db->prepare("
            SELECT COALESCE(SUM(ABS(e.net_difference)), 0)
            FROM exchanges e
            WHERE e.settlement_method = 'cash' AND e.net_difference < 0 AND $excDateWhere
        ");
        $stmtExchCashOut->execute(!empty($params) ? $params : []);
        $exchangeCashOut = (float)$stmtExchCashOut->fetchColumn();

        $totalDrawerOutflow = $drawerExpOut + $drawerAdvOut + $refundCashOut + $exchangeCashOut;
        $netDrawerCash = $totalDrawerInflow - $totalDrawerOutflow;

        // 6b. Bank Account & Digital Payments Tracking
        $stmtBank = $db->prepare("
            SELECT COALESCE(SUM(p.amount), 0)
            FROM payments p
            JOIN sales s2 ON p.sale_id = s2.id
            WHERE s2.status != 'voided' AND p.payment_method IN ('bank', 'card') AND $payDateWhere
        ");
        $stmtBank->execute(!empty($params) ? $params : []);
        $bankFromSales = (float)$stmtBank->fetchColumn();

        // Bank difference collected on exchanges
        $stmtExchBankIn = $db->prepare("
            SELECT COALESCE(SUM(e.net_difference), 0)
            FROM exchanges e
            WHERE e.settlement_method = 'bank' AND e.net_difference > 0 AND $excDateWhere
        ");
        $stmtExchBankIn->execute(!empty($params) ? $params : []);
        $exchangeBankIn = (float)$stmtExchBankIn->fetchColumn();

        // Standalone Bank/Digital recoveries from Customer Khata
        $stmtKhataBank = $db->prepare("
            SELECT COALESCE(SUM(p.amount), 0)
            FROM customer_khata_payments p
            WHERE p.payment_method IN ('bank', 'easypaisa_jazzcash') 
              AND p.status = 'active' 
              AND (p.notes IS NULL OR p.notes NOT LIKE 'Counter Cash Down Payment%') 
              AND $khataPayDateWhere
        ");
        $stmtKhataBank->execute(!empty($khataPayParams) ? $khataPayParams : []);
        $khataBankRecovered = (float)$stmtKhataBank->fetchColumn();

        $totalBankInflow = $bankFromSales + $exchangeBankIn + $khataBankRecovered;

        // Bank expenses paid directly from bank/online
        $stmtBankExp = $db->prepare("
            SELECT COALESCE(SUM(e.amount), 0)
            FROM expenses e
            WHERE e.status = 'active' AND (e.payment_source = 'bank_transfer' OR LOWER(e.payment_source) LIKE '%bank%') AND $expDateWhere
        ");
        $stmtBankExp->execute($expParams);
        $bankExpOut = (float)$stmtBankExp->fetchColumn();

        // Bank difference refunded on exchanges
        $stmtExchBankOut = $db->prepare("
            SELECT COALESCE(SUM(ABS(e.net_difference)), 0)
            FROM exchanges e
            WHERE e.settlement_method = 'bank' AND e.net_difference < 0 AND $excDateWhere
        ");
        $stmtExchBankOut->execute(!empty($params) ? $params : []);
        $exchangeBankOut = (float)$stmtExchBankOut->fetchColumn();

        $totalBankOutflow = $bankExpOut + $exchangeBankOut;
        $netBankAccount = $totalBankInflow - $totalBankOutflow;

        // 7. Cashier Sales & Billing Activity
        $stmtCashiers = $db->prepare("
            SELECT u.id, u.username, u.role,
                   COUNT(s3.id) as bills_count,
                   COALESCE(SUM(s3.total), 0) as total_sales
            FROM users u
            LEFT JOIN sales s3 ON u.id = s3.cashier_id AND s3.status != 'voided' AND " . str_replace('s.', 's3.', $dateWhere) . "
            GROUP BY u.id
            ORDER BY total_sales DESC
        ");
        $stmtCashiers->execute(!empty($params) ? $params : []);
        $cashierStats = $stmtCashiers->fetchAll(PDO::FETCH_ASSOC);

        // 8. Inventory summaries
        $activeProducts = $db->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
        $draftProducts = $db->query("SELECT COUNT(*) FROM products WHERE status = 'draft'")->fetchColumn();
        $lowStockProducts = $db->query("SELECT COUNT(*) FROM products WHERE quantity <= low_stock_threshold AND status = 'active'")->fetchColumn();
        $inventoryValue = $db->query("SELECT COALESCE(SUM(cost_price * quantity), 0) FROM products WHERE status = 'active'")->fetchColumn();
        
        sendJSON([
            'success' => true,
            'sales_revenue' => (float)$totalGrossRevenue,
            'sales_count' => (int)$totalSalesCount,
            'sales_discounts' => (float)$salesKpi['discount'],
            'items_sold' => (int)$totalItemsSold,
            'avg_transaction' => (int)$totalSalesCount > 0 ? (float)$totalGrossRevenue / (int)$totalSalesCount : 0.00,
            
            // Credit Sales (Udhar Given)
            'credit_sales' => round($totalCreditSales, 2),
            'credit_bills_count' => (int)$totalCreditBillsCount,
            'pos_credit_sales' => round($posCreditSales, 2),
            'direct_khata_sales' => round($directKhataRevenue, 2),

            'refunded_amount' => (float)$refundKpi['total_refunded'],
            
            'net_revenue' => $netRevenue,
            'cogs' => $netCogs,
            'gross_profit' => $grossProfit,
            'gross_margin_pct' => $grossMarginPct,
            
            // Fixed Overheads Breakdown
            'fixed_overheads' => [
                'monthly_rent' => $monthlyRent,
                'daily_rent' => round($dailyRent, 2),
                'period_rent' => round($periodRent, 2),
                'monthly_payroll' => $monthlyPayroll,
                'daily_payroll' => round($dailyPayroll, 2),
                'period_payroll' => round($periodPayroll, 2),
                'active_staff_count' => $activeStaffCount,
                'monthly_other_fixed' => $otherMonthlyFixed,
                'daily_other_fixed' => round($dailyOtherFixed, 2),
                'period_other_fixed' => round($periodOtherFixed, 2),
                'other_fixed_title' => $otherFixedTitle,
                'other_fixed_items' => $otherFixedItems,
                'days_in_month' => $daysInMonth,
                'effective_days' => $effectiveDays,
                'month_name' => $periodLabel,
                'total_monthly_fixed' => round($monthlyRent + $monthlyPayroll + $otherMonthlyFixed, 2),
                'daily_fixed_burden' => round($dailyTotalFixed, 2),
                'period_fixed_burden' => round($periodFixedBurden, 2)
            ],
            
            'variable_expenses' => round($variableExpenses, 2),
            'rent_disbursed' => round($rentDisbursed, 2),
            'total_cash_expenses' => round($totalCashExpenses, 2),
            'employee_settled_cash' => round($employeeSettledCash, 2),
            
            'operating_expenses' => round($trueOperatingExpenses, 2),
            'true_operating_expenses' => round($trueOperatingExpenses, 2),
            'net_profit' => round($trueNetProfit, 2),
            'true_net_profit' => round($trueNetProfit, 2),
            'breakeven_sales' => round($breakevenSales, 2),
            'breakeven_progress_pct' => $breakevenProgressPct,
            
            // Cash Drawer (Galla) Balance & Components
            'drawer_cash_in' => round($totalDrawerInflow, 2),
            'cash_from_sales' => round($cashFromSales, 2),
            'khata_cash_recovered' => round($khataCashRecovered, 2),
            'drawer_cash_out' => round($totalDrawerOutflow, 2),
            'drawer_cash_balance' => round($netDrawerCash, 2),
            
            // Bank & Online Account Tracking & Components
            'bank_inflow' => round($totalBankInflow, 2),
            'bank_from_sales' => round($bankFromSales, 2),
            'khata_bank_recovered' => round($khataBankRecovered, 2),
            'bank_outflow' => round($totalBankOutflow, 2),
            'bank_balance' => round($netBankAccount, 2),

            'cashier_stats' => $cashierStats,

            // Legacy / backward compatibility keys
            'expense_cash' => round($totalCashExpenses, 2),
            'employee_payroll' => round($periodPayroll, 2),
            
            'active_products' => (int)$activeProducts,
            'draft_products' => (int)$draftProducts,
            'low_stock_products' => (int)$lowStockProducts,
            'inventory_value' => (float)$inventoryValue
        ]);
    }
    
    else if ($action === 'revenue_trend') {
        requirePermission('VIEW_PROFIT');
        
        // Last 15 days trend
        $stmt = $db->query("
            SELECT DATE(created_at) as date, COALESCE(SUM(total), 0) as total_sales
            FROM sales
            WHERE status != 'voided' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC
        ");
        $trend = $stmt->fetchAll();
        
        sendJSON(['success' => true, 'trend' => $trend]);
    }
    
    else if ($action === 'top_selling') {
        requirePermission('VIEW_PROFIT');
        
        $stmt = $db->query("
            SELECT p.name, p.barcode, SUM(si.quantity) as total_qty, SUM(si.net_amount) as total_val
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            JOIN products p ON si.product_id = p.id
            WHERE s.status != 'voided'
            GROUP BY si.product_id
            ORDER BY total_qty DESC
            LIMIT 5
        ");
        $top = $stmt->fetchAll();
        sendJSON(['success' => true, 'top' => $top]);
    }
    
    else if ($action === 'low_stock') {
        requirePermission('MANAGE_INVENTORY');
        
        $stmt = $db->query("
            SELECT p.name, p.barcode, p.quantity, p.low_stock_threshold, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.quantity <= p.low_stock_threshold AND p.status = 'active'
            ORDER BY p.quantity ASC
            LIMIT 5
        ");
        $low = $stmt->fetchAll();
        sendJSON(['success' => true, 'low' => $low]);
    }
    
    else if ($action === 'audit_logs') {
        if (!isAdmin()) {
            sendJSON(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        
        $stmt = $db->query("
            SELECT al.*, u.username 
            FROM audit_logs al
            JOIN users u ON al.user_id = u.id
            ORDER BY al.id DESC
            LIMIT 100
        ");
        $logs = $stmt->fetchAll();
        
        sendJSON(['success' => true, 'logs' => $logs]);
    }

    else if ($action === 'get_fixed_overheads') {
        requirePermission('VIEW_PROFIT');
        
        $refDate = date('Y-m-d');
        $daysInMonth = (int)date('t', strtotime($refDate));
        
        $settingsStmt = $db->query("SELECT setting_key, setting_value FROM store_settings WHERE setting_key IN ('monthly_shop_rent', 'other_monthly_fixed', 'other_fixed_title', 'other_monthly_fixed_items')");
        $settingsMap = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $monthlyRent = isset($settingsMap['monthly_shop_rent']) ? (float)$settingsMap['monthly_shop_rent'] : 50000.00;
        $otherMonthlyFixed = isset($settingsMap['other_monthly_fixed']) ? (float)$settingsMap['other_monthly_fixed'] : 0.00;
        $otherFixedTitle = isset($settingsMap['other_fixed_title']) && !empty($settingsMap['other_fixed_title']) ? $settingsMap['other_fixed_title'] : 'Estimated Utilities & Bills';

        $otherFixedItems = [];
        if (!empty($settingsMap['other_monthly_fixed_items'])) {
            $decoded = json_decode($settingsMap['other_monthly_fixed_items'], true);
            if (is_array($decoded)) {
                $otherFixedItems = $decoded;
            }
        }
        if (empty($otherFixedItems) && $otherMonthlyFixed > 0) {
            $otherFixedItems[] = [
                'title' => $otherFixedTitle,
                'amount' => $otherMonthlyFixed
            ];
        }

        $salStmt = $db->query("
            SELECT e.id, e.name, e.designation,
                   COALESCE((SELECT salary_amount FROM employee_salary_history esh WHERE esh.employee_id = e.id ORDER BY esh.effective_from DESC, esh.id DESC LIMIT 1), 0) as current_salary
            FROM employees e
            WHERE e.status = 'active'
            ORDER BY e.name ASC
        ");
        $activeEmployees = $salStmt->fetchAll();
        $monthlyPayroll = 0.0;
        foreach ($activeEmployees as $emp) {
            $monthlyPayroll += (float)$emp['current_salary'];
        }

        $dailyRent = $monthlyRent / $daysInMonth;
        $dailyPayroll = $monthlyPayroll / $daysInMonth;
        $dailyOtherFixed = $otherMonthlyFixed / $daysInMonth;
        $dailyTotalFixed = ($monthlyRent + $monthlyPayroll + $otherMonthlyFixed) / $daysInMonth;

        sendJSON([
            'success' => true,
            'monthly_shop_rent' => $monthlyRent,
            'other_monthly_fixed' => $otherMonthlyFixed,
            'other_fixed_title' => $otherFixedTitle,
            'other_monthly_fixed_items' => $otherFixedItems,
            'active_staff_count' => count($activeEmployees),
            'active_employees' => $activeEmployees,
            'total_monthly_payroll' => round($monthlyPayroll, 2),
            'total_monthly_fixed' => round($monthlyRent + $monthlyPayroll + $otherMonthlyFixed, 2),
            'days_in_month' => $daysInMonth,
            'month_name' => date('F Y', strtotime($refDate)),
            'daily_rent' => round($dailyRent, 2),
            'daily_payroll' => round($dailyPayroll, 2),
            'daily_other_fixed' => round($dailyOtherFixed, 2),
            'daily_total_fixed' => round($dailyTotalFixed, 2)
        ]);
    }

    // -------------------------------------------------------------------------
    // PRODUCT VELOCITY, DEAD ITEMS & INVENTORY INTELLIGENCE
    // -------------------------------------------------------------------------
    else if ($action === 'product_velocity') {
        if (!isAdmin() && !hasPermission('VIEW_PROFIT') && !hasPermission('MANAGE_INVENTORY')) {
            sendJSON(['success' => false, 'message' => 'Forbidden: You do not have permission to view product velocity.'], 403);
        }

        $period = isset($_GET['period']) ? sanitize($_GET['period']) : '30_days';
        $startDate = null;
        $endDate = null;
        $hasDateFilter = true;

        if ($period === '7_days') {
            $startDate = date('Y-m-d', strtotime('-6 days'));
            $endDate = date('Y-m-d');
        } else if ($period === '30_days') {
            $startDate = date('Y-m-d', strtotime('-29 days'));
            $endDate = date('Y-m-d');
        } else if ($period === '60_days') {
            $startDate = date('Y-m-d', strtotime('-59 days'));
            $endDate = date('Y-m-d');
        } else if ($period === '90_days') {
            $startDate = date('Y-m-d', strtotime('-89 days'));
            $endDate = date('Y-m-d');
        } else if ($period === 'custom') {
            $startDate = !empty($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-d', strtotime('-29 days'));
            $endDate = !empty($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');
        } else if ($period === 'all_time') {
            $hasDateFilter = false;
        } else {
            // Default 30 days
            $period = '30_days';
            $startDate = date('Y-m-d', strtotime('-29 days'));
            $endDate = date('Y-m-d');
        }

        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $stockFilter = isset($_GET['stock_filter']) ? sanitize($_GET['stock_filter']) : 'all';
        $segment = isset($_GET['segment']) ? sanitize($_GET['segment']) : 'all';
        $search = isset($_GET['search']) ? trim(sanitize($_GET['search'])) : '';
        $sortBy = isset($_GET['sort_by']) ? sanitize($_GET['sort_by']) : 'units_sold';
        $sortDir = (isset($_GET['sort_dir']) && strtolower($_GET['sort_dir']) === 'asc') ? 'asc' : 'desc';

        // Subquery date constraints
        $salesDateCond = $hasDateFilter ? "AND DATE(s.created_at) >= ? AND DATE(s.created_at) <= ?" : "";
        $khataDateCond = $hasDateFilter ? "AND b.bill_date >= ? AND b.bill_date <= ?" : "";
        $refundDateCond = $hasDateFilter ? "AND DATE(r.created_at) >= ? AND DATE(r.created_at) <= ?" : "";
        $exchRetDateCond = $hasDateFilter ? "AND DATE(e.created_at) >= ? AND DATE(e.created_at) <= ?" : "";
        $exchRepDateCond = $hasDateFilter ? "AND DATE(e.created_at) >= ? AND DATE(e.created_at) <= ?" : "";

        $queryParams = [];
        if ($hasDateFilter) {
            $queryParams = [
                $startDate, $endDate, // POS sales
                $startDate, $endDate, // Khata sales
                $startDate, $endDate, // Refunds
                $startDate, $endDate, // Exch returned
                $startDate, $endDate  // Exch replaced
            ];
        }

        $sql = "
            SELECT 
                p.id,
                p.name,
                p.barcode,
                p.cost_price,
                p.selling_price,
                p.quantity AS stock_quantity,
                p.low_stock_threshold,
                p.status,
                c.name AS category_name,
                c.id AS category_id,
                COALESCE(pos_sales.units_sold, 0) AS pos_units_sold,
                COALESCE(pos_sales.revenue, 0) AS pos_revenue,
                COALESCE(pos_sales.cogs, 0) AS pos_cogs,
                COALESCE(khata_sales.units_sold, 0) AS khata_units_sold,
                COALESCE(khata_sales.revenue, 0) AS khata_revenue,
                COALESCE(khata_sales.cogs, 0) AS khata_cogs,
                COALESCE(refunds.units_refunded, 0) AS units_refunded,
                COALESCE(refunds.refund_amount, 0) AS refund_amount,
                COALESCE(refunds.refund_cogs, 0) AS refund_cogs,
                COALESCE(exch_ret.units_ret, 0) AS exch_units_returned,
                COALESCE(exch_ret.val_credited, 0) AS exch_val_returned,
                COALESCE(exch_ret.cogs_reversed, 0) AS exch_cogs_reversed,
                COALESCE(exch_rep.units_rep, 0) AS exch_units_replaced,
                COALESCE(exch_rep.val_charged, 0) AS exch_val_replaced,
                COALESCE(exch_rep.cogs_added, 0) AS exch_cogs_added,
                last_sales.last_sold_at
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN (
                SELECT 
                    si.product_id,
                    SUM(si.quantity) AS units_sold,
                    SUM(si.net_amount) AS revenue,
                    SUM(si.cost_price_snapshot * si.quantity) AS cogs
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                WHERE s.status != 'voided' $salesDateCond
                GROUP BY si.product_id
            ) pos_sales ON p.id = pos_sales.product_id
            LEFT JOIN (
                SELECT 
                    bi.product_id,
                    SUM(bi.quantity) AS units_sold,
                    SUM(bi.total_price) AS revenue,
                    SUM(p2.cost_price * bi.quantity) AS cogs
                FROM customer_khata_bill_items bi
                JOIN customer_khata_bills b ON bi.bill_id = b.id
                JOIN products p2 ON bi.product_id = p2.id
                WHERE b.status = 'active' AND b.sale_id IS NULL $khataDateCond
                GROUP BY bi.product_id
            ) khata_sales ON p.id = khata_sales.product_id
            LEFT JOIN (
                SELECT 
                    ri.product_id,
                    SUM(ri.quantity_refunded) AS units_refunded,
                    SUM(ri.refund_amount) AS refund_amount,
                    SUM(p3.cost_price * ri.quantity_refunded) AS refund_cogs
                FROM refund_items ri
                JOIN refunds r ON ri.refund_id = r.id
                JOIN products p3 ON ri.product_id = p3.id
                WHERE 1=1 $refundDateCond
                GROUP BY ri.product_id
            ) refunds ON p.id = refunds.product_id
            LEFT JOIN (
                SELECT 
                    eri.product_id,
                    SUM(eri.quantity) AS units_ret,
                    SUM(eri.value_credited) AS val_credited,
                    SUM(p4.cost_price * eri.quantity) AS cogs_reversed
                FROM exchange_returned_items eri
                JOIN exchanges e ON eri.exchange_id = e.id
                JOIN products p4 ON eri.product_id = p4.id
                WHERE 1=1 $exchRetDateCond
                GROUP BY eri.product_id
            ) exch_ret ON p.id = exch_ret.product_id
            LEFT JOIN (
                SELECT 
                    erp.product_id,
                    SUM(erp.quantity) AS units_rep,
                    SUM(erp.value_charged) AS val_charged,
                    SUM(erp.cost_price_snapshot * erp.quantity) AS cogs_added
                FROM exchange_replaced_items erp
                JOIN exchanges e ON erp.exchange_id = e.id
                WHERE 1=1 $exchRepDateCond
                GROUP BY erp.product_id
            ) exch_rep ON p.id = exch_rep.product_id
            LEFT JOIN (
                SELECT 
                    si.product_id,
                    MAX(s.created_at) AS last_sold_at
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                WHERE s.status != 'voided'
            ) last_sales ON p.id = last_sales.product_id
        ";

        $whereClause = "WHERE p.status = 'active'";
        if ($categoryId > 0) {
            $whereClause .= " AND p.category_id = ?";
            $queryParams[] = $categoryId;
        }
        $sql .= " " . $whereClause;

        $stmt = $db->prepare($sql);
        $stmt->execute($queryParams);
        $rawProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate period days for daily run-rate
        $periodDays = 30;
        if ($period === '7_days') $periodDays = 7;
        else if ($period === '30_days') $periodDays = 30;
        else if ($period === '60_days') $periodDays = 60;
        else if ($period === '90_days') $periodDays = 90;
        else if ($period === 'custom') {
            $periodDays = max(1, (int)((strtotime($endDate) - strtotime($startDate)) / 86400) + 1);
        } else if ($period === 'all_time') {
            $periodDays = 90;
        }

        // Process aggregations and velocity metrics
        $processedProducts = [];
        $totalDeadCount = 0;
        $deadCapitalTied = 0.0;
        $deadRetailTied = 0.0;
        $totalSlowCount = 0;
        $slowCapitalTied = 0.0;
        $totalFastCount = 0;
        $fastRevenueTotal = 0.0;
        $totalReorderNeeded = 0;
        $totalSuggestedReorderItems = 0;
        $totalSuggestedReorderUnits = 0;
        $totalEstimatedReorderBudget = 0.0;
        $totalInStockCount = 0;
        $totalOutOfStockCount = 0;
        $overallTotalStockCost = 0.0;
        $overallTotalStockRetail = 0.0;
        $overallTotalStockUnits = 0;

        foreach ($rawProducts as $p) {
            $costPrice = (float)$p['cost_price'];
            $sellingPrice = (float)$p['selling_price'];
            $stockQty = (int)$p['stock_quantity'];
            $lowThreshold = (int)$p['low_stock_threshold'];

            // Net Units Sold = (POS + Khata + Replaced) - (Refunded + Returned)
            $netSold = ($p['pos_units_sold'] + $p['khata_units_sold'] + $p['exch_units_replaced'])
                     - ($p['units_refunded'] + $p['exch_units_returned']);
            $netSold = max(0, $netSold);

            // Net Revenue
            $netRevenue = ($p['pos_revenue'] + $p['khata_revenue'] + $p['exch_val_replaced'])
                        - ($p['refund_amount'] + $p['exch_val_returned']);
            $netRevenue = max(0.00, (float)$netRevenue);

            // Net COGS
            $netCogs = ($p['pos_cogs'] + $p['khata_cogs'] + $p['exch_cogs_added'])
                     - ($p['refund_cogs'] + $p['exch_cogs_reversed']);
            $netCogs = max(0.00, (float)$netCogs);

            $netProfit = $netRevenue - $netCogs;

            // Stock Capital Values
            $stockCostVal = round($stockQty * $costPrice, 2);
            $stockRetailVal = round($stockQty * $sellingPrice, 2);

            if ($stockQty > 0) {
                $totalInStockCount++;
                $overallTotalStockUnits += $stockQty;
                $overallTotalStockCost += $stockCostVal;
                $overallTotalStockRetail += $stockRetailVal;
            } else {
                $totalOutOfStockCount++;
            }

            // Days since last sale calculation
            $daysSinceLastSale = null;
            $lastSoldFormatted = 'Never Sold';
            if (!empty($p['last_sold_at'])) {
                $lastDate = new DateTime($p['last_sold_at']);
                $todayDate = new DateTime();
                $diff = $todayDate->diff($lastDate);
                $daysSinceLastSale = $diff->days;
                $lastSoldFormatted = ($daysSinceLastSale == 0) ? 'Today' : (($daysSinceLastSale == 1) ? 'Yesterday' : $daysSinceLastSale . ' days ago');
            }

            // Daily Sales Velocity (Run-Rate)
            $dailyRunRate = ($periodDays > 0) ? round($netSold / $periodDays, 2) : 0.00;

            // Days of Stock Remaining (Days of Inventory in Hand - DOH)
            if ($dailyRunRate > 0) {
                $daysOfStock = round($stockQty / $dailyRunRate, 1);
            } else {
                $daysOfStock = ($stockQty > 0) ? 999 : 0;
            }

            // Dynamic Thresholds based on selected time window
            $slowSaleThreshold = max(3, (int)ceil($periodDays / 20));

            // Status Classification (Enterprise Retail Standard):
            // 1. Dead Stock: Stock > 0 AND 0 units sold in the period (Trapped Cash!)
            // 2. Slow Moving: Stock > 0 AND (1-3 sold OR hasn't sold in past 30+ days OR stock cover > 90 days)
            // 3. Fast Moving: netSold > slowSaleThreshold AND recent sales activity
            // 4. Out of Stock: Stock == 0
            $velocityStatus = 'zero_stock';
            $isDead = false;
            $isSlow = false;
            $isFast = false;
            $isReorder = false;
            $slowReason = '';

            if ($stockQty > 0 && $netSold == 0) {
                $velocityStatus = 'dead';
                $isDead = true;
                $totalDeadCount++;
                $deadCapitalTied += $stockCostVal;
                $deadRetailTied += $stockRetailVal;
            } else if ($stockQty > 0 && (
                ($netSold >= 1 && $netSold <= $slowSaleThreshold) ||
                ($daysSinceLastSale !== null && $daysSinceLastSale >= 30) ||
                ($daysOfStock >= 90 && $dailyRunRate < 0.25)
            )) {
                $velocityStatus = 'slow';
                $isSlow = true;
                $totalSlowCount++;
                $slowCapitalTied += $stockCostVal;

                if ($daysSinceLastSale !== null && $daysSinceLastSale >= 30) {
                    $slowReason = "Stalled Demand: No sales in {$daysSinceLastSale} days";
                } else if ($daysOfStock >= 90) {
                    $slowReason = "Overstocked: ~" . round($daysOfStock) . " days of stock remaining";
                } else {
                    $slowReason = "Low Velocity: Only {$netSold} sold in {$periodDays} days";
                }
            } else if ($netSold > $slowSaleThreshold && ($daysSinceLastSale === null || $daysSinceLastSale < 30)) {
                $velocityStatus = 'fast';
                $isFast = true;
                $totalFastCount++;
                $fastRevenueTotal += $netRevenue;
            } else if ($stockQty <= 0) {
                $velocityStatus = 'out_of_stock';
            }

            // Reorder recommendation:
            // If item has sales velocity > 0 AND (stock <= lowThreshold OR days of stock <= 7)
            if (($stockQty <= $lowThreshold || $daysOfStock <= 7) && $netSold > 0) {
                $isReorder = true;
                $totalReorderNeeded++;
            }

            // Smart Restock Forecasting & Decision Making (Target: 30 days buffer coverage)
            $suggestedOrderQty = 0;
            $orderUrgency = 'none';
            $orderAdvice = '';

            if ($isDead) {
                $suggestedOrderQty = 0;
                $orderUrgency = 'dead';
                $orderAdvice = 'DO NOT REORDER (Dead Capital). Discount or bundle to release cash.';
            } else if ($isSlow) {
                if ($stockQty > 0) {
                    $suggestedOrderQty = 0;
                    $orderUrgency = 'slow';
                    $orderAdvice = !empty($slowReason) ? $slowReason . '. Do not reorder in bulk.' : 'Slow mover. Current stock is sufficient. Avoid bulk reordering.';
                } else {
                    $suggestedOrderQty = 2;
                    $orderUrgency = 'slow';
                    $orderAdvice = 'Slow mover out of stock. Order minimal (1-2 pcs) on demand.';
                }
            } else if ($isFast || $isReorder || $netSold > 0) {
                $targetInventory = max((int)ceil($dailyRunRate * 30), $lowThreshold * 2);
                $deficit = max(0, $targetInventory - $stockQty);

                if ($stockQty <= $lowThreshold || $daysOfStock <= 7) {
                    $orderUrgency = 'urgent';
                    $suggestedOrderQty = max(1, (int)$deficit);
                    $daysLabel = ($daysOfStock < 999) ? "{$daysOfStock} days" : "immediate";
                    $orderAdvice = "CRITICAL: Stock finishes in {$daysLabel}! Order {$suggestedOrderQty} units to prevent sales loss.";
                } else if ($daysOfStock <= 15) {
                    $orderUrgency = 'medium';
                    $suggestedOrderQty = max(1, (int)$deficit);
                    $orderAdvice = "Restock Soon: {$daysOfStock} days stock remaining. Suggested order: {$suggestedOrderQty} units.";
                } else {
                    $orderUrgency = 'healthy';
                    $suggestedOrderQty = 0;
                    $orderAdvice = "Healthy stock (~{$daysOfStock} days remaining). No immediate order needed.";
                }
            } else if ($stockQty <= 0 && $netSold == 0) {
                $suggestedOrderQty = 0;
                $orderUrgency = 'inactive';
                $orderAdvice = 'Inactive item (0 stock & 0 sales). Review before reordering.';
            }

            $estimatedOrderCost = round($suggestedOrderQty * $costPrice, 2);
            if ($suggestedOrderQty > 0) {
                $totalSuggestedReorderItems++;
                $totalSuggestedReorderUnits += $suggestedOrderQty;
                $totalEstimatedReorderBudget += $estimatedOrderCost;
            }

            // Recommended Action (Decision Support)
            $recommendation = '';
            $recBadge = '';
            if ($isDead) {
                $recommendation = 'Clearance Discount / Promote';
                $recBadge = 'danger';
            } else if ($orderUrgency === 'urgent' || $isReorder) {
                $recommendation = 'Order Restock Urgently';
                $recBadge = 'warning';
            } else if ($isFast) {
                $recommendation = 'Top Performer (Keep Stocked)';
                $recBadge = 'success';
            } else if ($isSlow) {
                $recommendation = 'Monitor / Do Not Reorder Bulk';
                $recBadge = 'secondary';
            } else if ($stockQty <= 0 && $netSold == 0) {
                $recommendation = 'Inactive Product (0 Stock & 0 Sales)';
                $recBadge = 'dark';
            }

            $item = [
                'id' => (int)$p['id'],
                'name' => $p['name'],
                'barcode' => $p['barcode'],
                'category_id' => (int)$p['category_id'],
                'category_name' => $p['category_name'] ?? 'Uncategorized',
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'stock_quantity' => $stockQty,
                'low_stock_threshold' => $lowThreshold,
                'units_sold' => $netSold,
                'total_revenue' => $netRevenue,
                'total_cogs' => $netCogs,
                'total_profit' => $netProfit,
                'stock_cost_value' => $stockCostVal,
                'stock_retail_value' => $stockRetailVal,
                'daily_run_rate' => $dailyRunRate,
                'days_of_stock' => $daysOfStock,
                'suggested_order_qty' => $suggestedOrderQty,
                'estimated_order_cost' => $estimatedOrderCost,
                'order_urgency' => $orderUrgency,
                'order_advice' => $orderAdvice,
                'last_sold_at' => $p['last_sold_at'],
                'last_sold_text' => $lastSoldFormatted,
                'days_since_last_sale' => $daysSinceLastSale,
                'velocity_status' => $velocityStatus,
                'is_dead' => $isDead,
                'is_slow' => $isSlow,
                'is_fast' => $isFast,
                'is_reorder' => $isReorder,
                'recommendation' => $recommendation,
                'rec_badge' => $recBadge
            ];

            // Apply segment filter
            if ($segment === 'dead' && !$isDead) continue;
            if ($segment === 'slow' && !$isSlow) continue;
            if ($segment === 'fast' && !$isFast) continue;
            if ($segment === 'reorder' && !$isReorder) continue;

            // Apply stock filter
            if ($stockFilter === 'in_stock' && $stockQty <= 0) continue;
            if ($stockFilter === 'out_of_stock' && $stockQty > 0) continue;
            if ($stockFilter === 'low_stock' && $stockQty > $lowThreshold) continue;

            // Apply category filter
            if ($categoryId > 0 && (int)$p['category_id'] !== $categoryId) continue;

            // Apply search filter
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $nameLower = strtolower($p['name']);
                $barcodeLower = strtolower($p['barcode']);
                $catLower = strtolower($p['category_name'] ?? '');
                if (strpos($nameLower, $searchLower) === false &&
                    strpos($barcodeLower, $searchLower) === false &&
                    strpos($catLower, $searchLower) === false) {
                    continue;
                }
            }

            $processedProducts[] = $item;
        }

        // Sorting
        usort($processedProducts, function($a, $b) use ($sortBy, $sortDir) {
            $valA = $a[$sortBy] ?? 0;
            $valB = $b[$sortBy] ?? 0;

            if ($sortBy === 'name' || $sortBy === 'category_name') {
                $cmp = strcasecmp((string)$valA, (string)$valB);
            } else if ($sortBy === 'last_sold_at') {
                $timeA = !empty($valA) ? strtotime($valA) : 0;
                $timeB = !empty($valB) ? strtotime($valB) : 0;
                $cmp = ($timeA <=> $timeB);
            } else {
                $cmp = ((float)$valA <=> (float)$valB);
            }

            return ($sortDir === 'asc') ? $cmp : -$cmp;
        });

        sendJSON([
            'success' => true,
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => [
                'total_products_tracked' => count($rawProducts),
                'total_in_stock' => $totalInStockCount,
                'total_out_of_stock' => $totalOutOfStockCount,
                'total_stock_units' => $overallTotalStockUnits,
                'overall_stock_cost_value' => round($overallTotalStockCost, 2),
                'overall_stock_retail_value' => round($overallTotalStockRetail, 2),
                'profit_potential' => round($overallTotalStockRetail - $overallTotalStockCost, 2),
                'markup_margin_pct' => ($overallTotalStockCost > 0) ? round((($overallTotalStockRetail - $overallTotalStockCost) / $overallTotalStockCost) * 100, 1) : 0.0,
                'dead_items_count' => $totalDeadCount,
                'dead_capital_tied' => round($deadCapitalTied, 2),
                'dead_retail_tied' => round($deadRetailTied, 2),
                'slow_items_count' => $totalSlowCount,
                'slow_capital_tied' => round($slowCapitalTied, 2),
                'fast_items_count' => $totalFastCount,
                'fast_revenue_total' => round($fastRevenueTotal, 2),
                'reorder_needed_count' => $totalReorderNeeded,
                'suggested_reorder_items_count' => $totalSuggestedReorderItems,
                'suggested_reorder_units' => $totalSuggestedReorderUnits,
                'estimated_reorder_budget' => round($totalEstimatedReorderBudget, 2)
            ],
            'products' => $processedProducts,
            'total_filtered' => count($processedProducts)
        ]);
    }
}
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'save_fixed_overheads') {
        if (!isAdmin()) {
            sendJSON(['success' => false, 'message' => 'Forbidden. Admin privileges required.'], 403);
        }

        $rent = isset($_POST['monthly_shop_rent']) ? max(0.00, (float)$_POST['monthly_shop_rent']) : 0.00;

        $items = [];
        $totalOtherFixed = 0.0;

        if (isset($_POST['other_fixed_title']) && is_array($_POST['other_fixed_title'])) {
            $titles = $_POST['other_fixed_title'];
            $amounts = isset($_POST['other_fixed_amount']) && is_array($_POST['other_fixed_amount']) ? $_POST['other_fixed_amount'] : [];

            for ($i = 0; $i < count($titles); $i++) {
                $rawTitle = trim($titles[$i] ?? '');
                $rawAmount = isset($amounts[$i]) ? max(0.00, (float)$amounts[$i]) : 0.00;
                if ($rawTitle !== '' || $rawAmount > 0) {
                    $itemTitle = !empty($rawTitle) ? sanitize($rawTitle) : 'Fixed Cost #' . ($i + 1);
                    $items[] = [
                        'title' => $itemTitle,
                        'amount' => round($rawAmount, 2)
                    ];
                    $totalOtherFixed += $rawAmount;
                }
            }
        } else if (isset($_POST['other_monthly_fixed'])) {
            // Backward compatibility single value
            $totalOtherFixed = max(0.00, (float)$_POST['other_monthly_fixed']);
            $otherTitle = isset($_POST['other_fixed_title']) ? sanitize($_POST['other_fixed_title']) : 'Estimated Utilities & Bills';
            if ($totalOtherFixed > 0) {
                $items[] = [
                    'title' => $otherTitle,
                    'amount' => $totalOtherFixed
                ];
            }
        }

        $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);
        $summaryTitle = count($items) > 0 ? (count($items) === 1 ? $items[0]['title'] : count($items) . ' Fixed Costs') : 'Estimated Utilities & Bills';

        $oldStmt = $db->query("SELECT setting_key, setting_value FROM store_settings WHERE setting_key IN ('monthly_shop_rent', 'other_monthly_fixed', 'other_fixed_title', 'other_monthly_fixed_items')");
        $oldValues = $oldStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $upsertStmt = $db->prepare("
            INSERT INTO store_settings (setting_key, setting_value, updated_by)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)
        ");

        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
        $upsertStmt->execute(['monthly_shop_rent', number_format($rent, 2, '.', ''), $userId]);
        $upsertStmt->execute(['other_monthly_fixed', number_format($totalOtherFixed, 2, '.', ''), $userId]);
        $upsertStmt->execute(['other_fixed_title', $summaryTitle, $userId]);
        $upsertStmt->execute(['other_monthly_fixed_items', $itemsJson, $userId]);

        $newValues = [
            'monthly_shop_rent' => $rent,
            'other_monthly_fixed' => $totalOtherFixed,
            'other_fixed_title' => $summaryTitle,
            'other_monthly_fixed_items' => $items
        ];

        logAudit('UPDATE', 'store_settings', 1, $oldValues, $newValues, 'Updated monthly shop rent and multiple fixed overhead settings');

        $refDate = date('Y-m-d');
        $daysInMonth = (int)date('t', strtotime($refDate));

        sendJSON([
            'success' => true,
            'message' => 'Shop rent and monthly fixed overheads updated successfully.',
            'settings' => $newValues,
            'other_monthly_fixed_items' => $items,
            'days_in_month' => $daysInMonth,
            'daily_rent' => round($rent / $daysInMonth, 2),
            'daily_other_fixed' => round($totalOtherFixed / $daysInMonth, 2)
        ]);
    }
    
    sendJSON(['success' => false, 'message' => 'Invalid POST action.'], 400);
}

