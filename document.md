# Sanaa Yetu System Documentation

## Overview

Sanaa Yetu is a web marketplace that connects Ugandan artisans (makers) with buyers.  
The system is built as a lightweight single-page frontend with a PHP API backend and Supabase as the database.

Main goals of the platform:
- Let buyers browse handmade products.
- Let makers register/login and publish products.
- Let buyers contact makers directly on WhatsApp.
- Support both image URL input and image upload from device storage.

---

## High-Level Architecture

The system has three main layers:

1. Frontend (`index.html`, `style.css`, `script.js`)
   - Renders UI, handles interactions, calls backend APIs.
   - Runs as a single-page experience using modals and dynamic sections.

2. Backend API (`api/*.php`)
   - Exposes product, maker, and image upload endpoints.
   - Validates requests and communicates with Supabase.

3. Data + Storage
   - Supabase PostgreSQL stores makers and products.
   - Uploaded images are saved to the server `uploads/` directory (current setup).

---

## Frontend: How It Works

### Core Behavior

- On page load, `script.js` calls:
  - `api/products.php?action=list` to load products
  - `api/makers.php?action=list` to load maker list
- UI state is managed in JavaScript variables (`currentUser`, filters, view mode).
- Product cards and maker cards are rendered dynamically.

### Views and Navigation

The app behaves like a mini SPA:
- **Home view**: all products + maker list.
- **My Goods view**: only products posted by logged-in maker.
- **Maker Profile modal**: products by one maker.
- **Auth modal**: registration/login.
- **Studio modal**: maker dashboard for adding products.

### Product Image Options

When publishing a product, a maker can:
1. Upload image from device (`<input type="file">`), or
2. Paste an image URL, or
3. Use automatic fallback image if neither is provided.

Preview behavior:
- Live preview appears for selected file or entered URL.
- "Remove Selected Image" clears file, URL, and preview.

---

## Backend API: How It Works

### `api/products.php`

Handles product operations:
- `action=list` (GET): returns all products (optional category filter).
- `action=get` (GET): returns one product by ID.
- `action=maker` (GET): returns products for one maker.
- `action=search` (GET): product search by name/maker.
- `action=add` (POST): validates payload and creates a product.

### `api/makers.php`

Handles maker account operations:
- `action=list` (GET): returns unique makers.
- `action=register` (POST): validates input, hashes password, creates maker.
- `action=login` (POST): validates input, fetches maker by email, verifies hashed password.

### `api/upload.php`

Handles image upload from device:
- Accepts `POST` multipart/form-data with key `image`.
- Validates:
  - upload success
  - max size (5MB)
  - MIME type (JPG, PNG, WEBP)
- Saves file to `/uploads`.
- Returns public URL used in product creation.

### Shared Config (`api/config.php`)

Contains:
- Supabase request helper (`supabaseRequest`).
- business functions (`getProducts`, `registerMaker`, `loginMaker`, `addProduct`, etc.).
- CORS allowlist logic via `ALLOWED_ORIGINS`.
- Environment variable support for sensitive config:
  - `SUPABASE_URL`
  - `SUPABASE_KEY`
  - `SUPABASE_SERVICE_KEY`

---

## Authentication and Security Model

### Current Authentication Flow

- Maker registers with name, email, password, WhatsApp.
- Password is hashed with `password_hash`.
- Login verifies password using `password_verify`.
- Successful login stores user session info in browser `localStorage`.

### Security Measures in Place

- Input validation in register/login/add-product APIs.
- Password hashing (not plain text).
- CORS restricted by allowlist (not wildcard-only).
- Apache security headers in `.htaccess`.
- Restricted direct access to sensitive file types via `.htaccess`.

### Important Production Notes

- Keep real secrets in environment variables, not committed in code.
- Serve over HTTPS.
- Restrict `ALLOWED_ORIGINS` to your real domain(s).
- Ensure correct permissions for `uploads/`.

---

## Data Model (Conceptual)

### Makers

Typical maker fields:
- `id`
- `name`
- `email`
- `password` (hashed)
- `whatsapp`

### Products

Typical product fields:
- `id`
- `name`
- `price`
- `story`
- `category`
- `image_url`
- `artisan_id`
- `artisan_name`
- `artisan_whatsapp`
- `created_at`

---

## Request Flow Examples

### A) Maker publishes product with device image

1. Maker opens studio and fills product form.
2. Frontend uploads file to `api/upload.php`.
3. API validates and stores image in `uploads/`.
4. API returns image URL.
5. Frontend sends product payload with `image_url` to `api/products.php?action=add`.
6. Product is stored in Supabase and appears in marketplace.

### B) Buyer orders product

1. Buyer clicks "Order via WhatsApp" on product card.
2. Frontend builds `wa.me` link using artisan WhatsApp number and product name.
3. WhatsApp opens with prefilled message to maker.

---

## Routing and URL Structure

### Frontend Route Behavior

The frontend is a single-page app pattern (no separate HTML pages for each section).  
View changes happen through JavaScript state and modals.

### API Routes

Backend endpoints are served under `/api`:
- `/api/products` -> `api/products.php`
- `/api/makers` -> `api/makers.php`
- `/api/upload` -> `api/upload.php`

(`.htaccess` rewrite rules map clean routes to PHP files.)

---

## Deployment and Runtime

### Option 1: Standard PHP Hosting

- Upload files to web root.
- Ensure PHP 8+, `mod_rewrite`, and `curl` are enabled.
- Set environment variables on server.
- Enable HTTPS.

### Option 2: Dockerized Runtime

Container files included:
- `Dockerfile`
- `docker-compose.yml`
- `docker/apache-site.conf`

Run:
1. Copy `.env.example` to `.env`.
2. Fill real Supabase and origin values.
3. Start with `docker compose up --build`.
4. App becomes available on `http://localhost:8000`.

Uploads are persisted by mounting local `uploads/` into the container.

---

## Summary

Sanaa Yetu uses a simple but scalable structure:
- dynamic frontend UI,
- API-driven backend with validation,
- Supabase-backed data persistence,
- secure maker authentication with hashed passwords,
- flexible product image workflow (file upload + URL input),
- and deployment-ready setup for both shared hosting and Docker.

