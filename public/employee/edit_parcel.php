<?php
// employee/edit_parcel.php
//
// Op deze pagina beheert de medewerker een pakket. Wat je kunt doen hangt af van
// waar het pakket is:
//
//   Het pakket wordt nog verwacht
//     - de ontvangst registreren: je legt het in een vrij vakje
//   Het pakket is binnengekomen
//     - het naar een ander vrij vakje verplaatsen
//     - het terugsturen naar de vervoerder
//     - doorklikken naar uitgeven (daar is de afhaalcode voor nodig)

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../../includes/init.php';

// Alleen medewerkers en admins mogen hier komen.
require_role(['employee', 'admin']);

// Om welk pakket gaat het? Het nummer staat in de adresbalk en we maken er veilig een getal van.
$pakket_id = (int) ($_GET['id'] ?? 0);
$pakket = find_parcel($pakket_id);

// Bestaat het pakket niet? Dan gaan we terug met een melding.
if (!$pakket) {
    redirect_with_message('/employee/dashboard.php', 'error', 'Pakket niet gevonden.');
}

// Is het pakket al afgehandeld, dus opgehaald of teruggestuurd? Dan mag er niks meer aan veranderen.
if (!is_active_parcel($pakket)) {
    $status_tekst = PARCEL_STATUSES[$pakket['status']];
    redirect_with_message('/employee/dashboard.php', 'error', "Dit pakket is al afgehandeld (status: $status_tekst) en kan niet meer worden gewijzigd.");
}

// Is er op een knop gedrukt?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Op welke knop? Dat staat in een verborgen veld 'action' in het formulier.
    $actie = $_POST['action'] ?? '';

    // Wordt het pakket nog verwacht, of is het al binnen?
    $is_verwacht = $pakket['status'] === 'expected';

    // Ontvangen kan alleen als het pakket nog verwacht wordt.
    if ($actie === 'receive' && !$is_verwacht) {
        redirect_with_message("/employee/edit_parcel.php?id=$pakket_id", 'error', 'Dit pakket is al binnen en kan niet nog een keer ontvangen worden.');
    }

    // Verplaatsen en terugsturen kan alleen als het pakket al binnen is.
    if ($actie !== 'receive' && $is_verwacht) {
        redirect_with_message("/employee/edit_parcel.php?id=$pakket_id", 'error', 'Dit pakket is nog niet binnen. Registreer eerst de ontvangst.');
    }

    // Actie 1: de ontvangst registreren. Het verwachte pakket gaat in een vrij vakje.
    if ($actie === 'receive') {
        $vak_id = (int) ($_POST['storage_slot_id'] ?? 0);

        // Is het gekozen vakje echt vrij?
        if (!is_slot_free($vak_id)) {
            set_flash('error', 'Kies een vrij opslagvak.');
        } else {
            // Ja, dus de ontvangst kan worden opgeslagen.
            receive_parcel($pakket, $vak_id, current_user()['id']);
            redirect_with_message("/employee/edit_parcel.php?id=$pakket_id", 'success', 'Ontvangst geregistreerd! Het pakket ligt nu in het gekozen vak en de afhaaltermijn is gestart.');
        }
    }

    // Actie 2: het pakket naar een ander vakje verplaatsen.
    if ($actie === 'move_slot') {
        $nieuw_vak_id = (int) ($_POST['storage_slot_id'] ?? 0);

        // Is het gekozen vakje echt vrij?
        if (!is_slot_free($nieuw_vak_id)) {
            set_flash('error', 'Kies een vrij opslagvak.');
        } else {
            // Ja, dus we kunnen verplaatsen.
            move_parcel_to_slot($pakket, $nieuw_vak_id);
            redirect_with_message("/employee/edit_parcel.php?id=$pakket_id", 'success', 'Het pakket is verplaatst naar een ander opslagvak.');
        }
    }

    // Actie 3: het pakket terugsturen naar de vervoerder.
    if ($actie === 'return') {
        return_parcel($pakket);
        redirect_with_message(
            '/employee/dashboard.php',
            'success',
            "Pakket {$pakket['pickup_code']} staat nu op 'Retour vervoerder'. Opslagvak {$pakket['slot_code']} is weer vrij."
        );
    }
}

