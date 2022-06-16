jQuery(document).ready(function($) {
	$('.change-language-modal-form .form-item-languages').each(function() {
		var elem = $(this);
		var countryCode = elem.find('input').first().val();

		if (countryCode) {
			if (countryCode == 'ca') {
				countryCode = 'ad';
			}
			
			elem.find('label').first().append(jQuery('<img src="/themes/contrib/bootstrap_sass/images/lang/' + countryCode +'_lan.svg" />'));
		}
	});
	
	$('form.change-language-modal-form .btn-primary').click(function(e) {
		e.preventDefault();
		var selectedLangCode = null;
		var targetUrl = null;
		$('form.change-language-modal-form .form-item-languages input:checked').each(function() {
			var elem = $(this);
			selectedLangCode = elem.val();
			targetUrl = elem.attr('data-switch-url-' + selectedLangCode);
			if (targetUrl) {
				window.location = targetUrl;
			}
		});
		
		//var targetUrl = $('form.change-language-modal-form .form-item-languages input[value=' + selectedLangCode + ']')
	})
	.mousedown(function(e) {
		e.preventDefault();
	});
});
