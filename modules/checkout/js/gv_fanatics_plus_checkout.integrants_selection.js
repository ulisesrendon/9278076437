jQuery(document).ready(function($) {
	$('.paying-customer .paying-customer--item-avatar img, .block-gv-fanatics-plus-checkout-integrants-selection .available-integrants--item-avatar img').each(function() {
		var img = $(this);
		var imgBase64 = img.attr('data-src');
		
		img.attr('src', imgBase64);
	});

	$('.available-integrants.scrollable').removeClass("scrollable");
});
