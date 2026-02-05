<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123qwe');
define('DB_NAME', 'booking_jie');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Time zone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Business hours configuration
define('RESTAURANT_OPEN_TIME', '10:00');  // 餐厅开门时间 / Restaurant opens
define('BOOKING_START_TIME', '11:00');    // 预订开始时间 / Booking start time
define('BOOKING_END_TIME', '21:45');      // 预订结束时间 / Booking end time (Last Call)
define('RESTAURANT_CLOSE_TIME', '22:00'); // 餐厅关门时间 / Restaurant closes
?>
