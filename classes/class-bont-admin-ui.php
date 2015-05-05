<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BONT_Admin_UI {
	
	public function admin_menu() {
		global $submenu;
		if ( isset( $submenu['bontact-settings'] ) )
			$submenu['bontact-settings'][0][0] = __( 'General Settings', 'bontact' );
	}
	
	public function admin_print_scripts() {
		wp_enqueue_style( 'bont-admin-ui', plugins_url( '/assets/css/admin-ui.min.css', BONTACT_BASE_FILE ) );
	}
	
	public function __construct() {		
		add_action( 'admin_menu', array( &$this, 'admin_menu' ), 50 );
		add_action( 'admin_print_scripts', array( &$this, 'admin_print_scripts' ) );
	}
	
}