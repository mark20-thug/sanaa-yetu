-- Supabase Database Schema for Sanaa Yetu
-- Run this SQL in your Supabase SQL Editor

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================
-- MAKERS TABLE
-- ============================================
CREATE TABLE makers (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    firebase_uid TEXT UNIQUE,
    name TEXT NOT NULL,
    business_name TEXT,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL DEFAULT '',
    whatsapp TEXT NOT NULL,
    location TEXT,
    bio TEXT,
    profile_image_url TEXT,
    member_since DATE DEFAULT CURRENT_DATE,
    is_verified BOOLEAN DEFAULT false,
    verification_status TEXT DEFAULT 'unverified',
    payment_status TEXT DEFAULT 'unpaid',
    payment_reference TEXT,
    payment_amount_ugx INT DEFAULT 0,
    requested_plan TEXT DEFAULT 'starter',
    payment_amount TEXT,
    approval_status TEXT DEFAULT 'pending',
    approval_notes TEXT,
    plan TEXT DEFAULT 'free',
    max_products INT DEFAULT 10,
    can_feature_products BOOLEAN DEFAULT false,
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
    status TEXT DEFAULT 'pending',
    moderation_reason TEXT,
    is_featured BOOLEAN DEFAULT false,
    featured_until TIMESTAMP WITH TIME ZONE,
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
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_makers_email ON makers(email);
CREATE INDEX idx_makers_firebase_uid ON makers(firebase_uid);
CREATE INDEX idx_makers_payment_approval ON makers(payment_status, approval_status);

-- ============================================
-- RATINGS TABLES
-- ============================================
CREATE TABLE maker_ratings (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    maker_id UUID NOT NULL REFERENCES makers(id) ON DELETE CASCADE,
    score INT NOT NULL CHECK (score >= 1 AND score <= 5),
    comment TEXT,
    rater_token TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE product_ratings (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    maker_id UUID NOT NULL REFERENCES makers(id) ON DELETE CASCADE,
    score INT NOT NULL CHECK (score >= 1 AND score <= 5),
    comment TEXT,
    rater_token TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

ALTER TABLE maker_ratings ENABLE ROW LEVEL SECURITY;
ALTER TABLE product_ratings ENABLE ROW LEVEL SECURITY;

CREATE POLICY "anyone_can_read_maker_ratings" ON maker_ratings
    FOR SELECT USING (true);
CREATE POLICY "anyone_can_insert_maker_ratings" ON maker_ratings
    FOR INSERT WITH CHECK (true);

CREATE POLICY "anyone_can_read_product_ratings" ON product_ratings
    FOR SELECT USING (true);
CREATE POLICY "anyone_can_insert_product_ratings" ON product_ratings
    FOR INSERT WITH CHECK (true);

CREATE UNIQUE INDEX idx_maker_rating_unique_rater ON maker_ratings(maker_id, rater_token);
CREATE UNIQUE INDEX idx_product_rating_unique_rater ON product_ratings(product_id, rater_token);
CREATE INDEX idx_maker_ratings_maker ON maker_ratings(maker_id);
CREATE INDEX idx_product_ratings_product ON product_ratings(product_id);

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