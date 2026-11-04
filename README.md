# Proto Folio

A single-company point-of-sale, invoicing and catalog app for a small shop — built for a
counter workflow where invoices are printed on a **58 mm thermal receipt printer**.

It is a Laravel application with an Inertia.js + Svelte 5 front end (no separate REST API):
controllers render Svelte pages directly and pass data as props.

---

## What's in the box

| Area | Status |
| --- | --- |
| Authentication — login, registration, password reset, 2FA, passkeys | Working (Laravel Fortify starter kit) |
| Registration gate — a TOTP code is required to create an account | Working |
| Dashboard | Placeholder |
| Customers | CRUD |
| Products | CRUD, with photo upload, type (product/service), SKU/barcode, category, unit, tax rate, selling/cost price, stock tracking flag |
| Categories, Units, Tax rates | CRUD |
| Invoices | CRUD, line items (product, qty, price, tax), draft/sent/paid, server-side totals |
| 58 mm thermal receipt | Working (`/invoices/{invoice}/print`) |
| Expenses, Documents | Placeholder pages |
| Settings | Business details, Profile, Security, Appearance, Language (English / Bahasa Indonesia) |
| POS schema (`sales`, `sale_items`, `payments`, `shifts`, `stock_*`, `sale_returns`, `currencies`) | Database + models in place, no UI yet |
| Roles (admin/member) | Not implemented |

The app is single-location and single-company. There is no tenant, warehouse or variant
(batch/serial) concept.

---

## Tech stack

- **Backend:** PHP 8.3, Laravel 13, Inertia Laravel 3
- **Frontend:** Svelte 5, TypeScript, Tailwind CSS 4, bits-ui, lucide icons
- **Bridge:** Inertia.js (`<Form>`, `useForm`, Wayfinder-generated route helpers)
- **Database:** PostgreSQL 16
- **Assets/build:** Vite (`vp`), npm
- **Images:** PHP GD (resize + WebP re-encode)
- **Deploy:** Docker Compose (php-fpm + nginx + Postgres)

---

## Requirements

- PHP **8.3** with `pdo_pgsql`, `gd`, `intl`, `zip`, `bcmath`, `fileinfo` (`exif` enables
auto-rotation of phone photos)
- Composer 2
- Node.js **20+** (the Dockerfile builds with Node 24) and npm
- PostgreSQL 13+ (16 recommended)
- A thermal printer is optional — without one the print view still renders as a 58 mm PDF/page

---

## Local installation

```sh
git clone git@github.com:fuuyuki/proto-folio.git
cd proto-folio

# 1. PHP dependencies and environment
composer install
cp .env.example .env
php artisan key:generate

# 2. Database (native Postgres; the app does not ship SQLite for runtime)
sudo -u postgres psql -c "CREATE USER profolio_admin WITH PASSWORD 'your-password' CREATEDB;"
sudo -u postgres psql -c "CREATE DATABASE profolio_db OWNER profolio_admin;"
```

Match the credentials in `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433        # whatever port your Postgres listens on
DB_DATABASE=profolio_db
DB_USERNAME=profolio_admin
DB_PASSWORD=your-password
```

```sh
# 3. Schema + defaults (currency, unit, tax rate, store, register, payment methods, numbering)
php artisan migrate --seed

# 4. Public storage symlink — required for product photos
php artisan storage:link

# 5. Front end
npm install
npm run build       # or `npm run dev` while developing
```

Run everything (PHP server + Vite) with one command:

```sh
composer run dev
```

Then open `http://localhost:8000`. The same URL is configured as `APP_URL` in `.env.example`.

> `DatabaseSeeder` creates a demo user (`test@example.com` / `password`) for local work, then
> calls `PosDefaultsSeeder`. Never run the full seeder on production — the Docker entrypoint
> seeds only `PosDefaultsSeeder`.

### Creating an account

Registration is gated by a shared TOTP code instead of an email verification link, so the app
needs no mail server.

```sh
php artisan registration:secret            # prints the secret, otpauth URI and a live code
php artisan registration:secret --rotate   # invalidate existing authenticator entries
```

