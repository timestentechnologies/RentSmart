-- Add phone and address columns to users table
-- This SQL adds the missing columns required for the registration form

-- Check if columns exist before adding (optional safety check)
-- You can run these queries individually or all at once

-- Add phone column
ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) AFTER email;

-- Add address column  
ALTER TABLE users 
ADD COLUMN address TEXT AFTER phone;

-- Verify the columns were added
DESCRIBE users;
