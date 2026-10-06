<?php
// employee/overdue.php
//
// Alle pakketten waarvan de uiterste afhaaldatum al voorbij is. De medewerker kan hier
// de klant bellen, het pakket alsnog meegeven of het terugsturen naar de vervoerder.

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../../includes/init.php';

// Alleen medewerkers en admins mogen hier komen.
require_role(['employee', 'admin']);

// De pakketten ophalen die te lang liggen.
$pakketten = get_overdue_parcels();

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Te Lang Liggen';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">

    <!-- Een gele waarschuwing bovenaan, met een teller -->
    <div class="bg-amber-50 border-l-4 border-amber-500 p-6 rounded-2xl shadow-sm flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div>
            <h1 class="text-xl font-bold text-amber-900">Signalering Te Lang Aanwezige Pakketten</h1>
            <p class="text-sm text-amber-800 mt-1">
                Pakketten waarvan de uiterste afhaaldatum (<?= PICKUP_DAYS; ?> dagen) is verstreken.
                Neem contact op met de klant of stuur het pakket retour.
            </p>
        </div>
        <!-- Hoeveel pakketten zijn er te laat? -->
        <span class="text-2xl font-extrabold text-amber-900 bg-amber-200/70 px-4 py-1.5 rounded-xl border border-amber-300 whitespace-nowrap">
            <?= count($pakketten); ?> Verlopen
        </span>
    </div>

    <section class="card overflow-hidden">
        <?php if (empty($pakketten)): ?>
            <!-- Er ligt gelukkig niks te lang -->
            <div class="p-8 text-center">
                <p class="text-sm font-bold text-slate-700">✅ Geen te lang liggende pakketten!</p>
                <p class="text-sm text-slate-500 mt-1">Alle pakketten vallen binnen de bewaartermijn van <?= PICKUP_DAYS; ?> dagen.</p>
            </div>
        <?php else: ?>
            <!-- De tabel met de pakketten die te laat zijn -->
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Opslagvak</th>
                            <th>Afhaalcode</th>
                            <th>Klant &amp; contact</th>
                            <th>Vervoerder</th>
                            <th>Uiterste datum</th>
                            <th>Dagen te laat</th>
                            <th class="text-right">Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pakketten as $pakket): ?>
                            <?php
                                // Hoeveel dagen is dit pakket te laat?
                                $dagen = days_overdue($pakket['pickup_deadline']);
                            ?>
                            <tr class="bg-amber-50/40">
                                <!-- Het vakje -->
                                <td><span class="badge bg-slate-800 text-white">Vak <?= h($pakket['slot_code'] ?? 'Geen'); ?></span></td>

                                <!-- De afhaalcode -->
                                <td class="code"><?= h($pakket['pickup_code']); ?></td>

                                <!-- De klant, met e-mail en telefoon zodat je hem kunt bellen of mailen -->
                                <td>
                                    <div class="font-semibold text-slate-800"><?= h($pakket['customer_name']); ?></div>
                                    <div class="text-xs text-slate-500">
                                        <?= h($pakket['customer_email']); ?> | <?= h($pakket['customer_phone'] ?? 'Geen tel'); ?>
                                    </div>
                                </td>

                                <!-- De vervoerder en de barcode -->
                                <td>
                                    <div class="font-medium text-slate-700"><?= h($pakket['carrier_name']); ?></div>
                                    <div class="text-xs font-mono text-slate-400"><?= h($pakket['tracking_code']); ?></div>
                                </td>

                                <!-- De uiterste datum -->
                                <td class="font-semibold text-amber-900 whitespace-nowrap"><?= format_date($pakket['pickup_deadline']); ?></td>

                                <!-- Hoeveel dagen te laat. Bij 1 staat er "dag", bij meer "dagen". -->
                                <td>
                                    <span class="badge bg-amber-200 text-amber-900">
                                        <?= $dagen; ?> <?= $dagen === 1 ? 'dag' : 'dagen'; ?> te laat
                                    </span>
                                </td>

                                <!-- De knoppen: beheren (daar kun je ook terugsturen) of uitgeven -->
                                <td class="text-right whitespace-nowrap">
                                    <a href="/employee/edit_parcel.php?id=<?= (int) $pakket['id']; ?>" class="btn btn-secondary btn-sm">Beheren</a>
                                    <a href="/employee/verify_pickup.php?id=<?= (int) $pakket['id']; ?>" class="btn btn-success btn-sm">Uitgeven</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>

<?php
// En de onderkant van de pagina.
require_once __DIR__ . '/../../includes/footer.php';
?>
