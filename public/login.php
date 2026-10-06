<?php
// login.php
//
// Hier loggen klanten, baliemedewerkers en beheerders in. Na het inloggen sturen we
// je naar het startscherm van jouw rol:
//   klant            ->  /customer/dashboard.php
//   baliemedewerker  ->  /employee/dashboard.php
//   admin            ->  /admin/dashboard.php
//
// Op deze pagina zie je bewust geen menu. Dat krijg je pas als je bent ingelogd.

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../includes/init.php';

// Ben je al ingelogd? Dan heb je hier niks te zoeken en gaan we meteen naar je startscherm.
if (is_logged_in()) {
    redirect_to_dashboard();
}

// Is er net een formulier verstuurd? Dan gaan we kijken of het inloggen lukt.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Wat heeft iemand ingevuld? Spaties voor en na de naam halen we weg.
    $gebruikersnaam = trim($_POST['username'] ?? '');
    $wachtwoord = $_POST['password'] ?? '';

    // Eerst kijken of beide velden zijn ingevuld.
    if ($gebruikersnaam === '' || $wachtwoord === '') {
        set_flash('error', 'Vul je gebruikersnaam en wachtwoord in.');
    }
    // Dan proberen in te loggen.
    elseif (login($gebruikersnaam, $wachtwoord)) {
        // Gelukt! We kiezen een welkomstwoord dat bij de rol past.
        $welkom = match (current_user()['role']) {
            'customer' => 'Welkom terug!',
            'admin'    => 'Welkom Beheerder!',
            default    => 'Welkom bij de balie!',
        };
        set_flash('success', $welkom);

        // En dan naar het juiste startscherm.
        redirect_to_dashboard();
    }
    // Mislukt. We zeggen bewust niet of de naam of het wachtwoord fout was, anders
    // kan een hacker zo uitproberen welke gebruikersnamen bestaan.
    else {
        set_flash('error', 'Onjuiste gebruikersnaam of wachtwoord. Probeer het opnieuw.');
    }
}

// De testaccounts uit de opdracht, zodat je snel kunt inloggen tijdens het testen.
// Staat de site straks echt online? Haal dit blokje dan weg.
$testaccounts = [
    ['rol' => 'Klant',           'naam' => 'klant01', 'wachtwoord' => 'klant123'],
    ['rol' => 'Balie',           'naam' => 'balie01', 'wachtwoord' => 'balie123'],
    ['rol' => 'Admin',           'naam' => 'admin01', 'wachtwoord' => 'admin123'],
];

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Inloggen';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Het witte blok met het inlogformulier -->
<div class="card p-8">

    <!-- Het logo en de titel -->
    <div class="text-center mb-8">
        <!-- Een pakket-icoontje in een blauw vierkantje -->
        <div class="inline-flex p-3 bg-brand-navy text-brand-sky rounded-2xl shadow-md mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <h1 class="text-2xl font-extrabold text-brand-navy">Inloggen bij PackPoint</h1>
        <p class="text-sm text-slate-500 mt-1">Log in om verder te gaan</p>
    </div>

    <!-- Het formulier zelf -->
    <form action="/login.php" method="POST" class="space-y-4">
        <!-- Een verborgen geheime code die de site beschermt tegen nepformulieren (CSRF) -->
        <?= csrf_field(); ?>

        <!-- Gebruikersnaam. Ging er iets mis? Dan staat wat je typte er nog. -->
        <div>
            <label for="username" class="label">Gebruikersnaam</label>
            <input type="text" id="username" name="username" value="<?= old('username'); ?>"
                   class="input" placeholder="bijv. klant01" autocomplete="username" required autofocus>
        </div>

        <!-- Wachtwoord. Dit veld laten we nooit opnieuw zien. -->
        <div>
            <label for="password" class="label">Wachtwoord</label>
            <input type="password" id="password" name="password"
                   class="input" placeholder="Je wachtwoord" autocomplete="current-password" required>
        </div>

        <!-- De inlogknop. Dit is de enige op de pagina. -->
        <button type="submit" class="btn btn-primary w-full py-3">
            Inloggen
        </button>
    </form>

    <!-- De testaccounts. Klik op een knop en het formulier vult zichzelf in. -->
    <div class="mt-8 pt-6 border-t border-slate-100">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 text-center">Testaccounts</p>

        <!-- Drie knoppen naast elkaar -->
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

    <!-- Een link voor wie nog geen account heeft -->
    <p class="mt-6 text-center text-sm text-slate-500">
        Nog geen account?
        <a href="/register.php" class="font-bold text-brand-navy hover:underline">Registreer als klant</a>
    </p>
</div>

<script>
    // Als je op een testaccount klikt, zet dit de gebruikersnaam en het wachtwoord in het formulier.
    function fillTestAccount(gebruikersnaam, wachtwoord) {
        document.getElementById('username').value = gebruikersnaam;
        document.getElementById('password').value = wachtwoord;
    }
</script>

<?php
// En de onderkant van de pagina.
require_once __DIR__ . '/../includes/footer.php';
?>
