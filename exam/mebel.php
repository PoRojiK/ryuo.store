-- Создание базы данных
CREATE DATABASE FurnitureFirm;
USE FurnitureFirm;

-- Создание таблицы Мастера (Masters)
CREATE TABLE Masters (
    MasterID INT PRIMARY KEY IDENTITY(1,1),
    MasterName NVARCHAR(100) NOT NULL,
    Specialization NVARCHAR(100),
    Experience INT,
    ContactPhone NVARCHAR(20)
);

-- Создание таблицы Клиенты (Clients)
CREATE TABLE Clients (
    ClientID INT PRIMARY KEY IDENTITY(1,1),
    ClientName NVARCHAR(100) NOT NULL,
    ContactPerson NVARCHAR(100),
    ContactPhone NVARCHAR(20),
    Email NVARCHAR(100),
    Address NVARCHAR(200)
);

-- Создание таблицы Изделия (Products)
CREATE TABLE Products (
    ProductID INT PRIMARY KEY IDENTITY(1,1),
    ProductName NVARCHAR(100) NOT NULL,
    MasterID INT,
    CostPrice DECIMAL(10,2),
    Description NVARCHAR(MAX),
    Category NVARCHAR(50),
    FOREIGN KEY (MasterID) REFERENCES Masters(MasterID)
);

-- Создание таблицы Накладные (Invoices)
CREATE TABLE Invoices (
    InvoiceID INT PRIMARY KEY IDENTITY(1,1),
    ProductID INT,
    ClientID INT,
    SalePrice DECIMAL(10,2),
    SaleDate DATE,
    Quantity INT DEFAULT 1,
    Discount DECIMAL(5,2) DEFAULT 0,
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID),
    FOREIGN KEY (ClientID) REFERENCES Clients(ClientID)
);

-- Заполнение таблицы Мастера
INSERT INTO Masters (MasterName, Specialization, Experience, ContactPhone) VALUES
('Иван Петров', 'Столярное дело', 15, '+7(911)123-4567'),
('Анна Смирнова', 'Мягкая мебель', 10, '+7(912)234-5678'),
('Сергей Козлов', 'Корпусная мебель', 20, '+7(913)345-6789'),
('Елена Новикова', 'Дизайнерская мебель', 12, '+7(914)456-7890');

-- Заполнение таблицы Клиенты
INSERT INTO Clients (ClientName, ContactPerson, ContactPhone, Email, Address) VALUES
('ООО "Уютный Дом"', 'Михаил Иванов', '+7(495)111-2233', 'uytdom@mail.ru', 'Москва, ул. Строителей, 25'),
('ИП Петров', 'Петр Петров', '+7(812)222-3344', 'petrov_ip@gmail.com', 'Санкт-Петербург, пр. Науки, 30'),
('Мебельный Центр "Комфорт"', 'Анна Сергеева', '+7(343)333-4455', 'comfort@yandex.ru', 'Екатеринбург, ул. Победы, 100'),
('Частный клиент Елена', 'Елена Попова', '+7(917)444-5566', 'elena_popova@mail.ru', 'Казань, ул. Дружбы, 15');

-- Заполнение таблицы Изделия
INSERT INTO Products (ProductName, MasterID, CostPrice, Description, Category) VALUES
('Диван "Классика"', 2, 25000.00, 'Мягкий диван в классическом стиле', 'Мягкая мебель'),
('Кухонный гарнитур "Модерн"', 3, 50000.00, 'Современный кухонный гарнитур', 'Корпусная мебель'),
('Обеденный стол "Дубрава"', 1, 15000.00, 'Массивный стол из дубового массива', 'Деревянная мебель'),
('Кресло-качалка "Уют"', 4, 20000.00, 'Эргономичное кресло-качалка', 'Дизайнерская мебель');

-- Заполнение таблицы Накладные
INSERT INTO Invoices (ProductID, ClientID, SalePrice, SaleDate, Quantity, Discount) VALUES
(1, 1, 28000.00, '2024-01-15', 2, 0),
(2, 3, 55000.00, '2024-02-20', 1, 5),
(3, 2, 17000.00, '2024-03-10', 1, 0),
(4, 4, 22000.00, '2024-04-05', 1, 10),
(1, 2, 26000.00, '2024-04-15', 1, 0),
(2, 1, 52000.00, '2024-05-01', 1, 0);

-- Проверочный запрос
SELECT 
    p.ProductName,
    m.MasterName,
    COUNT(i.InvoiceID) AS TotalSales,
    SUM(i.SalePrice * i.Quantity) AS TotalRevenue
FROM 
    Products p
JOIN 
    Masters m ON p.MasterID = m.MasterID
LEFT JOIN 
    Invoices i ON p.ProductID = i.ProductID
GROUP BY 
    p.ProductName, 
    m.MasterName
ORDER BY 
    TotalSales DESC;