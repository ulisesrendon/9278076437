jQuery(document).ready(function($) {
	
	// Reveal password by default and create an inner toggle
	$('#mygrandski-register-password').hideShowPassword(false, true);
	$('#mygrandski-register-password').strengthify({
		"zxcvbn": "/modules/custom/gv_fplus/modules/gv_fplus_auth/js/deps/zxcvbn/zxcvbn.js",
		"userInputs": [],
		"drawTitles": false,
		"drawMessage": false,
		"drawBars": true,
		"$addAfter": null
	});
	
	/*jQuery('#edit-submit').click(function() {
		var l = Ladda.create(document.querySelector('#edit-submit'));
		l.start();
	
		jQuery('#gv-fplus-auth-basic-register-form').submit();
	});*/
	
	// Warning
	$(window).on('beforeunload', function(){
	    return "Any changes will be lost";
	});
	
	// Form Submit
	$(document).on("submit", '#gv-fplus-auth-basic-register-form', function(e){
		$('#mygrandski-register-password').hidePassword();
		
	    // disable warning
	    $(window).off('beforeunload');
	    
	    var l = Ladda.create(document.querySelector('#edit-submit'));
		l.start();
	});
});
