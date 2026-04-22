-- Sample data for testing the enhanced Prefecture Widget
-- Insert Nagano Prefecture
INSERT INTO wp_spcu_prefectures (name, name_ja, short_description) VALUES 
('Nagano', '長野県', 'Home to world-class ski resorts in the Japanese Alps');

-- Get the prefecture ID (assuming it's 1 for Nagano)
-- Insert sample areas matching the design image

INSERT INTO wp_spcu_areas (
    prefecture_id, 
    type, 
    name, 
    name_ja, 
    short_description, 
    description, 
    total_runs, 
    total_resorts, 
    distance, 
    featured_badge, 
    area_tags
) VALUES 
(
    1,
    'City',
    'Hakuba Valley',
    '白馬',
    'Host of the 1998 Winter Olympics. 10 interconnected resorts with world-class powder.',
    '<p>Host of the 1998 Winter Olympics. 10 interconnected resorts with world-class powder. The Alps Hotel opens Dec 2026.</p>',
    NULL,
    10,
    '2.5h from Tokyo',
    'MOST POPULAR',
    '["10 Resorts", "Olympic Heritage", "Deep Powder"]'
),
(
    1,
    'City',
    'Shiga Kogen',
    '志賀高原',
    'Japan\'s largest ski area with 19 interconnected resorts in a UNESCO Biosphere Reserve. Home of the famous Snow Monkeys.',
    '<p>Japan\'s largest ski area — 19 interconnected resorts in a UNESCO Biosphere Reserve. Home of the famous Snow Monkeys.</p>',
    NULL,
    19,
    '3.5h from Tokyo',
    'LARGEST IN JAPAN',
    '["19 Resorts", "UNESCO", "Snow Monkeys"]'
),
(
    1,
    'Village',
    'Nozawa Onsen',
    '野沢温泉',
    'A charming traditional village with 13 free public hot spring baths. Ski by day, soak by night in an authentic onsen town.',
    '<p>A charming traditional village with 13 free public hot spring baths. Ski by day, soak by night in an authentic onsen town.</p>',
    36,
    1,
    '3h from Tokyo',
    'HOT SPRINGS',
    '["36 Runs", "Onsen Village", "Traditional"]'
);
