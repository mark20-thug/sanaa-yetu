# Sanaa Yetu - Technical Implementation Checklist

This checklist maps the monetization/trust strategy to your current codebase so implementation is straightforward.

---

## 1) Maker Profiles (Phase 1)

### 1.1 Database changes (`database/supabase-schema.sql`)
- Add columns to `makers`:
  - `business_name TEXT`
  - `profile_image_url TEXT`
  - `location TEXT`
  - `bio TEXT`
  - `member_since DATE DEFAULT CURRENT_DATE`
  - `is_verified BOOLEAN DEFAULT false`
  - `verification_status TEXT DEFAULT 'unverified'` (`unverified|pending|verified|rejected`)
- Add index:
  - `CREATE INDEX idx_makers_verified ON makers(is_verified);`

### 1.2 Backend changes (`api/config.php`, `api/makers.php`)
- In `registerMaker(...)`:
  - accept/store optional profile fields.
- Add new helpers in `api/config.php`:
  - `getMakerById($id)`
  - `updateMakerProfile($makerId, $payload)`
- Add maker actions in `api/makers.php`:
  - `action=get&id=...`
  - `action=update` (POST/PATCH)

### 1.3 Frontend changes (`index.html`, `script.js`)
- Add maker profile form fields in Studio modal:
  - business name, location, bio, profile photo URL (or file upload later).
- Render profile details in maker cards and maker modal.
- Show `Member since` on profile section.

---

## 2) Verification Badge (Phase 1)

### 2.1 Database
- Use `is_verified` + `verification_status` in `makers`.
- Optional: add `verified_at TIMESTAMPTZ`.

### 2.2 Backend (`api/makers.php`, `api/config.php`)
- Add admin-only/manual action:
  - `action=verify` (for now protect by shared secret or manual DB update).
- Ensure maker fetch returns verification fields.

### 2.3 Frontend (`script.js`, `index.html`, `style.css`)
- Add badge rendering on:
  - product cards
  - maker cards
  - maker profile modal
- Badge rules:
  - `is_verified=true` -> show "Verified Maker"
  - pending -> optional "Verification Pending"

### 2.4 Monetization toggle
- Add `verification_fee_paid BOOLEAN DEFAULT false` (later).

---

## 3) Moderation Workflow (Phase 1)

### 3.1 Database
- Add listing status fields to `products`:
  - `status TEXT DEFAULT 'pending'` (`pending|approved|rejected`)
  - `moderation_reason TEXT`
  - `approved_at TIMESTAMPTZ`
- Add index:
  - `CREATE INDEX idx_products_status ON products(status);`

### 3.2 Backend (`api/config.php`, `api/products.php`)
- Update `addProduct(...)` to store `status='pending'`.
- Update `getProducts(...)` to return only `approved` for public marketplace.
- Add moderation actions:
  - `action=moderate` with approve/reject payload
  - `action=list_pending` for admin moderation queue

### 3.3 Frontend
- In Studio "My Products", show status badge:
  - Pending / Approved / Rejected
- Keep rejected reason visible to maker.

---

## 4) Featured Listings (Phase 1 Revenue)

### 4.1 Database
- Add to `products`:
  - `is_featured BOOLEAN DEFAULT false`
  - `featured_until TIMESTAMPTZ`

### 4.2 Backend
- In product listing query, order by featured first, then created date.
- Add admin/manual endpoint:
  - `action=feature` to enable/disable and set `featured_until`.

### 4.3 Frontend
- Show "Featured" badge on product cards.
- Optional dedicated featured row at top of home.

---

## 5) Ratings (Phase 2)

### 5.1 Database
- Create `maker_ratings` table:
  - `id UUID PK`
  - `maker_id UUID`
  - `score INT` (1-5)
  - `created_at TIMESTAMPTZ`
  - `source TEXT` (optional, e.g., `whatsapp_followup`)
- Optional aggregate fields in makers:
  - `rating_avg NUMERIC`
  - `rating_count INT`

### 5.2 Backend
- Add endpoints:
  - `api/makers.php?action=rate` (POST)
  - `api/makers.php?action=ratings&id=...` (GET)
