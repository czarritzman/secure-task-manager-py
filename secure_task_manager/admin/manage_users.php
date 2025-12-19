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

// Get all users
$users_result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$total_users = $users_result->num_rows;

$page_title = "Manage Users";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 20px; }
        .header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 14px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .btn:hover { opacity: 0.9; }
        .admin-nav { background: #34495e; padding: 15px 0; margin-bottom: 20px; }
        .admin-nav ul { list-style: none; display: flex; justify-content: center; gap: 20px; max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .admin-nav a { color: white; text-decoration: none; padding: 8px 16px; border-radius: 5px; transition: background 0.3s; }
        .admin-nav a:hover { background: rgba(255,255,255,0.1); }
        .admin-nav a.active { background: #3498db; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; color: #333; }
        tr:hover { background: #f9f9f9; }
        .admin-badge { background: #9b59b6; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; }
        .user-badge { background: #3498db; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; }
        .current-user { background: #e8f5e9 !important; }
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-bar input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .user-actions { display: flex; gap: 5px; }
        .stats-bar { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-box { background: white; padding: 15px; border-radius: 5px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 10% auto; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>👥 <?php echo $page_title; ?></h1>
            <div>
                <a href="dashboard.php" class="btn btn-secondary">← Admin Dashboard</a>
                <a href="../register.php" class="btn btn-success" style="margin-left: 10px;">➕ Create User</a>
            </div>
        </div>
    </div>
    
    <div class="admin-nav">
        <ul>
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="audit_log.php">📋 Audit Log</a></li>
            <li><a href="manage_users.php" class="active">👥 Manage Users</a></li>
            <li><a href="all_tasks.php">📝 All Tasks</a></li>
            <li><a href="system_logs.php">🔧 System Logs</a></li>
        </ul>
    </div>
    
    <div class="container">
        <!-- User Statistics -->
        <div class="stats-bar">
            <div class="stat-box">
                <h3>Total Users</h3>
                <div style="font-size: 24px; font-weight: bold;"><?php echo $total_users; ?></div>
            </div>
            <div class="stat-box">
                <h3>Admins</h3>
                <div style="font-size: 24px; font-weight: bold;">
                    <?php 
                    $admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1")->fetch_assoc()['count'];
                    echo $admins;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <h3>Regular Users</h3>
                <div style="font-size: 24px; font-weight: bold;">
                    <?php echo $total_users - $admins; ?>
                </div>
            </div>
            <div class="stat-box">
                <h3>Active Today</h3>
                <div style="font-size: 24px; font-weight: bold;">
                    <?php 
                    $today = date('Y-m-d');
                    $active = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM tasks WHERE DATE(created_at) = '$today'")->fetch_assoc()['count'];
                    echo $active;
                    ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>👥 User Management</h2>
            <p>Manage all registered users in the system. You can view, edit, or delete user accounts.</p>
            
            <!-- Search Bar -->
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search users by username, email, or ID..." onkeyup="searchUsers()">
                <button class="btn btn-primary" onclick="searchUsers()">Search</button>
                <button class="btn btn-secondary" onclick="clearSearch()">Clear</button>
            </div>
            
            <!-- Users Table -->
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tasks</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $users_result->fetch_assoc()): 
                        $is_current = ($user['id'] == $_SESSION['user_id']);
                        $task_count = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = " . $user['id'])->fetch_assoc()['count'];
                    ?>
                    <tr class="<?php echo $is_current ? 'current-user' : ''; ?>">
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            <?php if ($is_current): ?>
                                <small style="color: #2e7d32;">(You)</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['is_admin'] == 1): ?>
                                <span class="admin-badge">👑 Admin</span>
                            <?php else: ?>
                                <span class="user-badge">👤 User</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($task_count > 0): ?>
                                <a href="all_tasks.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                    <?php echo $task_count; ?> tasks
                                </a>
                            <?php else: ?>
                                <span style="color: #666;">No tasks</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="user-actions">
                                <?php if (!$is_current): ?>
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="change_role.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                        <?php echo $user['is_admin'] == 1 ? 'Demote' : 'Promote'; ?>
                                    </a>
                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete user <?php echo htmlspecialchars($user['username']); ?>?\n\nThis will also delete all their tasks.')">
                                        Delete
                                    </a>
                                <?php else: ?>
                                    <span style="color: #666; font-size: 12px;">Current user</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="../register.php" class="btn btn-success">➕ Create New User</a>
                <button class="btn btn-primary" onclick="exportUsers()">Export Users (CSV)</button>
                <button class="btn btn-warning" onclick="showBulkActions()">Bulk Actions</button>
            </div>
        </div>
        
        <!-- Admin Notes -->
        <div class="card">
            <h3>📝 Admin Notes</h3>
            <div style="margin-top: 15px;">
                <p><strong>Current Limitations (Security Team to Implement):</strong></p>
                <ul style="margin: 10px 0 10px 20px;">
                    <li>✅ User listing and statistics - <strong>IMPLEMENTED</strong></li>
                    <li>❌ User editing (password reset, profile update) - <em>To be implemented</em></li>
                    <li>❌ Role management (promote/demote users) - <em>To be implemented</em></li>
                    <li>❌ Bulk user actions - <em>To be implemented</em></li>
                    <li>❌ User activity tracking - <em>To be implemented</em></li>
                    <li>❌ Email verification system - <em>To be implemented</em></li>
                </ul>
                <p><em>Note: These features will be implemented by the security team with proper OWASP controls.</em></p>
            </div>
        </div>
    </div>
    
    <!-- Bulk Actions Modal -->
    <div id="bulkModal" class="modal">
        <div class="modal-content">
            <h2>Bulk User Actions</h2>
            <p>Select action to perform on multiple users:</p>
            <div style="margin: 20px 0;">
                <button class="btn btn-warning" style="width: 100%; margin-bottom: 10px;">Send Email to Selected</button>
                <button class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">Export Selected Users</button>
                <button class="btn btn-danger" style="width: 100%; margin-bottom: 10px;">Delete Selected Users</button>
            </div>
            <button class="btn btn-secondary" onclick="closeModal()" style="width: 100%;">Cancel</button>
        </div>
    </div>
    
    <div style="text-align: center; margin: 30px; color: #666; padding: 20px; border-top: 1px solid #ddd;">
        <p>👥 <strong>User Management System</strong> - Administrator controls</p>
        <p><small>Total: <?php echo $total_users; ?> users | <?php echo $admins; ?> admins | <?php echo $total_users - $admins; ?> regular users</small></p>
    </div>
    
    <script>
        function searchUsers() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('usersTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }
        
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            searchUsers();
        }
        
        function exportUsers() {
            alert('User export feature to be implemented by security team.');
        }
        
        function showBulkActions() {
            document.getElementById('bulkModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('bulkModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('bulkModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>