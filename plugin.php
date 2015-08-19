<?php
/*
Plugin Name: Old Post Notification
Plugin URI: http://www.wordpress.org/extend/plugins/old-post-notification
Description: Mark posts as old and display a notification message above the content.
Version: 1.1
Author: Bill Erickson
Author URI: http://www.billerickson.net
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class Old_Post_Notification {
	var $instance;
	
	function __construct() {
		$this->instance =& $this;
		add_action( 'init', array( $this, 'init' ), 20 );	
	}
	
	function init() {
		// Translations
		load_plugin_textdomain( 'old-post-notification', false, basename( dirname( __FILE__ ) ) . '/lib/languages' );

		// Metabox on Edit screen
		add_action( 'init', array( $this, 'initialize_cmb_meta_boxes' ), 50 );
		add_filter( 'cmb_meta_boxes', array( $this, 'create_metaboxes' ) );
		
		// Settings Page
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action('admin_init', array( $this, 'register_settings' ) );

		// Display default text
		add_filter( 'old_post_notification_default_text', array( $this, 'display_default_text' ) );
		
		// Display Old Post Notification
		add_filter( 'old_post_notification_display', array( $this, 'display_old_post_notification' ) );
		
		// Add it to the content
		add_filter( 'the_content', array( $this, 'display_old_post_notification_on_content' ) );
			
		// CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_css' ) );
		
	}
	
	/** 
	 * Initialize Metabox Class
	 * @link http://www.github.com/jaredatch/Custom-Metaboxes-and-Fields-for-WordPress
	 */
	function initialize_cmb_meta_boxes() {
		$post_types = apply_filters( 'old_post_notification_post_types', array( 'post' ) );
	    if ( !class_exists('cmb_Meta_Box') && !empty( $post_types ) ) {
	        require_once( dirname( __FILE__) . '/lib/metabox/init.php' );
	    }
	}

	/**
	 * Create Page Specific Metaboxes
	 * @link http://www.billerickson.net/wordpress-metaboxes/
	 *
	 * @param array $meta_boxes, current metaboxes
	 * @return array $meta_boxes, current + new metaboxes
	 *
	 */
	function create_metaboxes( $meta_boxes ) {

		$meta_boxes[] = array(
			'id' => 'old-post-notification',
			'title' => __( 'Old Post Notification', 'old-post-notification' ),
			'pages' => apply_filters( 'old_post_notification_post_types', array('post') ), 
			'context' => 'normal',
			'priority' => 'high',
			'show_names' => true, 
			'fields' => array(
				array(
					'name' => __( 'Mark as Old', 'old-post-notification' ),
					'desc' => '',
					'id' => 'old_post_notification_marked_old',
					'type' => 'checkbox'
				),
				array(
					'name' => __( 'Custom Message', 'old-post-notification' ),
					'desc' => __( 'You can set the default in Settings > Old Post Notification', 'old-post-notification' ),
					'id' => 'old_post_notification_custom_message',
					'type' => 'wysiwyg',
					'options' => array( 'textarea_rows' => 5 ),
				)
			),
		);
		
		return $meta_boxes;
	}
	
	/**
	 * Add Settings Page
	 * @link http://codex.wordpress.org/Function_Reference/add_options_page
	 * @link http://ottopress.com/2009/wordpress-settings-api-tutorial/
	 */
	function add_settings_page() {
		add_options_page( __( 'Old Post Notification', 'old-post-notification' ), __( 'Old Post Notification', 'old-post-notification' ), 'manage_options', 'old-post-notification',  array( $this, 'settings_page' ) );
	}
	
	/**
	 * Build Settings Page
	 * @link http://ottopress.com/2009/wordpress-settings-api-tutorial/
	 */
	function settings_page() {
		echo '<div class="wrap">';
		echo '<div id="icon-options-general" class="icon32"><br></div>';
		echo '<h2>' . __( 'Old Post Notification', 'old-post-notification' ) . '</h2>';
		echo '<form action="options.php" method="post">';
		settings_fields('old_post_notification_options');
		do_settings_sections('old_post_notification');
		
		echo '<input name="Submit" type="submit" class="button-primary" value="' . __( 'Save Changes', 'old-post-notification' ) . '" />';
		echo '</form></div>';
	}
	
	/**
	 * Register Settings
	 *
	 */
	function register_settings(){
		register_setting( 'old_post_notification_options', 'old_post_notification_options', array( $this, 'default_text_validate' ) );
		add_settings_section('old_post_notification_default', __( 'Default Text', 'old-post-notification' ), array( $this, 'default_text_intro' ), 'old_post_notification');
		add_settings_field('old_post_notification_default_text', __( 'Default Text', 'old-post-notification' ), array( $this, 'default_text_field' ), 'old_post_notification', 'old_post_notification_default');
	}	
	
	/**
	 * Default Text intro
	 *
	 */
	function default_text_intro() {
		echo wpautop( __( 'When a post is marked as old, this is what\'s displayed at the top of the post. You can override this on a per-post basis.', 'old-post-notification' ) );
	}
	
	/**
	 * Default Text field
	 *
	 */
	function default_text_field() {
		
		echo wp_editor( 
			apply_filters( 'old_post_notification_default_text', '' ), 'old_post_notification_default_text', 
			array( 'textarea_name' => 'old_post_notification_options[old_post_notification_default_text]', )
		);
	}
	
	/**
	 * Default Text validate
	 *
	 * @param string $input
	 * @return string
	 */
	function default_text_validate( $input ) {
		return wp_kses_post( $input );
	}
	
	/**
	 * Dispaly default text
	 * 
	 * @param string $text
	 * @return string
	 */
	function display_default_text( $text ) {
	
		$options = get_option( 'old_post_notification_options' );
		$default = wpautop( __( 'This post has been marked as old.', 'old-post-notification' ) );
		$text = isset( $options['old_post_notification_default_text'] ) ? $options['old_post_notification_default_text'] : $default;
		return $text;
	}
	
	/**
	 * Display Old Post Notification
	 *
	 * @param string $content
	 * @return string
	 */
	function display_old_post_notification( $notice ) {
		if( !is_singular() )
			return;
			
		global $post;
		$old = get_post_meta( $post->ID, 'old_post_notification_marked_old', true );
		if( 'on' !== $old )
			return;
		$default = apply_filters( 'old_post_notification_default_text', '' );
		$override = get_post_meta( $post->ID, 'old_post_notification_custom_message', true );
		
		$notice = !empty( $override ) ? $override : $default;
		return '<div class="old-post-notification">' . wpautop( $notice ) . '</div>';
	}
	
	/**
	 * Display Old Post Notification on Content
	 *
	 * Disable using: add_filter( 'old_post_notification_on_content', '__return_false' );
	 */
	function display_old_post_notification_on_content( $content ) {
		if( !apply_filters( 'old_post_notification_on_content', true )  )
			return $content;
		
		return apply_filters( 'old_post_notification_display', '' ) . $content;
	}
	
	/**
	 * Enqueue CSS
	 *
	 * Disable using this: add_filter( 'old_post_notification_css', '__return_false' );
	 */
	function enqueue_css() {
	global $post;
		if( is_singular() && 'on' == get_post_meta( $post->ID, 'old_post_notification_marked_old', true ) && apply_filters( 'old_post_notification_css', true ) )
			wp_enqueue_style( 'old-post-notification', plugins_url( 'lib/css/old-post-notification.css', __FILE__ ) );
	}
}

$Old_Post_Notification = new Old_Post_Notification;