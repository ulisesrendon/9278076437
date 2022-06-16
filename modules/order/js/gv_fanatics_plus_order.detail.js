jQuery(document).ready(function($) {
	$('.service-buyer-image img').each(function() {
		var img = $(this);
		var src = img.attr('data-src');
		img.attr('src', src);
	});
});
