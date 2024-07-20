<?php
/**
 * Public functions
 */
class SimpleSitemap_Public {

    /**
     * Constructor
     */
    function __construct() {
        add_action( 'template_redirect', array( $this, 'template_redirect' ) );
    }


    /**
	 * Register shortcode, and set up the_filter for an auto-add page if one is set
	 *
	 * @return void
	 */
	public function template_redirect() {
		// Register Shortcode.
		add_shortcode( SimpleSitemap::$shortcode, array( $this, 'handle_shortcode' ) );
		// Only filter content if a page was selected.
		if ( ! empty( SimpleSitemap::$opts['sitemap_page'] ) && is_page( SimpleSitemap::$opts['sitemap_page'] ) ) {
			add_filter( 'the_content', array( $this, 'sitemap_filter_content' ) );
		}
	}

	/**
	 * Insert the sitemap via a hook instead of a shortcode
	 *
	 * @param string $content  To prefix or append.
	 * @return string
	 */
	public function sitemap_filter_content( $content = '' ) {
		ob_start();
		$post_types = array(
			'page'    => 'Pages',
			'post'    => 'Posts',
			'product' => 'Products',
		);
		foreach ( $post_types as $type => $heading ) {
			if ( ! post_type_exists( $type ) ) {
				unset( $post_types[ $type ] );
			}
		}
		echo '<div class="simple-sitemap">' . "\n";
		foreach ( $post_types as $type => $heading ) {
			do_action( 'simple_sitemap_before_group_' . $type );
			echo '<div class="group-type">' . "\n";
			echo '<h3>' . esc_html( $heading ) . '</h3>' . "\n";
			$this->handle_shortcode(
				array(
					'view' => $type,
					'echo' => true,
				)
			);
			echo '</div><!-- group-type -->' . "\n";
			do_action( 'simple_sitemap_after_group_' . $type );
		}
		echo '</div><!-- simple-sitemap -->' . "\n";
		$output  = ob_get_clean();
		$content = ( 'before' === SimpleSitemap::$opts['sel_sitemap_position'] ) ? $output . $content : $content . $output;
		return $content;
	}



	/**
	 * Shortcode Handler
	 *
	 * Defaults to showing Page and Post types
	 * You can specify a particular type by [shortcode view="product"] or other post_type
	 *
	 * @param boolean $atts  Array of attributes.
	 * @return void|string
	 */
	public function handle_shortcode( $atts = false ) {
		$atts = shortcode_atts(
			array(
				'view' => 'all',
				'echo' => false,
			),
			$atts
		);
		ob_start();
		if ( post_type_exists( $atts['view'] ) ) {
			if ( is_post_type_hierarchical( $atts['view'] ) ) {
				$this->sitemap_show_hierarchical( $atts['view'] );
			} else {
				$this->sitemap_show_non_hierarchical( $atts['view'] );
			}
		} else {
			echo wp_kses_post( $this->sitemap_filter_content() );
		}
		$output = ob_get_clean();
		if ( $atts['echo'] ) {
			echo wp_kses_post( $output );
		}
		return $output;
	}


	/**
	 * Displays the sitemap for hierarchical post_types, like pages
	 *
	 * @param string  $post_type  WordPress post type to show.
	 * @param boolean $classes    CSS classes to include.
	 * @return void
	 */
	private function sitemap_show_hierarchical( $post_type = 'page', $classes = false ) {
		if ( is_string( $classes ) ) {
			$classes = array( $classes );
		}
		if ( ! is_array( $classes ) ) {
			$classes = array( 'sitemap-' . $post_type );
		}

		echo '<ul class="simplesitemap ' . esc_attr( implode( ' ', $classes ) ) . '">' . "\n";
		wp_list_pages(
			array(
				'post_type'    => $post_type,
				'sort_column'  => 'menu_order',
				'sort_order'   => 'DESC',
				'echo'         => true,
				'title_li'     => false,
				'exclude_tree' => SimpleSitemap::$opts['exclude_posts'],
			)
		);
		echo '</ul>';
	}

	/**
	 * Displays the sitemap for hierarchical post_types, like pages
	 *
	 * @param string  $post_type  WordPress post type to show.
	 * @param boolean $classes    CSS classes to include.
	 * @return void|array
	 */
	private function sitemap_show_non_hierarchical( $post_type = 'page', $classes = false ) {
		if ( is_string( $classes ) ) {
			$classes = array( $classes );
		}
		if ( ! is_array( $classes ) ) {
			$classes = array( 'sitemap-' . $post_type );
		}

		$query = array(
			'post_type'    => $post_type,
			'numberposts'  => -1,
			'order'        => ( 'post' === $post_type ) ? 'date' : 'title',
			'orderby'      => 'DESC',
			'post__not_in' => SimpleSitemap::$opts['exclude_posts'],
		);
		if ( SimpleSitemap::$opts['exclude_terms'] ) {
			$query['tax_query'][] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => SimpleSitemap::$opts['exclude_terms'],
				'operator' => 'NOT IN',
			);
		}
		$posts = get_posts( $query );
		if ( ! $posts ) {
			return $posts;
		}
		echo '<ul class="simplesitemap ' . esc_attr( implode( ' ', $classes ) ) . '">' . "\n";
		foreach ( $posts as $post ) {
			echo '<li><a href="' . esc_attr( get_permalink( $post ) ) . '">' . esc_html( $post->post_title ) . '</a></li>';
		}
		echo '</ul>';
	}

}