<?php
declare(strict_types=1);

/**
 * AIU Club Store - Database Configuration
 *
 * Provides a singleton MySQLi connection for the application.
 * Uses utf8mb4 charset for full Unicode support.
 *
 * This file now performs a lightweight startup verification: it logs the
 * database connection (obfuscated), verifies that required tables exist,
 * and (optionally) invokes the setup script once to create missing tables.
 */

// Database connection constants
const DB_HOST = 'localhost';
const DB_NAME = 'aiu_club_store';
const DB_USER = 'root';
const DB_PASS = '';

/**
 * Mask a secret for logs (keeps length but hides characters).
 */
function mask_secret(string $s): string
{
    if ($s === '') {
        return "(empty)";
    }
    return str_repeat('*', max(1, min(8, strlen($s))));
}

/**
 * Verify required tables exist. If missing and $autoRunSetup is true, try to run
 * setup_database.php via CLI and re-check. On persistent failure, emit a clear
 * 503 response and exit so the app doesn't serve requests with a partial schema.
 */
function verify_and_migrate(mysqli $conn, array $expectedTables, bool $autoRunSetup = true): void
{
    // Log connection info (do not log password in cleartext)
    $connInfo = sprintf("DB host=%s db=%s user=%s pass=%s", DB_HOST, DB_NAME, DB_USER, mask_secret(DB_PASS));
    error_log('[DB_STARTUP] ' . $connInfo);

    $missing = [];
    foreach ($expectedTables as $table) {
        $tbl = $conn->real_escape_string($table);
        $res = $conn->query("SHOW TABLES LIKE '{$tbl}'");
        if (!($res && $res->num_rows > 0)) {
            $missing[] = $table;
        }
    }

    if (empty($missing)) {
        error_log('[DB_STARTUP] All expected tables present: ' . implode(', ', $expectedTables));
    } else {
        error_log('[DB_STARTUP] Missing tables detected: ' . implode(', ', $missing));
    }

    if ($autoRunSetup && !empty($missing)) {
        $setupPath = __DIR__ . '/../setup_database.php';
        if (is_file($setupPath) && is_readable($setupPath)) {
            error_log('[DB_STARTUP] Attempting to run setup script: ' . $setupPath);

            // Run setup script via PHP CLI to ensure it executes in the same PHP binary.
            $php = defined('PHP_BINARY') ? PHP_BINARY : 'php';
            $cmd = escapeshellcmd($php) . ' ' . escapeshellarg($setupPath) . ' 2>&1';

            // Execute and capture output for logs. This may take a few seconds.
            $output = [];
            $returnVar = 0;
            @exec($cmd, $output, $returnVar);

            error_log('[DB_STARTUP] setup_database.php exit code: ' . $returnVar);
            if (!empty($output)) {
                foreach ($output as $line) {
                    error_log('[DB_SETUP_OUTPUT] ' . $line);
                }
            }

            // Re-check tables after running setup.
            $stillMissing = [];
            foreach ($missing as $table) {
                $tbl = $conn->real_escape_string($table);
                $res = $conn->query("SHOW TABLES LIKE '{$tbl}'");
                if (!($res && $res->num_rows > 0)) {
                    $stillMissing[] = $table;
                }
            }

            if (empty($stillMissing)) {
                error_log('[DB_STARTUP] setup script created missing tables successfully.');
                $missing = [];
            } else {
                error_log('[DB_STARTUP] setup script did not create tables: ' . implode(', ', $stillMissing));
                $missing = $stillMissing;
            }
        } else {
            error_log('[DB_STARTUP] setup_database.php not found or not readable: ' . $setupPath);
        }
    }

    if (!empty($missing)) {
        // Final failure: inform the caller and stop further processing to avoid runtime errors.
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Service Unavailable: required database tables are missing: " . implode(', ', $missing) . "\n";
        echo "Please run setup_database.php (visit /setup_database.php) or ensure migrations were applied.";

        // Add a helpful log entry before exiting.
        error_log('[DB_STARTUP] Aborting startup due to missing tables: ' . implode(', ', $missing));
        exit(1);
    }

    // -------------------------------------------------------------------------
    // Seed demo data when the schema exists but has no user accounts.
    // This keeps login functional even when the schema was created from
    // database.sql (which is schema-only) instead of relying on the app to
    // auto-run setup only when tables are entirely missing.
    // -------------------------------------------------------------------------

    if ($autoRunSetup) {
        $userCountRes = $conn->query('SELECT COUNT(*) AS total FROM users');
        $userCount = $userCountRes ? (int) $userCountRes->fetch_assoc()['total'] : 0;

        if ($userCount === 0) {
            $setupPath = __DIR__ . '/../setup_database.php';
            if (is_file($setupPath) && is_readable($setupPath)) {
                error_log('[DB_STARTUP] No users found; running setup script to seed demo data: ' . $setupPath);

                $php = defined('PHP_BINARY') ? PHP_BINARY : 'php';
                $cmd = escapeshellcmd($php) . ' ' . escapeshellarg($setupPath) . ' 2>&1';

                $output = [];
                $returnVar = 0;
                @exec($cmd, $output, $returnVar);

                error_log('[DB_STARTUP] setup_database.php (seed) exit code: ' . $returnVar);
                if (!empty($output)) {
                    foreach ($output as $line) {
                        error_log('[DB_SETUP_OUTPUT] ' . $line);
                    }
                }
            } else {
                error_log('[DB_STARTUP] setup_database.php not found or not readable: ' . $setupPath);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Column-level migrations for existing databases
    // -------------------------------------------------------------------------

    $columnMigrations = [
        'order_items' => [
            'size' => "ALTER TABLE order_items ADD COLUMN size VARCHAR(20) NULL AFTER product_id",
        ],
    ];

    foreach ($columnMigrations as $table => $columns) {
        $tbl = $conn->real_escape_string($table);
        $tableExists = $conn->query("SHOW TABLES LIKE '{$tbl}'");
        if (!($tableExists && $tableExists->num_rows > 0)) {
            continue;
        }

        foreach ($columns as $column => $sql) {
            $col = $conn->real_escape_string($column);
            $res = $conn->query("SHOW COLUMNS FROM {$tbl} LIKE '{$col}'");
            if (!($res && $res->num_rows > 0)) {
                error_log("[DB_STARTUP] Missing column detected: {$table}.{$column}");
                if ($conn->query($sql) === true) {
                    error_log("[DB_STARTUP] Added missing column: {$table}.{$column}");
                } else {
                    error_log("[DB_STARTUP] Failed to add column {$table}.{$column}: " . $conn->error);
                }
            }
        }
    }
}

/**
 * Returns a singleton database connection instance and verifies schema on first use.
 *
 * @return mysqli The shared MySQLi connection.
 */
function database(): mysqli
{
    static $connection = null;

    if ($connection === null) {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($connection->connect_error) {
            // Prefer logging the error to the server logs and show a brief message.
            error_log('[DB_STARTUP] Connection failed: ' . $connection->connect_error);
            http_response_code(503);
            exit('Database connection failed. Ensure MySQL is running and credentials in config/database.php are correct.');
        }

        $connection->set_charset('utf8mb4');

        // Verify expected tables and attempt to run setup if missing.
        $expectedTables = ['users', 'clubs', 'club_admins', 'products', 'product_sizes', 'events', 'orders', 'order_items', 'tickets', 'payments'];
        verify_and_migrate($connection, $expectedTables, /* autoRunSetup */ true);
    }

    return $connection;
}
