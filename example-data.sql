-- ============================================================================
-- Ski Price Calculator Example Data
-- Realistic sample data for testing and demonstration
-- ============================================================================

-- ── AREAS ────────────────────────────────────────────────────────────────
INSERT INTO wp_spcu_areas (type, name, name_ja, short_description, featured_image) VALUES
('Prefecture', 'Hakuba Valley', '白馬バレー',
'Stretching across the dramatic Northern Japan Alps, Hakuba Valley is one of Asia’s most legendary ski destinations and host of the 1998 Winter Olympic alpine events. The valley connects multiple world-class resorts offering terrain for every level—from gentle groomers to steep backcountry bowls and tree runs loved by advanced riders. Off the slopes, Hakuba delivers a lively alpine village atmosphere with international dining, craft beer bars, cozy cafés, and soothing natural onsens overlooking snow-covered peaks. Perfect for travelers seeking powder, scenery, and nightlife in one destination.',
0),

('Town', 'Niseko', 'ニセコ',
'Niseko is globally famous as the powder capital of the world, attracting skiers and snowboarders from every continent in search of legendary “Japow.” Niseko United links four major resorts across Mount Yotei, offering seamless lift access, extensive night skiing, and incredibly reliable snowfall. The town’s international atmosphere features English-friendly services, luxury chalets, gourmet restaurants, and vibrant après-ski culture, making it ideal for travelers wanting world-class facilities with Japanese hospitality.',
0),

('Town', 'Yuzawa Snow Park', '湯沢スノーパーク',
'Located just 80 minutes from Tokyo via Shinkansen, Yuzawa Snow Park is Japan’s most convenient ski destination. Perfect for day trips and weekend escapes, it offers gentle beginner terrain, excellent ski schools, and family-friendly snow activities. Visitors can enjoy relaxing hot springs, cozy mountain dining, and beautiful snowy landscapes without traveling far from the city.',
0),

('Prefecture', 'Nagano', '長野',
'Nagano is the historic heart of Japan’s winter sports scene and host of the 1998 Winter Olympics. The region features world-famous resorts such as Shiga Kogen and Hakuba, known for long seasons, high-altitude terrain, and breathtaking alpine scenery. Visitors can combine skiing with cultural experiences, including traditional villages, temples, and the famous Snow Monkeys bathing in natural hot springs.',
0),

('Town', 'Rusutsu', 'ルスツ',
'Spanning three mountains, Rusutsu is celebrated for some of the best tree skiing in the world. This purpose-built resort features high-speed lifts, wide uncrowded slopes, and consistent Hokkaido powder. Families love the indoor theme park and diverse dining, while advanced riders appreciate the endless off-piste opportunities.',
0),

('Town', 'Nozawa Onsen', '野沢温泉',
'Nozawa Onsen blends authentic Japanese culture with exceptional skiing. This charming village features narrow streets, traditional ryokans, and 13 free public hot springs. Famous for its 10km downhill run and lively festivals, it offers a unique mix of deep snow, cultural heritage, and warm hospitality.',
0),

('Town', 'Myoko Kogen', '妙高高原',
'Myoko Kogen is known for some of the heaviest snowfall in Japan, making it a paradise for powder lovers. The area retains an authentic, old-school charm with wide-open runs, deep tree skiing, and stunning views of Mount Myoko. Perfect for travelers seeking deep snow and a traditional Japanese atmosphere.',
0),

('Town', 'Tomamu', 'トマム',
'Tomamu is a stylish winter wonderland managed by Hoshino Resorts, designed with families in mind. Beyond the groomed slopes, the resort features the magical Ice Village, the Mina-Mina indoor beach, and the breathtaking Unkai Terrace. A perfect destination for combining skiing with luxury resort experiences.',
0),

('Town', 'Appi Kogen', '安比高原',
'Appi Kogen is often called the “Aspen of Japan” thanks to its perfectly groomed silky snow and elegant resort atmosphere. The north-facing slopes preserve snow quality all day, while refined hotels and dining create a luxurious winter escape ideal for couples and families.',
0),

('Town', 'Zao Onsen', '蔵王温泉',
'Zao Onsen is famous for its magical “Snow Monsters” — trees encased in snow and ice that create a surreal winter landscape. Visitors can enjoy scenic skiing, night tours, and the healing waters of historic sulfur hot springs, making it a destination where adventure and relaxation meet.',
0);

-- ── HOTELS ──────────────────────────────────────────────────────────────

