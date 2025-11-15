<?php
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'recruiter') {
    header('Location: login.php');
    exit;
}

if (isset($_POST['application_id'], $_POST['status'])) {
    $application_id = $_POST['application_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $application_id]);

    header("Location: dashboard.php");
    exit;
}
?>