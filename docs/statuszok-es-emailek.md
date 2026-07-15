# Elállás for WooCommerce — Ügy-státuszok és e-mailek

Ez az útmutató **egyértelműsíti, melyik státusz mit jelent, mit tartalmaz, és mikor / milyen
e-mail megy ki**. Cél, hogy a folyamat kiszámítható legyen: ki mit lát, és mikor.

> **Fontos fogalmi tisztázás.** Az „Automatikusan visszaigazolva" státusz **nem** azt jelenti,
> hogy a kereskedő elfogadta az elállást. Ez a **jogszabály által előírt, tartós adathordozón
> küldött visszaigazolás arról, hogy a nyilatkozat beérkezett** — automatikusan, emberi
> beavatkozás nélkül. Az elállás **elbírálása** (elfogadás / elutasítás, majd a visszatérítés)
> ezután, a kereskedő döntése alapján történik.

---

## 1. A két, egymástól független jelző

A bővítmény **két külön dolgot** tart nyilván egy ügyön:

| | Mit jelent | Értékek |
|---|---|---|
| **Ügy-státusz** | Hol tart az ügy a feldolgozási folyamatban | lásd lent (Beérkezett → … → Lezárva) |
| **Határidő-státusz** | A 14 napos elállási ablakon belül van-e a nyilatkozat | `Határidőn belül` / `Határidőn túl` / `Nem megállapítható` |

A **határidő-státusz csak jelölés** — alapból sosem blokkol, csak eldönti, hogy a beérkezett
nyilatkozat automatikusan visszaigazolható-e, vagy manuális ellenőrzésre kerül.

---

## 2. Az ügy-státuszok jelentése

A státuszok a feldolgozás sorrendjében:

| Státusz (címke) | Mit jelent | Mikor / ki állítja | Kimenő e-mail |
|---|---|---|---|
| **Beérkezett** (`received`) | A nyilatkozat beérkezett, de a vásárló még nem erősítette meg. Átmeneti állapot: az űrlapon a létrehozás után azonnal jön a megerősítés. | Ügy létrejöttekor (rendszer) | — |
| **Automatikusan visszaigazolva** (`auto_confirmed`) | A vásárló megerősítette, és a nyilatkozat **határidőn belül** érkezett → a beérkezés automatikusan visszaigazolható. **Nem** jelent kereskedői elfogadást. | Megerősítéskor, ha határidőn belül (rendszer) | **Vásárlói visszaigazolás** + **Admin értesítés** |
| **Manuális ellenőrzés alatt** (`manual_review`) | A vásárló megerősítette, de a határidő **lejárt vagy nem megállapítható** → emberi ellenőrzés kell. | Megerősítéskor, ha nincs határidőn belül (rendszer) | **Vásárlói visszaigazolás** + **Admin értesítés** |
| **Elfogadva** (`accepted`) | A kereskedő jóváhagyta az elállást. | Admin állítja | **Státusz-frissítés** a vásárlónak |
| **Elutasítva** (`rejected`) | A kereskedő (vagy a kizárt-termék védőháló) elutasította. Az indok az e-mailbe kerül. | Admin állítja, vagy a kizárás-védőháló (rendszer) | **Státusz-frissítés** (az indokkal) |
| **Visszaküldésre vár** (`awaiting_return`) | Elfogadva, a vásárlónak vissza kell küldenie az árut. | Admin állítja | **Státusz-frissítés** |
| **Áru beérkezett** (`goods_received`) | A visszaküldött áru megérkezett. | Admin állítja | **Státusz-frissítés** |
| **Visszatérítés folyamatban** (`refund_pending`) | A visszatérítés elindítva. | Admin állítja | **Státusz-frissítés** |
| **Lezárva** (`closed`) | Az ügy lezárva (végállapot). | Admin állítja | **Státusz-frissítés** |
| **Törölve / hibás beküldés** (`cancelled`) | Érvénytelen / téves beküldés (végállapot). | Admin állítja | **Státusz-frissítés** |