INSERT INTO wp_spcu_hotels (area_id, name, name_ja, grade, short_description, address, is_featured) VALUES

-- HAKUBA
(1,'Hakuba Highland Hotel','白馬ハイランドホテル','premium',
'Famous for its breathtaking panoramic views of the Northern Alps, this hotel offers one of Hakuba’s most scenic outdoor onsens. Guests enjoy spacious rooms, warm hospitality, and convenient shuttle access to the Happo-one lifts. A perfect blend of comfort, scenery, and value.',
'3581 Hakuba, Nagano',1),

(1,'Hakuba Grandvaux','白馬グランボー','exclusive',
'A refined luxury lodge combining French elegance with alpine charm. Guests enjoy gourmet dining, concierge ski services, and a tranquil private atmosphere. Designed for travelers seeking a sophisticated ski holiday with personalized service.',
'3593 Hakuba, Nagano',1),

(1,'Alpine Valley Lodge','アルパインバレーロッジ','standard',
'A welcoming family-run lodge known for its cozy atmosphere and hearty home-cooked breakfasts. Located near beginner-friendly slopes, it is ideal for families and first-time skiers seeking comfort and affordability.',
'3500 Hakuba, Nagano',0),

(1,'Ezo Powder House','エゾパウダーハウス','premium',
'A stylish modern lodge designed for groups and powder enthusiasts. Features a large communal kitchen, spacious lounge areas, and professional gear-drying facilities—perfect for adventurous skiers.',
'3585 Hakuba, Nagano',0),

(1,'Hakuba Mominoki Hotel','白馬樅の木ホテル','exclusive',
'Nestled in the tranquil Wadano forest, this hotel delivers a true alpine luxury experience with a renowned alkaline onsen and an impressive wine cellar. Perfect for travelers seeking relaxation after epic ski days.',
'4683-2 Hokujo, Hakuba, Nagano',0),

-- NISEKO
(2,'Park Hyatt Niseko Hanazono','パークハイアットニセコ','exclusive',
'The pinnacle of luxury in Niseko featuring ski-in/ski-out access, private onsens, and floor-to-ceiling Mount Yotei views. World-class dining and spa experiences make it one of Japan’s most prestigious ski resorts.',
'328-47 Iwaobetsu, Kutchan, Hokkaido',1),

(2,'Niseko Grand Hotel','ニセコグランドホテル','premium',
'Home to Niseko’s largest mixed outdoor onsen, this traditional hotel offers spacious rooms, authentic Japanese hospitality, and outstanding seafood buffets after a day in deep powder.',
'204 Niseko, Hokkaido',1),

(2,'Hilton Niseko Village','ヒルトンニセコビレッジ','exclusive',
'A modern ski-in/ski-out resort with an iconic outdoor onsen overlooking snow-covered mountains. Guests enjoy luxury spa treatments, premium dining, and direct gondola access.',
'Higashiyama-onsen, Niseko, Hokkaido',0),

(2,'Niseko Powder Lodge','ニセコパウダーロッジ','standard',
'A lively and budget-friendly lodge in Annupuri focused on powder lovers. Perfect for travelers who prioritize snow quality and a social ski atmosphere over luxury.',
'210 Niseko, Hokkaido',0),

(2,'Niseko Base Camp','ニセコベースキャンプ','standard',
'Modern self-catering apartments offering flexibility and convenience for families and groups. Located within walking distance of restaurants and village life.',
'198 Niseko, Hokkaido',0),

-- YUZAWA
(3,'NASPA New Otani','NASPAニューオータニ','exclusive',
'A rare ski-only resort offering a quiet luxury experience. Features premium facilities, private ski lockers, and spacious rooms designed for relaxation.',
'2117-9 Yuzawa, Niigata',1),

(3,'Hotel Futaba','ホテル双葉','premium',
'Known as the “bathhouse hotel,” featuring 28 mineral hot spring baths and authentic ryokan hospitality. Guests enjoy panoramic mountain views and exceptional Japanese cuisine.',
'419 Yuzawa, Niigata',1),

(3,'Yuzawa Prince Hotel','湯沢プリンスホテル','premium',
'A convenient resort hotel near the Shinkansen station, perfect for beginners and weekend travelers. Easy access to rental shops and ski lifts.',
'1800 Yuzawa, Niigata',0),

(3,'Snow Ridge Inn','スノーリッジイン','standard',
'A cheerful and affordable inn popular with solo travelers and students. Cozy lounge areas and night-skiing views create a relaxed atmosphere.',
'1770 Yuzawa, Niigata',0),

