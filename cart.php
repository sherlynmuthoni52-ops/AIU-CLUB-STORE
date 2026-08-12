<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

/**
 * AIU Club Store - Shopping Cart
 *
 * Allows users to view and remove items from their cart.
 */

// Remove item from cart if requested
if (isset($_GET['remove'])) {
    $productId = (int) $_GET['remove'];
    $size = $_GET['size'] ?? '';

    if (isset($_SESSION['cart'][$productId])) {
        if ($size !== '' && is_array($_SESSION['cart'][$productId]) && array_key_exists($size, $_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId][$size]);
            if (empty($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
            }
        } elseif ($size === '' && is_array($_SESSION['cart'][$productId]) && array_key_exists('', $_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]['']);
            if (empty($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
            }
        } elseif (!is_array($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
    }

    set_message('Item removed from cart.');
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$products = [];
$total = 0;

if ($cart) {
    $productIds = array_map('intval', array_keys($cart));
    $ids = implode(',', $productIds);
    $result = database()->query("SELECT id, name, price, stock FROM products WHERE id IN ($ids)");

    $productMap = [];
    while ($row = $result->fetch_assoc()) {
        $productMap[$row['id']] = $row;
    }

    // Fetch all sizes for products in cart.
    $sizeResult = database()->query("SELECT product_id, size, stock FROM product_sizes WHERE product_id IN ($ids)");
    $sizeMap = [];
    while ($row = $sizeResult->fetch_assoc()) {
        $sizeMap[$row['product_id']][] = $row;
    }

    foreach ($cart as $productId => $sizes) {
        $product = $productMap[$productId] ?? null;
        if (!$product) {
            continue;
        }

        // Support legacy flat cart entries (integer quantity).
        if (!is_array($sizes)) {
            $sizes = ['' => (int) $sizes];
        }

        foreach ($sizes as $size => $quantity) {
            $qty = (int) $quantity;
            $sizeLabel = $size === '' ? 'Standard' : $size;

            if ($size === '') {
                $available = (int) $product['stock'];
            } else {
                $available = 0;
                foreach ($sizeMap[$productId] ?? [] as $s) {
                    if ($s['size'] === $size) {
                        $available = (int) $s['stock'];
                        break;
                    }
                }
            }

            $displayQty = min($qty, $available);
            $products[] = [
                'product_id' => $productId,
                'name' => $product['name'],
                'price' => (float) $product['price'],
                'size' => $size,
                'size_label' => $sizeLabel,
                'quantity' => $displayQty,
                'available' => $available,
            ];
            $total += $product['price'] * $displayQty;
        }
    }
}

$page_title = 'Cart | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
  <h2>Your Cart</h2>
  <?php if ($products) { ?>
    <table class="table">
      <thead>
        <tr>
          <th>Item</th>
          <th>Size</th>
          <th>Quantity</th>
          <th>Price</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $product) { ?>
          <tr>
            <td><?php echo htmlspecialchars($product['name']); ?></td>
            <td><?php echo htmlspecialchars($product['size_label']); ?></td>
            <td><?php echo (int) $product['quantity']; ?></td>
            <td>KES <?php echo number_format($product['price'] * $product['quantity'], 2); ?></td>
            <td>
              <a class="text-link" href="cart.php?remove=<?php echo (int) $product['product_id']; ?>&size=<?php echo urlencode($product['size']); ?>">Remove</a>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
    <p class="total">Total: KES <?php echo number_format($total, 2); ?></p>
    <p class="align-right">
      <a class="button" href="checkout.php">Proceed to Checkout</a>
    </p>
  <?php } else { ?>
    <p>Your cart is empty. <a class="text-link" href="shop.php">Browse merchandise</a>.</p>
  <?php } ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
