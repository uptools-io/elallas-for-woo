# Elállás for WooCommerce

Ingyenes, GPL-2.0 licencű WooCommerce bővítmény, amely biztosítja a kötelező **online elállási funkciót** (a fogyasztó ezzel jelezheti a távollévők között kötött szerződéstől való elállási szándékát), a kereskedőnek pedig naplózott, rendeléshez kötött, auditálható ügykezelést ad. Az 1.1.0 óta a **harmonizált uniós szavatossági tájékoztatót** és a **GARAN címkét** is megjeleníti (kötelező 2026. szeptember 27-től). Ez nem „csak egy gomb": minden nyilatkozat egy igazolható, időbélyegzett üggyé válik, amelyet a kereskedő végig adminisztrálhat. HPOS-kompatibilis.

## Jogi alap

A funkció a **Directive (EU) 2023/2673** irányelv (amely a 2011/83/EU fogyasztói jogi irányelvet módosítja), Magyarországon pedig a **415/2025. (XII. 23.) Korm. rendelet** (a 45/2014. (II. 26.) Korm. rendelet módosítása) követelményeit valósítja meg. Ezek **2026. június 19-től** alkalmazandók, és előírják, hogy az online értékesítők könnyen elérhető elektronikus elállási funkciót biztosítsanak, és minden nyilatkozatot tartós adathordozón visszaigazoljanak.

**2026. szeptember 27-től** a fogyasztóknak árut értékesítő webshopoknak a **(EU) 2025/1960 végrehajtási rendelet** (a (EU) 2024/825 irányelv alapján) kötelező formájában meg kell jeleníteniük a **harmonizált tájékoztatót a jogszabályi szavatosságról**, és ha a gyártó 2 évnél hosszabb tartóssági jótállást vállal, a **harmonizált GARAN címkét** is. Magyarországon ezt a 116/2026. (VII. 30.) Korm. rendelet vezeti be a 45/2014. (II. 26.) Korm. rendeletbe: a tájékoztató és a címke „jól láthatóan” jelenjen meg (11. § (1a)), a tartóssági jótállásra pedig közvetlenül a megrendelés előtt fel kell hívni a figyelmet (15. § (1)).

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
- **B2B-felismerés** és **elállási kivételek termék, kategória és címke szerint** (terméken, vagy a kategória/címke szerkesztő oldalán állítva; jelöl, sosem blokkol automatikusan).
- **Beüzemelő varázsló** — webshop-adatok, a `/elallas/` oldal automatikus létrehozása, megjelenítési kapcsolók, határidő és egy teszt lépés.
- **Gutenberg blokk és Elementor widget** — az elállási űrlap bárhova beilleszthető; az `[elallas_form]` shortcode az univerzális tartalék.
- **Többnyelvű** — WPML / Polylang / TranslatePress integráció; a jogi szövegek nyelvenként kezelhetők.
- **REST API** — `elallas-for-woo/v1` végpontok (azonosítás, ügyek, megerősítés, státusz, dokumentum) nonce-szal + rate limittel a publikus, és `manage_woocommerce` ellenőrzéssel az admin útvonalakon.
- **Számlázási és szállítási integrációk** — Számlázz.hu / Billingo / NAV ÁFA-felismerés (rendelés-jegyzetek + hookok, automatikus storno nélkül) és futár kézbesítési dátum (GLS, Packeta/Foxpost, MPL, DPD, Shipment Tracking).
- **LW Site Manager abilities** — ügyek listázása/lekérése, státuszváltás és az audit log olvasása a WordPress Abilities API-n keresztül (AI/REST ügynököknek).
- **Harmonizált szavatossági tájékoztató** — a Bizottság hivatalos, módosítatlan tájékoztatója az oldal nyelvén (24 hivatalos nyelv, magyar tartalék), mellette a QR-kóddal azonos Your Europe link. Rövid feliratról nyílik (kattintás / hover), vagy beágyazva jelenik meg. Helyek: termékoldal (a kosárba gomb alatt), fejléc, lábléc, a rendelés gomb előtt (klasszikus és blokkos pénztár, „Rendelés kifizetése” oldal), rendelésnézet (köszönőoldal, Fiókom) és a vásárlói rendelési e-mailek (kép, színes PNG-csatolmány vagy mindkettő). Egy kattintással önálló tájékoztató oldal (`/szavatossag/`) is létrehozható.
- **GARAN címke** — a hivatalos címke kitöltve az időtartammal (egész év, 2-nél több), a gyártóval (Brand/Trademark) és a modellazonosítóval. Termékenként kapcsolható be a termékszerkesztőben (a bővítmény ellenőrzi, hogy az értékek elférnek-e a címke fix mezőiben, és előnézetet mutat). A variációk alapból öröklik a szülő adatait, de saját adatot is kaphatnak, vagy kikapcsolható rajtuk a címke; a termékoldalon a címke a kiválasztott variációt követi.
- **Kötelező GARAN a rendelés gomb előtt** — amíg a GARAN modul be van kapcsolva, az érintett tételek címkéi mindig megjelennek közvetlenül a rendelés / fizetés gomb előtt (klasszikus és blokkos pénztár, „Rendelés kifizetése” oldal). A termékoldali (beágyazott, teljes vagy a leírás alatti), kosár-, terméklista- és e-mail-megjelenés állítható. E-mailben a címke PNG-kép (ha a tárhelyen van GD FreeType-támogatás), alatta szöveges sor és linkek. A címke adatai a rendeléskor a rendelési tételre mentődnek, így a termék későbbi módosítása nem írja át a korábbi rendeléseket.
- **Shortcode-ok** — `[elallas_guarantee_notice]` (`label`, `mode="toggle|inline"`) és `[elallas_garan_label]` (`product_id`, `mode="nested|full"`) Elementor Pro-hoz, egyedi blokksablonokhoz vagy bármilyen más elhelyezéshez. Kikapcsolt modulnál semmit nem írnak ki.
- **Hatókör és B2B** — csak árukra vonatkozik: a tisztán virtuális (digitális) termékek kizárhatók, B2B rendeléseknél a megjelenés elrejthető.
- **Megfelelőség-ellenőrzés** — admin értesítés figyelmeztet, ha a tájékoztató ki van kapcsolva vagy nincs elhelyezve, a pénztári megjelenés ki van kapcsolva, a GARAN címke ki van kapcsolva, egy filter elrejtheti a kötelező GARAN-t, vagy a beállítások még nincsenek átnézve. A módosított hivatalos GARAN fájlokat a bővítmény felismeri (SHA-256), és ilyenkor nem jeleníti meg a címkét.
- **Blokktémák** — a termékoldali megjelenés a kosárba blokkot követi (`woocommerce/add-to-cart-form` vagy `woocommerce/add-to-cart-with-options`), és a klasszikus termék-sablon blokkal renderelt oldalon is működik.
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
| `elallas_case_status_changed` | `$case_id`, `$old_status`, `$new_status` | Egy ügy státusza megváltozik |

