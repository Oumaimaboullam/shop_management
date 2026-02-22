-- Add image column to articles table
ALTER TABLE articles ADD COLUMN image VARCHAR(255) NULL AFTER description;
