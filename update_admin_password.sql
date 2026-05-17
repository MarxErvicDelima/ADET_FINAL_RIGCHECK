-- Update Admin Password
-- This updates the password hash for admin@rigcheck.com to the correct bcrypt hash for "admin123"

UPDATE User SET password_hash = '$2y$10$c/1.MfAo1IZLLXYSOd7PG.yE27.Ubtr86aLDfcGe0CXzUl6zb6JD2' 
WHERE email = 'admin@rigcheck.com';

-- Verify the update
SELECT user_id, email, first_name, last_name FROM User WHERE email = 'admin@rigcheck.com';
