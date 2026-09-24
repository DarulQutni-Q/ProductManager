<?php
/**
 * =====================================================================
 * KINETIC PRODUCT MANAGER // PRODUCT EDIT (UPDATE)
 * =====================================================================
 * Menangani pembaruan data spesimen, form terisi (pre-filled),
 * validasi keunikan nama mengecualikan ID sendiri, dan Pola PRG.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pdo = getDbConnection();
$pageTitle = 'Edit Produk';

// 1. Ekstraksi dan Validasi Parameter ID dari GET / POST
$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

if ($id <= 0) {
    setFlash('danger', 'Identifikasi produk tidak valid. ID harus berupa bilangan bulat positif.');
    redirect('index.php');
}

// 2. Ambil Data Perangkat Saat Ini Menggunakan PDO Prepared Statement
// Syarat Query Slide 16: SELECT by ID -> $pdo->prepare(...)
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('danger', 'Produk dengan ID #' . $id . ' tidak ditemukan di dalam basis data.');
    redirect('index.php');
}

// --- MENANGANI REQUEST POST (PENGIRIMAN PEMBARUAN FORM) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verifikasi CSRF Token
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!verifyCsrfToken($csrfToken)) {
        setFlash('danger', 'Permintaan ditolak: Token keamanan CSRF tidak valid atau sesi telah kedaluwarsa.');
        redirect('edit.php?id=' . $id);
    }

    // 2. Validasi Server-Side (Kecualikan ID sendiri saat cek keunikan nama)
    $errors = validateProductData($_POST, $id);

    // 3. Tangani Upload Gambar Baru jika ada
    $imageFile = $_FILES['image'] ?? null;
    $hasNewUpload = ($imageFile && isset($imageFile['error']) && $imageFile['error'] !== UPLOAD_ERR_NO_FILE);
    $uploadResult = handleProductImageUpload($imageFile, null);

    if (!$uploadResult['success']) {
        $errors['image'] = $uploadResult['error'];
    }

    // 4. Jika Ada Error: Bersihkan upload baru dan redirect via PRG
    if (!empty($errors)) {
        if ($hasNewUpload && !empty($uploadResult['filename'])) {
            @unlink(__DIR__ . '/uploads/' . basename($uploadResult['filename']));
        }
        setOldInput($_POST);
        setFormErrors($errors);
        setFlash('danger', 'Pembaruan produk gagal. Harap periksa kesalahan isian pada formulir.');
        redirect('edit.php?id=' . $id);
    }

    // 5. Eksekusi UPDATE dengan PDO Prepared Statement
    $name = trim((string)$_POST['name']);
    $category = trim((string)$_POST['category']);
    $rawPrice = $_POST['price'] ?? 0;
    $price = is_numeric($rawPrice) ? (float)$rawPrice : (float)str_replace(['.', ','], ['', '.'], (string)$rawPrice);
    $stock = (int)$_POST['stock'];
    $description = trim((string)($_POST['description'] ?? ''));
    $imageFilename = ($hasNewUpload && $uploadResult['filename']) ? $uploadResult['filename'] : $product['image'];
    try {
        // Syarat Query Slide 16: UPDATE -> $pdo->prepare(...)
        $updateSql = "UPDATE products SET 
                        name = :name, 
                        category = :category, 
                        price = :price, 
                        stock = :stock, 
                        image = :image, 
                        description = :description 
                      WHERE id = :id";
        
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':name'        => $name,
            ':category'    => $category,
            ':price'       => $price,
            ':stock'       => $stock,
            ':image'       => $imageFilename,
            ':description' => $description !== '' ? $description : null,
            ':id'          => $id
        ]);

        // Hapus file gambar lama jika berhasil digantikan dengan file baru
        if ($hasNewUpload && !empty($product['image']) && $product['image'] !== $imageFilename) {
            $oldPath = __DIR__ . '/uploads/' . basename($product['image']);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
        clearOldInput();
        regenerateCsrfToken();
        setFlash('success', 'Data produk "' . $name . '" [SKU-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT) . '] berhasil diperbarui.', 'Pembaruan Berhasil');
        
        // Pola PRG: Redirect 303 ke index.php
        redirect('index.php');
    } catch (PDOException $e) {
        error_log('[UPDATE_PRODUCT_ERROR] ' . $e->getMessage());
        setOldInput($_POST);
        setFlash('danger', 'Terjadi kesalahan sistem saat menyimpan pembaruan ke basis data.');
        redirect('edit.php?id=' . $id);
    }
}

// --- MENAMPILKAN FORMULIR DENGAN PRE-FILLED VALUES ---
$formErrors = getFormErrors();

// Prioritaskan nilai input lama jika baru saja gagal validasi, atau fallback ke data basis data
$valName = (string)getOldInput('name', $product['name']);
$valCategory = (string)getOldInput('category', $product['category']);
$valPrice = (string)getOldInput('price', (string)$product['price']);
$valStock = (string)getOldInput('stock', (string)$product['stock']);
$valDescription = (string)getOldInput('description', $product['description'] ?? '');

$defaultCategories = [
    'Microcontroller',
    'Sensor Unit',
    'Power Module',
    'Mechanical Actuator',
    'Telemetry Radio',
    'Laboratory Instrument'
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="form-wrapper">
    <div class="form-title-group">
        <a href="index.php" class="back-link">
            &larr; Kembali ke Katalog
        </a>
        <h2 class="form-main-heading">Edit Data Produk</h2>
        <p class="form-sub-heading">
            Perbarui spesifikasi produk: <span class="mono" style="color: var(--brand-blue); font-weight: 700;">[SKU-<?= str_pad((string)$product['id'], 4, '0', STR_PAD_LEFT) ?>]</span>
        </p>
    </div>

    <div class="form-card">
        <form action="edit.php?id=<?= (int)$product['id'] ?>" method="POST" enctype="multipart/form-data" novalidate id="product-form">
            <!-- CSRF Token Hidden Field (Slide 16) -->
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">

            <div class="form-layout-grid">
                <!-- 1. NAMA PRODUK -->
                <div class="form-group col-full">
                    <label for="name" class="input-label">
                        <span>Nama Produk <span class="required">*</span></span>
                        <span class="mono" style="font-size: 0.72rem; color: var(--text-dim);">Min. 3 karakter &bull; Unik</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-input <?= isset($formErrors['name']) ? 'has-error' : '' ?>" 
                        value="<?= e($valName) ?>"
                        required
                    >
                    <?php if (isset($formErrors['name'])): ?>
                        <div class="error-text"><?= e($formErrors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- 2. KATEGORI PRODUK -->
                <div class="form-group">
                    <label for="category" class="input-label">
                        <span>Kategori Produk <span class="required">*</span></span>
                    </label>
                    <input 
                        type="text" 
                        id="category" 
                        name="category" 
                        list="category-options" 
                        class="form-input <?= isset($formErrors['category']) ? 'has-error' : '' ?>" 
                        value="<?= e($valCategory) ?>"
                        required
                    >
                    <datalist id="category-options">
                        <?php foreach ($defaultCategories as $cat): ?>
                            <option value="<?= e($cat) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <?php if (isset($formErrors['category'])): ?>
                        <div class="error-text"><?= e($formErrors['category']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- 3. KUANTITAS STOK -->
                <div class="form-group">
                    <label for="stock" class="input-label">
                        <span>Kuantitas Stok <span class="required">*</span></span>
                        <span id="stock-health-preview" class="stock-tag stock-nominal" style="font-size: 0.68rem; padding: 2px 7px;">
                            Tersedia
                        </span>
                    </label>
                    <input 
                        type="number" 
                        id="stock" 
                        name="stock" 
                        min="0" 
                        step="1" 
                        class="form-input mono <?= isset($formErrors['stock']) ? 'has-error' : '' ?>" 
                        value="<?= e($valStock) ?>"
                        required
                    >
                    <?php if (isset($formErrors['stock'])): ?>
                        <div class="error-text"><?= e($formErrors['stock']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- 4. HARGA SATUAN (IDR) -->
                <div class="form-group col-full">
                    <label for="price" class="input-label">
                        <span>Harga Satuan (IDR) <span class="required">*</span></span>
                        <span class="mono" id="price-preview" style="font-size: 0.8rem; font-weight: 700; color: var(--brand-blue);">Rp 0</span>
                    </label>
                    <input 
                        type="number" 
                        id="price" 
                        name="price" 
                        min="1" 
                        step="1" 
                        class="form-input mono <?= isset($formErrors['price']) ? 'has-error' : '' ?>" 
                        value="<?= e($valPrice) ?>"
                        required
                    >
                    <?php if (isset($formErrors['price'])): ?>
                        <div class="error-text"><?= e($formErrors['price']) ?></div>
                    <?php endif; ?>

                    <!-- Live Valuation Calculator -->
                    <div class="valuation-calc-box">
                        <span class="valuation-calc-title">Estimasi Total Valuasi Persediaan:</span>
                        <span class="valuation-calc-val" id="total-batch-valuation">Rp 0</span>
                    </div>
                </div>

                <!-- 5. UPLOAD / GANTI GAMBAR -->
                <div class="form-group col-full">
                    <label class="input-label">
                        <span>Foto Produk (Opsional)</span>
                        <span class="mono" style="font-size: 0.72rem; color: var(--text-dim);">Penggantian foto produk</span>
                    </label>

                    <?php if (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image'])): ?>
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px; padding: 10px 14px; background: var(--canvas-surface-subtle); border: 1px solid var(--line-hairline); border-radius: var(--radius-md);">
                            <img src="uploads/<?= e($product['image']) ?>" alt="Foto Aktif" style="width: 48px; height: 48px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--line-hairline);">
                            <div style="font-size: 0.82rem;">
                                <div style="font-weight: 700; color: var(--text-title);">Foto saat ini: <?= e($product['image']) ?></div>
                                <div style="color: var(--text-muted); font-size: 0.75rem;">Biarkan kosong di bawah ini jika tidak ingin mengubah foto.</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="upload-zone" id="file-dropzone">
                        <input 
                            type="file" 
                            id="image-input" 
                            name="image" 
                            class="file-input-hidden" 
                            accept="image/jpeg,image/png,image/webp"
                        >
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="color: var(--brand-blue); margin-bottom: 6px;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-title);">
                            Pilih atau tarik foto baru untuk mengganti
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                            JPG, PNG, WEBP &bull; Maks 2MB
                        </div>
                    </div>

                    <!-- Upload Preview Box -->
                    <div id="upload-preview-box" style="display: none; align-items: center; justify-content: space-between; margin-top: 10px; padding: 10px 14px; background: var(--canvas-surface-subtle); border: 1px solid var(--line-hairline); border-radius: var(--radius-md);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <img src="" alt="Pratinjau Baru" id="upload-preview-img" style="width: 50px; height: 50px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--line-hairline);">
                            <div>
                                <div id="upload-preview-name" style="font-weight: 700; font-size: 0.85rem; color: var(--text-title);">-</div>
                                <div id="upload-preview-size" style="color: var(--text-muted); font-size: 0.74rem;">-</div>
                            </div>
                        </div>
                        <button type="button" id="btn-clear-upload" class="btn btn-sm btn-secondary" style="font-size: 0.72rem;">Hapus</button>
                    </div>

                    <?php if (isset($formErrors['image'])): ?>
                        <div class="error-text"><?= e($formErrors['image']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- 6. DESKRIPSI PRODUK -->
                <div class="form-group col-full">
                    <label for="description" class="input-label">
                        <span>Deskripsi Spesifikasi Produk</span>
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="4" 
                        class="form-input"
                    ><?= e($valDescription) ?></textarea>
                </div>
            </div>

            <!-- FORM BUTTONS -->
            <div class="form-actions-bar">
                <a href="index.php" class="btn btn-secondary">
                    Batalkan
                </a>
                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
