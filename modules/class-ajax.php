<?php


/*-------------------------------------------
*  Exit if accessed directly
*-------------------------------------------*/
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'WDASR_AJAX' ) ) :


class WDASR_AJAX extends WDASR_settings {
    private $ajax_result = [
        'all_orders'    => [],
        'prime_data'    => [],
        'daily_orders'  => [],
        'total_orders'  => 0,
        'total_amount'  => 0,
        'currency'      => '',
        'from_date'     => '',
        'to_date'       => '',
        'warnings'      => [],
        'perday_footer' => '',
        'products'      => [],
        'map_others'    => [],
        'map_mnm'       => [],
        'map_variable'  => [],
        'total_products'=> 0,
    ];

    private $lopped_data = [];
    private $json_data = [];


    public function __construct () {
        $this->ajax_result['prime_data'][ 'others' ] = [
            'orders' => 0,
            'title' => 'Others',
            'total' => 0
        ];

        add_action( 'wp_ajax_wdasr_ajax_request', [ $this, 'ajax' ] );
        add_action( 'wp_ajax_nopriv_wdasr_ajax_request', [ $this, 'nopriv_ajax' ] );
    } // ENDS __construct()



    /*-------------------------------------------
    * PROCCESS THE AJAX REQUEST IF LOGGED IN
    *-------------------------------------------*/
    public function ajax () {



        /*-------------------------------------------
        *  Bailout if current user doesn't
        *  have enough permission
        *-------------------------------------------*/

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }



        /*----------------------------------------------------------
        *  Bailout if nonce not verified
        *----------------------------------------------------------*/
        
        check_ajax_referer( 'wdasr_ajax_nonce', '_ajax_nonce' );

