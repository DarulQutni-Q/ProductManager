<?php
/**
 * =====================================================================
 * PRODUCT MANAGER // CATALOG & INVENTORY DASHBOARD (SHADCN ZINC)
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
$perPage = 8;

// Whitelist sorting parameter
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

// Daftar kategori untuk tab filter
$catCountsStmt = $pdo->query("SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY category ASC");
$categoryCounts = [];
while ($row = $catCountsStmt->fetch()) {
    $categoryCounts[$row['category']] = (int)$row['count'];
}

// --- 3. BANGUN QUERY FILTER SECARA AMAN ---
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

// Hitung total filtered
$countSql = "SELECT COUNT(*) FROM products $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalFiltered = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// Ambil data produk
$dataSql = "SELECT * FROM products $whereSql ORDER BY $orderBy LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($dataSql);

foreach ($params as $key => $val) {
    $stmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<main class="app-container" style="flex: 1; padding-bottom: 3rem;">

    <!-- 1. PAGE HEADER -->
    <div class="page-header-row">
        <div class="page-header-titles">
            <h1>Katalog Produk</h1>
            <p>Kelola inventaris suku cadang, modul IoT, dan status stok persediaan gudang.</p>
        </div>
        <div class="page-header-actions">
            <a href="database/store_db.sql" download class="btn btn-outline btn-sm" title="Download cadangan database SQL">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Ekspor SQL</span>
            </a>
            <a href="create.php" class="btn btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Tambah Produk</span>
            </a>
        </div>
    </div>

    <!-- 2. MINIMALIST KPI SUMMARY STRIP (High-density, Anti-slop) -->
    <section class="kpi-strip" aria-label="Ringkasan Inventaris">
        <div class="kpi-pill">
            <div class="kpi-pill-label">
                <span>Total Katalog</span>
                <span class="badge badge-outline" style="font-size: 0.6875rem; padding: 0 0.35rem;">SKU Aktif</span>
            </div>
            <div class="kpi-pill-val font-mono"><?= number_format((int)$stats['total_items']) ?> SKU</div>
        </div>

        <div class="kpi-pill">
            <div class="kpi-pill-label">
                <span>Total Valuasi Aset</span>
                <span class="badge badge-outline" style="font-size: 0.6875rem; padding: 0 0.35rem;">Akumulasi</span>
            </div>
            <div class="kpi-pill-val font-mono"><?= formatRupiah($stats['total_valuation']) ?></div>
        </div>

        <div class="kpi-pill">
            <div class="kpi-pill-label">
                <span>Kuantitas Fisik</span>
                <span class="badge badge-outline" style="font-size: 0.6875rem; padding: 0 0.35rem;">Gudang</span>
            </div>
            <div class="kpi-pill-val font-mono"><?= number_format((int)$stats['total_units']) ?> Unit</div>
        </div>

        <div class="kpi-pill">
            <div class="kpi-pill-label">
                <span>Stok Kritis (≤ 5)</span>
                <?php if ((int)$stats['critical_items'] > 0): ?>
                    <span class="badge badge-amber" style="font-size: 0.6875rem; padding: 0 0.35rem;">Perhatian</span>
                <?php else: ?>
                    <span class="badge badge-emerald" style="font-size: 0.6875rem; padding: 0 0.35rem;">Aman</span>
                <?php endif; ?>
            </div>
            <div class="kpi-pill-val font-mono" style="color: <?= (int)$stats['critical_items'] > 0 ? '#f59e0b' : '#10b981' ?>;">
                <?= number_format((int)$stats['critical_items']) ?> SKU
            </div>
        </div>
    </section>

    <!-- 3. UNIFIED SEARCH & FILTER TOOLBAR -->
    <section class="catalog-toolbar" aria-label="Pencarian dan Filter">
        <form method="GET" action="index.php" id="filter-form">
            <div class="toolbar-row">
                <!-- Search input with shortcut hint -->
                <div class="search-box">
                    <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input 
                        type="text" 
                        name="q" 
                        id="search-input"
                        class="search-input" 
                        placeholder="Cari produk, kategori, atau spesifikasi..." 
                        value="<?= e($q) ?>"
                        autocomplete="off"
                    >
                    <span class="search-kbd"><kbd>/</kbd></span>
                </div>

                <!-- Sort & View Mode Group -->
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-left: auto;">
                    <select name="sort" class="select-control" onchange="this.form.submit()" aria-label="Urutkan Data">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Terlama</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Harga: Rendah ke Tinggi</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Harga: Tinggi ke Rendah</option>
                        <option value="stock_asc" <?= $sort === 'stock_asc' ? 'selected' : '' ?>>Stok: Paling Sedikit</option>
                        <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>Stok: Paling Banyak</option>
                    </select>

                    <!-- View Mode Toggle Buttons (Grid vs Table) -->
                    <div class="view-toggle-group">
                        <button type="button" class="view-toggle-btn is-active" id="btn-view-grid" title="Tampilan Grid [V]" aria-label="Tampilan Grid">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        </button>
                        <button type="button" class="view-toggle-btn" id="btn-view-table" title="Tampilan Tabel Data [V]" aria-label="Tampilan Tabel Data">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                        </button>
                    </div>

                    <?php if ($q !== '' || $categoryFilter !== '' || $sort !== 'newest'): ?>
                        <a href="index.php" class="btn btn-outline btn-sm">Reset</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Category Tabs Row (Authentic Shadcn Tabs) -->
            <div class="category-tabs" style="margin-top: 0.5rem;">
                <a href="index.php?<?= e(http_build_query(array_merge($_GET, ['category' => '', 'page' => 1]))) ?>" 
                   class="tab-item <?= $categoryFilter === '' ? 'is-active' : '' ?>">
                    <span>Semua Kategori</span>
                    <span class="tab-count">(<?= (int)$stats['total_items'] ?>)</span>
                </a>
                <?php foreach ($categoryCounts as $catName => $catCount): ?>
                    <a href="index.php?<?= e(http_build_query(array_merge($_GET, ['category' => $catName, 'page' => 1]))) ?>" 
                       class="tab-item <?= $categoryFilter === $catName ? 'is-active' : '' ?>">
                        <span><?= e($catName) ?></span>
                        <span class="tab-count">(<?= $catCount ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </form>
    </section>

    <!-- 4. CATALOG CONTENT HEADER -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
        <span style="font-size: 0.8125rem; font-weight: 500; color: var(--muted-foreground);" id="live-catalog-count">
            Menampilkan <?= count($products) ?> dari <?= $totalFiltered ?> item
        </span>
    </div>

    <?php if (empty($products)): ?>
        <!-- EMPTY STATE -->
        <div class="card" style="padding: 4rem 1.5rem; text-align: center; margin-top: 1rem;">
            <div style="width: 3rem; height: 3rem; border-radius: var(--radius-sm); background: var(--secondary); color: var(--muted-foreground); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
            <h3 class="card-title" style="margin-bottom: 0.35rem;">Tidak Ada Produk Ditemukan</h3>
            <p class="card-description" style="max-width: 400px; margin: 0 auto 1.5rem;">
                Tidak ada item yang sesuai dengan kriteria filter atau pencarian Anda saat ini.
            </p>
            <div style="display: flex; justify-content: center; gap: 0.5rem;">
                <a href="index.php" class="btn btn-outline btn-sm">Bersihkan Filter</a>
                <a href="create.php" class="btn btn-primary btn-sm">+ Tambah Produk Baru</a>
            </div>
        </div>
    <?php else: ?>

        <!-- A. GRID VIEW (DEFAULT) -->
        <div class="product-grid" id="catalog-grid-view">
            <?php foreach ($products as $product): ?>
                <?php 
                    $stock = (int)$product['stock'];
                    $skuCode = 'SKU-' . str_pad((string)$product['id'], 4, '0', STR_PAD_LEFT);
                    $badge = getStockBadge($stock);
                    $imgSrc = (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image']))
                        ? 'uploads/' . e($product['image'])
                        : '';
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
                    data-product-img="<?= $imgSrc ?>"
                >
                    <!-- Image Container -->
                    <div class="product-img-box">
                        <?php if ($imgSrc !== ''): ?>
                            <img src="<?= $imgSrc ?>" alt="Foto <?= e($product['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; color: var(--muted-foreground); font-size: 0.75rem;">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                <span>Tidak ada gambar</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="product-card-body">
                        <!-- Meta Header: SKU & Category -->
                        <div class="product-meta-header">
                            <span class="badge badge-outline font-mono" style="font-size: 0.6875rem;">
                                <?= e($skuCode) ?>
                            </span>
                            <span class="badge badge-secondary">
                                <?= e($product['category']) ?>
                            </span>
                        </div>

                        <!-- Title -->
                        <h3 class="product-name">
                            <?= htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <!-- Description -->
                        <p class="product-desc-snippet">
                            <?= e($product['description'] ?: 'Tidak ada catatan spesifikasi tambahan.') ?>
                        </p>

                        <!-- Price & Stock Row -->
                        <div class="product-price-row">
                            <div>
                                <div style="font-size: 0.6875rem; color: var(--muted-foreground); text-transform: uppercase; font-family: var(--font-mono);">Harga</div>
                                <div class="product-price-val font-mono"><?= formatRupiah($product['price']) ?></div>
                            </div>
                            <div>
                                <span class="badge <?= $badge['badge_class'] ?>">
                                    <span class="status-dot" style="background-color: <?= $badge['dot_color'] ?>;"></span>
                                    <span><?= $badge['status_text'] ?></span>
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card-actions">
                            <button type="button" class="btn btn-outline btn-sm btn-inspect-trigger" title="Lihat detail lengkap">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <span>Detail</span>
                            </button>

                            <a href="edit.php?id=<?= (int)$product['id'] ?>" class="btn btn-secondary btn-sm" title="Edit produk">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                                <span>Edit</span>
                            </a>

                            <button 
                                type="button" 
                                class="btn btn-outline btn-sm btn-delete-trigger" 
                                style="color: var(--destructive); border-color: var(--border);"
                                data-id="<?= (int)$product['id'] ?>" 
                                data-name="<?= e($product['name']) ?>"
                                title="Hapus produk"
                            >
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- B. TABLE VIEW (SHADCN DATA TABLE) -->
        <div class="table-wrapper" id="catalog-table-view" style="display: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Foto</th>
                        <th style="width: 110px;">Kode SKU</th>
                        <th>Nama Produk & Rincian</th>
                        <th style="width: 140px;">Kategori</th>
                        <th style="width: 140px;">Status Stok</th>
                        <th style="width: 140px; text-align: right;">Harga Satuan</th>
                        <th style="width: 130px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="catalog-table-body">
                    <?php foreach ($products as $product): ?>
                        <?php 
                            $stock = (int)$product['stock'];
                            $skuCode = 'SKU-' . str_pad((string)$product['id'], 4, '0', STR_PAD_LEFT);
                            $badge = getStockBadge($stock);
                            $imgSrc = (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image']))
                                ? 'uploads/' . e($product['image'])
                                : '';
                        ?>
                        <tr 
                            class="table-product-row"
                            data-product-id="<?= (int)$product['id'] ?>"
                            data-product-name="<?= e($product['name']) ?>"
                            data-product-category="<?= e($product['category']) ?>"
                            data-product-price="<?= (float)$product['price'] ?>"
                            data-product-price-formatted="<?= formatRupiah($product['price']) ?>"
                            data-product-stock="<?= $stock ?>"
                            data-product-sku="<?= e($skuCode) ?>"
                            data-product-desc="<?= e($product['description'] ?: 'Tidak ada dokumentasi catatan tambahan.') ?>"
                            data-product-img="<?= $imgSrc ?>"
                        >
                            <!-- Image thumbnail -->
                            <td>
                                <?php if ($imgSrc !== ''): ?>
                                    <img src="<?= $imgSrc ?>" alt="" class="table-product-thumb">
                                <?php else: ?>
                                    <div class="table-product-thumb">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"></rect></svg>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- SKU -->
                            <td>
                                <span class="badge badge-outline font-mono" style="font-size: 0.6875rem;">
                                    <?= e($skuCode) ?>
                                </span>
                            </td>

                            <!-- Name & Description -->
                            <td>
                                <div style="font-weight: 600; color: var(--foreground); font-size: 0.875rem;">
                                    <?= htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div style="color: var(--muted-foreground); font-size: 0.75rem; margin-top: 2px; max-width: 360px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= e($product['description'] ?: '-') ?>
                                </div>
                            </td>

                            <!-- Category -->
                            <td>
                                <span class="badge badge-secondary">
                                    <?= e($product['category']) ?>
                                </span>
                            </td>

                            <!-- Stock Status -->
                            <td>
                                <span class="badge <?= $badge['badge_class'] ?>">
                                    <span class="status-dot" style="background-color: <?= $badge['dot_color'] ?>;"></span>
                                    <span><?= $badge['status_text'] ?></span>
                                </span>
                            </td>

                            <!-- Price -->
                            <td style="text-align: right; font-weight: 600;" class="font-mono">
                                <?= formatRupiah($product['price']) ?>
                            </td>

                            <!-- Actions -->
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 4px;">
                                    <button type="button" class="btn btn-ghost btn-sm btn-icon btn-inspect-trigger" title="Detail">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    </button>
                                    <a href="edit.php?id=<?= (int)$product['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                                    </a>
                                    <button 
                                        type="button" 
                                        class="btn btn-ghost btn-sm btn-icon btn-delete-trigger" 
                                        style="color: var(--destructive);"
                                        data-id="<?= (int)$product['id'] ?>" 
                                        data-name="<?= e($product['name']) ?>"
                                        title="Hapus"
                                    >
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

    <!-- 5. SHADCN PAGINATION BAR -->
    <?php if ($totalPages > 1): ?>
        <nav class="pagination-bar" aria-label="Navigasi Halaman">
            <?php if ($page > 1): ?>
                <a href="index.php?<?= e(http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>" class="page-btn" aria-label="Halaman sebelumnya">
                    &larr;
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a 
                    href="index.php?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>" 
                    class="page-btn <?= $i === $page ? 'is-active' : '' ?>"
                >
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="index.php?<?= e(http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>" class="page-btn" aria-label="Halaman berikutnya">
                    &rarr;
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
