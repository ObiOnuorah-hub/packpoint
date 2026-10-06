<?php
// ==============================================================================
// VERVOERDERS BEHEER (public/admin/carriers.php)
// ==============================================================================
// De beheerder kan hier nieuwe vervoerders toevoegen (bijv. FedEx)
// en ziet een lijst van alle vervoerders.

// Laad alles wat we nodig hebben
require_once __DIR__ . '/../../includes/init.php';

// Alleen admins
require_role('admin');

// Is het formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lees de naam uit
    $naam = trim($_POST['name'] ?? '');

    // Controleer de invoer
    if ($naam === '') {
        set_flash('error', 'Vul a.u.b. de naam van de vervoerder in.');
    } elseif (is_too_long($naam, 100)) {
        set_flash('error', 'De naam mag maximaal 100 tekens lang zijn.');
    } elseif (carrier_name_exists($naam)) {
        // Elke vervoerder mag maar 1 keer bestaan
        set_flash('error', "Vervoerder '{$naam}' bestaat al.");
    } else {
        // Alles goed: toevoegen (een nieuwe vervoerder is altijd actief)
        add_carrier($naam);
        redirect_with_message('/admin/carriers.php', 'success', "Vervoerder '{$naam}' succesvol toegevoegd!");
    }
}

// Haal alle vervoerders op voor de tabel
$vervoerders = get_all_carriers();

// Titel en bovenkant van de pagina
$pagina_titel = 'Vervoerders';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">

    <!-- Titel -->
    <div class="card p-6 flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div>
            <h1 class="page-title">Vervoerders Beheer</h1>
            <p class="page-subtitle">Beheer de aangesloten pakketvervoerders (PostNL, DHL, DPD, enz.).</p>
        </div>
        <a href="/admin/dashboard.php" class="text-sm text-brand-navy font-bold hover:underline">&larr; Terug naar Admin Dashboard</a>
    </div>

    <!-- Formulier: nieuwe vervoerder -->
    <section class="card p-6">
        <h2 class="card-title mb-4">+ Nieuwe Vervoerder Toevoegen</h2>
        <form action="/admin/carriers.php" method="POST" class="flex flex-col sm:flex-row gap-3">
            <?= csrf_field(); ?>

            <div class="flex-grow">
                <label for="name" class="label">Naam vervoerder *</label>
                <input type="text" id="name" name="name" value="<?= old('name'); ?>" maxlength="100" required class="input" placeholder="bijv. FedEx Express">
            </div>

            <button type="submit" class="btn btn-primary whitespace-nowrap sm:self-end">Vervoerder Opslaan</button>
        </form>
    </section>

    <!-- Tabel met alle vervoerders -->
    <section class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Aangesloten Vervoerders (Totaal: <?= count($vervoerders); ?>)</h2>
        </div>
        <?php if (empty($vervoerders)): ?>
            <!-- Nog geen vervoerders -->
            <p class="p-8 text-center text-slate-400 text-sm">Er zijn nog geen vervoerders. Voeg hierboven de eerste vervoerder toe.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Naam vervoerder</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vervoerders as $vervoerder): ?>
                        <tr>
                            <td class="font-mono text-slate-400">#<?= (int) $vervoerder['id']; ?></td>
                            <td class="font-bold text-slate-800"><?= h($vervoerder['name']); ?></td>
                            <td>
                                <?php if ($vervoerder['is_active']): ?>
                                    <span class="badge bg-emerald-100 text-emerald-800">Actief</span>
                                <?php else: ?>
                                    <span class="badge bg-slate-100 text-slate-600">Inactief</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

</div>

<?php
// Onderkant van de pagina
require_once __DIR__ . '/../../includes/footer.php';
?>
