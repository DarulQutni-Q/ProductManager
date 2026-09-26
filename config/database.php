<?php
/**
 * =====================================================================
 * PRODUCT MANAGER // DATABASE CONFIGURATION (PDO DUAL-ENGINE)
 * File: config/database.php
 * =====================================================================
 * Mendukung koneksi MySQL (XAMPP / Laragon / Production) secara default,
 * dengan fallback cerdas ke SQLite untuk portabilitas instan tanpa setup daemon.
 */

declare(strict_types=1);

/**
 * Inisialisasi koneksi SQLite beserta migrasi tabel dan seeding otomatis jika kosong.
 */
function initSqliteConnection(string $sqlitePath): PDO
{
    $dir = dirname($sqlitePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Pastikan foreign keys & WAL mode aktif untuk performa optimal
    $pdo->exec('PRAGMA journal_mode = WAL;');
    $pdo->exec('PRAGMA foreign_keys = ON;');

    // Buat tabel jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        category TEXT NOT NULL,
        price REAL NOT NULL CHECK(price > 0),
        stock INTEGER NOT NULL DEFAULT 0 CHECK(stock >= 0),
        image TEXT DEFAULT NULL,
        description TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // Jika tabel kosong, seed 10 data awal
    $count = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($count === 0) {
        $seedData = [
            ['STM32H753 Arm Cortex-M7 Core Unit', 'Microcontroller', 1450000.00, 24, 'stm32h753.jpg', 'Unit prosesor kendali industri dual-core 480MHz dengan hardware cryptographic accelerator dan antarmuka CAN-FD.'],
            ['SCD41 NDIR Photoacoustic CO2 Sensor', 'Sensor Unit', 620000.00, 15, 'scd41_co2.jpg', 'Sensor pemantauan emisi CO2 presisi tinggi dengan teknologi photoacoustic dan kompensasi temperatur otomatis.'],
            ['Vibration Telemetry Piezo Probe v4', 'Sensor Unit', 890000.00, 4, 'piezo_probe.jpg', 'Sensor getaran akselerometer industri 3-axis dengan bandwidth 10kHz untuk pemantauan prediktif bearing motor.'],
            ['Isolated DC-DC Buck Module 48V to 12V 10A', 'Power Module', 475000.00, 38, 'dcdc_buck.jpg', 'Konverter daya industri efisiensi 94% dengan isolasi galvanik 1500VDC dan proteksi transient over-voltage.'],
            ['BNO085 9-DOF IMU Precision Tracker', 'Sensor Unit', 785000.00, 2, 'bno085_imu.jpg', 'Sensor navigasi inersia 9-axis dengan integrated sensor fusion algorithm untuk robotika otonom dan telemetry avionik.'],
            ['SX1262 LoRa 915MHz Transceiver Module', 'Telemetry Radio', 310000.00, 42, 'sx1262_lora.jpg', 'Modul transmisi data nirkabel jarak jauh ultra-low power hingga 15km line-of-sight dengan enkripsi AES-128 hardware.'],
            ['Linear Stepper Actuator NEMA 23 150mm', 'Mechanical Actuator', 1250000.00, 8, 'nema23_actuator.jpg', 'Aktuator linier industri dengan bola presisi lead-screw C7, torsi dorong 800N, dan resolusi langkah 0.01mm.'],
            ['GaN-FET Ultra-Fast Dual Gate Driver', 'Power Module', 285000.00, 0, 'gan_fet_driver.jpg', 'Modul driver switching daya berbasis Gallium Nitride frekuensi 10MHz untuk catu daya resonan tingkat lanjut.'],
            ['ESP32-S3 AI Telemetry Edge Gateway', 'Microcontroller', 215000.00, 56, 'esp32s3_gateway.jpg', 'Gateway nirkabel Wi-Fi 4 + BLE 5.0 dengan akselerator vektor neural network untuk inferensi machine learning di sisi edge.'],
            ['Industrial Current Clamp 100A Hall-Effect', 'Sensor Unit', 540000.00, 11, 'current_clamp.jpg', 'Sensor pembacaan arus AC/DC non-kontak berpresisi tinggi dengan linearitas 0.5% dan output tegangan terkalibrasi.']
        ];

        $stmt = $pdo->prepare('INSERT INTO products (name, category, price, stock, image, description) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($seedData as $row) {
            $stmt->execute($row);
        }
    }

    return $pdo;
}

/**
 * Mendapatkan koneksi basis data PDO (MySQL atau SQLite Fallback)
 *
 * @return PDO
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $sqlitePath = __DIR__ . '/../database/store_db.sqlite';
    $driver = getenv('DB_CONNECTION') ?: 'auto';

    // Jika pengguna secara eksplisit meminta SQLite
    if ($driver === 'sqlite') {
        $pdo = initSqliteConnection($sqlitePath);
        return $pdo;
    }

    // Konfigurasi MySQL default
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: 'store_db';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname);
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    try {
        // Coba koneksi ke server MySQL
        $pdo = new PDO($dsn, $username, $password, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Jika MySQL tidak aktif atau ditolak, alihkan otomatis ke SQLite bawaan
        error_log('[DB_NOTICE] MySQL tidak tersedia (' . $e->getMessage() . '). Menggunakan SQLite fallback.');
        try {
            $pdo = initSqliteConnection($sqlitePath);
            return $pdo;
        } catch (Throwable $fallbackError) {
            error_log('[DB_FATAL] Kegagalan inisialisasi basis data: ' . $fallbackError->getMessage());
        }

        if (php_sapi_name() === 'cli') {
            throw new RuntimeException("Koneksi database gagal (MySQL & SQLite): " . $e->getMessage());
        }

        // Tampilan Shadcn Dialog Error jika kedua engine gagal
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Koneksi Database Gagal - ProductManager</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&display=swap" rel="stylesheet">
            <style>body { font-family: 'Geist', sans-serif; }</style>
        </head>
        <body class="min-h-screen bg-zinc-50 flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-white border border-zinc-200 rounded-lg shadow-sm p-6 text-zinc-900">
                <div class="flex items-center gap-3 text-red-600 mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <h1 class="text-lg font-semibold">Koneksi Database Terputus</h1>
                </div>
                <p class="text-sm text-zinc-500 mb-4">Tidak dapat menghubungkan ke MySQL ataupun SQLite. Pastikan hak akses direktori database dapat ditulis.</p>
                <div class="bg-zinc-50 border border-zinc-200 rounded p-3 text-xs font-mono text-zinc-700 mb-4 overflow-x-auto">
                    <?= htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <a href="index.php" class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-zinc-900 rounded-md hover:bg-zinc-800 transition-colors">Muat Ulang Halaman</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
