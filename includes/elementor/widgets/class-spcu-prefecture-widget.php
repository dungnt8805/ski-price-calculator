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
			'show_prefecture_header',
			[
				'label' => esc_html__( 'Show Prefecture Header', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'ski-price-calculator' ),
				'label_off' => esc_html__( 'Hide', 'ski-price-calculator' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_description',
			[
				'label' => esc_html__( 'Show Area Description', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'ski-price-calculator' ),
				'label_off' => esc_html__( 'Hide', 'ski-price-calculator' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_location_info',
			[
				'label' => esc_html__( 'Show Location Info', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'ski-price-calculator' ),
				'label_off' => esc_html__( 'Hide', 'ski-price-calculator' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_tags',
			[
				'label' => esc_html__( 'Show Area Tags', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'ski-price-calculator' ),
				'label_off' => esc_html__( 'Hide', 'ski-price-calculator' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_button',
			[
				'label' => esc_html__( 'Show Button', 'ski-price-calculator' ),
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
				'placeholder' => 'https://example.com/area',
				'condition' => [
					'show_button' => 'yes',
				],
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
						'min' => 100,
						'max' => 500,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 320,
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
		
		// Get prefecture data
		$prefecture = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}spcu_prefectures WHERE id = %d",
			$prefecture_id
		) );

		// Get areas for this prefecture
		$areas = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}spcu_areas WHERE prefecture_id = %d ORDER BY name ASC",
			$prefecture_id
		) );

		if ( empty( $areas ) ) {
			echo '<p>No areas found for this prefecture.</p>';
			return;
		}

		// Prefecture header
		if ( $settings['show_prefecture_header'] === 'yes' && ! empty( $prefecture ) ) {
			?>
			<div class="spcu-prefecture-header">
				<div class="spcu-prefecture-header__content">
					<?php if ( ! empty( $prefecture->name_ja ) ) : ?>
						<span class="spcu-prefecture-label"><?php echo esc_html( $prefecture->name_ja ); ?></span>
					<?php endif; ?>
					<h2 class="spcu-prefecture-title"><?php echo esc_html( $prefecture->name ); ?> Prefecture</h2>
				</div>
			</div>
			<?php
		}

		?>
		<div class="spcu-prefecture-areas-wrapper">
			<div class="spcu-areas-carousel-container">
				<?php if ( count( $areas ) > 3 ) : ?>
					<button class="spcu-carousel-button spcu-carousel-prev" aria-label="Previous areas">‹</button>
				<?php endif; ?>
				<div class="spcu-areas-horizontal" data-carousel-container>
					<?php foreach ( $areas as $area ) : 
					$img_url = wp_get_attachment_image_url( $area->featured_image, 'medium' );
                    // Fallback to placeholder if no image
                    if ( ! $img_url ) {
                        $img_url = 'https://via.placeholder.com/400x300?text=' . urlencode($area->name);
                    }
					
					// Parse area tags
					$tags = [];
					if ( ! empty( $area->area_tags ) ) {
						$tags = json_decode( $area->area_tags, true ) ?: [];
					}
				?>
					<div class="spcu-area-card">
						<?php if ( ! empty( $area->featured_badge ) ) : ?>
							<div class="spcu-area-card__badge">
								<?php echo esc_html( $area->featured_badge ); ?>
							</div>
						<?php endif; ?>
						
						<div class="spcu-area-card__image">
							<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $area->name ); ?>">
						</div>
						
						<div class="spcu-area-card__content">
							<!-- Row 1: Name -->
							<h4 class="spcu-area-card__title"><?php echo esc_html( $area->name ); ?></h4>
							
							<!-- Row 2: Prefecture - Distance with Tokyo -->
							<?php if ( $settings['show_location_info'] === 'yes' && ! empty( $area->distance ) ) : ?>
								<p class="spcu-area-card__location">
									<?php if ( ! empty( $prefecture->name ) ) : ?>
										<?php echo esc_html( $prefecture->name ); ?> · 
									<?php endif; ?>
									<?php echo esc_html( $area->distance ); ?>
								</p>
							<?php endif; ?>
							
							<!-- Row 3: Short Description -->
							<?php if ( $settings['show_description'] === 'yes' && ! empty( $area->short_description ) ) : ?>
								<p class="spcu-area-card__short-description">
									<?php echo esc_html( $area->short_description ); ?>
								</p>
							<?php elseif ( $settings['show_description'] === 'yes' && ! empty( $area->description ) ) : ?>
								<p class="spcu-area-card__short-description">
									<?php echo wp_kses_post( wp_trim_words( $area->description, 20 ) ); ?>
								</p>
							<?php endif; ?>
							
							<!-- Row 4: Tags -->
							<?php if ( $settings['show_tags'] === 'yes' && ! empty( $tags ) ) : ?>
								<div class="spcu-area-card__tags">
									<?php foreach ( $tags as $tag ) : ?>
										<span class="spcu-area-card__tag"><?php echo esc_html( $tag ); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							
							<!-- Row 5: Quote Button -->
							<?php if ( $settings['show_button'] === 'yes' ) : ?>
								<a href="<?php echo esc_url( $settings['button_link'] ?: '#' ); ?>" class="spcu-area-card__button">
									<?php echo esc_html( $settings['button_text'] ?: 'View & Quote →' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
				</div>
				<?php if ( count( $areas ) > 3 ) : ?>
					<button class="spcu-carousel-button spcu-carousel-next" aria-label="Next areas">›</button>
				<?php endif; ?>
			</div>
		</div>
		
		<script>
		(function() {
			const container = document.querySelector('[data-carousel-container]');
			const prevBtn = document.querySelector('.spcu-carousel-prev');
			const nextBtn = document.querySelector('.spcu-carousel-next');
			
			if (!container || !prevBtn || !nextBtn) return;
			
			const cardWidth = container.querySelector('.spcu-area-card');
			if (!cardWidth) return;
			
			// Get card width and gap
			const style = window.getComputedStyle(cardWidth);
			const width = cardWidth.offsetWidth;
			const gap = parseInt(window.getComputedStyle(container).gap);
			const scrollAmount = width + gap;
			
			function updateButtonStates() {
				prevBtn.disabled = container.scrollLeft === 0;
				nextBtn.disabled = container.scrollLeft >= container.scrollWidth - container.clientWidth - 10;
			}
			
			prevBtn.addEventListener('click', () => {
				container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
				setTimeout(updateButtonStates, 300);
			});
			
			nextBtn.addEventListener('click', () => {
				container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
				setTimeout(updateButtonStates, 300);
			});
			
			container.addEventListener('scroll', updateButtonStates);
			updateButtonStates();
		})();
		</script>
		<?php
	}
}

