<?php
session_start();
require_once 'includes/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get task ID from URL
$task_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($task_id > 0) {
    // Delete the task (only if it belongs to the current user)
    $sql = "DELETE FROM tasks WHERE id = $task_id AND user_id = " . $_SESSION['user_id'];
    
    if ($conn->query($sql)) {
        // Success - redirect to tasks page
        header("Location: my_tasks.php?deleted=1");
        exit();
    } else {
        // Error
        header("Location: my_tasks.php?error=delete_failed");
        exit();
    }
} else {
    // No ID provided
    header("Location: my_tasks.php");
    exit();
}
?>