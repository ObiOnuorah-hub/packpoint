# PackPoint – Pakketbeheer

PackPoint is een webapplicatie waarmee een buurtwinkel pakketten registreert, in opslagvakken legt
en veilig uitgeeft aan klanten met een afhaalcode.

Gebouwd met **PHP 8**, **MySQL (MariaDB)**, **PDO** en **Tailwind CSS**.

---

## 1. Starten

1. Zorg dat **XAMPP** is geïnstalleerd (op `C:\xampp` of `D:\xampp`).
2. **Database importeren (alleen de eerste keer):**
   - Start Apache en MySQL in het XAMPP Control Panel.
   - Ga naar <http://localhost/phpmyadmin>, klik op **Importeren** en kies `sql/schema.sql`.
   - Dit maakt de database `packpoint` aan met alle tabellen en testgegevens.
3. Dubbelklik op **`Start-PackPoint.bat`**.
   - MySQL wordt gestart (als die nog niet draait).
   - De PHP-webserver start op <http://localhost:8000>.

> Database-instellingen (gebruiker, wachtwoord) staan in `includes/config.php`.

### Testaccounts

| Rol             | Gebruikersnaam | Wachtwoord | Komt uit op              |
|-----------------|----------------|------------|--------------------------|
| Klant           | `klant01`      | `klant123` | Klant dashboard          |
| Baliemedewerker | `balie01`      | `balie123` | Balie dashboard          |
| Admin           | `admin01`      | `admin123` | Admin dashboard          |

---

## 2. Mappenstructuur

```
packpoint/
├── includes/                 Code die door alle pagina's wordt gebruikt
│   ├── init.php              Laadt ALLES in. Elke pagina begint hiermee.
│   ├── config.php            Instellingen (database, 7 dagen afhaaltermijn) + get_db()
│   ├── auth.php              Deel 1: sessie, meldingen, CSRF
│   │                         Deel 2: inloggen, uitloggen, rollen, menu per rol, toegangscontrole
│   ├── functions.php         Deel 1: hulpjes (h(), format_date(), validatie)
│   │                         Deel 2-5: alle SQL voor gebruikers, vervoerders, opslagvakken, pakketten
│   ├── header.php            Bovenkant van elke pagina (menu alleen als je bent ingelogd)
│   └── footer.php            Onderkant van elke pagina
├── public/                   Alleen deze map is bereikbaar via de browser
│   ├── index.php             Stuurt je door naar login of je dashboard
│   ├── login.php / logout.php / register.php
│   ├── customer/             Pagina's voor klanten
│   ├── employee/             Pagina's voor baliemedewerkers (en admins)
│   └── admin/                Pagina's alleen voor admins
└── sql/schema.sql            Tabellen + testgegevens
```

**Vuistregel:** pagina's in `public/` bevatten zo min mogelijk SQL. Ze roepen functies aan uit
`includes/functions.php`. Zo staat elke query maar op één plek. Zoek in dat bestand op `DEEL`
om snel naar gebruikers, vervoerders, opslagvakken of pakketten te springen.

---

## 3. Hoe werkt inloggen?

1. De gebruiker vult gebruikersnaam (of e-mail) en wachtwoord in op `login.php`.
2. `login()` in `includes/auth.php` zoekt de gebruiker op met `find_user_by_login()`.
3. `password_verify()` controleert het wachtwoord tegen de **bcrypt-hash** in de database.
   Het echte wachtwoord wordt nergens opgeslagen.
4. Klopt het? Dan krijgt de gebruiker een **nieuw sessie-ID** (`session_regenerate_id`) en
   bewaren we alleen het `user_id` in de sessie.
5. `redirect_to_dashboard()` stuurt de gebruiker naar het dashboard van zijn rol.

---

## 4. Hoe werken rollen?

De rollen staan in `ROLES` in `includes/auth.php`:

| Rol (database) | Naam            | Mag                                                                 |
|----------------|-----------------|---------------------------------------------------------------------|
| `customer`     | Klant           | Eigen verwachte en binnengekomen pakketten bekijken (met tijdlijn en geschiedenis), afhaalcode + uiterste afhaaldatum zien, account en contactgegevens beheren |
| `employee`     | Baliemedewerker | Pakket registreren (verwacht of binnen), ontvangst registreren, opslagvak toewijzen, zoeken op code/klant/vak/status, status aanpassen, uitgeven |
| `admin`        | Beheerder       | Alles van de medewerker + gebruikers, vakken en vervoerders beheren |

Elke beveiligde pagina begint met één regel, bijvoorbeeld:

```php
require_role(['employee', 'admin']);   // alleen medewerkers en admins
```

Heb je de verkeerde rol? Dan word je met een melding teruggestuurd naar je eigen dashboard.
Welke menuknoppen je ziet staat in `menu_items()` in `includes/auth.php`.

---

## 5. Hoe werken pakketten?

```
 REGISTREREN (afhaalcode PK-XXXX wordt gemaakt)
     │
     ├── nog niet binnen ──► 'expected' (Verwacht)
     │                            │   geen vak, geen deadline
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
               vak wordt vrij                vak wordt vrij
```

- **Verwacht**: een aangekondigd pakket. De klant ziet het al in zijn tijdlijn, maar nog
  zonder afhaalcode. Bij ontvangst kiest de medewerker een vrij vak op de pagina **Beheren**.
- **Klanttijdlijn**: `parcel_timeline()` geeft de stappen *Aangemeld → Binnengekomen → Opgehaald*.
  Opgehaalde en retour gestuurde pakketten staan onder **Geschiedenis**.
- **Zoeken**: `search_parcels($zoekterm, $status)` zoekt op afhaalcode, barcode, klantnaam,
  e-mail en vak, en filtert op status (standaard: verwacht + binnengekomen).

- **Afhaalcode**: `generate_pickup_code()` maakt een code als `PK-7X9B` met `random_int()`
  (niet te raden) en controleert dat hij uniek is. De database heeft ook een `UNIQUE`-regel.
- **Barcode** (track & trace) is uniek: dubbel registreren wordt geweigerd.
- **Te lang liggen**: na `PICKUP_DAYS` (7) dagen verschijnt het pakket op `employee/overdue.php`.
- Registreren, ontvangen, uitgeven, retour en verplaatsen gebeuren in een **transactie**:
  pakket en vak worden samen bijgewerkt, of er gebeurt niks.

Alle code hiervoor staat in `includes/functions.php` onder **DEEL 5: PAKKETTEN**.

---

## 6. Hoe werken opslagvakken?

- Elk vak heeft een code (`A-01`) en hoort bij een stelling (`Stelling A`).
- Status is `free` (vrij) of `occupied` (bezet). In één vak ligt maximaal één pakket.
- Voordat een pakket in een vak gaat, checkt `is_slot_free()` of het vak echt vrij is.
  Daarna zet `set_slot_status()` het vak op `occupied`.
- Admins voegen vakken toe via **Vakken Beheer**. Medewerkers zien het raster via **Opslagvakken**
  en kunnen een pakket verplaatsen via **Beheren**.

Alle code hiervoor staat in `includes/functions.php` onder **DEEL 4: OPSLAGVAKKEN**.

---

## 7. Database en relaties

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

| Foreign key                      | Betekenis                                   | Bij verwijderen          |
|----------------------------------|---------------------------------------------|--------------------------|
| `parcels.carrier_id`             | welke vervoerder het pakket bracht          | niet toegestaan          |
| `parcels.storage_slot_id`        | in welk vak het pakket ligt                 | wordt `NULL`             |
| `parcels.customer_id`            | klantaccount (als dat bestaat)              | wordt `NULL`             |
| `parcels.received_by_user_id`    | welke medewerker het pakket inboekte        | niet toegestaan          |

---

