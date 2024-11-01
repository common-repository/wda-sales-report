<?php

/*-------------------------------------------
*  Exit if accessed directly
*-------------------------------------------*/
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'WDA_Sales_Report' ) ) :

class WDA_Sales_Report extends WDASR_settings {
    
    protected $settings = [];
    protected $options = [];

    protected $saved_filters = [];
    protected $active_filters = [];
    protected $inactive_filters = [];

    protected $preloaded_properties = [];
    protected $payment_methods;
    protected $order_status;
    

    public function admin_menu_callback () {
        $wc_method = WC();
        $payment_methods = $wc_method->payment_gateways()->get_available_payment_gateways();
        $this->payment_methods = array_combine( array_keys( $payment_methods ), array_column( $payment_methods, 'method_title' ) );
        $this->order_status = wc_get_order_statuses();

        $this->preloaded_properties = [
            'payment_method' => $this->payment_methods,
            'post_status'    => $this->order_status,
            'filters'        => $this->filter_components,
        ];

        $this->active_filters = array_keys( $this->filter_components );

        $_displays = $this->options['display'];

        ?>


        <div id="wdasr--report-container" class="wrap">

            <section class="wdasr--flex vertical row-gap" id="wdasr--filter-properties">

                <div id="wdasr--filters" class="wdasr--flex vertical">

                <?php
                
                $_saved_filters = json_decode( $this->saved_filters, true );
                $this->active_filters = array_keys( $_saved_filters );


                if ( ! count( $_saved_filters ) ) {
                    $this->filters( 'unkown', '' );
                } else {
                    // $this->inactive_filters = array_values( array_diff($this->inactive_filters, array_keys($_saved_filters)) );

                    foreach ( $_saved_filters as $_key => $_value ) {
                        $this->filters( $_key, $_value );
                    }
                }

                ?>

                </div>
                
                <button class="wdasr--button outline" type="button" id="wdasr--new-filter"><span class="dashicons dashicons-plus-alt2"></span> New filter</button>
            </section>
            
            <!-- <hr /> -->

            <section id="wdasr--date-range">
                <input class="wdasr--range-dates" type="date" name="from_date" value="<?php echo esc_attr($this->options['ranges']['from_date']); ?>" />
                <input class="wdasr--range-dates" type="date" name="to_date" value="<?php echo esc_attr($this->options['ranges']['to_date']); ?>" />
                
                <button class="wdasr--button primary" id="wdasr--generate-report" name="wdasr-sales-report">Generate report</button>
            </section>
            

            <?php if ( count($_displays) ) : ?>
            <section id="wdasr--display-options">
                <?php foreach ($_displays as $_display_key => $_display_object) : ?>

                    <?php $_ability = array_key_exists('free', $_display_object) ? $_display_object['free'] : true; ?>

                    <span class="wdasr--margin right-30">
                        <input
                            name="<?php echo esc_attr( $_display_key ); ?>"
                            type="checkbox"
                            id="<?php echo esc_attr( $_display_key ); ?>"
                            value="checked"
                            <?php echo esc_attr( $_display_object['value'] ); ?>
                            <?php echo esc_attr( $_ability ? '' : 'disabled' ); ?>
                        />

                        <label for="<?php echo esc_attr( $_display_key ); ?>"><?php echo esc_html( $_display_object['label'] ); ?> <?php $_ability ? '' : $this->get_pro(); ?></label>
                    </span>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>


            <!-- LOADING BAR -->
            <div class="wdasr--hide" id="wdasr--display_loading">
                <div></div>
            </div>


            <?php foreach ($_displays as $_display_key => $_display_object) : ?>
            <div class="wdasr--section<?php echo esc_attr( $_display_object['value'] == 'unchecked' ? " wdasr--hide" : "" ); ?>" id="wdasr--display_<?php echo esc_attr($_display_key); ?>"></div>
            <?php endforeach; ?>
            

            <!-- PRELOADED DATA -->
            <input id="wdasr--preloaded-options" type="hidden" value='<?php echo wp_json_encode( $this->options ); ?>' />
            <input id="wdasr--preloaded-data" type="hidden" value='<?php echo wp_json_encode( $this->preloaded_properties ); ?>' />
            <input id="wdasr--saved-filters" type="hidden" value='<?php echo esc_attr( count( $_saved_filters ) ? $this->saved_filters : wp_json_encode(['unkown' => '']) ); ?>' />

        </div>
        <?php
    }



