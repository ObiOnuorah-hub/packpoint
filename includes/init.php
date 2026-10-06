<?php
// ==============================================================================
// OPSTARTEN (includes/init.php)
// ==============================================================================
// Dit bestand laadt ALLES wat een pagina nodig heeft.
// Elke pagina begint daarom met 1 regel:
//     require_once __DIR__ . '/../includes/init.php';
//
// Zo hoef je nooit na te denken over welke bestanden je moet inladen.

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
