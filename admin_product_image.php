<?php
/**
 * AIU Club Store - Product Images
 *
 * Allows administrators to upload and assign images to products.
 * Supported formats: JPG, PNG, WebP.
 * Maximum file size: 2 MB.
 */

// -----------------------------------------------------------------------------
// Configuration and Authentication
// -----------------------------------------------------------------------------

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$db = database();

// -----------------------------------------------------------------------------
// Handle Image Upload
// -----------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $file = $_FILES['image'] ?? null;
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if (!$productId || !$file || $file['error'] !== UPLOAD_ERR_OK) {
        set_message('Choose a product and a valid image file.');
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        set_message('Image must be 2 MB or smaller.');
    } else {
        // Verify the MIME type is one of the allowed image formats.
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($allowedTypes[$mime])) {
            set_message('Only JPG, PNG, and WebP image files are allowed.');
        } else {
            // Ensure the uploads directory exists.
            $folder = __DIR__ . '/uploads';
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }

            // Generate a unique filename to avoid collisions.
            $filename = 'product-' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mime];

            // Move the uploaded file to the uploads folder.
            if (move_uploaded_file($file['tmp_name'], $folder . '/' . $filename)) {
                $stmt = $db->prepare('UPDATE products SET image=? WHERE id=?');
                $stmt->bind_param('si', $filename, $productId);
                $stmt->execute();
                set_message('Product image uploaded successfully.');
            } else {
                set_message('The image could not be saved.');
            }
        }
    }

    header('Location: admin_product_image.php');
    exit;
}

// -----------------------------------------------------------------------------
// Prepare Data for Display
// -----------------------------------------------------------------------------

$products = $db->query('SELECT id, name, image FROM products ORDER BY name');

// -----------------------------------------------------------------------------
// Render Page
// -----------------------------------------------------------------------------

$page_title = 'Product Images | AIU Club Store';
require __DIR__ . '/includes/header.php';
?>
<main class="container section">
    <p>
        <a class="text-link" href="admin.php">&larr; Dashboard</a>
    </p>
    <h2>Upload Product Image</h2>
    <p>Use JPG, PNG, or WebP files up to 2 MB.</p>

    <!-- Upload Form -->
    <form method="post" enctype="multipart/form-data" class="form-card">
        <label>
            Product
            <select name="product_id" required>
                <?php while ($product = $products->fetch_assoc()) { ?>
                    <option value="<?php echo $product['id']; ?>">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </option>
                <?php } ?>
            </select>
        </label>
        <label>
            Image file
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
        </label>
        <button class="button">Upload Image</button>
    </form>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
