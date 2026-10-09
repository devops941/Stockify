-- GST Billing and Stock Management System for Small Shops
-- Schema + sample seed data

CREATE DATABASE IF NOT EXISTS gst_billing_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gst_billing_db;

-- ---------------------------------------------------------------
-- users (Login module: owner/admin and cashier roles)
-- ---------------------------------------------------------------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    role          ENUM('admin','cashier') NOT NULL DEFAULT 'cashier',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- products (Product Master)
-- ---------------------------------------------------------------
CREATE TABLE products (
    product_id     INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    hsn_code       VARCHAR(10)  NOT NULL,
    unit           VARCHAR(10)  NOT NULL DEFAULT 'nos',
    purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    selling_price  DECIMAL(10,2) NOT NULL DEFAULT 0,
    gst_rate       DECIMAL(4,2)  NOT NULL DEFAULT 0,
    stock_qty      DECIMAL(10,2) NOT NULL DEFAULT 0,
    reorder_level  DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- customers (Customer Master)
-- ---------------------------------------------------------------
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    phone       VARCHAR(15)  NULL,
    state       VARCHAR(50)  NOT NULL,
    gstin       VARCHAR(15)  NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- invoice_counters (drives the INV/<FY>/0001 numbering, reset each FY)
-- ---------------------------------------------------------------
CREATE TABLE invoice_counters (
    fy_label    VARCHAR(10) PRIMARY KEY,
    last_number INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- invoices (Billing)
-- ---------------------------------------------------------------
CREATE TABLE invoices (
    invoice_id    INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no    VARCHAR(20) NOT NULL UNIQUE,
    customer_id   INT NOT NULL,
    invoice_date  DATE NOT NULL,
    taxable_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    cgst          DECIMAL(12,2) NOT NULL DEFAULT 0,
    sgst          DECIMAL(12,2) NOT NULL DEFAULT 0,
    igst          DECIMAL(12,2) NOT NULL DEFAULT 0,
    round_off     DECIMAL(12,2) NOT NULL DEFAULT 0,
    grand_total   DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_mode  ENUM('cash','upi','card') NOT NULL DEFAULT 'cash',
    created_by    INT NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoices_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    CONSTRAINT fk_invoices_user     FOREIGN KEY (created_by)  REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- invoice_items (Billing line items)
-- ---------------------------------------------------------------
CREATE TABLE invoice_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id    INT NOT NULL,
    product_id    INT NOT NULL,
    qty           DECIMAL(10,2) NOT NULL,
    rate          DECIMAL(10,2) NOT NULL,
    taxable_value DECIMAL(12,2) NOT NULL,
    gst_rate      DECIMAL(4,2)  NOT NULL,
    gst_amount    DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- purchases (Purchase Entry)
-- ---------------------------------------------------------------
CREATE TABLE purchases (
    purchase_id   INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    product_id    INT NOT NULL,
    qty           DECIMAL(10,2) NOT NULL,
    rate          DECIMAL(10,2) NOT NULL,
    purchase_date DATE NOT NULL,
    created_by    INT NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchases_product FOREIGN KEY (product_id) REFERENCES products(product_id),
    CONSTRAINT fk_purchases_user    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------

-- Users: admin/admin123, cashier/cashier123
INSERT INTO users (username, password_hash, full_name, role) VALUES
('admin',   '$2y$10$zokt0kyyvgYKvSZJLv4dxeLejeSCBiBZ1vHkhs8Ka5h45vlxMfKgy', 'Shop Owner', 'admin'),
('cashier', '$2y$10$8mroxjqZDejihO1CWEFex.Eh/gax0Qla9d86nG5EB3LOrB2DtSvxC', 'Cashier', 'cashier');

-- Sample products (shop state = Maharashtra, see config/config.php)
INSERT INTO products (name, hsn_code, unit, purchase_price, selling_price, gst_rate, stock_qty, reorder_level) VALUES
('Basmati Rice 1kg',      '1006', 'kg',   60.00,  80.00,  5,  50, 10),
('Toor Dal 1kg',          '0713', 'kg',   90.00, 120.00,  5,  40, 10),
('Refined Sunflower Oil 1L','1512','litre',110.00,140.00, 5,  30, 5),
('Colgate Toothpaste 100g','3306','nos',  45.00,  65.00, 18,  25, 5),
('Dettol Soap',           '3401', 'nos',  25.00,  35.00, 18,  60, 15),
('Notebook 200 pages',    '4820', 'nos',  20.00,  30.00, 12,  100, 20),
('Ball Pen (Pack of 5)',  '9608', 'nos',  15.00,  25.00, 12,  3,  5),
('LED Bulb 9W',           '8539', 'nos',  70.00, 100.00, 18,  2,  5);

-- Sample customers
INSERT INTO customers (name, phone, state, gstin) VALUES
('Walk-in Customer', NULL, 'Maharashtra', NULL),
('Rohit Sharma', '9823456710', 'Maharashtra', NULL),
('Priya Traders', '9988776655', 'Gujarat', '24AAAPL1234C1ZV');
