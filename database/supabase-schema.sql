-- Supabase Database Schema for Sanaa Yetu
-- Run this SQL in your Supabase SQL Editor

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================
-- MAKERS TABLE
-- ============================================
CREATE TABLE makers (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    whatsapp TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Enable Row Level Security
ALTER TABLE makers ENABLE ROW LEVEL SECURITY;

-- Policy: Makers can read all makers (for authentication)
CREATE POLICY "makers_can_read_all" ON makers
    FOR SELECT USING (true);

-- Policy: Anyone can register as a maker
CREATE POLICY "anyone_can_insert_maker" ON makers
    FOR INSERT WITH CHECK (true);

-- Policy: Makers can update their own profile
CREATE POLICY "makers_can_update_own" ON makers
    FOR UPDATE USING (auth.uid()::text = id::text);

-- ============================================
-- PRODUCTS TABLE
-- ============================================
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name TEXT NOT NULL,
    price TEXT NOT NULL,
    story TEXT,
    image_url TEXT,
    category TEXT NOT NULL,
    artisan_id UUID REFERENCES makers(id) ON DELETE CASCADE,
    artisan_name TEXT NOT NULL,
    artisan_whatsapp TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Enable Row Level Security
ALTER TABLE products ENABLE ROW LEVEL SECURITY;

-- Policy: Anyone can read products
CREATE POLICY "anyone_can_read_products" ON products
    FOR SELECT USING (true);

-- Policy: Makers can insert their own products
CREATE POLICY "makers_can_insert_products" ON products
    FOR INSERT WITH CHECK (auth.uid()::text = artisan_id::text);

-- Policy: Makers can update their own products
CREATE POLICY "makers_can_update_own_products" ON products
    FOR UPDATE USING (auth.uid()::text = artisan_id::text);

-- Policy: Makers can delete their own products
CREATE POLICY "makers_can_delete_own_products" ON products
    FOR DELETE USING (auth.uid()::text = artisan_id::text);

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================
CREATE INDEX idx_products_artisan ON products(artisan_id);
CREATE INDEX idx_products_category ON products(category);
CREATE INDEX idx_makers_email ON makers(email);

-- ============================================
-- STORAGE (for product images)
-- ============================================
-- Create a bucket for product images
INSERT INTO storage.buckets (id, name, public)
VALUES ('products', 'products', true);

-- Policy: Anyone can view product images
CREATE POLICY "Public Access" ON storage.objects
    FOR SELECT USING (bucket_id = 'products');

-- Policy: Authenticated makers can upload
CREATE POLICY "Authenticated Upload" ON storage.objects
    FOR INSERT WITH CHECK (bucket_id = 'products' AND auth.role() = 'authenticated');