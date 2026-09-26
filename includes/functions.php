<?php
/**
 * =====================================================================
 * KINETIC TELEMETRY ARCHIVE // CORE UTILITY & VALIDATION FUNCTIONS
 * File: includes/functions.php
 * =====================================================================
 * Menghandle sanitasi output XSS, validasi server-side tanpa celah,
 * manajemen session flash (PRG Pattern), dan penanganan upload berkas aman.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';

/**
 * Melakukan escaping kontekstual HTML untuk mencegah serangan Cross-Site Scripting (XSS).
 * Sesuai spesifikasi Slide 16: htmlspecialchars($product["name"], ENT_QUOTES, "UTF-8")
 *
 * @param mixed $value
 * @return string
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Menyimpan pesan flash feedback ke dalam session untuk ditampilkan setelah redirect (PRG).
 *
 * @param string $type success|danger|warning|info
 * @param string $message
 * @param string|null $title
 */
function setFlash(string $type, string $message, ?string $title = null): void
{
    initSession();
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
        'title'   => $title ?? strtoupper($type) . ' TELEMETRY'
    ];
}

/**
 * Mengambil dan langsung menghapus pesan flash dari session (sekali pakai).
 *
 * @return array|null
 */
function getFlash(): ?array
{
    initSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Menyimpan input sebelumnya saat validasi gagal agar form terisi kembali.
 *
 * @param array $data
 */
function setOldInput(array $data): void
{
    initSession();
    // Hilangkan field sensitif jika ada
    unset($data['csrf_token'], $data['image']);
    $_SESSION['old_input'] = $data;
}

/**
 * Mengambil nilai input sebelumnya berdasarkan key.
 *
 * @param string|null $key
 * @param mixed $default
 * @return mixed
 */
function getOldInput(?string $key = null, mixed $default = ''): mixed
{
    initSession();
    if ($key === null) {
        return $_SESSION['old_input'] ?? [];
    }
    return $_SESSION['old_input'][$key] ?? $default;
}

/**
 * Membersihkan cache input sebelumnya.
 */
function clearOldInput(): void
{
    initSession();
    unset($_SESSION['old_input']);
}

/**
 * Menyimpan array pesan error validasi form.
 *
 * @param array<string, string> $errors
 */
function setFormErrors(array $errors): void
{
    initSession();
    $_SESSION['form_errors'] = $errors;
}

/**
 * Mengambil array pesan error validasi.
 *
 * @return array<string, string>
 */
function getFormErrors(): array
{
    initSession();
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form_errors']);
    return $errors;
}

/**
 * Melakukan validasi ketat terhadap input produk sesuai spesifikasi Slide 16 & 20:
 * - Nama >= 3 karakter
 * - Nama unik dalam basis data
 * - Harga > 0
 * - Stok >= 0
 *
 * @param array $input Data mentah dari $_POST
 * @param int|null $currentId ID produk saat ini jika dalam mode Edit (untuk bypass nama sendiri)
 * @return array<string, string> Array asosiatif berisi pesan error per field
 */
function validateProductData(array $input, ?int $currentId = null): array
{
    $errors = [];
    $pdo = getDbConnection();

    // 1. Validasi Nama
    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') {
        $errors['name'] = 'Nama produk wajib diisi.';
    } elseif (mb_strlen($name, 'UTF-8') < 3) {
        $errors['name'] = 'Nama produk minimal harus terdiri dari 3 karakter.';
    } elseif (mb_strlen($name, 'UTF-8') > 150) {
        $errors['name'] = 'Nama produk maksimal 150 karakter.';
    } else {
        // Cek keunikan nama produk di database
        if ($currentId !== null) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE name = :name AND id != :id');
            $stmt->execute(['name' => $name, 'id' => $currentId]);
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE name = :name');
            $stmt->execute(['name' => $name]);
        }
        $exists = (int)$stmt->fetchColumn();
        if ($exists > 0) {
            $errors['name'] = 'Nama produk "' . $name . '" sudah terdaftar dalam sistem. Gunakan nama yang unik.';
        }
    }

    // 2. Validasi Kategori
    $category = trim((string)($input['category'] ?? ''));
    if ($category === '') {
        $errors['category'] = 'Kategori produk wajib dipilih.';
    } elseif (mb_strlen($category, 'UTF-8') > 100) {
        $errors['category'] = 'Kategori produk maksimal 100 karakter.';
    }

    // 3. Validasi Harga
    $rawPrice = $input['price'] ?? '';
    // Ganti pemisah koma jika pengguna menginput format lokal
    $normalizedPrice = str_replace(['.', ','], ['', '.'], (string)$rawPrice);
    if (!is_numeric($rawPrice) && !is_numeric($normalizedPrice)) {
        $errors['price'] = 'Harga produk harus berupa angka valid.';
    } else {
        $price = is_numeric($rawPrice) ? (float)$rawPrice : (float)$normalizedPrice;
        if ($price <= 0) {
            $errors['price'] = 'Harga produk harus lebih besar dari 0 (tidak boleh 0 atau bernilai negatif).';
        }
    }

    // 4. Validasi Stok
    $rawStock = $input['stock'] ?? '';
    if (!is_numeric($rawStock) || (int)$rawStock != (float)$rawStock) {
        $errors['stock'] = 'Kuantitas stok harus berupa bilangan bulat valid.';
    } else {
        $stock = (int)$rawStock;
        if ($stock < 0) {
            $errors['stock'] = 'Kuantitas stok tidak boleh bernilai negatif (minimal 0).';
        }
    }

    return $errors;
}

