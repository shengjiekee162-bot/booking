<?php
session_start();
require_once 'config.php';

// 确保session中有is_admin变量 / Ensure is_admin is in session
if (isset($_SESSION['user_id'])) {
    // 如果session中没有is_admin，从数据库中读取 / If is_admin not in session, fetch from database
    if (!isset($_SESSION['is_admin'])) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['is_admin'] = $user['is_admin'] ? true : false;
        }
        $conn->close();
    }
}

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

// Check if current time allows ordering based on booking time
$is_ordering_closed = false;
$booking_time = null;
$cutoff_time_str = null;

if ($booking_id) {
    // Fetch booking details to get booking time
    $stmt = $conn->prepare("
        SELECT b.booking_date, b.booking_time 
        FROM bookings b 
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $booking_result = $stmt->get_result();
    
    if ($booking_result->num_rows > 0) {
        $booking = $booking_result->fetch_assoc();
        $booking_datetime = $booking['booking_date'] . ' ' . $booking['booking_time'];
        $booking_timestamp = strtotime($booking_datetime);
        
        // Calculate cutoff time: booking time minus 1 hour 15 minutes
        $cutoff_timestamp = $booking_timestamp - (1 * 60 * 60 + 15 * 60); // 1 hour 15 minutes
        $current_timestamp = time();
        
        // If current time is after cutoff time, ordering is closed
        $is_ordering_closed = $current_timestamp > $cutoff_timestamp;
        
        // Format cutoff time for display
        $cutoff_time_str = date('H:i', $cutoff_timestamp);
        $booking_time = date('H:i', $booking_timestamp);
    }
}
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
            <a href="admin.php">Admin</a>
            <a href="history.php">历史记录 History</a>
        </div>
        
        <div class="user-status-bar">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-info">
                    <span class="user-welcome">👤 欢迎, <?php echo htmlspecialchars($_SESSION['user_name']); ?> / Welcome</span>
                    <a href="my_bookings.php" class="user-link">我的预订 My Bookings</a>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <a href="admin.php" class="user-link" style="background: #dc3545; color: white;">⚙️ 管理后台 Admin Panel</a>
                    <?php endif; ?>
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
            
            <?php if ($is_ordering_closed): ?>
                <div class="alert alert-error" style="background: #ffebee; border: 2px solid #f44336; color: #c62828;">
                    <h3 style="margin-top: 0; color: #c62828;">❌ 点餐已超时 / Ordering Deadline Passed</h3>
                    <p>抱歉，预订时间为 <?php echo $booking_time; ?>，点餐截止时间是 <?php echo $cutoff_time_str; ?>。</p>
                    <p>Sorry, your booking time is <?php echo $booking_time; ?>, and the deadline to order was <?php echo $cutoff_time_str; ?>.</p>
                    <p><strong>您可以在预订前 1 小时 15 分钟内点餐 / You can order up to 1 hour 15 minutes before your booking time</strong></p>
                </div>
            <?php endif; ?>
            
            <form action="process_order.php" method="POST" id="orderForm" <?php echo $is_ordering_closed ? 'style="opacity: 0.5; pointer-events: none;"' : ''; ?>>
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
