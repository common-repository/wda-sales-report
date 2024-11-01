<?php

/*-------------------------------------------
*  Exit if accessed directly
*-------------------------------------------*/
defined( 'ABSPATH' ) || exit;

if ( !function_exists( 'wdasr_admin_settings_template' ) ) {
    function wdasr_admin_settings_template ( $page_title, $option_group, $page_slug, $submit_btn = [] ) {
        // check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
    
        // add error/update messages
    
        // check if the user have submitted the settings
        // WordPress will add the "settings-updated" $_GET parameter to the url
        if ( isset( $_GET['settings-updated'] ) ) {
            // add settings saved message with the class of "updated"
            add_settings_error( 'wdasr_messages', 'wdasr_message', __( 'Settings Saved', 'wda-sales-report' ), 'updated' );
        }
    
        // show error/update messages
        settings_errors( 'wdasr_messages' );

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( $page_title ); ?></h1>
            <form action="options.php" method="post">
                <?php


                /*----- Nonce Field : So that we can verify while saving -----*/
                wp_nonce_field( 'wdasr_admin_settings', 'wdasr_admin_settings_nonce' );


                /*
                * Prints the input fields with names 'nonce', 'action', and 'option_page' in form section of settings page.
                * The 'schedule_settings' is the settings group name, wich should match the group name used in register_settings()
                * The add_settings_section callback is displayed here. For every new section we need to call settings_fields.
                */
                settings_fields( $option_group );
                
                /*
                * Prints out heading( h2 ) and a table with all settings sections inside the form section of the settings page.
                * 'schedule-settings' is the slug name of the page whose settings section you want to output
                * All the add_settings_field callbacks is displayed here.
                */
                do_settings_sections( $page_slug );
    
                // Add the submit button to serialize the options
                if ( !count( $submit_btn ) ) {
                    submit_button( 'Save Settings' );
                } else {
                    submit_button(
                        array_key_exists( 'label', $submit_btn ) ? $submit_btn['label'] : 'Save Settings',
                        array_key_exists( 'class', $submit_btn ) ? $submit_btn['class'] : 'primary',
                        array_key_exists( 'id', $submit_btn ) ? $submit_btn['id'] : ''
                    );
                }
                ?>
            </form>
        </div>
        <?php
        
 
        /*-------------------------------------------
        *  Bailout if nonce is not verified
        *-------------------------------------------*/
        if (
            ! isset( $_POST['wdasr_admin_settings_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['wdasr_admin_settings_nonce'])), 'wdasr_admin_settings' )
        ) { return; }
    }
}
