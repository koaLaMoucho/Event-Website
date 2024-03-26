DROP SCHEMA IF EXISTS lbaw23105 CASCADE;
CREATE SCHEMA IF NOT EXISTS lbaw23105;
SET search_path TO lbaw23105;


-- Drop Tables If They Exist
DROP TABLE IF EXISTS Notification_;
DROP TABLE IF EXISTS Rating;
DROP TABLE IF EXISTS Report;
DROP TABLE IF EXISTS Comment_;
DROP TABLE IF EXISTS TicketInstance;
DROP TABLE IF EXISTS TicketType;
DROP TABLE IF EXISTS FAQ;
DROP TABLE IF EXISTS Event_;
DROP TABLE IF EXISTS TicketOrder;
DROP TABLE IF EXISTS UserLikes;
DROP TABLE IF EXISTS users;


-- Drop Type If It Exists
DROP TYPE IF EXISTS NotificationType;

-- Types
CREATE TYPE NotificationType AS ENUM ('Comment', 'Report', 'Event');

-- Tables
CREATE TABLE users (
   user_id SERIAL PRIMARY KEY,
   email TEXT NOT NULL UNIQUE,
   name TEXT NOT NULL,
   password TEXT NOT NULL,
   phone_number TEXT NOT NULL UNIQUE,
   is_admin BOOLEAN NOT NULL DEFAULT FALSE,
   active BOOLEAN NOT NULL DEFAULT TRUE,
   temporary BOOLEAN NOT NULL DEFAULT FALSE,
   remember_token VARCHAR,
   profile_image VARCHAR
);

CREATE TABLE password_reset_tokens (
   email TEXT PRIMARY KEY,
   token TEXT,
   created_at TIMESTAMP
);


CREATE TABLE Event_ (
   event_id SERIAL PRIMARY KEY,
   name TEXT NOT NULL,
   location TEXT NOT NULL,
   description TEXT,
   private BOOLEAN NOT NULL DEFAULT TRUE,
   start_timestamp TIMESTAMP NOT NULL,
   end_timestamp TIMESTAMP NOT NULL CHECK (start_timestamp < end_timestamp),
   creator_id INT NOT NULL REFERENCES users (user_id) ON UPDATE CASCADE
);

CREATE TABLE Comment_ (
   comment_id SERIAL PRIMARY KEY,
   text TEXT,
   media BYTEA,
   private BOOLEAN NOT NULL DEFAULT FALSE,
   event_id INT REFERENCES Event_ (event_id) ON UPDATE CASCADE,
   author_id INT REFERENCES users (user_id),
   likes INT DEFAULT 0,
   CHECK (text IS NOT NULL OR media IS NOT NULL)
);

CREATE TABLE Rating (
   rating_id SERIAL PRIMARY KEY,
   rating INT NOT NULL,
   event_id INT NOT NULL REFERENCES Event_ (event_id) ON UPDATE CASCADE,
   author_id INT NOT NULL REFERENCES users (user_id)
);

CREATE TABLE Report (
   report_id SERIAL PRIMARY KEY,
   Type TEXT NOT NULL,
   comment_id INT NOT NULL REFERENCES Comment_ (comment_id) ON UPDATE CASCADE,
   author_id INT NOT NULL REFERENCES users (user_id)
);

CREATE TABLE TicketType (
   ticket_type_id SERIAL PRIMARY KEY,
   name TEXT NOT NULL,
   stock INT NOT NULL CHECK (stock >= 0),
   description TEXT NOT NULL,
   private BOOLEAN NOT NULL DEFAULT TRUE,
   person_buying_limit INT NOT NULL CHECK (person_buying_limit > 0),
   start_timestamp TIMESTAMP NOT NULL,
   end_timestamp TIMESTAMP NOT NULL CHECK (start_timestamp < end_timestamp),
   price NUMERIC NOT NULL DEFAULT 0,
   event_id INT NOT NULL REFERENCES Event_(event_id) ON UPDATE CASCADE
);

