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
    $usersTableSql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";
    
    $pdo->exec($usersTableSql);

    // 5. Create the medicines table automatically if it doesn't exist
    $medicinesTableSql = "CREATE TABLE IF NOT EXISTS medicines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        category VARCHAR(100),
        dosage VARCHAR(50),
        batch_number VARCHAR(100),
        manufacture_date DATE,
        expiry_date DATE NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        min_stock INT DEFAULT 10,
        price DECIMAL(10,2) DEFAULT 0.00,
        supplier VARCHAR(150),
        location VARCHAR(100),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";

    $pdo->exec($medicinesTableSql);

} catch(PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>