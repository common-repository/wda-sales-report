<?php


/*-------------------------------------------
*  Exit if accessed directly
*-------------------------------------------*/
defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'wdasr_activation_actions' ) ) {
    function wdasr_activation_actions() {
        $initial_settings = new WDASR_settings();

        update_option( 'wdasr_options', wp_json_encode( $initial_settings->settings( gmdate('Y-m-d') ) ) );
    }
}