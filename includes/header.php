<?php
/**
 * =====================================================================
 * PRODUCT MANAGER // SHADCN ZINC MINIMALIST HEADER
 * =====================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$flash = getFlash();
$currentScript = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>ProductManager</title>
    
    <!-- Shadcn / Geist Fonts & Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Security Directives -->
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>

    <!-- TOP NAVIGATION (SHADCN ZINC NAVBAR) -->
    <header class="site-navbar">
        <div class="app-container navbar-inner">
            <!-- Brand Wordmark & Icon -->
            <div class="navbar-brand">
                <a href="index.php" class="navbar-brand" aria-label="ProductManager Beranda">
                    <div class="brand-mark">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                    </div>
                    <span class="brand-title">
                        ProductManager
                        <span class="brand-badge">Inventaris</span>
                    </span>
                </a>

                <!-- Navigation Links -->
                <nav class="navbar-links" style="margin-left: 1.25rem;">
                    <a href="index.php" class="nav-link <?= $currentScript === 'index.php' ? 'is-active' : '' ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <span>Katalog</span>
                    </a>
                    <a href="create.php" class="nav-link <?= $currentScript === 'create.php' ? 'is-active' : '' ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Tambah Produk</span>
                    </a>
                </nav>
            </div>

            <!-- Actions (Add Product CTA & Links) -->
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <a href="create.php" class="btn btn-primary btn-sm" title="Tambah Produk [N]">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Produk Baru</span>
                    <kbd style="background: rgba(255,255,255,0.2); color: #fff; border: none; margin-left: 2px;">N</kbd>
                </a>

                <a href="https://github.com/DarulQutni-Q/ProductManager" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm btn-icon" title="Repository GitHub">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                </a>
            </div>
        </div>
    </header>

    <!-- FLASH NOTIFICATION TOAST -->
    <?php if ($flash): ?>
        <div class="app-container">
            <div class="flash-banner is-<?= e($flash['type']) ?>" role="alert">
                <div style="margin-top: 1px;">
                    <?php if ($flash['type'] === 'success'): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php endif; ?>
                </div>
                <div style="flex: 1;">
                    <div style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 2px;">
                        <?= e($flash['title'] ?: ($flash['type'] === 'success' ? 'Berhasil' : 'Perhatian')) ?>
                    </div>
                    <div style="font-size: 0.8125rem; line-height: 1.4; opacity: 0.9;">
                        <?= e($flash['message']) ?>
                    </div>
                </div>
                <button type="button" class="modal-close-btn flash-dismiss" aria-label="Tutup notifikasi">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </div>
    <?php endif; ?>
