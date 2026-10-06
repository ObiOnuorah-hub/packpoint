<?php
// admin/slots_manage.php
//
// Hier voegt de admin nieuwe opslagvakjes toe, bijvoorbeeld vak C-01 in Stelling C.
// Onder het formulier staat een lijst met alle vakjes en of ze vrij of bezet zijn.

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../../includes/init.php';

// Alleen admins mogen hier komen.
require_role('admin');

// Is er net een formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Wat is er ingevuld? Een vakcode maken we altijd in hoofdletters, dus c-01 wordt C-01.
    $vakcode = strtoupper(trim($_POST['slot_code'] ?? ''));
    $stelling = trim($_POST['rack'] ?? '');

    // We kijken de invoer stap voor stap na. Bij de eerste fout stoppen we en laten we die zien.
    if ($vakcode === '' || $stelling === '') {
        set_flash('error', 'Vul a.u.b. een vakcode en stelling in.');
    } elseif (!preg_match('/^[A-Z0-9\-]{1,10}$/', $vakcode)) {
        // Alleen letters, cijfers en een streepje, en maximaal 10 tekens, anders past het niet in de database.
        set_flash('error', 'De vakcode mag alleen letters, cijfers en - bevatten (max 10 tekens), bijv. C-01.');
    } elseif (is_too_long($stelling, 50)) {
        set_flash('error', 'De naam van de stelling mag maximaal 50 tekens zijn.');
    } elseif (slot_code_exists($vakcode)) {
        // Elke vakcode mag maar een keer bestaan.
        set_flash('error', "Opslagvak '{$vakcode}' bestaat al.");
    } else {
        // Alles klopt, dus het vakje wordt toegevoegd. Een nieuw vakje is altijd vrij.
        add_slot($vakcode, $stelling);
        redirect_with_message('/admin/slots_manage.php', 'success', "Opslagvak '{$vakcode}' succesvol toegevoegd aan {$stelling}!");
    }
}

// Alle vakjes ophalen voor de tabel.
$vakken = get_all_slots();

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Vakken Beheer';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="space-y-6">

    <!-- De titel -->
    <div class="card p-6 flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div>
            <h1 class="page-title">Opslagvakken Beheer</h1>
            <p class="page-subtitle">Voeg nieuwe opslagvakken en stellingen toe of bekijk de huidige status.</p>
        </div>
        <a href="/admin/dashboard.php" class="text-sm text-brand-navy font-bold hover:underline">&larr; Terug naar Admin Dashboard</a>
    </div>

    <!-- Het formulier voor een nieuw vakje -->
    <section class="card p-6">
        <h2 class="card-title mb-4">+ Nieuw Opslagvak Toevoegen</h2>
        <form action="/admin/slots_manage.php" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <?= csrf_field(); ?>

            <!-- De stelling -->
            <div>
                <label for="rack" class="label">Stelling / sectie *</label>
                <input type="text" id="rack" name="rack" value="<?= old('rack'); ?>" maxlength="50" required class="input" placeholder="bijv. Stelling C">
            </div>

            <!-- De vakcode -->
            <div>
                <label for="slot_code" class="label">Vakcode *</label>
                <input type="text" id="slot_code" name="slot_code" value="<?= old('slot_code'); ?>" maxlength="10" required class="input font-mono uppercase" placeholder="bijv. C-01">
            </div>

            <!-- De knop om toe te voegen -->
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">Vak Toevoegen</button>
            </div>
        </form>
    </section>

    <!-- De tabel met alle vakjes -->
    <section class="card overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Overzicht Opslagvakken (Totaal: <?= count($vakken); ?>)</h2>
        </div>
        <?php if (empty($vakken)): ?>
            <!-- Er zijn nog geen vakjes -->
            <p class="p-8 text-center text-slate-400 text-sm">Er zijn nog geen opslagvakken. Voeg hierboven het eerste vak toe.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vakcode</th>
                        <th>Stelling</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vakken as $vak): ?>
                        <tr>
                            <td class="font-mono font-bold text-slate-800"><?= h($vak['slot_code']); ?></td>
                            <td class="text-slate-600"><?= h($vak['rack']); ?></td>
                            <td><?= slot_status_badge($vak['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

</div>

<?php
// En de onderkant van de pagina.
require_once __DIR__ . '/../../includes/footer.php';
?>
