<?php
// auth.php
//
// Dit bestand is het beveiligingshart van PackPoint. Het doet twee dingen:
//   Deel 1  sessies, meldingen en de CSRF-beveiliging
//   Deel 2  inloggen, uitloggen en rollen (wie mag welke pagina zien?)
//
// Een paar begrippen die je hier tegenkomt, zodat je ze zo kunt uitleggen:
//
//   Sessie      Een website onthoudt van zichzelf niks tussen twee klikken. Een sessie
//               is het geheugen van de server: zodra je inlogt, weet de server bij
//               elke volgende pagina nog steeds wie je bent.
//   Cookie      Een klein bestandje in je browser met alleen je sessienummer erin,
//               een beetje zoals het bandje om je pols op een festival. Bij elke klik
//               laat je browser dat bandje zien.
//   Hash        Wachtwoorden slaan we nooit leesbaar op. We maken er een "hash" van,
//               en die kun je niet terugdraaien. Net zoals je van een appeltaart geen
//               appels meer kunt maken. Ook als iemand de database steelt, heeft hij
//               dus geen bruikbare wachtwoorden.
//   Rol         Klant, baliemedewerker of beheerder. Elke rol mag andere dingen.
//               Dit heet ook wel Role Based Access Control, afgekort RBAC.


// ------------------------------------------------------------------
// Deel 1: sessies, meldingen en CSRF
// ------------------------------------------------------------------

