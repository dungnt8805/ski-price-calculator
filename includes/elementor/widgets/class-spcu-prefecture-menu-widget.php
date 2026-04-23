<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SPCU_Prefecture_Menu_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'spcu_prefecture_menu';
	}

	public function get_title() {
		return esc_html__( 'Prefecture Menu', 'ski-price-calculator' );
	}

	public function get_icon() {
		return 'eicon-nav-menu';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	public function get_keywords() {
		return [ 'prefecture', 'menu', 'area', 'footer', 'ski' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'ski-price-calculator' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'prefecture_ids',
			[
				'label' => esc_html__( 'Select Prefectures', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple' => true,
				'options' => $this->get_prefectures(),
				'default' => [],
			]
		);

		$this->add_control(
			'show_name_ja',
			[
				'label' => esc_html__( 'Show Japanese Name', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'ski-price-calculator' ),
				'label_off' => esc_html__( 'Hide', 'ski-price-calculator' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'area_base_url',
			[
				'label' => esc_html__( 'Area Base URL', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::URL,
				'dynamic' => [ 'active' => true ],
				'placeholder' => esc_html__( 'https://example.com/resorts', 'ski-price-calculator' ),
				'description' => esc_html__( 'If set, each area link will point to this URL with ?area_id={id}.', 'ski-price-calculator' ),
			]
		);

		$this->end_controls_section();
	}

	protected function get_prefectures() {
		global $wpdb;
		$options = [];
		$rows = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}spcu_prefectures ORDER BY name ASC" );
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$options[ (string) $row->id ] = $row->name;
			}
		}
		return $options;
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		global $wpdb;

		$selected_ids = isset( $settings['prefecture_ids'] ) ? $settings['prefecture_ids'] : [];
		if ( ! is_array( $selected_ids ) ) {
			$selected_ids = [ $selected_ids ];
		}
		$selected_ids = array_values( array_filter( array_map( 'intval', $selected_ids ) ) );

		$pref_sql = "SELECT id, name, name_ja FROM {$wpdb->prefix}spcu_prefectures";
		$pref_params = [];
		if ( ! empty( $selected_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $selected_ids ), '%d' ) );
			$pref_sql .= " WHERE id IN ($placeholders)";
			$pref_params = $selected_ids;
		}
		$pref_sql .= ' ORDER BY name ASC';

		$prefectures = empty( $pref_params )
			? $wpdb->get_results( $pref_sql )
			: $wpdb->get_results( $wpdb->prepare( $pref_sql, $pref_params ) );

		if ( empty( $prefectures ) ) {
			echo '<p>No prefectures found.</p>';
			return;
		}

		$prefecture_ids = array_map( static function( $pref ) {
			return intval( $pref->id );
		}, $prefectures );

		$areas_by_prefecture = [];
		if ( ! empty( $prefecture_ids ) ) {
			$area_placeholders = implode( ',', array_fill( 0, count( $prefecture_ids ), '%d' ) );
			$areas_sql = "SELECT id, prefecture_id, name FROM {$wpdb->prefix}spcu_areas WHERE prefecture_id IN ($area_placeholders) ORDER BY name ASC";
			$areas = $wpdb->get_results( $wpdb->prepare( $areas_sql, $prefecture_ids ) );
			if ( ! empty( $areas ) ) {
				foreach ( $areas as $area ) {
					$key = intval( $area->prefecture_id );
					if ( ! isset( $areas_by_prefecture[ $key ] ) ) {
						$areas_by_prefecture[ $key ] = [];
					}
					$areas_by_prefecture[ $key ][] = $area;
				}
			}
		}

		$base_url = '';
		if ( isset( $settings['area_base_url']['url'] ) ) {
			$base_url = esc_url_raw( $settings['area_base_url']['url'] );
		}
		$target = '';
		$rel = '';
		if ( ! empty( $settings['area_base_url']['is_external'] ) ) {
			$target = ' target="_blank"';
		}
		if ( ! empty( $settings['area_base_url']['nofollow'] ) ) {
			$rel = ' rel="nofollow"';
		}

		echo '<div class="spcu-pref-menu">';
		echo '<div class="spcu-pref-menu__grid">';

		foreach ( $prefectures as $prefecture ) {
			$pref_id = intval( $prefecture->id );
			$areas = isset( $areas_by_prefecture[ $pref_id ] ) ? $areas_by_prefecture[ $pref_id ] : [];
			if ( empty( $areas ) ) {
				continue;
			}

			echo '<div class="spcu-pref-menu__col">';
			echo '<h4 class="spcu-pref-menu__title">';
			echo esc_html( $prefecture->name );
			if ( $settings['show_name_ja'] === 'yes' && ! empty( $prefecture->name_ja ) ) {
				echo ' <span class="spcu-pref-menu__title-ja">' . esc_html( $prefecture->name_ja ) . '</span>';
			}
			echo '</h4>';

			foreach ( $areas as $area ) {
				$link = '#';
				if ( ! empty( $base_url ) ) {
					$link = add_query_arg( 'area_id', intval( $area->id ), $base_url );
				}
				echo '<a class="spcu-pref-menu__link" href="' . esc_url( $link ) . '"' . $target . $rel . '>' . esc_html( $area->name ) . '</a>';
			}

			echo '</div>';
		}

		echo '</div>';
		echo '</div>';
	}
}
