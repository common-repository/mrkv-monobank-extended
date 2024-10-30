<?php 
# Check if class exist
if (!class_exists('MorkvaMonopayShortcodes'))
{
	/**
	 * Class for add widget shortcodes
	 */
	class MorkvaMonopayShortcodes
	{
		/**
		 * Constructor for add shortcodes
		 * */
		function __construct()
		{
			add_shortcode('mrkv_mono_checkout_black_long', array( $this, 'mrkv_add_mono_button_product_func' ));
			add_shortcode('mrkv_mono_checkout_white_long', array( $this, 'mrkv_add_mono_button_product_func_white' ));

			add_shortcode('mrkv_mono_checkout_black_short', array( $this, 'mrkv_add_mono_button_product_func' ));
			add_shortcode('mrkv_mono_checkout_white_short', array( $this, 'mrkv_add_mono_button_product_func_white' ));
		}

		/**
		 * Add product button
		 * */
		public function mrkv_add_mono_button_product_func(){
			# Add button
			require_once MORKVAMONOGATEWAY_DIR . 'templates/template-monopay-product-page.php';
		}

		/**
		 * Add product button white
		 * */
		public function mrkv_add_mono_button_product_func_white(){
			# Add button
			require_once MORKVAMONOGATEWAY_DIR . 'templates/template-monopay-product-page-white.php';
		}
	}
}