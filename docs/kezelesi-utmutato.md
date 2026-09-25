# Elállás for WooCommerce — Kezelési útmutató

Online elállási (visszalépési) funkció és naplózott ügykezelés WooCommerce webshopokhoz,
a **Directive (EU) 2023/2673** és a **415/2025. (XII. 23.) Korm. rendelet** (45/2014. (II. 26.)
Korm. rendelet módosítása, **2026. június 19-től** alkalmazandó) szerint. Az 1.1.0 óta a
**harmonizált uniós szavatossági tájékoztatót** és a **GARAN címkét** is megjeleníti (az
(EU) 2025/1960 végrehajtási rendelet és a 116/2026. (VII. 30.) Korm. rendelet szerint,
**2026. szeptember 27-től** kötelező) — lásd a [7. fejezetet](#7-szavatossági-tájékoztató-és-garan-címke).

> **Ez nem jogi tanácsadás.** A bővítmény mintaszövegeket és megfelelés-orientált folyamatot ad,
> de a végleges szövegeket a saját ÁSZF-eddel és magyar e-commerce jogásszal kell validáltatni.

---

## 1. Követelmények

| | |
|---|---|
| WordPress | 6.4+ |
| WooCommerce | 8.0+ (HPOS támogatott) |
| PHP | 8.0+ |

## 2. Telepítés

**ZIP-ből:** Bővítmények → Új hozzáadása → Bővítmény feltöltése → aktiválás. A release ZIP a
Composer-függőségeket (dompdf) is tartalmazza.

**Composer-rel:**

```bash
composer require uptools-io/elallas-for-woo
```

Aktiváláskor a bővítmény létrehozza a 4 adatbázis-táblát, a védett dokumentum-könyvtárat
(`wp-content/uploads/elallas-docs/`), és ütemez egy napi karbantartó cront.

## 3. Beüzemelés (wizard)

A **WooCommerce → Elállás – beállítások** oldalon és a beüzemelő varázslóban:

1. **Webshop adatok** – cégnév, székhely, e-mail, ÁSZF/adatkezelési URL.
2. **Elállási oldal létrehozása** – egy kattintással létrejön a `/elallas/` oldal a
   `[elallas_form]` shortcode-dal, és beállítódik a „Megjelenítési oldal".
3. **Megjelenítés** – hol jelenjen meg a gomb (Fiókom, rendelés-oldal, rendelési e-mail).
4. **Határidő** – alap 14 nap + a határidő kezdete.
5. **Teszt** – próba-nyilatkozat.

---

## 4. Beállítások (WooCommerce → Elállás – beállítások)

### Általános
- **Engedélyezés** – a teljes elállási funkció főkapcsolója. Kikapcsolva az űrlap, a gombok, a
  Fiókom-végpont és a REST sem működik.
- **Gomb felirata** – alapértelmezett: **„Elállás a szerződéstől"** (a jogszabályi szöveg).
- **Megerősítő gomb** – **„Elállás megerősítése"**.
- **Megjelenítés** – Fiókom / rendelés részletei / rendelési e-mail. (A linket bárhová máshová a `[elallas_form]`/`[elallas_button]` shortcode-dal, a Gutenberg blokkal, az Elementor widgettel vagy egy menüponttal teheted ki.)
- **Megjelenítési oldal** – a `[elallas_form]`-ot tartalmazó oldal.

### Határidő
- **Elállási határidő (nap)** – alap 14.
- **Határidő kezdete** – rendelés dátuma / teljesítés / kiszállítás (kézbesítési dátum) / manuális.
- **Lejárt határidő kezelése**
  - *Engedélyezett, figyelmeztetéssel* (alap): a lejárt ügy is beküldhető, „lejárt"-ként jelölve,
    manuális ellenőrzésre kerül.
  - *Tiltott*: a lejárt rendelés nem indíthat elállást.
  - *Admin jóváhagyáshoz kötött*: beküldhető, manuális ellenőrzésre kerül.

### Státuszok
- **Jogosult rendelési státuszok** – mely WooCommerce státuszoknál indítható elállás
  (alap: feldolgozás alatt, teljesítve).
- **Egyedi elállási rendelési státuszok** – ha bekapcsolod, a bővítmény regisztrálja a
  `wc-withdrawal-*` rendelési státuszokat, és az ügy státuszához igazítja a rendelés státuszát.

### Kivételek
**Termékszinten:** nyisd meg a terméket → **Termékadatok → Általános** → „Elállásból kizárt"
+ a kizárás indoka (bontatlan / egyedi / digitális / szolgáltatás / higiéniai / romlandó / zárt
csomagolás).

**Kategória vagy címke szerint:** nyisd meg szerkesztésre a kívánt **termékkategóriát**
(Termékek → Kategóriák) vagy **termékcímkét** (Termékek → Címkék), és jelöld be az
„Elállásból kizárt" lehetőséget az indokkal. Az adott kategóriába/címkébe tartozó termékek
automatikusan „kizárt"-ként jelölődnek. A termékszintű beállítás elsőbbséget élvez (a saját
indokával). A jelölés az ügy-pillanatképben és az admin ügynézetben jelenik meg — a kizárás
**jelöl, nem blokkol** (a végső döntés a kereskedőé).

