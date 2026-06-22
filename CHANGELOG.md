# Changelog

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
