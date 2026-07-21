<?php
// Put this file in C:\xampp\htdocs\aiu-club-store\ and open it in your browser.
$connection = new mysqli('localhost', 'root', '', 'aiu_club_store');

if ($connection->connect_error) {
    die('Database connection failed: ' . $connection->connect_error);
}

$connection->set_charset('utf8mb4');

// Example: show products with the club that owns each product.
$products = $connection->query(
    'SELECT products.name, products.price, products.stock, products.category, clubs.name AS club_name
     FROM products
     INNER JOIN clubs ON products.club_id = clubs.id
     ORDER BY products.id DESC'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIU Club Store</title>
</head>
<body>
    <h1>AIU Student Club Merchandise</h1>

    <?php if ($products && $products->num_rows > 0) { ?>
        <?php while ($product = $products->fetch_assoc()) { ?>
            <article>
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                <p>Club: <?php echo htmlspecialchars($product['club_name']); ?></p>
                <p>Category: <?php echo htmlspecialchars($product['category']); ?></p>
                <p>Price: KES <?php echo number_format((float) $product['price'], 2); ?></p>
                <p>Available stock: <?php echo (int) $product['stock']; ?></p>
            </article>
            <hr>
        <?php } ?>
    <?php } else { ?>
        <p>No products have been added yet.</p>
    <?php } ?>
</body>
</html>
<?php $connection->close(); ?>
