# AIU Club Store Testing Checklist

Complete these tests before your presentation.

- Register a student account and log in with it.
- Add a product to the cart and submit checkout; confirm stock goes down and an order/payment record is created.
- Book a free event ticket; confirm a unique ticket code appears in **My Account**.
- Use phpMyAdmin to change a student role to `club_admin`, sign out, then sign in again.
- Open **Admin** and add a product, a product size, and an event.
- In **Orders & Payments**, mark a paid ticket as `paid`.
- Use **Ticket Check-in** with that ticket code; first check-in should succeed and the second should show it was already used.
- Check **Reports** to confirm reservations, attendance, and sales totals update.
- Try opening `admin.php` while logged out or as a student; access must be denied.
