-- ============================================================================
-- Ski Engine Example Data (Updated Schema)
-- ============================================================================

-- ── PREFECTURES ───────────────────────────────────────────────────────────
INSERT INTO wp_spcu_prefectures (name, name_ja, short_description) VALUES
('Nagano', '長野県', 'The heart of Japan winter sports, host of the 1998 Olympics.'),
('Hokkaido', '北海道', 'Japan\'s northernmost island, famous for legendary powder snow.'),
('Niigata', '新潟県', 'Close to Tokyo with heavy snowfall and authentic village culture.'),
('Iwate', '岩手県', 'Tohoku\'s premier ski destination, home to Appi Kogen.'),
('Yamagata', '山形県', 'Famous for snow monsters and historic sulfur hot springs.');

-- ── AREAS ────────────────────────────────────────────────────────────────
-- prefecture_id: 1=Nagano, 2=Hokkaido, 3=Niigata, 4=Iwate, 5=Yamagata
INSERT INTO wp_spcu_areas (prefecture_id, type, name, name_ja, short_description, total_runs, max_vertical, total_resorts, season, summit, distance, difficulties_json) VALUES

(1, 'Valley', 'Hakuba Valley', '白馬バレー',
 'Host of the 1998 Winter Olympics. Nine interconnected resorts across the Northern Japan Alps.',
 220, 1071, 9, 'Dec to Apr', 2696, '4.5 hrs (Shinkansen + Bus)',
 '{"beginner":30,"intermediate":40,"advanced":30}'),

(2, 'Town', 'Niseko United', 'ニセコユナイテッド',
 'The powder capital of the world. Four interconnected resorts on Mt. Yotei.',
 61, 1200, 4, 'Nov to May', 1308, '7 hrs (Flight + Transfer)',
 '{"beginner":30,"intermediate":40,"advanced":30}'),

(3, 'Town', 'Yuzawa', '湯沢',
 'Just 80 minutes from Tokyo by Shinkansen. Perfect for beginners and weekend trips.',
 40, 600, 6, 'Dec to Mar', 1500, '1.5 hrs (Shinkansen)',
 '{"beginner":50,"intermediate":35,"advanced":15}'),

(1, 'Highland', 'Shiga Kogen', '志賀高原',
 'Japan\'s largest ski area with 80+ lifts and 19 interconnected resorts.',
 80, 770, 19, 'Dec to May', 2307, '4 hrs (Shinkansen + Bus)',
 '{"beginner":40,"intermediate":40,"advanced":20}'),

(2, 'Town', 'Rusutsu', 'ルスツ',
 'Three mountains with world-class tree skiing and consistent Hokkaido powder.',
 37, 621, 3, 'Nov to Apr', 994, '7 hrs (Flight + Transfer)',
 '{"beginner":25,"intermediate":45,"advanced":30}'),

(1, 'Village', 'Nozawa Onsen', '野沢温泉',
 'A 1,300-year-old hot spring village with 36 runs and a famous 10km descent.',
 36, 1085, 1, 'Dec to Mar', 1650, '3.5 hrs (Shinkansen + Bus)',
 '{"beginner":30,"intermediate":40,"advanced":30}'),

(3, 'Highland', 'Myoko Kogen', '妙高高原',
 'Some of the heaviest snowfall in Japan with wide open runs and deep tree skiing.',
 55, 800, 6, 'Dec to Mar', 1855, '2.5 hrs (Shinkansen + Bus)',
 '{"beginner":25,"intermediate":40,"advanced":35}'),

(2, 'Resort', 'Tomamu', 'トマム',
 'A stylish Hoshino Resorts destination famous for Ice Village and the Sea of Clouds.',
 26, 580, 2, 'Dec to Mar', 1239, '7 hrs (Flight + Transfer)',
 '{"beginner":40,"intermediate":40,"advanced":20}'),

(4, 'Highland', 'Appi Kogen', '安比高原',
 'The "Aspen of Japan" with perfectly groomed silky snow and elegant resort atmosphere.',
 22, 550, 1, 'Dec to Mar', 1304, '5 hrs (Shinkansen + Bus)',
 '{"beginner":35,"intermediate":45,"advanced":20}'),

