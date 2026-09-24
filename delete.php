<?php
/**
 * =====================================================================
 * KINETIC TELEMETRY ARCHIVE // PRODUCT DELETION CONTROLLER (DELETE)
 * File: delete.php
 * =====================================================================
 * Menangani penghapusan data secara aman dengan penegakan mutlak metode POST,
 * verifikasi kriptografis token CSRF, pembersihan file fisik, dan Pola PRG.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

// 1. PENEGAKAN METODE HTTP: Hanya menerima POST (Mencegah eksploitasi via tautan GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    setFlash('danger', 'Protokol Ditolak: Penghapusan asset hanya diizinkan melalui metode HTTP POST terotentikasi.');
    redirect('index.php');
}

// 2. VERIFIKASI CSRF TOKEN (Slide 16: Delete: POST + CSRF)
$csrfToken = (string)($_POST['csrf_token'] ?? '');
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    setFlash('danger', 'Otorisasi Gagal: Token keamanan CSRF tidak valid atau sesi browser telah berakhir.');
    redirect('index.php');
}

// 3. VALIDASI IDENTIFIKASI PRODUK (ID)
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Identifikasi spesimen tidak valid. Nilai ID harus berupa integer positif.');
    redirect('index.php');
}

$pdo = getDbConnection();

try {
    // 4. Periksa Keberadaan Produk & Ambil Metadata Berkas Gambar
    $checkStmt = $pdo->prepare('SELECT id, name, image FROM products WHERE id = :id');
    $checkStmt->execute([':id' => $id]);
    $product = $checkStmt->fetch();

    if (!$product) {
        setFlash('danger', 'Asset yang ingin dihapus (ID #' . $id . ') tidak ditemukan di dalam inventaris.');
        redirect('index.php');
    }

    $productName = (string)$product['name'];
    $imageFilename = $product['image'];

    // 5. Eksekusi DELETE dengan PDO Prepared Statement (Slide 16)
    $deleteStmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $deleteStmt->execute([':id' => $id]);

    // 6. Hapus Berkas Gambar Fisik dari Filesystem Jika Ada
    if (!empty($imageFilename)) {
        $filePath = __DIR__ . '/uploads/' . basename($imageFilename);
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    // 7. Regenerasi CSRF Token untuk Rotasi Kriptografis
    regenerateCsrfToken();

    // 8. Pola PRG: Set Flash Success & Redirect 303 ke index.php
    $skuFormatted = 'SKU-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
    setFlash('success', 'Produk "' . $productName . '" [' . $skuFormatted . '] berhasil dihapus dari basis data.', 'Produk Berhasil Dihapus');
    redirect('index.php');

} catch (PDOException $e) {
    error_log('[DELETE_PRODUCT_ERROR] ' . $e->getMessage());
    setFlash('danger', 'Kegagalan sistem saat mengeksekusi penghapusan dari basis data.');
    redirect('index.php');
}
