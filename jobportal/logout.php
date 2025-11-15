<?php
require_once 'includes/db_connect.php';

// Destroy all session data
session_destroy();

// Redirect to home page with success message
header('Location: index.php');
exit;
?>