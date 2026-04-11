# LCommerce Admin — Backend API & Admin Panel

A multi-vendor eCommerce backend built with **Laravel 12**, powering the Pethiyan packaging store. Serves the admin panel, seller panel, customer-facing REST API, and delivery boy API.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| PHP | >= 8.2 |
| Database | MySQL (default) |
| Auth | Laravel Sanctum (API) + Session guards (Admin/Seller) |
| Roles & Permissions | Spatie Laravel Permission |
| File Uploads | Spatie Media Library |
| 2FA | Custom TOTP Service (HMAC-SHA1, 30-second windows) |
| Payments | Razorpay, Stripe, Paystack, Flutterwave, Easepay |
| Push Notifications | Firebase (kreait/laravel-firebase) |
| PDF | barryvdh/laravel-dompdf |
| API Docs | Dedoc Scramble |

---

## Project Structure

```
admin/
├── app/
│   ├── Console/Commands/       # ProcessOrderCashback
│   ├── Enums/                  # AdminPermissionEnum, SellerPermissionEnum, status enums
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin panel controllers
│   │   │   ├── Seller/         # Seller panel controllers
│   │   │   ├── Api/            # REST API controllers (User, DeliveryBoy, Seller, Product)
│   │   │   └── Payments/       # Payment gateway controllers
│   │   └── Middleware/
│   ├── Models/                 # 81 Eloquent models
│   ├── Providers/              # AppServiceProvider, AuthServiceProvider, MailConfigServiceProvider
│   └── Services/               # TotpService, WalletService, SettingService
├── database/migrations/        # ~68 migration files
├── routes/
│   ├── web.php                 # Core web + setup routes
│   ├── admin-route.php         # Admin panel routes (/admin prefix)
│   ├── seller-route.php        # Seller panel routes (/seller prefix)
│   ├── api.php                 # Customer API routes
│   ├── seller-api.php          # Seller API routes
│   └── delivery-boy-api.php    # Delivery boy API routes
└── storage/
    └── app/firebase/           # Firebase service account JSON
```

---

## Authentication

Three separate guards are configured:

| Guard | Model | Access |
|---|---|---|
| `web` | `User` | Customers (API via Sanctum) |
| `admin` | `AdminUser` | Admin panel (session) |
| `seller` | `User` | Seller panel (session) |

---

## TOTP / Two-Factor Authentication

Admin accounts support TOTP-based 2FA implemented via a custom `TotpService`.

**AdminUser fields:**
- `totp_secret` — encrypted Base32 secret
- `totp_enabled_at` — timestamp when 2FA was enabled
- `totp_recovery_codes` — encrypted array of 8 recovery codes

**Routes** (`/admin/security/totp`):

| Method | Endpoint | Description |
|---|---|---|
| GET | `/status` | Check if TOTP is enabled |
| POST | `/setup` | Generate secret and QR code |
| POST | `/enable` | Verify code and activate TOTP |
| POST | `/disable` | Disable TOTP |
| POST | `/recovery-codes` | Regenerate recovery codes |

---

## API Overview

### Customer API (`/api`)
Requires `auth:sanctum` for protected routes.

- Auth: register, login, OTP, mobile verification
- Profile & addresses
- Cart, wishlist, wallet
- Orders, order tracking, returns
- Product reviews, seller feedback
- Promo code validation

### Product API (`/api`) — Public
- Products, categories, brands, FAQs
- Featured sections, hero sections, banners
- Delivery zone checking
- Store-wise products

### Payment Webhooks
- Razorpay, Stripe, Paystack, Flutterwave, Easepay

### Delivery Boy API (`/api/delivery-boy`)
- Auth, profile, location updates
- Order assignment & status updates
- Return pickups, cash collection
- Earnings, withdrawals

### Seller API (`/api/seller`)
- Seller registration

---

## Admin Panel Routes (`/admin`)

| Area | Description |
|---|---|
| Dashboard | Analytics overview |
| Products | Create, edit, approve products |
| Categories / Brands | Catalog management |
| Orders / Returns | Order processing, return handling |
| Customers | Customer accounts |
| Sellers | Seller accounts & earnings |
| Delivery Boys | Assignments, zones, earnings |
| Inventory | Stock levels per store |
| Promo Codes | Discount management |
| Tax Classes / Rates | Tax configuration |
| Shipping Rates | State-based shipping |
| Banners / Hero / Menus | CMS / content management |
| Reports | Sales and financial reports |
| Roles / Permissions | Access control |
| Settings | System-wide settings |
| System Updates | Live update logs |
| Security (TOTP) | 2FA management |

---

## Seller Panel Routes (`/seller`)

- Dashboard with chart data
- Products, inventory, FAQs, brands
- Orders & returns
- Earnings, wallet, withdrawal requests
- Staff (role/permission management for seller users)
- Shipping configuration

---

## Key Models (81 total)

**Users:** `User`, `AdminUser`, `Seller`, `SellerUser`

**Products:** `Product`, `ProductVariant`, `ProductVariantAttribute`, `Category`, `Brand`, `Collection`, `GlobalProductAttribute`

**Orders:** `Order`, `OrderItem`, `SellerOrder`, `Cart`, `Wishlist`, `OrderItemReturn`

**Delivery:** `DeliveryBoy`, `DeliveryZone`, `DeliveryBoyAssignment`, `PinServiceArea`

**Payments:** `PaymentDispute`, `PaymentRefund`, `TaxClass`, `TaxRate`, `Promo`, `Wallet`, `WalletTransaction`

**Content:** `Banner`, `HeroSlide`, `FeaturedSection`, `Menu`, `MenuItem`, `MegaMenuPanel`

**Communication:** `Review`, `Notification`, `SupportTicket`, `UserFcmToken`

---

## Artisan Commands

```bash
# Process cashback for orders delivered 7+ days ago
php artisan cashback:process

# Custom days parameter
php artisan cashback:process --days=14
```

---

## Local Setup

### Requirements
- PHP >= 8.2
- MySQL
- Composer

### Steps

```bash
cd /var/www/lcommerce/admin

composer install

cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```
APP_NAME=LCommerce
APP_URL=http://localhost:8000
DB_DATABASE=lcommerce
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

```bash
php artisan migrate
php artisan storage:link

php artisan serve --port=8000
```

### Setup Routes (local only)

These routes are available only when `APP_ENV=local` and require `SETUP_TOKEN` in `.env`:

| Route | Action |
|---|---|
| `/migrate` | Run all pending migrations |
| `/storage-link` | Create public storage symlink |
| `/optimize-clean` | Clear and re-cache config |

---

## Environment Variables

| Variable | Description |
|---|---|
| `APP_URL` | Backend base URL (affects storage URLs) |
| `CUSTOMER_APP_URL` | Frontend Next.js app URL |
| `DB_*` | MySQL connection details |
| `SANCTUM_STATEFUL_DOMAINS` | Allowed SPA domains for Sanctum |
| `FILESYSTEM_DISK` | Default disk (`public`) |
| `SESSION_DRIVER` | Session storage (`database`) |
| `SETUP_TOKEN` | Token for maintenance setup routes |
| `RAZORPAY_KEY` / `RAZORPAY_SECRET` | Razorpay credentials |
| `STRIPE_KEY` / `STRIPE_SECRET` | Stripe credentials |
| `FIREBASE_*` | Firebase project config |

---

## License

Proprietary — Pethiyan / LCommerce. All rights reserved.
