# Changelog

## [1.0.13] - 2026-07-15

### Added
- **Full localization — English, Romanian, Czech and Slovak.** The plugin now ships translations for `en_US`, `ro_RO`, `cs_CZ` and `sk_SK` (in addition to the Hungarian source) under `/languages` as `.po` + `.mo`, plus the matching `.json` for the block-editor script. Every admin- and front-end-facing string is covered, and the `.pot` template is regenerated with all current strings.
- **Policy / terms link in every withdrawal e-mail** (opt-in). A new setting adds a link — opening in a new tab — to the foot of all plugin e-mails (customer confirmation, status update and admin notification; HTML + plain). The target URL and its (translatable) label are configurable; when the URL is left empty it falls back to the store's **WooCommerce Terms & Conditions page** (`wc_terms_and_conditions_page_id()`). Handy for pointing a customer to the terms/reason behind a rejection.
- **Editable withdrawal text + smart link in WooCommerce order e-mails.** A new option **"Rendelési e-mail elállási szövege"** (`email_order_text`, translatable) lets the merchant write copy around the withdrawal link; a `{link}` placeholder is replaced with a smart link to the configured withdrawal page (pre-filled with that order via `?order=`), and when the placeholder is omitted the link is appended after the text. Shown only when the "Rendelési e-mailben" display option is on.
- **Status & e-mail guide** — `docs/statuszok-es-emailek.md` documents what each case status means, what it contains, and which of the three e-mails fires on which trigger (clarifying that "Automatikusan visszaigazolva" is the legally required durable-medium acknowledgement of receipt, **not** a merchant approval). Linked from the README and the operations guide.
- **Developer filters** (all `@since 1.0.13`): `elallas_enforce_exclusion` (`($enforced, $order)`) toggles whether excluded items are blocked (default `true`) or only flagged; `elallas_product_exclusion` (`([$excluded, $reason], $product_id)`) enables dynamic per-product exclusions beyond the meta/term settings; `elallas_exclusion_reason_message` (`($message, $excepted, $order)`) customizes the auto-rejection e-mail copy; `elallas_policy_url` (`($url)`) overrides the resolved policy/terms link (e.g. per language).

### Changed
- **Products excluded from withdrawal are now blocked, not just flagged.** Previously the exclusion only added a review flag and the customer could still complete an "auto-confirmed" withdrawal, which the merchant then had to reject by hand. Now, per item: on the selection step an excluded item is not selectable and shows its reason, while the order's remaining eligible items can still be withdrawn (partial withdrawal); if **every** item is excluded the whole request is blocked up front with the reasons listed; and as a server-side backstop (e.g. a tampered submission) a case that still reaches submission with only excluded items is created and immediately set to **Rejected** with the exclusion reason (the status e-mail carries that reason) instead of being auto-confirmed. The same rules apply to the REST create endpoint, and the identify endpoint now returns each item's `excluded` flag + reason. **Deadline handling is unchanged (still flag-only).** This behaviour is reversible per-site: return `false` from the new `elallas_enforce_exclusion` filter to restore the pre-1.0.13 flag-only behaviour.

### Fixed
- **Admin notification e-mail now reaches every configured recipient.** The "Admin recipient" field only worked when addresses were separated by commas; a semicolon-, space- or newline-separated list silently resolved to *no* valid recipient (WooCommerce validates the whole string against `is_email`), so nobody was notified. The value is now normalized through `Options::sanitize_email_list()` — commas, semicolons, spaces and newlines are all accepted, each address is validated and duplicates removed — both on save (new `email_list` field type) and when sending. The field help text documents this.
- **Remaining English UI strings are now Hungarian in the source.** The "autoloader not found" and "WooCommerce must be active" admin notices, the honeypot field label, and the LW Site Manager ability labels/descriptions + error messages were still in English.
- **Public per-order rate-limit throttle now keys on the resolved order ID.** It was keyed on the raw order-number string, so leading zeros / trailing characters (`5`, `05`, `#5`, …) that all resolve to the same order produced distinct throttle buckets, weakening the guard against billing-email guessing. The identify + create paths (front-end and REST) now throttle on the resolved WC order ID.
- **Admin "Order" column link is HPOS-safe.** It hand-built a legacy `post.php?post=…` URL, which points at a non-existent post when High-Performance Order Storage is authoritative; it now uses `WC_Order::get_edit_order_url()`.

