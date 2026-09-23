# คู่มือขั้นตอนการ Deploy และเอกสารรายงานโครงการ (Process Documentation)
## Web Application Project: Development and Deployment of Web App with PHP and MySQL on Railway PaaS

---

## 1. ข้อมูลภาพรวมโครงการ (Project Overview)
- **ชื่อโครงการ**: Northwind Products Management Web Application
- **เทคโนโลยีฝั่ง Backend**: Modern PHP 8.x, PDO Prepared Statements, RESTful JSON API Architecture
- **เทคโนโลยีฝั่ง Frontend**: HTML5, Vanilla CSS3 (Glassmorphism Dark Theme), Vanilla JavaScript (Fetch API, Live Debounce Search, Modal Controller, Animated Toast Notifications)
- **ฐานข้อมูล**: MySQL (Northwind Database Schema: `Categories`, `Suppliers`, `Products`)
- **แพลตฟอร์ม Cloud PaaS**: [Railway.com](https://railway.com/)

---

## 2. ขั้นตอนการเตรียมฐานข้อมูลและการ Deploy บน Railway (Step-by-Step Deployment Guide)

### ขั้นตอนที่ 1: สร้าง Project และ MySQL Database บน Railway
1. ไปที่เว็บไซต์ [https://railway.com/](https://railway.com/) และเข้าสู่ระบบ (Login ด้วย GitHub หรือ Email)
2. คลิกปุ่ม **"New Project"** หรือ **"Create a New Project"**
3. เลือก **"Provision MySQL"** (หรือเลือก **"Database"** &rarr; **"Add MySQL"**)
4. รอระบบทำการสร้าง Database Service สักครู่จนขึ้นสถานะ **Active / Deployed**

---

### ขั้นตอนที่ 2: นำเข้าข้อมูล Northwind Database (`sql/dbNorthwind.sql`)
มี 2 วิธีที่สามารถเลือกทำได้สะดวก:

#### วิธีที่ 2.1: นำเข้าผ่านโปรแกรมจัดการฐานข้อมูล (เช่น DBeaver / TablePlus / HeidiSQL / Navicat)
1. ในหน้า Railway คลิกที่ Service **MySQL** &rarr; ไปที่แท็บ **"Connect"**
2. คัดลอกข้อมูลการเชื่อมต่อแบบ **Public Network / TCP Proxy**:
   - `Host`: (เช่น `roundhouse.proxy.rlwy.net`)
   - `Port`: (เช่น `12345`)
   - `User`: `root`
   - `Password`: (ตามที่ระบุใน Railway)
   - `Database`: `railway`
3. เปิดโปรแกรมจัดการฐานข้อมูล (DBeaver / TablePlus / HeidiSQL) แล้วสร้าง Connection ด้วยข้อมูลข้างต้น
4. เปิดไฟล์ `sql/dbNorthwind.sql` ในโปรแกรม แล้วกด **Execute / Run SQL Script** เพื่อสร้างตาราง `Categories`, `Suppliers`, `Products` และเพิ่มข้อมูลตัวอย่าง

#### วิธีที่ 2.2: นำเข้าผ่าน MySQL Command Line
```bash
mysql -h <MYSQLHOST> -u <MYSQLUSER> -p<MYSQLPASSWORD> -P <MYSQLPORT> <MYSQLDATABASE> < sql/dbNorthwind.sql
```

---

### ขั้นตอนที่ 3: Deploy เว็บแอปพลิเคชัน PHP บน Railway
1. อัปโหลด Source Code ทั้งหมดขึ้น GitHub Repository ของท่าน (หรือ Deploy ผ่าน Railway CLI / Drag & Drop)
2. ใน Project บน Railway กดคลิกที่ปุ่ม **"+ New"** (หรือ **"Add Service"**)
3. เลือก **"GitHub Repo"** แล้วเลือก Repository ที่เก็บ Source Code นี้
4. Railway จะทำการตรวจจับและสร้าง Build อัตโนมัติ (ผ่าน Dockerfile หรือ Nixpacks ที่เตรียมไว้ให้)

---

### ขั้นตอนที่ 4: เชื่อมโยง Environment Variables ระหว่าง Web App และ Database
1. คลิกที่ Service **Web Application** ที่เพิ่งสร้าง &rarr; ไปที่แท็บ **"Variables"**
2. กด **"Add Variable"** หรือ **"Add Reference"**:
   - เพิ่ม `MYSQL_URL` และเลือก Reference จาก MySQL Service
   - หรือเพิ่มตัวแปรแยก:
     - `MYSQLHOST` = `${{MySQL.MYSQLHOST}}`
     - `MYSQLPORT` = `${{MySQL.MYSQLPORT}}`
     - `MYSQLUSER` = `${{MySQL.MYSQLUSER}}`
     - `MYSQLPASSWORD` = `${{MySQL.MYSQLPASSWORD}}`
     - `MYSQLDATABASE` = `${{MySQL.MYSQLDATABASE}}`
3. กด **Deploy / Redeploy**

*(ระบบ `config/database.php` ของเราได้รับการเขียนให้ตรวจจับตัวแปรจาก Railway ทั้ง `MYSQL_URL`, `DATABASE_URL` และ `MYSQLHOST` โดยอัตโนมัติ ไม่ต้องแก้โค้ดเพิ่มเติม)*

---

### ขั้นตอนที่ 5: สร้าง Public Domain URL
1. ในหน้า Service Web Application &rarr; ไปที่แท็บ **"Settings"**
2. เลื่อนลงมาที่หัวข้อ **"Networking"** &rarr; **"Public Networking"**
3. คลิก **"Generate Domain"**
4. จะได้รับ URL เช่น `https://web-production-xxxx.up.railway.app`
5. ทดสอบเปิดผ่านเบราว์เซอร์ เพื่อเข้าใช้งาน Web Application

---

## 3. การทดสอบการทำงานของระบบ (Feature Verification)

| ลำดับ | ฟีเจอร์ที่ต้องทดสอบ | ขั้นตอนการทดสอบ | ผลการทำงานที่คาดหวัง |
| :--- | :--- | :--- | :--- |
| 1 | **ค้นหาข้อมูล (Search)** | พิมพ์คำค้นหาในช่อง Search เช่น "Chai" หรือ "Sauce" | รายการสินค้าถูกกรองแบบ Real-time ตามคำค้นหา |
| 2 | **กรองตามหมวดหมู่ / ผู้จัดจำหน่าย** | เลือก Dropdown Category หรือ Supplier | ตารางแสดงเฉพาะสินค้าในหมวดหมู่ที่เลือก |
| 3 | **เพิ่มสินค้า (Create / Add)** | กดปุ่ม `+ เพิ่มสินค้าใหม่` &rarr; กรอกข้อมูล &rarr; กดบันทึก | มี Toast แจ้งเตือนสำเร็จ, ข้อมูลแสดงในตารางทันที |
| 4 | **การแจ้งเตือน Validation** | ลองกดบันทึกโดยไม่กรอกชื่อ หรือใส่ราคาติดลบ | ระบบแสดงกรอบสีแดง, ข้อความเตือน และ Toast แจ้งเตือนข้อผิดพลาด |
| 5 | **แก้ไขข้อมูล (Update / Edit)** | กดปุ่มรูปดินสอ ✏️ ที่รายการสินค้า &rarr; แก้ไขราคา &rarr; บันทึก | ข้อมูลอัปเดต และแสดง Toast แจ้งผลสำเร็จ |
| 6 | **ลบข้อมูล (Delete)** | กดปุ่มรูปถังขยะ 🗑️ &rarr; กดยืนยันการลบใน Modal | สินค้าถูกลบออกจากฐานข้อมูล และแสดง Toast ยืนยัน |
| 7 | **สรุปสถิติ (Dashboard Stats)** | ตรวจสอบการ์ดสถิติ 4 ใบด้านบน | คำนวณจำนวนสินค้า, มูลค่าสต็อก, สินค้าใกล้หมด, สินค้าหมดสต็อกถูกต้อง |

---

## 4. โครงสร้าง REST API (API Endpoints Documentation)

### 1. Products API (`/api/products.php`)
- `GET /api/products.php?page=1&limit=10&search=&category_id=&supplier_id=&status=&sort_by=&order=`
  - ดึงรายการสินค้าพร้อมตัวกรองและการแบ่งหน้า (Pagination)
- `GET /api/products.php?id={id}`
  - ดึงข้อมูลสินค้ารายชิ้นตาม ProductID
- `GET /api/products.php?stats=true`
  - ดึงข้อมูลสถิติภาพรวมสำหรับ Dashboard
- `POST /api/products.php`
  - เพิ่มสินค้าใหม่ (Body: JSON)
- `PUT /api/products.php`
  - อัปเดตข้อมูลสินค้า (Body: JSON รวมถึง ProductID)
- `DELETE /api/products.php?id={id}`
  - ลบสินค้าออกจากระบบ

### 2. Categories API (`/api/categories.php`)
- `GET /api/categories.php`
  - ดึงรายการหมวดหมู่สินค้าทั้งหมด

### 3. Suppliers API (`/api/suppliers.php`)
- `GET /api/suppliers.php`
  - ดึงรายการผู้จัดจำหน่ายทั้งหมด

---

## 5. ข้อมูลสำหรับนำไปกรอกในฟอร์มส่งงาน (Submission Template)

- **Live Application URL**: `https://<YOUR_APP_SUBDOMAIN>.up.railway.app`
- **Source Code URL (GitHub / Google Drive)**: `https://github.com/<USERNAME>/<REPO_NAME>`
- **Process Documentation Link (Google Docs)**: คัดลอกเนื้อหาในเอกสารนี้ไปวางใน Google Docs พร้อมแนบภาพบันทึกหน้าจอ (Screenshots) ตามขั้นตอน
