<?php
/**
 * Migration: Add admin management columns to orders and order_details tables
 */
require_once __DIR__ . '/../config.php';

try {
    $added = [];
    
    // Check and add admin_notes column to orders
    $stmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'orders' AND COLUMN_NAME = 'admin_notes'");
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN admin_notes TEXT AFTER note");
        $added[] = "✓ Added admin_notes to orders table";
    }
    
    // Check and add returned_at column to orders
    $stmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'orders' AND COLUMN_NAME = 'returned_at'");
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN returned_at DATETIME NULL AFTER admin_notes");
        $added[] = "✓ Added returned_at to orders table";
    }
    
    // Check and add status column to order_details
    $stmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'order_details' AND COLUMN_NAME = 'status'");
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        $conn->exec("ALTER TABLE order_details ADD COLUMN status ENUM('pending', 'collected', 'in-transit', 'in-use', 'returned') DEFAULT 'pending'");
        $added[] = "✓ Added status to order_details table";
    }
    
    // Create api directory if it doesn't exist
    $apiDir = __DIR__ . '/api';
    if (!is_dir($apiDir)) {
        mkdir($apiDir, 0755, true);
        $added[] = "✓ Created admin/api/ directory";
    }
    
    if (!empty($added)) {
        echo implode("\n", $added);
        echo "\n\n✓ Migration completed successfully!";
    } else {
        echo "✓ All columns already exist. No changes needed.";
    }
    
} catch (Exception $e) {
    echo "✗ Migration error: " . htmlspecialchars($e->getMessage());
    exit(1);
}
