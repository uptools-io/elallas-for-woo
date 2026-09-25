# Elállás for WooCommerce — Fejlesztői referencia

PSR-4, `LightweightPlugins\Elallas\` névtér, `includes/` mappa. HPOS-kompatibilis (minden
rendelés-hozzáférés WooCommerce CRUD-on keresztül megy, az `OrderAdapter`-ben).

## Architektúra (modulok)

| Mappa | Felelősség |
|---|---|
| `Database/` | `Schema` (4 tábla) + repository-k (`CaseRepository`, `CaseItemRepository`, `EventRepository`, `DocumentRepository`, `CaseQuery`) |
| `Models/` | `WithdrawalCase`, `CaseItem`, `CaseStatus`, `DeadlineStatus` |
| `Domain/` | `CaseService`, `EligibilityChecker`, `DeadlineCalculator`, `B2BDetector`, `OrderSnapshotBuilder`, `ProductExclusion`, `CaseNumberGenerator`, `EligibilityResult` |
| `Frontend/` | flow (`FormHandler`, `StepProcessor`, `FormRequest`, `WithdrawalForm`), `Shortcodes`, `MyAccountEndpoint`, `Assets`, `TemplateLoader`, `SubmissionContext` |
| `Admin/` | menü, `CasesListTable`, ügy-részletek, `Settings/` (benne `TabCompliance` + `ComplianceGaranSection`), `Onboarding/`, `ProductFields` (termék-meta), `TermFields` (kategória/címke-meta), `GaranProductFields` / `GaranVariationFields` (GARAN termék- és variáció-meta), `ComplianceNotice` + `ComplianceCheck` (szavatossági admin figyelmeztetések) |
| `Compliance/` | szavatossági tájékoztató és GARAN címke (1.1.0): `Bootstrap`, `NoticeHooks` / `NoticeRenderer` / `NoticeSource` / `NoticeUrlPolicy` / `NoticeEmail`, `GaranHooks` / `GaranRenderer` / `GaranResolver` / `GaranData` / `GaranSnapshot` / `GaranEmail` / `GaranRaster` / `GaranSource` / `GaranCheckoutList` / `GaranVariations`, `CheckoutSlot`, `ProductPlacement`, `GoodsScope`, `EmailContext`, `OfficialAssets`, `ComplianceShortcodes`, `ComplianceAssets` |
| `Emails/` | `EmailManager` + 3 `WC_Email` osztály + `PreviewableEmailTrait` (előnézet-minta) |
| `Security/` | `Encryption` (AES-256-GCM), `RateLimiter`, `Honeypot` |
| `Pdf/` | `PdfRenderer` (dompdf), `DocumentService`, `DownloadHandler` (token-védett) |
| `Woo/` | `OrderAdapter` (HPOS-safe), `OrderStatusManager`, `Hooks` |
| `Integrations/` | `Invoicing`, `Shipping`, `Multilingual`, `Elementor` |
| `Api/` | REST kontrollerek |
| `Blocks/` | Gutenberg blokk regisztráció |
| `SiteManager/` | LW Site Manager / Abilities API |
| `Cron/` | `RetentionCleaner` (adatmegőrzés) |
| `CLI/` | `Commands` (`wp elallas …`) |

## Action hookok

| Hook | Paraméterek | Mikor |
|---|---|---|
| `elallas_case_created` | `$case_id, $order_id` | Ügy létrejött |
| `elallas_case_confirmed` | `$case_id` | A vásárló megerősítette (kétlépcsős flow) |
| `elallas_case_status_changed` | `$case_id, $old_status, $new_status` | Státuszváltás |
| `elallas_invoicing_case_created` | `$case_id, $order_id` | Számlázó-integrációs kapaszkodó |
| `elallas_boot` | `$plugin` | A bővítmény elindult (WooCommerce aktív) |
| `elallas_compliance_boot` | – | A szavatossági modulok példányosítása után (`Compliance\Bootstrap::boot()`) |

## Filterek

| Hook | Aláírás | Cél |
|---|---|---|
| `elallas_is_order_eligible` | `($eligible, $order)` | Jogosultság felülbírálása |
| `elallas_deadline_days` | `($days, $order)` | Elállási határidő (alap 14) |
| `elallas_is_order_b2b` | `($is_b2b, $order)` | B2B-felismerés felülbírálása |
| `elallas_delivery_date` | `($date, $order)` | Kézbesítési dátum a határidőhöz (a Shipping integráció ezen át tölti) |
| `elallas_pdf_html` | `($html, $context)` | A PDF HTML-jének szűrése |

Példa:

```php
add_filter( 'elallas_deadline_days', fn( $days, $order ) => 30, 10, 2 );
```

## Szavatossági tájékoztató és GARAN címke (1.1.0)

A `Compliance\Bootstrap::boot()` mindig példányosítja az `EmailContext`, `GaranSnapshot`,
`ComplianceAssets`, `CheckoutSlot` és `ComplianceShortcodes` osztályt; a `NoticeHooks` +
`NoticeEmail` csak `notice_enabled`, a `GaranHooks` + `GaranEmail` csak `garan_enabled` mellett
töltődik be. Az admin oldalon (`Bootstrap::boot_admin()`) a `GaranProductFields`,
`ComplianceNotice` és `GaranVariationFields` a modul-kapcsolótól függetlenül fut (az adatok
bekapcsolás előtt is megadhatók). A modul nem függ az elállási funkció `enabled` kapcsolójától.

### Filterek

| Hook | Aláírás | Alapérték / cél |
|---|---|---|
| `elallas_notice_visible` | `(bool $visible, string $context, ?WC_Order $order)` | `true`. A tájékoztató elrejtése adott helyen. `$context`: `product`, `header`, `footer`, `checkout`, `orderpay`, `slot` (blokkos pénztár), `order`, `email`, `shortcode`, `page`. |
| `elallas_notice_language` | `(string $code, string $context)` | Az oldal nyelvéből feloldott kétbetűs kód. Csak a 24 hivatalos nyelv egyike fogadható el, különben az eredeti marad. |
| `elallas_notice_link_url` | `(string $url, string $code)` | A QR-kód hivatalos célja (Your Europe). Csak `https` séma és `europa.eu` vagy aldomainje fogadható el, különben a hivatalos link marad (`NoticeUrlPolicy::link()`). |
| `elallas_notice_image_url` | `(string $url, string $code)` | A hivatalos fájl URL-je. Csak `http(s)` és azonos fájlnév (`notice-<code>.svg` / `.png`) fogadható el, pl. CDN-re költöztetéshez; különben a hivatalos URL marad (`NoticeUrlPolicy::image()`). |
| `elallas_compliance_applies_to_product` | `(bool $applies, WC_Product $product)` | Alap: `true`, kivéve ha a `compliance_exclude_virtual` be van kapcsolva és a termék tisztán virtuális (variálható szülő csak akkor, ha minden variációja az). Kérésenként memoizált. |
| `elallas_compliance_is_b2b` | `(bool $is_b2b, string $context)` | `false`. Csak bekapcsolt `compliance_hide_b2b` mellett, és csak ott hívódik, ahol még nincs rendelés (termékoldal, kosár, pénztár, fejléc, lábléc, shortcode); rendeléses felületen a `B2BDetector` dönt. |
| `elallas_compliance_email_ids` | `(array $ids)` | `['customer_processing_order', 'customer_completed_order', 'customer_on_hold_order', 'customer_invoice']` (`EmailContext::CUSTOMER_EMAIL_IDS`). A tájékoztató és a GARAN ezekbe a WooCommerce e-mailekbe kerül; admin e-mailbe a tájékoztató sosem (`$sent_to_admin`). |
| `elallas_garan_data` | `(?GaranData $data, int $product_id, int $variation_id)` | A meta alapján feloldott adat (`null` = nincs címke). Nem `GaranData` visszatérési érték `null`-nak számít. Kérésenként memoizált. |
| `elallas_garan_checkout_visible` | `(bool $visible, string $context, ?WC_Order $order)` | `true`. A kötelező, rendelés gomb előtti GARAN lista elrejtése; `$context`: `checkout`, `slot`, `orderpay`. Ha bármilyen callback csatolva van rá, az admin figyelmeztet (`ComplianceCheck::GARAN_FILTERED`). |
| `elallas_garan_allow_half_years` | `(bool $allow)` | `false`. Fél éves értékek (2,5–9,5) mentésének engedélyezése; a fél éves értékek jogi/grafikai megítélése nyitott kérdés. |
| `elallas_garan_cart_priority` | `(int $priority)` | `5`. A kosár-GARAN prioritása a `woocommerce_proceed_to_checkout` hookon (az expressz fizetési gombok előtt). Csak bekapcsolt `garan_display_cart` mellett. |
| `elallas_checkout_block_anchors` | `(array $selectors)` | `['.wp-block-woocommerce-checkout-actions-block', '.wc-block-checkout__actions_row', '.wc-block-components-checkout-place-order-button']`. CSS-szelektorok (legspecifikusabb elöl); a blokkos pénztár slotja az első találat külső wrappere elé kerül. |

Példa — a tájékoztató elrejtése a láblécben egy adott oldalon, és a blokkos pénztár egyedi
horgonya:

```php
add_filter( 'elallas_notice_visible', function ( $visible, $context ) {
	return 'footer' === $context && is_front_page() ? false : $visible;
}, 10, 2 );

