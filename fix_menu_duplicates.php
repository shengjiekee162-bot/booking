<?php
require_once 'config.php';

$conn = getDBConnection();

echo "=== 清理重复的菜单项 ===\n\n";

// 找出所有重复的食物，保留ID最小的
$sql = "
DELETE t1 FROM food_menu t1
INNER JOIN food_menu t2 
WHERE 
    t1.id > t2.id 
    AND t1.name = t2.name 
    AND t1.category = t2.category 
    AND t1.price = t2.price
";

if ($conn->query($sql)) {
    echo "✅ 重复的菜单项已删除！\n\n";
    
    // 显示剩余的菜单项
    $result = $conn->query('SELECT id, name, category, price FROM food_menu ORDER BY category, name');
    
    echo "=== 清理后的菜单项 ===\n\n";
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} | {$row['name']} | {$row['category']} | RM {$row['price']}\n";
    }
    
    echo "\n✅ 菜单已更新！每个食物现在只显示一次。\n";
} else {
    echo "❌ 错误: " . $conn->error . "\n";
}

$conn->close();
?>
