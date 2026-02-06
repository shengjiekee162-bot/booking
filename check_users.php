<?php
require_once 'config.php';

$conn = getDBConnection();
$result = $conn->query('SELECT id, email, name, is_admin FROM users');

if ($result->num_rows > 0) {
    echo "现有用户:\n";
    while($row = $result->fetch_assoc()) {
        echo 'ID: ' . $row['id'] . ' | Email: ' . $row['email'] . ' | Name: ' . $row['name'] . ' | Admin: ' . ($row['is_admin'] ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "数据库中没有任何用户账号\n";
}

$conn->close();
?>
