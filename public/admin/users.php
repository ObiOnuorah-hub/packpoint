<?php
// admin/users.php
//
// Hier beheert de admin de gebruikers. Je kunt:
//   - alle gebruikers bekijken
//   - een nieuwe gebruiker aanmaken (klant, baliemedewerker of beheerder)
//   - de rol van een gebruiker aanpassen
//   - een gebruiker verwijderen
//
// Twee veiligheidsregels:
//   - Je kunt je eigen rol niet aanpassen en jezelf niet verwijderen. Anders sluit je
//     jezelf buiten.
//   - Een medewerker die pakketten heeft ingeboekt kun je niet verwijderen, anders klopt
//     de geschiedenis van die pakketten niet meer.

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../../includes/init.php';

// Alleen admins mogen hier komen.
require_role('admin');

// Wie is de ingelogde admin?
$admin = current_user();

// Hieronder staan drie functies, een voor elke knop op deze pagina.

// Maakt een nieuwe gebruiker aan.
function handle_create_user(): void
{
    // Wat is er in het formulier ingevuld?
    $naam = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $telefoon = trim($_POST['phone'] ?? '');
    $wachtwoord = $_POST['password'] ?? '';
    $rol = $_POST['role'] ?? '';

    // Eerst naam, e-mail en telefoon nakijken. Is dat goed, dan het wachtwoord.
    // Dat '??' betekent: is het eerste antwoord null (geen fout), pak dan het tweede.
    $fout = validate_contact_details($naam, $email, $telefoon) ?? validate_new_password($wachtwoord);

    // Bestaat de gekozen rol echt? Wat een formulier stuurt vertrouwen we nooit zomaar.
    if ($fout === null && !array_key_exists($rol, ROLES)) {
        $fout = 'Kies een geldige rol.';
    }

    // Is het e-mailadres nog niet in gebruik?
    if ($fout === null && email_exists($email)) {
        $fout = 'Er bestaat al een account met dit e-mailadres.';
    }

    // Klopt er iets niet? Dan de melding laten zien en hier stoppen.
    if ($fout !== null) {
        set_flash('error', $fout);
        return;
    }

    // Alles goed, dus de gebruiker kan worden aangemaakt.
    create_user($naam, $email, $telefoon, $wachtwoord, $rol);
    redirect_with_message('/admin/users.php', 'success', "Gebruiker {$naam} (" . role_label($rol) . ') succesvol aangemaakt!');
}

// Past de rol van een gebruiker aan.
function handle_change_role(int $admin_id): void
{
    // Om welke gebruiker gaat het, en welke rol moet hij krijgen?
    $gebruiker_id = (int) ($_POST['user_id'] ?? 0);
    $rol = $_POST['role'] ?? '';

    // Je eigen rol aanpassen mag niet.
    if ($gebruiker_id === $admin_id) {
        set_flash('error', 'Je kunt je eigen rol niet wijzigen.');
        return;
    }

    // Bestaan de gebruiker en de rol allebei?
    if (!find_user_by_id($gebruiker_id) || !array_key_exists($rol, ROLES)) {
        set_flash('error', 'Ongeldige gebruiker of rol.');
        return;
    }

    // Alles klopt, dus de rol wordt opgeslagen.
    update_user_role($gebruiker_id, $rol);
    redirect_with_message('/admin/users.php', 'success', 'Rol gewijzigd naar ' . role_label($rol) . '.');
}

// Verwijdert een gebruiker.
function handle_delete_user(int $admin_id): void
{
    // Om welke gebruiker gaat het?
    $gebruiker_id = (int) ($_POST['user_id'] ?? 0);
    $gebruiker = find_user_by_id($gebruiker_id);

    // Jezelf verwijderen mag niet.
    if ($gebruiker_id === $admin_id) {
        set_flash('error', 'Je kunt je eigen account niet verwijderen.');
        return;
    }

    // Bestaat de gebruiker wel?
    if (!$gebruiker) {
        set_flash('error', 'Deze gebruiker bestaat niet (meer).');
        return;
    }

    // Heeft deze medewerker al pakketten ingeboekt? Dan blijft hij staan.
    if (user_has_registered_parcels($gebruiker_id)) {
        set_flash('error', "{$gebruiker['name']} heeft pakketten ingeboekt en kan daarom niet worden verwijderd. Wijzig eventueel de rol.");
        return;
    }

    // Niks houdt ons tegen, dus weg ermee.
    delete_user($gebruiker_id);
    redirect_with_message('/admin/users.php', 'success', "Gebruiker {$gebruiker['name']} is verwijderd.");
}

