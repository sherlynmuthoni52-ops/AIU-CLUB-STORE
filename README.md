# AIU Student Club Store

## Run locally with XAMPP

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this project folder into `C:\xampp\htdocs\aiu-club-store`.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Import `database.sql`, then import `seed_data.sql`.
5. Open `http://localhost/aiu-club-store/index.php`.

## Current working features

- Student registration and login using hashed passwords.
- Dynamic clubs, products, and events loaded from MySQL.
- Session shopping cart and merchandise checkout.
- Merchandise orders, payments, stock reduction, and order history.
- Event ticket booking, capacity checks, unique ticket codes, and ticket history.

## Next build step

Create administrator CRUD pages for products, product sizes, and events. Change a registered user's `role` to `club_admin` in phpMyAdmin before testing the administrator area.
