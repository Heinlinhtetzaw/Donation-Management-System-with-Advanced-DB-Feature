# Copilot Instructions for Donation Management System

This repository is a small PHP-based charity donation web app. There is no framework, build system or package manager; everything is plain PHP/HTML/CSS with a MySQL database.

## Big picture

1. **Public pages** – `index.php`, `about.php`, `news.php`, `donate.php` display information and let visitors submit donations.
2. **Admin area** – a collection of scripts prefixed with `ad`/`add` plus `donor.php`, `addfoundation.php`, `addnews.php`, `adddonationstatus.php`, etc. They share a sidebar and per‑page CSS in `css/`.
3. **CRUD helpers** – every mutation occurs in a separate processing script (`insert_foundation.php`, `insert_news.php`, `insert_donation.php`, `delete_*.php`, `update_payment_status.php`).
4. **Database** – single MySQL schema named `dmssystem`. Key tables inferred from the code:
   * `foundations` (`fid`, `image_path`, `fname`, `description`, `intro`)
   * `news` (`nid`, `image_path`, `title`, `content`)
   * `donations` (`id`, `donor_name`, `address`, `phone`, `amount`, `foundation_id`, `payment_method`, `payment_status`, `created_at`)
   * `admin` (`adname`, `adpassword`)

   Connection parameters are hard‑coded to `localhost`, `root`/`''`.

5. **Data flow** – front‑end forms `POST` to insert scripts; donation form additionally validates Myanmar numerals and converts them on the server (see `insert_donation.php`). “Delete” operations use `GET` with an `id` query parameter and redirect back to the listing page.

## Project conventions

* Every PHP page starts by opening a `mysqli` connection and closing it at the end. Copy the snippet from existing files.
* Inline SQL is used everywhere; most mutations use prepared statements with `bind_param`, reads use `$conn->query()` and `fetch_assoc()` loops.
* Dynamic values echoed into HTML are wrapped with `htmlspecialchars()`.
* Related CSS files live in `css/` and are named after the page (e.g. `donor.css` for `donor.php`, `addashb.css` for `addashboard.php`). If you add a new page, add a matching stylesheet and include it in the `<head>`.
* Static assets:
  * Images in `image/` (logo etc.)
  * Uploaded files saved in `uploads/` (scripts create this directory if it doesn't exist)
  * JavaScript in `js/` – only `news.js` currently; other scripts are inline.
* The navigation bar on public pages and the sidebar on admin pages are constructed manually; there’s no templating system.
* Front‑end form labels sometimes use Myanmar text; phone number validation allows both Myanmar and Arabic numerals (see `donate.php` and `insert_donation.php`).
* Redirects after successful inserts use `header("location:…")` then `exit();`.

## Developer workflow

* **Running the app:** place the repository in the document root of a PHP‑enabled server such as XAMPP/WAMP and browse to `http://localhost/DMSproject/`. Create the `dmssystem` database and tables manually; there are no migration scripts.
* **No build/test steps** – editing PHP/CSS/JS and refreshing the browser is all that’s required.
* **Logging in:** `adlogin.php` posts to `login.php`; the credentials are compared against the `admin` table and stored in `$_SESSION`. Admin pages do _not_ currently check session state (you’ll need to add checks if modifying authentication).

## Patterns to copy

1. **Query loop**
   ```php
   $sql = "SELECT ... FROM ...";
   $result = $conn->query($sql);
   if ($result->num_rows > 0) {
       while ($row = $result->fetch_assoc()) {
           echo '<tr>...'.htmlspecialchars($row['foo']).'...</tr>';
       }
   }
   ```

2. **Prepared insert**
   ```php
   $stmt = $conn->prepare("INSERT INTO ... VALUES (?,?,?,?,?)");
   $stmt->bind_param("ssiss", $var1, $var2, ...);
   $stmt->execute();
   ```

3. **Delete handler**
   ```php
   if (isset($_GET['id'])) {
       $stmt = $conn->prepare("DELETE FROM table WHERE id = ?");
       $stmt->bind_param("i", $_GET['id']);
       $stmt->execute();
   }
   header('Location: parent.php');
   exit();
   ```

4. **File upload** (see `insert_foundation.php` and `insert_news.php`): ensure `enctype="multipart/form-data"` on forms, move the file to `uploads/`, insert the path into the database.

5. **Myanmar number handling** – donation pages convert digits with a map before inserting and validate 11‑digit phone numbers with a regex.

## External dependencies

* **Font Awesome** via CDN for icons.
* **SweetAlert2** included on `donate.php` for the confirmation dialog.
* No Composer/npm; all dependencies are either built into PHP or fetched from CDNs in the HTML.

## When writing new code

* Keep the same style: mix PHP and HTML in a single file, place PHP logic at the top, HTML below.
* Use procedural `mysqli` functions; do not introduce PDO or object‑oriented frameworks unless explicitly refactored across the project.
* Match naming conventions (table and column names, CSS filenames, sidebar links).
* Add any new SQL columns or tables to the database manually and update all affected pages.
* Preserve the existing character encoding (`UTF-8`) and include the standard `<meta>` tags in new files.

## What *not* to do

* Do not assume a routing framework – pages are referenced directly by filename.
* Avoid altering database credentials; they are hardcoded everywhere.
* Do not introduce namespaces, autoloaders, or Composer dependencies unless you refactor the whole codebase.

---

> ℹ️ **Questions or missing information?**
> This document covers every pattern currently present. If something seems unclear (for example the exact schema of `donations`), ask for more details or inspect other PHP files for similar queries.

