# PackPoint op PLESK zetten 🚀

Zo krijg je PackPoint online op je eigen PLESK-site (jouw.website). Duurt een kwartiertje.

---

## Stap 1 – Controleer je PHP-versie
1. Log in op PLESK en open je website.
2. Klik op **PHP** (of **PHP-instellingen**).
3. Kies **PHP 8.1 of hoger** en klik op **OK**.

## Stap 2 – Maak een database
1. Klik op **Databases** → **Database toevoegen**.
2. Vul in:
   - **Databasenaam:** bijvoorbeeld `packpoint` (PLESK plakt er soms iets voor, zoals `jouwnaam_packpoint`)
   - **Databasegebruiker** en **wachtwoord:** verzin zelf iets
3. Klik op **OK** en **schrijf die 3 dingen even op**: databasenaam, gebruiker en wachtwoord.

## Stap 3 – Tabellen en testdata erin zetten
1. Klik bij je nieuwe database op **phpMyAdmin**.
2. Klik links op je database.
3. Klik bovenaan op **Importeren**, kies `sql/schema.sql` en klik op **Starten**.
4. Links zie je nu 4 tabellen: `carriers`, `parcels`, `storage_slots` en `users`. Nice.

## Stap 4 – Bestanden uploaden
1. Klik op **Bestanden** (Bestandsbeheer) en open de map `httpdocs`.
2. Upload `packpoint.zip`, klik erop en kies **Uitpakken**.
3. In `httpdocs` staan nu de mappen `includes`, `public`, `sql` en `docs`.

## Stap 5 – Documentroot goed zetten (belangrijk!)
Alleen de map `public` mag zichtbaar zijn in de browser.

1. Ga naar **Hosting & DNS** → **Hosting** (of **Hosting-instellingen**).
2. Zet **Documentroot** op: `httpdocs/public`
3. Klik op **OK**.

Zo kan niemand via de browser bij `includes/config.php` of `sql/schema.sql`.

## Stap 6 – Databasegegevens invullen
1. Open in Bestandsbeheer het bestand `httpdocs/includes/config.php`.
2. Pas deze regels aan met wat je in stap 2 hebt opgeschreven:

   ```php
   const DB_HOST = 'localhost';
   const DB_NAME = 'jouwnaam_packpoint';   // jouw databasenaam
   const DB_USER = 'jouw_db_gebruiker';    // jouw databasegebruiker
   const DB_PASS = 'jouw_wachtwoord';      // jouw databasewachtwoord
   ```
3. Klik op **Opslaan**.

> ⚠️ Zet dat wachtwoord **nooit** op GitHub. Pas het alleen aan in het bestand op de server.

## Stap 7 – Testen
1. Ga naar `https://jouw.website`.
2. Zie je de loginpagina? Top. Log in met een testaccount:

   | Wie             | Gebruikersnaam | Wachtwoord |
   |-----------------|----------------|------------|
   | Klant           | `klant01`      | `klant123` |
   | Baliemedewerker | `balie01`      | `balie123` |
   | Admin           | `admin01`      | `admin123` |

---

## Werkt het niet?

| Wat zie je?                              | Fix                                                              |
|------------------------------------------|------------------------------------------------------------------|
| "Database niet bereikbaar"               | Kijk stap 6 nog even na. En heb je stap 3 gedaan?                  |
| Een lijst met mappen of "404 Not Found"  | De documentroot staat niet op `httpdocs/public` (stap 5).        |
| Witte pagina of een PHP-fout             | Je PHP-versie is te oud (stap 1).                                |
| Pagina zonder opmaak (alleen tekst)      | Tailwind komt via internet. Ververs de pagina even.              |
