<?php
// ==============================================================================
// ALLE FUNCTIES (includes/functions.php)
// ==============================================================================
// Dit bestand heeft 5 delen. Zoek op 'DEEL' om snel te springen:
//   DEEL 1: algemene hulpfuncties (h(), datums, validatie)
//   DEEL 2: gebruikers      (tabel users)
//   DEEL 3: vervoerders     (tabel carriers)
//   DEEL 4: opslagvakken    (tabel storage_slots)
//   DEEL 5: pakketten       (tabel parcels)
//
// LET OP: we gebruiken ALTIJD prepared statements met ? in de query.
// De waarden gaan los naar de database, dus SQL injection is niet mogelijk.
//
// ------------------------------------------------------------------------------
// LEESHULP: ZO LEES JE DIT BESTAND ALS JE NIKS VAN CODE WEET
// ------------------------------------------------------------------------------
// Een FUNCTIE is een klein programmaatje met een naam. Je roept hem aan als je
//   hem nodig hebt. Voorbeeld: find_parcel(5) betekent "zoek pakket nummer 5".
// Een VARIABELE begint met een $ en is een doosje waar iets in zit.
//   Voorbeeld: $pakket is het doosje met alle gegevens van 1 pakket.
// De DATABASE is een grote digitale kast met tabellen (zoals Excel).
//   In de tabel 'parcels' staan alle pakketten, in 'users' alle gebruikers.
// SQL is de taal waarmee je de database iets vraagt.
//   SELECT = "geef mij"      INSERT = "voeg toe"
//   UPDATE = "pas aan"       DELETE = "verwijder"
// Een ? in een SQL-opdracht is een LEGE PLEK. De echte waarde wordt er apart
//   in gestopt. Dat is veilig: een hacker kan zo geen eigen opdrachten verstoppen.
// 'null' betekent "niks" of "bestaat niet". Een functie die null teruggeeft,
//   zegt dus: "ik heb niks gevonden".
// 'return' betekent "dit is mijn antwoord" en daarmee stopt de functie.
// Een TRANSACTIE is een pakketje van acties dat samen slaagt of samen mislukt.
//   Net als bij een pinbetaling: of het geld gaat er helemaal af, of niet.
// 'status' vertelt waar een pakket is in zijn reis:
//   expected = verwacht (nog onderweg)      arrived = binnen, ligt in een vak
//   picked_up = opgehaald door de klant      returned = terug naar de vervoerder

// ==============================================================================
// DEEL 1: ALGEMENE HULPFUNCTIES
// ==============================================================================
// Kleine functies die we op heel veel pagina's gebruiken.
// Zo hoeven we dezelfde code niet steeds opnieuw te typen.

// ------------------------------------------------------------------------------
// 1. VEILIG TONEN & DOORSTUREN
// ------------------------------------------------------------------------------

// Maakt tekst veilig om op het scherm te tonen (tegen XSS-aanvallen)
// Gebruik ALTIJD h() als je iets uit de database of een formulier op de pagina zet.
function h(?string $tekst): string
{
    // htmlspecialchars maakt van < en > onschuldige tekens zoals &lt; en &gt;
    return htmlspecialchars($tekst ?? '', ENT_QUOTES, 'UTF-8');
}

// Geeft de eerder ingevulde waarde van een formulierveld terug (veilig gemaakt)
// Handig bij een foutmelding: dan hoeft de gebruiker niet alles opnieuw te typen.
function old(string $veldnaam): string
{
    // $_POST is alles wat de gebruiker net in het formulier heeft ingevuld.
    // Is dat veld leeg of niet verstuurd? Dan nemen we een lege tekst ('').
    return h($_POST[$veldnaam] ?? '');
}

// Stuurt de bezoeker door naar een andere pagina, met een melding erbij
function redirect_with_message(string $adres, string $type, string $bericht): void
{
    // Zet de melding klaar voor de volgende pagina
    set_flash($type, $bericht);

    // Zeg tegen de browser: "ga naar een andere pagina" (header Location = doorverwijzing)
    header('Location: ' . $adres);

    // Stop dit script meteen. Alles hieronder wordt niet meer uitgevoerd.
    exit;
}

// ------------------------------------------------------------------------------
// 2. DATUMS
// ------------------------------------------------------------------------------

// Maakt van een database-datum een Nederlandse datum, bijv. 05-10-2026 14:30
function format_date(?string $datum): string
{
    // Geen datum? Laat een streepje zien
    if (!$datum) {
        return '-';
    }

    // De database geeft een datum als 2026-10-05 14:30:00.
    // strtotime leest die datum, en date() maakt er 05-10-2026 14:30 van (dag-maand-jaar uur:minuut).
    return date('d-m-Y H:i', strtotime($datum));
}

// ------------------------------------------------------------------------------
// 3. INVOER CONTROLEREN (SERVER-SIDE VALIDATIE)
// ------------------------------------------------------------------------------
// Controleer ALTIJD op de server. Een 'required' in HTML kan een hacker gewoon weghalen!