CREATE TABLE TicketOrder (
   order_id SERIAL PRIMARY KEY,
   timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   buyer_id INT NOT NULL REFERENCES users (user_id) ON UPDATE CASCADE
);

CREATE TABLE TicketInstance (
   ticket_instance_id SERIAL PRIMARY KEY,
   ticket_type_id INT NOT NULL REFERENCES TicketType (ticket_type_id) ON UPDATE CASCADE,
   order_id INT NOT NULL REFERENCES TicketOrder(order_id) ON UPDATE CASCADE,
   qr_code_path TEXT
);

          
CREATE TABLE FAQ (
   faq_id SERIAL PRIMARY KEY,
   question TEXT NOT NULL,
   answer TEXT NOT NULL
);


CREATE TABLE Notification_ (
   notification_id SERIAL PRIMARY KEY,
   timestamp TIMESTAMP NOT NULL CHECK (timestamp <= NOW()),
   notified_user INTEGER NOT NULL REFERENCES users (user_id) ON UPDATE CASCADE,
   event_id INT REFERENCES Event_(event_id) ON UPDATE CASCADE,
   comment_id INT REFERENCES Comment_(comment_id) ON UPDATE CASCADE,
   report_id INT REFERENCES Report(report_id) ON UPDATE CASCADE,
   viewed BOOLEAN NOT NULL DEFAULT FALSE,
   notification_type NotificationType NOT NULL,
   CHECK (
    (notification_type = 'Event' AND event_id IS NOT NULL AND comment_id IS NULL AND report_id IS NULL) OR
    (notification_type = 'Comment' AND event_id IS NOT NULL AND comment_id IS NOT NULL AND report_id IS NULL) OR
    (notification_type = 'Report' AND event_id IS NULL AND comment_id IS NULL AND report_id IS NOT NULL)
  )
);

CREATE TABLE EventImage (
   event_image_id SERIAL PRIMARY KEY,
   event_id INT NOT NULL REFERENCES Event_ (event_id) ON UPDATE CASCADE,
   image_path VARCHAR NOT NULL
);

CREATE TABLE UserLikes (
   user_id INT REFERENCES users (user_id) ON UPDATE CASCADE,
   comment_id INT REFERENCES Comment_ (comment_id) ON UPDATE CASCADE,
   PRIMARY KEY (user_id, comment_id)
);


CREATE INDEX start_timestamp_event ON Event_ USING btree (start_timestamp);

CREATE INDEX notified_user_notification ON Notification_ USING btree (notified_user);

CREATE INDEX event_id_ticket_type ON TicketType USING btree (event_id);

ALTER TABLE Event_
ADD COLUMN tsvectors TSVECTOR;

CREATE OR REPLACE FUNCTION event_search_update() RETURNS TRIGGER AS $$
BEGIN
  IF TG_OP = 'INSERT' THEN
    NEW.tsvectors = (
      setweight(to_tsvector('english', NEW.name), 'A') ||
      setweight(to_tsvector('english', NEW.description), 'B') ||
      setweight(to_tsvector('english', NEW.location), 'C')
    );
  END IF;

  IF TG_OP = 'UPDATE' THEN
    IF (NEW.name <> OLD.name OR
        NEW.description <> OLD.description OR
         NEW.location <> OLD.location) THEN

      NEW.tsvectors = (
        setweight(to_tsvector('english', NEW.name), 'A') ||
        setweight(to_tsvector('english', NEW.description), 'B') ||
        setweight(to_tsvector('english', NEW.location), 'D')
      );

    END IF;
  END IF;



  RETURN NEW;
END $$ LANGUAGE plpgsql;



CREATE TRIGGER event_search_update

BEFORE INSERT OR UPDATE ON Event_
FOR EACH ROW
EXECUTE PROCEDURE event_search_update();
CREATE INDEX event_text_search_idx ON Event_ USING GIN (tsvectors);

