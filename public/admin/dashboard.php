<?php
// admin/dashboard.php
//
// Het overzicht voor de beheerder: de cijfers van de winkel en snelle links naar het
// beheer. De admin kan ook alles wat de baliemedewerker kan, via het menu bovenaan.

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../../includes/init.php';

// Alleen admins mogen hier komen.
require_role('admin');

// Wie is de ingelogde admin?
$admin = current_user();

// De cijfers voor de blokken bovenaan. Elke regel vraagt de database om een aantal.
$statistieken = [
    'verwacht'     => count_parcels_by_status('expected'),
    'klaarliggend' => count_parcels_by_status('arrived'),
    'uitgegeven'   => count_parcels_by_status('picked_up'),
    'vrije_vakken' => count_slots_by_status('free'),
    'bezette_vakken' => count_slots_by_status('occupied'),
    'gebruikers'   => count_users(),
    'vervoerders'  => count_active_carriers(),
];

// De 5 nieuwste pakketten, voor de tabel onderaan.
$recente_pakketten = get_recent_parcels(5);

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Admin Dashboard';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">

    <!-- De welkomstbanner bovenaan -->
    <div class="bg-gradient-to-r from-brand-navy to-sky-800 text-white p-6 rounded-2xl shadow-sm flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div>
            <span class="badge bg-amber-400 text-amber-950 uppercase tracking-wider">👑 Beheerderspaneel</span>
            <h1 class="text-2xl font-bold mt-2">Welkom, <?= h($admin['name']); ?>!</h1>
            <p class="text-sm text-sky-200 mt-1">Het totale overzicht van de winkel, opslagvakken en accounts.</p>
        </div>
        <a href="/employee/dashboard.php" class="btn bg-brand-sky text-brand-navy hover:bg-sky-300">Naar Balie Snelzoeken</a>
    </div>

    <!-- De vier blokken met cijfers -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- De pakketten die klaarliggen -->
        <div class="card p-5">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">📦 Klaarliggend</span>
            <span class="text-3xl font-extrabold text-brand-navy mt-1 block"><?= $statistieken['klaarliggend']; ?></span>
            <span class="text-xs text-slate-400">(<?= $statistieken['verwacht']; ?> verwacht, <?= $statistieken['uitgegeven']; ?> al uitgegeven)</span>
        </div>

        <!-- De vrije vakjes -->
        <div class="card p-5">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">🗄️ Vrije vakken</span>
            <span class="text-3xl font-extrabold text-emerald-600 mt-1 block"><?= $statistieken['vrije_vakken']; ?></span>
            <span class="text-xs text-slate-400">(<?= $statistieken['bezette_vakken']; ?> bezet)</span>
        </div>

        <!-- Het aantal gebruikers -->
        <div class="card p-5">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">👥 Gebruikers</span>
            <span class="text-3xl font-extrabold text-sky-700 mt-1 block"><?= $statistieken['gebruikers']; ?></span>
            <span class="text-xs text-slate-400">Admins, balie &amp; klanten</span>
        </div>

        <!-- Het aantal vervoerders -->
        <div class="card p-5">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">🚚 Vervoerders</span>
            <span class="text-3xl font-extrabold text-amber-600 mt-1 block"><?= $statistieken['vervoerders']; ?></span>
            <span class="text-xs text-slate-400">PostNL, DHL, DPD enz.</span>
        </div>
    </div>

    <!-- Snelle links naar de beheerpagina's -->
    <section class="card p-6 space-y-4">
        <h2 class="card-title">Beheermodules &amp; Snelkoppelingen</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Gebruikersbeheer -->
            <a href="/admin/users.php" class="p-4 rounded-xl border border-slate-200 hover:border-brand-sky hover:bg-sky-50/50 transition">
                <span class="text-base font-bold text-brand-navy block">👥 Gebruikersbeheer</span>
                <span class="text-sm text-slate-500 mt-1 block">Maak accounts aan, wijzig rollen of verwijder accounts.</span>
                <span class="text-xs font-bold text-sky-600 mt-3 inline-block">Beheer gebruikers &rarr;</span>
            </a>

            <!-- Beheer van de opslagvakken -->
            <a href="/admin/slots_manage.php" class="p-4 rounded-xl border border-slate-200 hover:border-brand-sky hover:bg-sky-50/50 transition">
                <span class="text-base font-bold text-brand-navy block">🗄️ Opslagvakken Beheer</span>
                <span class="text-sm text-slate-500 mt-1 block">Voeg nieuwe stellingen en vakken toe.</span>
                <span class="text-xs font-bold text-sky-600 mt-3 inline-block">Beheer vakken &rarr;</span>
            </a>

            <!-- Beheer van de vervoerders -->
            <a href="/admin/carriers.php" class="p-4 rounded-xl border border-slate-200 hover:border-brand-sky hover:bg-sky-50/50 transition">
                <span class="text-base font-bold text-brand-navy block">🚚 Vervoerders Beheer</span>
                <span class="text-sm text-slate-500 mt-1 block">Voeg nieuwe koeriersdiensten toe.</span>
                <span class="text-xs font-bold text-sky-600 mt-3 inline-block">Beheer vervoerders &rarr;</span>
            </a>
        </div>
    </section>

    <!-- De tabel met de nieuwste pakketten -->
    <section class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Recent Binnengekomen Pakketten</h2>
            <a href="/employee/dashboard.php?status=all" class="text-xs text-brand-navy font-bold hover:underline">Bekijk alle pakketten &rarr;</a>
        </div>

        <?php if (empty($recente_pakketten)): ?>
            <p class="p-8 text-center text-slate-400 text-sm">Er zijn nog geen pakketten geregistreerd.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Afhaalcode</th>
                            <th>Opslagvak</th>
                            <th>Klant</th>
                            <th>Vervoerder</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recente_pakketten as $pakket): ?>
                            <tr>
                                <td class="code"><?= h($pakket['pickup_code']); ?></td>
                                <!-- Een pakket dat nog verwacht wordt heeft nog geen vakje -->
                                <td>
                                    <?php if ($pakket['slot_code']): ?>
                                        <span class="badge bg-slate-800 text-white">Vak <?= h($pakket['slot_code']); ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="font-semibold"><?= h($pakket['customer_name']); ?></td>
                                <td><?= h($pakket['carrier_name']); ?></td>
                                <td><?= status_badge($pakket['status'], $pakket['pickup_deadline']); ?></td>
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
