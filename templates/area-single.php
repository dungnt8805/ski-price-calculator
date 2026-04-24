<?php
/**
 * Single Area Template
 * Displays the area details page.
 */

get_header();

// Support both plugin's area_name query var and theme's area_slug query var
$area_name = get_query_var('area_name') ?: get_query_var('area_slug');
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
$hotel_min_prices = SPCU_Frontend::get_hotel_min_prices_by_area($area->id);
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

$area_slideshow_images = $area_gallery_images;
if($area_featured_image){
    array_unshift($area_slideshow_images, $area_featured_image);
    $area_slideshow_images = array_values(array_unique($area_slideshow_images));
}

$price_data = [];
foreach($addon_prices as $addon_price){
    if(!isset($price_data[$addon_price->category])){
        $price_data[$addon_price->category] = [];
    }

    $price_data[$addon_price->category][] = $addon_price->grade;
}

$transport_cards = [];
$transport_grade_labels = [
    'standard' => 'Standard',
    'premium' => 'Premium',
    'exclusive' => 'Exclusive',
    'beginner' => 'Standard',
    'intermediate' => 'Premium',
    'advanced' => 'Exclusive',
    'expert' => 'Exclusive',
];
$transport_grades = array_values(array_unique(array_filter(array_map(['SPCU_Grades', 'normalize'], $price_data['transport'] ?? []))));

foreach(SPCU_Grades::ordered_keys() as $grade){
    if(!in_array($grade, $transport_grades, true)){
        continue;
    }

    $transport_price = SPCU_Frontend::get_addon_price($area->id, 'transport', $grade);
    if(!$transport_price){
        continue;
    }

    $transport_cards[] = [
        'grade' => $grade,
        'label' => $transport_grade_labels[$grade] ?? ucfirst($grade),
        'color' => SPCU_Grades::color($grade),
        'text_color' => SPCU_Grades::text_color($grade),
        'price_jpy' => (int) $transport_price->price_jpy,
        'price_usd' => (int) $transport_price->price_usd,
    ];
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
    'standard' => ['bg' => '#ecfdf5', 'text' => '#059669'],
    'premium' => ['bg' => '#fffbeb', 'text' => '#d97706'],
    'exclusive' => ['bg' => '#f5f3ff', 'text' => '#7c3aed'],
];

$terrain_image = '';
if(!empty($area->coursemap_terrain_image)){
    $terrain_image = wp_get_attachment_image_url((int) $area->coursemap_terrain_image, 'full');
}
if(!$terrain_image){
    $terrain_image = $area_featured_image;
}
if(!$terrain_image && !empty($area_gallery_images)){
    $terrain_image = $area_gallery_images[0];
}

$hero_stats = [];
if(!empty($area->total_runs)){
    $hero_stats[] = ['value' => (string) $area->total_runs, 'label' => 'Runs'];
}
if(!empty($area->total_resorts)){
    $hero_stats[] = ['value' => (string) $area->total_resorts, 'label' => 'Resorts'];
}
if(!empty($area->summit)){
    $hero_stats[] = ['value' => number_format_i18n((int) $area->summit) . 'm', 'label' => 'Summit'];
}
if(!empty($area->distance)){
    $hero_stats[] = ['value' => (string) $area->distance, 'label' => 'From Tokyo'];
} elseif(!empty($area->max_vertical)){
    $hero_stats[] = ['value' => number_format_i18n((int) $area->max_vertical) . 'm', 'label' => 'Vertical'];
}
if(count($hero_stats) < 4 && !empty($area->season)){
    $hero_stats[] = ['value' => (string) $area->season, 'label' => 'Season'];
}

$hero_stats = array_slice($hero_stats, 0, 4);
?>

