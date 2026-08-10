 -- Run this after importing database.sql through phpMyAdmin.
USE aiu_club_store;

INSERT INTO clubs (name, description, logo) VALUES
('Sports Club', 'Jerseys, caps, and match-day essentials.', NULL),
('Tech Club', 'Campus innovation, hackathons, and technology gear.', NULL),
('Drama Club', 'Creative performances and memorable shows.', NULL);

INSERT INTO products (club_id, name, price, image, stock, category) VALUES
(1, 'Sports Club T-Shirt', 1200.00, NULL, 25, 'Clothing'),
(1, 'AIU Club Cap', 800.00, NULL, 18, 'Accessories'),
(2, 'Tech Club Tote Bag', 700.00, NULL, 20, 'Accessories'),
(3, 'Drama Club Hoodie', 1800.00, NULL, 12, 'Clothing');

INSERT INTO product_sizes (product_id, size, stock) VALUES
(1, 'S', 8), (1, 'M', 10), (1, 'L', 7), (4, 'M', 6), (4, 'L', 6);

INSERT INTO events (club_id, title, description, venue, `date`, capacity, ticket_price) VALUES
(3, 'AIU Talent Night', 'An evening of music, drama, dance, and spoken word.', 'Main Auditorium', '2026-08-15 17:00:00', 150, 300.00),
(2, 'Campus Hackathon', 'A one-day student coding challenge and innovation workshop.', 'Innovation Lab', '2026-08-22 09:00:00', 60, 0.00),
(1, 'Interclub Finals', 'Support AIU teams in the interclub sports finals.', 'AIU Sports Ground', '2026-08-30 14:00:00', 300, 150.00);
