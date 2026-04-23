<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SPCU_Elementor {

	private static $instance = null;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
	}

	public function register_widgets( $widgets_manager ) {
		require_once SPCU_PATH . 'includes/elementor/widgets/class-spcu-prefecture-widget.php';
		$widgets_manager->register( new \SPCU_Prefecture_Widget() );

		require_once SPCU_PATH . 'includes/elementor/widgets/class-spcu-prefecture-menu-widget.php';
		$widgets_manager->register( new \SPCU_Prefecture_Menu_Widget() );

		require_once SPCU_PATH . 'includes/elementor/widgets/class-spcu-hotels-widget.php';
		$widgets_manager->register( new \SPCU_Hotels_Widget() );
	}
}

SPCU_Elementor::get_instance();