CREATE OR REPLACE FUNCTION send_comment_notification()
RETURNS TRIGGER AS $$
BEGIN
  -- Insert a new notification for the event owner
  INSERT INTO Notification_ (notified_user, event_id, comment_id, notification_type, timestamp)
  VALUES ((SELECT creator_id FROM Event_ WHERE event_id = NEW.event_id), NEW.event_id, NEW.comment_id, 'Comment', NOW());

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER send_comment_notification_trigger
AFTER INSERT ON Comment_
FOR EACH ROW
EXECUTE FUNCTION send_comment_notification();

CREATE OR REPLACE FUNCTION send_event_notification()
RETURNS TRIGGER AS $$
BEGIN
  -- Insert a new notification for each user who bought a ticket to the event
  INSERT INTO Notification_ (notified_user, event_id, notification_type, timestamp)
  SELECT DISTINCT
         u.user_id,
         NEW.event_id,
         'Event'::NotificationType,
         NOW()
  FROM TicketInstance ti
  JOIN TicketType tt ON ti.ticket_type_id = tt.ticket_type_id
  JOIN TicketOrder tos ON ti.order_id = tos.order_id
  JOIN users u ON u.user_id = tos.buyer_id
  WHERE tt.event_id = NEW.event_id;

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;



CREATE TRIGGER send_event_notification_trigger
AFTER UPDATE OF location, description, start_timestamp, end_timestamp
ON Event_
FOR EACH ROW
EXECUTE FUNCTION send_event_notification();

CREATE OR REPLACE FUNCTION send_report_notification()
RETURNS TRIGGER AS $$
BEGIN
  -- Insert a new 'Report' type notification for every user with isAdmin = TRUE
  IF NEW.report_id IS NOT NULL THEN
    INSERT INTO Notification_ (notified_user, report_id, notification_type, timestamp)
    SELECT user_id, NEW.report_id, 'Report'::NotificationType, NOW()
    FROM users
    WHERE is_admin = TRUE;
  END IF;

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER send_report_notification_trigger
AFTER INSERT ON Report
FOR EACH ROW
EXECUTE FUNCTION send_report_notification();

CREATE OR REPLACE FUNCTION update_ticket_stock()
RETURNS TRIGGER AS $$
BEGIN
  IF TG_OP = 'INSERT' THEN
    UPDATE TicketType
    SET stock = stock - 1
    WHERE ticket_type_id = NEW.ticket_type_id;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER ticket_stock_trigger
AFTER INSERT ON TicketInstance
FOR EACH ROW
EXECUTE FUNCTION update_ticket_stock();

CREATE OR REPLACE FUNCTION check_duplicate_report()
RETURNS TRIGGER AS $$
BEGIN
  IF EXISTS (
    SELECT 1
    FROM Report
    WHERE author_id = NEW.author_id AND comment_id = NEW.comment_id
  ) THEN
    RAISE EXCEPTION 'User has already reported this comment';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER check_duplicate_report_trigger
BEFORE INSERT ON Report
FOR EACH ROW
EXECUTE FUNCTION check_duplicate_report();

-- Create a trigger after insert on userlikes
CREATE OR REPLACE FUNCTION increment_comment_likes()
RETURNS TRIGGER AS $$
BEGIN
   
    UPDATE comment_
    SET likes = likes + 1
    WHERE comment_id = NEW.comment_id;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER increment_comment_likes_trigger
AFTER INSERT ON userlikes
FOR EACH ROW
EXECUTE FUNCTION increment_comment_likes();

CREATE OR REPLACE FUNCTION decrement_comment_likes()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE comment_
    SET likes = likes - 1
    WHERE comment_id = OLD.comment_id;

    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

-- Create a trigger to decrement likes after deletion in userlikes
CREATE TRIGGER decrement_comment_likes_trigger
AFTER DELETE ON userlikes
FOR EACH ROW
EXECUTE FUNCTION decrement_comment_likes();




