# Sanaa Yetu (Lite Overview)

## What this system does

Sanaa Yetu is an online marketplace for Ugandan handmade products.  
It connects artisans (makers) directly with buyers and allows ordering through WhatsApp.

---

## Who uses it

- **Buyers**: browse products, search by name/category, contact maker on WhatsApp.
- **Makers**: create accounts, log in, and publish products.

---

## Main features

- Product marketplace with categories
- Maker registration and login
- Maker dashboard ("Studio") to post products
- WhatsApp one-click ordering
- Product image support:
  - upload from device
  - paste image URL
  - automatic fallback image if none is provided

---

## How it works (simple flow)

1. User opens the site and sees products.
2. Data is loaded from backend APIs.
3. Maker can register/login from the same interface.
4. Maker adds a product with details and image.
5. Product is saved to the database and appears in marketplace.
6. Buyer clicks WhatsApp button to contact maker directly.

---

## Technology in simple terms

- **Frontend**: website interface (HTML, CSS, JavaScript)
- **Backend**: PHP APIs that process requests
- **Database**: Supabase (PostgreSQL)
- **Image storage**: server uploads folder (current setup)
- **Deployment**: works on regular PHP hosting or Docker containers

---

## Security and reliability highlights

- Passwords are stored as secure hashes
- API inputs are validated
- CORS allowlist controls which sites can call APIs
- HTTPS is supported for production
- Environment variables keep secrets out of code

---

## Deployment options

### 1) Standard hosting (easy)
- Upload files to hosting
- Set environment variables
- Enable SSL/HTTPS
- Test APIs and full user flow

### 2) Docker (portable and consistent)
- Use `docker compose` to run the whole app
- Same behavior across local/dev/prod
- Uploads persist via mounted folder

---

## Business value

Sanaa Yetu helps artisans reach more customers, keeps communication direct, and makes product discovery/order flow simple and mobile-friendly.

