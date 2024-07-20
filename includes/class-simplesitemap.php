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
	 *  Constructor
	 */
	public function __construct() {
		// Preload Options
		self::load_options();

		// Load front or back end
		if (is_admin()) {
			new SimpleSitemap_Admin();
		} else {
			new SimpleSitemap_Public();
		}
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

}