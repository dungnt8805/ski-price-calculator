<?php
/**
 * Single Area Template
 * Displays the area details page.
 */

get_header();

$area_name = get_query_var('area_name');
$area = SPCU_Frontend::get_area_by_slug($area_name);

if(!$area){
    echo '<div style="text-align:center; padding: 60px 20px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
    echo '<h1 style="font-size: 32px; margin-bottom: 20px;">Area Not Found</h1>';
    echo '<p style="color: #666; font-size: 16px;">The area you\'re looking for doesn\'t exist.</p>';
    echo '<a href="' . esc_url(home_url('/')) . '" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px;">Back to Home</a>';
    echo '</div>';
    get_footer();
    exit;
}

$hotels = SPCU_Frontend::get_hotels_by_area($area->id);
$addon_prices = SPCU_Frontend::get_area_addon_prices($area->id);

$area_featured_image = '';
if(!empty($area->featured_image)){
    $area_featured_image = wp_get_attachment_image_url((int) $area->featured_image, 'full');
}

$area_gallery_images = [];
if(!empty($area->images)){
    foreach(explode(',', $area->images) as $image_id){
        $image_id = (int) $image_id;
        if($image_id <= 0){
            continue;
        }

        $image_url = wp_get_attachment_image_url($image_id, 'large');
        if($image_url){
            $area_gallery_images[] = $image_url;
        }
    }
}

$price_data = [];
foreach($addon_prices as $addon_price){
    if(!isset($price_data[$addon_price->category])){
        $price_data[$addon_price->category] = [];
    }

    $price_data[$addon_price->category][] = $addon_price->grade;
}

$difficulty_breakdown = [];
if(!empty($area->difficulties_json)){
    $decoded_difficulties = json_decode($area->difficulties_json, true);
    if(is_array($decoded_difficulties)){
        $difficulty_breakdown = $decoded_difficulties;
    }
}

$hotel_grade_labels = [
    'standard' => 'Standard',
    'premium' => 'Premium',
    'exclusive' => 'Exclusive',
];

$hotel_grade_colors = [
    'standard' => ['bg' => '#e2e8f0', 'text' => '#334155'],
    'premium' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    'exclusive' => ['bg' => '#fef3c7', 'text' => '#92400e'],
];
?>

