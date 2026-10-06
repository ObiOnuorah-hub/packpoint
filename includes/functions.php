<?php
// functions.php
//
// Hier staan alle functies van PackPoint bij elkaar. Een functie is gewoon een
// klein klusje met een naam, zodat we niet overal dezelfde code hoeven te plakken.
// Het bestand is opgedeeld in vijf stukken, zoek maar op "Deel" om te springen:
//
//   Deel 1  handige hulpjes (tekst veilig tonen, datums, invoer controleren)
//   Deel 2  gebruikers
//   Deel 3  vervoerders
//   Deel 4  opslagvakken
//   Deel 5  pakketten
//
// Een paar woorden die je hier vaak tegenkomt, in gewone taal:
//
//   functie      een klusje met een naam. find_parcel(5) betekent: zoek pakket 5.
//   variabele    een doosje met een naam erop (begint met een $). Er zit iets in,
//                bijvoorbeeld $pakket met alle gegevens van een pakket.
//   database     de digitale kast waar alles in bewaard blijft, als een Excel met
//                tabellen. 'parcels' is de tabel met pakketten, 'users' die met mensen.
//   SQL          de taal waarmee je iets aan de database vraagt. SELECT betekent
//                "geef me", INSERT "voeg toe", UPDATE "pas aan" en DELETE "weg ermee".
//   het ?        in een SQL-opdracht is dat een lege plek. De echte waarde stoppen we
//                er los in. Zo kan niemand stiekem eigen opdrachten in een
//                invoerveld verstoppen (dat heet SQL-injectie). Daarom doen we dit
//                overal zo.
//   null         betekent "niks" of "bestaat niet". Geeft een functie null terug,
//                dan heeft hij niks kunnen vinden.
//   return       "dit is mijn antwoord", en daarmee is de functie klaar.
//   transactie   een setje acties dat samen lukt of samen mislukt, zoals bij pinnen:
//                of het geld gaat er helemaal af, of helemaal niet.
//
// De status van een pakket vertelt waar het in zijn reis zit:
//   expected    verwacht, nog onderweg
//   arrived     binnen, ligt in een vakje
//   picked_up   opgehaald door de klant
//   returned    teruggestuurd naar de vervoerder


// ------------------------------------------------------------------
// Deel 1: handige hulpjes
// ------------------------------------------------------------------

// Maakt tekst veilig om op het scherm te zetten. Gebruik dit altijd voor alles wat
// uit de database of uit een formulier komt, anders kan iemand er stiekem code in
// verstoppen die dan in andermans browser draait (dat heet XSS).
function h(?string $tekst): string
{
    // Tekens als < en > worden omgezet naar onschuldige varianten.
    return htmlspecialchars($tekst ?? '', ENT_QUOTES, 'UTF-8');
}

// Geeft terug wat iemand eerder in een formulierveld had getypt.
// Handig bij een foutmelding: dan hoeft niemand alles opnieuw in te vullen.
function old(string $veldnaam): string
{
    // $_POST bevat alles wat net in het formulier is ingevuld.
    // Was het veld leeg of niet verstuurd? Dan nemen we gewoon een lege tekst.
    return h($_POST[$veldnaam] ?? '');
}

// Stuurt de bezoeker naar een andere pagina en laat daar een melding zien,
// bijvoorbeeld "Pakket opgeslagen!".
function redirect_with_message(string $adres, string $type, string $bericht): void
{
    // Eerst de melding klaarzetten, dan pas doorsturen.
    set_flash($type, $bericht);

    // Tegen de browser zeggen: ga maar naar die andere pagina.
    header('Location: ' . $adres);

    // En hier stoppen we, de rest van deze pagina is niet meer nodig.
    exit;
}

// Maakt van een datum uit de database een datum zoals wij hem schrijven.
// Uit de database komt 2026-10-05 14:30:00, hier maken we er 05-10-2026 14:30 van.
function format_date(?string $datum): string
{
    // Geen datum? Dan laten we een streepje zien.
    if (!$datum) {
        return '-';
    }

    // strtotime leest de datum in en date() schrijft hem weer op in het formaat dat we willen.
    return date('d-m-Y H:i', strtotime($datum));
}

// Controleren van invoer gebeurt altijd op de server. Een "verplicht"-veld in de
// browser kan iemand namelijk makkelijk wegklikken, dus die vertrouwen we niet.

