<?php
require_once '../config/config.php';
requireRole(['owner']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    
    $description = trim($_POST['description'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $created_by = $_SESSION['user_id'];
    
    if (empty($description) || $amount <= 0 || empty($expense_date)) {
        die("Invalid input. Please check your data.");
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO expenses (description, amount, expense_date, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$description, $amount, $expense_date, $created_by]);
        
        header('Location: reports.php?success=1');
        exit;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    header('Location: reports.php');
    exit;
}