### Filterek

| Hook | Aláírás | Cél |
|------|---------|-----|
| `elallas_is_order_eligible` | `($eligible, $order)` | A rendelés jogosultságának felülbírálása |
| `elallas_deadline_days` | `($days, $order)` | Az elállási határidő felülbírálása (alap 14) |
| `elallas_pdf_html` | `($html, $context)` | A PDF-nyilatkozat HTML-jének szűrése |
| `elallas_delivery_date` | `($date, $order)` | Futár kézbesítési dátum megadása (a beépített szállítási integráció a gyakori futárokból / Shipment Trackingből oldja fel) |
| `elallas_is_order_b2b` | `($is_b2b, $order)` | A B2B-felismerés felülbírálása |
| `elallas_notice_visible` | `($visible, $context, $order)` | A szavatossági tájékoztató elrejtése adott helyen |
| `elallas_notice_language` | `($code, $context)` | A tájékoztató nyelvének felülbírálása (csak a 24 hivatalos nyelv egyike) |
| `elallas_notice_link_url` | `($url, $code)` | A tájékoztató melletti link (csak `https://*.europa.eu`) |
| `elallas_notice_image_url` | `($url, $code)` | A tájékoztató képének URL-je (csak azonos nevű hivatalos fájl, pl. CDN) |
| `elallas_compliance_applies_to_product` | `($applies, $product)` | Vonatkozik-e a tájékoztató / GARAN a termékre (alapból: áru) |
| `elallas_compliance_is_b2b` | `($is_b2b, $context)` | B2B-kontextus a tájékoztató / GARAN elrejtéséhez |
| `elallas_compliance_email_ids` | `($ids)` | Mely WooCommerce e-mailekbe kerüljön a tájékoztató / GARAN |
| `elallas_garan_data` | `($data, $product_id, $variation_id)` | A GARAN adatok felülbírálása (`GaranData` vagy `null`) |
| `elallas_garan_checkout_visible` | `($visible, $context, $order)` | A kötelező pénztári GARAN elrejtése (az admin figyelmeztet, ha használva van) |
| `elallas_garan_allow_half_years` | `($allow)` | Fél éves értékek engedélyezése (alap: `false`, jogi kérdés nyitott) |
| `elallas_garan_cart_priority` | `($priority)` | A kosár-GARAN prioritása a `woocommerce_proceed_to_checkout` hookon (alap 5, az expressz fizetés előtt) |
| `elallas_checkout_block_anchors` | `($selectors)` | CSS-szelektorok, amelyek elé a blokkos pénztárban a tájékoztató és a GARAN kerül |

