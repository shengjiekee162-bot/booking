<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$date = isset($_POST['date']) ? $_POST['date'] : '';
$time = isset($_POST['time']) ? $_POST['time'] : '';

if (empty($date) || empty($time)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$conn = getDBConnection();

// Get all tables
$tables_query = "SELECT table_number, capacity, description FROM tables WHERE available = 1 ORDER BY table_number";
$tables_result = $conn->query($tables_query);

$tables_status = [];
$available_count = 0;

while ($table = $tables_result->fetch_assoc()) {
    $table_number = $table['table_number'];
    
    // Check if this table is booked at the requested time
    // Consider a booking overlaps if it's within 2 hours
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
    
    $is_available = ($result->num_rows === 0);
    
    $tables_status[] = [
        'table_number' => $table_number,
        'capacity' => $table['capacity'],
        'description' => $table['description'],
        'available' => $is_available
    ];
    
    if ($is_available) {
        $available_count++;
    }
    
    $stmt->close();
}

$conn->close();

echo json_encode([
    'success' => true,
    'tables' => $tables_status,
    'available_count' => $available_count,
    'total_count' => count($tables_status),
    'all_full' => ($available_count === 0)
]);
?>
