<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function is_logged_in(): bool { return isset($_SESSION['user']); }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function cart_count(): int { return array_sum($_SESSION['cart'] ?? []); }

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['message'] = 'Please log in to continue.';
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (!in_array(current_user()['role'], ['club_admin', 'super_admin'], true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function set_message(string $message): void { $_SESSION['message'] = $message; }
function pull_message(): ?string { $message = $_SESSION['message'] ?? null; unset($_SESSION['message']); return $message; }
