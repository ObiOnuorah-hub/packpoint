<?php
// ==============================================================================
// GEBRUIKERSBEHEER (public/admin/users.php)
// ==============================================================================
// De beheerder kan hier (FE-08):
//   - alle gebruikers bekijken
//   - een nieuwe gebruiker aanmaken (klant, baliemedewerker of beheerder)
//   - de rol van een gebruiker wijzigen
//   - een gebruiker verwijderen
//
// Veiligheidsregels:
//   - je kunt je EIGEN rol niet wijzigen en jezelf niet verwijderen (anders sluit je jezelf buiten)
//   - een medewerker die pakketten heeft ingeboekt kun je niet verwijderen (geschiedenis blijft kloppen)

// Laad alles wat we nodig hebben
require_once __DIR__ . '/../../includes/init.php';

// Alleen admins
require_role('admin');

// Wie is de ingelogde admin?
$admin = current_user();

// ------------------------------------------------------------------------------
// FUNCTIES VOOR DE 3 KNOPPEN (aanmaken, rol wijzigen, verwijderen)
// ------------------------------------------------------------------------------

// Nieuwe gebruiker aanmaken
function handle_create_user(): void
{
    // Lees de velden uit
    $naam = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $telefoon = trim($_POST['phone'] ?? '');
    $wachtwoord = $_POST['password'] ?? '';
    $rol = $_POST['role'] ?? '';

    // Controleer naam, e-mail, telefoon en wachtwoord
    $fout = validate_contact_details($naam, $email, $telefoon) ?? validate_new_password($wachtwoord);

    // Bestaat de gekozen rol echt? (nooit zomaar vertrouwen wat het formulier stuurt!)
    if ($fout === null && !array_key_exists($rol, ROLES)) {
        $fout = 'Kies een geldige rol.';
    }

    // Is het e-mailadres nog vrij?
    if ($fout === null && email_exists($email)) {
        $fout = 'Er bestaat al een account met dit e-mailadres.';
    }

    // Fout? Melding tonen en stoppen
    if ($fout !== null) {
        set_flash('error', $fout);
        return;
    }

    // Alles goed: gebruiker aanmaken
    create_user($naam, $email, $telefoon, $wachtwoord, $rol);
    redirect_with_message('/admin/users.php', 'success', "Gebruiker {$naam} (" . role_label($rol) . ') succesvol aangemaakt!');
}

// Rol van een gebruiker wijzigen
function handle_change_role(int $admin_id): void
{
    // Welke gebruiker en welke nieuwe rol?
    $gebruiker_id = (int) ($_POST['user_id'] ?? 0);
    $rol = $_POST['role'] ?? '';

    // Je eigen rol wijzigen mag niet
    if ($gebruiker_id === $admin_id) {
        set_flash('error', 'Je kunt je eigen rol niet wijzigen.');
        return;
    }

    // Bestaat de gebruiker en de rol?
    if (!find_user_by_id($gebruiker_id) || !array_key_exists($rol, ROLES)) {
        set_flash('error', 'Ongeldige gebruiker of rol.');
        return;
    }

    // Rol opslaan
    update_user_role($gebruiker_id, $rol);
    redirect_with_message('/admin/users.php', 'success', 'Rol gewijzigd naar ' . role_label($rol) . '.');
}

// Gebruiker verwijderen
function handle_delete_user(int $admin_id): void
{
    // Welke gebruiker?
    $gebruiker_id = (int) ($_POST['user_id'] ?? 0);
    $gebruiker = find_user_by_id($gebruiker_id);

    // Jezelf verwijderen mag niet
    if ($gebruiker_id === $admin_id) {
        set_flash('error', 'Je kunt je eigen account niet verwijderen.');
        return;
    }

    // Bestaat de gebruiker wel?
    if (!$gebruiker) {
        set_flash('error', 'Deze gebruiker bestaat niet (meer).');
        return;
    }

    // Heeft deze medewerker pakketten ingeboekt? Dan niet verwijderen
    if (user_has_registered_parcels($gebruiker_id)) {
        set_flash('error', "{$gebruiker['name']} heeft pakketten ingeboekt en kan daarom niet worden verwijderd. Wijzig eventueel de rol.");
        return;
    }

    // Verwijderen
    delete_user($gebruiker_id);
    redirect_with_message('/admin/users.php', 'success', "Gebruiker {$gebruiker['name']} is verwijderd.");
}

// ------------------------------------------------------------------------------
// WELKE KNOP IS ER INGEDRUKT?
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Het verborgen veld 'action' vertelt welke actie het is
    match ($_POST['action'] ?? '') {
        'create'      => handle_create_user(),
        'change_role' => handle_change_role($admin['id']),
        'delete'      => handle_delete_user($admin['id']),
        default       => set_flash('error', 'Onbekende actie.'),
    };
}

// Haal alle gebruikers op voor de tabel
$gebruikers = get_all_users();

// Welke rol staat standaard geselecteerd in het 'nieuwe gebruiker' formulier?
// (na een fout bij aanmaken: de rol die je had gekozen, anders: baliemedewerker)
$gekozen_rol = ($_POST['action'] ?? '') === 'create' ? ($_POST['role'] ?? 'employee') : 'employee';

