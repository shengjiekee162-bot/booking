<?php
// Generate admin password hash
$admin_password = "admin123";
$hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);

echo "Admin Password Hash: " . $hashed_password . "\n";
echo "Email: admin@restaurant.com\n";
echo "Password: admin123\n\n";

echo "SQL Insert statement:\n";
echo "INSERT INTO users (email, password, name, phone, is_admin) VALUES ('admin@restaurant.com', '" . $hashed_password . "', 'Administrator', '6012345678', 1);\n";
?>
