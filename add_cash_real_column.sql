-- Script SQL para añadir la columna cash_real a la tabla financial_entries
-- Ejecutar este script en la base de datos SQLite

ALTER TABLE financial_entries ADD COLUMN cash_real DECIMAL(10,2) NULL;
