<?php
// index.php
//
// De startpagina laat zelf niks zien, hij verwijst je meteen door:
//   niet ingelogd  ->  naar de loginpagina
//   wel ingelogd   ->  naar het startscherm van jouw rol

// Eerst alles inladen wat we nodig hebben.
require_once __DIR__ . '/../includes/init.php';

// En dan de bezoeker naar de juiste plek sturen.
redirect_to_dashboard();
