-- Create database
CREATE DATABASE CarRental;
GO

-- Use the created database
USE CarRental;
GO

-- Create Clients table
CREATE TABLE Clients (
    ClientID INT PRIMARY KEY IDENTITY(1,1),
    FullName NVARCHAR(100) NOT NULL,
    PassportSeries NVARCHAR(10) NOT NULL,
    PassportNumber NVARCHAR(10) NOT NULL,
    ContactPhone NVARCHAR(20),
    Address NVARCHAR(200)
);

-- Create Cars table
CREATE TABLE Cars (
    CarID INT PRIMARY KEY IDENTITY(1,1),
    Brand NVARCHAR(50) NOT NULL,
    Model NVARCHAR(50) NOT NULL,
    Color NVARCHAR(30) NOT NULL,
    Year INT NOT NULL,
    InsuranceCost DECIMAL(10,2) NOT NULL,
    DailyRentalRate DECIMAL(10,2) NOT NULL
);

-- Create Rental table
CREATE TABLE Rental (
    RentalID INT PRIMARY KEY IDENTITY(1,1),
    ClientID INT FOREIGN KEY REFERENCES Clients(ClientID),
    CarID INT FOREIGN KEY REFERENCES Cars(CarID),
    RentalStartDate DATE NOT NULL,
    RentalEndDate DATE NOT NULL,
    TotalRentalDays INT NOT NULL,
    TotalRentalCost DECIMAL(10,2) NOT NULL
);

-- Insert sample data into Clients table
INSERT INTO Clients (FullName, PassportSeries, PassportNumber, ContactPhone, Address) VALUES
('Иванов Иван Петрович', '4510', '123456', '+7 (912) 345-6789', 'ул. Ленина, 10'),
('Петрова Анна Сергеевна', '4520', '234567', '+7 (987) 654-3210', 'пр. Мира, 25'),
('Смирнов Олег Викторович', '4530', '345678', '+7 (901) 234-5678', 'ул. Советская, 5'),
('Кузнецова Мария Игоревна', '4540', '456789', '+7 (345) 678-9012', 'ул. Пушкина, 15'),
('Попов Сергей Валентинович', '4550', '567890', '+7 (567) 890-1234', 'ул. Гагарина, 7');

-- Insert sample data into Cars table
INSERT INTO Cars (Brand, Model, Color, Year, InsuranceCost, DailyRentalRate) VALUES
('Toyota', 'Camry', 'Серебристый', 2020, 5000.00, 2500.00),
('Volkswagen', 'Polo', 'Белый', 2019, 4500.00, 2000.00),
('Honda', 'Civic', 'Черный', 2021, 5500.00, 2700.00),
('Mazda', '6', 'Синий', 2018, 4200.00, 1800.00),
('Kia', 'Rio', 'Красный', 2022, 4800.00, 2200.00);

-- Insert sample data into Rental table
INSERT INTO Rental (ClientID, CarID, RentalStartDate, RentalEndDate, TotalRentalDays, TotalRentalCost) VALUES
(1, 3, '2025-03-27', '2025-03-30', 3, 8100.00),  -- Honda Civic for 3 days
(2, 1, '2025-04-01', '2025-04-04', 3, 7500.00),  -- Toyota Camry for 3 days
(3, 5, '2025-04-10', '2025-04-13', 3, 6600.00),  -- Kia Rio for 3 days
(4, 2, '2025-04-15', '2025-04-18', 3, 6000.00),  -- Volkswagen Polo for 3 days
(5, 4, '2025-04-20', '2025-04-23', 3, 5400.00);  -- Mazda 6 for 3 days

-- Create a stored procedure to get cars rented for exactly 3 days
CREATE PROCEDURE GetCarsRentedFor3Days
AS
BEGIN
    SELECT 
        r.RentalID,
        c.FullName AS ClientName,
        car.Brand,
        car.Model,
        car.Color,
        r.RentalStartDate,
        r.RentalEndDate,
        r.TotalRentalDays,
        r.TotalRentalCost
    FROM 
        Rental r
        JOIN Clients c ON r.ClientID = c.ClientID
        JOIN Cars car ON r.CarID = car.CarID
    WHERE 
        r.TotalRentalDays = 3
    ORDER BY 
        r.RentalStartDate;
END;
GO

-- Example of how to execute the stored procedure
-- EXEC GetCarsRentedFor3Days;