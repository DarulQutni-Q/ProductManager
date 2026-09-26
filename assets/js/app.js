/**
 * =====================================================================
 * KINETIC PRODUCT MANAGER // STUDIO CLIENT INTERACTION CONTROLLER
 * =====================================================================
 * High-performance client-side interactivity:
 * 1. Global Keyboard Shortcuts ('/', 'n', 'k', 'Esc')
 * 2. Instant Live Search & Filter with Real-time Item Counters
 * 3. Spec Inspector / Quick View Modal
 * 4. Live Valuation & Stock Health Calculator
 * 5. Drag-and-Drop Image Dropzone with Clear Capability
 * 6. Accessible Delete Confirmation Modal
 * 7. Auto-dismissing Flash Toasts
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
  initKeyboardShortcuts();
  initLiveSearchAndFilter();
  initSpecInspectorModal();
  initFormCalculators();
  initImageUploadDropzone();
  initDeleteModal();
  initFlashDismiss();
  initFormDoubleSubmitGuard();
});

/**
 * 1. Global Keyboard Shortcuts
 */
function initKeyboardShortcuts() {
  window.addEventListener('keydown', (e) => {
    const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
    const isEditing = activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select';

    // Press '/' to focus search input
    if (e.key === '/' && !isEditing) {
      e.preventDefault();
      const searchInput = document.getElementById('search-input');
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
      }
    }

    // Press 'n' to go to create page
    if ((e.key === 'n' || e.key === 'N') && !isEditing && !e.metaKey && !e.ctrlKey) {
      if (!window.location.pathname.includes('create.php')) {
        window.location.href = 'create.php';
      }
    }

    // Press 'k' to go to catalog
    if ((e.key === 'k' || e.key === 'K') && !isEditing && !e.metaKey && !e.ctrlKey) {
      if (!window.location.pathname.endsWith('index.php')) {
        window.location.href = 'index.php';
      }
    }

    // Press 'Escape' to close any open modal
    if (e.key === 'Escape') {
      const activeModals = document.querySelectorAll('.modal-overlay.is-active');
      activeModals.forEach((m) => m.classList.remove('is-active'));
    }
  });
}

/**
 * 2. Instant Live Client Search & Category Filter (Progressive Enhancement)
 */
function initLiveSearchAndFilter() {
  const searchInput = document.getElementById('search-input');
  const cards = document.querySelectorAll('.product-card');
  const countEl = document.getElementById('live-catalog-count');
  const container = document.getElementById('product-grid-container');

  if (!cards.length) return;

  let debounceTimer;

  const performFilter = () => {
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    let visibleCount = 0;

    cards.forEach((card) => {
      const name = (card.dataset.productName || '').toLowerCase();
      const category = (card.dataset.productCategory || '').toLowerCase();
      const desc = (card.dataset.productDesc || '').toLowerCase();

      const matches = !query || name.includes(query) || category.includes(query) || desc.includes(query);

      if (matches) {
        card.style.display = '';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    if (countEl) {
      countEl.textContent = `Menampilkan ${visibleCount} dari ${cards.length} perangkat`;
    }

    // Manage Empty State on live search
    let emptyEl = document.getElementById('live-empty-feedback');
    if (visibleCount === 0) {
      if (!emptyEl && container) {
        emptyEl = document.createElement('div');
        emptyEl.id = 'live-empty-feedback';
        emptyEl.style.cssText = 'grid-column: 1 / -1; background: #ffffff; border: 1px solid var(--line-hairline); border-radius: var(--radius-xl); padding: 3.5rem 1.5rem; text-align: center;';
        emptyEl.innerHTML = `
          <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-title); margin-bottom: 4px;">Tidak Ada Perangkat yang Sesuai</h3>
          <p style="color: var(--text-muted); font-size: 0.88rem;">Tidak ditemukan produk dengan kata kunci "<strong>${escapeHtml(query)}</strong>".</p>
          <button type="button" id="btn-clear-live-search" class="btn btn-sm btn-secondary" style="margin-top: 1rem;">Bersihkan Pencarian</button>
        `;
        container.appendChild(emptyEl);
        document.getElementById('btn-clear-live-search').addEventListener('click', () => {
          if (searchInput) {
            searchInput.value = '';
            performFilter();
          }
        });
      }
    } else if (emptyEl) {
      emptyEl.remove();
    }
  };

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(performFilter, 120);
    });
  }
}

