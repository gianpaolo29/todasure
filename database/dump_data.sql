USE todasure_db;

-- Fix driver 1 toda_id
UPDATE drivers SET toda_id = 1 WHERE id = 1 AND toda_id IS NULL;

-- Create TODAs for barangays 2 and 3
INSERT IGNORE INTO todas (id, name, barangay_id) VALUES
(2, 'San Isidro TODA', 2),
(3, 'Santa Cruz TODA', 3);

-- 10 Passenger users
INSERT INTO users (username, email, password, first_name, last_name, phone, role) VALUES
('juan.delacruz', 'juan@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Juan', 'Dela Cruz', '09171234567', 'passenger'),
('maria.santos', 'maria.santos@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Maria', 'Santos', '09181234568', 'passenger'),
('pedro.reyes', 'pedro.reyes@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Pedro', 'Reyes', '09191234569', 'passenger'),
('ana.garcia', 'ana.garcia@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Ana', 'Garcia', '09201234570', 'passenger'),
('jose.mendoza', 'jose.mendoza@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Jose', 'Mendoza', '09211234571', 'passenger'),
('rosa.cruz', 'rosa.cruz@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Rosa', 'Cruz', '09221234572', 'passenger'),
('mark.torres', 'mark.torres@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Mark', 'Torres', '09231234573', 'passenger'),
('jenny.ramos', 'jenny.ramos@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Jenny', 'Ramos', '09241234574', 'passenger'),
('carlo.villanueva', 'carlo.v@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Carlo', 'Villanueva', '09251234575', 'passenger'),
('lyn.aquino', 'lyn.aquino@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Lyn', 'Aquino', '09261234576', 'passenger');

-- 5 Driver users
INSERT INTO users (username, email, password, first_name, last_name, phone, role) VALUES
('driver.rico', 'rico.driver@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Rico', 'Bautista', '09271111111', 'driver'),
('driver.danny', 'danny.driver@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Danny', 'Gonzales', '09272222222', 'driver'),
('driver.eman', 'eman.driver@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Emanuel', 'Lopez', '09273333333', 'driver'),
('driver.ben', 'ben.driver@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Benjamin', 'Rivera', '09274444444', 'driver'),
('driver.jay', 'jay.driver@gmail.com', '$2y$10$9jVcLLrnnZTyspe/OypgXeCxUhdYkqGXhMZTm5Ar9rDhyVluWjreW', 'Jay', 'Castillo', '09275555555', 'driver');

-- 5 Driver records
INSERT INTO drivers (user_id, toda_id, first_name, last_name, contact_number, address) VALUES
((SELECT id FROM users WHERE username='driver.rico'), 1, 'Rico', 'Bautista', '09271111111', 'Poblacion, Nasugbu'),
((SELECT id FROM users WHERE username='driver.danny'), 1, 'Danny', 'Gonzales', '09272222222', 'Poblacion, Nasugbu'),
((SELECT id FROM users WHERE username='driver.eman'), 2, 'Emanuel', 'Lopez', '09273333333', 'San Isidro, Nasugbu'),
((SELECT id FROM users WHERE username='driver.ben'), 2, 'Benjamin', 'Rivera', '09274444444', 'San Isidro, Nasugbu'),
((SELECT id FROM users WHERE username='driver.jay'), 3, 'Jay', 'Castillo', '09275555555', 'Santa Cruz, Nasugbu');

-- 5 Tricycles
INSERT INTO tricycles (driver_id, plate_number, body_number, color, model) VALUES
((SELECT id FROM drivers WHERE first_name='Rico'), 'ABC-1001', '001', 'Blue', 'Honda TMX'),
((SELECT id FROM drivers WHERE first_name='Danny'), 'ABC-1002', '002', 'Red', 'Honda TMX'),
((SELECT id FROM drivers WHERE first_name='Emanuel'), 'ABC-1003', '003', 'White', 'Yamaha YTX'),
((SELECT id FROM drivers WHERE first_name='Benjamin'), 'ABC-1004', '004', 'Green', 'Honda TMX'),
((SELECT id FROM drivers WHERE first_name='Jay'), 'ABC-1005', '005', 'Yellow', 'Suzuki Raider');