-- Inserts for Users
INSERT INTO users (email, name, password, phone_number,  is_admin, active, temporary) 
VALUES 
  ('danielmc2116@gmail.com', 'Daniel', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', '913756968', TRUE, TRUE, FALSE),
  ('user1@example.com', 'John Doe', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', '1234567890',  FALSE, TRUE, FALSE),
  ('user2@example.com', 'Jane Smith', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', '9876543210',  FALSE, TRUE, FALSE),
  ('user3@example.com', 'Bob Johnson', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', '5551231567', FALSE, TRUE, FALSE),
  ('user4@example.com', 'Alice Brown', 'password4', '7890123456',  FALSE, TRUE, FALSE),
  ('user5@example.com', 'Charlie Davis', 'password5', '3216149870',  FALSE, TRUE, FALSE),
  ('user6@example.com', 'David Wilson', 'password6', '6547810123',  FALSE, TRUE, FALSE),
  ('user7@example.com', 'Eva Rodriguez', 'password7', '7810123456',  FALSE, TRUE, FALSE),
  ('user8@example.com', 'Frank Garcia', 'password8', '9871543210',  FALSE, TRUE, FALSE),
  ('user9@example.com', 'Grace Miller', 'password9', '1231567890',  FALSE, TRUE, FALSE),
  ('user10@example.com', 'Henry Lee', 'password10', '5551134567',  FALSE, TRUE, FALSE),
  ('admin@example.com', 'Admin User', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', '1212223333',  TRUE, TRUE, FALSE),
  ('user11@example.com', 'Isabel Lopez', 'password11', '7178889999',  FALSE, TRUE, FALSE),
  ('user12@example.com', 'Jack Turner', 'password12', '4425556666',  FALSE, TRUE, FALSE),
  ('user13@example.com', 'Kelly White', 'password13', '2233334444',  FALSE, TRUE, FALSE),
  ('user14@example.com', 'Liam Anderson', 'password14', '1667778888',  FALSE, TRUE, FALSE),
  ('user15@example.com', 'Mia Harris', 'password15', '3331445555',  FALSE, TRUE, FALSE),
  ('user16@example.com', 'Nathan Moore', 'password16', '9190001111',  FALSE, TRUE, FALSE),
  ('user17@example.com', 'Olivia Taylor', 'password17', '2112223333',  FALSE, TRUE, FALSE),
  ('user18@example.com', 'Peter Martin', 'password18', '8189990000',  FALSE, TRUE, FALSE),
  ('user19@example.com', 'Quinn Hall', 'password19', '5553667777',   FALSE, TRUE, FALSE),
  ('user20@example.com', 'Rachel Clark', 'password20', '2123334444',   FALSE, TRUE, FALSE),
  ('user21@example.com', 'Samuel Allen', 'password21', '7578889999',   FALSE, TRUE, FALSE),
  ('user22@example.com', 'Tara Turner', 'password22', '4465556666',  FALSE, TRUE, FALSE),
  ('user23@example.com', 'Ulysses Walker', 'password23', '1667178888',   FALSE, TRUE, FALSE),
  ('user24@example.com', 'Vivian Scott', 'password24', '3324445555', FALSE, TRUE, FALSE),
  ('user25@example.com', 'Walter Bennett', 'password25', '1990001111', FALSE, TRUE, FALSE),
  ('user26@example.com', 'Xavier Garcia', 'password26', '1412223333',  FALSE, TRUE, FALSE),
  ('user27@example.com', 'Yasmine Williams', 'password27', '1889990000', FALSE, TRUE, FALSE),
  ('user28@example.com', 'Zachary Smith', 'password28', '5551667777',  FALSE, TRUE, FALSE),
  ('user29@example.com', 'Ava Davis', 'password29', '2223334144',  FALSE, TRUE, FALSE),
  ('user30@example.com', 'Benjamin Harris', 'password30', '7171889999', FALSE, TRUE, FALSE);


-- Inserts for Realistic Events
INSERT INTO Event_ (name, location, description, private, start_timestamp, end_timestamp, creator_id) 
VALUES 
  ('Arena Rock Serenade', 'Rock Arena', 'Experience the magic of these classic rock with bands, including Journey, Night Ranger, Toto and Whitesnake for a night filled with heartfelt lyrics, emotional melodies and unforgettable tunes.', FALSE, '2024-06-15 18:30:00', '2024-06-15 23:59:00', 1),  
  ('Conference on Technology Innovation', 'Tech Center', 'Join us for the latest in tech innovations and discussions.', FALSE, '2023-11-01 09:00:00', '2023-11-01 17:00:00', 1),
  ('Art Exhibition: Modern Perspectives', 'City Art Gallery', 'Explore contemporary art from local and international artists.', TRUE, '2023-11-05 18:00:00', '2023-11-05 21:00:00', 2),
  ('Community Charity Run', 'City Park', 'Run for a cause and support local charities.', FALSE, '2023-11-10 08:00:00', '2023-11-10 12:00:00', 3),
  ('Food Festival: Taste of the World', 'Downtown Square', 'Indulge in a culinary journey with flavors from around the globe.', FALSE, '2023-11-15 12:00:00', '2023-11-15 20:00:00', 4),
  ('Tech Workshop: Introduction to AI', 'Innovation Hub', 'Learn the basics of Artificial Intelligence in this hands-on workshop.', FALSE, '2023-11-20 14:00:00', '2023-11-20 17:00:00', 1),
  ('Music Concert: Jazz Fusion Night', 'Harmony Arena', 'Enjoy an evening of jazz fusion performances by talented musicians.', FALSE, '2023-11-25 19:00:00', '2023-11-25 22:00:00', 2),
  ('Environmental Awareness Seminar', 'Green Hall', 'Discuss and address current environmental challenges with experts.', FALSE, '2023-11-30 10:00:00', '2023-11-30 13:00:00', 3),
  ('Fashion Show: Urban Elegance', 'Fashion Center', 'Witness the latest trends in urban fashion and style.', TRUE, '2023-12-05 15:00:00', '2023-12-05 18:00:00', 4),
  ('Startup Pitch Competition', 'Innovation Hub', 'Entrepreneurs pitch their innovative ideas to a panel of investors.', FALSE, '2023-12-10 13:00:00', '2023-12-10 17:00:00', 1),
  ('Film Festival: Indie Cinema Showcase', 'Cineplex Theater', 'Explore unique and thought-provoking films from independent filmmakers.', TRUE, '2023-12-15 17:00:00', '2023-12-15 22:00:00', 2),
  ('Health and Wellness Expo', 'Wellness Center', 'Discover the latest trends in health, fitness, and overall well-being.', FALSE, '2023-12-20 11:00:00', '2023-12-20 16:00:00', 3),
  ('Culinary Masterclass: Holiday Edition', 'Gourmet Kitchen', 'Learn to prepare festive dishes with renowned chefs.', TRUE, '2023-12-25 16:00:00', '2023-12-25 19:00:00', 4),
  ('Tech Conference: Future Trends', 'Tech Expo Center', 'Explore upcoming trends in technology and network with industry leaders.', FALSE, '2023-12-30 09:00:00', '2023-12-30 18:00:00', 1),
  ('Artisan Craft Fair', 'Crafters Market', 'Shop for handmade crafts and unique artisanal products.', TRUE, '2024-01-05 12:00:00', '2024-01-05 15:00:00', 2),
  ('Educational Symposium: Innovation in Education', 'Education Center', 'Discuss innovative approaches and technologies in education.', FALSE, '2024-01-10 14:00:00', '2024-01-10 17:00:00', 3),
  ('Gaming Tournament: Esports Championship', 'Gaming Arena', 'Witness intense gaming battles and cheer for your favorite teams.', TRUE, '2024-01-15 18:00:00', '2024-01-15 22:00:00', 4),
  ('Science Fair for Kids', 'Science Discovery Center', 'Encourage young minds with interactive and educational science exhibits.', FALSE, '2024-01-20 10:00:00', '2024-01-20 14:00:00', 1),
  ('Fashion Workshop: Sustainable Fashion Practices', 'Fashion Hub', 'Learn about sustainable practices in the fashion industry.', TRUE, '2024-01-25 15:00:00', '2024-01-25 18:00:00', 2),
  ('Community Cleanup Day', 'City Streets', 'Join hands for a cleaner and greener community.', FALSE, '2024-01-30 09:00:00', '2024-01-30 12:00:00', 3),
  ('Outdoor Concert: Summer Vibes', 'Sunset Park', 'Celebrate summer with live music and a festive atmosphere.', TRUE, '2024-02-05 17:00:00', '2024-02-05 22:00:00', 4);

-- Inserts for Comments
INSERT INTO Comment_ (text, media, event_id, author_id) 
VALUES 
  ('Awesome concert, loved every song!', NULL, 1, 1),
  ('The atmosphere was electric, great performance!', NULL, 1, 2),
  ('Good vibes all night long, would attend again!', NULL, 1, 3),
  ('Solid lineup and fantastic sound quality!', NULL, 1, 4),
  ('Memorable night, the bands were phenomenal!', NULL, 1, 5),
  ('I had a blast running!', NULL, 3, 3),
  ('The food was delicious!', NULL, 4, 4),
  ('Interesting workshop on AI.', NULL, 5, 5),
  ('The jazz music was so soothing.', NULL, 6, 6),
  ('Informative seminar on the environment.', NULL, 7, 7),
  ('The fashion show was fabulous!', NULL, 8, 8),
  ('Impressive startup pitches!', NULL, 9, 9),
  ('The indie films were thought-provoking.', NULL, 10, 10),
  ('Health expo was very informative.', NULL, 11, 11),
  ('Enjoyed the culinary masterclass!', NULL, 12, 12),
  ('Tech conference was enlightening.', NULL, 13, 13),
  ('Bought some lovely crafts at the fair.', NULL, 14, 14),
  ('Educational symposium was insightful.', NULL, 15, 15),
  ('Esports championship was thrilling!', NULL, 16, 16),
  ('Kids loved the science fair!', NULL, 17, 17),
  ('Learned a lot about sustainable fashion.', NULL, 18, 18),
  ('Community cleanup was a success!', NULL, 19, 19),
  ('Summer vibes concert was fantastic!', NULL, 20, 20);
  
  -- Inserts for Ratings
INSERT INTO Rating (rating, event_id, author_id) 
VALUES 
  (5, 1, 1),
  (4, 1, 2),
  (4, 2, 2),
  (5, 2, 1),
  (5, 3, 1),
  (4, 4, 1),
  (4, 5, 1),
  (5, 6, 1),
  (4, 7, 1),
  (5, 8, 1),
  (4, 9, 1),
  (5, 10, 1),
  (4, 11, 1),
  (5, 12, 1),
  (4, 13, 1),
  (5, 14, 1),
  (4, 15, 1),
  (5, 16, 1),
  (4, 17, 1),
  (5, 18, 1),
  (4, 19, 1),
  (5, 20, 2);
  
  -- Inserts for Reports
INSERT INTO Report (Type, comment_id, author_id) 
VALUES 
  ('Spam', 1, 1),
  ('Inappropriate Content', 2, 2),
  ('Abusive Language', 3, 3),
  ('Off-Topic', 4, 4),
  ('Spam', 5, 5),
  ('Inappropriate Content', 6, 6),
  ('Abusive Language', 7, 7),
  ('Off-Topic', 8, 8),
  ('Spam', 9, 9),
  ('Inappropriate Content', 10, 10),
  ('Abusive Language', 11, 11),
  ('Off-Topic', 12, 12),
  ('Spam', 13, 13),
  ('Inappropriate Content', 14, 14),
  ('Abusive Language', 15, 15),
  ('Off-Topic', 16, 16),
  ('Spam', 17, 17),
  ('Inappropriate Content', 18, 18),
  ('Abusive Language', 19, 19),
  ('Off-Topic', 20, 20);
  
  -- Inserts for Ticket Types
INSERT INTO TicketType (name, stock, description, private, person_buying_limit, start_timestamp, end_timestamp, price, event_id)
VALUES 
  ('Standard Access', 100, 'Get your ticket for individual entry to the concert.', FALSE, 10, '2023-12-01 00:00:00', '2023-12-15 00:00:00', 25.99, 1),
  ('Premiere Pass', 50, 'Elevate your concert experience with exclusive VIP perks and access.', TRUE, 5, '2023-12-05 12:00:00', '2023-12-10 12:00:00', 99.99, 1),
  ('Runner''s Package', 75, 'Participate in the community charity run.', FALSE, 15, '2023-11-10 09:00:00', '2023-11-11 12:00:00', 10.00, 3),
  ('Foodie Ticket', 120, 'Taste a variety of dishes at the food festival.', TRUE, 20, '2023-11-15 18:00:00', '2023-11-16 23:59:59', 39.99, 4),
  ('Workshop Pass', 30, 'Attend workshops on AI and technology.', FALSE, 5, '2023-11-20 14:00:00', '2023-11-21 17:00:00', 49.99, 5),
  ('Concert Ticket', 200, 'Enjoy live jazz fusion performances.', TRUE, 25, '2023-11-25 19:00:00', '2023-11-26 22:00:00', 29.99, 6),
  ('Seminar Access', 80, 'Participate in the environmental awareness seminar.', FALSE, 10, '2023-11-30 10:00:00', '2023-12-01 13:00:00', 0.00, 7),
  ('Fashion Show Ticket', 60, 'Front-row access to the urban elegance fashion show.', TRUE, 8, '2023-12-05 15:00:00', '2023-12-06 18:00:00', 19.99, 8),
  ('Startup Pitch Pass', 40, 'Attend the startup pitch competition.', FALSE, 5, '2023-12-10 13:00:00', '2023-12-11 17:00:00', 59.99, 9),
  ('Film Festival Pass', 90, 'Access to screenings at the indie cinema showcase.', TRUE, 15, '2023-12-15 17:00:00', '2023-12-16 22:00:00', 44.99, 10),
  ('Health Expo Ticket', 110, 'Explore health and wellness trends.', FALSE, 20, '2023-12-20 11:00:00', '2023-12-21 16:00:00', 15.99, 11),
  ('Culinary Masterclass', 25, 'Learn festive dishes at the culinary masterclass.', TRUE, 5, '2023-12-25 16:00:00', '2023-12-26 19:00:00', 34.99, 12),
  ('Tech Conference Pass', 150, 'Participate in discussions on future tech trends.', FALSE, 30, '2023-12-30 09:00:00', '2023-12-31 18:00:00', 79.99, 13),
  ('Artisan Craft Fair Access', 70, 'Access to handmade crafts at the crafters market.', TRUE, 15, '2024-01-05 12:00:00', '2024-01-06 15:00:00', 9.99, 14),
  ('Educational Symposium Ticket', 45, 'Attend discussions on innovation in education.', FALSE, 8, '2024-01-10 14:00:00', '2024-01-11 17:00:00', 49.99, 15),
  ('Gaming Tournament Pass', 100, 'Witness intense battles at the esports championship.', TRUE, 20, '2024-01-15 18:00:00', '2024-01-16 22:00:00', 19.99, 16),
  ('Science Fair Access', 85, 'Encourage young minds at the kids science fair.', FALSE, 12, '2024-01-20 10:00:00', '2024-01-21 14:00:00', 0.00, 17),
  ('Fashion Workshop Pass', 55, 'Learn about sustainable fashion practices.', TRUE, 10, '2024-01-25 15:00:00', '2024-01-26 18:00:00', 29.99, 18),
  ('Community Cleanup Volunteer', 35, 'Join hands for a cleaner community.', FALSE, 5, '2024-01-30 09:00:00', '2024-01-31 12:00:00', 0.00, 19),
  ('Outdoor Concert Ticket', 180, 'Celebrate summer with live music.', TRUE, 25, '2024-02-05 17:00:00', '2024-02-06 22:00:00', 49.99, 20);

-- Inserts for Ticket Orders
INSERT INTO TicketOrder (timestamp, buyer_id) 
VALUES 
  ('2023-11-01 00:00:00', 1),
  ('2023-11-01 00:00:00',  1),
  ('2023-11-01 00:00:00',  3),
  ('2023-11-01 00:00:00',  4),
  ('2023-11-01 00:00:00',  5),
  ('2023-11-01 00:00:00',  6),
  ('2023-11-01 00:00:00',  7),
  ('2023-11-01 00:00:00',  8),
  ('2023-11-01 00:00:00',  9),
  ('2023-11-02 00:00:00',  10),
  ('2023-11-02 00:00:00', 11),
  ('2023-11-04 00:00:00',  12),
  ('2023-11-05 00:00:00',  13),
  ('2023-11-06 00:00:00',  14),
  ('2023-11-01 00:00:00',  15),
  ('2023-11-01 00:00:00', 16),
  ('2023-11-01 00:00:00',  17),
  ('2023-11-01 00:00:00',  18),
  ('2023-11-01 00:00:00',  19);



INSERT INTO TicketOrder ( buyer_id) 
VALUES 
  ( 1),
  ( 1),
  ( 3),
  ( 4),
  ( 5),
  ( 6),
  ( 7),
  ( 8),
  ( 9),
  ( 10),
  ( 11),
  ( 12),
  ( 13),
  ( 14),
  ( 15),
  ( 16),
  ( 17),
  (18),
  (19),
  ( 20);

-- Inserts for Ticket Instances
INSERT INTO TicketInstance (ticket_type_id, order_id) 
VALUES 
  (1, 1),
  (1, 2),
  (1, 3),
  (3, 4),
  (3, 5),
  (3, 6),
  (3, 7),
  (2, 8),
  (6, 9),
  (6, 10),
  (6, 11),
  (6, 12),
  (6, 13),
  (14, 14),
  (15, 15),
  (16, 16),
  (17, 17),
  (18, 18),
  (19, 19),
  (20, 20);

-- Inserts for FAQs
INSERT INTO FAQ (question, answer) 
VALUES 
  ('What is the purpose of this platform?', 'This platform is designed to connect users with various events, providing a centralized space to discover and participate in a wide range of activities.'),
  ('How can I create an account?', 'To create an account, click on the "Sign Up" button on the homepage and follow the registration process. You will need to provide some basic information, including your email address and a password.'),
  ('Can I participate in events without an account?', 'While browsing events does not require an account, most events and features, such as ticket purchases and event comments, are accessible only to registered users. Creating an account is quick and free.'),
  ('How do I reset my password?', 'If you forget your password, click on the "Forgot Password" link on the login page. Follow the instructions sent to your registered email address to reset your password.'),
  ('Are events on this platform free?', 'Event costs vary and are set by the event organizers. Some events may be free, while others may require the purchase of tickets or passes. Check the event details for pricing information.'),
  ('How can I contact the event organizer?', 'Each event page contains contact information for the organizer. You can find their email or other contact details in the event details section.'),
  ('What types of events are available?', 'The platform hosts a diverse range of events, including but not limited to conferences, workshops, concerts, charity runs, and art exhibitions. You can explore events by category or search for specific keywords.'),
  ('How do I leave a comment or review for an event?', 'To leave a comment or review for an event, you must be a registered user. Once logged in, navigate to the event page and use the comment section to share your thoughts or ask questions.'),
  ('Can I get a refund for purchased tickets?', 'Refund policies vary by event. Check the event details and terms and conditions before purchasing tickets. If you have questions about a specific event, contact the event organizer for more information.'),
  ('How can I promote my own event on this platform?', 'If you are interested in promoting your event on this platform, you can create an organizer account and follow the steps to add and promote your event. The platform provides tools to manage and market your events effectively.');

INSERT INTO UserLikes (user_id, comment_id) 
VALUES 
  (2, 1);

INSERT INTO EventImage (event_id, image_path) VALUES (1, 'concert_image1.jpg');
INSERT INTO EventImage (event_id, image_path) VALUES (1, 'concert_image2.jpg');
INSERT INTO EventImage (event_id, image_path) VALUES (1, 'concert_image3.jpg');
INSERT INTO EventImage (event_id, image_path) VALUES (1, 'concert_image4.jpg');
