# Donation Management System

A plain PHP and MySQL web application for publishing charity foundations and news, collecting donations, and managing donation records through an administrator dashboard.

## Features

- Public pages for the home page, about page, news, and donations.
- Donation capture with foundation selection, payment method, and Myanmar/Arabic numeral handling.
- Admin sign-up and sign-in with password hashing, lockout controls, CSRF protection, and session checks.
- Dashboard totals, donation-status updates, donor records, and foundation/news CRUD.
- Image uploads for foundations and news.

## Technology

- PHP with procedural `mysqli`
- MySQL / MariaDB
- HTML, CSS, and vanilla JavaScript
- Font Awesome and SweetAlert2 loaded from CDNs

No framework, package manager, build step, or automated test suite is required.

## Requirements

- PHP 7.4+ with the `mysqli` extension enabled
- MySQL or MariaDB
- A PHP-capable local server such as XAMPP, WAMP, or PHP's built-in development server

## Local setup

1. Create a MySQL database named `dmssystem`.
2. Configure the database constants in `config.php` (`DB_HOST`, `DB_USER`, `DB_PASS`, and `DB_NAME`) for your local environment. Do not commit real credentials.
3. Create the tables used by the application. The code expects these columns:

   - `admin`: `adname`, `adpassword`, `failed_attempts`, `last_failed_at`, `locked_until`
   - `foundations`: `fid`, `image_path`, `fname`, `description`, `intro`
   - `news`: `nid`, `image_path`, `title`, `content`
   - `donations`: `id`, `donor_name`, `address`, `phone`, `amount`, `foundation_id`, `payment_method`, `payment_status`, `created_at`

4. Ensure PHP can write to `uploads/` and `data/`. These folders hold runtime content and are intentionally ignored by Git.
5. Start the application from the project root:

   ```powershell
   php -S localhost:8000
   ```

6. Open <http://localhost:8000/index.php>. Use `adlogin.php` to access the administrator area.

> This repository currently has no database migration or schema file. Create the schema in your local database before using the app.

## Project layout

```text
├── index.php, about.php, news.php, donate.php  Public-facing pages
├── adlogin.php, signup.php, login.php           Administrator authentication
├── addashboard.php, donor.php                   Administrator dashboard and donor records
├── addfoundation.php, addnews.php                Foundation and news administration
├── insert_*.php, delete_*.php                   Form-processing endpoints
├── update_payment_status.php                     Donation-status endpoint
├── config.php, auth_check.php, csrf.php          Shared configuration and security helpers
├── css/                                          Page stylesheets
├── js/                                           Client-side scripts
├── image/                                        Versioned site imagery
├── uploads/                                      Runtime uploaded files (ignored)
└── data/                                         Runtime security state (ignored)
```

## Development notes

- Public routes are referenced directly by PHP filename; there is no router.
- The admin pages require an authenticated session. Make all new state-changing forms use the existing CSRF helpers.
- Keep uploaded files and generated runtime JSON out of version control. If existing tracked runtime files need to stop being tracked, remove them from Git's index deliberately with `git rm --cached <path>`.
- Run a PHP syntax check after PHP changes:

  ```powershell
  Get-ChildItem -File -Filter *.php | ForEach-Object { php -l $_.FullName }
  ```

## Security

The application includes password hashing, session-cookie settings, CSRF tokens, login throttling, and admin authorization checks. Before production use, set non-default database credentials, serve the site over HTTPS, restrict permissions on `uploads/` and `data/`, and review the PHP/MySQL server configuration.
