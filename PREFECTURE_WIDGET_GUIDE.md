# Prefecture Widget Enhancement - Implementation Guide

## Overview

The Prefecture Widget has been completely redesigned to display ski areas in a modern, feature-rich card layout that matches professional travel websites. Each area card now displays detailed information including badges, descriptions, location info, tags, and a call-to-action button.

## Changes Made

### 1. Database Schema Updates

Two new fields have been added to the `wp_spcu_areas` table:

- **`featured_badge`** (VARCHAR(100)): Display a special badge on area cards
  - Examples: "MOST POPULAR", "LARGEST IN JAPAN", "HOT SPRINGS", "SNOW MONKEYS", "FAMILY FRIENDLY", "BEGINNER PARADISE"
- **`area_tags`** (TEXT): JSON array of feature tags displayed as pills
  - Examples: `["10 Resorts", "Olympic Heritage", "Deep Powder"]`

### 2. Admin Area Form Updates

The area editing form (`spcu-admin-area-form.php`) now includes:

- **Featured Badge Dropdown**: Select from predefined badge options
- **Area Tags Textarea**: Enter one tag per line (automatically converted to JSON)

### 3. Widget Controls

The widget now includes new display options:

- **Show Prefecture Header** (default: ON) - Displays prefecture name and Japanese name
- **Show Area Description** (default: ON) - Shows trimmed description text
- **Show Location Info** (default: ON) - Displays prefecture name and distance
- **Show Area Tags** (default: ON) - Shows feature tags as pills
- **Show Button** (default: ON) - Displays CTA button
- **Button Text** (editable, default: "View & Quote →")
- **Button Link** (editable)

### 4. Enhanced Rendering

The widget now renders complete area cards with:

- **Badge overlay** on the featured image
- **Area title** in larger, bolder typography
- **Location info** with prefecture name and distance
- **Description** excerpt (first 20 words)
- **Feature tags** displayed as styled pills
- **CTA button** with hover effects

### 5. Updated Styling

New CSS classes for complete layout redesign:

```css
.spcu-prefecture-header          /* Prefecture section header */
.spcu-prefecture-header__content
.spcu-prefecture-label           /* Badge for prefecture name */
.spcu-prefecture-title

.spcu-area-card__badge           /* Badge overlay on image */
.spcu-area-card__location        /* Location/distance info */
.spcu-area-card__description     /* Description text */
.spcu-area-card__tags            /* Container for tag pills */
.spcu-area-card__tag             /* Individual tag pill */
.spcu-area-card__button          /* CTA button */
```

## File Changes

### Modified Files:

1. **`/includes/class-spcu-database.php`**
   - Added `featured_badge` and `area_tags` columns to areas table

2. **`/admin/partials/spcu-admin-area-form.php`**
   - Added Featured Badge dropdown field
   - Added Area Tags textarea field

3. **`/admin/partials/spcu-admin-areas-post.php`**
   - Added schema validation for new columns
   - Added data processing for featured_badge and area_tags

4. **`/admin/partials/spcu-admin-areas.php`**
   - Updated schema_columns to include new fields

5. **`/includes/elementor/widgets/class-spcu-prefecture-widget.php`**
   - Complete widget rewrite with enhanced controls
   - Updated render method to display full card layout
   - Added prefecture data fetching

6. **`/public/public.css`**
   - Completely redesigned CSS for new card layout
   - Added styles for badge, location, tags, and button

### New Files:

7. **`sample-data.sql`**
   - Sample data for Nagano Prefecture with example areas

## How to Use

### Step 1: Run Database Migration

The new fields will be automatically created via the admin interface when you edit an area. If you want to add them manually via SQL:

```sql
ALTER TABLE wp_spcu_areas ADD COLUMN featured_badge VARCHAR(100) NULL;
ALTER TABLE wp_spcu_areas ADD COLUMN area_tags TEXT NULL;
```

### Step 2: Load Sample Data (Optional)

To test with the design mockup data:

```bash
# In your WordPress database management tool, run:
cat sample-data.sql | mysql -u [username] -p [database_name]
```

Or use the WordPress admin interface to manually add areas.

### Step 3: Add Area Details

In the WordPress admin, go to **Ski Engine → Areas** and edit or create an area:

1. Fill in basic information (name, prefecture, description, etc.)
2. Select a **Featured Badge** from the dropdown
3. Enter **Area Tags** (one per line):
   ```
   10 Resorts
   Olympic Heritage
   Deep Powder
   ```
4. Save the area

### Step 4: Configure Widget in Elementor

1. Add the "Prefecture Areas" widget to your page
2. Select the prefecture
3. Toggle display options as desired:
   - ✓ Show Prefecture Header
   - ✓ Show Area Description
   - ✓ Show Location Info
   - ✓ Show Area Tags
   - ✓ Show Button
4. Customize button text and link
5. Adjust card width in the Style tab

## Design Features

### Card Layout

```
┌─────────────────────────────────┐
│ [BADGE]                         │
│   ┌───────────────────────────┐ │
│   │                           │ │
│   │      Featured Image       │ │
│   │      (4:3 aspect)         │ │
│   │                           │ │
│   └───────────────────────────┘ │
│                                 │
│   Hakuba Valley                 │
│   Nagano · 2.5h from Tokyo      │
│                                 │
│   Host of the 1998 Winter       │
│   Olympics. 10 interconnected   │
│   ...                           │
│                                 │
│   [10 Resorts] [Olympic...]     │
│                                 │
│   ┌───────────────────────────┐ │
│   │  View & Quote →           │ │
│   └───────────────────────────┘ │
└─────────────────────────────────┘
```

### Responsive Behavior

- Horizontal scrolling for area cards (same as before)
- Cards maintain 4:3 aspect ratio on images
- Text scales proportionally on different devices
- Tags wrap naturally on smaller screens
- Button spans full card width

### Hover Effects

- Card lifts up with shadow
- Image zooms slightly
- Border color changes
- Button changes background color and slides right

## Badge Options

Preset badges available in the dropdown:

- MOST POPULAR
- LARGEST IN JAPAN
- HOT SPRINGS
- SNOW MONKEYS
- FAMILY FRIENDLY
- BEGINNER PARADISE

(Additional badges can be added by modifying the form)

## Tag Examples

Feature tags should be concise and descriptive:

```
10 Resorts                  (number of resorts in area)
36 Runs                     (number of ski runs)
Olympic Heritage            (special designation)
Deep Powder                 (terrain/condition)
UNESCO                      (designation)
Snow Monkeys                (unique feature)
Onsen Village              (nearby attraction)
Traditional                 (style/character)
Family Friendly            (best for families)
Beginner Paradise           (skill level)
Backcountry Access         (advanced terrain)
Night Skiing               (evening activity)
```

## Troubleshooting

### Tags Not Displaying

Make sure tags are:

1. Entered one per line in the admin form
2. Properly saved (area should show updated timestamp)
3. The "Show Area Tags" option is enabled in widget

### Badge Not Showing

- Verify a badge was selected in the admin form
- Ensure the "Show Area Card Badges" rendering is correct

### Images Not Displaying

- Set a Featured Image in the area admin form
- Images should be in Media Library
- Check browser console for image loading errors

## Future Enhancements

Potential improvements:

1. Custom badge creation without dropdown
2. Additional tag templates for different resort types
3. Photo gallery on hover
4. Link to individual area pages
5. Price information display
6. Availability calendar integration

## Compatibility

- WordPress 5.0+
- Elementor 3.0+
- PHP 7.4+

## Support

For issues or questions, refer to the plugin documentation or contact support.
