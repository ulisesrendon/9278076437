jQuery(document).ready(function($) {	
	jQuery('#gv-fplus-auth-email-check-form').submit(function() {
		var l = Ladda.create(document.querySelector('#edit-submit'));
		l.start();
	});
});
