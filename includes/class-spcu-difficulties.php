<?php
if (!defined('ABSPATH')) exit;

class SPCU_Difficulties {

    const OPTION_NAME = 'spcu_difficulties';

    public static function bootstrap(){
        add_action('init', [__CLASS__, 'maybe_seed_defaults'], 1);
    }

    public static function default_records(){
        return [
            ['name' => 'Beginner', 'slug' => 'beginner', 'description' => '', 'color' => '#16a34a'],
            ['name' => 'Intermediate', 'slug' => 'intermediate', 'description' => '', 'color' => '#2563eb'],
            ['name' => 'Advanced', 'slug' => 'advanced', 'description' => '', 'color' => '#dc2626'],
            ['name' => 'Expert', 'slug' => 'expert', 'description' => '', 'color' => '#000000'],
        ];
    }

    public static function legacy_map(){
        return [
            'intermidiate' => 'intermediate',
            'intermidate' => 'intermediate',
        ];
    }

    public static function maybe_seed_defaults(){
        $stored = get_option(self::OPTION_NAME, null);
        if(!is_array($stored) || empty($stored)){
            update_option(self::OPTION_NAME, self::default_records());
            return;
        }

        $records = self::sanitize_records($stored);
        if(!self::looks_like_difficulty_records($records)){
            // If option data was previously used for grades, reset to canonical difficulties.
            update_option(self::OPTION_NAME, self::default_records());
            return;
        }

        if($records !== $stored){
            update_option(self::OPTION_NAME, $records);
        }
    }

    public static function records(){
        $stored = get_option(self::OPTION_NAME, []);
        $records = self::sanitize_records(is_array($stored) ? $stored : []);

        if(empty($records) || !self::looks_like_difficulty_records($records)){
            $records = self::default_records();
            update_option(self::OPTION_NAME, $records);
        } elseif($records !== $stored){
            update_option(self::OPTION_NAME, $records);
        }

        return $records;
    }

    public static function keyed_records(){
        $records = [];
        foreach(self::records() as $record){
            $records[$record['slug']] = $record;
        }
        return $records;
    }

    public static function options(){
        $options = [];
        foreach(self::records() as $record){
            $options[$record['slug']] = $record['name'];
        }
        return $options;
    }

    public static function ordered_keys(){
        return array_keys(self::options());
    }

    public static function normalize($value){
        $key = self::canonical_key($value);
        return array_key_exists($key, self::options()) ? $key : '';
    }

    public static function label($value){
        $key = self::normalize($value);
        $options = self::options();
        return $options[$key] ?? '';
    }

    public static function color($value){
        $key = self::normalize($value);
        $records = self::keyed_records();
        return $records[$key]['color'] ?? '#1d2327';
    }

    public static function text_color($value){
        $hex = ltrim(self::color($value), '#');
        if(strlen($hex) === 3){
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if(strlen($hex) !== 6){
            return '#ffffff';
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));
        $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

        return $brightness > 160 ? '#111111' : '#ffffff';
    }

    public static function get($slug){
        $slug = self::normalize($slug);
        $records = self::keyed_records();
        return $records[$slug] ?? null;
    }

    public static function save_records($records){
        $sanitized = self::sanitize_records($records);

        if(empty($sanitized) || !self::looks_like_difficulty_records($sanitized)){
            $sanitized = self::default_records();
        }

        update_option(self::OPTION_NAME, $sanitized);
    }

    public static function usage_count($slug){
        global $wpdb;

        $slug = self::normalize($slug);
        if($slug === ''){
            return 0;
        }

        $table = $wpdb->prefix . 'spcu_areas';
        $exists = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if(!$exists){
            return 0;
        }

        $rows = $wpdb->get_results("SELECT difficulties_json FROM {$table} WHERE difficulties_json IS NOT NULL AND difficulties_json != ''");
        $count = 0;

        foreach($rows as $row){
            $decoded = json_decode((string) $row->difficulties_json, true);
            if(is_array($decoded) && array_key_exists($slug, $decoded)){
                $count++;
            }
        }

        return $count;
    }

    public static function rename_references($old_slug, $new_slug){
        global $wpdb;

        $old_slug = self::normalize($old_slug);
        $new_slug = self::normalize($new_slug);

        if($old_slug === '' || $new_slug === '' || $old_slug === $new_slug){
            return;
        }

        $table = $wpdb->prefix . 'spcu_areas';
        $exists = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if(!$exists){
            return;
        }

        $rows = $wpdb->get_results("SELECT id, difficulties_json FROM {$table} WHERE difficulties_json IS NOT NULL AND difficulties_json != ''");
        foreach($rows as $row){
            $decoded = json_decode((string) $row->difficulties_json, true);
            if(!is_array($decoded) || !array_key_exists($old_slug, $decoded)){
                continue;
            }

            $decoded[$new_slug] = $decoded[$old_slug];
            unset($decoded[$old_slug]);
            $wpdb->update($table, ['difficulties_json' => wp_json_encode($decoded)], ['id' => intval($row->id)]);
        }
    }

    private static function sanitize_records($records){
        $sanitized = [];
        $seen = [];

        foreach((array) $records as $record){
            $record = self::sanitize_record($record);
            if(!$record || isset($seen[$record['slug']])){
                continue;
            }

            $seen[$record['slug']] = true;
            $sanitized[] = $record;
        }

        return $sanitized;
    }

    private static function sanitize_record($record){
        if(!is_array($record)){
            return null;
        }

        $name = sanitize_text_field($record['name'] ?? '');
        $slug_source = $record['slug'] ?? $name;
        $slug = self::canonical_key(sanitize_title($slug_source));
        $description = sanitize_textarea_field($record['description'] ?? '');
        $color = self::sanitize_color($record['color'] ?? '#111111');

        if($name === '' || $slug === ''){
            return null;
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'color' => $color,
        ];
    }

    private static function sanitize_color($color){
        $color = sanitize_text_field((string) $color);
        return preg_match('/^#([0-9a-fA-F]{6})$/', $color) ? strtolower($color) : '#111111';
    }

    private static function canonical_key($value){
        $key = sanitize_key((string) $value);
        $legacy = self::legacy_map();
        return $legacy[$key] ?? $key;
    }

    private static function looks_like_difficulty_records($records){
        $slugs = array_column((array) $records, 'slug');
        return in_array('beginner', $slugs, true) || in_array('intermediate', $slugs, true) || in_array('advanced', $slugs, true);
    }
}

SPCU_Difficulties::bootstrap();
