(function($, Drupal) {
	
	'use strict';
	
	Drupal.behaviors.onChangePasswordModal = {
    	attach: function(context, settings) {
    		if ($('.profile-change-password-form').length > 0) {
    			if ($('.profile-change-password-form #mygrandski-current-password, .profile-change-password-form #mygrandski-new-password,.profile-change-password-form #mygrandski-new-confirm-password').hasClass('hideShowPassword-field')) {
					return;
				}
				
    			$('.profile-change-password-form #mygrandski-current-password, .profile-change-password-form #mygrandski-new-password,.profile-change-password-form #mygrandski-new-confirm-password').hideShowPassword(false, true);
				$('.profile-change-password-form #mygrandski-current-password,.profile-change-password-form #mygrandski-new-password,.profile-change-password-form #mygrandski-new-confirm-password').strengthify({
					"zxcvbn": "/modules/custom/gv_fplus/modules/gv_fplus_auth/js/deps/zxcvbn/zxcvbn.js",
					"userInputs": [],
					"drawTitles": false,
					"drawMessage": false,
					"drawBars": true,
					"$addAfter": null
				});
				
				$('.profile-change-password-form .form-submit').click(function() {
					$('.profile-change-password-form #mygrandski-current-password,.profile-change-password-form #mygrandski-new-password,.profile-change-password-form #mygrandski-new-confirm-password').hidePassword();
				});
    		}
    	}
   };
	
	$(document).ready(function() {});
	
})(jQuery, Drupal);