(3,'Yuzawa Family Resort','湯沢ファミリーリゾート','standard',
'Designed for families with children, offering indoor play areas, nursery services, and easy access to sledding and beginner slopes.',
'1750 Yuzawa, Niigata',0),

-- NAGANO
(4,'Hotel Grand Phenix Okushiga','ホテルグランフェニックス奥志賀','exclusive',
'A prestigious Swiss chalet-style hotel known for fine dining and elegant alpine ambiance. A favorite among discerning skiers visiting Shiga Kogen.',
'Okushiga Kogen, Nagano',1),

(4,'Nagano Olympic Lodge','長野オリンピックロッジ','premium',
'A historic hotel that hosted Olympic athletes, blending sporty atmosphere with modern comfort and memorabilia displays.',
'3680 Nagano, Nagano',1),

(4,'Shiga Grand Hotel','志賀グランドホテル','standard',
'A large and practical hotel offering excellent lift access and buffet dining, perfect for groups and ski tours.',
'7148-31 Ichinose, Nagano',0),

(4,'Nagano Summit Hotel','長野サミットホテル','standard',
'Located at high altitude with spectacular sunrise views. Ideal for skiers who want first tracks every morning.',
'3670 Nagano, Nagano',0),

-- RUSUTSU
(5,'The Vale Rusutsu','ザ・ヴェール・ルスツ','exclusive',
'Luxury designer condos redefining mountain living with private onsens and premium interiors. Perfect for upscale family ski holidays.',
'27-6 Izumikawa, Rusutsu, Hokkaido',1),

(5,'The Westin Rusutsu Resort','ウェスティン ルスツリゾート','exclusive',
'A landmark tower hotel offering bi-level suites and signature Heavenly Beds for ultimate post-ski recovery.',
'133 Izumikawa, Rusutsu, Hokkaido',1),

(5,'Rusutsu Resort Hotel','ルスツリゾートホテル','premium',
'The heart of resort life connected to shopping, dining, and the monorail. Great for families seeking convenience and entertainment.',
'133 Izumikawa, Rusutsu, Hokkaido',0),

(5,'Hotel Lilla Huset','ホテルリラハセット','standard',
'A charming boutique hotel offering Scandinavian-inspired design and peaceful surroundings near the gondola.',
'13-3 Izumikawa, Rusutsu, Hokkaido',0),

(5,'Rusutsu Pension Lilla','ペンション リラハセット','standard',
'A cozy pension run by passionate local hosts, famous for hearty home-cooked meals and warm hospitality.',
'13-3 Izumikawa, Rusutsu, Hokkaido',0),

-- NOZAWA ONSEN
(6,'Ryokan Sakaya','旅館さかや','exclusive',
'An 18-generation luxury ryokan featuring stunning timber architecture and a spectacular traditional bathhouse.',
'9329 Toyosato, Nozawa Onsen, Nagano',1),

(6,'The Ridge Nozawa','ザ・リッジ野沢','premium',
'Modern ski apartments beside the Nagasaka Gondola, perfect for international guests seeking comfort and convenience.',
'9806 Toyosato, Nozawa Onsen, Nagano',1),

(6,'Address Nozawa','アドレス野沢','premium',
'Colorful boutique studios in the village center with creative design and kitchenettes for independent travelers.',
'9535 Toyosato, Nozawa Onsen, Nagano',0),

(6,'Nozawa Hospitality','野沢ホスピタリティ','standard',
'A collection of renovated traditional houses blending local charm with modern comfort.',
'9285 Toyosato, Nozawa Onsen, Nagano',0),

(6,'Kamoshika Lodge','カモシカロッジ','standard',
'A social lodge known for its lively bar and communal ski culture.',
'9330 Toyosato, Nozawa Onsen, Nagano',0),

-- MYOKO
(7,'Akakura Kanko Hotel','赤倉観光ホテル','exclusive',
'A historic luxury hotel famous for its infinity onsen terrace overlooking the mountains.',
'216 Tagiri, Myoko, Niigata',1),

(7,'Lotte Arai Resort','ロッテアライリゾート','exclusive',
'A massive upscale resort village offering spa, adventure activities, and guided powder experiences.',
'1966 Ryozenji, Myoko, Niigata',1),

(7,'Akakura Hotel','赤倉ホテル','premium',
'A traditional hotel in the heart of the village with expansive baths and classic charm.',
'441 Akakura Onsen, Myoko, Niigata',0),

