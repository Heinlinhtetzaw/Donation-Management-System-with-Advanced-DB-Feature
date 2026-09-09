# Donation Management System

Version 1.1.1 is a plain PHP and MySQL application for publishing charity content, accepting donation pledges, and managing verification through an auditable administrator workspace.

## What the application does

### Public experience

- Lists partner foundations on the home and about pages.
- Publishes expandable charity news articles.
- Accepts donation pledges for a selected foundation using Cash, Wavepay, or Kpay.
- Validates 11-digit Myanmar or Arabic-numeral phone numbers and stores one normalized form.
- Creates a stable reference such as `DON-00000042` and begins every donation in `Pending`.
- Reuses donor identities while rejecting a donor name/phone mismatch.

This project records and verifies donation pledges; it does not connect to a payment gateway or transfer funds.

### Administrator workspace

- Creates the first administrator without an invite code, then requires renewable one-time invite codes.
- Uses password hashing, CSRF protection, session renewal, a 15-minute inactivity timeout, and account/IP login throttling.
- Shows verified totals, donor counts, status counts, and recent donations.
- Filters and paginates the donation ledger by reference, donor, phone, status, foundation, and date.
- Provides a donation detail view with verifier attribution, notes, and complete status history.
- Maintains a searchable donor directory and verified totals by foundation, payment method, and month.
- Creates, previews, edits, and safely deletes foundation and news content.
- Validates uploaded JPEG, PNG, GIF, and WebP images up to 2 MB.
- Records administrator actions and preserves a view of deleted foundation/news audit entries.

Legacy donation-status and deletion endpoints remain disabled so financial history can only change through the traceable ledger workflow.

## Stack and requirements

- PHP 7.4 or newer with `mysqli` and mysqlnd support
- MySQL 8.0+ or a compatible MariaDB release
- HTML, CSS, and vanilla JavaScript
- A writable `uploads/` directory for images
- A writable `data/` directory for invite-code and login-throttle state

Font Awesome is loaded from a CDN, so icons require network access in the browser. There is no Composer, Node.js, framework, or build step.

## Clean installation

1. Import [database/schema.sql](database/schema.sql) in phpMyAdmin. It creates the `dmssystem` database and every required table, index, and foreign key.
2. Configure database access with environment variables. Defaults are suitable for a typical local XAMPP installation:

   | Variable | Default | Purpose |
   | --- | --- | --- |
   | `DMS_DB_HOST` | `localhost` | Database host |
   | `DMS_DB_PORT` | `3306` | Database port |
   | `DMS_DB_USER` | `root` | Database user |
   | `DMS_DB_PASS` | empty | Database password |
   | `DMS_DB_NAME` | `dmssystem` | Database name |
   | `DMS_ADMIN_INVITE_CODE` | empty | Optional initial invite fallback |
   | `DMS_TRUST_PROXY_HEADERS` | `false` | Trust `X-Forwarded-For` only behind a configured trusted proxy |

   PowerShell example for the current terminal:

   ```powershell
   $env:DMS_DB_USER = 'dms_app'
   $env:DMS_DB_PASS = 'replace-with-a-local-secret'
   $env:DMS_DB_NAME = 'dmssystem'
   ```

3. Start the development server from the repository root:

   ```powershell
   php -S localhost:8000
   ```

4. Open <http://localhost:8000/index.php>, then visit <http://localhost:8000/signup.php> to create the first administrator.
5. Sign in at <http://localhost:8000/adlogin.php>. The first signup displays an invite code; an authenticated administrator can rotate it later from **Invite Code**.

Do not commit real credentials or runtime files from `data/` and `uploads/`.

## Upgrade an existing database to v1.1.1

Back up the database before running [database/migrations/v1.1.1.sql](database/migrations/v1.1.1.sql). The migration is intentionally one-time and will stop if conflicting data or an already-created index is present.

Check for conflicts first in phpMyAdmin:

```sql
SELECT adname, COUNT(*) AS records
FROM admin
GROUP BY adname
HAVING COUNT(*) > 1;

SELECT full_name, COUNT(*) AS records,
       GROUP_CONCAT(phone ORDER BY phone) AS phones
FROM donors
GROUP BY full_name
HAVING COUNT(*) > 1;

SELECT id, amount
FROM donations
WHERE amount NOT REGEXP '^[0-9]+([.][0-9]{1,2})?$';
```

Resolve every returned row without deleting donation history, then import the migration. It:

- enforces unique administrator and donor names;
- converts donation amounts from text to `DECIMAL(12,2)`; and
- makes creation timestamps immutable so status/content edits do not move records into a different reporting period.

The application also explicitly preserves creation timestamps during updates for compatibility with databases that have not yet run the migration.

## Verification

Run the dependency-free regression checks:

```powershell
php tests/run.php
```

Check PHP syntax across the project:

```powershell
$failed = $false
Get-ChildItem -Recurse -File -Filter *.php | ForEach-Object {
    php -l $_.FullName
    if ($LASTEXITCODE -ne 0) { $failed = $true }
}
if ($failed) { exit 1 }
```

Verify a configured live database:

```powershell
php database/verify_live_database.php
```

The database verifier checks required columns and administrator relationships, exercises donation/admin attribution, and rolls back every test write.

## Project layout

```text
├── app/
│   ├── AdminAuditService.php       Administrator identity and audit writes
│   ├── AuditViewService.php        Audit labels and record links
│   ├── DonationAdminService.php    Donation rules, filters, references, donors
│   ├── UploadService.php           Validated image storage
│   ├── helpers.php                 Input, output, redirect, and format helpers
│   └── partials.php                Shared public and administrator UI
├── database/
│   ├── migrations/v1.1.1.sql       Existing-install upgrade
│   ├── schema.sql                   Clean-install schema
│   └── verify_live_database.php     Rollback-only integration verification
├── css/ and js/                     Page styling and browser behavior
├── image/                           Versioned site imagery
├── uploads/                         Runtime uploads (ignored by Git)
├── data/                            Runtime security state (ignored by Git)
├── index.php, about.php, news.php   Public content
├── donate.php                       Donation form
└── addashboard.php, donation_*.php,
    donor.php, reports.php, audit_*  Administrator workflows
```

## Architecture and development rules

- Public routes are direct PHP files; there is no router.
- `config.php` initializes secure sessions, reads environment configuration, and creates strict `mysqli` connections.
- Shared services in `app/` own validation, uploads, audit behavior, donation rules, and reusable UI.
- Every state-changing form must use POST, administrator authorization where applicable, and the existing CSRF helpers.
- Database writes that span multiple records use transactions.
- Donation records are financial history and must not be deleted.
- Store display-safe donor snapshots on donations even when linking the normalized donor record.
- Keep runtime JSON and uploaded images out of version control.

## Security and production notes

Before production use:

- create a least-privilege database user instead of `root`;
- serve the application over HTTPS;
- keep `DMS_TRUST_PROXY_HEADERS=false` unless requests can only arrive through a trusted reverse proxy;
- restrict filesystem permissions on `data/` and `uploads/`;
- set PHP upload and request limits consistently with the 2 MB application limit;
- configure backups for the database and uploaded media; and
- replace placeholder contact/social links with real organization details.

The file-backed invite and IP-throttle stores are suitable for a single web server. Move that state to a shared database or cache before running multiple application servers.

## v1.1.1 highlights

- Centralized shared public layout, escaping, input, formatting, audit-link, and donation-filter logic.
- Replaced string-built ledger/donor searches with prepared statements.
- Fixed News expansion controls and server-confirmed donation success messaging.
- Normalized Myanmar phone numerals before donor matching.
- Made the first-admin signup flow work without a browser-required invite code.
- Preserved original creation timestamps across content and donation-status updates.
- Added clean-install and upgrade SQL plus dependency-free regression tests.
