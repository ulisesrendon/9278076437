(function(jQuery, Drupal) {
	
	'use strict';
	
	jQuery('.my-grandski-my-integrants .divTableCell .image img').each(function() {
		var elem = jQuery(this);
		var imgSrc = elem.attr('data-src');
		if (imgSrc) {
			elem.attr('src', 'data:image/jpeg;base64,' + imgSrc);
		} else {
			elem.attr('src', '/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/user-line-white--default-avatar.svg');
		}
	});
	
})(jQuery, Drupal);
