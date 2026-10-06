# PackPoint 📦

Yo! Dit is **PackPoint**, een webapp voor een buurtwinkel die pakketjes aanneemt.
Pakket komt binnen → gaat in een vakje → klant haalt het op met een afhaalcode. Simpel.

Gemaakt met **PHP 8**, **MySQL (MariaDB)**, **PDO** en **Tailwind CSS**.

---

## 1. Zo start je hem op

1. Je hebt **XAMPP** nodig (op `C:\xampp` of `D:\xampp`).
2. **Database erin zetten (hoeft maar 1 keer):**
   - Zet Apache en MySQL aan in het XAMPP Control Panel.
   - Ga naar <http://localhost/phpmyadmin> en maak een lege database `packpoint`.
   - Klik links op `packpoint`, dan op **Importeren** en kies `sql/schema.sql`.
     Boem, alle tabellen en testdata staan erin.
3. Dubbelklik op **`Start-PackPoint.bat`**.
   - MySQL gaat aan (als dat nog niet zo was).
   - De site opent op <http://localhost:8000>.

> Database-wachtwoord en zo staan in `includes/config.php`.
> Wil je hem online zetten op PLESK? Kijk in [docs/installatie-plesk.md](docs/installatie-plesk.md).

### Testaccounts om mee te spelen

| Wie             | Gebruikersnaam | Wachtwoord | Je komt op               |
|-----------------|----------------|------------|--------------------------|
| Klant           | `klant01`      | `klant123` | Klant dashboard          |
| Baliemedewerker | `balie01`      | `balie123` | Balie dashboard          |
| Admin           | `admin01`      | `admin123` | Admin dashboard          |

---

## 2. Wat staat waar?

```
packpoint/
├── includes/                 Code die elke pagina gebruikt
│   ├── init.php              Laadt alles in. Elke pagina begint hiermee.
│   ├── config.php            Instellingen (database, 7 dagen afhaaltermijn) + get_db()
│   ├── auth.php              Deel 1: sessie, meldingen, CSRF
│   │                         Deel 2: inloggen, uitloggen, rollen, menu, wie mag waar komen
│   ├── functions.php         Deel 1: handige hulpjes (h(), format_date(), controles)
│   │                         Deel 2-5: alle SQL voor gebruikers, vervoerders, vakken, pakketten
│   ├── header.php            Bovenkant van elke pagina (menu zie je pas als je bent ingelogd)
│   └── footer.php            Onderkant van elke pagina
├── public/                   Alleen deze map kan je in de browser openen
│   ├── index.php             Stuurt je door naar login of je dashboard
│   ├── login.php / logout.php / register.php
│   ├── customer/             Pagina's voor klanten
│   ├── employee/             Pagina's voor de balie (admins mogen hier ook)
│   └── admin/                Pagina's alleen voor admins
└── sql/schema.sql            Tabellen + testdata
```

**Regel van de zaak:** in `public/` staat bijna geen SQL. De pagina's roepen gewoon functies aan
uit `includes/functions.php`. Zo staat elke query maar op één plek. Zoek in dat bestand op `DEEL`
en je springt zo naar gebruikers, vervoerders, vakken of pakketten.

---

## 3. Hoe werkt inloggen?

1. Je typt je gebruikersnaam (of e-mail) en wachtwoord op `login.php`.
2. `login()` in `includes/auth.php` zoekt je op met `find_user_by_login()`.
3. `password_verify()` controleert je wachtwoord tegen de **bcrypt-hash** in de database.
   Je echte wachtwoord staat nergens, alleen een hash.
4. Klopt het? Dan krijg je een **nieuw sessie-ID** (`session_regenerate_id`) en
   onthouden we alleen je `user_id`.
5. `redirect_to_dashboard()` gooit je naar het dashboard dat bij jouw rol hoort.

---

## 4. Hoe werken de rollen?

De rollen staan in `ROLES` in `includes/auth.php`:

| Rol (database) | Naam            | Wat mag je?                                                         |
|----------------|-----------------|---------------------------------------------------------------------|
| `customer`     | Klant           | Je eigen pakketjes zien (verwacht en binnen, met tijdlijn en geschiedenis), je afhaalcode + uiterste afhaaldatum zien, je gegevens aanpassen |
| `employee`     | Baliemedewerker | Pakket registreren (verwacht of binnen), ontvangst doen, vakje kiezen, zoeken op code/klant/vak/status, status aanpassen, uitgeven |
| `admin`        | Beheerder       | Alles wat de balie mag + gebruikers, vakken en vervoerders beheren  |

Elke beveiligde pagina begint met één regeltje, bijvoorbeeld:

```php
require_role(['employee', 'admin']);   // alleen balie en admins
```

