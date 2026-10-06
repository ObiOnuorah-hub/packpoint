<?php
// init.php
//
// Dit is het startsein van elke pagina. Het laadt alles in wat een pagina nodig heeft,
// zodat je niet bij elke pagina hoeft na te denken over welke bestanden erbij horen.
// Elke pagina begint daarom met deze ene regel:
//
//     require_once __DIR__ . '/../includes/init.php';
//
//
// Een korte leeshulp voor de pagina's, voor wie nog nooit code heeft gezien.
// Elke pagina is een mix van twee talen:
//   HTML   de tekst en knoppen die je op het scherm ziet (tussen < en >)
//   PHP    de "hersenen" die bepalen wat er getoond wordt
//
// Dit kom je in de pagina's vaak tegen. De tekens waarmee PHP begint en eindigt
// laten we hier weg, anders denkt de computer dat dit commentaar de code afsluit.
//   h($naam)        laat de tekst uit $naam zien, veilig gemaakt
//   if (...):       als dit klopt, laat dan het volgende stukje zien
//   else:           en als het niet klopt, dit stukje
//   endif;          hier eindigt het als-stukje
//   foreach (...):  doe het volgende stukje voor elk ding in de lijst
//   endforeach;     hier eindigt de herhaling
//   $_POST          alles wat iemand in een formulier heeft ingevuld
//   $_GET           alles wat in de adresbalk achter het vraagteken staat
//   ===  en  !==    "is precies gelijk aan" en "is niet gelijk aan"
//   &&  en  ||      "en" en "of"
//
// Alles met class="..." is alleen opmaak: kleuren, afstanden, lettergrootte.
// Dat verandert niks aan wat de pagina doet.


// Gaat er onverwacht iets mis, bijvoorbeeld met de database? Dan laten we de
// bezoeker een nette melding zien in plaats van een enge technische foutmelding.
function show_error_page(Throwable $fout): void
{
    // De echte fout schrijven we weg in het logboek van de server, voor de ontwikkelaar.
    error_log($fout->getMessage());

    // De bezoeker krijgt een simpele melding.
    http_response_code(500);
    echo '<h1>Er ging iets mis</h1><p>Probeer het opnieuw of ga terug naar de <a href="/index.php">startpagina</a>.</p>';
}
set_exception_handler('show_error_page');

// De instellingen en de verbinding met de database.
require_once __DIR__ . '/config.php';

// Sessies, meldingen, CSRF-beveiliging, inloggen en rollen.
require_once __DIR__ . '/auth.php';

// Alle functies: hulpjes, gebruikers, vervoerders, opslagvakken en pakketten.
require_once __DIR__ . '/functions.php';

// De sessie starten, met veilige cookie-instellingen.
start_secure_session();

// Is er een formulier verstuurd (POST)? Dan controleren we altijd eerst de geheime
// CSRF-code. Omdat dat hier gebeurt, is elk formulier op de hele site automatisch beschermd.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}
