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
    return array_sum($_SESSION['cart'] ?? []);
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
