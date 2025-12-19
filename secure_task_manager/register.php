<?php
session_start();
require_once 'includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        // Check if username/email exists
        $check_sql = "SELECT id FROM users WHERE username = '" . $conn->real_escape_string($username) . "' 
                      OR email = '" . $conn->real_escape_string($email) . "'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            // Insert new user - storing plain password for now
            // Security team will implement password hashing later
            $insert_sql = "INSERT INTO users (username, email, password, is_admin) 
                          VALUES ('" . $conn->real_escape_string($username) . "', 
                                  '" . $conn->real_escape_string($email) . "', 
                                  '" . $conn->real_escape_string($password) . "', 0)";
            
            if ($conn->query($insert_sql)) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed: " . $conn->error;
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
    <title>Register - Task Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .register-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        input:focus { outline: none; border-color: #4CAF50; }
        button { width: 100%; padding: 14px; background: #4CAF50; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #45a049; }
        .error { background: #ffebee; color: #c62828; padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .success { background: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .login-link { text-align: center; margin-top: 20px; }
        a { color: #2196F3; text-decoration: none; }
        .password-info { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="register-box">
        <h1>📝 Register New Account</h1>
        
        <?php if ($error): ?>
            <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required placeholder="Choose a username">
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required placeholder="Minimum 6 characters">
                <div class="password-info">Note: Password stored as plain text for development</div>
            </div>
            
            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required placeholder="Re-enter password">
            </div>
            
            <button type="submit">Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3e0; border-radius: 5px; font-size: 14px;">
            <h3>⚠️ Development Notes:</h3>
            <p>• Passwords stored in plain text (for development only)</p>
            <p>• Security team will implement password hashing</p>
            <p>• Security team will add input validation</p>
            <p>• Security team will use prepared statements</p>
        </div>
    </div>
</body>
</html>