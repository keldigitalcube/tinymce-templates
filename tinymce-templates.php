<?php
/*
Plugin Name: TinyMCE Templates
Plugin URI: http://wpist.me/wp/tinymce-templates/
Description: TinyMCE Templates plugin will enable to use HTML template on WordPress Visual Editor.
Author: Takayuki Miyauchi
Version: 3.4.5
Author URI: http://wpist.me/
Domain Path: /languages
Text Domain: tinymce_templates
*/

/*
Copyright (c) 2010 Takayuki Miyauchi (THETA NETWORKS Co,.Ltd).

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

$tinymce_templates = new tinymceTemplates();
$tinymce_templates->register();

class tinymceTemplates {

	private $version     = '2.9.0';
	private $post_type   = 'tinymcetemplates';
	private $table       = 'mce_template';
	private $base_url;
	private $translators = array(
		'Takayuki Miyauchi' => array(
			'lang' => 'Japanese',
			'url'  => 'http://wpist.me/',
		),
		'Andrea Bersi' => array(
			'lang' => 'Italian',
			'url'  => 'http://www.andreabersi.com/',
		),
		'Tobias Bergius' => array(
			'lang' => 'Swedish',
			'url'  => '',
		),
		'Martin Lettner' => array(
			'lang' => 'German',
			'url'  => 'http://www.martinlettner.info/',
		),
		'David Bravo' => array(
			'lang' => 'Spanish',
			'url'  => 'http://www.dimensionmultimedia.com/',
		),
		'Frank Groeneveld' => array(
			'lang' => 'Dutch',
			'url'  => 'http://ivaldi.nl/',
		),
		'HAROUY Jean-Michel' => array(
			'lang' => 'French',
			'url'  => 'http://www.laposte.net/',
		),
		'Rafael Funchal' => array(
			'lang' => 'Brazilian Portuguese',
			'url'  => 'http://www.rafaelfunchal.com.br/',
		),
		'Morten Elm' => array(
			'lang' => 'Filipino',
			'url'  => 'http://www.storbyfan.dk/',
		),
		'ihtimir' => array(
			'lang' => 'Russian',
			'url'  => '',
		),
		'Branco' => array(
			'lang' => 'Slovak',
			'url'  => 'http://webhostinggeeks.com/user-reviews/',
		),
	);

	public function register()
	{
		$this->base_url = plugins_url( dirname( plugin_basename( __FILE__ ) ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public function plugins_loaded()
	{
		load_plugin_textdomain(
			'tinymce_templates',
			false,
			dirname(plugin_basename(__FILE__)).'/languages'
		);

		$this->register_post_type();

		add_filter( 'mce_css', array( $this, 'mce_css' ) );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter( 'wp_mce_translation', array( $this, 'wp_mce_translation' ) );

		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_footer-post-new.php', array( $this, 'admin_footer' ) );
		add_action( 'wp_ajax_tinymce_templates', array( $this, 'wp_ajax' ) );
		add_action( 'post_submitbox_start', array( $this, 'post_submitbox_start' ) );
		add_action( 'wp_before_admin_bar_render', array( $this, 'wp_before_admin_bar_render' ) );
	}

	public function wp_before_admin_bar_render() {
		global $wp_admin_bar;
		if ( is_single() || is_page() ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'edit',
				'id' => 'new_template',
				'title' => __( 'Copy to a new template', 'tinymce_templates' ),
				'href' => $this->get_copy_template_url( get_the_ID() )
			) );
		}
	}

	public function wp_mce_translation($mce_translation)
	{
		$mce_translation['Insert template'] = __( "Insert template", "tinymce_templates" );
		$mce_translation['Templates'] = __( "Templates", "tinymce_templates" );
		$mce_translation['No templates defined'] = __( "No templates defined", "tinymce_templates" );

		return $mce_translation;
	}

	public function row_actions( $actions, $post )
	{
		$actions['copy_to_template'] = sprintf(
			'<a href="%s">%s</a>',
			$this->get_copy_template_url( $post->ID ),
			__( 'Copy to a new template', 'tinymce_templates' )
		);
		return $actions;
	}

	public function post_submitbox_start()
	{
		if ( isset( $_GET['post'] ) && intval( $_GET['post'] ) ) {
		?>
			<div id="duplicate-action">
				<a class="submitduplicate duplication"
					href="<?php echo $this->get_copy_template_url($_GET['post']) ?>"><?php _e('Copy to a new template', 'tinymce_templates'); ?></a>
			</div>
		<?php
		}
	}

	public function mce_css( $css )
	{
		$files   = preg_split( "/,/", $css );
		$files[] = $this->base_url . '/editor.css';
		$files   = array_map( 'trim', $files );
		return join( ",", $files );
	}

	public function admin_head()
	{
		$plugin = $this->base_url.'/mce_plugins/4.0/plugins/template/plugin.min.js';

		$url	= admin_url( 'admin-ajax.php' );
		$nonce  = wp_create_nonce( "tinymce_templates" );

		$args = array(
			'action' => 'tinymce_templates',
			'nonce'  => $nonce,
		);
		$url	= add_query_arg( $args, $url);

		$inits['templates'] = $url;

		require_once( dirname(__FILE__) . '/includes/mceplugins.class.php' );

		new tinymcePlugins(
			'template',
			$plugin,
			array( $this, 'tinymce_add_button' ),
			$inits
		);

		echo '<style type="text/css">';
		printf(
			'span.mceIcon.mce_template{background-image: url(%s) !important; background-position: center center !important;background-repeat: no-repeat;}',
			plugins_url('mce_plugins/3.5/plugins/template/img/icon.png', __FILE__)
		);
		echo '</style>';

		if ( get_post_type() === $this->post_type ) {
			global $hook_suffix;
			if ( $hook_suffix === 'post.php' || $hook_suffix === 'post-new.php' ) {
				remove_meta_box( 'slugdiv', $this->post_type, 'normal' );
				echo '<style>#visibility{display:none;}</style>';
			}
		}
	}

	public function tinymce_add_button( $buttons = array() )
	{
		array_unshift( $buttons, '|' );
		array_unshift( $buttons, 'template' );
		return $buttons;
	}

	private function register_post_type()
	{
		$args = array(
			'label' => __( 'Templates', 'tinymce_templates' ),
			'labels' => array(
				'singular_name' => __( 'Templates', 'tinymce_templates' ),
				'add_new_item' => __( 'Add New Template', 'tinymce_templates' ),
				'edit_item' => __( 'Edit Template', 'tinymce_templates' ),
				'add_new' => __( 'Add New', 'tinymce_templates' ),
				'new_item' => __( 'New Template', 'tinymce_templates' ),
				'view_item' => __( 'View Template', 'tinymce_templates' ),
				'not_found' => __( 'No templatess found.', 'tinymce_templates' ),
				'not_found_in_trash' => __(
					'No templates found in Trash.',
					'tinymce_templates'
				),
				'search_items' => __('Search Templates', 'tinymce_templates'),
			),
			'public' => false,
			'publicly_queryable' => false,
			'exclude_from_search' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => 100,
			'rewrite' => false,
			'show_in_nav_menus' => false,
			'register_meta_box_cb' => array( $this, 'addMetaBox' ),
			'supports' => array(
				'title',
				'editor',
				'excerpt',
				'revisions',
				'author',
			)
		);
		register_post_type( $this->post_type, $args );
	}

	public function addMetaBox()
	{
		add_meta_box(
			'tinymce_templates-translators',
			__( 'Translators', 'tinymce_templates' ),
			array( $this, 'translatorsMetaBox' ),
			$this->post_type,
			'side',
			'low'
		);
	}

	public function translatorsMetaBox($post, $box)
	{
		echo '<ul>';
		foreach ( $this->translators as $u => $p ) {
			if ( $p['url'] ) {
				printf(
					'<li><a href="%s">%s</a> ( %s )</li>',
					esc_attr( $p['url'] ),
					esc_html( $u ),
					esc_html( $p['lang'] )
				);
			} else {
				printf(
					'<li>%s ( %s )</li>',
					esc_html( $u ),
					esc_html( $p['lang'] )
				);
			}
		}
		echo '</ul>';
	}

	public function admin_footer()
	{
		if ( get_post_type() === $this->post_type ) {
			if ( isset($_GET['origin']) && intval($_GET['origin']) ) {
				$origin = get_post( intval($_GET['origin']) );
				if ( $origin ) {
					$template = array(
						'post_title' => $origin->post_title,
						'post_content' => wpautop( $origin->post_content ),
					);
					$template = json_encode( $template );
					echo <<<EOL
<script type="text/javascript">
var origin = {$template};
jQuery( '#title').val(origin.post_title );
jQuery( '#content').val(origin.post_content );
</script>
EOL;
				}
			}
		}
	}

	public function wp_ajax()
	{
		global $wp_version;

		nocache_headers();
		if ( !wp_verify_nonce($_GET['nonce'], 'tinymce_templates') ) {
			return;
		}

		header( 'Content-Type: application/javascript; charset=UTF-8' );
		if ( isset($_GET['template_id']) && intval($_GET['template_id']) ) {
			$p = get_post( $_GET['template_id'] );
			if ( $p->post_status === 'publish' ) {
				echo apply_filters(
					"tinymce_templates",
					wpautop( $p->post_content ),
					stripslashes( $p->post_content )
				);
			}
			exit;
		}
		$p = array(
			'post_status' => 'publish',
			'post_type'   => $this->post_type,
			'orderby'	 => 'date',
			'order'	   => 'DESC',
			'numberposts' => -1,
		);
		$posts = get_posts( $p );

		$url	= admin_url( 'admin-ajax.php' );
		$url	= add_query_arg( 'action', 'tinymce_templates', $url );
		$nonce  = wp_create_nonce( "tinymce_templates" );
		$url	= add_query_arg( 'nonce', $nonce, $url );

		$arr = array();
		foreach ( $posts as $p ) {
			$ID = intval( $p->ID );
			$name = esc_html( apply_filters('tinymce_template_title', $p->post_title) );
			$desc = esc_html( apply_filters('tinymce_template_excerpt', $p->post_excerpt) );
			$url  = add_query_arg( 'template_id', $ID, $url );
			$arr[] = array( 'title' => $name, 'url' => $url, 'description' => $desc );
		}

		echo json_encode( $arr );

		exit;
	}

	private function get_copy_template_url( $id )
	{
		return admin_url( 'post-new.php?post_type=tinymcetemplates&origin='.intval($id) );
	}


} // end class tinymceTemplates


// eof
