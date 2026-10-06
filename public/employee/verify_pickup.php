<?php
// employee/verify_pickup.php
//
// Hier geeft de medewerker een pakket mee aan de klant. De klant noemt zijn afhaalcode,
// de medewerker typt die in, en als de code klopt wordt het pakket uitgegeven en komt
// het vakje weer vrij.
//
// De belangrijkste regel: een pakket gaat alleen mee met de juiste afhaalcode.

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../../includes/init.php';

// Alleen medewerkers en admins mogen pakketten uitgeven.
require_role(['employee', 'admin']);

// Om welk pakket gaat het? Het nummer staat in de adresbalk, bijvoorbeeld ?id=1.
// Met (int) maken we er veilig een getal van.
$pakket_id = (int) ($_GET['id'] ?? 0);

// Het pakket opzoeken in de database.
$pakket = find_parcel($pakket_id);

// Bestaat het pakket niet? Dan gaan we terug naar het dashboard.
if (!$pakket) {
    redirect_with_message('/employee/dashboard.php', 'error', 'Pakket niet gevonden.');
}

// Alleen een pakket dat binnen ligt mag worden uitgegeven. Niet een dat nog verwacht
// wordt, en ook niet een dat al is opgehaald.
if ($pakket['status'] !== 'arrived') {
    $status_tekst = PARCEL_STATUSES[$pakket['status']];
    redirect_with_message('/employee/dashboard.php', 'error', "Dit pakket kan niet worden uitgegeven (status: $status_tekst).");
}

// Is er net een formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Klopt de afhaalcode die de klant noemt?
    if (!pickup_code_matches($pakket, $_POST['pickup_code'] ?? '')) {
        // Nee, dus de medewerker krijgt een waarschuwing en er gebeurt verder niks.
        set_flash('error', '⚠️ Onjuiste afhaalcode! Vraag de klant om de juiste code uit zijn e-mail of account.');
    } else {
        // Ja. Het pakket wordt uitgegeven en het vakje komt vrij.
        hand_out_parcel($pakket);

        // Terug naar het dashboard met een melding dat het gelukt is.
        redirect_with_message(
            '/employee/dashboard.php',
            'success',
            "Pakket succesvol uitgegeven! Opslagvak {$pakket['slot_code']} is weer vrij."
        );
    }
}

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Pakket Uitgeven';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-md mx-auto card p-6 text-center">

    <!-- De titel -->
    <h1 class="page-title">Afhaalcontrole &amp; Uitgifte</h1>
    <p class="page-subtitle mb-5">Vraag de klant om zijn afhaalcode.</p>

    <!-- Welk pakket moet de medewerker uit het vak halen? -->
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

    <!-- Het formulier waar de afhaalcode van de klant wordt ingetypt -->
    <form method="POST" class="space-y-4">
        <!-- Een verborgen geheime code die de site beschermt tegen nepformulieren (CSRF) -->
        <?= csrf_field(); ?>

        <div>
            <label for="pickup_code" class="label uppercase">Afhaalcode klant *</label>
            <input type="text" id="pickup_code" name="pickup_code" required autofocus maxlength="10" autocomplete="off"
                   class="input border-2 border-brand-sky text-center font-mono font-bold text-2xl uppercase text-brand-navy"
                   placeholder="PK-XXXX">
        </div>

        <!-- De knop om te bevestigen -->
        <button type="submit" class="btn btn-success w-full py-3">✓ Bevestig Uitgifte &amp; Maak Vak Vrij</button>
    </form>

    <!-- Toch maar niet? Dan kun je hier terug. -->
    <a href="/employee/dashboard.php" class="inline-block mt-4 text-sm text-slate-400 hover:text-slate-600">
        Annuleren en terug naar balie
    </a>
</div>

<?php
// En de onderkant van de pagina.
require_once __DIR__ . '/../../includes/footer.php';
?>