-- 20 Completed trips
INSERT INTO trips (tricycle_id, driver_id, fare_rate_id, origin, destination, distance_km, computed_fare, actual_fare, passenger_count, status, started_at, ended_at) VALUES
((SELECT id FROM tricycles WHERE plate_number='ABC-1001'), (SELECT id FROM drivers WHERE first_name='Rico'), 1, 'Poblacion Market', 'Barangay 5', 2.5, 22.50, 23.00, 2, 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 15 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1002'), (SELECT id FROM drivers WHERE first_name='Danny'), 1, 'Nasugbu Public Market', 'San Isidro', 3.1, 25.50, 25.00, 1, 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 20 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1003'), (SELECT id FROM drivers WHERE first_name='Emanuel'), 2, 'San Isidro Junction', 'Lumbangan', 4.0, 31.50, 32.00, 3, 'completed', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 25 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1004'), (SELECT id FROM drivers WHERE first_name='Benjamin'), 2, 'Wawa Beach Road', 'Poblacion', 5.2, 38.10, 40.00, 2, 'completed', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 30 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1005'), (SELECT id FROM drivers WHERE first_name='Jay'), 3, 'Santa Cruz Proper', 'Calayo', 3.8, 27.00, 27.00, 1, 'completed', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 18 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1001'), (SELECT id FROM drivers WHERE first_name='Rico'), 1, 'Barangay 1', 'Pantalan', 1.8, 19.00, 20.00, 2, 'completed', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 12 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1002'), (SELECT id FROM drivers WHERE first_name='Danny'), 1, 'Reparo', 'Poblacion', 2.2, 21.00, 21.00, 1, 'completed', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 14 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1003'), (SELECT id FROM drivers WHERE first_name='Emanuel'), 2, 'Latag', 'San Isidro', 3.5, 28.75, 30.00, 2, 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 22 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1004'), (SELECT id FROM drivers WHERE first_name='Benjamin'), 2, 'Bucana', 'Red Gate', 4.5, 34.25, 35.00, 1, 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 28 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1005'), (SELECT id FROM drivers WHERE first_name='Jay'), 3, 'Tumalim', 'Santa Cruz', 2.8, 22.00, 22.00, 3, 'completed', DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 16 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1001'), (SELECT id FROM drivers WHERE first_name='Rico'), 1, 'Barangay 3', 'Looc', 6.0, 40.00, 42.00, 2, 'completed', DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY) + INTERVAL 35 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1002'), (SELECT id FROM drivers WHERE first_name='Danny'), 1, 'Kaylaway', 'Poblacion', 3.0, 25.00, 25.00, 1, 'completed', DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY) + INTERVAL 19 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1003'), (SELECT id FROM drivers WHERE first_name='Emanuel'), 2, 'Dayap', 'San Isidro', 2.0, 20.50, 20.00, 2, 'completed', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY) + INTERVAL 13 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1004'), (SELECT id FROM drivers WHERE first_name='Benjamin'), 2, 'Bilaran', 'Natipuan', 5.5, 39.75, 40.00, 1, 'completed', DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY) + INTERVAL 32 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1005'), (SELECT id FROM drivers WHERE first_name='Jay'), 3, 'Papaya', 'Calayo', 4.2, 29.00, 30.00, 2, 'completed', DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY) + INTERVAL 26 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1001'), (SELECT id FROM drivers WHERE first_name='Rico'), 1, 'Poblacion', 'Malapad na Bato', 7.0, 45.00, 45.00, 1, 'completed', DATE_SUB(NOW(), INTERVAL 16 DAY), DATE_SUB(NOW(), INTERVAL 16 DAY) + INTERVAL 40 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1002'), (SELECT id FROM drivers WHERE first_name='Danny'), 1, 'Putat', 'Barangay 8', 1.5, 17.50, 18.00, 3, 'completed', DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY) + INTERVAL 10 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1003'), (SELECT id FROM drivers WHERE first_name='Emanuel'), 2, 'San Diego', 'Cogunan', 3.3, 26.65, 27.00, 2, 'completed', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY) + INTERVAL 21 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1004'), (SELECT id FROM drivers WHERE first_name='Benjamin'), 2, 'Catandaan', 'Bunducan', 4.8, 35.90, 36.00, 1, 'completed', DATE_SUB(NOW(), INTERVAL 22 DAY), DATE_SUB(NOW(), INTERVAL 22 DAY) + INTERVAL 29 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1005'), (SELECT id FROM drivers WHERE first_name='Jay'), 3, 'Sinala', 'Utod', 2.5, 20.50, 20.00, 2, 'completed', DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY) + INTERVAL 15 MINUTE);

