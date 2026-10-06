<?php
// ==============================================================================
// PAKKET UITGEVEN (public/employee/verify_pickup.php)
// ==============================================================================
// De klant noemt zijn afhaalcode, de medewerker typt hem in.
// Klopt de code? Dan wordt het pakket uitgegeven en het opslagvak weer vrij (FE-07).
//
// BUSINESS RULE: een pakket wordt ALLEEN uitgegeven met de juiste afhaalcode.

// Laad alles wat we nodig hebben
require_once __DIR__ . '/../../includes/init.php';

// Alleen medewerkers en admins mogen pakketten uitgeven
require_role(['employee', 'admin']);

// Welk pakket? Het ID staat in de adresbalk (bijv. ?id=1). (int) maakt er veilig een getal van.
$pakket_id = (int) ($_GET['id'] ?? 0);

// Haal het pakket op uit de database
$pakket = find_parcel($pakket_id);

// Bestaat het pakket niet? Terug naar het dashboard
if (!$pakket) {
    redirect_with_message('/employee/dashboard.php', 'error', 'Pakket niet gevonden.');
}

// Alleen een binnengekomen pakket mag worden uitgegeven (niet verwacht, niet al opgehaald)
if ($pakket['status'] !== 'arrived') {
    $status_tekst = PARCEL_STATUSES[$pakket['status']];
    redirect_with_message('/employee/dashboard.php', 'error', "Dit pakket kan niet worden uitgegeven (status: $status_tekst).");
}

// Is het formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Klopt de afhaalcode die de klant noemt?
    if (!pickup_code_matches($pakket, $_POST['pickup_code'] ?? '')) {
        // Nee: waarschuw de medewerker
        set_flash('error', '⚠️ Onjuiste afhaalcode! Vraag de klant om de juiste code uit zijn e-mail of account.');
    } else {
        // Ja: geef het pakket uit en maak het vak vrij
        hand_out_parcel($pakket);

        // Terug naar het dashboard met een succesmelding
        redirect_with_message(
            '/employee/dashboard.php',
            'success',
            "Pakket succesvol uitgegeven! Opslagvak {$pakket['slot_code']} is weer vrij."
        );
    }
}

// Titel en bovenkant van de pagina
$pagina_titel = 'Pakket Uitgeven';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-md mx-auto card p-6 text-center">

    <!-- Titel -->
    <h1 class="page-title">Afhaalcontrole &amp; Uitgifte</h1>
    <p class="page-subtitle mb-5">Vraag de klant om zijn afhaalcode.</p>

    <!-- Welk pakket moet de medewerker pakken? -->
    <dl class="bg-slate-50 p-4 rounded-xl text-sm text-left mb-5 space-y-2 border border-slate-200">
        <div>
            <dt class="inline text-slate-400">Te pakken vak:</dt>
            <dd class="inline"><span class="badge bg-slate-800 text-white text-sm">Vak <?= h($pakket['slot_code']); ?></span></dd>
        </div>
        <div>
            <dt class="inline text-slate-400">Naam klant:</dt>
            <dd class="inline font-semibold text-slate-700"><?= h($pakket['customer_name']); ?></dd>
        </div>
        <div>
            <dt class="inline text-slate-400">Vervoerder:</dt>
            <dd class="inline text-slate-600"><?= h($pakket['carrier_name']); ?> (<?= h($pakket['tracking_code']); ?>)</dd>
        </div>
    </dl>

    <!-- Formulier om de afhaalcode in te typen -->
    <form method="POST" class="space-y-4">
        <!-- Geheime CSRF-code (beveiliging) -->
        <?= csrf_field(); ?>

        <div>
            <label for="pickup_code" class="label uppercase">Afhaalcode klant *</label>
            <input type="text" id="pickup_code" name="pickup_code" required autofocus maxlength="10" autocomplete="off"
                   class="input border-2 border-brand-sky text-center font-mono font-bold text-2xl uppercase text-brand-navy"
                   placeholder="PK-XXXX">
        </div>

        <!-- Bevestigen -->
        <button type="submit" class="btn btn-success w-full py-3">✓ Bevestig Uitgifte &amp; Maak Vak Vrij</button>
    </form>

    <!-- Annuleren -->
    <a href="/employee/dashboard.php" class="inline-block mt-4 text-sm text-slate-400 hover:text-slate-600">
        Annuleren en terug naar balie
    </a>
</div>

<?php
// Onderkant van de pagina
require_once __DIR__ . '/../../includes/footer.php';
?>
