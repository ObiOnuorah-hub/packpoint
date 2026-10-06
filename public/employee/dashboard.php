<?php
// employee/dashboard.php
//
// Het hoofdscherm van de baliemedewerker. Hier zoek je snel een pakket, op code, klant,
// vak en status. Van hieruit ontvang je een verwacht pakket, of geef je een binnengekomen
// pakket mee aan de klant.

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../../includes/init.php';

// Alleen medewerkers en admins mogen hier komen.
require_role(['employee', 'admin']);

// De keuzes in het statusfilter: 'active' en 'all' plus alle losse statussen.
$status_keuzes = ['active' => 'Actief (verwacht + binnen)'] + PARCEL_STATUSES + ['all' => 'Alle statussen'];

// Wat is er in de zoekbalk getypt? Dat staat in de adresbalk, bijvoorbeeld ?q=PK-7X9B.
// We nemen maximaal 100 tekens, zodat niemand een gigantische tekst kan sturen.
$zoekterm = mb_substr(trim($_GET['q'] ?? ''), 0, 100);

// Welk statusfilter is gekozen? Is het iets wat we niet kennen? Dan nemen we gewoon 'active'.
$status = $_GET['status'] ?? 'active';
if (!array_key_exists($status, $status_keuzes)) {
    $status = 'active';
}

// En dan zoeken we de pakketten.
$pakketten = search_parcels($zoekterm, $status);

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Balie Snelzoeken';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">

    <!-- De titel, met een knop om meteen een nieuw pakket te registreren -->
    <div class="card p-6 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="page-title">Balie Snelzoeken &amp; Overzicht</h1>
            <p class="page-subtitle">Pakketontvangst, opslag &amp; uitgifte</p>
        </div>
        <a href="/employee/register_parcel.php" class="btn btn-primary">+ Nieuw Pakket Registreren</a>
    </div>

    <!-- De zoekbalk met het statusfilter -->
    <form method="GET" action="/employee/dashboard.php" class="card p-4 grid grid-cols-1 md:grid-cols-[1fr_16rem_auto] gap-3 md:items-end">
        <!-- Het zoekveld. Wat je net hebt gezocht blijft staan (met h() veilig gemaakt). -->
        <div>
            <label for="q" class="label">Zoek op code, klant of vak</label>
            <input type="search" id="q" name="q" value="<?= h($zoekterm); ?>" maxlength="100" class="input" autofocus
                   placeholder="Bijv. PK-7X9B, Jan de Vries, A-01 of een barcode">
        </div>

        <!-- Het statusfilter -->
        <div>
            <label for="status" class="label">Status</label>
            <select id="status" name="status" class="input">
                <?php foreach ($status_keuzes as $waarde => $tekst): ?>
                    <option value="<?= $waarde; ?>" <?= $waarde === $status ? 'selected' : ''; ?>><?= h($tekst); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- De knoppen -->
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Zoeken</button>
            <?php if ($zoekterm !== '' || $status !== 'active'): ?>
                <!-- De wis-knop. Die laten we alleen zien als er iets is gezocht of gefilterd. -->
                <a href="/employee/dashboard.php" class="btn btn-secondary">Wissen</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- De tabel met de gevonden pakketten -->
    <section class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Gevonden Pakketten (Totaal: <?= count($pakketten); ?>)</h2>
        </div>

        <?php if (empty($pakketten)): ?>
            <!-- Niks gevonden. Dan zeggen we dat, in plaats van een leeg scherm. -->
            <p class="p-8 text-center text-slate-400 text-sm">Geen pakketten gevonden. Probeer een andere zoekterm of status.</p>
        <?php else: ?>
            <!-- Op een telefoon is de tabel breder dan het scherm, dus je kunt hem opzij schuiven -->
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Afhaalcode</th>
                            <th>Opslagvak</th>
                            <th>Klant</th>
                            <th>Vervoerder</th>
                            <th>Status</th>
                            <th class="text-right">Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pakketten as $pakket): ?>
                            <tr>
                                <!-- De afhaalcode -->
                                <td class="code"><?= h($pakket['pickup_code']); ?></td>

                                <!-- Het vakje. Een pakket dat nog verwacht wordt heeft er nog geen. -->
                                <td>
                                    <?php if ($pakket['slot_code'] && $pakket['status'] === 'arrived'): ?>
                                        <span class="badge bg-slate-800 text-white">Vak <?= h($pakket['slot_code']); ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- De klant: naam en e-mailadres -->
                                <td>
                                    <div class="font-semibold"><?= h($pakket['customer_name']); ?></div>
                                    <div class="text-xs text-slate-400"><?= h($pakket['customer_email']); ?></div>
                                </td>

                                <!-- De vervoerder en de barcode -->
                                <td>
                                    <div class="font-semibold text-slate-700"><?= h($pakket['carrier_name']); ?></div>
                                    <div class="text-xs font-mono text-slate-400"><?= h($pakket['tracking_code']); ?></div>
                                </td>

                                <!-- De status, met kleur en tekst -->
                                <td><?= status_badge($pakket['status'], $pakket['pickup_deadline']); ?></td>

                                <!-- De knoppen. Welke je ziet hangt af van de status. -->
                                <td class="text-right whitespace-nowrap">
                                    <?php if ($pakket['status'] === 'expected'): ?>
                                        <!-- Het pakket wordt nog verwacht: je kunt de ontvangst registreren. -->
                                        <a href="/employee/edit_parcel.php?id=<?= (int) $pakket['id']; ?>" class="btn btn-primary btn-sm">Ontvangen</a>
                                    <?php elseif ($pakket['status'] === 'arrived'): ?>
                                        <!-- Het pakket ligt hier: je kunt het beheren of meegeven. -->
                                        <a href="/employee/edit_parcel.php?id=<?= (int) $pakket['id']; ?>" class="btn btn-secondary btn-sm">Beheren</a>
                                        <a href="/employee/verify_pickup.php?id=<?= (int) $pakket['id']; ?>" class="btn btn-success btn-sm">Uitgeven</a>
                                    <?php else: ?>
                                        <!-- Het pakket is al afgehandeld, dus we laten alleen de datum zien. -->
                                        <span class="text-xs text-slate-400">Afgehandeld <?= format_date($pakket['picked_up_at']); ?></span>
                                    <?php endif; ?>
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
