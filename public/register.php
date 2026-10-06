<?php
// register.php
//
// Hier maakt een nieuwe klant zelf een account aan. Daarna ziet hij op zijn eigen
// startscherm zijn pakketten en afhaalcodes.

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../includes/init.php';

// Ben je al ingelogd? Dan heb je geen nieuw account nodig.
if (is_logged_in()) {
    redirect_to_dashboard();
}

// Is er net een formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Wat is er ingevuld? Spaties weggehaald en het e-mailadres in kleine letters.
    $naam = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $telefoon = trim($_POST['phone'] ?? '');
    $wachtwoord = $_POST['password'] ?? '';
    $herhaling = $_POST['password_confirm'] ?? '';

    // Eerst naam, e-mail en telefoon nakijken.
    $fout = validate_contact_details($naam, $email, $telefoon);

    // Daarna het wachtwoord, maar alleen als de rest klopte.
    if ($fout === null) {
        $fout = validate_new_password($wachtwoord, $herhaling);
    }

    // Heeft iemand al een account met dit e-mailadres?
    if ($fout === null && email_exists($email)) {
        $fout = 'Er bestaat al een account met dit e-mailadres.';
    }

    if ($fout !== null) {
        // Er klopt iets niet, dus we laten de melding zien.
        set_flash('error', $fout);
    } else {
        // Alles in orde. Het nieuwe account is altijd een klant.
        create_user($naam, $email, $telefoon, $wachtwoord, 'customer');

        // We loggen de nieuwe klant meteen in, dat scheelt hem een stap.
        login($email, $wachtwoord);

        // En door naar zijn startscherm.
        redirect_with_message('/customer/dashboard.php', 'success', 'Account succesvol aangemaakt! Welkom bij PackPoint.');
    }
}

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Registreren';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Het witte blok met het registratieformulier -->
<div class="card p-8">
    <!-- De titel -->
    <div class="text-center mb-6">
        <h1 class="text-2xl font-extrabold text-brand-navy">Klant Registreren</h1>
        <p class="text-sm text-slate-500 mt-1">Maak een account aan om altijd je afhaalcodes te bekijken.</p>
    </div>

    <!-- Het formulier -->
    <form action="/register.php" method="POST" class="space-y-4">
        <!-- Een verborgen geheime code die de site beschermt tegen nepformulieren (CSRF) -->
        <?= csrf_field(); ?>

        <!-- Naam -->
        <div>
            <label for="name" class="label">Volledige naam *</label>
            <input type="text" id="name" name="name" value="<?= old('name'); ?>" maxlength="100"
                   class="input" placeholder="bijv. Jan de Vries" required>
        </div>

        <!-- E-mailadres -->
        <div>
            <label for="email" class="label">E-mailadres *</label>
            <input type="email" id="email" name="email" value="<?= old('email'); ?>" maxlength="150"
                   class="input" placeholder="naam@voorbeeld.nl" required>
        </div>

        <!-- Telefoonnummer, dat hoeft niet -->
        <div>
            <label for="phone" class="label">Telefoonnummer</label>
            <input type="tel" id="phone" name="phone" value="<?= old('phone'); ?>" maxlength="20"
                   class="input" placeholder="06-12345678">
        </div>

        <!-- Wachtwoord -->
        <div>
            <label for="password" class="label">Wachtwoord *</label>
            <input type="password" id="password" name="password" minlength="<?= MIN_PASSWORD_LENGTH; ?>"
                   class="input" placeholder="Minimaal <?= MIN_PASSWORD_LENGTH; ?> tekens" autocomplete="new-password" required>
        </div>

        <!-- Het wachtwoord nog een keer, zodat een typfout meteen opvalt -->
        <div>
            <label for="password_confirm" class="label">Bevestig wachtwoord *</label>
            <input type="password" id="password_confirm" name="password_confirm"
                   class="input" placeholder="Herhaal je wachtwoord" autocomplete="new-password" required>
        </div>

        <!-- De knop om op te slaan -->
        <button type="submit" class="btn btn-primary w-full py-3">Account Aanmaken</button>
    </form>

    <!-- Een link voor wie al een account heeft -->
    <p class="mt-6 pt-5 border-t border-slate-100 text-center text-sm text-slate-500">
        Al een account?
        <a href="/login.php" class="font-bold text-brand-navy hover:underline">Hier inloggen</a>
    </p>
</div>

<?php
// En de onderkant van de pagina.
require_once __DIR__ . '/../includes/footer.php';
?>