Add the secret (or the `otpauth://` URI) to Google Authenticator / Aegis / 1Password as a
time-based account, then enter the current 6-digit code in the **Registration code** field on
`/register`. Codes are checked with a ±30 second window and throttled to 5 attempts per minute
per IP.

---

## Configuration

Everything lives in the `settings` key/value table and is editable from the UI unless noted.

| Setting | Where | Notes |
| --- | --- | --- |
| Business name + address | **Settings → Business** | Printed at the top of the receipt. Falls back to `APP_NAME` until filled in. |
| Language | **Settings → Language** | `en` / `id`. Applies to the interface and to invoice/receipt labels. |
| Base currency | `settings.base_currency` (no UI yet) | Seeded as `IDR`; must match a row in `currencies`. |
| Product number / SKU / invoice numbering | `number_sequences` (no UI yet) | Patterns support `{PREFIX} {YYYY} {YY} {MM} {DD} {YYYYMMDD} {SEQ} {SEQ:n}` with per-period resets. |
| Registration secret | `settings.registration_totp_secret` | Managed by `php artisan registration:secret`. |

### Product photos

Uploads are re-encoded server-side by `App\Actions\Products\StoreProductImage`:

- accepted: JPG, PNG, WebP up to 5 MB and 6000 px per side
- stored on the `public` disk under `storage/app/public/products/YYYY/MM/`
- filenames are 40 random characters with a server-chosen extension — the uploaded name is discarded
- longest edge capped at **1024 px**, encoded as **WebP q82** (JPEG fallback), EXIF stripped

Served through the `public/storage` symlink, which is why `php artisan storage:link` matters.

---

## Thermal receipt printing (58 mm)

The print page is a dedicated layout (`resources/js/pages/invoices/Print.svelte`) that bypasses
the app shell:

- `@page { size: 58mm auto; margin: 0 }`
- receipt width `58mm`, horizontal padding `5mm` → **48 mm of printable content** (the GP-58 head width is 384 dots / 48 mm)
- monospace type, dashed rules, no tables or page-break-sensitive markup
- a 12 mm paper feed at the end so the cutter/tear bar clears the text
- the browser print dialog opens automatically

In Chrome/Chromium pick the 58 mm roll (or a custom `58 mm` width), **Margins: None**, **Scale: 100%**,
and disable headers/footers.

### CUPS driver for a Gprinter GP-58 (`zj-58`)

Zorin/Ubuntu ships no driver for these printers — the Windows `.exe` is not needed. The `zj-58`
filter works:

```sh
sudo apt install -y build-essential cmake cups-filters
git clone https://github.com/klirichek/zj-58 && cd zj-58
cmake -B build && cmake --build build && sudo cmake --install build

# PPDs install to /usr/share/cups/model/zjiang/ (lowercase names: zj58.ppd, ...)
sudo lpadmin -p GP58 -E \
  -v "usb://Gprinter/GP-58?serial=6B6929980933" \
  -P /usr/share/cups/model/zjiang/zj58.ppd
sudo lpadmin -d GP58
lpstat -p GP58

# Feed behaviour so the paper is cut clear of the receipt
sudo lpadmin -p GP58 -o BlankSpace=True -o FeedDist=4feed15mm -o FeedWhere=AfterJob
```

Quick sanity check without CUPS (device is usually `/dev/usb/lp0` or `lp1`):

```sh
lsusb | grep -i gprinter          # 6868:0200 Gprinter GP-58
sudo sh -c "printf 'GP-58 OK\n\n\n\n' > /dev/usb/lp1"
```

Shell redirection needs the `lp` group membership applied in a fresh login session
(`sudo usermod -aG lp "$USER"`, then log out/in); `sudo` is always required for redirection.

---

## Common commands

```sh
# Development
composer run dev                 # PHP server + Vite
npm run dev                     # Vite only
npm run build                   # production assets

# Routes / front-end helpers
php artisan route:list
php artisan wayfinder:generate --with-form    # regenerate @/routes and @/actions
```

