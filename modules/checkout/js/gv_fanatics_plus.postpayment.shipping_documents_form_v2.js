(function(jQuery) {
	'use strict';
	
	jQuery(document).ready(function() {
		jQuery('.shipping-option-data-integrants img').each(function() {
			var elem = jQuery(this);
			var imgSrc = elem.attr('data-src');
			elem.attr('src', imgSrc);
		});
		
		var numPendingDocuments = jQuery('.gv-fplus-checkout-shipping-documents-form input[type="file"]').length;
		if (numPendingDocuments > 0) {
			jQuery('.gv-fplus-checkout-shipping-documents-form .form-submit.btn.btn-primary').attr('disabled', 'disabled');
		}
		
		jQuery('.gv-fplus-checkout-shipping-documents-form').submit(function() {
			var pathIds = ['cable', 'ski', 'mountain'];
  			var myIcons = new SVGMorpheus('#loaderSvg');
  			var currentIndex = 0;
  			var svgOptions = {
    			duration: 1000,
    			rotation: 'none',
    			easing: 'quint-in'
  			};
  
  			jQuery('.loader-checkout').removeClass('hidden');
  			setInterval(function() {
    			myIcons.to(pathIds[currentIndex], svgOptions, function(){
      				if (currentIndex === (pathIds.length - 1)) {
        				currentIndex = 0;
      				} else {
        				currentIndex++;
      				}
    			});
  			}, 700);
		});
	});
	
	Drupal.behaviors.document_upload_behaviour = {
		attach: function(context, settings) {
			
			if (jQuery(context).find('.ajax-new-content').length > 0) { // Stop loader
				if (!jQuery('.loader-checkout').hasClass('hidden')) {
  					jQuery('.loader-checkout').addClass('hidden');
  				}
  				
  				var numPendingDocuments = jQuery('#gv-fplus-checkout-shipping-documents-form input[type="file"]').length;
				if (numPendingDocuments > 0) {
					jQuery('.gv-fplus-checkout-shipping-documents-form .form-submit.btn.btn-primary').attr('disabled', 'disabled');
				} else {
					jQuery('.gv-fplus-checkout-shipping-documents-form .form-submit.btn.btn-primary').removeAttr('disabled');
				}
  				
				jQuery('.gv-fplus-checkout-shipping-documents-form').submit(function() {
					var pathIds = ['cable', 'ski', 'mountain'];
				  	var myIcons = new SVGMorpheus('#loaderSvg');
				  	var currentIndex = 0;
				  	var svgOptions = {
				    	duration: 1000,
				    	rotation: 'none',
				    	easing: 'quint-in'
				  	};
  
				  	jQuery('.loader-checkout').removeClass('hidden');
				  	setInterval(function() {
				    	myIcons.to(pathIds[currentIndex], svgOptions, function(){
				      		if (currentIndex === (pathIds.length - 1)) {
				        		currentIndex = 0;
				      		} else {
				        		currentIndex++;
				      		}
				    	});
				  	}, 700);
				});
			}
 		}
	}
	
	
})(jQuery);
