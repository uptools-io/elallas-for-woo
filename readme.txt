=== Elállás for WooCommerce ===
Contributors: uptools
Tags: woocommerce, withdrawal, refund, gdpr, compliance
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 8.0
WC tested up to: 11.1

Compliant online withdrawal (elállás) button and audited case management for WooCommerce. EU 2023/2673 and 415/2025 Korm. rendelet ready.

== Description ==

Elállás for WooCommerce adds the **online withdrawal function** (online elállási funkció) that distance sellers must provide, and turns each declaration into a logged, order-linked, auditable case for the merchant. It is not "just a button": it is a legally defensible, verifiable, timestamped process plus an administrable workflow.

= Legal basis =

The function implements the requirements introduced by **Directive (EU) 2023/2673** (which amends the Consumer Rights Directive 2011/83/EU) and, in Hungary, by **415/2025. (XII. 23.) Korm. rendelet** (amending 45/2014. (II. 26.) Korm. rendelet). These rules apply from **19 June 2026** and require online sellers to make an electronic withdrawal function easily reachable and to acknowledge each declaration on a durable medium.

> **This plugin is not legal advice.** The bundled legal texts are samples only. Before going live you must validate the final wording against your own terms of service (ÁSZF) and with a Hungarian e-commerce lawyer. The text samples shipped here are not a substitute for professional legal review.

= Key features =

* **Online withdrawal page and button** — default label "Elállás a szerződéstől" (the legal wording, not "rendeléstől"). Public page, shortcode `[elallas_form]`, Gutenberg block / Elementor widget, My Account endpoint and an order-details button.
* **Reachable in two clicks** — the withdrawal function is placed where customers can find it from their account or order-details page, "well visible and easily accessible".
* **Guest-friendly identification** — works without an account. Logged-in customers get their email pre-filled and a quick-pick of their own eligible orders; the order number can always be typed in manually, so a guest order placed with a different email can be identified too. Opening the form from an order (?order=ID) pre-selects it, and a logged-in customer cannot act on another account's order.
* **Self-service in My Account** — customers see their previous withdrawal cases and can download their own withdrawal-statement PDF via a token-gated link.
* **Two-step flow** — the consumer fills the declaration on the electronic interface, then finalises it with a separate **"Elállás megerősítése"** confirmation button, with explicit data/intent/consent checkboxes. An optional refund bank account / IBAN (stored encrypted) and a free-text note can be added.
* **Durable-medium email** — an automatic acknowledgement email containing the withdrawal data and the exact date/time of receipt, plus an optional PDF attachment and editable extra text appended to the customer email.
* **Full or partial withdrawal** — per order, per line item and per quantity.
* **Deadline flagging, never blocking** — the 14-day window is calculated and flagged (within / expired / unknown) but never auto-rejected, so the merchant keeps the final decision on edge cases and extended deadlines.
* **Order snapshot** — product names, SKUs, quantities and totals are stored at submission time, so a case stays reconstructable even if the product or price changes later.
* **Audit log** — every event (who, when, what) is recorded in an append-only events table.
* **Case management admin** — a filterable case list and a detailed case view (summary incl. the refund bank account, customer declaration, order snapshot, audit log, admin decision, documents) under WooCommerce.
* **CSV export** — export the cases matching your current filters.
* **PDF withdrawal statement** — generated via dompdf with an SHA-256 file hash and an unguessable filename, stored in a protected directory and served through a token-gated download (direct URL access blocked). HTML fallback available.
* **Neutral identification** — a wrong order number or email returns the same neutral message, so order numbers cannot be brute-forced to reveal customer data.
* **Privacy controls** — IP and user agent stored as full / hash / off, email hashed for lookup and optionally encrypted, bank account encrypted at rest, configurable retention with scheduled anonymization, and WordPress export/erasure friendly storage.
* **B2B detection** — likely-B2B orders (company name / VAT number) are flagged so the consumer-only right is applied correctly.
* **Exceptions by product, category and tag** — exclude individual products (on the product), or whole product categories / tags (on the category/tag edit screen) from withdrawal, with a reason and a legal-risk warning; matching items are flagged in the case (per-product setting wins, and it flags for review rather than auto-blocking).
* **Onboarding wizard** — shop data, automatic creation of the `/elallas/` page, display toggles, deadline and a test step.
* **Gutenberg block & Elementor widget** — drop the withdrawal form into any page or template; the `[elallas_form]` shortcode is the universal fallback.
* **Multilingual ready** — WPML, Polylang and TranslatePress integration; legal texts are manageable per language so the declaration can be accepted in the customer's chosen language.
* **REST API** — endpoints under `elallas-for-woo/v1` (identify order, create / confirm / manage cases, document) with nonce + rate limiting on public routes and `manage_woocommerce` checks on admin routes.
* **Invoicing & shipping integrations** — Számlázz.hu / Billingo / NAV VAT detection (order notes + action hooks, no automatic storno) and carrier delivery-date pull (GLS, Packeta/Foxpost, MPL, DPD, WooCommerce Shipment Tracking) for accurate deadline calculation.
* **AI / Site Manager ready** — LW Site Manager Abilities API integration (list/get cases, update status, read the audit log) for REST and AI agents.
* **WP-CLI** — manage cases from the command line: `wp elallas list / get / status / stats / pdf / cleanup`.
* **HPOS compatible** — declares compatibility with WooCommerce High-Performance Order Storage.

