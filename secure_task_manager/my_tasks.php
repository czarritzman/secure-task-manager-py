<?php
session_start();
require_once 'includes/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Check for delete success message
$success_message = '';
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $success_message = "Task deleted successfully!";
}

// Get all tasks for this user
$sql = "SELECT * FROM tasks WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($sql);

// Count tasks by status
$status_counts = ['todo' => 0, 'in progress' => 0, 'completed' => 0];
$all_tasks = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $all_tasks[] = $row;
        $status_counts[$row['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - Task Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        .header { background: #333; color: white; padding: 20px; }
        .header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card.todo { border-top: 4px solid #ff9800; }
        .stat-card.in-progress { border-top: 4px solid #2196F3; }
        .stat-card.completed { border-top: 4px solid #4CAF50; }
        .stat-number { font-size: 32px; font-weight: bold; margin: 10px 0; }
        .task-list { display: grid; gap: 15px; }
        .task-item { border-left: 4px solid #ddd; padding: 20px; background: white; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
        .task-item.todo { border-left-color: #ff9800; }
        .task-item.in-progress { border-left-color: #2196F3; }
        .task-item.completed { border-left-color: #4CAF50; opacity: 0.8; }
        .task-info h3 { margin-bottom: 5px; color: #333; }
        .task-info p { color: #666; margin-bottom: 10px; }
        .task-meta { font-size: 12px; color: #888; }
        .task-actions { display: flex; gap: 10px; }
        .btn { display: inline-block; padding: 8px 16px; text-decoration: none; border-radius: 5px; font-size: 14px; }
        .btn-primary { background: #2196F3; color: white; }
        .btn-success { background: #4CAF50; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.9; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
        .empty-state h3 { margin-bottom: 10px; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-left: 10px; }
        .badge-todo { background: #fff3e0; color: #e65100; }
        .badge-in-progress { background: #e3f2fd; color: #1565c0; }
        .badge-completed { background: #e8f5e9; color: #2e7d32; }
        .filter-buttons { display: flex; gap: 10px; margin-bottom: 20px; }
        .filter-btn { padding: 8px 16px; background: #e0e0e0; border: none; border-radius: 5px; cursor: pointer; }
        .filter-btn.active { background: #2196F3; color: white; }
        .success-message { background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📋 My Tasks</h1>
            <div>
                <a href="index.php" class="btn btn-secondary">← Dashboard</a>
                <a href="add_task.php" class="btn btn-primary" style="margin-left: 10px;">➕ New Task</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Delete Success Message -->
        <?php if (!empty($success_message)): ?>
            <div class="success-message">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <!-- Task Statistics -->
        <div class="stats">
            <div class="stat-card todo">
                <h3>To Do</h3>
                <div class="stat-number"><?php echo $status_counts['todo']; ?></div>
                <p>Tasks pending</p>
            </div>
            <div class="stat-card in-progress">
                <h3>In Progress</h3>
                <div class="stat-number"><?php echo $status_counts['in progress']; ?></div>
                <p>Active tasks</p>
            </div>
            <div class="stat-card completed">
                <h3>Completed</h3>
                <div class="stat-number"><?php echo $status_counts['completed']; ?></div>
                <p>Finished tasks</p>
            </div>
        </div>
        
        <!-- Task List -->
        <div class="card">
            <h2>All Tasks (<?php echo count($all_tasks); ?>)</h2>
            
            <?php if (count($all_tasks) == 0): ?>
                <div class="empty-state">
                    <h3>No tasks yet! 🎉</h3>
                    <p>You don't have any tasks. Create your first task to get started.</p>
                    <a href="add_task.php" class="btn btn-primary" style="margin-top: 15px;">Create Your First Task</a>
                </div>
            <?php else: ?>
                <div class="task-list">
                    <?php foreach ($all_tasks as $task): 
                        $status_class = str_replace(' ', '-', $task['status']);
                    ?>
                    <div class="task-item <?php echo $status_class; ?>">
                        <div class="task-info">
                            <h3>
                                <?php echo htmlspecialchars($task['title']); ?>
                                <span class="status-badge badge-<?php echo $status_class; ?>">
                                    <?php 
                                    $status_labels = [
                                        'todo' => '📝 To Do',
                                        'in-progress' => '🔄 In Progress',
                                        'completed' => '✅ Completed'
                                    ];
                                    echo $status_labels[$status_class];
                                    ?>
                                </span>
                            </h3>
                            <?php if (!empty($task['description'])): ?>
                                <p><?php echo htmlspecialchars($task['description']); ?></p>
                            <?php endif; ?>
                            <div class="task-meta">
                                Created: <?php echo date('M d, Y', strtotime($task['created_at'])); ?>
                                <?php if ($task['updated_at'] != $task['created_at']): ?>
                                    | Updated: <?php echo date('M d, Y', strtotime($task['updated_at'])); ?>
                                <?php endif; ?>
                                | ID: <?php echo $task['id']; ?>
                            </div>
                        </div>
                        <div class="task-actions">
                            <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-primary">Edit</a>
                            <a href="delete_task.php?id=<?php echo $task['id']; ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to delete this task?\n\nThis action cannot be undone.')">Delete</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <h3>Quick Actions</h3>
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <a href="add_task.php" class="btn btn-primary">➕ Add New Task</a>
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
                <?php if ($_SESSION['is_admin']): ?>
                    <a href="admin/dashboard.php" class="btn btn-success">👑 Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Simple filter functionality
        function filterTasks(status) {
            const tasks = document.querySelectorAll('.task-item');
            tasks.forEach(task => {
                if (status === 'all' || task.classList.contains(status)) {
                    task.style.display = 'flex';
                } else {
                    task.style.display = 'none';
                }
            });
            
            // Update active filter button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.status === status) {
                    btn.classList.add('active');
                }
            });
        }
    </script>
</body>
</html>