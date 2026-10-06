<?php
// employee/register_parcel.php
//
// Hier boekt de medewerker een nieuw pakket in, met de vervoerder en een unieke barcode.
// Het systeem verzint er zelf een unieke afhaalcode bij voor de klant.
//
// Er zijn twee mogelijkheden:
//   Binnengekomen  het pakket ligt al aan de balie, dus je kiest meteen een vrij vakje
//   Verwacht       het pakket is aangekondigd maar nog niet binnen, dus nog geen vakje nodig

// Eerst alles inladen wat deze pagina nodig heeft.
require_once __DIR__ . '/../../includes/init.php';

// Alleen medewerkers en admins mogen pakketten registreren.
require_role(['employee', 'admin']);

// Is er net een formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Alles wat is ingevuld, in een lijstje. (int) maakt van tekst een getal en trim
    // haalt spaties weg aan het begin en het eind.
    $invoer = [
        'status'          => $_POST['status'] ?? '',
        'carrier_id'      => (int) ($_POST['carrier_id'] ?? 0),
        'tracking_code'   => trim($_POST['tracking_code'] ?? ''),
        'customer_name'   => trim($_POST['customer_name'] ?? ''),
        'customer_email'  => strtolower(trim($_POST['customer_email'] ?? '')),
        'customer_phone'  => trim($_POST['customer_phone'] ?? ''),
        'storage_slot_id' => (int) ($_POST['storage_slot_id'] ?? 0),
    ];

    // Alles controleren op de server: bestaat de vervoerder, is het vakje vrij, is de barcode nieuw?
    $fout = validate_parcel_input($invoer);

    if ($fout !== null) {
        // Er klopt iets niet, dus we laten de melding zien.
        set_flash('error', $fout);
    } else {
        // Alles klopt. Het pakket wordt opgeslagen, en als het al binnen is ook het vakje bezet.
        $afhaalcode = register_parcel($invoer, current_user()['id']);

        // Gelukt! We kiezen een melding die past bij wat de medewerker deed.
        if ($invoer['status'] === 'expected') {
            $bericht = "Pakket aangemeld als verwacht. Afhaalcode: $afhaalcode";
        } else {
            $bericht = "Pakket opgeslagen! Afhaalcode: $afhaalcode";
        }
        redirect_with_message('/employee/dashboard.php', 'success', $bericht);
    }
}

// De lijsten voor de keuzemenu's: de actieve vervoerders en de vrije vakjes.
$vervoerders = get_active_carriers();
$vrije_vakken = get_free_slots();

// Wat was er al gekozen? Dat is zo na een foutmelding, of als je via 'Gebruik vak' op
// het vakkenraster komt, dan staat dat vakje al klaar.
$gekozen_status = ($_POST['status'] ?? 'arrived') === 'expected' ? 'expected' : 'arrived';
$gekozen_vervoerder = (int) ($_POST['carrier_id'] ?? 0);
$gekozen_vak = (int) ($_POST['storage_slot_id'] ?? $_GET['slot_id'] ?? 0);