// Titel en bovenkant van de pagina
$pagina_titel = 'Gebruikersbeheer';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">

    <!-- Titel -->
    <div class="card p-6 flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div>
            <h1 class="page-title">Gebruikersbeheer</h1>
            <p class="page-subtitle">Beheer accounts en rollen van baliemedewerkers, beheerders en klanten.</p>
        </div>
        <a href="/admin/dashboard.php" class="text-sm text-brand-navy font-bold hover:underline">&larr; Terug naar Admin Dashboard</a>
    </div>

    <!-- Formulier: nieuwe gebruiker -->
    <section class="card p-6">
        <h2 class="card-title mb-4">+ Nieuwe Gebruiker Aanmaken</h2>
        <form action="/admin/users.php" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="create">

            <!-- Naam -->
            <div>
                <label for="name" class="label">Naam *</label>
                <input type="text" id="name" name="name" value="<?= old('name'); ?>" maxlength="100" required class="input" placeholder="Jan de Vries">
            </div>

            <!-- E-mail -->
            <div>
                <label for="email" class="label">E-mailadres *</label>
                <input type="email" id="email" name="email" value="<?= old('email'); ?>" maxlength="150" required class="input" placeholder="medewerker@packpoint.nl">
            </div>

            <!-- Telefoon -->
            <div>
                <label for="phone" class="label">Telefoonnummer</label>
                <input type="tel" id="phone" name="phone" value="<?= old('phone'); ?>" maxlength="20" class="input" placeholder="06-12345678">
            </div>

            <!-- Rol: de opties komen uit de ROLES lijst in auth.php -->
            <div>
                <label for="role" class="label">Rol *</label>
                <select id="role" name="role" required class="input font-semibold">
                    <?php foreach (ROLES as $waarde => $naam): ?>
                        <option value="<?= $waarde; ?>" <?= $gekozen_rol === $waarde ? 'selected' : ''; ?>><?= h($naam); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Wachtwoord -->
            <div>
                <label for="password" class="label">Wachtwoord *</label>
                <input type="password" id="password" name="password" minlength="<?= MIN_PASSWORD_LENGTH; ?>" required class="input" autocomplete="new-password" placeholder="Min. <?= MIN_PASSWORD_LENGTH; ?> tekens">
            </div>

            <!-- Opslaan -->
            <div class="sm:col-span-2 lg:col-span-5 text-right">
                <button type="submit" class="btn btn-primary">Gebruiker Opslaan</button>
            </div>
        </form>
    </section>

    <!-- Tabel met alle gebruikers -->
    <section class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Bestaande Gebruikers (Totaal: <?= count($gebruikers); ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Gebruikersnaam</th>
                        <th>Naam</th>
                        <th>E-mailadres</th>
                        <th>Telefoon</th>
                        <th>Rol</th>
                        <th>Aangemaakt</th>
                        <th class="text-right">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gebruikers as $gebruiker): ?>
                        <?php
                            // Is dit de ingelogde admin zelf?
                            $is_ikzelf = (int) $gebruiker['id'] === (int) $admin['id'];
                        ?>
                        <tr>
                            <td class="code"><?= h($gebruiker['username']); ?></td>
                            <td class="font-semibold text-slate-800"><?= h($gebruiker['name']); ?></td>
                            <td class="text-slate-600"><?= h($gebruiker['email']); ?></td>
                            <td class="text-slate-600"><?= h($gebruiker['phone'] ?? '-'); ?></td>
                            <td><?= role_badge($gebruiker['role']); ?></td>
                            <td class="text-slate-500"><?= format_date($gebruiker['created_at']); ?></td>

                            <!-- Acties: rol wijzigen en verwijderen (niet bij jezelf) -->
                            <td class="text-right">
                                <?php if ($is_ikzelf): ?>
                                    <span class="text-xs text-slate-400">(dit ben jij)</span>
                                <?php else: ?>
                                    <div class="flex justify-end items-center gap-2">
                                        <!-- Rol wijzigen -->
                                        <form method="POST" action="/admin/users.php" class="flex items-center gap-1">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?= (int) $gebruiker['id']; ?>">
                                            <!-- aria-label: voor schermlezers (blinde gebruikers) -->
                                            <select name="role" aria-label="Rol van <?= h($gebruiker['name']); ?>" class="input py-1.5 px-2 text-xs w-36">
                                                <?php foreach (ROLES as $waarde => $naam): ?>
                                                    <option value="<?= $waarde; ?>" <?= $gebruiker['role'] === $waarde ? 'selected' : ''; ?>><?= h($naam); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-secondary btn-sm">Opslaan</button>
                                        </form>

                                        <!-- Verwijderen (eerst bevestigen) -->
                                        <form method="POST" action="/admin/users.php" onsubmit="return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= (int) $gebruiker['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Verwijder</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<?php
// Onderkant van de pagina
require_once __DIR__ . '/../../includes/footer.php';
?>
