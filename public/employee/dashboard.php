<?php
// ==============================================================================
// BALIE DASHBOARD (public/employee/dashboard.php)
// ==============================================================================
// Het hoofdscherm van de baliemedewerker.
// Zoek snel een pakket op code, klant, vak en status.
// Klik door om een verwacht pakket te ontvangen, of een binnengekomen pakket uit te geven.

// Laad alles wat we nodig hebben
require_once __DIR__ . '/../../includes/init.php';

// Alleen medewerkers en admins mogen hier komen
require_role(['employee', 'admin']);

// De keuzes in het statusfilter: 'active' en 'all' + alle losse statussen
$status_keuzes = ['active' => 'Actief (verwacht + binnen)'] + PARCEL_STATUSES + ['all' => 'Alle statussen'];

// Lees de zoekterm uit de adresbalk (bijv. ?q=PK-7X9B), maximaal 100 tekens
$zoekterm = mb_substr(trim($_GET['q'] ?? ''), 0, 100);

// Lees het gekozen statusfilter. Onbekende waarde? Dan gewoon 'active'.
$status = $_GET['status'] ?? 'active';
if (!array_key_exists($status, $status_keuzes)) {
    $status = 'active';
}

// Zoek de pakketten
$pakketten = search_parcels($zoekterm, $status);

// Titel en bovenkant van de pagina
$pagina_titel = 'Balie Snelzoeken';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">

    <!-- Titel met knop om een nieuw pakket te registreren -->
    <div class="card p-6 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="page-title">Balie Snelzoeken &amp; Overzicht</h1>
            <p class="page-subtitle">Pakketontvangst, opslag &amp; uitgifte</p>
        </div>
        <a href="/employee/register_parcel.php" class="btn btn-primary">+ Nieuw Pakket Registreren</a>
    </div>

    <!-- Zoekbalk met statusfilter -->
    <form method="GET" action="/employee/dashboard.php" class="card p-4 grid grid-cols-1 md:grid-cols-[1fr_16rem_auto] gap-3 md:items-end">
        <!-- Zoekveld: we laten de zoekterm weer zien (veilig gemaakt met h()) -->
        <div>
            <label for="q" class="label">Zoek op code, klant of vak</label>
            <input type="search" id="q" name="q" value="<?= h($zoekterm); ?>" maxlength="100" class="input" autofocus
                   placeholder="Bijv. PK-7X9B, Jan de Vries, A-01 of een barcode">
        </div>

        <!-- Statusfilter -->
        <div>
            <label for="status" class="label">Status</label>
            <select id="status" name="status" class="input">
                <?php foreach ($status_keuzes as $waarde => $tekst): ?>
                    <option value="<?= $waarde; ?>" <?= $waarde === $status ? 'selected' : ''; ?>><?= h($tekst); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Knoppen -->
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Zoeken</button>
            <?php if ($zoekterm !== '' || $status !== 'active'): ?>
                <!-- Wis-knop (alleen als er gezocht of gefilterd is) -->
                <a href="/employee/dashboard.php" class="btn btn-secondary">Wissen</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabel met gevonden pakketten -->
    <section class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Gevonden Pakketten (Totaal: <?= count($pakketten); ?>)</h2>
        </div>

        <?php if (empty($pakketten)): ?>
            <!-- Niks gevonden -->
            <p class="p-8 text-center text-slate-400 text-sm">Geen pakketten gevonden. Probeer een andere zoekterm of status.</p>
        <?php else: ?>
            <!-- overflow-x-auto: op mobiel kun je de tabel opzij schuiven -->
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
                                <!-- Afhaalcode -->
                                <td class="code"><?= h($pakket['pickup_code']); ?></td>

                                <!-- Opslagvak (een verwacht pakket heeft nog geen vak) -->
                                <td>
                                    <?php if ($pakket['slot_code'] && $pakket['status'] === 'arrived'): ?>
                                        <span class="badge bg-slate-800 text-white">Vak <?= h($pakket['slot_code']); ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Klant: naam en e-mail -->
                                <td>
                                    <div class="font-semibold"><?= h($pakket['customer_name']); ?></div>
                                    <div class="text-xs text-slate-400"><?= h($pakket['customer_email']); ?></div>
                                </td>

                                <!-- Vervoerder en barcode -->
                                <td>
                                    <div class="font-semibold text-slate-700"><?= h($pakket['carrier_name']); ?></div>
                                    <div class="text-xs font-mono text-slate-400"><?= h($pakket['tracking_code']); ?></div>
                                </td>

                                <!-- Status (kleur + tekst) -->
                                <td><?= status_badge($pakket['status'], $pakket['pickup_deadline']); ?></td>

                                <!-- Knoppen: welke je ziet hangt af van de status -->
                                <td class="text-right whitespace-nowrap">
                                    <?php if ($pakket['status'] === 'expected'): ?>
                                        <!-- Verwacht pakket: ontvangst registreren -->
                                        <a href="/employee/edit_parcel.php?id=<?= (int) $pakket['id']; ?>" class="btn btn-primary btn-sm">Ontvangen</a>
                                    <?php elseif ($pakket['status'] === 'arrived'): ?>
                                        <!-- Binnengekomen pakket: beheren of uitgeven -->
                                        <a href="/employee/edit_parcel.php?id=<?= (int) $pakket['id']; ?>" class="btn btn-secondary btn-sm">Beheren</a>
                                        <a href="/employee/verify_pickup.php?id=<?= (int) $pakket['id']; ?>" class="btn btn-success btn-sm">Uitgeven</a>
                                    <?php else: ?>
                                        <!-- Al afgehandeld: alleen de datum laten zien -->
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
// Onderkant van de pagina
require_once __DIR__ . '/../../includes/footer.php';
?>
