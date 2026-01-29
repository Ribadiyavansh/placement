<?php
// Manual connection to avoid localhost socket issues in CLI
$host = '127.0.0.1';
$db_name = 'placement_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents('resume_schema.sql');
    if ($sql === false) {
        throw new Exception("Could not read resume_schema.sql");
    }

    $conn->exec($sql);
    echo "Database schema updated successfully.\n";
} catch(PDOException $e) {
    die("Connection/Execution failed: " . $e->getMessage());
} catch(Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