Verkeerde rol? Dan word je met een melding teruggestuurd naar je eigen dashboard.
Welke knoppen je in het menu ziet, staat in `menu_items()` in `includes/auth.php`.

---

## 5. Hoe werken pakketten?

```
 REGISTREREN (afhaalcode PK-XXXX wordt gemaakt)
     │
     ├── nog niet binnen ──► 'expected' (Verwacht)
     │                            │   nog geen vak, nog geen deadline
     │                            ▼
     │                       ONTVANGEN (vrij vak kiezen)
     │                            │
     └── al binnen ──────────────►▼
                             'arrived' (Binnengekomen)
                              vak bezet, deadline = +7 dagen
                                  │
                   ┌──────────────┴──────────────┐
                   ▼                             ▼
               UITGEVEN                       RETOUR
          (afhaalcode moet kloppen)
          'picked_up' (Uitgegeven)      'returned' (Retour vervoerder)
               vak weer vrij                 vak weer vrij
```

- **Verwacht**: het pakket is aangekondigd. De klant ziet het al in z'n tijdlijn, maar nog
  zonder afhaalcode. Komt het binnen? Dan kiest de balie een vrij vakje op de pagina **Beheren**.
- **Tijdlijn klant**: `parcel_timeline()` geeft de stapjes *Aangemeld → Binnengekomen → Opgehaald*.
  Wat al is opgehaald of retour is, staat onder **Geschiedenis**.
- **Zoeken**: `search_parcels($zoekterm, $status)` zoekt op afhaalcode, barcode, naam,
  e-mail en vak, en filtert op status (standaard: verwacht + binnen).
- **Afhaalcode**: `generate_pickup_code()` maakt iets als `PK-7X9B` met `random_int()`,
  dus niet te raden. En hij is altijd uniek (de database controleert dat ook met `UNIQUE`).
- **Barcode** (track & trace) is ook uniek. Twee keer hetzelfde pakket registreren? Nope.
- **Te lang liggen**: na `PICKUP_DAYS` (7) dagen komt het pakket op `employee/overdue.php`.
- Registreren, ontvangen, uitgeven, retour en verplaatsen gaan in een **transactie**:
  pakket en vak worden samen opgeslagen, of er gebeurt gewoon niks.

Alle code hiervoor: `includes/functions.php`, kopje **DEEL 5: PAKKETTEN**.

---

## 6. Hoe werken de opslagvakken?

- Elk vak heeft een code (`A-01`) en hoort bij een stelling (`Stelling A`).
- Een vak is `free` (vrij) of `occupied` (bezet). Er past maar één pakket in.
- Voordat er een pakket in gaat, controleert `is_slot_free()` of het vak echt vrij is.
  Daarna zet `set_slot_status()` hem op `occupied`.
- Admins maken nieuwe vakken via **Vakken Beheer**. De balie ziet alles in het raster
  bij **Opslagvakken** en kan een pakket verplaatsen via **Beheren**.

Alle code hiervoor: `includes/functions.php`, kopje **DEEL 4: OPSLAGVAKKEN**.

---

## 7. De database

```
 ┌──────────────┐         ┌──────────────────────┐         ┌──────────────┐
 │   carriers   │ 1     * │       parcels        │ *     1 │ storage_slots│
 │──────────────│◄────────│──────────────────────│────────►│──────────────│
 │ id (PK)      │         │ id (PK)              │         │ id (PK)      │
 │ name         │         │ tracking_code UNIQUE │         │ slot_code    │
 │ is_active    │         │ pickup_code   UNIQUE │         │ rack         │
 └──────────────┘         │ carrier_id      (FK) │         │ status       │
                          │ storage_slot_id (FK) │         └──────────────┘
 ┌──────────────┐ 1     * │ customer_id     (FK) │
 │    users     │◄────────│ received_by_user_id  │
 │──────────────│         │                 (FK) │
 │ id (PK)      │         │ status, deadline ... │
 │ username     │         └──────────────────────┘
 │ email        │
 │ password_hash│
 │ role         │
 └──────────────┘
```

| Foreign key                      | Wat betekent het?                           | Als je de andere kant verwijdert |
|----------------------------------|---------------------------------------------|----------------------------------|
| `parcels.carrier_id`             | welke vervoerder het pakket bracht          | mag niet                         |
| `parcels.storage_slot_id`        | in welk vak het pakket ligt                 | wordt `NULL`                     |
| `parcels.customer_id`            | het klantaccount (als dat er is)            | wordt `NULL`                     |
| `parcels.received_by_user_id`    | welke medewerker het pakket inboekte        | mag niet                         |

---

## 8. Beveiliging, kort en krachtig

