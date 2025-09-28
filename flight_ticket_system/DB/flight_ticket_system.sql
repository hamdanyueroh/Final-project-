-- 1) สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS flight_ticket_system
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE flight_ticket_system;

-- 2) ผู้ใช้ระบบ
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(30),
  role ENUM('customer','admin') DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3) สนามบิน
CREATE TABLE airports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  iata_code CHAR(3) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  city VARCHAR(100),
  country VARCHAR(100),
  timezone VARCHAR(64) DEFAULT 'UTC'
);

-- 4) เครื่องบิน (กำหนดผังที่นั่งอย่างง่าย)
CREATE TABLE aircrafts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  model VARCHAR(80) NOT NULL,
  registration VARCHAR(30) UNIQUE,
  seat_rows INT NOT NULL,
  seats_per_row INT NOT NULL,
  business_rows INT DEFAULT 0            -- แถวบนสุดเป็น Business
);

-- 5) เที่ยวบิน (เส้นทางพื้นฐาน)
CREATE TABLE flights (
  id INT AUTO_INCREMENT PRIMARY KEY,
  flight_no VARCHAR(10) NOT NULL UNIQUE, -- เช่น KB123
  airline VARCHAR(80) NOT NULL,
  origin_airport_id INT NOT NULL,
  dest_airport_id INT NOT NULL,
  base_duration_min INT NOT NULL,        -- ระยะเวลาโดยประมาณ
  aircraft_id INT NOT NULL,
  FOREIGN KEY (origin_airport_id) REFERENCES airports(id),
  FOREIGN KEY (dest_airport_id) REFERENCES airports(id),
  FOREIGN KEY (aircraft_id) REFERENCES aircrafts(id)
);

-- 6) ตารางบินรายวัน/รายไฟลท์จริง (instances)
CREATE TABLE flight_instances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  flight_id INT NOT NULL,
  departure_at DATETIME NOT NULL,
  arrival_at DATETIME NOT NULL,
  status ENUM('scheduled','cancelled','completed') DEFAULT 'scheduled',
  FOREIGN KEY (flight_id) REFERENCES flights(id)
);

-- 7) ราคา/คลาสที่นั่งต่อไฟลท์จริง
CREATE TABLE fares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  flight_instance_id INT NOT NULL,
  class ENUM('ECONOMY','BUSINESS','FIRST') NOT NULL,
  fare_code VARCHAR(20),
  price DECIMAL(10,2) NOT NULL,
  currency CHAR(3) DEFAULT 'THB',
  remaining_seats INT NOT NULL,
  FOREIGN KEY (flight_instance_id) REFERENCES flight_instances(id)
);

-- 8) การจอง
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  pnr_code CHAR(6) UNIQUE,               -- set เมื่อจ่ายสำเร็จ
  status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
  total_amount DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 9) ผู้โดยสารต่อการจอง
CREATE TABLE passengers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  gender ENUM('M','F','X') DEFAULT 'X',
  dob DATE,
  passport_no VARCHAR(30),
  nationality VARCHAR(60),
  FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- 10) รายการไฟลท์ที่อยู่ใน booking (รองรับขาไป-ขากลับ/ต่อเครื่อง)
CREATE TABLE booking_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  flight_instance_id INT NOT NULL,
  class ENUM('ECONOMY','BUSINESS','FIRST') NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id),
  FOREIGN KEY (flight_instance_id) REFERENCES flight_instances(id)
);

-- 11) ที่นั่งจริงต่อไฟลท์ (ล็อกที่นั่ง)
CREATE TABLE seat_inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  flight_instance_id INT NOT NULL,
  seat_no VARCHAR(5) NOT NULL,           -- เช่น 12A
  class ENUM('ECONOMY','BUSINESS','FIRST') NOT NULL,
  is_booked TINYINT(1) DEFAULT 0,
  booking_id INT,
  UNIQUE KEY uniq_seat (flight_instance_id, seat_no),
  FOREIGN KEY (flight_instance_id) REFERENCES flight_instances(id),
  FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- 12) การชำระเงิน (จำลอง)
CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  provider VARCHAR(40) DEFAULT 'MOCK',
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','paid','failed') DEFAULT 'pending',
  paid_at DATETIME NULL,
  ref_code VARCHAR(64),
  FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- 1) aircrafts: เพิ่มจำนวนแถว First
ALTER TABLE aircrafts
  ADD COLUMN first_rows INT NOT NULL DEFAULT 0 AFTER seat_rows,
  MODIFY COLUMN business_rows INT NOT NULL DEFAULT 0;

-- 2) fares: ให้รองรับชื่อคลาสอิสระ (แทน ENUM เดิมถ้ามี)
ALTER TABLE fares
  MODIFY COLUMN class VARCHAR(20) NOT NULL;

-- 3) seat_inventory: ให้รองรับชื่อคลาสอิสระ (แทน ENUM เดิมถ้ามี)
ALTER TABLE seat_inventory
  MODIFY COLUMN class VARCHAR(20) NOT NULL;

-- (แนะนำ) สร้างดัชนีให้ค้นหาเร็ว
CREATE INDEX idx_fares_fi_class ON fares(flight_instance_id, class);
CREATE INDEX idx_seatinv_fi_class ON seat_inventory(flight_instance_id, class);


-- Seed เบื้องต้น
INSERT INTO airports(iata_code,name,city,country,timezone) VALUES
('BKK','Suvarnabhumi Airport','Bangkok','Thailand','Asia/Bangkok'),
('DMK','Don Mueang International','Bangkok','Thailand','Asia/Bangkok'),
('HKT','Phuket International','Phuket','Thailand','Asia/Bangkok'),
('CNX','Chiang Mai International','Chiang Mai','Thailand','Asia/Bangkok');

INSERT INTO users(full_name,email,password_hash,role) VALUES
('Admin User','admin@example.com', '$2y$10$N5m1s1hUQzX5bN1J7qQwfeF6mJw3E5bYl6R5a.0Yl0g6u8Vn5n0rK','admin');

INSERT INTO aircrafts(model,registration,seat_rows,seats_per_row,business_rows) VALUES
('Airbus A320','HS-ABC',30,6,4);

-- ตัวอย่างเส้นทางและ instance วันพรุ่งนี้
INSERT INTO flights(flight_no,airline,origin_airport_id,dest_airport_id,base_duration_min,aircraft_id)
VALUES ('KB101','KB Air',(SELECT id FROM airports WHERE iata_code='BKK'),
                  (SELECT id FROM airports WHERE iata_code='HKT'),80,
                  (SELECT id FROM aircrafts WHERE registration='HS-ABC'));

INSERT INTO flight_instances(flight_id,departure_at,arrival_at)
VALUES (
 (SELECT id FROM flights WHERE flight_no='KB101'),
 DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 9 HOUR,
 DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 10 HOUR 20 MINUTE
);

-- กำหนดราคา + สร้างที่นั่งอย่างง่าย
INSERT INTO fares(flight_instance_id,class,fare_code,price,currency,remaining_seats)
SELECT id,'ECONOMY','ECOSAVER',1290,'THB',150 FROM flight_instances LIMIT 1;

-- เติมที่นั่ง (12A.. ตามผัง 30 แถว x 6 ที่/แถว)
DELIMITER $$
CREATE PROCEDURE seed_seats()
BEGIN
  DECLARE r INT DEFAULT 1;
  DECLARE letters VARCHAR(6) DEFAULT 'ABCDEF';
  DECLARE fi INT;
  SELECT id INTO fi FROM flight_instances LIMIT 1;

  WHILE r <= 30 DO
    SET @i = 1;
    WHILE @i <= 6 DO
      INSERT INTO seat_inventory(flight_instance_id, seat_no, class)
      VALUES (fi, CONCAT(r, SUBSTRING(letters,@i,1)),
              CASE WHEN r <= 4 THEN 'BUSINESS' ELSE 'ECONOMY' END);
      SET @i = @i + 1;
    END WHILE;
    SET r = r + 1;
  END WHILE;
END$$
DELIMITER ;
CALL seed_seats();
DROP PROCEDURE seed_seats;
