jQuery(window).on('load', function() {
	/**
	 * Check if product variable
	 * */
	if(jQuery('input.variation_id').length){
		/**
		 * Add event by change variation
		 * */
		jQuery('input.variation_id').change(function(){
			// If variation choosen
			if( '' != jQuery(this).val() ) {
				// Active button
				jQuery('.mrkv-monopay-checkout[data-product-type="variable"]').addClass('choosen');
			}
			else{
				// Disable button
				jQuery('.mrkv-monopay-checkout[data-product-type="variable"]').removeClass('choosen');
			}
		});
	}

	// Check choosen variation
	checkChoosenVariation();

	/**
	 * Check choosen variation
	 * */
	function checkChoosenVariation(){
		var button_type = jQuery('.single_add_to_cart_button').hasClass('disabled');
		if(button_type){
			// Disable button
			jQuery('.mrkv-monopay-checkout[data-product-type="variable"]').removeClass('choosen');
		}
		else{
			// Active button
			jQuery('.mrkv-monopay-checkout[data-product-type="variable"]').addClass('choosen');
		}
	}

	/**
	 * Event after variation change
	 * */
	jQuery( '.single_variation_wrap' ).on( 'show_variation', function( event, variation ) {
		jQuery('.mrkv-monopay-checkout[data-page="product"]').attr('data-price', variation.display_price);
		
		if(variation.display_price)
		{
			jQuery('.mrkv-monopay-checkout[data-product-type="variable"]').addClass('choosen');
		}
	});

	/**
	 * Send query Monopay checkout
	 * */
 	jQuery(document).on("click", ".mrkv-monopay-checkout", function(){
 		// Get page name
 		let page = jQuery(this).attr('data-page');
 		// Get current button
 		let button = jQuery(this);
 		// Get loader
 		let loader = jQuery(this).find('.mrkv-monopay-checkout__loader');
 		// Create product list 
 		var product = {};

 		// Check page type
 		if(page == 'product'){

 			// Get product type
 			let product_type = jQuery(this).attr('data-product-type');

			// Check product type
 			if(product_type == 'simple'){
 				// Get product data
 				let product_id = jQuery(this).attr('data-prod-id');
 				let amount = jQuery(this).attr('data-price');
 				let quantity = jQuery(this).closest('form.cart').find('input[name="quantity"]').val();

 				// Set product data to json
 				product = {
 					product_id : product_id,
 					variation_id : 0,
 					amount: amount,
 					quantity: quantity
 				};
 			}
 			else{

 				if(!jQuery(this).hasClass('choosen')){
	 				alert('Будь ласка оберіть опції товару, перш ніж додавати цей товар до кошика.');
	 				return;
	 			}

 				// Get product data
 				let product_id = jQuery(this).closest('.single_variation_wrap').find('input[name="variation_id"]').val();
 				let variation_id = jQuery(this).closest('.single_variation_wrap').find('input[name="variation_id"]').val();
 				let amount = jQuery(this).attr('data-price');
 				let quantity = jQuery(this).closest('.single_variation_wrap').find('input[name="quantity"]').val();

 				// Set product data to json
 				product = {
 					product_id : product_id,
 					variation_id : variation_id,
 					amount: amount,
 					quantity: quantity
 				};
 			}

 		}
 		else{
 			let amount = jQuery(this).attr('data-cart-total');
 			let coupon = jQuery(this).attr('data-cart-coupon');

 			product = {
 				amount : amount,
 				coupon : coupon
 			};
 		}

 		// Action data
 		let data = {
 			action : 'mrkv_monopay_' + page + '',
 			product : JSON.stringify(product)
 		};

 		// Send query for checkout
 		jQuery.ajax({
			url: monopay_script_object.ajax_url,
			type: 'POST',
			data: data, 
			beforeSend: function( xhr ) {
				jQuery(loader).addClass('active');
			},
			success: function( data ) {
				if(data != 0){
					window.open(data, "_self");
				}
				jQuery(loader).removeClass('active');
			}
		});
 	});
});