/**
 * 3. Spec Inspector / Quick View Modal
 */
function initSpecInspectorModal() {
  const modal = document.getElementById('inspector-modal');
  if (!modal) return;

  const skuEl = document.getElementById('inspector-sku');
  const titleEl = document.getElementById('inspector-title');
  const catEl = document.getElementById('inspector-category');
  const descEl = document.getElementById('inspector-desc');
  const priceEl = document.getElementById('inspector-price');
  const stockEl = document.getElementById('inspector-stock');
  const valEl = document.getElementById('inspector-valuation');
  const editLink = document.getElementById('inspector-edit-link');
  const closeBtn = document.getElementById('inspector-close');
  const btnClose = document.getElementById('inspector-btn-close');

  const openInspector = (card) => {
    const id = card.dataset.productId;
    const name = card.dataset.productName;
    const cat = card.dataset.productCategory;
    const price = parseFloat(card.dataset.productPrice || '0');
    const priceFormatted = card.dataset.productPriceFormatted;
    const stock = parseInt(card.dataset.productStock || '0', 10);
    const sku = card.dataset.productSku;
    const desc = card.dataset.productDesc;

    if (skuEl) skuEl.textContent = sku;
    if (titleEl) titleEl.textContent = name;
    if (catEl) catEl.textContent = cat;
    if (descEl) descEl.textContent = desc;
    if (priceEl) priceEl.textContent = priceFormatted;
    if (stockEl) stockEl.textContent = stock + ' unit';

    const batchVal = price * stock;
    if (valEl) valEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(batchVal);

    if (editLink) editLink.href = 'edit.php?id=' + encodeURIComponent(id);

    modal.classList.add('is-active');
  };

  const closeInspector = () => {
    modal.classList.remove('is-active');
  };

  document.querySelectorAll('.btn-inspect-trigger').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const card = btn.closest('.product-card');
      if (card) openInspector(card);
    });
  });

  if (closeBtn) closeBtn.addEventListener('click', closeInspector);
  if (btnClose) btnClose.addEventListener('click', closeInspector);

  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeInspector();
  });
}

/**
 * 4. Interactive Form Valuation & Stock Health Calculator
 */
function initFormCalculators() {
  const priceInput = document.getElementById('price');
  const stockInput = document.getElementById('stock');
  const pricePreview = document.getElementById('price-preview');
  const totalValuation = document.getElementById('total-batch-valuation');
  const stockHealth = document.getElementById('stock-health-preview');

  if (!priceInput || !stockInput) return;

  const updateCalculations = () => {
    const rawPrice = priceInput.value.replace(/[^0-9.]/g, '');
    const price = parseFloat(rawPrice) || 0;

    const rawStock = stockInput.value.replace(/[^0-9]/g, '');
    const stock = parseInt(rawStock, 10) || 0;

    // 1. Single price preview
    if (pricePreview) {
      pricePreview.textContent = price > 0 ? 'Rp ' + new Intl.NumberFormat('id-ID').format(price) : 'Rp 0';
    }

    // 2. Batch valuation preview (price * stock)
    if (totalValuation) {
      const total = price * stock;
      totalValuation.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    }

    // 3. Stock Health badge preview
    if (stockHealth) {
      if (stock <= 0) {
        stockHealth.className = 'stock-tag stock-depleted';
        stockHealth.textContent = 'Habis';
      } else if (stock <= 5) {
        stockHealth.className = 'stock-tag stock-critical';
        stockHealth.textContent = `Menipis (${stock} unit)`;
      } else {
        stockHealth.className = 'stock-tag stock-nominal';
        stockHealth.textContent = `Tersedia (${stock} unit)`;
      }
    }
  };

  priceInput.addEventListener('input', updateCalculations);
  stockInput.addEventListener('input', updateCalculations);
  updateCalculations();
}

/**
 * 5. Drag-and-Drop Image Dropzone with Clear Capability
 */
