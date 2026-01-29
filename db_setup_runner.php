<?php
require_once 'config/db.php';

try {
    $sql = file_get_contents('resume_schema.sql');
    if ($sql === false) {
        throw new Exception("Could not read resume_schema.sql");
    }

    $conn->exec($sql);
    echo "Database schema updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
