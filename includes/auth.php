<?php
declare(strict_types=1);

/**
 * AIU Club Store - Authentication & Session Helpers
 *
 * Manages user session state, login requirements,
 * and cart/session messaging utilities.
 */

// Start session if not already active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Check whether a user is currently logged in.
 *
 * @return bool True if user session exists, false otherwise.
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

/**
 * Retrieve the currently logged-in user as an associative array.
 *
 * @return array|null The user data, or null if not logged in.
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Get the total quantity of items in the shopping cart.
 *
 * @return int Total number of cart items.
 */
function cart_count(): int
{
    $count = 0;
    foreach ($_SESSION['cart'] ?? [] as $sizes) {
        if (is_array($sizes)) {
            $count += array_sum($sizes);
        } else {
            $count += (int) $sizes;
        }
    }
    return $count;
}

/**
 * Enforce that a user is logged in before accessing a protected page.
 * Redirects to login.php if the user is not authenticated.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['message'] = 'Please log in to continue.';
        header('Location: login.php');
        exit;
    }
}

/**
 * Enforce that the current user has an admin-level role.
 * Redirects to login.php if not authenticated, or shows 403 if unauthorized.
 */
function require_admin(): void
{
    require_login();

    if (!in_array(current_user()['role'], ['club_admin', 'super_admin'], true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

/**
 * Get the IDs of clubs managed by the current user.
 * For super_admin, returns all club IDs.
 * For club_admin, returns their allocated club IDs.
 * For student, returns an empty array.
 *
 * @return int[] Array of club IDs.
 */
function managed_club_ids(): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }

    $db = database();

    if ($user['role'] === 'super_admin') {
        $result = $db->query('SELECT id FROM clubs');
        return $result ? array_column($result->fetch_all(MYSQLI_ASSOC), 'id') : [];
    }

    if ($user['role'] === 'club_admin') {
        $stmt = $db->prepare('SELECT club_id FROM club_admins WHERE user_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            return array_column($result, 'club_id');
        }
        return [];
    }

    return [];
}

/**
 * Get a SQL condition string for filtering by managed clubs.
 * Returns "club_id IN (...)" or "1=1" (for super_admin or empty list).
 *
 * @return string SQL condition fragment.
 */
function managed_club_condition(): string
{
    $user = current_user();
    if (!$user || $user['role'] === 'student') {
        return '1=0';
    }

    $clubIds = managed_club_ids();

    if ($user['role'] === 'super_admin') {
        return '1=1';
    }

    if (empty($clubIds)) {
        return '1=0';
    }

    return 'club_id IN (' . implode(',', array_map('intval', $clubIds)) . ')';
}

/**
 * Check whether the current club_admin manages a specific club.
 *
 * @param int $clubId The club ID to check.
 * @return bool True if the current user manages the club or is super_admin.
 */
function manages_club(int $clubId): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    if ($user['role'] === 'super_admin') {
        return true;
    }

    $db = database();
    $stmt = $db->prepare('SELECT id FROM club_admins WHERE user_id = ? AND club_id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('ii', $user['id'], $clubId);
        $stmt->execute();
        return (bool) $stmt->get_result()->fetch_assoc();
    }
    return false;
}

/**
 * Store a temporary flash message in the session.
 *
 * @param string $message The message text to display.
 */
function set_message(string $message): void
{
    $_SESSION['message'] = $message;
}

/**
 * Retrieve and clear a pending flash message from the session.
 *
 * @return string|null The stored message, or null if none exists.
 */
function pull_message(): ?string
{
    $message = $_SESSION['message'] ?? null;
    unset($_SESSION['message']);

    return $message;
}

/**
 * Check whether a database table exists.
 *
 * @param mysqli $db The database connection.
 * @param string $tableName The table name to check.
 * @return bool True if the table exists, false otherwise.
 */
function table_exists(mysqli $db, string $tableName): bool
{
    // MySQL does not reliably support ? placeholders inside SHOW TABLES LIKE
    // prepared statements — prepare() may silently fail and always return false.
    // Use a direct query with real_escape_string instead, mirroring the approach
    // used by verify_and_migrate() in config/database.php.
    $table = $db->real_escape_string($tableName);
    $result = $db->query("SHOW TABLES LIKE '{$table}'");
    return $result && $result->num_rows > 0;
}