add_filter( 'elallas_checkout_block_anchors', function ( $selectors ) {
	array_unshift( $selectors, '.my-theme-place-order' );
	return $selectors;
} );
```

### Blokkos pénztár: `CheckoutSlot::contribute()`

A React-alapú (blokkos) pénztár nem futtat PHP hookot a rendelés gomb előtt, ezért a modulok egy
közös gyűjtőbe adnak be markupot:

```php
\LightweightPlugins\Elallas\Compliance\CheckoutSlot::contribute( string $id, int $priority, callable $render ): void
```

- `$id` – egyedi azonosító; azonos azonosítóval a korábbi beadás felülíródik.
- `$priority` – növekvő sorrend; a legnagyobb prioritású kerül legközelebb a gombhoz
  (beépített: `notice` = 10, `garan` = 20).
- `$render` – `string`-et ad vissza (lehet `''`); csak kiírásakor hívódik meg, a saját
  kimenetét neki kell escape-elnie.

A beadás jellemzően a `wp` actionön történik, `CheckoutSlot::is_block_checkout()` ellenőrzés
után (a `order-received` és `order-pay` végpont klasszikus kimenetet használ, ezért ott `false`).
A `wp_footer` 15-ös prioritásán az összes beadás prioritás szerint egy rejtett
`<template id="elallas-checkout-slot">` elembe kerül, az `assets/js/compliance-checkout.js`
pedig egyszer beklónozza a rendelés gomb külső wrappere elé (`elallas_checkout_block_anchors`),
és a React újrarenderelései során is ott tartja. Ha üres a kimenet, se template, se szkript nem
töltődik be.

```php
add_action( 'wp', function () {
	if ( ! \LightweightPlugins\Elallas\Compliance\CheckoutSlot::is_block_checkout() ) {
		return;
	}
	\LightweightPlugins\Elallas\Compliance\CheckoutSlot::contribute(
		'my-note',
		15,
		static fn (): string => '<p class="my-note">' . esc_html__( 'Saját megjegyzés', 'my-theme' ) . '</p>'
	);
} );
```

Admin ellenőrzés: `manage_woocommerce` joggal a pénztár `?elallas_slot_check=1` paraméterrel
megnyitva a szkript jelzi, hogy a slot a horgonyhoz került-e, vagy tartalék-hely lépett életbe.
Tartalék esetén a `lw_elallas_checkout_fallback` transient (30 nap) admin hibaüzenetet vált ki;
sikeres ellenőrzés törli.

### Shortcode-ok

| Shortcode | Attribútumok |
|---|---|
| `[elallas_guarantee_notice]` | `label` (alap: a `notice_label` opció), `mode` = `toggle` (alap) \| `inline` |
| `[elallas_garan_label]` | `product_id` (alap: a globális `$product`, különben `get_the_ID()`), `mode` = `nested` \| `full` (alap: a `garan_product_mode` beállítás) |

Mindkettő `''`-t ad vissza, ha a modulja ki van kapcsolva. Az önálló szavatossági oldalon a
tájékoztató kontextusa `page` (nincs „Megnyitás külön oldalon” önhivatkozás).

## REST API — `/wp-json/elallas-for-woo/v1/`

| Metódus + útvonal | Jogosultság |
|---|---|
| `POST /identify-order` | publikus (nonce + rate limit), semleges hiba |
| `POST /cases` | publikus (X-WP-Nonce: `wp_rest` + rate limit + consent) |
| `GET  /cases/{id}` | `manage_woocommerce` |
| `POST /cases/{id}/confirm` | publikus (e-mail újra-ellenőrzés) |
| `POST /cases/{id}/status` | `manage_woocommerce` |
| `GET  /cases/{id}/document` | `manage_woocommerce` |

```bash
curl -X POST https://example.com/wp-json/elallas-for-woo/v1/identify-order \
  -H 'Content-Type: application/json' \
  -d '{"order_number":"123","email":"vevo@example.com"}'
