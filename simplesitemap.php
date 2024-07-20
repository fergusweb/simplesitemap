<?php
/**
 * Plugin Name: SimpleSitemap
 * Plugin URI:  http://www.fergusweb.net/software/simplesitemap/
 * Description: Generate a sitemap to a page on your site.  By default, it will generate a list of Pages, Posts and Products.  You can use the shortcode to show custom types if you want to: <code>[simplesitemap view="POST_TYPE"]</code>
 * Version:     1.3.1
 * Author:      Anthony Ferguson
 * Author URI:  https://www.fergusweb.net
 *
 * @package SimpleSitemap
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Simple Sitemap Generator
 */
class SimpleSitemapGenerator {

	/**
	 * Container for the main instance of the class.
	 *
	 * @var NRSigns_Services|null
	 */
	private static $instance = null;

	/**
	 * Return instance of this class
	 *
	 * @return SimpleSitemapGenerator
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Option key to save settings
	 *
	 * @var string
	 */
	protected $option_key = 'sitemap-options';

	/**
	 * Shortcode
	 *
	 * @var string
	 */
	protected $shortcode = 'simplesitemap';

	/**
	 * Options
	 *
	 * @var array
	 */
	protected $opts = array();



	/**
	 *  Constructor
	 */
	public function __construct() {
		// Pre-load Options.
		$this->load_options();
		// Launch.
		if ( ! is_admin() ) {
			add_action( 'wp_loaded', array( $this, 'public_init' ) );
		} else {
			add_action( 'init', array( $this, 'admin_init' ) );
		}
	}


	/**
	 * Load default options
	 *
	 * @return void
	 */
	private function load_default_options() {
		$this->opts = array(
			'sitemap_page'         => false,
			'sel_sitemap_position' => 'after',
			'exclude_posts'        => array(),
			'exclude_terms'        => array(),
		);
	}

	/**
	 * Load options from database, with default fallback
	 *
	 * @return void
	 */
	private function load_options() {
		$this->opts = get_option( $this->option_key );
		if ( ! $this->opts ) {
			$this->load_default_options();
		}

		// Update options from old system.
		if ( isset( $this->opts['exclude_pages'] ) && ! ( $this->opts['exclude_posts'] ) ) {
			$this->opts['exclude_posts'] = $this->opts['exclude_pages'];
		}
		if ( isset( $this->opts['exclude_cats'] ) && ! ( $this->opts['exclude_terms'] ) ) {
			$this->opts['exclude_terms'] = $this->opts['exclude_cats'];
		}
		unset( $this->opts['exclude_pages'], $this->opts['exclude_cats'] );

		if ( ! $this->opts['exclude_posts'] || ! is_array( $this->opts['exclude_posts'] ) ) {
			$this->opts['exclude_posts'] = array();
		}
		if ( ! $this->opts['exclude_terms'] || ! is_array( $this->opts['exclude_terms'] ) ) {
			$this->opts['exclude_terms'] = array();
		}
	}

	/**
	 * Save optoins to database
	 *
	 * @param array $options Array of options, use $this->opts if not provided.
	 * @return void
	 */
	private function save_options( $options = false ) {
		if ( ! $options ) {
			$options = $this->opts; }
		update_option( $this->option_key, $options );
	}





	/**
	 *  Public function loader
	 */
	public function public_init() {
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
	}

	/**
	 * Register shortcode, and set up the_filter for an auto-add page if one is set
	 *
	 * @return void
	 */
	public function template_redirect() {
		// Register Shortcode.
		add_shortcode( $this->shortcode, array( $this, 'handle_shortcode' ) );
		// Only filter content if a page was selected.
		if ( ! empty( $this->opts['sitemap_page'] ) && is_page( $this->opts['sitemap_page'] ) ) {
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
		$content = ( 'before' === $this->opts['sel_sitemap_position'] ) ? $output . $content : $content . $output;
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
				'exclude_tree' => $this->opts['exclude_posts'],
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
			'post__not_in' => $this->opts['exclude_posts'],
		);
		if ( $this->opts['exclude_terms'] ) {
			$query['tax_query'][] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $this->opts['exclude_terms'],
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





	/**
	 *  Admin functions
	 */
	public function admin_init() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Admin Menu
	 *
	 * @return void
	 */
	public function admin_menu() {
		$hooks[] = add_submenu_page( 'options-general.php', 'Sitemap', 'Sitemap', 'administrator', 'sitemap', array( $this, 'admin_settings_page' ) );
		foreach ( $hooks as $hook ) {
			add_action( "load-$hook", array( $this, 'admin_enqueue' ) );
		}
	}

	/**
	 * Admin enqueue styles
	 *
	 * @return void
	 */
	public function admin_enqueue() {
		wp_enqueue_style( 'simplesitemap', plugins_url( 'admin.css', __FILE__ ), null, '1.0' );
	}

	/**
	 * Admin settings page
	 *
	 * @return void
	 */
	public function admin_settings_page() {
		echo '<div class="wrap">' . "\n";
		echo '<h2>Sitemap Settings</h2>' . "\n";

		if ( isset( $_POST['SaveSitemapSettings'] ) ) {
			if ( ! check_admin_referer( $this->option_key ) ) {
				echo '<p class="alert">Invalid Security</p></div>' . PHP_EOL;
				return;
			}

			$settings     = array();
			$setting_keys = array(
				'sel_sitemap_page'     => 'sitemap_page',
				'sel_sitemap_position' => 'sel_sitemap_position',
				'exclude_posts'        => 'exclude_posts',
				'exclude_terms'        => 'exclude_terms',
			);
			foreach ( $setting_keys as $postkey => $opkey ) {
				if ( isset( $_POST[ $postkey ] ) ) {
					if ( 'exclude_posts' === $opkey || 'exclude_terms' === $opkey ) {
						$settings[ $opkey ] = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $postkey ] ) );
					} else {
						$settings[ $opkey ] = sanitize_text_field( wp_unslash( $_POST[ $postkey ] ) );
					}
				}
			}
			if ( ! isset( $settings['exclude_posts'] ) || ! is_array( $settings['exclude_posts'] ) ) {
				$settings['exclude_posts'] = array();
			}
			if ( ! isset( $settings['exclude_terms'] ) || ! is_array( $settings['exclude_terms'] ) ) {
				$settings['exclude_terms'] = array();
			}

			$this->opts = array_merge( $this->opts, $settings );

			$this->save_options();
			echo '<div id="message" class="updated fade"><p><strong>Settings have been saved.</strong></p></div>';
		}

