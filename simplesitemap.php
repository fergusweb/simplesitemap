<?php
/*
Plugin Name:	SimpleSitemap
Plugin URI:		http://www.fergusweb.net/software/simplesitemap/
Description:	Generate a sitemap to a page on your site.  By default, it will generate a list of Pages, Posts and Products.  You can use the shortcode to show custom types if you want to: <code>[simplesitemap view="POST_TYPE"]</code>
Version:		1.3
Author:			Anthony Ferguson
Author URI:		http://www.fergusweb.net
*/


$sitemap = new SimpleSitemapGenerator();

class SimpleSitemapGenerator {
	protected $option_key	= 'sitemap-options';
	protected $nonce		= 'sitemap-verify';
	protected $shortcode	= 'simplesitemap';
	protected $opts			= false;
	
	/**
	 *	Constructor
	 */
	function __construct() {
		// Pre-load Options
		$this->load_options();
		// Launch
		if (!is_admin()) {
			add_action('wp_loaded', array($this,'public_init'));
		} else {
			add_action('init', array($this,'admin_init'));
		}
	}
	
	
	/**
	 *	Option helper functions
	 */
	function load_default_options() {
		$this->opts = array(
			'sitemap_page'			=> false,
			'sel_sitemap_position'	=> 'after',
			'exclude_posts'			=> array(),
			'exclude_terms'			=> array(),
		);
	}
	function load_options() {
		$this->opts = get_option($this->option_key);
		if (!$this->opts)	$this->load_default_options();
		
		// Update options from old system
		if (isset($this->opts['exclude_pages']) && !($this->opts['exclude_posts']))	$this->opts['exclude_posts'] = $this->opts['exclude_pages'];
		if (isset($this->opts['exclude_cats']) && !($this->opts['exclude_terms']))		$this->opts['exclude_terms'] = $this->opts['exclude_cats'];
		unset( $this->opts['exclude_pages'], $this->opts['exclude_cats'] );
		
		if (!$this->opts['exclude_posts'] || !is_array($this->opts['exclude_posts']))	$this->opts['exclude_posts'] = array();
		if (!$this->opts['exclude_terms'] || !is_array($this->opts['exclude_terms']))	$this->opts['exclude_terms'] = array();
		
	}
	function save_options($options = false) {
		if (!$options) { $options = $this->opts; }
		update_option($this->option_key, $options);
	}
	

	
	
	
	/**
	 *	Public functions
	 */
	function public_init() {
		add_action('template_redirect', array($this,'template_redirect'));
	}
	
	function template_redirect() {
		// Register Shortcode
		add_shortcode($this->shortcode, array($this,'handle_shortcode'));
		// Only filter content if a page was selected
		if (!empty($this->opts['sitemap_page']) && is_page($this->opts['sitemap_page'])) {
			add_filter('the_content', array($this,'sitemap_filter_content'));
		}
	}
	
	
	/**
	 *	Insert the sitemap via a hook instead of a shortcode
	 */
	function sitemap_filter_content($content='') {
		ob_start();
		$post_types = array(
			'page'		=> 'Pages',
			'post'		=> 'Posts',
			'product'	=> 'Products',
		);
		foreach ($post_types as $type => $heading) {
			if (!post_type_exists($type))	unset( $post_types[$type] );
		}
		echo '<div class="simple-sitemap">'."\n";
		foreach ($post_types as $type => $heading) {
			do_action( 'simple_sitemap_before_group_'.$type );
			echo '<div class="group-type">'."\n";
			echo '<h3>'.$heading.'</h3>'."\n";
			echo $this->handle_shortcode(array( 'view'=>$type ));
			echo '</div><!-- group-type -->'."\n";
			do_action( 'simple_sitemap_after_group_'.$type );
		}
		echo '</div><!-- simple-sitemap -->'."\n";
		$output = ob_get_clean();
		$content = ($this->opts['sel_sitemap_position'] == 'before') ? $output.$content : $content.$output;
		return $content;
	}
	
	
	/**
	 *	Shortcode Handler
	 *	Defaults to showing Page and Post types
	 *	You can specify a particular type by [shortcode view="product"] or other post_type
	 */
	function handle_shortcode($atts=false) {
		$atts = shortcode_atts(array(
			'view'	=> 'all'
		), $atts);
		ob_start();
		if (post_type_exists($atts['view'])) {
			if (is_post_type_hierarchical( $atts['view'])) {
				$this->sitemap_show_hierarchical( $atts['view'] );
			} else {
				$this->sitemap_show_non_hierarchical( $atts['view'] );
			}
		} else {
			echo $this->sitemap_filter_content();
		}
		return ob_get_clean();
	}
	
	/**
	 *	Displays the sitemap for hierarchical post_types, like pages
	 */
	function sitemap_show_hierarchical( $post_type='page', $classes=false ) {
		if (is_string($classes))	$classes = array( $classes );
		if (!is_array($classes))	$classes = array( 'sitemap-'.$post_type );
		
		echo '<ul class="simplesitemap '.implode(' ', $classes).'">'."\n";
		wp_list_pages(array(
			'post_type'		=> $post_type,
			'sort_column'	=> 'menu_order',
			'sort_order'	=> 'DESC',
			'echo'			=> true,
			'title_li'		=> false,
			'exclude_tree'	=> $this->opts['exclude_posts'],
		));
		echo '</ul>';
	}
	
