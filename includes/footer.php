<?php
/**
 * =====================================================================
 * KINETIC PRODUCT MANAGER // EDITORIAL FOOTER & MODAL CONTROLLERS
 * =====================================================================
 */

declare(strict_types=1);
?>

    <!-- 1. STUDIO MINIMALIST FOOTER -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-brand">
                <div class="footer-logo">
                    <span style="font-family: var(--font-serif); font-size: 1.15rem; font-weight: 600; color: var(--text-primary);">Kinetic</span>
                    <span style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-muted);">&bull; Studio</span>
                </div>
                <div class="footer-caption">
                    Sistem Manajemen Inventaris &bull; Praktikum Pemrograman Web (Pertemuan 3)
                </div>
            </div>


            <div class="footer-links">
                <a href="database/store_db.sql" download class="footer-link">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Skema SQL (store_db.sql)</span>
                </a>
            </div>
        </div>
    </footer>

    <!-- 2. QUICK SPEC INSPECTOR MODAL (CARD DETAIL VIEW) -->
    <div class="modal-overlay" id="inspector-modal" role="dialog" aria-modal="true" aria-labelledby="inspector-title">
        <div class="modal-dialog inspector-dialog">
            <div class="modal-header">
                <div>
                    <span class="mono" id="inspector-sku" style="font-size: 0.74rem; color: var(--text-muted); font-weight: 600;">SKU-0000</span>
                    <h3 class="modal-title" id="inspector-title">Detail Spesifikasi Produk</h3>
                    <div id="inspector-category" class="product-category-tag" style="display: inline-block; margin-top: 4px;">Kategori</div>
                </div>
                <button type="button" class="flash-dismiss" id="inspector-close" aria-label="Tutup detail" style="margin-left: auto;">&times;</button>
            </div>

            <div class="modal-body" style="margin-bottom: 1rem;">
                <p id="inspector-desc" style="color: var(--text-secondary); font-size: 0.88rem; line-height: 1.6;">-</p>

                <div class="inspector-grid">
                    <div class="inspector-card">
                        <div class="inspector-card-label">Harga Satuan</div>
                        <div class="inspector-card-val" id="inspector-price" style="font-family: var(--font-mono);">-</div>
                    </div>
                    <div class="inspector-card">
                        <div class="inspector-card-label">Kuantitas Stok</div>
                        <div class="inspector-card-val" id="inspector-stock" style="font-family: var(--font-mono);">-</div>
                    </div>
                    <div class="inspector-card" style="grid-column: 1 / -1;">
                        <div class="inspector-card-label">Total Valuasi Persediaan SKU Ini</div>
                        <div class="inspector-card-val" id="inspector-valuation" style="font-family: var(--font-mono); font-size: 1.15rem;">-</div>
                    </div>
                </div>
            </div>

            <div class="modal-actions" style="border-top: 1px solid var(--border-line); padding-top: 1rem;">
                <button type="button" class="btn btn-secondary" id="inspector-btn-close">
                    Tutup
                </button>
                <a href="#" class="btn btn-primary" id="inspector-edit-link">
                    Edit Produk Ini
                </a>
            </div>
        </div>
    </div>

    <!-- 3. SAFE DELETE CONFIRMATION MODAL (POST + CSRF) -->
    <div class="modal-overlay" id="delete-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-dialog">
            <div class="modal-header">
                <div class="modal-warning-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <div>
                    <h3 class="modal-title" id="modal-title">Konfirmasi Hapus Produk</h3>
                    <p class="modal-subtitle">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                </div>
            </div>

            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus produk ini dari database inventaris?</p>
                <div class="modal-item-highlight">
                    <span style="font-family: var(--font-mono); font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">PRODUK TERPILIH:</span>
                    <div id="modal-delete-name" class="modal-item-title">-</div>
                </div>
            </div>

            <form action="delete.php" method="POST" id="form-delete-modal">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="modal-delete-id" value="">
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="modal-delete-cancel">
                        Batalkan
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <span>Hapus Produk</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. JAVASCRIPT CONTROLLER -->
    <script src="assets/js/app.js"></script>
</body>
</html>
