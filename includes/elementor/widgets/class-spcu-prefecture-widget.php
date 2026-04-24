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
				'label' => esc_html__( 'Select Prefectures', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple' => true,
				'default' => [],
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
			'prefecture_title',
			[
				'label' => esc_html__( 'Prefecture Title', 'ski-price-calculator' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Leave blank to use selected prefecture name', 'ski-price-calculator' ),
				'default' => '',
				'condition' => [
					'show_prefecture_header' => 'yes',
				],
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
		$selected_prefectures = isset( $settings['prefecture_id'] ) ? $settings['prefecture_id'] : [];

		if ( ! is_array( $selected_prefectures ) ) {
			$selected_prefectures = [ $selected_prefectures ];
		}

		$selected_prefecture_ids = array_values( array_filter( array_map( 'intval', $selected_prefectures ) ) );

		if ( empty( $selected_prefecture_ids ) ) {
			echo '<p>Please select at least one prefecture.</p>';
			return;
		}

		global $wpdb;
		$custom_title = ! empty( $settings['prefecture_title'] ) ? sanitize_text_field( $settings['prefecture_title'] ) : '';
		$is_single_prefecture = count( $selected_prefecture_ids ) === 1;
		$placeholders = implode( ',', array_fill( 0, count( $selected_prefecture_ids ), '%d' ) );

		$prefecture_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, name, name_ja FROM {$wpdb->prefix}spcu_prefectures WHERE id IN ($placeholders)",
				$selected_prefecture_ids
			)
		);

		$prefecture_map = [];
		foreach ( $prefecture_rows as $row ) {
			$prefecture_map[ intval( $row->id ) ] = $row;
		}

		$areas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, p.name AS prefecture_name FROM {$wpdb->prefix}spcu_areas a LEFT JOIN {$wpdb->prefix}spcu_prefectures p ON p.id = a.prefecture_id WHERE a.prefecture_id IN ($placeholders) ORDER BY a.name ASC",
				$selected_prefecture_ids
			)
		);

		if ( empty( $areas ) ) {
			echo '<p>No areas found for selected prefectures.</p>';
			return;
		}

		$single_prefecture = null;
		if ( $is_single_prefecture ) {
			$single_id = $selected_prefecture_ids[0];
			$single_prefecture = isset( $prefecture_map[ $single_id ] ) ? $prefecture_map[ $single_id ] : null;
		}

		$prefecture_title = $custom_title;
		if ( $prefecture_title === '' ) {
			if ( $is_single_prefecture && ! empty( $single_prefecture ) ) {
				$prefecture_title = $single_prefecture->name;
			} else {
				$prefecture_title = esc_html__( 'Selected Prefectures', 'ski-price-calculator' );
			}
		}

		?>
		<?php if ( $settings['show_prefecture_header'] === 'yes' ) : ?>
			<div class="spcu-prefecture-header">
				<div class="spcu-prefecture-header__content">
					<?php if ( $is_single_prefecture && ! empty( $single_prefecture ) && ! empty( $single_prefecture->name_ja ) ) : ?>
						<span class="spcu-prefecture-label"><?php echo esc_html( $single_prefecture->name_ja ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $prefecture_title ) ) : ?>
						<h2 class="spcu-prefecture-title"><?php echo esc_html( $prefecture_title ); ?></h2>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="spcu-prefecture-areas-wrapper" data-spcu-carousel-wrapper>
			<div class="spcu-areas-carousel-container">
				<?php if ( count( $areas ) > 3 ) : ?>
					<button class="spcu-carousel-button spcu-carousel-prev" aria-label="Previous areas">‹</button>
				<?php endif; ?>
				<div class="spcu-areas-horizontal" data-carousel-container>
					<?php foreach ( $areas as $area ) :
						$img_url = wp_get_attachment_image_url( $area->featured_image, 'medium' );
						if ( ! $img_url ) {
							$img_url = 'https://via.placeholder.com/400x300?text=' . urlencode( $area->name );
						}

						$tags = [];
						if ( ! empty( $area->area_tags ) ) {
							$tags = json_decode( $area->area_tags, true ) ?: [];
						}
					?>
						<div class="spcu-area-card">
							<?php if ( ! empty( $area->featured_badge ) ) : ?>
								<div class="spcu-area-card__badge"><?php echo esc_html( $area->featured_badge ); ?></div>
							<?php endif; ?>

							<div class="spcu-area-card__image">
								<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $area->name ); ?>">
							</div>

							<div class="spcu-area-card__content">
								<h4 class="spcu-area-card__title"><?php echo esc_html( $area->name ); ?></h4>

								<?php if ( $settings['show_location_info'] === 'yes' && ! empty( $area->distance ) ) : ?>
									<p class="spcu-area-card__location">
										<?php if ( ! empty( $area->prefecture_name ) ) : ?>
											<?php echo esc_html( $area->prefecture_name ); ?> ·
										<?php endif; ?>
										<?php echo esc_html( $area->distance ); ?>
									</p>
								<?php endif; ?>

								<?php if ( $settings['show_description'] === 'yes' && ! empty( $area->short_description ) ) : ?>
									<p class="spcu-area-card__short-description"><?php echo esc_html( $area->short_description ); ?></p>
								<?php elseif ( $settings['show_description'] === 'yes' && ! empty( $area->description ) ) : ?>
									<p class="spcu-area-card__short-description"><?php echo wp_kses_post( wp_trim_words( $area->description, 20 ) ); ?></p>
								<?php endif; ?>

								<?php if ( $settings['show_tags'] === 'yes' && ! empty( $tags ) ) : ?>
									<div class="spcu-area-card__tags">
										<?php foreach ( $tags as $tag ) : ?>
											<span class="spcu-area-card__tag"><?php echo esc_html( $tag ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<?php if ( $settings['show_button'] === 'yes' ) : 
								// Always build area URL using area slug or name
								$area_slug = ! empty( $area->slug ) ? $area->slug : sanitize_title( $area->name );
								$area_link = home_url( '/area/' . $area_slug . '/' );
								?>
									<a href="<?php echo esc_url( $area_link ); ?>" class="spcu-area-card__button">
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
			const wrappers = document.querySelectorAll('[data-spcu-carousel-wrapper]');
			wrappers.forEach((wrapper) => {
				const container = wrapper.querySelector('[data-carousel-container]');
				const prevBtn = wrapper.querySelector('.spcu-carousel-prev');
				const nextBtn = wrapper.querySelector('.spcu-carousel-next');

				if (!container) return;
				if (!prevBtn || !nextBtn) return;

				const card = container.querySelector('.spcu-area-card');
				if (!card) return;

				const width = card.offsetWidth;
				const gap = parseInt(window.getComputedStyle(container).gap || '0', 10);
				const scrollAmount = width + gap;

				function updateButtonStates() {
					prevBtn.disabled = container.scrollLeft <= 0;
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
			});
		})();
		</script>
		<?php
	}
}

