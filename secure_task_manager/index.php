<?php
session_start();
require_once 'includes/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$is_admin = $_SESSION['is_admin'];
$user_id = $_SESSION['user_id'];

// Get user's recent tasks (last 5)
$tasks_sql = "SELECT * FROM tasks WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5";
$tasks_result = $conn->query($tasks_sql);
$recent_tasks = [];
if ($tasks_result->num_rows > 0) {
    while ($row = $tasks_result->fetch_assoc()) {
        $recent_tasks[] = $row;
    }
}

// Count user's tasks by status
$count_sql = "SELECT status, COUNT(*) as count FROM tasks WHERE user_id = $user_id GROUP BY status";
$count_result = $conn->query($count_sql);
$task_counts = ['todo' => 0, 'in progress' => 0, 'completed' => 0];
if ($count_result->num_rows > 0) {
    while ($row = $count_result->fetch_assoc()) {
        $task_counts[$row['status']] = $row['count'];
    }
}

$total_tasks = array_sum($task_counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        .header { background: #333; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #45a049; }
        .btn-danger { background: #f44336; }
        .btn-danger:hover { background: #da190b; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-primary { background: #2196F3; }
        .btn-primary:hover { background: #0b7dda; }
        .task-list { margin-top: 20px; }
        .task { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .admin-badge { background: #ff9800; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; margin-left: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-number { font-size: 36px; font-weight: bold; margin: 10px 0; }
        .stat-todo { border-top: 4px solid #ff9800; }
        .stat-progress { border-top: 4px solid #2196F3; }
        .stat-completed { border-top: 4px solid #4CAF50; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; margin-left: 10px; }
        .badge-todo { background: #fff3e0; color: #e65100; }
        .badge-progress { background: #e3f2fd; color: #1565c0; }
        .badge-completed { background: #e8f5e9; color: #2e7d32; }
        .empty-state { text-align: center; padding: 30px; color: #666; }
        .empty-state h3 { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Task Manager Dashboard</h1>
        <div>
            <span>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></span>
            <?php if ($is_admin): ?>
                <span class="admin-badge">ADMIN</span>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-danger" style="margin-left: 20px;">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Task Statistics -->
        <div class="stats-grid">
            <div class="stat-card stat-todo">
                <h3>To Do</h3>
                <div class="stat-number"><?php echo $task_counts['todo']; ?></div>
                <p>Tasks pending</p>
            </div>
            <div class="stat-card stat-progress">
                <h3>In Progress</h3>
                <div class="stat-number"><?php echo $task_counts['in progress']; ?></div>
                <p>Active tasks</p>
            </div>
            <div class="stat-card stat-completed">
                <h3>Completed</h3>
                <div class="stat-number"><?php echo $task_counts['completed']; ?></div>
                <p>Finished tasks</p>
            </div>
        </div>
        
        <div class="card">
            <h2>Welcome to Your Dashboard!</h2>
            <p>You are successfully logged in as <strong><?php echo htmlspecialchars($username); ?></strong>.</p>
            
            <div style="margin: 20px 0; padding: 15px; background: #e8f5e9; border-radius: 5px;">
                <h3>📊 Your Task Summary:</h3>
                <p>Total Tasks: <strong><?php echo $total_tasks; ?></strong></p>
                <p>To Do: <?php echo $task_counts['todo']; ?> | In Progress: <?php echo $task_counts['in progress']; ?> | Completed: <?php echo $task_counts['completed']; ?></p>
            </div>
            
            <?php if ($is_admin): ?>
                <h3>👑 Admin Features:</h3>
                <a href="admin/dashboard.php" class="btn btn-primary">Admin Panel</a>
                <a href="admin/audit_log.php" class="btn btn-primary">View Audit Log</a>
                <a href="admin/manage_users.php" class="btn btn-primary">Manage Users</a>
            <?php else: ?>
                <h3>👤 User Features:</h3>
                <a href="add_task.php" class="btn btn-primary">➕ Add New Task</a>
                <a href="my_tasks.php" class="btn btn-primary">📋 View My Tasks</a>
                <a href="profile.php" class="btn btn-primary">👤 My Profile</a>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>📝 Recent Tasks (Last 5)</h3>
            
            <?php if (count($recent_tasks) == 0): ?>
                <div class="empty-state">
                    <h3>No tasks yet! 🎉</h3>
                    <p>You don't have any tasks. Create your first task to get started.</p>
                    <a href="add_task.php" class="btn btn-primary" style="margin-top: 15px;">Create Your First Task</a>
                </div>
            <?php else: ?>
                <div class="task-list">
                    <?php foreach ($recent_tasks as $task): 
                        $status_class = str_replace(' ', '-', $task['status']);
                        $status_badges = [
                            'todo' => ['text' => '📝 To Do', 'class' => 'badge-todo'],
                            'in-progress' => ['text' => '🔄 In Progress', 'class' => 'badge-progress'],
                            'completed' => ['text' => '✅ Completed', 'class' => 'badge-completed']
                        ];
                    ?>
                    <div class="task">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                                <?php if (!empty($task['description'])): ?>
                                    <p><?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?><?php echo strlen($task['description']) > 100 ? '...' : ''; ?></p>
                                <?php endif; ?>
                                <small style="color: #666;">Created: <?php echo date('M d, Y', strtotime($task['created_at'])); ?></small>
                            </div>
                            <div>
                                <span class="status-badge <?php echo $status_badges[$status_class]['class']; ?>">
                                    <?php echo $status_badges[$status_class]['text']; ?>
                                </span>
                            </div>
                        </div>
                        <div style="margin-top: 10px; display: flex; gap: 10px;">
                            <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 14px;">Edit</a>
                            <a href="my_tasks.php" class="btn btn-secondary" style="padding: 5px 10px; font-size: 14px;">View All</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="my_tasks.php" class="btn btn-primary">View All Tasks (<?php echo $total_tasks; ?>)</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>📈 Quick Stats</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>User ID:</strong> <?php echo $user_id; ?>
                </div>
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Account Type:</strong> <?php echo $is_admin ? '👑 Administrator' : '👤 Regular User'; ?>
                </div>
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Session Started:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                </div>
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Total Tasks:</strong> <?php echo $total_tasks; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div style="text-align: center; margin: 30px; color: #666;">
        <p>Secure Software Development Project | Task Management System</p>
        <p><small>Note: Security features will be implemented by security team</small></p>
    </div>
</body>
</html>