// Klopt dit e-mailadres, bijvoorbeeld naam@voorbeeld.nl?
function is_valid_email(string $email): bool
{
    // PHP heeft hier een eigen controle voor, die gebruiken we gewoon.
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Klopt dit telefoonnummer? Leeg mag, want telefoon hoeft niet.
function is_valid_phone(string $telefoon): bool
{
    // Niks ingevuld is prima.
    if ($telefoon === '') {
        return true;
    }

    // Hier vergelijken we met een "patroon":
    //   [0-9+\-\s]  alleen cijfers, een plus, een min of een spatie
    //   {6,20}      tussen de 6 en 20 tekens lang
    // Past de tekst op het patroon, dan geeft preg_match een 1 terug.
    return preg_match('/^[0-9+\-\s]{6,20}$/', $telefoon) === 1;
}

// Is deze tekst langer dan toegestaan? Handig, want in de database past maar
// een beperkt aantal tekens per veld.
function is_too_long(string $tekst, int $maximaal): bool
{
    // mb_strlen telt een é of ë netjes als 1 teken.
    return mb_strlen($tekst) > $maximaal;
}

// Controleert naam, e-mail en telefoon in een keer.
// Geeft een foutmelding terug als er iets niet klopt, en null als alles goed is.
function validate_contact_details(string $naam, string $email, string $telefoon): ?string
{
    // Naam en e-mail moeten er sowieso zijn.
    if ($naam === '' || $email === '') {
        return 'Vul a.u.b. alle verplichte velden in.';
    }

    // In de database past een naam van maximaal 100 tekens.
    if (is_too_long($naam, 100)) {
        return 'De naam mag maximaal 100 tekens lang zijn.';
    }

    // Een e-mailadres moet er ook echt als een e-mailadres uitzien.
    if (!is_valid_email($email) || is_too_long($email, 150)) {
        return 'Vul een geldig e-mailadres in.';
    }

    // Het telefoonnummer controleren we alleen als het is ingevuld (zie is_valid_phone).
    if (!is_valid_phone($telefoon)) {
        return 'Vul een geldig telefoonnummer in (alleen cijfers, spaties, + of -).';
    }

    // Geen bezwaren, dus geen foutmelding.
    return null;
}

// Controleert of een nieuw wachtwoord goed genoeg is.
// $herhaling is het veld waar je het wachtwoord nog eens intypt. Heeft een formulier
// dat niet, dan laat je het weg.
// Geeft een foutmelding terug, of null als het wachtwoord prima is.
function validate_new_password(string $wachtwoord, ?string $herhaling = null): ?string
{
    // Te kort is te makkelijk te raden.
    if (mb_strlen($wachtwoord) < MIN_PASSWORD_LENGTH) {
        return 'Het wachtwoord moet minimaal ' . MIN_PASSWORD_LENGTH . ' tekens bevatten.';
    }

    // Is er een herhaling ingevuld? Dan moeten de twee wel hetzelfde zijn.
    if ($herhaling !== null && $wachtwoord !== $herhaling) {
        return 'De ingevulde wachtwoorden komen niet overeen.';
    }

    // Alles in orde.
    return null;
}


// ------------------------------------------------------------------
// Deel 2: gebruikers
// ------------------------------------------------------------------
// Alles wat met de tabel 'users' te maken heeft staat hier bij elkaar. De pagina's
// roepen deze functies aan, dus je ziet nergens dezelfde SQL twee keer.

// Dit zijn de gegevens van een gebruiker die de rest van de site mag zien.
// De password_hash zit er bewust niet bij, die willen we zo weinig mogelijk rondgeven.
const USER_COLUMNS = 'id, username, name, email, phone, role, created_at';

// Zoekt een gebruiker op gebruikersnaam of e-mailadres. Dit gebruiken we bij het
// inloggen. Hier zit de password_hash wel bij, want die hebben we nodig om het
// wachtwoord te controleren.
function find_user_by_login(string $inlognaam): ?array
{
    // Spaties voor en na de naam weghalen, die typ je snel per ongeluk.
    $inlognaam = trim($inlognaam);

    // MySQL let hier niet op hoofdletters, dus Klant01 en klant01 zijn dezelfde.
    $query = get_db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
    $query->execute([$inlognaam, $inlognaam]);

    // fetch() pakt de eerste gevonden rij. Is er niks gevonden, dan geven we null terug.
    return $query->fetch() ?: null;
}

// Zoekt een gebruiker op zijn nummer (ID), zonder het wachtwoord erbij.
function find_user_by_id(int $id): ?array
{
    // Eerst de opdracht klaarmaken. Het ? is de lege plek voor het ID.
    $query = get_db()->prepare('SELECT ' . USER_COLUMNS . ' FROM users WHERE id = ?');

    // Dan uitvoeren en het ID in die lege plek stoppen.
    $query->execute([$id]);

    // De gevonden gebruiker teruggeven, of null als die niet bestaat.
    return $query->fetch() ?: null;
}

// Zoekt het nummer van de gebruiker met dit e-mailadres. Bestaat die niet? Dan null.
function find_user_id_by_email(string $email): ?int
{
    $query = get_db()->prepare('SELECT id FROM users WHERE email = ?');
    $query->execute([$email]);

    // fetchColumn geeft de eerste waarde van het antwoord, of false als er niks was.
    $id = $query->fetchColumn();
    return $id === false ? null : (int) $id;
}

// Heeft iemand al een account met dit e-mailadres?
function email_exists(string $email): bool
{
    return find_user_id_by_email($email) !== null;
}

// Haalt alle gebruikers op, gesorteerd op rol en daarna op naam.
function get_all_users(): array
{
    return get_db()->query('SELECT ' . USER_COLUMNS . ' FROM users ORDER BY role, name')->fetchAll();
}

// Telt hoeveel gebruikers er zijn.
function count_users(): int
{
    return (int) get_db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

// Verzint een gebruikersnaam die nog niet bestaat, op basis van het e-mailadres.
// jan@mail.nl wordt 'jan'. Is 'jan' al weg? Dan wordt het 'jan2', 'jan3', enzovoort.
function make_unique_username(string $email): string
{
    // Alles voor het @-teken pakken, en alleen letters, cijfers en . - _ overhouden.
    $basis = preg_replace('/[^a-z0-9._-]/', '', strtolower(explode('@', $email)[0]));

    // Niet te lang maken. In de database passen er 50, dus er is nog ruimte voor een nummer.
    $basis = substr($basis, 0, 40);

    // Is er niks over? Dan noemen we hem gewoon 'gebruiker'.
    if ($basis === '') {
        $basis = 'gebruiker';
    }

    // We beginnen met de gewone naam en tellen erbij op als hij al bestaat.
    $gebruikersnaam = $basis;
    $nummer = 2;
    $query = get_db()->prepare('SELECT COUNT(*) FROM users WHERE username = ?');

    // 'while (true)' blijft herhalen tot we zelf 'return' zeggen.
    while (true) {
        // Hoeveel mensen hebben deze naam al?
        $query->execute([$gebruikersnaam]);

        // Niemand? Dan is hij vrij en zijn we klaar.
        if ((int) $query->fetchColumn() === 0) {
            return $gebruikersnaam;
        }

        // Anders een nummer erachter (jan2, jan3, ...) en nog een keer proberen.
        $gebruikersnaam = $basis . $nummer;
        $nummer++;
    }
}

// Maakt een nieuwe gebruiker aan en geeft zijn nieuwe nummer (ID) terug.
function create_user(string $naam, string $email, string $telefoon, string $wachtwoord, string $rol): int
{
    // Het wachtwoord slaan we nooit zelf op. We maken er een "hash" van: een
    // onleesbare versie die je niet kunt terugdraaien naar het echte wachtwoord.
    $hash = password_hash($wachtwoord, PASSWORD_DEFAULT);

    // Een gebruikersnaam verzinnen die nog vrij is.
    $gebruikersnaam = make_unique_username($email);

    // En dan opslaan in de database.
    $db = get_db();
    $query = $db->prepare('INSERT INTO users (username, name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)');

    // Bij het telefoonnummer zie je '?: null'. Dat betekent: is het leeg, sla dan niks op.
    $query->execute([$gebruikersnaam, $naam, $email, $telefoon ?: null, $hash, $rol]);

    // De database geeft elke nieuwe gebruiker zelf een nummer. Dat geven wij terug.
    return (int) $db->lastInsertId();
}

// Past de naam en het telefoonnummer van een gebruiker aan.
function update_user_profile(int $id, string $naam, string $telefoon): void
{
    $query = get_db()->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?');
    $query->execute([$naam, $telefoon ?: null, $id]);
}

// Zet een nieuw wachtwoord. Net als bij het aanmaken slaan we alleen de hash op.
function update_user_password(int $id, string $nieuw_wachtwoord): void
{
    $query = get_db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $query->execute([password_hash($nieuw_wachtwoord, PASSWORD_DEFAULT), $id]);
}

// Past de rol van een gebruiker aan (klant, baliemedewerker of beheerder).
function update_user_role(int $id, string $rol): void
{
    $query = get_db()->prepare('UPDATE users SET role = ? WHERE id = ?');
    $query->execute([$rol, $id]);
}

// Heeft deze medewerker al pakketten ingeboekt? Dan mogen we hem niet verwijderen,
// want anders weet je later niet meer wie welk pakket heeft aangenomen.
function user_has_registered_parcels(int $id): bool
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM parcels WHERE received_by_user_id = ?');
    $query->execute([$id]);
    return (int) $query->fetchColumn() > 0;
}

// Verwijdert een gebruiker. Pakketten van een klant blijven gewoon bestaan, alleen
// de koppeling met het account verdwijnt (zie ON DELETE SET NULL in schema.sql).
function delete_user(int $id): void
{
    $query = get_db()->prepare('DELETE FROM users WHERE id = ?');
    $query->execute([$id]);
}


// ------------------------------------------------------------------
// Deel 3: vervoerders
// ------------------------------------------------------------------
// Alles over de tabel 'carriers', dus PostNL, DHL en alle andere bezorgers.

// Alle vervoerders, op alfabet.
function get_all_carriers(): array
{
    return get_db()->query('SELECT * FROM carriers ORDER BY name')->fetchAll();
}

// Alleen de vervoerders die nog actief zijn. Die staan in het keuzemenu als je een pakket registreert.
function get_active_carriers(): array
{
    return get_db()->query('SELECT * FROM carriers WHERE is_active = 1 ORDER BY name')->fetchAll();
}

// Hoeveel vervoerders zijn er actief?
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

// Staat er al een vervoerder met deze naam in de lijst?
function carrier_name_exists(string $naam): bool
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM carriers WHERE name = ?');
    $query->execute([$naam]);
    return (int) $query->fetchColumn() > 0;
}

