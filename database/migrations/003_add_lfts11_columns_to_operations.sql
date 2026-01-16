-- Migration to add LFTS11 and Quantity columns to operations table
ALTER TABLE operations 
ADD COLUMN quantity INT DEFAULT 1000 AFTER notes,
ADD COLUMN lfts11_price DECIMAL(10,2) AFTER quantity,
ADD COLUMN lfts11_quantity INT AFTER lfts11_price,
ADD COLUMN lfts11_investment DECIMAL(12,2) AFTER lfts11_quantity,
ADD COLUMN lfts11_return DECIMAL(12,2) AFTER lfts11_investment;
