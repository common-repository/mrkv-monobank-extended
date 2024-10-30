<?php
/**
 * Class for add monobank to wordpress menu
 * 
 * */
Class MorkvaMonopayMenu
{
    /**
     * Slug for page in Woo Tab Sections
     * 
     * */
    public $slug = 'admin.php?page=wc-settings&tab=checkout&section=morkva-monopay';

    /**
     * Slug Mono Payparts for page in Woo Tab Sections
     * 
     * */
    public $slug_payparts = 'admin.php?page=wc-settings&tab=checkout&section=morkva-monopay-payparts';

    /**
     * Constructor for create menu
     * 
     * */
    public function __construct()
    {
        # Add menu
        add_action('admin_menu', array($this, 'mrkv_mono_register_admin_menu'));
    }

    /**
     * Register menu page
     * 
     * */
    public function mrkv_mono_register_admin_menu()
    {
        # Add menu Monopay
        add_menu_page('Morkva plata', 'Morkva plata', 'manage_options', $this->slug, false, plugin_dir_url(__DIR__) . 'assets/images/morkva-monopay-logo.svg', 26);

        # Add menu Monopay Payparts
        add_submenu_page($this->slug, __('Acquiring, Mono Checkout', 'morkva-monobank-extended'), __('Acquiring, Mono Checkout', 'morkva-monobank-extended'), 'manage_options', $this->slug); 

        # Add menu Monopay Payparts
        add_submenu_page($this->slug, __('Payparts', 'morkva-monobank-extended'), __('Payparts', 'morkva-monobank-extended'), 'manage_options', $this->slug_payparts);
    }
}