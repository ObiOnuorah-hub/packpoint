<?php
// employee/slots.php
//
// Een raster met alle stellingen en vakjes. Je ziet in een oogopslag welk vakje vrij
// is, welk bezet is en waar een pakket al te lang ligt.

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../../includes/init.php';

// Alleen medewerkers en admins mogen hier komen.
require_role(['employee', 'admin']);

// Alle vakjes ophalen, met het pakket dat erin ligt, en per stelling in groepjes zetten.
$stellingen = group_slots_by_rack(get_slots_with_parcels());

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Opslagvakken';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">

    <!-- De titel, met een legenda die uitlegt wat de kleuren betekenen -->
    <div class="card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="page-title">Opslagvakken (Vakkenraster)</h1>
            <p class="page-subtitle">Overzicht van alle stellingen en de bezetting.</p>
        </div>

        <!-- De legenda -->
        <div class="flex flex-wrap items-center gap-4 text-xs font-semibold">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-emerald-500 rounded-full"></span> Vrij</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-brand-navy rounded-full"></span> Bezet</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-amber-500 rounded-full"></span> Te lang aanwezig</span>
        </div>
    </div>

    <!-- Zijn er nog helemaal geen vakjes? -->
    <?php if (empty($stellingen)): ?>
        <p class="card p-8 text-center text-slate-500 text-sm">Er zijn nog geen opslagvakken. Een beheerder kan ze toevoegen via Vakken Beheer.</p>
    <?php endif; ?>

    <!-- Voor elke stelling (Stelling A, Stelling B, ...) een eigen blok -->
    <?php foreach ($stellingen as $stelling => $vakken): ?>
        <section class="card p-6 space-y-4">

            <!-- De naam van de stelling en hoeveel vakjes erin zitten -->
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-2">
                <?= h($stelling); ?> (<?= count($vakken); ?> vakken)
            </h2>

            <!-- De vakjes in een raster: 2 per rij op een telefoon, 5 op een groot scherm -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                <?php foreach ($vakken as $vak): ?>
                    <?php
                        // Ligt er een pakket in dit vakje?
                        $is_bezet = $vak['parcel_id'] !== null;

                        // En ligt dat pakket er al te lang?
                        $is_te_laat = $is_bezet && is_overdue('arrived', $vak['pickup_deadline']);
                    ?>

                    <?php if ($is_bezet): ?>
                        <!-- Een bezet vakje. Blauw, of oranje als het pakket te lang ligt. -->
                        <div class="p-4 rounded-xl border flex flex-col justify-between gap-3 shadow-sm <?= $is_te_laat ? 'border-amber-400 bg-amber-50' : 'border-sky-300 bg-sky-50'; ?>">
                            <!-- De vakcode en een labeltje -->
                            <div class="flex justify-between items-center gap-1">
                                <span class="font-extrabold text-sm text-slate-800">Vak <?= h($vak['slot_code']); ?></span>
                                <?php if ($is_te_laat): ?>
                                    <span class="badge bg-amber-500 text-white text-[10px]">VERLOPEN</span>
                                <?php else: ?>
                                    <span class="badge bg-brand-navy text-white text-[10px]">BEZET</span>
                                <?php endif; ?>
                            </div>

                            <!-- Van wie is het pakket, wat is de afhaalcode en welke vervoerder? -->
                            <div class="space-y-0.5 text-xs">
                                <div class="font-bold text-slate-800 truncate" title="<?= h($vak['customer_name']); ?>"><?= h($vak['customer_name']); ?></div>
                                <div class="code text-[11px]">Code: <?= h($vak['pickup_code']); ?></div>
                                <div class="text-[11px] text-slate-500 truncate"><?= h($vak['carrier_name']); ?></div>
                            </div>

                            <!-- Meteen uitgeven -->
                            <a href="/employee/verify_pickup.php?id=<?= (int) $vak['parcel_id']; ?>" class="btn btn-success btn-sm w-full">Uitgeven</a>
                        </div>

                    <?php else: ?>
                        <!-- Een vrij vakje, in het groen -->
                        <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/40 flex flex-col justify-between gap-3">
                            <!-- De vakcode en een labeltje -->
                            <div class="flex justify-between items-center gap-1">
                                <span class="font-extrabold text-sm text-slate-700">Vak <?= h($vak['slot_code']); ?></span>
                                <span class="badge bg-emerald-600 text-white text-[10px]">VRIJ</span>
                            </div>

                            <p class="text-xs text-slate-400">Dit vak is leeg en klaar voor gebruik.</p>

                            <!-- Een nieuw pakket in dit vakje leggen. Het vakje staat dan al klaar in het formulier. -->
                            <a href="/employee/register_parcel.php?slot_id=<?= (int) $vak['id']; ?>" class="btn btn-secondary btn-sm w-full">+ Gebruik Vak</a>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

</div>

<?php
// En de onderkant van de pagina.
require_once __DIR__ . '/../../includes/footer.php';
?>