(5, 'Onsen', 'Zao Onsen', '蔵王温泉',
 'Famous for magical Snow Monsters and healing sulfur hot springs.',
 28, 715, 1, 'Dec to Mar', 1660, '3 hrs (Shinkansen + Bus)',
 '{"beginner":40,"intermediate":35,"advanced":25}');

-- ── HOTELS ────────────────────────────────────────────────────────────────
-- area_id: 1=Hakuba, 2=Niseko, 3=Yuzawa, 4=Shiga, 5=Rusutsu, 6=Nozawa, 7=Myoko, 8=Tomamu, 9=Appi, 10=Zao
-- Backfill slugs for any existing hotels that don't have one
UPDATE wp_spcu_hotels SET slug = LOWER(REGEXP_REPLACE(REGEXP_REPLACE(name, '[^a-zA-Z0-9 ]', ''), ' +', '-')) WHERE slug IS NULL OR slug = '';

INSERT INTO wp_spcu_hotels (area_id, name, name_ja, slug, grade, short_description, address, is_featured) VALUES
-- HAKUBA
(1,'Hakuba Highland Hotel','白馬ハイランドホテル','hakuba-highland-hotel','premium','Breathtaking panoramic views with a scenic outdoor onsen and shuttle to Happo-one lifts.','3581 Hakuba, Nagano',1),
(1,'Hakuba Grandvaux','白馬グランボー','hakuba-grandvaux','exclusive','Refined luxury lodge combining French elegance with concierge ski services.','3593 Hakuba, Nagano',1),
(1,'Alpine Valley Lodge','アルパインバレーロッジ','alpine-valley-lodge','standard','Family-run lodge with hearty breakfasts, near beginner-friendly slopes.','3500 Hakuba, Nagano',0),
(1,'Ezo Powder House','エゾパウダーハウス','ezo-powder-house','premium','Stylish modern lodge for groups with gear-drying facilities and communal kitchen.','3585 Hakuba, Nagano',0),
(1,'Hakuba Mominoki Hotel','白馬樅の木ホテル','hakuba-mominoki-hotel','exclusive','Alpine luxury in the Wadano forest with alkaline onsen and wine cellar.','4683-2 Hokujo, Hakuba, Nagano',0),
-- NISEKO
(2,'Park Hyatt Niseko Hanazono','パークハイアットニセコ','park-hyatt-niseko-hanazono','exclusive','Ski-in/ski-out luxury with private onsens and Mt. Yotei views.','328-47 Iwaobetsu, Kutchan, Hokkaido',1),
(2,'Niseko Grand Hotel','ニセコグランドホテル','niseko-grand-hotel','premium','Largest mixed outdoor onsen in Niseko with authentic Japanese buffets.','204 Niseko, Hokkaido',1),
(2,'Hilton Niseko Village','ヒルトンニセコビレッジ','hilton-niseko-village','exclusive','Ski-in/ski-out resort with iconic outdoor onsen and direct gondola access.','Higashiyama-onsen, Niseko, Hokkaido',0),
(2,'Niseko Powder Lodge','ニセコパウダーロッジ','niseko-powder-lodge','standard','Budget-friendly lodge in Annupuri for powder lovers.','210 Niseko, Hokkaido',0),
(2,'Niseko Base Camp','ニセコベースキャンプ','niseko-base-camp','standard','Self-catering apartments for families and groups near the village.','198 Niseko, Hokkaido',0),
-- YUZAWA
(3,'NASPA New Otani','NASPAニューオータニ','naspa-new-otani','exclusive','Ski-only resort with premium facilities and private ski lockers.','2117-9 Yuzawa, Niigata',1),
(3,'Hotel Futaba','ホテル双葉','hotel-futaba','premium','28 mineral hot spring baths with authentic ryokan hospitality.','419 Yuzawa, Niigata',1),
(3,'Yuzawa Prince Hotel','湯沢プリンスホテル','yuzawa-prince-hotel','premium','Convenient hotel near the Shinkansen for beginners and weekend travelers.','1800 Yuzawa, Niigata',0),
(3,'Snow Ridge Inn','スノーリッジイン','snow-ridge-inn','standard','Affordable inn popular with solo travelers and students.','1770 Yuzawa, Niigata',0),
-- SHIGA KOGEN
(4,'Hotel Grand Phenix Okushiga','ホテルグランフェニックス奥志賀','hotel-grand-phenix-okushiga','exclusive','Prestigious Swiss chalet-style hotel with fine dining and elegant alpine ambiance.','Okushiga Kogen, Nagano',1),
(4,'Nagano Olympic Lodge','長野オリンピックロッジ','nagano-olympic-lodge','premium','Historic hotel that hosted Olympic athletes with modern comfort.','3680 Nagano, Nagano',1),
(4,'Shiga Grand Hotel','志賀グランドホテル','shiga-grand-hotel','standard','Large practical hotel with excellent lift access and buffet dining.','7148-31 Ichinose, Nagano',0),
-- RUSUTSU
(5,'The Vale Rusutsu','ザ・ヴェール・ルスツ','the-vale-rusutsu','exclusive','Luxury designer condos with private onsens and premium interiors.','27-6 Izumikawa, Rusutsu, Hokkaido',1),
(5,'The Westin Rusutsu Resort','ウェスティン ルスツリゾート','the-westin-rusutsu-resort','exclusive','Landmark tower hotel with bi-level suites and Heavenly Beds.','133 Izumikawa, Rusutsu, Hokkaido',1),
(5,'Rusutsu Resort Hotel','ルスツリゾートホテル','rusutsu-resort-hotel','premium','Connected to shopping, dining, and the monorail. Great for families.','133 Izumikawa, Rusutsu, Hokkaido',0),
(5,'Hotel Lilla Huset','ホテルリラハセット','hotel-lilla-huset','standard','Scandinavian-inspired boutique hotel near the gondola.','13-3 Izumikawa, Rusutsu, Hokkaido',0),
-- NOZAWA ONSEN
(6,'Ryokan Sakaya','旅館さかや','ryokan-sakaya','exclusive','18-generation luxury ryokan with stunning timber architecture and traditional bathhouse.','9329 Toyosato, Nozawa Onsen, Nagano',1),
(6,'The Ridge Nozawa','ザ・リッジ野沢','the-ridge-nozawa','premium','Modern ski apartments beside the Nagasaka Gondola.','9806 Toyosato, Nozawa Onsen, Nagano',1),
(6,'Nozawa Hospitality','野沢ホスピタリティ','nozawa-hospitality','standard','Renovated traditional houses blending local charm with modern comfort.','9285 Toyosato, Nozawa Onsen, Nagano',0),
-- MYOKO
(7,'Akakura Kanko Hotel','赤倉観光ホテル','akakura-kanko-hotel','exclusive','Historic luxury hotel with an infinity onsen terrace overlooking the mountains.','216 Tagiri, Myoko, Niigata',1),
(7,'Lotte Arai Resort','ロッテアライリゾート','lotte-arai-resort','exclusive','Upscale resort village with spa and guided powder experiences.','1966 Ryozenji, Myoko, Niigata',1),
(7,'Akakura Hotel','赤倉ホテル','akakura-hotel','premium','Traditional village hotel with expansive baths and classic charm.','441 Akakura Onsen, Myoko, Niigata',0),
(7,'Hotel Taizan','ホテル太山','hotel-taizan','standard','Friendly lodge with natural volcanic hot spring water.','351 Akakura Onsen, Myoko, Niigata',0),
-- TOMAMU
(8,'Hoshino Risonare Tomamu','リゾナーレトマム','hoshino-risonare-tomamu','exclusive','Luxury family suites with private whirlpool baths and premium services.','Nakatomamu, Shimukappu, Hokkaido',1),
(8,'Club Med Tomamu','クラブメッドトマム','club-med-tomamu','exclusive','All-inclusive experience covering lift passes, lessons, dining, and entertainment.','Nakatomamu, Shimukappu, Hokkaido',1),
(8,'Tomamu The Tower','トマム ザ・タワー','tomamu-the-tower','premium','Central landmark hotel with easy access to resort attractions.','Nakatomamu, Shimukappu, Hokkaido',0),
(8,'Pension Woody Note','ペンションウッディノート','pension-woody-note','standard','Cozy guesthouse famous for warm hospitality and local knowledge.','3073 Shimukappu, Hokkaido',0),
-- APPI
(9,'ANA InterContinental Appi','ANAインターコンチネンタル安比高原','ana-intercontinental-appi','exclusive','Five-star ski resort with refined dining and a private club lounge.','117-46 Appi Kogen, Iwate',1),
(9,'ANA Crowne Plaza Appi','ANAクラウンプラザリゾート安比高原','ana-crowne-plaza-appi','premium','Large resort with pools and one of Tohoku''s biggest onsen facilities.','117-17 Appi Kogen, Iwate',1),
(9,'ANA Holiday Inn Appi','ANAホリデイ・インリゾート安比高原','ana-holiday-inn-appi','standard','Family-friendly hotel with direct slope access and spacious rooms.','117-1 Appi Kogen, Iwate',0),
-- ZAO
(10,'Takamiya Ryokan Miyamaso','深山荘 高見屋','takamiya-ryokan-miyamaso','exclusive','300-year-old ryokan with traditional architecture and antique interiors.','54 Zao Onsen, Yamagata',1),
(10,'Zao Kokusai Hotel','蔵王国際ホテル','zao-kokusai-hotel','premium','Famous for its all-wood hot spring building and milky sulfur baths.','933 Zao Onsen, Yamagata',0),
(10,'Zao Astraea Hotel','蔵王アストリアホテル','zao-astraea-hotel','standard','Mountain hotel offering first access to the slopes every morning.','801 Zao Onsen, Yamagata',0);

