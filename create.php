<?php
/**
 * =====================================================================
 * KINETIC PRODUCT MANAGER // PRODUCT CREATION (CREATE)
 * =====================================================================
 * Menangani formulir pendaftaran aset baru, validasi server-side ketat,
 * kalkulator valuasi interaktif, dan implementasi Pola PRG (Post-Redirect-Get).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pdo = getDbConnection();
$pageTitle = 'Tambah Produk Baru';

// --- MENANGANI REQUEST POST (PENGIRIMAN FORM) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verifikasi CSRF Token
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!verifyCsrfToken($csrfToken)) {
        setFlash('danger', 'Permintaan ditolak: Token keamanan CSRF tidak valid atau sesi telah kedaluwarsa.');
        redirect('create.php');
    }

    // 2. Validasi Seluruh Input Server-Side
    $errors = validateProductData($_POST);

    // 3. Validasi & Proses Upload Berkas Gambar (Jika Ada)
    $imageFile = $_FILES['image'] ?? null;
    $uploadResult = handleProductImageUpload($imageFile);

    if (!$uploadResult['success']) {
        $errors['image'] = $uploadResult['error'];
    }

    // 4. Jika Terdapat Kesalahan Validasi: Bersihkan berkas upload baru dan kembalikan via PRG
    if (!empty($errors)) {
        if (!empty($uploadResult['filename'])) {
            @unlink(__DIR__ . '/uploads/' . basename($uploadResult['filename']));
        }
        setOldInput($_POST);
        setFormErrors($errors);
        setFlash('danger', 'Gagal menyimpan produk. Harap periksa kembali isian formulir.');
        redirect('create.php');
    }

    // 5. Normalisasi Data untuk Eksekusi Basis Data
    $name = trim((string)$_POST['name']);
    $category = trim((string)$_POST['category']);
    $rawPrice = $_POST['price'] ?? 0;
    $price = is_numeric($rawPrice) ? (float)$rawPrice : (float)str_replace(['.', ','], ['', '.'], (string)$rawPrice);
    $stock = (int)$_POST['stock'];
    $description = trim((string)($_POST['description'] ?? ''));
    $imageFilename = $uploadResult['filename'];

    try {
        // Syarat Query Slide 16: INSERT -> $pdo->prepare(...)
        $sql = "INSERT INTO products (name, category, price, stock, image, description) 
                VALUES (:name, :category, :price, :stock, :image, :description)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'        => $name,
            ':category'    => $category,
            ':price'       => $price,
            ':stock'       => $stock,
            ':image'       => $imageFilename,
            ':description' => $description !== '' ? $description : null
        ]);

        // Bersihkan cache old input & set pesan sukses
        clearOldInput();
        regenerateCsrfToken();
        setFlash('success', 'Produk "' . $name . '" berhasil ditambahkan ke inventaris.', 'Produk Berhasil Ditambahkan');
        
        // Pola PRG: Redirect 303 ke index.php (Meniadakan duplikasi saat refresh)
        redirect('index.php');
    } catch (PDOException $e) {
        error_log('[CREATE_PRODUCT_ERROR] ' . $e->getMessage());
        setOldInput($_POST);
        setFlash('danger', 'Terjadi kesalahan sistem saat menyimpan ke basis data.');
        redirect('create.php');
    }
}

// --- MENAMPILKAN FORMULIR (REQUEST GET) ---
$formErrors = getFormErrors();
$oldName = (string)getOldInput('name', '');
$oldCategory = (string)getOldInput('category', '');
$oldPrice = (string)getOldInput('price', '');
$oldStock = (string)getOldInput('stock', '0');
$oldDescription = (string)getOldInput('description', '');

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
        <h2 class="form-main-heading">Tambah Produk Baru</h2>
        <p class="form-sub-heading">Masukkan spesifikasi dan detail persediaan produk ke dalam sistem inventaris.</p>
    </div>

    <div class="form-card">
        <form action="create.php" method="POST" enctype="multipart/form-data" novalidate id="product-form">
            <!-- CSRF Token Hidden Field (Slide 16) -->
            <?= csrfField() ?>

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
                        placeholder="Contoh: STM32H753 Arm Cortex-M7 Core Unit" 
                        value="<?= e($oldName) ?>"
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
                        placeholder="Pilih atau ketik kategori..." 
                        value="<?= e($oldCategory) ?>"
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
                        placeholder="0" 
                        value="<?= e($oldStock) ?>"
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
                        placeholder="Contoh: 1450000" 
                        value="<?= e($oldPrice) ?>"
                        required
                    >
                    <?php if (isset($formErrors['price'])): ?>
                        <div class="error-text"><?= e($formErrors['price']) ?></div>
                    <?php else: ?>
                        <div class="help-text">Harga harus berupa angka lebih besar dari 0.</div>
                    <?php endif; ?>

                    <!-- Live Valuation Calculator -->
                    <div class="valuation-calc-box">
                        <span class="valuation-calc-title">Estimasi Total Valuasi Persediaan:</span>
                        <span class="valuation-calc-val" id="total-batch-valuation">Rp 0</span>
                    </div>
                </div>

                <!-- 5. UPLOAD BERKAS GAMBAR (BONUS) -->
                <div class="form-group col-full">
                    <label class="input-label">
                        <span>Foto Produk (Opsional / Bonus)</span>
                        <span class="mono" style="font-size: 0.72rem; color: var(--text-dim);">JPG, PNG, WEBP &bull; Maks 2MB</span>
                    </label>

                    <div class="upload-zone" id="file-dropzone">
                        <input 
                            type="file" 
                            id="image-input" 
                            name="image" 
                            class="file-input-hidden" 
                            accept="image/jpeg,image/png,image/webp"
                        >
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="color: var(--brand-blue); margin-bottom: 8px;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-title);">
                            Klik atau seret foto produk ke area ini
                        </div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                            Verifikasi tipe berkas biner dilakukan secara aman di sisi server.
                        </div>
                    </div>

                    <!-- Upload Preview Box -->
                    <div id="upload-preview-box" style="display: none; align-items: center; justify-content: space-between; margin-top: 10px; padding: 10px 14px; background: var(--canvas-surface-subtle); border: 1px solid var(--line-hairline); border-radius: var(--radius-md);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <img src="" alt="Pratinjau" id="upload-preview-img" style="width: 50px; height: 50px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--line-hairline);">
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
                        placeholder="Tambahkan informasi spesifikasi perangkat atau catatan penggunaan..."
                    ><?= e($oldDescription) ?></textarea>
                </div>
            </div>

            <!-- FORM BUTTONS -->
            <div class="form-actions-bar">
                <a href="index.php" class="btn btn-secondary">
                    Batalkan
                </a>
                <button type="submit" class="btn btn-primary">
                    Simpan Produk
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
