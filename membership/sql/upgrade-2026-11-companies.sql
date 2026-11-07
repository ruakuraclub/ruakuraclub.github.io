-- Only for a database created from schema.sql before companies were added.
-- A new install gets all of this from schema.sql.

CREATE TABLE companies (
    id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)      NOT NULL,
    is_active  TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE subscriptions
    ADD COLUMN company_id SMALLINT UNSIGNED NULL AFTER membership_type_id,
    ADD CONSTRAINT fk_subscriptions_company
        FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE RESTRICT;
