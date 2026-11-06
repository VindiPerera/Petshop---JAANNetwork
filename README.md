# Pet Shop — Warehouse, Branch & POS (Laravel 13 + MySQL)

## Run it
Double-click `start-petshop.bat` (starts MySQL on port 3307 + `php artisan serve`), then open http://127.0.0.1:8000

First time / reset demo data: `php artisan migrate:fresh --seed`

| Login | Email | Password |
|---|---|---|
| Main admin | admin@petshop.test | password |
| Shop (Colombo) | colombo@petshop.test | password |

**Change these passwords before real use.** Delete the demo shop/products as needed.

## How it works
- **Main admin** – warehouse, branches + shop logins, products/categories/suppliers, *Receive Stock* (supplier → warehouse),
  *GRN / Transfers* (one note moves many products warehouse → shop), and every report across all branches (filter by branch).
- **Shop login** – POS billing (search/scan SKU, per-item + whole-bill discount, cash/card/transfer or **split payment**,
  bill preview, auto-print on *Complete & print*, reprint from the Sales list), customers (with pet details),
  own inventory (only products sent to that shop), own sales and reports. Shop users can only ever see their own branch.
- **Shop Settings** (shop login) – logo, address, phones, email, tax no., currency and footer text printed on every bill.
- **Reports** – calendar date pickers + quick ranges (Today, Last 7 days, **Last week**, This month…), branch filter for admin,
  and **PDF / Excel** download for Sales, Profit, Stock Value and Inventory (all branches or one).
- Stock changes only go through `app/Services/StockService.php` (transactional, no negative stock) and are logged in `stock_movements`.
- Profit report uses the cost price captured at the time of each sale.

## Config
`.env` → MySQL on `127.0.0.1:3307`, database `petshop`. Tests use `petshop_test` (`php artisan test`).
The database files live in `.mysql-data/` (separate from XAMPP's MySQL, whose data directory is currently corrupted).
