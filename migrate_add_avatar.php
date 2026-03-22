<?php
/**
 * Migration: Add avatar column to users table
 * Run this once to migrate the database
 */
require_once 'config.php';

try {
    // Check if avatar column exists
    $stmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Column doesn't exist, add it
        $alter = "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT 'default.jpg' AFTER phone";
        $conn->exec($alter);
        echo "✓ Avatar column added successfully to users table";
    } else {
        echo "✓ Avatar column already exists";
    }
    
    // Create img/avatars folder if it doesn't exist
    if (!is_dir('img/avatars')) {
        mkdir('img/avatars', 0755, true);
        echo "\n✓ Created img/avatars folder";
    } else {
        echo "\n✓ img/avatars folder already exists";
    }
    
} catch (Exception $e) {
    echo "✗ Migration error: " . htmlspecialchars($e->getMessage());
    exit(1);
}

echo "\n\n✓ Migration completed successfully!";
