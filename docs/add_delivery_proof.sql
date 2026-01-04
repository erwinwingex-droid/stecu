-- Add delivery proof column to orders table
ALTER TABLE orders ADD COLUMN delivery_proof VARCHAR(255) NULL AFTER tracking_updated;
ALTER TABLE orders ADD COLUMN completed_at TIMESTAMP NULL AFTER delivery_proof;

-- Create index for faster queries
CREATE INDEX idx_completed_at ON orders(completed_at);
