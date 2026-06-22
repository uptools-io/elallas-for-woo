=== Elállás for WooCommerce ===
Contributors: uptools
Tags: woocommerce, withdrawal, refund, gdpr, compliance
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 8.0
WC tested up to: 9.9

Compliant online withdrawal (elállás) button and audited case management for WooCommerce. EU 2023/2673 and 415/2025 Korm. rendelet ready.

== Description ==

Elállás for WooCommerce adds the **online withdrawal function** (online elállási funkció) that distance sellers must provide, and turns each declaration into a logged, order-linked, auditable case for the merchant. It is not "just a button": it is a legally defensible, verifiable, timestamped process plus an administrable workflow.

= Legal basis =

The function implements the requirements introduced by **Directive (EU) 2023/2673** (which amends the Consumer Rights Directive 2011/83/EU) and, in Hungary, by **415/2025. (XII. 23.) Korm. rendelet** (amending 45/2014. (II. 26.) Korm. rendelet). These rules apply from **19 June 2026** and require online sellers to make an electronic withdrawal function easily reachable and to acknowledge each declaration on a durable medium.

> **This plugin is not legal advice.** The bundled legal texts are samples only. Before going live you must validate the final wording against your own terms of service (ÁSZF) and with a Hungarian e-commerce lawyer. The text samples shipped here are not a substitute for professional legal review.

= Key features =

* **Online withdrawal page and button** — default label "Elállás a szerződéstől" (the legal wording, not "rendeléstől"). Public page, shortcode `[elallas_form]`, Gutenberg block / Elementor widget, My Account endpoint and an order-details button.
* **Reachable in two clicks** — the withdrawal function is placed where customers can find it from their account or order-details page, "well visible and easily accessible".
* **Two-step flow** — the consumer fills the declaration on the electronic interface, then finalises it with a separate **"Elállás megerősítése"** confirmation button, with explicit data/intent/consent checkboxes.
* **Durable-medium email** — an automatic acknowledgement email containing the withdrawal data and the exact date/time of receipt, plus an optional PDF attachment.
* **Full or partial withdrawal** — per order, per line item and per quantity.
* **Deadline flagging, never blocking** — the 14-day window is calculated and flagged (within / expired / unknown) but never auto-rejected, so the merchant keeps the final decision on edge cases and extended deadlines.
* **Order snapshot** — product names, SKUs, quantities and totals are stored at submission time, so a case stays reconstructable even if the product or price changes later.
* **Audit log** — every event (who, when, what) is recorded in an append-only events table, with an optional immutable mode.
* **Case management admin** — a filterable case list and a detailed case view (summary, customer declaration, order snapshot, audit log, admin decision, documents) under WooCommerce.
* **CSV export** — export the cases matching your current filters.
* **PDF withdrawal statement** — generated via dompdf with an SHA-256 file hash, stored in a protected directory and served through a token-gated download (direct URL access blocked). HTML fallback available.
* **Neutral identification** — a wrong order number or email returns the same neutral message, so order numbers cannot be brute-forced to reveal customer data.
* **Privacy controls** — IP and user agent stored as full / hash / off, email hashed for lookup and optionally encrypted, configurable retention with scheduled cleanup, and WordPress export/erasure friendly storage.
* **B2B detection** — likely-B2B orders (company name / VAT number) are flagged so the consumer-only right is applied correctly.
* **Product and category exceptions** — mark products as withdrawal exceptions (with a legal-risk warning); surfaced on the form.
* **Onboarding wizard** — shop data, automatic creation of the `/elallas/` page, display toggles, deadline and a test step.
* **Gutenberg block & Elementor widget** — drop the withdrawal form into any page or template; the `[elallas_form]` shortcode is the universal fallback.
* **Multilingual ready** — WPML, Polylang and TranslatePress integration; legal texts are manageable per language so the declaration can be accepted in the customer's chosen language.
* **REST API** — endpoints under `elallas-for-woo/v1` (identify order, create / confirm / manage cases, document) with nonce + rate limiting on public routes and `manage_woocommerce` checks on admin routes.
* **Invoicing & shipping integrations** — Számlázz.hu / Billingo / NAV VAT detection (order notes + action hooks, no automatic storno) and carrier delivery-date pull (GLS, Packeta/Foxpost, MPL, DPD, WooCommerce Shipment Tracking) for accurate deadline calculation.
* **AI / Site Manager ready** — LW Site Manager Abilities API integration (list/get cases, update status, read the audit log) for REST and AI agents.
* **WP-CLI** — manage cases from the command line: `wp elallas list / get / status / stats / pdf / cleanup`.
* **HPOS compatible** — declares compatibility with WooCommerce High-Performance Order Storage.

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
