<?php
session_start();
echo "<h3>Session Status:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "<br>";
echo "Is Admin: " . ($_SESSION['is_admin'] ?? 'Not set') . "<br>";
echo "<a href='logout.php'>Logout</a> | <a href='login.php'>Login</a>";
?>