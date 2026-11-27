ALTER TABLE inbound_routes ADD COLUMN outbound_trunk_id INT NULL DEFAULT NULL AFTER destination;