- Recompute aggregates after rating submission (or via DB view).

### 5.3 Frontend
- Add simple rating UI in maker profile modal.
- Display stars + count on maker/product cards.

---

## 6) Activity Signals (Phase 2)

### 6.1 Database
- Add to `makers`:
  - `last_active_at TIMESTAMPTZ`
  - `response_score INT DEFAULT 0` (optional derived)
- New table `product_clicks`:
  - `id UUID PK`
  - `product_id UUID`
  - `maker_id UUID`
  - `event_type TEXT` (`view|whatsapp_click`)
  - `created_at TIMESTAMPTZ`

### 6.2 Backend
- Add lightweight tracking endpoint:
  - `api/analytics.php?action=track` (POST)
- Record WhatsApp click events before opening `wa.me`.

### 6.3 Frontend (`script.js`)
- In `openWhatsAppChat(...)`, fire tracking event before `window.open(...)`.
- Render labels:
  - Active this week
  - Responds quickly (if score threshold met)
  - X contacts this month

---

## 7) Storefront Pages (Phase 2 Revenue + Discovery)

### 7.1 Routing
- Keep current app structure (single-page), add route state:
  - `?maker=<id>` OR hash route `#/maker/<id>`
- Reuse `viewMakerGoods(...)` and load profile + products.

### 7.2 Database
- Add `makers.slug TEXT UNIQUE`.

### 7.3 Backend
- Add lookup by slug:
  - `api/makers.php?action=get_by_slug&slug=...`

### 7.4 Frontend
- Generate share link from maker modal:
  - `https://yourdomain.com/?maker=akello-designs`

---

## 8) Freemium / Studio Pro (Phase 2 Revenue)

### 8.1 Database
- Add to `makers`:
  - `plan TEXT DEFAULT 'free'` (`free|pro`)
  - `plan_expires_at TIMESTAMPTZ`
  - `max_products INT DEFAULT 10`

### 8.2 Backend
- In `addProduct(...)`, enforce product limit from plan.
- Return clear message when limit is exceeded.

### 8.3 Frontend
- In Studio modal:
  - show current plan
  - show product usage (`8/10 used`)
  - show CTA for upgrade

---

## 9) Pay-Per-Lead (Phase 3)

### 9.1 Data prerequisites
- Reliable event tracking for WhatsApp clicks.
- De-duplication logic (same user/session clicks).

### 9.2 Backend
- Add lead ledger:
  - lead count per maker per period
  - free quota then paid bundles

### 9.3 Frontend/Admin
- Display monthly leads and remaining free quota.

---

## 10) File-by-File Priority Map

### Immediate edits (next sprint)
- `database/supabase-schema.sql`
  - add maker profile + verification + moderation + featured fields
- `api/config.php`
  - expand data helpers for maker profile, moderation, featuring
- `api/makers.php`
  - add get/update/verify actions
- `api/products.php`
  - moderation-aware list/add/moderate actions
- `index.html`
  - studio profile inputs + status labels
- `script.js`
  - render badges, status, tracking hooks
- `style.css`
  - styles for badges, status pills, profile blocks

### New files to add
- `api/analytics.php` (event tracking)
- `api/admin.php` (optional manual admin actions)
- `database/migrations/001_trust_monetization.sql` (recommended incremental migration)

---

## 11) Implementation Order (Recommended)

1. DB migration for profile + verification + moderation + featured.
2. Backend support in `api/config.php`.
3. Endpoint updates in `api/makers.php` and `api/products.php`.
4. Frontend rendering of profile + badges + moderation status.
5. Featured listing ordering + badge.
6. Add analytics tracking for WhatsApp clicks.
7. Build storefront route and share links.
8. Add plan limits and monetization controls.

---

## 12) Acceptance Criteria for Phase 1

Phase 1 is complete when:
- maker profile fields are editable and visible to buyers
- verified badge appears correctly on maker/product surfaces
- new products require moderation before public display
- featured products can be set and appear first
- manual operational process exists for verification and moderation

