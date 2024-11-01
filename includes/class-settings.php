<?php


/*-------------------------------------------
*  Exit if accessed directly
*-------------------------------------------*/
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'WDASR_settings' ) ) :

class WDASR_settings {

    protected $wc;


    protected $default_settings = [
        'filter'    => [],
        'options'   => [
            'display' => [
                'primary_data'      => [ 'label' => 'Primary Data', 'value' => 'checked',   'free' => true ],
                'product_list'      => [ 'label' => 'Product List', 'value' => 'unchecked', 'free' => true ],
                'daily_orders'      => [ 'label' => 'Daily Report', 'value' => 'checked',   'free' => true ],
                'all_orders'        => [ 'label' => 'All Orders',   'value' => 'unchecked', 'free' => true ],
                'plot_lines'        => [ 'label' => 'Line Chart',   'value' => 'unchecked', 'free' => true ],
                'plot_bars'         => [ 'label' => 'Bar Chart',    'value' => 'unchecked', 'free' => true ],
                'plot_pie'          => [ 'label' => 'Pie Chart',    'value' => 'unchecked', 'free' => true ]
            ],
            'ranges' => [
                'from_date'    => '',
                'to_date'      => '',
            ],
            'primary'   => ''
        ]
    ];


    protected $filter_components = [
        'unkown'                => [ 'query_type' => 'primary',     'type' => 'disabled',       'primary' => false,  'label' => 'Choose Filter'     ],
        'billing_first_name'    => [ 'query_type' => 'primary',     'type' => 'text',           'primary' => false,  'label' => 'Billing Firstname' ],
        'billing_last_name'     => [ 'query_type' => 'primary',     'type' => 'text',           'primary' => false,  'label' => 'Billing Lastname'  ],
        'billing_city'          => [ 'query_type' => 'primary',     'type' => 'text',           'primary' => true,   'label' => 'Billing City'      ],
        'billing_country'       => [ 'query_type' => 'primary',     'type' => 'text',           'primary' => true,   'label' => 'Billing Country'   ],
        'currency'              => [ 'query_type' => 'primary',     'type' => 'text',           'primary' => true,   'label' => 'Currency'          ],
        'discount_total'        => [ 'query_type' => 'primary',     'type' => 'number',         'primary' => false,  'label' => 'Discount'          ],
        'payment_method'        => [ 'query_type' => 'primary',     'type' => 'multi-select',   'primary' => true,   'label' => 'Payment Method'    ],
        'shipping_first_name'   => [ 'query_type' => 'primary',     'type' => 'text',           'primary' => false,  'label' => 'Shipping Firstname'],
        'shipping_last_name'    => [ 'query_type' => 'primary',     'type' => 'text',           'primary' => false,  'label' => 'Shipping Lastname' ],
        'shipping_country'      => [ 'query_type' => 'primary',     'type' => 'text',           'primary' => true,   'label' => 'Shipping Country'  ],
        'shipping_city'         => [ 'query_type' => 'primary',     'type' => 'text',           'primary' => true,   'label' => 'Shipping City'     ],
        'shipping_methods'      => [ 'query_type' => 'primary',     'type' => 'disabled',       'primary' => true,   'label' => 'Shipping Methods'  ],
        'post_status'           => [ 'query_type' => 'primary',     'type' => 'multi-select',   'primary' => true,   'label' => 'Order Status'      ],
    ];



    public function settings ( $_date = null ) {
        $_date = $_date == null ? gmdate('Y-m-d') : $_date;
        $this->default_settings['options']['ranges'] = [
            'from_date' => $_date,
            'to_date' => $_date
        ];

        return $this->default_settings;
    } // ENDS settings()



    public function get_pro () {
        ?><strong class="wdasr--get-pro">(<a href="https://webdevadvisor.com/product/wda-sales-report-pro/">Get Pro</a>)</strong><?php
    } // ENDS settings()

    
}

endif;