(7,'Hotel Taizan','ホテル太山','standard',
'A friendly lodge known for natural volcanic hot spring water and great location.',
'351 Akakura Onsen, Myoko, Niigata',0),

(7,'Red Horse Myoko','レッドホース妙高','standard',
'A newly renovated lodge with modern comfort and excellent breakfasts.',
'209-3 Tagiri, Myoko, Niigata',0),

-- TOMAMU
(8,'Hoshino Risonare Tomamu','リゾナーレトマム','exclusive',
'Luxury family suites featuring private whirlpool baths and premium resort services.',
'Nakatomamu, Shimukappu, Hokkaido',1),

(8,'Club Med Tomamu','クラブメッドトマム','exclusive',
'The ultimate all-inclusive ski experience covering lift passes, lessons, dining, and entertainment.',
'Nakatomamu, Shimukappu, Hokkaido',1),

(8,'Tomamu The Tower','トマム ザ・タワー','premium',
'A central landmark hotel ideal for families with easy access to resort attractions.',
'Nakatomamu, Shimukappu, Hokkaido',0),

(8,'Petit Hotel Grace','プチホテルグレーズ','standard',
'A quiet and affordable hotel just outside the resort gates.',
'3070 Shimukappu, Hokkaido',0),

(8,'Pension Woody Note','ペンションウッディノート','standard',
'A cozy guesthouse famous for warm hospitality and local knowledge.',
'3073 Shimukappu, Hokkaido',0),

-- APPI
(9,'ANA InterContinental Appi','ANAインターコンチネンタル安比高原','exclusive',
'A true five-star ski resort featuring refined dining and a private club lounge.',
'117-46 Appi Kogen, Iwate',1),

(9,'ANA Crowne Plaza Appi','ANAクラウンプラザリゾート安比高原','premium',
'A large resort complex with pools, restaurants, and one of Tohoku’s biggest onsen facilities.',
'117-17 Appi Kogen, Iwate',1),

(9,'ANA Holiday Inn Appi','ANAホリデイ・インリゾート安比高原','standard',
'Family-friendly hotel with direct slope access and spacious rooms.',
'117-1 Appi Kogen, Iwate',0),

(9,'Hachimantai Heights','八幡平ハイツ','premium',
'A serene forest retreat known for outdoor baths and premium Wagyu dining.',
'1-1 Hachimantai, Iwate',0),

(9,'Pension Mutti','ペンション・ムッティ','standard',
'A charming pension famous for homemade breads and jams.',
'605-64 Appi Kogen, Iwate',0),

-- ZAO
(10,'Takamiya Ryokan Miyamaso','深山荘 高見屋','exclusive',
'A 300-year-old ryokan showcasing traditional architecture and antique interiors.',
'54 Zao Onsen, Yamagata',1),

(10,'Zao Meitoya So','蔵王温泉 名湯舎 創','exclusive',
'A modern minimalist ryokan designed for relaxation and comfort.',
'48 Zao Onsen, Yamagata',1),

(10,'Zao Kokusai Hotel','蔵王国際ホテル','premium',
'Famous for its all-wood hot spring building and milky sulfur baths.',
'933 Zao Onsen, Yamagata',0),

(10,'Hotel Lucent Takamiya','ホテルルーセントタカミヤ','premium',
'A convenient hotel at the base of the ropeway, ideal for night Snow Monster tours.',
'942 Zao Onsen, Yamagata',0),

(10,'Zao Astraea Hotel','蔵王アストリアホテル','standard',
'A unique mountain hotel offering first access to the slopes every morning.',
'801 Zao Onsen, Yamagata',0);


-- ── HOTEL PRICES (Weekday / Weekend / Holiday Dates) ─────────────────────
-- Rebuild hotel rules so every hotel has all 3 schedule types.
DELETE FROM wp_spcu_prices WHERE category = 'hotel';

