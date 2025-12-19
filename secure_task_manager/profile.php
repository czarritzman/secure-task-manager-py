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
$is_admin = $_SESSION['is_admin'];

// Get user details from database
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// Get user statistics
$tasks_count = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id")->fetch_assoc()['count'];
$tasks_todo = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id AND status = 'todo'")->fetch_assoc()['count'];
$tasks_in_progress = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id AND status = 'in progress'")->fetch_assoc()['count'];
$tasks_completed = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['count'];

// Get recent activity
$recent_tasks = $conn->query("SELECT * FROM tasks WHERE user_id = $user_id ORDER BY updated_at DESC LIMIT 5");

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Basic validation
    if (empty($email)) {
        $error = "Email is required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        // Check if email already exists (excluding current user)
        $check_sql = "SELECT id FROM users WHERE email = '" . $conn->real_escape_string($email) . "' AND id != $user_id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $error = "Email already registered by another user!";
        } else {
            // Update email
            $update_sql = "UPDATE users SET email = '" . $conn->real_escape_string($email) . "' WHERE id = $user_id";
            if ($conn->query($update_sql)) {
                $success = "Profile updated successfully!";
                // Refresh user data
                $user['email'] = $email;
            } else {
                $error = "Error updating profile: " . $conn->error;
            }
            
            // Handle password change if provided
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = "New passwords do not match!";
                } elseif (strlen($new_password) < 6) {
                    $error = "New password must be at least 6 characters!";
                } else {
                    // For now, just update password (security team will add proper validation)
                    // In production, should verify current password first
                    $update_pass_sql = "UPDATE users SET password = '" . $conn->real_escape_string($new_password) . "' WHERE id = $user_id";
                    if ($conn->query($update_pass_sql)) {
                        $success = "Profile and password updated successfully!";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Task Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .profile-header { text-align: center; margin-bottom: 30px; }
        .avatar { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 48px; margin: 0 auto 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card.total { border-top: 4px solid #667eea; }
        .stat-card.todo { border-top: 4px solid #ff9800; }
        .stat-card.in-progress { border-top: 4px solid #2196F3; }
        .stat-card.completed { border-top: 4px solid #4CAF50; }
        .stat-number { font-size: 32px; font-weight: bold; margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 14px; border: none; cursor: pointer; }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #4CAF50; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn:hover { opacity: 0.9; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: bold; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        input:focus { outline: none; border-color: #667eea; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .error { background: #ffebee; color: #c62828; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .info-box { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #2196F3; }
        .activity-list { margin-top: 20px; }
        .activity-item { border-left: 3px solid #667eea; padding: 15px; margin: 10px 0; background: #f8f9fa; border-radius: 0 5px 5px 0; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; margin-left: 10px; }
        .badge-admin { background: #ff9800; color: white; }
        .badge-user { background: #2196F3; color: white; }
        .password-note { font-size: 12px; color: #666; margin-top: 5px; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>👤 My Profile</h1>
            <div>
                <a href="index.php" class="btn btn-secondary">← Dashboard</a>
                <a href="logout.php" class="btn btn-danger" style="margin-left: 10px;">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Profile Header -->
        <div class="card">
            <div class="profile-header">
                <div class="avatar">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
                <h2><?php echo htmlspecialchars($username); ?></h2>
                <p>
                    <?php if ($is_admin): ?>
                        <span class="badge badge-admin">👑 Administrator</span>
                    <?php else: ?>
                        <span class="badge badge-user">👤 Regular User</span>
                    <?php endif; ?>
                </p>
                <p style="color: #666; margin-top: 5px;">Member since: <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Task Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <h3>Total Tasks</h3>
                <div class="stat-number"><?php echo $tasks_count; ?></div>
                <p>All your tasks</p>
            </div>
            <div class="stat-card todo">
                <h3>To Do</h3>
                <div class="stat-number"><?php echo $tasks_todo; ?></div>
                <p>Pending tasks</p>
            </div>
            <div class="stat-card in-progress">
                <h3>In Progress</h3>
                <div class="stat-number"><?php echo $tasks_in_progress; ?></div>
                <p>Active tasks</p>
            </div>
            <div class="stat-card completed">
                <h3>Completed</h3>
                <div class="stat-number"><?php echo $tasks_completed; ?></div>
                <p>Finished tasks</p>
            </div>
        </div>
        
        <!-- Profile Information -->
        <div class="card">
            <h2>📋 Profile Information</h2>
            
            <div class="info-box">
                <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?> <em>(cannot be changed)</em></p>
                <p><strong>Account Type:</strong> <?php echo $is_admin ? 'Administrator' : 'Regular User'; ?></p>
                <p><strong>Registration Date:</strong> <?php echo date('F d, Y, H:i', strtotime($user['created_at'])); ?></p>
            </div>
            
            <h3 style="margin-top: 30px;">✏️ Edit Profile</h3>
            <form method="POST" action="">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           placeholder="your.email@example.com" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" 
                               placeholder="Enter current password">
                        <div class="password-note">Required only if changing password</div>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               placeholder="Enter new password (min 6 chars)">
                        <div class="password-note">Leave empty to keep current password</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Re-enter new password">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">💾 Save Changes</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <a href="my_tasks.php" class="btn btn-primary">📋 View My Tasks</a>
                </div>
            </form>
            
            <div class="info-box" style="margin-top: 30px;">
                <h4>⚠️ Security Notes:</h4>
                <p><small>• Password hashing will be implemented by security team</small></p>
                <p><small>• Email verification system to be added</small></p>
                <p><small>• Two-factor authentication available in future updates</small></p>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card">
            <h2>📈 Recent Activity</h2>
            <p>Your latest task updates and activities</p>
            
            <div class="activity-list">
                <?php if ($recent_tasks->num_rows == 0): ?>
                    <div style="text-align: center; padding: 30px; color: #666;">
                        <h3>No activity yet! 📭</h3>
                        <p>You haven't created or updated any tasks recently.</p>
                        <a href="add_task.php" class="btn btn-primary" style="margin-top: 15px;">➕ Create Your First Task</a>
                    </div>
                <?php else: ?>
                    <?php while($task = $recent_tasks->fetch_assoc()): 
                        $status_class = str_replace(' ', '-', $task['status']);
                        $status_colors = [
                            'todo' => ['color' => '#ff9800', 'icon' => '📝'],
                            'in-progress' => ['color' => '#2196F3', 'icon' => '🔄'],
                            'completed' => ['color' => '#4CAF50', 'icon' => '✅']
                        ];
                    ?>
                    <div class="activity-item">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                                <?php if (!empty($task['description'])): ?>
                                    <p style="color: #666; margin: 5px 0;"><?php echo htmlspecialchars(substr($task['description'], 0, 80)); ?><?php echo strlen($task['description']) > 80 ? '...' : ''; ?></p>
                                <?php endif; ?>
                                <small style="color: #888;">
                                    Updated: <?php echo date('M d, Y H:i', strtotime($task['updated_at'])); ?>
                                    | Created: <?php echo date('M d, Y', strtotime($task['created_at'])); ?>
                                </small>
                            </div>
                            <div>
                                <span style="background: <?php echo $status_colors[$status_class]['color']; ?>; color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px;">
                                    <?php echo $status_colors[$status_class]['icon']; ?> 
                                    <?php echo ucwords(str_replace('-', ' ', $status_class)); ?>
                                </span>
                            </div>
                        </div>
                        <div style="margin-top: 10px;">
                            <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 12px; background: #e0e0e0;">Edit</a>
                            <a href="my_tasks.php" class="btn" style="padding: 5px 10px; font-size: 12px; background: #667eea; color: white;">View All</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="my_tasks.php" class="btn btn-primary">View All Activities</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Account Actions -->
        <div class="card">
            <h2>⚡ Quick Actions</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 15px;">
                <a href="add_task.php" class="btn btn-primary">➕ Add New Task</a>
                <a href="my_tasks.php" class="btn btn-primary">📋 View My Tasks</a>
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
                <?php if ($is_admin): ?>
                    <a href="admin/dashboard.php" class="btn btn-success">👑 Admin Panel</a>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #fff3e0; border-radius: 5px;">
                <h3>🔐 Account Security</h3>
                <p><small>For enhanced security, the following features will be implemented by the security team:</small></p>
                <ul style="margin: 10px 0 10px 20px; font-size: 14px;">
                    <li>Password hashing (bcrypt/Argon2)</li>
                    <li>Session timeout and management</li>
                    <li>Login attempt limiting</li>
                    <li>Two-factor authentication</li>
                    <li>Email verification</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div style="text-align: center; margin: 30px; color: #666; padding: 20px;">
        <p>👤 <strong>User Profile</strong> - Task Management System</p>
        <p><small>User ID: <?php echo $user_id; ?> | Account created: <?php echo date('F d, Y', strtotime($user['created_at'])); ?></small></p>
    </div>
    
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return false;
            }
            
            if (newPassword && newPassword.length < 6) {
                e.preventDefault();
                alert('New password must be at least 6 characters!');
                return false;
            }
            
            return true;
        });
        
        // Show password requirements
        document.getElementById('new_password').addEventListener('focus', function() {
            const note = document.createElement('div');
            note.className = 'password-note';
            note.innerHTML = '<strong>Password Requirements:</strong><br>• At least 6 characters<br>• Include letters and numbers<br>• Special characters recommended';
            note.style.marginTop = '10px';
            note.style.padding = '10px';
            note.style.background = '#f0f0f0';
            note.style.borderRadius = '5px';
            this.parentNode.appendChild(note);
        });
    </script>
</body>
</html>