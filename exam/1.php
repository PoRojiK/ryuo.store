-- Create database
CREATE DATABASE ClinicReception;
GO

-- Use the created database
USE ClinicReception;
GO

-- Create Doctors table
CREATE TABLE Doctors (
    DoctorID INT PRIMARY KEY IDENTITY(1,1),
    FullName NVARCHAR(100) NOT NULL,
    Specialization NVARCHAR(100) NOT NULL
);

-- Create Patients table
CREATE TABLE Patients (
    PatientID INT PRIMARY KEY IDENTITY(1,1),
    FullName NVARCHAR(100) NOT NULL,
    Address NVARCHAR(200),
    DateOfBirth DATE,
    ContactInfo NVARCHAR(100)
);

-- Create Reception table
CREATE TABLE Reception (
    ReceptionID INT PRIMARY KEY IDENTITY(1,1),
    DoctorID INT FOREIGN KEY REFERENCES Doctors(DoctorID),
    PatientID INT FOREIGN KEY REFERENCES Patients(PatientID),
    ReceptionDate DATE NOT NULL,
    ReceptionCost DECIMAL(10,2) NOT NULL,
    PercentageDeduction DECIMAL(5,2) NOT NULL
);

-- Insert sample data into Doctors table
INSERT INTO Doctors (FullName, Specialization) VALUES
('Иванов Иван Иванович', 'Хирург'),
('Петрова Анна Сергеевна', 'Терапевт'),
('Смирнов Олег Викторович', 'Кардиолог'),
('Козлова Елена Петровна', 'Офтальмолог'),
('Николаев Дмитрий Андреевич', 'Невролог');

-- Insert sample data into Patients table
INSERT INTO Patients (FullName, Address, DateOfBirth, ContactInfo) VALUES
('Сидоров Петр Максимович', 'ул. Ленина, 10', '1975-03-15', '+7 (912) 345-6789'),
('Кузнецова Мария Игоревна', 'пр. Мира, 25', '1990-11-22', '+7 (987) 654-3210'),
('Попов Сергей Валентинович', 'ул. Советская, 5', '1985-07-30', '+7 (901) 234-5678'),
('Новикова Ольга Николаевна', 'ул. Пушкина, 15', '1980-12-10', '+7 (345) 678-9012'),
('Морозов Артем Геннадьевич', 'ул. Гагарина, 7', '1995-05-18', '+7 (567) 890-1234');

-- Insert sample data into Reception table
INSERT INTO Reception (DoctorID, PatientID, ReceptionDate, ReceptionCost, PercentageDeduction) VALUES
(1, 3, '2025-03-27', 2500.00, 13.0),
(2, 1, '2025-03-28', 1800.00, 13.0),
(3, 5, '2025-03-29', 3200.00, 13.0),
(4, 2, '2025-03-30', 2100.00, 13.0),
(5, 4, '2025-03-31', 2700.00, 13.0);

-- Create a query to retrieve reception information by date
CREATE PROCEDURE GetReceptionsByDate
    @StartDate DATE,
    @EndDate DATE
AS
BEGIN
    SELECT 
        r.ReceptionID,
        d.FullName AS DoctorName,
        p.FullName AS PatientName,
        r.ReceptionDate,
        r.ReceptionCost,
        r.PercentageDeduction
    FROM 
        Reception r
        JOIN Doctors d ON r.DoctorID = d.DoctorID
        JOIN Patients p ON r.PatientID = p.PatientID
    WHERE 
        r.ReceptionDate BETWEEN @StartDate AND @EndDate
    ORDER BY 
        r.ReceptionDate;
END;
GO

-- Example of how to use the stored procedure
-- EXEC GetReceptionsByDate @StartDate = '2025-03-01', @EndDate = '2025-03-31';