// Is dit een geldig e-mailadres? (bijv. naam@voorbeeld.nl)
function is_valid_email(string $email): bool
{
    // filter_var is de ingebouwde PHP-controle voor e-mailadressen
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Is dit een geldig telefoonnummer? Leeg mag ook (want telefoon is niet verplicht).
function is_valid_phone(string $telefoon): bool
{
    // Leeg is goed
    if ($telefoon === '') {
        return true;
    }

    // preg_match controleert of de tekst past op een "patroon":
    //   [0-9+\-\s] = alleen cijfers, een plus, een min of een spatie
    //   {6,20}     = tussen de 6 en 20 tekens lang
    // Past de tekst op het patroon? Dan geeft preg_match 1 terug.
    return preg_match('/^[0-9+\-\s]{6,20}$/', $telefoon) === 1;
}

// Is deze tekst te lang? (bijv. langer dan wat in de database past)
function is_too_long(string $tekst, int $maximaal): bool
{
    // mb_strlen telt tekens als é en ë goed als 1 teken
    return mb_strlen($tekst) > $maximaal;
}

// Controleert naam, e-mail en telefoon in 1 keer.
// Geeft een foutmelding terug, of null als alles goed is.
function validate_contact_details(string $naam, string $email, string $telefoon): ?string
{
    // Naam en e-mail zijn verplicht
    if ($naam === '' || $email === '') {
        return 'Vul a.u.b. alle verplichte velden in.';
    }

    // Naam mag niet te lang zijn (max 100 tekens, zie database)
    if (is_too_long($naam, 100)) {
        return 'De naam mag maximaal 100 tekens lang zijn.';
    }

    // E-mailadres moet echt een e-mailadres zijn
    if (!is_valid_email($email) || is_too_long($email, 150)) {
        return 'Vul een geldig e-mailadres in.';
    }

    // Telefoonnummer moet kloppen (als het is ingevuld)
    if (!is_valid_phone($telefoon)) {
        return 'Vul een geldig telefoonnummer in (alleen cijfers, spaties, + of -).';
    }

    // Alles is goed!
    return null;
}

// Controleert of een nieuw wachtwoord goed genoeg is.
// $herhaling is het 'bevestig wachtwoord' veld (laat weg als het formulier dat niet heeft).
// Geeft een foutmelding terug, of null als het wachtwoord goed is.
function validate_new_password(string $wachtwoord, ?string $herhaling = null): ?string
{
    // Wachtwoord moet lang genoeg zijn
    if (mb_strlen($wachtwoord) < MIN_PASSWORD_LENGTH) {
        return 'Het wachtwoord moet minimaal ' . MIN_PASSWORD_LENGTH . ' tekens bevatten.';
    }

    // De twee ingevulde wachtwoorden moeten hetzelfde zijn (als er een herhaling is)
    if ($herhaling !== null && $wachtwoord !== $herhaling) {
        return 'De ingevulde wachtwoorden komen niet overeen.';
    }

    // Wachtwoord is goed!
    return null;
}

// ==============================================================================
// DEEL 2: GEBRUIKERS
// ==============================================================================
// Alle database-code voor de tabel 'users' staat hier bij elkaar.
// Pagina's roepen deze functies aan, zodat er nergens dubbele SQL staat.
//
// LET OP: we gebruiken ALTIJD prepared statements met ? in de query.
// De waarden gaan los naar de database, dus SQL injection is niet mogelijk.

// Deze kolommen mag de rest van de website zien (dus NIET de password_hash)
const USER_COLUMNS = 'id, username, name, email, phone, role, created_at';

// ------------------------------------------------------------------------------
// 1. GEBRUIKERS OPZOEKEN
// ------------------------------------------------------------------------------

// Zoekt een gebruiker op gebruikersnaam OF e-mailadres (voor het inloggen)
// Deze functie geeft WEL de password_hash mee, want die is nodig om het wachtwoord te controleren.
function find_user_by_login(string $inlognaam): ?array
{
    // Haal spaties weg aan het begin en eind
    $inlognaam = trim($inlognaam);

    // Zoek de gebruiker (MySQL vergelijkt hier niet hoofdlettergevoelig, dus Klant01 = klant01)
    $query = get_db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
    $query->execute([$inlognaam, $inlognaam]);

    // fetch() pakt de eerste gevonden rij uit de database.
    // Het stukje '?: null' betekent: "is er niks gevonden? Geef dan null (niks) terug".
    return $query->fetch() ?: null;
}

// Zoekt een gebruiker op ID (zonder wachtwoord-hash)
function find_user_by_id(int $id): ?array
{
    // Stap 1: maak de opdracht klaar (het ? is een lege plek voor het ID)
    $query = get_db()->prepare('SELECT ' . USER_COLUMNS . ' FROM users WHERE id = ?');

    // Stap 2: voer de opdracht uit en stop het ID in de lege plek
    $query->execute([$id]);

    // Stap 3: geef de gevonden gebruiker terug, of null als die niet bestaat
    return $query->fetch() ?: null;
}

// Zoekt het ID van een gebruiker met dit e-mailadres (of null als die niet bestaat)
function find_user_id_by_email(string $email): ?int
{
    $query = get_db()->prepare('SELECT id FROM users WHERE email = ?');
    $query->execute([$email]);

    // fetchColumn geeft de waarde van de eerste kolom, of false als er niks is
    $id = $query->fetchColumn();
    return $id === false ? null : (int) $id;
}

// Bestaat er al een account met dit e-mailadres?
function email_exists(string $email): bool
{
    return find_user_id_by_email($email) !== null;
}

// Haalt alle gebruikers op, gesorteerd op rol en naam
function get_all_users(): array
{
    return get_db()->query('SELECT ' . USER_COLUMNS . ' FROM users ORDER BY role, name')->fetchAll();
}

// Telt hoeveel gebruikers er zijn
function count_users(): int
{
    return (int) get_db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

// ------------------------------------------------------------------------------
// 2. GEBRUIKERS AANMAKEN
// ------------------------------------------------------------------------------

// Bedenkt een unieke gebruikersnaam op basis van het e-mailadres.
// jan@mail.nl wordt 'jan'. Bestaat 'jan' al? Dan wordt het 'jan2', 'jan3', enz.
function make_unique_username(string $email): string
{
    // Pak het stuk voor de @ en houd alleen letters, cijfers, punt, - en _ over
    $basis = preg_replace('/[^a-z0-9._-]/', '', strtolower(explode('@', $email)[0]));

    // Maximaal 40 tekens (in de database passen er 50, zo is er nog ruimte voor een nummer)
    $basis = substr($basis, 0, 40);

    // Blijft er niks over? Gebruik dan 'gebruiker'
    if ($basis === '') {
        $basis = 'gebruiker';
    }

    // Probeer eerst de basisnaam, daarna basis2, basis3, ...
    $gebruikersnaam = $basis;
    $nummer = 2;
    $query = get_db()->prepare('SELECT COUNT(*) FROM users WHERE username = ?');

    // 'while (true)' is een herhaling die doorgaat tot we zelf 'return' zeggen.
    while (true) {
        // Kijk hoeveel gebruikers al deze naam hebben
        $query->execute([$gebruikersnaam]);

        // Is dat er 0? Dan is de naam vrij en zijn we klaar
        if ((int) $query->fetchColumn() === 0) {
            return $gebruikersnaam;
        }

        // Anders: bedenk een nieuwe naam met een hoger nummer (jan2, jan3, ...) en probeer opnieuw
        $gebruikersnaam = $basis . $nummer;
        $nummer++;
    }
}

// Maakt een nieuwe gebruiker aan en geeft het nieuwe ID terug
function create_user(string $naam, string $email, string $telefoon, string $wachtwoord, string $rol): int
{
    // Versleutel het wachtwoord met een veilige hash (bcrypt).
    // Het echte wachtwoord slaan we NOOIT op!
    $hash = password_hash($wachtwoord, PASSWORD_DEFAULT);

    // Bedenk een unieke gebruikersnaam
    $gebruikersnaam = make_unique_username($email);

    // Sla de gebruiker op in de database
    $db = get_db();
    $query = $db->prepare('INSERT INTO users (username, name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)');
    // Bij het telefoonnummer staat '?: null': is het veld leeg? Dan slaan we "niks" op.
    $query->execute([$gebruikersnaam, $naam, $email, $telefoon ?: null, $hash, $rol]);

    // De database geeft elke nieuwe gebruiker zelf een nummer (ID). Dat geven wij terug.
    return (int) $db->lastInsertId();
}

// ------------------------------------------------------------------------------
// 3. GEBRUIKERS WIJZIGEN & VERWIJDEREN
// ------------------------------------------------------------------------------

// Wijzigt naam en telefoonnummer van een gebruiker
function update_user_profile(int $id, string $naam, string $telefoon): void
{
    $query = get_db()->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?');
    $query->execute([$naam, $telefoon ?: null, $id]);
}

// Zet een nieuw wachtwoord (wordt eerst gehasht)
function update_user_password(int $id, string $nieuw_wachtwoord): void
{
    $query = get_db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $query->execute([password_hash($nieuw_wachtwoord, PASSWORD_DEFAULT), $id]);
}

// Wijzigt de rol van een gebruiker
function update_user_role(int $id, string $rol): void
{
    $query = get_db()->prepare('UPDATE users SET role = ? WHERE id = ?');
    $query->execute([$rol, $id]);
}

// Heeft deze gebruiker (als medewerker) pakketten ingeboekt?
// Dan kunnen we hem niet verwijderen, want dan klopt de pakketgeschiedenis niet meer.
function user_has_registered_parcels(int $id): bool
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM parcels WHERE received_by_user_id = ?');
    $query->execute([$id]);
    return (int) $query->fetchColumn() > 0;
}

// Verwijdert een gebruiker.
// Pakketten van een klant blijven bestaan: customer_id wordt dan vanzelf NULL (zie ON DELETE SET NULL).
function delete_user(int $id): void
{
    $query = get_db()->prepare('DELETE FROM users WHERE id = ?');
    $query->execute([$id]);
}

// ==============================================================================
// DEEL 3: VERVOERDERS
// ==============================================================================
// Alle database-code voor de tabel 'carriers' (PostNL, DHL, enz.) staat hier.

// Haalt alle vervoerders op (op alfabet)
function get_all_carriers(): array
{
    return get_db()->query('SELECT * FROM carriers ORDER BY name')->fetchAll();
}

// Haalt alleen de ACTIEVE vervoerders op (voor het keuzemenu bij pakket registreren)
function get_active_carriers(): array
{
    return get_db()->query('SELECT * FROM carriers WHERE is_active = 1 ORDER BY name')->fetchAll();
}

// Telt hoeveel actieve vervoerders er zijn
function count_active_carriers(): int
{
    return (int) get_db()->query('SELECT COUNT(*) FROM carriers WHERE is_active = 1')->fetchColumn();
}

// Bestaat deze vervoerder en is hij actief?
function is_active_carrier(int $id): bool
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM carriers WHERE id = ? AND is_active = 1');
    $query->execute([$id]);
    return (int) $query->fetchColumn() > 0;
}

