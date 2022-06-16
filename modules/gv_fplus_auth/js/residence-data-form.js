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
