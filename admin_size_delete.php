<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: admin_products.php'); exit; }
$sizeId = filter_input(INPUT_POST, 'size_id', FILTER_VALIDATE_INT);
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
if ($sizeId && $productId) { database()->query('DELETE FROM product_sizes WHERE id=' . (int) $sizeId); set_message('Size deleted.'); }
header('Location: admin_sizes.php?product_id=' . (int) $productId); exit;
