function processCookiebotDialog() {
	jQuery('#CybotCookiebotDialogBodyContentText .refuse-all-cookies').click(function(e) {
    	//jQuery(document).on('click', '#CybotCookiebotDialogBodyContentText .refuse-all-cookies', function(e) {
    		e.preventDefault();
    		Cookiebot.submitCustomConsent(false, false, false);
			Cookiebot.hide();
    	});
    
    	var acceptAllLabel = jQuery('#CybotCookiebotDialogBodyButtonAccept').text();
    	var acceptSelectionLabelMap = {
    		'es': 'Aceptar selección',
    		'ca': 'Acceptar selecció',
    		'fr': 'Accepter la sélection',
    		'en': 'Accept selection',
    		'ru': 'Принять выбор'
    	};
    	
    	var acceptAllLabelMap = {
    		'es': 'Aceptar todas',
    		'ca': 'Acceptar totes',
    		'fr': 'Accepter tout',
    		'en': 'Accept all',
    		'ru': 'Принять все'
    	};
    
    	var htmlLang = jQuery('#CybotCookiebotDialog').attr('lang');
    	function allCategoriesChecked() {
    		var necessary = jQuery('#CybotCookiebotDialogDetailBodyContent #CybotCookiebotDialogBodyLevelButtonNecessary').is(':checked');
    		var preferences = jQuery('#CybotCookiebotDialogDetailBodyContent #CybotCookiebotDialogBodyLevelButtonPreferences').is(':checked');
    		var statistic = jQuery('#CybotCookiebotDialogDetailBodyContent #CybotCookiebotDialogBodyLevelButtonStatistics').is(':checked');
    		var marketing = jQuery('#CybotCookiebotDialogDetailBodyContent #CybotCookiebotDialogBodyLevelButtonMarketing').is(':checked');
    		return (necessary && preferences && statistic && marketing);
    	}
    	
    	if (allCategoriesChecked()) {
    		jQuery('#CybotCookiebotDialogBodyButtonAccept').text(acceptAllLabelMap[htmlLang]);
    	} else {
    		jQuery('#CybotCookiebotDialogBodyButtonAccept').text(acceptSelectionLabelMap[htmlLang]);
    	}
    	
    	jQuery('#CybotCookiebotDialogDetailBodyContent .CybotCookiebotDialogBodyLevelButton').change(function() {
    		if (allCategoriesChecked()) {
    			jQuery('#CybotCookiebotDialogBodyButtonAccept').text(acceptAllLabelMap[htmlLang]);
    		} else {
    			jQuery('#CybotCookiebotDialogBodyButtonAccept').text(acceptSelectionLabelMap[htmlLang]);
    		}
    	});
}

window.onload = function() {
	processCookiebotDialog();
    	
   window.addEventListener('CookiebotOnDialogDisplay', function (e) {
   	processCookiebotDialog();
   }, false);
};