// Voegt een nieuwe vervoerder toe. Die staat meteen op actief.
function add_carrier(string $naam): void
{
    $query = get_db()->prepare('INSERT INTO carriers (name, is_active) VALUES (?, 1)');
    $query->execute([$naam]);
}


// ------------------------------------------------------------------
// Deel 4: opslagvakken
// ------------------------------------------------------------------
// Alles over de tabel 'storage_slots', de vakjes in de stellingen.
//
// Zo werken ze:
//   - Elk vakje heeft een code (zoals A-01) en hoort bij een stelling (zoals Stelling A).
//   - Een vakje is vrij ('free') of bezet ('occupied').
//   - In een vakje ligt maximaal 1 pakket.
//   - Pakket registreren of ontvangen: het vakje wordt bezet.
//   - Pakket uitgeven of retour sturen: het vakje is weer vrij.

// Alle vakjes, gesorteerd op stelling en dan op code.
function get_all_slots(): array
{
    return get_db()->query('SELECT * FROM storage_slots ORDER BY rack, slot_code')->fetchAll();
}

// Alleen de vrije vakjes, om een pakket in te leggen.
function get_free_slots(): array
{
    return get_db()->query("SELECT * FROM storage_slots WHERE status = 'free' ORDER BY rack, slot_code")->fetchAll();
}

