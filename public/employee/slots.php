<?php
// ==============================================================================
// OPSLAGVAKKEN OVERZICHT (public/employee/slots.php)
// ==============================================================================
// Een raster met alle stellingen en vakken.
// Je ziet meteen welk vak vrij is, welk bezet is en waar een pakket te lang ligt.

// Laad alles wat we nodig hebben
require_once __DIR__ . '/../../includes/init.php';

// Alleen medewerkers en admins
require_role(['employee', 'admin']);

// Haal alle vakken op (met het pakket dat erin ligt) en groepeer ze per stelling
$stellingen = group_slots_by_rack(get_slots_with_parcels());

// Titel en bovenkant van de pagina
$pagina_titel = 'Opslagvakken';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">

    <!-- Titel met legenda (wat betekenen de kleuren?) -->
    <div class="card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="page-title">Opslagvakken (Vakkenraster)</h1>
            <p class="page-subtitle">Overzicht van alle stellingen en de bezetting.</p>
        </div>

        <!-- Legenda -->
        <div class="flex flex-wrap items-center gap-4 text-xs font-semibold">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-emerald-500 rounded-full"></span> Vrij</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-brand-navy rounded-full"></span> Bezet</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-amber-500 rounded-full"></span> Te lang aanwezig</span>
        </div>
    </div>

    <!-- Nog geen vakken? -->
    <?php if (empty($stellingen)): ?>
        <p class="card p-8 text-center text-slate-500 text-sm">Er zijn nog geen opslagvakken. Een beheerder kan ze toevoegen via Vakken Beheer.</p>
    <?php endif; ?>

    <!-- Loop door elke stelling (Stelling A, Stelling B, ...) -->
    <?php foreach ($stellingen as $stelling => $vakken): ?>
        <section class="card p-6 space-y-4">

            <!-- Naam van de stelling en hoeveel vakken erin zitten -->
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-2">
                <?= h($stelling); ?> (<?= count($vakken); ?> vakken)
            </h2>

            <!-- Raster: 2 vakken per rij op mobiel, 5 op grote schermen -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                <?php foreach ($vakken as $vak): ?>
                    <?php
                        // Ligt er een pakket in dit vak?
                        $is_bezet = $vak['parcel_id'] !== null;

                        // Ligt dat pakket er al te lang?
                        $is_te_laat = $is_bezet && is_overdue('arrived', $vak['pickup_deadline']);
                    ?>

                    <?php if ($is_bezet): ?>
                        <!-- BEZET VAK: blauw (of oranje als het pakket te lang ligt) -->
                        <div class="p-4 rounded-xl border flex flex-col justify-between gap-3 shadow-sm <?= $is_te_laat ? 'border-amber-400 bg-amber-50' : 'border-sky-300 bg-sky-50'; ?>">
                            <!-- Vakcode + label -->
                            <div class="flex justify-between items-center gap-1">
                                <span class="font-extrabold text-sm text-slate-800">Vak <?= h($vak['slot_code']); ?></span>
                                <?php if ($is_te_laat): ?>
                                    <span class="badge bg-amber-500 text-white text-[10px]">VERLOPEN</span>
                                <?php else: ?>
                                    <span class="badge bg-brand-navy text-white text-[10px]">BEZET</span>
                                <?php endif; ?>
                            </div>

                            <!-- Klant, afhaalcode en vervoerder -->
                            <div class="space-y-0.5 text-xs">
                                <div class="font-bold text-slate-800 truncate" title="<?= h($vak['customer_name']); ?>"><?= h($vak['customer_name']); ?></div>
                                <div class="code text-[11px]">Code: <?= h($vak['pickup_code']); ?></div>
                                <div class="text-[11px] text-slate-500 truncate"><?= h($vak['carrier_name']); ?></div>
                            </div>

                            <!-- Direct uitgeven -->
                            <a href="/employee/verify_pickup.php?id=<?= (int) $vak['parcel_id']; ?>" class="btn btn-success btn-sm w-full">Uitgeven</a>
                        </div>

                    <?php else: ?>
                        <!-- VRIJ VAK: groen -->
                        <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/40 flex flex-col justify-between gap-3">
                            <!-- Vakcode + label -->
                            <div class="flex justify-between items-center gap-1">
                                <span class="font-extrabold text-sm text-slate-700">Vak <?= h($vak['slot_code']); ?></span>
                                <span class="badge bg-emerald-600 text-white text-[10px]">VRIJ</span>
                            </div>

                            <p class="text-xs text-slate-400">Dit vak is leeg en klaar voor gebruik.</p>

                            <!-- Nieuw pakket registreren in DIT vak (het vak staat dan al geselecteerd) -->
                            <a href="/employee/register_parcel.php?slot_id=<?= (int) $vak['id']; ?>" class="btn btn-secondary btn-sm w-full">+ Gebruik Vak</a>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

</div>

<?php
// Onderkant van de pagina
require_once __DIR__ . '/../../includes/footer.php';
?>
