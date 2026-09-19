CREATE DATABASE IF NOT EXISTS store_2_pos;
USE store_2_pos;

-- Disable foreign key checks temporarily to drop tables in correct order if needed
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS expense_categories;
DROP TABLE IF EXISTS employee_settlements;
DROP TABLE IF EXISTS employee_transactions;
DROP TABLE IF EXISTS employee_tasks;
DROP TABLE IF EXISTS employee_salary_history;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS exchange_replaced_items;
DROP TABLE IF EXISTS exchange_returned_items;
DROP TABLE IF EXISTS exchanges;
DROP TABLE IF EXISTS refund_items;
DROP TABLE IF EXISTS refunds;
DROP TABLE IF EXISTS held_cart_items;
DROP TABLE IF EXISTS held_carts;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS user_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Permissions Table
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    perm_key VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. User Permissions mapping
CREATE TABLE user_permissions (
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    barcode VARCHAR(100) NOT NULL UNIQUE,
    cost_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    category_id INT NULL,
    description TEXT NULL,
    show_in_pos TINYINT(1) NOT NULL DEFAULT 1,
    low_stock_threshold INT NOT NULL DEFAULT 10,
    status ENUM('draft', 'active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Stock Movements Table
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    movement_type ENUM('sale', 'refund', 'exchange_out', 'exchange_in', 'manual_add', 'manual_remove', 'void_reversal') NOT NULL,
    reference_id INT NULL,
    reason VARCHAR(255) NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Sales Table
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(50) NOT NULL UNIQUE,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    returned_change DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    cashier_id INT NOT NULL,
    status ENUM('completed', 'voided', 'fully_refunded', 'partially_refunded') NOT NULL DEFAULT 'completed',
    void_reason VARCHAR(255) NULL,
    voided_by INT NULL,
    voided_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (voided_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Sale Items Table
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    cost_price_snapshot DECIMAL(12,2) NOT NULL,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    net_amount DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Payments Table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    payment_method ENUM('cash', 'card', 'bank', 'other') NOT NULL DEFAULT 'cash',
    amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Held Carts Table
CREATE TABLE held_carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hold_number VARCHAR(50) NOT NULL UNIQUE,
    cashier_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Held Cart Items Table
CREATE TABLE held_cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    held_cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (held_cart_id) REFERENCES held_carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Refunds Table
CREATE TABLE refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    refund_voucher VARCHAR(50) NOT NULL UNIQUE,
    original_sale_id INT NOT NULL,
    cashier_id INT NOT NULL,
    total_refunded DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    reason TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (original_sale_id) REFERENCES sales(id) ON DELETE RESTRICT,
    FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Refund Items Table
CREATE TABLE refund_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    refund_id INT NOT NULL,
    sale_item_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_refunded INT NOT NULL,
    refund_amount DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (refund_id) REFERENCES refunds(id) ON DELETE CASCADE,
    FOREIGN KEY (sale_item_id) REFERENCES sale_items(id) ON DELETE RESTRICT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Exchanges Table
CREATE TABLE exchanges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exchange_voucher VARCHAR(50) NOT NULL UNIQUE,
    original_sale_id INT NOT NULL,
    cashier_id INT NOT NULL,
    returned_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    replaced_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    net_difference DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    settlement_method ENUM('cash', 'card', 'bank', 'credit_note') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (original_sale_id) REFERENCES sales(id) ON DELETE RESTRICT,
    FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Exchange Returned Items Table
CREATE TABLE exchange_returned_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exchange_id INT NOT NULL,
    sale_item_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    value_credited DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (exchange_id) REFERENCES exchanges(id) ON DELETE CASCADE,
    FOREIGN KEY (sale_item_id) REFERENCES sale_items(id) ON DELETE RESTRICT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Exchange Replaced Items Table
CREATE TABLE exchange_replaced_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exchange_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    value_charged DECIMAL(12,2) NOT NULL,
    cost_price_snapshot DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (exchange_id) REFERENCES exchanges(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. Employees Table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    designation VARCHAR(100) NOT NULL,
    joining_date DATE NOT NULL,
    notes TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    pay_status ENUM('active', 'paused') NOT NULL DEFAULT 'active',
    pay_pause_date DATE NULL,
    pay_pause_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17a. Employee Pay Pauses Table
CREATE TABLE IF NOT EXISTS employee_pay_pauses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    pause_date DATE NOT NULL,
    resume_date DATE NULL,
    reason TEXT NULL,
    status ENUM('paused', 'resumed') NOT NULL DEFAULT 'paused',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_emp (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. Employee Salary History Table
CREATE TABLE employee_salary_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    salary_amount DECIMAL(12,2) NOT NULL,
    effective_from DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. Employee Tasks Table
CREATE TABLE employee_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    description TEXT NOT NULL,
    compensation_amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
    completed_at DATE NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20. Employee Transactions Table
CREATE TABLE employee_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    type ENUM('daily_payment', 'advance', 'extra_task', 'incentive', 'salary_settlement', 'reversal') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description TEXT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 21. Employee Settlements Table
CREATE TABLE employee_settlements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL,
    base_salary DECIMAL(12,2) NOT NULL,
    extra_tasks_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    incentives_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    advances_deducted DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    daily_payments_deducted DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    remaining_payable DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('settled', 'closed') NOT NULL DEFAULT 'settled',
    settled_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (settled_by) REFERENCES users(id) ON DELETE RESTRICT,
    UNIQUE KEY emp_month (employee_id, month_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 22. Expense Categories Table
CREATE TABLE expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 23. Expenses Table
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description TEXT NOT NULL,
    payment_method ENUM('cash', 'card', 'bank') NOT NULL DEFAULT 'cash',
    payment_date DATE NOT NULL,
    status ENUM('active', 'voided') NOT NULL DEFAULT 'active',
    voided_by INT NULL,
    void_reason VARCHAR(255) NULL,
    voided_at TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (voided_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 24. Audit Logs Table
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    old_values TEXT NULL,
    new_values TEXT NULL,
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- SEEDING CORE DATA

-- Seeding Permissions
INSERT INTO permissions (perm_key, description) VALUES
('CREATE_SALE', 'Access POS and create sales transactions'),
('EDIT_CART', 'Edit item quantity, price, and discounts in the cart'),
('APPLY_DISCOUNT', 'Apply discount on checkout total'),
('CHANGE_PRICE', 'Override or change unit price in cart'),
('HOLD_BILL', 'Hold/Suspend active bills'),
('VIEW_SALES', 'View historical sales invoices'),
('PRINT_BILL', 'Print and reprint customer invoices'),
('REFUND', 'Process invoice refunds'),
('EXCHANGE', 'Process product exchanges'),
('VOID_BILL', 'Cancel and void completed sales bills'),
('VIEW_OTHER_CASHIERS_SALES', 'View historical sales completed by other staff members'),
('VIEW_PROFIT', 'Access dashboard financial metrics and P&L reports'),
('VIEW_EXPENSES', 'Access and manage operational expenses'),
('MANAGE_INVENTORY', 'Manage categories, products, stock levels, and movements'),
('MANAGE_EMPLOYEES', 'Manage employee ledger, salary settlements, tasks, and advances'),
('MANAGE_USERS', 'Manage user accounts and permission maps');

-- Seeding Default Users
-- admin / admin123
INSERT INTO users (id, username, password_hash, role, status) VALUES
(1, 'admin', '$2y$10$vkBJtdz3x615OW2JMS.lD.vJUNDIrn98MQx8p1qgQB7/4mTepSyvC', 'admin', 'active');

-- cashier / cashier123
INSERT INTO users (id, username, password_hash, role, status) VALUES
(2, 'cashier', '$2y$10$YbZ9/Aw31PshObIWppjRU.UYo3zMMUkeQcj.al6Prl8kVbh.yzLV.', 'staff', 'active');

-- Assign all permissions to Admin
INSERT INTO user_permissions (user_id, permission_id)
SELECT 1, id FROM permissions;

-- Assign POS, sales, refunds, exchanges, voids permissions to Cashier
INSERT INTO user_permissions (user_id, permission_id)
SELECT 2, id FROM permissions WHERE perm_key IN ('CREATE_SALE', 'EDIT_CART', 'HOLD_BILL', 'VIEW_SALES', 'PRINT_BILL', 'REFUND', 'EXCHANGE', 'VOID_BILL', 'VIEW_OTHER_CASHIERS_SALES', 'VIEW_EXPENSES');

-- Seed initial expense categories
INSERT INTO expense_categories (name) VALUES
('Rent'),
('Electricity'),
('Gas'),
('Water'),
('Internet'),
('Telephone'),
('Tea & Refreshments'),
('Cleaning'),
('Stationery'),
('Maintenance'),
('Miscellaneous');
