<?php
if (!defined('ABSPATH')) exit;

class SPCU_Shortcode {

    public function __construct(){
        add_shortcode('ski_calculator', [$this,'render']);
        add_shortcode('ski_areas_overview', [$this,'render_areas_overview']);
        add_shortcode('ski_quote_form', [$this,'render_quote_form']);
        add_action('wp_enqueue_scripts', [$this,'enqueue_assets']);
    }

    public function enqueue_assets(){
        wp_enqueue_style(
            'spcu-public',
            SPCU_URL . 'public/public.css',
            [],
            '2.0'
        );
    }

    public function render(){
        $api_base = esc_url(rest_url('spc/v1'));
        ob_start(); ?>

<div class="ski-calculator" id="spcu-app">

    <h2>Ski Price Calculator</h2>

    <!-- Step 1: Pick hotel -->
    <div class="spcu-step" id="spcu-step-1">
        <div class="spcu-field">
            <label for="spcu_hotel">Hotel</label>
            <select id="spcu_hotel">
                <option value="">— Loading hotels… —</option>
            </select>
        </div>

        <div class="spcu-hotel-info" id="spcu_hotel_info" style="display:none;">
            <div id="spcu_hotel_images" class="spcu-hotel-images"></div>
            <div id="spcu_hotel_meta" class="spcu-hotel-meta"></div>
        </div>
    </div>

    <!-- Step 2: Pick check-in date + nights -->
    <div class="spcu-step" id="spcu-step-2" style="display:none;">
        <div class="spcu-field">
            <label for="spcu_checkin">Check-in Date</label>
            <input type="date" id="spcu_checkin">
        </div>

        <div class="spcu-field">
            <label for="spcu_nights">Number of Nights</label>
            <input type="number" id="spcu_nights" min="1" value="3">
        </div>

        <div class="spcu-field">
            <label for="spcu_currency">Display Currency</label>
            <select id="spcu_currency">
                <option value="JPY">JPY (¥)</option>
                <option value="USD">USD ($)</option>
            </select>
        </div>
    </div>

    <!-- Step 3: Add-ons -->
    <div class="spcu-step" id="spcu-step-3" style="display:none;">
        <div class="spcu-field">
            <label for="spcu_days">Ski Days (for Lift &amp; Gear)</label>
            <input type="number" id="spcu_days" min="1" value="3">
        </div>
        <div class="spcu-addons" id="spcu_addons"></div>
    </div>

    <!-- Customer name -->
    <div class="spcu-step" id="spcu-step-info" style="display:none;">
        <div class="spcu-field">
            <label for="spcu_customer">Customer Name</label>
            <input id="spcu_customer" placeholder="John Smith">
        </div>
    </div>

    <!-- Action buttons -->
    <div class="spcu-actions">
        <button class="spcu-btn" id="spcu-btn-calc" style="display:none;">Calculate Price</button>
    </div>

    <!-- Result -->
    <div class="spcu-result" id="spcu_result_box" style="display:none;">
        <h3>Estimated Package Price</h3>
        <div id="spcu_breakdown" class="spcu-breakdown"></div>
        <div class="spcu-total">
            Total: <span id="spcu_total_price"></span>
        </div>
        <p class="spcu-note">* Hotel price shown is per night. Final price subject to availability.</p>
    </div>

    <div class="spcu-error" id="spcu_error" style="display:none;"></div>

</div>

<script>
(function(){

var API = <?= json_encode($api_base) ?>;
var allPrices = [];
var hotels    = [];

/* ── Boot ─────────────────────────────────────────────────────── */
fetch(API + '/hotels')
    .then(function(r){ return r.json(); })
    .then(function(data){
        hotels = data;
        var sel = document.getElementById('spcu_hotel');
        sel.innerHTML = '<option value="">— Select a hotel —</option>';
        data.forEach(function(h){
            var opt = document.createElement('option');
            opt.value = h.id;
            opt.textContent = h.name + (h.name_ja ? ' / '+h.name_ja : '');
            sel.appendChild(opt);
        });
    })
    .catch(function(){ showError('Could not load hotel data. Please try again later.'); });

fetch(API + '/prices')
    .then(function(r){ return r.json(); })
    .then(function(data){ allPrices = data; });

/* ── Hotel change ──────────────────────────────────────────────── */
document.getElementById('spcu_hotel').addEventListener('change', function(){
    var id  = this.value;
    var h   = hotels.find(function(x){ return String(x.id) === id; });
    var info = document.getElementById('spcu_hotel_info');

    if(!h){ info.style.display='none'; show('spcu-step-2',false); return; }

    // Images
    var imgWrap = document.getElementById('spcu_hotel_images');
    imgWrap.innerHTML = '';
    (h.image_urls||[]).slice(0,4).forEach(function(url){
        var img = document.createElement('img');
        img.src = url; img.alt = h.name;
        imgWrap.appendChild(img);
    });

    // Meta
    document.getElementById('spcu_hotel_meta').innerHTML =
        '<strong>'+esc(h.name)+(h.name_ja?' / '+esc(h.name_ja):'')+'</strong>' +
        (h.area ? '<br><span>📍 '+esc(h.area)+(h.area_ja?' / '+esc(h.area_ja):'')+'</span>' : '') +
        (h.address ? '<br><span>'+esc(h.address)+'</span>' : '') +
        (h.grade ? '<br><span class="spcu-badge">'+esc(h.grade)+'</span>' : '');

    info.style.display = 'block';

    // Load add-ons for this hotel's area
    populateAddons(h.area_id);

    show('spcu-step-2', true);
    show('spcu-step-3', true);
    show('spcu-step-info', true);
    document.getElementById('spcu-btn-calc').style.display = '';
    document.getElementById('spcu_result_box').style.display = 'none';
});

/* ── Populate add-ons (lift/gear/transport for area) ───────────── */
function populateAddons(area_id){
    var cats   = ['lift','gear','transport'];
    var wrap   = document.getElementById('spcu_addons');
    wrap.innerHTML = '';
    cats.forEach(function(cat){
        var label = cat.charAt(0).toUpperCase()+cat.slice(1);
        var div   = document.createElement('div');
        div.className = 'spcu-field';
        div.innerHTML = '<label><input type="checkbox" class="spcu-addon" data-cat="'+cat+'"> Include '+label+'</label>';
        wrap.appendChild(div);
    });
}

/* ── Calculate ─────────────────────────────────────────────────── */
document.getElementById('spcu-btn-calc').addEventListener('click', calculate);

function calculate(){
    hideError();
    var hotelId  = document.getElementById('spcu_hotel').value;
    var checkin  = document.getElementById('spcu_checkin').value;
    var nights   = parseInt(document.getElementById('spcu_nights').value) || 1;
    var skiDays  = parseInt(document.getElementById('spcu_days').value)   || 1;
    var currency = document.getElementById('spcu_currency').value;

    if(!hotelId){ showError('Please select a hotel.'); return; }
    if(!checkin){ showError('Please select a check-in date.'); return; }

    var qs = '?hotel_id='+encodeURIComponent(hotelId)+'&date='+encodeURIComponent(checkin);

    fetch(API + '/hotel-price' + qs)
        .then(function(r){ return r.json(); })
        .then(function(data){
            renderResult(data.matched, nights, skiDays, currency);
        })
        .catch(function(){ showError('Could not fetch prices. Please try again.'); });
}

function renderResult(rule, nights, skiDays, currency){
    var breakdown = document.getElementById('spcu_breakdown');
    var totalEl   = document.getElementById('spcu_total_price');
    var result    = document.getElementById('spcu_result_box');

    breakdown.innerHTML = '';
    var total = 0;

    // Hotel
    if(rule){
        var label = scheduleLabel(rule);
        var minPrc = currency==='USD' ? rule.price_min_usd : rule.price_min_jpy;
        var maxPrc = currency==='USD' ? rule.price_max_usd : rule.price_max_jpy;
        var sym    = currency==='USD' ? '$' : '¥';

        if(minPrc && maxPrc){
            var midPrc = ((parseFloat(minPrc)+parseFloat(maxPrc))/2) * nights;
            total += midPrc;
            addRow(breakdown, 'Hotel (×'+nights+' nights)', label,
                sym+fmt(minPrc)+' – '+sym+fmt(maxPrc)+' /night', sym+fmt(midPrc)+' (mid)');
        }
    }

    // Add-ons
    var checkinVal = document.getElementById('spcu_checkin').value;
    var dObj  = checkinVal ? new Date(checkinVal) : null;
    var ymd   = checkinVal; // already YYYY-MM-DD
    var dow   = dObj ? dObj.toLocaleDateString('en-US', {weekday:'long'}).toLowerCase() : '';

    document.querySelectorAll('.spcu-addon:checked').forEach(function(chk){
        var cat = chk.dataset.cat;
        var hotelId = document.getElementById('spcu_hotel').value;
        var h   = hotels.find(function(x){ return String(x.id)===hotelId; });
        var areaId  = h ? h.area_id : null;
        var gradeKey = h ? h.grade_key : null;

        // Filter prices for this category/area/days/grade
        var possible = allPrices.filter(function(p){
            var areaMatch = (!p.area_id || String(p.area_id)===String(areaId));
            if(!areaMatch) return false;

            if(p.category !== cat) return false;

            if(cat === 'transport'){
                return (!p.grade_key || String(p.grade_key)===String(gradeKey));
            } else {
                return (!p.days     || parseInt(p.days)===skiDays);
            }
        });

        // Addons do not have date schedules
        var match = possible.length ? possible[0] : null;

        var sym = currency==='USD' ? '$' : '¥';
        if(match){
            var prc = currency==='USD' ? parseFloat(match.price_usd||0) : parseFloat(match.price_jpy||0);
            total += prc;
            var catLabel = cat.charAt(0).toUpperCase()+cat.slice(1);
            addRow(breakdown, catLabel+' ('+skiDays+' days)', '', sym+fmt(prc), sym+fmt(prc));
        }
    });

    var sym = currency==='USD' ? '$' : '¥';
    totalEl.textContent = sym + fmt(total);
    result.style.display = 'block';
    result.scrollIntoView({behavior:'smooth', block:'nearest'});
}

function addRow(wrap, label, sub, unit, total){
    var tr = document.createElement('div');
    tr.className = 'spcu-brow';
    tr.innerHTML = '<span class="spcu-brow-label">'+esc(label)+(sub?'<small>'+esc(sub)+'</small>':'')+'</span>'
                 + '<span class="spcu-brow-unit">'+esc(unit)+'</span>'
                 + '<span class="spcu-brow-total">'+esc(total)+'</span>';
    wrap.appendChild(tr);
}

function scheduleLabel(rule){
    if(!rule) return '';
    switch(rule.price_type){
        case 'selected_days': return (rule.weekdays||[]).map(function(d){ return d.charAt(0).toUpperCase()+d.slice(1); }).join(', ');
        case 'weekend':       return 'Sat & Sun';
        case 'date_range':    return rule.date_from+' → '+rule.date_to;
        case 'specific_dates':return (rule.dates||[]).join(', ');
        default:              return rule.price_type;
    }
}

/* ── Utilities ─────────────────────────────────────────────────── */
function fmt(n){ return parseFloat(n).toLocaleString(undefined,{maximumFractionDigits:0}); }
function esc(s){ return String(s||'').replace(/[<>&"]/g,function(c){return({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'})[c];}); }
function show(id, vis){ var el=document.getElementById(id); if(el) el.style.display = vis ? '' : 'none'; }
function showError(msg){ var el=document.getElementById('spcu_error'); el.textContent=msg; el.style.display='block'; }
function hideError(){ document.getElementById('spcu_error').style.display='none'; }

})();
</script>

<?php
        return ob_get_clean();
    }

    public function render_areas_overview(){
        global $wpdb;
        
        // Get all areas
        $areas = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}spcu_areas ORDER BY name ASC" );
        
        if( empty($areas) ){
            return '<p style="text-align:center; color:#999;">No areas found.</p>';
        }
        
        ob_start();
        ?>
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1200px; margin: 20px auto;">
    <?php foreach( $areas as $area ):
        // Get hotels in this area
        $hotels = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spcu_hotels WHERE area_id = %d ORDER BY name ASC",
            $area->id
        ));
        
        // Get addon prices for this area
        $prices = $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT category, grade FROM {$wpdb->prefix}spcu_addon_prices WHERE area_id = %d ORDER BY category ASC, FIELD(grade, 'standard', 'premium', 'exclusive') ASC",
            $area->id
        ));
        
        // Group prices by category and grade
        $price_data = [];
        foreach( $prices as $p ){
            if( !isset($price_data[$p->category]) ){
                $price_data[$p->category] = [];
            }
            $price_data[$p->category][] = $p->grade;
        }
        ?>
        <div style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <!-- Area Header -->
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #f0f0f0;">
                <div>
                    <h2 style="margin: 0 0 8px 0; font-size: 28px; font-weight: 700; color: #1a1a1a;">
                        <?php echo esc_html($area->name); ?>
                    </h2>
                    <?php if( !empty($area->name_ja) ): ?>
                        <p style="margin: 0; font-size: 16px; color: #666;">
                            <?php echo esc_html($area->name_ja); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 32px; font-weight: 700; color: var(--spcu-admin-primary, #0073aa);">
                        <?php echo count($hotels); ?>
                    </div>
                    <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">
                        Hotel<?php echo count($hotels) !== 1 ? 's' : ''; ?>
                    </div>
                </div>
            </div>

            <!-- Hotels Grid -->
            <?php if( !empty($hotels) ): ?>
                <div style="margin-bottom: 20px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #666; text-transform: uppercase;">
                        Hotels
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
                        <?php foreach( $hotels as $hotel ): ?>
                            <div style="background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 6px; padding: 12px; font-size: 14px;">
                                <strong><?php echo esc_html($hotel->name); ?></strong>
                                <?php if( !empty($hotel->name_ja) ): ?>
                                    <div style="font-size: 12px; color: #666; margin-top: 4px;">
                                        <?php echo esc_html($hotel->name_ja); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Addon Fees Table -->
            <?php if( !empty($price_data) ): ?>
                <div style="margin-top: 20px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #666; text-transform: uppercase;">
                        Addon Fees
                    </h3>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <thead>
                                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                                    <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Category</th>
                                    <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Standard</th>
                                    <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Premium</th>
                                    <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Exclusive</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach( $price_data as $category => $grades ): ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 10px; font-weight: 500; color: #333; text-transform: capitalize;">
                                            <?php echo esc_html(str_replace('_', ' ', $category)); ?>
                                        </td>
                                        <?php foreach( ['standard', 'premium', 'exclusive'] as $grade ): ?>
                                            <td style="padding: 10px; color: #555;">
                                                <?php
                                                if( in_array($grade, $grades) ){
                                                    $price_entry = $wpdb->get_row( $wpdb->prepare(
                                                        "SELECT price_jpy, price_usd FROM {$wpdb->prefix}spcu_addon_prices WHERE area_id = %d AND category = %s AND grade = %s LIMIT 1",
                                                        $area->id, $category, $grade
                                                    ));
                                                    if( $price_entry ){
                                                        echo '¥' . intval($price_entry->price_jpy) . ' / $' . intval($price_entry->price_usd);
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
    <?php endforeach; ?>
</div>
        <?php
        return ob_get_clean();
    }

    public function render_quote_form(){
        global $wpdb;
        
        // Get areas
        $areas = $wpdb->get_results("SELECT id, name, name_ja FROM {$wpdb->prefix}spcu_areas ORDER BY name ASC");
        
        // Grades for package level
        $grades = SPCU_Grades::options();
        
        ob_start(); ?>

<div class="spcu-quote-form-wrapper">
    <div class="spcu-quote-form">
        <!-- Step indicators -->
        <div class="spcu-quote-steps">
            <div class="spcu-quote-step active">
                <span class="spcu-quote-step-num">1</span>
                <span class="spcu-quote-step-label">Where</span>
            </div>
            <div class="spcu-quote-step">
                <span class="spcu-quote-step-num">2</span>
                <span class="spcu-quote-step-label">Level</span>
            </div>
            <div class="spcu-quote-step">
                <span class="spcu-quote-step-num">3</span>
                <span class="spcu-quote-step-label">Dates</span>
            </div>
            <div class="spcu-quote-step">
                <span class="spcu-quote-step-num">4</span>
                <span class="spcu-quote-step-label">Group</span>
            </div>
            <div class="spcu-quote-step">
                <span class="spcu-quote-step-num">5</span>
                <span class="spcu-quote-step-label">Options</span>
            </div>
        </div>

        <!-- Form fields -->
        <form id="spcu-quote-form">
            <div class="spcu-quote-row">
                <!-- Column 1: Resort Area -->
                <div class="spcu-quote-col">
                    <label>RESORT AREA</label>
                    <select id="quote_area" name="area" required>
                        <?php if(!empty($areas)): ?>
                            <option value="<?= esc_attr($areas[0]->id) ?>" selected><?= esc_html($areas[0]->name) ?></option>
                            <?php foreach(array_slice($areas, 1) as $area): ?>
                                <option value="<?= esc_attr($area->id) ?>"><?= esc_html($area->name) ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">- No areas available -</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Column 2: Package Level -->
                <div class="spcu-quote-col">
                    <label>PACKAGE LEVEL</label>
                    <select id="quote_level" name="level" required>
                        <?php $grade_list = array_keys($grades); if(!empty($grade_list)): ?>
                            <option value="<?= esc_attr($grade_list[0]) ?>" selected><?= esc_html($grades[$grade_list[0]]) ?></option>
                            <?php foreach(array_slice($grade_list, 1) as $key): ?>
                                <option value="<?= esc_attr($key) ?>"><?= esc_html($grades[$key]) ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">- No levels available -</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="spcu-quote-row">
                <!-- Column 3: Stay Dates -->
                <div class="spcu-quote-col">
                    <label>STAY DATES</label>
                    <div class="spcu-quote-date-range">
                        <input type="date" id="quote_checkin" name="checkin" required>
                        <span class="spcu-quote-date-sep">to</span>
                        <input type="date" id="quote_checkout" name="checkout" required>
                    </div>
                    <small id="quote_duration_hint" class="spcu-quote-date-hint">Select check-in and check-out dates</small>
                    <small id="quote_calendar_min_rate" class="spcu-quote-date-hint">Minimum rate/day: ¥--</small>
                    <input type="hidden" id="quote_duration" name="duration" value="5">
                </div>
            </div>

            <div class="spcu-quote-row">
                <!-- Column 4: Number of Guests -->
                <div class="spcu-quote-col">
                    <label>NUMBER OF GUESTS</label>
                    <select id="quote_guests" name="guests" required>
                        <option value="1" selected>1 guest</option>
                        <option value="2">2 guests</option>
                        <option value="3">3 guests</option>
                        <option value="4">4 guests</option>
                        <option value="5">5 guests</option>
                        <option value="6">6 guests</option>
                        <option value="7">7 guests</option>
                        <option value="8">8+ guests</option>
                    </select>
                </div>

                <!-- Column 5: Season -->
                <div class="spcu-quote-col">
                    <label>SEASON</label>
                    <select id="quote_season" name="season" required>
                        <option value="regular" selected>Regular Season (Dec - Mar)</option>
                        <option value="peak">Peak (Dec 26-Jan 3 / Feb 20-22)</option>
                    </select>
                </div>
            </div>
        </form>

        <!-- Pricing breakdown -->
        <div class="spcu-quote-pricing" id="quote_pricing_box">
            <div class="spcu-quote-pricing-header">
                <h3>Estimated Price Per Person</h3>
                <div class="spcu-quote-pricing-main">
                    <div class="spcu-quote-price-jpy">
                        <span class="spcu-quote-price-symbol">¥</span><span id="quote_price_jpy">0</span>
                    </div>
                    <div class="spcu-quote-price-range">~ ¥<span id="quote_price_jpy_max">0</span></div>
                </div>
            </div>

            <div class="spcu-quote-pricing-total">
                <div class="spcu-quote-group-total">
                    <span>Group Total (<span id="quote_guests_display">2</span> guests)</span>
                    <span class="spcu-quote-group-price">¥<span id="quote_total_jpy">0</span> ~ ¥<span id="quote_total_jpy_max">0</span></span>
                </div>
            </div>

            <div class="spcu-quote-pricing-breakdown">
                <div class="spcu-quote-item spcu-quote-item-full">
                    <span>Ski Days</span>
                    <span><span id="quote_skidays_display">3</span> days</span>
                </div>
                <div class="spcu-quote-item">
                    <span>Accommodation (<span id="quote_nights_display">5</span> nights)</span>
                    <span>¥<span id="quote_accommodation">0</span> ~ ¥<span id="quote_accommodation_max">0</span></span>
                </div>
                <div class="spcu-quote-item">
                    <span>Lift Tickets</span>
                    <span>¥<span id="quote_lift">0</span></span>
                </div>
                <div class="spcu-quote-item">
                    <span>Gear Rental</span>
                    <span>¥<span id="quote_gear">0</span></span>
                </div>
                <div class="spcu-quote-item">
                    <span>Transport (round trip)</span>
                    <span>¥<span id="quote_transport">0</span></span>
                </div>
                <div class="spcu-quote-item spcu-quote-item-full spcu-quote-item-accent">
                    <span>Minimum Rate / Day</span>
                    <span>¥<span id="quote_min_day_rate">0</span></span>
                </div>
            </div>

            <p class="spcu-quote-note">* Prices are estimates based on standard rates. Final pricing depends on specific dates and hotel availability. Weekend / peak season rates may be higher.</p>

            <button type="button" class="spcu-quote-btn" id="quote_get_quote">Get Exact Quote →</button>
        </div>
    </div>
</div>

<style>
.spcu-quote-form-wrapper {
    max-width: 1000px;
    margin: 30px auto;
}

.spcu-quote-form {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.spcu-quote-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    gap: 10px;
}

.spcu-quote-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    flex: 1;
}

.spcu-quote-step-num {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8e8e8;
    border-radius: 50%;
    font-weight: 600;
    color: #666;
    font-size: 16px;
}

.spcu-quote-step.active .spcu-quote-step-num {
    background: #2563eb;
    color: white;
}

.spcu-quote-step-label {
    font-size: 13px;
    font-weight: 500;
    color: #666;
    text-align: center;
}

.spcu-quote-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.spcu-quote-col {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.spcu-quote-col label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #333;
}

.spcu-quote-col select {
    padding: 12px 14px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
}

.spcu-quote-col input[type="date"] {
    padding: 12px 14px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    transition: all 0.2s;
}

.spcu-quote-col select:hover {
    border-color: #2563eb;
}

.spcu-quote-col input[type="date"]:hover {
    border-color: #2563eb;
}

.spcu-quote-col select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.spcu-quote-col input[type="date"]:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.spcu-quote-date-range {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 8px;
    align-items: center;
}

.spcu-quote-date-sep {
    font-size: 13px;
    font-weight: 600;
    color: #666;
}

.spcu-quote-date-hint {
    font-size: 12px;
    color: #666;
}

.spcu-quote-pricing {
    background: #1a2a4a;
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-top: 30px;
}

.spcu-quote-pricing-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.spcu-quote-pricing-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.spcu-quote-pricing-main {
    display: flex;
    align-items: baseline;
    gap: 10px;
}

.spcu-quote-price-jpy {
    font-size: 28px;
    font-weight: 700;
}

.spcu-quote-price-symbol {
    font-size: 20px;
    margin-right: 4px;
}

.spcu-quote-price-range {
    font-size: 14px;
    color: #aaa;
}

.spcu-quote-pricing-total {
    text-align: right;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.spcu-quote-group-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.spcu-quote-group-total span:first-child {
    font-size: 16px;
    color: #aaa;
}

.spcu-quote-group-price {
    font-size: 24px;
    font-weight: 700;
    color: #ffa500;
}

.spcu-quote-pricing-breakdown {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px 30px;
    margin-bottom: 20px;
}

.spcu-quote-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: 14px;
}

.spcu-quote-item-full {
    grid-column: 1 / -1;
}

.spcu-quote-item-accent {
    border-bottom-color: rgba(255,165,0,0.35);
}

.spcu-quote-item span:first-child {
    color: #bbb;
}

.spcu-quote-item span:last-child {
    font-weight: 600;
    color: #fff;
}

.spcu-quote-note {
    font-size: 12px;
    color: #888;
    margin: 20px 0;
    line-height: 1.5;
}

.spcu-quote-btn {
    display: block;
    width: 100%;
    padding: 16px;
    background: #ffa500;
    color: #1a2a4a;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.spcu-quote-btn:hover {
    background: #ff9500;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(255, 165, 0, 0.3);
}

@media (max-width: 640px) {
    .spcu-quote-form {
        padding: 20px;
    }

    .spcu-quote-steps {
        flex-direction: column;
        gap: 12px;
    }

    .spcu-quote-step {
        flex-direction: row;
        gap: 12px;
    }

    .spcu-quote-row {
        grid-template-columns: 1fr;
    }

    .spcu-quote-pricing-breakdown {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

<script>
(function(){
    var API_BASE = '<?= esc_url(rest_url('spc/v1')) ?>';
    var allPrices = {};
    var dataLoaded = false;

    // Delay init to ensure DOM is ready
    function initializeForm() {
        // Set initial values from form HTML
        var areaSelect = document.getElementById('quote_area');
        var levelSelect = document.getElementById('quote_level');
        var durationSelect = document.getElementById('quote_duration');
        var checkinInput = document.getElementById('quote_checkin');
        var checkoutInput = document.getElementById('quote_checkout');
        var guestsSelect = document.getElementById('quote_guests');
        var seasonSelect = document.getElementById('quote_season');

        var today = new Date();
        var checkinDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 30);
        var checkoutDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 35);

        function formatDate(d) {
            var month = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            return d.getFullYear() + '-' + month + '-' + day;
        }

        if (checkinInput) {
            checkinInput.min = formatDate(today);
            if (!checkinInput.value) {
                checkinInput.value = formatDate(checkinDate);
            }
        }
        if (checkoutInput) {
            checkoutInput.min = checkinInput && checkinInput.value ? checkinInput.value : formatDate(today);
            if (!checkoutInput.value) {
                checkoutInput.value = formatDate(checkoutDate);
            }
        }

        // Ensure selects have values
        if(areaSelect && !areaSelect.value && areaSelect.options.length > 0) {
            areaSelect.value = areaSelect.options[0].value;
        }
        if(levelSelect && !levelSelect.value && levelSelect.options.length > 0) {
            levelSelect.value = levelSelect.options[0].value;
        }
        if(durationSelect && !durationSelect.value) {
            durationSelect.value = '5';
        }
        if(guestsSelect && !guestsSelect.value && guestsSelect.options.length > 0) {
            guestsSelect.value = guestsSelect.options[0].value;
        }
        if(seasonSelect && !seasonSelect.value && seasonSelect.options.length > 0) {
            seasonSelect.value = seasonSelect.options[0].value;
        }

        // Attach change listeners
        ['quote_area', 'quote_level', 'quote_checkin', 'quote_checkout', 'quote_guests', 'quote_season'].forEach(function(id){
            var el = document.getElementById(id);
            if(el) el.addEventListener('change', calculatePrice);
        });

        // Calculate once DOM is ready (data might still be loading)
        calculatePrice();
    }

    // Load catalog data
    fetch(API_BASE + '/catalog')
        .then(function(r){ return r.json(); })
        .then(function(data){
            allPrices = data;
            dataLoaded = true;
            // Recalculate now that we have data
            calculatePrice();
        })
        .catch(function(e){
            console.error('Failed to load catalog:', e);
            allPrices = { areas: [], addon_prices: [], hotels: [] };
        });

    function calculatePrice(){
        var areaId = document.getElementById('quote_area').value;
        var level = document.getElementById('quote_level').value;
        var checkin = document.getElementById('quote_checkin').value;
        var checkout = document.getElementById('quote_checkout').value;
        var guests = parseInt(document.getElementById('quote_guests').value) || 1;
        var season = document.getElementById('quote_season').value;
        var durationInput = document.getElementById('quote_duration');
        var durationHint = document.getElementById('quote_duration_hint');
        var calendarMinRate = document.getElementById('quote_calendar_min_rate');

        function parseDateLocal(value) {
            if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                return null;
            }
            var parts = value.split('-');
            return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        }

        var checkinDate = parseDateLocal(checkin);
        var checkoutDate = parseDateLocal(checkout);
        var nights = 0;
        if (checkinDate && checkoutDate) {
            nights = Math.round((checkoutDate.getTime() - checkinDate.getTime()) / 86400000);
        }

        if (durationInput) {
            durationInput.value = nights > 0 ? String(nights) : '';
        }

        if (durationHint) {
            if (!checkin || !checkout) {
                durationHint.textContent = 'Select check-in and check-out dates';
                durationHint.style.color = '#666';
            } else if (nights <= 0) {
                durationHint.textContent = 'Check-out must be after check-in';
                durationHint.style.color = '#c2410c';
            } else {
                durationHint.textContent = nights + (nights === 1 ? ' night selected' : ' nights selected');
                durationHint.style.color = '#666';
            }
        }

        var checkoutInput = document.getElementById('quote_checkout');
        if (checkoutInput && checkin) {
            checkoutInput.min = checkin;
        }

        // Always show pricing box
        document.getElementById('quote_pricing_box').style.display = 'block';

        // Update display values
        document.getElementById('quote_nights_display').textContent = nights || '-';
        var skiDays = nights > 0 ? Math.ceil(nights * 0.6) : 0;
        document.getElementById('quote_skidays_display').textContent = skiDays || '-';
        document.getElementById('quote_guests_display').textContent = guests || '-';

        // Default to showing dashes if incomplete selection or data not loaded
        if(!areaId || !level || !nights || !guests || !season || !allPrices.hotels || allPrices.hotels.length === 0){
            document.getElementById('quote_price_jpy').textContent = '--';
            document.getElementById('quote_price_jpy_max').textContent = '--';
            document.getElementById('quote_total_jpy').textContent = '--';
            document.getElementById('quote_total_jpy_max').textContent = '--';
            document.getElementById('quote_accommodation').textContent = '--';
            document.getElementById('quote_accommodation_max').textContent = '--';
            document.getElementById('quote_lift').textContent = '--';
            document.getElementById('quote_gear').textContent = '--';
            document.getElementById('quote_transport').textContent = '--';
            document.getElementById('quote_min_day_rate').textContent = '--';
            if (calendarMinRate) {
                calendarMinRate.textContent = 'Minimum rate/day: ¥--';
            }
            return;
        }

        // Find hotels in selected area with selected grade
        var hotelsInArea = (allPrices.hotels || []).filter(function(h){
            return String(h.area_id) === String(areaId) && h.grade_key === level;
        });

        if(hotelsInArea.length === 0){
            document.getElementById('quote_price_jpy').textContent = '--';
            document.getElementById('quote_price_jpy_max').textContent = '--';
            document.getElementById('quote_total_jpy').textContent = '--';
            document.getElementById('quote_total_jpy_max').textContent = '--';
            document.getElementById('quote_accommodation').textContent = '--';
            document.getElementById('quote_accommodation_max').textContent = '--';
            document.getElementById('quote_lift').textContent = '--';
            document.getElementById('quote_gear').textContent = '--';
            document.getElementById('quote_transport').textContent = '--';
            document.getElementById('quote_min_day_rate').textContent = '--';
            if (calendarMinRate) {
                calendarMinRate.textContent = 'Minimum rate/day: ¥--';
            }
            return;
        }

        // Get min/max accommodation prices per night
        var accom_prices = hotelsInArea.map(function(h){
            var prices = (h.prices || []);
            if(prices.length === 0) return null;
            var min_price = Math.min.apply(null, prices.map(function(p){ return parseFloat(p.price_jpy) || 0; }));
            var max_price = Math.max.apply(null, prices.map(function(p){ return parseFloat(p.price_jpy) || 0; }));
            return { min: min_price, max: max_price };
        }).filter(function(x){ return x !== null; });

        var accom_min_per_night = 0;
        var accom_max_per_night = 0;
        if(accom_prices.length > 0){
            accom_min_per_night = Math.min.apply(null, accom_prices.map(function(p){ return p.min; }));
            accom_max_per_night = Math.max.apply(null, accom_prices.map(function(p){ return p.max; }));
        }

        // Accommodation total (per guest for the group)
        var accommodation_low = accom_min_per_night > 0 ? accom_min_per_night * nights : 0;
        var accommodation_high = accom_max_per_night > 0 ? accom_max_per_night * nights : 0;
        var accommodation_group_low = accommodation_low * guests;
        var accommodation_group_high = accommodation_high * guests;

        // Find addon prices (lift, gear, transport) for this area and grade
        var addons = (allPrices.addon_prices || []).filter(function(a){
            return String(a.area_id) === String(areaId) && a.grade_key === level;
        });

        var lift_price = 0, gear_price = 0, transport_price = 0;

        addons.forEach(function(addon){
            if(addon.category === 'lift') lift_price = parseFloat(addon.price_jpy) || 0;
            if(addon.category === 'gear') gear_price = parseFloat(addon.price_jpy) || 0;
            if(addon.category === 'transport') transport_price = parseFloat(addon.price_jpy) || 0;
        });

        // Calculate totals
        var lift_total = lift_price * skiDays * guests;
        var gear_total = gear_price * skiDays * guests;
        var transport_total = transport_price * guests;
        var min_day_rate = (accom_min_per_night + lift_price + gear_price + transport_price) * guests;

        // Total for all guests
        var total_low = accommodation_group_low + lift_total + gear_total + transport_total;
        var total_high = accommodation_group_high + lift_total + gear_total + transport_total;

        // Per-person pricing
        var per_person_low = guests > 0 ? Math.floor(total_low / guests) : 0;
        var per_person_high = guests > 0 ? Math.floor(total_high / guests) : 0;

        // Display prices
        document.getElementById('quote_price_jpy').textContent = per_person_low > 0 ? per_person_low.toLocaleString() : '--';
        document.getElementById('quote_price_jpy_max').textContent = per_person_high > 0 ? per_person_high.toLocaleString() : '--';
        document.getElementById('quote_total_jpy').textContent = total_low > 0 ? total_low.toLocaleString() : '--';
        document.getElementById('quote_total_jpy_max').textContent = total_high > 0 ? total_high.toLocaleString() : '--';
        document.getElementById('quote_accommodation').textContent = accommodation_group_low > 0 ? accommodation_group_low.toLocaleString() : '--';
        document.getElementById('quote_accommodation_max').textContent = accommodation_group_high > 0 ? accommodation_group_high.toLocaleString() : '--';
        document.getElementById('quote_lift').textContent = lift_total > 0 ? lift_total.toLocaleString() : '--';
        document.getElementById('quote_gear').textContent = gear_total > 0 ? gear_total.toLocaleString() : '--';
        document.getElementById('quote_transport').textContent = transport_total > 0 ? transport_total.toLocaleString() : '--';
        document.getElementById('quote_min_day_rate').textContent = min_day_rate > 0 ? Math.floor(min_day_rate).toLocaleString() : '--';
        if (calendarMinRate) {
            calendarMinRate.textContent = min_day_rate > 0
                ? 'Minimum rate/day: ¥' + Math.floor(min_day_rate).toLocaleString()
                : 'Minimum rate/day: ¥--';
        }
    }

    // Get exact quote
    document.getElementById('quote_get_quote').addEventListener('click', function(){
        alert('Quote form submission functionality to be implemented');
    });

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeForm);
    } else {
        initializeForm();
    }
})();
</script>

        <?php
        return ob_get_clean();
    }
}


new SPCU_Shortcode();