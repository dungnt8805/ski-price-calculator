<?php
if (!defined('ABSPATH')) exit;

class SPCU_Shortcode {

    public function __construct(){
        add_shortcode('ski_calculator', [$this,'render']);
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
}

new SPCU_Shortcode();