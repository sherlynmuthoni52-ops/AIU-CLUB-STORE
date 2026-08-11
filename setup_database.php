<?php
/**
 * AIU Club Store - Database Setup Script
 *
 * This script creates the database and all required tables,
 * then seeds initial data. Run this once if the database is empty
 * or missing the club_admins table.
 *
 * Usage: http://localhost/aiu-club-store/setup_database.php
 */

// Direct database connection for setup (bypasses app config if DB doesn't exist yet).
$db = new mysqli('localhost', 'root', '', '');
if ($db->connect_error) {
    exit('Database connection failed: ' . htmlspecialchars($db->connect_error));
}

$messages = [];

// Ensure database exists with correct charset.
$db->query('CREATE DATABASE IF NOT EXISTS aiu_club_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db->select_db('aiu_club_store');

// Helper: check table existence using direct query.
function table_exists(mysqli $db, string $name): bool
{
    $name = $db->real_escape_string($name);
    $result = $db->query("SHOW TABLES LIKE '{$name}'");
    return $result && $result->num_rows > 0;
}

// Helper: run SQL and report status.
function run_sql(mysqli $db, string $sql, string $label): void
{
    global $messages;
    if ($db->query($sql) === true) {
        $messages[] = '<span style="color:green;">✔ ' . $label . '</span>';
    } else {
        $messages[] = '<span style="color:red;">✘ ' . $label . ': ' . htmlspecialchars($db->error) . '</span>';
    }
}

// -----------------------------------------------------------------------------
// Step 1: Create all tables
// -----------------------------------------------------------------------------

$tables = [
    'users' => "
    CREATE TABLE IF NOT EXISTS users (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      email VARCHAR(150) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL,
      role ENUM('student', 'club_admin', 'super_admin') NOT NULL DEFAULT 'student'
    ) ENGINE=InnoDB;
    ",
    'clubs' => "
    CREATE TABLE IF NOT EXISTS clubs (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(120) NOT NULL UNIQUE,
      description TEXT,
      logo VARCHAR(255)
    ) ENGINE=InnoDB;
    ",
    'club_admins' => "
    CREATE TABLE IF NOT EXISTS club_admins (
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
    ",
    'products' => "
    CREATE TABLE IF NOT EXISTS products (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      club_id INT UNSIGNED NOT NULL,
      name VARCHAR(150) NOT NULL,
      price DECIMAL(10,2) NOT NULL,
      image VARCHAR(255),
      stock INT UNSIGNED NOT NULL DEFAULT 0,
      category VARCHAR(100) NOT NULL,
      CONSTRAINT fk_products_club
        FOREIGN KEY (club_id) REFERENCES clubs(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB;
    ",
    'product_sizes' => "
    CREATE TABLE IF NOT EXISTS product_sizes (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      product_id INT UNSIGNED NOT NULL,
      size VARCHAR(20) NOT NULL,
      stock INT UNSIGNED NOT NULL DEFAULT 0,
      UNIQUE KEY unique_product_size (product_id, size),
      CONSTRAINT fk_product_sizes_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ",
    'events' => "
    CREATE TABLE IF NOT EXISTS events (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      club_id INT UNSIGNED NOT NULL,
      title VARCHAR(150) NOT NULL,
      description TEXT,
      venue VARCHAR(150) NOT NULL,
      `date` DATETIME NOT NULL,
      capacity INT UNSIGNED NOT NULL,
      ticket_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      CONSTRAINT fk_events_club
        FOREIGN KEY (club_id) REFERENCES clubs(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB;
    ",
    'orders' => "
    CREATE TABLE IF NOT EXISTS orders (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id INT UNSIGNED NOT NULL,
      total_amount DECIMAL(10,2) NOT NULL,
      payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
      order_status ENUM('pending', 'processing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB;
    ",
    'order_items' => "
    CREATE TABLE IF NOT EXISTS order_items (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      order_id INT UNSIGNED NOT NULL,
      product_id INT UNSIGNED NOT NULL,
      quantity INT UNSIGNED NOT NULL,
      price DECIMAL(10,2) NOT NULL,
      CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
      CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB;
    ",
    'tickets' => "
    CREATE TABLE IF NOT EXISTS tickets (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      event_id INT UNSIGNED NOT NULL,
      user_id INT UNSIGNED NOT NULL,
      ticket_code VARCHAR(64) NOT NULL UNIQUE,
      qr_code VARCHAR(255),
      payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
      checked_in TINYINT(1) NOT NULL DEFAULT 0,
      CONSTRAINT fk_tickets_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
      CONSTRAINT fk_tickets_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
    ) ENGINE=InnoDB;
    ",
    'payments' => "
    CREATE TABLE IF NOT EXISTS payments (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id INT UNSIGNED NOT NULL,
      order_id INT UNSIGNED NULL,
      ticket_id INT UNSIGNED NULL,
      amount DECIMAL(10,2) NOT NULL,
      method VARCHAR(50) NOT NULL,
      status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
      reference VARCHAR(100) NOT NULL UNIQUE,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_payments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
      CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
      CONSTRAINT fk_payments_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
      CONSTRAINT chk_payment_target
        CHECK ((order_id IS NOT NULL AND ticket_id IS NULL) OR
               (order_id IS NULL AND ticket_id IS NOT NULL))
    ) ENGINE=InnoDB;
    ",
];

foreach ($tables as $name => $sql) {
    run_sql($db, $sql, 'Table <strong>' . $name . '</strong> created/verified');
}

// Create indexes.
$indexes = [
    'idx_products_club' => 'CREATE INDEX idx_products_club ON products(club_id)',
    'idx_events_club' => 'CREATE INDEX idx_events_club ON events(club_id)',
    'idx_orders_user' => 'CREATE INDEX idx_orders_user ON orders(user_id)',
    'idx_tickets_event' => 'CREATE INDEX idx_tickets_event ON tickets(event_id)',
    'idx_tickets_user' => 'CREATE INDEX idx_tickets_user ON tickets(user_id)',
];

foreach ($indexes as $name => $sql) {
    // Ignore errors if index already exists.
    @$db->query($sql);
}

// -----------------------------------------------------------------------------
// Step 2: Seed initial data if tables are empty
// -----------------------------------------------------------------------------

// Seed clubs.
$clubsCount = (int) $db->query('SELECT COUNT(*) AS total FROM clubs')->fetch_assoc()['total'];
if ($clubsCount === 0) {
    $db->query("INSERT INTO clubs (name, description, logo) VALUES
        ('Sports Club', 'Jerseys, caps, and match-day essentials.', NULL),
        ('Tech Club', 'Campus innovation, hackathons, and technology gear.', NULL),
        ('Drama Club', 'Creative performances and memorable shows.', NULL)");
    $messages[] = '<span style="color:green;">✔ Seeded 3 sample clubs.</span>';
} else {
    $messages[] = '<span style="color:green;">✔ Clubs table already has ' . $clubsCount . ' rows.</span>';
}

// Seed products.
$productsCount = (int) $db->query('SELECT COUNT(*) AS total FROM products')->fetch_assoc()['total'];
if ($productsCount === 0) {
    $db->query("INSERT INTO products (club_id, name, price, image, stock, category) VALUES
        (1, 'Sports Club T-Shirt', 1200.00, NULL, 25, 'Clothing'),
        (1, 'AIU Club Cap', 800.00, NULL, 18, 'Accessories'),
        (2, 'Tech Club Tote Bag', 700.00, NULL, 20, 'Accessories'),
        (3, 'Drama Club Hoodie', 1800.00, NULL, 12, 'Clothing')");
    $messages[] = '<span style="color:green;">✔ Seeded 4 sample products.</span>';
} else {
    $messages[] = '<span style="color:green;">✔ Products table already has ' . $productsCount . ' rows.</span>';
}

// Seed events.
$eventsCount = (int) $db->query('SELECT COUNT(*) AS total FROM events')->fetch_assoc()['total'];
if ($eventsCount === 0) {
    $db->query("INSERT INTO events (club_id, title, description, venue, `date`, capacity, ticket_price) VALUES
        (3, 'AIU Talent Night', 'An evening of music, drama, dance, and spoken word.', 'Main Auditorium', '2026-08-15 17:00:00', 150, 300.00),
        (2, 'Campus Hackathon', 'A one-day student coding challenge and innovation workshop.', 'Innovation Lab', '2026-08-22 09:00:00', 60, 0.00),
        (1, 'Interclub Finals', 'Support AIU teams in the interclub sports finals.', 'AIU Sports Ground', '2026-08-30 14:00:00', 300, 150.00)");
    $messages[] = '<span style="color:green;">✔ Seeded 3 sample events.</span>';
} else {
    $messages[] = '<span style="color:green;">✔ Events table already has ' . $eventsCount . ' rows.</span>';
}

// Seed users.
$usersCount = (int) $db->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'];
if ($usersCount === 0) {
    // Password hash for "password123"
    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    $db->query("INSERT INTO users (name, email, password, role) VALUES
        ('Super Admin', 'super@aiu.edu', '$hash', 'super_admin'),
        ('John Club Admin', 'john@aiu.edu', '$hash', 'club_admin'),
        ('Jane Student', 'jane@aiu.edu', '$hash', 'student')");
    $messages[] = '<span style="color:green;">✔ Seeded 3 sample users (password: password123).</span>';
} else {
    $messages[] = '<span style="color:green;">✔ Users table already has ' . $usersCount . ' rows.</span>';
}

// Seed club allocations.
$allocationsCount = (int) $db->query('SELECT COUNT(*) AS total FROM club_admins')->fetch_assoc()['total'];
if ($allocationsCount === 0) {
    // Get user and club IDs.
    $adminResult = $db->query("SELECT id FROM users WHERE role='club_admin' LIMIT 1");
    $clubResult = $db->query("SELECT id FROM clubs LIMIT 2");

    if ($adminResult && $clubResult) {
        $adminId = $adminResult->fetch_assoc()['id'] ?? null;
        $clubs = $clubResult->fetch_all(MYSQLI_ASSOC);

        if ($adminId && !empty($clubs)) {
            foreach ($clubs as $club) {
                $db->query('INSERT IGNORE INTO club_admins (user_id, club_id) VALUES (' . (int) $adminId . ', ' . (int) $club['id'] . ')');
            }
            $messages[] = '<span style="color:green;">✔ Seeded club allocations.</span>';
        }
    }
} else {
    $messages[] = '<span style="color:green;">✔ Club allocations table already has ' . $allocationsCount . ' rows.</span>';
}

// -----------------------------------------------------------------------------
// Final verification
// -----------------------------------------------------------------------------

$allTablesExist = true;
$expectedTables = ['users', 'clubs', 'club_admins', 'products', 'product_sizes', 'events', 'orders', 'order_items', 'tickets', 'payments'];
foreach ($expectedTables as $table) {
    if (!table_exists($db, $table)) {
        $allTablesExist = false;
        break;
    }
}

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
        .back { display: inline-block; margin-top: 1rem; padding: 0.5rem 1rem; background: #e3111f; color: #fff; text-decoration: none; border-radius: 4px; }
        .success { background: #c6f6d5; padding: 1rem; border-radius: 6px; margin-top: 1rem; }
        .error { background: #fed7d7; padding: 1rem; border-radius: 6px; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Setup Results</h1>
        <ul>
            <?php foreach ($messages as $msg) { echo '<li>' . $msg . '</li>'; } ?>
        </ul>

        <?php if ($allTablesExist) { ?>
            <div class="success">
                <strong>✔ Setup complete!</strong> All tables are created and verified.
                <p>You can now log in as <strong>super@aiu.edu</strong> / <strong>password123</strong> and allocate clubs to admins.</p>
                <a class="back" href="admin.php">&larr; Go to Admin Dashboard</a>
            </div>
        <?php } else { ?>
            <div class="error">
                <strong>✘ Some tables are still missing.</strong> Please check the errors above and ensure MySQL is running.
                <br><br>
                <a class="back" href="setup_database.php">Run Setup Again</a>
            </div>
        <?php } ?>
    </div>
</body>
</html>