<?php
/**
 * =====================================================================
 * PRODUCT MANAGER // SHADCN ZINC FOOTER & MODAL PRIMITIVES
 * =====================================================================
 */

declare(strict_types=1);
?>

    <!-- SITE FOOTER -->
    <footer class="site-footer">
        <div class="app-container footer-inner">
            <div>
                <span style="font-weight: 600; color: var(--foreground);">ProductManager</span>
                <span style="margin: 0 6px; color: var(--muted-foreground);">•</span>
                <span>Inventaris Perangkat &bull; Praktikum Pemrograman Web</span>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <div style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.75rem; color: var(--muted-foreground);">
                    <kbd>/</kbd> <span>Cari</span>
                    <span style="margin: 0 2px;">•</span>
                    <kbd>N</kbd> <span>Baru</span>
                    <span style="margin: 0 2px;">•</span>
                    <kbd>V</kbd> <span>Mode Tampilan</span>
                    <span style="margin: 0 2px;">•</span>
                    <kbd>Esc</kbd> <span>Tutup</span>
                </div>

                <a href="database/store_db.sql" download class="btn btn-outline btn-sm">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    <span>Unduh store_db.sql</span>
                </a>
            </div>
        </div>
    </footer>

    <!-- 1. DETAIL INSPECTOR MODAL (SHADCN DIALOG) -->
    <div class="modal-backdrop" id="inspector-modal" role="dialog" aria-modal="true" aria-labelledby="inspector-title">
        <div class="modal-dialog">
            <div class="modal-header-row">
                <div>
                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <span class="badge badge-outline font-mono" id="inspector-sku">SKU-0000</span>
                        <span class="badge badge-secondary" id="inspector-category">Kategori</span>
                    </div>
                    <h3 class="card-title" id="inspector-title" style="font-size: 1.125rem;">Detail Spesifikasi Produk</h3>
                </div>
                <button type="button" class="modal-close-btn" id="inspector-close" aria-label="Tutup dialog">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="18" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <div id="inspector-image-wrap" style="width: 100%; height: 180px; background: var(--muted); border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <img id="inspector-img" src="" alt="" style="max-height: 100%; max-width: 100%; object-fit: contain; padding: 0.5rem;">
                </div>

                <p id="inspector-desc" style="color: var(--muted-foreground); font-size: 0.84rem; line-height: 1.55; margin-bottom: 1rem;">-</p>

                <div class="spec-grid">
                    <div class="spec-card">
                        <div class="spec-card-label">Harga Satuan</div>
                        <div class="spec-card-val font-mono" id="inspector-price">-</div>
                    </div>
                    <div class="spec-card">
                        <div class="spec-card-label">Kuantitas Stok</div>
                        <div class="spec-card-val font-mono" id="inspector-stock">-</div>
                    </div>
                    <div class="spec-card" style="grid-column: 1 / -1;">
                        <div class="spec-card-label">Total Valuasi Aset SKU Ini</div>
                        <div class="spec-card-val font-mono" id="inspector-valuation" style="font-size: 1.1rem;">-</div>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                <button type="button" class="btn btn-outline" id="inspector-btn-close">
                    Tutup
                </button>
                <a href="#" class="btn btn-primary" id="inspector-edit-link">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                    <span>Edit Produk</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 2. SAFE DELETE CONFIRMATION MODAL (SHADCN ALERTDIALOG) -->
    <div class="modal-backdrop" id="delete-modal" role="dialog" aria-modal="true" aria-labelledby="modal-delete-title">
        <div class="modal-dialog">
            <div class="modal-header-row">
                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                    <div style="width: 2.25rem; height: 2.25rem; border-radius: var(--radius-sm); background: rgba(239, 68, 68, 0.12); color: var(--destructive); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </div>
                    <div>
                        <h3 class="card-title" id="modal-delete-title">Hapus Produk?</h3>
                        <p class="card-description" style="margin-top: 2px;">Tindakan ini permanen dan data produk akan dihapus dari sistem inventaris.</p>
                    </div>
                </div>
                <button type="button" class="modal-close-btn" id="modal-delete-x" aria-label="Tutup dialog">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <div style="background: var(--secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.75rem 1rem; margin: 1rem 0;">
                <span style="font-size: 0.6875rem; text-transform: uppercase; font-family: var(--font-mono); color: var(--muted-foreground); font-weight: 500;">PRODUK TERPILIH:</span>
                <div id="modal-delete-name" style="font-size: 0.875rem; font-weight: 600; color: var(--foreground); margin-top: 2px;">-</div>
            </div>

            <form action="delete.php" method="POST" id="form-delete-modal">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="modal-delete-id" value="">
                
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="btn btn-outline" id="modal-delete-cancel">
                        Batalkan
                    </button>
                    <button type="submit" class="btn btn-destructive">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        <span>Hapus Produk</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CLIENT JAVASCRIPT CONTROLLER -->
    <script src="assets/js/app.js"></script>
</body>
</html>
