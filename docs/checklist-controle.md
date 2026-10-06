# Checklist-check PackPoint ✅

Per checklistpunt: klopt het, waar zie je dat, en moet je zelf nog iets doen?

✅ = klopt ⚠️ = bijna, nog even iets doen ❌ = klopt (nog) niet

---

### 1. Genoeg geplande functies gebouwd ✅
Alles uit de briefing zit erin, plus de admin-dingen uit de rollenlijst.
**Bewijs:** de koppeltabel in [eisen-en-verschillen.md](eisen-en-verschillen.md) (F01 t/m F16).

### 2. Bij elke functie is duidelijk bij welke eis, welk ontwerp en welke taak hij hoort ⚠️
De koppeltabel koppelt elke functie al aan een eis en een stukje van het ontwerp.
**Nog doen:** vul in de kolom *Taak* de nummers uit je eigen planning in.

### 3. De software start en werkt (+ demofilmpje) ⚠️
- Lokaal: check de [README](../README.md) → *Zo start je hem op*.
- Online: check [installatie-plesk.md](installatie-plesk.md).

**Nog doen:** zet de app op je PLESK-site en neem het filmpje op met het
[demo-draaiboek](demo-draaiboek.md). Hou het onder de **3 minuten en 200 MB**
(dat is strenger dan de checklist, dus dan zit je altijd goed).

### 4. Alles werkt zoals de eisen zeggen ✅
Getest met 73 automatische checks, en ze slagen allemaal. Onder andere getest:
- inloggen per rol, fout wachtwoord, niet bij pagina's van een andere rol komen
- pakket registreren (verwacht en binnen), dubbele barcode, bezet vak, foute invoer
- ontvangst doen, verplaatsen, uitgeven met goede en foute afhaalcode, retour
- zoeken op code, klant, vak en status, lege zoekresultaten
- tijdlijn van de klant, verborgen afhaalcode bij verwachte pakketten, geschiedenis
- gebruikers, vakken en vervoerders beheren
- alle 16 pagina's op telefoon (375px) en computer

### 5. Verschillen tussen eisen, ontwerp en product zijn uitgelegd ✅
**Bewijs:** [eisen-en-verschillen.md](eisen-en-verschillen.md), hoofdstuk 2 (V1 t/m V7 en de bekende beperkingen).

### 6. De code is logisch ingedeeld, met duidelijke en vaste namen ✅
- Mappen: `includes/` (gedeelde code), `public/` (pagina's per rol: `customer/`, `employee/`, `admin/`), `sql/`, `docs/`.
- Functies hebben altijd een Engelse naam die zegt wat ze doen:
  PHP in `snake_case` (`find_parcel()`, `register_parcel()`, `is_slot_free()`),
  JavaScript in `camelCase` (`toggleMenu()`, `fillTestAccount()`, `toggleSlotChoice()`).
- Variabelen en comments zijn altijd Nederlands: `$pakket`, `$vrije_vakken`.

**Bewijs:** [README](../README.md) → *Wat staat waar?*

### 7. Duidelijke onderdelen, zo min mogelijk dubbele code ✅
- Elke pagina laadt alles met 1 regel: `includes/init.php`.
- Boven- en onderkant van elke pagina: `header.php` en `footer.php`.
- Alle SQL staat op één plek: `includes/functions.php`, verdeeld in DEEL 1 t/m 5.
- De JOIN voor pakketten staat er maar 1 keer in (`PARCEL_SELECT`).
- Uitgeven en retour gebruiken dezelfde functie (`finish_parcel()`).

### 8. Invoer wordt gecheckt, fouten netjes afgehandeld, data goed opgeslagen ✅
- **Invoer:** elk formulier wordt op de server gecheckt (`validate_contact_details()`, `validate_parcel_input()`, `validate_new_password()`).
- **Fouten:** duidelijke meldingen via `set_flash()`. Gaat er onverwacht iets mis? Dan zie je "Er ging iets mis" (`show_error_page()` in `init.php`).
- **Opslaan:** foreign keys en `UNIQUE` in de database. Pakket en vak worden samen opgeslagen in een transactie.

### 9. De software is goed beveiligd ✅
| Wat de checklist vraagt                  | Hoe het geregeld is                                              |
|------------------------------------------|------------------------------------------------------------------|
| Wachtwoorden veilig opslaan              | `password_hash()` (bcrypt)                                       |
| Beschermd tegen injectie                 | Alleen prepared statements met `?` (SQL) en `h()` (XSS)          |
| Geen toegang zonder toestemming          | `require_role()` op elke pagina                                  |
| Geen data aanpassen door onbevoegden     | Rolcheck + CSRF-code in elk formulier                            |

### 10. Alle code in één centrale repository, definitieve versie is duidelijk ✅
- Alles staat op GitHub: <https://github.com/ObiOnuorah-hub/packpoint> (publiek).
- De definitieve versie is de tag **`v1.1`** op branch **`main`**.

### 11. Commits verspreid over de hele projectperiode ❌
De Git-repository is pas op **6 oktober 2026** gemaakt. Daarvoor is er zonder Git gewerkt.
Nep-commits met oude datums maken zou vals spelen zijn, dus dat is niet gedaan.

**Nog doen:**
- Commit vanaf nu elke wijziging apart, met een duidelijk bericht.
- Leg in je verslag eerlijk uit dat je eerst zonder Git werkte en wanneer je bent overgestapt.

### 12. Duidelijke commitberichten, branches en merges ✅
- Elke commit heeft een titel en een korte uitleg.
- De documentatie is gemaakt op een eigen branch (`documentatie`) en daarna gemerged in `main`.

**Bewijs:** op GitHub bij *Commits*, in GitHub Desktop bij *History*, of met `git log --graph --oneline`.
