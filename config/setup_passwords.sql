-- RUN THIS ONCE IN MYSQL BEFORE USING THE SYSTEM
USE neychurlava_db;
ALTER TABLE Staff ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NOT NULL DEFAULT '';
UPDATE Staff SET password_hash = SHA2('neychurlava2025', 256);
SELECT staff_id, first_name, last_name, email FROM Staff LIMIT 5;
