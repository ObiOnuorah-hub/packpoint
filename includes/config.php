<?php
// config.php
//
// Dit bestand heeft twee delen. Eerst de instellingen van PackPoint (database,
// afhaaltermijn, wachtwoordlengte) en daarna de functie die de verbinding met de
// database maakt. Wil je iets aanpassen, zoals het databasewachtwoord? Dan hoef je
// alleen hier te kijken.


// ------------------------------------------------------------------
// Deel 1: instellingen
// ------------------------------------------------------------------

// De databasegegevens hieronder zijn de standaard van XAMPP: gebruiker 'root' en
// geen wachtwoord.
//
// Zet je de site online op PLESK? Vul dan de gegevens in van de database die je daar
// hebt gemaakt. Doe dat alleen in het bestand op de server en nooit in GitHub,
// anders staat je wachtwoord voor iedereen te lezen.

// Op welke computer draait MySQL? 127.0.0.1 betekent: op deze computer zelf.
const DB_HOST = '127.0.0.1';

// Via welke poort bereiken we MySQL? 3306 is de standaard.
const DB_PORT = 3306;

// Hoe heet de database?
const DB_NAME = 'packpoint';

// Met welke gebruiker loggen we in op MySQL?
const DB_USER = 'root';

// En met welk wachtwoord? Bij XAMPP is dat standaard leeg.
const DB_PASS = '';

// Hoeveel dagen mag een pakket in de winkel blijven liggen? Daarna komt het op de
// lijst 'Te lang liggen'.
const PICKUP_DAYS = 7;

// Hoeveel tekens moet een wachtwoord minimaal hebben?
const MIN_PASSWORD_LENGTH = 6;

// Alle datums en tijden in Nederlandse tijd, anders kloppen de deadlines niet.
date_default_timezone_set('Europe/Amsterdam');


// ------------------------------------------------------------------
// Deel 2: de verbinding met de database
// ------------------------------------------------------------------
// We gebruiken PDO. Dat is de standaardmanier in PHP om veilig met een database te praten.
//
// Let op: importeer eerst sql/schema.sql in phpMyAdmin. Dat bestand maakt alle
// tabellen aan en zet er een paar testgegevens in.

// Geeft de verbinding met de database terug. Elke pagina gebruikt deze functie.
function get_db(): PDO
{
    // 'static' betekent dat PHP deze variabele onthoudt tussen twee aanroepen.
    // Zo maken we maar een keer per pagina verbinding, dat is sneller.
    static $pdo = null;

    // Is er al een verbinding? Dan geven we die gewoon terug.
    if ($pdo !== null) {
        return $pdo;
    }

    // Het "adres" van de database. Met utf8mb4 gaan ook letters als é en ë goed.
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        // Proberen verbinding te maken.
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
    } catch (PDOException $fout) {
        // Lukt het niet? Dan laten we een nette melding zien. De technische fout laten
        // we bewust niet zien, want daar kan gevoelige informatie in staan.
        http_response_code(500);
        exit('<h1>Database niet bereikbaar</h1><p>Staat MySQL aan in het XAMPP Control Panel '
            . 'en heb je sql/schema.sql geïmporteerd in phpMyAdmin?</p>');
    }

    // Gaat er bij een vraag iets mis? Dan willen we een duidelijke fout, geen stilte.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Antwoorden komen terug als lijstje met kolomnamen, bijvoorbeeld $rij['name'].
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Echte "prepared statements" van MySQL gebruiken. Dat is extra bescherming tegen SQL-injectie.
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // MySQL moet dezelfde tijd gebruiken als PHP, anders kloppen de deadlines niet.
    // date('P') geeft bijvoorbeeld '+02:00' terug.
    $pdo->exec("SET time_zone = '" . date('P') . "'");

    // Klaar, de verbinding kan gebruikt worden.
    return $pdo;
}
