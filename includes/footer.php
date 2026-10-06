<?php
// footer.php
//
// De onderkant van elke pagina. Het doet drie dingen: het sluit het inhoudsgedeelte af
// dat header.php had geopend, het zet de voettekst onderaan, en het sluit de hele
// pagina netjes af. Een pagina eindigt daarom altijd met:
//
//     require_once __DIR__ . '/../../includes/footer.php';
?>
</main>

<!-- De voettekst onderaan de pagina -->
<footer class="border-t border-slate-200 bg-white py-5">
    <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-500">
        <!-- &copy; is het copyright-teken. date('Y') geeft het jaar van vandaag, dus het jaartal blijft vanzelf kloppen. -->
        <p>&copy; <?= date('Y'); ?> <strong>PackPoint</strong></p>

        <!-- Een korte omschrijving van wat de app doet (&amp; is gewoon het &-teken) -->
        <p class="mt-1 text-slate-400">Pakketregistratie, Opslagvakbeheer &amp; Veilige Uitgifte</p>
    </div>
</footer>

<!-- Hier eindigt de pagina die in header.php is begonnen -->
</body>
</html>
