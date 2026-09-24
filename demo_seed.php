<?php
/**
 * =====================================================================
 * KINETIC TELEMETRY ARCHIVE // DATABASE SEEDER & RESET UTILITY
 * File: demo_seed.php
 * =====================================================================
 * Menginisialisasi ulang tabel products dan menginjeksi 10 data spesimen
 * perangkat keras awal dari database/store_db.sql.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pdo = getDbConnection();
$sqlFile = __DIR__ . '/database/store_db.sql';

if (!file_exists($sqlFile)) {
    die("File schema database/store_db.sql tidak ditemukan.");
}

$sqlContent = file_get_contents($sqlFile);

try {
    // Eksekusi multi-query schema
    $pdo->exec($sqlContent);
    $count = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

    if (php_sapi_name() === 'cli') {
        echo "\033[32m[SUCCESS]\033[0m Basis data berhasil di-reset & diisi ulang! Total spesimen: {$count}\n";
        exit(0);
    }

    setFlash('success', 'Basis data store_db berhasil di-reset dan diisi ulang (' . $count . ' spesimen aktif terdaftar).', 'DATABASE RE-SEEDED');
    redirect('index.php');

} catch (PDOException $e) {
    if (php_sapi_name() === 'cli') {
        echo "\033[31m[ERROR]\033[0m Gagal me-reset basis data: " . $e->getMessage() . "\n";
        exit(1);
    }

    setFlash('danger', 'Gagal me-reset basis data: ' . $e->getMessage());
    redirect('index.php');
}
