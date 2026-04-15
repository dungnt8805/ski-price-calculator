<?php
class SPCU_Activator {
    public static function activate() {
        require_once SPCU_PATH.'includes/class-spcu-database.php';
        SPCU_Database::create_tables();
    }
}