// Alle vakjes, met het pakket dat er op dit moment in ligt. Dit gebruiken we voor het vakkenraster.
function get_slots_with_parcels(): array
{
    // LEFT JOIN betekent: neem alle vakjes mee, ook als er geen pakket in ligt.
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

// Legt de vakjes in groepjes per stelling.
// Het resultaat ziet er zo uit: ['Stelling A' => [vak, vak, ...], 'Stelling B' => [...]]
function group_slots_by_rack(array $vakken): array
{
    // We beginnen met een lege lijst.
    $stellingen = [];

    // 'foreach' betekent: doe dit voor elk vakje uit de lijst, een voor een.
    // Elk vakje gaat in het groepje van zijn eigen stelling.
    foreach ($vakken as $vak) {
        $stellingen[$vak['rack']][] = $vak;
    }

    return $stellingen;
}

// Telt hoeveel vakjes een bepaalde status hebben, 'free' of 'occupied'.
function count_slots_by_status(string $status): int
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM storage_slots WHERE status = ?');
    $query->execute([$status]);
    return (int) $query->fetchColumn();
}

// Is dit vakje vrij? Bestaat het vakje niet, dan is het antwoord ook nee.
function is_slot_free(int $vak_id): bool
{
    // We vragen aan de database wat de status van dit vakje is.
    $query = get_db()->prepare('SELECT status FROM storage_slots WHERE id = ?');
    $query->execute([$vak_id]);

    // fetchColumn() geeft dat ene antwoord terug. Alleen als het precies 'free' is,
    // is het vakje vrij. Bestaat het vakje niet, dan is er geen antwoord en dus ook geen 'free'.
    return $query->fetchColumn() === 'free';
}

// Bestaat er al een vakje met deze code?
function slot_code_exists(string $vakcode): bool
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM storage_slots WHERE slot_code = ?');
    $query->execute([$vakcode]);
    return (int) $query->fetchColumn() > 0;
}

// Zet een vakje op 'free' of 'occupied'.
function set_slot_status(int $vak_id, string $status): void
{
    $query = get_db()->prepare('UPDATE storage_slots SET status = ? WHERE id = ?');
    $query->execute([$status, $vak_id]);
}

// Voegt een nieuw vakje toe. Een nieuw vakje is altijd vrij.
function add_slot(string $vakcode, string $stelling): void
{
    $query = get_db()->prepare("INSERT INTO storage_slots (slot_code, rack, status) VALUES (?, ?, 'free')");
    $query->execute([$vakcode, $stelling]);
}

// Geeft een kleurig labeltje voor de status van een vakje, groen voor vrij en donkerblauw voor bezet.
function slot_status_badge(string $status): string
{
    return match ($status) {
        'free'     => '<span class="badge bg-emerald-100 text-emerald-800">Vrij</span>',
        'occupied' => '<span class="badge bg-brand-navy text-white">Bezet</span>',
        default    => '<span class="badge bg-slate-100 text-slate-600">' . h($status) . '</span>',
    };
}


