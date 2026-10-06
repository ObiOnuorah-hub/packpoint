<?php
// ==============================================================================
// SESSIES, INLOGGEN & ROLLEN (includes/auth.php)
// ==============================================================================
// Dit bestand heeft 2 delen:
//   DEEL 1: sessiebeheer (veilige sessie, meldingen, CSRF-beveiliging)
//   DEEL 2: inloggen, uitloggen, rollen en rechten
//
// UITLEG VOOR IEDEREEN:
//   - Een SESSIE is het geheugen van de website voor 1 bezoeker. Zo weet de website
//     bij elke nieuwe pagina nog steeds wie er is ingelogd (net als een armbandje
//     dat je bij de ingang van een festival krijgt).
//   - Een ROL is wat je mag: klant, baliemedewerker of beheerder.
//   - Een WACHTWOORD slaan we nooit op. We slaan alleen een 'hash' op: een onleesbare
//     versie van het wachtwoord, die je niet kunt terugdraaien.
//   - Een COOKIE is een klein bestandje in je browser. Daar staat je 'armbandje' in.

// ==============================================================================
// DEEL 1: SESSIEBEHEER
// ==============================================================================
// Een sessie is het 'geheugen' van de server per bezoeker.
// Hierin onthouden we wie er is ingelogd, meldingen voor de volgende pagina
// en een geheime CSRF-code om formulieren te beveiligen.

// ------------------------------------------------------------------------------
// 1. VEILIGE SESSIE STARTEN
// ------------------------------------------------------------------------------

// Start de sessie met veilige instellingen
function start_secure_session(): void
{
    // Is de sessie al gestart? Dan hoeven we niks te doen.
    // 'return' zonder iets erachter betekent: "stop hier met deze functie".
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Accepteer alleen sessie-ID's die de server zelf heeft gemaakt
    // (zo kan een hacker jou geen eigen sessie-ID 'toeschuiven')
    ini_set('session.use_strict_mode', '1');

    // Stel het sessie-cookie veilig in
    session_set_cookie_params([
        'lifetime' => 0,                       // cookie verdwijnt als de browser sluit
        'path'     => '/',                     // cookie geldt voor de hele website
        'httponly' => true,                    // JavaScript kan het cookie niet lezen (tegen XSS)
        'samesite' => 'Lax',                   // andere websites kunnen het cookie niet meesturen
        'secure'   => !empty($_SERVER['HTTPS']), // via HTTPS? Dan alleen via HTTPS versturen
    ]);

    // Start de sessie
    session_start();
}

// ------------------------------------------------------------------------------
// 2. MELDINGEN (FLASH MESSAGES)
// ------------------------------------------------------------------------------
// Een 'flash' melding wordt 1 keer getoond en verdwijnt daarna.
// Handig na een redirect: "Pakket opgeslagen!" op de volgende pagina.
// Types: 'success' (groen), 'error' (rood), 'warning' (geel)

// Zet een melding klaar
function set_flash(string $type, string $bericht): void
{
    // $_SESSION is het geheugen van deze bezoeker.
    // De [] erachter betekent: "zet dit onderaan in het lijstje" (er kunnen meerdere meldingen zijn).
    $_SESSION['flash'][] = ['type' => $type, 'message' => $bericht];
}

// Haal alle meldingen op en gooi ze daarna weg (zodat ze maar 1 keer verschijnen)
function get_flashes(): array
{
    // Pak de meldingen. De '?? []' betekent: "zijn er geen? Neem dan een lege lijst".
    $meldingen = $_SESSION['flash'] ?? [];

    // Verwijder ze uit het geheugen (unset = weggooien), anders zie je ze elke keer weer
    unset($_SESSION['flash']);

    // Geef ze terug zodat header.php ze kan laten zien
    return $meldingen;
}

// ------------------------------------------------------------------------------
// 3. CSRF BESCHERMING
// ------------------------------------------------------------------------------
// CSRF = een andere website laat jouw browser stiekem een formulier versturen.
// Oplossing: elk formulier krijgt een geheime code mee. Klopt de code niet? Dan weigeren we.

// Geeft de geheime code van deze sessie (maakt er een als die er nog niet is)
function csrf_token(): string
{
    // Nog geen code? Maak een willekeurige, onvoorspelbare code van 64 tekens
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Geef de code terug
    return $_SESSION['csrf_token'];
}

