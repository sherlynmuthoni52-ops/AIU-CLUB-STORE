<?php
// Product CRUD page: one handler processes add, update, and delete actions.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();
$db = database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($action === 'save') {
        $clubId = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
        $category = trim($_POST['category'] ?? '');

        // Reject invalid data before writing to the database.
        if (!$clubId || !$name || $price === false || $price < 0 || $stock === false || $stock < 0 || !$category) {
            set_message('Please complete all product fields with valid values.');
        } elseif ($id) {
            $statement = $db->prepare('UPDATE products SET club_id=?, name=?, price=?, stock=?, category=? WHERE id=?');
            $statement->bind_param('isdisi', $clubId, $name, $price, $stock, $category, $id);
            $statement->execute(); set_message('Product updated.');
        } else {
            $statement = $db->prepare('INSERT INTO products (club_id, name, price, stock, category) VALUES (?, ?, ?, ?, ?)');
            $statement->bind_param('isdis', $clubId, $name, $price, $stock, $category);
            $statement->execute(); set_message('Product added.');
        }
    }

    if ($action === 'delete' && $id) {
        try { $db->query('DELETE FROM products WHERE id = ' . (int) $id); set_message('Product deleted.'); }
        catch (Throwable $exception) { set_message('This product cannot be deleted because it is used in an order.'); }
    }
    header('Location: admin_products.php'); exit;
}

// Load a product into the form when the administrator selects Edit.
$editing = null;
if ($id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT)) $editing = $db->query('SELECT * FROM products WHERE id = ' . (int) $id)->fetch_assoc();
$clubs = $db->query('SELECT id, name FROM clubs ORDER BY name');
$products = $db->query('SELECT products.*, clubs.name AS club_name FROM products JOIN clubs ON clubs.id = products.club_id ORDER BY products.id DESC');
$page_title = 'Manage Products | AIU Club Store'; require __DIR__ . '/includes/header.php';
?>
<main class="container section"><p><a class="text-link" href="admin.php">&larr; Dashboard</a></p><h2><?php echo $editing ? 'Edit Product' : 'Add Product'; ?></h2><form method="post" class="form-card"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?php echo (int) ($editing['id'] ?? 0); ?>"><label>Club<select name="club_id" required><?php while ($club = $clubs->fetch_assoc()) { ?><option value="<?php echo (int) $club['id']; ?>" <?php echo (($editing['club_id'] ?? 0) == $club['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($club['name']); ?></option><?php } ?></select></label><label>Product name<input name="name" value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>" required></label><label>Price (KES)<input name="price" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($editing['price'] ?? ''); ?>" required></label><label>Stock<input name="stock" type="number" min="0" value="<?php echo htmlspecialchars($editing['stock'] ?? '0'); ?>" required></label><label>Category<input name="category" value="<?php echo htmlspecialchars($editing['category'] ?? ''); ?>" required></label><button class="button"><?php echo $editing ? 'Save Changes' : 'Add Product'; ?></button></form><h2>Products</h2><table class="table"><thead><tr><th>Name</th><th>Club</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead><tbody><?php while ($product = $products->fetch_assoc()) { ?><tr><td><?php echo htmlspecialchars($product['name']); ?></td><td><?php echo htmlspecialchars($product['club_name']); ?></td><td>KES <?php echo number_format((float) $product['price'], 2); ?></td><td><?php echo (int) $product['stock']; ?></td><td><a class="text-link" href="admin_products.php?edit=<?php echo (int) $product['id']; ?>">Edit</a> · <a class="text-link" href="admin_sizes.php?product_id=<?php echo (int) $product['id']; ?>">Sizes</a><form method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $product['id']; ?>"><button class="text-link" onclick="return confirm('Delete this product?')">Delete</button></form></td></tr><?php } ?></tbody></table></main>
<?php require __DIR__ . '/includes/footer.php'; ?>
