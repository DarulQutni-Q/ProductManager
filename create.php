<?php
/**
 * =====================================================================
 * PRODUCT MANAGER // PRODUCT CREATION (SHADCN ZINC FORM)
 * =====================================================================
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

    // 4. Jika Ada Error: Bersihkan upload baru dan redirect via PRG
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

        clearOldInput();
        regenerateCsrfToken();
        setFlash('success', 'Produk "' . $name . '" berhasil ditambahkan ke inventaris.', 'Produk Berhasil Ditambahkan');
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

<main class="app-container" style="flex: 1;">
    <div class="form-container">
        <!-- Breadcrumb -->
        <nav class="form-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Katalog</a>
            <span>/</span>
            <span style="color: var(--foreground); font-weight: 500;">Tambah Produk</span>
        </nav>

        <!-- Form Card (Shadcn Card) -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Tambah Produk Baru</h2>
                <p class="card-description">Lengkapi spesifikasi dan rincian persediaan produk ke dalam sistem inventaris.</p>
            </div>

            <div class="card-content">
                <form action="create.php" method="POST" enctype="multipart/form-data" novalidate id="product-form">
                    <?= csrfField() ?>

                    <div class="form-grid">
                        <!-- 1. NAMA PRODUK -->
                        <div class="form-group col-span-2">
                            <label for="name" class="input-label">
                                <span>Nama Produk <span class="required-mark">*</span></span>
                                <span class="font-mono" style="font-size: 0.75rem; color: var(--muted-foreground);">Min. 3 karakter &bull; Unik</span>
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="input-control <?= isset($formErrors['name']) ? 'is-invalid' : '' ?>" 
                                placeholder="Contoh: STM32H753 Arm Cortex-M7 Core Unit" 
                                value="<?= e($oldName) ?>"
                                required
                            >
                            <?php if (isset($formErrors['name'])): ?>
                                <div class="form-error-msg"><?= e($formErrors['name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- 2. KATEGORI PRODUK -->
                        <div class="form-group">
                            <label for="category" class="input-label">
                                <span>Kategori <span class="required-mark">*</span></span>
                            </label>
                            <input 
                                type="text" 
                                id="category" 
                                name="category" 
                                list="category-options" 
                                class="input-control <?= isset($formErrors['category']) ? 'is-invalid' : '' ?>" 
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
                                <div class="form-error-msg"><?= e($formErrors['category']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- 3. KUANTITAS STOK -->
                        <div class="form-group">
                            <label for="stock" class="input-label">
                                <span>Kuantitas Stok <span class="required-mark">*</span></span>
                                <span id="stock-health-preview" class="badge badge-emerald">
                                    Tersedia
                                </span>
                            </label>
                            <input 
                                type="number" 
                                id="stock" 
                                name="stock" 
                                min="0" 
                                step="1" 
                                class="input-control font-mono <?= isset($formErrors['stock']) ? 'is-invalid' : '' ?>" 
                                placeholder="0" 
                                value="<?= e($oldStock) ?>"
                                required
                            >
                            <?php if (isset($formErrors['stock'])): ?>
                                <div class="form-error-msg"><?= e($formErrors['stock']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- 4. HARGA SATUAN (IDR) -->
                        <div class="form-group col-span-2">
                            <label for="price" class="input-label">
                                <span>Harga Satuan (IDR) <span class="required-mark">*</span></span>
                                <span class="font-mono" id="price-preview" style="font-size: 0.8125rem; font-weight: 600; color: var(--foreground);">Rp 0</span>
                            </label>
                            <input 
                                type="number" 
                                id="price" 
                                name="price" 
                                min="1" 
                                step="1" 
                                class="input-control font-mono <?= isset($formErrors['price']) ? 'is-invalid' : '' ?>" 
                                placeholder="Contoh: 1450000" 
                                value="<?= e($oldPrice) ?>"
                                required
                            >
                            <?php if (isset($formErrors['price'])): ?>
                                <div class="form-error-msg"><?= e($formErrors['price']) ?></div>
                            <?php else: ?>
                                <div class="form-hint">Harga per unit dalam Rupiah (harus > 0).</div>
                            <?php endif; ?>

                            <!-- Valuation Preview -->
                            <div class="valuation-preview-card" style="margin-top: 0.5rem;">
                                <span style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; font-weight: 500;">Estimasi Total Valuasi:</span>
                                <span class="font-mono" id="total-batch-valuation" style="font-weight: 700; color: var(--foreground); font-size: 0.9375rem;">Rp 0</span>
                            </div>
                        </div>

                        <!-- 5. FOTO PRODUK (UPLOAD) -->
                        <div class="form-group col-span-2">
                            <label class="input-label">
                                <span>Foto Produk (Opsional)</span>
                                <span class="font-mono" style="font-size: 0.75rem; color: var(--muted-foreground);">JPG, PNG, WEBP &bull; Maks 2MB</span>
                            </label>

                            <div class="file-dropzone" id="file-dropzone">
                                <input 
                                    type="file" 
                                    id="image-input" 
                                    name="image" 
                                    class="dropzone-hidden-input" 
                                    accept="image/jpeg,image/png,image/webp"
                                >
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 0.5rem auto; color: var(--muted-foreground);">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <div style="font-size: 0.875rem; font-weight: 500; color: var(--foreground);">
                                    Klik atau seret file gambar ke sini
                                </div>
                                <div style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 2px;">
                                    Format gambar didukung: JPG, PNG, WEBP
                                </div>
                            </div>

                            <!-- Image preview box -->
                            <div id="upload-preview-box" style="display: none; align-items: center; justify-content: space-between; margin-top: 0.75rem; padding: 0.75rem 1rem; background: var(--secondary); border: 1px solid var(--border); border-radius: var(--radius-sm);">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <img src="" alt="Pratinjau" id="upload-preview-img" style="width: 44px; height: 44px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--border);">
                                    <div>
                                        <div id="upload-preview-name" style="font-size: 0.8125rem; font-weight: 600; color: var(--foreground);">-</div>
                                        <div id="upload-preview-size" class="font-mono" style="font-size: 0.75rem; color: var(--muted-foreground);">-</div>
                                    </div>
                                </div>
                                <button type="button" id="btn-clear-upload" class="btn btn-outline btn-sm">Hapus</button>
                            </div>

                            <?php if (isset($formErrors['image'])): ?>
                                <div class="form-error-msg"><?= e($formErrors['image']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- 6. DESKRIPSI PRODUK -->
                        <div class="form-group col-span-2">
                            <label for="description" class="input-label">
                                <span>Deskripsi Spesifikasi</span>
                            </label>
                            <textarea 
                                id="description" 
                                name="description" 
                                class="textarea-control" 
                                placeholder="Tambahkan spesifikasi teknis atau rincian perangkat..."
                            ><?= e($oldDescription) ?></textarea>
                        </div>
                    </div>

                    <!-- Form Footer Buttons -->
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                        <a href="index.php" class="btn btn-outline">
                            Batalkan
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
