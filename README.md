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

### Option 3: Run with Docker (Recommended)

```bash
# 1) copy env template
cp .env.example .env

# 2) update .env with your real Supabase keys

# 3) build and run
docker compose up --build
```

Visit `http://localhost:8000`

### Option 4: With Supabase

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

### Production Environment Variables

Set these on your server (cPanel, Plesk, VPS, or Apache/Nginx env config):

```bash
SUPABASE_URL=https://your-project-id.supabase.co
SUPABASE_KEY=your-supabase-anon-key
SUPABASE_SERVICE_KEY=your-supabase-service-role-key
ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
```

Notes:
- `ALLOWED_ORIGINS` is comma-separated.
- Keep `SUPABASE_SERVICE_KEY` private (never expose in browser JavaScript).
- Do not commit real secrets to Git.

### Docker Deployment (Containerized)

Files added for containerization:
- `Dockerfile`
- `docker/apache-site.conf`
- `docker-compose.yml`
- `.env.example`
- `.dockerignore`

Run locally:

```bash
cp .env.example .env
docker compose up --build -d
```

Stop:

```bash
docker compose down
```

Notes:
- App runs on `http://localhost:8000`.
- Uploaded product images are persisted via `./uploads` volume.
- Make sure your `.env` contains valid Supabase keys before testing register/login/add product.

### Frontend API Base (Optional Override)

By default, frontend calls `${window.location.origin}/api`.  
If your API is hosted on a different domain, set this in `index.html` before `script.js`:

```html
<script>
  window.APP_CONFIG = {
    API_BASE: 'https://api.yourdomain.com'
  };
</script>
```

If frontend and API are on the same domain, keep the default:

```html
<script>
  window.APP_CONFIG = window.APP_CONFIG || {};
</script>
```

### Deploy on Shared Hosting (Apache + PHP) — Step by Step

1. Create hosting + connect your domain.
2. Ensure hosting supports:
   - PHP 8+
   - `mod_rewrite`
   - `curl` extension
3. Upload project files to `public_html/` (or your web root).
4. Confirm `.htaccess` exists in web root.
5. Add environment variables (`SUPABASE_URL`, keys, `ALLOWED_ORIGINS`).
6. In `.htaccess`, enable HTTPS redirect by uncommenting:
   - `RewriteCond %{HTTPS} off`
   - `RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]`
7. Verify API endpoints in browser or Postman:
   - `https://yourdomain.com/api/products?action=list`
   - `https://yourdomain.com/api/makers?action=list`
8. Test full user flows:
   - Register maker
   - Login maker
   - Add product
   - WhatsApp order button
9. Turn off debug display in production (already handled in `.htaccess`).
10. Set up backups for Supabase and hosting files.

### VPS Deployment (Nginx/Apache) Quick Notes

- Point domain DNS to VPS.
- Install PHP 8+, web server, SSL cert (Let's Encrypt).
- Set web root to project folder.
- Configure rewrite rules equivalent to `.htaccess`.
- Set env vars in server config/systemd/PHP-FPM pool.
- Reload services and run smoke tests.

### Production Checklist

- [ ] Set server environment variables (`SUPABASE_URL`, `SUPABASE_KEY`, `SUPABASE_SERVICE_KEY`)
- [ ] Set `ALLOWED_ORIGINS` to your production domain(s)
- [ ] Enable HTTPS
- [ ] Set proper file permissions
- [ ] Configure error logging
- [ ] Test all API endpoints

## License

MIT License — feel free to use this for your own projects.

---

Made with ❤️ in Uganda