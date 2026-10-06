<?php
// ==============================================================================
// LOGINPAGINA (public/login.php)
// ==============================================================================
// Hier logt iedereen in: klanten, baliemedewerkers en beheerders.
// Na het inloggen stuurt de website je naar het dashboard van jouw rol:
//   klant           -> /customer/dashboard.php
//   baliemedewerker -> /employee/dashboard.php
//   admin           -> /admin/dashboard.php
//
// Op deze pagina staat GEEN menu: dat zie je pas als je bent ingelogd.

// Laad alles wat we nodig hebben
require_once __DIR__ . '/../includes/init.php';

// Ben je al ingelogd? Dan hoef je hier niet te zijn: door naar je dashboard
if (is_logged_in()) {
    redirect_to_dashboard();
}

// Is het formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lees de ingevulde gegevens uit het formulier
    $gebruikersnaam = trim($_POST['username'] ?? '');
    $wachtwoord = $_POST['password'] ?? '';

    // Check of beide velden zijn ingevuld
    if ($gebruikersnaam === '' || $wachtwoord === '') {
        set_flash('error', 'Vul je gebruikersnaam en wachtwoord in.');
    }
    // Probeer in te loggen
    elseif (login($gebruikersnaam, $wachtwoord)) {
        // Gelukt! Kies een welkomstbericht dat past bij de rol
        $welkom = match (current_user()['role']) {
            'customer' => 'Welkom terug!',
            'admin'    => 'Welkom Beheerder!',
            default    => 'Welkom bij de balie!',
        };
        set_flash('success', $welkom);

        // Stuur de gebruiker naar het juiste dashboard
        redirect_to_dashboard();
    }
    // Inloggen mislukt. We zeggen NIET of de naam of het wachtwoord fout is
    // (anders weet een hacker welke gebruikersnamen bestaan).
    else {
        set_flash('error', 'Onjuiste gebruikersnaam of wachtwoord. Probeer het opnieuw.');
    }
}

// De testaccounts uit de opdracht (om snel in te loggen tijdens het testen)
// LET OP: haal dit blok weg als de website echt live gaat!
$testaccounts = [
    ['rol' => 'Klant',           'naam' => 'klant01', 'wachtwoord' => 'klant123'],
    ['rol' => 'Balie',           'naam' => 'balie01', 'wachtwoord' => 'balie123'],
    ['rol' => 'Admin',           'naam' => 'admin01', 'wachtwoord' => 'admin123'],
];

// Titel voor het browsertabblad en laad de bovenkant van de pagina
$pagina_titel = 'Inloggen';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ================================================================== -->
<!-- INLOGKAART                                                         -->
<!-- ================================================================== -->
<div class="card p-8">

    <!-- Logo en titel -->
    <div class="text-center mb-8">
        <!-- Pakket icoontje in een blauw vierkantje -->
        <div class="inline-flex p-3 bg-brand-navy text-brand-sky rounded-2xl shadow-md mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <h1 class="text-2xl font-extrabold text-brand-navy">Inloggen bij PackPoint</h1>
        <p class="text-sm text-slate-500 mt-1">Log in om verder te gaan</p>
    </div>

    <!-- Het inlogformulier -->
    <form action="/login.php" method="POST" class="space-y-4">
        <!-- Geheime CSRF-code (beveiliging) -->
        <?= csrf_field(); ?>

        <!-- Gebruikersnaam -->
        <div>
            <label for="username" class="label">Gebruikersnaam</label>
            <input type="text" id="username" name="username" value="<?= old('username'); ?>"
                   class="input" placeholder="bijv. klant01" autocomplete="username" required autofocus>
        </div>

        <!-- Wachtwoord -->
        <div>
            <label for="password" class="label">Wachtwoord</label>
            <input type="password" id="password" name="password"
                   class="input" placeholder="Je wachtwoord" autocomplete="current-password" required>
        </div>

        <!-- Inlogknop (de enige inlogknop op de pagina) -->
        <button type="submit" class="btn btn-primary w-full py-3">
            Inloggen
        </button>
    </form>

    <!-- Testaccounts: klik er een aan om de velden automatisch in te vullen -->
    <div class="mt-8 pt-6 border-t border-slate-100">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 text-center">Testaccounts</p>

        <!-- 3 knoppen naast elkaar -->
        <div class="grid grid-cols-3 gap-2">
            <?php foreach ($testaccounts as $account): ?>
                <button type="button"
                        aria-label="Vul testaccount <?= h($account['rol']); ?> in"
                        onclick="fillTestAccount('<?= h($account['naam']); ?>', '<?= h($account['wachtwoord']); ?>')"
                        class="p-2 rounded-lg border border-slate-200 bg-slate-50 hover:border-brand-sky hover:bg-sky-50 transition text-center">
                    <span class="block text-xs font-bold text-slate-800"><?= h($account['rol']); ?></span>
                    <span class="block text-[11px] text-slate-500 font-mono"><?= h($account['naam']); ?></span>
                    <span class="block text-[11px] text-slate-400 font-mono"><?= h($account['wachtwoord']); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Link naar registreren voor nieuwe klanten -->
    <p class="mt-6 text-center text-sm text-slate-500">
        Nog geen account?
        <a href="/register.php" class="font-bold text-brand-navy hover:underline">Registreer als klant</a>
    </p>
</div>

<script>
    // Vult de gebruikersnaam en het wachtwoord in als je op een testaccount klikt
    function fillTestAccount(gebruikersnaam, wachtwoord) {
        document.getElementById('username').value = gebruikersnaam;
        document.getElementById('password').value = wachtwoord;
    }
</script>

<?php
// Laad de onderkant van de pagina
require_once __DIR__ . '/../includes/footer.php';
?>
