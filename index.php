<?php
require_once __DIR__ . '/config/helper.php';
require_once __DIR__ . '/config/auth.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

if (!isAdmin()) {
    redirect('/pos/index.php');
}

redirect('/dashboard/index.php');