// ------------------------------------------------------------------
// Deel 5: pakketten
// ------------------------------------------------------------------
// Alles over de tabel 'parcels'. Hier zit ook het meeste denkwerk van de app.
//
// De reis van een pakket, stap voor stap:
//   1. Registreren
//      De medewerker boekt het pakket in met vervoerder en barcode. Het systeem
//      maakt er meteen een unieke afhaalcode bij, bijvoorbeeld PK-7X9B.
//        a) Ligt het pakket er al? Dan kiest de medewerker meteen een vrij vakje.
//           De status wordt 'arrived' en de afhaaltermijn gaat lopen.
//        b) Is het alleen aangekondigd? Dan wordt de status 'expected'. Er is dan
//           nog geen vakje en nog geen deadline.
//   2. Ontvangen
//      Komt een verwacht pakket binnen? Dan kiest de medewerker een vrij vakje.
//      De status wordt 'arrived' en de afhaaltermijn begint te lopen.
//   3. Bekijken
//      De klant ziet zijn pakketten, de afhaalcode en de uiterste afhaaldatum.
//   4. Uitgeven
//      De klant komt langs en noemt zijn afhaalcode. Klopt die? Dan wordt de status
//      'picked_up' en is het vakje weer vrij.
//   5. Of retour
//      Haalt de klant het niet op? Dan gaat het pakket terug naar de vervoerder.
//      De status wordt 'returned' en het vakje is weer vrij.

// Alle statussen met de naam zoals de gebruiker hem ziet, in de volgorde van de reis.
const PARCEL_STATUSES = [
    'expected'  => 'Verwacht',
    'arrived'   => 'Binnengekomen',
    'picked_up' => 'Uitgegeven',
    'returned'  => 'Retour vervoerder',
];

// Het begin van bijna elke pakketvraag: het pakket zelf, plus de naam van de
// vervoerder en de code van het vakje. Door dit op een plek te zetten hoeven we die
// koppeling niet op elke pagina opnieuw uit te schrijven.
const PARCEL_SELECT = '
    SELECT p.*, c.name AS carrier_name, s.slot_code
    FROM parcels p
    LEFT JOIN carriers c      ON c.id = p.carrier_id
    LEFT JOIN storage_slots s ON s.id = p.storage_slot_id
';

// Zoekt een pakket op zijn nummer. Bestaat het niet? Dan null.
function find_parcel(int $id): ?array
{
    $query = get_db()->prepare(PARCEL_SELECT . ' WHERE p.id = ?');
    $query->execute([$id]);
    return $query->fetch() ?: null;
}

// Haalt alle pakketten van een klant op, in elke status (verwacht, binnen, opgehaald en retour).
// We zoeken op klantnummer en op e-mailadres. Zo ziet de klant ook pakketten die
// zijn ingeboekt voordat hij een account had.
function get_customer_parcels(array $klant): array
{
    $query = get_db()->prepare(PARCEL_SELECT . '
        WHERE p.customer_id = ? OR p.customer_email = ?
        ORDER BY p.received_at DESC
    ');
    $query->execute([$klant['id'], $klant['email']]);
    return $query->fetchAll();
}

// Is dit pakket nog onderweg of binnen? Dan is het nog niet afgehandeld.
function is_active_parcel(array $pakket): bool
{
    return $pakket['status'] === 'expected' || $pakket['status'] === 'arrived';
}

// De zoekfunctie van de balie. Je kunt zoeken op code, klant en vak, en filteren op status.
// Voor $status kun je dit invullen:
//   'active'  verwacht en binnengekomen pakketten (dit is de standaard)
//   'all'     alle pakketten, wat de status ook is
//   of een van de statussen uit PARCEL_STATUSES, bijvoorbeeld 'arrived'
function search_parcels(string $zoekterm = '', string $status = 'active'): array
{
    // 'WHERE 1 = 1' is altijd waar. Handig trucje, want zo kunnen we hieronder
    // steeds gewoon 'AND ...' achter de vraag plakken.
    $sql = PARCEL_SELECT . ' WHERE 1 = 1';
    $waarden = [];

    // Eerst het filter op status.
    if ($status === 'active') {
        $sql .= " AND p.status IN ('expected', 'arrived')";
    } elseif (array_key_exists($status, PARCEL_STATUSES)) {
        $sql .= ' AND p.status = ?';
        $waarden[] = $status;
    }
    // Bij 'all' filteren we niet op status.

    // Is er iets ingetypt? Dan zoeken we in afhaalcode, barcode, naam, e-mail en vakcode.
    if ($zoekterm !== '') {
        $sql .= ' AND (p.pickup_code LIKE ? OR p.tracking_code LIKE ? OR p.customer_name LIKE ?
                       OR p.customer_email LIKE ? OR s.slot_code LIKE ?)';

        // De % betekent: er mag van alles voor en achter staan. Zo vind je ook een
        // pakket als je maar een stukje van de naam intypt. We hebben 5 vraagtekens
        // in de vraag, dus we voegen de zoekwaarde 5 keer toe.
        $zoekwaarde = '%' . $zoekterm . '%';
        for ($i = 0; $i < 5; $i++) {
            $waarden[] = $zoekwaarde;
        }
    }

    // De nieuwste pakketten bovenaan.
    $sql .= ' ORDER BY p.received_at DESC';

    // De vraag veilig uitvoeren met de zoekwaarden erin.
    $query = get_db()->prepare($sql);
    $query->execute($waarden);
    return $query->fetchAll();
}