-- Weekday prices (Mon-Fri)
INSERT INTO wp_spcu_prices (
	category, hotel_id, days, price_type,
	weekdays_json, dates_json, date_from, date_to,
	currency, price_jpy, price_usd
)
SELECT
	'hotel' AS category,
	h.id AS hotel_id,
	NULL AS days,
	'selected_days' AS price_type,
	'["monday","tuesday","wednesday","thursday","friday"]' AS weekdays_json,
	NULL AS dates_json,
	NULL AS date_from,
	NULL AS date_to,
	'BOTH' AS currency,
	ROUND(
		(
			CASE h.grade
				WHEN 'standard' THEN 58000
				WHEN 'premium' THEN 88000
				WHEN 'exclusive' THEN 135000
				ELSE 70000
			END
		)
		*
		(
			CASE h.area_id
				WHEN 1 THEN 1.08  -- Hakuba
				WHEN 2 THEN 1.18  -- Niseko
				WHEN 3 THEN 0.86  -- Yuzawa
				WHEN 4 THEN 1.00  -- Nagano
				WHEN 5 THEN 1.10  -- Rusutsu
				WHEN 6 THEN 1.02  -- Nozawa
				WHEN 7 THEN 1.06  -- Myoko
				WHEN 8 THEN 1.12  -- Tomamu
				WHEN 9 THEN 1.04  -- Appi
				WHEN 10 THEN 0.98 -- Zao
				ELSE 1.00
			END
		),
		0
	) AS price_jpy,
	ROUND(
		(
			(
				CASE h.grade
					WHEN 'standard' THEN 58000
					WHEN 'premium' THEN 88000
					WHEN 'exclusive' THEN 135000
					ELSE 70000
				END
			)
			*
			(
				CASE h.area_id
					WHEN 1 THEN 1.08
					WHEN 2 THEN 1.18
					WHEN 3 THEN 0.86
					WHEN 4 THEN 1.00
					WHEN 5 THEN 1.10
					WHEN 6 THEN 1.02
					WHEN 7 THEN 1.06
					WHEN 8 THEN 1.12
					WHEN 9 THEN 1.04
					WHEN 10 THEN 0.98
					ELSE 1.00
				END
			)
		) * 0.0073,
		0
	) AS price_usd
FROM wp_spcu_hotels h;

-- Weekend prices (Sat-Sun)
INSERT INTO wp_spcu_prices (
	category, hotel_id, days, price_type,
	weekdays_json, dates_json, date_from, date_to,
	currency, price_jpy, price_usd
)
SELECT
	'hotel',
	h.id,
	NULL,
	'weekend',
	NULL,
	NULL,
	NULL,
	NULL,
	'BOTH',
	ROUND(
		(
			CASE h.grade
				WHEN 'standard' THEN 58000
				WHEN 'premium' THEN 88000
				WHEN 'exclusive' THEN 135000
				ELSE 70000
			END
		)
		*
		(
			CASE h.area_id
				WHEN 1 THEN 1.08
				WHEN 2 THEN 1.18
				WHEN 3 THEN 0.86
				WHEN 4 THEN 1.00
				WHEN 5 THEN 1.10
				WHEN 6 THEN 1.02
				WHEN 7 THEN 1.06
				WHEN 8 THEN 1.12
				WHEN 9 THEN 1.04
				WHEN 10 THEN 0.98
				ELSE 1.00
			END
		)
		* 1.18,
		0
	),
	ROUND(
		(
			(
				CASE h.grade
					WHEN 'standard' THEN 58000
					WHEN 'premium' THEN 88000
					WHEN 'exclusive' THEN 135000
					ELSE 70000
				END
			)
			*
			(
				CASE h.area_id
					WHEN 1 THEN 1.08
					WHEN 2 THEN 1.18
					WHEN 3 THEN 0.86
					WHEN 4 THEN 1.00
					WHEN 5 THEN 1.10
					WHEN 6 THEN 1.02
					WHEN 7 THEN 1.06
					WHEN 8 THEN 1.12
					WHEN 9 THEN 1.04
					WHEN 10 THEN 0.98
					ELSE 1.00
				END
			)
			* 1.18
		) * 0.0073,
		0
	)
FROM wp_spcu_hotels h;

