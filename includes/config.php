<?php
// ==============================================================================
// INSTELLINGEN & DATABASE (includes/config.php)
// ==============================================================================
// Dit bestand heeft 2 delen:
//   DEEL 1: alle instellingen van PackPoint (database, afhaaltermijn, beveiliging)
//   DEEL 2: de verbinding met de database (get_db())
//
// Wil je iets aanpassen (bijv. het databasewachtwoord)? Dan hoef je alleen dit bestand te wijzigen.

// ==============================================================================
// DEEL 1: INSTELLINGEN
// ==============================================================================

// ------------------------------------------------------------------------------
// 1. DATABASE (MySQL)
// ------------------------------------------------------------------------------
// Dit zijn de standaard XAMPP instellingen: gebruiker 'root' zonder wachtwoord.

// Op welke computer draait MySQL? (127.0.0.1 = deze computer)
const DB_HOST = '127.0.0.1';

// Op welke poort luistert MySQL? (3306 is de standaard)
const DB_PORT = 3306;

// Hoe heet onze database?
const DB_NAME = 'packpoint';

// Met welke gebruiker loggen we in op MySQL?
const DB_USER = 'root';

// Wat is het wachtwoord van die gebruiker? (bij XAMPP standaard leeg)
const DB_PASS = '';

// ------------------------------------------------------------------------------
// 2. BUSINESS RULES (de regels van de winkel)
// ------------------------------------------------------------------------------

// Hoeveel dagen mag een pakket in de winkel blijven liggen?
// Daarna komt het op de lijst 'Te lang liggen'.
const PICKUP_DAYS = 7;

// ------------------------------------------------------------------------------
// 3. BEVEILIGING
// ------------------------------------------------------------------------------

// Hoe lang moet een wachtwoord minimaal zijn?
const MIN_PASSWORD_LENGTH = 6;

// ------------------------------------------------------------------------------
// 4. TIJDZONE
// ------------------------------------------------------------------------------

// Zorg dat alle datums en tijden in Nederlandse tijd zijn
date_default_timezone_set('Europe/Amsterdam');

// ==============================================================================
// DEEL 2: DATABASE VERBINDING
// ==============================================================================
// Hier maken we verbinding met onze MySQL database.
// We gebruiken PDO: dat is de standaard manier in PHP om veilig met een database te praten.
//
// LET OP: importeer eerst sql/schema.sql in phpMyAdmin.
// Dat bestand maakt de database 'packpoint' aan met alle tabellen en testgegevens.

// Geeft de databaseverbinding terug. Elke pagina gebruikt deze functie.
function get_db(): PDO
{
    // 'static' betekent: PHP onthoudt deze variabele tussen functie-aanroepen.
    // Zo maken we maar 1 keer per pagina verbinding (dat is sneller).
    static $pdo = null;

    // Hebben we al een verbinding? Geef die dan gewoon terug
    if ($pdo !== null) {
        return $pdo;
    }

    // Het 'adres' van de database, met utf8mb4 zodat ook é en ë goed gaan
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        // Maak verbinding met de database
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
    } catch (PDOException $fout) {
        // Lukt het niet? Laat een nette melding zien (en NIET de technische fout,
        // want daar kan gevoelige info in staan zoals gebruikersnamen)
        http_response_code(500);
        exit('<h1>Database niet bereikbaar</h1><p>Staat MySQL aan in het XAMPP Control Panel '
            . 'en heb je sql/schema.sql geïmporteerd in phpMyAdmin?</p>');
    }

    // Gooi een duidelijke fout (exception) als een query misgaat
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Geef resultaten terug als array met kolomnamen, bijv. $rij['name']
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Gebruik ECHTE prepared statements van MySQL (extra bescherming tegen SQL injection)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Zorg dat MySQL dezelfde tijd gebruikt als PHP (anders kloppen de deadlines niet)
    // date('P') geeft bijvoorbeeld '+02:00' terug
    $pdo->exec("SET time_zone = '" . date('P') . "'");

    // Geef de verbinding terug zodat andere bestanden queries kunnen uitvoeren
    return $pdo;
}
