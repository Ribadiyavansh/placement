<?php
require_once 'config/db.php';

try {
    $conn->exec('ALTER TABLE jobs ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1 AFTER created_at');
    echo "is_active column added successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$cols = $conn->query("DESCRIBE jobs")->fetchAll(PDO::FETCH_ASSOC);
echo "Jobs table structure:\n";
print_r($cols);
?>
