# BAMStay (PHP / XAMPP)

This is the local PHP version of the BAMStay site. It serves plain PHP-rendered
HTML, local CSS, and vanilla JavaScript; no Node.js, frontend framework, or
build step is needed to run it. This PHP site serves property and bed photos as
regular files from `assets/images/`. The chatbot does not read or write the
existing `site_images` database table.

## XAMPP setup

1. Copy this whole `BAMStay` folder into your XAMPP web root. For the folder
   layout used by this project, that is:

   ```text
   C:\New folder\htdocs\bamstay\BAMStay
   ```

   This workspace uses `C:\New folder` as its XAMPP directory. If your
   installation uses a different directory, preserve the
   `bamstay\BAMStay` folders under its `htdocs` directory.
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Select **Import**, choose `database.sql` from this project folder, and run
   the import. The script creates the `bamstay` database and the `inquiries`
   table.
5. Open [`config.php`](./config.php). Its defaults match a standard local XAMPP
   setup (`127.0.0.1`, port `3306`, database `bamstay`, user `root`, blank
   password). If your MySQL credentials differ, change those values there.
6. Confirm `PDO_MySQL`, `cURL`, and `mbstring` are enabled in
   `C:\New folder\php\php.ini` for Apache. If you enable an extension, remove
   the leading `;` from its `extension=...` line, save the file, and restart
   Apache.
7. Import [`migrations/20261012_create_public_faqs.sql`](./migrations/20261012_create_public_faqs.sql)
   in phpMyAdmin. It adds a separate `public_faqs` table and seeds only
   information already shown on the public BAMStay website. It does not alter
   `inquiries`, `site_images`, or any existing table.
8. Import [`migrations/20261012_add_verified_room_and_facility_faqs.sql`](./migrations/20261012_add_verified_room_and_facility_faqs.sql)
   in phpMyAdmin. This adds the published room rates, room descriptions,
   listed bedspaces, and confirmed facility details to the chatbot's approved
   FAQ data. Price and bed-status answers are returned directly from these
   approved entries. It is safe to run again and updates these seeded answers
   to match the published website.
9. Import [`migrations/20261028_create_payment_management.sql`](./migrations/20261028_create_payment_management.sql)
   in phpMyAdmin. It adds the admin, tenant, rental obligation, payment, and
   payment audit tables without changing the existing inquiry, FAQ, or image
   tables. Tenant accounts start empty and are created by an administrator.
10. Import [`migrations/20261028_create_tenant_payment_submissions.sql`](./migrations/20261028_create_tenant_payment_submissions.sql)
   in phpMyAdmin. It adds a separate review queue for tenant-submitted cash
   and GCash payment requests.
11. Import [`migrations/20261028_create_room_management.sql`](./migrations/20261028_create_room_management.sql)
   in phpMyAdmin. It creates the editable room and bedspace catalogue and
   seeds the existing rooms and bedspaces. Existing tenant assignments are
   preserved and shown as occupied.
12. To display the official GCash QR in the tenant portal, place the
   administrator-provided image at
   `assets\images\gcash-qr.png`. Use the real payment QR only; do not use a
   generated or sample QR. Replacing the file updates the QR shown in the
   portal. Without this file, tenants can still submit cash requests, while
   GCash is unavailable.
13. Create the first administrator from PowerShell in the project folder:

   ```powershell
   & 'C:\New folder\php\php.exe' tools\create_admin.php admin@example.com
   ```

   Enter a unique email and a password of at least 12 characters when
   prompted. Keep this password private. Run the command again only when
   intentionally creating another administrator.
14. Open the site at:

   **http://localhost/bamstay/BAMStay/**

   The **Tenant Portal** link opens `login.php`. Administrators sign in with
   the account created above. From the dashboard, an administrator can create
   tenant accounts and share each generated temporary password with that
   tenant through a secure channel. Tenants must change that password at their
   first sign-in. Payment, rental balance, receipt, and rent-report data are
   stored in MySQL and scoped by the signed-in role. Tenant payment requests
   remain pending until an administrator verifies them. Accepting a request
   creates a confirmed payment and receipt; declining it leaves the rent
   balance unchanged. Administrators can edit room details and upload a room
   photo from **Rooms & Bedspaces → Edit room**. From **View room**, they can
   add/edit bedspaces and upload an optional photo for each bedspace; a
   bedspace without its own image uses the room photo. Uploads accept JPG,
   PNG, or WebP images up to 5 MB.
   PHP/Apache must have write permission to
   `assets\images\room-uploads`; updated room photos appear in the dashboard
   and tenant room view. Room details, status, rent, and bedspaces are edited
   from the Rooms & Bedspaces page; a bedspace with a tenant record cannot be
   deleted, but it can be marked for maintenance when vacant.

   The installed command-line PHP runtime is 8.2.12. The local chatbot endpoint
   has also been exercised through the running Apache/PHP site with PDO MySQL,
   cURL, and mbstring enabled.

### Chatbot response speed

Common questions already covered by the approved FAQ (including room details,
listed bedspaces, rates, facilities, and location) are answered directly from
MySQL without waiting for Ollama. Only questions that need a generated reply
use the local model. First-time or other generated replies can take longer
while Ollama loads the model. Close memory-heavy applications if it struggles;
the installed `qwen2.5:3b` model needs substantially more free memory than the
FAQ-only responses.

## Inquiry submissions

The inquiry form validates and normalizes input on the server, checks a
session-based CSRF token, confirms a requested bed is currently available,
then inserts the inquiry with a PDO prepared statement. A success message is
shown only after MySQL confirms that the row was inserted. If MySQL is stopped,
the database is missing, or saving fails, the form displays an error instead.

