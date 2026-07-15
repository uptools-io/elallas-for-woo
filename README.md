# Elállás for WooCommerce

Ingyenes, GPL-2.0 licencű WooCommerce bővítmény, amely biztosítja a kötelező **online elállási funkciót** (a fogyasztó ezzel jelezheti a távollévők között kötött szerződéstől való elállási szándékát), a kereskedőnek pedig naplózott, rendeléshez kötött, auditálható ügykezelést ad. Ez nem „csak egy gomb": minden nyilatkozat egy igazolható, időbélyegzett üggyé válik, amelyet a kereskedő végig adminisztrálhat. HPOS-kompatibilis.

## Jogi alap

A funkció a **Directive (EU) 2023/2673** irányelv (amely a 2011/83/EU fogyasztói jogi irányelvet módosítja), Magyarországon pedig a **415/2025. (XII. 23.) Korm. rendelet** (a 45/2014. (II. 26.) Korm. rendelet módosítása) követelményeit valósítja meg. Ezek **2026. június 19-től** alkalmazandók, és előírják, hogy az online értékesítők könnyen elérhető elektronikus elállási funkciót biztosítsanak, és minden nyilatkozatot tartós adathordozón visszaigazoljanak.

> **Ez nem jogi tanácsadás.** A bővítményhez mellékelt jogi szövegek csak minták. Élesítés előtt a végleges szövegeket a saját ÁSZF-eddel összhangban, magyar e-commerce jogásszal kell validáltatni.

## Funkciók

