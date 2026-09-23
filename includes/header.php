<?php
/**
 * =====================================================================
 * KINETIC PRODUCT MANAGER // EDITORIAL & MINIMALIST HEADER
 * =====================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>Kinetic Product Studio</title>
    
    <!-- Minimalist & Editorial Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Security & Crawling Directives -->
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>

    <!-- 1. STUDIO HEADER -->
    <header class="site-header">
        <div class="nav-container">
            <!-- Bespoke Monochrome Emblem & Wordmark -->
            <a href="index.php" class="brand-link" aria-label="Kinetic Product Studio Beranda">
                <div class="brand-logo-mark">
                    <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Architectural Precision Monochrome Emblem -->
                        <rect x="2" y="2" width="28" height="28" rx="5" fill="#111111"/>
                        <rect x="7" y="7" width="8" height="18" fill="#FFFFFF"/>
                        <path d="M15 16L24 7H25V9L18 16L25 23V25H24L15 16Z" fill="#FFFFFF"/>
                        <rect x="21" y="14" width="4" height="4" fill="#C4E7FD"/>
                    </svg>
                </div>
                <div class="brand-meta">
                    <div class="brand-name">
                        <span>Kinetic</span>
                        <span class="sub">Studio</span>
                    </div>
                    <div class="brand-tagline">Hardware &bull; Inventory Registry</div>
                </div>
            </a>

            <!-- Action Controls -->
            <nav class="nav-actions">
                <a href="index.php" class="btn btn-sm btn-secondary" title="Buka Katalog [K]">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Katalog</span>
                </a>
                <a href="create.php" class="btn btn-sm btn-primary" title="Tambah Produk Baru [N]">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Tambah Produk</span>
                </a>
            </nav>
        </div>
    </header>

    <!-- 2. FLASH TOAST BANNER (PRG NOTIFICATION) -->
    <?php if ($flash): ?>
        <div class="flash-container">
            <div class="flash-alert flash-alert-<?= e($flash['type']) ?>" role="alert">
                <div class="flash-indicator">
                    <?php if ($flash['type'] === 'success'): ?>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php else: ?>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php endif; ?>
                </div>
                <div class="flash-content">
                    <div class="flash-title"><?= e($flash['title']) ?></div>
                    <div class="flash-message"><?= e($flash['message']) ?></div>
                </div>
                <button type="button" class="flash-dismiss" aria-label="Tutup notifikasi">&times;</button>
            </div>
        </div>
    <?php endif; ?>