-- Holiday prices (specific high-season dates)
INSERT INTO wp_spcu_prices (
	category, hotel_id, days, price_type,
	weekdays_json, dates_json, date_from, date_to,
	currency, price_jpy, price_usd
)
SELECT
	'hotel',
	h.id,
	NULL,
	'specific_dates',
	NULL,
	'["2026-12-24","2026-12-25","2026-12-26","2026-12-27","2026-12-28","2026-12-29","2026-12-30","2026-12-31","2027-01-01","2027-01-02","2027-01-03","2027-02-11"]',
	NULL,
	NULL,
	'BOTH',
	ROUND(
		(
			CASE h.grade
				WHEN 'standard' THEN 58000
				WHEN 'premium' THEN 88000
				WHEN 'exclusive' THEN 135000
				ELSE 70000
			END
		)
		*
		(
			CASE h.area_id
				WHEN 1 THEN 1.08
				WHEN 2 THEN 1.18
				WHEN 3 THEN 0.86
				WHEN 4 THEN 1.00
				WHEN 5 THEN 1.10
				WHEN 6 THEN 1.02
				WHEN 7 THEN 1.06
				WHEN 8 THEN 1.12
				WHEN 9 THEN 1.04
				WHEN 10 THEN 0.98
				ELSE 1.00
			END
		)
		* 1.35,
		0
	),
	ROUND(
		(
			(
				CASE h.grade
					WHEN 'standard' THEN 58000
					WHEN 'premium' THEN 88000
					WHEN 'exclusive' THEN 135000
					ELSE 70000
				END
			)
			*
			(
				CASE h.area_id
					WHEN 1 THEN 1.08
					WHEN 2 THEN 1.18
					WHEN 3 THEN 0.86
					WHEN 4 THEN 1.00
					WHEN 5 THEN 1.10
					WHEN 6 THEN 1.02
					WHEN 7 THEN 1.06
					WHEN 8 THEN 1.12
					WHEN 9 THEN 1.04
					WHEN 10 THEN 0.98
					ELSE 1.00
				END
			)
			* 1.35
		) * 0.0073,
		0
	)
FROM wp_spcu_hotels h;


-- ── ADDON PRICES (Lift, Gear, Transport) ────────────────────────────────

-- Hakuba Valley Addons
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(1, 'lift', 'standard', 5, 31000, 225),
(1, 'lift', 'premium', 5, 31000, 225),
(1, 'lift', 'exclusive', 5, 31000, 225),
(1, 'gear', 'standard', 5, 42000, 305),
(1, 'gear', 'premium', 5, 52000, 378),
(1, 'gear', 'exclusive', 5, 62000, 451),
(1, 'transport', 'standard', NULL, 24000, 174),
(1, 'transport', 'premium', NULL, 28000, 204),
(1, 'transport', 'exclusive', NULL, 32000, 233);

-- Niseko Addons
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(2, 'lift', 'standard', 5, 33000, 240),
(2, 'lift', 'premium', 5, 33000, 240),
(2, 'lift', 'exclusive', 5, 33000, 240),
(2, 'gear', 'standard', 5, 45000, 327),
(2, 'gear', 'premium', 5, 55000, 400),
(2, 'gear', 'exclusive', 5, 65000, 473),
(2, 'transport', 'standard', NULL, 26000, 189),
(2, 'transport', 'premium', NULL, 30000, 218),
(2, 'transport', 'exclusive', NULL, 34000, 247);

-- Yuzawa Snow Park Addons
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(3, 'lift', 'standard', 5, 28000, 204),
(3, 'lift', 'premium', 5, 28000, 204),
(3, 'lift', 'exclusive', 5, 28000, 204),
(3, 'gear', 'standard', 5, 38000, 276),
(3, 'gear', 'premium', 5, 48000, 349),
(3, 'gear', 'exclusive', 5, 58000, 422),
(3, 'transport', 'standard', NULL, 18000, 131),
(3, 'transport', 'premium', NULL, 22000, 160),
(3, 'transport', 'exclusive', NULL, 26000, 189);

-- Nagano Addons
INSERT INTO wp_spcu_addon_prices (area_id, category, grade, days, price_jpy, price_usd) VALUES
(4, 'lift', 'standard', 5, 32000, 233),
(4, 'lift', 'premium', 5, 32000, 233),
(4, 'lift', 'exclusive', 5, 32000, 233),
(4, 'gear', 'standard', 5, 40000, 291),
(4, 'gear', 'premium', 5, 50000, 364),
(4, 'gear', 'exclusive', 5, 60000, 436),
(4, 'transport', 'standard', NULL, 20000, 145),
(4, 'transport', 'premium', NULL, 25000, 182),
(4, 'transport', 'exclusive', NULL, 30000, 218);

-- ── NOTES ────────────────────────────────────────────────────────────────
-- 1. Prices are examples based on typical Japanese ski resort rates
-- 2. Hotel prices are per night with schedule-based rule types
-- 3. Hotel price types included: weekday (selected_days), weekend, holiday (specific_dates)
-- 4. Lift & Gear prices are per 5-day period
-- 5. Transport is per person round trip
-- 6. Exclusive grades are typically 20-50% more expensive
-- 7. Niseko is premium (highest prices), Yuzawa is budget-friendly (lowest)
-- 8. All currency conversions at ~1¥ = 0.0073 USD (approximate)
