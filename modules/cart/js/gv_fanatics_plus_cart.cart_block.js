(function(jQuery, Drupal) {
	
	'use strict';
	
	function enableFullscreenLoader() {
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
	
	function disableFullscreenLoader() {
		if (!jQuery('.loader-checkout').hasClass('hidden')) {
  			jQuery('.loader-checkout').addClass('hidden');
  		}
	}
	
	Drupal.behaviors.onCartRefresh = {
    	attach: function(context, settings) {
			if (jQuery(context).hasClass('gv-fanatics-plus-cart-block-form')) {
				if (!jQuery('.loader-checkout').hasClass('hidden')) {
  					jQuery('.loader-checkout').addClass('hidden');
  				}
  				
				jQuery('.gv-fanatics-plus-cart-block-form .product-owner-image img').each(function() {
					var elem = jQuery(this);
					var imgSrc = elem.attr('data-src');
					if (!imgSrc) {
						return;
					}
					
					elem.attr('src', 'data:image/jpeg;base64,' + imgSrc);
				});
				
				jQuery('#booking-referral').inputmask({
					 'mask': "******", 
					 'autoUnmask' : true,
					 definitions: {
      					'*': {
        					validator: "[0-9A-Za-z!#$%&'*+/=?^_`{|}~\-]",
        					casing: "upper"
      					}
    				}
				});
  				
  				var input = document.querySelector("#booking-referral");
				if (input) {
					//input.addEventListener('blur', function() {
					jQuery("#booking-referral").keyup(function() {
		 				var referralCode = jQuery("#booking-referral").val();
		 				if (!referralCode || referralCode == '') {
		 					jQuery('#booking-referral').removeClass('error');
		 					jQuery('.checkout-form-main-actions #edit-submit').removeAttr('disabled');
		 					return;
		 				}
		 				
		 				if (!jQuery('#booking-referral').inputmask("isComplete")){
		 					jQuery('#booking-referral').addClass('error');
		 					jQuery('.checkout-form-main-actions #edit-submit').attr('disabled', true);
		  					return;
		  				}
		  				
		  				jQuery('#booking-referral').removeClass('error');
		  				jQuery('.checkout-form-main-actions #edit-submit').removeAttr('disabled');
		  				
		  				enableFullscreenLoader();
		  				
		 				jQuery('input[name="referral"]').val(referralCode);
		 				jQuery('input[name="referral"]').blur(function(){}).blur();
		 			});
 				}
 				
 				var referralCode = jQuery('#booking-referral').val();
				if (referralCode && referralCode != '' && !jQuery('#booking-referral').inputmask("isComplete")){
 					jQuery('#booking-referral').addClass('error');
 					jQuery('.checkout-form-main-actions #edit-submit').attr('disabled', true);
  					return;
  				} else if (referralCode && referralCode != '') {
  					jQuery('#booking-referral').removeClass('error');
  					jQuery('.checkout-form-main-actions #edit-submit').removeAttr('disabled');
  				}
  				
				jQuery('body').removeClass('open-cart');
				
				jQuery(".header-cart, .close-mobile").off('click').on('click', function(){
					jQuery('body').toggleClass('open-cart');
				});
			}
    	}
  	};
	
	jQuery(document).ready(function($) {
		
		jQuery('#booking-referral').inputmask({
			'mask': "******", 
			'autoUnmask' : true,
			definitions: {
			    '*': {
			    	validator: "[0-9A-Za-z!#$%&'*+/=?^_`{|}~\-]",
			    	casing: "upper"
			    }
		   	}
		});
		
		var input = document.querySelector("#booking-referral");
		if (input) {
			//input.addEventListener('blur', function() {
 			jQuery("#booking-referral").keyup(function() { 				
 				var referralCode = $("#booking-referral").val();
 				if (!referralCode || referralCode == '') {
 					jQuery('#booking-referral').removeClass('error');
 					jQuery('.checkout-form-main-actions #edit-submit').removeAttr('disabled');
 					return;
 				}
 				
 				if (!$('#booking-referral').inputmask("isComplete")){
 					$('#booking-referral').addClass('error');
 					jQuery('.checkout-form-main-actions #edit-submit').attr('disabled', true);
  					return;
  				}
  				
  				$('#booking-referral').removeClass('error');
  				jQuery('.checkout-form-main-actions #edit-submit').removeAttr('disabled');
  				
  				enableFullscreenLoader();
  				
 				$('input[name="referral"]').val(referralCode);
 				$('input[name="referral"]').blur(function(){}).blur();
 			});
 			
		}
		
 		var referralCode = jQuery('#booking-referral').val();
		if (referralCode && referralCode != '' && !jQuery('#booking-referral').inputmask("isComplete")){
 			jQuery('#booking-referral').addClass('error');
 			jQuery('.checkout-form-main-actions #edit-submit').attr('disabled', true);
  			return;
  		} else if (referralCode && referralCode != '') {
  			jQuery('#booking-referral').removeClass('error');
  			jQuery('.checkout-form-main-actions #edit-submit').removeAttr('disabled');
  		}
		
		jQuery('.gv-fanatics-plus-cart-block-form .product-owner-image img').each(function() {
			var elem = jQuery(this);
			var imgSrc = elem.attr('data-src');
			if (!imgSrc) {
				return;
			}
			
			elem.attr('src', 'data:image/jpeg;base64,' + imgSrc);
		});
		
		jQuery(document).on('click', '.delete-line-item .btn', function(e) {
			e.preventDefault();
			
			var elem = jQuery(this);
			var productId = elem.attr('data-service-id');
	
			if (!productId) {
				return;
			}
	
			$('.form-item-product-id input').val(productId);
			$('#edit-delete-product').trigger('mousedown')
		});
		
		jQuery(document).on('gv-fanatics-plus:enable-fullscreen-loader', function(e) {
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
		
		/*jQuery(document).on('submit', '#gv-fanatics-plus-cart-block-form', function(e) {			
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
		});*/
		
		jQuery(".header-cart, .close-mobile").off('click').on('click', function(){
			jQuery('body').toggleClass('open-cart');
		});
	});
	
})(jQuery, Drupal);