-- 3 Overcharge trips
INSERT INTO trips (tricycle_id, driver_id, fare_rate_id, origin, destination, distance_km, computed_fare, actual_fare, passenger_count, status, started_at, ended_at) VALUES
((SELECT id FROM tricycles WHERE plate_number='ABC-1001'), (SELECT id FROM drivers WHERE first_name='Rico'), 1, 'Poblacion', 'Balaytigui', 3.0, 25.00, 35.00, 1, 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 20 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1003'), (SELECT id FROM drivers WHERE first_name='Emanuel'), 2, 'San Isidro', 'Aga', 4.0, 31.50, 45.00, 2, 'completed', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 25 MINUTE),
((SELECT id FROM tricycles WHERE plate_number='ABC-1005'), (SELECT id FROM drivers WHERE first_name='Jay'), 3, 'Santa Cruz', 'Wawa', 5.0, 33.00, 50.00, 1, 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 30 MINUTE);

-- 5 Violations
INSERT INTO violations (driver_id, violation_type, description, severity, status) VALUES
((SELECT id FROM drivers WHERE first_name='Rico'), 'fare_overcharge', 'Overcharged: PHP 35.00 vs computed PHP 25.00', 'moderate', 'pending'),
((SELECT id FROM drivers WHERE first_name='Emanuel'), 'fare_overcharge', 'Overcharged: PHP 45.00 vs computed PHP 31.50', 'moderate', 'pending'),
((SELECT id FROM drivers WHERE first_name='Jay'), 'fare_overcharge', 'Overcharged: PHP 50.00 vs computed PHP 33.00', 'major', 'confirmed'),
((SELECT id FROM drivers WHERE first_name='Danny'), 'complaint_based', 'Rude behavior reported by passenger', 'minor', 'pending'),
((SELECT id FROM drivers WHERE first_name='Benjamin'), 'unauthorized_route', 'Took unauthorized detour through private road', 'moderate', 'pending');

-- 5 Complaints
INSERT INTO complaints (tricycle_id, driver_id, passenger_name, passenger_contact, complaint_type, description, status) VALUES
((SELECT id FROM tricycles WHERE plate_number='ABC-1001'), (SELECT id FROM drivers WHERE first_name='Rico'), 'Juan Dela Cruz', '09171234567', 'overcharging', 'Charged PHP 35 for a PHP 25 trip from Poblacion to Balaytigui', 'pending'),
((SELECT id FROM tricycles WHERE plate_number='ABC-1002'), (SELECT id FROM drivers WHERE first_name='Danny'), 'Maria Santos', '09181234568', 'rude_behavior', 'Driver was rude and impatient during the ride', 'investigating'),
((SELECT id FROM tricycles WHERE plate_number='ABC-1003'), (SELECT id FROM drivers WHERE first_name='Emanuel'), 'Pedro Reyes', '09191234569', 'reckless_driving', 'Driver was speeding and making dangerous turns', 'pending'),
((SELECT id FROM tricycles WHERE plate_number='ABC-1004'), (SELECT id FROM drivers WHERE first_name='Benjamin'), 'Ana Garcia', '09201234570', 'refusal_of_service', 'Driver refused to take me saying destination was too far', 'resolved'),
((SELECT id FROM tricycles WHERE plate_number='ABC-1005'), (SELECT id FROM drivers WHERE first_name='Jay'), 'Jose Mendoza', '09211234571', 'overcharging', 'Asked for PHP 50 when the meter showed PHP 33', 'pending');

-- 5 Bookings
INSERT INTO bookings (passenger_id, pickup_address, pickup_lat, pickup_lng, dropoff_address, dropoff_lat, dropoff_lng, estimated_distance, estimated_fare, status, driver_id, tricycle_id) VALUES
((SELECT id FROM users WHERE username='juan.delacruz'), 'Poblacion Market', 14.0637, 120.6283, 'San Isidro Junction', 14.0550, 120.6350, 2.5, 22.50, 'completed', (SELECT id FROM drivers WHERE first_name='Rico'), (SELECT id FROM tricycles WHERE plate_number='ABC-1001')),
((SELECT id FROM users WHERE username='maria.santos'), 'Nasugbu Public Market', 14.0640, 120.6290, 'Barangay 5', 14.0580, 120.6310, 1.8, 19.00, 'completed', (SELECT id FROM drivers WHERE first_name='Danny'), (SELECT id FROM tricycles WHERE plate_number='ABC-1002')),
((SELECT id FROM users WHERE username='pedro.reyes'), 'Wawa Beach', 14.0420, 120.6180, 'Poblacion', 14.0637, 120.6283, 3.5, 28.75, 'completed', (SELECT id FROM drivers WHERE first_name='Emanuel'), (SELECT id FROM tricycles WHERE plate_number='ABC-1003')),
((SELECT id FROM users WHERE username='ana.garcia'), 'Latag', 14.0700, 120.6400, 'Santa Cruz', 14.0500, 120.6250, 4.0, 31.50, 'cancelled', NULL, NULL),
((SELECT id FROM users WHERE username='jose.mendoza'), 'Red Gate', 14.0750, 120.6450, 'Bucana', 14.0480, 120.6200, 5.0, 37.00, 'pending', NULL, NULL);

-- 10 Notifications
INSERT INTO notifications (role_target, type, title, message, link) VALUES
('admin', 'complaint', 'New Complaint Filed', 'overcharging - Charged PHP 35 for a PHP 25 trip', 'complaints.html'),
('admin', 'complaint', 'New Complaint Filed', 'rude_behavior - Driver was rude and impatient', 'complaints.html'),
('admin', 'complaint', 'New Complaint Filed', 'reckless_driving - Driver was speeding', 'complaints.html'),
('admin', 'violation', 'Fare Overcharge Detected', 'PHP 35.00 charged vs PHP 25.00 computed', 'violations.html'),
('admin', 'violation', 'Fare Overcharge Detected', 'PHP 45.00 charged vs PHP 31.50 computed', 'violations.html'),
('admin', 'violation', 'Fare Overcharge Detected', 'PHP 50.00 charged vs PHP 33.00 computed', 'violations.html'),
('admin', 'booking', 'New Ride Request', 'Poblacion Market to San Isidro Junction', 'trips.html'),
('admin', 'booking', 'New Ride Request', 'Wawa Beach to Poblacion', 'trips.html'),
('admin', 'booking', 'New Ride Request', 'Red Gate to Bucana', 'trips.html'),
('admin', 'complaint', 'New Complaint Filed', 'overcharging - Asked for PHP 50 when meter showed PHP 33', 'complaints.html');
