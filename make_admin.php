<?php
require_once 'config.php';

$conn = getDBConnection();

// 将 ID 为 1 的用户设置为 admin
$stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE id = 1");
if ($stmt->execute()) {
    echo "✅ 成功！用户 jie 已被设置为管理员\n";
    echo "现在可以用该账号登入 admin.php\n";
} else {
    echo "❌ 失败: " . $stmt->error . "\n";
}

$conn->close();
?>
