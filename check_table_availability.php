<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['available' => true]);
    exit;
}

$date = isset($_POST['date']) ? $_POST['date'] : '';
$time = isset($_POST['time']) ? $_POST['time'] : '';
$table_number = isset($_POST['table_number']) ? intval($_POST['table_number']) : 0;

if (empty($date) || empty($time) || empty($table_number)) {
    echo json_encode(['available' => true]);
    exit;
}

$conn = getDBConnection();

// Check if table is already booked at this date and time
// Consider a booking overlaps if it's within 2 hours of the requested time
$stmt = $conn->prepare("
    SELECT b.id, b.booking_time, c.name 
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    WHERE b.table_number = ? 
    AND b.booking_date = ? 
    AND b.status IN ('pending', 'confirmed')
    AND ABS(TIMESTAMPDIFF(MINUTE, b.booking_time, ?)) < 120
");

$stmt->bind_param("iss", $table_number, $date, $time);
$stmt->execute();
$result = $stmt->get_result();

$available = ($result->num_rows === 0);
$conflicting_bookings = [];

if (!$available) {
    while ($row = $result->fetch_assoc()) {
        $conflicting_bookings[] = [
            'id' => $row['id'],
            'time' => $row['booking_time'],
            'customer' => $row['name']
        ];
    }
}

$conn->close();

echo json_encode([
    'available' => $available,
    'conflicts' => $conflicting_bookings
]);
?>
