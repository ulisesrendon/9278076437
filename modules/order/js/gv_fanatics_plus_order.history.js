jQuery(document).ready(function($) {
	var previousVal = null;
	$('select[name="season"]').change(function(e) {
		var currentVal = $(this).val();
		if (currentVal == previousVal) {
			return;
		}
		
		previousVal = currentVal;
		//insertParam('season', currentVal);
		var targetUrl = $('option:selected', this).attr('data-url');
		if (!targetUrl) {
			return;
		}
		
		window.location.replace(targetUrl);
	});
});