// De vrije vakjes voor het keuzemenu.
$vrije_vakken = get_free_slots();

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Pakket Beheren';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">

    <!-- De titel en een knop om terug te gaan -->
    <div class="card p-6 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="page-title">Pakket Beheren</h1>
            <p class="page-subtitle">Afhaalcode <span class="code"><?= h($pakket['pickup_code']); ?></span></p>
        </div>
        <a href="/employee/dashboard.php" class="text-sm font-bold text-brand-navy hover:underline">&larr; Terug naar balie</a>
    </div>

    <!-- Alle gegevens van het pakket -->
    <section class="card p-6">
        <h2 class="card-title mb-4">Pakketgegevens</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div><dt class="text-slate-400">Status</dt><dd><?= status_badge($pakket['status'], $pakket['pickup_deadline']); ?></dd></div>
            <div>
                <dt class="text-slate-400">Opslagvak</dt>
                <dd>
                    <?php if ($pakket['status'] === 'arrived'): ?>
                        <span class="badge bg-slate-800 text-white">Vak <?= h($pakket['slot_code']); ?></span>
                    <?php else: ?>
                        <span class="text-slate-400">Nog geen vak (pakket is nog niet binnen)</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div><dt class="text-slate-400">Klant</dt><dd class="font-semibold"><?= h($pakket['customer_name']); ?></dd></div>
            <div><dt class="text-slate-400">Contact</dt><dd><?= h($pakket['customer_email']); ?> <?= $pakket['customer_phone'] ? '| ' . h($pakket['customer_phone']) : ''; ?></dd></div>
            <div><dt class="text-slate-400">Vervoerder</dt><dd><?= h($pakket['carrier_name']); ?></dd></div>
            <div><dt class="text-slate-400">Barcode</dt><dd class="font-mono"><?= h($pakket['tracking_code']); ?></dd></div>
            <div><dt class="text-slate-400"><?= $pakket['status'] === 'expected' ? 'Aangemeld op' : 'Binnengekomen'; ?></dt><dd><?= format_date($pakket['received_at']); ?></dd></div>
            <div><dt class="text-slate-400">Uiterste afhaaldatum</dt><dd class="font-bold text-brand-navy"><?= format_date($pakket['pickup_deadline']); ?></dd></div>
        </dl>
    </section>

    <?php if ($pakket['status'] === 'expected'): ?>
        <!-- Het pakket wordt nog verwacht, dus je kunt de ontvangst registreren -->
        <section class="card p-6">
            <h2 class="card-title mb-1">Ontvangst registreren</h2>
            <p class="text-sm text-slate-500 mb-4">Is het pakket binnengekomen? Leg het in een vrij vak. Daarna start de afhaaltermijn van <?= PICKUP_DAYS; ?> dagen.</p>

            <?php if (empty($vrije_vakken)): ?>
                <p class="alert alert-warning">Er zijn geen vrije vakken. Vraag een beheerder om nieuwe vakken aan te maken.</p>
            <?php else: ?>
                <form method="POST" class="space-y-3">
                    <?= csrf_field(); ?>
                    <!-- Dit verborgen veld vertelt de server welke actie we willen -->
                    <input type="hidden" name="action" value="receive">

                    <label for="ontvangst_vak" class="label">Opslagvak *</label>
                    <select id="ontvangst_vak" name="storage_slot_id" required class="input font-bold text-brand-navy">
                        <option value="">-- Kies vrij vak --</option>
                        <?php foreach ($vrije_vakken as $vak): ?>
                            <option value="<?= (int) $vak['id']; ?>">Vak <?= h($vak['slot_code']); ?> (<?= h($vak['rack']); ?>)</option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary w-full">📦 Pakket ontvangen &amp; in vak leggen</button>
                </form>
            <?php endif; ?>
        </section>

    <?php else: ?>
    <!-- Het pakket is binnen. Twee blokken naast elkaar, op een telefoon onder elkaar. -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Blok 1: een ander vakje kiezen -->
        <section class="card p-6">
            <h2 class="card-title mb-1">Opslagvak wijzigen</h2>
            <p class="text-sm text-slate-500 mb-4">Verplaats het pakket naar een ander vrij vak. Het oude vak wordt dan weer vrij.</p>

            <?php if (empty($vrije_vakken)): ?>
                <p class="alert alert-warning">Er zijn geen vrije vakken om naartoe te verplaatsen.</p>
            <?php else: ?>
                <form method="POST" class="space-y-3">
                    <?= csrf_field(); ?>
                    <!-- Dit verborgen veld vertelt de server welke actie we willen -->
                    <input type="hidden" name="action" value="move_slot">

                    <label for="storage_slot_id" class="label">Nieuw opslagvak</label>
                    <select id="storage_slot_id" name="storage_slot_id" required class="input font-bold text-brand-navy">
                        <option value="">-- Kies vrij vak --</option>
                        <?php foreach ($vrije_vakken as $vak): ?>
                            <option value="<?= (int) $vak['id']; ?>">Vak <?= h($vak['slot_code']); ?> (<?= h($vak['rack']); ?>)</option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary w-full">Verplaatsen</button>
                </form>
            <?php endif; ?>
        </section>

        <!-- Blok 2: de status aanpassen -->
        <section class="card p-6">
            <h2 class="card-title mb-1">Status aanpassen</h2>
            <p class="text-sm text-slate-500 mb-4">
                Komt de klant zijn pakket halen? Kies <strong>Uitgeven</strong> (afhaalcode nodig).
                Wordt het pakket niet opgehaald? Stuur het <strong>retour naar de vervoerder</strong>.
            </p>

            <div class="space-y-3">
                <!-- Naar het scherm om het pakket uit te geven -->
                <a href="/employee/verify_pickup.php?id=<?= $pakket_id; ?>" class="btn btn-success w-full">✓ Uitgeven aan klant</a>

                <!-- Terugsturen. Eerst vragen we of je het zeker weet. -->
                <form method="POST" onsubmit="return confirm('Weet je zeker dat dit pakket retour gaat naar de vervoerder?');">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="return">
                    <button type="submit" class="btn btn-warning w-full">↩️ Retour naar vervoerder</button>
                </form>
            </div>
        </section>
    </div>
    <?php endif; ?>
</div>

<?php
// En de onderkant van de pagina.
require_once __DIR__ . '/../../includes/footer.php';
?>