// Bestaat er al een vervoerder met deze naam?
function carrier_name_exists(string $naam): bool
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM carriers WHERE name = ?');
    $query->execute([$naam]);
    return (int) $query->fetchColumn() > 0;
}

// Voegt een nieuwe (actieve) vervoerder toe
function add_carrier(string $naam): void
{
    $query = get_db()->prepare('INSERT INTO carriers (name, is_active) VALUES (?, 1)');
    $query->execute([$naam]);
}

// ==============================================================================
// DEEL 4: OPSLAGVAKKEN
// ==============================================================================
// Alle database-code voor de tabel 'storage_slots' staat hier.
//
// HOE WERKEN OPSLAGVAKKEN?
//   - Elk vak heeft een code (A-01) en hoort bij een stelling (Stelling A)
//   - Een vak is 'free' (vrij) of 'occupied' (bezet)
//   - In 1 vak ligt maximaal 1 pakket
//   - Pakket registreren  -> vak wordt 'occupied'
//   - Pakket uitgeven     -> vak wordt weer 'free'
//   - Pakket retour       -> vak wordt weer 'free'

// ------------------------------------------------------------------------------
// 1. VAKKEN OPHALEN
// ------------------------------------------------------------------------------

// Haalt alle vakken op, gesorteerd op stelling en vakcode
function get_all_slots(): array
{
    return get_db()->query('SELECT * FROM storage_slots ORDER BY rack, slot_code')->fetchAll();
}

