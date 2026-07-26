<?php
// config.php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Your MySQL username
define('DB_PASSWORD', '');     // Your MySQL password
define('DB_NAME', 'pharmacore_db'); 

try {
    // 1. First, connect to MySQL Server WITHOUT selecting a database
    $pdo = new PDO("mysql:host=" . DB_SERVER, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create the database if it doesn't exist yet
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    
    // 3. Now, switch to using our specific database
    $pdo->exec("USE " . DB_NAME);

    // 4. Create the users table automatically if it doesn't exist
    $tableSql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";
    
    $pdo->exec($tableSql);

} catch(PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>