<?php
// ==============================================================================
// STARTPAGINA (public/index.php)
// ==============================================================================
// Deze pagina laat zelf niks zien. Hij stuurt je meteen door:
//   - niet ingelogd -> naar de loginpagina
//   - wel ingelogd  -> naar het dashboard van jouw rol

// Laad alles wat we nodig hebben (database, sessie, functies)
require_once __DIR__ . '/../includes/init.php';

// Stuur de bezoeker naar de juiste plek
redirect_to_dashboard();