	/**
	 *	Displays the sitemap for hierarchical post_types, like pages
	 */
	function sitemap_show_non_hierarchical( $post_type='page', $classes=false ) {
		if (is_string($classes))	$classes = array( $classes );
		if (!is_array($classes))	$classes = array( 'sitemap-'.$post_type );
		
		$query = array(
			'post_type'		=> $post_type,
			'numberposts'	=> -1,
			'order'			=> ($post_type=='post') ? 'date' : 'title',
			'orderby'		=> 'DESC',
			'post__not_in'	=> $this->opts['exclude_posts'],
		);
		if ($this->opts['exclude_terms']) {
			$query['tax_query'][] = array(
				'taxonomy'	=> 'category',
				'field'		=> 'term_id',
				'terms'		=> $this->opts['exclude_terms'],
				'operator'	=> 'NOT IN',
			);
		}
		$posts = get_posts($query);
		if (!$posts)	return $posts;
		echo '<ul class="simplesitemap '.implode(' ', $classes).'">'."\n";
		foreach ($posts as $post) {
			echo '<li><a href="'.get_permalink($post).'">'.$post->post_title.'</a></li>';
		}
		echo '</ul>';
	}
	
	
	
	
	
	/**
	 *	Admin functions
	 */
	function admin_init() {
		add_action('admin_menu', array($this,'admin_menu'));
	}
	
	
	function admin_menu() {
		$hooks[] = add_submenu_page('options-general.php', 'Sitemap', 'Sitemap', 'administrator', 'sitemap', array($this,'admin_settings_page'));
		foreach ($hooks as $hook) {		add_action("load-$hook", array($this,'admin_enqueue'));	}
	}
	
	function admin_enqueue() {
		wp_enqueue_style('simplesitemap', plugins_url('admin.css', __FILE__));
	}
	
	function admin_settings_page() {
		echo '<div class="wrap">'."\n";
		echo '<h2>Sitemap Settings</h2>'."\n";
		
		if (isset($_POST['SaveSitemapSettings'])) {
			if (!wp_verify_nonce($_POST['_wpnonce'], $this->nonce)) { echo '<p class="alert">Invalid Security</p></div>'."\n"; return;	}
			$this->opts = array_merge($this->opts, array(
				'sitemap_page'			=> $_POST['sel_sitemap_page'],
				'sel_sitemap_position'	=> $_POST['sel_sitemap_position'],
				'exclude_posts'			=> (array)$_POST['exclude_posts'],
				'exclude_terms'			=> (array)$_POST['exclude_terms'],
			));
			$this->save_options();
			echo '<div id="message" class="updated fade"><p><strong>Settings have been saved.</strong></p></div>';
		}
		
				
//		echo '<pre>'; print_r($this->opts); echo '</pre>';
		?>
  <form class="sitemap" method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
	<?php wp_nonce_field($this->nonce); ?>
	<fieldset>
		<legend>Display Settings</legend>
		<p><label for="sel_sitemap_page">Auto-add to page:</label>
			<?php  echo wp_dropdown_pages(array(
						'name' => 'sel_sitemap_page', 
						'echo' => 0, 
						'show_option_none' => __( '&mdash; None &mdash;' ), 
						'option_none_value' => '0', 
						'selected' => $this->opts['sitemap_page'],
					 ));
			?></p>
		<p><label for="sel_sitemap_position">Auto-add Position:</label>
			<select name="sel_sitemap_position" id="sel_sitemap_position">
			<?php
			$poss_positions = array(
				'below'	=> 'After existing post content',
				'above'	=> 'Before existing post content',
			);
			foreach ($poss_positions as $pos => $label) {
				$sel = ($this->opts['sel_sitemap_position'] == $pos) ? ' selected="selected"' : '';
				echo '<option value="'.$pos.'"'.$sel.'>'.$label.'</option>';
			}
			?>
			</select></p>
	</fieldset>
	
	<fieldset>
		<legend>Exclude these pages:</legend>
		<p class="tick"><span class="scrolling">
		<?php	$this->admin_show_page_items();	?>
		</span></p>
	</fieldset>
    
	<fieldset>
		<legend>Exclude these post categories:</legend>
		<p class="tick"><span class="scrolling">
		<?php	$this->admin_show_category_items();	?>
		</span></p>
	</fieldset>
	    
    <p><label>&nbsp;</label>
	    <input type="submit" name="SaveSitemapSettings" value="Save Changes" class="button-primary save" /></p>
        <?php
		?>
  </form>
        <?php
		echo '</div>'."\n";
	}
	
	function admin_show_page_items($parentID=0, $level=0) {
		$pages = get_pages(array(
			'parent'		=> $parentID,
			'child_of'		=> $parentID,
			'hierarchical'	=> false,
		));
		foreach ($pages as $page) {
			$s = (in_array($page->ID, $this->opts['exclude_posts'])) ? ' checked="checked"' : '';
			echo '<label class="level_'.$level.'"><input type="checkbox" name="exclude_posts[]" value="'.$page->ID.'" '.$s.'/>'.$page->post_title.'</label>';
			$this->admin_show_page_items($page->ID, $level+1);
		}
	}
	
	function admin_show_category_items($parentID=0, $level=0) {
		$cats = get_categories(array(
			'parent'		=> $parentID,
			'child_of'		=> $parentID,
			'hierarchical'	=> false,
			'hide_empty'	=> false,
		));
		foreach ($cats as $cat) {
			$s = (in_array($cat->term_id, $this->opts['exclude_terms'])) ? ' checked="checked"' : '';
			echo '<label class="level_'.$level.'"><input type="checkbox" name="exclude_terms[]" value="'.$cat->term_id.'" '.$s.'/>'.$cat->name.'</label>';
			$this->admin_show_category_items($cat->term_id, $level+1);
		}
	}
	
	
}



?>