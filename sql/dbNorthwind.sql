-- Drop child tables first, then parent tables
DROP TABLE IF EXISTS `Products`;
DROP TABLE IF EXISTS `Categories`;
DROP TABLE IF EXISTS `Suppliers`;

-- --------------------------------------------------------
-- Table: Categories
-- --------------------------------------------------------
CREATE TABLE `Categories` (
  `CategoryID` INT AUTO_INCREMENT PRIMARY KEY,
  `CategoryName` VARCHAR(50) NOT NULL,
  `Description` TEXT DEFAULT NULL,
  `Picture` VARCHAR(255) DEFAULT NULL
);

INSERT INTO `Categories` (`CategoryID`, `CategoryName`, `Description`) VALUES
(1, 'Beverages', 'Soft drinks, coffees, teas, beers, and ales'),
(2, 'Condiments', 'Sweet and savory sauces, relishes, spreads, and seasonings'),
(3, 'Confections', 'Desserts, candies, and sweet breads'),
(4, 'Dairy Products', 'Cheeses, milk, butter, and yogurts'),
(5, 'Grains/Cereals', 'Breads, crackers, pasta, and cereal'),
(6, 'Meat/Poultry', 'Prepared meats, chicken, and beef'),
(7, 'Produce', 'Dried fruit and bean curd, fresh vegetables'),
(8, 'Seafood', 'Seaweed and fish, shellfish, and caviar');

-- --------------------------------------------------------
-- Table: Suppliers
-- --------------------------------------------------------
CREATE TABLE `Suppliers` (
  `SupplierID` INT AUTO_INCREMENT PRIMARY KEY,
  `CompanyName` VARCHAR(100) NOT NULL,
  `ContactName` VARCHAR(100) DEFAULT NULL,
  `ContactTitle` VARCHAR(50) DEFAULT NULL,
  `Address` VARCHAR(255) DEFAULT NULL,
  `City` VARCHAR(50) DEFAULT NULL,
  `Region` VARCHAR(50) DEFAULT NULL,
  `PostalCode` VARCHAR(20) DEFAULT NULL,
  `Country` VARCHAR(50) DEFAULT NULL,
  `Phone` VARCHAR(30) DEFAULT NULL,
  `HomePage` VARCHAR(255) DEFAULT NULL
);

INSERT INTO `Suppliers` (`SupplierID`, `CompanyName`, `ContactName`, `ContactTitle`, `City`, `Country`, `Phone`) VALUES
(1, 'Exotic Liquids', 'Charlotte Cooper', 'Purchasing Manager', 'London', 'UK', '(171) 555-2222'),
(2, 'New Orleans Cajun Delights', 'Shelley Burke', 'Order Administrator', 'New Orleans', 'USA', '(100) 555-4822'),
(3, 'Grandma Kelly\'s Homestead', 'Regina Murphy', 'Sales Representative', 'Ann Arbor', 'USA', '(313) 555-5735'),
(4, 'Tokyo Traders', 'Yoshi Nagase', 'Marketing Manager', 'Tokyo', 'Japan', '(03) 3555-5011'),
(5, 'Cooperativa de Quesos \'Las Cabras\'', 'Antonio del Valle Saavedra', 'Export Administrator', 'Oviedo', 'Spain', '(98) 598 76 54'),
(6, 'Mayumi\'s', 'Mayumi Ohno', 'Marketing Representative', 'Osaka', 'Japan', '(06) 431-7877'),
(7, 'Pavlova, Ltd.', 'Ian Devling', 'Marketing Manager', 'Melbourne', 'Australia', '(03) 444-2343'),
(8, 'Specialty Biscuits, Ltd.', 'Peter Wilson', 'Sales Representative', 'Manchester', 'UK', '(161) 555-4448'),
(9, 'PB Knäckebröd AB', 'Lars Peterson', 'Sales Agent', 'Göteborg', 'Sweden', '031-987 65 43'),
(10, 'Refrescos Americanas LTDA', 'Carlos Diaz', 'Marketing Manager', 'São Paulo', 'Brazil', '(11) 555 4640');

