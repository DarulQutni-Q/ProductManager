<?php
/**
 * =====================================================================
 * KINETIC TELEMETRY ARCHIVE // CSRF PROTECTION MODULE
 * File: config/csrf.php
 * =====================================================================
 * Melindungi seluruh mutasi state (Create, Update, Delete) dari serangan
 * Cross-Site Request Forgery (CSRF) menggunakan token kriptografis acak
 * dan verifikasi berbasis perbandingan waktu konstan (hash_equals).
 */

declare(strict_types=1);

/**
 * Inisialisasi session aman jika belum aktif.
 */
function initSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Konfigurasi cookie session aman
        $cookieParams = [
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        session_set_cookie_params($cookieParams);
        session_start();
    }
}

/**
 * Mengambil atau menghasilkan token CSRF kriptografis unik untuk session pengguna.
 *
 * @return string
 */
function getCsrfToken(): string
{
    initSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Menghasilkan elemen tag input hidden HTML untuk disisipkan ke dalam form POST.
 *
 * @return string
 */
function csrfField(): string
{
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Memverifikasi validitas token CSRF dari request POST menggunakan hash_equals
 * untuk mencegah timing attack.
 *
 * @param string|null $token
 * @return bool
 */
function verifyCsrfToken(?string $token): bool
{
    initSession();

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Meregenerasi token CSRF setelah aksi mutasi state berhasil untuk mencegah token hijacking.
 */
function regenerateCsrfToken(): void
{
    initSession();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