| Wat                      | Hoe                                                                   |
|--------------------------|-----------------------------------------------------------------------|
| Wachtwoorden             | Gehasht met `password_hash()`, gecontroleerd met `password_verify()`        |
| SQL injection            | Alleen prepared statements (`?`), echte prepares (`ATTR_EMULATE_PREPARES = false`) |
| XSS                      | Alles wat op het scherm komt gaat door `h()` (`htmlspecialchars`)     |
| CSRF                     | `csrf_field()` in elk formulier, `init.php` controleert het automatisch    |
| Rollen                   | `require_role()` bovenaan elke pagina                                 |
| Sessie                   | HttpOnly + SameSite cookie, strict mode, nieuw ID na inloggen          |
| Invoer controleren           | `validate_*()` functies op de server (HTML `required` is alleen extra) |

---

## 9. Zelf iets toevoegen? Zo doe je dat

**Nieuwe pagina**
1. Maak een bestand in `public/employee/` (of `customer/` / `admin/`).
2. Begin met:
   ```php
   require_once __DIR__ . '/../../includes/init.php';
   require_role(['employee', 'admin']);
   ```
3. Zet `$pagina_titel` en laad `header.php` en `footer.php`.
4. Zet de pagina in het menu via `menu_items()` in `includes/auth.php`.
5. Heb je een formulier met `method="POST"`? Zet er `<?= csrf_field(); ?>` in.

**Nieuwe pakketstatus** → zet hem in de `ENUM` in `sql/schema.sql`, in `PARCEL_STATUSES`
en in `status_badge()` in `includes/functions.php`.

**Nieuwe rol** → zet hem in de `ENUM` van `users.role`, in `ROLES`, `dashboard_url()` en
`menu_items()` in `includes/auth.php`.

**Andere afhaaltermijn** → verander `PICKUP_DAYS` in `includes/config.php`. Klaar.

---

## 10. Projectbriefing – waar vind je wat?

| Wat de briefing vroeg                                   | Waar het zit                                                |
|---------------------------------------------------------|-------------------------------------------------------------|
| Klant: account en contactgegevens beheren               | `customer/profile.php` (naam, telefoon, wachtwoord)         |
| Klant: eigen verwachte en binnengekomen pakketten zien  | `customer/dashboard.php` (kaartjes met tijdlijn + geschiedenis) |
| Klant: afhaalcode en uiterste afhaaldatum zien          | `customer/dashboard.php` (zodra het pakket binnen is)        |
| Balie: pakket registreren met vervoerder en unieke code | `employee/register_parcel.php`                              |
| Balie: vrij opslagvak toewijzen                         | `employee/register_parcel.php`, `employee/edit_parcel.php`  |
| Balie: zoeken op code, klant, vak en status             | `employee/dashboard.php` (zoekbalk + statusfilter)          |
| Max. één actief pakket per vak                          | `is_slot_free()` + `storage_slots.status`                   |
| Afhaalcodes niet te raden                               | `generate_pickup_code()` met `random_int()`                 |
| Elke pakketcode en afhaalcode uniek                     | `UNIQUE` in de database + controle in PHP                      |
| Pakketten die te lang liggen opvallen                   | `employee/overdue.php`                                      |
| Niet het verkeerde pakket meegeven (afhaalcontrole)     | `employee/verify_pickup.php`                                |
| Vakkenraster                                            | `employee/slots.php`                                        |
| Status met kleur én tekst                               | `status_badge()`                                            |
| Meldingen bij succes, fout, lege lijst, niet toegestaan | `set_flash()` + een melding bij elke lege lijst             |
| Werkt op telefoon, tablet en computer                   | Tailwind responsive classes + uitklapmenu op mobiel         |
| Kleuren #0C4A6E, #38BDF8, #FBBF24, #F8FAFC              | `brand`-kleuren in `includes/header.php`                    |

---

## 11. Versie en inleveren

**Definitieve versie:** tag `v1.3` op branch `main`.

| Document                                                         | Waar is het voor?                                     |
|------------------------------------------------------------------|-------------------------------------------------------|
| [docs/installatie-plesk.md](docs/installatie-plesk.md)           | De app online zetten op PLESK                         |
| [docs/eisen-en-verschillen.md](docs/eisen-en-verschillen.md)     | Functie → eis → ontwerp → taak, en wat er anders is   |
| [docs/checklist-controle.md](docs/checklist-controle.md)         | Alle checklistpunten met bewijs                       |
| [docs/demo-draaiboek.md](docs/demo-draaiboek.md)                 | Script voor het demofilmpje (max. 3 minuten)          |
| [github.txt](github.txt)                                         | De link naar deze GitHub                              |
