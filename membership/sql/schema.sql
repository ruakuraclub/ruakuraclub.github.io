-- Ruakura Club membership database
-- Target: MariaDB 10.11. Run in phpMyAdmin against an empty database.

SET NAMES utf8mb4;

-- People who can log in to this app (committee / admin), not club members.
CREATE TABLE users (
    id                  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username            VARCHAR(100)      NOT NULL,
    display_name        VARCHAR(100)      NOT NULL,
    password_hash       VARCHAR(255)      NOT NULL,
    totp_secret         VARCHAR(64)       NULL,     -- NULL until the user has set up their authenticator app
    totp_last_timeslice BIGINT UNSIGNED   NULL,     -- stops the same code being used twice
    failed_attempts     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until        DATETIME          NULL,
    last_login_at       DATETIME          NULL,
    is_active           TINYINT(1)        NOT NULL DEFAULT 1,
    created_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts and account changes, kept for 12 months (older rows are removed automatically).
CREATE TABLE auth_log (
    id             BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    created_at     DATETIME          NOT NULL,
    event          VARCHAR(40)       NOT NULL,   -- see AUTH_EVENTS in src/auth.php
    user_id        SMALLINT UNSIGNED NULL,       -- the account concerned, if known
    username       VARCHAR(100)      NULL,       -- as typed at login (may not exist)
    actor_user_id  SMALLINT UNSIGNED NULL,       -- who made the change, if someone else
    ip_address     VARCHAR(45)       NULL,
    user_agent     VARCHAR(255)      NULL,
    PRIMARY KEY (id),
    KEY ix_auth_log_created (created_at),
    KEY ix_auth_log_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE membership_types (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(50)       NOT NULL,
    annual_fee  DECIMAL(8,2)      NOT NULL DEFAULT 0.00,
    is_life     TINYINT(1)        NOT NULL DEFAULT 0,   -- life members get a $0 subscription each season
    is_active   TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_membership_types_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE members (
    id                         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    membership_number          INT UNSIGNED  NULL,          -- RCC card number; NULL until a new applicant is accepted
    first_name                 VARCHAR(100)  NOT NULL,
    last_name                  VARCHAR(100)  NOT NULL,
    preferred_name             VARCHAR(100)  NULL,
    email                      VARCHAR(255)  NULL,          -- not unique: households may share
    phone                      VARCHAR(30)   NULL,          -- form: "home / mobile"
    phone_work                 VARCHAR(30)   NULL,
    address                    TEXT          NULL,          -- free text, as entered on the form
    date_of_birth              DATE          NULL,
    newsletter_consent         TINYINT(1)    NOT NULL DEFAULT 0,
    newsletter_consent_date    DATE          NULL,
    interest_other             VARCHAR(150)  NULL,          -- "Other – please specify"
    -- New-member application
    application_received_date  DATE          NULL,
    join_date                  DATE          NULL,          -- application accepted
    nominator1_name            VARCHAR(150)  NULL,
    nominator1_number          VARCHAR(20)   NULL,
    nominator2_name            VARCHAR(150)  NULL,
    nominator2_number          VARCHAR(20)   NULL,
    status                     ENUM('active','lapsed','resigned','deceased') NOT NULL DEFAULT 'active',
    notes                      TEXT          NULL,
    created_at                 TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                 TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_members_membership_number (membership_number),
    KEY ix_members_name (last_name, first_name),
    KEY ix_members_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Companies that pay for their staff's memberships (corporate membership).
CREATE TABLE companies (
    id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)      NOT NULL,
    is_active  TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per payment. A multi-year prepayment is a single row
-- whose expiry_date is several years after start_date.
-- The people it covers (primary, partner, juniors) are in subscription_members.
CREATE TABLE subscriptions (
    id                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    membership_type_id  SMALLINT UNSIGNED NOT NULL,
    company_id          SMALLINT UNSIGNED NULL,     -- set when a company paid (corporate membership)
    start_date          DATE              NOT NULL,
    expiry_date         DATE              NOT NULL,
    amount_paid         DECIMAL(8,2)      NULL,     -- RCC total, after any early-renewal discount
    payment_method      ENUM('eftpos','internet','cash','other') NULL,
    paid_date           DATE              NULL,     -- NULL = not yet paid
    payment_reference   VARCHAR(100)      NULL,
    student_details     VARCHAR(150)      NULL,     -- Uni/Polytech name and ID no.
    notes               TEXT              NULL,
    created_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_subscriptions_expiry (expiry_date),
    CONSTRAINT fk_subscriptions_type
        FOREIGN KEY (membership_type_id) REFERENCES membership_types (id) ON DELETE RESTRICT,
    CONSTRAINT fk_subscriptions_company
        FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE RESTRICT,
    CONSTRAINT ck_subscriptions_dates CHECK (expiry_date > start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Who a subscription covers. The admin dates are per person,
-- matching the "Administrative use only" box on the form.
CREATE TABLE subscription_members (
    subscription_id        INT UNSIGNED NOT NULL,
    member_id              INT UNSIGNED NOT NULL,
    role                   ENUM('primary','partner','junior') NOT NULL DEFAULT 'primary',
    card_printed_date      DATE         NULL,
    menumate_updated_date  DATE         NULL,
    PRIMARY KEY (subscription_id, member_id),
    KEY ix_subscription_members_member (member_id),
    CONSTRAINT fk_subscription_members_subscription
        FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE CASCADE,
    CONSTRAINT fk_subscription_members_member
        FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paid sub-clubs: Pool, Tennis, Squash, Darts, ...
-- Named club_groups because GROUPS is a reserved word in MySQL 8.
CREATE TABLE club_groups (
    id           SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(100)      NOT NULL,
    description  VARCHAR(255)      NULL,
    is_active    TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_club_groups_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A person's group membership for one subscription; it ends when that subscription does.
CREATE TABLE subscription_member_groups (
    subscription_id  INT UNSIGNED      NOT NULL,
    member_id        INT UNSIGNED      NOT NULL,
    group_id         SMALLINT UNSIGNED NOT NULL,
    option_label     VARCHAR(100)      NULL,    -- e.g. "Squash – competition", "Pool key (renewal)"
    amount_paid      DECIMAL(8,2)      NULL,
    details          JSON              NULL,    -- group-specific answers, e.g. squash: {"squash_nz":true,"directory":false,"grading_code":"…","grade":"…"}
    PRIMARY KEY (subscription_id, member_id, group_id),
    KEY ix_smg_group (group_id),
    CONSTRAINT fk_smg_subscription_member
        FOREIGN KEY (subscription_id, member_id)
        REFERENCES subscription_members (subscription_id, member_id) ON DELETE CASCADE,
    CONSTRAINT fk_smg_group
        FOREIGN KEY (group_id) REFERENCES club_groups (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "My interests are" / "Reason for joining" tick boxes. Not paid, not per cycle.
CREATE TABLE interests (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100)      NOT NULL,
    sort_order  SMALLINT          NOT NULL DEFAULT 0,
    is_active   TINYINT(1)        NOT NULL DEFAULT 1,
    brevo_list_id INT UNSIGNED    NULL,   -- Brevo list that members with this interest are added to
    PRIMARY KEY (id),
    UNIQUE KEY uq_interests_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE member_interests (
    member_id    INT UNSIGNED      NOT NULL,
    interest_id  SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (member_id, interest_id),
    KEY ix_member_interests_interest (interest_id),
    CONSTRAINT fk_member_interests_member
        FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
    CONSTRAINT fk_member_interests_interest
        FOREIGN KEY (interest_id) REFERENCES interests (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Physical items issued to a member. They outlive a subscription
-- ("Your card remains active while you remain a member").
CREATE TABLE access_items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id    INT UNSIGNED NOT NULL,
    item_type    ENUM('barrier_card','pool_key','tennis_key','wristband') NOT NULL,
    identifier   VARCHAR(50)       NULL,     -- card/key number; NULL for wristbands (not numbered)
    colour       VARCHAR(30)       NULL,     -- wristbands: the season's colour
    quantity     SMALLINT UNSIGNED NOT NULL DEFAULT 1,   -- wristbands: how many issued
    returned_quantity SMALLINT UNSIGNED NOT NULL DEFAULT 0,  -- how many of them came back
    issued_date  DATE              NULL,
    status       ENUM('active','returned','lost','disabled') NOT NULL DEFAULT 'active',
    status_date  DATE              NULL,
    notes        VARCHAR(255)      NULL,
    PRIMARY KEY (id),
    -- Not unique: a returned key can be issued again. The app stops two active items sharing a number.
    KEY ix_access_items_identifier (item_type, identifier),
    KEY ix_access_items_member (member_id),
    CONSTRAINT fk_access_items_member
        FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Single values the app keeps, e.g. brevo_main_list_id, brevo_lapsed_list_id, brevo_last_sync.
CREATE TABLE settings (
    name   VARCHAR(50)   NOT NULL,
    value  VARCHAR(255)  NULL,
    PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Emails sent through Brevo from the Email members page: one row per send...
CREATE TABLE email_sends (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    sent_at          DATETIME      NOT NULL,
    user_id          SMALLINT UNSIGNED NULL,            -- who sent it
    template_id      INT UNSIGNED  NOT NULL,            -- Brevo template
    template_name    VARCHAR(255)  NOT NULL,
    subject          VARCHAR(255)  NULL,
    mode             ENUM('individual','bcc','test') NOT NULL,
    recipient_count  SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY ix_email_sends_sent_at (sent_at),
    CONSTRAINT fk_email_sends_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ...and one row per member it went to (members sharing an email get one email, a row each).
CREATE TABLE email_send_recipients (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    send_id     INT UNSIGNED  NOT NULL,
    member_id   INT UNSIGNED  NULL,                     -- NULL for a test send
    email       VARCHAR(255)  NOT NULL,
    message_id  VARCHAR(255)  NULL,                     -- Brevo's message ID, for tracing delivery in Brevo
    PRIMARY KEY (id),
    KEY ix_email_send_recipients_member (member_id),
    CONSTRAINT fk_email_send_recipients_send
        FOREIGN KEY (send_id) REFERENCES email_sends (id) ON DELETE CASCADE,
    CONSTRAINT fk_email_send_recipients_member
        FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