-- --------------------------------------------------------
-- Table: Products
-- --------------------------------------------------------
CREATE TABLE `Products` (
  `ProductID` INT AUTO_INCREMENT PRIMARY KEY,
  `ProductName` VARCHAR(100) NOT NULL,
  `SupplierID` INT DEFAULT NULL,
  `CategoryID` INT DEFAULT NULL,
  `QuantityPerUnit` VARCHAR(50) DEFAULT NULL,
  `UnitPrice` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `UnitsInStock` SMALLINT NOT NULL DEFAULT 0,
  `UnitsOnOrder` SMALLINT NOT NULL DEFAULT 0,
  `ReorderLevel` SMALLINT NOT NULL DEFAULT 0,
  `Discontinued` TINYINT(1) NOT NULL DEFAULT 0,
  `CreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_products_categories` FOREIGN KEY (`CategoryID`) REFERENCES `Categories` (`CategoryID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_products_suppliers` FOREIGN KEY (`SupplierID`) REFERENCES `Suppliers` (`SupplierID`) ON DELETE SET NULL ON UPDATE CASCADE
);

INSERT INTO `Products` (`ProductID`, `ProductName`, `SupplierID`, `CategoryID`, `QuantityPerUnit`, `UnitPrice`, `UnitsInStock`, `UnitsOnOrder`, `ReorderLevel`, `Discontinued`) VALUES
(1, 'Chai', 1, 1, '10 boxes x 20 bags', 18.00, 39, 0, 10, 0),
(2, 'Chang', 1, 1, '24 - 12 oz bottles', 19.00, 17, 40, 25, 0),
(3, 'Aniseed Syrup', 1, 2, '12 - 550 ml bottles', 10.00, 13, 70, 25, 0),
(4, 'Chef Anton\'s Cajun Seasoning', 2, 2, '48 - 6 oz jars', 22.00, 53, 0, 0, 0),
(5, 'Chef Anton\'s Gumbo Mix', 2, 2, '36 boxes', 21.35, 0, 0, 0, 1),
(6, 'Grandma\'s Boysenberry Spread', 3, 2, '12 - 8 oz jars', 25.00, 120, 0, 25, 0),
(7, 'Uncle Bob\'s Organic Dried Pears', 3, 7, '12 - 1 lb pkgs.', 30.00, 15, 0, 10, 0),
(8, 'Northwoods Cranberry Sauce', 3, 2, '12 - 12 oz jars', 40.00, 6, 0, 0, 0),
(9, 'Mishi Kobe Niku', 4, 6, '18 - 500 g pkgs.', 97.00, 29, 0, 0, 1),
(10, 'Ikura', 4, 8, '12 - 200 ml jars', 31.00, 31, 0, 0, 0),
(11, 'Queso Cabrales', 5, 4, '1 kg pkg.', 21.00, 22, 30, 30, 0),
(12, 'Queso Manchego La Pastora', 5, 4, '10 - 500 g pkgs.', 38.00, 86, 0, 0, 0),
(13, 'Konbu', 6, 8, '2 kg box', 6.00, 24, 0, 5, 0),
(14, 'Tofu', 6, 7, '40 - 100 g pkgs.', 23.25, 35, 0, 0, 0),
(15, 'Genen Shouyu', 6, 2, '24 - 250 ml bottles', 15.50, 39, 0, 5, 0),
(16, 'Pavlova', 7, 3, '32 - 500 g boxes', 17.45, 29, 0, 10, 0),
(17, 'Alice Mutton', 7, 6, '20 - 1 kg tins', 39.00, 0, 0, 0, 1),
(18, 'Carnarvon Tigers', 7, 8, '16 kg pkg.', 62.50, 42, 0, 0, 0),
(19, 'Teatime Chocolate Biscuits', 8, 3, '10 boxes x 12 pieces', 9.20, 25, 0, 5, 0),
(20, 'Sir Rodney\'s Marmalade', 8, 3, '30 gift boxes', 81.00, 40, 0, 0, 0),
(21, 'Sir Rodney\'s Scones', 8, 3, '24 pkgs. x 4 pieces', 10.00, 3, 40, 5, 0),
(22, 'Gustaf\'s Knäckebröd', 9, 5, '24 - 500 g pkgs.', 21.00, 104, 0, 25, 0),
(23, 'Tunnbröd', 9, 5, '12 - 250 g pkgs.', 9.00, 61, 0, 25, 0),
(24, 'Guaraná Fantástica', 10, 1, '12 - 355 ml cans', 4.50, 20, 0, 0, 1),
(25, 'NuNuCa Nuß-Nougat-Creme', 10, 3, '20 - 450 g glasses', 14.00, 76, 0, 30, 0);
