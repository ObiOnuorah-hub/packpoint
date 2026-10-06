-- ==============================================================================
-- PACKPOINT DATABASE SCHEMA (MySQL / MariaDB)
-- ==============================================================================
-- Dit bestand maakt de database 'packpoint' aan, met alle tabellen en testgegevens.
--
-- ZO IMPORTEER JE HEM:
--   1. Start Apache en MySQL in het XAMPP Control Panel
--   2. Ga naar http://localhost/phpmyadmin
--   3. Klik bovenaan op 'Importeren', kies dit bestand en klik op 'Starten'
--
-- Wil je opnieuw beginnen? Verwijder dan eerst de database 'packpoint' in phpMyAdmin.
--
-- RELATIES TUSSEN DE TABELLEN (foreign keys):
--
--   users (1) ──────< parcels.customer_id          een klant kan meerdere pakketten hebben
--   users (1) ──────< parcels.received_by_user_id  een medewerker kan meerdere pakketten innemen
--   carriers (1) ───< parcels.carrier_id           een vervoerder brengt meerdere pakketten
--   storage_slots (1) < parcels.storage_slot_id    een pakket ligt in 1 opslagvak


-- Maak de database aan (als die nog niet bestaat) en ga hem gebruiken
CREATE DATABASE IF NOT EXISTS packpoint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE packpoint;