**Végállapotok** (nincs további lépés): `Elutasítva`, `Lezárva`, `Törölve / hibás beküldés`.

---

## 3. A három e-mail

Mindhárom külön ki-/bekapcsolható (**WooCommerce → Elállás – beállítások → E-mailek**), a
tárgyat/fejlécet pedig a **WooCommerce → Beállítások → E-mailek** alatt szabhatod testre.

| E-mail | Kinek | Mikor (trigger) | Feltétel | Tartalom |
|---|---|---|---|---|
| **Vásárlói visszaigazolás** (`elallas_customer_confirmation`) | Vásárló | Amikor az ügy megerősítésre kerül (`auto_confirmed` **vagy** `manual_review`) | „Vásárlói visszaigazolás" bekapcsolva | Tartós adathordozós visszaigazolás a nyilatkozat beérkezéséről, a tételekkel; opcionális **PDF-csatolmány**; a beállított **extra szöveg**. |
| **Admin értesítés** (`elallas_admin_notification`) | Kereskedő | Ugyanakkor (ügy megerősítve) | „Admin értesítés" bekapcsolva | Új elállási nyilatkozat összefoglalója. Címzett: az **Admin címzett** mező (több cím is megadható). |
| **Státusz-frissítés** (`elallas_status_update`) | Vásárló | **Minden** státuszváltáskor | „Státusz e-mail" bekapcsolva | Az új státusz + opcionális **„A kereskedő üzenete"** (pl. az elutasítás indoka). |

> **Admin címzett – több cím.** Az admin értesítő címzettjénél több e-mail cím is megadható,
> **vesszővel, pontosvesszővel vagy szóközzel** elválasztva (pl. `info@bolt.hu, ugyfel@bolt.hu`).
> Üresen hagyva a WordPress oldal adminisztrátori címére megy.

---

## 4. Tipikus idővonal

1. **A vásárló beküldi a nyilatkozatot** → ügy jön létre (`Beérkezett`), majd a megerősítéssel
   azonnal **`Automatikusan visszaigazolva`** (ha határidőn belül) vagy **`Manuális ellenőrzés
   alatt`** (ha nem) lesz.
   → Ekkor megy ki a **Vásárlói visszaigazolás** és az **Admin értesítés**.
2. **A kereskedő elbírálja** az ügyet a **WooCommerce → Elállási ügyek** alatt, és állítja a
   státuszt (`Elfogadva` / `Elutasítva` / `Visszaküldésre vár` / …).
   → Minden váltásnál megy a **Státusz-frissítés** a vásárlónak (a beírt üzenettel együtt).
3. **Elutasításkor** érdemes a „Státusz módosítása" űrlap **üzenet** mezőjébe beírni az indokot —
   ez bekerül a vásárlónak menő e-mailbe (**„A kereskedő üzenete"**).

---

## 5. Kizárt termékek a folyamatban

Ha egy termék (vagy a kategóriája/címkéje) **elállásból kizárt**, a vásárló **nem tudja azt
igényelni**:

- **Az űrlapon** a kizárt tétel nem választható, mellette az **indok** olvasható. A rendelés
  **többi, jogosult** tételére az elállás továbbra is kezdeményezhető (részleges elállás).
- Ha a rendelés **minden** tétele kizárt, az egész kérelem blokkolva van, az okok kilistázva.
- **Védőháló:** ha egy kizárt tétel mégis eljut a beküldésig (pl. manipulált kérés), az ügy
  **azonnal `Elutasítva`** lesz, és a **Státusz-frissítés** e-mail kimegy az **indokkal** —
  automatikus visszaigazolás nélkül.

---

Kapcsolódó: [Kezelési útmutató](kezelesi-utmutato.md) · [Áttekintés](attekintes.md) ·
[Fejlesztői referencia](fejlesztoi-referencia.md)