<div class="spcu-area-detail">
    <nav class="spcu-area-detail__breadcrumb" aria-label="Breadcrumb">
        <div class="spcu-area-detail__shell">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
            <span>/</span>
            <span>Area</span>
            <span>/</span>
            <span><?php echo esc_html($area->name); ?></span>
        </div>
    </nav>

    <section class="spcu-area-detail__hero"<?php if($area_featured_image): ?> style="background-image: url('<?php echo esc_url($area_featured_image); ?>');"<?php endif; ?>>
        <div class="spcu-area-detail__hero-overlay"></div>
        <div class="spcu-area-detail__shell spcu-area-detail__hero-inner">
            <div class="spcu-area-detail__eyebrow">
                <?php if(!empty($area->featured_badge)): ?>
                    <span class="spcu-area-detail__pill spcu-area-detail__pill--accent"><?php echo esc_html($area->featured_badge); ?></span>
                <?php endif; ?>
                <?php if(!empty($area->type)): ?>
                    <span class="spcu-area-detail__pill"><?php echo esc_html($area->type); ?></span>
                <?php endif; ?>
            </div>

            <h1 class="spcu-area-detail__title"><?php echo esc_html($area->name); ?></h1>

            <?php if(!empty($area->name_ja)): ?>
                <p class="spcu-area-detail__title-ja"><?php echo esc_html($area->name_ja); ?></p>
            <?php endif; ?>

            <?php if(!empty($area->short_description)): ?>
                <p class="spcu-area-detail__summary"><?php echo esc_html($area->short_description); ?></p>
            <?php endif; ?>

            <?php if(!empty($area->prefecture_name) || !empty($area->distance)): ?>
                <div class="spcu-area-detail__location">
                    <span>📍</span>
                    <?php if(!empty($area->prefecture_name)): ?>
                        <span><?php echo esc_html($area->prefecture_name); ?></span>
                    <?php endif; ?>
                    <?php if(!empty($area->prefecture_name) && !empty($area->distance)): ?>
                        <span class="spcu-area-detail__dot">•</span>
                    <?php endif; ?>
                    <?php if(!empty($area->distance)): ?>
                        <span><?php echo esc_html($area->distance); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <main class="spcu-area-detail__shell spcu-area-detail__main">
        <?php if(!empty($area->description) || !empty($area->short_description)): ?>
            <section class="spcu-area-detail__section">
                <div class="spcu-area-detail__section-head">
                    <span class="spcu-area-detail__kicker">Overview</span>
                    <h2>About this area</h2>
                </div>

                <div class="spcu-area-detail__copy">
                    <?php
                    if(!empty($area->description)){
                        echo wp_kses_post(wpautop($area->description));
                    } else {
                        echo wp_kses_post(wpautop(esc_html($area->short_description)));
                    }
                    ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if(
            !empty($area->total_runs) ||
            !empty($area->max_vertical) ||
            !empty($area->summit) ||
            !empty($area->total_resorts) ||
            !empty($area->season) ||
            !empty($area->distance) ||
            !empty($difficulty_breakdown)
        ): ?>
            <section class="spcu-area-detail__section spcu-area-detail__section--split">
                <div>
                    <div class="spcu-area-detail__section-head">
                        <span class="spcu-area-detail__kicker">Highlights</span>
                        <h2>Mountain snapshot</h2>
                    </div>

                    <div class="spcu-area-detail__stats">
                        <?php if(!empty($area->total_runs)): ?>
                            <div class="spcu-area-detail__stat-card"><strong><?php echo esc_html($area->total_runs); ?></strong><span>Total Runs</span></div>
                        <?php endif; ?>
                        <?php if(!empty($area->max_vertical)): ?>
                            <div class="spcu-area-detail__stat-card"><strong><?php echo esc_html(number_format_i18n((int) $area->max_vertical)); ?>m</strong><span>Max Vertical</span></div>
                        <?php endif; ?>
                        <?php if(!empty($area->summit)): ?>
                            <div class="spcu-area-detail__stat-card"><strong><?php echo esc_html(number_format_i18n((int) $area->summit)); ?>m</strong><span>Summit</span></div>
                        <?php endif; ?>
                        <?php if(!empty($area->total_resorts)): ?>
                            <div class="spcu-area-detail__stat-card"><strong><?php echo esc_html($area->total_resorts); ?></strong><span>Resorts</span></div>
                        <?php endif; ?>
                        <?php if(!empty($area->season)): ?>
                            <div class="spcu-area-detail__stat-card"><strong><?php echo esc_html($area->season); ?></strong><span>Season</span></div>
                        <?php endif; ?>
                        <?php if(!empty($area->distance)): ?>
                            <div class="spcu-area-detail__stat-card"><strong><?php echo esc_html($area->distance); ?></strong><span>From Tokyo</span></div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if(!empty($difficulty_breakdown)): ?>
                    <div>
                        <div class="spcu-area-detail__section-head">
                            <span class="spcu-area-detail__kicker">Terrain</span>
                            <h2>Difficulty mix</h2>
                        </div>

                        <div class="spcu-area-detail__difficulty-list">
                            <?php foreach(SPCU_Grades::records() as $difficulty_record): ?>
                                <?php
                                $difficulty_slug = $difficulty_record['slug'];
                                $difficulty_value = isset($difficulty_breakdown[$difficulty_slug]) ? (int) $difficulty_breakdown[$difficulty_slug] : 0;
                                if($difficulty_value <= 0){
                                    continue;
                                }
                                ?>
                                <div class="spcu-area-detail__difficulty-row">
                                    <div class="spcu-area-detail__difficulty-meta">
                                        <span><?php echo esc_html($difficulty_record['name']); ?></span>
                                        <strong><?php echo esc_html($difficulty_value); ?>%</strong>
                                    </div>
                                    <div class="spcu-area-detail__difficulty-track">
                                        <span style="width: <?php echo esc_attr(min(100, $difficulty_value)); ?>%; background: <?php echo esc_attr($difficulty_record['color']); ?>;"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if(!empty($area_gallery_images)): ?>
            <section class="spcu-area-detail__section">
                <div class="spcu-area-detail__section-head">
                    <span class="spcu-area-detail__kicker">Gallery</span>
                    <h2>See the area</h2>
                </div>

                <div class="spcu-area-detail__gallery">
                    <?php foreach($area_gallery_images as $gallery_image): ?>
                        <figure class="spcu-area-detail__gallery-item">
                            <img src="<?php echo esc_url($gallery_image); ?>" alt="<?php echo esc_attr($area->name); ?>" loading="lazy">
                        </figure>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="spcu-area-detail__section">
            <div class="spcu-area-detail__section-head">
                <span class="spcu-area-detail__kicker">Stay</span>
                <h2>Hotels in <?php echo esc_html($area->name); ?></h2>
            </div>

            <?php if(empty($hotels)): ?>
                <div class="spcu-area-detail__empty">No hotels found in this area yet.</div>
            <?php else: ?>
                <div class="spcu-area-detail__hotel-grid">
                    <?php foreach($hotels as $hotel): ?>
                        <?php
                        $primary_image = '';
                        if(!empty($hotel->featured_image)){
                            $primary_image = wp_get_attachment_image_url((int) $hotel->featured_image, 'large');
                        }
                        if(!$primary_image && !empty($hotel->images)){
                            foreach(explode(',', $hotel->images) as $image_id){
                                $candidate = wp_get_attachment_image_url((int) $image_id, 'large');
                                if($candidate){
                                    $primary_image = $candidate;
                                    break;
                                }
                            }
                        }

                        $hotel_grade_key = strtolower((string) ($hotel->grade ?? 'standard'));
                        $hotel_grade_label = $hotel_grade_labels[$hotel_grade_key] ?? ucfirst($hotel_grade_key);
                        $hotel_grade_palette = $hotel_grade_colors[$hotel_grade_key] ?? $hotel_grade_colors['standard'];

                        $facilities_list = [];
                        if(!empty($hotel->facilities)){
                            if(is_string($hotel->facilities)){
                                $decoded_facilities = json_decode($hotel->facilities, true);
                                if(is_array($decoded_facilities)){
                                    $facilities_list = $decoded_facilities;
                                }
                            } elseif(is_array($hotel->facilities)){
                                $facilities_list = $hotel->facilities;
                            }
                        }
                        ?>
                        <article class="spcu-area-hotel-card<?php echo !empty($hotel->is_featured) ? ' spcu-area-hotel-card--featured' : ''; ?>">
                            <div class="spcu-area-hotel-card__media">
                                <?php if($primary_image): ?>
                                    <img src="<?php echo esc_url($primary_image); ?>" alt="<?php echo esc_attr($hotel->name); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="spcu-area-hotel-card__placeholder">🏨</div>
                                <?php endif; ?>

                                <?php if(!empty($hotel->is_featured)): ?>
                                    <span class="spcu-area-hotel-card__badge spcu-area-hotel-card__badge--featured">Featured</span>
                                <?php endif; ?>

                                <span class="spcu-area-hotel-card__badge spcu-area-hotel-card__badge--grade" style="background: <?php echo esc_attr($hotel_grade_palette['bg']); ?>; color: <?php echo esc_attr($hotel_grade_palette['text']); ?>;">
                                    <?php echo esc_html($hotel_grade_label); ?>
                                </span>
                            </div>

                            <div class="spcu-area-hotel-card__body">
                                <h3 class="spcu-area-hotel-card__title"><?php echo esc_html($hotel->name); ?></h3>

                                <?php if(!empty($hotel->name_ja)): ?>
                                    <p class="spcu-area-hotel-card__title-ja"><?php echo esc_html($hotel->name_ja); ?></p>
                                <?php endif; ?>

                                <?php if(!empty($hotel->short_description)): ?>
                                    <p class="spcu-area-hotel-card__desc"><?php echo esc_html($hotel->short_description); ?></p>
                                <?php endif; ?>

                                <?php if(!empty($facilities_list)): ?>
                                    <div class="spcu-area-hotel-card__tags">
                                        <?php foreach($facilities_list as $facility): ?>
                                            <?php
                                            $facility_name = is_array($facility) ? ($facility['name'] ?? '') : $facility;
                                            if($facility_name === ''){
                                                continue;
                                            }
                                            ?>
                                            <span><?php echo esc_html($facility_name); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if(!empty($hotel->address)): ?>
                                    <p class="spcu-area-hotel-card__address"><?php echo esc_html($hotel->address); ?></p>
                                <?php endif; ?>

                                <?php if(!empty($hotel->address_ja)): ?>
                                    <p class="spcu-area-hotel-card__address-ja"><?php echo esc_html($hotel->address_ja); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if(!empty($price_data)): ?>
            <section class="spcu-area-detail__section">
                <div class="spcu-area-detail__section-head">
                    <span class="spcu-area-detail__kicker">Pricing</span>
                    <h2>Additional fees</h2>
                </div>

                <div class="spcu-area-detail__table-wrap">
                    <table class="spcu-area-detail__table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <?php foreach(SPCU_Grades::records() as $difficulty_record): ?>
                                    <th>
                                        <span class="spcu-area-detail__table-pill" style="background: <?php echo esc_attr($difficulty_record['color']); ?>; color: <?php echo esc_attr(SPCU_Grades::text_color($difficulty_record['slug'])); ?>;">
                                            <?php echo esc_html($difficulty_record['name']); ?>
                                        </span>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($price_data as $category => $grades): ?>
                                <tr>
                                    <td class="spcu-area-detail__service-name"><?php echo esc_html(str_replace('_', ' ', $category)); ?></td>
                                    <?php foreach(SPCU_Grades::ordered_keys() as $grade): ?>
                                        <td>
                                            <?php
                                            if(in_array($grade, array_map(['SPCU_Grades', 'normalize'], $grades), true)){
                                                $price = SPCU_Frontend::get_addon_price($area->id, $category, $grade);
                                                if($price){
                                                    echo '<span class="spcu-area-detail__price">¥' . esc_html(number_format_i18n((int) $price->price_jpy)) . ' / $' . esc_html(number_format_i18n((int) $price->price_usd)) . '</span>';
                                                } else {
                                                    echo '—';
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
            </section>
        <?php endif; ?>
    </main>
</div>

<?php get_footer();