// Haalt alleen de VRIJE vakken op (om een pakket in te leggen)
function get_free_slots(): array
{
    return get_db()->query("SELECT * FROM storage_slots WHERE status = 'free' ORDER BY rack, slot_code")->fetchAll();
}

// Haalt alle vakken op MET het pakket dat er nu in ligt (voor het vakkenraster)
function get_slots_with_parcels(): array
{
    // LEFT JOIN: we willen ook de vakken zonder pakket zien
    return get_db()->query("
        SELECT s.*,
               p.id AS parcel_id, p.pickup_code, p.customer_name, p.pickup_deadline,
               c.name AS carrier_name
        FROM storage_slots s
        LEFT JOIN parcels p  ON p.storage_slot_id = s.id AND p.status = 'arrived'
        LEFT JOIN carriers c ON c.id = p.carrier_id
        ORDER BY s.rack, s.slot_code
    ")->fetchAll();
}

// Zet een lijst met vakken in groepjes per stelling
// Uitkomst: ['Stelling A' => [vak, vak, ...], 'Stelling B' => [...]]
function group_slots_by_rack(array $vakken): array
{
    // Begin met een lege lijst
    $stellingen = [];

    // 'foreach' betekent: "doe dit voor elk vak in de lijst, een voor een".
    // We leggen elk vak in het groepje van zijn eigen stelling.
    foreach ($vakken as $vak) {
        $stellingen[$vak['rack']][] = $vak;
    }

    return $stellingen;
}

// Telt hoeveel vakken een bepaalde status hebben ('free' of 'occupied')
function count_slots_by_status(string $status): int
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM storage_slots WHERE status = ?');
    $query->execute([$status]);
    return (int) $query->fetchColumn();
}

// ------------------------------------------------------------------------------
// 2. VAKKEN CONTROLEREN
// ------------------------------------------------------------------------------

// Is dit vak vrij? (geeft ook false als het vak niet bestaat)
function is_slot_free(int $vak_id): bool
{
    // Kijk in de database wat de status van het vak is
    $query = get_db()->prepare('SELECT status FROM storage_slots WHERE id = ?');
    $query->execute([$vak_id]);

    // fetchColumn() geeft het ene antwoord terug dat de database gevonden heeft (de status).
    // Alleen als dat precies 'free' is, is het vak vrij. Bestaat het vak niet? Dan is er geen antwoord en dus 'false'.
    return $query->fetchColumn() === 'free';
}

// Bestaat deze vakcode al?
function slot_code_exists(string $vakcode): bool
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM storage_slots WHERE slot_code = ?');
    $query->execute([$vakcode]);
    return (int) $query->fetchColumn() > 0;
}

// ------------------------------------------------------------------------------
// 3. VAKKEN WIJZIGEN
// ------------------------------------------------------------------------------

// Zet de status van een vak op 'free' of 'occupied'
function set_slot_status(int $vak_id, string $status): void
{
    $query = get_db()->prepare('UPDATE storage_slots SET status = ? WHERE id = ?');
    $query->execute([$status, $vak_id]);
}

// Voegt een nieuw (vrij) vak toe
function add_slot(string $vakcode, string $stelling): void
{
    $query = get_db()->prepare("INSERT INTO storage_slots (slot_code, rack, status) VALUES (?, ?, 'free')");
    $query->execute([$vakcode, $stelling]);
}