A `elallas_compliance_boot` action a szavatossági modulok betöltése után fut.

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
- [ ] **Kivételek** — a nem visszaküldhető termékeknél jelöld be az *Elállásból kizárt* opciót a termék **Általános** fülén; egész csoport kizárásához szerkessz egy termék**kategóriát** vagy **címkét**, és ott jelöld be az *Elállásból kizárt*-ot (a termékszintű beállítás elsőbbséget élvez; jelöl, sosem blokkol automatikusan)

**Szavatosság és GARAN (2026. szeptember 27-től kötelező)**
- [ ] Nézd át a **Beállítások → Szavatosság és GARAN** fület (a tájékoztató és a GARAN alapból be van kapcsolva), és mentsd el
- [ ] A 2 évnél hosszabb gyártói tartóssági jótállású termékeknél töltsd ki a termékszerkesztőben a **Gyártói tartóssági jótállás (GARAN címke)** mezőit (időtartam, gyártó, modellazonosító); eltérő variációknál a variáció **GARAN címke** beállításában
- [ ] Ellenőrizd a termékoldalon a tájékoztatót és a címkét, a pénztárban pedig azt, hogy mindkettő a rendelés gomb előtt jelenik meg
- [ ] A GARAN csak a gyártó által vállalt, térítésmentes, az egész termékre kiterjedő, 2 évnél hosszabb tartóssági jótállásra használható (bolti garanciára, kötelező jótállásra és fizetős kiterjesztett garanciára nem)

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

## Harmadik féltől származó fájlok

- **Európai Bizottság** — a harmonizált tájékoztató (PNG és SVG, `assets/notice/`) és a GARAN címke (`assets/garan/`) a Bizottság által közzétett hivatalos fájlok bájtra azonos, módosítatlan másolata ([Practical guidelines and high-resolution vector files – EU notice and label for product guarantees](https://commission.europa.eu/publications/practical-guidelines-and-high-resolution-vector-files-eu-notice-and-label-product-guarantees_en)). Az `assets/garan/garan-base-colour@4x.png` build időben készül a hivatalos színes címkéből üres mezőkkel, az e-mail képhez. Az épséget az `assets/CHECKSUMS.sha256` ellenőrzi.
- **Inter 3.19** — a GARAN címke kitöltéséhez használt betűtípus (`assets/fonts/inter/`), SIL Open Font License 1.1 (`assets/fonts/inter/OFL.txt`).

## Ismert korlátok (1.1.0)

- A GARAN címke az e-mailben csak GD FreeType-támogatású tárhelyen jelenik meg képként; enélkül szövegként (évek, gyártó, modell) és linkként.
- A terméklistákon (archívum) a GARAN címke csak klasszikus témával jelenik meg; blokktémánál a termékoldalon, a kosárban és a pénztárban.
- A szoftverfrissítési és javíthatósági információt 1.1.1-ig a termékleírásban tüntesd fel.
- A szavatossági Gutenberg-blokk és Elementor-widget 1.1.1-ben jön; addig a Shortcode blokkal / widgettel helyezd el a shortcode-okat.
- A blokkos kosár expressz fizetési gombjai előtt a GARAN címke nem jelenik meg (a klasszikus kosárban igen).

## Nem jogi tanácsadás

A bővítmény minta jogi és üzenetszövegeket, valamint egy megfelelés-orientált folyamatot szállít, de nem minősül jogi tanácsadásnak. A jogi érték a szövegek és a folyamat naprakészen tartásában és validáltságában rejlik. Élesítés előtt a végleges szövegeket a saját ÁSZF-eddel és magyar e-commerce jogásszal kell validáltatni.

## Licenc

GPL-2.0-or-later. Lásd: [license.txt](license.txt).

---

Készítette az [uptools.io](https://uptools.io) — könnyűsúlyú WordPress bővítmények minimális footprinttel, felár és tracking nélkül.