		?>
<form class="sitemap" method="post" action="<?php echo esc_url( 'options-general.php?page=sitemap' ); ?>">
		<?php wp_nonce_field( $this->option_key ); ?>
	<fieldset>
		<legend>Display Settings</legend>
		<p><label for="sel_sitemap_page">Auto-add to page:</label>
			<?php
			wp_dropdown_pages(
				array(
					'name'              => 'sel_sitemap_page',
					'echo'              => 1,
					'show_option_none'  => esc_attr( __( '&mdash; None &mdash;' ) ),
					'option_none_value' => '0',
					'selected'          => esc_attr( $this->opts['sitemap_page'] ),
				)
			);
			?>
		</p>
		<p><label for="sel_sitemap_position">Auto-add Position:</label>
			<select name="sel_sitemap_position" id="sel_sitemap_position">
			<?php
			$poss_positions = array(
				'below' => 'After existing post content',
				'above' => 'Before existing post content',
			);
			foreach ( $poss_positions as $pos => $label ) {
				$sel = ( $this->opts['sel_sitemap_position'] === $pos ) ? ' selected="selected"' : '';
				echo '<option value="' . esc_attr( $pos ) . '"' . esc_html( $sel ) . '>' . esc_html( $label ) . '</option>';
			}
			?>
			</select></p>
	</fieldset>

	<fieldset>
		<legend>Exclude these pages:</legend>
		<p class="tick"><span class="scrolling">
		<?php	$this->admin_show_page_items(); ?>
		</span></p>
	</fieldset>

	<fieldset>
		<legend>Exclude these post categories:</legend>
		<p class="tick"><span class="scrolling">
		<?php	$this->admin_show_category_items(); ?>
		</span></p>
	</fieldset>

	<p><label>&nbsp;</label>
		<input type="submit" name="SaveSitemapSettings" value="Save Changes" class="button-primary save" /></p>
</form>
		<?php
		echo '</div>' . "\n";
	}

	/**
	 * Helper to get page items, and output as indented list
	 *
	 * @param integer $parent_id  Start with 0, and loop for each parent id.
	 * @param integer $level     Keep track of which level we're on for indentation.
	 * @return void
	 */
	public function admin_show_page_items( int $parent_id = 0, $level = 0 ) {
		$pages = get_pages(
			array(
				'parent'       => $parent_id,
				'child_of'     => $parent_id,
				'hierarchical' => false,
			)
		);
		foreach ( $pages as $page ) {
			$s = ( in_array( $page->ID, $this->opts['exclude_posts'] ) ) ? ' checked="checked"' : '';
			echo '<label class="level_' . esc_attr( $level ) . '"><input type="checkbox" name="exclude_posts[]" value="' . esc_attr( $page->ID ) . '" ' . esc_html( $s ) . '/>' . esc_html( $page->post_title ) . '</label>';
			$this->admin_show_page_items( $page->ID, $level + 1 );
		}
	}

	/**
	 * Helper to get term items, and output as indented list
	 *
	 * @param integer $parent_id  Start with 0, and loop for each parent id.
	 * @param integer $level      Keep track of which level we're on for indentation.
	 * @return void
	 */
	public function admin_show_category_items( $parent_id = 0, $level = 0 ) {
		$cats = get_categories(
			array(
				'parent'       => $parent_id,
				'child_of'     => $parent_id,
				'hierarchical' => false,
				'hide_empty'   => false,
			)
		);
		foreach ( $cats as $cat ) {
			$s = ( in_array( $cat->term_id, $this->opts['exclude_terms'] ) ) ? ' checked="checked"' : '';
			echo '<label class="level_' . esc_attr( $level ) . '"><input type="checkbox" name="exclude_terms[]" value="' . esc_attr( $cat->term_id ) . '" ' . esc_html( $s ) . '/>' . esc_html( $cat->name ) . '</label>';
			$this->admin_show_category_items( $cat->term_id, $level + 1 );
		}
	}
}


SimpleSitemapGenerator::instance();