### Improved
- **LW Site Manager `elallas/update-case-status` ability accepts an optional reason `message`**, carried into the status-change event and the customer status e-mail. Previously the message was dropped for Site Manager / AI-driven transitions, so an agent can now reject a case *with a reason*.
- **Cases list "Customer" column links to the user profile.** Registered customers now render as a linked display name to the WordPress user-edit screen (`get_edit_user_link()`) instead of the raw `#<user id>`; guests still show "Vendég" and a deleted account falls back to the id.
- **Consent checkboxes on the confirm step are marked as required** with an asterisk and an explanatory note (they already blocked submission via `required`; this makes the requirement visible).
- **The exclusion backstop is bounded.** A refused (rejected) case is terminal, so without a guard a repeated tampered submission would create a new case and re-send the customer a rejection e-mail every time; the backstop now records + notifies at most once per order, and the confirm step gained a per-IP throttle. This closes a repeat-submission e-mail-flooding vector.
- **`withdrawal_type` is quantity-aware** — withdrawing a reduced quantity of a line is now recorded as *partial* (it was mis-recorded as *full* whenever every line was present, regardless of quantity).
- **Custom WooCommerce order statuses (opt-in) track the case more accurately** — the order moves to "withdrawal requested" only once the case is *confirmed* (so a refused/backstop case never parks the order there), and rejected/cancelled cases move to "withdrawal closed".
- **Accessibility of the withdrawal form (WCAG 2.2 AA).** The quantity input has an accessible name; the item tables use `scope="col"` headers + `scope="row"` product headers; the "cannot withdraw" view has a heading; excluded rows are tinted with a solid background instead of `opacity` (which had dropped the exclusion badge/reason text below the AA contrast minimum); and a screen-reader-only "not selectable" label replaces the bare em-dash.
- **Polylang: withdrawal exclusions now apply across translations.** Product/category/tag exclusion meta is read from the default-language original (via `pll_get_post`/`pll_get_term`, and `wpml_object_id` for WPML), so an exclusion set on the source product blocks its translations too.
- **The order-e-mail withdrawal link is no longer appended to admin order e-mails** (it is customer-facing; the hook also fires for the admin copy).

## [1.0.12] - 2026-07-07

