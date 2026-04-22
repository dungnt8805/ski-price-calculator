<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SPCU_Prefecture_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'spcu_prefecture';
	}

	public function get_title() {
		return esc_html__( 'Prefecture Areas', 'ski-price-calculator' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	public function get_keywords() {
		return [ 'prefecture', 'area', 'ski' ];
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
				'label' => esc_html__( 'Select Prefecture', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'label_block' => true,
				'options' => $this->get_prefectures(),
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
				'default' => 'no',
			]
		);

		$this->end_controls_section();

        // Style Section
        $this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Style', 'ski-price-calculator' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

        $this->add_control(
			'card_width',
			[
				'label' => esc_html__( 'Card Width', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 200,
						'max' => 800,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 450,
				],
				'selectors' => [
					'{{WRAPPER}} .spcu-area-card' => 'width: {{SIZE}}{{UNIT}}; min-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->end_controls_section();
	}

	protected function get_prefectures() {
		global $wpdb;
		$table = $wpdb->prefix . 'spcu_prefectures';
		$results = $wpdb->get_results( "SELECT id, name FROM $table ORDER BY name ASC" );
		$options = [];
		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$options[ $row->id ] = $row->name;
			}
		}
		return $options;
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$prefecture_id = intval( $settings['prefecture_id'] );

		if ( ! $prefecture_id ) {
			echo '<p>Please select a prefecture.</p>';
			return;
		}

		global $wpdb;
        $prefecture = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}spcu_prefectures WHERE id = %d", $prefecture_id ) );
		$areas = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}spcu_areas WHERE prefecture_id = %d ORDER BY name ASC",
			$prefecture_id
		) );

		if ( empty( $areas ) || ! $prefecture ) {
			echo '<p>No data found.</p>';
			return;
		}

		?>
		<div class="spcu-prefecture-section">
            <div class="spcu-prefecture-header">
                <?php if ( ! empty( $prefecture->name_ja ) ) : ?>
                    <span class="spcu-pref-badge"><?php echo esc_html( $prefecture->name_ja ); ?></span>
                <?php endif; ?>
                <h2 class="spcu-pref-title"><?php echo esc_html( $prefecture->name ); ?></h2>
                <div class="spcu-pref-line"></div>
            </div>

			<div class="spcu-areas-horizontal">
				<?php foreach ( $areas as $area ) : 
					$img_url = wp_get_attachment_image_url( $area->featured_image, 'large' );
                    if ( ! $img_url ) {
                        $img_url = 'https://via.placeholder.com/600x400?text=' . urlencode($area->name);
                    }
				?>
					<div class="spcu-area-card">
						<div class="spcu-area-card__image-wrap">
							<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $area->name ); ?>">
                            <?php if ( ! empty( $area->type ) ) : ?>
                                <span class="spcu-area-card__tag"><?php echo esc_html( strtoupper( $area->type ) ); ?></span>
                            <?php endif; ?>
						</div>
						<div class="spcu-area-card__body">
                            <h3 class="spcu-area-card__title"><?php echo esc_html( $area->name ); ?></h3>
                            
                            <div class="spcu-area-card__meta">
                                <?php echo esc_html( $prefecture->name ); ?> 
                                <?php if ( ! empty( $area->distance ) ) : ?>
                                    &nbsp;&middot;&nbsp; <?php echo esc_html( $area->distance ); ?>
                                <?php endif; ?>
                            </div>

                            <div class="spcu-area-card__desc">
                                <?php echo wp_kses_post( wp_trim_words( $area->short_description, 25, '...' ) ); ?>
                            </div>

                            <div class="spcu-area-card__pills">
                                <?php if ( ! empty( $area->total_resorts ) ) : ?>
                                    <span class="spcu-pill"><?php echo esc_html( $area->total_resorts ); ?> Resorts</span>
                                <?php endif; ?>
                                <?php if ( ! empty( $area->total_runs ) ) : ?>
                                    <span class="spcu-pill"><?php echo esc_html( $area->total_runs ); ?> Runs</span>
                                <?php endif; ?>
                                <?php if ( ! empty( $area->season ) ) : ?>
                                    <span class="spcu-pill"><?php echo esc_html( $area->season ); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="spcu-area-card__footer">
                                <a href="#" class="spcu-area-card__btn">View & Quote &rarr;</a>
                            </div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
