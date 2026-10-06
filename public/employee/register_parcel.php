<?php
// ==============================================================================
// PAKKET REGISTREREN (public/employee/register_parcel.php)
// ==============================================================================
// De medewerker boekt hier een nieuw pakket in met vervoerder en unieke barcode (FE-04).
// Het systeem maakt automatisch een unieke afhaalcode voor de klant.
//
// Twee keuzes:
//   - Binnengekomen: het pakket ligt al aan de balie -> meteen een vrij vak kiezen (FE-05)
//   - Verwacht:      het pakket is aangekondigd maar nog niet binnen -> nog geen vak nodig

// Laad alles wat we nodig hebben
require_once __DIR__ . '/../../includes/init.php';

// Alleen medewerkers en admins mogen pakketten registreren
require_role(['employee', 'admin']);

// Is het formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lees alle velden uit. (int) maakt van de tekst een getal, trim haalt spaties weg.
    $invoer = [
        'status'          => $_POST['status'] ?? '',
        'carrier_id'      => (int) ($_POST['carrier_id'] ?? 0),
        'tracking_code'   => trim($_POST['tracking_code'] ?? ''),
        'customer_name'   => trim($_POST['customer_name'] ?? ''),
        'customer_email'  => strtolower(trim($_POST['customer_email'] ?? '')),
        'customer_phone'  => trim($_POST['customer_phone'] ?? ''),
        'storage_slot_id' => (int) ($_POST['storage_slot_id'] ?? 0),
    ];

    // Controleer alle invoer op de server (vervoerder bestaat? vak vrij? barcode uniek?)
    $fout = validate_parcel_input($invoer);

    if ($fout !== null) {
        // Er klopt iets niet: laat de melding zien
        set_flash('error', $fout);
    } else {
        // Sla het pakket op (en zet het vak op bezet als het pakket al binnen is)
        $afhaalcode = register_parcel($invoer, current_user()['id']);

        // Gelukt! Terug naar het dashboard met een melding die past bij de keuze
        if ($invoer['status'] === 'expected') {
            $bericht = "Pakket aangemeld als verwacht. Afhaalcode: $afhaalcode";
        } else {
            $bericht = "Pakket opgeslagen! Afhaalcode: $afhaalcode";
        }
        redirect_with_message('/employee/dashboard.php', 'success', $bericht);
    }
}

// Haal de keuzelijsten op: actieve vervoerders en vrije vakken
$vervoerders = get_active_carriers();
$vrije_vakken = get_free_slots();

// Welke waarden waren al gekozen? (bij een fout, of als je via 'Gebruik vak' komt)
$gekozen_status = ($_POST['status'] ?? 'arrived') === 'expected' ? 'expected' : 'arrived';
$gekozen_vervoerder = (int) ($_POST['carrier_id'] ?? 0);
$gekozen_vak = (int) ($_POST['storage_slot_id'] ?? $_GET['slot_id'] ?? 0);

