# Northwind Products Management Web Application 📦

A full-stack modern web application built with **PHP 8.x**, **MySQL (Northwind Schema)**, and **Vanilla Modern CSS & JavaScript**, designed for seamless deployment on **Railway PaaS Cloud**.

---

## 🌟 Key Features
- **Dashboard & Inventory Analytics**: Real-time summary cards for total products, total valuation, low stock, and out-of-stock items.
- **Full CRUD Support**: Create, Read, Update, and Delete operations for products via structured RESTful PHP APIs.
- **Smart Filtering & Live Search**: Debounced instant search, filtering by Category, Supplier, Stock status, and multi-field sorting.
- **Data Validation & Feedback**: Rich client-side and server-side validation with animated Toast notification alerts.
- **Glassmorphism UI**: Beautiful modern dark-theme responsive design.
- **Cloud-Ready**: Native configuration for Railway (`Dockerfile`, `nixpacks.toml`, auto-detecting Railway MySQL environment variables).

---

## 📂 Project Structure
```text
├── api/
│   ├── categories.php       # Categories REST endpoint
│   ├── products.php         # Products CRUD + Search + Stats REST endpoint
│   └── suppliers.php        # Suppliers REST endpoint
├── assets/
│   ├── css/
│   │   └── style.css        # Glassmorphic responsive design system
│   └── js/
│       ├── app.js           # Client-side SPA controller & event binding
│       └── toast.js         # Animated toast alert notification library
├── config/
│   └── database.php         # PDO connector supporting Local and Railway PaaS
├── sql/
│   └── dbNorthwind.sql      # Database schema & sample seed data
├── .env.example             # Local development environment sample
├── .htaccess                # Apache routing & CORS headers
├── DEPLOYMENT_GUIDE.md      # Detailed step-by-step Railway deployment report
├── Dockerfile               # Production container image configuration
├── index.php                # Main dashboard single-page interface
├── nixpacks.toml            # Railway native build configuration
└── README.md                # Project documentation
```

---

## 🚀 Local Development Setup

1. **Clone or Extract Project Files**:
   Place the project folder in your local web server root (e.g. `htdocs` in XAMPP or any local directory).

2. **Database Import**:
   - Open MySQL (e.g., phpMyAdmin, HeidiSQL, or MySQL CLI).
   - Create a database named `dbnorthwind` (or any name of your choice).
   - Import `sql/dbNorthwind.sql`.

3. **Configure Environment (Optional)**:
   - Copy `.env.example` to `.env` and set your credentials if they differ from standard defaults:
     ```env
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_NAME=dbnorthwind
     DB_USER=root
     DB_PASS=
     ```

4. **Run Server**:
   You can run PHP's built-in web server:
   ```bash
   php -S 127.0.0.1:8000
   ```
   Then open `http://127.0.0.1:8000` in your browser.

---

## ☁️ Cloud Deployment on Railway

Please follow the complete step-by-step instructions in [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md).
