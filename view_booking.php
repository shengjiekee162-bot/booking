<?php
session_start();
require_once 'config.php';

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : (isset($_SESSION['booking_id']) ? $_SESSION['booking_id'] : null);
$booking = null;
$order = null;
$order_items = [];

if ($booking_id) {
    $conn = getDBConnection();
    
    // Get booking details with customer info
    $stmt = $conn->prepare("
        SELECT b.*, c.name, c.phone, c.email 
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        
        // Get food order if exists
        $stmt = $conn->prepare("SELECT * FROM food_orders WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            
            // Get order items
            $stmt = $conn->prepare("
                SELECT oi.*, fm.name as food_name 
                FROM order_items oi
                JOIN food_menu fm ON oi.food_item_id = fm.id
                WHERE oi.order_id = ?
            ");
            $stmt->bind_param("i", $order['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $order_items[] = $row;
            }
        }
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查看预订 - View Booking</title>
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
            <a href="menu.php">提前点餐 Pre-Order</a>
            <a href="view_booking.php" class="active">查看预订 View Booking</a>
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
            <h2>查看预订详情 / View Booking Details</h2>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$booking_id): ?>
                <div class="alert alert-info">
                    <p>请输入您的预订编号或电话号码查询预订信息</p>
                    <p>Please enter your booking ID or phone number to view your booking</p>
                </div>
                
                <form action="view_booking.php" method="GET">
                    <div class="form-group">
                        <label for="booking_id">预订编号 / Booking ID</label>
                        <input type="number" id="booking_id" name="booking_id" placeholder="例如: 1">
                    </div>
                    <button type="submit" class="btn">查询 / Search</button>
                </form>
                
            <?php elseif (!$booking): ?>
                <div class="alert alert-error">
                    <p>找不到预订信息 / Booking not found</p>
                    <a href="view_booking.php" class="btn btn-secondary btn-small">返回 / Back</a>
                </div>
                
            <?php else: ?>
                <div class="booking-info">
                    <h3>预订信息 / Booking Information</h3>
                    <p><strong>预订编号 / Booking ID:</strong> #<?php echo $booking['id']; ?></p>
                    <p><strong>姓名 / Name:</strong> <?php echo htmlspecialchars($booking['name']); ?></p>
                    <p><strong>电话 / Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?></p>
                    <?php if ($booking['email']): ?>
                        <p><strong>电邮 / Email:</strong> <?php echo htmlspecialchars($booking['email']); ?></p>
                    <?php endif; ?>
                    <p><strong>日期 / Date:</strong> <?php echo date('Y-m-d (D)', strtotime($booking['booking_date'])); ?></p>
                    <p><strong>时间 / Time:</strong> <?php echo date('h:i A', strtotime($booking['booking_time'])); ?></p>
                    <p><strong>人数 / Guests:</strong> <?php echo $booking['number_of_guests']; ?> 人</p>
                    <?php if ($booking['table_number']): ?>
                        <p><strong>桌号 / Table:</strong> <?php echo $booking['table_number']; ?></p>
                    <?php endif; ?>
                    <p><strong>状态 / Status:</strong> 
                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                            <?php 
                            $status_text = [
                                'pending' => '待确认 / Pending',
                                'confirmed' => '已确认 / Confirmed',
                                'cancelled' => '已取消 / Cancelled',
                                'completed' => '已完成 / Completed'
                            ];
                            echo $status_text[$booking['status']];
                            ?>
                        </span>
                    </p>
                    <?php if ($booking['special_requests']): ?>
                        <p><strong>特殊要求 / Special Requests:</strong><br>
                        <?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                    <?php endif; ?>
                    <p><strong>预订时间 / Booked At:</strong> <?php echo date('Y-m-d H:i:s', strtotime($booking['created_at'])); ?></p>
                </div>
                
                <?php if ($order): ?>
                    <h3 style="margin-top: 30px; color: #667eea;">餐点订单 / Food Order</h3>
                    <div class="booking-info">
                        <p><strong>订单编号 / Order ID:</strong> #<?php echo $order['id']; ?></p>
                        <p><strong>订单状态 / Status:</strong> 
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php 
                                $order_status_text = [
                                    'pending' => '待确认 / Pending',
                                    'confirmed' => '已确认 / Confirmed',
                                    'preparing' => '准备中 / Preparing',
                                    'completed' => '已完成 / Completed',
                                    'cancelled' => '已取消 / Cancelled'
                                ];
                                echo $order_status_text[$order['status']];
                                ?>
                            </span>
                        </p>
                        
                        <h4 style="margin-top: 20px;">订单明细 / Order Details:</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>菜品 / Item</th>
                                    <th>数量 / Qty</th>
                                    <th>单价 / Price</th>
                                    <th>小计 / Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['food_name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>RM <?php echo number_format($item['price'], 2); ?></td>
                                        <td>RM <?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background: #f8f9fa; font-weight: bold;">
                                    <td colspan="3" style="text-align: right;">总计 / Total:</td>
                                    <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" style="margin-top: 30px;">
                        <p>您还没有点餐 / You haven't ordered any food yet</p>
                        <a href="menu.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-small">现在点餐 / Order Now</a>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 30px;">
                    <a href="view_booking.php" class="btn btn-secondary">查询其他预订 / Search Another Booking</a>
                    <a href="index.php" class="btn btn-secondary">新建预订 / New Booking</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
