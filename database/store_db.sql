-- =====================================================================
-- KINETIC TELEMETRY ARCHIVE // STORE DATABASE SCHEMA & SEED DATA
-- File: database/store_db.sql
-- Mata Kuliah: Pemrograman Web - Pertemuan 3 (Mini Project Product Manager)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `store_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `store_db`;

-- ---------------------------------------------------------------------
-- Table: products
-- Description: Menyimpan katalog perangkat keras laboratorium & instrumen telemetri
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `price` DECIMAL(14, 2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `image` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Constraints & Indexes
  CONSTRAINT `uk_products_name` UNIQUE (`name`),
  CONSTRAINT `chk_products_price` CHECK (`price` > 0),
  CONSTRAINT `chk_products_stock` CHECK (`stock` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes untuk optimasi query pencarian GET (search by name, category, order)
CREATE INDEX `idx_products_category` ON `products` (`category`);
CREATE INDEX `idx_products_name` ON `products` (`name`);
CREATE INDEX `idx_products_created` ON `products` (`created_at` DESC);

-- ---------------------------------------------------------------------
-- Seed Initial Telemetry & Hardware Assets
-- Data realistis instrumen hardware dengan berbagai variasi kategori & kuantitas stok
-- ---------------------------------------------------------------------
INSERT INTO `products` (`name`, `category`, `price`, `stock`, `image`, `description`) VALUES
('STM32H753 Arm Cortex-M7 Core Unit', 'Microcontroller', 1450000.00, 24, 'stm32h753.jpg', 'Unit prosesor kendali industri dual-core 480MHz dengan hardware cryptographic accelerator dan antarmuka CAN-FD.'),
('SCD41 NDIR Photoacoustic CO2 Sensor', 'Sensor Unit', 620000.00, 15, 'scd41_co2.jpg', 'Sensor pemantauan emisi CO2 presisi tinggi dengan teknologi photoacoustic dan kompensasi temperatur otomatis.'),
('Vibration Telemetry Piezo Probe v4', 'Sensor Unit', 890000.00, 4, 'piezo_probe.jpg', 'Sensor getaran akselerometer industri 3-axis dengan bandwidth 10kHz untuk pemantauan prediktif bearing motor.'),
('Isolated DC-DC Buck Module 48V to 12V 10A', 'Power Module', 475000.00, 38, 'dcdc_buck.jpg', 'Konverter daya industri efisiensi 94% dengan isolasi galvanik 1500VDC dan proteksi transient over-voltage.'),
('BNO085 9-DOF IMU Precision Tracker', 'Sensor Unit', 785000.00, 2, 'bno085_imu.jpg', 'Sensor navigasi inersia 9-axis dengan integrated sensor fusion algorithm untuk robotika otonom dan telemetry avionik.'),
('SX1262 LoRa 915MHz Transceiver Module', 'Telemetry Radio', 310000.00, 42, 'sx1262_lora.jpg', 'Modul transmisi data nirkabel jarak jauh ultra-low power hingga 15km line-of-sight dengan enkripsi AES-128 hardware.'),
('Linear Stepper Actuator NEMA 23 150mm', 'Mechanical Actuator', 1250000.00, 8, 'nema23_actuator.jpg', 'Aktuator linier industri dengan bola presisi lead-screw C7, torsi dorong 800N, dan resolusi langkah 0.01mm.'),
('GaN-FET Ultra-Fast Dual Gate Driver', 'Power Module', 285000.00, 0, 'gan_fet_driver.jpg', 'Modul driver switching daya berbasis Gallium Nitride frekuensi 10MHz untuk catu daya resonan tingkat lanjut.'),
('ESP32-S3 AI Telemetry Edge Gateway', 'Microcontroller', 215000.00, 56, 'esp32s3_gateway.jpg', 'Gateway nirkabel Wi-Fi 4 + BLE 5.0 dengan akselerator vektor neural network untuk inferensi machine learning di sisi edge.'),
('Industrial Current Clamp 100A Hall-Effect', 'Sensor Unit', 540000.00, 11, 'current_clamp.jpg', 'Sensor pembacaan arus AC/DC non-kontak berpresisi tinggi dengan linearitas 0.5% dan output tegangan terkalibrasi.');
