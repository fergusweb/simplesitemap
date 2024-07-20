<?php
/**
 * 
 */
class SimpleSitemap {

   	/**
	 * Option key to save settings
	 *
	 * @var string
	 */
	public static $option_key = 'sitemap-options';

	/**
	 * Shortcode
	 *
	 * @var string
	 */
	public static $shortcode = 'simplesitemap';

	/**
	 * Options
	 *
	 * @var array
	 */
	public static $opts = array();

	/**
	 * Hold the Admin and Public classes
	 */
	public static $admin = null;
	public static $public = null;



	/**
	 *  Constructor
	 */
	public function __construct() {
		// Preload Options
		self::load_options();

		// Blocks
		add_action( 'init', array( $this,'load_blocks' ) );

		// Load front or back end
		self::$admin = new SimpleSitemap_Admin();
		self::$public = new SimpleSitemap_Public();
		if (is_admin()) {
			
		} else {
			
		}
	}

	/**
	 * Load blocks
	 *
	 * @return void
	 */
	public function load_blocks() {
		register_block_type( __DIR__ . '/../build/sitemap-block' );
	}


	/**
	 * Load default options
	 *
	 * @return void
	 */
	private static function load_default_options() {
		self::$opts = array(
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
    public static function load_options() {
		self::$opts = get_option( self::$option_key );
		if ( ! self::$opts ) {
			self::load_default_options();
		}

		// Update options from old system.
		if ( isset( self::$opts['exclude_pages'] ) && ! ( self::$opts['exclude_posts'] ) ) {
			self::$opts['exclude_posts'] = self::$opts['exclude_pages'];
		}
		if ( isset( self::$opts['exclude_cats'] ) && ! ( self::$opts['exclude_terms'] ) ) {
			self::$opts['exclude_terms'] = self::$opts['exclude_cats'];
		}
		unset( self::$opts['exclude_pages'], self::$opts['exclude_cats'] );

		if ( ! self::$opts['exclude_posts'] || ! is_array( self::$opts['exclude_posts'] ) ) {
			self::$opts['exclude_posts'] = array();
		}
		if ( ! self::$opts['exclude_terms'] || ! is_array( self::$opts['exclude_terms'] ) ) {
			self::$opts['exclude_terms'] = array();
		}
    }

	/**
	 * Save options to database
	 *
	 * @param array $options Array of options, use self::$opts if not provided.
	 * @return void
	 */
	public static function save_options( $options = false ) {
		if ( ! $options ) {
			$options = self::$opts;
        }
		update_option( self::$option_key, $options );
	}


	/**
	 * Helper to output Post Types - used in Block
	 */
	public static function outputPostType($type, $showHeading) {
		if ($showHeading) {
			$ptObject = get_post_type_object( $type );
			echo '<h3>' . $ptObject->label. '</h3>';
		}
		if ( is_post_type_hierarchical( $type ) ) {
			SimpleSitemap::$public->sitemap_show_hierarchical( $type );
		} else {
			SimpleSitemap::$public->sitemap_show_non_hierarchical( $type );
		}
	}

}