### Dokumentumok
- **PDF generálás** – elállási nyilatkozat PDF (dompdf), SHA-256 hash-sel, védett könyvtárban.
- **Megőrzési idő (nap)** – lásd Adatvédelem.

### Adatvédelem (GDPR)
- **IP-cím / User agent tárolása** – teljes / hash / kikapcsolva.
- **E-mail titkosítás** – a vásárlói e-mail titkosítva tárolódik (kereséshez hash-elve is).
- **Bankszámla/IBAN** – ha a vásárló megadja, **mindig titkosítva** (AES-256-GCM) tárolódik, és az
  adatmegőrzési anonimizálás törli.
- **Adatmegőrzés (nap)** – 0 = örökre. Ha > 0, a napi karbantartó cron a megőrzési időn túli
  ügyek **személyes adatait anonimizálja** (e-mail, IP, user agent, megjegyzés, bankszámla
  törlése), de az ügy és az audit log megmarad. Kézzel is futtatható: `wp elallas cleanup`.

### E-mailek
- Vásárlói visszaigazoló (tartós adathordozó), admin értesítő, státusz-frissítés – külön
  ki/bekapcsolható; az admin értesítő címe megadható.
- **Vásárlói e-mail extra szöveg** – a visszaigazoló e-mail aljához fűzött szabad szöveg (pl.
  visszaküldési cím, ügyfélszolgálat). A tárgyat/fejlécet a WooCommerce → Beállítások → E-mailek
  alatt, a teljes sablont a témád `elallas-for-woo/` mappájában szabhatod testre.

### Jogi szövegek
- **Nyilatkozat szövege** és **visszaigazoló szöveg** – szabadon szerkeszthető; a beállítások
  tetején a jogi felelősség-kizáró figyelmeztetés.

