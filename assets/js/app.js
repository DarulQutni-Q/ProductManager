/**
 * =====================================================================
 * PRODUCT MANAGER // CLIENT INTERACTION CONTROLLER (SHADCN/UI STYLE)
 * =====================================================================
 * 1. Dark Mode Theme Switcher (System & LocalStorage persistence)
 * 2. View Mode Toggle (Grid vs Table) with LocalStorage persistence
 * 3. Global Keyboard Shortcuts ('/', 'n', 'k', 'v', 'Esc')
 * 4. Instant Live Search & Filter across Grid and Table items
 * 5. Spec Inspector / Quick View Dialog (Shadcn Dialog)
 * 6. Safe Delete Confirmation Dialog (Shadcn AlertDialog)
 * 7. Live Valuation & Stock Health Calculator in Forms
 * 8. Drag-and-Drop Image Dropzone with instant preview
 * 9. Auto-dismissing Flash Toasts
 * 10. Form Double-Submit Protection
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
  initThemeToggle();
  initViewModeToggle();
  initKeyboardShortcuts();
  initLiveSearchAndFilter();
  initSpecInspectorModal();
  initDeleteModal();
  initFormCalculators();
  initImageUploadDropzone();
  initFlashDismiss();
  initFormDoubleSubmitGuard();
});

/**
 * 1. Dark Mode Theme Switcher
 */
function initThemeToggle() {
  const toggleBtn = document.getElementById('btn-theme-toggle');
  if (!toggleBtn) return;

  const sunIcon = toggleBtn.querySelector('.theme-icon-sun');
  const moonIcon = toggleBtn.querySelector('.theme-icon-moon');

  const updateIcons = (isDark) => {
    if (sunIcon) sunIcon.style.display = isDark ? 'inline-block' : 'none';
    if (moonIcon) moonIcon.style.display = isDark ? 'none' : 'inline-block';
  };

  const isDarkInitial = document.documentElement.classList.contains('dark');
  updateIcons(isDarkInitial);

  toggleBtn.addEventListener('click', () => {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('pm_theme', isDark ? 'dark' : 'light');
    updateIcons(isDark);
  });
}

/**
 * 2. View Mode Switcher (Grid vs Table)
 */
function initViewModeToggle() {
  const btnGrid = document.getElementById('btn-view-grid');
  const btnTable = document.getElementById('btn-view-table');
  const gridView = document.getElementById('catalog-grid-view');
  const tableView = document.getElementById('catalog-table-view');

  if (!btnGrid || !btnTable || !gridView || !tableView) return;

  const setView = (mode) => {
    if (mode === 'table') {
      gridView.style.display = 'none';
      tableView.style.display = 'block';
      btnGrid.classList.remove('is-active');
      btnTable.classList.add('is-active');
      localStorage.setItem('pm_catalog_view', 'table');
    } else {
      gridView.style.display = 'grid';
      tableView.style.display = 'none';
      btnTable.classList.remove('is-active');
      btnGrid.classList.add('is-active');
      localStorage.setItem('pm_catalog_view', 'grid');
    }
  };

  // Restore preferred view or default to grid
  const savedView = localStorage.getItem('pm_catalog_view') || 'grid';
  setView(savedView);

  btnGrid.addEventListener('click', () => setView('grid'));
  btnTable.addEventListener('click', () => setView('table'));

  window.toggleViewMode = () => {
    const current = localStorage.getItem('pm_catalog_view') || 'grid';
    setView(current === 'grid' ? 'table' : 'grid');
  };
}

/**
 * 3. Global Keyboard Shortcuts
 */
