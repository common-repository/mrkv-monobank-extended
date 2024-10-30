<?php
/**
 * Class WC_Gateway_Morkva_Mono_Payparts file
 * */
class WC_Gateway_Morkva_Mono_Payparts extends WC_Payment_Gateway
{
    /**
     * @var string Store id connect with monopay
     * */
    private $mrkv_mono_store_id;

    /**
     * @var string Secret key connect with monopay
     * */
    private $mrkv_mono_secret_key;

    /**
     * Constructor for the gateway
     * */
    public function __construct()
    {
        # Load all classes monopay connection
        mrkv_mono_loadMonoLibrary();

        # Get settings        
        $this->id = 'morkva-monopay-payparts';
        $this->icon = apply_filters('woocommerce_mono_icon', '');
        $this->has_fields = true;
        $this->method_title = _x('Morkva Monobank Extended Pro Payparts', 'morkva-monobank-extended');
        $this->method_description = __('This method is used to receive a form of payment in installments by monobank', 'morkva-monobank-extended');
        $this->supports[] = '';
        $this->enabled = 'no';

        # Load the settings
        $this->init_form_fields();
        $this->init_settings();

        # Get settings
        $this->title = $this->get_option('title');

        # Include functions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        # Add payment image
        add_filter( 'woocommerce_gateway_icon', array( $this, 'morkva_monopay_gateway_icon' ), 10, 2 );

        $this->enabled = 'no';

        # Check if payment settings
        if(isset($_GET['page']) && $_GET['page'] == 'wc-settings' && isset($_GET['section']) && ($_GET['section'] == 'morkva-monopay' || $_GET['section'] == 'morkva-monopay-payparts')){
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
        # Create fields gateway
        $this->form_fields = array(
            'mono_payparts_title' => array(
                'title' => __( 'Mono PayParts', 'morkva-monobank-extended' ),
                'type' => 'title', 
            ),
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type' => 'checkbox',
                'label' => '<span>' . __( 'Enable Morkva Mono PayParts Payment', 'morkva-monobank-extended' )  . '</span>',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __( 'Title', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'morkva-monobank-extended' ),
                'default' => '',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __( 'Description', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type' => 'textarea',
                'desc_tip' => true,
                'description' => __( 'This controls the description which the user sees during checkout.', 'morkva-monobank-extended' ),
            ),
            'store_id' => array(
                'title' => __( 'Store ID', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type' => 'text',
                'description' => __( 'You can find out your store-id by the link: <a href="https://web.monobank.ua/" target="blank">web.monobank.ua</a>', 'morkva-monobank-extended' ),
                'default' => '',
            ),
            'store_secret_key' => array(
                'title' => __( 'Secret Key', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type' => 'text',
                'description' => __( 'You can find out your secret key by the link: <a href="https://web.monobank.ua/" target="blank">web.monobank.ua</a>', 'morkva-monobank-extended' ),
                'default' => '',
            ),
            'hide_image' => array(
                'title' => __( 'Hide logo', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type' => 'checkbox',
                'label' => '<span>' . __( 'If checked, Monopay logo or custom logo will not be displayed by the payment method title', 'morkva-monobank-extended' ) . '</span>',
                'default' => 'no'
            ),
            'url_monobank_img' => array(
                'title'       => __( 'Custom logo url', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __( 'Enter full url to image', 'morkva-monobank-extended' ),
                'default'     => '',
            ),
            'enabled_debug_mode' => array(
                'title' => __( 'Debug mode', 'morkva-monobank-extended' ) . '<b><a href="https://morkva.co.ua/shop/monobank-extended-pro-lifetime/" target="blank">' . __( 'Only in Pro version', 'morkva-monobank-extended' ) . '</a></b>',
                'type' => 'checkbox',
                'label' => '<span>' . __( 'Enable Debug mode', 'morkva-monobank-extended' ) . '</span>',
                'default' => 'no'
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
    }

    /**
     * Add custom gateway icon
     * 
     * @var string Icon
     * @var string Payment id
     * */
    function morkva_monopay_gateway_icon( $icon, $id ) {
        return $icon;
    }

    /**
     * Callback success function
     * */
    public function mrkv_mono_callback_success() 
    {   
    }

    /**
     * Return settigs mono Store id
     * @return string Store id
     * */
    protected function mrkv_mono_get_store_id() 
    {
        # Return monopay test token
        return $this->mrkv_mono_store_id;
    }

    /**
     * Return settigs mono secret key
     * @return string Secret key
     * */
    public function get_mrkv_mono_get_secret_key() 
    {
        # Return monopay test token
        return $this->mrkv_mono_secret_key;
    }

    /**
     * Add styles to settings
     * */
    public function mrkv_mono_style_settings(){
        # Add styles
        wp_enqueue_style( 'monopay-setting-style', MORKVAMONOGATEWAY_PATH . 'assets/css/monopay-setting-style.css' );
    }

    public function mrkv_mono_scripts_settings(){
        wp_enqueue_script('monopay-setting-script', MORKVAMONOGATEWAY_PATH . 'assets/js/monopay-setting-script.js');
    }
}
