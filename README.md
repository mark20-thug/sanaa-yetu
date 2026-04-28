# Sanaa Yetu — Ugandan Artisan Marketplace

A web-based marketplace connecting Ugandan artisans with buyers worldwide. Features WhatsApp integration for instant ordering.

![Sanaa Yetu](https://images.unsplash.com/photo-1604242692760-2e7b0c1dd203?w=800&h=400&fit=crop)

## Features

- 🛒 **Product Marketplace** — Browse handcrafted Ugandan goods
- 👨‍🎨 **Meet the Makers** — Discover artisan profiles
- 📱 **WhatsApp Ordering** — One-click order via WhatsApp
- 🔐 **Maker Accounts** — Register as an artisan to sell
- 📦 **Product Management** — Add and manage your crafts
- 🔍 **Search & Filter** — Find products by category or name

## Tech Stack

- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Backend:** PHP 8+
- **Database:** Supabase (PostgreSQL)
- **Storage:** Supabase Storage for images

## Project Structure

```
saana-yetu/
├── index.html          # Main HTML file
├── style.css           # All styles
├── script.js           # Frontend JavaScript
├── api/
│   ├── config.php      # Supabase configuration
│   ├── products.php    # Products API endpoint
│   └── makers.php      # Makers API endpoint
├── database/
│   └── supabase-schema.sql  # Database schema
├── README.md           # This file
└── .htaccess           # Apache deployment config
```

## Quick Start

### Option 1: Local Development (No Backend)

Simply open `index.html` in a browser. The app uses localStorage as fallback.

### Option 2: With PHP Backend

```bash
# Start PHP server
cd saana-yetu
php -S localhost:8000
```

Visit `http://localhost:8000`

### Option 3: With Supabase

1. Create a Supabase project at [supabase.com](https://supabase.com)
2. Run `database/supabase-schema.sql` in the SQL Editor
3. Update `api/config.php` with your credentials:
   ```php
   define('SUPABASE_URL', 'https://your-project.supabase.co');
   define('SUPABASE_KEY', 'your-anon-key');
   ```
4. Start the PHP server

## API Endpoints

### Products

| Endpoint | Method | Description |
|----------|--------|-------------|
| `api/products.php?action=list` | GET | List all products |
| `api/products.php?action=list&category=Baskets` | GET | Filter by category |
| `api/products.php?action=add` | POST | Add new product |
| `api/products.php?action=search&q=basket` | GET | Search products |

### Makers

| Endpoint | Method | Description |
|----------|--------|-------------|
| `api/makers.php?action=list` | GET | List all makers |
| `api/makers.php?action=register` | POST | Register new maker |
| `api/makers.php?action=login` | POST | Login maker |

## Categories

- Beadwork
- Bark Cloth
- Drums
- Baskets
- Pottery

## Deployment

### Apache (.htaccess)

The included `.htaccess` file handles:
- URL rewriting for clean URLs
- PHP error logging
- Security headers

### Production Checklist

- [ ] Update Supabase credentials in `api/config.php`
- [ ] Enable HTTPS
- [ ] Set proper file permissions
- [ ] Configure error logging
- [ ] Test all API endpoints

## License

MIT License — feel free to use this for your own projects.

---

Made with ❤️ in Uganda