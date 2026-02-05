<?php
require_once 'config.php';

$conn = getDBConnection();
$result = $conn->query('SELECT id, name, category, price FROM food_menu ORDER BY category, name');

echo "=== 数据库中的菜单项 ===\n\n";
while($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} | {$row['name']} | {$row['category']} | RM {$row['price']}\n";
}

$conn->close();
?>
