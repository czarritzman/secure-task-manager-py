<?php
session_start();
require_once 'includes/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$task = null;

// Get task ID from URL
$task_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch task details
if ($task_id > 0) {
    $sql = "SELECT * FROM tasks WHERE id = $task_id AND user_id = " . $_SESSION['user_id'];
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $task = $result->fetch_assoc();
    } else {
        $error = "Task not found or you don't have permission to edit it!";
    }
} else {
    $error = "No task specified!";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    
    if (empty($title)) {
        $error = "Task title is required!";
    } else {
        // Update task in database
        $update_sql = "UPDATE tasks SET 
                      title = '" . $conn->real_escape_string($title) . "',
                      description = '" . $conn->real_escape_string($description) . "',
                      status = '" . $conn->real_escape_string($status) . "'
                      WHERE id = $task_id AND user_id = " . $_SESSION['user_id'];
        
        if ($conn->query($update_sql)) {
            $success = "Task updated successfully!";
            // Refresh task data
            $task['title'] = $title;
            $task['description'] = $description;
            $task['status'] = $status;
        } else {
            $error = "Error updating task: " . $conn->error;
        }
    }
}

// If no task found, redirect
if (!$task && !$error) {
    header("Location: my_tasks.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - Task Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        .header { background: #333; color: white; padding: 20px; }
        .header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        textarea { min-height: 120px; resize: vertical; }
        .btn { display: inline-block; padding: 12px 24px; text-decoration: none; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn-primary { background: #2196F3; color: white; }
        .btn-success { background: #4CAF50; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.9; }
        .error { background: #ffebee; color: #c62828; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .task-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info-row { display: flex; margin-bottom: 8px; }
        .info-label { font-weight: bold; width: 120px; color: #666; }
        .info-value { color: #333; }
        .status-indicator { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 14px; margin-left: 10px; }
        .status-todo { background: #fff3e0; color: #e65100; }
        .status-in-progress { background: #e3f2fd; color: #1565c0; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>✏️ Edit Task</h1>
            <div>
                <a href="my_tasks.php" class="btn btn-secondary">← Back to My Tasks</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <?php if ($error && !$task): ?>
                <div class="error">❌ <?php echo $error; ?></div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="my_tasks.php" class="btn btn-primary">Back to My Tasks</a>
                </div>
            <?php else: ?>
                <h2>Edit Task #<?php echo $task['id']; ?></h2>
                
                <!-- Task Information -->
                <div class="task-info">
                    <div class="info-row">
                        <div class="info-label">Created:</div>
                        <div class="info-value"><?php echo date('F d, Y H:i', strtotime($task['created_at'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Last Updated:</div>
                        <div class="info-value"><?php echo date('F d, Y H:i', strtotime($task['updated_at'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Current Status:</div>
                        <div class="info-value">
                            <?php 
                            $status_text = ucwords(str_replace('_', ' ', $task['status']));
                            $status_class = str_replace(' ', '-', $task['status']);
                            echo $status_text; 
                            ?>
                            <span class="status-indicator status-<?php echo $status_class; ?>">
                                <?php 
                                $status_icons = [
                                    'todo' => '📝',
                                    'in-progress' => '🔄',
                                    'completed' => '✅'
                                ];
                                echo $status_icons[$status_class] . ' ' . $status_text;
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="error">❌ <?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success">✅ <?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Edit Form -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Task Title *</label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo htmlspecialchars($task['title']); ?>"
                               placeholder="What needs to be done?" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" 
                                  placeholder="Add details about this task..."><?php echo htmlspecialchars($task['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="todo" <?php echo $task['status'] == 'todo' ? 'selected' : ''; ?>>📝 To Do</option>
                            <option value="in progress" <?php echo $task['status'] == 'in progress' ? 'selected' : ''; ?>>🔄 In Progress</option>
                            <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>✅ Completed</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">💾 Save Changes</button>
                        <a href="my_tasks.php" class="btn btn-secondary">Cancel</a>
                        <a href="delete_task.php?id=<?php echo $task['id']; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('⚠️ Are you sure you want to delete this task?\n\nThis action cannot be undone.')">🗑️ Delete Task</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Quick Navigation -->
        <div class="card" style="margin-top: 20px;">
            <h3>Quick Navigation</h3>
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <a href="my_tasks.php" class="btn btn-secondary">📋 View All Tasks</a>
                <a href="add_task.php" class="btn btn-primary">➕ Add New Task</a>
                <a href="index.php" class="btn btn-secondary">🏠 Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>