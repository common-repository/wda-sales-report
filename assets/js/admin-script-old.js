(function($){

class WDA_Tools {
    log ( data ) {
        console.log( data );
    }
    parse ( json ) {
        return JSON.parse( json );
    }
    stringify ( json ) {
        return JSON.stringify( json );
    }
    getParsed ( key = '' ) {
        return this.parse( this.get( key ) );
    }
    set ( key, data ) {
        localStorage.setItem( key, data );
    }
    setString ( key, data ) {
        localStorage.setItem( key, this.stringify( data ) );
    }
    get ( key = '' ) {
        return localStorage.getItem( key );
    }
    remove ( key = '' ) {
        localStorage.removeItem( key );
    }
}


class WDA_Sales_Report extends WDA_Tools {
    constructor () {
        super();

        this.jsonData = {};

        // Preloaded Elements
        this.preloadedOptions = $('#wdasr--preloaded-options');
        this.preloadedData = $('#wdasr--preloaded-data');
        this.savedFilters = $('#wdasr--saved-filters');

        this.reportContainer = $('#wdasr--report-container');

        // Start & End Date field for range fields
        this.dateRange = $('.wdasr--range-dates');

        // display options
        this.displayOptions = $('#wdasr--display-options :is(input, select)');

        // Containers
        this.containerDailyReport = $('#wdasr--display_daily_orders');
        this.containerOrders = $('#wdasr--display_all_orders');
        this.containerProducts = $('#wdasr--display_product_list');
        this.containerPrimaryData = $('#wdasr--display_primary_data');

        this.getReportButton = $('#wdasr--generate-report');

        // Filter Section
        this.filterContainer = $('#wdasr--filters');

        // Filter Buttons
        this.newFilterButton = $('#wdasr--new-filter');


        // Loading Selector 
        this.loading = $( '#wdasr--display_loading' );

        this.events();
    } // ENDS constructor()

    events () {
        this.dateRange.on('change', this.changeDates.bind(this));
        this.getReportButton.on('click', this.getData.bind(this));
        this.displayOptions.on('change', this.displayItems.bind(this));

        // CRUD FILTERS
        this.newFilterButton.on('click', this.addFilter.bind(this));
        this.filterContainer.on( 'click', '.wdasr--remove-filter', this.removeFilter.bind(this) );
        
        // FILTERS ACTION
        this.filterContainer.on('change', '.filter-value', this.filterValue.bind(this));
        this.filterContainer.on('change', '.filter-property', this.filterProperty.bind(this));
        this.filterContainer.on('change', 'input[name="primary-filter"]', this.primaryFilter.bind(this));
    } // ENDS events()

    addFilter () {
        let optionsHTML = '';
        const filters = this.parse( this.preloadedData.val() )[ 'filters' ];

        let savedFilters = this.parse( this.savedFilters.val() );

        savedFilters.unkown = '';

        $.each(filters, function( key, filter ) {
            optionsHTML += `<option value="${ key }">${ filter.label }</option>`;
        });
        
        this.filterContainer.append(
            `<div class="wdasr--filter wdasr--flex horizontal" data-filter_key="unkown">
                <input type="radio" name="primary-filter" value="yes" disabled />
                <select class="filter-property" name="filter-props">
                    ${ optionsHTML }
                </select>

                <input class="filter-value" type="text" name="unkown" disabled placeholder="type here" />
                
                <button type="button" class="wdasr--remove-filter">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>`
        );

        this.savedFilters.val( this.stringify( savedFilters ) );
    }

    ajax ( objectParameter ) {

        const that = this;
        
        $.ajax({
            url : wdasrData.ajax_url,
            type : "POST",
            data : {
                _ajax_nonce : wdasrData.nonce,
                action : 'wdasr_ajax_request',
                key : objectParameter.key,
                json_data : this.stringify( objectParameter.json ),
            },
			beforeSend : function( xhr ) {
                that.loading.show();
                that.containerPrimaryData.html('');
                that.containerProducts.html('');
                that.containerDailyReport.html('');
                that.containerOrders.html('');
			},
            success : ( recievedData ) => {
                // this.log( recievedData );
                this.distributor( recievedData );
                this.loading.hide();
            }
        });
    } // ENDS ajax()
    
    changeDates ( e ) {
        const thisElement = $( e.target );
        let preloadedInput = this.parse( this.preloadedOptions.val() );

        preloadedInput.ranges[ thisElement.attr('name') ] = thisElement.val();
        this.preloadedOptions.val( this.stringify( preloadedInput ) );
    } // ENDS dateInputToggle()

    displayItems ( e ) {
        let json = this.parse( this.preloadedOptions.val() );
        const thisElement = $( e.target );
        const displayItem = $( '#wdasr--display_' + thisElement.attr('name') );
        let inputvalue;

        if ( thisElement.is(':checked') ) {
            inputvalue = 'checked';
            displayItem.show();
        } else {
            inputvalue = 'unchecked';
            displayItem.hide();
        }
        
        thisElement.val( inputvalue );
        json.display[ thisElement.attr( 'name' ) ].value = inputvalue;

        this.preloadedOptions.val( this.stringify( json ) );
    } // ENDS displayItems()
    
    distributor ( json ) {
        this.jsonData = json;

        if ( json.total_orders ) {
            this.renderDailyOrders( json.daily_orders );
            this.renderAllOrders( json.all_orders );
            this.renderProducts( json );
            this.renderPrimaryData( json );
            this.renderWarnings( json.warnings );
        } else {
            const message = '<h3>No Orders Found!</h3>';
            this.containerPrimaryData.html( message );
            this.containerProducts.html( message );
            this.containerDailyReport.html( message );
            this.containerOrders.html( message );
        }
        // this.log('Called: distributor');
    } // ENDS distributor()

    filterProperty ( e ) {
        const preloadedData = this.parse( this.preloadedData.val() );
        let savedFilters = this.parse( this.savedFilters.val() );

        let valueHTML;
        let thisProperty = $( e.target );
        let thisFilter = thisProperty.closest( '.wdasr--filter' );
        const filterkey = thisProperty.val();
        


        thisFilter.attr( 'data-filter_key', filterkey );
        
        savedFilters[ filterkey ] = '';
        delete savedFilters[ 'unkown' ];
        this.savedFilters.val( this.stringify( savedFilters ) );

        thisFilter.find('.filter-value').remove();
        thisFilter.find('[name="primary-filter"]').remove();
        


        if ( preloadedData.filters[ filterkey ].type == 'select' ) {
            let savedFiltersJSON = this.parse( this.savedFilters.val() );

            let allKeys = [];
            let optionsHTML = '';
            
            $.each(preloadedData[ filterkey ], function( key, label ) {
                optionsHTML += `<option value="${ key }">${ label }</option>`;

                if ( filterkey in preloadedData ) {
                    allKeys.push( key );
                }
            });


            valueHTML = `
            <select class="filter-value">
                <option value='${ this.stringify( allKeys ) }'>All ${ preloadedData.filters[ filterkey ].label }</option>
                ${ optionsHTML }
            </select>`;
            
            
            savedFiltersJSON[ filterkey ] = this.stringify( allKeys );

            this.savedFilters.val( this.stringify( savedFiltersJSON ) );
        } else {
            valueHTML = `<input class="filter-value" type="text" value="" ${ preloadedData.filters[ filterkey ].type !== 'disabled' ? 'placeholder=""' : `disabled placeholder="${preloadedData.filters[ filterkey ].label}"` } />`;
        }


        thisProperty.after( valueHTML );
        thisProperty.before( `<input type="radio" name="primary-filter" value="yes" ${ preloadedData.filters[ filterkey ].primary ? '' : 'disabled'} />` );
    } // END filterProperty
    
    filterValue ( e ) {
        const container = $( e.target ).closest('.wdasr--filter');

        let filters = this.parse( this.savedFilters.val() );

        const key = container.find('.filter-property').val();
        const value = container.find('.filter-value').val();

        filters[ key ] = value;

        this.savedFilters.val( this.stringify( filters ) );
    } // ENDS filterValue()
    

    getData () {
        this.ajax({
            key : 'get_sales_report',
            json : {
                options : this.parse( this.preloadedOptions.val() ),
                filters : this.parse( this.savedFilters.val() )
            }
        });
    } // ENDS getData()

    primaryFilter ( e ) {
        let preloadedOptions = this.parse( this.preloadedOptions.val() );
        let thisFilter = $( e.target ).closest('.wdasr--filter');

        const filterKey = thisFilter.find('.filter-property').val();

        // Add Primary Class
        this.filterContainer.find('.wdasr--filter').removeClass( 'primary-filter' );
        thisFilter.addClass( 'primary-filter' );

        // Change Primary Key In Options
        preloadedOptions.primary = filterKey;
        this.preloadedOptions.val( this.stringify( preloadedOptions ) );
    } // ENDS primaryFilter()

    removeFilter ( e ) {
        let container = $( e.target ).closest( '.wdasr--filter' );
        const key = container.data('filter_key');

        let savedFilters = this.parse( this.savedFilters.val() );
        let preloadedOptions = this.parse( this.preloadedOptions.val() );

        delete savedFilters[ key ];
        this.savedFilters.val( this.stringify( savedFilters ) );

        preloadedOptions.primary = preloadedOptions.primary !== key ? preloadedOptions.primary : '';
        this.preloadedOptions.val( this.stringify( preloadedOptions ) );

        container.remove();
    } // ENDS removeFilter()

    renderAllOrders ( orders ) {
        const preloadedData = this.parse( this.preloadedData.val() );
        const primaryObject = this.jsonData.primary_key ? preloadedData.filters[this.jsonData.primary_key] : false;

        const that = this;
        let html = "";
        let tableHeadHTML = "";
        let tableBodyHTML = "";
        let tableFooterHTML = "";


        html += '<h2 class="wdasr--top-space">All Orders</h2>';


        tableHeadHTML += `
        <th>Order Name</th>
        <th>Date</th>
        <th>Status</th>
        ${ primaryObject.primary ? '<th>' + primaryObject.label + '</th>' : '' }
        <th>Total</th>
        `;

        tableFooterHTML += `
        <th>Total ${ this.jsonData.total_orders } orders</th>
        <th>in ${ this.jsonData.total_days } days</th>
        <th>and total sold</th>
        ${ primaryObject ? '<th style="text-align: center;">----------></th>' : '' }
        <th>${ that.jsonData.currency + this.jsonData.total_amount }</th>`;


        $.each(orders, function( index, object ) {
            tableBodyHTML += `
            <tr>
                <td><strong>#${ object.order_id }</strong> ${ object.full_name }</td>
                <td>${ object.created_at }</td>
                <td class="wdasr--order-status ${ object.post_status }"><span>${ object.post_status }</span></td>
                ${ primaryObject.primary ? '<td>' + object.primary_title + '</td>' : '' }
                <td>${ that.jsonData.currency + object.amount }</td>
            </tr>
            `;
        });
        

        html += `
        <table class="wdasr--report_table" id="wdasr--orders-table">
            <thead><tr>${ tableHeadHTML }</tr></thead>
            <tbody>${ tableBodyHTML }</tbody>
            <tfoot><tr>${ tableFooterHTML }</tr></tfoot>
        </table>`;


        this.containerOrders.html( html );
    } // ENDS renderAllOrders()

    renderDailyOrders ( daily_orders ) {
        const that = this;

        let dailyOrdersHTML = "";
        

        let tableHeadHTML = "";
        let tableBodyHTML = "";
        let tableFooterHTML = "";


        // Header & Footer without prime data
        tableHeadHTML = `<th>Days</th><th>Per Day</th>`;
        tableFooterHTML += `<th>In ${ this.jsonData.total_days } Days</th><th>total sold ${ that.jsonData.currency + this.jsonData.total_amount }</th>`;


        // Header & Footer if prime data exists
        if ( that.jsonData.primary_key ) {
            if ( ! that.jsonData.prime_data.others.total ) {
                delete that.jsonData.prime_data.others;
            }
    
    
            $.each(that.jsonData.prime_data, function( primary_key, object ) {
                tableHeadHTML += `<th>(${ object.orders }) ${ object.title }</th>`;
                tableFooterHTML += `<th>${ that.jsonData.currency + object.total.toFixed(2) }</th>`;
            });
        }
        
        $.each(daily_orders, function(dateKey, primes) {
            const orderdate = dateKey.replace("_", " ").replace("@", ", ");
            let singlePrimeColumn = '';


            // Header & Footer if prime data exists
            if ( that.jsonData.primary_key ) {
                $.each(that.jsonData.prime_data, function( primary_key, object ) {
                    const amount = primes[ primary_key ];
                    singlePrimeColumn += `<td>${ that.jsonData.currency }${ amount ? amount.toFixed( 2 ) : '0.00' }</td>`;
                });
            }


            tableBodyHTML += `
            <tr>
                <td>${ orderdate }</td>
                <td>${ that.jsonData.currency + primes.perday.toFixed( 2 ) }</td>
                ${ singlePrimeColumn }
            </tr>`;
        });


        dailyOrdersHTML += `<h2 class="wdasr--top-space">Daily Orders (${ this.jsonData.total_orders } Orders in ${ this.jsonData.total_days } Days)</h2>`;


        dailyOrdersHTML += `
        <table class="wdasr--report_table">

            <thead><tr>${ tableHeadHTML }</tr></thead>
            <tbody>${ tableBodyHTML }</tbody>
            <tfoot><tr>${ tableFooterHTML }</tr></tfoot>

        </table>`;


        this.containerDailyReport.html( dailyOrdersHTML );
    } // ENDS renderDailyOrders() 

    renderProducts ( json ) {
        let tbodyHTML = "";
        let totalProducts = 0;


        this.containerProducts.html(
            `<h2 class="wdasr--top-space">Product List : ${ totalProducts } Items</h2>
            <table class="wdasr--report_table">
                <thead><th>Product Name</th><th>SKU</th><th>Qty</th></thead>
                <tbody>${ tbodyHTML }</tbody>
            </table>`
        );
    } // ENDS renderProducts()

    renderPrimaryData ( json ) {
        if ( this.jsonData.primary_key ) {
            const that = this;
            const preloadedData = this.parse( this.preloadedData.val() );
            let tbodyHTML = "";
    
            $.each(json.prime_data, function( index, object ) {
                tbodyHTML += `<tr><td>${ object.title }</td><td>${ object.orders }</td><td>${ that.jsonData.currency + object.total.toFixed( 2 ) }</td></tr>`;
            });

            this.containerPrimaryData.html(
                `<h2 class="wdasr--top-space">${ preloadedData.filters[ this.jsonData.primary_key ].label } Summery</h2>
                <table class="wdasr--report_table">
                    <thead><th>Prime Title</th><th>Orders</th><th>Amount</th></thead>
                    <tbody>${ tbodyHTML }</tbody>
                    <tfoot><th>Total</th><th>${ this.jsonData.total_orders }</th><th>${ that.jsonData.currency + this.jsonData.total_amount }</th></tfoot>
                </table>`
            );
        } else {
            this.containerPrimaryData.html('');
        }
    } // ENDS renderPrimaryData()

    renderWarnings ( warnings ) {} // ENDS renderWarnings()
}

new WDA_Sales_Report();

})(jQuery)