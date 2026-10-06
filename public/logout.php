<?php
// logout.php
//
// Logt de gebruiker uit en stuurt hem terug naar de loginpagina.

// Eerst alles inladen wat we nodig hebben.
require_once __DIR__ . '/../includes/init.php';

// Het geheugen van deze bezoeker leegmaken. Daarmee ben je uitgelogd.
logout();

// Terug naar de loginpagina, met een vriendelijke melding.
redirect_with_message('/login.php', 'success', 'Je bent succesvol uitgelogd. Tot ziens!');
