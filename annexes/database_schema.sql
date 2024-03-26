-- Drop Tables If They Exist
DROP TABLE IF EXISTS Notification_;
DROP TABLE IF EXISTS Rating;
DROP TABLE IF EXISTS Report;
DROP TABLE IF EXISTS Comment_;
DROP TABLE IF EXISTS TagEvent;
DROP TABLE IF EXISTS TicketInstance;
DROP TABLE IF EXISTS TicketType;
DROP TABLE IF EXISTS Tag;
DROP TABLE IF EXISTS FAQ;
DROP TABLE IF EXISTS Event_;
DROP TABLE IF EXISTS TicketOrder;
DROP TABLE IF EXISTS UserClass;

-- Drop Type If It Exists
DROP TYPE IF EXISTS Role_;
DROP TYPE IF EXISTS NotificationType;

-- Types
CREATE TYPE Role_ AS ENUM ('Event_Creator', 'Event_Manager');
CREATE TYPE NotificationType AS ENUM ('Comment', 'Report', 'Event');

-- Tables
CREATE TABLE UserClass (
   user_id SERIAL PRIMARY KEY,
   email_address TEXT NOT NULL UNIQUE,
   name TEXT NOT NULL,
   password TEXT NOT NULL,
   phone_number TEXT NOT NULL UNIQUE,
   role Role_,
   is_admin BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE Event_ (
   event_id SERIAL PRIMARY KEY,
   name TEXT NOT NULL,
   location TEXT NOT NULL,
   description TEXT,
   private BOOLEAN NOT NULL DEFAULT TRUE,
   start_timestamp TIMESTAMP NOT NULL,
   end_timestamp TIMESTAMP NOT NULL CHECK (start_timestamp < end_timestamp),
   creator_id INT NOT NULL REFERENCES UserClass (user_id) ON UPDATE CASCADE
);

CREATE TABLE Comment_ (
   comment_id SERIAL PRIMARY KEY,
   text TEXT,
   media BYTEA,
   event_id INT REFERENCES Event_ (event_id) ON UPDATE CASCADE,
   author_id INT REFERENCES UserClass (user_id),
   CHECK (text IS NOT NULL OR media IS NOT NULL)
);

CREATE TABLE Rating (
   rating_id SERIAL PRIMARY KEY,
   rating INT NOT NULL,
   event_id INT NOT NULL REFERENCES Event_ (event_id) ON UPDATE CASCADE,
   author_id INT NOT NULL REFERENCES UserClass (user_id)
);

CREATE TABLE Report (
   report_id SERIAL PRIMARY KEY,
   Type TEXT NOT NULL,
   comment_id INT NOT NULL REFERENCES Comment_ (comment_id) ON UPDATE CASCADE,
   author_id INT NOT NULL REFERENCES UserClass (user_id)
);

CREATE TABLE TicketType (
   ticket_type_id SERIAL PRIMARY KEY,
   name TEXT NOT NULL,
   stock INT NOT NULL CHECK (stock > 0),
   description TEXT NOT NULL,
   private BOOLEAN NOT NULL DEFAULT TRUE,
   person_buying_limit INT NOT NULL CHECK (person_buying_limit > 0 AND person_buying_limit < stock),
   start_timestamp TIMESTAMP NOT NULL,
   end_timestamp TIMESTAMP NOT NULL CHECK (start_timestamp < end_timestamp),
   price NUMERIC NOT NULL DEFAULT 0,
   event_id INT NOT NULL REFERENCES Event_(event_id) ON UPDATE CASCADE
);

CREATE TABLE TicketOrder (
   order_id SERIAL PRIMARY KEY,
   timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   buyer_id INT NOT NULL REFERENCES UserClass (user_id) ON UPDATE CASCADE
);

CREATE TABLE TicketInstance (
   ticket_instance_id SERIAL PRIMARY KEY,
   ticket_type_id INT NOT NULL REFERENCES TicketType (ticket_type_id) ON UPDATE CASCADE,
   order_id INT NOT NULL REFERENCES TicketOrder(order_id) ON UPDATE CASCADE
);

CREATE TABLE Tag (
   tag_id SERIAL PRIMARY KEY,
   name TEXT NOT NULL UNIQUE
);

CREATE TABLE FAQ (
   faq_id SERIAL PRIMARY KEY,
   question TEXT NOT NULL,
   answer TEXT NOT NULL
);

CREATE TABLE TagEvent (
   event_id INT NOT NULL REFERENCES Event_ (event_id) ON UPDATE CASCADE,
   tag_id INT NOT NULL REFERENCES Tag (tag_id),
   PRIMARY KEY (event_id, tag_id)
);

CREATE TABLE Notification_ (
   notification_id SERIAL PRIMARY KEY,
   timestamp TIMESTAMP NOT NULL CHECK (timestamp <= NOW()),
   notified_user INTEGER NOT NULL REFERENCES UserClass (user_id) ON UPDATE CASCADE,
   event_id INT REFERENCES Event_(event_id) ON UPDATE CASCADE,
   comment_id INT REFERENCES Comment_(comment_id) ON UPDATE CASCADE,
   report_id INT REFERENCES Report(report_id) ON UPDATE CASCADE,
   viewed BOOLEAN NOT NULL DEFAULT FALSE,
   notification_type NotificationType NOT NULL,
   CHECK (
      (notification_type = 'Event' AND event_id IS NOT NULL AND comment_id IS NULL AND report_id IS NULL) OR
      (notification_type = 'Comment' AND event_id IS NULL AND comment_id IS NOT NULL AND report_id IS NULL) OR
      (notification_type = 'Report' AND event_id IS NULL AND comment_id IS NULL AND report_id IS NOT NULL)
   )
);