function initKeyboardShortcuts() {
  window.addEventListener('keydown', (e) => {
    const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
    const isEditing = activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select';

    // '/' to focus search input
    if (e.key === '/' && !isEditing) {
      e.preventDefault();
      const searchInput = document.getElementById('search-input');
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
      }
    }

    // 'n' or 'N' to go to create page
    if ((e.key === 'n' || e.key === 'N') && !isEditing && !e.metaKey && !e.ctrlKey) {
      if (!window.location.pathname.includes('create.php')) {
        window.location.href = 'create.php';
      }
    }

    // 'k' or 'K' to go to catalog
    if ((e.key === 'k' || e.key === 'K') && !isEditing && !e.metaKey && !e.ctrlKey) {
      if (!window.location.pathname.endsWith('index.php')) {
        window.location.href = 'index.php';
      }
    }

    // 'v' or 'V' to toggle view mode
    if ((e.key === 'v' || e.key === 'V') && !isEditing && !e.metaKey && !e.ctrlKey) {
      if (typeof window.toggleViewMode === 'function') {
        window.toggleViewMode();
      }
    }

    // 'Escape' to close modals
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-backdrop.is-open').forEach((m) => {
        m.classList.remove('is-open');
      });
    }
  });
}

/**
 * 4. Instant Live Search & Filter (Progressive Enhancement for Grid & Table)
 */
function initLiveSearchAndFilter() {
  const searchInput = document.getElementById('search-input');
  const gridCards = document.querySelectorAll('.product-card');
  const tableRows = document.querySelectorAll('.table-product-row');
  const countEl = document.getElementById('live-catalog-count');

  if (!gridCards.length && !tableRows.length) return;

  let debounceTimer;

  const performFilter = () => {
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    let visibleCount = 0;

    // Filter grid cards
    gridCards.forEach((card) => {
      const name = (card.dataset.productName || '').toLowerCase();
      const category = (card.dataset.productCategory || '').toLowerCase();
      const desc = (card.dataset.productDesc || '').toLowerCase();
      const sku = (card.dataset.productSku || '').toLowerCase();

      const matches = !query || name.includes(query) || category.includes(query) || desc.includes(query) || sku.includes(query);
      card.style.display = matches ? '' : 'none';
      if (matches) visibleCount++;
    });

    // Filter table rows
    tableRows.forEach((row) => {
      const name = (row.dataset.productName || '').toLowerCase();
      const category = (row.dataset.productCategory || '').toLowerCase();
      const desc = (row.dataset.productDesc || '').toLowerCase();
      const sku = (row.dataset.productSku || '').toLowerCase();

      const matches = !query || name.includes(query) || category.includes(query) || desc.includes(query) || sku.includes(query);
      row.style.display = matches ? '' : 'none';
    });

    if (countEl) {
      const total = gridCards.length || tableRows.length;
      countEl.textContent = `Menampilkan ${visibleCount} dari ${total} item`;
    }
  };

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(performFilter, 100);
    });
  }
}

/**
 * 5. Spec Inspector / Detail Modal
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
  const imgEl = document.getElementById('inspector-img');
  const imgWrap = document.getElementById('inspector-image-wrap');
  const editLink = document.getElementById('inspector-edit-link');
  const closeBtn = document.getElementById('inspector-close');
  const btnClose = document.getElementById('inspector-btn-close');

  const openInspector = (el) => {
    const id = el.dataset.productId;
    const name = el.dataset.productName;
    const cat = el.dataset.productCategory;
    const price = parseFloat(el.dataset.productPrice || '0');
    const priceFormatted = el.dataset.productPriceFormatted;
    const stock = parseInt(el.dataset.productStock || '0', 10);
    const sku = el.dataset.productSku;
    const desc = el.dataset.productDesc;
    const img = el.dataset.productImg;

    if (skuEl) skuEl.textContent = sku;
    if (titleEl) titleEl.textContent = name;
    if (catEl) catEl.textContent = cat;
    if (descEl) descEl.textContent = desc;
    if (priceEl) priceEl.textContent = priceFormatted;
    if (stockEl) stockEl.textContent = stock + ' unit';

    const batchVal = price * stock;
    if (valEl) valEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(batchVal);

    if (imgEl && imgWrap) {
      if (img) {
        imgEl.src = img;
        imgEl.alt = name;
        imgWrap.style.display = 'flex';
      } else {
        imgWrap.style.display = 'none';
      }
    }

    if (editLink) editLink.href = 'edit.php?id=' + encodeURIComponent(id);

    modal.classList.add('is-open');
  };

  const closeInspector = () => {
    modal.classList.remove('is-open');
  };

  document.querySelectorAll('.btn-inspect-trigger').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const item = btn.closest('.product-card, .table-product-row');
      if (item) openInspector(item);
    });
  });

  if (closeBtn) closeBtn.addEventListener('click', closeInspector);
  if (btnClose) btnClose.addEventListener('click', closeInspector);

  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeInspector();
  });
}

/**
 * 6. Accessible Delete Confirmation Dialog (Shadcn AlertDialog)
 */
