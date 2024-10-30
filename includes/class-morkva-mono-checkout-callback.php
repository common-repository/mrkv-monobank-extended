<?php
# Check if class exist
if (!class_exists('MrkvMonoCheckoutCallback'))
{
	/**
	 * Class for getting callback mono checkout
	 */
	class MrkvMonoCheckoutCallback
	{
		/**
		 * Constructor for save data by mono checkout
		 * */
		function __construct()
		{
			# Create callback
			add_action('woocommerce_api_morkva-monopay-checkout', array($this, 'mrkv_mono_checkout_callback_success'));
		}

		/**
		 * Add data order by mono checkout
		 * */
		public function mrkv_mono_checkout_callback_success(){
			# Get content
	        $mrkv_mono_callback_json = @file_get_contents('php://input');

	        # Get callback data
	        $mrkv_mono_callback = json_decode($mrkv_mono_callback_json, true);

	        # Change Timezone
			date_default_timezone_set("Europe/Kiev");
			# Get current datetime
			$date_now = date("Y-m-d h:i:sa");

			# Text Start script
			$script_start_text = "" . $date_now . "\r\n";
			$script_start_text = "Get data: " . print_r($mrkv_mono_callback, 1) . "\r\n";
			# Write text to degug.log file
			file_put_contents( __DIR__ . '/debug.log', $script_start_text, FILE_APPEND );

	        # Check callback data
	        if($mrkv_mono_callback){
	        	if(isset($mrkv_mono_callback['orderId'])){
	        		# Get order object
	        		$order = wc_get_orders( array(
					    'limit'        => 1, 
					    'orderby'      => 'date',
					    'order'        => 'DESC',
					    'meta_key'     => '_order_mono_ref', 
					    'meta_compare' => '==',
					    'meta_value'   => $mrkv_mono_callback['orderId'],
					));
	        		# Get order
					$order_main = $order[0];
					# Add data to order
					$order_main->add_order_note(print_r($mrkv_mono_callback, 1) , $is_customer_note = 0, $added_by_user = false);

					global $woocommerce;

					$wc_gateways      = new WC_Payment_Gateways();
		    		$payment_gateways = $wc_gateways->get_available_payment_gateways();
		    		$mono_payment_gateway = $payment_gateways['morkva-monopay'];

					if(isset($mrkv_mono_callback['payment_method'])){
						$mono_payment_gateway_method = '';

						switch($mrkv_mono_callback['payment_method']){
							case 'card':
								$mono_payment_gateway_method = $payment_gateways['morkva-monopay'];
							break;
							case 'payment_on_delivery':
								$mono_payment_gateway_method = $payment_gateways['cod'];
							break;
							case 'part_purchase':
								$mono_payment_gateway_method = $payment_gateways['morkva-monopay'];
							break;
						}

						$order_main->set_payment_method($mono_payment_gateway_method);
					}

					if(isset($mrkv_mono_callback['generalStatus']))
					{
						$order_main->update_meta_data( 'mrkv_mopay_checkout_status', $mrkv_mono_callback['generalStatus']);
		                update_post_meta( $order_main->get_id(), 'mrkv_mopay_checkout_status', $mrkv_mono_callback['generalStatus'] );
		                $order_main->save();
					}

					if(isset($mrkv_mono_callback['payment_status']) && $mrkv_mono_callback['payment_status'] == 'success'){
						# Set payment complete
						$order_main->payment_complete($mrkv_mono_callback['payment_status']);
					}

					# Check mono first name
					if(isset($mrkv_mono_callback['deliveryRecipientInfo']['first_name'])){
						# Set billing first name field
						$order_main->set_billing_first_name($mrkv_mono_callback['deliveryRecipientInfo']['first_name']);	
					}
					
					# Check mono last name
					if(isset($mrkv_mono_callback['deliveryRecipientInfo']['last_name'])){
						# Set billing last name field
						$order_main->set_billing_last_name($mrkv_mono_callback['deliveryRecipientInfo']['last_name']);	
					}

					# Check mono phone
					if(isset($mrkv_mono_callback['deliveryRecipientInfo']['phoneNumber'])){
						# Set billing phone
						$order_main->set_billing_phone($mrkv_mono_callback['deliveryRecipientInfo']['phoneNumber']);	
					}

					# Check mono phone
					if(isset($mrkv_mono_callback['deliveryAddressInfo']['cityName'])){
						# Set billing phone field
						$order_main->set_billing_city($mrkv_mono_callback['deliveryAddressInfo']['cityName']);		
					}	

					# Check mono phone
					if(isset($mrkv_mono_callback['deliveryAddressInfo']['cityRef'])){
						# Set billing city ref field
						$order_main->update_meta_data('np_city_ref', $mrkv_mono_callback['deliveryAddressInfo']['cityRef']);
						# Set billing city ref field
						$order_main->update_meta_data('_billing_nova_poshta_city', $mrkv_mono_callback['deliveryAddressInfo']['cityRef']);	
					}

					# Check mono phone
					if(isset($mrkv_mono_callback['deliveryAddressInfo']['areaName'])){
						# Set billing phone field
						$order_main->set_billing_state($mrkv_mono_callback['deliveryAddressInfo']['areaName']);	
					}

					# Check mono phone
					if(isset($mrkv_mono_callback['deliveryAddressInfo']['areaRef'])){
						# Set billing phone field
						$order_main->update_meta_data('billing_nova_poshta_region', $mrkv_mono_callback['deliveryAddressInfo']['areaRef']);	
					}	

					# Check mono phone
					if(isset($mrkv_mono_callback['deliveryRecipientInfo']['phoneNumber'])){
						# Set billing phone field
						$order_main->set_billing_phone($mrkv_mono_callback['deliveryRecipientInfo']['phoneNumber']);	
					}	

					# Check mono email
					if(isset($mrkv_mono_callback['mainClientInfo']['email']) && is_email($mrkv_mono_callback['mainClientInfo']['email'])){
						# Set billing email field
						$order_main->set_billing_email($mrkv_mono_callback['mainClientInfo']['email']);	
					}	

					# Check mono address
					if(isset($mrkv_mono_callback['delivery_branch_address'])){
						# Set billing address field
						$order_main->set_billing_address_1($mrkv_mono_callback['delivery_branch_address']);	
						# Set shipping address field
						$order_main->set_shipping_address_1($mrkv_mono_callback['delivery_branch_address']);	
						# Add to meta
						$order_main->update_meta_data( 'mono_delivery_branch_address', $mrkv_mono_callback['delivery_branch_address'] );
					}	

					# Add to meta delivery method
					if(isset($mrkv_mono_callback['delivery_method'])){
						# Add to meta
						$order_main->update_meta_data( 'mono_delivery_method', $mrkv_mono_callback['delivery_method'] );
					}	

					# Add to meta delivery method desc
					if(isset($mrkv_mono_callback['delivery_method_desc'])){
						# Add to meta
						$order_main->update_meta_data( 'mono_delivery_method_desc', $mrkv_mono_callback['delivery_method_desc'] );
					}

					# Add to meta delivery_branch_id
					if(isset($mrkv_mono_callback['delivery_branch_id'])){
						# Add to meta
						$order_main->update_meta_data( 'mono_delivery_branch_id', $mrkv_mono_callback['delivery_branch_id'] );
					}

					if(isset($mrkv_mono_callback['delivery_method']) && isset($mrkv_mono_callback['delivery_method_desc'])){
						# Get country code
						$country_code = $order_main->get_shipping_country();

						$calculate_tax_for = array(
						    'country' => $country_code,
						    'state' => '', 
						    'postcode' => '', 
						    'city' => '', 
						);

						# Add shipping method to order
						$item = new WC_Order_Item_Shipping();
						$item->set_method_title( $mrkv_mono_callback['delivery_method_desc'] );
						if($mono_payment_gateway->get_option('mono_delivery_methods_' . $mrkv_mono_callback['delivery_method']) &&
						$mono_payment_gateway->get_option('mono_delivery_methods_' . $mrkv_mono_callback['delivery_method']) != 'none'){
							$item->set_method_id($mono_payment_gateway->get_option('mono_delivery_methods_' . $mrkv_mono_callback['delivery_method'])); 
						}
						$item->set_total( 0 ); 
						$item->calculate_taxes($calculate_tax_for);

						$order_main->add_item( $item );
						$order_main->calculate_totals();
					}

					# Save order data
					$order_main->save();	

					# Send email
					# Get the WC_Email_New_Order object
					$email_new_order = WC()->mailer()->get_emails()['WC_Email_New_Order'];

					# Sending the new Order email notification for an $order_id (order ID)
					$email_new_order->trigger( $order_main->get_id() );
	        	}
        	}
		}
	}
}