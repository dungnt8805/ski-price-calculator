=== Ski Price Calculator Ultimate ===
Contributors: dungnt
Tags: ski, price calculator, japan, hotels, lift, gear, transport
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete ski package price management and calculator plugin for Japanese ski areas and hotels.

== Description ==

Ski Price Calculator Ultimate is a WordPress plugin for managing and displaying ski package pricing for Japanese ski areas. It supports bilingual data (English and Japanese), schedule-based pricing rules, JPY/USD values, and a frontend calculator via shortcode.

= Key Features =

* **Areas** - Define ski destinations as City, Town, or Village with English and Japanese names.
* **Hotels** - Manage hotels per area with grade classification, bilingual names and addresses, and multiple images via the WordPress Media Library.
* **Grades** - Define hotel/package quality tiers (for example: Standard, Premium, Exclusive).
* **Hotel Price Rules** - Configure hotel prices with schedule types:
  * selected weekdays
  * weekend
  * date range
  * specific dates
* **Add-on Pricing** - Manage Lift, Gear, and Transport prices by area, grade, and/or ski days.
* **Dual Currency Fields** - Store and display JPY and USD values.
* **REST API** - Exposes `/spc/v1/prices`, `/spc/v1/hotels`, and `/spc/v1/hotel-price` for frontend usage.
* **CSV Export** — Export all price data via `/spc/v1/export`.
* **Shortcode** — Embed an interactive price calculator on any page with `[ski_calculator]`.

= Shortcode Usage =

Place the following shortcode on any page or post:

    [ski_calculator]

The calculator widget collects customer name, area/hotel, grade, nights and days, then displays the estimated total.

= Calculator Flow =

1. Select a hotel.
2. Select check-in date and number of nights.
3. Select display currency (JPY or USD).
4. Optional: include Lift, Gear, and/or Transport add-ons.
5. Click **Calculate Price** to see the estimated total and line-item breakdown.

== Installation ==

1. Upload the `ski-price-calculator` folder to `/wp-content/plugins/`.
2. Activate the plugin via **Plugins → Installed Plugins** in the WordPress admin.
3. Upon activation, the required database tables are created automatically.
4. Navigate to **Ski Calculator** in the left admin menu to begin setup.

= Recommended Setup Order =

1. **Grades** - Add grade tiers first (for example: Standard, Premium, Exclusive).
2. **Areas** - Add ski areas (City / Town / Village), with English and Japanese names.
3. **Hotels** - Add hotels and map each to an Area and Grade.
4. **Prices (Hotel)** - Add hotel rules with schedule type and min/max prices in JPY/USD.
5. **Prices (Add-on)** - Add Lift/Gear/Transport rows by area (and grade where needed), with day-based values.

== Database Tables ==

The plugin creates the following tables (prefixed with your WordPress table prefix):

* `spcu_areas` - Area definitions (type, name, name_ja)
* `spcu_hotels` - Hotel listings (name, name_ja, address, address_ja, images, area_id, grade_id)
* `spcu_grades` - Grade or tier definitions
* `spcu_prices` - Main price rules (hotel pricing with schedule and range fields)
* `spcu_addon_prices` - Add-on price rules for lift/gear/transport

== REST API Endpoints ==

* `GET /wp-json/spc/v1/prices` - Returns all hotel and add-on price records.
* `GET /wp-json/spc/v1/hotels` - Returns hotels with area, grade, and image URLs.
* `GET /wp-json/spc/v1/hotel-price?hotel_id={id}&date=YYYY-MM-DD` - Returns matching hotel rule for a date plus all available rules.
* `GET /wp-json/spc/v1/export` - Downloads all pricing data as CSV.

== Changelog ==

= 2.0 =
* Added Areas with type (City / Town / Village), English name and Japanese name.
* Renamed "Levels" to "Grades" throughout admin and database.
* Replaced "Resorts" with "Hotels"; hotels are now linked to an Area and a Grade.
* Hotels extended with: Japanese name, English address, Japanese address, multiple images (WordPress Media Library).
* Added schedule-based hotel pricing types (selected days, weekend, date range, specific dates).
* Added dual-currency fields (JPY and USD) for pricing.
* Added add-on pricing table for Lift, Gear, and Transport (`spcu_addon_prices`).
* Hotel prices now reference a specific hotel instead of a generic resort.
* REST API expanded with `/hotels` and `/hotel-price` endpoints.
* CSV export and REST API updated to reflect the new schema.
* Removed old spcu_resorts and spcu_levels tables; replaced with spcu_hotels and spcu_grades.

= 1.0 =
* Initial release with Resorts, Levels, and Prices CRUD.
* REST API and CSV export.
* Frontend shortcode calculator.

== Upgrade Notice ==

= 2.0 =
Database schema has changed significantly from v1.0. After updating, **deactivate then reactivate** the plugin via WordPress admin to run the automatic table migration (dbDelta). Old spcu_resorts and spcu_levels data will not be migrated automatically.

== Frequently Asked Questions ==

= Do I need to reinstall the plugin after updating? =
No. Simply deactivate and reactivate the plugin — WordPress will trigger the activation hook which runs `dbDelta` to update the database schema safely without data loss to existing compatible columns.

= Can I have multiple price tiers for the same area? =
Yes. You can add as many price rows as needed per area, hotel, or day-count combination.

= How is a hotel price selected for a date? =
The calculator checks the selected check-in date against hotel pricing rules in this order: specific dates, date range, weekend, and selected days. If no rule matches, it falls back to the first available hotel rule.

= Are Japanese characters supported? =
Yes. All name and address fields support UTF-8 / full-width Japanese characters. The database tables are created with the WordPress default charset collation (typically utf8mb4).

= Where are hotel images stored? =
Images are stored in the WordPress Media Library. The plugin saves a comma-separated list of attachment IDs in the `spcu_hotels.images` column and renders them using standard WordPress image functions.

= Does Transport pricing depend on grade? =
It can. Transport add-on rows may be linked to a grade, and the calculator will try to match the selected hotel's grade.
