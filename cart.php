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
    unset($_SESSION['cart'][(int) $_GET['remove']]);
    set_message('Item removed from cart.');
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$products = [];
$total = 0;

if ($cart) {
    // Fetch all cart products in a single query
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $result = database()->query("SELECT id, name, price, stock FROM products WHERE id IN ($ids)");

    while ($row = $result->fetch_assoc()) {
        $row['quantity'] = min($cart[$row['id']], $row['stock']);
        $products[] = $row;
        $total += $row['price'] * $row['quantity'];
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
          <th>Quantity</th>
          <th>Price</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $product) { ?>
          <tr>
            <td><?php echo htmlspecialchars($product['name']); ?></td>
            <td><?php echo (int) $product['quantity']; ?></td>
            <td>KES <?php echo number_format($product['price'] * $product['quantity'], 2); ?></td>
            <td>
              <a class="text-link" href="cart.php?remove=<?php echo (int) $product['id']; ?>">Remove</a>
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
