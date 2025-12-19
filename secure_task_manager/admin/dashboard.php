<?php
session_start();
require_once '../includes/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is admin
if ($_SESSION['is_admin'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Get statistics
$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$tasks_count = $conn->query("SELECT COUNT(*) as count FROM tasks")->fetch_assoc()['count'];
$admins_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1")->fetch_assoc()['count'];
$recent_tasks = $conn->query("SELECT t.*, u.username FROM tasks t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 10");
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Task Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 20px; }
        .header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card.users { border-top: 4px solid #3498db; }
        .stat-card.tasks { border-top: 4px solid #2ecc71; }
        .stat-card.admins { border-top: 4px solid #9b59b6; }
        .stat-card.activity { border-top: 4px solid #e74c3c; }
        .stat-number { font-size: 36px; font-weight: bold; margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 14px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn:hover { opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; color: #333; }
        tr:hover { background: #f9f9f9; }
        .admin-badge { background: #9b59b6; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; }
        .user-badge { background: #3498db; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; }
        .status-badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-todo { background: #fff3e0; color: #e65100; }
        .badge-progress { background: #e3f2fd; color: #1565c0; }
        .badge-completed { background: #e8f5e9; color: #2e7d32; }
        .admin-nav { background: #34495e; padding: 15px 0; margin-bottom: 20px; }
        .admin-nav ul { list-style: none; display: flex; justify-content: center; gap: 20px; max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .admin-nav a { color: white; text-decoration: none; padding: 8px 16px; border-radius: 5px; transition: background 0.3s; }
        .admin-nav a:hover { background: rgba(255,255,255,0.1); }
        .admin-nav a.active { background: #3498db; }
        .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .two-columns { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>👑 Admin Dashboard</h1>
            <div>
                <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="../index.php" class="btn btn-secondary" style="margin-left: 20px;">← User Dashboard</a>
                <a href="../logout.php" class="btn btn-danger" style="margin-left: 10px;">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="admin-nav">
        <ul>
            <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="audit_log.php">📋 Audit Log</a></li>
            <li><a href="manage_users.php">👥 Manage Users</a></li>
            <li><a href="all_tasks.php">📝 All Tasks</a></li>
            <li><a href="system_logs.php">🔧 System Logs</a></li>
        </ul>
    </div>
    
    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card users">
                <h3>👥 Total Users</h3>
                <div class="stat-number"><?php echo $users_count; ?></div>
                <p>Registered users</p>
                <a href="manage_users.php" class="btn btn-primary" style="margin-top: 10px;">Manage Users</a>
            </div>
            
            <div class="stat-card tasks">
                <h3>📝 Total Tasks</h3>
                <div class="stat-number"><?php echo $tasks_count; ?></div>
                <p>Created tasks</p>
                <a href="all_tasks.php" class="btn btn-success" style="margin-top: 10px;">View All Tasks</a>
            </div>
            
            <div class="stat-card admins">
                <h3>👑 Administrators</h3>
                <div class="stat-number"><?php echo $admins_count; ?></div>
                <p>Admin accounts</p>
                <a href="manage_users.php?filter=admin" class="btn btn-warning" style="margin-top: 10px;">View Admins</a>
            </div>
            
            <div class="stat-card activity">
                <h3>📈 Recent Activity</h3>
                <div class="stat-number"><?php echo $recent_tasks->num_rows; ?></div>
                <p>Recent tasks</p>
                <a href="audit_log.php" class="btn btn-danger" style="margin-top: 10px;">View Logs</a>
            </div>
        </div>
        
        <!-- Two Columns: Recent Tasks & Recent Users -->
        <div class="two-columns">
            <!-- Recent Tasks -->
            <div class="card">
                <h2>📝 Recent Tasks</h2>
                <p>Latest tasks created by all users</p>
                
                <?php if ($recent_tasks->num_rows == 0): ?>
                    <p style="text-align: center; padding: 20px; color: #666;">No tasks created yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($task = $recent_tasks->fetch_assoc()): 
                                $status_class = str_replace(' ', '-', $task['status']);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars(substr($task['title'], 0, 30)); ?></strong>
                                    <?php if (strlen($task['title']) > 30): ?>...<?php endif; ?>
                                </td>
                                <td>
                                    <span class="user-badge"><?php echo htmlspecialchars($task['username']); ?></span>
                                    <?php if ($task['user_id'] == $_SESSION['user_id']): ?>
                                        <small>(You)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge badge-<?php echo $status_class; ?>">
                                        <?php 
                                        $status_text = [
                                            'todo' => '📝 To Do',
                                            'in-progress' => '🔄 In Progress',
                                            'completed' => '✅ Completed'
                                        ];
                                        echo $status_text[$status_class];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($task['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="all_tasks.php" class="btn btn-primary">View All Tasks</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Users -->
            <div class="card">
                <h2>👥 Recent Users</h2>
                <p>Latest registered users</p>
                
                <?php if ($recent_users->num_rows == 0): ?>
                    <p style="text-align: center; padding: 20px; color: #666;">No users registered yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = $recent_users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                        <small>(You)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['is_admin'] == 1): ?>
                                        <span class="admin-badge">Admin</span>
                                    <?php else: ?>
                                        <span class="user-badge">User</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="manage_users.php" class="btn btn-primary">Manage All Users</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Admin Actions -->
        <div class="card">
            <h2>⚡ Quick Admin Actions</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                <a href="../register.php" class="btn btn-success">➕ Create New User</a>
                <a href="all_tasks.php" class="btn btn-primary">📋 View All Tasks</a>
                <a href="audit_log.php" class="btn btn-warning">📊 View System Logs</a>
                <a href="manage_users.php" class="btn btn-danger">👥 Manage Users</a>
                <a href="../add_task.php" class="btn btn-success">➕ Create Task</a>
                <a href="../index.php" class="btn btn-secondary">← User View</a>
            </div>
        </div>
        
        <!-- System Info -->
        <div class="card">
            <h2>🔧 System Information</h2>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Current User:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?>
                    <?php if ($_SESSION['is_admin'] == 1): ?>
                        <span class="admin-badge">Administrator</span>
                    <?php endif; ?>
                </div>
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?>
                </div>
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Session Started:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                </div>
                <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Database:</strong> <?php echo $tasks_count; ?> tasks, <?php echo $users_count; ?> users
                </div>
            </div>
        </div>
    </div>
    
    <div style="text-align: center; margin: 30px; color: #666; padding: 20px; border-top: 1px solid #ddd;">
        <p>👑 <strong>Admin Panel</strong> - Task Management System</p>
        <p><small>Secure Software Development Project | OWASP Security Implementation Required</small></p>
    </div>
</body>
</html>