jQuery(document).ready(function($) {
	
	// Reveal password by default and create an inner toggle
	$('#mygrandski-login-password').hideShowPassword(false, true);
	
	$('#mygrandski-login-password').focus();
	
	/*jQuery('#edit-submit').click(function() {
		var l = Ladda.create(document.querySelector('#edit-submit'));
		l.start();
		
		$('#mygrandski-login-password').hidePassword();
		jQuery('#gv-fplus-auth-login-form').submit();
	});*/
	
	jQuery('#gv-fplus-auth-login-form').submit(function() {
		$('#mygrandski-login-password').hidePassword();
		
		var l = Ladda.create(document.querySelector('#edit-submit'));
		l.start();
	});
	
});
