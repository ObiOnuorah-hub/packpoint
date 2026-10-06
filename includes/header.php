<?php
// ==============================================================================
// HEADER (includes/header.php)
// ==============================================================================
// De bovenkant van elke pagina: <head>, de stijlen, het menu en de meldingen.
//
// Gebruik op een pagina:
//     $pagina_titel = 'Mijn Pakketten';             (optioneel: tekst in het browsertabblad)
//     require_once __DIR__ . '/../../includes/header.php';
//
// BELANGRIJK: het menu wordt ALLEEN getoond als iemand is ingelogd.
// Op de loginpagina zie je dus geen menu en geen extra knoppen.

// Wie is er ingelogd? (null als niemand)
$ingelogde_gebruiker = current_user();

// Op welke pagina zijn we nu? (bijv. /employee/dashboard.php) -> om de actieve menuknop te kleuren
$huidige_pagina = $_SERVER['SCRIPT_NAME'];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <!-- Tekenset en schaal voor mobiel -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Titel in het browsertabblad -->
    <title><?= h($pagina_titel ?? 'Pakketbeheer'); ?> | PackPoint</title>

    <!-- Lettertype: Plus Jakarta Sans (modern en goed leesbaar) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS: hiermee stylen we met korte classnamen zoals 'p-4' en 'rounded-lg' -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // De PackPoint huisstijl: deze kleuren gebruik je als bijv. 'bg-brand-navy' of 'text-brand-sky'
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            navy: '#0C4A6E',  // donkerblauw: hoofdkleur
                            sky: '#38BDF8',   // lichtblauw: accentkleur
                            amber: '#FBBF24', // geel: waarschuwing
                            bg: '#F8FAFC',    // lichte achtergrond
                        },
                    },
                },
            },
        };
    </script>

    <!-- Onze eigen herbruikbare stijlen. Zo ziet elke knop, tabel en kaart er hetzelfde uit. -->
    <style type="text/tailwindcss">
        @layer components {
            /* --- Kaarten (witte blokken) --- */
            .card        { @apply bg-white rounded-2xl border border-slate-200 shadow-sm; }
            .card-header { @apply px-6 py-4 border-b border-slate-100 bg-slate-50 rounded-t-2xl flex flex-wrap justify-between items-center gap-2; }
            .card-title  { @apply text-xs font-bold text-slate-700 uppercase tracking-wider; }

            /* --- Paginatitels --- */
            .page-title    { @apply text-xl font-bold text-brand-navy; }
            .page-subtitle { @apply text-sm text-slate-500 mt-1; }

            /* --- Knoppen: gebruik altijd 'btn' + een kleur, bijv. class="btn btn-primary" --- */
            .btn           { @apply inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-brand-sky; }
            .btn-sm        { @apply px-3 py-1.5 text-xs; }
            .btn-primary   { @apply bg-brand-navy text-white hover:bg-sky-900; }
            .btn-success   { @apply bg-emerald-600 text-white hover:bg-emerald-700; }
            .btn-danger    { @apply bg-rose-600 text-white hover:bg-rose-700; }
            .btn-warning   { @apply bg-amber-500 text-white hover:bg-amber-600; }
            .btn-secondary { @apply bg-white text-slate-700 border border-slate-300 hover:bg-slate-50; }

            /* --- Formuliervelden --- */
            .label { @apply block text-xs font-bold text-slate-700 mb-1; }
            .input { @apply w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-sky focus:border-brand-sky; }

            /* --- Tabellen: zet class="data-table" op een <table> --- */
            .data-table          { @apply min-w-full divide-y divide-slate-200 text-sm; }
            .data-table thead    { @apply bg-slate-50; }
            .data-table th       { @apply px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 whitespace-nowrap; }
            .data-table th.text-right { text-align: right; } /* kolomkop rechts uitlijnen (bijv. 'Actie') */
            .data-table td       { @apply px-4 py-3 align-middle; }
            .data-table tbody    { @apply divide-y divide-slate-100; }
            .data-table tbody tr { @apply hover:bg-slate-50 transition; }

            /* --- Labeltjes en codes --- */
            .badge { @apply inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-bold whitespace-nowrap; }
            .code  { @apply font-mono font-bold text-brand-navy; }

            /* --- Meldingen (groen, rood, geel) --- */
            .alert         { @apply mb-4 p-4 rounded-xl border-l-4 text-sm font-medium shadow-sm; }
            .alert-success { @apply bg-emerald-50 border-emerald-500 text-emerald-800; }
            .alert-error   { @apply bg-rose-50 border-rose-500 text-rose-800; }
            .alert-warning { @apply bg-amber-50 border-amber-500 text-amber-900; }

            /* --- Menuknoppen in de bovenbalk --- */
            .nav-link        { @apply block px-3 py-2 rounded-md text-sm font-medium text-slate-200 hover:bg-white/10 hover:text-white transition; }
            .nav-link-active { @apply bg-white/15 text-white; }
            .nav-link-cta    { @apply bg-brand-sky text-brand-navy font-semibold hover:bg-sky-300 hover:text-brand-navy; }
            .nav-link-admin  { @apply bg-amber-400 text-amber-950 font-bold hover:bg-amber-300 hover:text-amber-950; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-brand-bg font-sans text-slate-800 antialiased">

<?php if ($ingelogde_gebruiker): ?>
    <!-- ================================================================== -->
    <!-- BOVENBALK MET MENU (alleen voor ingelogde gebruikers)              -->
    <!-- ================================================================== -->
    <header class="bg-brand-navy text-white shadow-md sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- flex-wrap: zo valt het menu op een nieuwe regel onder het logo -->
            <div class="flex flex-wrap items-center justify-between gap-x-4 py-3">

                <!-- Logo: klik erop om naar je eigen dashboard te gaan -->
                <a href="<?= dashboard_url($ingelogde_gebruiker['role']); ?>" class="flex items-center gap-2 font-extrabold text-xl tracking-tight">
                    <span class="p-2 bg-brand-sky text-brand-navy rounded-lg shadow-sm">
                        <!-- Pakket icoontje -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </span>
                    PackPoint
                </a>

                <!-- Rechterkant: naam, rol, uitlogknop en (op mobiel) de menuknop -->
                <div class="flex items-center gap-3">
                    <!-- Naam en rol van de ingelogde gebruiker (verborgen op kleine schermen) -->
                    <div class="text-right hidden sm:block leading-tight">
                        <div class="text-sm font-semibold"><?= h($ingelogde_gebruiker['name']); ?></div>
                        <div class="text-xs text-brand-sky font-medium"><?= h(role_label($ingelogde_gebruiker['role'])); ?></div>
                    </div>

                    <!-- Uitloggen -->
                    <a href="/logout.php" class="text-xs bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-md border border-white/20 transition">
                        Uitloggen
                    </a>

                    <!-- Hamburger-knop: alleen zichtbaar op kleine schermen -->
                    <button type="button" onclick="toggleMenu()" class="xl:hidden p-2 rounded-md hover:bg-white/10" aria-label="Menu openen of sluiten">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>

                <!-- Het menu (op een eigen regel onder het logo). -->
                <!-- Welke knoppen je ziet hangt af van je rol (zie menu_items() in auth.php). -->
                <!-- Op mobiel: verborgen tot je op de hamburger klikt. Op grote schermen: altijd zichtbaar. -->
                <nav id="hoofdmenu" class="w-full flex hidden xl:flex flex-col xl:flex-row gap-1 mt-3 pt-3 border-t border-white/10">
                    <?php foreach (menu_items($ingelogde_gebruiker['role']) as $url => $tekst): ?>
                        <?php
                            // Kies de stijl van de knop: speciale kleur voor admin en registreren
                            $klasse = 'nav-link';
                            if ($url === '/admin/dashboard.php') {
                                $klasse .= ' nav-link-admin';
                            } elseif ($url === '/employee/register_parcel.php') {
                                $klasse .= ' nav-link-cta';
                            }

                            // Is dit de pagina waar we nu zijn? Dan laten we dat zien
                            if ($url === $huidige_pagina) {
                                $klasse .= ' nav-link-active';
                            }
                        ?>
                        <a href="<?= $url; ?>" class="<?= $klasse; ?>"><?= h($tekst); ?></a>
                    <?php endforeach; ?>
                </nav>

            </div>
        </div>
    </header>

    <script>
        // Klapt het menu open of dicht op mobiel (door de class 'hidden' aan/uit te zetten)
        function toggleMenu() {
            document.getElementById('hoofdmenu').classList.toggle('hidden');
        }
    </script>
<?php endif; ?>

<!-- ================================================================== -->
<!-- HOOFDINHOUD                                                        -->
<!-- Ingelogd: brede pagina. Niet ingelogd (login/registreren): smal en  -->
<!-- in het midden van het scherm.                                      -->
<!-- ================================================================== -->
<main class="<?= $ingelogde_gebruiker
    ? 'flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6'
    : 'flex-grow w-full max-w-md mx-auto px-4 py-10 flex flex-col justify-center'; ?>">

    <!-- Meldingen (bijv. "Pakket opgeslagen!") die door set_flash() zijn klaargezet -->
    <?php foreach (get_flashes() as $melding): ?>
        <div class="alert alert-<?= h($melding['type']); ?>" role="alert">
            <?= h($melding['message']); ?>
        </div>
    <?php endforeach; ?>