// Titel en bovenkant van de pagina
$pagina_titel = 'Pakket Registreren';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-xl mx-auto card p-6">
    <!-- Titel -->
    <h1 class="page-title">Pakket Registreren (Intake)</h1>
    <p class="page-subtitle mb-6">Boek een nieuw pakket in. Ligt het al aan de balie? Wijs dan meteen een opslagvak toe.</p>

    <!-- Waarschuwing als er geen vrije vakken meer zijn -->
    <?php if (empty($vrije_vakken)): ?>
        <div class="alert alert-warning">
            ⚠️ <strong>Let op:</strong> er zijn geen vrije opslagvakken. Je kunt alleen verwachte pakketten aanmelden. Vraag een beheerder om nieuwe vakken aan te maken.
        </div>
    <?php endif; ?>

    <form method="POST" action="/employee/register_parcel.php" class="space-y-4">
        <!-- Geheime CSRF-code (beveiliging) -->
        <?= csrf_field(); ?>

        <!-- Is het pakket er al, of wordt het verwacht? -->
        <fieldset>
            <legend class="label">Status van het pakket *</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <!-- Keuze 1: binnengekomen -->
                <label class="flex items-start gap-2 p-3 border border-slate-300 rounded-lg cursor-pointer hover:border-brand-sky">
                    <input type="radio" name="status" value="arrived" class="mt-1" onchange="toonVakkeuze()"
                           <?= $gekozen_status === 'arrived' ? 'checked' : ''; ?>>
                    <span>
                        <span class="block text-sm font-bold">Binnengekomen</span>
                        <span class="block text-xs text-slate-500">Ligt aan de balie, meteen in een vak</span>
                    </span>
                </label>

                <!-- Keuze 2: verwacht -->
                <label class="flex items-start gap-2 p-3 border border-slate-300 rounded-lg cursor-pointer hover:border-brand-sky">
                    <input type="radio" name="status" value="expected" class="mt-1" onchange="toonVakkeuze()"
                           <?= $gekozen_status === 'expected' ? 'checked' : ''; ?>>
                    <span>
                        <span class="block text-sm font-bold">Verwacht</span>
                        <span class="block text-xs text-slate-500">Aangekondigd, nog niet binnen</span>
                    </span>
                </label>
            </div>
        </fieldset>

        <!-- Vervoerder kiezen -->
        <div>
            <label for="carrier_id" class="label">Vervoerder *</label>
            <select id="carrier_id" name="carrier_id" required class="input">
                <option value="">-- Kies vervoerder (bijv. PostNL, DHL) --</option>
                <?php foreach ($vervoerders as $vervoerder): ?>
                    <option value="<?= (int) $vervoerder['id']; ?>" <?= (int) $vervoerder['id'] === $gekozen_vervoerder ? 'selected' : ''; ?>>
                        <?= h($vervoerder['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Barcode / Track & Trace -->
        <div>
            <label for="tracking_code" class="label">Track &amp; Trace barcode *</label>
            <input type="text" id="tracking_code" name="tracking_code" value="<?= old('tracking_code'); ?>"
                   maxlength="50" pattern="[A-Za-z0-9\-]+" required class="input font-mono"
                   placeholder="Scan de barcode of typ hem over">
        </div>

        <!-- Gegevens van de klant -->
        <fieldset class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
            <legend class="px-1 text-xs font-bold text-slate-700 uppercase tracking-wider">Gegevens ontvanger</legend>

            <!-- Naam ontvanger -->
            <div>
                <label for="customer_name" class="label">Naam ontvanger *</label>
                <input type="text" id="customer_name" name="customer_name" value="<?= old('customer_name'); ?>"
                       maxlength="100" required class="input" placeholder="Volledige naam klant">
            </div>

            <!-- E-mail ontvanger (hiermee ziet de klant het pakket op zijn dashboard) -->
            <div>
                <label for="customer_email" class="label">E-mailadres klant *</label>
                <input type="email" id="customer_email" name="customer_email" value="<?= old('customer_email'); ?>"
                       maxlength="150" required class="input" placeholder="klant@voorbeeld.nl">
            </div>

            <!-- Telefoon ontvanger (niet verplicht) -->
            <div>
                <label for="customer_phone" class="label">Telefoonnummer (optioneel)</label>
                <input type="tel" id="customer_phone" name="customer_phone" value="<?= old('customer_phone'); ?>"
                       maxlength="20" class="input" placeholder="06-12345678">
            </div>
        </fieldset>

        <!-- Opslagvak kiezen (alleen vrije vakken staan in de lijst). Verborgen bij 'Verwacht'. -->
        <div id="vakkeuze">
            <label for="storage_slot_id" class="label">Opslagvak toewijzen (kies een vrij vak) *</label>
            <select id="storage_slot_id" name="storage_slot_id" class="input font-bold text-brand-navy">
                <option value="">-- Kies vrij vak --</option>
                <?php foreach ($vrije_vakken as $vak): ?>
                    <option value="<?= (int) $vak['id']; ?>" <?= (int) $vak['id'] === $gekozen_vak ? 'selected' : ''; ?>>
                        Vak <?= h($vak['slot_code']); ?> (<?= h($vak['rack']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Opslaan -->
        <button type="submit" class="btn btn-primary w-full py-3">Pakket Opslaan &amp; Afhaalcode Genereren</button>
    </form>
</div>

<script>
    // Laat de vakkeuze alleen zien als 'Binnengekomen' is gekozen.
    // (Dit is alleen gemak. De server controleert het ook!)
    function toonVakkeuze() {
        const isBinnen = document.querySelector('input[name="status"][value="arrived"]').checked;
        document.getElementById('vakkeuze').style.display = isBinnen ? 'block' : 'none';
    }

    // Meteen 1 keer uitvoeren als de pagina laadt
    toonVakkeuze();
</script>

<?php
// Onderkant van de pagina
require_once __DIR__ . '/../../includes/footer.php';
?>
