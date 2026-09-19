<?php
// Unified Employee Hub - Redirects to Ledger Tab
require_once __DIR__ . '/../config/helper.php';
$empId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$query = "?tab=ledger" . ($empId > 0 ? "&employee_id=$empId" : "");
redirect('/employees/index.php' . $query);
