/**
 * Northwind Web Application - Main Client Logic
 */

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});

const App = {
  state: {
    products: [],
    categories: [],
    suppliers: [],
    stats: {},
    pagination: {
      currentPage: 1,
      perPage: 10,
      totalPages: 1,
      totalRecords: 0
    },
    filters: {
      search: '',
      categoryId: '',
      supplierId: '',
      status: 'all',
      sortBy: 'ProductID',
      order: 'DESC'
    },
    selectedProductId: null,
    isLoading: false
  },

  debounceTimer: null,

  init() {
    this.bindEvents();
    this.loadCategories();
    this.loadSuppliers();
    this.loadStats();
    this.loadProducts();
  },

  bindEvents() {
    // Search Input with Debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
          this.state.filters.search = e.target.value.trim();
          this.state.pagination.currentPage = 1;
          this.loadProducts();
        }, 350);
      });
    }

    // Filter Selects
    document.getElementById('filterCategory')?.addEventListener('change', (e) => {
      this.state.filters.categoryId = e.target.value;
      this.state.pagination.currentPage = 1;
      this.loadProducts();
    });

    document.getElementById('filterSupplier')?.addEventListener('change', (e) => {
      this.state.filters.supplierId = e.target.value;
      this.state.pagination.currentPage = 1;
      this.loadProducts();
    });

    document.getElementById('filterStatus')?.addEventListener('change', (e) => {
      this.state.filters.status = e.target.value;
      this.state.pagination.currentPage = 1;
      this.loadProducts();
    });

    document.getElementById('filterSort')?.addEventListener('change', (e) => {
      const [sortBy, order] = e.target.value.split(':');
      this.state.filters.sortBy = sortBy;
      this.state.filters.order = order || 'DESC';
      this.loadProducts();
    });

    // Modals
    document.getElementById('btnOpenAddModal')?.addEventListener('click', () => {
      this.openProductModal();
    });

    document.getElementById('btnCloseModal')?.addEventListener('click', () => {
      this.closeProductModal();
    });

    document.getElementById('btnCancelModal')?.addEventListener('click', () => {
      this.closeProductModal();
    });

    document.getElementById('productForm')?.addEventListener('submit', (e) => {
      e.preventDefault();
      this.handleProductSubmit();
    });

    // Delete Modal
    document.getElementById('btnCloseDeleteModal')?.addEventListener('click', () => {
      this.closeDeleteModal();
    });

    document.getElementById('btnCancelDelete')?.addEventListener('click', () => {
      this.closeDeleteModal();
    });

    document.getElementById('btnConfirmDelete')?.addEventListener('click', () => {
      this.handleProductDelete();
    });

    // Close Modals on Overlay Click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
          this.closeProductModal();
          this.closeDeleteModal();
        }
      });
    });

    // Escape key to close modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closeProductModal();
        this.closeDeleteModal();
      }
    });
  },

  // API Requests
  async loadCategories() {
    try {
      const res = await fetch('api/categories.php');
      const result = await res.json();
      if (result.success) {
        this.state.categories = result.data;
        this.renderCategoryOptions();
      }
    } catch (err) {
      console.error('Error fetching categories:', err);
    }
  },

  async loadSuppliers() {
    try {
      const res = await fetch('api/suppliers.php');
      const result = await res.json();
      if (result.success) {
        this.state.suppliers = result.data;
        this.renderSupplierOptions();
      }
    } catch (err) {
      console.error('Error fetching suppliers:', err);
    }
  },

  async loadStats() {
    try {
      const res = await fetch('api/products.php?stats=true');
      const result = await res.json();
      if (result.success) {
        this.state.stats = result.data;
        this.renderStats();
      }
    } catch (err) {
      console.error('Error fetching stats:', err);
    }
  },

  async loadProducts() {
    this.renderLoading(true);
    const { search, categoryId, supplierId, status, sortBy, order } = this.state.filters;
    const { currentPage, perPage } = this.state.pagination;

    const params = new URLSearchParams({
      search,
      category_id: categoryId,
      supplier_id: supplierId,
      status,
      sort_by: sortBy,
      order,
      page: currentPage,
      limit: perPage
    });

    try {
      const res = await fetch(`api/products.php?${params.toString()}`);
      const result = await res.json();

      if (result.success) {
        this.state.products = result.data || [];
        this.state.pagination.totalRecords = result.pagination.total_records;
        this.state.pagination.totalPages = result.pagination.total_pages;
        this.renderProductsTable();
        this.renderPagination();
      } else {
        Toast.error('ข้อผิดพลาด', result.message || 'ไม่สามารถดึงข้อมูลสินค้าได้');
      }
    } catch (err) {
      Toast.error('ข้อผิดพลาดการเชื่อมต่อ', 'ไม่สามารถเชื่อมต่อกับฐานข้อมูลหรือ API ได้');
    } finally {
      this.renderLoading(false);
    }
  },

  // Rendering
  renderStats() {
    const { total_products, total_inventory_value, out_of_stock_count, low_stock_count } = this.state.stats;
    const formatNumber = (num) => Number(num || 0).toLocaleString();
    const formatCurrency = (num) => '$' + Number(num || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    document.getElementById('statTotalProducts').innerText = formatNumber(total_products);
    document.getElementById('statInventoryValue').innerText = formatCurrency(total_inventory_value);
    document.getElementById('statLowStock').innerText = formatNumber(low_stock_count);
    document.getElementById('statOutOfStock').innerText = formatNumber(out_of_stock_count);
  },

  renderCategoryOptions() {
    const filterCat = document.getElementById('filterCategory');
    const formCat = document.getElementById('prodCategory');
    
    let optionsHtml = '<option value="">ทุกหมวดหมู่ (All Categories)</option>';
    let formOptionsHtml = '<option value="">-- เลือกหมวดหมู่ (Select Category) --</option>';

    this.state.categories.forEach(cat => {
      optionsHtml += `<option value="${cat.CategoryID}">${cat.CategoryName}</option>`;
      formOptionsHtml += `<option value="${cat.CategoryID}">${cat.CategoryName}</option>`;
    });

    if (filterCat) filterCat.innerHTML = optionsHtml;
    if (formCat) formCat.innerHTML = formOptionsHtml;
  },

  renderSupplierOptions() {
    const filterSup = document.getElementById('filterSupplier');
    const formSup = document.getElementById('prodSupplier');

    let optionsHtml = '<option value="">ผู้จำหน่ายทั้งหมด (All Suppliers)</option>';
    let formOptionsHtml = '<option value="">-- เลือกผู้จำหน่าย (Select Supplier) --</option>';

    this.state.suppliers.forEach(sup => {
      optionsHtml += `<option value="${sup.SupplierID}">${sup.CompanyName} (${sup.Country || 'N/A'})</option>`;
      formOptionsHtml += `<option value="${sup.SupplierID}">${sup.CompanyName}</option>`;
    });

    if (filterSup) filterSup.innerHTML = optionsHtml;
    if (formSup) formSup.innerHTML = formOptionsHtml;
  },

  renderProductsTable() {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;

    if (this.state.products.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="state-container">
              <div style="font-size: 2.5rem;">📦</div>
              <div style="font-size: 1.1rem; font-weight: 600;">ไม่พบข้อมูลสินค้า</div>
              <div style="color: var(--text-muted); font-size: 0.85rem;">ลองปรับคำค้นหาหรือเปลี่ยนตัวกรองข้อมูล</div>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = this.state.products.map(p => {
      const price = '$' + parseFloat(p.UnitPrice).toFixed(2);
      const stock = parseInt(p.UnitsInStock, 10);
      const reorder = parseInt(p.ReorderLevel, 10);
      const isDiscontinued = parseInt(p.Discontinued, 10) === 1;

      let stockBadge = '';
      if (isDiscontinued) {
        stockBadge = '<span class="badge badge-discontinued">ยกเลิกจำหน่าย</span>';
      } else if (stock === 0) {
        stockBadge = '<span class="badge badge-out-stock">สินค้าหมด (0)</span>';
      } else if (stock <= reorder) {
        stockBadge = `<span class="badge badge-low-stock">เหลือน้อย (${stock})</span>`;
      } else {
        stockBadge = `<span class="badge badge-in-stock">มีสินค้า (${stock})</span>`;
      }

      return `
        <tr>
          <td><span style="font-weight: 700; color: var(--text-muted);">#${p.ProductID}</span></td>
          <td>
            <div style="font-weight: 600; color: var(--text-main); font-size: 0.95rem;">${this.escapeHtml(p.ProductName)}</div>
            <div style="font-size: 0.78rem; color: var(--text-sub);">${this.escapeHtml(p.QuantityPerUnit || '-')}</div>
          </td>
          <td>
            <span class="badge badge-category">${this.escapeHtml(p.CategoryName || 'Unassigned')}</span>
          </td>
          <td>
            <span style="color: var(--text-muted); font-size: 0.85rem;">${this.escapeHtml(p.SupplierName || '-')}</span>
          </td>
          <td>
            <span style="font-weight: 700; color: #34D399; font-size: 1rem;">${price}</span>
          </td>
          <td>${stockBadge}</td>
          <td>
            <div class="table-actions">
              <button class="btn btn-icon btn-action-edit" title="แก้ไขสินค้า" onclick="App.openProductModal(${p.ProductID})">
                ✏️
              </button>
              <button class="btn btn-icon btn-action-delete" title="ลบสินค้า" onclick="App.openDeleteModal(${p.ProductID}, '${this.escapeJsString(p.ProductName)}')">
                🗑️
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  },

  renderPagination() {
    const { currentPage, totalPages, totalRecords, perPage } = this.state.pagination;
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');

    if (paginationInfo) {
      const start = totalRecords > 0 ? (currentPage - 1) * perPage + 1 : 0;
      const end = Math.min(currentPage * perPage, totalRecords);
      paginationInfo.innerText = `แสดง ${start} - ${end} จากทั้งหมด ${totalRecords} รายการ`;
    }

    if (paginationControls) {
      if (totalPages <= 1) {
        paginationControls.innerHTML = '';
        return;
      }

      let html = `
        <button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="App.changePage(${currentPage - 1})">
          ‹
        </button>
      `;

      for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
          html += `
            <button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="App.changePage(${i})">
              ${i}
            </button>
          `;
        } else if (i === currentPage - 3 || i === currentPage + 3) {
          html += `<span style="color: var(--text-sub); padding: 0 4px;">...</span>`;
        }
      }

      html += `
        <button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="App.changePage(${currentPage + 1})">
          ›
        </button>
      `;

      paginationControls.innerHTML = html;
    }
  },

  changePage(page) {
    if (page < 1 || page > this.state.pagination.totalPages) return;
    this.state.pagination.currentPage = page;
    this.loadProducts();
  },

  renderLoading(isLoading) {
    this.state.isLoading = isLoading;
    const tbody = document.getElementById('productsTableBody');
    if (isLoading && tbody) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7">
            <div class="state-container">
              <div class="spinner"></div>
              <div style="color: var(--text-muted); font-size: 0.9rem;">กำลังโหลดข้อมูล...</div>
            </div>
          </td>
        </tr>
      `;
    }
  },

  // Modal Handlers
  async openProductModal(productId = null) {
    this.resetProductFormErrors();
    const modal = document.getElementById('productModal');
    const modalTitle = document.getElementById('modalProductTitle');
    const form = document.getElementById('productForm');

    form.reset();

    if (productId) {
      modalTitle.innerText = `แก้ไขสินค้า (Edit Product #${productId})`;
      this.state.selectedProductId = productId;
      
      try {
        const res = await fetch(`api/products.php?id=${productId}`);
        const result = await res.json();
        if (result.success && result.data) {
          const p = result.data;
          document.getElementById('prodId').value = p.ProductID;
          document.getElementById('prodName').value = p.ProductName;
          document.getElementById('prodCategory').value = p.CategoryID || '';
          document.getElementById('prodSupplier').value = p.SupplierID || '';
          document.getElementById('prodQuantityPerUnit').value = p.QuantityPerUnit || '';
          document.getElementById('prodUnitPrice').value = p.UnitPrice;
          document.getElementById('prodUnitsInStock').value = p.UnitsInStock;
          document.getElementById('prodUnitsOnOrder').value = p.UnitsOnOrder;
          document.getElementById('prodReorderLevel').value = p.ReorderLevel;
          document.getElementById('prodDiscontinued').checked = parseInt(p.Discontinued, 10) === 1;
        }
      } catch (err) {
        Toast.error('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลสินค้าที่ต้องการแก้ไขได้');
        return;
      }
    } else {
      modalTitle.innerText = 'เพิ่มสินค้าใหม่ (Add New Product)';
      this.state.selectedProductId = null;
      document.getElementById('prodId').value = '';
    }

    modal.classList.add('active');
  },

  closeProductModal() {
    document.getElementById('productModal')?.classList.remove('active');
    this.state.selectedProductId = null;
  },

  openDeleteModal(productId, productName) {
    this.state.selectedProductId = productId;
    document.getElementById('deleteProductName').innerText = productName;
    document.getElementById('deleteModal')?.classList.add('active');
  },

  closeDeleteModal() {
    document.getElementById('deleteModal')?.classList.remove('active');
    this.state.selectedProductId = null;
  },

  // CRUD Actions
  async handleProductSubmit() {
    const id = document.getElementById('prodId').value;
    const isEdit = !!id;

    const data = {
      ProductID: id ? parseInt(id, 10) : undefined,
      ProductName: document.getElementById('prodName').value.trim(),
      CategoryID: document.getElementById('prodCategory').value || null,
      SupplierID: document.getElementById('prodSupplier').value || null,
      QuantityPerUnit: document.getElementById('prodQuantityPerUnit').value.trim(),
      UnitPrice: document.getElementById('prodUnitPrice').value,
      UnitsInStock: document.getElementById('prodUnitsInStock').value,
      UnitsOnOrder: document.getElementById('prodUnitsOnOrder').value,
      ReorderLevel: document.getElementById('prodReorderLevel').value,
      Discontinued: document.getElementById('prodDiscontinued').checked ? 1 : 0
    };

    // Client-side validation
    let hasError = false;
    this.resetProductFormErrors();

    if (!data.ProductName) {
      this.showInputError('prodName', 'errProdName', 'กรุณาระบุชื่อสินค้า');
      hasError = true;
    }

    if (data.UnitPrice === '' || isNaN(data.UnitPrice) || Number(data.UnitPrice) < 0) {
      this.showInputError('prodUnitPrice', 'errProdUnitPrice', 'กรุณาระบุราคาสินค้าที่ถูกต้อง (ตัวเลข >= 0)');
      hasError = true;
    }

    if (hasError) {
      Toast.warning('ข้อมูลไม่ถูกต้อง', 'กรุณาตรวจสอบและกรอกข้อมูลในช่องที่จำเป็น');
      return;
    }

    const btnSubmit = document.getElementById('btnSaveProduct');
    btnSubmit.disabled = true;
    btnSubmit.innerText = 'กำลังบันทึก...';

    try {
      const res = await fetch('api/products.php', {
        method: isEdit ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });

      const result = await res.json();

      if (res.ok && result.success) {
        Toast.success(isEdit ? 'แก้ไขข้อมูลสำเร็จ' : 'เพิ่มสินค้าสำเร็จ', result.message);
        this.closeProductModal();
        this.loadProducts();
        this.loadStats();
      } else {
        if (result.errors) {
          Object.keys(result.errors).forEach(key => {
            const fieldId = 'prod' + key;
            const errId = 'errProd' + key;
            this.showInputError(fieldId, errId, result.errors[key]);
          });
        }
        Toast.error('บันทึกไม่สำเร็จ', result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
      }
    } catch (err) {
      Toast.error('ข้อผิดพลาด', 'ไม่สามารถติดต่อกับเซิร์ฟเวอร์ได้');
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.innerText = 'บันทึกข้อมูล (Save)';
    }
  },

  async handleProductDelete() {
    const id = this.state.selectedProductId;
    if (!id) return;

    const btnConfirm = document.getElementById('btnConfirmDelete');
    btnConfirm.disabled = true;
    btnConfirm.innerText = 'กำลังลบ...';

    try {
      const res = await fetch(`api/products.php?id=${id}`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' }
      });

      const result = await res.json();

      if (res.ok && result.success) {
        Toast.success('ลบสินค้าสำเร็จ', result.message);
        this.closeDeleteModal();
        this.loadProducts();
        this.loadStats();
      } else {
        Toast.error('ลบไม่สำเร็จ', result.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
      }
    } catch (err) {
      Toast.error('ข้อผิดพลาด', 'ไม่สามารถติดต่อกับเซิร์ฟเวอร์เพื่อลบข้อมูลได้');
    } finally {
      btnConfirm.disabled = false;
      btnConfirm.innerText = 'ยืนยันลบสินค้า';
    }
  },

  // Helper Methods
  showInputError(inputId, errId, msg) {
    const input = document.getElementById(inputId);
    const errSpan = document.getElementById(errId);
    if (input) input.classList.add('is-invalid');
    if (errSpan) errSpan.innerText = msg;
  },

  resetProductFormErrors() {
    document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.form-error-msg').forEach(el => el.innerText = '');
  },

  escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  },

  escapeJsString(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
  }
};