-- ── HOTEL PRICES ─────────────────────────────────────────────────────────
DELETE FROM wp_spcu_prices WHERE category = 'hotel';

-- Weekday prices
INSERT INTO wp_spcu_prices (category, hotel_id, price_type, weekdays_json, currency, price_jpy, price_usd)
SELECT 'hotel', h.id, 'selected_days',
 '["monday","tuesday","wednesday","thursday","friday"]',
 'JPY',
 ROUND((CASE h.grade WHEN 'standard' THEN 58000 WHEN 'premium' THEN 88000 WHEN 'exclusive' THEN 135000 ELSE 70000 END)
  * (CASE h.area_id WHEN 1 THEN 1.08 WHEN 2 THEN 1.18 WHEN 3 THEN 0.86 WHEN 4 THEN 1.02 WHEN 5 THEN 1.10 WHEN 6 THEN 1.02 WHEN 7 THEN 1.06 WHEN 8 THEN 1.12 WHEN 9 THEN 1.04 WHEN 10 THEN 0.98 ELSE 1.00 END), 0),
 ROUND((CASE h.grade WHEN 'standard' THEN 58000 WHEN 'premium' THEN 88000 WHEN 'exclusive' THEN 135000 ELSE 70000 END)
  * (CASE h.area_id WHEN 1 THEN 1.08 WHEN 2 THEN 1.18 WHEN 3 THEN 0.86 WHEN 4 THEN 1.02 WHEN 5 THEN 1.10 WHEN 6 THEN 1.02 WHEN 7 THEN 1.06 WHEN 8 THEN 1.12 WHEN 9 THEN 1.04 WHEN 10 THEN 0.98 ELSE 1.00 END) * 0.0073, 0)
