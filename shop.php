<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

/**
 * AIU Club Store - Merchandise Shop
 *
 * Displays available products and handles add-to-cart actions.
 */

$db = database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if ($product_id) {
        $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + 1;
        set_message('Product added to your cart.');
    }

    header('Location: cart.php');
    exit;
}

$club_id = filter_input(INPUT_GET, 'club', FILTER_VALIDATE_INT);

$sql = 'SELECT products.*, clubs.name AS club_name FROM products JOIN clubs ON clubs.id = products.club_id';

if ($club_id) {
    $sql .= ' WHERE products.club_id = ' . (int) $club_id;
}

$sql .= ' ORDER BY products.id DESC';

$products = $db->query($sql);

$page_title = 'Merchandise | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
  <h2>Club Merchandise</h2>
  <div class="grid">
    <?php if ($products && $products->num_rows) { while ($product = $products->fetch_assoc()) { ?>
      <article class="card">
        <div class="card-image">
          <?php echo $product['image'] ? '<img src="uploads/' . htmlspecialchars($product['image']) . '" alt="' . htmlspecialchars($product['name']) . '">' : 'AIU'; ?>
        </div>
        <p class="muted">
          <?php echo htmlspecialchars($product['club_name']); ?> · <?php echo htmlspecialchars($product['category']); ?>
        </p>
        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
        <p class="price">KES <?php echo number_format((float) $product['price'], 2); ?></p>
        <p><?php echo (int) $product['stock']; ?> in stock</p>
        <form method="post">
          <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
          <button class="button" <?php echo $product['stock'] < 1 ? 'disabled' : ''; ?>>
            Add to Cart
          </button>
        </form>
      </article>
    <?php }} else { ?>
      <p>No products are available yet.</p>
    <?php } ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
