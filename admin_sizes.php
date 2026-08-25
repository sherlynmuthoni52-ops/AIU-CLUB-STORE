<?php
/**
 * AIU Club Store - Product Sizes
 *
 * Allows administrators to add, edit, and delete size-specific stock levels
 * for a given product (e.g., S, M, L, XL).
 * Club administrators can only manage sizes for products in their allocated club.
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

// -----------------------------------------------------------------------------
// Load Product
// -----------------------------------------------------------------------------

$productId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$product = $productId ? $db->query('SELECT id, name FROM products WHERE id=' . (int) $productId)->fetch_assoc() : null;

if (!$product) {
    set_message('Choose a valid product first.');
    header('Location: admin_products.php');
    exit;
}

if (!$isSuperAdmin) {
    $productClub = $db->query('SELECT club_id FROM products WHERE id=' . (int) $productId)->fetch_assoc();
    if (!$productClub || !in_array((int) $productClub['club_id'], $managedClubIds, true)) {
        set_message('You can only manage sizes for products in your allocated club.');
        header('Location: admin_products.php');
        exit;
    }
}

// -----------------------------------------------------------------------------
// Handle Add / Update / Delete
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $size = strtoupper(trim($_POST['size'] ?? ''));

    if ($action === 'delete' && $size !== '') {
        $stmt = $db->prepare('DELETE FROM product_sizes WHERE product_id = ? AND size = ?');
        $stmt->bind_param('is', $productId, $size);
        $stmt->execute();
        set_message('Size deleted.');
    } elseif ($size !== '') {
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

        if ($stock !== false && $stock >= 0) {
            $stmt = $db->prepare('INSERT INTO product_sizes (product_id, size, stock) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE stock=VALUES(stock)');
            $stmt->bind_param('isi', $productId, $size, $stock);
            $stmt->execute();
            set_message('Size saved.');
        }
    }

    header('Location: admin_sizes.php?product_id=' . $productId);
    exit;
}

// -----------------------------------------------------------------------------
// Prepare Data for Display
// -----------------------------------------------------------------------------

$sizes = $db->query('SELECT * FROM product_sizes WHERE product_id=' . (int) $productId . ' ORDER BY size');

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'Product Sizes | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section admin-page">
    <p>
        <a class="text-link" href="admin_products.php"><i class="fas fa-arrow-left"></i> Products</a>
    </p>
    <h2>Sizes: <?php echo htmlspecialchars($product['name']); ?></h2>

    <!-- Add Size Form -->
    <form method="post" class="form-card">
        <div class="field">
            <input id="size" name="size" placeholder="e.g. Medium" required>
            <label for="size">Size</label>
        </div>
        <div class="field">
            <input id="stock" name="stock" type="number" min="0" required>
            <label for="stock">Stock</label>
        </div>
        <button class="button button-primary" name="action" value="add" data-loading="Adding size...">Add Size</button>
    </form>

    <!-- Sizes List -->
    <table class="table">
        <thead>
            <tr>
                <th>Size</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($size = $sizes->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($size['size']); ?></td>
                    <td><?php echo (int) $size['stock']; ?></td>
                    <td>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="size" value="<?php echo htmlspecialchars($size['size']); ?>">
                            <input type="hidden" name="stock" value="<?php echo (int) $size['stock']; ?>">
                            <button type="submit" class="button button-sm" name="action" value="save">Save</button>
                        </form>
                        <form method="post" class="inline-form" onsubmit="return confirm('Delete this size?');">
                            <input type="hidden" name="size" value="<?php echo htmlspecialchars($size['size']); ?>">
                            <button type="submit" class="button button-sm" name="action" value="delete">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
