<?php
// Size inventory page. The unique product/size key makes Save act as add or update.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_admin(); $db = database();
$productId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$product = $productId ? $db->query('SELECT id, name FROM products WHERE id = ' . (int) $productId)->fetch_assoc() : null;
if (!$product) { set_message('Choose a valid product first.'); header('Location: admin_products.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $size = strtoupper(trim($_POST['size'] ?? ''));
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    if ($size && $stock !== false && $stock >= 0) {
        $statement = $db->prepare('INSERT INTO product_sizes (product_id, size, stock) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE stock = VALUES(stock)');
        $statement->bind_param('isi', $productId, $size, $stock); $statement->execute(); set_message('Size saved.');
    } else set_message('Enter a valid size and stock amount.');
    header('Location: admin_sizes.php?product_id=' . $productId); exit;
}
$sizes = $db->query('SELECT * FROM product_sizes WHERE product_id = ' . (int) $productId . ' ORDER BY size');
$page_title = 'Product Sizes | AIU Club Store'; require __DIR__ . '/includes/header.php';
?>
<main class="container section"><p><a class="text-link" href="admin_products.php">&larr; Products</a></p><h2>Sizes: <?php echo htmlspecialchars($product['name']); ?></h2><form method="post" class="form-card"><label>Size<input name="size" placeholder="e.g. M" required></label><label>Stock<input name="stock" type="number" min="0" required></label><button class="button">Save Size</button></form><table class="table"><thead><tr><th>Size</th><th>Stock</th></tr></thead><tbody><?php while ($size = $sizes->fetch_assoc()) { ?><tr><td><?php echo htmlspecialchars($size['size']); ?></td><td><?php echo (int) $size['stock']; ?></td></tr><?php } ?></tbody></table></main>
<?php require __DIR__ . '/includes/footer.php'; ?>
