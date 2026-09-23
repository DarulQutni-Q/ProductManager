<?php
/**
 * =====================================================================
 * KINETIC TELEMETRY ARCHIVE // DATABASE CONFIGURATION (PDO)
 * File: config/database.php
 * =====================================================================
 * Membangun koneksi PDO MySQL yang aman, menggunakan prepared statements murni,
 * dan menangani error secara gracefully tanpa membocorkan kredensial sistem.
 */

declare(strict_types=1);

/**
 * Mendapatkan koneksi PDO ke database store_db.
 * Menggunakan pola singleton sederhana untuk menghindari overhead multi-koneksi.
 *
 * @return PDO
 * @throws PDOException
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // Membaca konfigurasi dari environment variables atau fallback lokal
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: 'store_db';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root';

    // DSN spesifikasi UTF-8 multibyte
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname);

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Memastikan prepared statement dieksekusi secara native di server MySQL
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log pesan internal secara aman
        error_log('[DB_CONNECTION_ERROR] ' . $e->getMessage());

        // Jika dipanggil dari script CLI atau web, tampilkan halaman diagnosa yang rapi
        if (php_sapi_name() === 'cli') {
            throw new RuntimeException("Koneksi Database Gagal: " . $e->getMessage());
        }

        // Tampilan error antarmuka industrial jika koneksi gagal
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Koneksi Basis Data Terputus // Kinetic Telemetry</title>
            <style>
                body {
                    background-color: #0b0e14;
                    color: #f1f5f9;
                    font-family: 'Courier New', Courier, monospace;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                    box-sizing: border-box;
                }
                .error-chassis {
                    background: #111622;
                    border: 1px solid #ff334b;
                    padding: 24px;
                    max-width: 600px;
                    width: 100%;
                    border-radius: 8px;
                    box-shadow: 0 10px 30px rgba(255, 51, 75, 0.15);
                }
                h1 { color: #ff334b; margin-top: 0; font-size: 1.25rem; }
                p { color: #94a3b8; font-size: 0.9rem; line-height: 1.5; }
                code { background: #161d2c; padding: 2px 6px; border-radius: 4px; color: #ffb800; }
                .retry-btn {
                    display: inline-block;
                    margin-top: 16px;
                    padding: 8px 16px;
                    background: #ff5c00;
                    color: #fff;
                    text-decoration: none;
                    font-weight: bold;
                    border-radius: 4px;
                }
            </style>
        </head>
        <body>
            <div class="error-chassis">
                <h1>[CRITICAL // DATABASE CONNECTION OFFLINE]</h1>
                <p>Sistem tidak dapat menginisialisasi tautan PDO ke host <code><?= htmlspecialchars($host . ':' . $port, ENT_QUOTES, 'UTF-8') ?></code> dengan basis data <code><?= htmlspecialchars($dbname, ENT_QUOTES, 'UTF-8') ?></code>.</p>
                <p>Pastikan servis MySQL/MariaDB aktif dan skema <code>database/store_db.sql</code> telah diimpor.</p>
                <a href="" class="retry-btn" onclick="location.reload(); return false;">COBA HUBUNGKAN KEMBALI</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