// Geeft een gekleurd labeltje voor de status van een vak
function slot_status_badge(string $status): string
{
    return match ($status) {
        'free'     => '<span class="badge bg-emerald-100 text-emerald-800">Vrij</span>',
        'occupied' => '<span class="badge bg-brand-navy text-white">Bezet</span>',
        default    => '<span class="badge bg-slate-100 text-slate-600">' . h($status) . '</span>',
    };
}

// ==============================================================================
// DEEL 5: PAKKETTEN
// ==============================================================================
// Alle database-code en regels voor de tabel 'parcels' staan hier.
//
// HOE WERKT EEN PAKKET? (de levensloop)
//   1. REGISTREREN  Medewerker boekt het pakket in met vervoerder en barcode.
//                   Het systeem maakt een unieke afhaalcode (bijv. PK-7X9B).
//                   a) Is het pakket er al? Dan kiest de medewerker meteen een vrij vak.
//                      Status = 'arrived', deadline = vandaag + PICKUP_DAYS dagen.
//                   b) Is het pakket alleen aangekondigd? Dan is de status 'expected'
//                      (verwacht). Nog geen vak en nog geen deadline.
//   2. ONTVANGST    Komt een verwacht pakket binnen? De medewerker kiest een vrij vak.
//                   Status wordt 'arrived' en de deadline gaat lopen.
//   3. BEKIJKEN     De klant ziet zijn verwachte en binnengekomen pakketten,
//                   de afhaalcode en de uiterste afhaaldatum.
//   4. UITGEVEN     Klant komt naar de balie en noemt de afhaalcode.
//                   Klopt de code? Status = 'picked_up' en het vak wordt weer vrij.
//   OF RETOUR       Wordt het pakket niet opgehaald? De medewerker zet de status
//                   op 'returned' (retour vervoerder) en het vak wordt weer vrij.

// Alle mogelijke statussen met hun Nederlandse naam (in de volgorde van de levensloop)
const PARCEL_STATUSES = [
    'expected'  => 'Verwacht',
    'arrived'   => 'Binnengekomen',
    'picked_up' => 'Uitgegeven',
    'returned'  => 'Retour vervoerder',
];

// Het begin van bijna elke pakket-query: pakket + naam vervoerder + code van het opslagvak.
// Zo hoeven we deze JOIN niet op elke pagina opnieuw te typen.
const PARCEL_SELECT = '
    SELECT p.*, c.name AS carrier_name, s.slot_code
    FROM parcels p
    LEFT JOIN carriers c      ON c.id = p.carrier_id
    LEFT JOIN storage_slots s ON s.id = p.storage_slot_id
';

// ------------------------------------------------------------------------------
// 1. PAKKETTEN OPHALEN
// ------------------------------------------------------------------------------

// Zoekt 1 pakket op ID (of null als het niet bestaat)
function find_parcel(int $id): ?array
{
    $query = get_db()->prepare(PARCEL_SELECT . ' WHERE p.id = ?');
    $query->execute([$id]);
    return $query->fetch() ?: null;
}

// Haalt ALLE pakketten van een klant op (verwacht, binnen, opgehaald en retour).
// We zoeken op klant-ID én op e-mailadres, zodat de klant ook pakketten ziet
// die zijn ingeboekt voordat hij een account had.
function get_customer_parcels(array $klant): array
{
    $query = get_db()->prepare(PARCEL_SELECT . '
        WHERE p.customer_id = ? OR p.customer_email = ?
        ORDER BY p.received_at DESC
    ');
    $query->execute([$klant['id'], $klant['email']]);
    return $query->fetchAll();
}

// Is dit pakket nog 'actief'? (verwacht of binnengekomen = nog niet afgehandeld)
function is_active_parcel(array $pakket): bool
{
    return $pakket['status'] === 'expected' || $pakket['status'] === 'arrived';
}

// Zoekt pakketten voor de balie op code, klant, vak en status.
// $status kan zijn:
//   'active' = verwacht + binnengekomen (standaard)
//   'all'    = alle statussen
//   of 1 status uit PARCEL_STATUSES, bijv. 'arrived'
function search_parcels(string $zoekterm = '', string $status = 'active'): array
{
    // 'WHERE 1 = 1' is altijd waar. Zo kunnen we hieronder makkelijk 'AND ...' erachter plakken.
    $sql = PARCEL_SELECT . ' WHERE 1 = 1';
    $waarden = [];

    // Filter op status
    if ($status === 'active') {
        $sql .= " AND p.status IN ('expected', 'arrived')";
    } elseif (array_key_exists($status, PARCEL_STATUSES)) {
        $sql .= ' AND p.status = ?';
        $waarden[] = $status;
    }
    // Bij 'all' voegen we geen filter toe

    // Is er een zoekterm? Zoek dan in code, naam, e-mail, barcode en vak
    if ($zoekterm !== '') {
        $sql .= ' AND (p.pickup_code LIKE ? OR p.tracking_code LIKE ? OR p.customer_name LIKE ?
                       OR p.customer_email LIKE ? OR s.slot_code LIKE ?)';

        // % betekent 'alles mag ervoor/erna staan', dus ook stukjes van een woord worden gevonden.
        // We voegen 5 keer dezelfde zoekwaarde toe (1 per vraagteken).
        $zoekwaarde = '%' . $zoekterm . '%';
        for ($i = 0; $i < 5; $i++) {
            $waarden[] = $zoekwaarde;
        }
    }

    // Nieuwste pakketten bovenaan
    $sql .= ' ORDER BY p.received_at DESC';

    // Voer de query veilig uit met de zoekwaarden
    $query = get_db()->prepare($sql);
    $query->execute($waarden);
    return $query->fetchAll();
}

