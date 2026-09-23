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
            $query = "SELECT 
                        p.i_ProductID AS ProductID,
                        p.c_ProductName AS ProductName,
                        p.i_SupplierID AS SupplierID,
                        p.i_CategoryID AS CategoryID,
                        p.c_Unit AS QuantityPerUnit,
                        p.i_Price AS UnitPrice,
                        c.c_CategoryName AS CategoryName, 
                        s.c_SupplierName AS SupplierName 
                      FROM tb_products p 
                      LEFT JOIN tb_categories c ON p.i_CategoryID = c.i_CategoryID 
                      LEFT JOIN tb_suppliers s ON p.i_SupplierID = s.i_SupplierID 
                      WHERE p.i_ProductID = :id LIMIT 1";
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
                COALESCE(AVG(i_Price), 0) AS avg_price,
                COALESCE(SUM(i_Price), 0) AS total_inventory_value,
                COUNT(DISTINCT i_CategoryID) AS total_categories,
                COUNT(DISTINCT i_SupplierID) AS total_suppliers
            FROM tb_products";
            $statsStmt = $db->query($statsQuery);
            $stats = $statsStmt->fetch();

            echo json_encode(["success" => true, "data" => $stats], JSON_UNESCAPED_UNICODE);
            return;
        }

        // 3. List Products with Filter, Search, Sort & Pagination
        $search = trim($_GET['search'] ?? '');
        $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? intval($_GET['category_id']) : null;
        $supplierId = isset($_GET['supplier_id']) && $_GET['supplier_id'] !== '' ? intval($_GET['supplier_id']) : null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $sortBy = $_GET['sort_by'] ?? 'ProductID';
        $order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $allowedSortCols = [
            'ProductID' => 'p.i_ProductID',
            'ProductName' => 'p.c_ProductName',
            'UnitPrice' => 'p.i_Price',
            'CategoryName' => 'c.c_CategoryName',
            'SupplierName' => 's.c_SupplierName'
        ];
        $sortColumn = $allowedSortCols[$sortBy] ?? 'p.i_ProductID';

        $conditions = ["1=1"];
        $params = [];

        if (!empty($search)) {
            $conditions[] = "(p.c_ProductName LIKE :search1 OR c.c_CategoryName LIKE :search2 OR s.c_SupplierName LIKE :search3 OR p.c_Unit LIKE :search4)";
            $params[':search1'] = "%{$search}%";
            $params[':search2'] = "%{$search}%";
            $params[':search3'] = "%{$search}%";
            $params[':search4'] = "%{$search}%";
        }

        if ($categoryId !== null) {
            $conditions[] = "p.i_CategoryID = :catId";
            $params[':catId'] = $categoryId;
        }

        if ($supplierId !== null) {
            $conditions[] = "p.i_SupplierID = :supId";
            $params[':supId'] = $supplierId;
        }

        $whereClause = implode(" AND ", $conditions);

        // Count total matching records
        $countQuery = "SELECT COUNT(*) as total 
                       FROM tb_products p 
                       LEFT JOIN tb_categories c ON p.i_CategoryID = c.i_CategoryID 
                       LEFT JOIN tb_suppliers s ON p.i_SupplierID = s.i_SupplierID 
                       WHERE {$whereClause}";
        $countStmt = $db->prepare($countQuery);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $totalRecords = (int) $countStmt->fetch()['total'];
        $totalPages = ceil($totalRecords / $limit);

        // Fetch paginated data
        $dataQuery = "SELECT 
                        p.i_ProductID AS ProductID,
                        p.c_ProductName AS ProductName,
                        p.i_SupplierID AS SupplierID,
                        p.i_CategoryID AS CategoryID,
                        p.c_Unit AS QuantityPerUnit,
                        p.i_Price AS UnitPrice,
                        c.c_CategoryName AS CategoryName, 
                        s.c_SupplierName AS SupplierName 
                      FROM tb_products p 
                      LEFT JOIN tb_categories c ON p.i_CategoryID = c.i_CategoryID 
                      LEFT JOIN tb_suppliers s ON p.i_SupplierID = s.i_SupplierID 
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
        $query = "INSERT INTO tb_products (c_ProductName, i_SupplierID, i_CategoryID, c_Unit, i_Price) 
                  VALUES (:productName, :supplierId, :categoryId, :unit, :price)";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':productName', trim($data['ProductName']));
        $stmt->bindValue(':supplierId', !empty($data['SupplierID']) ? intval($data['SupplierID']) : null, PDO::PARAM_INT);
        $stmt->bindValue(':categoryId', !empty($data['CategoryID']) ? intval($data['CategoryID']) : null, PDO::PARAM_INT);
        $stmt->bindValue(':unit', trim($data['QuantityPerUnit'] ?? ''));
        $stmt->bindValue(':price', floatval($data['UnitPrice']));

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
        $checkStmt = $db->prepare("SELECT i_ProductID FROM tb_products WHERE i_ProductID = :id");
        $checkStmt->execute([':id' => $productId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Product #{$productId} not found."]);
            return;
        }

        $query = "UPDATE tb_products SET 
                    c_ProductName = :productName,
                    i_SupplierID = :supplierId,
                    i_CategoryID = :categoryId,
                    c_Unit = :unit,
                    i_Price = :price
                  WHERE i_ProductID = :productId";

        $stmt = $db->prepare($query);
        $stmt->bindValue(':productName', trim($data['ProductName']));
        $stmt->bindValue(':supplierId', !empty($data['SupplierID']) ? intval($data['SupplierID']) : null, PDO::PARAM_INT);
        $stmt->bindValue(':categoryId', !empty($data['CategoryID']) ? intval($data['CategoryID']) : null, PDO::PARAM_INT);
        $stmt->bindValue(':unit', trim($data['QuantityPerUnit'] ?? ''));
        $stmt->bindValue(':price', floatval($data['UnitPrice']));
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
        // Fetch name first
        $fetchStmt = $db->prepare("SELECT c_ProductName FROM tb_products WHERE i_ProductID = :id");
        $fetchStmt->execute([':id' => $productId]);
        $product = $fetchStmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Product with ID #{$productId} does not exist."]);
            return;
        }

        $query = "DELETE FROM tb_products WHERE i_ProductID = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $productId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "ลบสินค้า '{$product['c_ProductName']}' (ID: #{$productId}) สำเร็จแล้ว!"
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

    return $errors;
}