### Fixed
- **WPML/Polylang: dynamic strings are now translated on every output path.** The admin-entered strings (`button_label`, `confirm_label`, `legal_declaration`, `legal_confirmation`, `email_customer_extra`) were registered for translation but printed raw from the database, so the `[elallas_button]` label, the confirmation button, the withdrawal declaration, and the extra e-mail text always appeared in the source language. A single `Integrations\Multilingual::translate_option_string()` helper (WPML `wpml_translate_single_string` / Polylang `pll__`, graceful passthrough when neither is active) is now used by the shortcode, the WooCommerce order/e-mail button, the confirm step, the customer e-mail (HTML + plain) and the PDF.
- **WPML: the stored `withdrawal_page_id` is resolved to the translated page.** The `[elallas_button]`/order button link and the front-end asset loading (`is_page()`) now run the page ID through `wpml_object_id`, so on a translated page the button points to the right language's withdrawal page and the CSS/JS still loads.
- **WPML: e-mails and the PDF now render in the case's language.** The submission language is stored as the WPML/Polylang language code (was `determine_locale()`), and the customer confirmation + status-update e-mails and the withdrawal-statement PDF switch to that language while rendering (the admin notification renders in the shop's default language). The PDF `<html lang>` is no longer hard-coded to `hu`.
- **`confirm_label` option is now actually used** — the confirmation step button rendered a hard-coded gettext string and ignored the configured (and translatable) label.
- **Early-translation notice fixed.** `Options::get_defaults()` (read from module constructors on `plugins_loaded`) no longer calls `__()`, which triggered WordPress 6.7's "translation loading was triggered too early" notice for the `elallas-for-woo` text domain.
- **WooCommerce Sequential Order Numbers (Pro) compatibility** (issue #19). The customer sees and types the plugin's sequential order number (e.g. `1436`), which is *not* the WooCommerce order ID — so identifying the order by the typed value always failed. `OrderAdapter::get_order_by_number()` now resolves the entered number via `wc_seq_order_number_pro()->find_order_by_order_number()` (also handles the free plugin and a new `elallas_resolve_order_number` filter for other numbering plugins), and only falls back to the native order ID when no numbering plugin is active. The value is no longer digit-stripped, so prefixes/suffixes in the order number are preserved.
- **The withdrawal button now carries the display order number** (issue #19 follow-up). `Woo\Hooks::order_details_button()` linked to `?order=<native WC id>` and `WithdrawalForm::identify_prefill()` cast it with `absint()`, so on a Sequential Order Numbers (Pro) store the identify step was pre-filled with the internal ID and could not be resolved. The button now passes `$order->get_order_number()` (matching the logged-in order picker), and the prefill is read with `sanitize_text_field()` so a non-numeric display number is preserved.

- **Admin case detail now surfaces the full context** (issue #22). The eligibility flag is shown translated ("Kizárt – ellenőrizendő" / "Jogosult") instead of the raw `excepted`/`eligible`, together with the exclusion reason; the customer note is shown under a clear heading; and each product name links to the product editor.
- **The plugin action link label** is now Hungarian ("Beállítások" instead of "Settings").

### Added
- `wpml-config.xml` declaring the product/category/tag withdrawal-exception meta as `copy` and the `[elallas_button]` `label` attribute as translatable.
- JavaScript translation support for the block editor script (`editor.asset.php` dependency manifest + `wp_set_script_translations()`).
- **Admin notification e-mail warning** (issue #22): a prominent banner and per-item flag when the case contains products excluded from withdrawal, with the reason for each.
- **Sender e-mail settings** (issue #22): "Feladó neve" / "Feladó e-mail címe" (`email_from_name`, `email_from_address`) so the plugin's e-mails can be sent from — and replied to at — a monitored mailbox.
- **Status-change note to the customer** (issue #22): an optional message field on the admin decision form that is logged and included in the status-update e-mail (e.g. the reason for a rejection).
- **Order-screen withdrawal panel** (issue #22): a prominent panel on the WooCommerce order edit screen (legacy + HPOS) listing the order's withdrawal case(s), status, date, a link to the case, and an excluded-item flag.
- **Excluded-items listing** on the Exceptions settings tab (issue #21): the products, categories and tags currently excluded from withdrawal, each with its reason and an edit link.
- **WooCommerce-native logging** (issue #20): a new `Support\Logger` writes to WooCommerce → Status → Logs under the `elallas-for-woo` source. Warnings/errors (failed case insert, failed/exception PDF render, unavailable Dompdf) are always recorded; verbose info/debug (case created/confirmed, status changed, e-mails triggered, rejected identification) are gated behind a new "Debug naplózás" option (default off). Context is scrubbed of PII. `PdfRenderer` now also catches Dompdf exceptions so a PDF failure can no longer break case creation or e-mail sending.
- Regenerated `languages/elallas-for-woo.pot` with all new strings.

## [1.0.11] - 2026-06-26

### Fixed
- PDF generation fatally errored with `Class "FontLib\TrueType\File" not found` as soon as dompdf had to parse a font file (issue #16) — a regression from the 1.0.10 Dompdf scoping. Strauss prefixes namespaces but not the namespaces embedded in dompdf/php-font-lib's *interpolated* class-name strings (`"FontLib\\$class"`) or its index-based type lookup (`getFontType()` returning `$class_parts[1]`). A new post-Strauss build step (`bin/strauss-fixups.php`, run in `release.yml`) rewrites those so the scoped FontLib classes resolve; it self-verifies and fails the build if any unprefixed reference remains. (Simple PDFs using cached core-font metrics were unaffected, which is why earlier tests passed.)
- The admin notification email (`elallas_admin_notification`) was never sent (issues #17, #18). `AdminNotification::trigger()` set `$this->recipient = $this->get_recipient()`, which returns the already-set (empty) recipient — the constructor never sets one and `WC_Email` has no automatic `get_default_recipient()` call. With an empty recipient the send condition was false, so `send()` never ran (no attempt even reached the mail log) and the WooCommerce Emails screen showed an empty recipient. It now uses `get_default_recipient()` (the configured "Admin recipient", falling back to `admin_email`). The customer confirmation and status-update emails were unaffected (they set the recipient from the order).

## [1.0.10] - 2026-06-22

### Fixed
- The bundled Dompdf is now **namespace-scoped** with [Strauss](https://github.com/BrianHenryIE/strauss) under `LightweightPlugins\Elallas\Vendor\`, so it can no longer collide with a Dompdf bundled by another active plugin (issue #15). Before, two plugins shipping Dompdf in the global `\Dompdf\` namespace could mix classes across versions and fatal with e.g. `Call to undefined method Dompdf\LineBox::reset_float_reflow_limit()`. `PdfRenderer` now uses the scoped class and falls back to the global `\Dompdf\Dompdf` when the plugin is installed as a Composer dependency (unscoped). Scoping runs in the release build (`release.yml`); the original global packages are removed from `vendor/`.

## [1.0.9] - 2026-06-22

### Added
- Withdrawal exceptions by **product category** and **product tag**, in addition to the existing per-product exclusion (issue #14). The exclusion is set directly on the category/tag edit screen (a new `Admin\TermFields` adds an "Elállásból kizárt" checkbox + reason, stored as term meta). A new `Domain\ProductExclusion` resolver combines per-product meta with the product's term meta, and `OrderSnapshotBuilder` flags matching items as `excepted` (per-product settings take precedence). The Exceptions settings tab now just explains where to set both. Consistent with product-level exceptions, this flags for review and never auto-blocks.

## [1.0.8] - 2026-06-22

### Fixed
- Logged-in customers could not identify an order that is not linked to their account — e.g. a guest order placed with a different email — because the identify step rendered the order-number field as a dropdown of only their own orders, with no free-text fallback. The order number is now always a free-text input; for logged-in users their own eligible orders are offered as an optional JS quick-pick that fills the field. The ownership binding in `EligibilityChecker` still blocks orders that belong to a *different* registered account.
- The confirm-step double-submit guard in `frontend.js` no longer depends on a select-step element being present (it never ran on the confirm step before).

## [1.0.7] - 2026-06-22

### Fixed
- The WooCommerce email preview (WooCommerce → Settings → Emails) no longer shows a rendering error for the three withdrawal emails. WooCommerce renders the preview without calling `trigger()`, so `$this->object` was not a `WithdrawalCase` (it is a dummy `WC_Order`), and the case-specific templates threw. The emails are now preview-aware: a shared `PreviewableEmailTrait` substitutes a sample `WithdrawalCase` + `CaseItem` when no real case is set. Actual email delivery was never affected.

## [1.0.6] - 2026-06-19

### Changed
- Plugin display name is now "Elállás for WooCommerce" (with the Hungarian accent). The slug (`elallas-for-woo`), text domain, namespace and Composer package name are unchanged.

## [1.0.5] - 2026-06-19

### Added
- Optional bank account / IBAN field on the withdrawal form for the refund destination — encrypted at rest (AES-256-GCM), shown to admins and on the PDF, and cleared by the retention anonymization
- My Account: customers can download their own withdrawal-statement PDF (token-gated)
- Logged-in customers can select from their eligible orders (with the email pre-filled); opening the form from an order (`?order=ID`) pre-selects it. A logged-in owner is verified by ownership, so the email match is not required for them
- Editable extra text appended to the customer confirmation email (Emails settings tab)

### Changed
- Generated document filename is Hungarian (`elallasi-nyilatkozat-…`); the admin and My Account labels show "Elállási nyilatkozat"

## [1.0.4] - 2026-06-19

### Fixed
- Admin case-detail document download link pointed at an unhandled `download_doc` parameter (dead since the initial build). It now uses the token-gated `DownloadHandler` (`?elallas_doc=ID`), with admins authorised via the `manage_woocommerce` capability.

## [1.0.3] - 2026-06-19

### Removed
- The automatic header/footer link (`DisplayLinks`, `display_header`/`display_footer` options). It injected a stray, unstyled link above the theme header. Use the `[elallas_form]`/`[elallas_button]` shortcode, the Gutenberg block, the Elementor widget, a menu item, or the My Account / order-details / order-email surfaces instead.

## [1.0.2] - 2026-06-19

### Changed
- Minimum PHP lowered to 8.0; dependencies pinned to 8.0-compatible versions (`composer config platform.php = 8.0.30`, dompdf with `thecodingmachine/safe` v2, PHPUnit 9.6)

### Build
- The Release workflow now runs a PHP 8.0 validation job (PHPCompatibility ruleset + PHPUnit) first and only publishes the build if it passes
- CI test matrix expanded to PHP 8.0–8.4

## [1.0.1] - 2026-06-18

### Security
- PDF statements use an unguessable filename so they cannot be enumerated on servers that ignore `.htaccess` (Nginx/LiteSpeed)
- Document download tokens are now random, per-document and revocable (no longer derived only from the document ID)
- Authenticated encryption (AES-256-GCM) for PII at rest, with `wp_salt`-derived, purpose-separated keys
- The `confirm` REST endpoint is now rate-limited; added a cross-IP per-order throttle across the public flow
- Honeypot uses a rotating field name plus a signed minimum form-fill-time check
- Logged-in users may only act on their own orders

### Fixed
- The withdrawal-statement PDF is now correctly attached to the customer confirmation email

### Removed
- Unused `immutable_audit` option (the audit log is already append-only)

## [1.0.0] - 2026-06-18

### Added
- Online withdrawal page and button ("Elállás a szerződéstől") with the `[elallas_form]` shortcode
- Two-step flow with the "Elállás megerősítése" confirmation step and explicit consent checkboxes
- Durable-medium customer acknowledgement email with optional PDF attachment
- Reachable-in-two-clicks surfaces: My Account endpoint, order-details button, header/footer link
- Full, partial, per-line and per-quantity withdrawal
- Eligibility checking with deadline flagging (within / expired / unknown), never auto-blocking
- Order snapshot so cases stay reconstructable after product or price changes
- Append-only audit log
- Case management admin: filterable cases list and detailed case view (summary, declaration, order snapshot, audit log, admin decision, documents)
- CSV export of the cases matching the current filters
- PDF withdrawal statement (dompdf) with SHA-256 file hash and token-gated, protected download
- Neutral identification error to prevent order-number brute forcing
- Privacy controls: IP/UA stored full/hash/off, email hashing and optional encryption, configurable retention with scheduled anonymization (daily cron + `wp elallas cleanup`)
- B2B detection and product-level withdrawal exceptions
- Master enable switch, configurable eligible order statuses, and optional custom `wc-withdrawal-*` order statuses
- Onboarding wizard (shop data, page creation, display, deadline, test)
- Gutenberg "Elállási űrlap" block and Elementor widget
- Multilingual integration (WPML / Polylang / TranslatePress)
- Invoicing detection (Számlázz.hu / Billingo / NAV VAT) and carrier delivery-date integration (GLS, Packeta/Foxpost, MPL, DPD, Shipment Tracking)
- REST API under `elallas-for-woo/v1`
- LW Site Manager Abilities API integration
- WP-CLI commands: `wp elallas list / get / status / stats / pdf / cleanup`
- HPOS (custom order tables) compatibility
- Developer hooks: actions `elallas_case_created`, `elallas_case_confirmed`, `elallas_case_status_changed`, `elallas_invoicing_case_created`; filters `elallas_is_order_eligible`, `elallas_deadline_days`, `elallas_is_order_b2b`, `elallas_delivery_date`, `elallas_pdf_html`
