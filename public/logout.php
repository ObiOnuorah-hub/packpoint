<?php
// ==============================================================================
// UITLOGGEN (public/logout.php)
// ==============================================================================
// Logt de gebruiker uit en stuurt hem terug naar de loginpagina.

// Laad alles wat we nodig hebben
require_once __DIR__ . '/../includes/init.php';

// Gooi de sessie leeg (de gebruiker is nu uitgelogd)
logout();

// Terug naar de loginpagina met een vriendelijke melding
redirect_with_message('/login.php', 'success', 'Je bent succesvol uitgelogd. Tot ziens!');