// Alle pakketten die al te lang liggen, dus waarvan de afhaaldeadline voorbij is.
function get_overdue_parcels(): array
{
    return get_db()->query(PARCEL_SELECT . "
        WHERE p.status = 'arrived'
          AND p.pickup_deadline < NOW()
        ORDER BY p.pickup_deadline
    ")->fetchAll();
}

// De laatst geregistreerde pakketten, voor op het dashboard van de admin.
function get_recent_parcels(int $aantal = 5): array
{
    $query = get_db()->prepare(PARCEL_SELECT . ' ORDER BY p.received_at DESC LIMIT ?');

    // LIMIT betekent "maximaal zoveel rijen", en daar moet echt een getal staan, geen tekst.
    // Met PARAM_INT zeggen we dus: dit is een heel getal.
    $query->bindValue(1, $aantal, PDO::PARAM_INT);
    $query->execute();
    return $query->fetchAll();
}

// Telt hoeveel pakketten een bepaalde status hebben.
function count_parcels_by_status(string $status): int
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM parcels WHERE status = ?');
    $query->execute([$status]);
    return (int) $query->fetchColumn();
}

// Is deze barcode (track & trace) al eens geregistreerd? Elke pakketcode moet uniek zijn.
function tracking_code_exists(string $barcode): bool
{
    $query = get_db()->prepare('SELECT COUNT(*) FROM parcels WHERE tracking_code = ?');
    $query->execute([$barcode]);
    return (int) $query->fetchColumn() > 0;
}

// Verzint een afhaalcode zoals PK-7X9B. Elke afhaalcode moet uniek zijn.
function generate_pickup_code(): string
{
    // Deze tekens mogen erin. De cijfers 0 en 1 en de letters O en I laten we weg,
    // want die lijken te veel op elkaar en dan gaat het mis aan de balie.
    $tekens = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    // Met deze vraag kijken we of een code al bij een pakket hoort.
    $query = get_db()->prepare('SELECT COUNT(*) FROM parcels WHERE pickup_code = ?');

    // We blijven nieuwe codes verzinnen tot we er een hebben die nog niet bestaat.
    do {
        // Elke code begint met PK-
        $code = 'PK-';

        // Daarachter komen 4 willekeurige tekens. random_int is echt onvoorspelbaar,
        // dus niemand kan de volgende code raden.
        for ($i = 0; $i < 4; $i++) {
            $code .= $tekens[random_int(0, strlen($tekens) - 1)];
        }

        // Bestaat deze code al? Dan gaan we nog een keer.
        $query->execute([$code]);
        $bestaat_al = (int) $query->fetchColumn() > 0;
    } while ($bestaat_al);

    // Deze is nog vrij.
    return $code;
}

// Klopt de afhaalcode die de klant noemt met die van het pakket?
function pickup_code_matches(array $pakket, string $ingevulde_code): bool
{
    // Hoofdletters en spaties maken niet uit, dus pk-7x9b is ook goed.
    $ingevulde_code = strtoupper(trim($ingevulde_code));

    // hash_equals vergelijkt twee teksten op een veilige manier. Het verklapt niet
    // hoeveel tekens er al klopten. Het geeft true als ze gelijk zijn.
    return hash_equals($pakket['pickup_code'], $ingevulde_code);
}

// De uiterste afhaaldatum: nu plus het aantal dagen uit PICKUP_DAYS (standaard 7).
function pickup_deadline_from_now(): string
{
    return date('Y-m-d H:i:s', strtotime('+' . PICKUP_DAYS . ' days'));
}

