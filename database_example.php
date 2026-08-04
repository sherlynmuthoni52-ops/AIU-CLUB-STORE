<?php
// Put this file in C:\xampp\htdocs\ and open it in your browser.
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'portfolio_db';

$connection = new mysqli($host, $username, $password, $database);

if ($connection->connect_error) {
    die('Database connection failed: ' . $connection->connect_error);
}

$connection->set_charset('utf8mb4');

$columnResult = $connection->query('SHOW COLUMNS FROM contacts');
$columns = [];

if ($columnResult) {
    while ($column = $columnResult->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
}

$findColumn = static function (array $availableColumns, array $candidates): ?string {
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $availableColumns, true)) {
            return $candidate;
        }
    }

    return null;
};

$nameColumn = $findColumn($columns, ['name', 'full_name', 'contact_name', 'first_name', 'last_name']);
$emailColumn = $findColumn($columns, ['email', 'contact_email', 'email_address']);
$phoneColumn = $findColumn($columns, ['phone', 'telephone', 'mobile', 'contact_phone', 'phone_number']);
$messageColumn = $findColumn($columns, ['message', 'comment', 'comments', 'details', 'description', 'query']);

$sql = 'SELECT * FROM contacts ORDER BY id DESC';
$result = $connection->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Database</title>
</head>
<body>
    <h1>Contacts</h1>

    <?php if ($result && $result->num_rows > 0) { ?>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <?php $displayName = $nameColumn && isset($row[$nameColumn]) ? $row[$nameColumn] : 'No name'; ?>
            <article>
                <h2><?php echo htmlspecialchars($displayName); ?></h2>
                <?php if ($emailColumn && isset($row[$emailColumn])) { ?>
                    <p>Email: <?php echo htmlspecialchars($row[$emailColumn]); ?></p>
                <?php } ?>
                <?php if ($phoneColumn && isset($row[$phoneColumn])) { ?>
                    <p>Phone: <?php echo htmlspecialchars($row[$phoneColumn]); ?></p>
                <?php } ?>
                <?php if ($messageColumn && isset($row[$messageColumn])) { ?>
                    <p>Message: <?php echo htmlspecialchars($row[$messageColumn]); ?></p>
                <?php } ?>
            </article>
            <hr>
        <?php } ?>
    <?php } else { ?>
        <p>No contacts found in the database.</p>
    <?php } ?>
</body>
</html>
<?php $connection->close(); ?>
