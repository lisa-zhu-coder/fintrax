-- Script SQL para añadir deleted_at a todas las tablas necesarias
-- Ejecuta este script directamente en tu base de datos SQLite

-- Añadir deleted_at a roles
ALTER TABLE roles ADD COLUMN deleted_at DATETIME NULL;

-- Añadir deleted_at a stores
ALTER TABLE stores ADD COLUMN deleted_at DATETIME NULL;

-- Añadir deleted_at a company
ALTER TABLE company ADD COLUMN deleted_at DATETIME NULL;

-- Añadir deleted_at a company_businesses
ALTER TABLE company_businesses ADD COLUMN deleted_at DATETIME NULL;

-- Añadir deleted_at a employees
ALTER TABLE employees ADD COLUMN deleted_at DATETIME NULL;

-- Añadir deleted_at a payrolls
ALTER TABLE payrolls ADD COLUMN deleted_at DATETIME NULL;

-- Añadir deleted_at a orders
ALTER TABLE orders ADD COLUMN deleted_at DATETIME NULL;

-- Añadir deleted_at a order_payments
ALTER TABLE order_payments ADD COLUMN deleted_at DATETIME NULL;

-- Añadir deleted_at a expense_payments
ALTER TABLE expense_payments ADD COLUMN deleted_at DATETIME NULL;

-- Añadir deleted_at a invoices
ALTER TABLE invoices ADD COLUMN deleted_at DATETIME NULL;
