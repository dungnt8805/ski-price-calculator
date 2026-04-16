# Ski Price Calculator - Example Data

This folder contains beautiful, realistic example data for testing and demonstrating the Ski Price Calculator plugin.

## What's Included

### 4 Ski Resort Areas

- **Hakuba Valley** — Japan's largest interconnected ski resort
- **Niseko** — Premium powder destination (highest prices)
- **Yuzawa Snow Park** — Family-friendly, near Tokyo (most budget-friendly)
- **Nagano** — Olympic host with alpine skiing

### 12 Hotels Across 3 Grades

- **Standard**: Budget-friendly lodges (¥48,000-65,000/night)
- **Premium**: Mid-range resorts (¥72,000-95,000/night)
- **Exclusive**: Luxury hotels (¥125,000-180,000/night)

### Realistic Pricing

- Hotel prices show min/max ranges (reflecting different seasons)
- Lift tickets: ¥28,000-33,000 per 5-day period
- Gear rental: ¥38,000-65,000 per 5-day period
- Transport: ¥18,000-34,000 round trip

---

## How to Import

### Option 1: PHP Importer (Recommended)

1. Include the importer file in your plugin:

```php
require_once SPCU_PATH . 'includes/example-data-importer.php';
```

2. Run the importer via WordPress hooks:

```php
do_action('spcu_import_example_data');
```

3. Or create a quick admin page button:

```php
add_action('admin_menu', function(){
    add_management_page(
        'SKI Data Import',
        'SKI Data Import',
        'manage_options',
        'ski-import',
        function(){
            if(isset($_GET['action']) && $_GET['action'] === 'import'){
                do_action('spcu_import_example_data');
                echo '<div class="updated"><p>Data imported successfully!</p></div>';
            }
            echo '<a href="?page=ski-import&action=import" class="button button-primary">Import Example Data</a>';
        }
    );
});
```

### Option 2: SQL File

1. Access your WordPress database via phpMyAdmin or command line
2. Import `example-data.sql`:

```bash
mysql -u username -p database_name < example-data.sql
```

---

## Pricing Examples

### Hakuba Valley - Premium Grade - 5 Nights

- **Accommodation**: ¥85,000-95,000/night = ¥425,000-475,000
- **Lift Tickets**: ¥31,000 × 3 ski days = ¥93,000
- **Gear Rental**: ¥52,000 × 3 ski days = ¥156,000
- **Transport**: ¥28,000/person = ¥28,000 (per guest)
- **Per Person Total**: ¥193,000-211,000
- **Group of 2 Total**: ¥414,000-450,000

### Yuzawa Snow Park - Standard Grade - 4 Nights

- **Accommodation**: ¥48,000-58,000/night = ¥192,000-232,000
- **Lift Tickets**: ¥28,000 × 2 ski days = ¥56,000
- **Gear Rental**: ¥38,000 × 2 ski days = ¥76,000
- **Transport**: ¥18,000/person = ¥18,000 (per guest)
- **Per Person Total**: ¥114,000-134,000
- **Group of 4 Total**: ¥456,000-536,000

---

## Customizing the Data

### To modify hotel prices:

Edit `wp_spcu_prices` table, adjust `price_jpy` and `price_usd` values.

### To change addon prices:

Edit `wp_spcu_addon_prices` table, adjust prices by category (lift, gear, transport).

### To mark a hotel as featured:

In the admin or database, set `is_featured = 1` for featured hotels.

---

## Testing the Quote Form

1. Import example data
2. Go to any page and add shortcode: `[ski_quote_form]`
3. Select:
   - **Area**: Hakuba Valley
   - **Level**: Premium
   - **Duration**: 5 nights
   - **Guests**: 2
   - **Season**: Regular
4. Watch the pricing calculate automatically!

---

## Database Schema Notes

### Areas Table

- `id`: Unique identifier
- `type`: Prefecture, City, Town, Village
- `name`: English name
- `name_ja`: Japanese name
- `short_description`: Area overview
- `featured_image`: Attachment ID

### Hotels Table

- `id`: Unique identifier
- `area_id`: Foreign key to area
- `name`: Hotel name (English)
- `name_ja`: Hotel name (Japanese)
- `grade`: standard | premium | exclusive
- `short_description`: Hotel overview
- `address`: Physical address
- `is_featured`: 0 or 1 (for admin list badge)

### Prices Table

- `hotel_id`: Foreign key to hotel
- `category`: "hotel" (for accommodation)
- `days`: Usually NULL for hotels (fixed rate per night)
- `price_jpy`: Price in Japanese Yen
- `price_usd`: Price in US Dollars

### Addon Prices Table

- `area_id`: Foreign key to area
- `category`: "lift" | "gear" | "transport"
- `grade`: standard | premium | exclusive
- `days`: 5 (for lift/gear), NULL for transport
- `price_jpy`: Price in Japanese Yen
- `price_usd`: Price in US Dollars

---

## Currency Conversion Reference

Used approximate rate: **1 JPY = 0.0073 USD**

For accurate conversions, check current rates and adjust prices accordingly.

---

## Questions or Issues?

If data seems incorrect or you'd like different values, edit the arrays in the PHP importer and re-run it. The data is designed to be realistic and easily customizable.
