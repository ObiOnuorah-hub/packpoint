# Checklist-controle PackPoint

Per checklistpunt: voldoet het, waar staat het bewijs en wat moet je zelf nog doen.

✅ = voldoet ⚠️ = bijna, jij moet nog iets doen ❌ = voldoet niet

---

### 1. Er zijn genoeg geplande functies gebouwd ✅
Alle functies uit de briefing zijn gebouwd, plus de admin-functies uit de rollenlijst.
**Bewijs:** de koppeltabel in [eisen-en-verschillen.md](eisen-en-verschillen.md) (F01 t/m F16).

### 2. Bij elke functie is duidelijk bij welke eis, ontwerp en taak hij hoort ⚠️
De koppeltabel koppelt elke functie aan een eis en een onderdeel van het ontwerp.
**Zelf doen:** vul in de kolom *Taak* de taaknummers uit je eigen planning in.

### 3. De software kan worden gestart en gebruikt (+ demofilmpje) ⚠️
- Lokaal: zie [README.md](../README.md) → *Starten*.
- Online: zie [installatie-plesk.md](installatie-plesk.md).

**Zelf doen:** zet de app op je PLESK-omgeving en neem het filmpje op. Gebruik daarvoor het
[demo-draaiboek.md](demo-draaiboek.md). Houd het binnen de limiet van de inleveropdracht:
**max. 3 minuten en max. 200 MB** (strenger dan de checklist, dus dan zit je altijd goed).

### 4. De functies werken volgens de eisen ✅
Alle functies zijn getest met 73 automatische controles. Alle controles slagen. Getest is onder andere:
- inloggen per rol, fout wachtwoord, geen toegang tot pagina's van een andere rol
- pakket registreren (verwacht en binnen), dubbele barcode, bezet vak, ongeldige invoer
- ontvangst registreren, verplaatsen, uitgeven met goede en foute afhaalcode, retour
- zoeken op code, klant, vak en status, lege zoekresultaten
- klanttijdlijn, verborgen afhaalcode bij verwachte pakketten, geschiedenis
- gebruikers, opslagvakken en vervoerders beheren
- alle 16 pagina's op telefoon (375px) en computer

### 5. Verschillen tussen eisen, ontwerp en product zijn beschreven ✅
**Bewijs:** [eisen-en-verschillen.md](eisen-en-verschillen.md), hoofdstuk 2 (V1 t/m V7 en bekende beperkingen).

### 6. De code is logisch ingedeeld met duidelijke, consistente namen ✅
- Mappen: `includes/` (gedeelde code), `public/` (pagina's per rol: `customer/`, `employee/`, `admin/`), `sql/`, `docs/`.
- Functies hebben altijd Engelse namen die zeggen wat ze doen:
  PHP in `snake_case` (`find_parcel()`, `register_parcel()`, `is_slot_free()`),
  JavaScript in `camelCase` (`toggleMenu()`, `fillTestAccount()`, `toggleSlotChoice()`).
- Variabelen en commentaar zijn altijd in het Nederlands: `$pakket`, `$vrije_vakken`.

**Bewijs:** [README.md](../README.md) → *Mappenstructuur*.

### 7. De code is verdeeld in onderdelen met weinig dubbele code ✅
- Elke pagina laadt alles met 1 regel: `includes/init.php`.
- Bovenkant en onderkant van elke pagina: `header.php` en `footer.php`.
- Alle SQL staat op één plek: `includes/functions.php`, verdeeld in DEEL 1 t/m 5.
- De JOIN voor pakketten staat maar 1 keer (`PARCEL_SELECT`).
- Uitgeven en retour delen dezelfde functie (`finish_parcel()`).

### 8. Invoer wordt gecontroleerd, fouten worden afgehandeld, gegevens worden betrouwbaar opgeslagen ✅
- **Invoer:** elk formulier wordt op de server gecontroleerd (`validate_contact_details()`, `validate_parcel_input()`, `validate_new_password()`).
- **Fouten:** duidelijke meldingen via `set_flash()`. Een onverwachte fout toont "Er ging iets mis" (`show_error_page()` in `init.php`).
- **Opslag:** foreign keys en `UNIQUE` in de database. Pakket en vak worden samen opgeslagen in een transactie.

### 9. De software is passend beveiligd ✅
| Eis                                     | Oplossing                                                         |
|-----------------------------------------|-------------------------------------------------------------------|
| Wachtwoorden veilig opslaan             | `password_hash()` (bcrypt)                                        |
| Bescherming tegen injectie              | Alleen prepared statements met `?` (SQL) en `h()` (XSS)           |
| Geen toegang zonder toestemming         | `require_role()` op elke pagina                                   |
| Geen gegevens aanpassen door onbevoegden| Rolcontrole + CSRF-code in elk formulier                          |

### 10. Alle code staat in één centrale repository, de definitieve versie is duidelijk ⚠️
- De code staat in een Git-repository. De definitieve versie is de tag **`v1.0`** op branch **`main`**.

**Zelf doen:** zet de repository **publiek** op GitHub en zet de link in `github.txt`.
Met GitHub Desktop: *File → Add local repository* → kies de map → *Publish repository*
→ vink **Keep this code private** UIT → *Publish*.

### 11. De commits zijn verspreid over de hele projectperiode ❌
De Git-repository is pas op **6 oktober 2026** aangemaakt. Het werk daarvoor is zonder Git gedaan.
Oude commits met oude datums maken zou het werk vervalsen, dus dat is niet gedaan.

**Zelf doen:**
- Commit vanaf nu elke wijziging apart, met een duidelijk bericht.
- Leg in je verslag eerlijk uit dat je eerst zonder Git werkte en wanneer je bent overgestapt.

### 12. Duidelijke commitberichten, branches en merges ✅
- Elke commit heeft een titel en een korte uitleg.
- De documentatie is gemaakt op een aparte branch (`documentatie`) en daarna gemerged in `main`.

**Bewijs:** in GitHub Desktop → *History*, of met `git log --graph --oneline`.
