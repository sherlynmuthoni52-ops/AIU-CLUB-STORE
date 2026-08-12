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
    $size = $_POST['size'] ?? '';

    if ($product_id) {
        $product = $db->query("SELECT id FROM products WHERE id = " . (int) $product_id)->fetch_assoc();
        if (!$product) {
            set_message('Product not found.');
            header('Location: shop.php');
            exit;
        }

        // Check if product has sizes
        $sizeCheck = $db->query("SELECT COUNT(*) AS total FROM product_sizes WHERE product_id = " . (int) $product_id)->fetch_assoc();
        $hasSizes = (int) $sizeCheck['total'] > 0;

        if ($hasSizes && $size === '') {
            set_message('Please select a size.');
            header('Location: shop.php');
            exit;
        }

        $wasInCart = isset($_SESSION['cart'][$product_id][$size]);
        $_SESSION['cart'][$product_id][$size] = ($_SESSION['cart'][$product_id][$size] ?? 0) + 1;
        set_message($wasInCart
            ? 'Item is already in your cart. Quantity updated to ' . $_SESSION['cart'][$product_id][$size] . '.'
            : 'Product added to your cart.');
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

// Pre-fetch sizes for all products in one query to avoid N+1.
$allSizes = [];
if ($products && $products->num_rows) {
    $ids = [];
    $productsCopy = $products;
    while ($p = $productsCopy->fetch_assoc()) {
        $ids[] = (int) $p['id'];
    }
    $idList = implode(',', $ids);
    $sizeResult = $db->query("SELECT product_id, size, stock FROM product_sizes WHERE product_id IN ($idList)");
    while ($row = $sizeResult->fetch_assoc()) {
        $allSizes[$row['product_id']][] = $row;
    }
    // Reset pointer for main loop.
    $products->data_seek(0);
}

$page_title = 'Merchandise | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
  <h2>Club Merchandise</h2>
  <div class="grid">
    <?php if ($products && $products->num_rows) { while ($product = $products->fetch_assoc()) { 
      $sizes = $allSizes[(int) $product['id']] ?? [];
    ?>
      <article class="card">
        <div class="card-image">
          <?php if ($product['image']): ?>
            <div class="img-zoom">
              <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumb">
            </div>
          <?php else: ?>
            AIU
          <?php endif; ?>
        </div>
        <p class="muted">
          <?php echo htmlspecialchars($product['club_name']); ?> · <?php echo htmlspecialchars($product['category']); ?>
        </p>
        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
        <p class="price">KES <?php echo number_format((float) $product['price'], 2); ?></p>
        <?php if ($sizes): ?>
          <p>Sizes: <?php echo htmlspecialchars(implode(', ', array_column($sizes, 'size'))); ?></p>
        <?php else: ?>
          <p><?php echo (int) $product['stock']; ?> in stock</p>
        <?php endif; ?>
        <form method="post">
          <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
          <?php if ($sizes): ?>
            <select name="size" required>
              <option value="">Select size</option>
              <?php foreach ($sizes as $s): ?>
                <option value="<?php echo htmlspecialchars($s['size']); ?>" <?php echo (int) $s['stock'] < 1 ? 'disabled' : ''; ?>>
                  <?php echo htmlspecialchars($s['size']); ?> (<?php echo (int) $s['stock']; ?> in stock)
                </option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
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
