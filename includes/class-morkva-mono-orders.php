<?php 
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;

# Check if class exist
if (!class_exists('MorkvaMonopayOrders'))
{
	/**
	 * Class for add widget orders
	 */
	class MorkvaMonopayOrders
	{
		/**
		 * Constructor for add orders data
		 * */
		function __construct()
		{
			add_action('add_meta_boxes', array( $this, 'mrkv_monopay_add_meta_boxes' ));

			add_action( 'wp_ajax_mrkv_mono_checkout_status_check', array( $this, 'mrkv_mono_checkout_status_check_func' ) );
			add_action( 'wp_ajax_nopriv_mrkv_mono_checkout_status_check', array( $this, 'mrkv_mono_checkout_status_check_func' ) );

			add_action( 'wp_ajax_mrkv_mono_accuiring_status_check', array( $this, 'mrkv_mono_accuiring_status_check_func' ) );
			add_action( 'wp_ajax_nopriv_mrkv_mono_accuiring_status_check', array( $this, 'mrkv_mono_accuiring_status_check_func' ) );
		}

		/**
		 * Check mono accuiring status
		 * */
		public function mrkv_mono_accuiring_status_check_func()
		{
			if(isset($_POST['order_id']))
			{
				# Get token by mono gateway
	    		$wc_gateways      = new WC_Payment_Gateways();
	    		$payment_gateways = $wc_gateways->get_available_payment_gateways();
	    		$mono_payment_gateway = $payment_gateways['morkva-monopay'];
	    		$mrkv_mono_token = $mono_payment_gateway->get_mrkv_mono_getToken();

				# Create request header
		        $mrkv_mono_headers = array(
		            'Content-type'  => 'application/json',
		            'X-Token' => $mrkv_mono_token,
		            'X-Cms' => 'morkva'
		        );

				# Create request args
		        $mrkv_mono_args = array(
		            'method'      => 'GET',
		            'headers'     => $mrkv_mono_headers,
		            'user-agent'  => 'WooCommerce/' . WC()->version,
		        );

		        $mrkv_mono_order_id = $_POST['order_id'];

		        $mrkv_mono_order = wc_get_order($mrkv_mono_order_id);

		        $invoice_number = $mrkv_mono_order->get_meta('mrkv_mopay_accuiring_invoice_id');

		        $response = wp_remote_get('https://api.monobank.ua/api/merchant/invoice/status?invoiceId=' . $invoice_number, $mrkv_mono_args);


		        if( 200 === wp_remote_retrieve_response_code( $response ) ) 
		        {
					$response = json_decode( wp_remote_retrieve_body( $response ), true );

					if(isset($response['status']))
					{
						$mrkv_mono_order->update_meta_data( 'mrkv_mopay_payment_method', 'morkva-monopay');
		                update_post_meta( $_POST['order_id'], 'mrkv_mopay_payment_method', 'morkva-monopay' );

		                $mrkv_mono_callback = $response;

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

		                if($response['status'] == 'success')
		                {
		                	# Update order status
                			$mrkv_mono_order->update_status($mono_payment_gateway->mrkv_mono_get_order_status_success());
		                }

		                $mrkv_mono_order->save();
					}
				}
			}

			wp_die();
		}

		/**
		 * Check mono checkout status
		 * */
		public function mrkv_mono_checkout_status_check_func()
		{
			if(isset($_POST['order_id']))
			{
				# Get token by mono gateway
	    		$wc_gateways      = new WC_Payment_Gateways();
	    		$payment_gateways = $wc_gateways->get_available_payment_gateways();
	    		$mono_payment_gateway = $payment_gateways['morkva-monopay'];
	    		$mrkv_mono_token = $mono_payment_gateway->get_mrkv_mono_getToken();

				# Create request header
		        $mrkv_mono_headers = array(
		            'Content-type'  => 'application/json',
		            'X-Token' => $mrkv_mono_token,
		            'X-Cms' => 'morkva'
		        );

				# Create request args
		        $mrkv_mono_args = array(
		            'method'      => 'GET',
		            'headers'     => $mrkv_mono_headers,
		            'user-agent'  => 'WooCommerce/' . WC()->version,
		        );

				$response = wp_remote_get('https://api.monobank.ua/personal/checkout/order/' . $_POST['order_id'], $mrkv_mono_args);

				if( 200 === wp_remote_retrieve_response_code( $response ) ) {
					$response = json_decode( wp_remote_retrieve_body( $response ), true );
					
					if(isset($response['result']) && isset($response['result']['generalStatus']))
					{
						$mrkv_mono_callback = $response['result'];
						# Get order by id
	            		$order = wc_get_order($_POST['order_id']);

	            		# Update order meta
						$order->update_meta_data( 'mrkv_mopay_checkout_status', $response['result']['generalStatus']);

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

							$order->set_payment_method($mono_payment_gateway_method);
						}

						if(isset($mrkv_mono_callback['payment_status']) && $mrkv_mono_callback['payment_status'] == 'success'){
							# Set payment complete
							$order->payment_complete($mrkv_mono_callback['payment_status']);
						}

						# Check mono first name
						if(isset($mrkv_mono_callback['deliveryRecipientInfo']['first_name'])){
							# Set billing first name field
							$order->set_billing_first_name($mrkv_mono_callback['deliveryRecipientInfo']['first_name']);	
						}
						
						# Check mono last name
						if(isset($mrkv_mono_callback['deliveryRecipientInfo']['last_name'])){
							# Set billing last name field
							$order->set_billing_last_name($mrkv_mono_callback['deliveryRecipientInfo']['last_name']);	
						}

						# Check mono phone
						if(isset($mrkv_mono_callback['deliveryRecipientInfo']['phoneNumber'])){
							# Set billing phone field
							$order->set_billing_phone($mrkv_mono_callback['deliveryRecipientInfo']['phoneNumber']);	
						}	

						# Check mono email
						if(isset($mrkv_mono_callback['mainClientInfo']['email'])){
							# Set billing email field
							$order->set_billing_email($mrkv_mono_callback['mainClientInfo']['email']);	
						}	

						# Check mono address
						if(isset($mrkv_mono_callback['delivery_branch_address'])){
							# Set billing address field
							$order->set_billing_address_1($mrkv_mono_callback['delivery_branch_address']);	
							# Set shipping address field
							$order->set_shipping_address_1($mrkv_mono_callback['delivery_branch_address']);	
							# Add to meta
							$order->update_meta_data( 'mono_delivery_branch_address', $mrkv_mono_callback['delivery_branch_address'] );
						}	

						# Add to meta delivery method
						if(isset($mrkv_mono_callback['delivery_method'])){
							# Add to meta
							$order->update_meta_data( 'mono_delivery_method', $mrkv_mono_callback['delivery_method'] );
						}	

						# Add to meta delivery method desc
						if(isset($mrkv_mono_callback['delivery_method_desc'])){
							# Add to meta
							$order->update_meta_data( 'mono_delivery_method_desc', $mrkv_mono_callback['delivery_method_desc'] );
						}

						# Add to meta delivery_branch_id
						if(isset($mrkv_mono_callback['delivery_branch_id'])){
							# Add to meta
							$order->update_meta_data( 'mono_delivery_branch_id', $mrkv_mono_callback['delivery_branch_id'] );
						}

						if(isset($mrkv_mono_callback['delivery_method']) && isset($mrkv_mono_callback['delivery_method_desc'])){
							# Get country code
							$country_code = $order->get_shipping_country();

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

							$order->add_item( $item );
							$order->calculate_totals();
						}

						# Save order
						$order->save();
					}
				}

				die;
			}
		}

		/**
	     * Generating meta boxes
	     *
	     * @since 1.0.0
	     */
	    public function mrkv_monopay_add_meta_boxes()
	    {
	        # Check hpos
	        if(class_exists( CustomOrdersTableController::class )){
	            $screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
	            ? wc_get_page_screen_id( 'shop-order' )
	            : 'shop_order';
	        }
	        else{
	            $screen = 'shop_order';
	        }


	        # Check order id
	    	if (isset($_GET["post"]) || isset($_GET["id"])) 
	    	{
	    		# Set order id
	    		$order_id = '';

	    		# Check get data
	            if(isset($_GET["post"]))
	            {
	            	# Set order id
	                $order_id = $_GET["post"];    
	            }
	            else
	            {
	            	# Set order id
	                $order_id = $_GET["id"];
	            }

	            # Get order by id
	            $order = wc_get_order($order_id);

            	if($order)
            	{
            		# Get payment method
		            $payment_method = $order->get_payment_method();

		            $mrkv_mopay_payment_method = $order->get_meta('mrkv_mopay_payment_method');

		            # Check monopay method
		            if($mrkv_mopay_payment_method == 'morkva-monopay-checkout' || 'morkva-monopay' == $payment_method || 'morkva-monopay-payparts' == $payment_method)
		            {
		            	# Add metabox
		         		add_meta_box('mrkv_monopay_order', __('MonoPay', 'morkva-monobank-extended'), array( $this, 'mrkv_monopay_add_plugin_meta_box' ), $screen, 'side', 'core');   
		            }
            	}
	    	}
	    }

	    /**
	     * Add metabox content
	     * */
	    public function mrkv_monopay_add_plugin_meta_box()
	    {
	    	# Check order id
	    	if (isset($_GET["post"]) || isset($_GET["id"])) 
	    	{
	    		# Set order id
	    		$order_id = '';

	    		# Check get data
	            if(isset($_GET["post"]))
	            {
	            	# Set order id
	                $order_id = $_GET["post"];    
	            }
	            else
	            {
	            	# Set order id
	                $order_id = $_GET["id"];
	            }

	            # Get order by id
	            $order = wc_get_order($order_id);

	            # Get payment method
	            $payment_method = $order->get_payment_method();

	            $mrkv_mopay_payment_method = $order->get_meta('mrkv_mopay_payment_method');

	            # Check monopay method
	            if($mrkv_mopay_payment_method == 'morkva-monopay-checkout' || 'morkva-monopay' == $payment_method || 'morkva-monopay-payparts' == $payment_method)
	            {
	            	# Get Acuiring status
            		$mrkv_mopay_payment_method = $order->get_meta('mrkv_mopay_payment_method');

	            	if('morkva-monopay' == $payment_method && $mrkv_mopay_payment_method == 'morkva-monopay')
	            	{
	            		# Get Acuiring status
	            		$mrkv_mopay_accuiring_status = $order->get_meta('mrkv_mopay_accuiring_status');

	            		# Get Acuiring reference
	            		$mrkv_mopay_accuiring_reference = $order->get_meta('mrkv_mopay_accuiring_reference');

	            		# Get Acuiring invoice id
	            		$mrkv_mopay_accuiring_invoice_id = $order->get_meta('mrkv_mopay_accuiring_invoice_id');

	            		# Get Acuiring error
	            		$mrkv_mopay_accuiring_failure_reason = $order->get_meta('mrkv_mopay_accuiring_failure_reason');

	            		?>
	            			<div class="monopay_metabox_line">
	            				<h4><?php echo __( 'Mono Acquiring', 'morkva-monobank-extended' ); ?></h4>
	            			</div>
	            		<?php
	            		if($mrkv_mopay_accuiring_status)
	            		{
	            			?>
	            			<div class="monopay_metabox_line">
	            				<b><?php echo __('Status', 'morkva-monobank-extended'); ?>:</b>
	            				<span>
	            					<?php 
	            						$order_status_text = '';

	            						switch($mrkv_mopay_accuiring_status)
	            						{
	            							case 'created':
	            								$order_status_text = __('Created', 'morkva-monobank-extended');
	            							break;
	            							case 'processing':
	            								$order_status_text = __('Processing', 'morkva-monobank-extended');
	            							break;
	            							case 'failure':
	            								$order_status_text = $mrkv_mopay_accuiring_failure_reason;
	            							break;
	            							case 'success':
	            								$order_status_text = __('Success', 'morkva-monobank-extended');
	            							break;
	            						}
	            						echo $order_status_text;
	            					?>
	            				</span>
	            			</div>
	            			<?php
	            		}

	            		if($mrkv_mopay_accuiring_reference)
	            		{
	            			?>
	            				<div class="monopay_metabox_line">
	            					<b><?php echo __('Reference', 'morkva-monobank-extended'); ?>:</b>
	            					<span><?php echo $mrkv_mopay_accuiring_reference; ?></span>
	            				</div>
	            			<?php
	            		}

	            		if($mrkv_mopay_accuiring_invoice_id)
	            		{
	            			?>
	            				<div class="monopay_metabox_line">
	            					<b><?php echo __('Invoice ID', 'morkva-monobank-extended'); ?>:</b>
	            					<span><?php echo $mrkv_mopay_accuiring_invoice_id; ?></span>
	            				</div>
	            				<div style="margin-top: 15px;" class="monopay_metabox_line">
	            					<div class="mrkv_mono_accuiring-status-call button button-primary"><?php echo __('Checking the status of the order', 'morkva-monobank-extended'); ?></div>
	            					<svg style="display: none; position:absolute; right:0;" version="1.1" id="L9" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30px" height="30px" x="0px" y="0px"
									  viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve">
									    <path fill="#000" d="M73,50c0-12.7-10.3-23-23-23S27,37.3,27,50 M30.9,50c0-10.5,8.5-19.1,19.1-19.1S69.1,39.5,69.1,50">
									      <animateTransform 
									         attributeName="transform" 
									         attributeType="XML" 
									         type="rotate"
									         dur="1s" 
									         from="0 50 50"
									         to="360 50 50" 
									         repeatCount="indefinite" />
									  </path>
									</svg>
	            				</div>
	            				<script>
            					jQuery(window).on('load', function() {
	            					jQuery(document).on("click", ".mrkv_mono_accuiring-status-call", function(){
	            						var order_id = '<?php echo $order->get_id(); ?>';

	            						if(order_id)
	            						{
	            							var data = {
	            								action: 'mrkv_mono_accuiring_status_check',
	            								order_id: order_id
	            							};
	            							jQuery.ajax({
												url: '<?php echo admin_url( "admin-ajax.php" ) ?>',
												type: 'POST',
												data: data,
												beforeSend: function( xhr ) {
									                jQuery('.monopay_metabox_line svg').show();
									            },
												success: function( data ) {
													location.reload();
												}
											});
	            						}
	            					});
            					});
            				</script>
	            			<?php
	            		}
	            	}

	            	if($mrkv_mopay_payment_method == 'morkva-monopay-checkout')
	            	{
	            		?>
	            			<div class="monopay_metabox_line">
	            				<h4><?php echo __( 'Mono Checkout', 'morkva-monobank-extended' ); ?></h4>
	            			</div>
	            		<?php

	            		# Get Checkout status
	            		$mrkv_mopay_checkout_status = $order->get_meta('mrkv_mopay_checkout_status');

	            		if($mrkv_mopay_checkout_status)
	            		{
	            			?>
	            				<div class="monopay_metabox_line">
	            					<b><?php echo __('Status', 'morkva-monobank-extended'); ?>:</b>
	            					<span>
	            						<?php 
	            						$order_status_text = '';

	            						$order_message_text = '';

	            						switch($mrkv_mopay_checkout_status)
	            						{
	            							case 'sent_to_checkout':
	            								$order_status_text = __('Redirect user to Checkout', 'morkva-monobank-extended');
	            								$order_message_text = __('The user went to the Mono Checkout page', 'morkva-monobank-extended');
	            							break;
	            							case 'not_authorized':
	            								$order_status_text = __('Not authorized', 'morkva-monobank-extended');
	            								$order_message_text = __('The user was not authorized when entering the checkout. We do not send the goods', 'morkva-monobank-extended');
	            							break;
	            							case 'not_confirmed':
	            								$order_status_text = __('Not confirmed', 'morkva-monobank-extended');
	            								$order_message_text = __('The user authorized in the checkout flats did not confirm the purchase. We do not send the goods', 'morkva-monobank-extended');
	            							break;
	            							case 'in_process':
	            								$order_status_text = __('In process', 'morkva-monobank-extended');
	            								$order_message_text = __('The user confirmed the payment and went to the acquiring screen to confirm the payment. Confirmation is in progress. The status should be updated later. We do not send the goods.', 'morkva-monobank-extended');
	            							break;
	            							case 'payment_on_delivery':
	            								$order_status_text = __('Payment upon delivery (final status)', 'morkva-monobank-extended');
	            								$order_message_text = __('The user chose payment upon receipt and confirmed the purchase. You can send the goods', 'morkva-monobank-extended');
	            							break;
	            							case 'success':
	            								$order_status_text = __('Paid (final status)', 'morkva-monobank-extended');
	            								$order_message_text = __('The user chose to pay by card or IF and confirmed the purchase. The user made the payment successfully. You can send the goods', 'morkva-monobank-extended');
	            							break;
	            							case 'fail':
	            								$order_status_text = __('Not paid (final status)', 'morkva-monobank-extended');
	            								$order_message_text = __('The user was not paid goods. We do not send the goods', 'morkva-monobank-extended');

	            							break;
	            							case 'error':
	            								$order_status_text = __('Error', 'morkva-monobank-extended');
	            								$order_message_text = $order->get_meta('mrkv_mopay_checkout_status_message');

	            							break;
	            						}
	            						echo $order_status_text;
	            						echo '<div>' . $order_message_text . '</div>';
	            					?>
	            					</span>
	            				</div>
	            			<?php
	            		}

	            		# Get Payparts status
	            		$_order_mono_ref = $order->get_meta('_order_mono_ref');	

	            		if($_order_mono_ref)
	            		{
	            			?>
	            				<div class="monopay_metabox_line">
	            					<b><?php echo __('Reference', 'morkva-monobank-extended'); ?>:</b>
	            					<span><?php echo $_order_mono_ref; ?></span>
	            				</div>
	            				<div style="margin-top: 15px;" class="monopay_metabox_line">
	            					<div class="mrkv_mono_checkout-status-call button button-primary"><?php echo __('Checking the status of the order', 'morkva-monobank-extended'); ?></div>
	            					<svg style="display: none; position:absolute; right:0;" version="1.1" id="L9" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30px" height="30px" x="0px" y="0px"
									  viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve">
									    <path fill="#000" d="M73,50c0-12.7-10.3-23-23-23S27,37.3,27,50 M30.9,50c0-10.5,8.5-19.1,19.1-19.1S69.1,39.5,69.1,50">
									      <animateTransform 
									         attributeName="transform" 
									         attributeType="XML" 
									         type="rotate"
									         dur="1s" 
									         from="0 50 50"
									         to="360 50 50" 
									         repeatCount="indefinite" />
									  </path>
									</svg>
	            				</div>
	            				<script>
            					jQuery(window).on('load', function() {
	            					jQuery(document).on("click", ".mrkv_mono_checkout-status-call", function(){
	            						var order_id = '<?php echo $order->get_id(); ?>';

	            						if(order_id)
	            						{
	            							var data = {
	            								action: 'mrkv_mono_checkout_status_check',
	            								order_id: order_id
	            							};
	            							jQuery.ajax({
												url: '<?php echo admin_url( "admin-ajax.php" ) ?>',
												type: 'POST',
												data: data,
												beforeSend: function( xhr ) {
									                jQuery('.monopay_metabox_line svg').show();
									            },
												success: function( data ) {
													location.reload();
												}
											});
	            						}
	            					});
            					});
            				</script>
	            			<?php
	            		}
	            	}

	            	if('morkva-monopay-payparts' == $payment_method && $mrkv_mopay_payment_method == 'morkva-monopay-payparts')
	            	{
	            		?>
	            			<div class="monopay_metabox_line">
	            				<h4><?php echo __( 'Mono PayParts', 'morkva-monobank-extended' ); ?></h4>
	            			</div>
	            		<?php

	            		# Get Payparts status
	            		$mrkv_mopay_payparts_status = $order->get_meta('mrkv_mopay_payparts_status');	

	            		if($mrkv_mopay_payparts_status)
	            		{
	            			?>
	            				<div class="monopay_metabox_line">
	            					<b><?php echo __('Status', 'morkva-monobank-extended'); ?>:</b>
	            					<span><?php echo $mrkv_mopay_payparts_status; ?></span>
	            				</div>
	            			<?php
	            		}
	            	}
	            }
	    	}
	    }
	}
}