// De titel voor het browsertabblad, en daarna de bovenkant van de pagina.
$pagina_titel = 'Pakket Registreren';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-xl mx-auto card p-6">
    <!-- De titel -->
    <h1 class="page-title">Pakket Registreren (Intake)</h1>
    <p class="page-subtitle mb-6">Boek een nieuw pakket in. Ligt het al aan de balie? Wijs dan meteen een opslagvak toe.</p>

    <!-- Een waarschuwing als er geen vrije vakjes meer zijn -->
    <?php if (empty($vrije_vakken)): ?>
        <div class="alert alert-warning">
            ⚠️ <strong>Let op:</strong> er zijn geen vrije opslagvakken. Je kunt alleen verwachte pakketten aanmelden. Vraag een beheerder om nieuwe vakken aan te maken.
        </div>
    <?php endif; ?>

    <form method="POST" action="/employee/register_parcel.php" class="space-y-4">
        <!-- Een verborgen geheime code die de site beschermt tegen nepformulieren (CSRF) -->
        <?= csrf_field(); ?>

        <!-- Is het pakket er al, of wordt het nog verwacht? -->
        <fieldset>
            <legend class="label">Status van het pakket *</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <!-- Keuze 1: het pakket is binnen -->
                <label class="flex items-start gap-2 p-3 border border-slate-300 rounded-lg cursor-pointer hover:border-brand-sky">
                    <input type="radio" name="status" value="arrived" class="mt-1" onchange="toggleSlotChoice()"
                           <?= $gekozen_status === 'arrived' ? 'checked' : ''; ?>>
                    <span>
                        <span class="block text-sm font-bold">Binnengekomen</span>
                        <span class="block text-xs text-slate-500">Ligt aan de balie, meteen in een vak</span>
                    </span>
                </label>

                <!-- Keuze 2: het pakket wordt verwacht -->
                <label class="flex items-start gap-2 p-3 border border-slate-300 rounded-lg cursor-pointer hover:border-brand-sky">
                    <input type="radio" name="status" value="expected" class="mt-1" onchange="toggleSlotChoice()"
                           <?= $gekozen_status === 'expected' ? 'checked' : ''; ?>>
                    <span>
                        <span class="block text-sm font-bold">Verwacht</span>
                        <span class="block text-xs text-slate-500">Aangekondigd, nog niet binnen</span>
                    </span>
                </label>
            </div>
        </fieldset>

        <!-- De vervoerder kiezen -->
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

        <!-- De barcode (track & trace) van het pakket -->
        <div>
            <label for="tracking_code" class="label">Track &amp; Trace barcode *</label>
            <input type="text" id="tracking_code" name="tracking_code" value="<?= old('tracking_code'); ?>"
                   maxlength="50" pattern="[A-Za-z0-9\-]+" required class="input font-mono"
                   placeholder="Scan de barcode of typ hem over">
        </div>

        <!-- De gegevens van de klant voor wie het pakket is -->
        <fieldset class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
            <legend class="px-1 text-xs font-bold text-slate-700 uppercase tracking-wider">Gegevens ontvanger</legend>

            <!-- De naam -->
            <div>
                <label for="customer_name" class="label">Naam ontvanger *</label>
                <input type="text" id="customer_name" name="customer_name" value="<?= old('customer_name'); ?>"
                       maxlength="100" required class="input" placeholder="Volledige naam klant">
            </div>

            <!-- Het e-mailadres. Daarmee ziet de klant het pakket op zijn eigen scherm. -->
            <div>
                <label for="customer_email" class="label">E-mailadres klant *</label>
                <input type="email" id="customer_email" name="customer_email" value="<?= old('customer_email'); ?>"
                       maxlength="150" required class="input" placeholder="klant@voorbeeld.nl">
            </div>

            <!-- Het telefoonnummer, dat hoeft niet -->
            <div>
                <label for="customer_phone" class="label">Telefoonnummer (optioneel)</label>
                <input type="tel" id="customer_phone" name="customer_phone" value="<?= old('customer_phone'); ?>"
                       maxlength="20" class="input" placeholder="06-12345678">
            </div>
        </fieldset>

        <!-- Het vakje kiezen. In de lijst staan alleen vrije vakjes. Bij 'Verwacht' verbergen we dit stuk. -->
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

        <!-- De knop om op te slaan -->
        <button type="submit" class="btn btn-primary w-full py-3">Pakket Opslaan &amp; Afhaalcode Genereren</button>
    </form>
</div>

<script>
    // Laat de vakkeuze alleen zien als 'Binnengekomen' is gekozen. Dit is puur gemak voor
    // de gebruiker, want de server controleert het zelf nog een keer.
    function toggleSlotChoice() {
        const isBinnen = document.querySelector('input[name="status"][value="arrived"]').checked;
        document.getElementById('vakkeuze').style.display = isBinnen ? 'block' : 'none';
    }

    // Een keer meteen uitvoeren als de pagina opent.
    toggleSlotChoice();
</script>

<?php
// En de onderkant van de pagina.
require_once __DIR__ . '/../../includes/footer.php';
?>
