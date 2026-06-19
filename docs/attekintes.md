# Elállás for WooCommerce — Áttekintés: mi ez, minek kell megfelelni, miben segít

Ez a dokumentum **döntéshozóknak és webshop-tulajdonosoknak** szól: röviden elmagyarázza, mi a
2026-os jogszabályváltozás lényege, mit kell emiatt a webshopodnak tudnia, és hogy ebből mit old
meg helyetted ez a bővítmény. A gyakorlati kezeléshez lásd a
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

## 5. Mit NEM old meg helyetted

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

---

## 6. Összefoglalva

- **Mi ez?** A 2026. június 19-től kötelező **online elállási funkció** megvalósítása
  WooCommerce-hez, az EU 2023/2673 irányelv és a 415/2025. Korm. rendelet szerint.
- **Minek kell megfelelni?** Könnyen elérhető, elektronikus, megerősítéssel záruló elállási
  folyamat, tartós adathordozós visszaigazolással, helyes jogi megfogalmazással és igazolható
  dokumentációval.
- **Miben segít?** A bővítmény a teljes vásárlói folyamatot és a kereskedői ügykezelést adja —
  naplózottan, biztonságosan, GDPR-tudatosan, magyar jogi környezetre szabva, bloat és felár nélkül.

---

*Fejlesztő: [uptools.io](https://uptools.io) — GPL-2.0-or-later, ingyenes. Kapcsolódó dokumentumok:
[kezelési útmutató](kezelesi-utmutato.md) · [fejlesztői referencia](fejlesztoi-referencia.md).*
