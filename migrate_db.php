<?php
/**
 * 数据库迁移脚本 / Database Migration Script
 * 为customers表添加user_id列（如果不存在）
 * 为users表添加is_admin列（如果不存在）
 * Adds user_id column to customers table if it doesn't exist
 * Adds is_admin column to users table if it doesn't exist
 */

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    echo "🔧 正在检查数据库结构... / Checking database structure...\n\n";
    
    // Check if user_id column exists in customers table
    $check_column = $conn->query("SHOW COLUMNS FROM customers LIKE 'user_id'");
    
    if ($check_column->num_rows === 0) {
        echo "⏳ 添加用户ID列... / Adding user_id column...\n";
        
        // Add user_id column
        $add_column = "ALTER TABLE customers ADD COLUMN user_id INT NULL AFTER id";
        if ($conn->query($add_column) === TRUE) {
            echo "✅ user_id 列添加成功 / user_id column added successfully\n";
        } else {
            echo "❌ 错误 / Error: " . $conn->error . "\n";
            exit(1);
        }
        
        // Add foreign key constraint
        echo "⏳ 添加外键约束... / Adding foreign key constraint...\n";
        $add_fk = "ALTER TABLE customers ADD CONSTRAINT fk_customers_user_id 
                   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL";
        
        if ($conn->query($add_fk) === TRUE) {
            echo "✅ 外键约束添加成功 / Foreign key constraint added successfully\n";
        } else {
            // Foreign key might already exist, that's okay
            echo "⚠️ 外键可能已存在 / Foreign key may already exist (this is okay)\n";
        }
        
    } else {
        echo "✅ user_id 列已存在，无需迁移 / user_id column already exists, no migration needed\n";
    }
    
    // Check if is_admin column exists in users table
    $check_admin = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    
    if ($check_admin->num_rows === 0) {
        echo "\n⏳ 添加管理员标记列... / Adding is_admin column...\n";
        
        // Add is_admin column
        $add_admin = "ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE";
        if ($conn->query($add_admin) === TRUE) {
            echo "✅ is_admin 列添加成功 / is_admin column added successfully\n";
        } else {
            echo "❌ 错误 / Error: " . $conn->error . "\n";
            exit(1);
        }
    } else {
        echo "✅ is_admin 列已存在 / is_admin column already exists\n";
    }
    
    echo "\n✅ 数据库迁移完成！/ Database migration completed!\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ 错误 / Error: " . $e->getMessage();
    exit(1);
}
?>
