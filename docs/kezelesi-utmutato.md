# Elállás for WooCommerce — Kezelési útmutató

Online elállási (visszalépési) funkció és naplózott ügykezelés WooCommerce webshopokhoz,
a **Directive (EU) 2023/2673** és a **415/2025. (XII. 23.) Korm. rendelet** (45/2014. (II. 26.)
Korm. rendelet módosítása, **2026. június 19-től** alkalmazandó) szerint.

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
indokával).

A kizárt termékre a vásárló **nem tud elállást igényelni**: az űrlapon a tétel nem választható,
mellette az indok olvasható, a rendelés többi (jogosult) tételére viszont az elállás továbbra is
kezdeményezhető. Ha a rendelés **minden** tétele kizárt, az egész kérelem blokkolva van, az okok
kilistázva. Védőhálóként, ha egy kizárt tétel mégis eljut a beküldésig (pl. manipulált kérés), az
ügy azonnal **„Elutasítva"** lesz, és a státusz-e-mail kimegy az indokkal — automatikus
visszaigazolás nélkül. Részletek: [Ügy-státuszok és e-mailek](statuszok-es-emailek.md).

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
  ki/bekapcsolható; az admin értesítő címzettje megadható (**több cím is**, vesszővel,
  pontosvesszővel vagy szóközzel elválasztva).
- **Vásárlói e-mail extra szöveg** – a visszaigazoló e-mail aljához fűzött szabad szöveg (pl.
  visszaküldési cím, ügyfélszolgálat). A tárgyat/fejlécet a WooCommerce → Beállítások → E-mailek
  alatt, a teljes sablont a témád `elallas-for-woo/` mappájában szabhatod testre.
- **Rendelési e-mail elállási szövege** – a WooCommerce rendelési e-mailekbe fűzött szöveg az
  elállási link köré (csak ha az Általános fülön a „Rendelési e-mailben" megjelenítés be van
  kapcsolva). A `{link}` helyőrző helyére a beállított elállási oldalra mutató hivatkozás kerül
  (az adott rendelésre előtöltve); ha nincs `{link}` a szövegben, a hivatkozás a szöveg után
  kerül. Üresen hagyva csak a hivatkozás jelenik meg.
- **Tájékoztató / ÁSZF link** – bekapcsolva minden elállási e-mail (vásárlói visszaigazoló,
  státusz-frissítés, admin értesítő) aljára egy **új lapon nyíló** hivatkozás kerül. A cél URL és
  a felirat szabadon megadható; ha az URL-t üresen hagyod, a **WooCommerce → Beállítások →
  Speciális** alatt beállított ÁSZF oldal érvényes. Hasznos pl. az elutasítás indokának/feltételek
  elolvastatásához.

### Jogi szövegek
- **Nyilatkozat szövege** és **visszaigazoló szöveg** – szabadon szerkeszthető; a beállítások
  tetején a jogi felelősség-kizáró figyelmeztetés.

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
2. **Tételek kiválasztása** – teljes vagy részleges elállás, mennyiség szerint. Az elállásból
   **kizárt** termékek nem választhatók, mellettük az indok olvasható.
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
  A **Vásárló** oszlop regisztrált vásárlónál a felhasználó WordPress-profiljára hivatkozik
  (vendég rendelésnél „Vendég").
- **Ügy részletei** – összefoglaló (a megadott visszatérítési bankszámlával, ha van),
  vásárlói nyilatkozat, **rendelés-pillanatkép** (a beküldéskori adatok, akkor is, ha a termék/ár
  később változik; a kizárt tételek „kizárt"-ként jelölve), **audit log** (ki, mikor, mit), admin
  döntés (státuszváltás), dokumentumok (PDF letöltés token-védetten).

### Ügy-státuszok
`Beérkezett` → `Automatikusan visszaigazolva` / `Manuális ellenőrzés alatt` → `Elfogadva` /
`Elutasítva` → `Visszaküldésre vár` → `Áru beérkezett` → `Visszatérítés folyamatban` → `Lezárva`
(+ `Törölve / hibás beküldés`).

Az egyes státuszok **pontos jelentése, tartalma és a hozzájuk tartozó e-mail-triggerek**
(beleértve, hogy az „Automatikusan visszaigazolva" **nem** kereskedői elfogadás):
**[Ügy-státuszok és e-mailek](statuszok-es-emailek.md)**.

### Határidő-státusz (jelölés, sosem blokkol alapból)
`Határidőn belül` / `Határidőn túl – manuális ellenőrzést igényel` / `Nem megállapítható`.

---

## 7. WP-CLI

```bash
wp elallas stats                      # ügyek státusz szerinti darabszáma
wp elallas list [--status=] [--deadline=] [--format=table|csv|json|count]
wp elallas get <id>                   # ügy + tételek + audit log
wp elallas status <id> <status>       # státuszváltás (pl. accepted, rejected, closed)
wp elallas pdf <id>                   # PDF (újra)generálása
wp elallas cleanup                    # adatmegőrzési anonimizálás futtatása
```

---

## 8. Eltávolítás

A bővítmény törlésekor csak akkor töröl adatot (táblák, opciók, meta), ha az **„Adatok törlése
eltávolításkor"** opció be van kapcsolva. Egyébként az ügyek megmaradnak.

## 9. Biztonság röviden

Nonce minden űrlapon; rate limit + honeypot a rendelés-próbálgatás ellen; semleges azonosítási
hiba; előkészített SQL; jogosultság-ellenőrzés (`manage_woocommerce`); védett dokumentum-könyvtár
és token-védett PDF-letöltés.

---

Fejlesztői részletek (hookok, REST API, Site Manager abilities, sablon-felülírás, adatmodell):
lásd [fejlesztoi-referencia.md](fejlesztoi-referencia.md).