- **Online elállási oldal és gomb** — alapértelmezett felirat: `Elállás a szerződéstől`; önálló oldal, `[elallas_form]` shortcode, Gutenberg blokk / Elementor widget, Fiókom-végpont és rendelés-oldali gomb (két kattintáson belül elérhető).
- **Vendégbarát azonosítás** — fiók nélkül is működik. Belépett vásárlónál az e-mail előre kitöltött, és a saját jogosult rendelései gyorsválasztóból választhatók; a rendelésszám mindig kézzel is megadható, így egy vendégként, más e-mail címmel leadott rendelés is azonosítható. A `?order=ID` paraméterrel megnyitott űrlap előválasztja az adott rendelést. Belépett vásárló nem műveletezhet másik fiók rendelésével.
- **Önkiszolgálás a Fiókom oldalon** — a vásárló látja a korábbi elállási ügyeit, és token-védett linken letöltheti a saját elállási nyilatkozat PDF-jét.
- **Kétlépcsős folyamat** — a nyilatkozat kitöltése az elektronikus felületen, majd külön `Elállás megerősítése` lépés, kifejezett adat/szándék/hozzájárulás pipákkal. Opcionális visszatérítési **bankszámla / IBAN** (titkosítva tárolva) és szabad szöveges megjegyzés.
- **Tartós adathordozós e-mail** — automatikus visszaigazolás az elállás adataival és a pontos beérkezési időponttal, opcionális PDF-csatolmánnyal és a vásárlói e-mailhez fűzhető szerkeszthető extra szöveggel.
- **Teljes / részleges / tételenkénti / mennyiségenkénti elállás.**
- **Határidő-jelölés, sosem blokkol** — a 14 napos ablakot kiszámolja és jelöli (határidőn belül / lejárt / nem megállapítható); a végső döntés a kereskedőé.
- **Rendelés-pillanatkép** — a nevek, SKU-k, mennyiségek és összegek a beküldés pillanatában rögzülnek, így az ügy a termék/ár későbbi változása után is rekonstruálható.
- **Audit log** — append-only eseménynapló (ki, mikor, mit).
- **Ügykezelő admin** — szűrhető ügylista és részletes ügynézet (összefoglaló a visszatérítési bankszámlával, nyilatkozat, rendelés-pillanatkép, audit log, admin döntés, dokumentumok) a WooCommerce alatt.
- **CSV export** és **PDF elállási nyilatkozat** (dompdf, SHA-256 hash, védett, token-védett letöltés).
- **Semleges azonosítás** — hibás rendelésszám vagy e-mail ugyanazt a semleges üzenetet adja, megakadályozva a próbálgatást.
- **Adatvédelmi vezérlők** — IP/UA teljes/hash/kikapcsolva, e-mail hash-elés és opcionális titkosítás, titkosított bankszámla, állítható megőrzés ütemezett anonimizálással.
- **B2B-felismerés** és **elállási kivételek termék, kategória és címke szerint** (terméken, vagy a kategória/címke szerkesztő oldalán állítva). A kizárt termékre a vásárló **nem indíthat elállást** (az ok megjelenik; a rendelés többi tétele továbbra is igényelhető), és ha minden tétel kizárt, az egész kérelem blokkolva van. A viselkedés az `elallas_enforce_exclusion` filterrel kikapcsolható (visszaáll a korábbi „csak jelöl" módra).
- **Beüzemelő varázsló** — webshop-adatok, a `/elallas/` oldal automatikus létrehozása, megjelenítési kapcsolók, határidő és egy teszt lépés.
- **Gutenberg blokk és Elementor widget** — az elállási űrlap bárhova beilleszthető; az `[elallas_form]` shortcode az univerzális tartalék.
- **Többnyelvű** — beépített fordítások: **magyar** (forrás), **angol**, **román**, **cseh**, **szlovák** (admin + frontend, `.po`/`.mo` a `/languages` alatt). Ezen felül WPML / Polylang / TranslatePress integráció az adminból mentett dinamikus szövegekhez (futásidejű fordítás).
- **REST API** — `elallas-for-woo/v1` végpontok (azonosítás, ügyek, megerősítés, státusz, dokumentum) nonce-szal + rate limittel a publikus, és `manage_woocommerce` ellenőrzéssel az admin útvonalakon.
- **Számlázási és szállítási integrációk** — Számlázz.hu / Billingo / NAV ÁFA-felismerés (rendelés-jegyzetek + hookok, automatikus storno nélkül) és futár kézbesítési dátum (GLS, Packeta/Foxpost, MPL, DPD, Shipment Tracking).
- **LW Site Manager abilities** — ügyek listázása/lekérése, státuszváltás és az audit log olvasása a WordPress Abilities API-n keresztül (AI/REST ügynököknek).
- **HPOS-kompatibilis** — deklarálja a kompatibilitást a WooCommerce egyedi rendelési tábláival.

## Követelmények

- WordPress 6.4+
- WooCommerce 8.0+
- PHP 8.0+ (8.2+ ajánlott)

## Telepítés

Composerrel:

```bash
composer require uptools-io/elallas-for-woo
```

Vagy töltsd le a release ZIP-et, és telepítsd a **Bővítmények → Új hozzáadása → Bővítmény feltöltése** menüben. A release ZIP a Composer-függőségeket is tartalmazza. Aktiválás után nyisd meg a **WooCommerce → Elállási ügyek → Beállítások** oldalt, futtasd a beüzemelő varázslót, és hozd létre a `/elallas/` elállási oldalt.

## Fejlesztői jegyzetek

### Action hookok

| Hook | Paraméterek | Mikor |
|------|-------------|-------|
| `elallas_case_created` | `$case_id`, `$order_id` | Egy elállási ügy létrejött |
| `elallas_case_confirmed` | `$case_id` | Egy ügyet megerősítettek a kétlépcsős flow-ban |
| `elallas_case_status_changed` | `$case_id`, `$old_status`, `$new_status`, `$message` | Egy ügy státusza megváltozik (`$message` = opcionális, vevőnek szóló üzenet) |

### Filterek

| Hook | Aláírás | Cél |
|------|---------|-----|
| `elallas_is_order_eligible` | `($eligible, $order)` | A rendelés jogosultságának felülbírálása |
| `elallas_deadline_days` | `($days, $order)` | Az elállási határidő felülbírálása (alap 14) |
| `elallas_pdf_html` | `($html, $context)` | A PDF-nyilatkozat HTML-jének szűrése |
| `elallas_delivery_date` | `($date, $order)` | Futár kézbesítési dátum megadása (a beépített szállítási integráció a gyakori futárokból / Shipment Trackingből oldja fel) |
| `elallas_is_order_b2b` | `($is_b2b, $order)` | A B2B-felismerés felülbírálása |
| `elallas_enforce_exclusion` | `($enforced, $order)` | Kizárt tétel blokkoljon (alap `true`), vagy csak jelöljön (`false`) |
| `elallas_product_exclusion` | `([$excluded, $reason], $product_id)` | Per-termék kizárás felülbírálása (dinamikus) |
| `elallas_exclusion_reason_message` | `($message, $excepted, $order)` | Az automatikus elutasító e-mail szövege |
| `elallas_policy_url` | `($url)` | A tájékoztató/ÁSZF link URL-je (pl. nyelvenként) |

A határidő kezdetét a kézbesítési dátum is vezérelheti. A beépített szállítási
integráció ezt az `elallas_delivery_date` filteren keresztül oldja fel (a GLS /
Packeta / Foxpost / MPL / DPD / WooCommerce Shipment Tracking metákat olvasva), és
a `_lw_elallas_delivery_date` rendelés-metában is eltárolja.

### WP-CLI

```bash
wp elallas list [--status=<status>] [--deadline=<deadline>] [--format=<table|csv|json|count>]
wp elallas get <id>
wp elallas status <id> <status>
wp elallas stats
wp elallas pdf <id>
wp elallas cleanup
```

## Dokumentáció

- [Kezelési útmutató](docs/kezelesi-utmutato.md) — telepítés, beüzemelés, beállítások, vásárlói és admin folyamatok.
- [Ügy-státuszok és e-mailek](docs/statuszok-es-emailek.md) — melyik státusz mit jelent, mit tartalmaz, és melyik e-mail mikor megy ki.
- [Fejlesztői referencia](docs/fejlesztoi-referencia.md) — hookok, REST API, WP-CLI, abilities, sablonok, adatmodell.

## Beüzemelési checklist (új telepítés)

Friss oldalon az aktiválás után egyszer érdemes végigmenni rajta.

**Előfeltételek**
- [ ] WooCommerce 8.0+ telepítve és aktív
- [ ] PHP 8.0+ (8.2+ ajánlott); az `AUTH_KEY` / `AUTH_SALT` beállítva a `wp-config.php`-ban (ezekből származnak a PII titkosítási kulcsok)

**Létrehozás és engedélyezés**
- [ ] Futtasd a beüzemelő varázslót: **WooCommerce → Elállási ügyek → Beállítások**
- [ ] Hozd létre a publikus elállási oldalt (`/elallas/`) — a varázsló beilleszti az `[elallas_form]` shortcode-ot, és beállítja *megjelenítési oldalként* (vagy hozz létre egy oldalt kézzel, és válaszd ki a **Beállítások → Általános** alatt)
- [ ] Kapcsold be az **Engedélyezés** főkapcsolót a **Beállítások → Általános** alatt

**Beállítás (Beállítások fülek)**
- [ ] **Általános** — erősítsd meg a gomb feliratát (`Elállás a szerződéstől`), és válaszd ki a megjelenítési felületeket (Fiókom / rendelés részletei / rendelési e-mail). A linket bárhová máshová az `[elallas_form]`/`[elallas_button]` shortcode-dal, a Gutenberg blokkal, az Elementor widgettel vagy egy menüponttal teheted ki.
- [ ] **Határidő** — állítsd be az elállási ablakot (alap 14 nap), a kezdő dátumot (rendelés / teljesítés / kézbesítés) és a lejárt kérések kezelését
- [ ] **Státuszok** — válaszd ki, mely rendelési státuszoknál indítható elállás; opcionálisan engedélyezd az egyedi `wc-withdrawal-*` rendelési státuszokat
- [ ] **Adatvédelem** — IP/UA tárolás (teljes / hash / kikapcsolva), e-mail titkosítás és megőrzési idő (a napi cron anonimizálja a régebbi ügyeket)
- [ ] **E-mailek** — engedélyezd a vásárlói / admin / státusz e-maileket, és add meg az admin címzettet
- [ ] **Jogi szövegek** — nézd át a nyilatkozat és visszaigazoló szövegeket, és **validáltasd jogásszal** (lásd lent)
- [ ] **Kivételek** — a nem visszaküldhető termékeknél jelöld be az *Elállásból kizárt* opciót a termék **Általános** fülén; egész csoport kizárásához szerkessz egy termék**kategóriát** vagy **címkét**, és ott jelöld be az *Elállásból kizárt*-ot (a termékszintű beállítás elsőbbséget élvez; a kizárt termékre a vásárló nem tud elállást igényelni, a rendelés többi tételére viszont igen)

**Elérhetőség ellenőrzése (jogszabályi követelmény)**
- [ ] Az elállási funkció **≤ 2 kattintással** elérhető a vásárló fiók / rendelés oldaláról

**Teszt élesítés előtt**
- [ ] Adj le egy teszt rendelést, és állítsd jogosult státuszba (pl. *teljesítve*)
- [ ] Küldj be egy elállást a `/elallas/`-on (rendelésszám + e-mail → tételek kiválasztása → 3 pipa → megerősítés)
- [ ] Ellenőrizd, hogy az ügy megjelenik a **WooCommerce → Elállási ügyek** alatt, a visszaigazoló e-mail kimegy (tartós adathordozó), és a PDF elkészül
- [ ] Próbálj hibás e-mailt → a semleges „nem található / nem jogosult" üzenetet kell kapnod (mező-szintű információ nélkül)

**Éles üzemeltetési jegyzetek**
- [ ] **Nginx / LiteSpeed** alatt az `uploads/elallas-docs/`-ban lévő `.htaccess` figyelmen kívül marad — a PDF-ek kitalálhatatlan fájlnevet + token-védett letöltést használnak, de érdemes egy szerver oldali `location` deny szabályt is hozzáadni az `uploads/elallas-docs/`-ra
- [ ] Verzióemeléskor frissítsd **minden** helyen: `elallas-for-woo.php` (fejléc + `ELALLAS_FOR_WOO_VERSION`), `readme.txt` (Stable tag + Changelog), `CHANGELOG.md` — majd push a `main`-re (a gated Release workflow PHP 8.0-n validál, és csak siker esetén ad ki buildet)

## Nem jogi tanácsadás

A bővítmény minta jogi és üzenetszövegeket, valamint egy megfelelés-orientált folyamatot szállít, de nem minősül jogi tanácsadásnak. A jogi érték a szövegek és a folyamat naprakészen tartásában és validáltságában rejlik. Élesítés előtt a végleges szövegeket a saját ÁSZF-eddel és magyar e-commerce jogásszal kell validáltatni.

## Licenc

GPL-2.0-or-later. Lásd: [license.txt](license.txt).

---

Készítette az [uptools.io](https://uptools.io) — könnyűsúlyú WordPress bővítmények minimális footprinttel, felár és tracking nélkül.
