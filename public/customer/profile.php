<?php
// customer/profile.php
//
// Hier past de ingelogde gebruiker zijn naam, telefoonnummer en wachtwoord aan.
// Je kunt alleen je eigen gegevens wijzigen, want we werken altijd met het nummer
// uit je sessie en nooit met iets uit het formulier.

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../../includes/init.php';

// Je moet ingelogd zijn om je profiel te zien.
require_login();

// Wie is er ingelogd?
$gebruiker = current_user();

// Is er net een formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Wat is er ingevuld?
    $naam = trim($_POST['name'] ?? '');
    $telefoon = trim($_POST['phone'] ?? '');
    $nieuw_wachtwoord = $_POST['new_password'] ?? '';
    $herhaling = $_POST['new_password_confirm'] ?? '';

    // Naam en telefoon nakijken. Het e-mailadres kan niet worden aangepast.
    $fout = validate_contact_details($naam, $gebruiker['email'], $telefoon);

    // Is er een nieuw wachtwoord ingevuld? Dan kijken we of het goed genoeg is.
    if ($fout === null && $nieuw_wachtwoord !== '') {
        $fout = validate_new_password($nieuw_wachtwoord, $herhaling);
    }

    if ($fout !== null) {
        // Er klopt iets niet, dus we laten de melding zien.
        set_flash('error', $fout);
    } else {
        // Eerst de naam en het telefoonnummer opslaan.
        update_user_profile($gebruiker['id'], $naam, $telefoon);
        $bericht = 'Profielgegevens succesvol bijgewerkt!';

        // En het nieuwe wachtwoord, maar alleen als je er een hebt ingevuld.
        if ($nieuw_wachtwoord !== '') {
            update_user_password($gebruiker['id'], $nieuw_wachtwoord);
            $bericht = 'Je profiel en wachtwoord zijn succesvol bijgewerkt!';
        }

        // De pagina opnieuw laden, met een melding dat het gelukt is.
        redirect_with_message('/customer/profile.php', 'success', $bericht);
    }
}

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Mijn Profiel';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Het witte blok met het profiel -->
<div class="max-w-xl mx-auto card p-8">
    <h1 class="page-title text-2xl mb-6">Mijn Profiel</h1>

    <form action="/customer/profile.php" method="POST" class="space-y-4">
        <!-- Een verborgen geheime code die de site beschermt tegen nepformulieren (CSRF) -->
        <?= csrf_field(); ?>

        <!-- Naam. Ging er iets mis? Dan laten we zien wat je net had getypt. -->
        <div>
            <label for="name" class="label">Volledige naam *</label>
            <input type="text" id="name" name="name" maxlength="100" required class="input"
                   value="<?= h($_POST['name'] ?? $gebruiker['name']); ?>">
        </div>

        <!-- E-mailadres. Je kunt het alleen lezen, want je pakketten hangen eraan vast. -->
        <div>
            <label for="email" class="label">E-mailadres (vast)</label>
            <input type="email" id="email" disabled class="input bg-slate-100 text-slate-500 cursor-not-allowed"
                   value="<?= h($gebruiker['email']); ?>">
            <p class="text-xs text-slate-400 mt-1">Je pakketten zijn aan dit adres gekoppeld. Wil je het wijzigen? Vraag het aan de balie.</p>
        </div>

        <!-- Telefoonnummer -->
        <div>
            <label for="phone" class="label">Telefoonnummer</label>
            <input type="tel" id="phone" name="phone" maxlength="20" class="input" placeholder="06-12345678"
                   value="<?= h($_POST['phone'] ?? $gebruiker['phone']); ?>">
        </div>

        <!-- Een nieuw wachtwoord kiezen mag, maar hoeft niet -->
        <fieldset class="pt-4 border-t border-slate-100 space-y-4">
            <legend class="text-xs font-bold text-slate-500 uppercase tracking-wider pt-4">Wachtwoord wijzigen (optioneel)</legend>

            <!-- Het nieuwe wachtwoord -->
            <div>
                <label for="new_password" class="label">Nieuw wachtwoord</label>
                <input type="password" id="new_password" name="new_password" class="input" autocomplete="new-password"
                       placeholder="Laat leeg als je je wachtwoord niet wilt wijzigen">
            </div>

            <!-- Nog een keer, zodat een typfout meteen opvalt -->
            <div>
                <label for="new_password_confirm" class="label">Herhaal nieuw wachtwoord</label>
                <input type="password" id="new_password_confirm" name="new_password_confirm" class="input" autocomplete="new-password">
            </div>
        </fieldset>

        <!-- De knop om op te slaan -->
        <button type="submit" class="btn btn-primary w-full py-3">Wijzigingen Opslaan</button>
    </form>
</div>

<?php
// En de onderkant van de pagina.
require_once __DIR__ . '/../../includes/footer.php';
?>
