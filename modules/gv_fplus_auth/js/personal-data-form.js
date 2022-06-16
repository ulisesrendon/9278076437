jQuery(document).ready(function($) {
	//'.form-item-image'
	// '.image-preview'
	// '.edit-image-btn'
	
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
	
	function hideLoader() {
		if (!jQuery('.loader-checkout').hasClass('hidden')) {
  			jQuery('.loader-checkout').addClass('hidden');
  		}
	}
	
	/*jQuery('#edit-submit').click(function() {

		
		jQuery('#gv-fplus-auth-personal-data-form').submit();
	});*/
	
	// Warning
	$(window).on('beforeunload', function(){
	    return "Any changes will be lost";
	});
		
	// Form Submit
	$(document).on("submit", '#gv-fplus-auth-personal-data-form', function(e){
	    // disable warning
	    $(window).off('beforeunload');
	    
	    var l = Ladda.create(document.querySelector('#edit-submit'));
		l.start();
	});
	
	function onFinishCallback(base64Image) {
		showLoader();
		$('#edit-image').val(base64Image);
		$('#edit-image-base64').val(base64Image);
		$('#edit-image-base64').trigger('change');
		$('#edit-image').attr('value', base64Image);
	}
	
	var webcamWidget = new window.fanaticsPlusWebcam('.form-item-image', '.image-preview', '.edit-image-btn', onFinishCallback);
	webcamWidget.start();

});

/* RG: Unificación pasos */
jQuery(document).ready(function($) {
	var input = document.querySelector("#edit-phone-number");
	var iti = window.intlTelInput(input, {
		hiddenInput: "full_phone_number",
		utilsScript: '/modules/custom/gv_fplus/modules/gv_fplus_auth/js/deps/intl-tel-input/utils.js',
		preferredCountries: ['ad', 'es', 'fr', 'gb']
	});

	input.addEventListener('blur', function() {
		var fullNumber = iti.getNumber();
		jQuery('#edit-full-phone-number').val(fullNumber);

	});

	/*$('#edit-submit').click(function() {
       var l = Ladda.create(document.querySelector('#edit-submit'));
       l.start();

       jQuery('#gv-fplus-auth-residence-data-form').submit();
   });*/

	// Warning
	$(window).on('beforeunload', function(){
		return "Any changes will be lost";
	});

	// Form Submit
	$(document).on("submit", '#gv-fplus-auth-residence-data-form', function(e){
		// disable warning
		$(window).off('beforeunload');

		var l = Ladda.create(document.querySelector('#edit-submit'));
		l.start();
	});

	var fullNumber = jQuery('#edit-full-phone-number').val();
	if (fullNumber) {
		iti.setNumber(fullNumber);
	}

	$('#edit-country').on('change', function() {
		$('#edit-postal-code').val('');

		var currentPhoneNumber = $('#edit-phone-number').val();
		if (currentPhoneNumber != '') {
			return;
		}

		if ($('#edit-country option:selected').attr('data-country-iso-code')) {
			var newCountryCode = $('#edit-country option:selected').attr('data-country-iso-code').toLowerCase();
			iti.setCountry(newCountryCode);
			$('#edit-postal-code').val('');
			$('#edit-phone-number').val('');
		}
	});

	$('#edit-census-number').on('blur', function() {
		var censusNumber = $(this).val();
		if (censusNumber && censusNumber.length > 0) {
			$('.census-field-container').removeClass('warning');
		} else {
			$('.census-field-container').addClass('warning');
		}
	});
});
