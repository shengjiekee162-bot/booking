<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}

$booking_id = intval($_POST['booking_id']);
$items = isset($_POST['items']) ? $_POST['items'] : [];

// Validation
if (empty($booking_id)) {
    $_SESSION['error_message'] = "无效的预订 / Invalid booking";
    header('Location: index.php');
    exit;
}

if (empty($items)) {
    $_SESSION['error_message'] = "请至少选择一个菜品 / Please select at least one item";
    header('Location: menu.php?booking_id=' . $booking_id);
    exit;
}

$conn = getDBConnection();

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Verify booking exists
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND status = 'pending'");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("预订不存在或已处理 / Booking not found or already processed");
    }
    
    // Calculate total and prepare order items
    $total_amount = 0;
    $order_items = [];
    
    foreach ($items as $food_id => $quantity) {
        $quantity = intval($quantity);
        if ($quantity > 0) {
            // Get food item details
            $stmt = $conn->prepare("SELECT id, name, price, available FROM food_menu WHERE id = ?");
            $stmt->bind_param("i", $food_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $food = $result->fetch_assoc();
                if ($food['available']) {
                    $subtotal = $food['price'] * $quantity;
                    $total_amount += $subtotal;
                    $order_items[] = [
                        'food_id' => $food_id,
                        'quantity' => $quantity,
                        'price' => $food['price']
                    ];
                }
            }
        }
    }
    
    if (empty($order_items)) {
        throw new Exception("没有有效的菜品 / No valid items in order");
    }
    
    // Create food order
    $stmt = $conn->prepare("INSERT INTO food_orders (booking_id, total_amount, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("id", $booking_id, $total_amount);
    $stmt->execute();
    $order_id = $conn->insert_id;
    
    // Insert order items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, food_item_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($order_items as $item) {
        $stmt->bind_param("iiid", $order_id, $item['food_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "订单提交成功！订单编号: #$order_id, 总计: RM " . number_format($total_amount, 2) . "<br>Order submitted successfully! Order ID: #$order_id, Total: RM " . number_format($total_amount, 2);
    header('Location: view_booking.php?booking_id=' . $booking_id);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error_message'] = "订单提交失败 / Order submission failed: " . $e->getMessage();
    header('Location: menu.php?booking_id=' . $booking_id);
    exit;
} finally {
    $conn->close();
}
?>