= EU legal-guarantee notice and GARAN label (from 27 September 2026) =

From **27 September 2026** online shops selling goods to consumers must show the **harmonised EU notice on the legal guarantee** and, where the manufacturer offers a commercial guarantee of durability longer than two years, the **harmonised GARAN label** — both in the mandatory form set by **Commission Implementing Regulation (EU) 2025/1960** (based on Directive (EU) 2024/825). In Hungary the rules are added to 45/2014. (II. 26.) Korm. rendelet by 116/2026. (VII. 30.) Korm. rendelet: the notice and the label must be shown "clearly visible" (11. § (1a)), and the durability guarantee must be pointed out right before the order is placed (15. § (1)).

The "Szavatosság és GARAN" settings tab adds both:

* **Harmonised legal-guarantee notice** — the official, unmodified European Commission notice in the page language (24 official languages, Hungarian fallback), with the link next to it pointing to the same Your Europe page as the notice's QR code. Opens from a short label (click/hover) or is shown inline. Placements: product page (under the add-to-cart form), header, footer, before the order button on the classic checkout, the block checkout and the "Pay for order" page, the order view (thank-you page, My Account) and the customer order e-mails (processing, completed, on hold, invoice) as an image, a colour PNG attachment or both. A standalone notice page (`/szavatossag/`) can be created with one click.
* **GARAN label** — the official label filled with the durability period (whole years, more than 2), the manufacturer (brand/trademark) and the model identifier. It is shown only for products where it is switched on in the product editor ("Gyártói tartóssági jótállás (GARAN címke)" with "Időtartam (év)", "Gyártó (Brand/Trademark)" and "Modellazonosító"; the values are checked to fit the label's fixed field widths, and a preview is shown). Variations inherit the parent's data by default or can have their own data or no label ("GARAN címke" on the variation: inherit / own data / no label); the label on the product page follows the selected variation.
* **Mandatory GARAN before the order button** — while the GARAN module is on, the labels of the covered items are always listed right before the order / pay button (classic checkout, block checkout, "Pay for order" page). Product page (nested label that opens the full label, full label under the add-to-cart button, or full label under the description), cart, product lists and order e-mails are configurable. In e-mails the label is a PNG image (when the host has GD with FreeType) plus a text line and links. The label data is stored on the order line at checkout, so later product edits do not change past orders.
* **Shortcodes** — `[elallas_guarantee_notice]` (attributes `label` and `mode="toggle|inline"`) and `[elallas_garan_label]` (attributes `product_id` and `mode="nested|full"`), for Elementor Pro, customised block templates or any other placement (Shortcode block / widget). Both print nothing while their module is switched off.
* **Scope and B2B** — only goods are covered: purely virtual (digital) products can be excluded, and the output can be hidden for business (B2B) orders.
* **Configuration check** — an admin notice warns before and after the deadline when the notice is off or not placed, the checkout placement is off, the GARAN label is off, a filter may hide the mandatory GARAN, or the settings were not reviewed yet. Tampered official GARAN files are detected (SHA-256) and the label is then not shown.
* **Block themes** — the output follows the add-to-cart block (`woocommerce/add-to-cart-form` or `woocommerce/add-to-cart-with-options`), and also works when the product is rendered through the classic product template block.

= Third-party assets =

* **European Commission files** — the harmonised notice (PNG and SVG, `assets/notice/`) and the GARAN label (`assets/garan/`) are byte-identical, unmodified copies of the official files published by the European Commission ([Practical guidelines and high-resolution vector files – EU notice and label for product guarantees](https://commission.europa.eu/publications/practical-guidelines-and-high-resolution-vector-files-eu-notice-and-label-product-guarantees_en)). `assets/garan/garan-base-colour@4x.png` is rendered at build time from the official colour label with empty fields, for the e-mail image. Integrity is verified against `assets/CHECKSUMS.sha256`.
* **Inter 3.19** — the font used to fill the GARAN label (`assets/fonts/inter/`, Regular and ExtraBold, TTF and WOFF2), licensed under the SIL Open Font License 1.1 (`assets/fonts/inter/OFL.txt`).

= Known limitations =

* The GARAN label appears as an image in e-mails only when the host has GD with FreeType support; otherwise it is sent as text (years, manufacturer, model) and a link.
* The GARAN label on product lists (archives) works with classic themes only; on block themes it appears on the product page, in the cart and at checkout.
* Until 1.1.1, state software update and repairability information in the product description.
* The Gutenberg block and Elementor widget arrive in 1.1.1; until then place the shortcodes with the Shortcode block / widget.
* The GARAN label is not shown before the express payment buttons of the block cart (it is in the classic cart).

= By uptools.io =

Elállás for WooCommerce is built by [uptools.io](https://uptools.io) — lightweight WordPress plugins with minimal footprint, no upsells and no tracking.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/elallas-for-woo`, or install the release ZIP through the 'Plugins' screen in WordPress.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Make sure WooCommerce 8.0+ is installed and active.
4. Go to WooCommerce > Elállási ügyek > Beállítások, run the onboarding wizard and create the `/elallas/` withdrawal page.

Or install via Composer:

`composer require uptools-io/elallas-for-woo`

When installed from a release ZIP the vendor dependencies are bundled. When installed via Composer they are resolved by your project's autoloader.

== Frequently Asked Questions ==

= Does this require WooCommerce? =

Yes. WooCommerce 8.0+ must be installed and active — the plugin works on WooCommerce orders.

= Is this plugin legal advice? =

No. The bundled texts are samples only and do not constitute legal advice. Validate the final wording with your own terms of service (ÁSZF) and a Hungarian e-commerce lawyer before going live.

= Does it block withdrawals after the 14-day deadline? =

No. The deadline is calculated and flagged (within / expired / unknown) but never used to auto-reject a declaration. Deadlines can shift due to delivery dates, exceptions or merchant decisions, so the merchant always keeps the final decision.

= Is it HPOS compatible? =

Yes. The plugin declares compatibility with WooCommerce High-Performance Order Storage (custom order tables).

= Can a customer withdraw from only part of an order? =

Yes. Full, partial, per-line-item and per-quantity withdrawal are all supported.

= How is customer data protected? =

The email is hashed for lookup and can be encrypted at rest. IP and user agent can be stored full, hashed or not at all. A configurable retention period with scheduled cleanup is available, and the identification step returns a neutral error so order numbers cannot be brute-forced.

= Can I customise the legal and email texts? =

Yes. The declaration, confirmation and other texts are editable in the Legal and Emails settings tabs, and email templates can be overridden from your theme's `elallas-for-woo/` directory.

== Screenshots ==

1. Public withdrawal page with the "Elállás a szerződéstől" button
2. Two-step flow: item selection and confirmation
3. Cases list table under WooCommerce
4. Case detail view with order snapshot and audit log
5. Settings page with tabbed interface
6. Onboarding wizard

== Changelog ==

= 1.1.0 =
* New: harmonised EU legal-guarantee notice (Implementing Regulation (EU) 2025/1960, mandatory from 27 September 2026): the official Commission notice in 24 languages with its Your Europe link, on the product page, header, footer, before the order button (classic checkout, block checkout, "Pay for order" page), in the order view and in customer order e-mails (image, colour PNG attachment or both), plus a one-click standalone notice page.
* New: GARAN durability label: product and variation fields (period, manufacturer, model identifier) with fit checks and an editor preview, the filled official label on the product page (nested, full, or under the description), in the cart, on product lists and in order e-mails (PNG image with GD FreeType, otherwise text), and always before the order / pay button while the module is on. The label data is stored on the order line at checkout.
* New: `[elallas_guarantee_notice]` and `[elallas_garan_label]` shortcodes.
* New: "Szavatosság és GARAN" settings tab with goods-only scope, virtual-product exclusion and B2B hiding.
* New: compliance admin notice for the 27 September 2026 deadline (notice off or not placed, checkout placement off, GARAN label off, GARAN hidden by a filter, settings not reviewed), with a 30-day dismiss; errors for tampered official GARAN files and a misplaced block-checkout slot.
* New: block theme support for the product-page output after the `woocommerce/add-to-cart-form` and `woocommerce/add-to-cart-with-options` blocks and inside the classic product template block; several products on one page each get their own output.
* New: official European Commission notice and GARAN files and the Inter 3.19 font (SIL OFL 1.1), verified by SHA-256 checksums.
* New: English, Czech, Romanian and Slovak translations for the new strings.
* Change: declared WooCommerce cart and checkout blocks compatibility; WC tested up to 11.1.

= 1.0.14 =
* Fix: the release package and Composer dist no longer ship tests, docs or development configuration

= 1.0.13 =
* New: English, Romanian, Czech and Slovak translations.
* Fix: Admin notification e-mail now goes to every configured recipient, not only the first (issue #25) — comma, semicolon or space separated lists are all supported; invalid and duplicate addresses are dropped.
* Update: Internal quality gates (PHPCS, PHPUnit, PHPStan level 5) now run in CI. No functional change beyond the fix above.

= 1.0.12 =
* New: WPML/Polylang compatibility — admin-entered dynamic strings (button label, confirm label, withdrawal declaration, extra e-mail text) are now translated on every output path, the withdrawal page ID resolves to the translated page, and e-mails/PDF render in the case's language.
* New: WooCommerce Sequential Order Numbers (Pro) compatibility (issue #19) — the order is resolved by the sequential number the customer sees, and the withdrawal button/link now carries that same display number so the identify step is pre-filled correctly.
* New: WooCommerce-native logging (issue #20) under WooCommerce → Status → Logs (source "elallas-for-woo"); warnings/errors always logged, verbose info/debug behind a "Debug naplózás" option, PII scrubbed. PDF rendering now degrades gracefully instead of breaking case creation.
* New: Exceptions settings tab lists the products, categories and tags excluded from withdrawal, each with its reason and an edit link (issue #21).
* New: Sender e-mail settings, status-change note to the customer, an order-screen withdrawal panel (legacy + HPOS), and an admin-notification warning when an excluded product is in the case (issue #22).
* Fix: admin case detail shows the translated eligibility flag and exclusion reason, the customer note, and product-editor links (issue #22).
* Fix: WordPress 6.7 "translation loading was triggered too early" notice.

= 1.0.11 =
* Fix: PDF generation could fatal with "Class FontLib\TrueType\File not found" once dompdf had to parse a font (a regression from the 1.0.10 Dompdf scoping). The bundled font library's dynamic class references are now correctly namespaced in the build.
* Fix: the admin notification email ("Új elállási nyilatkozat (admin)") was never sent because its recipient was never set — trigger() read the (empty) current recipient instead of the configured admin recipient. It now uses the configured "Admin recipient" (falling back to the site admin email), so the notification is actually delivered. The customer confirmation email was unaffected.

= 1.0.10 =
* Fix: the bundled Dompdf is now namespace-scoped (Strauss) under LightweightPlugins\Elallas\Vendor\, so it can no longer collide with a Dompdf shipped by another active plugin (e.g. a PDF-invoice plugin) — which previously could cause a fatal error such as "Call to undefined method Dompdf\LineBox::reset_float_reflow_limit()". When installed via Composer (unscoped), the renderer falls back to the host project's Dompdf.

= 1.0.9 =
* New: withdrawal exceptions can now be set by product category and product tag, not just per product. Open a product category or tag, tick "Elállásból kizárt" and pick a reason — products in it are flagged as excepted in the case (per-product settings take precedence). Like product-level exceptions, this flags for review and never auto-blocks.

= 1.0.8 =
* Fix: a logged-in customer can now also identify a guest order placed with a different email. The order field is always a free-text input again; for logged-in users their own eligible orders are offered as an optional quick-pick dropdown that fills it. Orders belonging to a different registered account stay blocked.

= 1.0.7 =
* Fix: the WooCommerce email preview (WooCommerce → Settings → Emails) no longer shows a rendering error for the withdrawal emails. They are now preview-aware and render with sample data when no real case object is present. Actual email delivery was never affected.

= 1.0.6 =
* Change: plugin display name is now "Elállás for WooCommerce" (with the Hungarian accent). The slug, text domain and package name are unchanged.

= 1.0.5 =
* New: optional bank account / IBAN field on the form (encrypted at rest, shown to admins and on the PDF, anonymized by the retention cleanup)
* New: customers can download their own withdrawal-statement PDF from My Account
* New: logged-in customers can pick from their eligible orders and the email is pre-filled; opening the form from an order pre-selects it
* New: editable extra text appended to the customer confirmation email (Emails settings)
* Change: the generated document name is Hungarian (elallasi-nyilatkozat-…) and the admin/My Account label shows "Elállási nyilatkozat"

= 1.0.4 =
* Fix: the document download link on the admin case-detail page now works — it pointed at an unhandled `download_doc` parameter; it now uses the token-gated download handler (admins are authorised via capability). This was broken since the initial release, not a regression.

= 1.0.3 =
* Change: removed the automatic header/footer link (it injected a stray, unstyled link above the theme header). Place the withdrawal link yourself via the shortcode, Gutenberg block, Elementor widget, or a menu — the My Account, order-details and order-email surfaces are unchanged.

= 1.0.2 =
* Change: minimum PHP lowered to 8.0 (dependencies pinned to 8.0-compatible versions)
* Build: releases are now gated on a PHP 8.0 validation job (PHPCompatibility + tests) — no build is published unless it passes

= 1.0.1 =
* Security: PDF statements now use an unguessable filename so they cannot be enumerated on servers that ignore .htaccess (Nginx/LiteSpeed)
* Security: document download tokens are now random, per-document and revocable (no longer derived only from the document ID)
* Security: authenticated encryption (AES-256-GCM) for PII at rest, with wp_salt-derived, purpose-separated keys
* Security: the confirm REST endpoint is now rate-limited, plus a cross-IP per-order throttle on the public flow
* Security: honeypot now uses a rotating field name and a minimum form-fill-time check
* Security: logged-in users may only act on their own orders
* Fix: the withdrawal-statement PDF is now correctly attached to the customer confirmation email

= 1.0.0 =
* New: Online withdrawal page and button ("Elállás a szerződéstől") with shortcode `[elallas_form]`
* New: Two-step flow with the "Elállás megerősítése" confirmation step and explicit consent checkboxes
* New: Durable-medium customer acknowledgement email with optional PDF attachment
* New: Reachable-in-two-clicks surfaces — My Account endpoint, order-details button, header/footer link
* New: Full, partial, per-line and per-quantity withdrawal
* New: Eligibility checking with deadline flagging (within / expired / unknown), never auto-blocking
* New: Order snapshot so cases stay reconstructable after product/price changes
* New: Append-only audit log with optional immutable mode
* New: Case management admin — filterable cases list and detailed case view
* New: CSV export of the cases matching the current filters
* New: PDF withdrawal statement (dompdf) with SHA-256 hash and token-gated, protected download
* New: Neutral identification error to prevent order-number brute forcing
* New: Privacy controls — IP/UA full/hash/off, email hashing and optional encryption, configurable retention
* New: B2B detection and product/category withdrawal exceptions
* New: Onboarding wizard (shop data, page creation, display, deadline, test)
* New: Gutenberg block and Elementor widget for the withdrawal form
* New: Multilingual integration (WPML, Polylang, TranslatePress)
* New: REST API under `elallas-for-woo/v1` (identify, cases, document)
* New: Invoicing detection (Számlázz.hu, Billingo, NAV VAT) and carrier delivery-date pull (GLS, Packeta/Foxpost, MPL, DPD, Shipment Tracking)
* New: LW Site Manager Abilities API integration for AI/REST agents
* New: WP-CLI commands (wp elallas list/get/status/stats/pdf/cleanup)
* New: HPOS (custom order tables) compatibility
* New: Developer hooks (actions and filters) for extension

== Upgrade Notice ==

= 1.0.0 =
Initial release. Adds the EU 2023/2673 and 415/2025 Korm. rendelet online withdrawal function and audited case management to WooCommerce.
