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
2. Create or import the core database directly in phpMyAdmin. The repository intentionally does not contain `.sql` schema or migration files.
3. Configure the database constants in `config.php` (`DB_HOST`, `DB_USER`, `DB_PASS`, and `DB_NAME`) for your local environment. Do not commit real credentials.
4. Ensure PHP can write to `uploads/` and `data/`. These folders hold runtime content and are intentionally ignored by Git.
5. Optionally verify the configured database from the project root:

   ```powershell
   php database/verify_live_database.php
   ```

6. Start the application from the project root:

   ```powershell
   php -S localhost:8000
   ```

7. Open <http://localhost:8000/index.php>. Use `signup.php` to create the first administrator, then use `adlogin.php` for administrator access.

### Core database in phpMyAdmin

The phpMyAdmin database must contain `admin`, `foundations`, `news`, `donors`, `donations`, `donation_status_history`, and `admin_audit_logs`. Keep administrator names, donor names, donor phone numbers, and donation reference codes unique. Donation records must reference valid donors and foundations, and current donation statuses must be `Pending` or `Complete`.

For an existing database, first find donor names connected to more than one phone in phpMyAdmin's SQL tab:

```sql
SELECT full_name, COUNT(*) AS donor_records,
       GROUP_CONCAT(phone ORDER BY phone) AS phones
FROM donors
GROUP BY full_name
HAVING COUNT(DISTINCT phone) > 1;
```

Resolve every returned conflict without deleting donation history, then enforce one donor name per phone:

```sql
ALTER TABLE donors
ADD UNIQUE INDEX uq_donors_full_name (full_name);
```

After the migration, the admin sidebar provides:

- **Donation Ledger**: filterable, paginated donation records with a dedicated detail view.
- **Donor Directory**: normalized donor records linked to their donation history.
- **Reports**: completed-donation totals by foundation, payment method, and month.
- **Audit Log**: a chronological record of administrator changes.

Use the new `update_donation_status.php` workflow from a donation detail page. It supports only **Pending** and **Complete**, and records the prior status, new status, administrator, timestamp, and optional note. Do not use the legacy status page for new administration work.

## Project layout

```text
├── index.php, about.php, news.php, donate.php  Public-facing pages
├── adlogin.php, signup.php, login.php           Administrator authentication
├── addashboard.php, donor.php                   Administrator dashboard and donor records
├── addfoundation.php, addnews.php                Foundation and news administration
├── insert_*.php, delete_*.php                   Form-processing endpoints
├── update_payment_status.php                     Donation-status endpoint
├── config.php, auth_check.php, csrf.php          Shared configuration and security helpers
├── database/verify_live_database.php              Read/rollback checks for phpMyAdmin schema
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
