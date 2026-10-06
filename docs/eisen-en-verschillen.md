# Functies, eisen en wat er anders is 🧩

Hier zie je:
1. bij welke **eis**, welk stukje van het **ontwerp** en welke **taak** elke functie hoort
2. waar het eindproduct **anders** is dan de eisen of het ontwerp, en waarom

> **Let op:** vul in de kolom *Taak* het nummer uit je eigen planning in (bijv. `T05`).

---

## 1. Koppeltabel: functie → eis → ontwerp → taak

| #   | Gebouwde functie                                  | Eis uit de briefing                                              | Ontwerp (onderdeel / pagina)                     | Taak | Bestand                                   |
|-----|---------------------------------------------------|------------------------------------------------------------------|--------------------------------------------------|------|-------------------------------------------|
| F01 | Inloggen, naar dashboard per rol                  | Verschillende soorten accounts, ieder ziet alleen zijn rol       | Loginpagina                                      | T__  | `public/login.php`, `includes/auth.php`   |
| F02 | Uitloggen                                         | Verschillende soorten accounts                                   | Menu → Uitloggen                                 | T__  | `public/logout.php`                       |
| F03 | Klantaccount aanmaken                             | Klant: account beheren                                           | Registratiepagina                                | T__  | `public/register.php`                     |
| F04 | Naam, telefoon en wachtwoord wijzigen             | Klant: account en contactgegevens beheren                        | Mijn Profiel                                     | T__  | `public/customer/profile.php`             |
| F05 | Verwachte en binnengekomen pakketten + tijdlijn   | Klant: eigen verwachte en binnengekomen pakketten bekijken       | Klanttijdlijn (bijlage)                          | T__  | `public/customer/dashboard.php`           |
| F06 | Afhaalcode en uiterste afhaaldatum tonen          | Klant: afhaalcode en uiterste afhaaldatum zien                   | Pakketkaartje klant                              | T__  | `public/customer/dashboard.php`           |
| F07 | Pakket registreren (verwacht of binnengekomen)    | Medewerker: pakket registreren met vervoerder en unieke code     | Registratieformulier                             | T__  | `public/employee/register_parcel.php`     |
| F08 | Vrij opslagvak toewijzen / ontvangst registreren  | Medewerker: vrij opslagvak toewijzen                             | Registratieformulier, Pakket Beheren             | T__  | `public/employee/edit_parcel.php`         |
| F09 | Zoeken op code, klant, vak en status              | Medewerker: pakket zoeken op code, klant, vak en status          | Codezoekbalk (bijlage)                           | T__  | `public/employee/dashboard.php`           |
| F10 | Pakket uitgeven met afhaalcode                    | Pakketten veilig uitgeven, verkeerd meegeven voorkomen           | Afhaalcontrole (bijlage)                         | T__  | `public/employee/verify_pickup.php`       |
| F11 | Overzicht van te lang liggende pakketten          | Pakketten die te lang liggen signaleren (achtergrond)            | Signaleringslijst                                | T__  | `public/employee/overdue.php`             |
| F12 | Vakkenraster met bezetting                        | Vrij opslagvak toewijzen                                         | Vakkenraster (bijlage)                           | T__  | `public/employee/slots.php`               |
| F13 | Pakket verplaatsen of retour sturen               | Status aanpassen (rollenlijst opdracht)                          | Pakket Beheren                                   | T__  | `public/employee/edit_parcel.php`         |
| F14 | Gebruikers aanmaken, rol wijzigen, verwijderen    | Gebruikers beheren (rollenlijst opdracht)                        | Gebruikersbeheer                                 | T__  | `public/admin/users.php`                  |
| F15 | Opslagvakken en vervoerders toevoegen             | Beheerfuncties (rollenlijst opdracht)                            | Vakken Beheer, Vervoerders Beheer                | T__  | `public/admin/slots_manage.php`, `carriers.php` |
| F16 | Admin dashboard met cijfers                       | Beheerfuncties (rollenlijst opdracht)                            | Admin Dashboard                                  | T__  | `public/admin/dashboard.php`              |

### Algemene eisen

| Eis uit de briefing                                       | Hoe opgelost                                                              | Waar                                   |
|-----------------------------------------------------------|---------------------------------------------------------------------------|----------------------------------------|
| Een vak bevat maximaal één actief pakket                  | Alleen vrije vakken kiesbaar + controle `is_slot_free()` op de server     | `includes/functions.php` (DEEL 4)      |
| Afhaalcodes niet voorspelbaar                             | `random_int()` in `generate_pickup_code()`                                | `includes/functions.php` (DEEL 5)      |
| Elke pakketcode en afhaalcode is uniek                    | `UNIQUE` in de database + controle in PHP                                 | `sql/schema.sql`, `functions.php`      |
| Wachtwoorden veilig opgeslagen                            | `password_hash()` / `password_verify()`                                   | `functions.php`, `auth.php`            |
| Gegevens controleren en teksten veilig tonen              | `validate_*()` functies en `h()`                                          | `includes/functions.php` (DEEL 1)      |
| Iedere gebruiker ziet alleen wat bij zijn rol hoort       | `require_role()` bovenaan elke pagina                                     | `includes/auth.php`                    |
| Duidelijke meldingen (succes, fout, leeg, niet toegestaan)| `set_flash()` en een melding bij elke lege lijst                          | alle pagina's                          |
| Werkt op telefoon, tablet en computer                     | Tailwind responsive classes + uitklapmenu                                 | `includes/header.php`                  |
| Status met kleur én tekst                                 | `status_badge()`                                                          | `includes/functions.php` (DEEL 5)      |
| Kleuren #0C4A6E, #38BDF8, #FBBF24, #F8FAFC                | `brand`-kleuren in de Tailwind-instellingen                               | `includes/header.php`                  |

---

## 2. Wat is er anders dan de eisen of het ontwerp?

| #  | Wat is anders                                                       | Waarom                                                                                                  |
|----|---------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------|
| V1 | Er is een derde rol: **Admin**                                      | De briefing noemt alleen klant en balie, maar de rollenlijst van de opdracht wil ook een admin voor gebruikersbeheer. De admin kan alles wat de balie kan. |
| V2 | Extra status **Retour vervoerder**                                  | De rollenlijst vraagt "status aanpassen". Ligt een pakket te lang? Dan gaat het terug naar de vervoerder en is het vak weer vrij. |
| V3 | **Verwachte pakketten** meldt de balie zelf aan                     | Een koppeling met de vervoerder (API) hoeft volgens de briefing nog niet. Dus de balie zet het pakket zelf op "Verwacht". |
| V4 | De **afhaalcode** zie je pas als het pakket binnen is               | Anders loopt een klant naar de balie voor een pakket dat er nog niet is.                                |
| V5 | De klant kan zijn **e-mailadres niet zelf aanpassen**               | De pakketten hangen aan dat e-mailadres. Aanpassen kan via de balie.                                    |
| V6 | De **testaccounts** staan op de loginpagina                         | Handig bij het nakijken. Als de app echt gebruikt wordt, moet dit blok weg (staat een comment bij in `login.php`). |
| V7 | **Tailwind CSS** komt via internet (CDN)                            | Hoef je niks voor te installeren. Zonder internet ziet de site er wel kaal uit.                         |

### Wat er (nog) niet in zit (was ook niet gevraagd voor de eerste versie)
- De klant krijgt **geen mailtje** als zijn pakket binnen is.
- Bij registreren wordt het **e-mailadres niet gecheckt** (geen bevestigingsmail).
- Er is **geen limiet** op hoe vaak je mag proberen in te loggen.