### Szavatosság és GARAN
A harmonizált szavatossági tájékoztató és a GARAN címke beállításai — részletesen a
[7. fejezetben](#7-szavatossági-tájékoztató-és-garan-címke).

---

## 5. A vásárlói folyamat

A `/elallas/` oldal **regisztráció nélkül** is működik (a vendég vásárlók is használhatják):

1. **Azonosítás** – rendelési szám + e-mail. Hibás adatnál **semleges** üzenet (nem árulja el,
   melyik mező rossz) → nem lehet rendelési számokat próbálgatni.
   - **Belépett vásárlónak** az e-mail előre kitöltött, és a saját jogosult rendelései egy
     gyorsválasztó legördülőből választhatók (ami kitölti a rendelésszám mezőt). A rendelésszám
     mindig kézzel is megadható – így egy vendégként, más e-mail címmel leadott rendelés is
     azonosítható. Másik regisztrált fiók rendelése viszont nem indítható.
   - A `?order=ID` paraméterrel megnyitott űrlap automatikusan előválasztja az adott rendelést.
2. **Tételek kiválasztása** – teljes vagy részleges elállás, mennyiség szerint.
3. **Megerősítés** – összefoglaló + 3 nyilatkozat-pipa + **„Elállás megerősítése"** gomb.
   Opcionálisan megadható a **visszatérítési bankszámla/IBAN** is (titkosítva tárolódik, lásd
   Adatvédelem), valamint egy szabad szöveges megjegyzés.
4. **Visszaigazolás** – ügyszám (pl. `EL-2026-000001`) + a beérkezés időpontja; automatikus
   e-mail tartós adathordozón (opcionális PDF nyilatkozat-csatolmánnyal).

**Megjelenési felületek:** `/elallas/` oldal, `[elallas_form]` shortcode, Gutenberg „Elállási
űrlap" blokk, Elementor „Elállási űrlap" widget, Fiókom → Elállás (`/my-account/withdrawals/`),
rendelés-oldali gomb, rendelési e-mailbe ágyazott link.

A **Fiókom → Elállás** oldalon a vásárló a korábbi elállási ügyeit is látja, és — ha készült
PDF — token-védett linken **le is töltheti a saját elállási nyilatkozatát**.

---

## 6. Ügykezelés (WooCommerce → Elállási ügyek)

- **Ügylista** – ügyszám, rendelés, vásárló, státusz, típus, beérkezés, határidő-státusz, tételek.
  Szűrhető státusz/határidő/típus szerint és kereshető. Tömeges műveletek: státuszváltás, CSV export.
- **Ügy részletei** – összefoglaló (a megadott visszatérítési bankszámlával, ha van),
  vásárlói nyilatkozat, **rendelés-pillanatkép** (a beküldéskori adatok, akkor is, ha a termék/ár
  később változik; a kizárt tételek „kizárt"-ként jelölve), **audit log** (ki, mikor, mit), admin
  döntés (státuszváltás), dokumentumok (PDF letöltés token-védetten).

### Ügy-státuszok
`Beérkezett` → `Automatikusan visszaigazolva` / `Manuális ellenőrzés alatt` → `Elfogadva` /
`Elutasítva` → `Visszaküldésre vár` → `Áru beérkezett` → `Visszatérítés folyamatban` → `Lezárva`
(+ `Törölve / hibás beküldés`).

### Határidő-státusz (jelölés, sosem blokkol alapból)
`Határidőn belül` / `Határidőn túl – manuális ellenőrzést igényel` / `Nem megállapítható`.

---

## 7. Szavatossági tájékoztató és GARAN címke

**2026. szeptember 27-től** a fogyasztóknak árut értékesítő webshopoknak meg kell jeleníteniük a
**harmonizált tájékoztatót a jogszabályi szavatosságról**, és ha a gyártó 2 évnél hosszabb
tartóssági jótállást vállal, a **harmonizált GARAN címkét** is. A tájékoztatónak és a címkének
„jól láthatóan” kell megjelennie (45/2014. Korm. rendelet 11. § (1a)), a tartóssági jótállásra
pedig közvetlenül a megrendelés előtt kell felhívni a figyelmet (15. § (1)).

A hivatalos értesítés és címke grafikája **nem módosítható**: a bővítmény a Bizottság fájljait
változatlanul jeleníti meg. Az ÁSZF frissítése a te feladatod, az értesítés azt nem helyettesíti.

Frissítés vagy friss telepítés után nyisd meg a fület, nézd át, és **mentsd el** — amíg ezt nem
teszed meg, az admin figyelmeztetés jelzi, hogy a beállítások még nincsenek átnézve.

### 7.1. A „Szavatosság és GARAN” fül

**WooCommerce → Elállás – beállítások → Szavatosság és GARAN.** A fül tetején a jogi
felelősség-kizárás és az ismert korlátok listája látható. A beállítások sorrendben:

**Jogszabályi szavatosság tájékoztató**

- **Bekapcsolás** – „Harmonizált szavatossági értesítés megjelenítése” (alap: be).
- **Felirat** – a rövid felirat, amelyre kattintva / rámutatva a teljes hivatalos értesítés
  megjelenik. Üresen hagyva az alapértelmezett felirat jelenik meg („Az Ön jogszabályi
  szavatossági jogai”).
- **Megjelenítési helyek**
  - *Termékoldal* (alap: be) – a kosárba gomb alatt.
  - *Fejléc* (alap: ki) – csak a `wp_body_open` horgonyt támogató témákon jelenik meg.
  - *Lábléc* (alap: ki).
  - *Pénztár* (alap: be) – a rendelés gomb előtt: klasszikus, blokkos és „Rendelés kifizetése”
    oldal. Ez a kapcsoló a **rendelésnézetre** (köszönőoldal, Fiókom → rendelés) is vonatkozik.
  - *Rendelési e-mail* (alap: be).
- **E-mail mód** – *Kép* (alap) / *Színes PNG-melléklet* / *Mindkettő*. A vevői rendelési
  e-mailekben (feldolgozás alatt, teljesítve, várakozik, számla) jelenik meg. Egyszerű szöveges
  e-mailhez a hivatalos színes PNG mindig mellékletként is csatolódik. Az angol nyelvű
  értesítéshez nincs hivatalos PNG, ott szöveg és link jelenik meg.
- **Szavatossági oldal** – az önálló tájékoztató oldal kiválasztása, vagy az
  **„Oldal létrehozása (/szavatossag/)”** gombbal egy kattintással létrehozható (lásd 7.4).

**GARAN címke**

- **Bekapcsolás** – „GARAN címke megjelenítése” (alap: be). Bekapcsolva a címke a rendelés gomb
  előtt **mindig** megjelenik (kötelező, ehhez nincs külön kapcsoló).
- **Termékoldal** – hogyan jelenjen meg a címke a termékoldalon:
  - *Beágyazott, kattintásra teljes* (alap) – a kicsi, beágyazott címke, amelyre kattintva a
    teljes címke nyílik meg;
  - *Teljes címke a kosárgomb alatt*;
  - *Teljes címke a leírás alatt* – a kosárgomb alatt ilyenkor csak a szöveges sor jelenik meg;
  - *Galériában* – ebben a verzióban még nem választható.
- **Megjelenítési helyek**
  - *Kosároldal, az expressz fizetés és a pénztár gombok előtt* (alap: be) – a klasszikus
    kosárban.
  - *Rendelési e-mail* (alap: be) – a vevői rendelési e-mailekben a tétel alatt; ugyanez a
    kapcsoló szabályozza a rendelésnézetben (köszönőoldal, Fiókom) a tételek alatti címkét is.
  - *Terméklista (listaoldal)* (alap: ki) – a terméklistákon (csak klasszikus témával).

  A címke csak azoknál a termékeknél jelenik meg, ahol a termékszerkesztőben be van kapcsolva.
  Ha a termékoldal Elementor Pro vagy testreszabott blokksablon, helyezd el az
  `[elallas_garan_label]` shortcode-ot a kosárgomb alá.
- **Állapot** – csak tájékoztató sorok:
  - *E-mail címke képként* – elérhető-e a kitöltött címke PNG-ként az e-mailben (GD
    FreeType-támogatás kell hozzá a tárhelyen); ha nem, az e-mailben szöveges tájékoztatás megy
    (évek, gyártó, modell, Your Europe link, termékoldal-link).
  - *Hivatalos GARAN fájlok sértetlensége* – „rendben”, vagy hiba esetén a címke nem jelenik
    meg; ilyenkor telepítsd újra a bővítményt.
  - *Blokkos pénztár ellenőrzése* – link a pénztárra. Tegyél egy terméket a kosárba; az oldal
    alján megjelenő sáv jelzi, hogy az értesítés és a GARAN a rendelés gomb előtt van-e.

**Általános**

- **B2B** – „Elrejtés céges (B2B) rendeléseknél” (alap: ki). Rendelésnézetben, a Rendelés
  kifizetése oldalon és e-mailben a számlázási cégnév / adószám alapján dönt; termékoldalon,
  kosárban és pénztárban (ahol még nincs rendelés) csak fejlesztői szűrővel
  (`elallas_compliance_is_b2b`) rejthető el.
- **Digitális termékek** – „Tisztán virtuális (digitális) termékeknél ne jelenjen meg” (alap: be).
  A virtuálisnak jelölt (nem szállítandó) termékeknél nem jelenik meg; ha virtuálisként kezelt
  fizikai árut árulsz, kapcsold ki.
- **Termékinformációk** – „Opcionális termékinformációk (1.1.1-től)”; az 1.1.0-ban még nincs
  hatása.

Többnyelvű boltban az értesítés nyelve az oldal nyelvét követi (24 hivatalos nyelv, magyar
tartalék). Polylang esetén a GARAN termékadatok szinkronizálását a Polylang (for WooCommerce)
beállításaiban kapcsold be.

### 7.2. GARAN adatok a terméken és a variációkon

**Ki használhatja a GARAN címkét?** Kizárólag a **gyártó által vállalt, térítésmentes, az egész
termékre kiterjedő, 2 évnél hosszabb tartóssági jótállás** jelölésére. A bolti garancia, a
kötelező jótállás (151/2003. Korm. rendelet) és a fizetős kiterjesztett garancia **nem**
jelölhető vele. (Ez a figyelmeztetés a termékszerkesztőben is megjelenik.)

**Egyszerű (és variálható) termék:** nyisd meg a terméket → **Termékadatok → Általános**:

- **Gyártói tartóssági jótállás (GARAN címke)** – bekapcsolja a címkét erre a termékre.
- **Időtartam (év)** – csak 2 évnél hosszabb, egész év (pl. 3); a rendszer ellenőrzi, hogy a
  címkén elfér-e.
- **Gyártó (Brand/Trademark)** – a gyártó által megadott név, a gyártó által a címkén használt
  formában.
- **Modellazonosító**.

Mentéskor a bővítmény ellenőrzi az értékeket. Ha az időtartam nem 2 évnél hosszabb egész év,
valamelyik mező üres, vagy a szöveg nem fér el a címke fix mezőszélességén (a betűméret nem
csökkenthető), a címke **nem kapcsol be**, és a hibaüzenet megnevezi a hibás mezőt. Sikeres
mentés után a mezők alatt **előnézet** látható a mentett adatokkal. A mezők akkor is
kitölthetők, ha a GARAN modul a beállításokban ki van kapcsolva (ezt a szerkesztő jelzi).

**Variációk:** variálható terméknél a variációk alapból a szülő adatait öröklik. Ha egy
variációnak eltérő a modellazonosítója vagy a jótállása, nyisd meg a variációt, és a
**GARAN címke** legördülőben válaszd:

- *Öröklés a szülőtől* (alap);
- *Saját adat* – megjelenik a három mező (időtartam, gyártó, modellazonosító), ugyanazzal az
  ellenőrzéssel; hibás saját adatnál a variáció a szülő adatait örökli, és hibaüzenetet kapsz;
- *Nincs GARAN címke*.

A termékoldalon a címke a kiválasztott variációt követi.

A címke adatai a rendeléskor a **rendelési tételre mentődnek**, így a termék későbbi módosítása
nem írja át a korábbi rendelések címkéjét (sem a rendelésnézetben, sem az e-mailben).

### 7.3. Hol jelenik meg?

| Hely | Szavatossági tájékoztató | GARAN címke |
|---|---|---|
| Termékoldal | a kosárba gomb alatt (*Termékoldal* kapcsoló) | a *Termékoldal* mód szerint; variálható terméknél a kiválasztott variációt követi |
| Fejléc / lábléc | *Fejléc* / *Lábléc* kapcsoló | – |
| Klasszikus pénztár | a rendelés gomb előtt (*Pénztár* kapcsoló) | az érintett tételek címkéi a rendelés gomb előtt, **mindig** |
| Blokkos pénztár | a rendelés gomb előtt (*Pénztár* kapcsoló) | a rendelés gomb előtt, **mindig** |
| „Rendelés kifizetése” oldal | a fizetés gomb előtt (*Pénztár* kapcsoló) | a fizetés gomb előtt, **mindig** |
| Kosár | – | a klasszikus kosárban az expressz fizetés és a pénztár gombok előtt (*Kosároldal* kapcsoló) |
| Terméklisták | – | a termékkártyán (*Terméklista* kapcsoló, csak klasszikus témával) |
| Rendelésnézet (köszönőoldal, Fiókom) | a rendelés táblázata után (*Pénztár* kapcsoló) | a tételek alatt (*Rendelési e-mail* kapcsoló) |
| Vevői rendelési e-mailek | a rendelés táblázata után (*Rendelési e-mail* kapcsoló, *E-mail mód* szerint) | a tételek alatt: PNG-kép (GD FreeType esetén), szöveges sor és linkek (*Rendelési e-mail* kapcsoló) |

A tájékoztató és a címke csak **árukra** vonatkozik: a pénztárban akkor jelenik meg, ha a
kosárban van legalább egy érintett (nem kizárt virtuális) termék. Az admin e-mailekbe sosem
kerül. A vevői e-mailek közül alapból a feldolgozás alatt, teljesítve, várakozik és számla
e-mailbe kerül.

Blokktémán a termékoldali megjelenés a kosárba blokkot követi
(`woocommerce/add-to-cart-form` vagy `woocommerce/add-to-cart-with-options`), és a klasszikus
termék-sablon blokkal renderelt oldalon is működik.

### 7.4. Önálló szavatossági oldal (/szavatossag/)

A fülön az **„Oldal létrehozása (/szavatossag/)”** gomb egy kattintással létrehoz egy
**Szavatosság** című, `/szavatossag/` címen elérhető oldalt az
`[elallas_guarantee_notice mode="inline"]` shortcode-dal, és beállítja *Szavatossági
oldal*-ként. A gomb csak addig látszik, amíg nincs kiválasztott, közzétett oldal. Ha van
kiválasztott oldal, a máshol megjelenő értesítés alatt **„Megnyitás külön oldalon”** link
jelenik meg. Az oldalt például a láblécmenübe is felveheted.

### 7.5. Shortcode-ok

| Shortcode | Attribútumok | Mit ír ki |
|---|---|---|
| `[elallas_guarantee_notice]` | `label` – egyedi felirat (alap: a beállított felirat); `mode="toggle"` (alap, kattintásra nyíló) vagy `mode="inline"` (azonnal látható) | a harmonizált szavatossági tájékoztatót |
| `[elallas_garan_label]` | `product_id` – a termék azonosítója (alap: az aktuális termék); `mode="nested"` vagy `mode="full"` (alap: a *Termékoldal* beállítás) | a termék GARAN címkéjét, ha be van kapcsolva rá |

Elementor Pro-hoz, testreszabott blokksablonokhoz vagy bármilyen más elhelyezéshez a Shortcode
blokkal / Elementor Shortcode widgettel használd őket. Kikapcsolt modulnál semmit nem írnak ki.
A saját termékoldalán az `[elallas_garan_label]` nem duplázza a már automatikusan megjelenített
címkét.

### 7.6. Admin figyelmeztetések

A **manage_woocommerce** jogú felhasználók az admin felületen a következő értesítéseket kaphatják:

- **Határidő / beállítás figyelmeztetés** – 2026. szeptember 27. előtt és után is megjelenik,
  ha:
  - a tájékoztató ki van kapcsolva, vagy egyetlen megjelenítési helye sincs bekapcsolva
    („Jelenleg nem jelenik meg.”);
  - a pénztári megjelenítés ki van kapcsolva;
  - a GARAN címke ki van kapcsolva, pedig az érintett termékeknél a rendelés gomb előtt kötelező;
  - egy bővítmény vagy a téma szűrővel (`elallas_garan_checkout_visible`) elrejtheti a rendelés
    gomb előtti GARAN címkét;
  - a beállítások még nincsenek átnézve (a fület még nem mentetted el).

  A **„Beállítások megnyitása”** gomb a fülre visz, az **„Elrejtés 30 napra”** link 30 napra
  elrejti (felhasználónként).
- **Sérült GARAN fájl** (hiba, nem rejthető el) – a hivatalos GARAN címkefájl sérült vagy
  módosult; a címke nem jelenik meg. Telepítsd újra a bővítményt.
- **Blokkos pénztár tartalék-hely** (hiba) – a *Blokkos pénztár ellenőrzése* azt találta, hogy a
  GARAN és az értesítés nem a rendelés gomb előtt jelenik meg (a téma eltérő pénztár-szerkezete
  miatt). Szólj a fejlesztődnek (lásd a fejlesztői referenciát: `elallas_checkout_block_anchors`),
  majd futtasd újra az ellenőrzést.

### 7.7. Ismert korlátok (1.1.0)

- A GARAN címke az e-mailben csak GD FreeType-támogatású tárhelyen jelenik meg képként; enélkül
  szövegként (évek, gyártó, modell) és linkként.
- A terméklistákon (archívum) a GARAN címke csak klasszikus témával jelenik meg; blokktémánál a
  termékoldalon, a kosárban és a pénztárban.
- A szoftverfrissítési és javíthatósági információt 1.1.1-ig a termékleírásban tüntesd fel.
- A szavatossági Gutenberg-blokk és Elementor-widget 1.1.1-ben jön; addig a Shortcode blokkal /
  widgettel helyezd el a shortcode-okat.
- A blokkos kosár expressz fizetési gombjai előtt a GARAN címke nem jelenik meg (a klasszikus
  kosárban igen).

---

## 8. WP-CLI

```bash
wp elallas stats                      # ügyek státusz szerinti darabszáma
wp elallas list [--status=] [--deadline=] [--format=table|csv|json|count]
wp elallas get <id>                   # ügy + tételek + audit log
wp elallas status <id> <status>       # státuszváltás (pl. accepted, rejected, closed)
wp elallas pdf <id>                   # PDF (újra)generálása
wp elallas cleanup                    # adatmegőrzési anonimizálás futtatása
```

---

## 9. Eltávolítás

A bővítmény törlésekor csak akkor töröl adatot (táblák, opciók, meta), ha az **„Adatok törlése
eltávolításkor"** opció be van kapcsolva. Egyébként az ügyek megmaradnak. Bekapcsolt opciónál a
GARAN termék- és variációadatok, a rendelési tételekre mentett GARAN adatok és a generált GARAN
e-mail képek is törlődnek; a létrehozott `/szavatossag/` oldal megmarad (azt kézzel töröld).

## 10. Biztonság röviden

Nonce minden űrlapon; rate limit + honeypot a rendelés-próbálgatás ellen; semleges azonosítási
hiba; előkészített SQL; jogosultság-ellenőrzés (`manage_woocommerce`); védett dokumentum-könyvtár
és token-védett PDF-letöltés.

---

Fejlesztői részletek (hookok, REST API, Site Manager abilities, sablon-felülírás, adatmodell,
szavatossági és GARAN hookok):
lásd [fejlesztoi-referencia.md](fejlesztoi-referencia.md).
