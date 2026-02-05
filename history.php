<?php
session_start();
require_once 'config.php';

// Pagination settings
$records_per_page = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Filter settings
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_table = isset($_GET['table']) ? intval($_GET['table']) : 0;
$search_customer = isset($_GET['search']) ? trim($_GET['search']) : '';

$conn = getDBConnection();

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($filter_status)) {
    $where_conditions[] = "b.status = ?";
    $params[] = $filter_status;
    $param_types .= 's';
}

if (!empty($filter_date)) {
    $where_conditions[] = "b.booking_date = ?";
    $params[] = $filter_date;
    $param_types .= 's';
}

if (!empty($filter_table)) {
    $where_conditions[] = "b.table_number = ?";
    $params[] = $filter_table;
    $param_types .= 'i';
}

if (!empty($search_customer)) {
    $where_conditions[] = "(c.name LIKE ? OR c.phone LIKE ?)";
    $search_term = "%$search_customer%";
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'ss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total records
$count_sql = "
    SELECT COUNT(*) as total
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    $where_clause
";

if (!empty($params)) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($count_sql);
}

$total_records = $result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch bookings with pagination
$sql = "
    SELECT b.*, c.name, c.phone, c.email,
    (SELECT COUNT(*) FROM food_orders WHERE booking_id = b.id) as has_order,
    (SELECT total_amount FROM food_orders WHERE booking_id = b.id LIMIT 1) as order_amount
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    $where_clause
    ORDER BY b.created_at DESC, b.booking_date DESC, b.booking_time DESC
    LIMIT ? OFFSET ?
";

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Get available tables for filter
$tables_result = $conn->query("SELECT DISTINCT table_number FROM tables ORDER BY table_number");
$all_tables = [];
while ($row = $tables_result->fetch_assoc()) {
    $all_tables[] = $row['table_number'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>历史记录 - Booking History</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .filters .row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 8px 15px;
            border: 1px solid #667eea;
            border-radius: 5px;
            text-decoration: none;
            color: #667eea;
            transition: all 0.3s;
        }
        .pagination a:hover {
            background: #667eea;
            color: white;
        }
        .pagination .active {
            background: #667eea;
            color: white;
        }
        .pagination .disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box h4 {
            font-size: 1.8em;
            margin-bottom: 5px;
        }
        .export-btn {
            float: right;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 历史记录</h1>
            <p>Booking History - All Records</p>
        </div>
        
        <div class="nav">
            <a href="index.php">预订餐桌 Booking</a>
            <a href="menu.php">提前点餐 Pre-Order</a>
            <a href="view_booking.php">查看预订 View Booking</a>
            <a href="admin.php">管理后台 Admin</a>
            <a href="history.php" class="active">历史记录 History</a>
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
            <h2>所有预订历史 / All Booking History</h2>
            
            <div class="summary-stats">
                <div class="stat-box">
                    <h4><?php echo $total_records; ?></h4>
                    <p>总记录数 / Total Records</p>
                </div>
                <div class="stat-box">
                    <h4><?php echo $total_pages; ?></h4>
                    <p>总页数 / Total Pages</p>
                </div>
                <div class="stat-box">
                    <h4><?php echo $page; ?></h4>
                    <p>当前页 / Current Page</p>
                </div>
            </div>
            
            <div class="filters">
                <h3>筛选条件 / Filters</h3>
                <form method="GET" action="history.php">
                    <div class="row">
                        <div class="form-group">
                            <label for="search">搜索客户 / Search Customer</label>
                            <input type="text" id="search" name="search" placeholder="姓名或电话 / Name or Phone" value="<?php echo htmlspecialchars($search_customer); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">状态 / Status</label>
                            <select id="status" name="status">
                                <option value="">全部 / All</option>
                                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>待确认 Pending</option>
                                <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>已确认 Confirmed</option>
                                <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>已取消 Cancelled</option>
                                <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>已完成 Completed</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date">日期 / Date</label>
                            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="table">桌号 / Table</label>
                            <select id="table" name="table">
                                <option value="">全部 / All</option>
                                <?php foreach ($all_tables as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $filter_table == $t ? 'selected' : ''; ?>>
                                        桌号 Table #<?php echo $t; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">应用筛选 / Apply Filters</button>
                    <a href="history.php" class="btn btn-secondary">清除筛选 / Clear</a>
                </form>
            </div>
            
            <?php if (empty($bookings)): ?>
                <div class="alert alert-info">
                    没有找到记录 / No records found
                </div>
            <?php else: ?>
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
                            <th>创建时间 / Created</th>
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
                                <td><?php echo $booking['number_of_guests']; ?> 人</td>
                                <td>
                                    <?php if ($booking['table_number']): ?>
                                        <strong>#<?php echo $booking['table_number']; ?></strong>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($booking['has_order']): ?>
                                        <span class="status-badge status-confirmed">
                                            ✓ RM <?php echo number_format($booking['order_amount'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
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
                                        echo $status_text[$booking['status']];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <a href="view_booking.php?booking_id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-small btn-secondary">查看 View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo !empty($filter_status) ? '&status='.$filter_status : ''; ?><?php echo !empty($filter_date) ? '&date='.$filter_date : ''; ?><?php echo !empty($filter_table) ? '&table='.$filter_table : ''; ?><?php echo !empty($search_customer) ? '&search='.urlencode($search_customer) : ''; ?>">
                            &laquo; 首页 First
                        </a>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($filter_status) ? '&status='.$filter_status : ''; ?><?php echo !empty($filter_date) ? '&date='.$filter_date : ''; ?><?php echo !empty($filter_table) ? '&table='.$filter_table : ''; ?><?php echo !empty($search_customer) ? '&search='.urlencode($search_customer) : ''; ?>">
                            &lsaquo; 上一页 Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($filter_status) ? '&status='.$filter_status : ''; ?><?php echo !empty($filter_date) ? '&date='.$filter_date : ''; ?><?php echo !empty($filter_table) ? '&table='.$filter_table : ''; ?><?php echo !empty($search_customer) ? '&search='.urlencode($search_customer) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($filter_status) ? '&status='.$filter_status : ''; ?><?php echo !empty($filter_date) ? '&date='.$filter_date : ''; ?><?php echo !empty($filter_table) ? '&table='.$filter_table : ''; ?><?php echo !empty($search_customer) ? '&search='.urlencode($search_customer) : ''; ?>">
                            下一页 Next &rsaquo;
                        </a>
                        <a href="?page=<?php echo $total_pages; ?><?php echo !empty($filter_status) ? '&status='.$filter_status : ''; ?><?php echo !empty($filter_date) ? '&date='.$filter_date : ''; ?><?php echo !empty($filter_table) ? '&table='.$filter_table : ''; ?><?php echo !empty($search_customer) ? '&search='.urlencode($search_customer) : ''; ?>">
                            末页 Last &raquo;
                        </a>
                    <?php endif; ?>
                </div>
                
                <p style="text-align: center; margin-top: 15px; color: #666;">
                    显示第 <?php echo $offset + 1; ?> - <?php echo min($offset + $records_per_page, $total_records); ?> 条，共 <?php echo $total_records; ?> 条记录
                    <br>
                    Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> records
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
