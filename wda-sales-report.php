<?php

/*
* Plugin Name:          WDA Sales Report
* Description:          Generate detailed WooCommerce order reports with customizable filters and visualizations.
* Plugin URI:           https://webdevadvisor.com/product/wda-sales-report/
* Version:              1.2.0
* Requires at least:    6.2
* Requires PHP:         5.6.20
* License:              GPLv2 or later
* License URI:          https://www.gnu.org/licenses/gpl-2.0.html
* Author:               Web Dev Advisor
* Author URI:           https://webdevadvisor.com/
* Text Domain:          wda-sales-report
* Requires Plugins:     woocommerce
*/



/*-------------------------------------------
*  Exit if accessed directly
*-------------------------------------------*/

defined( 'ABSPATH' ) || exit;



/*-------------------------------------------
*  Plugin Root Path
*-------------------------------------------*/

define( 'WDASR_ROOT_DIR', plugin_dir_path( __FILE__ ) );



/*-------------------------------------------
*  Plugin Root URL
*-------------------------------------------*/

define( 'WDASR_ROOT_URL', plugin_dir_url( __FILE__ )) ;



/*-------------------------------------------
*  Classes
*-------------------------------------------*/

require_once( WDASR_ROOT_DIR . 'classes/settings-fields.php' );


/*-------------------------------------------
*  Includes
*-------------------------------------------*/

require_once( WDASR_ROOT_DIR . 'includes/activation-actions.php' );
require_once( WDASR_ROOT_DIR . 'includes/class-settings.php' );
require_once( WDASR_ROOT_DIR . 'includes/enqueue-assets.php' );
require_once( WDASR_ROOT_DIR . 'includes/admin-templates.php' );



/*-------------------------------------------
*  Modules
*-------------------------------------------*/

require_once( WDASR_ROOT_DIR . 'modules/class-sales-report.php' );
require_once( WDASR_ROOT_DIR . 'modules/class-ajax.php' );
require_once( WDASR_ROOT_DIR . 'modules/class-admin-menu.php' );


/*-------------------------------------------
*  Plugin Activation Actions
*-------------------------------------------*/
register_activation_hook( WDASR_ROOT_DIR . 'wda-sales-report.php', 'wdasr_activation_actions' );