<?php
// customer/dashboard.php
//
// Het startscherm van de klant. Hij ziet hier:
//   - zijn pakketten die nog onderweg zijn of klaarliggen, met een tijdlijn per pakket
//   - de afhaalcode en de uiterste afhaaldatum, zodra het pakket binnen is
//   - een geschiedenis van pakketten die al zijn opgehaald of teruggestuurd

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../../includes/init.php';

// Alleen klanten mogen hier komen. Iedereen anders wordt teruggestuurd.
require_role('customer');

// Wie is de ingelogde klant?
$klant = current_user();

// We halen alleen de pakketten van deze klant op, nooit die van een ander.
$alle_pakketten = get_customer_parcels($klant);

// En die verdelen we in twee lijstjes: wat nog loopt en wat al is afgehandeld.
$actieve_pakketten = [];
$geschiedenis = [];
foreach ($alle_pakketten as $pakket) {
    if (is_active_parcel($pakket)) {
        $actieve_pakketten[] = $pakket;
    } else {
        $geschiedenis[] = $pakket;
    }
}

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Mijn Pakketten';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">

    <!-- Het blauwe welkomstblok bovenaan -->
    <div class="bg-brand-navy text-white p-6 rounded-2xl shadow-sm">
        <h1 class="text-xl font-bold">Welkom, <?= h($klant['name']); ?>!</h1>
        <p class="text-sm text-brand-sky mt-1">Hieronder zie je je verwachte en binnengekomen pakketten, met je afhaalcodes.</p>
    </div>

    <!-- Eerst de pakketten die nog lopen: verwacht of binnengekomen -->
    <section class="space-y-4">
        <h2 class="text-base font-bold text-slate-800">Mijn Pakketten</h2>

        <?php if (empty($actieve_pakketten)): ?>
            <!-- Geen pakketten? Dan zeggen we dat vriendelijk, in plaats van een leeg scherm. -->
            <div class="card p-8 text-center text-slate-500 text-sm">
                Er zijn op dit moment geen pakketten voor jou onderweg of klaar om op te halen.
            </div>
        <?php else: ?>
            <!-- De kaartjes naast elkaar: een kolom op een telefoon, twee op een groter scherm -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($actieve_pakketten as $pakket): ?>
                    <!-- Een kaartje voor een pakket -->
                    <article class="card p-5 space-y-4">

                        <!-- Bovenaan het kaartje: de vervoerder en de status -->
                        <div class="flex justify-between items-center gap-2 border-b border-slate-100 pb-3">
                            <span class="font-bold text-slate-700 text-sm uppercase"><?= h($pakket['carrier_name']); ?></span>
                            <?= status_badge($pakket['status'], $pakket['pickup_deadline']); ?>
                        </div>

                        <!-- De tijdlijn: Aangemeld, Binnengekomen en Opgehaald.
                             Met flex-wrap lopen de stappen op een smalle telefoon door naar de volgende regel. -->
                        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                            <?php foreach (parcel_timeline($pakket) as $nummer => $stap): ?>
                                <!-- Een streepje tussen de stappen. Niet voor de eerste stap en niet op een telefoon. -->
                                <?php if ($nummer > 0): ?>
                                    <li class="hidden sm:block flex-1 h-0.5 <?= $stap['klaar'] ? 'bg-emerald-500' : 'bg-slate-200'; ?>" aria-hidden="true"></li>
                                <?php endif; ?>

                                <!-- De stap zelf. Is hij al gebeurd? Dan een groen bolletje met een vinkje, anders grijs. -->
                                <li class="flex items-center gap-1.5 font-semibold <?= $stap['klaar'] ? 'text-emerald-700' : 'text-slate-400'; ?>">
                                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-white text-[10px] <?= $stap['klaar'] ? 'bg-emerald-500' : 'bg-slate-300'; ?>">
                                        <?= $stap['klaar'] ? '✓' : $nummer + 1; ?>
                                    </span>
                                    <?= h($stap['tekst']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>

                        <?php if ($pakket['status'] === 'arrived'): ?>
                            <!-- Het pakket is binnen: de afhaalcode laten we groot zien, om aan de balie te tonen. -->
                            <div class="bg-sky-50 border border-sky-200 rounded-xl p-4 text-center">
                                <span class="text-[11px] font-bold text-slate-500 block uppercase tracking-wider">Jouw afhaalcode (toon aan de balie)</span>
                                <span class="code text-3xl tracking-widest mt-1 block"><?= h($pakket['pickup_code']); ?></span>
                            </div>
                        <?php else: ?>
                            <!-- Het pakket wordt alleen nog verwacht. Dan laten we de code nog niet zien. -->
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center text-sm text-slate-500">
                                📦 Je pakket is onderweg. Zodra het binnen is, zie je hier je afhaalcode.
                            </div>
                        <?php endif; ?>

                        <!-- De details van het pakket -->
                        <dl class="text-sm text-slate-600 space-y-1">
                            <div><dt class="inline font-semibold">Track &amp; Trace:</dt> <dd class="inline font-mono"><?= h($pakket['tracking_code']); ?></dd></div>
                            <?php if ($pakket['status'] === 'arrived'): ?>
                                <div><dt class="inline font-semibold">Binnengekomen:</dt> <dd class="inline"><?= format_date($pakket['received_at']); ?></dd></div>
                                <div><dt class="inline font-semibold">Uiterste afhaaldatum:</dt> <dd class="inline font-bold text-brand-navy"><?= format_date($pakket['pickup_deadline']); ?></dd></div>
                            <?php else: ?>
                                <div><dt class="inline font-semibold">Aangemeld op:</dt> <dd class="inline"><?= format_date($pakket['received_at']); ?></dd></div>
                            <?php endif; ?>
                        </dl>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Daaronder de geschiedenis: pakketten die al zijn opgehaald of teruggestuurd -->
    <section class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Geschiedenis</h2>
        </div>

        <?php if (empty($geschiedenis)): ?>
            <p class="p-6 text-center text-slate-400 text-sm">Je hebt nog geen opgehaalde pakketten.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vervoerder</th>
                            <th>Track &amp; Trace</th>
                            <th>Status</th>
                            <th>Afgehandeld op</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($geschiedenis as $pakket): ?>
                            <tr>
                                <td class="font-semibold"><?= h($pakket['carrier_name']); ?></td>
                                <td class="font-mono text-xs"><?= h($pakket['tracking_code']); ?></td>
                                <td><?= status_badge($pakket['status']); ?></td>
                                <td class="whitespace-nowrap"><?= format_date($pakket['picked_up_at']); ?></td>
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
