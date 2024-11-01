<?php


/*-------------------------------------------
*  Exit if accessed directly
*-------------------------------------------*/
defined( 'ABSPATH' ) || exit;


if ( ! function_exists ( 'wdasr_admin_assets_enqueue' ) ) {
    add_action( 'admin_enqueue_scripts', 'wdasr_admin_assets_enqueue' );
    function wdasr_admin_assets_enqueue() {
        // loading css
        wp_register_style( 'wdasr-admin-style', WDASR_ROOT_URL . 'assets/css/admin-style.css', false, '1.0.0' );
        wp_enqueue_style( 'wdasr-admin-style' );
        
        // loading js
        wp_register_script( 'wdasr-admin-script', WDASR_ROOT_URL .'assets/js/admin-script.js', [ 'jquery' ], '1.0.0', true );
        wp_enqueue_script( 'wdasr-admin-script' );

        wp_register_script( 'wdasr-plot-script', WDASR_ROOT_URL .'assets/js/plotly-2.32.0.min.js', [], '1.0.0', true );
        wp_enqueue_script( 'wdasr-plot-script' );

        // DISPATCHER LOCALIZE SCRIPT
        wp_localize_script('wdasr-admin-script', 'wdasrData', array(
            "ajax_url" => admin_url( 'admin-ajax.php' ),
            "nonce" => wp_create_nonce( 'wdasr_ajax_nonce' ),
        ));
    }
}