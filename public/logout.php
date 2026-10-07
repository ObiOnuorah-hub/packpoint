<?php
// logout.php
//
// Logt de gebruiker uit en stuurt hem terug naar de loginpagina.
//
// Waarom is dit bestand zo kort? Uitloggen is maar één stap: het geheugen van de bezoeker
// leegmaken. Het is een los bestand omdat de knop "Uitloggen" in het menu naar dit adres
// (/logout.php) verwijst. Er is dus ook geen scherm om te laten zien, alleen een doorverwijzing.

// Eerst alles inladen wat we nodig hebben.
require_once __DIR__ . '/../includes/init.php';

// Het geheugen van deze bezoeker leegmaken. Daarmee ben je uitgelogd.
logout();

// Terug naar de loginpagina, met een vriendelijke melding.
redirect_with_message('/login.php', 'success', 'Je bent succesvol uitgelogd. Tot ziens!');
