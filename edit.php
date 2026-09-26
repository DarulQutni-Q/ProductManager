<?php
/**
 * =====================================================================
 * PRODUCT MANAGER // PRODUCT EDIT (SHADCN ZINC FORM)
 * =====================================================================
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

$skuFormatted = 'SKU-' . str_pad((string)$product['id'], 4, '0', STR_PAD_LEFT);

require_once __DIR__ . '/includes/header.php';
?>

<main class="app-container" style="flex: 1;">
    <div class="form-container">
        <!-- Breadcrumb -->
        <nav class="form-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Katalog</a>
            <span>/</span>
            <span>Edit</span>
            <span>/</span>
            <span style="color: var(--foreground); font-weight: 500;" class="font-mono">[<?= $skuFormatted ?>]</span>
        </nav>

        <!-- Form Card (Shadcn Card) -->
        <div class="card">
            <div class="card-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h2 class="card-title">Edit Produk</h2>
                    <span class="badge badge-outline font-mono" style="font-size: 0.75rem;">
                        <?= $skuFormatted ?>
                    </span>
                </div>
                <p class="card-description">Perbarui data spesifikasi dan stok produk di sistem inventaris.</p>
            </div>

            <div class="card-content">
                <form action="edit.php?id=<?= (int)$product['id'] ?>" method="POST" enctype="multipart/form-data" novalidate id="product-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">

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
                                value="<?= e($valName) ?>"
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
                                value="<?= e($valCategory) ?>"
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
                                value="<?= e($valStock) ?>"
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
                                value="<?= e($valPrice) ?>"
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

                        <!-- 5. FOTO PRODUK (PENGGANTIAN) -->
                        <div class="form-group col-span-2">
                            <label class="input-label">
                                <span>Foto Produk</span>
                                <span class="font-mono" style="font-size: 0.75rem; color: var(--muted-foreground);">JPG, PNG, WEBP &bull; Maks 2MB</span>
                            </label>

                            <?php if (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image'])): ?>
                                <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: var(--secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 0.75rem;">
                                    <img src="uploads/<?= e($product['image']) ?>" alt="Foto Saat Ini" style="width: 44px; height: 44px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--border); background: #ffffff;">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: 0.8125rem; font-weight: 600; color: var(--foreground); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= e($product['image']) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--muted-foreground);">Foto saat ini aktif di sistem. Pilih gambar baru di bawah jika ingin menggantinya.</div>
                                    </div>
                                </div>
                            <?php endif; ?>

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
                                    Pilih foto baru untuk mengganti
                                </div>
                                <div style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 2px;">
                                    Biarkan kosong jika tetap menggunakan gambar sebelumnya
                                </div>
                            </div>

                            <!-- Image preview box -->
                            <div id="upload-preview-box" style="display: none; align-items: center; justify-content: space-between; margin-top: 0.75rem; padding: 0.75rem 1rem; background: var(--secondary); border: 1px solid var(--border); border-radius: var(--radius-sm);">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <img src="" alt="Pratinjau Baru" id="upload-preview-img" style="width: 44px; height: 44px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--border);">
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
                            ><?= e($valDescription) ?></textarea>
                        </div>
                    </div>

                    <!-- Form Footer Buttons -->
                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                        <a href="index.php" class="btn btn-outline">
                            Batalkan
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