// Op welke knop is er gedrukt? Dat staat in het verborgen veld 'action' van het formulier.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    match ($_POST['action'] ?? '') {
        'create'      => handle_create_user(),
        'change_role' => handle_change_role($admin['id']),
        'delete'      => handle_delete_user($admin['id']),
        default       => set_flash('error', 'Onbekende actie.'),
    };
}

// Alle gebruikers ophalen voor de tabel.
$gebruikers = get_all_users();

// Welke rol staat er standaard aangevinkt in het formulier voor een nieuwe gebruiker?
// Ging er bij het aanmaken iets mis? Dan de rol die je had gekozen. Anders baliemedewerker.
$gekozen_rol = ($_POST['action'] ?? '') === 'create' ? ($_POST['role'] ?? 'employee') : 'employee';

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Gebruikersbeheer';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">

    <!-- De titel -->
    <div class="card p-6 flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div>
            <h1 class="page-title">Gebruikersbeheer</h1>
            <p class="page-subtitle">Beheer accounts en rollen van baliemedewerkers, beheerders en klanten.</p>
        </div>
        <a href="/admin/dashboard.php" class="text-sm text-brand-navy font-bold hover:underline">&larr; Terug naar Admin Dashboard</a>
    </div>

    <!-- Het formulier voor een nieuwe gebruiker -->
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

            <!-- E-mailadres -->
            <div>
                <label for="email" class="label">E-mailadres *</label>
                <input type="email" id="email" name="email" value="<?= old('email'); ?>" maxlength="150" required class="input" placeholder="medewerker@packpoint.nl">
            </div>

            <!-- Telefoonnummer -->
            <div>
                <label for="phone" class="label">Telefoonnummer</label>
                <input type="tel" id="phone" name="phone" value="<?= old('phone'); ?>" maxlength="20" class="input" placeholder="06-12345678">
            </div>

            <!-- De rol. De keuzes komen uit de lijst ROLES in auth.php. -->
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

            <!-- De knop om op te slaan -->
            <div class="sm:col-span-2 lg:col-span-5 text-right">
                <button type="submit" class="btn btn-primary">Gebruiker Opslaan</button>
            </div>
        </form>
    </section>

    <!-- De tabel met alle gebruikers -->
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
                            // Is deze regel de admin die nu is ingelogd?
                            $is_ikzelf = (int) $gebruiker['id'] === (int) $admin['id'];
                        ?>
                        <tr>
                            <td class="code"><?= h($gebruiker['username']); ?></td>
                            <td class="font-semibold text-slate-800"><?= h($gebruiker['name']); ?></td>
                            <td class="text-slate-600"><?= h($gebruiker['email']); ?></td>
                            <td class="text-slate-600"><?= h($gebruiker['phone'] ?? '-'); ?></td>
                            <td><?= role_badge($gebruiker['role']); ?></td>
                            <td class="text-slate-500"><?= format_date($gebruiker['created_at']); ?></td>

                            <!-- De knoppen: de rol aanpassen en verwijderen. Bij jezelf niet. -->
                            <td class="text-right">
                                <?php if ($is_ikzelf): ?>
                                    <span class="text-xs text-slate-400">(dit ben jij)</span>
                                <?php else: ?>
                                    <div class="flex justify-end items-center gap-2">
                                        <!-- De rol aanpassen -->
                                        <form method="POST" action="/admin/users.php" class="flex items-center gap-1">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?= (int) $gebruiker['id']; ?>">
                                            <!-- Het aria-label is er voor schermlezers, zodat ook blinde gebruikers weten wat dit is -->
                                            <select name="role" aria-label="Rol van <?= h($gebruiker['name']); ?>" class="input py-1.5 px-2 text-xs w-36">
                                                <?php foreach (ROLES as $waarde => $naam): ?>
                                                    <option value="<?= $waarde; ?>" <?= $gebruiker['role'] === $waarde ? 'selected' : ''; ?>><?= h($naam); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-secondary btn-sm">Opslaan</button>
                                        </form>

                                        <!-- Verwijderen. Eerst vragen we of je het zeker weet. -->
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
// En de onderkant van de pagina.
require_once __DIR__ . '/../../includes/footer.php';
?>
