<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

/**
 * AIU Club Store - Checkout
 *
 * Converts the shopping cart into an order.
 * Validates stock (including per-size stock), decrements inventory,
 * and creates payment record.
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
    foreach ($cart as $productId => $sizes) {
        $id = (int) $productId;

        // Support legacy flat cart entries (integer quantity).
        if (!is_array($sizes)) {
            $sizes = ['' => (int) $sizes];
        }

        $product = $db->query("SELECT id, price, stock FROM products WHERE id = $id FOR UPDATE")->fetch_assoc();

        if (!$product) {
            throw new Exception('A cart item is no longer available.');
        }

        // Check if this product has any size variants defined.
        $sizeCount = (int) $db->query("SELECT COUNT(*) AS total FROM product_sizes WHERE product_id = $id")->fetch_assoc()['total'];

        foreach ($sizes as $size => $quantity) {
            $qty = (int) $quantity;

            if ($qty < 1) {
                continue;
            }

            if ($sizeCount > 0 && $size !== '') {
                // Validate per-size stock.
                $escapedSize = $db->real_escape_string($size);
                $sizeRow = $db->query("SELECT stock FROM product_sizes WHERE product_id = $id AND size = '$escapedSize' FOR UPDATE")->fetch_assoc();

                if (!$sizeRow || $sizeRow['stock'] < $qty) {
                    throw new Exception('A cart item is no longer available in that quantity/size.');
                }
            } else {
                // No sizes or unspecified size: validate against product stock.
                if ($product['stock'] < $qty) {
                    throw new Exception('A cart item is no longer available in that quantity.');
                }
            }

            $items[] = [$id, $size, $qty, (float) $product['price']];
            $total += $product['price'] * $qty;
        }
    }

    $userId = (int) current_user()['id'];
    $pending = 'pending';

    // Create the order
    $order = $db->prepare('INSERT INTO orders (user_id, total_amount, payment_status, order_status) VALUES (?, ?, ?, ?)');
    $order->bind_param('idss', $userId, $total, $pending, $pending);
    $order->execute();
    $orderId = $db->insert_id;

    // Insert order items and update stock
    $itemStmt = $db->prepare('INSERT INTO order_items (order_id, product_id, size, quantity, price) VALUES (?, ?, ?, ?, ?)');
    $stockStmt = $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
    $sizeStockStmt = $db->prepare('UPDATE product_sizes SET stock = stock - ? WHERE product_id = ? AND size = ?');

    foreach ($items as [$id, $size, $quantity, $price]) {
        $itemStmt->bind_param('iisid', $orderId, $id, $size, $quantity, $price);
        $itemStmt->execute();

        // Decrement product-level stock.
        $stockStmt->bind_param('ii', $quantity, $id);
        $stockStmt->execute();

        // If a specific size was ordered, also decrement size-level stock.
        if ($size !== '') {
            $sizeStockStmt->bind_param('iis', $quantity, $id, $size);
            $sizeStockStmt->execute();
        }
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
