<div align="center">

# 🏨 SOLMBRA
**Hotel Management Made Elegant**

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)](https://sqlite.org)
[![Security](https://img.shields.io/badge/Security-CSRF%20%7C%20XSS%20%7C%20Rate%20Limit-success)](/)
[![Status](https://img.shields.io/badge/Status-Active-success)](/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

*A secure, full-stack local hotel management system built with vanilla PHP, SQLite, and a modern CSS design.*

</div>

---

## 📖 Table of Contents
- [About The Project](#-about-the-project)
- [Core Features](#-core-features)
- [Security Architecture](#-security-architecture)
- [Tech Stack](#-tech-stack)
- [Installation Guide](#-installation-guide)
- [Default Credentials](#-default-credentials)
- [Folder Structure](#-folder-structure)

---

## 🚀 About The Project

**SOLMBRA** is a lightweight, secure, and elegant web application designed for front-desk operations, hotel management, and reservation tracking. It is built without heavy frameworks, relying on pure PHP (backend) and Vanilla JavaScript (frontend) to demonstrate a solid understanding of core web technologies, relational databases, and modern UI/UX design.

> **Note:** This is a local project designed to run on Apache/XAMPP. It is not currently configured for live cloud hosting.

---

## ✨ Core Features

- 🔐 **Secure Authentication:** Full login system with hashed passwords and brute-force protection.
- 📊 **Live Dashboard:** Real-time occupancy stats, revenue calculations, and daily arrivals/departures.
- 📅 **Reservation Engine:** Create, edit, check-in, and check-out guests. Includes server-side **Double-Booking Prevention**.
- 🛏️ **Room Sync:** Room statuses (Available, Occupied, Maintenance) automatically sync based on active reservations.
- 💳 **Payment Ledger:** Track payments, methods, and statuses. Financial records are **append-only** (archived, never hard-deleted) for data integrity.
- 🔔 **Notification System:** In-app inbox for pending reservations and payments with read/dismiss persistence.
- 📥 **Export System:** Export any table (Rooms, Guests, Payments) to `.csv` / Excel format.
- 🛡️ **Role-Based Access Control (RBAC):** Backend checks ensure only Admins/Managers can delete records.

---

## 🛡️ Security Architecture

This project was built with security-first principles:
- **SQL Injection Prevention:** All database queries use **PDO Prepared Statements**.
- **XSS Protection:** All dynamic outputs are escaped using `htmlspecialchars()`.
- **CSRF Protection:** API state mutations require a valid CSRF token.
- **Rate Limiting:** Login attempts are limited (5 tries per 15 minutes) to prevent brute-forcing.
- **Database Isolation:** The `.htaccess` file blocks direct browser access to the `storage/solmbra.sqlite` file.

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|------------|
| **Frontend** | HTML5, CSS3 (Custom, Flexbox/Grid), Vanilla JS (ES6) |
| **Backend** | PHP 8.x (REST-like API architecture) |
| **Database** | SQLite 3 (Auto-migrating on first run) |
| **Icons** | FontAwesome 6 |
| **Fonts** | Google Fonts (DM Sans, Outfit) |

---

## ⚙️ Installation Guide

1. **Install XAMPP**
   Download and install [XAMPP](https://www.apachefriends.org/) (PHP 8.0+ required).

2. **Clone the Repository**
   Navigate to your XAMPP `htdocs` folder:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/harveyyase/solmbra-hotel-management.git solmbra
