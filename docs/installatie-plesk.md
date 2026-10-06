# PackPoint installeren op PLESK

Met dit stappenplan zet je PackPoint online op je eigen PLESK-omgeving (jouw.website).

---

## Stap 1 – PHP-versie controleren
1. Log in op PLESK en open je website.
2. Klik op **PHP** (of **PHP-instellingen**).
3. Kies **PHP 8.1 of hoger** en klik op **OK**.

## Stap 2 – Database aanmaken
1. Klik op **Databases** → **Database toevoegen**.
2. Vul in:
   - **Databasenaam:** bijvoorbeeld `packpoint` (PLESK zet er soms iets voor, zoals `jouwnaam_packpoint`)
   - **Databasegebruiker** en **wachtwoord:** kies zelf
3. Klik op **OK** en **schrijf deze 3 gegevens op**: databasenaam, gebruiker en wachtwoord.

## Stap 3 – Tabellen en testgegevens importeren
1. Klik bij je nieuwe database op **phpMyAdmin**.
2. Klik links op je database.
3. Klik bovenaan op **Importeren**, kies `sql/schema.sql` en klik op **Starten**.
4. Links zie je nu 4 tabellen: `carriers`, `parcels`, `storage_slots` en `users`.

## Stap 4 – Bestanden uploaden
1. Klik op **Bestanden** (Bestandsbeheer) en open de map `httpdocs`.
2. Upload `packpoint.zip` en klik op de zip → **Uitpakken**.
3. In `httpdocs` staan nu de mappen `includes`, `public`, `sql` en `docs`.

## Stap 5 – Documentroot instellen (belangrijk!)
Alleen de map `public` mag via de browser bereikbaar zijn.

1. Ga naar **Hosting & DNS** → **Hosting** (of **Hosting-instellingen**).
2. Zet **Documentroot** op: `httpdocs/public`
3. Klik op **OK**.

Zo kan niemand via de browser bij `includes/config.php` of `sql/schema.sql`.

## Stap 6 – Databasegegevens invullen
1. Open in Bestandsbeheer het bestand `httpdocs/includes/config.php`.
2. Pas deze regels aan met de gegevens uit stap 2:

   ```php
   const DB_HOST = 'localhost';
   const DB_NAME = 'jouwnaam_packpoint';   // jouw databasenaam
   const DB_USER = 'jouw_db_gebruiker';    // jouw databasegebruiker
   const DB_PASS = 'jouw_wachtwoord';      // jouw databasewachtwoord
   ```
3. Klik op **Opslaan**.

> ⚠️ Zet dit wachtwoord **nooit** in GitHub. Pas het alleen aan in het bestand op de server.

## Stap 7 – Testen
1. Open `https://jouw.website`.
2. Je ziet de loginpagina. Log in met een testaccount:

   | Rol             | Gebruikersnaam | Wachtwoord |
   |-----------------|----------------|------------|
   | Klant           | `klant01`      | `klant123` |
   | Baliemedewerker | `balie01`      | `balie123` |
   | Admin           | `admin01`      | `admin123` |

---

## Werkt het niet?

| Wat zie je?                              | Oplossing                                                        |
|------------------------------------------|------------------------------------------------------------------|
| "Database niet bereikbaar"               | Klopt alles in stap 6? Is de database geïmporteerd (stap 3)?     |
| Een lijst met mappen of "404 Not Found"  | De documentroot staat niet op `httpdocs/public` (stap 5).        |
| Een witte pagina of PHP-fout             | De PHP-versie is te oud (stap 1).                                |
| Pagina zonder opmaak (alleen tekst)      | Tailwind CSS wordt via internet geladen. Ververs de pagina.      |