// Maakt een verborgen invoerveld met de code. Zet csrf_field() in ELK formulier met method POST.
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Controleert of de meegestuurde code klopt. Wordt automatisch aangeroepen in init.php
function check_csrf(): void
{
    // Welke code stuurde het formulier mee?
    $meegestuurd = $_POST['csrf_token'] ?? '';

    // Vergelijk de meegestuurde code met de code die wij hebben onthouden.
    // (hash_equals is een extra veilige manier om twee teksten te vergelijken.)
    // Het uitroepteken betekent "NIET": dus "als de codes NIET gelijk zijn...".
    if (!hash_equals(csrf_token(), $meegestuurd)) {
        // Code klopt niet: stop direct. 400 is de internetcode voor "foute aanvraag".
        http_response_code(400);
        exit('Ongeldig formulier. Ga terug, vernieuw de pagina en probeer het opnieuw.');
    }
}

// ==============================================================================
// DEEL 2: INLOGGEN, ROLLEN & RECHTEN
// ==============================================================================
// Hier staat alles over:
//   - inloggen en uitloggen
//   - wie er nu is ingelogd
//   - welke rollen er zijn en wat elke rol mag zien (Role Based Access Control)
//
// HOE WERKT INLOGGEN?
//   1. Gebruiker vult gebruikersnaam + wachtwoord in op login.php
//   2. login() zoekt de gebruiker op in de database
//   3. password_verify() controleert of het wachtwoord bij de opgeslagen hash past
//   4. Klopt het? Dan zetten we het user_id in de sessie
//   5. Elke pagina roept require_role() aan om te controleren of je er mag komen

// ------------------------------------------------------------------------------
// 1. DE ROLLEN
// ------------------------------------------------------------------------------
// Links staat hoe de rol in de database heet, rechts de nette Nederlandse naam.
// Nieuwe rol nodig? Voeg hem hier toe én in de ENUM van users.role in sql/schema.sql.
const ROLES = [
    'customer' => 'Klant',
    'employee' => 'Baliemedewerker',
    'admin'    => 'Beheerder',
];

// Geeft de nette naam van een rol, bijv. 'employee' wordt 'Baliemedewerker'
function role_label(string $rol): string
{
    // Zoek de rol op in de lijst hierboven. Staat hij er niet in? Dan geven we de rol zelf terug.
    return ROLES[$rol] ?? $rol;
}

// Geeft een gekleurd labeltje voor een rol (geel = admin, blauw = medewerker, grijs = klant)
function role_badge(string $rol): string
{
    // Kies de kleur bij de rol (een keuzelijst: "als de rol dit is, dan deze kleur")
    $kleur = match ($rol) {
        'admin'    => 'bg-amber-100 text-amber-900',
        'employee' => 'bg-sky-100 text-sky-900',
        default    => 'bg-slate-100 text-slate-700',
    };

    // Maak er een klein gekleurd labeltje van. h() maakt de tekst veilig om te tonen.
    return '<span class="badge ' . $kleur . '">' . h(role_label($rol)) . '</span>';
}

// Geeft het adres van het dashboard dat bij een rol hoort
function dashboard_url(string $rol): string
{
    // match (PHP 8) kiest de waarde die bij de rol past
    return match ($rol) {
        'customer' => '/customer/dashboard.php',
        'admin'    => '/admin/dashboard.php',
        default    => '/employee/dashboard.php',
    };
}

// Stuurt de ingelogde gebruiker door naar zijn eigen dashboard
function redirect_to_dashboard(): void
{
    // Haal de ingelogde gebruiker op
    $gebruiker = current_user();

    // Stuur door naar het juiste dashboard. Dit stukje lees je zo:
    //   is er een gebruiker?  ja  -> dashboard van zijn rol
    //                         nee -> de loginpagina
    header('Location: ' . ($gebruiker ? dashboard_url($gebruiker['role']) : '/login.php'));

    // Stop dit script meteen
    exit;
}

// Geeft de menu-knoppen die een rol mag zien (wordt gebruikt in header.php)
// Nieuwe pagina gemaakt? Zet hem hier in het menu bij de juiste rol(len).
function menu_items(string $rol): array
{
    // Menu voor klanten
    if ($rol === 'customer') {
        return [
            '/customer/dashboard.php' => 'Mijn Pakketten',
            '/customer/profile.php'   => 'Mijn Profiel',
        ];
    }

    // Menu voor baliemedewerkers (de admin krijgt deze knoppen ook)
    $menu = [
        '/employee/dashboard.php'       => 'Balie Snelzoeken',
        '/employee/register_parcel.php' => '+ Pakket Registreren',
        '/employee/slots.php'           => 'Opslagvakken',
        '/employee/overdue.php'         => 'Te Lang Liggen',
    ];

    // De admin krijgt er nog extra beheer-knoppen bij
    if ($rol === 'admin') {
        // Admin dashboard als eerste knop in het menu (het + plakt twee lijstjes aan elkaar)
        $menu = ['/admin/dashboard.php' => '👑 Admin Dashboard'] + $menu;
        $menu['/admin/users.php'] = 'Gebruikers';
        $menu['/admin/slots_manage.php'] = 'Vakken Beheer';
        $menu['/admin/carriers.php'] = 'Vervoerders';
    }

    // Geef het menu terug
    return $menu;
}

