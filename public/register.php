<?php
// ==============================================================================
// KLANT REGISTREREN (public/register.php)
// ==============================================================================
// Nieuwe klanten kunnen hier zelf een account maken.
// Daarna zien ze hun pakketten en afhaalcodes op hun eigen dashboard.

// Laad alles wat we nodig hebben
require_once __DIR__ . '/../includes/init.php';

// Al ingelogd? Dan hoef je geen account te maken
if (is_logged_in()) {
    redirect_to_dashboard();
}

// Is het formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lees alle velden uit en haal spaties weg
    $naam = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $telefoon = trim($_POST['phone'] ?? '');
    $wachtwoord = $_POST['password'] ?? '';
    $herhaling = $_POST['password_confirm'] ?? '';

    // Controleer naam, e-mail en telefoon
    $fout = validate_contact_details($naam, $email, $telefoon);

    // Controleer het wachtwoord (alleen als de rest goed was)
    if ($fout === null) {
        $fout = validate_new_password($wachtwoord, $herhaling);
    }

    // Bestaat dit e-mailadres al?
    if ($fout === null && email_exists($email)) {
        $fout = 'Er bestaat al een account met dit e-mailadres.';
    }

    if ($fout !== null) {
        // Er is iets fout: laat de melding zien
        set_flash('error', $fout);
    } else {
        // Alles goed: maak het klantaccount aan (rol is altijd 'customer')
        create_user($naam, $email, $telefoon, $wachtwoord, 'customer');

        // Log de nieuwe klant direct in
        login($email, $wachtwoord);

        // Door naar het klantdashboard
        redirect_with_message('/customer/dashboard.php', 'success', 'Account succesvol aangemaakt! Welkom bij PackPoint.');
    }
}

// Titel en bovenkant van de pagina
$pagina_titel = 'Registreren';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Registratiekaart -->
<div class="card p-8">
    <!-- Titel -->
    <div class="text-center mb-6">
        <h1 class="text-2xl font-extrabold text-brand-navy">Klant Registreren</h1>
        <p class="text-sm text-slate-500 mt-1">Maak een account aan om altijd je afhaalcodes te bekijken.</p>
    </div>

    <!-- Registratieformulier -->
    <form action="/register.php" method="POST" class="space-y-4">
        <!-- Geheime CSRF-code (beveiliging) -->
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

        <!-- Telefoonnummer (niet verplicht) -->
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

        <!-- Wachtwoord nog een keer (om typfouten te voorkomen) -->
        <div>
            <label for="password_confirm" class="label">Bevestig wachtwoord *</label>
            <input type="password" id="password_confirm" name="password_confirm"
                   class="input" placeholder="Herhaal je wachtwoord" autocomplete="new-password" required>
        </div>

        <!-- Opslaan -->
        <button type="submit" class="btn btn-primary w-full py-3">Account Aanmaken</button>
    </form>

    <!-- Terug naar inloggen -->
    <p class="mt-6 pt-5 border-t border-slate-100 text-center text-sm text-slate-500">
        Al een account?
        <a href="/login.php" class="font-bold text-brand-navy hover:underline">Hier inloggen</a>
    </p>
</div>

<?php
// Onderkant van de pagina
require_once __DIR__ . '/../includes/footer.php';
?>
