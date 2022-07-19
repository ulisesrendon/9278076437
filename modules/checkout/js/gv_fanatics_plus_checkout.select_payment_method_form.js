jQuery(document).ready(function($) {	
	function onPaymentMethodChange() {
		var selectedValue = $("input[name='payment_method']:checked").val();
    	$('.payment-method-descriptors .payment-method-descriptors--inner').css('display', 'none');
    	$('.payment-method-descriptors .payment-method-descriptors--inner.payment-method-' + selectedValue).css('display', 'block');
	}
	
	onPaymentMethodChange();
	$('#edit-payment-method').change(onPaymentMethodChange);

	$('.payment-method-title').on("click", function() {
		$(this).parent().find('.payment-description-body').toggle();
	});
	
	$('.gv-fanatics-plus-checkout-select-payment-method').submit(function(e) {
		var selectedPaymentMethod = jQuery('input[name="payment_method"]').val();
		var paymentMethodTag = null;
		if (selectedPaymentMethod == 32) {
			paymentMethodTag = 'TPV Recurrente';
		} else if (selectedPaymentMethod == 50) {
			paymentMethodTag = 'TPV FFT';
		} else if (paymentMethod == 38) {
			paymentMethodTag = 'TPV Recurrente';
		}
		
		if (paymentMethodTag != null && dataLayer) {
			dataLayer.push({
				'event': 'checkoutOption',
				'ecommerce': {
				'checkout_option': {
				'actionField': {
				'step': 3,
				'option': paymentMethodTag
				}
		}}});	
		}

		
		var pathIds = ['cable', 'ski', 'mountain'];
	  	var myIcons = new SVGMorpheus('#loaderSvg');
	  	var currentIndex = 0;
	  	var svgOptions = {
	    	duration: 1000,
	    	rotation: 'none',
	    	easing: 'quint-in'
	  	};
	  
	  	jQuery('.loader-checkout').removeClass('hidden');
	  	setInterval(function() {
	    	myIcons.to(pathIds[currentIndex], svgOptions, function(){
	      		if (currentIndex === (pathIds.length - 1)) {
	        		currentIndex = 0;
	      		} else {
	        		currentIndex++;
	      		}
	    	});
	  	}, 700);
	});
});