// ------------------------------------------------------------------------------
// 2. INLOGGEN & UITLOGGEN
// ------------------------------------------------------------------------------

// Probeert in te loggen. Geeft true terug als het gelukt is, anders false.
function login(string $inlognaam, string $wachtwoord): bool
{
    // Zoek de gebruiker op gebruikersnaam of e-mailadres
    $gebruiker = find_user_by_login($inlognaam);

    // Bestaat de gebruiker niet? Dan stoppen we met 'false' ("inloggen mislukt").
    // (We zeggen bewust niet of de naam of het wachtwoord fout is, anders weet een hacker welke namen bestaan.)
    if (!$gebruiker) {
        return false;
    }

    // Controleer of het wachtwoord past bij de opgeslagen hash
    // (password_verify hasht het ingevulde wachtwoord en vergelijkt de uitkomst)
    if (!password_verify($wachtwoord, $gebruiker['password_hash'])) {
        return false;
    }

    // Maak een NIEUW sessie-ID aan na het inloggen (tegen 'session fixation')
    session_regenerate_id(true);

    // Onthoud alleen het ID van de gebruiker in de sessie (NOOIT het wachtwoord)
    $_SESSION['user_id'] = (int) $gebruiker['id'];

    // Inloggen gelukt!
    return true;
}

// Logt de gebruiker uit
function logout(): void
{
    // Maak alle sessiegegevens leeg ([] is een lege lijst). Zo weet de website niet meer wie je was.
    $_SESSION = [];

    // Geef de bezoeker een nieuw, leeg sessie-ID
    session_regenerate_id(true);
}

// ------------------------------------------------------------------------------
// 3. WIE IS ER INGELOGD?
// ------------------------------------------------------------------------------

// Geeft de gegevens van de ingelogde gebruiker, of null als niemand is ingelogd
function current_user(): ?array
{
    // Staat er geen user_id in de sessie? Dan is niemand ingelogd.
    // (isset = "bestaat dit en is het niet leeg?")
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    // Haal de VERSE gegevens op uit de database.
    // Zo merken we het meteen als een admin je rol wijzigt of je account verwijdert.
    $gebruiker = find_user_by_id($_SESSION['user_id']);

    // Is het account inmiddels verwijderd? Dan ben je ook niet meer ingelogd
    if (!$gebruiker) {
        unset($_SESSION['user_id']);
        return null;
    }

    // Geef de gebruiker terug
    return $gebruiker;
}

// Is er iemand ingelogd? (true of false)
function is_logged_in(): bool
{
    return current_user() !== null;
}

// ------------------------------------------------------------------------------
// 4. TOEGANG CONTROLEREN (ROLE BASED ACCESS CONTROL)
// ------------------------------------------------------------------------------
// Zet bovenaan elke beveiligde pagina bijvoorbeeld:
//   require_role('admin');                  -> alleen admins
//   require_role(['employee', 'admin']);    -> medewerkers en admins

// Stuurt je naar de loginpagina als je niet bent ingelogd
function require_login(): void
{
    // Niet ingelogd? Melding klaarzetten en naar de loginpagina
    if (!is_logged_in()) {
        set_flash('error', 'Log eerst in om deze pagina te bekijken.');
        header('Location: /login.php');
        exit;
    }
}

// Stuurt je weg als je niet de juiste rol hebt
function require_role(string|array $toegestane_rollen): void
{
    // Eerst controleren of je überhaupt bent ingelogd
    require_login();

    // Maak er altijd een lijstje van (ook als er maar 1 rol is meegegeven, bijv. 'admin').
    // Zo kunnen we hieronder altijd op dezelfde manier zoeken.
    $toegestane_rollen = (array) $toegestane_rollen;

    // Welke rol heeft de ingelogde gebruiker?
    $rol = current_user()['role'];

    // Staat jouw rol niet in het lijstje? Dan mag je hier niet komen.
    // (in_array = "staat dit in de lijst?", het uitroepteken ervoor betekent "NIET")
    if (!in_array($rol, $toegestane_rollen, true)) {
        set_flash('error', 'Je hebt geen rechten om die pagina te bekijken.');
        redirect_to_dashboard();
    }
}