FROM wp_spcu_hotels h;

-- Weekend prices (+18%)
INSERT INTO wp_spcu_prices (category, hotel_id, price_type, currency, price_jpy, price_usd)
SELECT 'hotel', h.id, 'weekend', 'JPY',
 ROUND((CASE h.grade WHEN 'standard' THEN 58000 WHEN 'premium' THEN 88000 WHEN 'exclusive' THEN 135000 ELSE 70000 END)
  * (CASE h.area_id WHEN 1 THEN 1.08 WHEN 2 THEN 1.18 WHEN 3 THEN 0.86 WHEN 4 THEN 1.02 WHEN 5 THEN 1.10 WHEN 6 THEN 1.02 WHEN 7 THEN 1.06 WHEN 8 THEN 1.12 WHEN 9 THEN 1.04 WHEN 10 THEN 0.98 ELSE 1.00 END) * 1.18, 0),
 ROUND((CASE h.grade WHEN 'standard' THEN 58000 WHEN 'premium' THEN 88000 WHEN 'exclusive' THEN 135000 ELSE 70000 END)
  * (CASE h.area_id WHEN 1 THEN 1.08 WHEN 2 THEN 1.18 WHEN 3 THEN 0.86 WHEN 4 THEN 1.02 WHEN 5 THEN 1.10 WHEN 6 THEN 1.02 WHEN 7 THEN 1.06 WHEN 8 THEN 1.12 WHEN 9 THEN 1.04 WHEN 10 THEN 0.98 ELSE 1.00 END) * 1.18 * 0.0073, 0)
