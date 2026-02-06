<?php
require_once 'config.php';

// 生成新的密码哈希
$password = "admin123";
$hashed = password_hash($password, PASSWORD_BCRYPT);

echo "生成的密码哈希: " . $hashed . "\n\n";

// 连接数据库并更新
$conn = getDBConnection();

// 先检查是否有admin账号
$result = $conn->query("SELECT id, email, is_admin FROM users WHERE email = 'admin@restaurant.com'");
if ($result->num_rows > 0) {
    echo "找到 admin 账号，准备更新密码...\n";
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@restaurant.com'");
    $stmt->bind_param("s", $hashed);
    if ($stmt->execute()) {
        echo "✅ 密码更新成功!\n";
        echo "邮箱: admin@restaurant.com\n";
        echo "密码: admin123\n";
    } else {
        echo "❌ 更新失败: " . $stmt->error . "\n";
    }
} else {
    echo "❌ 找不到 admin@restaurant.com 账号\n";
    echo "正在创建新的 admin 账号...\n";
    
    $stmt = $conn->prepare("INSERT INTO users (email, password, name, phone, is_admin) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $email, $hashed, $name, $phone);
    
    $email = "admin@restaurant.com";
    $name = "Administrator";
    $phone = "6012345678";
    
    if ($stmt->execute()) {
        echo "✅ Admin 账号创建成功!\n";
        echo "邮箱: admin@restaurant.com\n";
        echo "密码: admin123\n";
    } else {
        echo "❌ 创建失败: " . $stmt->error . "\n";
    }
}

$conn->close();
?>
