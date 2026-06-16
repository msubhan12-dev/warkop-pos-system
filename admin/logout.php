<?php
require_once '../config/config.php';

if (isset($_SESSION['user_id'])) {
    createAuditLog('logout', 'users', $_SESSION['user_id']);
}

session_unset();
session_destroy();

header('Location: ../index.php?logout=1');
exit;
