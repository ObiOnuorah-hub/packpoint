<?php
// ==============================================================================
// MIJN PROFIEL (public/customer/profile.php)
// ==============================================================================
// Hier kan de ingelogde gebruiker zijn naam, telefoonnummer en wachtwoord wijzigen.
// Je kunt ALLEEN je eigen gegevens wijzigen (we gebruiken altijd het ID uit de sessie).

// Laad alles wat we nodig hebben
require_once __DIR__ . '/../../includes/init.php';

// Je moet ingelogd zijn om je profiel te bekijken
require_login();

// Haal de gegevens van de ingelogde gebruiker op
$gebruiker = current_user();

// Is het formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lees de ingevulde velden uit
    $naam = trim($_POST['name'] ?? '');
    $telefoon = trim($_POST['phone'] ?? '');
    $nieuw_wachtwoord = $_POST['new_password'] ?? '';
    $herhaling = $_POST['new_password_confirm'] ?? '';

    // Controleer naam en telefoon (het e-mailadres kan niet worden gewijzigd)
    $fout = validate_contact_details($naam, $gebruiker['email'], $telefoon);

    // Wil de gebruiker ook een nieuw wachtwoord? Controleer dan of het goed genoeg is
    if ($fout === null && $nieuw_wachtwoord !== '') {
        $fout = validate_new_password($nieuw_wachtwoord, $herhaling);
    }

    if ($fout !== null) {
        // Er ging iets mis: laat de melding zien
        set_flash('error', $fout);
    } else {
        // Sla naam en telefoon op
        update_user_profile($gebruiker['id'], $naam, $telefoon);
        $bericht = 'Profielgegevens succesvol bijgewerkt!';

        // Sla ook het nieuwe wachtwoord op (als dat is ingevuld)
        if ($nieuw_wachtwoord !== '') {
            update_user_password($gebruiker['id'], $nieuw_wachtwoord);
            $bericht = 'Je profiel en wachtwoord zijn succesvol bijgewerkt!';
        }

        // Herlaad de pagina met een succesmelding
        redirect_with_message('/customer/profile.php', 'success', $bericht);
    }
}

// Titel en bovenkant van de pagina
$pagina_titel = 'Mijn Profiel';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Profielkaart -->
<div class="max-w-xl mx-auto card p-8">
    <h1 class="page-title text-2xl mb-6">Mijn Profiel</h1>

    <form action="/customer/profile.php" method="POST" class="space-y-4">
        <!-- Geheime CSRF-code (beveiliging) -->
        <?= csrf_field(); ?>

        <!-- Naam (bij een fout tonen we wat je net had ingevuld) -->
        <div>
            <label for="name" class="label">Volledige naam *</label>
            <input type="text" id="name" name="name" maxlength="100" required class="input"
                   value="<?= h($_POST['name'] ?? $gebruiker['name']); ?>">
        </div>

        <!-- E-mailadres: alleen lezen. Je pakketten zijn aan dit adres gekoppeld, daarom kun je het niet zelf wijzigen. -->
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

        <!-- Wachtwoord wijzigen (niet verplicht) -->
        <fieldset class="pt-4 border-t border-slate-100 space-y-4">
            <legend class="text-xs font-bold text-slate-500 uppercase tracking-wider pt-4">Wachtwoord wijzigen (optioneel)</legend>

            <!-- Nieuw wachtwoord -->
            <div>
                <label for="new_password" class="label">Nieuw wachtwoord</label>
                <input type="password" id="new_password" name="new_password" class="input" autocomplete="new-password"
                       placeholder="Laat leeg als je je wachtwoord niet wilt wijzigen">
            </div>

            <!-- Nieuw wachtwoord herhalen -->
            <div>
                <label for="new_password_confirm" class="label">Herhaal nieuw wachtwoord</label>
                <input type="password" id="new_password_confirm" name="new_password_confirm" class="input" autocomplete="new-password">
            </div>
        </fieldset>

        <!-- Opslaan -->
        <button type="submit" class="btn btn-primary w-full py-3">Wijzigingen Opslaan</button>
    </form>
</div>

<?php
// Onderkant van de pagina
require_once __DIR__ . '/../../includes/footer.php';
?>
