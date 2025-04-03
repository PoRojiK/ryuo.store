-- Create database
CREATE DATABASE WholesaleStoreManagement;
GO

-- Use the created database
USE WholesaleStoreManagement;
GO

-- Create WholesaleStoreProducts table
CREATE TABLE WholesaleStoreProducts (
    WholesaleStoreProductID INT PRIMARY KEY IDENTITY(1,1),
    ProductName NVARCHAR(200) NOT NULL,
    ProductUnit NVARCHAR(50) NOT NULL,
    PurchasePrice DECIMAL(10,2) NOT NULL,
    SellingPrice DECIMAL(10,2) NOT NULL
);

-- Create WholesaleStoreSellers table
CREATE TABLE WholesaleStoreSellers (
    WholesaleStoreSellerID INT PRIMARY KEY IDENTITY(1,1),
    SellerName NVARCHAR(200) NOT NULL,
    ContactInfo NVARCHAR(200)
);

-- Create WholesaleStoreSales table
CREATE TABLE WholesaleStoreSales (
    WholesaleStoreSaleID INT PRIMARY KEY IDENTITY(1,1),
    WholesaleStoreProductID INT FOREIGN KEY REFERENCES WholesaleStoreProducts(WholesaleStoreProductID),
    WholesaleStoreSellerID INT FOREIGN KEY REFERENCES WholesaleStoreSellers(WholesaleStoreSellerID),
    SaleDate DATE NOT NULL,
    QuantitySold DECIMAL(10,2) NOT NULL,
    CommissionPercentage DECIMAL(5,2) NOT NULL,
    CommissionAmount DECIMAL(10,2) NOT NULL
);

-- Insert sample data into WholesaleStoreProducts table
INSERT INTO WholesaleStoreProducts (ProductName, ProductUnit, PurchasePrice, SellingPrice) VALUES
('Кофемашина DELONGHI', 'шт', 25000.00, 32000.00),
('Холодильник SAMSUNG', 'шт', 35000.00, 45000.00),
('Ноутбук LENOVO', 'шт', 40000.00, 55000.00),
('Телевизор LG', 'шт', 30000.00, 42000.00),
('Смартфон APPLE', 'шт', 50000.00, 65000.00);

-- Insert sample data into WholesaleStoreSellers table
INSERT INTO WholesaleStoreSellers (SellerName, ContactInfo) VALUES
('Иванов Петр Сергеевич', 'ivan@example.com'),
('Петрова Анна Михайловна', 'anna@example.com'),
('Смирнов Олег Викторович', 'oleg@example.com'),
('Кузнецова Елена Игоревна', 'elena@example.com'),
('Попов Дмитрий Андреевич', 'dmitry@example.com');

-- Insert sample data into WholesaleStoreSales table
INSERT INTO WholesaleStoreSales (
    WholesaleStoreProductID, 
    WholesaleStoreSellerID, 
    SaleDate, 
    QuantitySold, 
    CommissionPercentage,
    CommissionAmount
) VALUES
(1, 1, '2025-03-27', 5, 10, 16000.00),
(2, 2, '2025-03-28', 3, 10, 13500.00),
(3, 3, '2025-03-29', 4, 10, 22000.00),
(4, 4, '2025-03-30', 6, 10, 25200.00),
(5, 5, '2025-03-31', 2, 10, 13000.00);

-- Create a stored procedure to search products by name
CREATE PROCEDURE SearchProductsByName
    @ProductNameSearch NVARCHAR(200)
AS
BEGIN
    SELECT 
        WholesaleStoreProductID,
        ProductName,
        ProductUnit,
        PurchasePrice,
        SellingPrice
    FROM 
        WholesaleStoreProducts
    WHERE 
        ProductName LIKE '%' + @ProductNameSearch + '%'
    ORDER BY 
        ProductName;
END;
GO

-- Example of how to execute the stored procedure
-- EXEC SearchProductsByName @ProductNameSearch = 'SAMSUNG';