(function(jQuery, Drupal) {
	
	'use strict';
	
	jQuery(document).ready(function($) {
		var banner = jQuery('#sliding-popup .eu-cookie-compliance-banner');
		if (banner.length <= 0) {
			return;
		}
		
		banner.find('#popup-text h2').text(Drupal.t("Grandvalira website uses our own and third-party cookies to improve user's browsing experience. If you continue browsing our website we consider you’ve accepted our cookies policy."));
		//banner.find('#popup-text p').text(Drupal.t("By clicking the Accept button, you agree to us doing so."));
		banner.find('#popup-text p').text('');
		banner.find('#popup-text .find-more-button').text(Drupal.t("No, give me more info"));
		
		banner.find('#popup-text .find-more-button').off('click').click(function(e) {
			e.preventDefault();
			var langCode = $('html')[0].lang;
			if (langCode == 'es') {
				window.location.replace('https://www.grandvalira.com/nota-legal-plus#13');
			} else if (langCode == 'ca') {
				window.location.replace('https://www.grandvalira.com/ca/nota-legal-plus#13');
			} else if (langCode == 'en') {
				window.location.replace('https://www.grandvalira.com/fr/information-legale-plus#13');
			} else if (langCode == 'fr') {
				window.location.replace('https://www.grandvalira.com/en/legal-note-plus#13');
			}
		});
	});
	
})(jQuery, Drupal);