<div class="spcu-area-detail">
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

            <?php if(!empty($hero_stats)): ?>
                <div class="spcu-area-detail__hero-stats">
                    <?php foreach($hero_stats as $hero_stat): ?>
                        <div class="spcu-area-detail__hero-stat">
                            <div class="spcu-area-detail__hero-stat-value"><?php echo esc_html($hero_stat['value']); ?></div>
                            <div class="spcu-area-detail__hero-stat-label"><?php echo esc_html($hero_stat['label']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <main class="spcu-area-detail__shell spcu-area-detail__main">
        <?php if(!empty($area->description) || !empty($area->short_description)): ?>
            <section class="spcu-area-detail__section">
                <div class="spcu-area-detail__section-head">
                    <h2>About this area</h2>
                </div>

                <div class="spcu-area-detail__copy spcu-area-detail__intro-copy">
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
            <section class="spcu-area-detail__section">
                <div class="spcu-area-detail__section-head">
                    <h2>Course Map &amp; Terrain</h2>
                </div>

                <div class="spcu-area-detail__terrain-panel">
                    <?php if($terrain_image): ?>
                        <div class="spcu-area-detail__terrain-image" style="background-image: url('<?php echo esc_url($terrain_image); ?>');">
                            <div class="spcu-area-detail__terrain-overlay">
                                <span>🗺 <?php echo esc_html($area->name); ?> terrain overview</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="spcu-area-detail__stats spcu-area-detail__stats--terrain">
                        <?php if(!empty($area->total_runs)): ?>
                            <div class="spcu-area-detail__stat-card"><strong><?php echo esc_html($area->total_runs); ?></strong><span>Total Runs</span></div>
                        <?php endif; ?>
                        <?php if(!empty($area->max_vertical)): ?>
                            <div class="spcu-area-detail__stat-card"><strong><?php echo esc_html(number_format_i18n((int) $area->max_vertical)); ?>m</strong><span>Max Vertical</span></div>
                        <?php endif; ?>
                        <?php if(!empty($area->total_resorts)): ?>
                            <div class="spcu-area-detail__stat-card"><strong><?php echo esc_html($area->total_resorts); ?></strong><span>Resorts</span></div>
                        <?php endif; ?>
                        <?php if(!empty($area->season)): ?>
                            <div class="spcu-area-detail__stat-card"><strong><?php echo esc_html($area->season); ?></strong><span>Season</span></div>
                        <?php elseif(!empty($area->summit)): ?>
                            <div class="spcu-area-detail__stat-card"><strong><?php echo esc_html(number_format_i18n((int) $area->summit)); ?>m</strong><span>Summit</span></div>
                        <?php endif; ?>
                    </div>

                    <?php if(!empty($difficulty_breakdown)): ?>
                        <div class="spcu-area-detail__difficulty-bar">
                            <span class="spcu-area-detail__difficulty-label">Difficulty:</span>
                            <?php foreach(SPCU_Difficulties::records() as $difficulty_record): ?>
                                <?php
                                $difficulty_slug = $difficulty_record['slug'];
                                $difficulty_value = isset($difficulty_breakdown[$difficulty_slug]) ? (int) $difficulty_breakdown[$difficulty_slug] : 0;
                                if($difficulty_value <= 0){
                                    continue;
                                }
                                ?>
                                <span class="spcu-area-detail__difficulty-pill">
                                    <span class="spcu-area-detail__difficulty-dot" style="background: <?php echo esc_attr($difficulty_record['color']); ?>;"></span>
                                    <?php echo esc_html($difficulty_record['name']); ?> <?php echo esc_html($difficulty_value); ?>%
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if(!empty($area_slideshow_images)): ?>
            <section class="spcu-area-detail__section">
                <div class="spcu-area-detail__section-head">
                    <h2>See the area</h2>
                </div>

                <div class="spcu-area-detail__slideshow" data-autoplay="true" data-interval="4200">
                    <div class="spcu-area-detail__slides-track">
                        <?php foreach($area_slideshow_images as $index => $gallery_image): ?>
                            <figure class="spcu-area-detail__slide" aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>">
                                <img src="<?php echo esc_url($gallery_image); ?>" alt="<?php echo esc_attr($area->name . ' photo ' . ($index + 1)); ?>" loading="lazy">
                            </figure>
                        <?php endforeach; ?>
                    </div>

                    <?php if(count($area_slideshow_images) > 1): ?>
                        <button type="button" class="spcu-area-detail__slide-btn spcu-area-detail__slide-btn--prev" data-direction="prev" aria-label="Previous image">&#10094;</button>
                        <button type="button" class="spcu-area-detail__slide-btn spcu-area-detail__slide-btn--next" data-direction="next" aria-label="Next image">&#10095;</button>

                        <div class="spcu-area-detail__slide-dots" role="tablist" aria-label="Area image navigation">
                            <?php foreach($area_slideshow_images as $index => $gallery_image): ?>
                                <button
                                    type="button"
                                    class="spcu-area-detail__slide-dot<?php echo $index === 0 ? ' is-active' : ''; ?>"
                                    data-slide-index="<?php echo esc_attr((string) $index); ?>"
                                    aria-label="Go to image <?php echo esc_attr((string) ($index + 1)); ?>"
                                    aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                ></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="spcu-area-detail__section">
            <div class="spcu-area-detail__section-head">
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
                        $hotel_min_price = $hotel_min_prices[(int) ($hotel->id ?? 0)] ?? null;

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

                                <span class="spcu-area-hotel-card__badge spcu-area-hotel-card__badge--grade" style="background: <?php echo esc_attr($hotel_grade_palette['bg']); ?>; color: <?php echo esc_attr($hotel_grade_palette['text']); ?>;">
                                    <?php echo esc_html($hotel_grade_label); ?>
                                </span>
                            </div>

                            <div class="spcu-area-hotel-card__body">
                                <h3 class="spcu-area-hotel-card__title"><?php echo esc_html($hotel->name); ?></h3>

                                <?php if(!empty($hotel->name_ja)): ?>
                                    <p class="spcu-area-hotel-card__title-ja"><?php echo esc_html($hotel->name_ja); ?></p>
                                <?php endif; ?>

                                <p class="spcu-area-hotel-card__meta">
                                    <?php if(!empty($area->name)): ?>
                                        <?php echo esc_html($area->name); ?>
                                    <?php endif; ?>
                                    <?php if(!empty($hotel->address)): ?>
                                        <span class="spcu-area-hotel-card__meta-sep">·</span>
                                        <?php echo esc_html($hotel->address); ?>
                                    <?php endif; ?>
                                </p>

                                <?php if(!empty($hotel->short_description)): ?>
                                    <p class="spcu-area-hotel-card__desc"><?php echo esc_html($hotel->short_description); ?></p>
                                <?php endif; ?>

                                <?php if(!empty($hotel_min_price) && !empty($hotel_min_price['price_jpy'])): ?>
                                    <p class="spcu-area-hotel-card__from-price">
                                        From
                                        <span class="spcu-area-hotel-card__amt">¥<?php echo esc_html(number_format_i18n((int) $hotel_min_price['price_jpy'])); ?></span>
                                        per night
                                    </p>
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

                                <?php if(!empty($hotel->address_ja)): ?>
                                    <p class="spcu-area-hotel-card__address-ja"><?php echo esc_html($hotel->address_ja); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="spcu-area-detail__section">
            <div class="spcu-area-detail__section-head">
                <h2>Choose your transportation</h2>
            </div>

            <?php if(empty($transport_cards)): ?>
                <div class="spcu-area-detail__empty">Transportation options are being updated for this area.</div>
            <?php else: ?>
                <div class="spcu-area-detail__transport-cards">
                    <?php foreach($transport_cards as $transport_card): ?>
                        <article class="spcu-area-detail__transport-card">
                            <h4><?php echo esc_html($transport_card['label']); ?> Grade Transfer</h4>

                            <div class="spcu-area-detail__transport-meta">
                                <span>
                                    <span class="spcu-area-detail__transport-pill" style="background: <?php echo esc_attr($transport_card['color']); ?>; color: <?php echo esc_attr($transport_card['text_color']); ?>;">
                                        <?php echo esc_html($transport_card['label']); ?>
                                    </span>
                                </span>
                                <?php if(!empty($area->distance)): ?>
                                    <span><?php echo esc_html($area->distance); ?></span>
                                <?php endif; ?>
                            </div>

                            <p class="spcu-area-detail__transport-price">
                                From
                                <span class="spcu-area-detail__transport-amount">¥<?php echo esc_html(number_format_i18n($transport_card['price_jpy'])); ?></span>
                                per person
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Price Simulator -->
        <section class="spcu-area-detail__section" style="margin-top: 60px;">
            <div class="section-header-sim" style="margin-bottom: 2rem;">
                <div class="sim-overline" style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:2px; color:var(--color-blue, #2563eb); margin-bottom:0.5rem;">Price Simulator</div>
                <h2 style="font-family:'Playfair Display',serif; font-size:2rem; color:var(--color-navy, #0f1b2d); margin-bottom:0.8rem;">Calculate Your Package</h2>
                <p style="color:var(--color-muted, #64748b); font-size:0.95rem;">Select dates, guests, hotel grade, hotel & transportation for an instant price estimate.</p>
            </div>
            <?= do_shortcode('[ski_simulator area_id="' . $area->id . '"]') ?>
        </section>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var sliders = document.querySelectorAll('.spcu-area-detail__slideshow');

    sliders.forEach(function(slider){
        var track = slider.querySelector('.spcu-area-detail__slides-track');
        var slides = slider.querySelectorAll('.spcu-area-detail__slide');
        var dots = slider.querySelectorAll('.spcu-area-detail__slide-dot');
        var prevBtn = slider.querySelector('.spcu-area-detail__slide-btn--prev');
        var nextBtn = slider.querySelector('.spcu-area-detail__slide-btn--next');
        var total = slides.length;
        var current = 0;
        var autoplay = slider.dataset.autoplay === 'true';
        var intervalMs = parseInt(slider.dataset.interval || '4200', 10);
        var timerId = null;

        if(!track || total <= 1){
            return;
        }

        var render = function(index){
            current = (index + total) % total;
            track.style.transform = 'translateX(' + (-current * 100) + '%)';

            slides.forEach(function(slide, slideIndex){
                slide.setAttribute('aria-hidden', slideIndex === current ? 'false' : 'true');
            });

            dots.forEach(function(dot, dotIndex){
                var active = dotIndex === current;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        };

        var stopAutoplay = function(){
            if(timerId !== null){
                window.clearInterval(timerId);
                timerId = null;
            }
        };

        var startAutoplay = function(){
            if(!autoplay){
                return;
            }
            stopAutoplay();
            timerId = window.setInterval(function(){
                render(current + 1);
            }, intervalMs);
        };

        if(prevBtn){
            prevBtn.addEventListener('click', function(){
                render(current - 1);
                startAutoplay();
            });
        }

        if(nextBtn){
            nextBtn.addEventListener('click', function(){
                render(current + 1);
                startAutoplay();
            });
        }

        dots.forEach(function(dot){
            dot.addEventListener('click', function(){
                var target = parseInt(dot.getAttribute('data-slide-index') || '0', 10);
                render(target);
                startAutoplay();
            });
        });

        slider.addEventListener('mouseenter', stopAutoplay);
        slider.addEventListener('mouseleave', startAutoplay);

        render(0);
        startAutoplay();
    });
});
</script>

<?php get_footer(); ?>

<style>
/* Simulator Styling */
.area-simulator {
    /* background: #0f1b2d; */
    padding: 3rem 2rem;
    border-radius: 20px;
    color: white;
}
.sim-card {
    background: #0f1b2d;
    border: 1px solid #0f1b2d;
    border-radius: 16px;
    padding: 2rem;
}
.sim-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.sim-field label {
    display: block;
    font-size: 0.72rem;
    font-weight: 600;
    color: rgba(255,255,255,0.7);
    margin-bottom: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.sim-field input, .sim-field select {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 10px;
    font-size: 0.9rem;
    background: rgba(255,255,255,0.08);
    color: white;
    font-family: inherit;
    appearance: none;
}
.sim-field input:focus, .sim-field select:focus {
    outline: none;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245,158,11,0.15);
}
.sim-field input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}
.sim-field select option {
    background: #1a2d4a;
    color: white;
}
.sim-result {
    background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(245,158,11,0.05));
    border: 1px solid rgba(245,158,11,0.3);
    border-radius: 14px;
    padding: 2rem;
    margin-top: 1.5rem;
    text-align: center;
}
.sim-result .price-label {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: rgba(255,255,255,0.6);
    margin-bottom: 0.8rem;
}
.sim-result .price-pp {
    font-family: 'Playfair Display', serif;
    font-size: 3rem;
    font-weight: 800;
    color: #f59e0b;
    letter-spacing: 0;
    white-space: nowrap;
}
.sim-result .price-sub {
    font-size: 1rem;
    color: rgba(255,255,255,0.7);
    margin: 0.5rem 0 1.5rem;
}
.sim-breakdown {
    margin: 0 auto 1.2rem;
    max-width: 640px;
    text-align: left;
}
.sim-breakdown ul {
    margin: 0;
    padding: 0;
    list-style: none;
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 10px;
    overflow: hidden;
    background: rgba(255,255,255,0.05);
}
.sim-breakdown li {
    display: flex;
    justify-content: space-between;
    gap: 0.9rem;
    padding: 0.7rem 0.9rem;
    border-bottom: 1px solid rgba(255,255,255,0.12);
    font-size: 0.88rem;
    color: rgba(255,255,255,0.82);
}
.sim-breakdown li:last-child {
    border-bottom: none;
}
.sim-breakdown li strong {
    color: #f8fafc;
    font-size: 0.9rem;
    white-space: nowrap;
}
.sim-breakdown li.is-strong {
    background: rgba(245,158,11,0.16);
}
.sim-breakdown li.is-strong strong {
    color: #fcd34d;
    font-weight: 800;
}
.sim-peak-note {
    font-size: 0.75rem;
    color: #fbbf24;
    background: rgba(251,191,36,0.1);
    border: 1px solid rgba(251,191,36,0.2);
    border-radius: 6px;
    padding: 0.5rem 1rem;
    margin-bottom: 1.5rem;
    display: inline-block;
}
.sim-note {
    font-size: 0.72rem;
    color: rgba(255,255,255,0.45);
    margin-top: 1.5rem;
}

@media (max-width: 768px) {
    .sim-row { grid-template-columns: 1fr; gap: 1rem; }
    .sim-result .price-pp { font-size: 2.2rem; }
}
</style>
