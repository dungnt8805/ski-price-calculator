# Ski Engine Plugin

Ski Engine is a custom WordPress plugin for managing ski destinations, hotels, pricing, and inquiry workflows.

## Plugin Info

- Name: Ski Engine
- Version: 2.0
- Text domain: `ski-price-calculator`
- Main file: [plugins/ski-engine/ski-engine-ultimate.php](plugins/ski-engine/ski-engine-ultimate.php)

A WordPress.org-style readme already exists at [plugins/ski-engine/readme.txt](plugins/ski-engine/readme.txt). This README.md is a practical developer guide for this repository.

## Core Modules

- Data layer and table setup:
  - [plugins/ski-engine/includes/class-spcu-database.php](plugins/ski-engine/includes/class-spcu-database.php)
- Admin pages:
  - [plugins/ski-engine/admin/class-spcu-admin.php](plugins/ski-engine/admin/class-spcu-admin.php)
- Frontend and templates:
  - [plugins/ski-engine/includes/class-spcu-frontend.php](plugins/ski-engine/includes/class-spcu-frontend.php)
  - [plugins/ski-engine/templates/area-single.php](plugins/ski-engine/templates/area-single.php)
- Inquiry and email:
  - [plugins/ski-engine/includes/class-spcu-inquiry.php](plugins/ski-engine/includes/class-spcu-inquiry.php)
- Calculator and shortcodes:
  - [plugins/ski-engine/includes/class-spcu-shortcode.php](plugins/ski-engine/includes/class-spcu-shortcode.php)
- REST API:
  - [plugins/ski-engine/includes/class-spcu-api.php](plugins/ski-engine/includes/class-spcu-api.php)

## Data Model Summary

Main tables (prefix omitted):

- `spcu_prefectures`
- `spcu_areas`
- `spcu_hotels`
- `spcu_prices`
- `spcu_addon_prices`
- `spcu_difficulties`
- `spcu_grades`
- `spcu_inquiries`

### Important Domain Split

- `Difficulty` is for terrain profile (beginner/intermediate/advanced).
- `Grade` is for product/package tier (standard/premium/exclusive).

Do not mix these concepts in UI or price mapping.

## Key Features

- Prefecture/area/hotel management in admin
- Grade and difficulty management
- Schedule-based hotel pricing
- Add-on pricing (lift/gear/transport)
- Frontend simulation calculator
- Inquiry form with dedicated inquiry page and prefill support
- Email notifications and customer auto-responder
- REST endpoints for prices/hotels

## Inquiry System Notes

- Dedicated inquiry page support (`/inquiry` flow)
- URL prefill parameters for area/hotel/check-in/check-out/guests/package
- Confirmation success state after submit
- WP Mail SMTP integration checks and notices

## Menu and Theme Integration

Ski Engine data is consumed by Skiverse theme to build menu/content behavior:

- Prefecture and area records are synced by theme-side helpers for menu taxonomies.
- Area detail links use canonical route `/area/{slug}/`.

## Development Notes

- Use `php -l` for quick syntax checks while editing.
- Keep compatibility with existing admin page slugs and options keys.
- Preserve old option keys and menu locations when refactoring to avoid migration breakage.

## Local Validation

Typical checks after changes:

1. `php -l plugins/ski-engine/ski-engine-ultimate.php`
2. `php -l` on edited module files
3. Verify admin pages load:
   - Dashboard
   - Resorts
   - Accommodation
   - Operations
   - Settings tabs
4. Verify frontend routes and calculator behavior
5. Verify inquiry submit and email flow
