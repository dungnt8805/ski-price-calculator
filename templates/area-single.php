<?php
/**
 * Single Area Template
 * Displays area with hotels in card format + addon fees
 * URL: /area/[areaname]
 */

get_header();

$area_name = get_query_var('area_name');
$area = SPCU_Frontend::get_area_by_slug($area_name);

if(!$area){
    echo '<div style="text-align:center; padding: 60px 20px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
    echo '<h1 style="font-size: 32px; margin-bottom: 20px;">Area Not Found</h1>';
    echo '<p style="color: #666; font-size: 16px;">The area you\'re looking for doesn\'t exist.</p>';
    echo '<a href="'.home_url().'" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px;">Back to Home</a>';
    echo '</div>';
    get_footer();
    exit;
}

$hotels = SPCU_Frontend::get_hotels_by_area($area->id);
$addon_prices = SPCU_Frontend::get_area_addon_prices($area->id);

$area_featured_image = '';
if(!empty($area->featured_image)){
    $area_featured_image = wp_get_attachment_image_url(intval($area->featured_image), 'full');
}

$area_gallery_images = [];
if(!empty($area->images)){
    foreach(explode(',', $area->images) as $image_id){
        $image_id = intval($image_id);
        if($image_id){
            $image_url = wp_get_attachment_image_url($image_id, 'large');
            if($image_url){
                $area_gallery_images[] = $image_url;
            }
        }
    }
}

// Group addon prices by category
$price_data = [];
foreach($addon_prices as $p){
    if(!isset($price_data[$p->category])){
        $price_data[$p->category] = [];
    }
    $price_data[$p->category][] = $p->grade;
}
?>

