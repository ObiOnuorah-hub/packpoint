# Draaiboek demofilmpje (max. 3 minuten, max. 200 MB)

## Voorbereiden
1. **Zet de database terug naar de testgegevens:** maak de database leeg en importeer `sql/schema.sql` opnieuw.
2. **Open de app** (op je PLESK-website, of lokaal via `Start-PackPoint.bat`).
3. **Opnemen:** gebruik de Xbox Game Bar (**Windows + Alt + R** start en stopt de opname) of OBS.
4. **Oefen 1 keer** met de klok erbij.

---

## Script

| Tijd        | Wat laat je zien                                                                                     | Wat zeg je (ongeveer)                                              |
|-------------|------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------|
| 0:00 – 0:15 | Loginpagina                                                                                          | "Dit is PackPoint. Je ziet geen menu zolang je niet bent ingelogd." |
| 0:15 – 0:35 | Log in als **balie01**. Typ `PK-7X9B` in de zoekbalk. Zet daarna het statusfilter op **Verwacht**.   | "De medewerker zoekt op code, klant, vak en status."              |
| 0:35 – 1:00 | **+ Pakket Registreren**: kies *Binnengekomen*, een vervoerder, barcode, klant en vak **A-03** → opslaan. | "Het systeem maakt een unieke afhaalcode, die is niet te raden."   |
| 1:00 – 1:15 | Klik bij het UPS-pakket op **Ontvangen** → kies een vrij vak.                                        | "Een verwacht pakket komt binnen en krijgt een vrij vak."         |
| 1:15 – 1:25 | Open **Opslagvakken**.                                                                              | "In elk vak ligt maximaal één pakket."                            |
| 1:25 – 1:50 | **Uitgeven** bij `PK-7X9B`: typ eerst een **foute** code, daarna de **goede** code.                   | "Met een foute code wordt het pakket niet meegegeven."            |
| 1:50 – 2:00 | Open **Te Lang Liggen**.                                                                            | "Pakketten die te lang liggen, worden gesignaleerd."              |
| 2:00 – 2:30 | Log uit, log in als **klant01**. Laat de tijdlijn, afhaalcode, deadline en geschiedenis zien.        | "De klant ziet alleen zijn eigen pakketten."                      |
| 2:30 – 2:45 | Typ in de adresbalk `/admin/users.php`.                                                              | "Een klant mag niet op de admin-pagina: hij wordt teruggestuurd." |
| 2:45 – 3:00 | Log in als **admin01** → Gebruikersbeheer. Maak het browservenster smal (telefoonweergave).          | "De admin beheert gebruikers. De app werkt ook op een telefoon."   |

---

## Na het opnemen
- Is het filmpje **langer dan 3 minuten**? Knip het in met de Foto's-app of Clipchamp (Windows).
- Is het **groter dan 200 MB**? Exporteer het opnieuw in 720p.
