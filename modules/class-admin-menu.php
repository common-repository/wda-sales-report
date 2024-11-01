<?php


/*-------------------------------------------
*  Exit if accessed directly
*-------------------------------------------*/
defined( 'ABSPATH' ) || exit;




if ( !class_exists( 'WDASR_Admin_Menu' ) ) {
	class WDASR_Admin_Menu extends WDA_Sales_Report {
        
		public function __construct () {
            $this->settings = json_decode( get_option( 'wdasr_options', '{}' ), true );
            $this->options  = $this->settings['options'];
            $this->saved_filters = count($this->saved_filters) ? $this->saved_filters : get_option( 'wdasr_saved_filters', '{}' );

			add_action( 'admin_menu', [ $this, 'admin_menu_options' ] );
		}

		

		public function admin_menu_options () {
            add_menu_page(
                'Admin Sales Report',               // Page title
                'Sales Report',                     // Menu Title
                'manage_options',                   // Capability
                'wdasr-settings',                   // Menu Slug
                [ $this, 'admin_menu_callback' ],   // Callback function to render the page
                WDASR_ROOT_URL . 'assets/images/logo.svg',	// Dashicon
                30                                  // Position
            );
		}
        

	}


    new WDASR_Admin_Menu();
}