function initImageUploadDropzone() {
  const fileInput = document.getElementById('image-input');
  const dropzone = document.getElementById('file-dropzone');
  const previewBox = document.getElementById('upload-preview-box');
  const previewImg = document.getElementById('upload-preview-img');
  const previewName = document.getElementById('upload-preview-name');
  const previewSize = document.getElementById('upload-preview-size');
  const clearBtn = document.getElementById('btn-clear-upload');

  if (!fileInput || !dropzone) return;

  const processFile = (file) => {
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
      alert('Peringatan: Ukuran berkas melebihi batas 2MB.');
      fileInput.value = '';
      return;
    }

    if (previewBox && previewImg && previewName && previewSize) {
      previewName.textContent = file.name;
      previewSize.textContent = (file.size / 1024).toFixed(1) + ' KB';

      const reader = new FileReader();
      reader.onload = (e) => {
        previewImg.src = e.target.result;
        previewBox.style.display = 'flex';
      };
      reader.readAsDataURL(file);
    }
  };

  fileInput.addEventListener('change', (e) => {
    if (e.target.files && e.target.files[0]) {
      processFile(e.target.files[0]);
    }
  });

  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      fileInput.value = '';
      if (previewBox) previewBox.style.display = 'none';
      if (previewImg) previewImg.src = '';
    });
  }

  // Drag and drop visual cues
  ['dragenter', 'dragover'].forEach((evt) => {
    dropzone.addEventListener(evt, (e) => {
      e.preventDefault();
      dropzone.classList.add('is-dragover');
    });
  });

  ['dragleave', 'drop'].forEach((evt) => {
    dropzone.addEventListener(evt, (e) => {
      e.preventDefault();
      dropzone.classList.remove('is-dragover');
    });
  });

  dropzone.addEventListener('drop', (e) => {
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      fileInput.files = e.dataTransfer.files;
      processFile(e.dataTransfer.files[0]);
    }
  });
}

/**
 * 6. Accessible Delete Confirmation Modal
 */
function initDeleteModal() {
  const modal = document.getElementById('delete-modal');
  if (!modal) return;

  const targetNameEl = document.getElementById('modal-delete-name');
  const targetIdInput = document.getElementById('modal-delete-id');
  const cancelBtn = document.getElementById('modal-delete-cancel');
  const triggers = document.querySelectorAll('.btn-delete-trigger');

  const openDeleteModal = (id, name) => {
    if (targetIdInput) targetIdInput.value = id;
    if (targetNameEl) targetNameEl.textContent = name;
    modal.classList.add('is-active');
  };

  const closeDeleteModal = () => {
    modal.classList.remove('is-active');
  };

  triggers.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const id = btn.dataset.id;
      const name = btn.dataset.name;
      openDeleteModal(id, name);
    });
  });

  if (cancelBtn) cancelBtn.addEventListener('click', closeDeleteModal);

  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeDeleteModal();
  });
}

/**
 * 7. Auto-dismissing Flash Toasts
 */
function initFlashDismiss() {
  const flashAlerts = document.querySelectorAll('.flash-alert');
  flashAlerts.forEach((alert) => {
    // Auto-dismiss after 6 seconds
    const timer = setTimeout(() => {
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-6px)';
      alert.style.transition = 'all 0.25s ease';
      setTimeout(() => alert.parentElement?.remove(), 250);
    }, 6000);

    const closeBtn = alert.querySelector('.flash-dismiss');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        clearTimeout(timer);
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-6px)';
        alert.style.transition = 'all 0.25s ease';
        setTimeout(() => alert.parentElement?.remove(), 250);
      });
    }
  });
}

/**
 * 8. Form Double-Submit Protection (PRG Support)
 */
function initFormDoubleSubmitGuard() {
  const forms = document.querySelectorAll('form[method="POST"], form[method="post"]');
  forms.forEach((form) => {
    form.addEventListener('submit', () => {
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn && !form.classList.contains('is-submitting')) {
        form.classList.add('is-submitting');
        submitBtn.style.opacity = '0.75';
        submitBtn.style.pointerEvents = 'none';
      }
    });
  });
}

/**
 * Helper to escape HTML characters in live client messages
 */
function escapeHtml(str) {
  return String(str || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
