# AIU Student Club Store

AIU Student Club Store is a web application that enables university clubs to manage and sell merchandise, host events, and handle ticket bookings. It provides students with a centralized platform to browse club products, add items to a cart, check out securely, and book event tickets with unique codes. Club administrators and super admins can manage products, sizes, events, view orders, and generate reports through a dedicated dashboard.

## Key Features

- **User Registration & Authentication**: Secure student registration and login with hashed passwords using PHP's `password_hash()` and `password_verify()`.
- **Dynamic Content**: Clubs, products, and events are loaded dynamically from a MySQL database.
- **Shopping Cart**: Session-based cart allowing students to manage quantities and proceed to checkout.
- **Merchandise Checkout**: Complete order creation, payment recording, stock reduction, and order history with transaction safety (`FOR UPDATE` row locking).
- **Event Ticket Booking**: Capacity-aware ticket booking, unique ticket code generation (`AIU-{YEAR}-{HEX}`), and payment status tracking for both free and paid events.
- **Admin Dashboard**: Role-based admin area (`club_admin` and `super_admin`) with summary counts and quick links.
- **Product & Size Management**: CRUD for products and size-specific stock management (e.g., S, M, L).
- **Event Management**: CRUD for events including venue, capacity, and ticket pricing.
- **Order & Ticket Administration**: Update payment and order statuses for merchandise and tickets.
- **Reports**: Paid merchandise sales totals, event attendance statistics, and top 5 best-selling products.
- **Ticket Check-In**: Admin-only ticket validation with QR/code lookup and check-in status updates.
- **User & Club Management**: Super admin can manage user roles (`student`, `club_admin`, `super_admin`) and club CRUD.
- **Image Uploads**: Admin can upload product images (JPG, PNG, WebP, max 2MB) with client-side resizing and preview.
- **Responsive Design**: Mobile-friendly layout with CSS custom properties, breakpoints at 700px, and animated UI elements.

## Technologies Used

- **Backend**: PHP 7.x / 8.x
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (ES6)
- **Server**: Apache (via XAMPP)
- **Session Management**: PHP native sessions
- **Security**: Prepared statements, CSRF-safe forms, password hashing, role-based access control

## Installation Requirements

- **XAMPP** (or equivalent local PHP + MySQL + Apache stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2+
- Modern web browser (Chrome, Firefox, Edge, Safari)
- Minimum 5 MB of disk space for the project and uploads

## Installation & Setup

1. Start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Copy this project folder into `C:\xampp\htdocs\aiu-club-store`.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Create a new database named `aiu_club_store` (or import via phpMyAdmin UI).
5. Import `database.sql` to create all tables and constraints.
6. Import `seed_data.sql` to populate initial clubs, products, sizes, and events.
7. Verify `config/database.php` contains the correct MySQL credentials:
   - Host: `localhost`
   - Database: `aiu_club_store`
   - Username: `root`
   - Password: `` (empty for default XAMPP)
8. Open `http://localhost/aiu-club-store/index.php` in your browser.

## Basic Usage

### Student Flow
1. Register a new account at `register.php` or log in at `login.php`.
2. Browse clubs and products on the homepage or `shop.php`.
3. Add products to your cart with desired quantities.
4. Proceed to `cart.php` and click "Proceed to Checkout".
5. Confirm the order at `checkout.php` (stock is validated and reduced automatically).
6. Browse upcoming events at `events.php` and book tickets via `book_ticket.php`.
7. View your order history and tickets in `account.php`.

### Admin Flow
1. Log in with an account that has `role = club_admin` or `role = super_admin`.
2. Access the admin dashboard at `admin.php`.
3. Manage products and sizes via `admin_products.php` and `admin_sizes.php`.
4. Manage events via `admin_events.php` and `admin_events_edit.php`.
5. View and update orders/tickets at `admin_orders.php`.
6. View sales and attendance reports at `admin_reports.php`.
7. Validate tickets at `ticket_checkin.php`.
8. (Super admin only) Manage clubs at `admin_clubs.php` and users/roles at `admin_users.php`.

## Project Structure

```
aiu-club-store/
├── account.php                  # User account page (orders, tickets)
├── admin.php                    # Admin dashboard
├── admin_clubs.php              # Club CRUD (super admin)
├── admin_events.php             # Event CRUD
├── admin_events_edit.php        # Single event edit form
├── admin_orders.php             # Order & ticket status management
├── admin_product_image.php      # Product image upload
├── admin_products.php           # Product CRUD
├── admin_reports.php            # Sales & attendance reports
├── admin_size_delete.php        # Delete product size entry
├── admin_sizes.php              # Size-specific stock management
├── admin_users.php              # User roles management (super admin)
├── book_ticket.php              # Event ticket booking
├── cart.php                     # Shopping cart
├── checkout.php                 # Merchandise checkout & stock reduction
├── config/
│   └── database.php             # Database connection singleton
├── database.sql                 # Schema definition
├── events.php                   # Event listing page
├── includes/
│   ├── auth.php                 # Auth helpers & flash messages
│   ├── footer.php               # Shared footer
│   └── header.php               # Shared navbar & flash messages
├── index.php                    # Homepage (featured clubs)
├── login.php                    # Login handler
├── logout.php                   # Session destroy
├── main.js                      # Client-side demo & interactions
├── PROJECT_DOCUMENTATION.md     # Extended project docs
├── register.php                 # Registration handler
├── seed_data.sql                # Initial data
├── shop.php                     # Product catalog & add-to-cart
├── style.css                    # Global styles
├── TESTING_CHECKLIST.md         # Manual testing steps
└── uploads/                     # Product images
```

## Configuration Options

- **Database Credentials**: Edit `config/database.php` to change MySQL host, database name, username, or password.
- **Session Cart**: Cart data is stored in PHP sessions (`$_SESSION['cart']`). No persistent cart storage.
- **Payment Method**: Default payment method for checkout is "Pay on collection". Modify `checkout.php` to add additional payment gateways.
- **Ticket Code Prefix**: Ticket codes are generated as `AIU-{YEAR}-{HEX}` in `book_ticket.php`. Adjust the format there if needed.
- **Upload Limits**: Image uploads are limited to 2 MB and resized client-side to 800x800 in `admin_product_image.php`.
- **Role-Based Access**: Access control is enforced in `includes/auth.php` via `require_admin()`.

## Troubleshooting

- **"Access denied for user 'root'@'localhost'"**: Verify MySQL is running in XAMPP and the credentials in `config/database.php` are correct.
- **Blank page / white screen**: Ensure PHP errors are visible by checking `php.ini` error reporting or checking `error_log`. Common causes include missing tables or syntax errors.
- **Images not loading**: Confirm the `uploads/` directory exists and is writable. Check that product `image` paths in the database match filenames in `uploads/`.
- **Foreign key constraint errors on delete**: Products linked to existing orders cannot be deleted. Update or archive orders first, or remove the constraint if intentional.
- **Ticket booking fails with "already booked"**: Users may already have a ticket for that event. Check `tickets` table for duplicate `(event_id, user_id)` entries.
- **Cart items disappearing**: Sessions rely on browser cookies. If cookies are disabled or the session expires, cart data is lost.

## Contributing

1. Fork the repository and create a feature branch.
2. Follow the existing code style: procedural PHP with prepared statements, minimal inline CSS, and consistent indentation.
3. Test changes locally using the XAMPP setup described above.
4. Ensure all forms validate input server-side and use prepared statements to prevent SQL injection.
5. Submit a pull request with a clear description of changes.

## License

This project is provided as-is for educational purposes at AIU. No formal license is attached.