function initDeleteModal() {
  const modal = document.getElementById('delete-modal');
  if (!modal) return;

  const targetNameEl = document.getElementById('modal-delete-name');
  const targetIdInput = document.getElementById('modal-delete-id');
  const cancelBtn = document.getElementById('modal-delete-cancel');
  const xBtn = document.getElementById('modal-delete-x');
  const triggers = document.querySelectorAll('.btn-delete-trigger');

  const openDeleteModal = (id, name) => {
    if (targetIdInput) targetIdInput.value = id;
    if (targetNameEl) targetNameEl.textContent = name;
    modal.classList.add('is-open');
  };

  const closeDeleteModal = () => {
    modal.classList.remove('is-open');
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
  if (xBtn) xBtn.addEventListener('click', closeDeleteModal);

  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeDeleteModal();
  });
}

/**
 * 7. Interactive Form Valuation & Stock Health Calculator
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

    if (pricePreview) {
      pricePreview.textContent = price > 0 ? 'Rp ' + new Intl.NumberFormat('id-ID').format(price) : 'Rp 0';
    }

    if (totalValuation) {
      const total = price * stock;
      totalValuation.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    }

    if (stockHealth) {
      if (stock <= 0) {
        stockHealth.className = 'badge badge-rose';
        stockHealth.textContent = 'Habis (0)';
      } else if (stock <= 5) {
        stockHealth.className = 'badge badge-amber';
        stockHealth.textContent = `Menipis (${stock})`;
      } else {
        stockHealth.className = 'badge badge-emerald';
        stockHealth.textContent = `Tersedia (${stock})`;
      }
    }
  };

  priceInput.addEventListener('input', updateCalculations);
  stockInput.addEventListener('input', updateCalculations);
  updateCalculations();
}

/**
 * 8. Drag-and-Drop Image Dropzone with Instant Preview
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
 * 9. Auto-dismissing Flash Toasts
 */
function initFlashDismiss() {
  const flashBanners = document.querySelectorAll('.flash-banner');
  flashBanners.forEach((banner) => {
    const timer = setTimeout(() => {
      banner.style.opacity = '0';
      banner.style.transform = 'translateY(-4px)';
      banner.style.transition = 'all 0.2s ease';
      setTimeout(() => banner.parentElement?.remove(), 200);
    }, 5000);

    const closeBtn = banner.querySelector('.flash-dismiss');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        clearTimeout(timer);
        banner.style.opacity = '0';
        banner.style.transform = 'translateY(-4px)';
        banner.style.transition = 'all 0.2s ease';
        setTimeout(() => banner.parentElement?.remove(), 200);
      });
    }
  });
}

/**
 * 10. Form Double-Submit Protection
 */
function initFormDoubleSubmitGuard() {
  const forms = document.querySelectorAll('form[method="POST"], form[method="post"]');
  forms.forEach((form) => {
    form.addEventListener('submit', () => {
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn && !form.classList.contains('is-submitting')) {
        form.classList.add('is-submitting');
        submitBtn.style.opacity = '0.7';
        submitBtn.style.pointerEvents = 'none';
      }
    });
  });
}