// Controleert alles wat op het registratieformulier is ingevuld.
// Geeft een foutmelding terug als er iets niet klopt, en null als alles goed is.
function validate_parcel_input(array $invoer): ?string
{
    // Is er gekozen of het pakket al binnen is of nog verwacht wordt?
    if ($invoer['status'] !== 'arrived' && $invoer['status'] !== 'expected') {
        return 'Kies of het pakket al binnen is of nog verwacht wordt.';
    }

    // Zijn vervoerder en barcode ingevuld?
    if ($invoer['carrier_id'] === 0 || $invoer['tracking_code'] === '') {
        return 'Vul a.u.b. alle verplichte velden in.';
    }

    // Ligt het pakket al binnen? Dan moet het meteen in een vakje.
    if ($invoer['status'] === 'arrived' && $invoer['storage_slot_id'] === 0) {
        return 'Kies een vrij opslagvak voor een binnengekomen pakket.';
    }

    // De barcode mag alleen letters, cijfers en - bevatten, tot maximaal 50 tekens.
    // Het uitroepteken betekent "niet": als de barcode NIET op het patroon past, dan fout.
    if (!preg_match('/^[A-Za-z0-9\-]{1,50}$/', $invoer['tracking_code'])) {
        return 'De barcode mag alleen letters, cijfers en - bevatten (maximaal 50 tekens).';
    }

    // Kloppen naam, e-mail en telefoon van de klant?
    $fout = validate_contact_details($invoer['customer_name'], $invoer['customer_email'], $invoer['customer_phone']);
    if ($fout !== null) {
        return $fout;
    }

    // Bestaat de gekozen vervoerder, en is hij nog actief?
    if (!is_active_carrier($invoer['carrier_id'])) {
        return 'Kies een geldige vervoerder.';
    }

    // Is het vakje echt vrij? Dit controleren we altijd opnieuw, want iemand kan de
    // pagina in de browser hebben aangepast.
    if ($invoer['status'] === 'arrived' && !is_slot_free($invoer['storage_slot_id'])) {
        return 'Dit opslagvak is niet (meer) vrij. Kies een ander vak.';
    }

    // Is deze barcode al eerder gebruikt?
    if (tracking_code_exists($invoer['tracking_code'])) {
        return 'Er is al een pakket met deze barcode geregistreerd.';
    }

    // Alles klopt.
    return null;
}

// Slaat een nieuw pakket op. Ligt het pakket al binnen? Dan wordt het vakje ook bezet.
// Geeft de nieuwe afhaalcode terug.
// Let op: roep eerst validate_parcel_input() aan, daar zit de controle of het vakje vrij is.
function register_parcel(array $invoer, int $medewerker_id): string
{
    $db = get_db();

    // Ligt het pakket al binnen? Zo niet, dan wordt het alleen verwacht.
    $is_binnen = $invoer['status'] === 'arrived';

    // Een nieuwe, unieke afhaalcode erbij.
    $afhaalcode = generate_pickup_code();

    // Alleen een pakket dat binnen is krijgt een vakje en een deadline.
    // Een verwacht pakket krijgt die pas als het binnenkomt (zie receive_parcel).
    $vak_id = $is_binnen ? $invoer['storage_slot_id'] : null;
    $deadline = $is_binnen ? pickup_deadline_from_now() : null;

    // Heeft de klant al een account? Dan koppelen we het pakket meteen daaraan.
    $klant_id = find_user_id_by_email($invoer['customer_email']);

    // Nu komt een transactie: er moet of alles lukken, of niks. Zo kan het nooit
    // gebeuren dat het pakket wel is opgeslagen maar het vakje nog als leeg staat.
    // beginTransaction betekent: houd alle wijzigingen even vast.
    $db->beginTransaction();

    // 'try' betekent: probeer dit. Gaat er iets mis, dan springen we naar 'catch'.
    try {
        // Eerst het vakje op bezet zetten, maar alleen als het pakket binnen is.
        if ($is_binnen) {
            set_slot_status($vak_id, 'occupied');
        }

        // Dan het pakket zelf opslaan.
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

        // commit betekent: het is gelukt, bewaar de wijzigingen nu echt.
        $db->commit();
        return $afhaalcode;
    } catch (PDOException $fout) {
        // Er ging iets mis met de database.
        // rollBack draait alles terug, alsof er niks is gebeurd.
        $db->rollBack();

        // De fout geven we door, zodat de gebruiker "Er ging iets mis" te zien krijgt.
        throw $fout;
    }
}

