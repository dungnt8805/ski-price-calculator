<?php
if (!defined('ABSPATH')) exit;

class SPCU_Grades {

    public static function options(){
        return [
            'standard'  => 'Standard',
            'premium'   => 'Premium',
            'exclusive' => 'Exclusive',
        ];
    }

    public static function normalize($value){
        $key = sanitize_key((string)$value);
        return array_key_exists($key, self::options()) ? $key : '';
    }

    public static function label($value){
        $key = sanitize_key((string)$value);
        $options = self::options();
        return $options[$key] ?? '';
    }
}
