<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "请填写所有字段 / Please fill in all fields";
    } else {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT id, email, password, name, phone FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_phone'] = $user['phone'];
                
                // Update last login
                $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                $conn->close();
                
                // Redirect to home or previous page
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                header("Location: " . $redirect);
                exit;
            } else {
                $error = "密码错误 / Incorrect password";
            }
        } else {
            $error = "找不到该邮箱 / Email not found";
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
    <title>登录 - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 登录</h1>
            <p>Login to Your Account</p>
        </div>
        
        <div class="nav">
            <a href="index.php">预订餐桌 Booking</a>
            <a href="menu.php">提前点餐 Pre-Order</a>
            <a href="view_booking.php">查看预订 View Booking</a>
            <a href="admin.php">管理后台 Admin</a>
            <a href="history.php">历史记录 History</a>
        </div>
        
        <div class="content">
            <div style="max-width: 500px; margin: 0 auto;">
                <div class="alert alert-info" style="text-align: center;">
                    <p><strong>💡 温馨提示 / Note</strong></p>
                    <p>登录是<strong>可选的</strong>！您可以选择：</p>
                    <p>Login is <strong>optional</strong>! You can choose to:</p>
                    <ul style="text-align: left; display: inline-block;">
                        <li>✅ 登录账户 - 查看您的所有预订历史</li>
                        <li>✅ 访客预订 - 无需登录直接预订</li>
                    </ul>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['registered']) && $_GET['registered'] === 'success'): ?>
                    <div class="alert alert-success">
                        注册成功！请登录 / Registration successful! Please login
                    </div>
                <?php endif; ?>
                
                <h2 style="text-align: center;">会员登录 / Member Login</h2>
                
                <form method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
                    <div class="form-group">
                        <label for="email">邮箱 / Email *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">密码 / Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn">登录 / Login</button>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <p>还没有账户？ / Don't have an account?</p>
                        <a href="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" 
                           class="btn btn-secondary">注册新账户 / Register</a>
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
