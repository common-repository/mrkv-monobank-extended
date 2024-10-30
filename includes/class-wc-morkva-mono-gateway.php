<?php
# Include namespaces
use MorkvaMonoGateway\Morkva_Mono_Order;
use MorkvaMonoGateway\Morkva_Mono_Payment;

/**
 * Class WC_Gateway_Morkva_Mono file
 * */
class WC_Gateway_Morkva_Mono extends WC_Payment_Gateway
{
    /**
     * @var string Token connect with monopay
     * */
    private $mrkv_mono_token;

    /**
     * Constructor for the gateway
     * */
    public function __construct()
    {
        # Load all classes monopay connection
        mrkv_mono_loadMonoLibrary();

        # Get settings        
        $this->id = 'morkva-monopay';
        $this->icon = apply_filters('woocommerce_mono_icon', '');
        $this->has_fields = true;
        $this->method_title = _x('Morkva Plata by Mono Extended', 'morkva-monobank-extended');
        $this->method_description = __('Accept credit card payments on your website via Morkva Monobank payment gateway.', 'morkva-monobank-extended');
        $this->supports[] = 'refunds';

        # Load the settings
        $this->init_form_fields();
        $this->init_settings();

        # Get settings
        $this->title = $this->get_option('title');
        $this->description  = $this->get_option( 'description' );
        $this->mrkv_mono_token = $this->get_option('API_KEY');

        # Include functions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_morkva-monopay', array($this, 'mrkv_mono_callback_success'));
        
        # Callback function
        add_action('woocommerce_thankyou_'.$this->id, array( $this, 'return_handler' ) );

        # Add payment image
        add_filter( 'woocommerce_gateway_icon', array( $this, 'morkva_monopay_gateway_icon' ), 100, 2 );

        # Check if payment settings
        if(isset($_GET['page']) && $_GET['page'] == 'wc-settings' && isset($_GET['section']) && $_GET['section'] == 'morkva-monopay'){
            # Include styles
            add_action('admin_head', array($this, 'mrkv_mono_style_settings'));
            # Include scripts
            add_action('admin_enqueue_scripts', array($this, 'mrkv_mono_scripts_settings'));
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     * 
     */
    public function init_form_fields() 
    {
        $all_order_statuses = wc_get_order_statuses();
        $correct_order_statuses = array();

        foreach($all_order_statuses as $k => $v)
        {
            $k = str_replace('wc-', '', $k);
            $correct_order_statuses[$k] = $v;
        }
        $shipping_methods = WC()->shipping()->get_shipping_methods();
        $mono_shipping_methods['none'] = __( 'None', 'morkva-monobank-extended-pro' );

        foreach($shipping_methods as $key => $method)
        {
            $mono_shipping_methods[$key] = $method->get_method_title();
        }

        # Create fields gateway
        $this->form_fields = array(
            'mono_acquiring_title' => array(
                'title' => __( 'Mono Acquiring', 'morkva-monobank-extended' ),
                'type' => 'title',
            ),
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'morkva-monobank-extended' ), 
                'type' => 'checkbox',
                'label' => '<span>' . __( 'Enable Morkva Mono Payment', 'morkva-monobank-extended' )  . '</span>',
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __( 'Title', 'morkva-monobank-extended' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'morkva-monobank-extended' ),
                'default' => '',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __( 'Description', 'morkva-monobank-extended' ),
                'type' => 'textarea',
                'desc_tip' => true,
                'description' => __( 'This controls the description which the user sees during checkout.', 'morkva-monobank-extended' ),
            ),
            'API_KEY' => array(
                'title' => __( 'Api token', 'morkva-monobank-extended' ),
                'type' => 'text',
                'description' => __( 'You can find out your X-Token by the link: <a href="https://web.monobank.ua/" target="blank">web.monobank.ua</a>', 'morkva-monobank-extended' ) . __( '<br>After receiving the API token and activating your merchant, write to Monobank\'s support chat<br> to activate a redirect to the site\'s thank you page (the plugin transmits the page URL via API).<br> Tell support that you are using the Morkva plugin.', 'morkva-monobank-extended' ),
                'default' => '',
            ),
            'monopay_image_type_black' => array(
                'title' => __( 'Image style', 'morkva-monobank-extended' ),
                'type' => 'checkbox',
                'label' => '<span></span><p style="padding: 20px;"><img src="' . MORKVAMONOGATEWAY_PATH . 'assets/images/plata_light_bg.png"></p>',
                'default' => 'yes'
            ),
            'monopay_image_type_white' => array(
                'title' => '',
                'type' => 'checkbox',
                'label' => '<span></span><p style="background: #676767; padding: 20px; border-radius: 10px;"><img src="' . MORKVAMONOGATEWAY_PATH . 'assets/images/plata_dark_bg.png" ></p>',
                'default' => 'no'
            ),
            'monopay_image_width' => array(
                'title' => __( 'Image width(px)', 'morkva-monobank-extended' ),
                'type' => 'number',
                'label' => '',
                'default' => ''
            ),
            'hide_image' => array(
                'title' => __( 'Hide logo', 'morkva-monobank-extended' ),
                'type' => 'checkbox',
                'label' => '<span>' . __( 'If checked, Monopay logo or custom logo will not be displayed by the payment method title', 'morkva-monobank-extended' ) . '</span>',
                'default' => 'no' 
            ),
            'url_monobank_img' => array(
                'title'       => __( 'Custom logo url', 'morkva-monobank-extended' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __( 'Enter full url to image', 'morkva-monobank-extended' ),
                'default'     => '',
            ),
            'enabled_test_mode' => array(
                'title' => __( 'Test mode', 'morkva-monobank-extended' ),
                'type' => 'checkbox',
                'label' => '<span>' . __( 'Enable Test mode', 'morkva-monobank-extended' ) . '</span>',
                'default' => 'no'
            ),
            'enabled_test_mode_admin' => array(
                'title' => __( 'Test mode Admin', 'morkva-monobank-extended' ),
                'type' => 'checkbox',
                'label' => '<span>' . __( 'Enable Test Admin mode', 'morkva-monobank-extended' ) . '</span>',
                'default' => 'no'
            ),
            'TEST_API_KEY' => array(
                'title' => __( 'Test Api token', 'morkva-monobank-extended' ),
                'type' => 'text',
                'description' => __( 'You can find out your Test Token by the link: <a href="https://api.monobank.ua/" target="blank">api.monobank.ua</a>', 'morkva-monobank-extended' ),
                'default' => '',
            ),
            'monopay_order_status' => array(
                'title' => __( 'Status of completed payment', 'morkva-monobank-extended' ),
                'type' => 'select',
                'description' => __( 'Select the status to which the order status will change after successful payment', 'morkva-monobank-extended' ),
                'label' => __( 'Courier', 'morkva-monobank-extended' ),
                'options' => $correct_order_statuses,
                'default' => array('processing'),
            ),
            'mono_checkout_title' => array(
                'title' => __( 'Mono Checkout', 'morkva-monobank-extended' ),
                'type' => 'title',
            ),
            'enabled_checkout' => array(
                'title' => __( 'Enable/Disable', 'morkva-monobank-extended' ),
                'type' => 'checkbox',
                'label' => '<span>' . __( 'Enable Morkva Mono Checkout', 'morkva-monobank-extended' )  . '</span>',
                'default' => 'no'
            ),
            'mono_delivery_methods' => array(
                'title' => __( 'Delivery methods Monobank', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'label' => '<span>' . __( 'Compare with the delivery method on the website', 'morkva-monobank-extended' ) . '</span>',
                'type' => 'checkbox',
            ),
            'mono_delivery_methods_pickup' => array(
                'title' => __( 'Pickup', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type' => 'select',
                'label' => __( 'Pickup', 'morkva-monobank-extended' ),
                'options' => array()
            ),
            'mono_delivery_methods_courier' => array(
                'title' => __( 'Courier', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type' => 'select',
                'label' => __( 'Courier', 'morkva-monobank-extended' ),
                'options' => array()
            ),
            'mono_delivery_methods_np_brnm' => array(
                'title' => __( 'Nova Poshta', 'morkva-monobank-extended' ),
                'type' => 'select',
                'label' => __( 'Nova Poshta', 'morkva-monobank-extended' ),
                'options' => $mono_shipping_methods
            ),
            'mono_delivery_methods_np_box' => array(
                'title' => __( 'Nova Poshta Poshtamat', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type' => 'select',
                'label' => __( 'Nova Poshta Poshtamat', 'morkva-monobank-extended' ),
                'options' => array()
            ),
            'mono_payment_methods' => array(
                'title' => __( 'Payment methods', 'morkva-monobank-extended' ),
                'type' => 'multiselect',
                'label' => __( 'Payment methods', 'morkva-monobank-extended' ),
                'description' => __( 'Press Ctrl to select multiple options', 'morkva-monobank-extended' ) . '. <b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Delivery payment and Payparts Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'default' => array('card'),
                'options' => array(
                    'card' => __( 'Card', 'morkva-monobank-extended' )
                )
            ),
            'mono_payment_partparts_number' => array(
                'title' => __( 'Payparts numbers', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type' => 'number',
                'description' => __( 'Number of payments in installments', 'morkva-monobank-extended' ),
                'default' => '',
            ),
            'checkout_button_type_black' => array(
                'title' => __( 'Button style', 'morkva-monobank-extended' ),
                'type' => 'checkbox',
                'label' => '<span></span><p><img src="' . MORKVAMONOGATEWAY_PATH . 'assets/images/monocheckout_button_black_normal.svg">
                            <img src="' . MORKVAMONOGATEWAY_PATH . 'assets/images/monocheckout_button_black_short.svg" ></p>',
                'default' => 'yes'
            ),
            'checkout_button_type_white' => array(
                'title' => '',
                'type' => 'checkbox',
                'label' => '<span></span><p><img src="' . MORKVAMONOGATEWAY_PATH . 'assets/images/monocheckout_button_white_normal.svg" >
                            <img src="' . MORKVAMONOGATEWAY_PATH . 'assets/images/monocheckout_button_white_short.svg" ></p>',
                'default' => 'no'
            ),
            'mono_checkout_button_width' => array(
                'title' => __( 'Button width(px)', 'morkva-monobank-extended' ),
                'type' => 'number',
                'label' => '',
                'default' => ''
            ),
            'mono_checkout_button_height' => array(
                'title' => __( 'Button height(px)', 'morkva-monobank-extended' ),
                'type' => 'number',
                'label' => '',
                'default' => ''
            ),
            'display_mode_product' => array(
                'title' => __( 'Display mode', 'morkva-monobank-extended' ),
                'type' => 'checkbox',
                'label' => '<span>' . __( 'Product page', 'morkva-monobank-extended' )  . '</span>',
                'default' => 'yes'
            ),
            'monocheckout_debug' => array(
                'title' => __( 'Debug mode', 'morkva-monobank-extended' ),
                'type' => 'checkbox',
                'label' => '<span>' . __( 'Enable debug log', 'morkva-monobank-extended' )  . '</span>',
                'default' => 'no'
            ),
            'mono_checkout_shortcodes' => array(
                'title' => __('Shortcodes', 'morkva-monobank-extended'),
                'type' => 'checkbox',
                'label' => '[mrkv_mono_checkout_black_long], [mrkv_mono_checkout_black_short]<br>[mrkv_mono_checkout_white_long], [mrkv_mono_checkout_white_short]',
                'default' => 'yes'
            ),
            'mono_checkout_logo_morkva' => array(
                'title' => '<div class="plugin-development mt-40">
                    <span>Веб студія</span>
                    <a href="https://morkva.co.ua/" target="_blank"><img src="' . MORKVAMONOGATEWAY_PATH . 'assets/images/morkva-logo.svg" alt="Morkva" title="Morkva"></a>
                </div>
                <a target="blanc" href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" class="mono-pro-version-btn">' . __( 'Pro-version', 'morkva-monobank-extended' ) . '</a>',
                'type' => 'title',
            ),
        );
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id Order ID
     * @return array Result query
     */
    public function process_payment( $order_id ) 
    {
        # Get user token
        $mrkv_mono_token = $this->mrkv_mono_getToken();

        # Include global woocommerce data
        global $woocommerce;

        # Get order data
        $mrkv_mono_order = new WC_Order( $order_id );

        # Get cart products
        $mrkv_mono_cart_info = $woocommerce->cart->get_cart();
        $mrkv_mono_basket_info = [];
        $products_sum = 0;

        # Loop all Cart data
        foreach ($mrkv_mono_cart_info as $mrkv_mono_product) 
        {
            # Get and set product image
            $mrkv_mono_image_elem = $mrkv_mono_product['data']->get_image();
            $mrkv_mono_image = [];
            preg_match_all('/src="(.+)" class/', $mrkv_mono_image_elem, $mrkv_mono_image);

            # Set product data
            $mrkv_mono_basket_info[] = [
                "name" => $mrkv_mono_product['data']->get_name(),
                "qty"  => intval($mrkv_mono_product['quantity']),
                "sum"  => round($mrkv_mono_product['line_total']*100) / intval($mrkv_mono_product['quantity']),
                "icon" => $mrkv_mono_image[1][0],
                "code" => "" . $mrkv_mono_product['product_id']
            ];

            $products_sum += round($mrkv_mono_product['line_total']*100);
        }

        $order_total_mono = round($mrkv_mono_order->get_total()*100);

        if($products_sum != $order_total_mono)
        {
            $discount_val = $products_sum - $order_total_mono;
            $counter = 0;

            foreach ($mrkv_mono_cart_info as $mrkv_mono_product) 
            {
                if($discount_val == 0)
                {
                    break;
                }

                $sum = round($mrkv_mono_product['line_total']*100);
                $qnt = intval($mrkv_mono_product['quantity']);

                if($discount_val < $sum)
                {
                    $new_sum = $sum - $discount_val;
                    $mrkv_mono_basket_info[$counter]['sum'] = $new_sum / $qnt;

                    $discount_val = 0;
                }
                else
                {
                    $discount_minus = $sum - $qnt;

                    $mrkv_mono_basket_info[$counter]['sum'] = 1;

                    $discount_val = $discount_val - $discount_minus;
                }

                ++$counter;
            }
        }

        # Set order data to send query
        $mrkvmonoOrder = new Morkva_Mono_Order();

        # Set data
        $mrkvmonoOrder->mrkv_mono_setCurrency($mrkv_mono_order->get_currency());
        $mrkvmonoOrder->mrkv_mono_setId($mrkv_mono_order->get_id());
        $mrkvmonoOrder->mrkv_mono_setReference($mrkv_mono_order->get_id());
        $mrkvmonoOrder->mrkv_mono_setAmount(round($mrkv_mono_order->get_total()*100));
        $mrkvmonoOrder->mrkv_mono_setBasketOrder($mrkv_mono_basket_info);

        # Check 
        $web_url = get_site_url();
        if($web_url){
            $mrkvmonoOrder->mrkv_mono_setRedirectUrl($this->get_return_url($mrkv_mono_order));
            $mrkvmonoOrder->mrkv_mono_setWebHookUrl($web_url . '/?wc-api=morkva-monopay');
        }

        # Create Payment object 
        $mrkv_mono_payment = new Morkva_Mono_Payment($mrkv_mono_token);
        $mrkv_mono_payment->mrkv_mono_setOrder($mrkvmonoOrder);

        # Check error
        try 
        {
            # Create invoice
            $mrkv_mono_invoice = $mrkv_mono_payment->mrkv_mono_create();
            # Check result
            if ( !empty($mrkv_mono_invoice) ) 
            {
                # Check status
                if ($mrkv_mono_order->get_status() != 'pending') 
                {
                    # Update status
                    $mrkv_mono_order->update_status('pending');
                }
            } 
            else 
            {
                # Show error
                throw new \Exception("Bad request");
            }
        } 
        catch (\Exception $e) 
        {
            # Show error notice
            wc_add_notice(  'Request error ('. $e->getMessage() . ')', 'error' );
            # Stop job
            return false;
        }

        # Return result
        return [
            'result'   => 'success',
            'redirect' => $mrkv_mono_invoice->pageUrl,
        ];
    }

    /**
     * Add custom gateway icon
     * 
     * @var string Icon
     * @var string Payment id
     * */
    function morkva_monopay_gateway_icon( $icon, $id ) {
        if ( $id === 'morkva-monopay' ) {
            if($this->get_option( 'hide_image' ) == 'no'){
                if($this->get_option( 'url_monobank_img' )){
                    return '<img src="' . $this->get_option( 'url_monobank_img' ) . '" > '; 
                }
                else{

                    $width_btn = '';

                    if($this->get_option( 'monopay_image_width' )  != 'no' && $this->get_option( 'monopay_image_width' )  != '')
                    {
                        $width_btn = 'style="width: 100%; max-width: ' . $this->get_option( 'monopay_image_width' ) . 'px; padding-top: 0.6%;"';
                    }
                    else
                    {
                        $width_btn = 'style="width: 100%; max-width: 100px; padding-top: 0"';
                    }

                    if($this->get_option( 'monopay_image_type_white' ) != 'no')
                    {
                        echo '<img class="mrkv_plata_checkout" ' . $width_btn . ' src="' . plugins_url( '../assets/images/plata_dark_bg.png', __FILE__ ) . '" > ';    
                        return '<img class="mrkv_plata_checkout" ' . $width_btn . ' src="' . plugins_url( '../assets/images/plata_dark_bg.png', __FILE__ ) . '" > ';    
                    }
                    else{
                        echo '<img class="mrkv_plata_checkout" ' . $width_btn . ' src="' . plugins_url( '../assets/images/plata_light_bg.png', __FILE__ ) . '" > '; 
                        return '<img class="mrkv_plata_checkout" ' . $width_btn . ' src="' . plugins_url( '../assets/images/plata_light_bg.png', __FILE__ ) . '" > '; 
                    }
                }
            }
        } else {
            return $icon;
        }
    }

    /**
     * Get gateway icon url
     * */
    public function get_icon_url()
    {
        if($this->get_option( 'hide_image' ) == 'no'){
            if($this->get_option( 'url_monobank_img' )){
                return $this->get_option( 'url_monobank_img' ); 
            }
            else{
                if($this->get_option( 'monopay_image_type_white' ) != 'no')
                {
                    return plugins_url( '../assets/images/plata_dark_bg.png', __FILE__ );    
                }
                else{
                    return plugins_url( '../assets/images/plata_light_bg.png', __FILE__ ); 
                }
            }
        }

        return '';
    }

    /**
     * Add Callback function. Handle
     * */
    public function return_handler() 
    {
        # Main callback
        $this->mrkv_mono_callback_success();
    }

    /**
     * Callback success function
     * */
    public function mrkv_mono_callback_success() 
    {   
        # Get content
        $mrkv_mono_callback_json = @file_get_contents('php://input');

        # Get callback data
        $mrkv_mono_callback = json_decode($mrkv_mono_callback_json, true);

        # Check callback data
        if($mrkv_mono_callback){
            # Get response
            $mrkv_mono_response = new \MorkvaMonoGateway\Morkva_Mono_Response($mrkv_mono_callback);

            if(isset($mrkv_mono_callback['reference']))
            {
                $mrkv_mono_order_id = (int)$mrkv_mono_response->mrkv_mono_getOrderId();
                $mrkv_mono_order = new WC_Order( $mrkv_mono_order_id );

                $mrkv_mono_order->update_meta_data( 'mrkv_mopay_payment_method', 'morkva-monopay');
                update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_payment_method', 'morkva-monopay' );

                if(isset($mrkv_mono_callback['status']))
                {
                    $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_status',  $mrkv_mono_callback['status']);
                    update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_status',  $mrkv_mono_callback['status'] );
                }
                if(isset($mrkv_mono_callback['reference']))
                {
                    $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_reference',  $mrkv_mono_callback['reference']);
                    update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_reference',  $mrkv_mono_callback['reference'] );
                }
                if(isset($mrkv_mono_callback['invoiceId']))
                {
                    $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_invoice_id',  $mrkv_mono_callback['invoiceId']);
                    update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_invoice_id',  $mrkv_mono_callback['invoiceId'] );
                }
                if(isset($mrkv_mono_callback['failureReason']))
                {
                    $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_failure_reason',  $mrkv_mono_callback['failureReason']);
                    update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_failure_reason',  $mrkv_mono_callback['failureReason'] );
                }
                if(isset($mrkv_mono_callback['paymentInfo']))
                {
                    if(isset($mrkv_mono_callback['paymentInfo']['maskedPan']))
                    {
                        $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_masked_pan',  $mrkv_mono_callback['paymentInfo']['maskedPan']);
                        update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_masked_pan',  $mrkv_mono_callback['paymentInfo']['maskedPan'] );
                    }
                    if(isset($mrkv_mono_callback['paymentInfo']['approvalCode']))
                    {
                        $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_approval_code',  $mrkv_mono_callback['paymentInfo']['approvalCode']);
                        update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_approval_code',  $mrkv_mono_callback['paymentInfo']['approvalCode'] );
                    }
                    if(isset($mrkv_mono_callback['paymentInfo']['rrn']))
                    {
                        $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_rrn',  $mrkv_mono_callback['paymentInfo']['rrn']);
                        update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_rrn',  $mrkv_mono_callback['paymentInfo']['rrn'] );
                    }
                    if(isset($mrkv_mono_callback['paymentInfo']['tranId']))
                    {
                        $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_tran_id',  $mrkv_mono_callback['paymentInfo']['tranId']);
                        update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_tran_id',  $mrkv_mono_callback['paymentInfo']['tranId'] );
                    }
                    if(isset($mrkv_mono_callback['paymentInfo']['terminal']))
                    {
                        $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_terminal',  $mrkv_mono_callback['paymentInfo']['terminal']);
                        update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_terminal',  $mrkv_mono_callback['paymentInfo']['terminal'] );
                    }
                    if(isset($mrkv_mono_callback['paymentInfo']['paymentSystem']))
                    {
                        $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_payment_system',  $mrkv_mono_callback['paymentInfo']['paymentSystem']);
                        update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_payment_system',  $mrkv_mono_callback['paymentInfo']['paymentSystem'] );
                    }
                    if(isset($mrkv_mono_callback['paymentInfo']['paymentMethod']))
                    {
                        $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_payment_method',  $mrkv_mono_callback['paymentInfo']['paymentMethod']);
                        update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_payment_method',  $mrkv_mono_callback['paymentInfo']['paymentMethod'] );
                    }
                    if(isset($mrkv_mono_callback['paymentInfo']['fee']))
                    {
                        $mrkv_mono_order->update_meta_data( 'mrkv_mopay_accuiring_fee',  $mrkv_mono_callback['paymentInfo']['fee']);
                        update_post_meta( $mrkv_mono_order_id, 'mrkv_mopay_accuiring_fee',  $mrkv_mono_callback['paymentInfo']['fee'] );
                    }
                }

                $mrkv_mono_order->save();
            }

            # Check status
            if($mrkv_mono_response->mrkv_mono_isComplete()) {
                global $woocommerce;

                $mrkv_mono_order_id = (int)$mrkv_mono_response->mrkv_mono_getOrderId();
                $mrkv_mono_order = new WC_Order( $mrkv_mono_order_id );

                $woocommerce->cart->empty_cart();

                $mrkv_mono_order->payment_complete($mrkv_mono_response->mrkv_mono_getInvoiceId());

                $new_order_status = ($this->get_option( 'monopay_order_status' ) && $this->get_option( 'monopay_order_status' ) != '') ? $this->get_option( 'monopay_order_status' ) : 'processing';

                # Update order status
                $mrkv_mono_order->update_status($new_order_status);
            }
        }
    }

    /**
     * Function status
     * @return string Status
     * */
    public function mrkv_mono_get_order_status_success() 
    {
        # Get status
        $new_order_status = ($this->get_option( 'monopay_order_status' ) && $this->get_option( 'monopay_order_status' ) != '') ? $this->get_option( 'monopay_order_status' ) : 'processing';
        # Return data
        return $new_order_status;

    }

    /**
     * Function can refund
     * @param object Order data
     * @return mixed Data
     * */
    public function mrkv_mono_can_refund_order( $order ) 
    {
        # Get api key
        $mrkv_mono_has_api_creds = $this->mrkv_mono_getToken();
        # Return data
        return $order && $order->get_transaction_id() && $mrkv_mono_has_api_creds;

    }

    /**
     * Function process refund
     * @var integer Order id
     * @var integer Order total
     * @var string Reason
     * @return Result 
     * */
    public function process_refund( $order_id, $amount = null, $reason = '' ) 
    {

        $mrkv_mono_order = wc_get_order( $order_id );
        $mrkv_mono_transaction_id = $mrkv_mono_order->get_transaction_id();

        if ( ! $this->mrkv_mono_can_refund_order( $mrkv_mono_order ) ) {
            return new WP_Error( 'error', __( 'Refund failed.', 'morkva-monobank-extended' ) );
        }

        $mrkv_mono_token = $this->mrkv_mono_getToken();
        $mrkv_mono_payment = new Morkva_Mono_Payment($mrkv_mono_token);
        $mrkv_mono_refund_order = array(
            "invoiceId" => $mrkv_mono_transaction_id,
            "amount" => $amount*100
        );
        $mrkv_mono_payment->mrkv_mono_setRefundOrder($mrkv_mono_refund_order);
        try {
            $mrkv_mono_result = $mrkv_mono_payment->mrkv_mono_cancel();
            if ( is_wp_error( $mrkv_mono_result ) ) {
                //$this->log( 'Refund Failed: ' . $result->get_error_message(), 'error' );
                return new WP_Error( 'error', $mrkv_mono_result->get_error_message() );
            }
            if ($mrkv_mono_result->stage == "c") {
                $mrkv_mono_order->add_order_note(
                    sprintf( __( 'Refunded %1$s - Refund ID: %2$s', 'morkva-monobank-extended' ), $amount, $mrkv_mono_result->cancelRef )
                );
                return true;
            }
        } catch (\Exception $e) {
            wc_add_notice('Request error (' . $e->getMessage() . ')', 'error');
            return false;
        }
        return false;
    }

    /**
     * Return settigs mono token
     * @return string Token
     * */
    protected function mrkv_mono_getToken() 
    {
        # Check test mode
        if($this->get_option( 'enabled_test_mode' ) == 'yes' && $this->get_option( 'enabled_test_mode_admin' ) != 'yes')
        {
            # Return monopay token
            return $this->get_option( 'TEST_API_KEY' );
        }
        elseif($this->get_option( 'enabled_test_mode_admin' ) == 'yes' && ( current_user_can('editor') || current_user_can('administrator') ))
        {
            # Return monopay test token
            return $this->get_option( 'TEST_API_KEY' );
        }
        else
        {   
            # Return monopay test token
            return $this->mrkv_mono_token;
        }
    }

    /**
     * Return button width
     * @return string Width
     * */
    public function get_mrkv_checkout_button_width()
    {
        if($this->get_option( 'mono_checkout_button_width' ))
        {
            return ' max-width: ' . $this->get_option( 'mono_checkout_button_width' ) . 'px; ';
        }
        else
        {
            return '';
        }
    }

    /**
     * Return button height
     * @return string height
     * */
    public function get_mrkv_checkout_button_height()
    {
        if($this->get_option( 'mono_checkout_button_height' ))
        {
            return ' height: ' . $this->get_option( 'mono_checkout_button_height' ) . 'px; ';
        }
        else
        {
            return '';
        }
    }

    /**
     * Add styles to settings
     * */
    public function mrkv_mono_style_settings(){
        # Add styles
        wp_enqueue_style( 'monopay-setting-style', MORKVAMONOGATEWAY_PATH . 'assets/css/monopay-setting-style.css' );
    }

    /**
     * Add scripts to settings
     * */
    public function mrkv_mono_scripts_settings(){
        wp_enqueue_script('monopay-setting-script', MORKVAMONOGATEWAY_PATH . 'assets/js/monopay-setting-script.js');
    }

    /**
     * Return settigs mono token
     * @return string Token
     * */
    public function get_mrkv_mono_getToken() 
    {
        # Check test mode
        if($this->get_option( 'enabled_test_mode' ) == 'yes' && $this->get_option( 'enabled_test_mode_admin' ) != 'yes')
        {
            # Return monopay token
            return $this->get_option( 'TEST_API_KEY' );
        }
        elseif($this->get_option( 'enabled_test_mode_admin' ) == 'yes' && ( current_user_can('editor') || current_user_can('administrator') ))
        {
            # Return monopay test token
            return $this->get_option( 'TEST_API_KEY' );
        }
        else
        {   
            # Return monopay test token
            return $this->mrkv_mono_token;
        }
    }
}