Submitted inquiries are available in phpMyAdmin under
`bamstay.inquiries`. Do not put photos or other image data in this table.
Room, bedspace, facility, and gallery content is structured in
[`data/site.php`](./data/site.php) and can later be loaded from a database
without changing the page templates.

## BAMStay Assistant (local Ollama)

The public floating chat widget sends requests only to `api/chat.php`.
PHP checks the session CSRF token, rate limit, and input sizes; retrieves only
approved and active answers from `bamstay.public_faqs`; and forwards the
approved facts and visitor message to Ollama at `127.0.0.1:11434`. The browser
never connects to Ollama, and the chatbot never reads inquiry or image rows.
Visitor messages are not saved in MySQL. The endpoint uses the existing PDO
settings in `config.php` and the installed `qwen2.5:3b` model.

### Install and start Ollama

1. Install Ollama for Windows from [ollama.com/download/windows](https://ollama.com/download/windows)
   if it is not already installed.
2. In PowerShell, download the free model. If the `ollama` command is already
   on your PATH, run:

   ```powershell
   ollama pull qwen2.5:3b
   ```

   Otherwise, use the Windows install location:

   ```powershell
   $ollama = Join-Path $env:LOCALAPPDATA 'Programs\Ollama\ollama.exe'
   & $ollama pull qwen2.5:3b
   ```

3. Confirm the model is listed (`ollama list`, or `& $ollama list`).
4. Leave Ollama running. If the installer did not start it, run `ollama serve`
   in a separate PowerShell window (`& $ollama serve` when using the full path).
   Its local API should answer at
   `http://127.0.0.1:11434`. Do not forward or expose port `11434` to the
   internet.
5. Open the BAMStay site and use the round assistant button at the bottom-right.

The first response can take longer while Ollama loads the model into memory.
The server gives Ollama up to 90 seconds. The machine must have enough free RAM
for the model and XAMPP's Apache/MySQL processes together.

### Approved FAQ maintenance

The migration starts with public facts already displayed by the website.
Private, unconfirmed details—such as phone numbers, exact rental terms,
payment steps, and house rules—are intentionally not seeded. If an answer is
missing, the assistant directs visitors to the inquiry form rather than
guessing.

To add or change an answer, edit `public_faqs` in phpMyAdmin only after the
BAMStay administrator approves the wording. Set `is_approved = 1` and
`is_active = 1` for public answers; drafts should remain unapproved or
inactive. The web endpoint runs SELECT queries only and cannot edit this table.

The FAQ seed includes the ₱1,800 monthly rate, room details, and bedspace
statuses published for Bistro Bam Dormitories. The assistant answers these approved
facts directly. It does not claim that an inquiry reserves a bed. Payment
procedures, house rules, and contact details are not added because the project
does not provide those details. The web endpoint runs SELECT queries only and cannot edit this table.

To roll back the chatbot FAQ data, first export/backup `public_faqs` if its
contents matter, then run `DROP TABLE bamstay.public_faqs;` in phpMyAdmin.
This removes only chatbot FAQ data; it does not affect inquiries or images.
The chatbot will show an unavailable message until the migration is re-applied.

### Troubleshooting

- **“cannot reach its approved information”**: start MySQL, check the values in
  `config.php`, and import the FAQ migration.
- **“Ollama is unavailable”**: start Ollama and test `http://127.0.0.1:11434/api/tags`
  locally. Check that `qwen2.5:3b` appears in `ollama list`.
- **Model fails to load or times out**: close memory-heavy applications and
  check available RAM. Ollama logs show local model-load errors.
- **Chat says no approved information**: add an administrator-approved entry
  in `public_faqs`; do not ask the model to guess missing policy details.
- **Chat session expired**: reload the website so it can issue a current CSRF
  token, then reopen the chat.

### Local development vs public hosting

This integration is for a local XAMPP installation. A public web host cannot
reach Ollama on your personal computer's `127.0.0.1`; on the host, that address
means the host itself. For a public deployment, run Ollama on a server you
control and let the PHP server call it over a private network/VPN, or host the
PHP site and Ollama together on a suitably sized private server. Restrict the
Ollama port to the PHP server, use TLS for public site traffic, and use a
least-privilege MySQL account. No paid AI API is required, but public serving
requires your own server resources and operations.

### Scope and authentication

The remaining project contains the public `index.php` site and its inquiry
form; it has no login, registration, tenant pages, admin pages, or role-based
access-control files. This chatbot is included once on that public page and
does not create, replace, or bypass an authentication system. No private
inquiry, tenant, payment-history, or password data is queried.

## Project layout

- `index.php` handles requests, server-side validation, and form submissions.
- `data/site.php` holds rooms, bedspaces, rates, facilities, and gallery data.
- `partials/` contains the page sections, navigation, footer, and dialogs.
- `assets/css/` contains the site stylesheet and the bundled compiled styles
  matching the current BAMStay design.
- `assets/js/site.js` implements the responsive menu, room carousel and modal,
  bed selection, inquiry preview, and continuous gallery carousel.
- `assets/images/` contains the existing property and bed photos.
- `database.sql` creates the MySQL database and inquiry table.
- `migrations/20261012_create_public_faqs.sql` adds the isolated, approved FAQ table.
- `api/chat.php` validates and rate-limits chat requests, reads public FAQs,
  and calls the local Ollama model.
- `partials/chatbot.php` and `assets/js/chatbot.js` provide the public chat
  interface without storing messages.
