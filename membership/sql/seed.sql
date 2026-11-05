-- Starting lookup data, taken from the 2026/27 application form.
-- Run after schema.sql.

SET NAMES utf8mb4;

INSERT INTO membership_types (name, annual_fee, is_life) VALUES
    ('Single',              60.00, 0),
    ('Double',              85.00, 0),
    ('Student or Retired',  40.00, 0),
    ('Life',                 0.00, 1);

INSERT INTO club_groups (name) VALUES
    ('Swimming pool'),
    ('Tennis'),
    ('Squash'),
    ('Darts');

INSERT INTO interests (name, sort_order) VALUES
    ('Social',                   10),
    ('Squash',                   20),
    ('Darts',                    30),
    ('Swimming pool',            40),
    ('HMC Walkers and Runners',  50),
    ('Ruakura Rugby Club',       60);