<style>
    * {
        box-sizing: border-box;
    }

    .spcu-area-container {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
        color: #1a1a1a;
        line-height: 1.6;
    }

    /* Hero Section - Area Header */
    .spcu-area-header {
        background: linear-gradient(135deg, #0f5fa4 0%, #0073aa 50%, #005a87 100%);
        color: white;
        padding: 80px 20px;
        text-align: center;
        margin-bottom: 60px;
        position: relative;
        overflow: hidden;
        background-size: cover;
        background-position: center;
    }

    .spcu-area-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(100px, -100px);
    }

    .spcu-area-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        transform: translate(-100px, 100px);
    }

    .spcu-area-header-content {
        position: relative;
        z-index: 2;
        max-width: 1200px;
        margin: 0 auto;
    }

    .spcu-area-header h1 {
        font-size: 56px;
        margin: 0 0 16px 0;
        font-weight: 800;
        letter-spacing: -1px;
    }

    .spcu-area-header .subtext {
        font-size: 20px;
        opacity: 0.95;
        margin: 0;
        font-weight: 300;
        letter-spacing: 0.5px;
    }

    /* Main Container */
    .spcu-area-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .spcu-area-intro {
        max-width: 920px;
        margin: 0 auto 40px;
        color: #334155;
    }

    .spcu-area-short-description {
        font-size: 18px;
        line-height: 1.7;
        margin: 0 auto 24px;
        text-align: center;
        color: #0f172a;
    }

    .spcu-area-description {
        font-size: 16px;
        line-height: 1.8;
    }

    .spcu-area-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin: 0 0 40px;
    }

    .spcu-area-gallery img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    }

    /* Section Titles */
    .spcu-section-title {
        font-size: 32px;
        font-weight: 800;
        margin: 60px 0 32px 0;
        padding-bottom: 16px;
        border-bottom: 4px solid #0073aa;
        display: inline-block;
        letter-spacing: -0.5px;
        position: relative;
    }

    .spcu-section-title::before {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 0;
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, #0073aa 0%, #00a8e8 100%);
    }

    /* Hotels Grid */
    .spcu-hotels-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 28px;
        margin-bottom: 60px;
    }

    .spcu-hotel-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }

    .spcu-hotel-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
    }

    /* Hotel Image Container */
    .spcu-hotel-image {
        width: 100%;
        height: 260px;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }

    .spcu-hotel-image::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.1) 100%);
        z-index: 1;
        pointer-events: none;
    }

    .spcu-hotel-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .spcu-hotel-card:hover .spcu-hotel-image img {
        transform: scale(1.05);
    }

    .spcu-hotel-image-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #0073aa 0%, #00a8e8 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 64px;
        position: relative;
        z-index: 0;
    }

    /* Hotel Content */
    .spcu-hotel-content {
        padding: 28px;
    }

    .spcu-hotel-name {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 6px 0;
        color: #1a1a1a;
        letter-spacing: -0.3px;
    }

    .spcu-hotel-name-ja {
        font-size: 14px;
        color: #0073aa;
        margin: 0 0 16px 0;
        font-weight: 500;
    }

    .spcu-hotel-address {
        font-size: 14px;
        color: #555;
        line-height: 1.6;
        margin: 0;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .spcu-hotel-address::before {
        content: '📍';
        flex-shrink: 0;
        font-size: 16px;
    }

    .spcu-hotel-address-ja {
        font-size: 13px;
        color: #888;
        line-height: 1.6;
        margin: 12px 0 0 26px;
    }

    /* Addon Prices Section */
    .spcu-addon-prices {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 12px;
        padding: 40px;
        margin-bottom: 60px;
        border: 1px solid #e9ecef;
    }

    .spcu-addon-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 15px;
        overflow: hidden;
        border-radius: 8px;
    }

    .spcu-addon-table thead {
        background: linear-gradient(90deg, #0f5fa4 0%, #0073aa 100%);
        color: white;
    }

    .spcu-addon-table th {
        padding: 16px;
        text-align: left;
        font-weight: 600;
        border: none;
        letter-spacing: 0.3px;
    }

    .spcu-addon-table td {
        padding: 14px 16px;
        border: none;
        color: #333;
        vertical-align: middle;
    }

    .spcu-addon-table tbody tr {
        border-bottom: 1px solid #e9ecef;
        transition: background-color 0.2s ease;
    }

    .spcu-addon-table tbody tr:hover {
        background-color: #f0f7ff;
    }

    .spcu-addon-table tbody tr:last-child {
        border-bottom: none;
    }

    .spcu-addon-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .spcu-addon-category {
        font-weight: 700;
        color: #0073aa;
        text-transform: capitalize;
        letter-spacing: 0.2px;
    }

    .spcu-addon-price {
        color: #00a8e8;
        font-weight: 700;
        font-size: 16px;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .spcu-area-header h1 {
            font-size: 44px;
        }

        .spcu-section-title {
            font-size: 26px;
        }
    }

    @media (max-width: 768px) {
        .spcu-area-container {
            padding: 0;
        }

        .spcu-area-header {
            padding: 60px 20px;
            margin-bottom: 40px;
        }

        .spcu-area-header h1 {
            font-size: 36px;
        }

        .spcu-area-header .subtext {
            font-size: 16px;
        }

        .spcu-area-content {
            padding: 0 16px;
        }

        .spcu-hotels-grid {
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 40px;
        }

        .spcu-section-title {
            font-size: 22px;
            margin: 40px 0 20px 0;
        }

        .spcu-hotel-image {
            height: 220px;
        }

        .spcu-hotel-content {
            padding: 20px;
        }

        .spcu-addon-prices {
            padding: 24px;
            margin-bottom: 40px;
        }

        .spcu-addon-table {
            font-size: 13px;
        }

        .spcu-addon-table th,
        .spcu-addon-table td {
            padding: 12px;
        }
    }

    @media (max-width: 480px) {
        .spcu-area-header {
            padding: 40px 16px;
        }

        .spcu-area-header h1 {
            font-size: 28px;
        }

        .spcu-area-header .subtext {
            font-size: 14px;
        }

        .spcu-section-title {
            font-size: 18px;
            border-bottom-width: 3px;
        }

        .spcu-addon-table th,
        .spcu-addon-table td {
            padding: 10px 8px;
            font-size: 12px;
        }

        .spcu-addon-price {
            font-size: 14px;
        }
    }
</style>

<div class="spcu-area-container">
    <!-- Area Header -->
    <div class="spcu-area-header"<?php if($area_featured_image): ?> style="background-image: linear-gradient(rgba(15,95,164,0.72), rgba(0,90,135,0.78)), url('<?php echo esc_url($area_featured_image); ?>');"<?php endif; ?>>
        <div class="spcu-area-header-content">
            <h1><?php echo esc_html($area->name); ?></h1>
            <?php if(!empty($area->name_ja)): ?>
                <p class="subtext"><?php echo esc_html($area->name_ja); ?></p>
            <?php endif; ?>
            <?php if(!empty($area->short_description)): ?>
                <p class="subtext" style="max-width:760px;margin:18px auto 0;"><?php echo esc_html($area->short_description); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="spcu-area-content">
        <?php if(!empty($area->description) || !empty($area_gallery_images)): ?>
            <div class="spcu-area-intro">
                <?php if(!empty($area->description)): ?>
                    <div class="spcu-area-description"><?php echo wp_kses_post(wpautop($area->description)); ?></div>
                <?php endif; ?>
            </div>
            <?php if(!empty($area_gallery_images)): ?>
                <div class="spcu-area-gallery">
                    <?php foreach($area_gallery_images as $gallery_image): ?>
                        <img src="<?php echo esc_url($gallery_image); ?>" alt="<?php echo esc_attr($area->name); ?>" loading="lazy">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Hotels Section -->
        <?php if(!empty($hotels)): ?>
            <h2 class="spcu-section-title">🏨 Hotels</h2>
            <div class="spcu-hotels-grid">
                <?php foreach($hotels as $hotel): 
                    $primary_image = '';
                    if(!empty($hotel->featured_image)){
                        $primary_image = wp_get_attachment_image_url(intval($hotel->featured_image), 'large');
                    }
                    if(!$primary_image && !empty($hotel->images)){
                        foreach(explode(',', $hotel->images) as $image_id){
                            $candidate = wp_get_attachment_image_url(intval($image_id), 'large');
                            if($candidate){
                                $primary_image = $candidate;
                                break;
                            }
                        }
                    }
                ?>
                    <div class="spcu-hotel-card">
                        <div class="spcu-hotel-image">
                            <?php if($primary_image): ?>
                                <img src="<?php echo esc_url($primary_image); ?>" alt="<?php echo esc_attr($hotel->name); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="spcu-hotel-image-placeholder">🏨</div>
                            <?php endif; ?>
                        </div>
                        <div class="spcu-hotel-content">
                            <h3 class="spcu-hotel-name"><?php echo esc_html($hotel->name); ?></h3>
                            <?php if(!empty($hotel->name_ja)): ?>
                                <p class="spcu-hotel-name-ja"><?php echo esc_html($hotel->name_ja); ?></p>
                            <?php endif; ?>

                            <?php if(!empty($hotel->short_description)): ?>
                                <p style="margin:0 0 14px 0;font-size:14px;color:#334155;line-height:1.55;"><?php echo esc_html($hotel->short_description); ?></p>
                            <?php endif; ?>

                            <?php if(!empty($hotel->facilities)): ?>
                                <?php 
                                $facilities_list = [];
                                if(is_string($hotel->facilities)){
                                    // Handle old JSON string format for backward compatibility
                                    $decoded = json_decode($hotel->facilities, true);
                                    if(is_array($decoded)){
                                        $facilities_list = $decoded;
                                    }
                                } elseif(is_array($hotel->facilities)){
                                    // New format from WordPress taxonomy
                                    $facilities_list = $hotel->facilities;
                                }
                                if(!empty($facilities_list)):
                                ?>
                                <div style="margin:0 0 14px 0;display:flex;flex-wrap:wrap;gap:6px;">
                                    <?php foreach($facilities_list as $facility): 
                                        $facility_name = is_array($facility) ? ($facility['name'] ?? '') : $facility;
                                        if(empty($facility_name)) continue;
                                    ?>
                                        <span style="display:inline-block;background:#e0f2fe;color:#0369a1;border:1px solid #06b6d4;border-radius:12px;padding:4px 12px;font-size:12px;font-weight:500;"><?php echo esc_html($facility_name); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if(!empty($hotel->address)): ?>
                                <p class="spcu-hotel-address"><?php echo esc_html($hotel->address); ?></p>
                            <?php endif; ?>
                            
                            <?php if(!empty($hotel->address_ja)): ?>
                                <p class="spcu-hotel-address-ja"><?php echo esc_html($hotel->address_ja); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Addon Prices Section -->
        <?php if(!empty($price_data)): ?>
            <div style="margin-top: 60px;">
                <h2 class="spcu-section-title">💰 Additional Fees</h2>
                <div class="spcu-addon-prices">
                    <table class="spcu-addon-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Standard</th>
                                <th>Premium</th>
                                <th>Exclusive</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($price_data as $category => $grades): ?>
                                <tr>
                                    <td class="spcu-addon-category"><?php echo esc_html(str_replace('_', ' ', $category)); ?></td>
                                    <?php foreach(['standard', 'premium', 'exclusive'] as $grade): ?>
                                        <td>
                                            <?php
                                            if(in_array($grade, $grades)){
                                                $price = SPCU_Frontend::get_addon_price($area->id, $category, $grade);
                                                if($price){
                                                    echo '<span class="spcu-addon-price">¥'.intval($price->price_jpy).' / $'.intval($price->price_usd).'</span>';
                                                }
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer();