        if ( isset( $_REQUEST['_ajax_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash($_REQUEST['_ajax_nonce'])), 'wdasr_ajax_nonce' ) ) {
            exit();
        }



        if ( isset( $_SERVER["REQUEST_METHOD"] ) && sanitize_text_field(wp_unslash( $_SERVER["REQUEST_METHOD"] )) == "POST" ) {

            
            /*-----------------------------------
            *  Getting incoming data
            *-----------------------------------*/
            
            $request_type       = isset( $_POST['key'] ) && !empty( $_POST['key'] ) ? sanitize_text_field( wp_unslash($_POST['key']) ) : 'empty';
            $this->json_data    = isset( $_POST['json_data'] ) && !empty( $_POST['json_data'] ) ? json_decode( sanitize_text_field( wp_unslash($_POST['json_data']) ), true ) : 'empty';
            $_filters           = $this->json_data['filters'];
            $_ranges            = $this->json_data['options']['ranges'];


            
            /*-----------------------------------
            *  Get currency Symbol
            *-----------------------------------*/

            $this->ajax_result['currency'] = get_woocommerce_currency_symbol();


            
            /*-----------------------------------
            *  Query arguments
            *-----------------------------------*/

            $_order_args = [
                'posts_per_page'    => -1,
                'orderby'           => 'date',
                'order'             => 'DESC',
                'date_query'        => [
                    [
                        'after'     => $_ranges['from_date'],
                        'before'    => $_ranges['to_date'],
                        'inclusive' => true,
                    ],
                ],
                'type'              => 'shop_order'  // This explicitly queries only orders, not refunds or other post types
            ];


            foreach ( $_filters as $_key => $_value ) {
                // $_value = json_decode( $_value ) == null ? sanitize_text_field( $_value ) : json_decode( $_value );
                $_filter_components = $this->filter_components[ $_key ];

                if ( $_filter_components['query_type'] == 'primary' ) {
                    // $_order_args[ $_key ] = $_filter_components['type'] == 'multi-select' ? $_value['filter_items'] : $_value;
                    $_order_args[ $_key ] = $_value;
                } else {
                    $_order_args[ 'meta_query' ][] = [
                        'key'     => $_key,
                        'value'   => $_value,
                        'compare' => $_filter_components['query_type']
                    ];
                }
            }

            
            $this->wc = $this->wc ? $this->wc : WC();
            


            /*-----------------------------------
            *  Finally retrieve data from DB
            *-----------------------------------*/

            $_orders_query = wc_get_orders( $_order_args );
            


            /*-----------------------------------
            *  Count total orders
            *-----------------------------------*/

            $this->ajax_result['total_orders'] = count( $_orders_query );
            


            /*-----------------------------------
            *  Serialize data
            *-----------------------------------*/

            if ( $this->ajax_result['total_orders'] ) {
                foreach ( $_orders_query as $index => $_order ) {
                    $this->serialize_data( $_order );
                }
            } else {
                $this->ajax_result[ 'warnings' ][] = 'No orders found!';
            }
            


            /*-----------------------------------
            *  Saving Essential Data
            *-----------------------------------*/
            
            update_option( 'wdasr_options', wp_json_encode( $this->json_data ) );
            update_option( 'wdasr_saved_filters', wp_json_encode( $_filters ) );

            $others_primary_filters = $this->ajax_result['prime_data'][ 'others' ];
            unset( $this->ajax_result['prime_data'][ 'others' ] );
            $this->ajax_result['prime_data'][ 'others' ] = $others_primary_filters;
            


            /*-----------------------------------
            *  Send JSON data to front-end
            *-----------------------------------*/

            $this->ajax_result['total_days']        = count( $this->ajax_result['daily_orders'] );
            $this->ajax_result['from_date']         = gmdate( 'F j, Y', strtotime( $_ranges['from_date'] ) );
            $this->ajax_result['to_date']           = gmdate( 'F j, Y', strtotime( $_ranges['to_date'] ) );
            $this->ajax_result['primary_key']       = $this->json_data['options']['primary'];
            $this->ajax_result['total_amount']      = number_format($this->ajax_result['total_amount'], 2);
            $this->ajax_result['total_products']    = count( $this->ajax_result['products'] );

            wp_send_json( $this->ajax_result );
            // wp_send_json( $this->json_data['filters'] );
        }

        wp_die();
    } // ENDS ajax()



    private function serialize_data ( $order ) {
        $_primary_key = $this->json_data['options']['primary'];

        // Order Date
        $creatation_date        = $order->get_date_created();
        $creatation_date_key    = $creatation_date->date("M_j@Y");

        
        $order_object = [
            'order_id'      => $order->get_id(),
            'full_name'     => $order->get_formatted_billing_full_name(),
            'created_at'    => $creatation_date->date("M j, Y"),
            'post_status'   => $order->get_status(),
            'amount'        =>  number_format($order->get_total(), 2),
        ];



        if ( ! array_key_exists( $creatation_date_key, $this->ajax_result[ 'daily_orders' ] ) ) {
            $this->ajax_result[ 'daily_orders' ][ $creatation_date_key ] = [];
            $this->ajax_result[ 'daily_orders' ][ $creatation_date_key ][ 'perday' ] = 0;
        }
        $this->ajax_result[ 'daily_orders' ][ $creatation_date_key ][ 'perday' ] += $order->get_total();



        if ( ! empty( $_primary_key ) ) {
            $order_object['primary_title'] = $this->filter_components[ $_primary_key ]['primary'] ? $this->primary_data( $_primary_key, $order, $creatation_date_key ) : '';
        }



        // Store the order object for all orders
        $this->ajax_result[ 'all_orders' ][] = $order_object;



        // Get and Loop Over Order Items
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();

            $parent_id = $item->get_product_id();
            $variable_id = $item->get_variation_id();
            
            $product_id = $parent_id;



            if ( $variable_id ) {
                $product_id = $variable_id;

                if ( ! array_key_exists( $parent_id, $this->ajax_result[ 'map_variable' ]) ) {
                    $parent_data = $product->get_parent_data();

                    $this->ajax_result[ 'map_variable' ][ $parent_id ] = [
                        'children'  => [],
                        'title'     => $parent_data['title'],
                        'sku'       => $parent_data['sku'],
                    ];
                }

                if ( ! in_array($variable_id, $this->ajax_result[ 'map_variable' ][ $parent_id ]) ) {
                    $this->ajax_result[ 'map_variable' ][ $parent_id ][ 'children' ][] = $variable_id;
                    $this->ajax_result[ 'map_variable' ][ $parent_id ][ 'children' ] = array_unique($this->ajax_result[ 'map_variable' ][ $parent_id ][ 'children' ]);
                }
            } else if ( is_array( $item->get_meta('_mnm_config') ) ) {
                $this->ajax_result[ 'map_mnm' ][ $parent_id ] = array_keys( $item->get_meta('_mnm_config') );
            } else {
                if ( ! in_array($parent_id, $this->ajax_result[ 'map_others' ]) ) {
                    $this->ajax_result[ 'map_others' ][] = $parent_id;
                }
            }
            


            if ( ! array_key_exists( $product_id, $this->ajax_result[ 'products' ] ) ) {
                $this->ajax_result[ 'products' ][ $product_id ][ 'name' ] = '';
                $this->ajax_result[ 'products' ][ $product_id ][ 'sku' ] = '';
                $this->ajax_result[ 'products' ][ $product_id ][ 'quantity' ] = 0;
            }


            $this->ajax_result[ 'products' ][ $product_id ][ 'name' ] = $item->get_name();
            $this->ajax_result[ 'products' ][ $product_id ][ 'sku' ] = $product->get_sku();
            $this->ajax_result[ 'products' ][ $product_id ][ 'quantity' ] += $item->get_quantity();
        }


        // Adding order amount to total
        $this->ajax_result[ 'total_amount' ] += $order->get_total();
    } // ENDS serialize_data()


    
    private function primary_data ( $primary_filter_key, $order, $_date_key ) {
        $primary_key   = '';
        $primary_title = '';
        

        switch ( $primary_filter_key ) {
            case 'payment_method':
                $primary_key = $order->get_payment_method();
                $primary_title = ucwords( $order->get_payment_method_title() );
                break;

            case 'post_status':
                $primary_key = $order->get_status();
                $primary_title = ucwords( $primary_key );
                break;

            case 'billing_city':
                $primary_title = $order->get_billing_city();
                $primary_key = str_replace(" ","_", strtolower( $primary_title ));
                break;

            case 'billing_country':
                $primary_title = $order->get_billing_country();
                $primary_key = str_replace(" ","_", strtolower( $primary_title ));
                break;

            case 'shipping_methods':
                foreach ( $order->get_shipping_methods() as $method ) {
                    $primary_key = $method->get_instance_id();
                    $zone_name = json_decode( WC_Shipping_Zones::get_zone_by( 'instance_id', $primary_key ), true )['zone_name'];
                    $primary_title =  $method->get_name() . ' : ' . $zone_name;
                }
                break;

            case 'shipping_city':
                $primary_title = $order->get_shipping_city();
                $primary_key = str_replace(" ","_", strtolower( $primary_title ));
                break;

            case 'shipping_country':
                $primary_title = $order->get_shipping_country();
                $primary_key = str_replace(" ","_", strtolower( $primary_title ));
                break;

            case 'currency':
                $primary_title = $order->get_currency();
                $primary_key = str_replace(" ","_", strtolower( $primary_title ));
                break;
        }



        if ( ! array_key_exists( $primary_key, $this->ajax_result[ 'prime_data' ] ) ) {
            $this->ajax_result[ 'prime_data' ][ $primary_key ] = [
                'orders' => 0,
                'title'  => $primary_title,
                'total'  => 0
            ];
        }
        
        $primary_key = $primary_key ? $primary_key : 'others';

        $this->ajax_result[ 'prime_data' ][ $primary_key ][ 'total' ] += number_format($order->get_total(), 2);
        $this->ajax_result[ 'prime_data' ][ $primary_key ][ 'orders' ]++;
        

        // Create payment method array against per day if not exists
        if ( ! array_key_exists( $primary_key, $this->ajax_result[ 'daily_orders'][ $_date_key ] ) ) {
            $this->ajax_result[ 'daily_orders' ][ $_date_key ][ $primary_key ] = 0;
        }
        

        $perday_prime_total = $this->ajax_result[ 'daily_orders' ][ $_date_key ][ $primary_key ];
        $this->ajax_result[ 'daily_orders' ][ $_date_key ][ $primary_key ] = $perday_prime_total + $order->get_total();

        return $primary_title;
    }
    
    
    /*-------------------------------------------
    * IGNORE THE AJAX REQUEST IF NOT LOGGED IN
    *-------------------------------------------*/
    public function nopriv_ajax () {
        check_ajax_referer( 'wdasr_ajax_nonce', '_ajax_nonce' );
        if ( isset($_REQUEST['_ajax_nonce']) && ! wp_verify_nonce( sanitize_text_field(wp_unslash($_REQUEST['_ajax_nonce'])), 'wdasr_ajax_nonce' ) ) {
            exit();
        }
        echo "notloggedin";
        wp_die();
    } // ENDS nopriv_ajax()

}

new WDASR_AJAX();


endif;