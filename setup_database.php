<?php
/**
 * AIU Club Store - Database Setup / Update Script
 *
 * Run this once after importing database.sql to verify the schema
 * and create the club_admins table if it is missing.
 *
 * Usage: http://localhost/aiu-club-store/setup_database.php
 */

require_once __DIR__ . '/config/database.php';

$db = database();
$messages = [];

// Ensure database exists.
$db->query('CREATE DATABASE IF NOT EXISTS aiu_club_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db->select_db('aiu_club_store');

// Helper to check table existence.
function table_exists(mysqli $db, string $name): bool
{
    $stmt = $db->prepare('SHOW TABLES LIKE ?');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result && $result->num_rows > 0;
}

// Create club_admins table if missing.
if (!table_exists($db, 'club_admins')) {
    $sql = "
    CREATE TABLE club_admins (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id INT UNSIGNED NOT NULL,
      club_id INT UNSIGNED NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY unique_user_club (user_id, club_id),
      CONSTRAINT fk_club_admins_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
      CONSTRAINT fk_club_admins_club
        FOREIGN KEY (club_id) REFERENCES clubs(id)
        ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";
    if ($db->query($sql)) {
        $messages[] = '<span style="color:green;">✔ club_admins table created successfully.</span>';
    } else {
        $messages[] = '<span style="color:red;">✘ Failed to create club_admins table: ' . htmlspecialchars($db->error) . '</span>';
    }
} else {
    $messages[] = '<span style="color:green;">✔ club_admins table already exists.</span>';
}

// Verify all expected tables.
$expectedTables = ['users', 'clubs', 'club_admins', 'products', 'product_sizes', 'events', 'orders', 'order_items', 'tickets', 'payments'];
foreach ($expectedTables as $table) {
    if (table_exists($db, $table)) {
        $count = (int) $db->query('SELECT COUNT(*) AS total FROM `' . $table . '`')->fetch_assoc()['total'];
        $messages[] = '<span style="color:green;">✔ Table <strong>' . $table . '</strong> exists (' . $count . ' rows).</span>';
    } else {
        $messages[] = '<span style="color:red;">✘ Table <strong>' . $table . '</strong> is missing.</span>';
    }
}

// Output results.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Setup | AIU Club Store</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; background: #f7fafc; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; }
        ul { list-style: none; padding: 0; }
        li { padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0; }
        .back { display: inline-block; margin-top: 1rem; padding: 0.5rem 1rem; background: #e53e3e; color: #fff; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Setup Results</h1>
        <ul>
            <?php foreach ($messages as $msg) { echo '<li>' . $msg . '</li>'; } ?>
        </ul>
        <a class="back" href="admin.php">&larr; Back to Admin</a>
    </div>
</body>
</html>