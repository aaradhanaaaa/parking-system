# ParkEase — Smart Vehicle Parking Management System

A full-stack web app for managing a parking facility: vehicle check-in/check-out,
slot allocation, automatic fee calculation, and revenue reporting.

Built with **PHP (PDO) + MySQL + HTML/CSS/JS** — no frameworks, so every line is
easy to explain in a viva or interview.

## Features

- **Admin login** — session-based auth, passwords hashed with bcrypt (`password_hash`)
- **Vehicle entry** — auto-assigns the next free slot of the right type (car/bike)
- **Vehicle exit** — calculates parking duration and fee (with a configurable grace period)
- **Slot management** — add/remove slots, see live occupancy as a grid
- **Vehicle log** — searchable, filterable history of every entry/exit
- **Reports** — 7-day revenue chart, all-time totals, vehicle type split
- **Activity Log** — every login attempt (success/failure), logout, and new account
  registration is recorded with username, IP address, and timestamp, so you always
  know who's been signing in — and a banner on the dashboard flags any recent failed attempts
- **Sign-up page** — new admin accounts can be created from the login screen; every
  registration is logged too
- **Settings** — editable hourly rates, grace period, and password change

## Tech stack

| Layer     | Technology                     |
|-----------|---------------------------------|
| Frontend  | HTML5, CSS3 (custom, no framework), vanilla JS |
| Backend   | PHP 8 (PDO with prepared statements) |
| Database  | MySQL / MariaDB                |

## Project structure

```
parking-system/
├── config/db.php          # database connection
├── includes/
│   ├── auth_check.php     # guards protected pages
│   ├── header.php         # sidebar + topbar markup
│   └── footer.php
├── auth/
│   ├── login.php
│   └── logout.php
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── index.php               # dashboard
├── vehicle_entry.php
├── vehicle_exit.php
├── slots.php
├── vehicle_log.php
├── reports.php
├── activity_log.php        # login/logout audit trail
├── settings.php
├── sql/
│   ├── parking_system.sql  # schema + seed data (fresh installs)
│   └── add_activity_log.sql # migration for existing installs
```

## Setup (XAMPP / WAMP / LAMP)

1. Copy the `parking-system` folder into your server's web root
   (e.g. `htdocs/` for XAMPP).
2. Create the database: open phpMyAdmin (or the `mysql` CLI) and import
   `sql/parking_system.sql`. This creates the `parking_system` database,
   all tables, and seed data (20 slots, billing settings, one admin).
3. Open `config/db.php` and set `$DB_USER` / `$DB_PASS` to match your
   MySQL setup (defaults are `root` / empty password, which matches a
   fresh XAMPP install).
4. Visit `http://localhost/parking-system/auth/login.php` in your browser.
5. Log in with:
   - **Username:** `admin`
   - **Password:** `admin123`
6. Change the password immediately from the **Settings** page.

> **Already set this up before and just want the new Activity Log?**
> Open phpMyAdmin, select the `parking_system` database, go to Import,
> choose `sql/add_activity_log.sql`, and click Go — your existing data
> is untouched.

## Security notes

- Passwords are hashed with bcrypt (`password_hash` / `password_verify`), never stored in plain text.
- All database queries use PDO prepared statements — no raw string concatenation into SQL.
- Login is rate-limited: 5 failed attempts for the same username within 15 minutes
  temporarily blocks further attempts, which is logged and visible on the Activity Log page.

> **Heads up:** the sign-up page currently lets *anyone* who reaches it create an
> admin account with full access. That's fine for a local demo, but if you ever
> put this on a network others can reach, either remove the "Create an account"
> link from the login page or add an invite-code check in `auth/register.php`
> before the app is truly multi-user safe.

## Database design (for your viva)

- `slots` — every physical parking spot, its type (car/bike), and current status.
- `vehicles` — one row per visit: which slot, entry/exit time, computed duration
  and fee, and whether the vehicle is still parked or has exited.
- `settings` — a single-row table holding the hourly rates and grace period,
  so rates can change without touching code.
- `admins` — login credentials, password stored as a bcrypt hash (never plain text).

**Key logic to be ready to explain:**
- *Slot allocation*: on entry, the app picks the lowest-numbered `available`
  slot of the requested type, marks it `occupied`, and links it to the new
  `vehicles` row — all inside a database transaction so a slot is never
  double-booked.
- *Fee calculation*: `duration = exit_time - entry_time` in minutes. If the
  stay is under the grace period, the fee is ₹0; otherwise the duration is
  rounded up to the next full hour and multiplied by the per-hour rate.
- *SQL injection protection*: every query uses PDO prepared statements with
  bound parameters — no raw string concatenation into SQL.

## Possible extensions (good talking points if asked "what would you add?")

- Real hardware integration: an ESP32/Arduino + RFID or number-plate camera
  feeding entries into `vehicle_entry.php` automatically.
- Online pre-booking of a slot before arrival.
- SMS/email receipt on exit.
- Multi-branch support (multiple parking lots under one admin account).
