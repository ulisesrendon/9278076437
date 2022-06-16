(function(jQuery) {
	'use strict';
	
	function onUpload(numPendingUploads) {
		if (numPendingUploads > 0) {
			return;
		}
		
		jQuery('#edit-submit').attr('disabled', false);
    	jQuery('#gv-fplus-checkout-shipping-documents-form').submit();
	}
	
	jQuery(document).ready(function() {
		jQuery('.shipping-option-data-integrants img').each(function() {
			var elem = jQuery(this);
			var imgSrc = elem.attr('data-src');
			elem.attr('src', imgSrc);
		});
		
		var numPendingUploads = jQuery('.document-upload-iframe').length;
		if (numPendingUploads <= 0) {
			jQuery('#edit-submit').attr('disabled', false);
    		jQuery('#gv-fplus-checkout-shipping-documents-form').submit();
		}
		window.addEventListener("message", function(event){
       		//var iframeName = event.source.frameElement.name;
       		
       		if (!event || !event.data) {
    			return;
    		}
    		
    		if (event.data != 'OK') {
    			return;
    		}
    		
       		//jQuery('iframe[name="' + iframeName + '"]').css('display', 'none');
       		--numPendingUploads;
       		onUpload(numPendingUploads);
   		});
	});
})(jQuery);
