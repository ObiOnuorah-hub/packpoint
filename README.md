# PackPoint

Webapp voor een buurtwinkel die pakketjes aanneemt. Pakket komt binnen, gaat in een vakje en de klant haalt het op met een afhaalcode.

Gemaakt met PHP 8, MySQL, PDO en Tailwind CSS.

## Starten

1. Je hebt XAMPP nodig.
2. Zet Apache en MySQL aan.
3. Ga naar <http://localhost/phpmyadmin> en maak een lege database `packpoint`.
4. Klik op `packpoint`, dan Importeren, en kies `sql/schema.sql`.
5. Dubbelklik op `Start-PackPoint.bat`. De site opent op <http://localhost:8000>.

Database-gegevens staan in `includes/config.php`.
Online zetten op PLESK? Kijk in [docs/installatie-plesk.md](docs/installatie-plesk.md).

## Testaccounts

| Rol             | Gebruikersnaam | Wachtwoord |
|-----------------|----------------|------------|
| Klant           | `klant01`      | `klant123` |
| Baliemedewerker | `balie01`      | `balie123` |
| Admin           | `admin01`      | `admin123` |

## Mappen

- `includes/`: code die elke pagina gebruikt (config, inloggen, functies, header en footer)
- `public/`: de pagina's, per rol in `customer/`, `employee/` en `admin/`
- `sql/schema.sql`: tabellen en testdata
- `docs/`: installatie op PLESK en de koppeling tussen eisen en functies