// Een verwacht pakket is binnengekomen: het gaat in een vrij vakje.
// De status wordt 'arrived' en de afhaaltermijn begint te lopen.
// Let op: controleer eerst met is_slot_free() of het vakje vrij is.
function receive_parcel(array $pakket, int $vak_id, int $medewerker_id): void
{
    $db = get_db();

    // Het vakje bezetten en het pakket bijwerken horen bij elkaar, dus weer een transactie.
    $db->beginTransaction();

    try {
        // Eerst het vakje bezetten.
        set_slot_status($vak_id, 'occupied');

        // Dan het pakket op 'binnengekomen' zetten, met vakje, tijdstip en deadline.
        $query = $db->prepare("
            UPDATE parcels
            SET status = 'arrived', storage_slot_id = ?, received_at = NOW(),
                pickup_deadline = ?, received_by_user_id = ?
            WHERE id = ?
        ");
        $query->execute([$vak_id, pickup_deadline_from_now(), $medewerker_id, $pakket['id']]);

        // Gelukt, dus bewaren.
        $db->commit();
    } catch (PDOException $fout) {
        // Mislukt, dus alles terugdraaien.
        $db->rollBack();
        throw $fout;
    }
}

// Geeft een pakket mee aan de klant en maakt het vakje weer vrij.
function hand_out_parcel(array $pakket): void
{
    finish_parcel($pakket, 'picked_up');
}

// Stuurt een pakket terug naar de vervoerder en maakt het vakje weer vrij.
function return_parcel(array $pakket): void
{
    finish_parcel($pakket, 'returned');
}

// Zet een pakket op 'picked_up' of 'returned' en maakt het vakje vrij.
// Uitgeven en retour sturen doen precies hetzelfde, op de status na. Daarom staat
// het hier maar een keer.
function finish_parcel(array $pakket, string $nieuwe_status): void
{
    $db = get_db();

    // Het pakket bijwerken en het vakje vrijmaken horen weer bij elkaar.
    $db->beginTransaction();

    try {
        // Eerst de nieuwe status en het tijdstip opslaan.
        $query = $db->prepare("UPDATE parcels SET status = ?, picked_up_at = NOW() WHERE id = ? AND status = 'arrived'");
        $query->execute([$nieuwe_status, $pakket['id']]);

        // Dan het vakje vrijgeven voor het volgende pakket.
        if ($pakket['storage_slot_id']) {
            set_slot_status($pakket['storage_slot_id'], 'free');
        }

        // Gelukt, dus bewaren.
        $db->commit();
    } catch (PDOException $fout) {
        // Mislukt, dus alles terugdraaien.
        $db->rollBack();
        throw $fout;
    }
}

// Verplaatst een pakket naar een ander vakje.
// Let op: controleer eerst met is_slot_free() of het nieuwe vakje vrij is.
function move_parcel_to_slot(array $pakket, int $nieuw_vak_id): void
{
    $db = get_db();
    $db->beginTransaction();

    try {
        // Het nieuwe vakje wordt bezet.
        set_slot_status($nieuw_vak_id, 'occupied');

        // Het pakket hoort nu bij dat nieuwe vakje.
        $query = $db->prepare('UPDATE parcels SET storage_slot_id = ? WHERE id = ?');
        $query->execute([$nieuw_vak_id, $pakket['id']]);

        // En het oude vakje komt weer vrij.
        if ($pakket['storage_slot_id']) {
            set_slot_status($pakket['storage_slot_id'], 'free');
        }

        $db->commit();
    } catch (PDOException $fout) {
        $db->rollBack();
        throw $fout;
    }
}

// Ligt dit pakket al te lang? Dat geldt alleen voor pakketten die nog in de winkel liggen.
function is_overdue(string $status, ?string $deadline): bool
{
    // Al opgehaald of teruggestuurd? Dan is het nooit te laat.
    if ($status !== 'arrived' || !$deadline) {
        return false;
    }

    // strtotime maakt van de deadline een getal (seconden sinds 1970) en time() is nu.
    // Is het getal van de deadline kleiner dan dat van nu, dan is de deadline al voorbij.
    return strtotime($deadline) < time();
}

// Hoeveel dagen is de deadline al voorbij?
function days_overdue(string $deadline): int
{
    // diff() rekent het verschil tussen twee datums uit.
    return (new DateTime($deadline))->diff(new DateTime())->days;
}

// Geeft een kleurig labeltje voor de status van een pakket.
function status_badge(string $status, ?string $deadline = null): string
{
    // Ligt het pakket te lang? Dan gaat die oranje waarschuwing boven alles.
    if (is_overdue($status, $deadline)) {
        return '<span class="badge bg-amber-100 text-amber-900 border border-amber-300">⚠️ Te lang aanwezig</span>';
    }

    // Elke status krijgt een kleur en een tekst. Alleen kleur is niet voor iedereen
    // duidelijk, bijvoorbeeld niet als je kleurenblind bent.
    // 'match' werkt als een keuzelijst: is de status dit, geef dan dat labeltje terug.
    // 'default' is het vangnet voor als de status geen van de andere is.
    return match ($status) {
        'expected'  => '<span class="badge bg-sky-100 text-sky-800 border border-sky-300">🔵 Verwacht</span>',
        'arrived'   => '<span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300">🟢 Binnengekomen (Klaar)</span>',
        'picked_up' => '<span class="badge bg-slate-100 text-slate-700 border border-slate-300">⚪ Uitgegeven</span>',
        'returned'  => '<span class="badge bg-rose-50 text-rose-800 border border-rose-200">↩️ Retour vervoerder</span>',
        default     => '<span class="badge bg-slate-100 text-slate-700">' . h($status) . '</span>',
    };
}

// Maakt de tijdlijn voor de klant: Aangemeld, Binnengekomen en Opgehaald.
// Elke stap heeft een tekst en 'klaar'. Als dat true is, is die stap al gebeurd.
function parcel_timeline(array $pakket): array
{
    $status = $pakket['status'];

    // Is het pakket teruggestuurd? Dan heet de laatste stap anders.
    $laatste_stap = $status === 'returned' ? 'Retour vervoerder' : 'Opgehaald';

    return [
        ['tekst' => 'Aangemeld',     'klaar' => true],
        ['tekst' => 'Binnengekomen', 'klaar' => $status !== 'expected'],
        ['tekst' => $laatste_stap,   'klaar' => $status === 'picked_up' || $status === 'returned'],
    ];
}
