<?php
// index.php
//
// De startpagina laat zelf niks zien, hij verwijst je meteen door:
//   niet ingelogd  ->  naar de loginpagina
//   wel ingelogd   ->  naar het startscherm van jouw rol
//
// Waarom is dit bestand zo kort? Het hoeft maar één ding te doen: de bezoeker doorsturen.
// Het moet wel een los bestand zijn, want als iemand gewoon naar de website gaat (zonder
// iets erachter te typen), zoekt de server altijd naar index.php.

// Eerst alles inladen wat we nodig hebben.
require_once __DIR__ . '/../includes/init.php';

// En dan de bezoeker naar de juiste plek sturen.
redirect_to_dashboard();
