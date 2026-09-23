<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Northwind Products Management | Cloud Web Application</title>
  <meta name="description" content="Development and Deployment of Web App with PHP, MySQL and Cloud PaaS (Northwind Database)">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📦</text></svg>">
</head>
<body>

  <div class="container">
    <!-- Header -->
    <header class="glass-panel app-header">
      <div class="brand-wrapper">
        <div class="brand-icon">📦</div>
        <div>
          <h1 class="brand-title">Northwind Products Manager</h1>
          <p class="brand-subtitle">Cloud Web Application &bull; PHP &amp; MySQL (dbNorthwind)</p>
        </div>
      </div>
      <div class="header-badges">
        <span class="badge-pill online">Cloud Live</span>
        <span class="badge-pill">PHP 8.x + MySQL</span>
      </div>
    </header>

    <!-- Analytics Stats Overview -->
    <section class="stats-grid">
      <div class="glass-panel stat-card primary">
        <div class="stat-icon">📊</div>
        <div class="stat-info">
          <span class="stat-label">สินค้าทั้งหมด (Total Products)</span>
          <span class="stat-value" id="statTotalProducts">-</span>
        </div>
      </div>

      <div class="glass-panel stat-card success">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
          <span class="stat-label">ราคาเฉลี่ยต่อหน่วย (Avg Price)</span>
          <span class="stat-value" id="statAvgPrice">-</span>
        </div>
      </div>

      <div class="glass-panel stat-card warning">
        <div class="stat-icon">🏷️</div>
        <div class="stat-info">
          <span class="stat-label">หมวดหมู่ทั้งหมด (Categories)</span>
          <span class="stat-value" id="statCategories">-</span>
        </div>
      </div>

      <div class="glass-panel stat-card danger">
        <div class="stat-icon">🏭</div>
        <div class="stat-info">
          <span class="stat-label">ผู้จัดจำหน่าย (Suppliers)</span>
          <span class="stat-value" id="statSuppliers">-</span>
        </div>
      </div>
    </section>

    <!-- Main Toolbar & Controls -->
    <section class="glass-panel toolbar-section">
      <div class="toolbar-row-top">
        <div class="search-box-wrapper">
          <span class="search-icon">🔍</span>
          <input type="text" id="searchInput" class="search-input" placeholder="ค้นหาชื่อสินค้า, หมวดหมู่, หรือผู้จัดจำหน่าย..." autocomplete="off">
        </div>

        <button id="btnOpenAddModal" class="btn btn-primary">
          <span>➕</span> เพิ่มสินค้าใหม่ (Add Product)
        </button>
      </div>

      <div class="filters-wrapper">
        <select id="filterCategory" class="custom-select">
          <option value="">ทุกหมวดหมู่ (All Categories)</option>
        </select>

        <select id="filterSupplier" class="custom-select">
          <option value="">ผู้จำหน่ายทั้งหมด (All Suppliers)</option>
        </select>

        <select id="filterSort" class="custom-select">
          <option value="ProductID:DESC">รหัสสินค้า: ล่าสุดก่อน</option>
          <option value="ProductID:ASC">รหัสสินค้า: แรกสุดก่อน</option>
          <option value="UnitPrice:ASC">ราคา: น้อยไปมาก</option>
          <option value="UnitPrice:DESC">ราคา: มากไปน้อย</option>
          <option value="ProductName:ASC">ชื่อสินค้า: A-Z</option>
        </select>
      </div>
    </section>

    <!-- Products Table Section -->
    <main class="glass-panel" style="overflow: hidden;">
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th style="width: 70px;">ID</th>
              <th>ชื่อสินค้า (Product Name)</th>
              <th>ขนาดบรรจุ (Unit)</th>
              <th>หมวดหมู่ (Category)</th>
              <th>ผู้จัดจำหน่าย (Supplier)</th>
              <th>ราคาต่อหน่วย (Price)</th>
              <th style="width: 110px; text-align: center;">จัดการ</th>
            </tr>
          </thead>
          <tbody id="productsTableBody">
            <!-- Populated via JavaScript -->
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo">
          กำลังโหลดข้อมูล...
        </div>
        <div class="pagination-controls" id="paginationControls">
          <!-- Populated via JavaScript -->
        </div>
      </div>
    </main>
  </div>

  <!-- Modal: Add / Edit Product -->
  <div id="productModal" class="modal-overlay">
    <div class="modal-card">
      <div class="modal-header">
        <h2 id="modalProductTitle" class="modal-title">เพิ่มสินค้าใหม่</h2>
        <button id="btnCloseModal" class="modal-close-btn">&times;</button>
      </div>
      
      <form id="productForm">
        <div class="modal-body">
          <input type="hidden" id="prodId">

          <div class="form-grid">
            <!-- Product Name -->
            <div class="form-group full-width">
              <label class="form-label" for="prodName">ชื่อสินค้า (Product Name) <span class="required">*</span></label>
              <input type="text" id="prodName" class="form-control" placeholder="เช่น Chai, Chang, Tofu" required>
              <span id="errProdName" class="form-error-msg"></span>
            </div>

            <!-- Category -->
            <div class="form-group">
              <label class="form-label" for="prodCategory">หมวดหมู่ (Category)</label>
              <select id="prodCategory" class="form-control">
                <option value="">-- เลือกหมวดหมู่ --</option>
              </select>
              <span id="errProdCategory" class="form-error-msg"></span>
            </div>

            <!-- Supplier -->
            <div class="form-group">
              <label class="form-label" for="prodSupplier">ผู้จัดจำหน่าย (Supplier)</label>
              <select id="prodSupplier" class="form-control">
                <option value="">-- เลือกผู้จำหน่าย --</option>
              </select>
              <span id="errProdSupplier" class="form-error-msg"></span>
            </div>

            <!-- Quantity Per Unit -->
            <div class="form-group full-width">
              <label class="form-label" for="prodQuantityPerUnit">ขนาดบรรจุต่อหน่วย (Unit / Packaging)</label>
              <input type="text" id="prodQuantityPerUnit" class="form-control" placeholder="เช่น 24 - 12 oz bottles, 10 boxes x 20 bags">
              <span id="errProdQuantityPerUnit" class="form-error-msg"></span>
            </div>

            <!-- Unit Price -->
            <div class="form-group full-width">
              <label class="form-label" for="prodUnitPrice">ราคาต่อหน่วย ($ Price) <span class="required">*</span></label>
              <input type="number" id="prodUnitPrice" class="form-control" step="0.01" min="0" placeholder="0.00" required>
              <span id="errProdUnitPrice" class="form-error-msg"></span>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" id="btnCancelModal" class="btn btn-secondary">ยกเลิก (Cancel)</button>
          <button type="submit" id="btnSaveProduct" class="btn btn-primary">บันทึกข้อมูล (Save)</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Delete Confirmation -->
  <div id="deleteModal" class="modal-overlay">
    <div class="modal-card" style="max-width: 460px;">
      <div class="modal-header">
        <h2 class="modal-title" style="color: var(--danger);">⚠️ ยืนยันการลบสินค้า</h2>
        <button id="btnCloseDeleteModal" class="modal-close-btn">&times;</button>
      </div>
      <div class="modal-body" style="text-align: center; padding: 2rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🗑️</div>
        <p style="font-size: 1.05rem; margin-bottom: 0.5rem;">คุณแน่ใจหรือไม่ว่าต้องการลบสินค้านี้?</p>
        <p style="font-weight: 700; color: #FFFFFF; font-size: 1.15rem;" id="deleteProductName">-</p>
        <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.75rem;">การดำเนินการนี้ไม่สามารถยกเลิกได้ และข้อมูลจะถูกลบออกจากฐานข้อมูลอย่างถาวร</p>
      </div>
      <div class="modal-footer" style="justify-content: center;">
        <button type="button" id="btnCancelDelete" class="btn btn-secondary">ยกเลิก</button>
        <button type="button" id="btnConfirmDelete" class="btn btn-danger">ยืนยันลบสินค้า</button>
      </div>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div id="toastContainer" class="toast-container"></div>

  <!-- Scripts -->
  <script src="assets/js/toast.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
