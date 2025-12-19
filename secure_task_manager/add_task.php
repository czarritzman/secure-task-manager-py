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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    
    if (empty($title)) {
        $error = "Task title is required!";
    } else {
        // Insert task into database
        $sql = "INSERT INTO tasks (title, description, status, user_id) 
                VALUES ('" . $conn->real_escape_string($title) . "', 
                        '" . $conn->real_escape_string($description) . "', 
                        '" . $conn->real_escape_string($status) . "', 
                        " . $_SESSION['user_id'] . ")";
        
        if ($conn->query($sql)) {
            $success = "Task added successfully!";
            // Clear form
            $title = $description = '';
        } else {
            $error = "Error adding task: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Task - Task Manager</title>
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
        textarea { min-height: 100px; resize: vertical; }
        .btn { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #45a049; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .error { background: #ffebee; color: #c62828; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>➕ Add New Task</h1>
            <div>
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Create New Task</h2>
            <p>Fill in the details below to create a new task.</p>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Task Title *</label>
                    <input type="text" id="title" name="title" 
                           value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>"
                           placeholder="What needs to be done?" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Add details about this task..."><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="todo">📝 To Do</option>
                        <option value="in progress">🔄 In Progress</option>
                        <option value="completed">✅ Completed</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">➕ Create Task</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
            
            <div style="margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
                <h3>ℹ️ About Tasks</h3>
                <p><strong>To Do:</strong> Tasks that haven't been started yet</p>
                <p><strong>In Progress:</strong> Tasks you're currently working on</p>
                <p><strong>Completed:</strong> Finished tasks</p>
                <p><em>You can change the status later when editing tasks.</em></p>
            </div>
        </div>
    </div>
</body>
</html>