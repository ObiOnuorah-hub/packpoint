<?php
// ==============================================================================
// OPSTARTEN (includes/init.php)
// ==============================================================================
// Dit bestand laadt ALLES wat een pagina nodig heeft.
// Elke pagina begint daarom met 1 regel:
//     require_once __DIR__ . '/../includes/init.php';
//
// Zo hoef je nooit na te denken over welke bestanden je moet inladen.

// Gaat er onverwacht iets mis (bijv. een databasefout)?
// Dan laten we een nette melding zien in plaats van een technische foutmelding.
function show_error_page(Throwable $fout): void
{
    // Schrijf de echte fout in het logbestand van de server (voor de ontwikkelaar)
    error_log($fout->getMessage());

    // Laat de gebruiker een simpele melding zien
    http_response_code(500);
    echo '<h1>Er ging iets mis</h1><p>Probeer het opnieuw of ga terug naar de <a href="/index.php">startpagina</a>.</p>';
}
set_exception_handler('show_error_page');

// Instellingen en databaseverbinding
require_once __DIR__ . '/config.php';

// Sessies, meldingen, CSRF, inloggen en rollen
require_once __DIR__ . '/auth.php';

// Alle functies: hulpjes, gebruikers, vervoerders, opslagvakken en pakketten
require_once __DIR__ . '/functions.php';

// Start de sessie (met veilige cookie-instellingen)
start_secure_session();

// Wordt er een formulier verstuurd (POST)? Check dan ALTIJD eerst de CSRF-code.
// Omdat dit hier staat, is elk formulier op de hele website automatisch beschermd.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}
