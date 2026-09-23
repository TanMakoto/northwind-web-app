<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($db);
        break;
    case 'POST':
        handlePost($db);
        break;
    case 'PUT':
        handlePut($db);
        break;
    case 'DELETE':
        handleDelete($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
        break;
}

/**
 * Handle GET Requests (List, Single Product, Filter, Search, Stats)
 */
function handleGet($db) {
    try {
        // 1. Single Product by ID
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $id = intval($_GET['id']);
            $query = "SELECT p.*, c.CategoryName, s.CompanyName as SupplierName 
                      FROM Products p 
                      LEFT JOIN Categories c ON p.CategoryID = c.CategoryID 
                      LEFT JOIN Suppliers s ON p.SupplierID = s.SupplierID 
                      WHERE p.ProductID = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $product = $stmt->fetch();

            if ($product) {
                echo json_encode(["success" => true, "data" => $product], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Product with ID #{$id} not found."]);
            }
            return;
        }

        // 2. Dashboard Statistics
        if (isset($_GET['stats']) && $_GET['stats'] === 'true') {
            $statsQuery = "SELECT 
                COUNT(*) AS total_products,
                COALESCE(SUM(UnitsInStock), 0) AS total_units_stock,
                COALESCE(SUM(UnitPrice * UnitsInStock), 0) AS total_inventory_value,
                SUM(CASE WHEN UnitsInStock = 0 THEN 1 ELSE 0 END) AS out_of_stock_count,
                SUM(CASE WHEN UnitsInStock > 0 AND UnitsInStock <= ReorderLevel THEN 1 ELSE 0 END) AS low_stock_count,
                SUM(CASE WHEN Discontinued = 1 THEN 1 ELSE 0 END) AS discontinued_count
            FROM Products";
            $statsStmt = $db->query($statsQuery);
            $stats = $statsStmt->fetch();

            echo json_encode(["success" => true, "data" => $stats], JSON_UNESCAPED_UNICODE);
            return;
        }

        // 3. List Products with Filter, Search, Sort & Pagination
        $search = trim($_GET['search'] ?? '');
        $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? intval($_GET['category_id']) : null;
        $supplierId = isset($_GET['supplier_id']) && $_GET['supplier_id'] !== '' ? intval($_GET['supplier_id']) : null;
        $status = $_GET['status'] ?? 'all';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $sortBy = $_GET['sort_by'] ?? 'ProductID';
        $order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $allowedSortCols = [
            'ProductID' => 'p.ProductID',
            'ProductName' => 'p.ProductName',
            'UnitPrice' => 'p.UnitPrice',
            'UnitsInStock' => 'p.UnitsInStock',
            'CategoryName' => 'c.CategoryName',
            'SupplierName' => 's.CompanyName'
        ];
        $sortColumn = $allowedSortCols[$sortBy] ?? 'p.ProductID';

        $conditions = ["1=1"];
        $params = [];

        if (!empty($search)) {
            $conditions[] = "(p.ProductName LIKE :search OR c.CategoryName LIKE :search OR s.CompanyName LIKE :search OR p.QuantityPerUnit LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if ($categoryId !== null) {
            $conditions[] = "p.CategoryID = :catId";
            $params[':catId'] = $categoryId;
        }

        if ($supplierId !== null) {
            $conditions[] = "p.SupplierID = :supId";
            $params[':supId'] = $supplierId;
        }

        if ($status === 'in_stock') {
            $conditions[] = "p.UnitsInStock > 0 AND p.Discontinued = 0";
        } elseif ($status === 'out_of_stock') {
            $conditions[] = "p.UnitsInStock = 0";
        } elseif ($status === 'low_stock') {
            $conditions[] = "p.UnitsInStock > 0 AND p.UnitsInStock <= p.ReorderLevel";
        } elseif ($status === 'discontinued') {
            $conditions[] = "p.Discontinued = 1";
        }

        $whereClause = implode(" AND ", $conditions);

        // Count total matching records
        $countQuery = "SELECT COUNT(*) as total 
                       FROM Products p 
                       LEFT JOIN Categories c ON p.CategoryID = c.CategoryID 
                       LEFT JOIN Suppliers s ON p.SupplierID = s.SupplierID 
                       WHERE {$whereClause}";
        $countStmt = $db->prepare($countQuery);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $totalRecords = (int) $countStmt->fetch()['total'];
        $totalPages = ceil($totalRecords / $limit);

        // Fetch paginated data
        $dataQuery = "SELECT p.*, c.CategoryName, s.CompanyName as SupplierName 
                      FROM Products p 
                      LEFT JOIN Categories c ON p.CategoryID = c.CategoryID 
                      LEFT JOIN Suppliers s ON p.SupplierID = s.SupplierID 
                      WHERE {$whereClause} 
                      ORDER BY {$sortColumn} {$order} 
                      LIMIT :limit OFFSET :offset";
        $dataStmt = $db->prepare($dataQuery);
        foreach ($params as $key => $val) {
            $dataStmt->bindValue($key, $val);
        }
        $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();
        $products = $dataStmt->fetchAll();

        echo json_encode([
            "success" => true,
            "data" => $products,
            "pagination" => [
                "total_records" => $totalRecords,
                "current_page" => $page,
                "per_page" => $limit,
                "total_pages" => $totalPages,
                "has_next" => $page < $totalPages,
                "has_prev" => $page > 1
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database Query Error: " . $e->getMessage()]);
    }
}

/**
 * Handle POST Request (Create Product)
 */
function handlePost($db) {
    $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;

    // Validation
    $errors = validateProductData($data);
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Validation failed", "errors" => $errors]);
        return;
    }

    try {
        $query = "INSERT INTO Products (ProductName, SupplierID, CategoryID, QuantityPerUnit, UnitPrice, UnitsInStock, UnitsOnOrder, ReorderLevel, Discontinued) 
                  VALUES (:productName, :supplierId, :categoryId, :quantityPerUnit, :unitPrice, :unitsInStock, :unitsOnOrder, :reorderLevel, :discontinued)";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':productName', trim($data['ProductName']));
        $stmt->bindValue(':supplierId', !empty($data['SupplierID']) ? intval($data['SupplierID']) : null, PDO::PARAM_INT);
        $stmt->bindValue(':categoryId', !empty($data['CategoryID']) ? intval($data['CategoryID']) : null, PDO::PARAM_INT);
        $stmt->bindValue(':quantityPerUnit', trim($data['QuantityPerUnit'] ?? ''));
        $stmt->bindValue(':unitPrice', floatval($data['UnitPrice']));
        $stmt->bindValue(':unitsInStock', intval($data['UnitsInStock'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':unitsOnOrder', intval($data['UnitsOnOrder'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':reorderLevel', intval($data['ReorderLevel'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':discontinued', !empty($data['Discontinued']) ? 1 : 0, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $newId = $db->lastInsertId();
            http_response_code(201);
            echo json_encode([
                "success" => true,
                "message" => "เพิ่มสินค้า '{$data['ProductName']}' สำเร็จเรียบร้อยแล้ว!",
                "data" => ["ProductID" => $newId]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to insert product."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database Insert Error: " . $e->getMessage()]);
    }
}

/**
 * Handle PUT Request (Update Product)
 */
function handlePut($db) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['ProductID']) || !is_numeric($data['ProductID'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ProductID is required for update."]);
        return;
    }

    $productId = intval($data['ProductID']);

    // Validation
    $errors = validateProductData($data);
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Validation failed", "errors" => $errors]);
        return;
    }

    try {
        // Check if exists
        $checkStmt = $db->prepare("SELECT ProductID FROM Products WHERE ProductID = :id");
        $checkStmt->execute([':id' => $productId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Product #{$productId} not found."]);
            return;
        }

        $query = "UPDATE Products SET 
                    ProductName = :productName,
                    SupplierID = :supplierId,
                    CategoryID = :categoryId,
                    QuantityPerUnit = :quantityPerUnit,
                    UnitPrice = :unitPrice,
                    UnitsInStock = :unitsInStock,
                    UnitsOnOrder = :unitsOnOrder,
                    ReorderLevel = :reorderLevel,
                    Discontinued = :discontinued
                  WHERE ProductID = :productId";

        $stmt = $db->prepare($query);
        $stmt->bindValue(':productName', trim($data['ProductName']));
        $stmt->bindValue(':supplierId', !empty($data['SupplierID']) ? intval($data['SupplierID']) : null, PDO::PARAM_INT);
        $stmt->bindValue(':categoryId', !empty($data['CategoryID']) ? intval($data['CategoryID']) : null, PDO::PARAM_INT);
        $stmt->bindValue(':quantityPerUnit', trim($data['QuantityPerUnit'] ?? ''));
        $stmt->bindValue(':unitPrice', floatval($data['UnitPrice']));
        $stmt->bindValue(':unitsInStock', intval($data['UnitsInStock'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':unitsOnOrder', intval($data['UnitsOnOrder'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':reorderLevel', intval($data['ReorderLevel'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':discontinued', !empty($data['Discontinued']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':productId', $productId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "อัปเดตข้อมูลสินค้า '{$data['ProductName']}' เรียบร้อยแล้ว!"
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to update product."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database Update Error: " . $e->getMessage()]);
    }
}

/**
 * Handle DELETE Request (Delete Product)
 */
function handleDelete($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = isset($_GET['id']) ? $_GET['id'] : ($data['ProductID'] ?? null);

    if (empty($id) || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Valid ProductID is required for deletion."]);
        return;
    }

    $productId = intval($id);

    try {
        // Fetch name first for informative response
        $fetchStmt = $db->prepare("SELECT ProductName FROM Products WHERE ProductID = :id");
        $fetchStmt->execute([':id' => $productId]);
        $product = $fetchStmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Product with ID #{$productId} does not exist."]);
            return;
        }

        $query = "DELETE FROM Products WHERE ProductID = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $productId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "ลบสินค้า '{$product['ProductName']}' (ID: #{$productId}) สำเร็จแล้ว!"
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to delete product."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database Delete Error: " . $e->getMessage()]);
    }
}

/**
 * Validate Product Input Data
 */
function validateProductData($data) {
    $errors = [];

    if (empty($data['ProductName']) || trim($data['ProductName']) === '') {
        $errors['ProductName'] = 'กรุณาระบุชื่อสินค้า (Product Name)';
    } elseif (mb_strlen(trim($data['ProductName'])) < 2) {
        $errors['ProductName'] = 'ชื่อสินค้าต้องมีความยาวอย่างน้อย 2 ตัวอักษร';
    }

    if (!isset($data['UnitPrice']) || trim((string)$data['UnitPrice']) === '') {
        $errors['UnitPrice'] = 'กรุณาระบุราคาสินค้า (Unit Price)';
    } elseif (!is_numeric($data['UnitPrice']) || floatval($data['UnitPrice']) < 0) {
        $errors['UnitPrice'] = 'ราคาสินค้าต้องเป็นตัวเลขและมากกว่าหรือเท่ากับ 0';
    }

    if (isset($data['UnitsInStock']) && trim((string)$data['UnitsInStock']) !== '') {
        if (!is_numeric($data['UnitsInStock']) || intval($data['UnitsInStock']) < 0) {
            $errors['UnitsInStock'] = 'จำนวนสินค้าคงคลังต้องเป็นจำนวนเต็มบวก';
        }
    }

    if (isset($data['ReorderLevel']) && trim((string)$data['ReorderLevel']) !== '') {
        if (!is_numeric($data['ReorderLevel']) || intval($data['ReorderLevel']) < 0) {
            $errors['ReorderLevel'] = 'จุดสั่งซื้อซ้ำต้องเป็นจำนวนเต็มบวก';
        }
    }

    return $errors;
}
