<?php
/**
 * 设置管理员脚本 / Set Admin Script
 * 用于将用户设置为管理员
 */

require_once 'config.php';

echo "=== 设置管理员 / Set Admin ===\n\n";

$conn = getDBConnection();

// 查询所有用户
$result = $conn->query("SELECT id, email, name, is_admin FROM users");

if ($result->num_rows > 0) {
    echo "现有用户列表 / Existing Users:\n";
    echo str_repeat("-", 80) . "\n";
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
        $admin_status = $row['is_admin'] ? "✅ 管理员" : "❌ 普通用户";
        echo "ID: {$row['id']} | Email: {$row['email']} | Name: {$row['name']} | Status: $admin_status\n";
    }
    echo str_repeat("-", 80) . "\n\n";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $make_admin = isset($_POST['make_admin']) ? 1 : 0;
        
        $update = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $update->bind_param("ii", $make_admin, $user_id);
        
        if ($update->execute()) {
            echo "✅ 更新成功！/ Update successful!\n\n";
            // 重新加载用户列表
            header("Refresh: 2; url=set_admin.php");
        } else {
            echo "❌ 更新失败 / Update failed: " . $update->error . "\n";
        }
    }
} else {
    echo "❌ 没有找到任何用户 / No users found\n";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>设置管理员 - Set Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .user-table th, .user-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .user-table th {
            background: #667eea;
            color: white;
        }
        .user-table tr:hover {
            background: #f5f5f5;
        }
        .btn {
            padding: 8px 16px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-admin {
            background: #4CAF50;
            color: white;
        }
        .btn-user {
            background: #f44336;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .admin-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-size: 12px;
        }
        .admin-badge.admin {
            background: #4CAF50;
        }
        .admin-badge.user {
            background: #f44336;
        }
        form {
            display: inline;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-info {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            color: #004085;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 设置管理员 / Set Admin</h1>
        
        <div class="alert alert-info">
            <strong>💡 说明：</strong><br>
            选择用户后点击按钮可以将其设置为管理员或普通用户。<br>
            Instructions: Select a user and click the button to set them as admin or regular user.
        </div>
        
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>邮箱 / Email</th>
                    <th>名字 / Name</th>
                    <th>状态 / Status</th>
                    <th>操作 / Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $conn = getDBConnection();
                $result = $conn->query("SELECT id, email, name, is_admin FROM users ORDER BY id");
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $is_admin = $row['is_admin'];
                        $status_badge = $is_admin ? '<span class="admin-badge admin">✅ 管理员 / Admin</span>' : '<span class="admin-badge user">❌ 普通用户 / User</span>';
                        
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                        echo '<td>' . $status_badge . '</td>';
                        echo '<td>';
                        
                        if ($is_admin) {
                            echo '<form method="POST" style="display:inline;">';
                            echo '<input type="hidden" name="user_id" value="' . $row['id'] . '">';
                            echo '<input type="hidden" name="make_admin" value="0">';
                            echo '<button type="submit" class="btn btn-user">降级为普通用户 / Make User</button>';
                            echo '</form>';
                        } else {
                            echo '<form method="POST" style="display:inline;">';
                            echo '<input type="hidden" name="user_id" value="' . $row['id'] . '">';
                            echo '<input type="hidden" name="make_admin" value="1">';
                            echo '<button type="submit" class="btn btn-admin">升级为管理员 / Make Admin</button>';
                            echo '</form>';
                        }
                        
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" style="text-align: center; color: #999;">❌ 没有找到任何用户 / No users found</td></tr>';
                }
                
                $conn->close();
                ?>
            </tbody>
        </table>
        
        <div style="margin-top: 30px; padding: 15px; background: #f0f0f0; border-radius: 4px;">
            <a href="index.php" style="color: #667eea; text-decoration: none; font-weight: bold;">← 返回首页 / Back to Home</a>
        </div>
    </div>
</body>
</html>