## 8. Beveiliging in het kort

| Maatregel                | Waar                                                                  |
|--------------------------|-----------------------------------------------------------------------|
| Wachtwoorden gehasht     | `password_hash()` / `password_verify()` in `functions.php`, `auth.php` |
| SQL injection            | Alleen prepared statements (`?`), echte prepares (`ATTR_EMULATE_PREPARES = false`) |
| XSS                      | Alle uitvoer via `h()` (`htmlspecialchars`)                           |
| CSRF                     | `csrf_field()` in elk formulier, automatisch gecontroleerd in `init.php` |
| Rollen en rechten        | `require_role()` bovenaan elke pagina                                 |
| Sessie                   | HttpOnly + SameSite cookie, strict mode, nieuw ID na inloggen |
| Server-side validatie    | `validate_*()` functies; HTML `required` is alleen extra gemak        |

---

## 9. Uitbreiden – zo doe je dat

**Nieuwe pagina toevoegen**
1. Maak een bestand in `public/employee/` (of `customer/` / `admin/`).
2. Begin met:
   ```php
   require_once __DIR__ . '/../../includes/init.php';
   require_role(['employee', 'admin']);
   ```
3. Zet `$pagina_titel` en laad `header.php` en `footer.php`.
4. Zet de pagina in het menu via `menu_items()` in `includes/auth.php`.
5. Formulier met `method="POST"`? Zet er `<?= csrf_field(); ?>` in.

**Nieuwe pakketstatus** → voeg hem toe aan de `ENUM` in `sql/schema.sql`, aan `PARCEL_STATUSES`
en aan `status_badge()` in `includes/functions.php`.

**Nieuwe rol** → voeg hem toe aan de `ENUM` van `users.role`, aan `ROLES`, `dashboard_url()` en
`menu_items()` in `includes/auth.php`.

**Andere afhaaltermijn** → pas `PICKUP_DAYS` aan in
`includes/config.php`.

---

## 10. Projectbriefing – waar vind je wat?

| Wens uit de briefing                                   | Waar in de app                                              |
|--------------------------------------------------------|-------------------------------------------------------------|
| Klant: account en contactgegevens beheren              | `customer/profile.php` (naam, telefoon, wachtwoord)         |
| Klant: eigen verwachte en binnengekomen pakketten zien | `customer/dashboard.php` (kaartjes met tijdlijn + geschiedenis) |
| Klant: afhaalcode en uiterste afhaaldatum zien         | `customer/dashboard.php` (zodra het pakket binnen is)        |
| Medewerker: pakket registreren met vervoerder en unieke code | `employee/register_parcel.php`                        |
| Medewerker: vrij opslagvak toewijzen                   | `employee/register_parcel.php`, `employee/edit_parcel.php`  |
| Medewerker: zoeken op code, klant, vak en status       | `employee/dashboard.php` (zoekbalk + statusfilter)          |
| Een vak bevat maximaal één actief pakket               | `is_slot_free()` + `storage_slots.status`                   |
| Afhaalcodes niet voorspelbaar                          | `generate_pickup_code()` met `random_int()`                 |
| Elke pakketcode en afhaalcode is uniek                 | `UNIQUE` in de database + controle in PHP                   |
| Te lang liggende pakketten signaleren                  | `employee/overdue.php`                                      |
| Verkeerd meegeven voorkomen (afhaalcontrole)           | `employee/verify_pickup.php`                                |
| Vakkenraster                                           | `employee/slots.php`                                        |
| Status met kleur én tekst                              | `status_badge()`                                            |
| Meldingen bij succes, fout, lege lijst, niet toegestaan | `set_flash()` + lege-lijst-teksten op elke pagina          |
| Werkt op telefoon, tablet en computer                  | Tailwind responsive classes + uitklapmenu op mobiel         |
| Kleuren #0C4A6E, #38BDF8, #FBBF24, #F8FAFC             | `brand`-kleuren in `includes/header.php`                    |
