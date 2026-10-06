<?php
// ==============================================================================
// FOOTER (includes/footer.php)
// ==============================================================================
// Sluit de <main> en <body> af en toont de voettekst onderaan elke pagina.
?>
</main>

<!-- Voettekst onderaan de pagina -->
<footer class="border-t border-slate-200 bg-white py-5">
    <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-500">
        <!-- Naam van de applicatie (date('Y') = het huidige jaar) -->
        <p>&copy; <?= date('Y'); ?> <strong>PackPoint</strong></p>
        <!-- Korte uitleg van het systeem -->
        <p class="mt-1 text-slate-400">Pakketregistratie, Opslagvakbeheer &amp; Veilige Uitgifte</p>
    </div>
</footer>

</body>
</html>
