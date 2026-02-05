<?php
require_once 'config.php';

$conn = getDBConnection();

echo "=== 添加更多食物到菜单 ===\n\n";

// 新增的食物菜单
$new_items = [
    // Main Course
    ['Nasi Goreng Kampung', '马来西亚乡村炒饭配江鱼仔和虾 / Malaysian village fried rice with anchovies and prawns', 'Main Course', 14.90],
    ['Curry Laksa', '咖喱叻沙配豆腐泡和鸡肉 / Curry laksa with tofu puff and chicken', 'Main Course', 16.50],
    ['Mee Goreng', '印式炒面配虾和鱿鱼 / Indian fried noodles with prawns and squid', 'Main Course', 15.00],
    ['Rendang Chicken', '印尼仁当鸡配饭 / Indonesian rendang chicken with rice', 'Main Course', 18.90],
    ['Beef Hor Fun', '干炒牛河 / Stir-fried flat noodles with beef', 'Main Course', 17.50],
    
    // Soup
    ['Soto Ayam', '印尼黄姜鸡汤 / Indonesian turmeric chicken soup', 'Soup', 9.50],
    ['Bak Kut Teh', '肉骨茶 / Pork rib soup with herbs', 'Soup', 16.90],
    ['Seafood Tom Yam', '海鲜泰式酸辣汤 / Seafood Thai hot and sour soup', 'Soup', 12.90],
    
    // Appetizer
    ['Roti Canai (2 pcs)', '印度煎饼配咖喱 / Indian flatbread with curry', 'Appetizer', 5.50],
    ['Popiah (3 rolls)', '薄饼卷 / Fresh spring rolls', 'Appetizer', 8.50],
    ['Fried Wonton (8 pcs)', '炸云吞 / Fried wontons', 'Appetizer', 9.00],
    ['Otak-Otak (5 sticks)', '乌达 / Grilled fish cake', 'Appetizer', 10.00],
    ['Keropok Lekor', '炸鱼饼配辣椒酱 / Fried fish crackers with chili sauce', 'Appetizer', 7.00],
    
    // Dessert
    ['Cendol', '煎蕊冰 / Shaved ice with coconut milk and palm sugar', 'Dessert', 6.90],
    ['Kuih Lapis', '千层糕 / Rainbow layer cake', 'Dessert', 5.50],
    ['Ondeh-Ondeh (6 pcs)', '椰丝糯米球 / Pandan glutinous rice balls with palm sugar', 'Dessert', 6.00],
    ['Pulut Hitam', '黑糯米甜汤 / Black glutinous rice dessert', 'Dessert', 7.50],
    
    // Beverage
    ['Milo Dinosaur', '美禄恐龙 / Iced Milo with extra powder', 'Beverage', 5.50],
    ['Limau Ais', '冰柠檬水 / Iced lime juice', 'Beverage', 4.00],
    ['Sirap Bandung', '玫瑰糖浆奶 / Rose syrup with milk', 'Beverage', 4.50],
    ['Kopi O', '黑咖啡 / Black coffee', 'Beverage', 3.00],
    ['Teh O Limau', '柠檬茶 / Lemon tea', 'Beverage', 3.50]
];

$success_count = 0;
$error_count = 0;

foreach ($new_items as $item) {
    $stmt = $conn->prepare("INSERT INTO food_menu (name, description, category, price, available) VALUES (?, ?, ?, ?, TRUE)");
    $stmt->bind_param("sssd", $item[0], $item[1], $item[2], $item[3]);
    
    if ($stmt->execute()) {
        echo "✅ 已添加: {$item[0]} - {$item[2]} - RM {$item[3]}\n";
        $success_count++;
    } else {
        echo "❌ 失败: {$item[0]} - " . $conn->error . "\n";
        $error_count++;
    }
    
    $stmt->close();
}

echo "\n=== 添加完成 ===\n";
echo "✅ 成功添加: {$success_count} 项\n";
if ($error_count > 0) {
    echo "❌ 失败: {$error_count} 项\n";
}

// 显示所有菜单项统计
$result = $conn->query("SELECT category, COUNT(*) as count FROM food_menu GROUP BY category ORDER BY category");
echo "\n=== 菜单统计 ===\n";
while($row = $result->fetch_assoc()) {
    echo "{$row['category']}: {$row['count']} 项\n";
}

$total = $conn->query("SELECT COUNT(*) as total FROM food_menu")->fetch_assoc();
echo "\n总计: {$total['total']} 个菜单项\n";

$conn->close();
?>
