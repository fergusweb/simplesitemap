<?php
/**
 * Admin functions
 */
class SimpleSitemap_Admin {

    /**
     * Constructor
     */
    function __construct() {
        add_action( 'init', array( $this, 'admin_init' ) );
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
		wp_enqueue_style( 'simplesitemap', plugins_url( '../css/admin.css', __FILE__ ), null, '1.0' );
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
			if ( ! check_admin_referer( SimpleSitemap::$option_key ) ) {
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

			SimpleSitemap::$opts = array_merge( SimpleSitemap::$opts, $settings );

			SimpleSitemap::save_options();
			echo '<div id="message" class="updated fade"><p><strong>Settings have been saved.</strong></p></div>';
		}

		?>
<form class="sitemap" method="post" action="<?php echo esc_url( 'options-general.php?page=sitemap' ); ?>">
		<?php wp_nonce_field( SimpleSitemap::$option_key ); ?>
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
					'selected'          => esc_attr( SimpleSitemap::$opts['sitemap_page'] ),
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
				$sel = ( SimpleSitemap::$opts['sel_sitemap_position'] === $pos ) ? ' selected="selected"' : '';
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
			$s = ( in_array( $page->ID, SimpleSitemap::$opts['exclude_posts'] ) ) ? ' checked="checked"' : '';
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
			$s = ( in_array( $cat->term_id, SimpleSitemap::$opts['exclude_terms'] ) ) ? ' checked="checked"' : '';
			echo '<label class="level_' . esc_attr( $level ) . '"><input type="checkbox" name="exclude_terms[]" value="' . esc_attr( $cat->term_id ) . '" ' . esc_html( $s ) . '/>' . esc_html( $cat->name ) . '</label>';
			$this->admin_show_category_items( $cat->term_id, $level + 1 );
		}
	}
}