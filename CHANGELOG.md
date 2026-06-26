# Changelog

## [1.0.11] - 2026-06-26

### Fixed
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
