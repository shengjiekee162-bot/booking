<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "请填写所有必填字段 / Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "邮箱格式不正确 / Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "密码至少需要6个字符 / Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $error = "两次密码不一致 / Passwords do not match";
    } else {
        $conn = getDBConnection();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "该邮箱已被注册 / Email already registered";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (email, password, name, phone) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $email, $hashed_password, $name, $phone);
            
            if ($stmt->execute()) {
                $conn->close();
                // Redirect to login with success message
                header("Location: login.php?registered=success" . (isset($_GET['redirect']) ? '&redirect=' . urlencode($_GET['redirect']) : ''));
                exit;
            } else {
                $error = "注册失败，请重试 / Registration failed, please try again";
            }
        }
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 注册账户</h1>
            <p>Create Your Account</p>
        </div>
        
        <div class="nav">
            <a href="index.php">预订餐桌 Booking</a>
            <a href="menu.php">提前点餐 Pre-Order</a>
            <a href="view_booking.php">查看预订 View Booking</a>
            <a href="history.php">历史记录 History</a>
        </div>
        
        <div class="content">
            <div style="max-width: 500px; margin: 0 auto;">
                <div class="alert alert-info" style="text-align: center;">
                    <p><strong>✨ 注册账户的好处 / Benefits of Registration</strong></p>
                    <ul style="text-align: left; display: inline-block;">
                        <li>📋 查看所有预订历史</li>
                        <li>⚡ 快速预订（自动填写信息）</li>
                        <li>🔔 接收预订提醒（未来功能）</li>
                        <li>🎁 会员优惠（未来功能）</li>
                    </ul>
                    <p style="margin-top: 10px; color: #667eea;">
                        <strong>💡 提示：注册完全免费且可选</strong>
                    </p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <h2 style="text-align: center;">创建新账户 / Create New Account</h2>
                
                <form method="POST" action="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
                    <div class="form-group">
                        <label for="name">姓名 / Name *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">邮箱 / Email *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        <small style="color: #666;">用于登录 / Used for login</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">电话 / Phone</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">密码 / Password *</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        <small style="color: #666;">至少6个字符 / At least 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">确认密码 / Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn">注册 / Register</button>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <p>已有账户？ / Already have an account?</p>
                        <a href="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" 
                           class="btn btn-secondary">登录 / Login</a>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <p style="color: #666;">或者 / Or</p>
                        <a href="<?php echo isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'index.php'; ?>" 
                           class="btn btn-secondary">作为访客继续 / Continue as Guest</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
