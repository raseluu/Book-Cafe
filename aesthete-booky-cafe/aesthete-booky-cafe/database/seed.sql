-- Seed Data

-- Admin User (Password: rasel123)
-- We'll handle admin creation in the PHP seeder to ensure hash consistency, 
-- but let's re-insert the content with better images.

-- Clear content tables first (handled by schema reset usually, but safe to delete here if just re-seeding)
DELETE FROM books;
DELETE FROM menu_items;
DELETE FROM events;

INSERT INTO books (title, author, price, image, description) VALUES
('Pother Pauchali', 'Bibhutibhushan Bandyopadhyay', 450.00, 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&q=80&w=800', 'A classic masterpiece of Bengali literature, portraying rural life with unmatched depth.'),
('The Namesake', 'Jhumpa Lahiri', 650.00, 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&q=80&w=800', 'A poignant story of the Ganguli family and the immigrant experience.'),
('Shesher Kobita', 'Rabindranath Tagore', 350.00, 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&q=80&w=800', 'The Last Poem - a romantic novel by Tagore that defies convention.'),
('The Great Gatsby', 'F. Scott Fitzgerald', 500.00, 'https://images.unsplash.com/photo-1541963463532-d68292c34b19?auto=format&fit=crop&q=80&w=800', 'A tale of money, love, and ambition in the Jazz Age.'),
('Norwegian Wood', 'Haruki Murakami', 700.00, 'https://images.unsplash.com/photo-1629196914375-f7e48f477b6d?auto=format&fit=crop&q=80&w=800', 'A nostalgic story of loss and burgeoning sexuality.');


INSERT INTO menu_items (name, category, price, image, description) VALUES
('Masala Chai', 'Tea', 120.00, 'https://images.unsplash.com/photo-1561336313-0bd5e0b27ec8?auto=format&fit=crop&q=80&w=800', 'Traditional Bangladeshi spiced tea brewed to perfection.'),
('Artisan Cold Coffee', 'Coffee', 180.00, 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&q=80&w=800', 'Chilled coffee served with a scoop of vanilla ice cream.'),
('Classic Shingara', 'Snacks', 30.00, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&q=80&w=800', 'Crispy fried pastry stuffed with spiced potatoes and peanuts.'),
('Red Velvet Cake', 'Pastry', 250.00, 'https://images.unsplash.com/photo-1586788680434-30d32443d858?auto=format&fit=crop&q=80&w=800', 'Rich and smooth layer cake with cream cheese frosting.'),
('Chicken Sandwich', 'Snacks', 150.00, 'https://images.unsplash.com/photo-1521390188846-e2a3a97453a0?auto=format&fit=crop&q=80&w=800', 'Grilled chicken breast with fresh lettuce and mayo.');


INSERT INTO events (title, date, location, image, description) VALUES
('Pohela Boishakh Celebration', '2024-04-14 10:00:00', 'Aesthete Book Cafe, Dhaka', 'https://images.unsplash.com/photo-1558253196-03f6f15777df?auto=format&fit=crop&q=80&w=800', 'Celebrating the Bengali New Year with traditional music, panta ilish, and books.'),
('Book Reading: Himu', '2024-05-20 17:00:00', 'Aesthete Book Cafe, Chattogram', 'https://images.unsplash.com/photo-1526721940322-10fb6e3ae94a?auto=format&fit=crop&q=80&w=800', 'An evening dedicated to Humayun Ahmeds Himu series with guest readers.');
