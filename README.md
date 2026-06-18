# Elallas for WooCommerce

A free, GPL-2.0 WooCommerce plugin that provides the **online withdrawal function** (online elállási funkció) consumers can use to declare their intent to withdraw from a distance contract, and gives the merchant a logged, order-linked, auditable case-management workflow. It is not "just a button": every declaration becomes a verifiable, timestamped case the merchant can administer end to end. HPOS compatible.

## Legal basis

The withdrawal function implements the requirements introduced by **Directive (EU) 2023/2673** (amending the Consumer Rights Directive 2011/83/EU) and, in Hungary, by **415/2025. (XII. 23.) Korm. rendelet** (amending 45/2014. (II. 26.) Korm. rendelet). These rules apply from **19 June 2026** and require online sellers to make an electronic withdrawal function easily reachable and to acknowledge each declaration on a durable medium.

> **Not legal advice.** The bundled legal texts are samples only and do not constitute legal advice. Before going live, validate the final wording against your own terms of service (ÁSZF) and with a Hungarian e-commerce lawyer.

## Features

- **Online withdrawal page and button** — default label `Elállás a szerződéstől`; public page, `[elallas_form]` shortcode, header/footer link, My Account endpoint and an order-details button (reachable within two clicks).
- **Two-step flow** — declaration on the electronic interface, then a separate `Elállás megerősítése` confirmation step with explicit data/intent/consent checkboxes.
- **Durable-medium email** — automatic acknowledgement with the withdrawal data and exact receipt time, plus optional PDF attachment.
- **Full / partial / per-line / per-quantity withdrawal**.
- **Deadline flagging, never blocking** — the 14-day window is calculated and flagged (within / expired / unknown); the merchant keeps the final decision.
- **Order snapshot** — names, SKUs, quantities and totals captured at submission time, so cases stay reconstructable after product or price changes.
- **Audit log** — append-only event log with an optional immutable mode.
- **Case management admin** — filterable cases list and a detailed case view (summary, declaration, order snapshot, audit log, admin decision, documents) under WooCommerce.
- **CSV export** and **PDF withdrawal statement** (dompdf, SHA-256 hash, protected token-gated download).
- **Neutral identification** — wrong order number or email returns the same neutral message to prevent brute forcing.
- **Privacy controls** — IP/UA full/hash/off, email hashing and optional encryption, configurable retention with scheduled cleanup.
- **B2B detection** and **product/category withdrawal exceptions**.
- **Onboarding wizard** — shop data, automatic `/elallas/` page creation, display toggles, deadline and a test step.
- **Gutenberg block & Elementor widget** — place the withdrawal form anywhere; `[elallas_form]` shortcode fallback.
- **Multilingual** — WPML / Polylang / TranslatePress integration; legal texts manageable per language.
- **REST API** — `elallas-for-woo/v1` endpoints (identify, cases, confirm, status, document) with nonce + rate limiting on public routes and `manage_woocommerce` on admin routes.
- **Invoicing & shipping integrations** — Számlázz.hu / Billingo / NAV VAT detection (notes + hooks, no auto-storno) and carrier delivery-date pull (GLS, Packeta/Foxpost, MPL, DPD, Shipment Tracking).
- **LW Site Manager abilities** — list/get cases, update status and read the audit log via the WordPress Abilities API (AI/REST agents).
- **HPOS compatible** — declares compatibility with WooCommerce custom order tables.

## Requirements

- WordPress 6.4+
- WooCommerce 8.0+
- PHP 8.2+

## Installation

Via Composer:

```bash
composer require uptools-io/elallas-for-woo
```

Or download the release ZIP and install it through **Plugins → Add New → Upload Plugin** in WordPress. The release ZIP bundles the vendor dependencies. After activation, go to **WooCommerce → Elállási ügyek → Beállítások**, run the onboarding wizard and create the `/elallas/` withdrawal page.

## Developer notes

### Action hooks

| Hook | Arguments | Fired when |
|------|-----------|------------|
| `elallas_case_created` | `$case_id`, `$order_id` | A withdrawal case has been created |
| `elallas_case_confirmed` | `$case_id` | A case has been confirmed in the two-step flow |
| `elallas_case_status_changed` | `$case_id`, `$old_status`, `$new_status` | A case status changes |

### Filters

| Hook | Signature | Purpose |
|------|-----------|---------|
| `elallas_is_order_eligible` | `($eligible, $order)` | Override whether an order is eligible for withdrawal |
| `elallas_deadline_days` | `($days, $order)` | Override the withdrawal deadline window (default 14) |
| `elallas_pdf_html` | `($html, $context)` | Filter the HTML used to render the PDF statement |
| `elallas_delivery_date` | `($date, $order)` | Provide a carrier delivery date (the bundled Shipping integration resolves it from common carriers / Shipment Tracking) |
| `elallas_is_order_b2b` | `($is_b2b, $order)` | Override B2B detection |

The deadline start can be driven by the delivery date. The bundled shipping
integration resolves it via the `elallas_delivery_date` filter (reading GLS /
Packeta / Foxpost / MPL / DPD / WooCommerce Shipment Tracking meta), and it is
also stored in the `_lw_elallas_delivery_date` order meta.

### WP-CLI

```bash
wp elallas list [--status=<status>] [--deadline=<deadline>] [--format=<table|csv|json|count>]
wp elallas get <id>
wp elallas status <id> <status>
wp elallas stats
wp elallas pdf <id>
wp elallas cleanup
```

## Documentation

- [Kezelési útmutató (HU)](docs/kezelesi-utmutato.md) — install, setup, settings, customer & admin flows.
- [Fejlesztői referencia (HU)](docs/fejlesztoi-referencia.md) — hooks, REST API, WP-CLI, abilities, templates, data model.

## Not legal advice

This plugin ships sample legal and message texts and a compliance-oriented workflow, but it does not constitute legal advice. The legal value lies in keeping the wording and process up to date and validated. Validate the final texts with your own terms of service (ÁSZF) and a Hungarian e-commerce lawyer before relying on them.

## License

GPL-2.0-or-later. See [license.txt](license.txt).

---

By [uptools.io](https://uptools.io) — lightweight WordPress plugins with minimal footprint, no upsells and no tracking.