    protected function filters ( $key, $value ) {
        // $this->filter_components[$key]
        $primary = $this->filter_components[$key]['primary'];
        ?>


        <div class="wdasr--filter wdasr--flex horizontal <?php echo esc_attr( $key !== $this->options['primary'] ? '' : 'primary-filter' ); ?>" data-filter_key="<?php echo esc_attr( $key ); ?>">
            <input class="wdasr--margin top-10" type="radio" name="primary-filter" value="yes" <?php echo esc_attr( !$primary ? 'disabled' : '' ); ?> <?php echo esc_attr( $this->options['primary'] !== $key ? '' : 'checked' ); ?> />
            
            <select class="filter-property" name="filter-props">

                <?php foreach ($this->filter_components as $_key => $_filter) : ?>
                <option
                    <?php
                    echo esc_attr( $key == $_key ? 'selected' : '' );
                    // echo esc_attr( in_array($_key, $this->active_filters) ? 'disabled' : '' );
                    ?>
                    value="<?php echo esc_attr( $_key ); ?>"
                ><?php echo esc_html( $_filter['label'] ); ?></option>
                <?php endforeach; ?>

            </select>

            <?php
            $this->filter_field_html(
                $this->filter_components[$key]['type'],
                $key,
                $value
            );
            ?>

            <button type="button" class="wdasr--remove-filter wdasr--margin top-5">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <?php
    }



    protected function filter_field_html ( $type, $key, $value ) {
        
        ?><div class="filter-value-wrapper <?php echo $type == "multi-select" ? "wdasr--margin top-5" : "" ?>"><?php
        switch ($type) {
            case 'select':


                ?>
                <select class="filter-value <?php echo esc_attr( $type ); ?>">
                    <option
                    value='<?php echo esc_attr( wp_json_encode( array_keys( $this->preloaded_properties[ $key ] ) ) ); ?>'
                    >All <?php echo esc_html( $this->filter_components[$key]['label'] ); ?></option>
                    
                    <?php foreach ( $this->preloaded_properties[ $key ] as $_key => $_value ) : ?>
                    <option value="<?php echo esc_attr(wp_json_encode([$_key])); ?>" <?php echo esc_attr( $_key !== $value ? '' : 'selected' ); ?>><?php echo esc_html( $_value ); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php


                break;

            
            case 'multi-select':
                $total_items = count( $this->preloaded_properties[$key] );
                $selected_items = 0;
        
                $_saved_filters = json_decode( $this->saved_filters, true )[$key];
        
                if ( is_array($_saved_filters) ) {
                    $selected_items = count( $_saved_filters );
                } else if ( gettype($_saved_filters) == 'string' ) {
                    $selected_items = 1;
                }
                
                ?>
                <div class="<?php echo esc_attr( $type ); ?>">
                    
                    <div class="wdasr--single-filter wdasr--flex space-between">
                        <label for="multiselect-<?php echo esc_attr( $key ); ?>" >
                            <input
                                class="filter-multi-item"
                                type="checkbox"
                                id="multiselect-<?php echo esc_attr( $key ); ?>"
                                value="<?php echo esc_attr( $key ); ?>"
                                <?php echo esc_attr( $total_items == $selected_items ? 'checked' : '' ); ?>
                                <?php echo esc_attr( $total_items ? '' : 'disabled' ); ?>
                            >All <?php echo esc_html( $this->filter_components[$key]['label'] ); ?>
                        </label>
                        
                        <span
                            class="wdasr--filter-toggle wdasr--border radius wdasr--padding padding-around-5"
                        >
                            <span class="selected-item"><?php echo esc_html( $selected_items ); ?></span>/
                            <span class="total-items"><?php echo esc_html($total_items); ?></span> 
                            <span class="toogle-text">Show</span> 
                            <span class="dashicons dashicons-arrow-down"></span>
                        </span>
                    </div>

                    

                    <div class="wdasr--multi-options wdasr--hide vertical row-gap wdasr--margin left-10">
                        <?php foreach ( $this->preloaded_properties[ $key ] as $_key => $_value ) : ?>
                            <?php
                            $checked_text = '';

                            if ( $selected_items > 1 ) {
                                $checked_text = in_array($_key, $_saved_filters) ? 'checked': '';
                            } else if ( $selected_items == 1 ) {
                                $checked_text = $_key == $_saved_filters ? 'checked' : '';
                            }
                            ?>
                        <label for="multiselect-<?php echo esc_attr($_key); ?>" >
                            <input
                                class="filter-multi-item"
                                type="checkbox"
                                id="multiselect-<?php echo esc_attr($_key); ?>"
                                value="<?php echo esc_attr($_key); ?>"
                                <?php echo esc_attr( $checked_text ); ?>
                            > <?php echo esc_html( $_value ); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                </div>
                <?php

                break;
            
            default:
            
                ?>
                <input
                    class="<?php echo esc_attr( $type ); ?>" type="text"
                    <?php echo esc_attr( $_field['type'] !== 'disabled' ? '' : 'disabled' ); ?>
                    value="<?php echo esc_attr( $value ); ?>"
                    placeholder="<?php echo esc_attr( $_field['label'] ); ?>"
                />
                <?php
                
                break;
        }

        ?> </div> <?php
    }
}


endif;