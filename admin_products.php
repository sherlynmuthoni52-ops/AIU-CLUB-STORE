<?php
/**
 * AIU Club Store - Manage Products
 *
 * Allows administrators to add, edit, and delete products.
 * Each product can optionally have sizes managed via admin_sizes.php.
 * Club administrators can only manage products for their allocated club.
 */

// -----------------------------------------------------------------------------
// Configuration and Authentication
// -----------------------------------------------------------------------------

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$db = database();
$user = current_user();
$managedClubIds = managed_club_ids();
$isSuperAdmin = $user['role'] === 'super_admin';
$managedCondition = managed_club_condition();

// -----------------------------------------------------------------------------
// Handle Form Submissions
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Save a new or existing product.
    if ($action === 'save') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $clubId = filter_input(INPUT_POST, 'club_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
        $category = trim($_POST['category'] ?? '');

        if (!$clubId || !$name || $price === false || $price < 0 || $stock === false || $stock < 0 || !$category) {
            set_message('Please complete all product fields with valid values.');
        } elseif (!$isSuperAdmin && !in_array($clubId, $managedClubIds, true)) {
            set_message('You can only manage products for your allocated club.');
        } elseif ($id) {
            // Update an existing product.
            $stmt = $db->prepare('UPDATE products SET club_id=?, name=?, price=?, stock=?, category=? WHERE id=?');
            $stmt->bind_param('isdisi', $clubId, $name, $price, $stock, $category, $id);
            $stmt->execute();
            set_message('Product updated.');
        } else {
            // Insert a new product.
            $stmt = $db->prepare('INSERT INTO products (club_id, name, price, stock, category) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('isdis', $clubId, $name, $price, $stock, $category);
            $stmt->execute();
            set_message('Product added.');
        }
    }

    // Delete a product.
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            // Verify the product belongs to a managed club.
            $product = $db->query('SELECT club_id FROM products WHERE id=' . (int) $id)->fetch_assoc();
            if ($product && ($isSuperAdmin || in_array((int) $product['club_id'], $managedClubIds, true))) {
                try {
                    $db->query('DELETE FROM products WHERE id=' . (int) $id);
                    set_message('Product deleted.');
                } catch (Throwable $e) {
                    set_message('This product cannot be deleted because it is used in an order.');
                }
            } else {
                set_message('You can only delete products from your allocated club.');
            }
        }
    }

    header('Location: admin_products.php');
    exit;
}

// -----------------------------------------------------------------------------
// Prepare Data for Display
// -----------------------------------------------------------------------------

// Check if we are editing an existing product.
$editing = null;
if ($id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT)) {
    $editing = $db->query('SELECT * FROM products WHERE id=' . (int) $id)->fetch_assoc();
    if ($editing && !$isSuperAdmin && !in_array((int) $editing['club_id'], $managedClubIds, true)) {
        $editing = null;
    }
}

$clubsQuery = 'SELECT id, name FROM clubs';
if (!$isSuperAdmin) {
    $clubsQuery .= ' WHERE id IN (' . implode(',', array_map('intval', $managedClubIds)) . ')';
}
$clubsQuery .= ' ORDER BY name';
$clubs = $db->query($clubsQuery);

$productsQuery = 'SELECT products.*, clubs.name AS club_name FROM products JOIN clubs ON clubs.id = products.club_id WHERE ' . $managedCondition . ' ORDER BY products.id DESC';
$products = $db->query($productsQuery);

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'Manage Products | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
    <p>
        <a class="button dashboard-btn" href="admin.php">&larr; Dashboard</a>
    </p>
    <h2><?php echo $editing ? 'Edit Product' : 'Add Product'; ?></h2>

    <!-- Add / Edit Product Form -->
    <form method="post" class="form-card">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo (int) ($editing['id'] ?? 0); ?>">
        <label>
            Club
            <select name="club_id" required <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>>
                <?php while ($club = $clubs->fetch_assoc()) { ?>
                    <option value="<?php echo $club['id']; ?>" <?php echo (($editing['club_id'] ?? 0) == $club['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($club['name']); ?>
                    </option>
                <?php } ?>
            </select>
            <?php if (!$isSuperAdmin) { ?>
                <input type="hidden" name="club_id" value="<?php echo (int) ($editing['club_id'] ?? $managedClubIds[0] ?? 0); ?>">
            <?php } ?>
        </label>
        <label>
            Product name
            <input name="name" value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>" required>
        </label>
        <label>
            Price (KES)
            <input name="price" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($editing['price'] ?? ''); ?>" required>
        </label>
        <label>
            Stock
            <input name="stock" type="number" min="0" value="<?php echo htmlspecialchars($editing['stock'] ?? '0'); ?>" required>
        </label>
        <label>
            Category
            <input name="category" value="<?php echo htmlspecialchars($editing['category'] ?? ''); ?>" required>
        </label>
        <button class="button">
            <?php echo $editing ? 'Save Changes' : 'Add Product'; ?>
        </button>
    </form>

    <!-- Products List -->
    <h2>Products</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Club</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($product = $products->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['club_name']); ?></td>
                    <td>KES <?php echo number_format((float) $product['price'], 2); ?></td>
                    <td><?php echo $product['stock']; ?></td>
                    <td>
                        <a class="text-link" href="admin_products.php?edit=<?php echo $product['id']; ?>">Edit</a>
                        <a class="text-link" href="admin_sizes.php?product_id=<?php echo $product['id']; ?>">Sizes</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            <button class="text-link">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
