<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Get form data
$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$email = trim($_POST['email']);
$booking_date = $_POST['booking_date'];
$booking_time = $_POST['booking_time'];
$number_of_guests = $_POST['number_of_guests'];
$table_number = isset($_POST['table_number']) ? intval($_POST['table_number']) : null;
$special_requests = trim($_POST['special_requests']);

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = "姓名不能为空 / Name is required";
}

if (empty($phone)) {
    $errors[] = "电话不能为空 / Phone is required";
}

if (empty($booking_date)) {
    $errors[] = "预订日期不能为空 / Booking date is required";
}

if (empty($booking_time)) {
    $errors[] = "预订时间不能为空 / Booking time is required";
}

if (empty($number_of_guests)) {
    $errors[] = "人数不能为空 / Number of guests is required";
}

if (empty($table_number)) {
    $errors[] = "请选择桌子 / Please select a table";
}

// Check if booking date is not in the past
$booking_datetime = strtotime($booking_date . ' ' . $booking_time);
if ($booking_datetime < time()) {
    $errors[] = "预订时间不能早于现在 / Booking time cannot be in the past";
}

// Validate business hours (11:00 - 21:30 Last Call)
$time_parts = explode(':', $booking_time);
$booking_hour = intval($time_parts[0]);
$booking_minute = intval($time_parts[1]);
$booking_time_in_minutes = $booking_hour * 60 + $booking_minute;
$start_time = 11 * 60; // 11:00
$end_time = 21 * 60 + 30; // 21:30 (Last Call)

if ($booking_time_in_minutes < $start_time || $booking_time_in_minutes > $end_time) {
    $errors[] = "预订时间必须在营业时间内 (11:00 - 21:30 Last Call) / Booking time must be within business hours (11:00 AM - 9:30 PM Last Call)";
}

// Check if there are any validation errors so far
if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
    header('Location: index.php');
    exit;
}

// Connect to database to check table availability
$conn = getDBConnection();

// Check if table is already booked at this time (within 2 hours)
$stmt = $conn->prepare("
    SELECT b.id, c.name, b.booking_time 
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    WHERE b.table_number = ? 
    AND b.booking_date = ? 
    AND b.status IN ('pending', 'confirmed')
    AND ABS(TIMESTAMPDIFF(MINUTE, b.booking_time, ?)) < 120
");

$stmt->bind_param("iss", $table_number, $booking_date, $booking_time);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $conflict = $result->fetch_assoc();
    $errors[] = "抱歉，桌号 #$table_number 在所选时间已被预订。<br>Sorry, Table #$table_number is already booked at the selected time.<br>已预订时间 Booked time: " . date('H:i', strtotime($conflict['booking_time']));
}

if (!empty($errors)) {
    $conn->close();
    $_SESSION['error_message'] = implode('<br>', $errors);
    header('Location: index.php');
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get logged in user_id if exists
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Check if customer exists
    if ($user_id) {
        // For logged in users, check by user_id first
        $stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // For guests, check by phone
        $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ? AND user_id IS NULL");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    if ($result->num_rows > 0) {
        // Customer exists, get ID
        $row = $result->fetch_assoc();
        $customer_id = $row['id'];
        
        // Update customer info
        if ($user_id) {
            $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $email, $phone, $customer_id);
        } else {
            $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $email, $customer_id);
        }
        $stmt->execute();
    } else {
        // Create new customer
        if ($user_id) {
            $stmt = $conn->prepare("INSERT INTO customers (user_id, name, phone, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $name, $phone, $email);
        } else {
            $stmt = $conn->prepare("INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $phone, $email);
        }
        $stmt->execute();
        $customer_id = $conn->insert_id;
    }
    
    // Create booking
    $stmt = $conn->prepare("INSERT INTO bookings (customer_id, booking_date, booking_time, number_of_guests, table_number, special_requests, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isssis", $customer_id, $booking_date, $booking_time, $number_of_guests, $table_number, $special_requests);
    $stmt->execute();
    $booking_id = $conn->insert_id;
    
    // Commit transaction
    $conn->commit();
    
    // Store booking ID in session
    $_SESSION['booking_id'] = $booking_id;
    $_SESSION['success_message'] = "预订成功！预订编号: #$booking_id<br>Booking successful! Booking ID: #$booking_id<br><a href='menu.php?booking_id=$booking_id' style='color: #155724; text-decoration: underline; font-weight: bold;'>现在点餐 / Order Food Now</a>";
    
    header('Location: index.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error_message'] = "预订失败，请重试 / Booking failed, please try again. Error: " . $e->getMessage();
    header('Location: index.php');
    exit;
} finally {
    $conn->close();
}
?>