> After adding or renaming a route you **must** regenerate Wayfinder with `--with-form`,
> otherwise every `<Form>` component loses its generated form helpers.

```sh
# Quality gates
php artisan test --compact      # Pest; uses in-memory SQLite, no database setup needed
vendor/bin/pint                 # code style
vendor/bin/phpstan analyse      # Larastan, level 7
npm run types:check             # svelte-check
npm run check                   # Vite+ checks (formatting / lint)
```

---

## Docker deployment

The repository ships a three-stage `Dockerfile` (Composer vendor → Node assets → php-fpm +
nginx runtime) and a `docker-compose.yml` with `app`, `web` and `db` services.

```sh
cp .env.docker.example .env.docker
```

Fill in `.env.docker`:

```dotenv
APP_URL=https://app.yourdomain.com
APP_KEY=base64:...        # php artisan key:generate --show  |  echo "base64:$(openssl rand -base64 32)"
DB_DATABASE=profolio
DB_USERNAME=profolio
DB_PASSWORD=use-a-long-random-value
APP_BIND=127.0.0.1        # keep nginx/certbot on the host in front
APP_PORT=8080
DB_HOST_PORT=5433         # Postgres published on loopback only, for DBeaver over SSH
```

```sh
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs -f app
```

On boot the `app` entrypoint waits for Postgres, then runs `migrate --force`, seeds
`PosDefaultsSeeder`, creates `storage:link` and runs `artisan optimize`. Toggle with
`RUN_MIGRATIONS`, `SEED_DEFAULTS` and `APP_OPTIMIZE` in `.env.docker`. Both `app` and `web`
share the `storage` volume (read-only in `web`) so uploaded product photos are served directly
by nginx.

Useful runtime commands:

```sh
docker compose --env-file .env.docker exec -u www-data app php artisan registration:secret
docker compose --env-file .env.docker exec -u www-data app php artisan migrate:status
docker compose --env-file .env.docker exec db psql -U profolio -d profolio
```

> `POSTGRES_PASSWORD` is only applied when the `dbdata` volume is first created. If you change
> the database password later, either run `ALTER USER ... PASSWORD ...` inside `psql` or recreate
> the volume with `docker compose down -v` (this deletes the database).

### Reverse proxy + TLS on the host

Point the subdomain at the VPS, then:

```nginx
server {
    server_name app.yourdomain.com;
    client_max_body_size 20M;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
    }

    listen 443 ssl; # managed by Certbot
}
```

```sh
sudo ln -s /etc/nginx/sites-available/proto-folio /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d app.yourdomain.com
```

The app trusts proxy headers (`trustProxies(at: '*')`), so `APP_URL` must use `https://` for
correct cookie and asset URLs. Health check: `GET /up` returns `200`.

---

## Project layout

```
app/
  Actions/            Number sequence generator, product image pipeline
  Enums/              InvoiceStatus, ProductType, sale/payment/stock enums
  Http/Controllers/   CRUD controllers + Settings/*
  Http/Middleware/    SetLocale, HandleInertiaRequests (shared currency/locale)
  Models/             Eloquent models (invoicing + POS schema)
  Support/            BusinessProfile, RegistrationCode
database/
  migrations/         Invoicing tables, POS schema, settings, number sequences
  seeders/            PosDefaultsSeeder (idempotent defaults)
lang/{en,id}/         Invoice and receipt labels
resources/js/
  pages/              Inertia pages (invoices, products, customers, settings, …)
  components/forms/   InvoiceForm, ProductForm, …
  routes/, actions/   Wayfinder-generated route helpers (do not edit)
docker/               entrypoint, nginx config, php.ini overrides
```

---

## Roadmap

- POS checkout screen (cart, barcode search, cash tendered/change, shift open/close, stock deduction)
- 58 mm receipt for sales, reusing the invoice print layout
- Expenses and Documents (file storage)
- Dashboard metrics (revenue, recent invoices, low stock)
- `users.role` (admin/member) and UI gating
- Sale returns UI
- Currency management UI (currently a seeded `settings` value)
