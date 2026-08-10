<?php
require_once __DIR__ . '/includes/auth.php';

/**
 * AIU Club Store - Logout
 *
 * Destroys the current session and redirects to the homepage.
 */

session_unset();
session_destroy();
session_start();

$_SESSION['message'] = 'You have logged out.';
header('Location: index.php');
exit;
