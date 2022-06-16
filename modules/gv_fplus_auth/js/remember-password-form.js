jQuery(document).ready(function($) {
	jQuery('#edit-submit').click(function() {
		var l = Ladda.create(document.querySelector('#edit-submit'));
		l.start();
	
		jQuery('#gv-fplus-auth-remember-password-form').submit();
	});	
});
