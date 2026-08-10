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
            $destinationPath = $folder . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
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
    <form id="product-image-form" method="post" enctype="multipart/form-data" class="form-card">
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
            <input id="image-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
        </label>
        <div id="image-preview" class="image-preview" hidden></div>
        <button class="button">Upload Image</button>
    </form>
</main>
<script>
(function () {
    const form = document.getElementById('product-image-form');
    const input = document.getElementById('image-input');
    const preview = document.getElementById('image-preview');
    const targetSize = 800;

    if (!form || !input || !preview) {
        return;
    }

    const readFile = file => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });

    const loadImage = src => new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = src;
    });

    const resizeImage = async (file) => {
        const dataUrl = await readFile(file);
        const image = await loadImage(dataUrl);
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        const mimeType = file.type || 'image/jpeg';

        canvas.width = targetSize;
        canvas.height = targetSize;
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, targetSize, targetSize);

        const scale = Math.max(targetSize / image.width, targetSize / image.height);
        const width = image.width * scale;
        const height = image.height * scale;
        const x = (targetSize - width) / 2;
        const y = (targetSize - height) / 2;

        context.drawImage(image, x, y, width, height);

        return new Promise((resolve) => {
            canvas.toBlob((blob) => resolve(blob || file), mimeType, 0.9);
        });
    };

    input.addEventListener('change', async () => {
        const file = input.files && input.files[0];
        if (!file) {
            preview.hidden = true;
            preview.innerHTML = '';
            return;
        }

        try {
            const dataUrl = await readFile(file);
            preview.hidden = false;
            preview.innerHTML = '<img src="' + dataUrl + '" alt="Selected preview">';
        } catch (error) {
            preview.hidden = true;
            preview.innerHTML = '';
        }
    });

    form.addEventListener('submit', async (event) => {
        const file = input.files && input.files[0];
        if (!file) {
            return;
        }

        event.preventDefault();

        try {
            const resizedFile = await resizeImage(file);
            const formData = new FormData(form);
            formData.delete('image');
            formData.append('image', resizedFile, file.name || 'image.jpg');

            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            if (response.ok || response.type === 'opaqueredirect') {
                window.location.href = response.url || window.location.href;
            } else {
                window.location.reload();
            }
        } catch (error) {
            form.submit();
        }
    });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