```

## WP-CLI

```
wp elallas list [--status=<status>] [--deadline=<deadline>] [--format=<table|csv|json|count>]
wp elallas get <id>
wp elallas status <id> <status>
wp elallas stats
wp elallas pdf <id>
wp elallas cleanup
```

## LW Site Manager / Abilities API

A bővítmény a Site Manager (vagy a WordPress Abilities API) jelenléte esetén regisztrálja:

- `elallas/list-cases` – ügyek listázása (szűrőkkel)
- `elallas/get-case` – egy ügy + tételek + események
- `elallas/update-case-status` – státuszváltás (jogosultság: `manage_woocommerce`)
- `elallas/get-audit-log` – ügy audit logja

## Sablon-felülírás

Másold a sablont a témád `elallas-for-woo/` mappájába:

```
your-theme/elallas-for-woo/frontend/{identify,select,confirm,success,denied,my-account}.php
your-theme/elallas-for-woo/emails/{customer-confirmation,admin-notification,status-update}.php
your-theme/elallas-for-woo/pdf/withdrawal-statement.php
```

A szavatossági sablonok (1.1.0) ugyanígy felülírhatók (`Frontend\TemplateLoader::locate()`,
`locate_template( 'elallas-for-woo/<útvonal>' )`):

```
your-theme/elallas-for-woo/frontend/{guarantee-notice,garan-label,checkout-garan-list}.php
your-theme/elallas-for-woo/emails/{guarantee-notice,garan-item}.php
your-theme/elallas-for-woo/emails/plain/{guarantee-notice,garan-item}.php
```

A hivatalos grafika kötelező és nem módosítható: a kép URL-jét, a QR-kód célját, a kitöltött
GARAN SVG-t, a szöveges sort és a Your Europe linket a renderer kész értékként adja át
(`$image_url`, `$link_url`, `$nested_svg`, `$full_svg`, `$text`, `$garan_url`); a felülírás a
keretet stílusozhatja, de ezeket változatlanul ki kell írnia. A változók listája az egyes
sablonfájlok fejlécében van.

## Adatmodell

| Tábla | Tartalom |
|---|---|
| `{prefix}lw_elallas_cases` | elállási ügyek (snapshot határidő, hash-elt/titkosított PII, titkosított `bank_account_encrypted`) |
| `{prefix}lw_elallas_case_items` | érintett tételek pillanatképe (név, SKU, mennyiség, összegek) |
| `{prefix}lw_elallas_events` | audit log (append-only) |
| `{prefix}lw_elallas_documents` | generált dokumentumok (útvonal, SHA-256 hash) |

### Rendelés-meta

`_lw_elallas_has_case` (yes/no) · `_lw_elallas_case_ids` (json) · `_lw_elallas_deadline_status` ·
`_lw_elallas_delivery_date`.

**Termék-meta** (`ProductFields`) és **term-meta** (`TermFields`, `product_cat` / `product_tag`):
`_lw_elallas_excluded` (yes/no) · `_lw_elallas_exclusion_reason`. A `ProductExclusion` resolver
ezeket összegzi (a termék-meta elsőbbséget élvez a kategória/címke felett).

### GARAN meta (1.1.0)

**Termék és variáció** (`GaranProductFields::META_*`, post meta):

| Meta | Termék | Variáció |
|---|---|---|
| `_lw_elallas_garan_enabled` | `yes` / `no` | `''` = öröklés a szülőtől, `yes` = saját adat, `no` = nincs címke |
| `_lw_elallas_garan_years` | időtartam, kanonikus alak (pl. `3`, engedélyezett fél évnél `2.5`) | ugyanaz |
| `_lw_elallas_garan_brand` | gyártó (Brand/Trademark) | ugyanaz |
| `_lw_elallas_garan_model` | modellazonosító | ugyanaz |

Mentéskor a `GaranData` + `GaranMetrics` validál (2 évnél hosszabb időtartam, kötelező gyártó és
modell, elférés a címke fix mezőszélességén); érvénytelen adatnál a termék `no`, a variáció `''`
(öröklés) jelzőt kap, admin hibaüzenettel. A feloldást a `GaranResolver` végzi
(`for_product()`, `for_cart_item()`, `for_order_item()`, `for_wc_product()`), a végén az
`elallas_garan_data` filterrel. A WPML-konfiguráció (`wpml-config.xml`) a négy kulcsot
`copy` módban szinkronizálja.

**Rendelési tétel** — `GaranResolver::ITEM_META` = `_lw_elallas_garan`: a rendeléskori
pillanatkép JSON-ként (`{"years":"3","brand":"…","model":"…","v":"2025-10"}`, ahol `v` az
`OfficialAssets::ASSET_VERSION`). A `GaranSnapshot` a `woocommerce_checkout_create_order_line_item`
hookon írja (klasszikus és blokkos pénztárnál is, WC CRUD-on át, HPOS-safe), a GARAN modul
kapcsolójától függetlenül. A `GaranResolver::for_order_item()` ezt olvassa; pillanatkép nélküli
(régi vagy kézzel létrehozott) rendelésnél az aktuális termékadatra esik vissza.

**User meta:** `lw_elallas_compliance_dismissed` – a szavatossági admin figyelmeztetés
elrejtésének időbélyege (`ComplianceNotice::DISMISS_KEY`, 30 nap).

**Transientek:** `lw_elallas_checkout_fallback` (blokkos pénztár tartalék-hely, 30 nap),
`lw_elallas_garan_integrity_error` (sérült hivatalos GARAN fájl észlelése).

## Opció kulcs

Minden beállítás egy tömbben: `lw_elallas_options`. Olvasás:

```php
\LightweightPlugins\Elallas\Options::get( 'deadline_days', 14 );
```

Az 1.1.0 szavatossági kulcsai (`Options::get_defaults()`, a `TabCompliance` menti őket):

| Kulcs | Alap | Megjegyzés |
|---|---|---|
| `notice_enabled` | `true` | a tájékoztató modul kapcsolója |
| `notice_label` | `'Az Ön jogszabályi szavatossági jogai'` | nyers magyar forrásszöveg, kimenetkor fordítva (WPML / Polylang string) |
| `notice_display_product` | `true` | |
| `notice_display_header` | `false` | `wp_body_open` |
| `notice_display_footer` | `false` | |
| `notice_display_checkout` | `true` | klasszikus + blokkos pénztár + order-pay + rendelésnézet |
| `notice_display_email` | `true` | |
| `notice_email_mode` | `'image'` | `image` \| `attachment` \| `both` |
| `notice_page_id` | `0` | önálló szavatossági oldal |
| `garan_enabled` | `true` | a GARAN modul kapcsolója; a pénztári GARAN-nak nincs külön kulcsa (kötelező) |
| `garan_product_mode` | `'nested'` | `nested` \| `full` \| `description` (a `gallery` 1.1.1-ben) |
| `garan_display_archive` | `false` | |
| `garan_display_cart` | `true` | klasszikus kosár |
| `garan_display_email` | `true` | e-mail és rendelésnézet |
| `compliance_hide_b2b` | `false` | |
| `compliance_exclude_virtual` | `true` | |
| `compliance_reviewed` | `false` | a fül rejtett mezője mentéskor `true`-ra állítja |
| `product_info_enabled` | `true` | 1.1.1-re fenntartva, 1.1.0-ban nincs hatása |

## Egyedi rendelési státuszok (opcionális)

Ha a `use_wc_statuses` be van kapcsolva: `wc-withdrawal-requested`, `wc-withdrawal-review`,
`wc-withdrawal-accepted`, `wc-withdrawal-closed` — az ügy státuszához szinkronizálva.

## Dokumentum-letöltés

A PDF a `wp-content/uploads/elallas-docs/` védett könyvtárba kerül (`.htaccess` deny-all),
nem kitalálható fájlnévvel. Letöltés token-védett: `?elallas_doc=<id>&token=<token>`, ahol a
token **dokumentumonkénti, véletlen, visszavonható** (nem az ID-ből származtatott), vagy
`manage_woocommerce` joggal. A vásárló a Fiókom oldalról a saját nyilatkozatát töltheti le ezen
a tokenes linken (`DocumentService::download_url()`).

## Hivatalos fájlok és épség

A harmonizált tájékoztató (`assets/notice/notice-<code>.svg` minden nyelven, `.png` az angol
kivételével) és a GARAN címke (`assets/garan/garan-label-colour.svg`,
`garan-label-nested.svg`, `garan-label-colour.png`) a Bizottság fájljainak bájtra azonos
másolata. Az `assets/garan/garan-base-colour@4x.png` build időben készül
(`bin/build-garan-base.php`) a hivatalos színes címkéből üres mezőkkel, az e-mail képhez.

- **`assets/CHECKSUMS.sha256`** – a hivatalos fájlok, a build-alap és az Inter 3.19 betűtípus
  SHA-256 listája (`bin/build-compliance-assets.sh` írja). A CI és a Release workflow
  `sha256sum -c assets/CHECKSUMS.sha256`-tel ellenőrzi, a `ComplianceChecksumTest` unit teszt
  pedig a manifeszt teljességét.
- **`Compliance\OfficialAssets`** – tiszta (WordPress-független) nyilvántartás: a 24 nyelv
  (`codes()`, `is_supported()`, `resolve_code()`, tartalék: `hu`), a fájlútvonalak
  (`svg_relpath()`, `png_relpath()`, `garan_svg_relpath()`), a QR-kód célok (`link_url()`), az
  `ASSET_VERSION` (`2025-10`) és a két GARAN SVG várt hash-e (`SVG_SHA256`).
- **Futásidejű ellenőrzés** – a `GaranSource` minden kérésben legfeljebb egyszer beolvassa és
  `SVG_SHA256` alapján ellenőrzi a GARAN SVG-ket; módosított fájl sosem jelenik meg (csak a
  szöveges sor és a link), a hibát naponta legfeljebb egyszer naplózza, és beállítja a
  `lw_elallas_garan_integrity_error` transientet. Az admin oldali
  `ComplianceNotice::garan_files_intact()` ugyanezt ellenőrzi a figyelmeztetéshez és a fül
  *Állapot* sorához.
- **E-mail kép** – a `GaranRaster` GD + FreeType mellett a build-alapra rajzolja a validált
  értékeket (Inter), és az `uploads/elallas-garan/` mappába cache-eli
  (`GaranData::hash( ASSET_VERSION )` alapján); a termék mentésekor előre legenerálja. GD
  FreeType nélkül `''` (szöveges e-mail); kitöltetlen hivatalos képet sosem küld.

## Eltávolítás

Az `uninstall.php` csak bekapcsolt `uninstall_remove_data` mellett töröl. Az 1.1.0-tól ekkor a
korábbiakon felül törli: a `_lw_elallas_garan_enabled` / `_years` / `_brand` / `_model` post
metát (termékek és variációk), a `_lw_elallas_garan` rendelési tétel metát
(`woocommerce_order_itemmeta`), a `lw_elallas_compliance_dismissed` user metát, a
`lw_elallas_checkout_fallback` és `lw_elallas_garan_integrity_error` transientet, valamint az
`uploads/elallas-garan/` mappa generált képeit és magát a mappát. A `notice_page_id` által
mutatott oldal nem törlődik.

## Minőség

`composer phpcs` (WordPress Coding Standards), `composer test` (PHPUnit, domain logika),
`phpstan` (level 5). PSR-4 autoload; osztályok ≤200 sor.
