# Elállás for WooCommerce — Áttekintés: mi ez, minek kell megfelelni, miben segít

Ez a dokumentum **döntéshozóknak és webshop-tulajdonosoknak** szól: röviden elmagyarázza, mi a
2026-os jogszabályváltozás lényege, mit kell emiatt a webshopodnak tudnia, és hogy ebből mit old
meg helyetted ez a bővítmény. Az 1.1.0 óta a bővítmény a **2026. szeptember 27-től kötelező
harmonizált szavatossági tájékoztatót és GARAN címkét** is kezeli (lásd az
[5. fejezetet](#5-szavatossági-tájékoztató-és-garan-címke-2026-szeptember-27-től)). A gyakorlati kezeléshez lásd a
[kezelési útmutatót](kezelesi-utmutato.md); a technikai részletekhez a
[fejlesztői referenciát](fejlesztoi-referencia.md).

> ⚠️ **Ez nem jogi tanácsadás.** A bővítmény mintaszövegeket és egy megfelelés-orientált folyamatot
> ad, de a végleges szövegeket (nyilatkozat, ÁSZF, adatkezelési tájékoztató) a saját
> dokumentumaiddal összhangban, magyar e-commerce jogásszal kell validáltatnod, mielőtt élesíted.

---

## 1. Mi ez az egész?

2026-tól a magyar (és uniós) webshopoknak **online elállási funkciót** kell biztosítaniuk a
fogyasztóknak. A klasszikus, indokolás nélküli 14 napos elállási jog **nem változik** — az eddig is
megvolt. Az újdonság az, hogy a fogyasztónak **a webshop online felületén, néhány kattintással,
elektronikusan** is jeleznie tudja az elállási szándékát, nem csak e-mailben, levélben vagy a régi
elállási nyilatkozat-mintát kinyomtatva.

A jogalkotó célja, hogy az elállás ugyanolyan egyszerű legyen, mint maga a vásárlás volt: jól
látható, könnyen elérhető funkció, egyértelmű megerősítő lépés, és **azonnali, igazolható
visszaigazolás** a fogyasztónak.

### Jogi alap

| Szint | Jogszabály | Mit tesz |
|---|---|---|
| EU | **Directive (EU) 2023/2673** (2023. nov. 22.) | Módosítja a 2011/83/EU fogyasztói jogi irányelvet; bevezeti az online felületen kötött távolléti szerződésekre az **elektronikus elállási funkciót**. |
| Magyarország | **415/2025. (XII. 23.) Korm. rendelet** | Módosítja a **45/2014. (II. 26.) Korm. rendeletet** (a fogyasztó és vállalkozás közötti szerződések részletszabályai, az elállási jog hazai alapja). |

**Alkalmazás kezdete: 2026. június 19.**

---

## 2. Kire vonatkozik?

- **Online értékesítő webshopokra**, amelyek **fogyasztókkal** (B2C) kötnek távolléti szerződést —
  termék, szolgáltatás és digitális tartalom egyaránt.
- A kötelezettség a **fogyasztói** vásárlókat illeti meg. A **céges (B2B)** vásárlókat a
  fogyasztói elállási jog alapból nem illeti meg — a bővítmény ezért **jelzi a valószínűsíthetően
  B2B rendeléseket** (cégnév / adószám alapján), de a végső döntést rád bízza.

Ha a webshopod kizárólag céges ügyfeleknek értékesít, a kötelezettség jellemzően nem terhel — ezt
azonban érdemes jogásszal megerősíttetni, mert a „fogyasztó” minősítés nem mindig egyértelmű.

---

## 3. Minek kell megfelelni? (a konkrét követelmények)

A 2026-os szabályok az alábbi gyakorlati elvárásokat támasztják az online elállási funkcióval
szemben:

1. **Könnyen megtalálható, jól látható funkció** — a fogyasztó a fiókjából / a rendelés
   oldaláról néhány (gyakorlatban legfeljebb két) kattintással elérje.
2. **Elektronikus nyilatkozattétel** — a fogyasztó az online felületen tudja megadni az
   elálláshoz szükséges adatokat (név, e-mail, rendelés azonosítója, érintett termék/szerződés,
   az elállási szándék).
3. **Külön megerősítő lépés** — a nyilatkozatot egy egyértelmű, dedikált gombbal
   (pl. „Elállás megerősítése”) kell véglegesíteni, hogy ne legyen véletlen beküldés.
4. **Helyes jogi megfogalmazás** — az elállás a **szerződéstől** való elállás (nem
   „a rendeléstől”). Az alapértelmezett gombfelirat ezért **„Elállás a szerződéstől”**.
5. **Visszaigazolás tartós adathordozón** — a fogyasztó **automatikus visszaigazolást** kap
   (praktikusan e-mailben), amely tartalmazza az elállás adatait és a **beérkezés pontos
   időpontját**.
6. **A fogyasztó nyelvén** — a nyilatkozat a fogyasztó által választott nyelven megtehető legyen.
7. **Teljes vagy részleges elállás** — több tételes rendelésnél a fogyasztó az egyes tételekre /
   mennyiségekre is elállhasson.
8. **Visszatérítés határidőben** — a vételárat az elállás kézhezvételétől számított **14 napon
   belül** vissza kell téríteni (ez a kereskedő kötelezettsége; a bővítmény ezt ügyviteli
   határidőként kezeli).
9. **Igazolhatóság, dokumentáltság** — vita esetén bizonyítható legyen, ki, mikor, mit jelentett be.

**Miért fontos?** Az online elállási funkció hiánya vagy hiányos megvalósítása fogyasztóvédelmi
jogsértés, amely panaszhoz, hatósági eljáráshoz és bírsághoz vezethet, ráadásul rontja a vásárlói
bizalmat. (A konkrét szankciók mértékét jogszabály és hatósági gyakorlat határozza meg — ezt
jogásszal érdemes tisztázni.)

---

## 4. Miben segít ez a bővítmény?

A bővítmény **nem „csak egy gomb”**: egy jogilag védhető, naplózott, rendeléshez kötött folyamatot
és egy adminisztrálható ügykezelést ad. Az alábbi táblázat a fenti követelményeket párosítja a
bővítmény funkcióival.

| Követelmény | Hogyan oldja meg a bővítmény |
|---|---|
| Könnyen megtalálható, jól látható | Önálló `/elallas/` oldal, `[elallas_form]` shortcode, Gutenberg blokk, Elementor widget, **Fiókom → Elállás** végpont, rendelés-oldali gomb és rendelési e-mailbe ágyazott link. |
| Elektronikus nyilatkozattétel | Kétlépcsős űrlap: **azonosítás** (rendelésszám + e-mail), **tételek kiválasztása**, **megerősítés**. Regisztráció nélkül, vendég vásárlóknak is működik. |
| Külön megerősítő lépés | Külön **„Elállás megerősítése”** gomb + három kifejezett nyilatkozat-pipa (adatok, szándék, hozzájárulás). |
| Helyes jogi megfogalmazás | Alapértelmezett felirat **„Elállás a szerződéstől”** (nem „rendeléstől”); a szövegek szerkeszthetők. |
| Visszaigazolás tartós adathordozón | Automatikus visszaigazoló e-mail az elállás adataival és a **beérkezés időpontjával**; opcionális **PDF elállási nyilatkozat** (SHA-256 hash-sel) is csatolható. |
| A fogyasztó nyelvén | WPML / Polylang / TranslatePress integráció; a jogi szövegek nyelvenként kezelhetők. |
| Teljes vagy részleges elállás | Rendelésenként, tételenként és mennyiségenként is. |
| Visszatérítés határidőben | A 14 napos határidőt **kiszámolja és jelöli** (határidőn belül / túl / nem megállapítható), de **alapból sosem blokkol** — a végső döntés a kereskedőé. |
| Igazolhatóság, dokumentáltság | **Rendelés-pillanatkép** (a beküldéskori termék/ár adatok megmaradnak), **append-only audit log** (ki, mikor, mit), egyedi ügyszám (pl. `EL-2026-000001`), token-védett PDF-letöltés. |
| B2B megkülönböztetés | Valószínűsíthetően céges rendelések jelölése (cégnév / adószám alapján). |
| Adatvédelem (GDPR) | E-mail hash-elve és opcionálisan titkosítva; IP / user agent teljes / hash / kikapcsolt módban; állítható **adatmegőrzés** napi automatikus anonimizálással; az opcionális banki/IBAN mező **titkosítva** tárolódik. |
| Biztonság | Nonce, rate limit és honeypot a rendelésszám-próbálgatás ellen; **semleges azonosítási hiba** (nem árulja el, melyik mező rossz); jogosultság-ellenőrzés; védett dokumentum-könyvtár. |

### Ügykezelés a kereskedőnek

A beérkezett elállások a **WooCommerce → Elállási ügyek** alatt naplózott ügyként jelennek meg:
szűrhető ügylista, részletes ügynézet (összefoglaló, vásárlói nyilatkozat, rendelés-pillanatkép,
audit log, admin döntés, dokumentumok), CSV export, és egy átlátható státusz-folyamat a
beérkezéstől a visszatérítésen át a lezárásig. Parancssorból WP-CLI-vel (`wp elallas …`), illetve
REST API-n és az LW Site Manager Abilities API-n keresztül is kezelhető.

---

## 5. Szavatossági tájékoztató és GARAN címke (2026. szeptember 27-től)

Az elállási funkció mellett 2026 őszétől egy második kötelezettség is érinti a fogyasztóknak
**árut** értékesítő webshopokat: a vásárlót egységes, uniós formában kell tájékoztatni a
**jogszabályi szavatosságról**, és ha a gyártó hosszabb tartóssági jótállást vállal, azt is
egységes címkével kell jelölni.

### Jogi alap

| Szint | Jogszabály | Mit tesz |
|---|---|---|
| EU | **(EU) 2025/1960 végrehajtási rendelet** (a (EU) 2024/825 irányelv alapján) | Kötelező formában előírja a **harmonizált tájékoztatót a jogszabályi szavatosságról** és a **harmonizált GARAN címkét** a 2 évnél hosszabb gyártói tartóssági jótálláshoz. |
| Magyarország | **116/2026. (VII. 30.) Korm. rendelet** | A 45/2014. (II. 26.) Korm. rendeletbe vezeti be: a tájékoztató és a címke „jól láthatóan” jelenjen meg (11. § (1a)), a tartóssági jótállásra pedig **közvetlenül a megrendelés előtt** fel kell hívni a figyelmet (15. § (1)). |

**Alkalmazás kezdete: 2026. szeptember 27.**

### Mit kell tudnia a webshopnak?

1. **A hivatalos tájékoztató változatlanul** — a Bizottság által közzétett grafika nem
   módosítható, az oldal nyelvén kell megjeleníteni, a QR-kód célját (Your Europe) linkként is
   elérhetővé téve.
2. **Jól látható elhelyezés** — a termékoldalon, a vásárlási folyamatban és a rendelés után is.
3. **GARAN címke, ahol van tartóssági jótállás** — csak a gyártó által vállalt, térítésmentes, az
   egész termékre kiterjedő, **2 évnél hosszabb** tartóssági jótállásra. Bolti garancia, a kötelező
   jótállás (151/2003. Korm. rendelet) és a fizetős kiterjesztett garancia nem jelölhető vele.
4. **A rendelés gomb előtt** — a tartóssági jótállásra közvetlenül a megrendelés előtt kell
   felhívni a figyelmet.

### Miben segít a bővítmény?

| Követelmény | Hogyan oldja meg a bővítmény |
|---|---|
| Hivatalos tájékoztató változatlanul | A Bizottság hivatalos, bájtra azonos fájljai (24 hivatalos nyelv, magyar tartalék), az oldal nyelvén; mellette a QR-kóddal azonos Your Europe link. A képet és a linket sablon-felülírással sem lehet lecserélni. |
| Jól látható elhelyezés | Termékoldal (a kosárba gomb alatt), fejléc, lábléc, a rendelés gomb előtt (klasszikus és blokkos pénztár, „Rendelés kifizetése” oldal), rendelésnézet (köszönőoldal, Fiókom) és a vásárlói rendelési e-mailek. Rövid feliratról nyílik, vagy beágyazva látszik. Egy kattintással önálló `/szavatossag/` oldal is létrehozható. |
| GARAN címke | Termékenként kapcsolható be (időtartam, gyártó, modellazonosító); a bővítmény ellenőrzi, hogy az értékek elférnek-e a címke fix mezőiben, és előnézetet mutat. A variációk öröklik a szülő adatait, de saját adatot is kaphatnak. |
| Figyelemfelhívás a rendelés előtt | Amíg a GARAN modul be van kapcsolva, az érintett tételek címkéi **mindig** megjelennek a rendelés / fizetés gomb előtt — ezt beállítással nem lehet kikapcsolni. |
| Igazolhatóság | A címke adatai a rendeléskor a rendelési tételre mentődnek, így a termék későbbi módosítása nem írja át a korábbi rendeléseket. |
| Hatókör | Csak árukra: a tisztán virtuális (digitális) termékek kizárhatók, céges (B2B) rendeléseknél a megjelenés elrejthető. |
| Megfelelőség-ellenőrzés | Admin figyelmeztetés, ha a tájékoztató vagy a GARAN ki van kapcsolva, nincs elhelyezve, vagy a beállítások még nincsenek átnézve; a módosított hivatalos GARAN fájlt a bővítmény felismeri, és nem jeleníti meg. |

A beállítások a **Szavatosság és GARAN** fülön vannak; a részletekhez lásd a
[kezelési útmutatót](kezelesi-utmutato.md).

**Ismert korlátok az 1.1.0-ban:** az e-mailben a GARAN címke csak GD FreeType-támogatású
tárhelyen jelenik meg képként (enélkül szövegként és linkként); a terméklistákon csak klasszikus
témával; a szoftverfrissítési és javíthatósági információt 1.1.1-ig a termékleírásban kell
feltüntetni; a szavatossági Gutenberg-blokk és Elementor-widget 1.1.1-ben jön (addig
shortcode-dal helyezhető el); a blokkos kosár expressz fizetési gombjai előtt a GARAN címke nem
jelenik meg.

---

## 6. Mit NEM old meg helyetted

A bővítmény a **technikai és folyamati** megfelelést adja meg. A következők továbbra is a te (és a
jogászod) felelősséged:

- **Jogi szövegek véglegesítése** — a nyilatkozat és a visszaigazoló szövegek mintaszövegek;
  a saját ÁSZF-eddel összhangban kell véglegesíteni.
- **ÁSZF és adatkezelési tájékoztató** frissítése az új funkcióra hivatkozva.
- **A konkrét elállási döntések** — az elfogadás/elutasítás, a határidőn túli vagy kivételes esetek
  elbírálása emberi döntés marad (a bővítmény ezért jelöl, és alapból nem blokkol).
- **A visszatérítés tényleges teljesítése** a 14 napos határidőn belül.
- **A kivételek helyes beállítása** — mely termékek esnek az elállási jog alóli kivételek alá
  (pl. higiéniai, romlandó, egyedi, digitális termékek); ezt jogi mérlegelés alapján kell megadni.
- **A GARAN adatok helyessége** — hogy egy terméknél van-e a gyártónak 2 évnél hosszabb
  tartóssági jótállása, és mennyi az időtartam, a gyártó és a modellazonosító: ezt a gyártói
  adatok alapján neked kell megadnod.
- **Az ÁSZF frissítése** a szavatossági szabályokra — a harmonizált tájékoztató ezt nem
  helyettesíti.

---

## 7. Összefoglalva

- **Mi ez?** A 2026. június 19-től kötelező **online elállási funkció** megvalósítása
  WooCommerce-hez, az EU 2023/2673 irányelv és a 415/2025. Korm. rendelet szerint.
- **Minek kell megfelelni?** Könnyen elérhető, elektronikus, megerősítéssel záruló elállási
  folyamat, tartós adathordozós visszaigazolással, helyes jogi megfogalmazással és igazolható
  dokumentációval.
- **És még?** 2026. szeptember 27-től a harmonizált **szavatossági tájékoztató** és a
  **GARAN címke** megjelenítése is kötelező az árut értékesítő webshopoknak — az 1.1.0 óta ezt
  is a bővítmény intézi, a hivatalos uniós fájlokkal.
- **Miben segít?** A bővítmény a teljes vásárlói folyamatot és a kereskedői ügykezelést adja —
  naplózottan, biztonságosan, GDPR-tudatosan, magyar jogi környezetre szabva, bloat és felár nélkül.

---

*Fejlesztő: [uptools.io](https://uptools.io) — GPL-2.0-or-later, ingyenes. Kapcsolódó dokumentumok:
[kezelési útmutató](kezelesi-utmutato.md) · [fejlesztői referencia](fejlesztoi-referencia.md).*
