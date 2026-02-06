<?php
session_start();
require_once 'config.php';

// Handle admin logout
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    header('Location: admin.php');
    exit;
}

$admin_error = '';
$admin_success = '';
$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

// Handle admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $email = trim($_POST['admin_email']);
    $password = $_POST['admin_password'];
    
    if (empty($email) || empty($password)) {
        $admin_error = "请填写邮箱和密码 / Please fill in email and password";
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, password, name, is_admin FROM users WHERE email = ? AND is_admin = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                $is_admin_logged_in = true;
                $admin_success = "登入成功! / Login successful!";
            } else {
                $admin_error = "密码错误 / Incorrect password";
            }
        } else {
            $admin_error = "该邮箱不是管理员账号 / This email is not an admin account";
        }
        $conn->close();
    }
}

// Handle status updates (only if admin is logged in)
if ($is_admin_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $conn = getDBConnection();
        
        if ($_POST['action'] === 'update_booking_status') {
            $booking_id = intval($_POST['booking_id']);
            $status = $_POST['status'];
            $table_number = isset($_POST['table_number']) ? intval($_POST['table_number']) : null;
            
            $stmt = $conn->prepare("UPDATE bookings SET status = ?, table_number = ? WHERE id = ?");
            $stmt->bind_param("sii", $status, $table_number, $booking_id);
            $stmt->execute();
            
            $_SESSION['success_message'] = "预订状态已更新 / Booking status updated";
        }
        
        if ($_POST['action'] === 'update_order_status') {
            $order_id = intval($_POST['order_id']);
            $status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE food_orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $order_id);
            $stmt->execute();
            
            $_SESSION['success_message'] = "订单状态已更新 / Order status updated";
        }
        
        $conn->close();
        header('Location: admin.php');
        exit;
    }
}

// Fetch all bookings with customer info (only if admin is logged in)
$bookings = [];
$orders = [];