/**
 * Menangani upload berkas gambar secara aman dengan verifikasi tipe MIME biner,
 * ukuran file, ekstensi whitelist, dan pengacakan nama berkas anti-traversal.
 *
 * @param array|null $file Objek dari $_FILES['image']
 * @param string|null $existingImage Nama file lama jika ada (untuk penggantian)
 * @return array{success: bool, filename: ?string, error: ?string}
 */
function handleProductImageUpload(?array $file, ?string $existingImage = null): array
{
    // Jika tidak ada berkas yang diupload
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => $existingImage, 'error' => null];
    }

    // Periksa status error upload dasar PHP
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'Gagal mengunggah berkas. Kode error: ' . $file['error']];
    }

    // 1. Batas ukuran berkas (Maksimal 2MB = 2097152 bytes)
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'filename' => null, 'error' => 'Ukuran berkas gambar melebihi batas maksimal 2MB.'];
    }

    // 2. Ekstraksi dan sanitasi ekstensi
    $originalName = (string)$file['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $allowedExtensions, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Ekstensi berkas tidak diizinkan. Hanya menerima format: JPG, JPEG, PNG, WEBP.'];
    }

    // 3. Validasi tipe MIME aktual menggunakan Fileinfo (bukan header HTTP klien yang mudah dimanipulasi)
    $tmpPath = (string)$file['tmp_name'];
    if (!is_uploaded_file($tmpPath)) {
        return ['success' => false, 'filename' => null, 'error' => 'Peringatan keamanan: Berkas bukan upload yang sah.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if (!in_array($mimeType, $allowedMimes, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Integritas berkas ditolak: Tipe konten biner terdeteksi (' . $mimeType . ') bukan gambar yang valid.'];
    }

    // 4. Buat direktori penyimpanan aman jika belum ada
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 5. Generate nama berkas acak kriptografis (mencegah overwrite & remote code execution)
    $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $uploadDir . $newFilename;

    if (!move_uploaded_file($tmpPath, $destination)) {
        return ['success' => false, 'filename' => null, 'error' => 'Gagal memindahkan berkas gambar ke direktori penyimpanan server.'];
    }

    // 6. Hapus file lama jika ada dan berhasil digantikan
    if ($existingImage && $existingImage !== $newFilename) {
        $oldFilePath = $uploadDir . basename($existingImage);
        if (is_file($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }

    return ['success' => true, 'filename' => $newFilename, 'error' => null];
}

/**
 * Format mata uang Rupiah Indonesia yang presisi.
 *
 * @param float|int|string $amount
 * @return string Contoh: "Rp 1.450.000"
 */
function formatRupiah(float|int|string $amount): string
{
    $num = (float)$amount;
    return 'Rp ' . number_format($num, 0, ',', '.');
}

/**
 * Menghasilkan metadata status persediaan telemetri.
 *
 * @param int $stock
 * @return array{badge_class: string, status_text: string, dot_color: string}
 */
function getStockBadge(int $stock): array
{
    if ($stock <= 0) {
        return [
            'badge_class' => 'bg-red-50 text-red-700 border-red-200/80',
            'status_text' => 'Habis',
            'dot_color'   => '#ef4444'
        ];
    }

    if ($stock <= 5) {
        return [
            'badge_class' => 'bg-amber-50 text-amber-700 border-amber-200/80',
            'status_text' => 'Sisa ' . $stock . ' unit',
            'dot_color'   => '#f59e0b'
        ];
    }

    return [
        'badge_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
        'status_text' => 'Tersedia (' . $stock . ')',
        'dot_color'   => '#10b981'
    ];
}

/**
 * Mengarahkan (redirect) pengguna secara aman ke URL tujuan dengan HTTP 303 See Other (Pola PRG).
 *
 * @param string $url
 * @param int $code
 * @return never
 */
function redirect(string $url, int $code = 303): never
{
    header('Location: ' . $url, true, $code);
    exit;
}
