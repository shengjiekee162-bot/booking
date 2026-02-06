<?php
require_once 'config.php';

$reset_success = false;
$reset_error = '';

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_admin'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $reset_error = "请填写密码 / Please enter password";
    } elseif ($new_password !== $confirm_password) {
        $reset_error = "两次密码不一致 / Passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $reset_error = "密码至少需要6个字符 / Password must be at least 6 characters";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update database
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@restaurant.com'");
        $stmt->bind_param("s", $hashed_password);
        
        if ($stmt->execute()) {
            $reset_success = true;
        } else {
            $reset_error = "更新失败 / Update failed: " . $stmt->error;
        }
        
        $conn->close();
    }
}

// Handle direct password reset via GET parameter
if (isset($_GET['reset']) && $_GET['reset'] === 'default') {
    $default_password = 'admin123';
    $hashed_password = password_hash($default_password, PASSWORD_BCRYPT);
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@restaurant.com'");
    $stmt->bind_param("s", $hashed_password);
    
    if ($stmt->execute()) {
        $reset_success = true;
    } else {
        $reset_error = "重置失败 / Reset failed";
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置Admin密码 - Reset Admin Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 重置管理员密码</h1>
            <p>Reset Admin Password</p>
        </div>
        
        <div class="nav">
            <a href="index.php">预订餐桌 Booking</a>
            <a href="admin.php">管理后台 Admin</a>
        </div>
        
        <div class="content" style="max-width: 600px; margin: 40px auto;">
            
            <?php if ($reset_success): ?>
                <div class="alert alert-success">
                    <h3>✅ 密码重置成功! / Password Reset Successfully!</h3>
                    <p>Admin 账号密码已更新。</p>
                    <p>Admin account password has been updated.</p>
                    <p style="margin-top: 20px;">
                        <strong>邮箱 / Email:</strong> admin@restaurant.com<br>
                        <strong>新密码 / New Password:</strong> 
                        <?php 
                        if (isset($_POST['new_password'])) {
                            echo htmlspecialchars($_POST['new_password']);
                        } else {
                            echo 'admin123';
                        }
                        ?>
                    </p>
                    <p style="margin-top: 20px;">
                        <a href="admin.php" class="btn btn-primary" style="padding: 10px 20px; text-decoration: none; display: inline-block;">
                            前往登入 / Go to Login
                        </a>
                    </p>
                </div>
            <?php else: ?>
                
                <div style="background: #f8f9fa; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1);">
                    <h2 style="text-align: center; margin-bottom: 30px;">🔐 设置新密码 / Set New Password</h2>
                    
                    <?php if ($reset_error): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($reset_error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                        <input type="hidden" name="reset_admin" value="1">
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">📧 Admin 邮箱 / Email:</label>
                            <input type="text" value="admin@restaurant.com" disabled 
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; background: #e9ecef; font-size: 1em;">
                        </div>
                        
                        <div>
                            <label for="new_password" style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">🔑 新密码 / New Password:</label>
                            <input type="password" id="new_password" name="new_password" 
                                   placeholder="输入新密码 / Enter new password"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; box-sizing: border-box;"
                                   required>
                        </div>
                        
                        <div>
                            <label for="confirm_password" style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">🔐 确认密码 / Confirm Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="再次输入密码 / Confirm password"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; box-sizing: border-box;"
                                   required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="padding: 12px; font-size: 1.1em; margin-top: 10px;">
                            💾 重置密码 / Reset Password
                        </button>
                    </form>
                    
                    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
                    
                    <div style="text-align: center;">
                        <p style="margin-bottom: 10px; color: #666;">或 / Or</p>
                        <a href="?reset=default" class="btn btn-secondary" style="padding: 10px 20px; text-decoration: none; display: inline-block;">
                            🔄 重置为默认密码 (admin123) / Reset to Default Password
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" style="color: #667eea; text-decoration: none;">← 返回首页 / Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
