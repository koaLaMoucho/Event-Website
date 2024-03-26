-- Drop Tables If They Exist
DROP TABLE IF EXISTS Notification_;
DROP TABLE IF EXISTS Rating;
DROP TABLE IF EXISTS Report;
DROP TABLE IF EXISTS Comment_;
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

setweight(to_tsvector('english', (SELECT string_agg(Tag.name, ' ') FROM TagEvent JOIN Tag ON TagEvent.tag_id = Tag.tag_id WHERE TagEvent.event_id = NEW.event_id)), 'C') ||

setweight(to_tsvector('english', NEW.location), 'D')

);

END IF;

IF TG_OP = 'UPDATE' THEN


     IF (NEW.name <> OLD.name OR
       NEW.description <> OLD.description OR
       (SELECT string_agg(Tag.name, ' ')
        FROM TagEvent
        JOIN Tag ON TagEvent.tag_id = Tag.tag_id
       WHERE TagEvent.event_id = NEW.event_id)
       <> (SELECT string_agg(Tag.name, ' ')
            FROM TagEvent
         JOIN Tag ON TagEvent.tag_id = Tag.tag_id
          WHERE TagEvent.event_id = OLD.event_id) OR

        NEW.location <> OLD.location) THEN


      NEW.tsvectors = (

        setweight(to_tsvector('english', NEW.name), 'A') ||

       setweight(to_tsvector('english', NEW.description), 'B') ||

      setweight(to_tsvector('english', (SELECT string_agg(Tag.name, ' ') FROM TagEvent JOIN Tag ON TagEvent.tag_id = Tag.tag_id WHERE TagEvent.event_id = NEW.event_id)), 'C') ||

       setweight(to_tsvector('english', NEW.location), 'D')

     );

    END IF;
 END IF;
 RETURN NEW;

END $$

LANGUAGE plpgsql;
CREATE TRIGGER event_search_update

BEFORE INSERT OR UPDATE ON Event_

FOR EACH ROW

EXECUTE PROCEDURE event_search_update();
CREATE INDEX event_text_search_idx ON Event_ USING GIN (tsvectors);

CREATE OR REPLACE FUNCTION send_comment_notification()
RETURNS TRIGGER AS $$
BEGIN
  -- Insert a new notification for the event owner
  INSERT INTO Notification_ (notified_user, comment_id, notification_type, timestamp)
  VALUES ((SELECT creator_id FROM Event_ WHERE event_id = NEW.event_id), NEW.comment_id, 'Comment', NOW());

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
  JOIN UserClass u ON u.user_id = tos.buyer_id
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
  INSERT INTO Notification_ (notified_user, report_id, notification_type, timestamp)
  SELECT user_id, NEW.report_id, 'Report'::NotificationType, NOW()
  FROM UserClass
  WHERE is_admin = TRUE;

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

