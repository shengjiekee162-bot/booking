<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=my_bookings.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's bookings
$conn = getDBConnection();

$sql = "
    SELECT b.*, 
    (SELECT COUNT(*) FROM food_orders WHERE booking_id = b.id) as has_order,
    (SELECT total_amount FROM food_orders WHERE booking_id = b.id LIMIT 1) as order_amount
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    WHERE c.user_id = ?
    ORDER BY b.created_at DESC, b.booking_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的预订 - My Bookings</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 我的预订</h1>
            <p>My Bookings - <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        </div>
        
        <div class="nav">
            <a href="index.php">预订餐桌 Booking</a>
            <a href="menu.php">提前点餐 Pre-Order</a>
            <a href="view_booking.php">查看预订 View Booking</a>
            <a href="admin.php">管理后台 Admin</a>
            <a href="history.php">历史记录 History</a>
        </div>
        
        <div class="user-status-bar">
            <div class="user-info">
                <span class="user-welcome">👤 欢迎, <?php echo htmlspecialchars($_SESSION['user_name']); ?> / Welcome</span>
                <a href="my_bookings.php" class="user-link" style="background: #4CAF50;">我的预订 My Bookings</a>
                <a href="logout.php" class="user-link logout">登出 Logout</a>
            </div>
        </div>
        
        <div class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>我的所有预订 / All My Bookings</h2>
                <div>
                    <a href="index.php" class="btn btn-success">新建预订 / New Booking</a>
                    <a href="logout.php" class="btn btn-secondary">登出 / Logout</a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($bookings)): ?>
                <div class="alert alert-info" style="text-align: center;">
                    <h3>📭 您还没有任何预订</h3>
                    <p>You don't have any bookings yet</p>
                    <a href="index.php" class="btn" style="margin-top: 15px;">立即预订 / Book Now</a>
                </div>
            <?php else: ?>
                <div class="summary-stats" style="margin-bottom: 30px;">
                    <div class="stat-box">
                        <h4><?php echo count($bookings); ?></h4>
                        <p>总预订 / Total</p>
                    </div>
                    <div class="stat-box">
                        <h4><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed')); ?></h4>
                        <p>已确认 / Confirmed</p>
                    </div>
                    <div class="stat-box">
                        <h4><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'pending')); ?></h4>
                        <p>待确认 / Pending</p>
                    </div>
                    <div class="stat-box">
                        <h4><?php echo count(array_filter($bookings, fn($b) => $b['has_order'] > 0)); ?></h4>
                        <p>含订单 / With Orders</p>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>预订ID / ID</th>
                            <th>日期 / Date</th>
                            <th>时间 / Time</th>
                            <th>人数 / Guests</th>
                            <th>桌号 / Table</th>
                            <th>订单 / Order</th>
                            <th>状态 / Status</th>
                            <th>操作 / Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td><?php echo date('Y-m-d (D)', strtotime($booking['booking_date'])); ?></td>
                                <td><?php echo date('H:i', strtotime($booking['booking_time'])); ?></td>
                                <td><?php echo $booking['number_of_guests']; ?> 人</td>
                                <td>
                                    <?php if ($booking['table_number']): ?>
                                        <strong>#<?php echo $booking['table_number']; ?></strong>
                                    <?php else: ?>
                                        <span style="color: #999;">待分配</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($booking['has_order']): ?>
                                        <span class="status-badge status-confirmed">
                                            ✓ RM <?php echo number_format($booking['order_amount'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed'): ?>
                                            <a href="menu.php?booking_id=<?php echo $booking['id']; ?>" 
                                               class="btn btn-small">点餐 Order</a>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php 
                                        $status_text = [
                                            'pending' => '待确认',
                                            'confirmed' => '已确认',
                                            'cancelled' => '已取消',
                                            'completed' => '已完成'
                                        ];
                                        echo $status_text[$booking['status']] ?? $booking['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_booking.php?booking_id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-small btn-secondary">查看详情 / View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-box h4 {
            font-size: 2em;
            margin-bottom: 10px;
        }
    </style>
</body>
</html>
