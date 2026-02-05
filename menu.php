<?php
session_start();
require_once 'config.php';

// Get booking ID from session or query parameter
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : (isset($_SESSION['booking_id']) ? $_SESSION['booking_id'] : null);

// Fetch menu items
$conn = getDBConnection();
$sql = "SELECT * FROM food_menu WHERE available = TRUE ORDER BY category, name";
$result = $conn->query($sql);

$menu_items = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $menu_items[] = $row;
    }
}

// Group by category
$grouped_menu = [];
foreach ($menu_items as $item) {
    $grouped_menu[$item['category']][] = $item;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>菜单 - Menu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍽️ 餐厅预订系统</h1>
            <p>Restaurant Booking & Pre-Order System</p>
        </div>
        
        <div class="nav">
            <a href="index.php">预订餐桌 Booking</a>
            <a href="menu.php" class="active">提前点餐 Pre-Order</a>
            <a href="view_booking.php">查看预订 View Booking</a>
            <a href="admin.php">管理后台 Admin</a>
            <a href="history.php">历史记录 History</a>
        </div>
        
        <div class="user-status-bar">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-info">
                    <span class="user-welcome">👤 欢迎, <?php echo htmlspecialchars($_SESSION['user_name']); ?> / Welcome</span>
                    <a href="my_bookings.php" class="user-link">我的预订 My Bookings</a>
                    <a href="logout.php" class="user-link logout">登出 Logout</a>
                </div>
            <?php else: ?>
                <div class="user-info">
                    <span class="user-welcome">👋 您好 Hello!</span>
                    <a href="login.php" class="user-link">登录 Login</a>
                    <a href="register.php" class="user-link">注册 Register</a>
                    <span class="guest-note">💡 可选：登录后查看历史预订</span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="content">
            <h2>菜单 / Food Menu</h2>
            
            <?php if ($booking_id): ?>
                <div class="alert alert-info">
                    预订编号 / Booking ID: <strong>#<?php echo $booking_id; ?></strong>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    请先预订餐桌再点餐 / Please book a table first before ordering food.
                    <a href="index.php" style="color: #721c24; text-decoration: underline; font-weight: bold;">前往预订 / Go to Booking</a>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form action="process_order.php" method="POST" id="orderForm">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                
                <?php foreach ($grouped_menu as $category => $items): ?>
                    <h3 style="margin-top: 30px; color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                        <?php echo htmlspecialchars($category); ?>
                    </h3>
                    
                    <div class="menu-grid">
                        <?php foreach ($items as $item): ?>
                            <div class="menu-item">
                                <span class="category"><?php echo htmlspecialchars($item['category']); ?></span>
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="description"><?php echo htmlspecialchars($item['description']); ?></p>
                                <div class="price">RM <?php echo number_format($item['price'], 2); ?></div>
                                
                                <div class="quantity-control">
                                    <label>数量 / Qty:</label>
                                    <input type="number" 
                                           name="items[<?php echo $item['id']; ?>]" 
                                           min="0" 
                                           value="0" 
                                           class="item-quantity"
                                           data-price="<?php echo $item['price']; ?>"
                                           data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($booking_id): ?>
                    <div style="margin-top: 30px; text-align: center;">
                        <button type="submit" class="btn">提交订单 / Submit Order</button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="cart-summary" id="cartSummary" style="display: none;">
        <h3>购物车 / Cart</h3>
        <div id="cartItems"></div>
        <div class="cart-total">
            总计 / Total: <span id="cartTotal">RM 0.00</span>
        </div>
    </div>
    
    <script>
        const quantities = document.querySelectorAll('.item-quantity');
        const cartSummary = document.getElementById('cartSummary');
        const cartItems = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');
        
        function updateCart() {
            let total = 0;
            let itemsHtml = '';
            let hasItems = false;
            
            quantities.forEach(input => {
                const qty = parseInt(input.value) || 0;
                if (qty > 0) {
                    hasItems = true;
                    const price = parseFloat(input.dataset.price);
                    const name = input.dataset.name;
                    const subtotal = qty * price;
                    total += subtotal;
                    
                    itemsHtml += `
                        <div class="cart-item">
                            <span>${name} x${qty}</span>
                            <span>RM ${subtotal.toFixed(2)}</span>
                        </div>
                    `;
                }
            });
            
            cartItems.innerHTML = itemsHtml;
            cartTotal.textContent = `RM ${total.toFixed(2)}`;
            cartSummary.style.display = hasItems ? 'block' : 'none';
        }
        
        quantities.forEach(input => {
            input.addEventListener('change', updateCart);
            input.addEventListener('input', updateCart);
        });
        
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            let hasItems = false;
            quantities.forEach(input => {
                if (parseInt(input.value) > 0) hasItems = true;
            });
            
            if (!hasItems) {
                e.preventDefault();
                alert('请至少选择一个菜品 / Please select at least one item');
            }
        });
    </script>
</body>
</html>
