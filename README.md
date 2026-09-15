# ParkEase — Smart Vehicle Parking Management System

A full-stack web app for managing a parking facility: vehicle check-in/check-out,
slot allocation, automatic fee calculation, and revenue reporting.

Built with **PHP (PDO) + MySQL + HTML/CSS/JS** 

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

## Security notes

- Passwords are hashed with bcrypt (`password_hash` / `password_verify`), never stored in plain text.
- Login is rate-limited: 5 failed attempts for the same username within 15 minutes
  temporarily blocks further attempts, which is logged and visible on the Activity Log page.