FROM wp_spcu_hotels h;

-- Holiday prices (+35%)
INSERT INTO wp_spcu_prices (category, hotel_id, price_type, dates_json, currency, price_jpy, price_usd)
SELECT 'hotel', h.id, 'specific_dates',
 '["2026-12-24","2026-12-25","2026-12-26","2026-12-27","2026-12-28","2026-12-29","2026-12-30","2026-12-31","2027-01-01","2027-01-02","2027-01-03","2027-02-11"]',
 'JPY',
 ROUND((CASE h.grade WHEN 'standard' THEN 58000 WHEN 'premium' THEN 88000 WHEN 'exclusive' THEN 135000 ELSE 70000 END)
  * (CASE h.area_id WHEN 1 THEN 1.08 WHEN 2 THEN 1.18 WHEN 3 THEN 0.86 WHEN 4 THEN 1.02 WHEN 5 THEN 1.10 WHEN 6 THEN 1.02 WHEN 7 THEN 1.06 WHEN 8 THEN 1.12 WHEN 9 THEN 1.04 WHEN 10 THEN 0.98 ELSE 1.00 END) * 1.35, 0),
 ROUND((CASE h.grade WHEN 'standard' THEN 58000 WHEN 'premium' THEN 88000 WHEN 'exclusive' THEN 135000 ELSE 70000 END)
  * (CASE h.area_id WHEN 1 THEN 1.08 WHEN 2 THEN 1.18 WHEN 3 THEN 0.86 WHEN 4 THEN 1.02 WHEN 5 THEN 1.10 WHEN 6 THEN 1.02 WHEN 7 THEN 1.06 WHEN 8 THEN 1.12 WHEN 9 THEN 1.04 WHEN 10 THEN 0.98 ELSE 1.00 END) * 1.35 * 0.0073, 0)
FROM wp_spcu_hotels h;

-- ── ADDON PRICES (Lift, Gear, Transport) ─────────────────────────────────
-- Hakuba
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(1,'lift','standard',5,31000,225),(1,'lift','premium',5,31000,225),(1,'lift','exclusive',5,31000,225),
(1,'gear','standard',5,42000,305),(1,'gear','premium',5,52000,378),(1,'gear','exclusive',5,62000,451),
(1,'transport','standard',NULL,24000,174),(1,'transport','premium',NULL,28000,204),(1,'transport','exclusive',NULL,32000,233);

-- Niseko
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(2,'lift','standard',5,33000,240),(2,'lift','premium',5,33000,240),(2,'lift','exclusive',5,33000,240),
(2,'gear','standard',5,45000,327),(2,'gear','premium',5,55000,400),(2,'gear','exclusive',5,65000,473),
(2,'transport','standard',NULL,26000,189),(2,'transport','premium',NULL,30000,218),(2,'transport','exclusive',NULL,34000,247);

