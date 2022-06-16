jQuery(document).ready(function($) {
	
	function showLoader() {
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
	}
	
	var childWindow = document.getElementById('logalty-sign-get-url');
	if (childWindow) {
		childWindow = childWindow.contentWindow;
		window.addEventListener('message', function(message) {
	    	if (message.source !== childWindow) {
	        	return; // Skip message in this event listener
	    	}
	    	
	    	if (!message || !message.data) {
	    		return;
	    	}
	
	    	if (message.data == 'OK') {
	    		showLoader();
	    		jQuery('#logalty-sign-get-url').css('visibility', 'hidden');
	    		jQuery('#edit-submit').attr('disabled', false);
	    		jQuery('#gv-fplus-checkout-logalty-form').submit();
	    	} else if (message.data == 'KO') {
	    		// TODO: handle error gracefully
	    		jQuery('.error-container').removeClass('hidden');
	    		jQuery('#logalty-sign-get-url').addClass('hidden');
	    	}
		});
	}
	
	var input = document.querySelector("#edit-logalty-sms");
	if (input) {
		var iti = window.intlTelInput(input, {
	  		hiddenInput: "logalty[full_phone_number]",
	  		utilsScript: '/modules/custom/gv_fplus/modules/gv_fplus_auth/js/deps/intl-tel-input/utils.js',
	  		preferredCountries: ['ad', 'es', 'fr', 'gb']
	 	});
	 	
	 	input.addEventListener('blur', function() {
	 		var fullNumber = iti.getNumber();
	 		jQuery('#edit-logalty-full-phone-number').val(fullNumber);
	 		
	 	});
	 	
	 	var fullNumber = jQuery('#edit-logalty-full-phone-number').val();
	    if (fullNumber) {
	    	iti.setNumber(fullNumber);
	    }	
	} else {
		//jQuery('#edit-submit').attr('disabled', true);
	}
	
	jQuery('.gv-fplus-checkout-logalty-form').submit(function() {
		showLoader();
	});
	
});
