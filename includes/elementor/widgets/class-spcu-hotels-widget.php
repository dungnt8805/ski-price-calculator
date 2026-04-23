<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SPCU_Hotels_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'spcu_hotels';
	}

	public function get_title() {
		return esc_html__( 'Hotels List', 'ski-price-calculator' );
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	public function get_keywords() {
		return [ 'hotel', 'accommodation', 'ski', 'area', 'prefecture' ];
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
			'prefecture_id',
			[
				'label' => esc_html__( 'Filter by Prefecture', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'label_block' => true,
				'options' => $this->get_prefectures(),
				'default' => '',
			]
		);

		$this->add_control(
			'area_id',
			[
				'label' => esc_html__( 'Filter by Area', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'label_block' => true,
				'options' => $this->get_areas(),
				'default' => '',
			]
		);

		$this->add_control(
			'hotel_grade',
			[
				'label' => esc_html__( 'Filter by Grade', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => $this->get_grades(),
				'default' => '',
			]
		);

		$this->add_control(
			'hotel_id',
			[
				'label' => esc_html__( 'Filter by Hotel Name', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'label_block' => true,
				'options' => $this->get_hotels(),
				'default' => '',
			]
		);

		$this->add_control(
			'limit',
			[
				'label' => esc_html__( 'Number of Hotels', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
				'max' => 100,
				'step' => 1,
				'default' => 12,
			]
		);

		$this->add_control(
			'show_button',
			[
				'label' => esc_html__( 'Show Quote Button', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'ski-price-calculator' ),
				'label_off' => esc_html__( 'Hide', 'ski-price-calculator' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'button_text',
			[
				'label' => esc_html__( 'Button Text', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => 'View & Quote →',
				'condition' => [
					'show_button' => 'yes',
				],
			]
		);

		$this->add_control(
			'button_link',
			[
				'label' => esc_html__( 'Button Link', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'https://example.com/hotel',
				'condition' => [
					'show_button' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function get_prefectures() {
		global $wpdb;
		$options = [ '' => esc_html__( 'All Prefectures', 'ski-price-calculator' ) ];
		$rows = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}spcu_prefectures ORDER BY name ASC" );
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$options[ (string) $row->id ] = $row->name;
			}
		}
		return $options;
	}

	protected function get_areas() {
		global $wpdb;
		$options = [ '' => esc_html__( 'All Areas', 'ski-price-calculator' ) ];
		$rows = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}spcu_areas ORDER BY name ASC" );
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$options[ (string) $row->id ] = $row->name;
			}
		}
		return $options;
	}

	protected function get_grades() {
		return [
			'' => esc_html__( 'All Grades', 'ski-price-calculator' ),
			'std' => esc_html__( 'Standard', 'ski-price-calculator' ),
			'prem' => esc_html__( 'Premium', 'ski-price-calculator' ),
			'excl' => esc_html__( 'Exclusive', 'ski-price-calculator' ),
		];
	}

	protected function get_hotels() {
		global $wpdb;
		$options = [ '' => esc_html__( 'All Hotels', 'ski-price-calculator' ) ];
		$rows = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}spcu_hotels ORDER BY name ASC" );
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

		$where = [ '1=1' ];
		$params = [];

		$prefecture_id = isset( $settings['prefecture_id'] ) ? intval( $settings['prefecture_id'] ) : 0;
		$area_id = isset( $settings['area_id'] ) ? intval( $settings['area_id'] ) : 0;
		$hotel_id = isset( $settings['hotel_id'] ) ? intval( $settings['hotel_id'] ) : 0;
		$hotel_grade = isset( $settings['hotel_grade'] ) ? sanitize_text_field( $settings['hotel_grade'] ) : '';
		$limit = isset( $settings['limit'] ) ? max( 1, intval( $settings['limit'] ) ) : 12;

		if ( $prefecture_id > 0 ) {
			$where[] = 'a.prefecture_id = %d';
			$params[] = $prefecture_id;
		}

		if ( $area_id > 0 ) {
			$where[] = 'h.area_id = %d';
			$params[] = $area_id;
		}

		if ( $hotel_id > 0 ) {
			$where[] = 'h.id = %d';
			$params[] = $hotel_id;
		}

		if ( ! empty( $hotel_grade ) ) {
			$where[] = 'h.grade = %s';
			$params[] = $hotel_grade;
		}

		$sql = "
			SELECT
				h.*,
				a.name AS area_name,
				p.name AS prefecture_name
			FROM {$wpdb->prefix}spcu_hotels h
			LEFT JOIN {$wpdb->prefix}spcu_areas a ON a.id = h.area_id
			LEFT JOIN {$wpdb->prefix}spcu_prefectures p ON p.id = a.prefecture_id
			WHERE " . implode( ' AND ', $where ) . "
			ORDER BY h.name ASC
			LIMIT %d
		";

		$params[] = $limit;
		$prepared_sql = $wpdb->prepare( $sql, $params );
		$hotels = $wpdb->get_results( $prepared_sql );

		if ( empty( $hotels ) ) {
			echo '<p>No hotels found for the selected filters.</p>';
			return;
		}

		?>
		<div class="spcu-hotels-widget">
			<div class="spcu-hotels-grid">
				<?php foreach ( $hotels as $hotel ) :
					$img_url = wp_get_attachment_image_url( $hotel->featured_image, 'medium' );
					if ( ! $img_url ) {
						$img_url = 'https://via.placeholder.com/600x360?text=' . urlencode( $hotel->name );
					}

					$grade_label = 'Standard';
					$grade_class = 'spcu-hotel-grade--std';
					if ( $hotel->grade === 'prem' ) {
						$grade_label = 'Premium';
						$grade_class = 'spcu-hotel-grade--prem';
					} elseif ( $hotel->grade === 'excl' ) {
						$grade_label = 'Exclusive';
						$grade_class = 'spcu-hotel-grade--excl';
					}
				?>
					<div class="spcu-hotel-card">
						<div class="spcu-hotel-card__image">
							<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $hotel->name ); ?>">
							<span class="spcu-hotel-grade <?php echo esc_attr( $grade_class ); ?>"><?php echo esc_html( $grade_label ); ?></span>
						</div>
						<div class="spcu-hotel-card__content">
							<h3 class="spcu-hotel-card__title"><?php echo esc_html( $hotel->name ); ?></h3>
							<p class="spcu-hotel-card__meta">
								<?php echo esc_html( $hotel->prefecture_name ?: 'Unknown Prefecture' ); ?>
								<?php if ( ! empty( $hotel->area_name ) ) : ?>
									· <?php echo esc_html( $hotel->area_name ); ?>
								<?php endif; ?>
							</p>
							<?php if ( ! empty( $hotel->short_description ) ) : ?>
								<p class="spcu-hotel-card__description"><?php echo esc_html( $hotel->short_description ); ?></p>
							<?php elseif ( ! empty( $hotel->description ) ) : ?>
								<p class="spcu-hotel-card__description"><?php echo esc_html( wp_strip_all_tags( wp_trim_words( $hotel->description, 22 ) ) ); ?></p>
							<?php endif; ?>

							<?php if ( $settings['show_button'] === 'yes' ) : ?>
								<a href="<?php echo esc_url( $settings['button_link'] ?: '#' ); ?>" class="spcu-hotel-card__button">
									<?php echo esc_html( $settings['button_text'] ?: 'View & Quote →' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