if ($is_admin_logged_in) {
    $conn = getDBConnection();

    $sql = "
        SELECT b.*, c.name, c.phone, c.email,
        (SELECT COUNT(*) FROM food_orders WHERE booking_id = b.id) as has_order
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        ORDER BY b.booking_date DESC, b.booking_time DESC
    ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }

    // Fetch all orders
    $sql = "
        SELECT fo.*, b.id as booking_id, b.booking_date, b.booking_time, 
        c.name as customer_name
        FROM food_orders fo
        JOIN bookings b ON fo.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        ORDER BY fo.created_at DESC
    ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }

    $conn->close();
}?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .tabs {
            display: flex;
            border-bottom: 2px solid #667eea;
            margin-bottom: 20px;
        }
        .tab {
            padding: 15px 30px;
            cursor: pointer;
            background: #f8f9fa;
            border: none;
            font-size: 1.1em;
            transition: all 0.3s;
        }
        .tab.active {
            background: #667eea;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .action-form {
            display: inline-block;
            margin: 0 5px;
        }
        .action-form select {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 2em;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍽️ 管理后台</h1>
            <p>Admin Panel - Restaurant Management</p>
        </div>
        
        <div class="nav">
            <a href="index.php">预订餐桌 Booking</a>
            <a href="menu.php">提前点餐 Pre-Order</a>
            <a href="view_booking.php">查看预订 View Booking</a>
            <a href="admin.php" class="active">管理后台 Admin</a>
            <a href="history.php">历史记录 History</a>
        </div>
        
        <div class="user-status-bar">
            <?php if ($is_admin_logged_in): ?>
                <div class="user-info">
                    <span class="user-welcome">👤 欢迎, <?php echo htmlspecialchars($_SESSION['admin_name']); ?> / Welcome (Admin)</span>
                    <a href="index.php" class="user-link">返回首页 Back to Home</a>
                    <a href="admin.php?logout=1" class="user-link logout">登出 Admin Logout</a>
                </div>
            <?php else: ?>
                <div class="user-info">
                    <span class="user-welcome">👋 管理员登入 Admin Login</span>
                    <a href="index.php" class="user-link">返回首页 Back to Home</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="content">
            <?php if (!$is_admin_logged_in): ?>
                <!-- Admin Login Form -->
                <div style="max-width: 500px; margin: 50px auto; padding: 40px; background: #f8f9fa; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1);">
                    <h2 style="text-align: center; margin-bottom: 30px;">🔐 管理员登入</h2>
                    <h3 style="text-align: center; color: #666; margin-bottom: 30px;">Admin Login</h3>
                    
                    <?php if ($admin_error): ?>
                        <div class="alert alert-error" style="margin-bottom: 20px;">
                            <?php echo htmlspecialchars($admin_error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($admin_success): ?>
                        <div class="alert alert-success" style="margin-bottom: 20px;">
                            <?php echo htmlspecialchars($admin_success); ?>
                            <script>
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            </script>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                        <input type="hidden" name="admin_login" value="1">
                        
                        <div>
                            <label for="admin_email" style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">📧 邮箱 / Email:</label>
                            <input type="email" id="admin_email" name="admin_email" 
                                   placeholder="输入管理员邮箱 / Enter admin email"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; box-sizing: border-box;"
                                   required>
                        </div>
                        
                        <div>
                            <label for="admin_password" style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">🔑 密码 / Password:</label>
                            <input type="password" id="admin_password" name="admin_password" 
                                   placeholder="输入密码 / Enter password"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 1em; box-sizing: border-box;"
                                   required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="padding: 12px; font-size: 1.1em; margin-top: 10px;">
                            🚀 登入 / Login
                        </button>
                    </form>
                    
                    <p style="text-align: center; color: #666; margin-top: 20px; font-size: 0.9em;">
                        💡 只有管理员账号可以登入 / Only admin accounts can login
                    </p>
                </div>
            <?php else: ?>
                <!-- Admin Dashboard -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="stats">
                <div class="stat-card">
                    <h3><?php echo count($bookings); ?></h3>
                    <p>总预订 / Total Bookings</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'pending')); ?></h3>
                    <p>待确认 / Pending</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed')); ?></h3>
                    <p>已确认 / Confirmed</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count($orders); ?></h3>
                    <p>总订单 / Total Orders</p>
                </div>
            </div>
            
            <div class="tabs">
                <button class="tab active" onclick="showTab('bookings')">预订管理 / Bookings</button>
                <button class="tab" onclick="showTab('orders')">订单管理 / Orders</button>
            </div>
            
            <div id="bookings" class="tab-content active">
                <h2>预订管理 / Booking Management</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>客户 / Customer</th>
                            <th>电话 / Phone</th>
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
                                <td><?php echo htmlspecialchars($booking['name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['phone']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($booking['booking_date'])); ?></td>
                                <td><?php echo date('H:i', strtotime($booking['booking_time'])); ?></td>
                                <td><?php echo $booking['number_of_guests']; ?></td>
                                <td>
                                    <?php if ($booking['table_number']): ?>
                                        <?php echo $booking['table_number']; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($booking['has_order']): ?>
                                        <span class="status-badge status-confirmed">✓</span>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php 
                                        $booking_status_text = [
                                            'pending' => '待确认',
                                            'confirmed' => '已确认',
                                            'cancelled' => '已取消',
                                            'completed' => '已完成'
                                        ];
                                        echo $booking_status_text[$booking['status']] ?? $booking['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="action-form" style="display: inline-flex; flex-wrap: wrap; gap: 5px; align-items: center;">
                                        <input type="hidden" name="action" value="update_booking_status">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="number" name="table_number" placeholder="桌号 Table" 
                                               value="<?php echo $booking['table_number']; ?>" 
                                               style="width: 70px; padding: 5px;">
                                        <select name="status" style="padding: 5px;">
                                            <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>待确认 Pending</option>
                                            <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>已确认 Confirmed</option>
                                            <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>已取消 Cancelled</option>
                                            <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>已完成 Completed</option>
                                        </select>
                                        <button type="submit" class="btn btn-small btn-success">✓ 更新 Update</button>
                                    </form>
                                    <a href="view_booking.php?booking_id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-small btn-secondary" style="margin-top: 5px;">查看 View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="orders" class="tab-content">
                <h2>订单管理 / Order Management</h2>
                
                <?php if (empty($orders)): ?>
                    <div class="alert alert-info">
                        <p>📦 暂无订单 / No orders yet</p>
                        <p>当客户预订后点餐，订单会显示在这里 / Orders will appear here after customers place food orders with their bookings.</p>
                    </div>
                <?php else: ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>订单ID / Order ID</th>
                            <th>预订ID / Booking ID</th>
                            <th>客户 / Customer</th>
                            <th>预订日期 / Date</th>
                            <th>金额 / Amount</th>
                            <th>状态 / Status</th>
                            <th>下单时间 / Ordered At</th>
                            <th>操作 / Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>#<?php echo $order['booking_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['booking_date'] . ' ' . $order['booking_time'])); ?></td>
                                <td><strong>RM <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php 
                                        $order_status_text = [
                                            'pending' => '待确认',
                                            'confirmed' => '已确认',
                                            'preparing' => '准备中',
                                            'completed' => '已完成',
                                            'cancelled' => '已取消'
                                        ];
                                        echo $order_status_text[$order['status']] ?? $order['status'];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <form method="POST" class="action-form" style="display: inline-block;">
                                        <input type="hidden" name="action" value="update_order_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="status-select">
                                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>待确认 Pending</option>
                                            <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>已确认 Confirmed</option>
                                            <option value="preparing" <?php echo $order['status'] === 'preparing' ? 'selected' : ''; ?>>准备中 Preparing</option>
                                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>已完成 Completed</option>
                                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>已取消 Cancelled</option>
                                        </select>
                                        <button type="submit" class="btn btn-small btn-success" style="margin: 0 5px;">✓ 更新 Update</button>
                                    </form>
                                    <a href="view_booking.php?booking_id=<?php echo $order['booking_id']; ?>" 
                                       class="btn btn-small btn-secondary">查看 View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
                <?php endif; ?>
</body>
</html>