// Start de sessie, met alle beveiligingsinstellingen voor het cookie.
function start_secure_session(): void
{
    // Loopt de sessie al? Dan hoeven we niks meer te doen.
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Alleen sessienummers accepteren die onze eigen server heeft bedacht. Zo kan een
    // hacker jou niet vooraf een eigen verzonnen nummer opdringen (dat heet "session fixation").
    ini_set('session.use_strict_mode', '1');

    // Nu de instellingen van het cookie zelf.
    session_set_cookie_params([
        // 0 betekent: het cookie verdwijnt als je de browser sluit. Handig op een
        // gedeelde computer, bijvoorbeeld op school, zodat niemand na jou nog ingelogd is.
        'lifetime' => 0,

        // Het cookie geldt voor de hele website.
        'path'     => '/',

        // JavaScript kan dit cookie niet uitlezen. Komt er toch kwaadaardige code
        // op de pagina, dan kan die je sessie in elk geval niet stelen.
        'httponly' => true,

        // Andere websites kunnen dit cookie niet zomaar meesturen bij een stiekeme
        // aanvraag. Dat helpt tegen CSRF (zie verderop).
        'samesite' => 'Lax',

        // Staat de site op https? Dan sturen we het cookie ook alleen via https.
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);

    // En dan de sessie echt starten.
    session_start();
}

// Meldingen zoals "Pakket opgeslagen!" noemen we flash-meldingen.
// Na het opslaan van een formulier sturen we je door naar een andere pagina, en daar
// laten we de melding precies een keer zien. Daarna is hij weg.

// Zet een melding klaar. Het type is 'success', 'error' of 'warning'.
function set_flash(string $type, string $bericht): void
{
    // $_SESSION is het geheugen van deze bezoeker. De [] betekent: zet het onderaan
    // in de lijst, want er kunnen meerdere meldingen tegelijk zijn.
    $_SESSION['flash'][] = ['type' => $type, 'message' => $bericht];
}

// Haalt alle meldingen op en gooit ze meteen weg, zodat je ze niet twee keer ziet.
function get_flashes(): array
{
    // Zijn er geen meldingen? Dan nemen we een lege lijst. Dat doet '?? []'.
    $meldingen = $_SESSION['flash'] ?? [];

    // Weggooien uit het geheugen.
    unset($_SESSION['flash']);

    // header.php zet ze daarna als gekleurde blokjes op het scherm.
    return $meldingen;
}

// Dan CSRF, een lastige afkorting voor een simpel probleem.
//
// Stel: je bent ingelogd op PackPoint en opent in een ander tabblad een foute website.
// Die site stuurt stiekem een formulier naar PackPoint om iets te verwijderen. Je browser
// stuurt je cookie automatisch mee, dus PackPoint zou denken dat jij het was.
//
// De oplossing: elk formulier krijgt een geheime code mee die ook in jouw sessie op de
// server staat. De foute website kent die code niet en kan dus niks versturen.

// Geeft de geheime code van deze sessie. Is er nog geen, dan maakt hij er een.
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes maakt echt onvoorspelbare getallen, en bin2hex schrijft ze op
        // als 64 leesbare tekens.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Maakt het verborgen veld met de code. Zet dit in elk formulier met method POST.
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Controleert of het formulier de goede code meestuurt. init.php roept dit zelf aan
// bij elk formulier, dus de pagina's hoeven er niet aan te denken.
function check_csrf(): void
{
    // Welke code stuurde het formulier mee? Staat er niks? Dan een lege tekst.
    $meegestuurd = $_POST['csrf_token'] ?? '';

    // We vergelijken met hash_equals en niet met een gewoon ===. Een gewone vergelijking
    // stopt zodra het eerste teken niet klopt, en een hacker kan aan die minimale
    // tijdverschillen afleiden hoeveel tekens hij al goed had. hash_equals kijkt altijd
    // naar alle tekens en lekt dus niks. Het uitroepteken ervoor betekent "niet".
    if (!hash_equals(csrf_token(), $meegestuurd)) {
        // Klopt de code niet, of ontbreekt hij? Dan stoppen we meteen.
        // 400 is de internetcode voor "foute aanvraag".
        http_response_code(400);
        exit('Ongeldig formulier (CSRF-beveiliging). Ga terug, vernieuw de pagina en probeer het opnieuw.');
    }
}


// ------------------------------------------------------------------
// Deel 2: inloggen, rollen en rechten
// ------------------------------------------------------------------

// De drie rollen. Links staat hoe ze in de database heten, rechts hoe ze op het scherm staan.
// Komt er een rol bij? Zet hem dan hier neer en ook in de ENUM van users.role in schema.sql.
const ROLES = [
    'customer' => 'Klant',
    'employee' => 'Baliemedewerker',
    'admin'    => 'Beheerder',
];

// Geeft de nette naam van een rol, bijvoorbeeld 'employee' wordt 'Baliemedewerker'.
function role_label(string $rol): string
{
    // Staat de rol niet in de lijst hierboven? Dan geven we de rol zelf terug.
    return ROLES[$rol] ?? $rol;
}

// Maakt een klein gekleurd labeltje voor een rol: geel voor admin, blauw voor
// medewerker en grijs voor klant.
function role_badge(string $rol): string
{
    // Een keuzelijst: is de rol dit, dan deze kleur.
    $kleur = match ($rol) {
        'admin'    => 'bg-amber-100 text-amber-900',
        'employee' => 'bg-sky-100 text-sky-900',
        default    => 'bg-slate-100 text-slate-700',
    };

    // h() maakt de tekst veilig om te tonen.
    return '<span class="badge ' . $kleur . '">' . h(role_label($rol)) . '</span>';
}

// Welk startscherm hoort bij welke rol?
function dashboard_url(string $rol): string
{
    return match ($rol) {
        'customer' => '/customer/dashboard.php',
        'admin'    => '/admin/dashboard.php',
        default    => '/employee/dashboard.php',
    };
}

// Stuurt de ingelogde gebruiker naar zijn eigen startscherm.
function redirect_to_dashboard(): void
{
    $gebruiker = current_user();

    // Dit lees je zo: is er een gebruiker? Dan naar het startscherm van zijn rol.
    // Is er niemand ingelogd? Dan naar de loginpagina.
    header('Location: ' . ($gebruiker ? dashboard_url($gebruiker['role']) : '/login.php'));
    exit;
}

// Bepaalt welke knoppen iemand in het menu ziet, afhankelijk van zijn rol.
// Maak je een nieuwe pagina? Zet hem hier in het menu bij de juiste rol.
function menu_items(string $rol): array
{
    // Een klant ziet alleen zijn eigen pakketten en zijn profiel.
    if ($rol === 'customer') {
        return [
            '/customer/dashboard.php' => 'Mijn Pakketten',
            '/customer/profile.php'   => 'Mijn Profiel',
        ];
    }

    // De knoppen voor de balie. De admin krijgt deze ook.
    $menu = [
        '/employee/dashboard.php'       => 'Balie Snelzoeken',
        '/employee/register_parcel.php' => '+ Pakket Registreren',
        '/employee/slots.php'           => 'Opslagvakken',
        '/employee/overdue.php'         => 'Te Lang Liggen',
    ];

    // De admin heeft er nog de beheerknoppen bij. Het Admin Dashboard zetten we vooraan.
    if ($rol === 'admin') {
        $menu = ['/admin/dashboard.php' => '👑 Admin Dashboard'] + $menu;
        $menu['/admin/users.php'] = 'Gebruikers';
        $menu['/admin/slots_manage.php'] = 'Vakken Beheer';
        $menu['/admin/carriers.php'] = 'Vervoerders';
    }

    return $menu;
}

// Probeert in te loggen. Geeft true terug als het lukt, anders false.
function login(string $inlognaam, string $wachtwoord): bool
{
    // Zoek het account op, met de gebruikersnaam of het e-mailadres.
    $gebruiker = find_user_by_login($inlognaam);

    // Bestaat het account niet? Dan geven we gewoon false terug. We zeggen bewust
    // niet of de naam of het wachtwoord fout was. Anders kan een hacker uitproberen
    // welke namen wel bestaan.
    if (!$gebruiker) {
        return false;
    }

    // Klopt het wachtwoord? password_verify maakt van wat je intypt opnieuw een hash en
    // kijkt of die overeenkomt met de hash in de database. We slaan wachtwoorden nooit
    // leesbaar op, dus zo controleren we het.
    if (!password_verify($wachtwoord, $gebruiker['password_hash'])) {
        return false;
    }

    // Een belangrijk moment. Je bent nu van onbekende bezoeker ingelogde gebruiker
    // geworden, dus geven we je een nieuw sessienummer en vernietigen we het oude.
    // Zo kan niemand een nummer van voor het inloggen nog gebruiken.
    session_regenerate_id(true);

    // In de sessie bewaren we alleen je nummer. Geen wachtwoord en ook geen rol: de rest
    // halen we steeds vers uit de database via current_user().
    $_SESSION['user_id'] = (int) $gebruiker['id'];

    return true;
}

// Logt de gebruiker uit.
function logout(): void
{
    // Eerst alles uit het geheugen halen. Een [] is een lege lijst.
    $_SESSION = [];

    // En de bezoeker een nieuw, leeg sessienummer geven.
    session_regenerate_id(true);
}

// Geeft de gegevens van de ingelogde gebruiker terug, of null als niemand is ingelogd.
function current_user(): ?array
{
    // Staat er geen user_id in de sessie? Dan is er niemand ingelogd.
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    // We halen de gegevens elke keer opnieuw uit de database. Past een beheerder
    // tijdens jouw sessie je rol aan, of verwijdert hij je account? Dan merken we dat
    // direct, zonder dat je eerst opnieuw hoeft in te loggen.
    $gebruiker = find_user_by_id($_SESSION['user_id']);

    // Is het account ondertussen verwijderd? Dan ben je ook niet meer ingelogd.
    if (!$gebruiker) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $gebruiker;
}

// Is er iemand ingelogd? Ja of nee.
function is_logged_in(): bool
{
    return current_user() !== null;
}

// De twee functies hieronder zijn de portiers. Zet er een bovenaan een pagina en hij
// laat alleen binnen wie er mag komen, bijvoorbeeld:
//   require_role('admin');                 alleen admins
//   require_role(['employee', 'admin']);   medewerkers en admins

// Stuurt je naar de loginpagina als je niet bent ingelogd.
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Log eerst in om deze pagina te bekijken.');
        header('Location: /login.php');
        exit;
    }
}

// Stuurt je weg als je niet de juiste rol hebt.
function require_role(string|array $toegestane_rollen): void
{
    // Eerst moet je sowieso ingelogd zijn.
    require_login();

    // Er is soms maar een rol meegegeven, bijvoorbeeld 'admin'. We maken er altijd een
    // lijstje van, zodat we hieronder op een manier kunnen zoeken.
    $toegestane_rollen = (array) $toegestane_rollen;

    // Welke rol heeft de ingelogde gebruiker nu?
    $rol = current_user()['role'];

    // Staat jouw rol niet in het lijstje? Dan mag je hier niet komen. (in_array kijkt of
    // iets in een lijst staat, en het uitroepteken ervoor betekent "niet".)
    if (!in_array($rol, $toegestane_rollen, true)) {
        set_flash('error', 'Je hebt geen rechten om die pagina te bekijken.');
        redirect_to_dashboard();
    }
}