// Haalt alle pakketten op die te lang liggen (deadline is voorbij)
function get_overdue_parcels(): array
{
    return get_db()->query(PARCEL_SELECT . "
        WHERE p.status = 'arrived'
          AND p.pickup_deadline < NOW()
        ORDER BY p.pickup_deadline
    ")->fetchAll();
}

// Haalt de laatst binnengekomen pakketten op (voor het admin dashboard)
function get_recent_parcels(int $aantal = 5): array
{
    $query = get_db()->prepare(PARCEL_SELECT . ' ORDER BY p.received_at DESC LIMIT ?');

    // LIMIT = "geef maximaal zoveel rijen". Daar moet echt een getal staan (geen tekst).
    // Daarom zeggen we hier met PARAM_INT: "dit is een heel getal".
    $query->bindValue(1, $aantal, PDO::PARAM_INT);
    $query->execute();
    return $query->fetchAll();
}

// Telt hoeveel pakketten een bepaalde status hebben
function count_parcels_by_status(string $status): int
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM parcels WHERE status = ?');
    $query->execute([$status]);
    return (int) $query->fetchColumn();
}

// ------------------------------------------------------------------------------
// 2. CODES
// ------------------------------------------------------------------------------

// Bestaat er al een pakket met deze barcode (track & trace)? (TE-07: unieke pakketcodes)
function tracking_code_exists(string $barcode): bool
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM parcels WHERE tracking_code = ?');
    $query->execute([$barcode]);
    return (int) $query->fetchColumn() > 0;
}

// Maakt een nieuwe, unieke afhaalcode zoals PK-7X9B (TE-08: unieke afhaalcodes)
function generate_pickup_code(): string
{
    // Tekens die we gebruiken. Zonder 0, O, 1 en I want die lijken te veel op elkaar.
    $tekens = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    // Query om te controleren of een code al bestaat
    $query = get_db()->prepare('SELECT COUNT(*) FROM parcels WHERE pickup_code = ?');

    // Blijf codes maken tot we er een hebben die nog niet bestaat
    do {
        // Begin altijd met PK-
        $code = 'PK-';

        // Plak er 4 willekeurige tekens achter.
        // random_int is cryptografisch veilig: de code is niet te voorspellen.
        for ($i = 0; $i < 4; $i++) {
            $code .= $tekens[random_int(0, strlen($tekens) - 1)];
        }

        // Kijk of deze code al bij een ander pakket hoort
        $query->execute([$code]);
        $bestaat_al = (int) $query->fetchColumn() > 0;
    } while ($bestaat_al);

    // Unieke code gevonden!
    return $code;
}

// Klopt de afhaalcode die de klant noemt?
function pickup_code_matches(array $pakket, string $ingevulde_code): bool
{
    // Maak hoofdletters en haal spaties weg, zodat pk-7x9b ook goed is
    $ingevulde_code = strtoupper(trim($ingevulde_code));

    // hash_equals vergelijkt twee teksten. Het is een extra veilige manier van vergelijken,
    // die niet verklapt hoeveel tekens er al goed waren. Geeft true als ze gelijk zijn.
    return hash_equals($pakket['pickup_code'], $ingevulde_code);
}

// ------------------------------------------------------------------------------
// 3. PAKKET REGISTREREN
// ------------------------------------------------------------------------------

// Geeft de uiterste afhaaldatum: nu + PICKUP_DAYS dagen (standaard 7)
function pickup_deadline_from_now(): string
{
    return date('Y-m-d H:i:s', strtotime('+' . PICKUP_DAYS . ' days'));
}

// Controleert de invoer van het registratieformulier.
// Geeft een foutmelding terug, of null als alles klopt.
function validate_parcel_input(array $invoer): ?string
{
    // Is het pakket al binnen ('arrived') of wordt het verwacht ('expected')?
    if ($invoer['status'] !== 'arrived' && $invoer['status'] !== 'expected') {
        return 'Kies of het pakket al binnen is of nog verwacht wordt.';
    }

    // Zijn alle verplichte velden ingevuld?
    if ($invoer['carrier_id'] === 0 || $invoer['tracking_code'] === '') {
        return 'Vul a.u.b. alle verplichte velden in.';
    }

    // Een pakket dat al binnen is, moet meteen in een vak
    if ($invoer['status'] === 'arrived' && $invoer['storage_slot_id'] === 0) {
        return 'Kies een vrij opslagvak voor een binnengekomen pakket.';
    }

    // Klopt de barcode? Alleen letters, cijfers en - (max 50 tekens).
    // (Het uitroepteken ! betekent "NIET": dus "als het NIET past op het patroon...")
    if (!preg_match('/^[A-Za-z0-9\-]{1,50}$/', $invoer['tracking_code'])) {
        return 'De barcode mag alleen letters, cijfers en - bevatten (maximaal 50 tekens).';
    }

    // Kloppen naam, e-mail en telefoon van de klant?
    $fout = validate_contact_details($invoer['customer_name'], $invoer['customer_email'], $invoer['customer_phone']);
    if ($fout !== null) {
        return $fout;
    }

    // Bestaat de vervoerder en is hij actief?
    if (!is_active_carrier($invoer['carrier_id'])) {
        return 'Kies een geldige vervoerder.';
    }

    // Is het opslagvak echt vrij? (iemand kan de HTML aanpassen, dus altijd controleren!)
    if ($invoer['status'] === 'arrived' && !is_slot_free($invoer['storage_slot_id'])) {
        return 'Dit opslagvak is niet (meer) vrij. Kies een ander vak.';
    }

    // Is deze barcode al eerder geregistreerd?
    if (tracking_code_exists($invoer['tracking_code'])) {
        return 'Er is al een pakket met deze barcode geregistreerd.';
    }

    // Alles klopt!
    return null;
}

