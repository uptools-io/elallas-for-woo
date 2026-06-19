# Elállás for WooCommerce — Fejlesztői referencia

PSR-4, `LightweightPlugins\Elallas\` névtér, `includes/` mappa. HPOS-kompatibilis (minden
rendelés-hozzáférés WooCommerce CRUD-on keresztül megy, az `OrderAdapter`-ben).

## Architektúra (modulok)

| Mappa | Felelősség |
|---|---|
| `Database/` | `Schema` (4 tábla) + repository-k (`CaseRepository`, `CaseItemRepository`, `EventRepository`, `DocumentRepository`, `CaseQuery`) |
| `Models/` | `WithdrawalCase`, `CaseItem`, `CaseStatus`, `DeadlineStatus` |
| `Domain/` | `CaseService`, `EligibilityChecker`, `DeadlineCalculator`, `B2BDetector`, `OrderSnapshotBuilder`, `CaseNumberGenerator` |
| `Frontend/` | flow (`FormHandler`, `StepProcessor`, `FormRequest`, `WithdrawalForm`), `Shortcodes`, `MyAccountEndpoint`, `DisplayLinks`, `Assets`, `TemplateLoader`, `SubmissionContext` |
| `Admin/` | menü, `CasesListTable`, ügy-részletek, `Settings/`, `Onboarding/`, `ProductFields` |
| `Emails/` | `EmailManager` + 3 `WC_Email` osztály |
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

## Adatmodell

| Tábla | Tartalom |
|---|---|
| `{prefix}lw_elallas_cases` | elállási ügyek (snapshot határidő, hash-elt/titkosított PII) |
| `{prefix}lw_elallas_case_items` | érintett tételek pillanatképe (név, SKU, mennyiség, összegek) |
| `{prefix}lw_elallas_events` | audit log (append-only) |
| `{prefix}lw_elallas_documents` | generált dokumentumok (útvonal, SHA-256 hash) |

### Rendelés-meta

`_lw_elallas_has_case` (yes/no) · `_lw_elallas_case_ids` (json) · `_lw_elallas_deadline_status` ·
`_lw_elallas_delivery_date`. Termék-meta: `_lw_elallas_excluded` (yes/no) ·
`_lw_elallas_exclusion_reason`.

## Opció kulcs

Minden beállítás egy tömbben: `lw_elallas_options`. Olvasás:

```php
\LightweightPlugins\Elallas\Options::get( 'deadline_days', 14 );
```

## Egyedi rendelési státuszok (opcionális)

Ha a `use_wc_statuses` be van kapcsolva: `wc-withdrawal-requested`, `wc-withdrawal-review`,
`wc-withdrawal-accepted`, `wc-withdrawal-closed` — az ügy státuszához szinkronizálva.

## Dokumentum-letöltés

A PDF a `wp-content/uploads/elallas-docs/` védett könyvtárba kerül (`.htaccess` deny-all).
Letöltés token-védett: `?elallas_doc=<id>&token=<hmac>` vagy `manage_woocommerce` joggal.

## Minőség

`composer phpcs` (WordPress Coding Standards), `composer test` (PHPUnit, domain logika),
`phpstan` (level 5). PSR-4 autoload; osztályok ≤200 sor.
