<?php
/**
 * =====================================================================
 * KINETIC PRODUCT MANAGER // CATALOG & INVENTORY DASHBOARD
 * Studio Human Interface System
 * =====================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Katalog Produk';
$pdo = getDbConnection();

// --- 1. PARAMETER QUERY GET (SEARCH, FILTER, SORT, PAGINATION) ---
$q = trim((string)($_GET['q'] ?? ''));
$categoryFilter = trim((string)($_GET['category'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? 'newest'));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;

// Whitelist sorting parameter untuk mencegah SQL injection pada klausa ORDER BY
$sortMap = [
    'newest'     => 'id DESC',
    'oldest'     => 'id ASC',
    'price_asc'  => 'price ASC',
    'price_desc' => 'price DESC',
    'stock_asc'  => 'stock ASC',
    'stock_desc' => 'stock DESC'
];
$orderBy = $sortMap[$sort] ?? 'id DESC';

// --- 2. STATISTIK RINGKASAN INVENTARIS ---
$statsQuery = "SELECT 
    COUNT(*) as total_items,
    COALESCE(SUM(price * stock), 0) as total_valuation,
    COALESCE(SUM(stock), 0) as total_units,
    COUNT(CASE WHEN stock <= 5 THEN 1 END) as critical_items
FROM products";
$stats = $pdo->query($statsQuery)->fetch() ?: [
    'total_items' => 0,
    'total_valuation' => 0,
    'total_units' => 0,
    'critical_items' => 0
];

// Ambil daftar seluruh kategori unik beserta jumlah itemnya untuk filter pills
$catCountsStmt = $pdo->query("SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY category ASC");
$categoryCounts = [];
while ($row = $catCountsStmt->fetch()) {
    $categoryCounts[$row['category']] = (int)$row['count'];
}

// --- 3. BANGUN KUERI FILTER SECARA AMAN DENGAN PARAMETER BINDING ---
$whereClauses = [];
$params = [];

if ($q !== '') {
    $whereClauses[] = "(name LIKE :q_name OR category LIKE :q_cat OR description LIKE :q_desc)";
    $params['q_name'] = "%$q%";
    $params['q_cat']  = "%$q%";
    $params['q_desc'] = "%$q%";
}

if ($categoryFilter !== '') {
    $whereClauses[] = "category = :cat_filter";
    $params['cat_filter'] = $categoryFilter;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Hitung total data hasil filter untuk paginasi
$countSql = "SELECT COUNT(*) FROM products $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalFiltered = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// Ambil data produk halaman ini dengan LIMIT dan OFFSET
$dataSql = "SELECT * FROM products $whereSql ORDER BY $orderBy LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($dataSql);

// Bind parameter pencarian/filter
foreach ($params as $key => $val) {
    $stmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. BENTO STATS SECTION -->
<section class="stats-section" aria-label="Ringkasan Inventaris">
    <div class="stats-grid-row">
        <div class="metric-card">
            <div class="metric-header">
                <span>Total Produk</span>
                <span class="metric-badge">SKU</span>
            </div>
            <div class="metric-number"><?= number_format((int)$stats['total_items']) ?></div>
            <div class="metric-sub">Katalog perangkat terdaftar</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <span>Valuasi Inventaris</span>
                <span class="metric-badge">IDR</span>
            </div>
            <div class="metric-number" style="color: var(--brand-blue);">
                <?= formatRupiah($stats['total_valuation']) ?>
            </div>
            <div class="metric-sub">Akumulasi nilai seluruh unit</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <span>Total Unit Fisik</span>
                <span class="metric-badge">UNIT</span>
            </div>
            <div class="metric-number"><?= number_format((int)$stats['total_units']) ?></div>
            <div class="metric-sub">Kuantitas persediaan fisik</div>
        </div>

        <div class="metric-card">
            <div class="metric-header">
                <span>Stok Menipis (&le; 5)</span>
                <span class="metric-badge">STATUS</span>
            </div>
            <div class="metric-number" style="color: <?= (int)$stats['critical_items'] > 0 ? 'var(--status-amber)' : 'var(--status-green)' ?>;">
                <?= number_format((int)$stats['critical_items']) ?> SKU
            </div>
            <div class="metric-sub">Perlu restock segera</div>
        </div>
    </div>
</section>

<!-- 2. INTERACTIVE SEARCH & CATEGORY TOOLBAR -->
<section class="toolbar-section" aria-label="Pencarian dan Filter">
    <div class="toolbar-panel">
        <form method="GET" action="index.php" class="toolbar-controls" id="filter-form">
            <!-- Search Box with Live Debounce & Keyboard Shortcut Hint -->
            <div class="search-field">
                <svg class="search-icon-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input 
                    type="text" 
                    name="q" 
                    id="search-input"
                    class="search-input" 
                    placeholder="Cari nama, kategori, atau spesifikasi..." 
                    value="<?= e($q) ?>"
                    autocomplete="off"
                >
                <span class="search-shortcut-hint">
                    <kbd>/</kbd>
                </span>
            </div>

            <!-- Category Pills with Dynamic Counts -->
            <div class="category-tags">
                <a href="index.php?<?= e(http_build_query(array_merge($_GET, ['category' => '', 'page' => 1]))) ?>" 
                   class="cat-pill <?= $categoryFilter === '' ? 'is-active' : '' ?>"
                   data-category-slug="">
                    <span>Semua</span>
                    <span class="pill-count">(<?= (int)$stats['total_items'] ?>)</span>
                </a>
                <?php foreach ($categoryCounts as $catName => $catCount): ?>
                    <a href="index.php?<?= e(http_build_query(array_merge($_GET, ['category' => $catName, 'page' => 1]))) ?>" 
                       class="cat-pill <?= $categoryFilter === $catName ? 'is-active' : '' ?>"
                       data-category-slug="<?= e($catName) ?>">
                        <span><?= e($catName) ?></span>
                        <span class="pill-count">(<?= $catCount ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Sort Dropdown -->
            <div>
                <select name="sort" class="sort-dropdown" onchange="this.form.submit()">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Terlama</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Harga Terendah</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Harga Tertinggi</option>
                    <option value="stock_asc" <?= $sort === 'stock_asc' ? 'selected' : '' ?>>Stok Terendah</option>
                    <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>Stok Terbanyak</option>
                </select>
                <?php if ($categoryFilter !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
                <?php endif; ?>
            </div>

            <!-- Reset Filter Link -->
            <?php if ($q !== '' || $categoryFilter !== '' || $sort !== 'newest'): ?>
                <a href="index.php" class="btn btn-sm btn-secondary">
                    Reset Filter
                </a>
            <?php endif; ?>
        </form>
    </div>
</section>

<!-- 3. PRODUCT SHOWCASE GRID -->
<main class="main-catalog-area">
    <div class="catalog-header">
        <h2 class="catalog-title">Katalog Produk</h2>
        <div class="catalog-count" id="live-catalog-count">
            Menampilkan <?= count($products) ?> dari <?= $totalFiltered ?> perangkat
        </div>
    </div>

    <div class="product-grid" id="product-grid-container">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <?php 
                    $stock = (int)$product['stock'];
                    $skuCode = 'SKU-' . str_pad((string)$product['id'], 4, '0', STR_PAD_LEFT);
                ?>
                <article 
                    class="product-card"
                    data-product-id="<?= (int)$product['id'] ?>"
                    data-product-name="<?= e($product['name']) ?>"
                    data-product-category="<?= e($product['category']) ?>"
                    data-product-price="<?= (float)$product['price'] ?>"
                    data-product-price-formatted="<?= formatRupiah($product['price']) ?>"
                    data-product-stock="<?= $stock ?>"
                    data-product-sku="<?= e($skuCode) ?>"
                    data-product-desc="<?= e($product['description'] ?: 'Tidak ada dokumentasi catatan tambahan.') ?>"
                >
                    <!-- Top Category & SKU Meta -->
                    <div class="product-card-top">
                        <span class="product-sku"><?= e($skuCode) ?></span>
                        <span class="product-category-tag"><?= e($product['category']) ?></span>
                    </div>

                    <!-- Product Image Container -->
                    <div class="product-image-frame">
                        <?php if (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image'])): ?>
                            <img 
                                src="uploads/<?= e($product['image']) ?>" 
                                alt="Foto <?= e($product['name']) ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="product-image-placeholder">
                                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="2" width="20" height="20" rx="4"></rect>
                                    <path d="M12 2v20"></path>
                                    <path d="M2 12h20"></path>
                                    <circle cx="12" cy="12" r="4"></circle>
                                </svg>
                                <span>Perangkat Keras</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Title (Slide 16: htmlspecialchars with ENT_QUOTES) -->
                    <h3 class="product-title">
                        <?= htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8') ?>
                    </h3>

                    <!-- Product Description -->
                    <p class="product-desc">
                        <?= e($product['description'] ?: 'Tidak ada dokumentasi catatan tambahan.') ?>
                    </p>

                    <!-- Price & Stock Row -->
                    <div class="product-meta-row">
                        <div class="price-block">
                            <span class="price-caption">Harga</span>
                            <span class="price-val"><?= formatRupiah($product['price']) ?></span>
                        </div>

                        <div>
                            <?php if ($stock <= 0): ?>
                                <span class="stock-tag stock-depleted">
                                    <span>Habis</span>
                                </span>
                            <?php elseif ($stock <= 5): ?>
                                <span class="stock-tag stock-critical">
                                    <span>Sisa <?= $stock ?> unit</span>
                                </span>
                            <?php else: ?>
                                <span class="stock-tag stock-nominal">
                                    <span>Tersedia &bull; <?= $stock ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Controls: Detail Inspector, Edit, & Safe Delete -->
                    <div class="product-actions">
                        <button type="button" class="btn btn-sm btn-quick-view btn-inspect-trigger" title="Lihat detail lengkap spesifikasi">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <span>Detail</span>
                        </button>

                        <a href="edit.php?id=<?= (int)$product['id'] ?>" class="btn btn-sm btn-secondary" title="Edit produk">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                            </svg>
                            <span>Edit</span>
                        </a>

                        <button 
                            type="button" 
                            class="btn btn-sm btn-danger btn-delete-trigger" 
                            data-id="<?= (int)$product['id'] ?>" 
                            data-name="<?= e($product['name']) ?>"
                            title="Hapus produk"
                        >
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            <span>Hapus</span>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; background: #ffffff; border: 1px solid var(--line-hairline); border-radius: var(--radius-xl); padding: 4rem 1.5rem; text-align: center; box-shadow: var(--shadow-subtle);">
                <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.35rem; color: var(--text-title);">Tidak Ada Produk yang Sesuai</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 440px; margin: 0 auto 1.5rem;">
                    Tidak ditemukan perangkat dengan filter saat ini. Coba gunakan kata kunci pencarian lain atau bersihkan filter.
                </p>
                <div style="display: flex; justify-content: center; gap: 10px;">
                    <a href="index.php" class="btn btn-secondary btn-sm">Bersihkan Filter</a>
                    <a href="create.php" class="btn btn-primary btn-sm">+ Tambah Produk</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 4. PAGINATION -->
    <?php if ($totalPages > 1): ?>
        <nav class="pagination-container" aria-label="Navigasi Halaman">
            <?php if ($page > 1): ?>
                <a href="index.php?<?= e(http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>" class="page-link">
                    &larr; Sebelumnya
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a 
                    href="index.php?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>" 
                    class="page-link <?= $i === $page ? 'is-current' : '' ?>"
                >
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="index.php?<?= e(http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>" class="page-link">
                    Berikutnya &rarr;
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