// Slaat een nieuw pakket op. Is het al binnen? Dan wordt het opslagvak ook bezet.
// Geeft de nieuwe afhaalcode terug.
// LET OP: roep eerst validate_parcel_input() aan, die controleert of het vak vrij is.
function register_parcel(array $invoer, int $medewerker_id): string
{
    $db = get_db();

    // Is het pakket al binnen? (anders is het 'verwacht')
    $is_binnen = $invoer['status'] === 'arrived';

    // Maak een unieke afhaalcode
    $afhaalcode = generate_pickup_code();

    // Alleen een binnengekomen pakket krijgt een vak en een deadline.
    // Een verwacht pakket krijgt die pas bij ontvangst (zie receive_parcel()).
    $vak_id = $is_binnen ? $invoer['storage_slot_id'] : null;
    $deadline = $is_binnen ? pickup_deadline_from_now() : null;

    // Heeft de klant al een account? Dan koppelen we het pakket aan dat account
    $klant_id = find_user_id_by_email($invoer['customer_email']);

    // TRANSACTIE: of ALLES lukt, of er gebeurt NIKS.
    // Zo kan het nooit gebeuren dat het pakket wel is opgeslagen maar het vak niet bezet is.
    // beginTransaction = "vanaf nu houdt de database alle wijzigingen nog even vast"
    $db->beginTransaction();

    // 'try' betekent: "probeer dit". Gaat er iets mis? Dan springt de code naar 'catch' hieronder.
    try {
        // Stap 1: zet het vak op bezet (alleen als het pakket al binnen is)
        if ($is_binnen) {
            set_slot_status($vak_id, 'occupied');
        }

        // Stap 2: sla het pakket op
        $query = $db->prepare('
            INSERT INTO parcels (tracking_code, pickup_code, carrier_id, storage_slot_id, customer_id,
                                 customer_name, customer_email, customer_phone, status, pickup_deadline, received_by_user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $query->execute([
            $invoer['tracking_code'],
            $afhaalcode,
            $invoer['carrier_id'],
            $vak_id,
            $klant_id,
            $invoer['customer_name'],
            $invoer['customer_email'],
            $invoer['customer_phone'] ?: null,
            $invoer['status'],
            $deadline,
            $medewerker_id,
        ]);

        // commit = "alles is gelukt, sla de wijzigingen nu echt op"
        $db->commit();
        return $afhaalcode;
    } catch (PDOException $fout) {
        // Er ging iets mis met de database.
        // rollBack = "draai alle wijzigingen terug, alsof er niks is gebeurd"
        $db->rollBack();

        // Geef de fout door, zodat de gebruiker de melding "Er ging iets mis" ziet
        throw $fout;
    }
}

// Een VERWACHT pakket is binnengekomen: leg het in een vrij vak.
// Status wordt 'arrived' en vanaf nu loopt de afhaaltermijn.
// LET OP: controleer eerst met is_slot_free() of het vak vrij is.
function receive_parcel(array $pakket, int $vak_id, int $medewerker_id): void
{
    $db = get_db();

    // Transactie: vak bezetten en pakket bijwerken horen bij elkaar
    $db->beginTransaction();

    try {
        // Stap 1: het vak bezet maken
        set_slot_status($vak_id, 'occupied');

        // Stap 2: pakket op 'binnengekomen' zetten, met vak, tijdstip en deadline
        $query = $db->prepare("
            UPDATE parcels
            SET status = 'arrived', storage_slot_id = ?, received_at = NOW(),
                pickup_deadline = ?, received_by_user_id = ?
            WHERE id = ?
        ");
        $query->execute([$vak_id, pickup_deadline_from_now(), $medewerker_id, $pakket['id']]);

        // Alles gelukt: opslaan!
        $db->commit();
    } catch (PDOException $fout) {
        // Mislukt: alles terugdraaien
        $db->rollBack();
        throw $fout;
    }
}

// ------------------------------------------------------------------------------
// 4. PAKKET UITGEVEN, RETOUR & VERPLAATSEN
// ------------------------------------------------------------------------------

// Geeft een pakket uit aan de klant en maakt het vak vrij
function hand_out_parcel(array $pakket): void
{
    finish_parcel($pakket, 'picked_up');
}

// Stuurt een pakket retour naar de vervoerder en maakt het vak vrij
function return_parcel(array $pakket): void
{
    finish_parcel($pakket, 'returned');
}

// Zet een pakket op 'picked_up' of 'returned' en maakt het opslagvak vrij.
// Dit doen hand_out_parcel() en return_parcel() allebei, dus staat het hier maar 1 keer.
function finish_parcel(array $pakket, string $nieuwe_status): void
{
    $db = get_db();

    // Transactie: pakket bijwerken en vak vrijmaken horen bij elkaar
    $db->beginTransaction();

    try {
        // Stap 1: nieuwe status + tijdstip opslaan
        $query = $db->prepare("UPDATE parcels SET status = ?, picked_up_at = NOW() WHERE id = ? AND status = 'arrived'");
        $query->execute([$nieuwe_status, $pakket['id']]);

        // Stap 2: het opslagvak is weer vrij voor een volgend pakket
        if ($pakket['storage_slot_id']) {
            set_slot_status($pakket['storage_slot_id'], 'free');
        }

        // Alles gelukt: opslaan!
        $db->commit();
    } catch (PDOException $fout) {
        // Mislukt: alles terugdraaien
        $db->rollBack();
        throw $fout;
    }
}

// Verplaatst een pakket naar een ander (vrij) opslagvak.
// LET OP: controleer eerst met is_slot_free() of het nieuwe vak vrij is.
function move_parcel_to_slot(array $pakket, int $nieuw_vak_id): void
{
    $db = get_db();
    $db->beginTransaction();

    try {
        // Stap 1: het nieuwe vak bezet maken
        set_slot_status($nieuw_vak_id, 'occupied');

        // Stap 2: het pakket aan het nieuwe vak koppelen
        $query = $db->prepare('UPDATE parcels SET storage_slot_id = ? WHERE id = ?');
        $query->execute([$nieuw_vak_id, $pakket['id']]);

        // Stap 3: het oude vak is nu weer vrij
        if ($pakket['storage_slot_id']) {
            set_slot_status($pakket['storage_slot_id'], 'free');
        }

        $db->commit();
    } catch (PDOException $fout) {
        $db->rollBack();
        throw $fout;
    }
}

// ------------------------------------------------------------------------------
// 5. DEADLINES & LABELTJES
// ------------------------------------------------------------------------------

// Ligt dit pakket te lang? (alleen pakketten die nog in de winkel liggen)
function is_overdue(string $status, ?string $deadline): bool
{
    // Al opgehaald of retour? Dan is het nooit te laat
    if ($status !== 'arrived' || !$deadline) {
        return false;
    }

    // strtotime(...) maakt van de deadline een getal (seconden sinds 1970) en time() is "nu".
    // Is de deadline een kleiner getal dan nu? Dan is hij al voorbij.
    return strtotime($deadline) < time();
}

// Hoeveel dagen is de deadline al voorbij?
function days_overdue(string $deadline): int
{
    // diff() berekent het verschil tussen twee datums
    return (new DateTime($deadline))->diff(new DateTime())->days;
}

// Geeft een gekleurd labeltje voor de status van een pakket
function status_badge(string $status, ?string $deadline = null): string
{
    // Te lang aanwezig gaat voor alles (oranje waarschuwing)
    if (is_overdue($status, $deadline)) {
        return '<span class="badge bg-amber-100 text-amber-900 border border-amber-300">⚠️ Te lang aanwezig</span>';
    }

    // Kies kleur EN tekst op basis van de status (alleen kleur is niet duidelijk voor iedereen).
    // 'match' werkt als een keuzelijst: "als de status X is, geef dan dit labeltje terug".
    // 'default' is het laatste vangnet voor als de status geen van de bovenstaande is.
    return match ($status) {
        'expected'  => '<span class="badge bg-sky-100 text-sky-800 border border-sky-300">🔵 Verwacht</span>',
        'arrived'   => '<span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300">🟢 Binnengekomen (Klaar)</span>',
        'picked_up' => '<span class="badge bg-slate-100 text-slate-700 border border-slate-300">⚪ Uitgegeven</span>',
        'returned'  => '<span class="badge bg-rose-50 text-rose-800 border border-rose-200">↩️ Retour vervoerder</span>',
        default     => '<span class="badge bg-slate-100 text-slate-700">' . h($status) . '</span>',
    };
}

// Geeft de 3 stappen voor de tijdlijn van de klant:
//   Aangemeld  ->  Binnengekomen  ->  Opgehaald (of Retour vervoerder)
// Elke stap heeft een tekst en 'klaar' (true = deze stap is al gebeurd).
function parcel_timeline(array $pakket): array
{
    $status = $pakket['status'];

    // De laatste stap heet anders als het pakket retour is gegaan
    $laatste_stap = $status === 'returned' ? 'Retour vervoerder' : 'Opgehaald';

    return [
        ['tekst' => 'Aangemeld',     'klaar' => true],
        ['tekst' => 'Binnengekomen', 'klaar' => $status !== 'expected'],
        ['tekst' => $laatste_stap,   'klaar' => $status === 'picked_up' || $status === 'returned'],
    ];
}
