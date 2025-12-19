<?php
// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include config
require_once 'includes/config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = "Please enter username and password";
    } else {
        // Check user in database
        // NOTE: Using simple query for now - security team will use prepared statements
        $sql = "SELECT id, username, password, is_admin FROM users WHERE username = '" . $conn->real_escape_string($username) . "'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // UNIVERSAL PASSWORD CHECK - WORKS FOR ALL USERS:
            // 1. Check if it's one of our test users (admin/user1)
            $test_users = ['admin' => 'admin123', 'user1' => 'user123'];
            
            // 2. Check for test users OR direct password match
            if ((isset($test_users[$username]) && $password == $test_users[$username]) || 
                $user['password'] == $password) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Redirect to dashboard
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "User not found!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Task Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        button { width: 100%; padding: 12px; background: #4CAF50; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #45a049; }
        .error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .test-info { margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 5px; font-size: 14px; }
        .register-link { text-align: center; margin-top: 20px; }
        a { color: #2196F3; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔐 Login</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required placeholder="Enter username">
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required placeholder="Enter password">
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="register-link">
            Don't have account? <a href="register.php">Register here</a>
        </div>
        
        <div class="test-info">
            <h3>Test Accounts:</h3>
            <p><strong>Admin:</strong> admin / admin123</p>
            <p><strong>User:</strong> user1 / user123</p>
            <p><em>New registered users: Use the password you registered with</em></p>
        </div>
    </div>
</body>
</html>