(function(jQuery, Drupal) {
	
	'use strict';
	
	function showLoader() {
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
	}
	
	Drupal.behaviors.onShippingDataFormRefresh = {
    	attach: function(context, settings) {
    		if (!context) {
    			return;
    		}
    		
    		
			var contextElem = jQuery(context);
			contextElem.find('.shipping-option-data-integrants .integrant img').each(function() {
				var elem = jQuery(this);
				var imgSrc = elem.attr('data-src');
				if (!imgSrc) {
					return;
				}
				elem.attr('src', 'data:image/jpeg;base64,' + imgSrc);
			});
			
			var defaultMask = "********-***-***";
			contextElem.find('.recharge-options-data .form-type-textfield input.form-text').each(function() {
				var elem = jQuery(this);
				var prefix = elem.attr('data-wtp-prefix');
				var suffix = elem.attr('data-wtp-suffix');
				
				var mask = '';
				if (!prefix && !suffix) {
					mask = defaultMask;
				}
	
				prefix = prefix.replaceAll('9', '\\9').replaceAll('A', '\\A');
				suffix = suffix.replaceAll('9', '\\9').replaceAll('A', '\\A');
	
				mask = (prefix) ? (prefix + '-') : '********-';
				mask += '***-';
				mask += (suffix) ? suffix : '***';
				
				elem.inputmask({
					mask: mask, 
					'autoUnmask' : true,
					definitions: {
			    		'*': {
			    			validator: "[0-9A-Za-z]",
			    			casing: "upper"
			    		}
		   			}
				});
				
				elem.keyup(function() {
					var wtpCode = jQuery(this).val();
	 				if (!wtpCode || wtpCode == '') {
	 					return;
	 				}
	 				
	 				if (!jQuery(this).inputmask("isComplete")) {
	 					elem.closest('.recharge-options-data').find('.form-submit').attr('disabled', 'disabled');
						return;
	  				}
	  				
	  				elem.closest('.recharge-options-data').find('.form-submit').removeAttr('disabled');
				});
				
				/*elem.closest('.recharge-options-data').find('.form-submit').mousedown(function() {
					showLoader();
				});*/
			});
    	}
  	};
	
	jQuery(document).ready(function() {
		jQuery('.shipping-method-option').click(function() {
			var elem = jQuery(this);
			var targetID = elem.attr('data-target-id');
			jQuery('.shipping-method-option').removeClass('active');
			elem.addClass('active');
		});
		
		jQuery('.shipping-method-option').first().addClass('active');
		
		jQuery('#shipping-method-selector .images img, .shipping-option-data-integrants .integrant img').each(function() {
			var elem = jQuery(this);
			var imgSrc = elem.attr('data-src');
			if (!imgSrc) {
				return;
			}
			
			elem.attr('src', 'data:image/jpeg;base64,' + imgSrc);
		});
		
		var defaultMask = "********-***-***";
		jQuery('.recharge-options-data .form-type-textfield input.form-text').each(function() {
			var elem = jQuery(this);
			var prefix = elem.attr('data-wtp-prefix');
			var suffix = elem.attr('data-wtp-suffix');
			
			var mask = '';
			if (!prefix && !suffix) {
				mask = defaultMask;
			}

			prefix = prefix.replaceAll('9', '\\9').replaceAll('A', '\\A');
			suffix = suffix.replaceAll('9', '\\9').replaceAll('A', '\\A');

			mask = (prefix) ? (prefix + '-') : '********-';
			mask += '***-';
			mask += (suffix) ? suffix : '***';
			
			elem.inputmask({
				mask: mask, 
				'autoUnmask' : true,
				definitions: {
				    '*': {
				    	validator: "[0-9A-Za-z]",
				    	casing: "upper"
				    }
		   		}
			});
			
			elem.keyup(function() {
				var wtpCode = jQuery(this).val();
 				if (!wtpCode || wtpCode == '') {
 					return;
 				}
 				
 				if (!jQuery(this).inputmask("isComplete")){
 					elem.closest('.recharge-options-data').find('.form-submit').attr('disabled', 'disabled');
					return;
  				}
  				
  				elem.closest('.recharge-options-data').find('.form-submit').removeAttr('disabled');
			});
			
			/*elem.closest('.recharge-options-data').find('.form-submit').mousedown(function() {
				showLoader();
			});*/
			
		});
		
		jQuery('.gv-fplus-checkout-shipping-data-form').submit(function() {
			showLoader();
		});
	});
	
})(jQuery, Drupal);
