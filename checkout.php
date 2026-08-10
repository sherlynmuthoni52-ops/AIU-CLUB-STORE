<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

/**
 * AIU Club Store - Checkout
 *
 * Converts the shopping cart into an order.
 * Validates stock, decrements inventory, and creates payment record.
 */

require_login();

$cart = $_SESSION['cart'] ?? [];

if (!$cart) {
    set_message('Your cart is empty.');
    header('Location: shop.php');
    exit;
}

$db = database();

try {
    $db->begin_transaction();

    $total = 0;
    $items = [];

    // Validate all cart items and lock rows for update
    foreach ($cart as $productId => $quantity) {
        $id = (int) $productId;
        $quantity = (int) $quantity;

        $product = $db->query("SELECT id, price, stock FROM products WHERE id = $id FOR UPDATE")->fetch_assoc();

        if (!$product || $quantity < 1 || $product['stock'] < $quantity) {
            throw new Exception('A cart item is no longer available in that quantity.');
        }

        $items[] = [$id, $quantity, (float) $product['price']];
        $total += $product['price'] * $quantity;
    }

    $userId = (int) current_user()['id'];
    $pending = 'pending';

    // Create the order
    $order = $db->prepare('INSERT INTO orders (user_id, total_amount, payment_status, order_status) VALUES (?, ?, ?, ?)');
    $order->bind_param('idss', $userId, $total, $pending, $pending);
    $order->execute();
    $orderId = $db->insert_id;

    // Insert order items and update stock
    $itemStmt = $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
    $stockStmt = $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

    foreach ($items as [$id, $quantity, $price]) {
        $itemStmt->bind_param('iiid', $orderId, $id, $quantity, $price);
        $itemStmt->execute();

        $stockStmt->bind_param('ii', $quantity, $id);
        $stockStmt->execute();
    }

    // Create payment record
    $reference = 'ORDER-' . $orderId . '-' . strtoupper(bin2hex(random_bytes(3)));
    $method = 'Pay on collection';

    $payment = $db->prepare(
        'INSERT INTO payments (user_id, order_id, amount, method, status, reference) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $payment->bind_param('iidsss', $userId, $orderId, $total, $method, $pending, $reference);
    $payment->execute();

    $db->commit();

    unset($_SESSION['cart']);
    set_message("Order #$orderId placed. Payment is pending: pay on collection.");
    header('Location: account.php');
    exit;
} catch (Throwable $e) {
    $db->rollback();
    set_message($e->getMessage());
    header('Location: cart.php');
    exit;
}
