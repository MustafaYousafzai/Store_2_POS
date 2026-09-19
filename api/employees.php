<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

// Enforce login
if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : (isset($_POST['action']) ? sanitize($_POST['action']) : '');
$db = getDBConnection();

/**
 * Enterprise-grade calculation of active working days and paused days
 * evaluating joining date and all pay pause/resume intervals for a given month.
 */
function getEmployeeWorkingDaysBreakdown($db, $empId, $joiningDate, $month, $calcMode = 'till_date') {
    $totalDaysInMonth = (int)date('t', strtotime($month . '-01'));
    $todayDate = date('Y-m-d');
    $currentYm = date('Y-m');
    $isCurrentMonth = ($month === $currentYm);

    // Period Start
    $periodStart = $month . '-01';
    if (!empty($joiningDate) && $joiningDate > $periodStart) {
        if (date('Y-m', strtotime($joiningDate)) === $month) {
            $periodStart = $joiningDate;
        } else if ($joiningDate > ($month . '-' . sprintf('%02d', $totalDaysInMonth))) {
            return [
                'period_start' => $periodStart,
                'period_end' => $periodStart,
                'days_evaluated' => 0,
                'paused_days' => 0,
                'active_days_worked' => 0,
                'pauses_count' => 0,
                'pauses_list' => []
            ];
        }
    }

    // Period End
    if ($calcMode === 'till_date' && $isCurrentMonth) {
        $periodEnd = min($todayDate, $month . '-' . sprintf('%02d', $totalDaysInMonth));
    } else {
        $periodEnd = $month . '-' . sprintf('%02d', $totalDaysInMonth);
    }

    // Fetch pause intervals overlapping this month
    $monthStart = $month . '-01';
    $monthEnd = $month . '-' . sprintf('%02d', $totalDaysInMonth);

    $pauseStmt = $db->prepare("
        SELECT * FROM employee_pay_pauses 
        WHERE employee_id = ? 
          AND pause_date <= ?
          AND (resume_date >= ? OR resume_date IS NULL)
        ORDER BY pause_date ASC
    ");
    $pauseStmt->execute([$empId, $monthEnd, $monthStart]);
    $pauses = $pauseStmt->fetchAll(PDO::FETCH_ASSOC);

    $startTs = strtotime($periodStart);
    $endTs = strtotime($periodEnd);

    $daysEvaluated = 0;
    $pausedDays = 0;
    $activeDays = 0;

    for ($ts = $startTs; $ts <= $endTs; $ts += 86400) {
        $curDate = date('Y-m-d', $ts);
        $daysEvaluated++;

        $isDayPaused = false;
        foreach ($pauses as $p) {
            $pStart = $p['pause_date'];
            $pResume = $p['resume_date'];

            if ($curDate >= $pStart) {
                if (empty($pResume) || $curDate < $pResume) {
                    $isDayPaused = true;
                    break;
                }
            }
        }

        if ($isDayPaused) {
            $pausedDays++;
        } else {
            $activeDays++;
        }
    }

    if ($calcMode === 'full_month' && $pausedDays === 0 && $periodStart === ($month . '-01')) {
        $activeDays = 30; // standard commercial month
    } else if ($calcMode === 'full_month' && $periodStart === ($month . '-01')) {
        $activeDays = max(0, min(30, 30 - $pausedDays));
    }

    return [
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'days_evaluated' => $daysEvaluated,
        'paused_days' => $pausedDays,
        'active_days_worked' => $activeDays,
        'pauses_count' => count($pauses),
        'pauses_list' => $pauses
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        requirePermission('MANAGE_EMPLOYEES');
        
        $statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        $where = "";
        $params = [];
        if (!empty($statusFilter)) {
            $where = "WHERE e.status = ?";
            $params[] = $statusFilter;
        }
        
        $currentMonth = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? sanitize($_GET['month']) : date('Y-m');
        $isCurrentMonth = ($currentMonth === date('Y-m'));
        $daysInMonth = (int)date('t', strtotime($currentMonth . '-01'));
        $dayOfMonth = (int)date('j');
        $elapsedDays = $isCurrentMonth ? min(30, max(1, $dayOfMonth)) : 30;
        
        $stmt = $db->prepare("
            SELECT e.*, 
                   COALESCE((SELECT salary_amount FROM employee_salary_history esh WHERE esh.employee_id = e.id ORDER BY esh.effective_from DESC, esh.id DESC LIMIT 1), 0) as current_salary,
                   
                   -- Selected Month Advances (advance + daily_payment)
                   COALESCE((SELECT SUM(amount) FROM employee_transactions WHERE employee_id = e.id AND type IN ('advance', 'daily_payment') AND DATE_FORMAT(created_at, '%Y-%m') = ?), 0) as month_advances,
                   
                   -- Selected Month Deductions (fines / absence)
                   COALESCE((SELECT SUM(amount) FROM employee_transactions WHERE employee_id = e.id AND type = 'deduction' AND DATE_FORMAT(created_at, '%Y-%m') = ?), 0) as month_deductions,
                   
                   -- Selected Month Bonuses (incentive + completed extra_task)
                   COALESCE((SELECT SUM(amount) FROM employee_transactions WHERE employee_id = e.id AND type IN ('incentive', 'extra_task') AND DATE_FORMAT(created_at, '%Y-%m') = ?), 0) as month_bonuses,
                   
                   -- Selected Month Settlement Status
                   (SELECT COUNT(*) FROM employee_settlements WHERE employee_id = e.id AND month_year = ? AND status != 'voided') as is_settled_month,
                   
                   -- Active Unsettled Advances / Deductions vs Bonuses (strictly excluding closed/settled months)
                   COALESCE((
                       SELECT 
                           COALESCE(SUM(CASE WHEN et.type IN ('advance', 'daily_payment', 'deduction') THEN et.amount ELSE 0 END), 0) -
                           COALESCE(SUM(CASE WHEN et.type IN ('extra_task', 'incentive') THEN et.amount ELSE 0 END), 0)
                       FROM employee_transactions et 
                       WHERE et.employee_id = e.id 
                         AND et.type != 'salary_settlement'
                         AND (et.reference_type IS NULL OR et.reference_type != 'employee_settlements')
                         AND DATE_FORMAT(et.created_at, '%Y-%m') NOT IN (
                             SELECT es.month_year FROM employee_settlements es WHERE es.employee_id = e.id AND es.status != 'voided'
                         )
                   ), 0) as total_unsettled_balance
            FROM employees e
            $where
            ORDER BY e.status ASC, e.id DESC
        ");
        
        $queryParams = array_merge([$currentMonth, $currentMonth, $currentMonth, $currentMonth], $params);
        $stmt->execute($queryParams);
        $employees = $stmt->fetchAll();
        
        // Enhance calculated fields
        foreach ($employees as &$emp) {
            $salary = (float)$emp['current_salary'];
            $adv = (float)$emp['month_advances'];
            $ded = (float)$emp['month_deductions'];
            $bon = (float)$emp['month_bonuses'];
            $isSettled = (int)$emp['is_settled_month'] > 0;
            $unsettledBal = (float)$emp['total_unsettled_balance'];
            
            $dailyWage = round($salary / 30, 2);

            // Compute elapsed days accurately respecting pay pause intervals
            $daysBreakdownTillToday = getEmployeeWorkingDaysBreakdown($db, $emp['id'], $emp['joining_date'] ?? null, $currentMonth, 'till_date');
            $empElapsedDays = $daysBreakdownTillToday['active_days_worked'];
            $empPausedDays = $daysBreakdownTillToday['paused_days'];

            $daysBreakdownFullMonth = getEmployeeWorkingDaysBreakdown($db, $emp['id'], $emp['joining_date'] ?? null, $currentMonth, 'full_month');
            $fullMonthActiveDays = $daysBreakdownFullMonth['active_days_worked'];
            $fullMonthEarned = round($dailyWage * $fullMonthActiveDays, 2);

            $earnedBase = round($dailyWage * $empElapsedDays, 2);
            $grossTillToday = round($earnedBase + $bon, 2);
            $totalDeductTillToday = round($adv + $ded, 2);
            $netTillToday = round($grossTillToday - $totalDeductTillToday, 2);
            $fullMonthNet = round($fullMonthEarned + $bon - $totalDeductTillToday, 2);
            
            $emp['current_salary'] = $salary;
            $emp['daily_wage'] = $dailyWage;
            $emp['days_worked_till_today'] = $empElapsedDays;
            $emp['paused_days_till_today'] = $empPausedDays;
            $emp['full_month_active_days'] = $fullMonthActiveDays;
            $emp['earned_base_till_today'] = $earnedBase;
            $emp['gross_till_today'] = $grossTillToday;
            $emp['total_deductions_till_today'] = $totalDeductTillToday;
            $emp['net_due_till_today'] = $netTillToday;
            $emp['full_month_net_payable'] = $fullMonthNet;
            $emp['month_advances'] = $adv;
            $emp['month_deductions'] = $ded;
            $emp['month_bonuses'] = $bon;
            $emp['net_advance_balance'] = $isSettled ? 0.00 : max(0.00, $unsettledBal);
            $emp['estimated_take_home'] = $isSettled ? 0.00 : max(0.00, $fullMonthNet);
            $emp['is_settled_month'] = $isSettled;
            $emp['is_pay_paused'] = ($emp['pay_status'] === 'paused');
        }
        unset($emp);
        
        sendJSON(['success' => true, 'employees' => $employees, 'current_month' => $currentMonth, 'elapsed_days' => $elapsedDays]);
    }
    
    else if ($action === 'summary_kpi') {
        requirePermission('MANAGE_EMPLOYEES');
        
        $currentMonth = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? sanitize($_GET['month']) : date('Y-m');
        $isCurrentMonth = ($currentMonth === date('Y-m'));
        $dayOfMonth = (int)date('j');
        $elapsedDays = $isCurrentMonth ? min(30, max(1, $dayOfMonth)) : 30;
        
        $totalStaff = (int)$db->query("SELECT COUNT(*) FROM employees")->fetchColumn();
        $activeStaff = (int)$db->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn();
        
        $salStmt = $db->query("
            SELECT COALESCE(SUM(
                COALESCE((SELECT salary_amount FROM employee_salary_history esh WHERE esh.employee_id = e.id ORDER BY esh.effective_from DESC, esh.id DESC LIMIT 1), 0)
            ), 0) as total_payroll
            FROM employees e
            WHERE e.status = 'active'
        ");
        $totalPayroll = (float)$salStmt->fetchColumn();
        
        // Month advances given
        $advStmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) 
            FROM employee_transactions 
            WHERE type IN ('advance', 'daily_payment') AND DATE_FORMAT(created_at, '%Y-%m') = ?
        ");
        $advStmt->execute([$currentMonth]);
        $monthAdvances = (float)$advStmt->fetchColumn();
        
        // Month deductions
        $dedStmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) 
            FROM employee_transactions 
            WHERE type = 'deduction' AND DATE_FORMAT(created_at, '%Y-%m') = ?
        ");
        $dedStmt->execute([$currentMonth]);
        $monthDeductions = (float)$dedStmt->fetchColumn();
        
        // Month bonuses/tasks
        $bonStmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) 
            FROM employee_transactions 
            WHERE type IN ('incentive', 'extra_task') AND DATE_FORMAT(created_at, '%Y-%m') = ?
        ");
        $bonStmt->execute([$currentMonth]);
        $monthBonuses = (float)$bonStmt->fetchColumn();
        
        // Settled employees count for selected month
        $settStmt = $db->prepare("SELECT COUNT(DISTINCT employee_id) FROM employee_settlements WHERE month_year = ? AND status != 'voided'");
        $settStmt->execute([$currentMonth]);
        $settledCount = (int)$settStmt->fetchColumn();
        
        // Compute Dukan Ne Dena Hai (Store Owes Staff) vs Staff Ne Dena Hai (Staff Owes Store)
        $actStmt = $db->query("SELECT id, name FROM employees WHERE status = 'active'");
        $activeEmployees = $actStmt->fetchAll();
        
        $totalStoreOwes = 0.0;
        $totalStaffOwes = 0.0;
        
        foreach ($activeEmployees as $aEmp) {
            $eId = (int)$aEmp['id'];
            
            // Check if settled
            $chkSett = $db->prepare("SELECT id FROM employee_settlements WHERE employee_id = ? AND month_year = ? AND status != 'voided' LIMIT 1");
            $chkSett->execute([$eId, $currentMonth]);
            if ($chkSett->fetch()) {
                continue; // Already settled, nothing pending for this month
            }
            
            $eSalStmt = $db->prepare("SELECT salary_amount FROM employee_salary_history WHERE employee_id = ? ORDER BY effective_from DESC, id DESC LIMIT 1");
            $eSalStmt->execute([$eId]);
            $baseSal = (float)($eSalStmt->fetchColumn() ?: 0.0);
            $dWage = round($baseSal / 30, 2);
            $earnedBase = round($dWage * $elapsedDays, 2);
            
            $txStmt = $db->prepare("
                SELECT type, COALESCE(SUM(amount), 0) as total_amt
                FROM employee_transactions
                WHERE employee_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?
                GROUP BY type
            ");
            $txStmt->execute([$eId, $currentMonth]);
            $txGroups = $txStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $eAdv = (float)($txGroups['advance'] ?? 0);
            $eDailyPay = (float)($txGroups['daily_payment'] ?? 0);
            $eDed = (float)($txGroups['deduction'] ?? 0);
            $eInc = (float)($txGroups['incentive'] ?? 0);
            $eTasks = (float)($txGroups['extra_task'] ?? 0);
            
            $gross = round($earnedBase + $eInc + $eTasks, 2);
            $totDeduct = round($eAdv + $eDailyPay + $eDed, 2);
            $net = round($gross - $totDeduct, 2);
            
            if ($net > 0) {
                $totalStoreOwes += $net;
            } else if ($net < 0) {
                $totalStaffOwes += abs($net);
            }
        }
        
        sendJSON([
            'success' => true,
            'current_month' => $currentMonth,
            'is_current_month' => $isCurrentMonth,
            'elapsed_days' => $elapsedDays,
            'total_staff' => $totalStaff,
            'active_staff' => $activeStaff,
            'total_payroll' => $totalPayroll,
            'total_store_owes' => round($totalStoreOwes, 2),
            'total_staff_owes' => round($totalStaffOwes, 2),
            'month_advances' => $monthAdvances,
            'month_deductions' => $monthDeductions,
            'month_bonuses' => $monthBonuses,
            'settled_staff_count' => $settledCount
        ]);
    }
    
    else if ($action === 'get') {
        requirePermission('MANAGE_EMPLOYEES');
        
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        $emp = $stmt->fetch();
        
        if ($emp) {
            $salStmt = $db->prepare("SELECT salary_amount FROM employee_salary_history WHERE employee_id = ? ORDER BY effective_from DESC, id DESC LIMIT 1");
            $salStmt->execute([$id]);
            $salary = (float)($salStmt->fetchColumn() ?: 0.00);
            $emp['salary'] = $salary;
            $emp['daily_wage'] = round($salary / 30, 2);
            
            $stmtKc = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM customer_khata_payments WHERE received_by_employee_id = ? AND status = 'active'");
            $stmtKc->execute([$id]);
            $emp['total_khata_collected'] = (float)$stmtKc->fetchColumn();

            sendJSON(['success' => true, 'employee' => $emp]);
        } else {
            sendJSON(['success' => false, 'message' => 'Employee not found.'], 404);
        }
    }
    
    else if ($action === 'list_absences') {
        requirePermission('MANAGE_EMPLOYEES');

        $empId = (int)($_GET['employee_id'] ?? 0);
        $month = sanitize($_GET['month'] ?? '');

        if ($empId <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid employee ID.'], 400);
        }

        $where = "WHERE a.employee_id = ?";
        $params = [$empId];
        if (!empty($month) && $month !== 'all') {
            $where .= " AND DATE_FORMAT(a.absence_date, '%Y-%m') = ?";
            $params[] = $month;
        }

        $stmt = $db->prepare("
            SELECT a.*, u.username as created_by_name, v.username as voided_by_name
            FROM employee_absences a
            LEFT JOIN users u ON a.created_by = u.id
            LEFT JOIN users v ON a.voided_by = v.id
            $where
            ORDER BY a.absence_date DESC, a.id DESC
        ");
        $stmt->execute($params);
        $absences = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendJSON(['success' => true, 'absences' => $absences]);
    }
    
    else if ($action === 'pause_history') {
        requirePermission('MANAGE_EMPLOYEES');
        $empId = (int)($_GET['employee_id'] ?? 0);
        if ($empId <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid employee ID.'], 400);
        }
        $stmt = $db->prepare("
            SELECT p.*, u.username as created_by_name 
            FROM employee_pay_pauses p
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.employee_id = ?
            ORDER BY p.id DESC
        ");
        $stmt->execute([$empId]);
        $history = $stmt->fetchAll();
        sendJSON(['success' => true, 'history' => $history]);
    }
    
    else if ($action === 'ledger') {
        requirePermission('MANAGE_EMPLOYEES');
        
        $empId = (int)($_GET['employee_id'] ?? 0);
        $monthFilter = isset($_GET['month']) ? sanitize($_GET['month']) : ''; // e.g. '2026-09' or 'all'
        
        if ($empId <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid employee ID.'], 400);
        }
        
        // Calculate Opening Balance from prior unsettled months if a specific month is filtered
        $openingBalance = 0.00;
        if (!empty($monthFilter) && $monthFilter !== 'all') {
            $priorStmt = $db->prepare("
                SELECT COALESCE(SUM(CASE 
                    WHEN et.type IN ('advance', 'daily_payment', 'deduction') THEN et.amount 
                    WHEN et.type IN ('extra_task', 'incentive') THEN -et.amount 
                    ELSE 0 END), 0)
                FROM employee_transactions et 
                WHERE et.employee_id = ? 
                  AND et.status = 'active'
                  AND et.type != 'salary_settlement'
                  AND (et.reference_type IS NULL OR et.reference_type != 'employee_settlements')
                  AND DATE_FORMAT(et.created_at, '%Y-%m') < ?
                  AND DATE_FORMAT(et.created_at, '%Y-%m') NOT IN (
                      SELECT es.month_year FROM employee_settlements es WHERE es.employee_id = ?
                  )
            ");
            $priorStmt->execute([$empId, $monthFilter, $empId]);
            $openingBalance = (float)$priorStmt->fetchColumn();
        }

        $whereClause = "WHERE et.employee_id = ?";
        $params = [$empId];
        
        if (!empty($monthFilter) && $monthFilter !== 'all') {
            $whereClause .= " AND DATE_FORMAT(et.created_at, '%Y-%m') = ?";
            $params[] = $monthFilter;
        }
        
        // Fetch chronological order ASC to compute accurate running balance
        $stmt = $db->prepare("
            SELECT et.*, u.username as created_by_name, v.username as voided_by_name
            FROM employee_transactions et
            LEFT JOIN users u ON et.created_by = u.id
            LEFT JOIN users v ON et.voided_by = v.id
            $whereClause
            ORDER BY et.created_at ASC, et.id ASC
        ");
        $stmt->execute($params);
        $allTx = $stmt->fetchAll();
        
        $runningBalance = $openingBalance; // Net advance owed (+ = staff owes store, - = store owes staff)
        $totalDebits = 0.00;   // Advances + Deductions
        $totalCredits = 0.00;  // Extra tasks + Incentives
        $totAdvances = 0.00;
        $totDailyCash = 0.00;
        $totFines = 0.00;
        $totBonuses = 0.00;
        $totTasks = 0.00;
        
        $enrichedTx = [];

        // If there is an opening balance for this month, prepend an Opening Balance entry
        if (!empty($monthFilter) && $monthFilter !== 'all' && abs($openingBalance) > 0.001) {
            $enrichedTx[] = [
                'id' => 0,
                'employee_id' => $empId,
                'type' => 'opening_balance',
                'amount' => abs($openingBalance),
                'description' => 'Opening Balance (Sabqa Peshi Baqaya)',
                'reference_type' => null,
                'reference_id' => null,
                'created_by' => 1,
                'created_by_name' => 'System',
                'created_at' => $monthFilter . '-01 00:00:00',
                'debit' => $openingBalance > 0 ? $openingBalance : 0.00,
                'credit' => $openingBalance < 0 ? abs($openingBalance) : 0.00,
                'running_balance' => $openingBalance,
                'status' => 'active'
            ];
            if ($openingBalance > 0) $totalDebits += $openingBalance;
            else $totalCredits += abs($openingBalance);
        }

        foreach ($allTx as $t) {
            $amt = (float)$t['amount'];
            $type = $t['type'];
            $isVoided = (($t['status'] ?? 'active') === 'voided');
            
            $debit = 0.00;
            $credit = 0.00;
            
            if (!$isVoided) {
                if (in_array($type, ['advance', 'daily_payment', 'deduction'])) {
                    $debit = $amt;
                    $runningBalance += $amt;
                    $totalDebits += $amt;
                    if ($type === 'advance') $totAdvances += $amt;
                    else if ($type === 'daily_payment') $totDailyCash += $amt;
                    else if ($type === 'deduction') $totFines += $amt;
                } else if (in_array($type, ['extra_task', 'incentive'])) {
                    $credit = $amt;
                    $runningBalance -= $amt;
                    $totalCredits += $amt;
                    if ($type === 'incentive') $totBonuses += $amt;
                    else if ($type === 'extra_task') $totTasks += $amt;
                } else if ($type === 'salary_settlement') {
                    // Closing milestone entry
                    $runningBalance = 0.00; // Resets active advance balance for settled period
                }
            }
            
            $t['amount'] = $amt;
            $t['debit'] = $debit;
            $t['credit'] = $credit;
            $t['running_balance'] = $runningBalance;
            $t['is_voided'] = $isVoided;
            $enrichedTx[] = $t;
        }
        
        // Reverse to display newest first
        $displayTx = array_reverse($enrichedTx);
        
        // Check if current or selected month is settled
        $checkMonth = (!empty($monthFilter) && $monthFilter !== 'all') ? $monthFilter : date('Y-m');
        $chkSett = $db->prepare("SELECT * FROM employee_settlements WHERE employee_id = ? AND month_year = ?");
        $chkSett->execute([$empId, $checkMonth]);
        $settlementRecord = $chkSett->fetch();
        
        sendJSON([
            'success' => true,
            'transactions' => $displayTx,
            'total_debits' => round($totalDebits, 2),
            'total_credits' => round($totalCredits, 2),
            'net_balance' => round($runningBalance, 2),
            'opening_balance' => round($openingBalance, 2),
            'total_advances' => round($totAdvances, 2),
            'total_daily_cash' => round($totDailyCash, 2),
            'total_fines' => round($totFines, 2),
            'total_bonuses' => round($totBonuses, 2),
            'total_tasks' => round($totTasks, 2),
            'month_filter' => $monthFilter,
            'is_settled' => !empty($settlementRecord),
            'settlement_record' => $settlementRecord
        ]);
    }
    
    else if ($action === 'list_tasks') {
        requirePermission('MANAGE_EMPLOYEES');
        $empId = (int)($_GET['employee_id'] ?? 0);
        
        $stmt = $db->prepare("
            SELECT et.*, u.username as creator_name 
            FROM employee_tasks et
            LEFT JOIN users u ON et.created_by = u.id
            WHERE et.employee_id = ?
            ORDER BY et.status ASC, et.id DESC
        ");
        $stmt->execute([$empId]);
        $tasks = $stmt->fetchAll();
        
        sendJSON(['success' => true, 'tasks' => $tasks]);
    }
    
    else if ($action === 'settlement_history') {
        requirePermission('MANAGE_EMPLOYEES');
        $empId = (int)($_GET['employee_id'] ?? 0);
        
        $stmt = $db->prepare("
            SELECT es.*, u.username as settled_by_name
            FROM employee_settlements es
            LEFT JOIN users u ON es.settled_by = u.id
            WHERE es.employee_id = ?
            ORDER BY es.month_year DESC, es.id DESC
        ");
        $stmt->execute([$empId]);
        $history = $stmt->fetchAll();
        
        sendJSON(['success' => true, 'history' => $history]);
    }
    
    else if ($action === 'calculate_settlement') {
        requirePermission('MANAGE_EMPLOYEES');
        
        $empId = (int)($_GET['employee_id'] ?? 0);
        $month = sanitize($_GET['month'] ?? ''); // Format: YYYY-MM
        
        if ($empId <= 0 || empty($month)) {
            sendJSON(['success' => false, 'message' => 'Invalid parameters.'], 400);
        }
        
        // 0. Fetch Employee Record
        $empStmt = $db->prepare("SELECT id, name, designation, contact, joining_date, status, pay_status, pay_pause_date, pay_pause_reason FROM employees WHERE id = ?");
        $empStmt->execute([$empId]);
        $empData = $empStmt->fetch();
        if (!$empData) {
            sendJSON(['success' => false, 'message' => 'Employee profile not found.'], 404);
        }

        // 1. Base Salary active during selected month
        $lastDayOfMonth = date("Y-m-t", strtotime($month . "-01"));
        $salStmt = $db->prepare("
            SELECT salary_amount 
            FROM employee_salary_history 
            WHERE employee_id = ? AND effective_from <= ?
            ORDER BY effective_from DESC, id DESC 
            LIMIT 1
        ");
        $salStmt->execute([$empId, $lastDayOfMonth]);
        $baseSalary = $salStmt->fetchColumn();
        
        if ($baseSalary === false) {
            $salStmt2 = $db->prepare("SELECT salary_amount FROM employee_salary_history WHERE employee_id = ? ORDER BY effective_from ASC, id ASC LIMIT 1");
            $salStmt2->execute([$empId]);
            $baseSalary = $salStmt2->fetchColumn() ?: 0.00;
        }
        $baseSalary = (float)$baseSalary;
        
        // 2. Extra tasks completed in selected month (Itemized list & sum)
        $taskStmt = $db->prepare("
            SELECT id, description, compensation_amount, completed_at 
            FROM employee_tasks 
            WHERE employee_id = ? 
              AND status = 'completed' 
              AND DATE_FORMAT(completed_at, '%Y-%m') = ?
            ORDER BY id ASC
        ");
        $taskStmt->execute([$empId, $month]);
        $tasksList = $taskStmt->fetchAll();
        $extraTasks = 0.00;
        foreach ($tasksList as $tk) {
            $extraTasks += (float)$tk['compensation_amount'];
        }
        
        // 3. Incentives recorded in selected month (Itemized list & sum)
        $incStmt = $db->prepare("
            SELECT id, description, amount, created_at 
            FROM employee_transactions 
            WHERE employee_id = ? 
              AND type = 'incentive' 
              AND status = 'active'
              AND DATE_FORMAT(created_at, '%Y-%m') = ?
            ORDER BY id ASC
        ");
        $incStmt->execute([$empId, $month]);
        $incentivesList = $incStmt->fetchAll();
        $incentives = 0.00;
        foreach ($incentivesList as $inc) {
            $incentives += (float)$inc['amount'];
        }
        
        // 4. Advances recorded in selected month (Itemized list & sum)
        $advStmt = $db->prepare("
            SELECT id, description, amount, created_at 
            FROM employee_transactions 
            WHERE employee_id = ? 
              AND type = 'advance' 
              AND status = 'active'
              AND DATE_FORMAT(created_at, '%Y-%m') = ?
            ORDER BY id ASC
        ");
        $advStmt->execute([$empId, $month]);
        $advancesList = $advStmt->fetchAll();
        $advances = 0.00;
        foreach ($advancesList as $ad) {
            $advances += (float)$ad['amount'];
        }
        
        // 5. Daily payments recorded in selected month (Itemized list & sum)
        $payStmt = $db->prepare("
            SELECT id, description, amount, created_at 
            FROM employee_transactions 
            WHERE employee_id = ? 
              AND type = 'daily_payment' 
              AND status = 'active'
              AND DATE_FORMAT(created_at, '%Y-%m') = ?
            ORDER BY id ASC
        ");
        $payStmt->execute([$empId, $month]);
        $dailyPaymentsList = $payStmt->fetchAll();
        $dailyPayments = 0.00;
        foreach ($dailyPaymentsList as $dp) {
            $dailyPayments += (float)$dp['amount'];
        }
        
        // 6. Fines / Manual Deductions in selected month (Itemized list & sum)
        $dedStmt = $db->prepare("
            SELECT id, description, amount, created_at 
            FROM employee_transactions 
            WHERE employee_id = ? 
              AND type = 'deduction' 
              AND status = 'active'
              AND DATE_FORMAT(created_at, '%Y-%m') = ?
            ORDER BY id ASC
        ");
        $dedStmt->execute([$empId, $month]);
        $finesList = $dedStmt->fetchAll();
        $finesDeducted = 0.00;
        foreach ($finesList as $fn) {
            $finesDeducted += (float)$fn['amount'];
        }

        // 6.b. Official Absences (Chuti) logged in selected month
        $absStmt = $db->prepare("
            SELECT id, absence_date, deduction_amount, reason, created_at 
            FROM employee_absences 
            WHERE employee_id = ? 
              AND status = 'active' 
              AND DATE_FORMAT(absence_date, '%Y-%m') = ?
            ORDER BY absence_date ASC
        ");
        $absStmt->execute([$empId, $month]);
        $absencesList = $absStmt->fetchAll(PDO::FETCH_ASSOC);
        $totalAbsentDeduction = 0.00;
        foreach ($absencesList as $ab) {
            $totalAbsentDeduction += (float)$ab['deduction_amount'];
        }

        // 7. Prior Unsettled Advance Balance (Carried Forward from past unsettled periods prior to this month)
        $priorStmt = $db->prepare("
            SELECT COALESCE(SUM(CASE 
                WHEN et.type IN ('advance', 'daily_payment', 'deduction') THEN et.amount 
                WHEN et.type IN ('extra_task', 'incentive') THEN -et.amount 
                ELSE 0 END), 0)
            FROM employee_transactions et 
            WHERE et.employee_id = ? 
              AND et.status = 'active'
              AND et.type != 'salary_settlement'
              AND (et.reference_type IS NULL OR et.reference_type != 'employee_settlements')
              AND DATE_FORMAT(et.created_at, '%Y-%m') < ?
              AND DATE_FORMAT(et.created_at, '%Y-%m') NOT IN (
                  SELECT es.month_year FROM employee_settlements es WHERE es.employee_id = ?
              )
        ");
        $priorStmt->execute([$empId, $month, $empId]);
        $priorUnsettled = (float)$priorStmt->fetchColumn();
        
        $calcMode = sanitize($_GET['calc_mode'] ?? $_GET['mode'] ?? ''); // 'till_date' or 'full_month'
        
        $currentYm = date('Y-m');
        $todayDate = date('Y-m-d');
        $isCurrentMonth = ($month === $currentYm);
        $isPastMonth = ($month < $currentYm);
        $isFutureMonth = ($month > $currentYm);
        
        // Days configuration
        $totalDaysInMonth = (int)date('t', strtotime($month . "-01")); // e.g. 30 in Sep, 31 in Aug
        $salaryBasisDays = 30; // standard commercial denominator in Pakistan retail
        $dailyWage = round($baseSalary / $salaryBasisDays, 2);
        
        // Compute accurate working days and paused days breakdown
        $breakdownTillDate = getEmployeeWorkingDaysBreakdown($db, $empId, $empData['joining_date'] ?? null, $month, 'till_date');
        $breakdownFullMonth = getEmployeeWorkingDaysBreakdown($db, $empId, $empData['joining_date'] ?? null, $month, 'full_month');

        $daysTillToday = $breakdownTillDate['active_days_worked'];
        $pausedDaysTillToday = $breakdownTillDate['paused_days'];
        $tillDateEnd = $breakdownTillDate['period_end'];
        $periodStart = $breakdownTillDate['period_start'];

        $daysWorkedFullMonth = $breakdownFullMonth['active_days_worked'];
        $pausedDaysFullMonth = $breakdownFullMonth['paused_days'];
        $fullMonthEnd = $breakdownFullMonth['period_end'];

        if (empty($calcMode)) {
            $calcMode = $isCurrentMonth ? 'till_date' : 'full_month';
        }

        // Account for pay pause status
        $isPayPaused = ($empData['pay_status'] === 'paused');
        $payPauseDate = $empData['pay_pause_date'] ?? null;
        
        // Calculations for Till Today
        $earnedBaseTillToday = round($dailyWage * $daysTillToday, 2);
        $grossTillToday = round($earnedBaseTillToday + $extraTasks + $incentives, 2);
        
        // Calculations for Full Month
        $earnedBaseFullMonth = round($dailyWage * $daysWorkedFullMonth, 2);
        $grossFullMonth = round($earnedBaseFullMonth + $extraTasks + $incentives, 2);
        
        // Common Deductions including Absences
        $totalDeductions = round($advances + $dailyPayments + $finesDeducted + $totalAbsentDeduction + max(0.00, $priorUnsettled), 2);
        
        // Net position for Till Today
        $netTillToday = round($grossTillToday - $totalDeductions, 2);
        $remainingPayableTillToday = max(0.00, $netTillToday);
        $advanceExceededTillToday = ($totalDeductions > $grossTillToday);
        $deficitAmountTillToday = $advanceExceededTillToday ? round($totalDeductions - $grossTillToday, 2) : 0.00;
        
        // Net position for Full Month
        $netFullMonth = round($grossFullMonth - $totalDeductions, 2);
        $remainingPayableFullMonth = max(0.00, $netFullMonth);
        $advanceExceededFullMonth = ($totalDeductions > $grossFullMonth);
        $deficitAmountFullMonth = $advanceExceededFullMonth ? round($totalDeductions - $grossFullMonth, 2) : 0.00;
        
        // Active selected mode values (for direct UI binding & backward compatibility)
        $activeDaysWorked = ($calcMode === 'till_date') ? $daysTillToday : $daysWorkedFullMonth;
        $activePausedDays = ($calcMode === 'till_date') ? $pausedDaysTillToday : $pausedDaysFullMonth;
        $activeEarnedBase = ($calcMode === 'till_date') ? $earnedBaseTillToday : $earnedBaseFullMonth;
        $activeGross = ($calcMode === 'till_date') ? $grossTillToday : $grossFullMonth;
        $activeRemainingPayable = ($calcMode === 'till_date') ? $remainingPayableTillToday : $remainingPayableFullMonth;
        $activeNetPosition = ($calcMode === 'till_date') ? $netTillToday : $netFullMonth;
        $activeAdvanceExceeded = ($calcMode === 'till_date') ? $advanceExceededTillToday : $advanceExceededFullMonth;
        $activeDeficitAmount = ($calcMode === 'till_date') ? $deficitAmountTillToday : $deficitAmountFullMonth;
        $activePeriodEnd = ($calcMode === 'till_date') ? $tillDateEnd : $fullMonthEnd;

        $pauseEffectiveText = '';
        if ($activePausedDays > 0) {
            $pauseEffectiveText = "Pay paused for {$activePausedDays} day" . ($activePausedDays > 1 ? 's' : '') . " in this period ({$activeDaysWorked} active working days accrued).";
        } else if ($isPayPaused && !empty($payPauseDate)) {
            $pauseEffectiveText = "Pay is currently paused effective " . date('d M Y', strtotime($payPauseDate));
        }

        // Enrich employee data object for UI convenience
        $empData['monthly_base_salary'] = round($baseSalary, 2);
        $empData['current_salary'] = round($baseSalary, 2);
        $empData['salary'] = round($baseSalary, 2);
        $empData['daily_wage'] = round($dailyWage, 2);
        
        // Check if already settled
        $chkStmt = $db->prepare("
            SELECT es.*, u.username as settled_by_name 
            FROM employee_settlements es
            LEFT JOIN users u ON es.settled_by = u.id
            WHERE es.employee_id = ? AND es.month_year = ?
        ");
        $chkStmt->execute([$empId, $month]);
        $settlementRecord = $chkStmt->fetch();
        $alreadySettled = !empty($settlementRecord);
        
        sendJSON([
            'success' => true,
            'month' => $month,
            'employee' => $empData,
            'calc_mode' => $calcMode,
            'is_current_month' => $isCurrentMonth,
            'is_past_month' => $isPastMonth,
            'today_date' => $todayDate,
            'period_start' => $periodStart,
            'period_end' => $activePeriodEnd,
            'period_end_till_date' => $tillDateEnd,
            'period_end_full_month' => $fullMonthEnd,
            'total_days_in_month' => $totalDaysInMonth,
            'salary_basis_days' => $salaryBasisDays,
            'days_worked' => $activeDaysWorked,
            'days_till_today' => $daysTillToday,
            'days_full_month' => $daysWorkedFullMonth,
            'paused_days' => $activePausedDays,
            'paused_days_till_today' => $pausedDaysTillToday,
            'paused_days_full_month' => $pausedDaysFullMonth,
            'daily_wage' => $dailyWage,
            'monthly_base_salary' => round($baseSalary, 2),
            'base_salary' => round($baseSalary, 2),
            'earned_base_salary' => round($activeEarnedBase, 2),
            'earned_base_till_today' => round($earnedBaseTillToday, 2),
            'earned_base_full_month' => round($earnedBaseFullMonth, 2),
            'extra_tasks' => round($extraTasks, 2),
            'incentives' => round($incentives, 2),
            'advances' => round($advances, 2),
            'daily_payments' => round($dailyPayments, 2),
            'fines_deducted' => round($finesDeducted, 2),
            'prior_unsettled' => round($priorUnsettled, 2),
            'gross_earnings' => round($activeGross, 2),
            'gross_earnings_till_today' => round($grossTillToday, 2),
            'gross_earnings_full_month' => round($grossFullMonth, 2),
            'total_deductions' => round($totalDeductions, 2),
            'remaining_payable' => round($activeRemainingPayable, 2),
            'remaining_payable_till_today' => round($remainingPayableTillToday, 2),
            'remaining_payable_full_month' => round($remainingPayableFullMonth, 2),
            'net_position' => round($activeNetPosition, 2),
            'net_position_till_today' => round($netTillToday, 2),
            'net_position_full_month' => round($netFullMonth, 2),
            'advance_exceeded' => $activeAdvanceExceeded,
            'advance_exceeded_till_today' => $advanceExceededTillToday,
            'advance_exceeded_full_month' => $advanceExceededFullMonth,
            'advance_exceeded_amount' => $activeDeficitAmount,
            'advance_exceeded_amount_till_today' => $deficitAmountTillToday,
            'advance_exceeded_amount_full_month' => $deficitAmountFullMonth,
            'already_settled' => $alreadySettled,
            'settlement_record' => $settlementRecord,
            'tasks_list' => $tasksList,
            'incentives_list' => $incentivesList,
            'advances_list' => $advancesList,
            'daily_payments_list' => $dailyPaymentsList,
            'fines_list' => $finesList,
            'absences_list' => $absencesList,
            'absences_count' => count($absencesList),
            'total_absent_deductions' => round($totalAbsentDeduction, 2),
            'is_pay_paused' => $isPayPaused,
            'pay_pause_date' => $payPauseDate,
            'pause_effective_text' => $pauseEffectiveText
        ]);
    }
}

else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePermission('MANAGE_EMPLOYEES');
    
    if ($action === 'add') {
        $name = trim(sanitize($_POST['name'] ?? ''));
        $contact = trim(sanitize($_POST['contact'] ?? ''));
        $designation = trim(sanitize($_POST['designation'] ?? ''));
        $joining_date = sanitize($_POST['joining_date'] ?? date('Y-m-d'));
        $salary = max(0.00, (float)($_POST['salary'] ?? 0));
        $notes = trim(sanitize($_POST['notes'] ?? ''));
        
        if (empty($name) || empty($designation)) {
            sendJSON(['success' => false, 'message' => 'Full Name and Role / Job are required.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO employees (name, contact, designation, joining_date, notes, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$name, $contact, $designation, $joining_date, $notes]);
            $empId = (int)$db->lastInsertId();
            
            // Log initial salary rate
            $salStmt = $db->prepare("
                INSERT INTO employee_salary_history (employee_id, salary_amount, effective_from)
                VALUES (?, ?, ?)
            ");
            $salStmt->execute([$empId, $salary, $joining_date]);
            
            logAudit('employee_create', 'employees', $empId, null, [
                'name' => $name,
                'designation' => $designation,
                'salary' => $salary
            ], 'Registered employee profile.');
            
            $db->commit();
            sendJSON(['success' => true, 'message' => 'Employee registered successfully.', 'id' => $empId]);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => 'Failed to create employee: ' . $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim(sanitize($_POST['name'] ?? ''));
        $contact = trim(sanitize($_POST['contact'] ?? ''));
        $designation = trim(sanitize($_POST['designation'] ?? ''));
        $joining_date = sanitize($_POST['joining_date'] ?? date('Y-m-d'));
        $salary = max(0.00, (float)($_POST['salary'] ?? 0));
        $status = sanitize($_POST['status'] ?? 'active');
        $notes = trim(sanitize($_POST['notes'] ?? ''));
        
        if ($id <= 0 || empty($name) || empty($designation)) {
            sendJSON(['success' => false, 'message' => 'Full Name and Role / Job are required.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            $currStmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
            $currStmt->execute([$id]);
            $current = $currStmt->fetch();
            
            if (!$current) {
                throw new Exception("Employee profile not found.");
            }
            
            $stmt = $db->prepare("
                UPDATE employees 
                SET name = ?, contact = ?, designation = ?, joining_date = ?, notes = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $contact, $designation, $joining_date, $notes, $status, $id]);
            
            // Check if salary rate changed from current active rate
            $salStmt = $db->prepare("SELECT salary_amount FROM employee_salary_history WHERE employee_id = ? ORDER BY effective_from DESC, id DESC LIMIT 1");
            $salStmt->execute([$id]);
            $currentSalary = (float)$salStmt->fetchColumn();
            
            if (abs($currentSalary - $salary) > 0.01) {
                $insSal = $db->prepare("
                    INSERT INTO employee_salary_history (employee_id, salary_amount, effective_from)
                    VALUES (?, ?, CURDATE())
                ");
                $insSal->execute([$id, $salary]);
            }
            
            logAudit('employee_update', 'employees', $id, $current, [
                'name' => $name,
                'designation' => $designation,
                'salary' => $salary,
                'status' => $status
            ], 'Employee profile updated.');
            
            $db->commit();
            sendJSON(['success' => true, 'message' => 'Employee profile updated successfully.']);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'pause_pay') {
        $empId = (int)($_POST['employee_id'] ?? 0);
        $pauseDate = sanitize($_POST['pause_date'] ?? date('Y-m-d'));
        $reason = trim(sanitize($_POST['reason'] ?? 'Pay paused'));
        
        if ($empId <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid employee ID.'], 400);
        }
        
        try {
            $db->beginTransaction();
            $emp = $db->prepare("SELECT * FROM employees WHERE id = ? FOR UPDATE");
            $emp->execute([$empId]);
            $empData = $emp->fetch();
            if (!$empData) {
                throw new Exception("Employee profile not found.");
            }
            if ($empData['pay_status'] === 'paused') {
                throw new Exception("Pay is already paused for this employee.");
            }
            
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
            
            // Update employee
            $upd = $db->prepare("UPDATE employees SET pay_status = 'paused', pay_pause_date = ?, pay_pause_reason = ? WHERE id = ?");
            $upd->execute([$pauseDate, $reason, $empId]);
            
            // Insert log in employee_pay_pauses
            $ins = $db->prepare("INSERT INTO employee_pay_pauses (employee_id, pause_date, reason, status, created_by) VALUES (?, ?, ?, 'paused', ?)");
            $ins->execute([$empId, $pauseDate, $reason, $userId]);
            
            logAudit('employee_pay_pause', 'employees', $empId, $empData, [
                'pay_status' => 'paused',
                'pay_pause_date' => $pauseDate,
                'pay_pause_reason' => $reason
            ], "Paused employee pay effective $pauseDate: $reason");
            
            $db->commit();
            sendJSON(['success' => true, 'message' => "Pay paused successfully effective " . date('d M Y', strtotime($pauseDate)) . "."]);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'resume_pay') {
        $empId = (int)($_POST['employee_id'] ?? 0);
        $resumeDate = sanitize($_POST['resume_date'] ?? date('Y-m-d'));
        $notes = trim(sanitize($_POST['notes'] ?? 'Pay resumed'));
        
        if ($empId <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid employee ID.'], 400);
        }
        
        try {
            $db->beginTransaction();
            $emp = $db->prepare("SELECT * FROM employees WHERE id = ? FOR UPDATE");
            $emp->execute([$empId]);
            $empData = $emp->fetch();
            if (!$empData) {
                throw new Exception("Employee profile not found.");
            }
            if ($empData['pay_status'] !== 'paused') {
                throw new Exception("Employee pay is not currently paused.");
            }
            
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
            
            // Update latest paused record in employee_pay_pauses
            $updPause = $db->prepare("UPDATE employee_pay_pauses SET resume_date = ?, status = 'resumed' WHERE employee_id = ? AND status = 'paused' ORDER BY id DESC LIMIT 1");
            $updPause->execute([$resumeDate, $empId]);
            
            // Update employee record
            $upd = $db->prepare("UPDATE employees SET pay_status = 'active', pay_pause_date = NULL, pay_pause_reason = NULL WHERE id = ?");
            $upd->execute([$empId]);
            
            logAudit('employee_pay_resume', 'employees', $empId, $empData, [
                'pay_status' => 'active',
                'pay_resume_date' => $resumeDate
            ], "Resumed employee pay effective $resumeDate: $notes");
            
            $db->commit();
            sendJSON(['success' => true, 'message' => "Pay resumed successfully effective " . date('d M Y', strtotime($resumeDate)) . "."]);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'add_transaction') {
        $empId = (int)($_POST['employee_id'] ?? 0);
        $type = sanitize($_POST['type'] ?? 'advance'); // 'advance', 'daily_payment', 'incentive', 'deduction'
        $amount = (float)($_POST['amount'] ?? 0);
        $description = trim(sanitize($_POST['description'] ?? ''));
        
        $validTypes = ['advance', 'daily_payment', 'incentive', 'deduction'];
        if ($empId <= 0 || !in_array($type, $validTypes) || $amount <= 0) {
            sendJSON(['success' => false, 'message' => 'Please enter a valid amount and employee.'], 400);
        }
        
        if (empty($description)) {
            switch ($type) {
                case 'advance': $description = 'Cash Advance (Naqad Peshi)'; break;
                case 'daily_payment': $description = 'Daily Cash (Rozana Kharcha)'; break;
                case 'deduction': $description = 'Fine / Absence Deduction (Chuti/Jurmana)'; break;
                case 'incentive': $description = 'Incentive Bonus (Inaam)'; break;
            }
        }
        
        try {
            $txDate = !empty($_POST['transaction_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['transaction_date'])
                ? sanitize($_POST['transaction_date'])
                : date('Y-m-d');
            $createdAt = $txDate . ' ' . date('H:i:s');
            
            $stmt = $db->prepare("
                INSERT INTO employee_transactions (employee_id, type, amount, description, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
            $stmt->execute([$empId, $type, $amount, $description, $userId, $createdAt]);
            $txId = (int)$db->lastInsertId();
            
            logAudit('employee_tx_create', 'employee_transactions', $txId, null, [
                'employee_id' => $empId,
                'type' => $type,
                'amount' => $amount
            ], "Recorded employee ledger entry: $type (Rs. $amount).");
            
            sendJSON(['success' => true, 'message' => 'Transaction recorded in khata successfully.']);
        } catch (Exception $e) {
            sendJSON(['success' => false, 'message' => 'Failed to record entry: ' . $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'void_transaction' || $action === 'delete_transaction') {
        $txId = (int)($_POST['id'] ?? $_POST['transaction_id'] ?? 0);
        $voidReason = trim(sanitize($_POST['void_reason'] ?? $_POST['reason'] ?? 'Entry error / Ghalti se add ho gaya'));
        if (empty($voidReason)) $voidReason = 'Entry error / Ghalti se add ho gaya';
        
        if ($txId <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid transaction ID.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("SELECT * FROM employee_transactions WHERE id = ? FOR UPDATE");
            $stmt->execute([$txId]);
            $tx = $stmt->fetch();
            
            if (!$tx) {
                throw new Exception("Transaction entry not found.");
            }
            
            if ($tx['status'] === 'voided') {
                throw new Exception("This transaction is already voided.");
            }
            
            if ($tx['type'] === 'salary_settlement' || $tx['reference_type'] === 'employee_settlements') {
                throw new Exception("Cannot void a closed settlement payout directly. Please manage settlements through the payroll clearance tab.");
            }
            
            // If linked to an employee task, reset task to pending
            if ($tx['reference_type'] === 'employee_tasks' && !empty($tx['reference_id'])) {
                $db->prepare("UPDATE employee_tasks SET status = 'pending', completed_at = NULL WHERE id = ?")->execute([$tx['reference_id']]);
            }
            
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
            
            // Soft-void transaction
            $uStmt = $db->prepare("
                UPDATE employee_transactions 
                SET status = 'voided', void_reason = ?, voided_at = CURRENT_TIMESTAMP, voided_by = ? 
                WHERE id = ?
            ");
            $uStmt->execute([$voidReason, $userId, $txId]);
            
            logAudit('employee_tx_void', 'employee_transactions', $txId, $tx, ['status' => 'voided', 'reason' => $voidReason], "Voided transaction of type {$tx['type']} (Rs. {$tx['amount']}): $voidReason");
            
            $db->commit();
            sendJSON(['success' => true, 'message' => 'Transaction voided successfully. Ledger balance recalculated.']);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'mark_absence') {
        $empId = (int)($_POST['employee_id'] ?? 0);
        $absenceDate = sanitize($_POST['absence_date'] ?? date('Y-m-d'));
        $deductionAmount = isset($_POST['deduction_amount']) && $_POST['deduction_amount'] !== '' ? (float)$_POST['deduction_amount'] : null;
        $reason = trim(sanitize($_POST['reason'] ?? 'Unannounced Absence / Ghair Hazri'));
        if (empty($reason)) $reason = 'Unannounced Absence / Ghair Hazri';

        if ($empId <= 0 || empty($absenceDate)) {
            sendJSON(['success' => false, 'message' => 'Employee and date of absence are required.'], 400);
        }

        try {
            $db->beginTransaction();
            
            $empStmt = $db->prepare("SELECT id, name, salary FROM employees WHERE id = ?");
            $empStmt->execute([$empId]);
            $emp = $empStmt->fetch();
            if (!$emp) {
                throw new Exception("Employee profile not found.");
            }

            // Calculate daily rate if not specified
            if ($deductionAmount === null || $deductionAmount < 0) {
                $salStmt = $db->prepare("SELECT salary_amount FROM employee_salary_history WHERE employee_id = ? ORDER BY effective_from DESC, id DESC LIMIT 1");
                $salStmt->execute([$empId]);
                $baseSal = (float)($salStmt->fetchColumn() ?: $emp['salary']);
                $deductionAmount = round($baseSal / 30, 2);
            }

            // Prevent duplicate active absence on same date
            $chkStmt = $db->prepare("SELECT id FROM employee_absences WHERE employee_id = ? AND absence_date = ? AND status = 'active'");
            $chkStmt->execute([$empId, $absenceDate]);
            if ($chkStmt->fetch()) {
                throw new Exception("Absence (chuti) is already recorded for {$emp['name']} on " . date('d M Y', strtotime($absenceDate)) . ".");
            }

            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
            $insStmt = $db->prepare("
                INSERT INTO employee_absences (employee_id, absence_date, deduction_amount, reason, status, created_by)
                VALUES (?, ?, ?, ?, 'active', ?)
            ");
            $insStmt->execute([$empId, $absenceDate, $deductionAmount, $reason, $userId]);
            $absId = (int)$db->lastInsertId();

            logAudit('employee_absence_mark', 'employee_absences', $absId, null, [
                'employee_id' => $empId,
                'absence_date' => $absenceDate,
                'deduction_amount' => $deductionAmount
            ], "Marked absence for {$emp['name']} on $absenceDate: $reason (-Rs. $deductionAmount)");

            $db->commit();
            sendJSON([
                'success' => true, 
                'message' => "Absence (chuti) marked for {$emp['name']} on " . date('d M Y', strtotime($absenceDate)) . ". Rs. " . number_format($deductionAmount, 2) . " will be deducted.",
                'absence_id' => $absId
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'void_absence') {
        $absId = (int)($_POST['id'] ?? $_POST['absence_id'] ?? 0);
        $voidReason = trim(sanitize($_POST['void_reason'] ?? 'Marked by mistake'));
        if (empty($voidReason)) $voidReason = 'Marked by mistake';

        if ($absId <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid absence record ID.'], 400);
        }

        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("SELECT * FROM employee_absences WHERE id = ? FOR UPDATE");
            $stmt->execute([$absId]);
            $abs = $stmt->fetch();
            if (!$abs) {
                throw new Exception("Absence record not found.");
            }
            if ($abs['status'] === 'voided') {
                throw new Exception("This absence record is already voided.");
            }

            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
            $upd = $db->prepare("
                UPDATE employee_absences 
                SET status = 'voided', void_reason = ?, voided_at = CURRENT_TIMESTAMP, voided_by = ? 
                WHERE id = ?
            ");
            $upd->execute([$voidReason, $userId, $absId]);

            logAudit('employee_absence_void', 'employee_absences', $absId, $abs, ['status' => 'voided', 'reason' => $voidReason], "Voided absence record #$absId: $voidReason");

            $db->commit();
            sendJSON(['success' => true, 'message' => 'Absence record voided successfully. Salary deduction restored.']);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'add_task') {
        $empId = (int)($_POST['employee_id'] ?? 0);
        $desc = trim(sanitize($_POST['description'] ?? ''));
        if (empty($desc)) {
            $desc = 'Extra Duty / Task';
        }
        $comp = max(0.00, (float)($_POST['compensation'] ?? 0));
        
        if ($empId <= 0 || $comp <= 0) {
            sendJSON(['success' => false, 'message' => 'Positive compensation amount is required.'], 400);
        }
        
        try {
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
            $stmt = $db->prepare("
                INSERT INTO employee_tasks (employee_id, description, compensation_amount, status, created_by)
                VALUES (?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([$empId, $desc, $comp, $userId]);
            $taskId = (int)$db->lastInsertId();
            
            logAudit('employee_task_create', 'employee_tasks', $taskId, null, [
                'employee_id' => $empId,
                'compensation' => $comp
            ], 'Assigned task to employee.');
            
            sendJSON(['success' => true, 'message' => 'Task assigned successfully.']);
        } catch (Exception $e) {
            sendJSON(['success' => false, 'message' => 'Failed to assign task.'], 500);
        }
    }
    
    else if ($action === 'complete_task') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        if ($taskId <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid task ID.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("SELECT * FROM employee_tasks WHERE id = ? FOR UPDATE");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();
            
            if (!$task) {
                throw new Exception("Task not found.");
            }
            if ($task['status'] === 'completed') {
                throw new Exception("Task already completed.");
            }
            
            $db->prepare("UPDATE employee_tasks SET status = 'completed', completed_at = CURDATE() WHERE id = ?")->execute([$taskId]);
            
            // Post transaction to employee ledger as extra_task
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
            $insTx = $db->prepare("
                INSERT INTO employee_transactions (employee_id, type, amount, description, reference_type, reference_id, created_by)
                VALUES (?, 'extra_task', ?, ?, 'employee_tasks', ?, ?)
            ");
            $insTx->execute([
                $task['employee_id'],
                $task['compensation_amount'],
                "Earned for completed task: " . $task['description'],
                $taskId,
                $userId
            ]);
            
            logAudit('employee_task_complete', 'employee_tasks', $taskId, $task, ['status' => 'completed'], 'Task marked as completed.');
            
            $db->commit();
            sendJSON(['success' => true, 'message' => 'Task marked as completed & compensation added to ledger.']);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'save_settlement') {
        $empId = (int)($_POST['employee_id'] ?? 0);
        $month = sanitize($_POST['month'] ?? '');
        $paymentMethod = sanitize($_POST['payment_method'] ?? 'cash'); // 'cash' or 'bank'
        $paymentSource = sanitize($_POST['payment_source'] ?? ($paymentMethod === 'bank' ? 'bank_transfer' : 'drawer_cash'));
        $paymentDate = sanitize($_POST['payment_date'] ?? date('Y-m-d'));
        $notes = trim(sanitize($_POST['notes'] ?? ''));
        
        if ($empId <= 0 || empty($month)) {
            sendJSON(['success' => false, 'message' => 'Missing employee details or month.'], 400);
        }
        
        try {
            $db->beginTransaction();
            
            // Prevent duplicate settlement
            $chk = $db->prepare("SELECT COUNT(*) FROM employee_settlements WHERE employee_id = ? AND month_year = ?");
            $chk->execute([$empId, $month]);
            if ($chk->fetchColumn() > 0) {
                throw new Exception("This employee salary is already settled and closed for month $month.");
            }
            
            // Compute values server-side
            $lastDayOfMonth = date("Y-m-t", strtotime($month . "-01"));
            
            $salStmt = $db->prepare("
                SELECT salary_amount FROM employee_salary_history 
                WHERE employee_id = ? AND effective_from <= ? 
                ORDER BY effective_from DESC, id DESC LIMIT 1
            ");
            $salStmt->execute([$empId, $lastDayOfMonth]);
            $baseSalary = $salStmt->fetchColumn();
            if ($baseSalary === false) {
                $empStmt = $db->prepare("SELECT salary_amount FROM employee_salary_history WHERE employee_id = ? ORDER BY effective_from ASC, id ASC LIMIT 1");
                $empStmt->execute([$empId]);
                $baseSalary = $empStmt->fetchColumn() ?: 0.00;
            }
            $baseSalary = (float)$baseSalary;
            
            // Tasks
            $taskStmt = $db->prepare("SELECT COALESCE(SUM(compensation_amount), 0) FROM employee_tasks WHERE employee_id = ? AND status = 'completed' AND DATE_FORMAT(completed_at, '%Y-%m') = ?");
            $taskStmt->execute([$empId, $month]);
            $extraTasks = (float)$taskStmt->fetchColumn();
            
            // Incentives
            $incStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM employee_transactions WHERE employee_id = ? AND type = 'incentive' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
            $incStmt->execute([$empId, $month]);
            $incentives = (float)$incStmt->fetchColumn();
            
            // Advances
            $advStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM employee_transactions WHERE employee_id = ? AND type = 'advance' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
            $advStmt->execute([$empId, $month]);
            $advances = (float)$advStmt->fetchColumn();
            
            // Daily Payments
            $payStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM employee_transactions WHERE employee_id = ? AND type = 'daily_payment' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
            $payStmt->execute([$empId, $month]);
            $dailyPayments = (float)$payStmt->fetchColumn();
            
            // Fines / Deductions
            $dedStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM employee_transactions WHERE employee_id = ? AND type = 'deduction' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
            $dedStmt->execute([$empId, $month]);
            $finesDeducted = (float)$dedStmt->fetchColumn();

            // Prior Unsettled Advance Balance
            $priorStmt = $db->prepare("
                SELECT COALESCE(SUM(CASE 
                    WHEN et.type IN ('advance', 'daily_payment', 'deduction') THEN et.amount 
                    WHEN et.type IN ('extra_task', 'incentive') THEN -et.amount 
                    ELSE 0 END), 0)
                FROM employee_transactions et 
                WHERE et.employee_id = ? 
                  AND et.type != 'salary_settlement'
                  AND (et.reference_type IS NULL OR et.reference_type != 'employee_settlements')
                  AND DATE_FORMAT(et.created_at, '%Y-%m') < ?
                  AND DATE_FORMAT(et.created_at, '%Y-%m') NOT IN (
                      SELECT es.month_year FROM employee_settlements es WHERE es.employee_id = ?
                  )
            ");
            $priorStmt->execute([$empId, $month, $empId]);
            $priorUnsettled = (float)$priorStmt->fetchColumn();
            
            // Server-side pro-rata or full month calculation
            $settlementType = sanitize($_POST['settlement_type'] ?? 'till_date'); // 'till_date' or 'full_month'
            $currentYm = date('Y-m');
            $todayDate = date('Y-m-d');
            $isCurrentMonth = ($month === $currentYm);
            $totalDaysInMonth = (int)date('t', strtotime($month . "-01"));
            $salaryBasisDays = 30;
            $dailyWage = round($baseSalary / $salaryBasisDays, 2);

            $periodStart = $month . "-01";
            $empCheck = $db->prepare("SELECT joining_date FROM employees WHERE id = ?");
            $empCheck->execute([$empId]);
            $joiningDate = $empCheck->fetchColumn();
            if (!empty($joiningDate) && $joiningDate > $periodStart && date('Y-m', strtotime($joiningDate)) === $month) {
                $periodStart = $joiningDate;
            }

            if ($settlementType === 'till_date' && $isCurrentMonth) {
                $joinDay = (int)date('j', strtotime($periodStart));
                $currDay = (int)date('j', strtotime($todayDate));
                $daysWorked = max(1, $currDay - $joinDay + 1);
                $daysWorked = min($daysWorked, $totalDaysInMonth);
                $earnedBaseSalary = round($dailyWage * $daysWorked, 2);
                $periodNote = "Till $paymentDate ($daysWorked Days)";
            } else {
                $settlementType = 'full_month';
                $daysWorked = $totalDaysInMonth;
                $earnedBaseSalary = $baseSalary;
                $periodNote = "Full Month ($daysWorked Days)";
            }
            
            $grossEarnings = $earnedBaseSalary + $extraTasks + $incentives;
            $totalDeductions = $advances + $dailyPayments + $finesDeducted + max(0.00, $priorUnsettled);
            $remainingPayable = max(0.00, $grossEarnings - $totalDeductions);
            
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
            
            $insSett = $db->prepare("
                INSERT INTO employee_settlements (employee_id, month_year, base_salary, extra_tasks_amount, incentives_amount, advances_deducted, daily_payments_deducted, fines_deducted, remaining_payable, paid_amount, payment_method, payment_source, settlement_type, days_worked, notes, status, settled_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'closed', ?, ?)
            ");
            $insSett->execute([
                $empId,
                $month,
                $baseSalary,
                $extraTasks,
                $incentives,
                $advances,
                $dailyPayments,
                $finesDeducted,
                $remainingPayable,
                $remainingPayable,
                $paymentMethod,
                $paymentSource,
                $settlementType,
                $daysWorked,
                $notes,
                $userId,
                $paymentDate . ' ' . date('H:i:s')
            ]);
            $settId = (int)$db->lastInsertId();
            
            // Record settlement milestone in transactions
            $methodLabel = ($paymentMethod === 'bank') ? 'Bank Transfer' : 'Cash Drawer';
            $insTx = $db->prepare("
                INSERT INTO employee_transactions (employee_id, type, amount, description, reference_type, reference_id, created_by, created_at)
                VALUES (?, 'salary_settlement', ?, ?, 'employee_settlements', ?, ?, ?)
            ");
            $insTx->execute([
                $empId,
                $remainingPayable,
                "Monthly salary settlement ($periodNote, $methodLabel) - Earned: Rs. " . number_format($grossEarnings, 2) . ", Deductions: Rs. " . number_format($totalDeductions, 2) . ($notes ? " ($notes)" : ""),
                $settId,
                $userId,
                $paymentDate . ' ' . date('H:i:s')
            ]);
            
            logAudit('employee_settlement', 'employee_settlements', $settId, null, [
                'employee_id' => $empId,
                'month_year' => $month,
                'net_paid' => $remainingPayable,
                'payment_method' => $paymentMethod
            ], "Settled and closed payroll for month $month via $methodLabel.");
            
            $db->commit();
            sendJSON(['success' => true, 'message' => "Month $month settled and closed successfully.", 'settlement_id' => $settId]);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    else if ($action === 'void_settlement') {
        $settId = (int)($_POST['settlement_id'] ?? 0);
        $empId = (int)($_POST['employee_id'] ?? 0);
        $month = sanitize($_POST['month'] ?? '');
        
        try {
            $db->beginTransaction();
            
            if ($settId > 0) {
                $stmt = $db->prepare("SELECT * FROM employee_settlements WHERE id = ? FOR UPDATE");
                $stmt->execute([$settId]);
            } else {
                $stmt = $db->prepare("SELECT * FROM employee_settlements WHERE employee_id = ? AND month_year = ? FOR UPDATE");
                $stmt->execute([$empId, $month]);
            }
            $sett = $stmt->fetch();
            if (!$sett) {
                throw new Exception("Settlement record not found.");
            }
            $targetSettId = (int)$sett['id'];
            $targetMonth = $sett['month_year'];
            $targetEmpId = (int)$sett['employee_id'];
            
            // Delete linked settlement transaction from ledger
            $db->prepare("DELETE FROM employee_transactions WHERE reference_type = 'employee_settlements' AND reference_id = ?")->execute([$targetSettId]);
            
            // Delete settlement record
            $db->prepare("DELETE FROM employee_settlements WHERE id = ?")->execute([$targetSettId]);
            
            logAudit('employee_settlement_void', 'employee_settlements', $targetSettId, $sett, null, "Voided settlement for employee #$targetEmpId, month $targetMonth.");
            
            $db->commit();
            sendJSON(['success' => true, 'message' => "Settlement for $targetMonth has been re-opened successfully."]);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    else if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            sendJSON(['success' => false, 'message' => 'Invalid employee ID.'], 400);
        }
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            $emp = $stmt->fetch();
            if (!$emp) {
                throw new Exception("Employee profile not found.");
            }

            // 1. Clear customer khata collection reference if any
            $db->prepare("UPDATE customer_khata_payments SET received_by_employee_id = NULL WHERE received_by_employee_id = ?")->execute([$id]);

            // 2. Delete child records
            $db->prepare("DELETE FROM employee_pay_pauses WHERE employee_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM employee_tasks WHERE employee_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM employee_transactions WHERE employee_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM employee_settlements WHERE employee_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM employee_salary_history WHERE employee_id = ?")->execute([$id]);

            // 3. Delete employee record
            $db->prepare("DELETE FROM employees WHERE id = ?")->execute([$id]);

            $db->commit();
            logAudit('employee_delete', 'employees', $id, $emp, null, "Permanently deleted employee profile {$emp['name']} and all associated records.");
            sendJSON(['success' => true, 'message' => "Employee '{$emp['name']}' deleted successfully."]);
        } catch (Exception $e) {
            $db->rollBack();
            sendJSON(['success' => false, 'message' => 'Failed to delete employee: ' . $e->getMessage()], 500);
        }
    }
}
