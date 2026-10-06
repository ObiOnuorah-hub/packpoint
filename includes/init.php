<?php
// ==============================================================================
// OPSTARTEN (includes/init.php)
// ==============================================================================
// Dit bestand laadt ALLES wat een pagina nodig heeft.
// Elke pagina begint daarom met 1 regel:
//     require_once __DIR__ . '/../includes/init.php';
//
// Zo hoef je nooit na te denken over welke bestanden je moet inladen.
//
// ------------------------------------------------------------------------------
// LEESHULP VOOR ALLE PAGINA'S (voor wie niks van code weet)
// ------------------------------------------------------------------------------
// Elke pagina is een mix van twee talen:
//   - HTML: de tekst en knoppen die je op het scherm ziet (stukjes tussen < en >).
//   - PHP:  de "hersenen" die bepalen wat er getoond wordt (stukjes die beginnen
//           met een speciaal openingsteken en eindigen met een sluitteken).
//
// Veelvoorkomende PHP-stukjes in de pagina's (het openings- en sluitteken laten
// we hier weg, anders denkt de computer dat dit commentaar de code afsluit):
//   h($naam)                toont de tekst in $naam op het scherm (veilig gemaakt)
//   if (...):               "als dit klopt, laat dan het volgende stukje zien"
//   else:                   "en zo niet, laat dan dit zien"
//   endif;                  hier houdt het "als"-stukje op
//   foreach (...):          "doe het volgende stukje voor elk item in de lijst"
//   endforeach;             hier houdt de herhaling op
//   $_POST                 alles wat iemand in een formulier heeft ingevuld
//   $_GET                   alles wat in de adresbalk achter het ? staat
//   ===  en  !==            "is precies gelijk aan" en "is NIET gelijk aan"
//   &&  en  ||              "en" en "of"
//
// De stukjes met 'class="..."' zijn alleen opmaak (kleuren, afstanden, lettergrootte).
// Ze veranderen niets aan wat de pagina doet.

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

// Wordt er een formulier verstuurd (POST)? Controleer dan ALTIJD eerst de CSRF-code.
// Omdat dit hier staat, is elk formulier op de hele website automatisch beschermd.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}