-- ------------------------------------------------------------------------------
-- 1. TABEL: users (alle gebruikers: klanten, baliemedewerkers en beheerders)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,           -- uniek nummer, telt vanzelf op
    username      VARCHAR(50)  NOT NULL UNIQUE,                       -- inlognaam (bijv. klant01), mag maar 1 keer voorkomen
    name          VARCHAR(100) NOT NULL,                              -- volledige naam
    email         VARCHAR(150) NOT NULL UNIQUE,                       -- e-mailadres, mag maar 1 keer voorkomen
    phone         VARCHAR(20)  NULL,                                  -- telefoonnummer (niet verplicht)
    password_hash VARCHAR(255) NOT NULL,                              -- GEHASHT wachtwoord (nooit het echte wachtwoord!)
    role          ENUM('customer', 'employee', 'admin') NOT NULL DEFAULT 'customer', -- rol: klant, medewerker of admin
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP         -- wanneer het account is gemaakt
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------------------------
-- 2. TABEL: carriers (vervoerders zoals PostNL en DHL)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS carriers (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,               -- uniek nummer
    name      VARCHAR(100) NOT NULL UNIQUE,                           -- naam van de vervoerder
    is_active TINYINT(1)   NOT NULL DEFAULT 1                         -- 1 = actief, 0 = niet meer actief
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------------------------
-- 3. TABEL: storage_slots (opslagvakken in de stellingen)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS storage_slots (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,               -- uniek nummer
    slot_code VARCHAR(10)  NOT NULL UNIQUE,                           -- code van het vak (bijv. A-01)
    rack      VARCHAR(50)  NOT NULL DEFAULT 'Stelling A',             -- in welke stelling het vak zit
    status    ENUM('free', 'occupied') NOT NULL DEFAULT 'free'        -- vrij of bezet
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------------------------
-- 4. TABEL: parcels (de pakketten)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS parcels (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,     -- uniek nummer
    tracking_code       VARCHAR(50)  NOT NULL UNIQUE,                 -- barcode van de vervoerder (uniek = TE-07)
    pickup_code         VARCHAR(10)  NOT NULL UNIQUE,                 -- afhaalcode voor de klant (uniek = TE-08)
    carrier_id          INT UNSIGNED NOT NULL,                        -- welke vervoerder (verwijst naar carriers.id)
    storage_slot_id     INT UNSIGNED NULL,                            -- in welk vak (verwijst naar storage_slots.id)
    customer_id         INT UNSIGNED NULL,                            -- klant-account, als die bestaat (verwijst naar users.id)
    customer_name       VARCHAR(100) NOT NULL,                        -- naam ontvanger
    customer_email      VARCHAR(150) NOT NULL,                        -- e-mail ontvanger
    customer_phone      VARCHAR(20)  NULL,                            -- telefoon ontvanger (niet verplicht)
    status              ENUM('expected', 'arrived', 'picked_up', 'returned') NOT NULL DEFAULT 'arrived', -- verwacht, binnengekomen, uitgegeven of retour
    received_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- wanneer aangemeld, en later: wanneer binnengekomen
    pickup_deadline     DATETIME NULL,                                -- uiterste afhaaldatum (leeg zolang het pakket nog verwacht wordt)
    picked_up_at        DATETIME NULL,                                -- wanneer opgehaald (of retour gegaan)
    received_by_user_id INT UNSIGNED NOT NULL,                        -- welke medewerker het pakket heeft ingeboekt

    -- Indexen maken zoeken sneller
    INDEX idx_parcels_status (status),
    INDEX idx_parcels_customer_email (customer_email),

    -- FOREIGN KEYS: zo weet MySQL hoe de tabellen aan elkaar vastzitten
    -- en kun je bijvoorbeeld geen pakket opslaan met een vervoerder die niet bestaat
    CONSTRAINT fk_parcels_carrier  FOREIGN KEY (carrier_id)          REFERENCES carriers(id),
    CONSTRAINT fk_parcels_slot     FOREIGN KEY (storage_slot_id)     REFERENCES storage_slots(id) ON DELETE SET NULL,
    CONSTRAINT fk_parcels_customer FOREIGN KEY (customer_id)         REFERENCES users(id)         ON DELETE SET NULL,
    CONSTRAINT fk_parcels_employee FOREIGN KEY (received_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================================================
-- TESTGEGEVENS (SEED DATA)
-- ==============================================================================

-- Standaard vervoerders
INSERT INTO carriers (id, name, is_active) VALUES
(1, 'PostNL', 1),
(2, 'DHL Express', 1),
(3, 'DPD', 1),
(4, 'UPS', 1),
(5, 'Amazon', 1);

-- Standaard opslagvakken in Stelling A en Stelling B
INSERT INTO storage_slots (id, slot_code, rack, status) VALUES
(1, 'A-01', 'Stelling A', 'occupied'),
(2, 'A-02', 'Stelling A', 'occupied'),
(3, 'A-03', 'Stelling A', 'free'),
(4, 'A-04', 'Stelling A', 'free'),
(5, 'A-05', 'Stelling A', 'free'),
(6, 'B-01', 'Stelling B', 'free'),
(7, 'B-02', 'Stelling B', 'free');

-- De 3 testaccounts. De wachtwoorden zijn gehasht met password_hash() in PHP:
--   admin01 / admin123   (beheerder)
--   balie01 / balie123   (baliemedewerker)
--   klant01 / klant123   (klant)
INSERT INTO users (id, username, name, email, phone, password_hash, role) VALUES
(1, 'admin01', 'Beheerder (Admin)',         'admin@packpoint.nl', '0612345678', '$2y$10$1sjYmuCoV/SISYfhQFZ52eNReJjfBmwQfkvUCN3J9kRQzIRT..9Ya', 'admin'),
(2, 'balie01', 'Janine Balie (Medewerker)', 'balie@packpoint.nl',  '0687654321', '$2y$10$qQrz7gPIS2FJTYgc7SEUxeHhClIYwlc5uAfDfZDYJvOtr/RLFNwAS', 'employee'),
(3, 'klant01', 'John Doe (Klant)',          'johndoe@example.com', '0611223344', '$2y$10$N9QFc7E7XXiNiG0n.KWmlue2FiBOSBYFOEsQaJkiqwq5KAVCGXEO.', 'customer');

-- Testpakket 1: ligt klaar om opgehaald te worden (in vak A-01)
INSERT INTO parcels (id, tracking_code, pickup_code, carrier_id, storage_slot_id, customer_id, customer_name, customer_email, customer_phone, status, received_at, pickup_deadline, received_by_user_id) VALUES
(1, '3S123456789NL', 'PK-7X9B', 1, 1, 3, 'John Doe', 'johndoe@example.com', '0611223344', 'arrived', NOW() - INTERVAL 2 DAY, NOW() + INTERVAL 5 DAY, 2);

-- Testpakket 2: ligt er al 10 dagen, dus de deadline is verlopen (test voor 'Te lang liggen')
INSERT INTO parcels (id, tracking_code, pickup_code, carrier_id, storage_slot_id, customer_id, customer_name, customer_email, customer_phone, status, received_at, pickup_deadline, received_by_user_id) VALUES
(2, 'DHL-987654321', 'PK-3M2K', 2, 2, 3, 'John Doe', 'johndoe@example.com', '0611223344', 'arrived', NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 3 DAY, 2);

-- Testpakket 3: is aangekondigd maar nog NIET binnen (status 'expected', nog geen vak en geen deadline)
INSERT INTO parcels (id, tracking_code, pickup_code, carrier_id, storage_slot_id, customer_id, customer_name, customer_email, customer_phone, status, received_at, pickup_deadline, received_by_user_id) VALUES
(3, '1Z999AA10123456784', 'PK-8Q4N', 4, NULL, 3, 'John Doe', 'johndoe@example.com', '0611223344', 'expected', NOW() - INTERVAL 1 DAY, NULL, 2);

-- Testpakket 4: is al opgehaald (komt in de geschiedenis van de klant)
INSERT INTO parcels (id, tracking_code, pickup_code, carrier_id, storage_slot_id, customer_id, customer_name, customer_email, customer_phone, status, received_at, pickup_deadline, picked_up_at, received_by_user_id) VALUES
(4, 'DPD-55501234', 'PK-5H7T', 3, 3, 3, 'John Doe', 'johndoe@example.com', '0611223344', 'picked_up', NOW() - INTERVAL 12 DAY, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 9 DAY, 2);