-- Yuzawa
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(3,'lift','standard',5,28000,204),(3,'lift','premium',5,28000,204),(3,'lift','exclusive',5,28000,204),
(3,'gear','standard',5,38000,276),(3,'gear','premium',5,48000,349),(3,'gear','exclusive',5,58000,422),
(3,'transport','standard',NULL,18000,131),(3,'transport','premium',NULL,22000,160),(3,'transport','exclusive',NULL,26000,189);

-- Shiga Kogen
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(4,'lift','standard',5,32000,233),(4,'lift','premium',5,32000,233),(4,'lift','exclusive',5,32000,233),
(4,'gear','standard',5,40000,291),(4,'gear','premium',5,50000,364),(4,'gear','exclusive',5,60000,436),
(4,'transport','standard',NULL,20000,145),(4,'transport','premium',NULL,25000,182),(4,'transport','exclusive',NULL,30000,218);

-- Rusutsu
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(5,'lift','standard',5,32000,233),(5,'lift','premium',5,32000,233),(5,'lift','exclusive',5,32000,233),
(5,'gear','standard',5,43000,312),(5,'gear','premium',5,53000,385),(5,'gear','exclusive',5,63000,458),
(5,'transport','standard',NULL,25000,182),(5,'transport','premium',NULL,29000,211),(5,'transport','exclusive',NULL,33000,240);

-- Nozawa Onsen
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(6,'lift','standard',5,29000,211),(6,'lift','premium',5,29000,211),(6,'lift','exclusive',5,29000,211),
(6,'gear','standard',5,39000,284),(6,'gear','premium',5,49000,356),(6,'gear','exclusive',5,59000,429),
(6,'transport','standard',NULL,21000,152),(6,'transport','premium',NULL,26000,189),(6,'transport','exclusive',NULL,31000,225);

-- Myoko
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(7,'lift','standard',5,30000,218),(7,'lift','premium',5,30000,218),(7,'lift','exclusive',5,30000,218),
(7,'gear','standard',5,40000,291),(7,'gear','premium',5,50000,364),(7,'gear','exclusive',5,60000,436),
(7,'transport','standard',NULL,22000,160),(7,'transport','premium',NULL,27000,196),(7,'transport','exclusive',NULL,32000,233);

-- Tomamu
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(8,'lift','standard',5,30000,218),(8,'lift','premium',5,30000,218),(8,'lift','exclusive',5,30000,218),
(8,'gear','standard',5,42000,305),(8,'gear','premium',5,52000,378),(8,'gear','exclusive',5,62000,451),
(8,'transport','standard',NULL,24000,174),(8,'transport','premium',NULL,28000,204),(8,'transport','exclusive',NULL,32000,233);

-- Appi
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(9,'lift','standard',5,28000,204),(9,'lift','premium',5,28000,204),(9,'lift','exclusive',5,28000,204),
(9,'gear','standard',5,38000,276),(9,'gear','premium',5,48000,349),(9,'gear','exclusive',5,58000,422),
(9,'transport','standard',NULL,22000,160),(9,'transport','premium',NULL,27000,196),(9,'transport','exclusive',NULL,32000,233);

-- Zao
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(10,'lift','standard',5,27000,196),(10,'lift','premium',5,27000,196),(10,'lift','exclusive',5,27000,196),
(10,'gear','standard',5,36000,261),(10,'gear','premium',5,46000,334),(10,'gear','exclusive',5,56000,407),
(10,'transport','standard',NULL,19000,138),(10,'transport','premium',NULL,24000,174),(10,'transport','exclusive',NULL,29000,211);

-- ── NOTES ────────────────────────────────────────────────────────────────
-- Prefectures: 1=Nagano, 2=Hokkaido, 3=Niigata, 4=Iwate, 5=Yamagata
-- Areas: 1=Hakuba, 2=Niseko, 3=Yuzawa, 4=Shiga, 5=Rusutsu, 6=Nozawa, 7=Myoko, 8=Tomamu, 9=Appi, 10=Zao
-- Hotel grades: standard / premium / exclusive
-- Weekday = base, Weekend = +18%, Holiday = +35%
-- Lift & Gear: per 5-day period. Transport: per person round trip.
