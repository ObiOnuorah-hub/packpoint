<?php
// ==============================================================================
// PAKKET BEHEREN (public/employee/edit_parcel.php)
// ==============================================================================
// Op deze pagina kan de medewerker bij 1 pakket:
//   VERWACHT pakket:
//     - ontvangst registreren: het pakket in een vrij vak leggen          (FE-05)
//   BINNENGEKOMEN pakket:
//     - het opslagvak wijzigen (naar een ander vrij vak verplaatsen)      (FE-05)
//     - de status aanpassen: retour naar de vervoerder sturen
//     - doorklikken naar uitgeven (daarvoor is de afhaalcode nodig)

// Laad alles wat we nodig hebben
require_once __DIR__ . '/../../includes/init.php';

// Alleen medewerkers en admins
require_role(['employee', 'admin']);

// Welk pakket? (ID uit de adresbalk, veilig omgezet naar een getal)
$pakket_id = (int) ($_GET['id'] ?? 0);
$pakket = find_parcel($pakket_id);

// Bestaat het pakket niet? Terug met een melding
if (!$pakket) {
    redirect_with_message('/employee/dashboard.php', 'error', 'Pakket niet gevonden.');
}

// Is het pakket al afgehandeld (opgehaald of retour)? Dan mag je het niet meer wijzigen
if (!is_active_parcel($pakket)) {
    $status_tekst = PARCEL_STATUSES[$pakket['status']];
    redirect_with_message('/employee/dashboard.php', 'error', "Dit pakket is al afgehandeld (status: $status_tekst) en kan niet meer worden gewijzigd.");
}

// Is er op een knop gedrukt?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Welke knop? (verborgen veld 'action' in het formulier)
    $actie = $_POST['action'] ?? '';

    // Is het pakket verwacht of al binnen?
    $is_verwacht = $pakket['status'] === 'expected';

    // Ontvangen kan alleen bij een VERWACHT pakket
    if ($actie === 'receive' && !$is_verwacht) {
        redirect_with_message("/employee/edit_parcel.php?id=$pakket_id", 'error', 'Dit pakket is al binnen en kan niet nog een keer ontvangen worden.');
    }

    // Verplaatsen en retour kunnen alleen bij een BINNENGEKOMEN pakket
    if ($actie !== 'receive' && $is_verwacht) {
        redirect_with_message("/employee/edit_parcel.php?id=$pakket_id", 'error', 'Dit pakket is nog niet binnen. Registreer eerst de ontvangst.');
    }

    // ACTIE 0: ontvangst registreren (verwacht pakket in een vrij vak leggen)
    if ($actie === 'receive') {
        $vak_id = (int) ($_POST['storage_slot_id'] ?? 0);

        // Controleer of het gekozen vak echt vrij is
        if (!is_slot_free($vak_id)) {
            set_flash('error', 'Kies een vrij opslagvak.');
        } else {
            // Vak is vrij: ontvangst opslaan
            receive_parcel($pakket, $vak_id, current_user()['id']);
            redirect_with_message("/employee/edit_parcel.php?id=$pakket_id", 'success', 'Ontvangst geregistreerd! Het pakket ligt nu in het gekozen vak en de afhaaltermijn is gestart.');
        }
    }

    // ACTIE 1: pakket naar een ander vak verplaatsen
    if ($actie === 'move_slot') {
        $nieuw_vak_id = (int) ($_POST['storage_slot_id'] ?? 0);

        // Controleer of het gekozen vak echt vrij is
        if (!is_slot_free($nieuw_vak_id)) {
            set_flash('error', 'Kies een vrij opslagvak.');
        } else {
            // Vak is vrij: verplaatsen
            move_parcel_to_slot($pakket, $nieuw_vak_id);
            redirect_with_message("/employee/edit_parcel.php?id=$pakket_id", 'success', 'Het pakket is verplaatst naar een ander opslagvak.');
        }
    }

    // ACTIE 2: status aanpassen naar 'retour vervoerder'
    if ($actie === 'return') {
        return_parcel($pakket);
        redirect_with_message(
            '/employee/dashboard.php',
            'success',
            "Pakket {$pakket['pickup_code']} staat nu op 'Retour vervoerder'. Opslagvak {$pakket['slot_code']} is weer vrij."
        );
    }
}

// Lijst met vrije vakken voor het keuzemenu
$vrije_vakken = get_free_slots();

// Titel en bovenkant van de pagina
$pagina_titel = 'Pakket Beheren';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">

    <!-- Titel + terugknop -->
    <div class="card p-6 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="page-title">Pakket Beheren</h1>
            <p class="page-subtitle">Afhaalcode <span class="code"><?= h($pakket['pickup_code']); ?></span></p>
        </div>
        <a href="/employee/dashboard.php" class="text-sm font-bold text-brand-navy hover:underline">&larr; Terug naar balie</a>
    </div>

    <!-- Gegevens van het pakket -->
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
        <!-- ============================================================== -->
        <!-- VERWACHT PAKKET: ontvangst registreren                          -->
        <!-- ============================================================== -->
        <section class="card p-6">
            <h2 class="card-title mb-1">Ontvangst registreren</h2>
            <p class="text-sm text-slate-500 mb-4">Is het pakket binnengekomen? Leg het in een vrij vak. Daarna start de afhaaltermijn van <?= PICKUP_DAYS; ?> dagen.</p>

            <?php if (empty($vrije_vakken)): ?>
                <p class="alert alert-warning">Er zijn geen vrije vakken. Vraag een beheerder om nieuwe vakken aan te maken.</p>
            <?php else: ?>
                <form method="POST" class="space-y-3">
                    <?= csrf_field(); ?>
                    <!-- Vertelt de server welke actie we willen -->
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
    <!-- ============================================================== -->
    <!-- BINNENGEKOMEN PAKKET: twee blokken naast elkaar (onder elkaar op mobiel) -->
    <!-- ============================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- BLOK 1: opslagvak wijzigen -->
        <section class="card p-6">
            <h2 class="card-title mb-1">Opslagvak wijzigen</h2>
            <p class="text-sm text-slate-500 mb-4">Verplaats het pakket naar een ander vrij vak. Het oude vak wordt dan weer vrij.</p>

            <?php if (empty($vrije_vakken)): ?>
                <p class="alert alert-warning">Er zijn geen vrije vakken om naartoe te verplaatsen.</p>
            <?php else: ?>
                <form method="POST" class="space-y-3">
                    <?= csrf_field(); ?>
                    <!-- Vertelt de server welke actie we willen -->
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

        <!-- BLOK 2: status aanpassen -->
        <section class="card p-6">
            <h2 class="card-title mb-1">Status aanpassen</h2>
            <p class="text-sm text-slate-500 mb-4">
                Komt de klant zijn pakket halen? Kies <strong>Uitgeven</strong> (afhaalcode nodig).
                Wordt het pakket niet opgehaald? Stuur het <strong>retour naar de vervoerder</strong>.
            </p>

            <div class="space-y-3">
                <!-- Naar het uitgifte-scherm -->
                <a href="/employee/verify_pickup.php?id=<?= $pakket_id; ?>" class="btn btn-success w-full">✓ Uitgeven aan klant</a>

                <!-- Retour: vraagt eerst om bevestiging -->
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
// Onderkant van de pagina
require_once __DIR__ . '/../../includes/footer.php';
?>
