<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $query = "SELECT i_SupplierID AS SupplierID, c_SupplierName AS CompanyName, c_ContactName AS ContactName, c_City AS City, c_Country AS Country, c_Phone AS Phone 
                  FROM tb_suppliers 
                  ORDER BY c_SupplierName ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $suppliers = $stmt->fetchAll();

        echo json_encode([
            "success" => true,
            "data" => $suppliers
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to fetch suppliers: " . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
