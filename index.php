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

// Fetch available tables
$conn = getDBConnection();
$sql = "SELECT * FROM tables WHERE available = TRUE ORDER BY table_number";
$result = $conn->query($sql);
$tables = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tables[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>餐厅预订系统 - Restaurant Booking System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍽️ 餐厅预订系统</h1>
            <p>Restaurant Booking & Pre-Order System</p>
            <div style="position: absolute; top: 20px; right: 20px;">
                <a href="admin.php" class="btn btn-primary">⚙️ Admin</a>
            </div>
        </div>
        
        <div class="nav">
            <a href="index.php" class="active">预订餐桌 Booking</a>
            <a href="menu.php">提前点餐 Pre-Order</a>
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
            <h2>预订餐桌 / Book a Table</h2>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-info" style="background: #e7f3ff; border: 2px solid #667eea; color: #004085;">
                <strong>⏰ 营业时间 / Business Hours:</strong><br>
                我们的营业时间是每天 <strong>10:00 AM - 10:00 PM (22:00)</strong><br>
                We are open daily from <strong>10:00 AM to 10:00 PM</strong><br>
                <small style="margin-top: 5px; display: block;">📅 预订时间：11:00 AM - 9:45 PM (21:45 - Last Call) / Booking: 11:00 AM - 9:45 PM (Last Call)<br>
                🍽️ 点餐(Order)：11:00 AM - 9:45 PM (21:45 - Last Call)</small>
            </div>
            
            <form action="process_booking.php" method="POST" id="bookingForm">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;">
                        ✅ 您已登录！信息已自动填写 / You're logged in! Info auto-filled
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="name">姓名 / Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>"
                                   <?php echo isset($_SESSION['user_id']) ? 'readonly style="background: #e9ecef;"' : ''; ?>>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="phone">电话 / Phone *</label>
                            <input type="tel" id="phone" name="phone" required
                                   value="<?php echo isset($_SESSION['user_phone']) ? htmlspecialchars($_SESSION['user_phone']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">电邮 / Email</label>
                    <input type="email" id="email" name="email"
                           value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>"
                           <?php echo isset($_SESSION['user_id']) ? 'readonly style="background: #e9ecef;"' : ''; ?>>
                </div>
                
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="booking_date">预订日期 / Booking Date *</label>
                            <input type="date" id="booking_date" name="booking_date" required>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="booking_time">预订时间 / Booking Time *</label>
                            <input type="time" id="booking_time" name="booking_time" required min="11:00" max="21:45">
                            <small style="color: #667eea; display: block; margin-top: 5px;">
                                ⏰ 餐厅营业：10:00 - 22:00 | 预订时间：11:00 - 21:45 (Last Call)<br>
                                ⏰ Restaurant: 10:00 AM - 10:00 PM | Booking: 11:00 AM - 9:45 PM (Last Call)
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="number_of_guests">人数 / Number of Guests *</label>
                    <select id="number_of_guests" name="number_of_guests" required>
                        <option value="">选择人数 / Select...</option>
                        <option value="1">1 人 / Person</option>
                        <option value="2">2 人 / Persons</option>
                        <option value="3">3 人 / Persons</option>
                        <option value="4">4 人 / Persons</option>
                        <option value="5">5 人 / Persons</option>
                        <option value="6">6 人 / Persons</option>
                        <option value="7">7 人 / Persons</option>
                        <option value="8">8 人 / Persons</option>
                        <option value="9">9 人 / Persons</option>
                        <option value="10">10 人 / Persons</option>
                        <option value="more">10+ 人 / More than 10</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="table_number">选择桌子 / Select Table *</label>
                    
                    <!-- Table availability status display -->
                    <div id="tableAvailabilityStatus" style="display: none; margin-bottom: 15px; padding: 15px; border-radius: 5px;">
                        <!-- Will be populated by JavaScript -->
                    </div>
                    
                    <select id="table_number" name="table_number" required>
                        <option value="">请先选择日期和时间 / Please select date and time first...</option>
                        <?php foreach ($tables as $table): ?>
                            <option value="<?php echo $table['table_number']; ?>" 
                                    data-capacity="<?php echo $table['capacity']; ?>"
                                    data-description="<?php echo htmlspecialchars($table['description']); ?>"
                                    data-table-id="<?php echo $table['table_number']; ?>">
                                桌号 Table #<?php echo $table['table_number']; ?> - 
                                容量 Capacity: <?php echo $table['capacity']; ?>人 - 
                                <?php echo htmlspecialchars($table['description']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small id="tableHint" style="color: #dc3545; display: none; margin-top: 5px;">
                        ⚠️ 此桌子在所选时间已被预订 / This table is already booked at the selected time
                    </small>
                    <small id="capacityHint" style="color: #ffc107; display: none; margin-top: 5px;">
                        💡 提示：此桌子容量为 <span id="capacityValue"></span> 人
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="special_requests">特殊要求 / Special Requests</label>
                    <textarea id="special_requests" name="special_requests" placeholder="例如：需要婴儿椅、轮椅通道、过敏提醒等..."></textarea>
                </div>
                
                <button type="submit" class="btn">提交预订 / Submit Booking</button>
            </form>
        </div>
    </div>
    
    <script>
        // Set minimum date to today
        document.getElementById('booking_date').min = new Date().toISOString().split('T')[0];
        
        // Store current tables availability
        let currentTablesAvailability = {};
        
        // Check all tables availability when date and time are selected
        document.getElementById('booking_date').addEventListener('change', updateTableAvailability);
        document.getElementById('booking_time').addEventListener('change', updateTableAvailability);
        
        function updateTableAvailability() {
            const date = document.getElementById('booking_date').value;
            const time = document.getElementById('booking_time').value;
            const statusDiv = document.getElementById('tableAvailabilityStatus');
            const tableSelect = document.getElementById('table_number');
            const submitButton = document.querySelector('button[type="submit"]');
            
            if (date && time) {
                // Show loading
                statusDiv.style.display = 'block';
                statusDiv.innerHTML = '<p style="text-align: center;">⏳ 正在检查桌子可用性... / Checking table availability...</p>';
                
                // AJAX request to get all tables availability
                fetch('get_available_tables.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `date=${date}&time=${time}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentTablesAvailability = {};
                        
                        // Build the tables status object
                        data.tables.forEach(table => {
                            currentTablesAvailability[table.table_number] = table.available;
                        });
                        
                        // Update table select options
                        updateTableSelectOptions(data.tables);
                        
                        // Display availability status
                        if (data.all_full) {
                            // All tables are full
                            statusDiv.style.background = '#ffebee';
                            statusDiv.style.border = '2px solid #f44336';
                            statusDiv.style.color = '#c62828';
                            statusDiv.innerHTML = `
                                <div style="text-align: center;">
                                    <h3 style="margin: 0 0 10px 0; color: #c62828;">❌ 抱歉，所有桌子已满 / Sorry, All Tables Are Full</h3>
                                    <p style="margin: 5px 0;">在所选的日期和时间（${date} ${time}），所有桌子都已被预订。</p>
                                    <p style="margin: 5px 0;">All tables are booked for the selected date and time (${date} ${time}).</p>
                                    <p style="margin: 10px 0; font-weight: bold;">💡 请选择其他日期或时间 / Please select a different date or time</p>
                                </div>
                            `;
                            tableSelect.disabled = true;
                            submitButton.disabled = true;
                            submitButton.style.opacity = '0.5';
                            submitButton.style.cursor = 'not-allowed';
                        } else {
                            // Some tables are available
                            const availableList = data.tables.filter(t => t.available);
                            const unavailableList = data.tables.filter(t => !t.available);
                            
                            statusDiv.style.background = '#e8f5e9';
                            statusDiv.style.border = '2px solid #4caf50';
                            statusDiv.style.color = '#2e7d32';
                            
                            let html = `
                                <div>
                                    <h4 style="margin: 0 0 10px 0; color: #2e7d32;">
                                        ✅ 有 ${data.available_count} 张桌子可用 / ${data.available_count} Tables Available
                                    </h4>
                            `;
                            
                            if (availableList.length > 0) {
                                html += '<div style="margin: 10px 0;"><strong style="color: #4caf50;">✓ 可用桌子 / Available Tables:</strong><br>';
                                availableList.forEach(table => {
                                    html += `<span style="display: inline-block; margin: 5px 5px; padding: 5px 10px; background: #4caf50; color: white; border-radius: 3px;">
                                        桌号 Table #${table.table_number} (${table.capacity}人) - ${table.description}
                                    </span>`;
                                });
                                html += '</div>';
                            }
                            
                            if (unavailableList.length > 0) {
                                html += '<div style="margin: 10px 0;"><strong style="color: #f44336;">✗ 已满桌子 / Unavailable Tables:</strong><br>';
                                unavailableList.forEach(table => {
                                    html += `<span style="display: inline-block; margin: 5px 5px; padding: 5px 10px; background: #f44336; color: white; border-radius: 3px; opacity: 0.7;">
                                        桌号 Table #${table.table_number} (${table.capacity}人) - ${table.description}
                                    </span>`;
                                });
                                html += '</div>';
                            }
                            
                            html += '</div>';
                            statusDiv.innerHTML = html;
                            
                            tableSelect.disabled = false;
                            submitButton.disabled = false;
                            submitButton.style.opacity = '1';
                            submitButton.style.cursor = 'pointer';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking availability:', error);
                    statusDiv.style.display = 'none';
                });
            } else {
                statusDiv.style.display = 'none';
                // Reset table select
                tableSelect.innerHTML = '<option value="">请先选择日期和时间 / Please select date and time first...</option>';
                const tables = <?php echo json_encode($tables); ?>;
                tables.forEach(table => {
                    const option = document.createElement('option');
                    option.value = table.table_number;
                    option.dataset.capacity = table.capacity;
                    option.dataset.description = table.description;
                    option.textContent = `桌号 Table #${table.table_number} - 容量 Capacity: ${table.capacity}人 - ${table.description}`;
                    tableSelect.appendChild(option);
                });
                tableSelect.disabled = false;
            }
        }
        
        function updateTableSelectOptions(tables) {
            const tableSelect = document.getElementById('table_number');
            const currentValue = tableSelect.value;
            
            // Clear and rebuild options
            tableSelect.innerHTML = '<option value="">选择可用的桌子 / Select an available table...</option>';
            
            tables.forEach(table => {
                const option = document.createElement('option');
                option.value = table.table_number;
                option.dataset.capacity = table.capacity;
                option.dataset.description = table.description;
                option.dataset.tableId = table.table_number;
                
                if (table.available) {
                    option.textContent = `✓ 桌号 Table #${table.table_number} - 容量 ${table.capacity}人 - ${table.description}`;
                    option.style.color = '#4caf50';
                    option.style.fontWeight = 'bold';
                } else {
                    option.textContent = `✗ 桌号 Table #${table.table_number} - 容量 ${table.capacity}人 - ${table.description} (已满 Full)`;
                    option.disabled = true;
                    option.style.color = '#999';
                    option.style.textDecoration = 'line-through';
                }
                
                tableSelect.appendChild(option);
            });
            
            // Restore previous selection if still available
            if (currentValue && currentTablesAvailability[currentValue]) {
                tableSelect.value = currentValue;
            }
        }
        
        // Show table capacity when selected
        document.getElementById('table_number').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const capacity = selectedOption.dataset.capacity;
            const capacityHint = document.getElementById('capacityHint');
            const capacityValue = document.getElementById('capacityValue');
            const tableHint = document.getElementById('tableHint');
            
            if (capacity) {
                capacityValue.textContent = capacity;
                capacityHint.style.display = 'block';
                
                // Check if this table is unavailable
                const tableNumber = parseInt(this.value);
                if (currentTablesAvailability[tableNumber] === false) {
                    tableHint.style.display = 'block';
                    this.style.borderColor = '#dc3545';
                } else {
                    tableHint.style.display = 'none';
                    this.style.borderColor = '#28a745';
                }
            } else {
                capacityHint.style.display = 'none';
                tableHint.style.display = 'none';
            }
        });
        
        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const date = new Date(document.getElementById('booking_date').value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (date < today) {
                e.preventDefault();
                alert('预订日期不能早于今天 / Booking date cannot be earlier than today');
                return;
            }
            
            // Validate booking time (must be between 11:00 and 21:45)
            const bookingTime = document.getElementById('booking_time').value;
            if (bookingTime) {
                const [hours, minutes] = bookingTime.split(':').map(Number);
                const timeInMinutes = hours * 60 + minutes;
                const startTime = 11 * 60; // 11:00
                const endTime = 21 * 60 + 45; // 21:45 (Last Call)
                
                if (timeInMinutes < startTime || timeInMinutes > endTime) {
                    e.preventDefault();
                    alert('预订时间必须在 11:00 - 21:45 之间 (Last Call)\nBooking time must be between 11:00 AM - 9:45 PM (Last Call)');
                    return;
                }
            }
            
            // Check if selected table is available
            const tableNumber = parseInt(document.getElementById('table_number').value);
            if (currentTablesAvailability[tableNumber] === false) {
                e.preventDefault();
                alert('所选桌子已被预订，请选择其他桌子\nThe selected table is booked, please choose another table');
                return;
            }
            
            // Check if capacity warning is displayed (optional warning, not blocking)
            const guests = parseInt(document.getElementById('number_of_guests').value);
            const selectedOption = document.getElementById('table_number').options[document.getElementById('table_number').selectedIndex];
            const capacity = parseInt(selectedOption.dataset.capacity);
            
            if (guests > capacity) {
                if (!confirm(`所选桌子容量为 ${capacity} 人，您有 ${guests} 位客人。是否继续？\nSelected table capacity is ${capacity}, you have ${guests} guests. Continue?`)) {
                    e.preventDefault();
                    return;
                }
            }
        });
    </script>
</body>
</html>
