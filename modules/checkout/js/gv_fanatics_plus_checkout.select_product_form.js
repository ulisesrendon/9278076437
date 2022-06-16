(function(jQuery, Drupal) {
	
	'use strict';
	
	Drupal.behaviors.onSelectProductsForm = {
    	attach: function(context, settings) {
    		if (jQuery(context).find('.gv-fanatics-plus-checkout-select-product #edit-submit').length > 0) {
    			jQuery('.gv-fanatics-plus-checkout-select-product #edit-submit').mousedown( function(e) {
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
  	};
	
	jQuery(document).ready(function($) {
		/*var submitBtnLabel = $('.gv-fanatics-plus-checkout-select-product #edit-submit').text();
		$('.gv-fanatics-plus-checkout-select-product #edit-submit').html(submitBtnLabel);
		var submitBtnLabel = $('.gv-fanatics-plus-checkout-select-product #edit-submit').text();
		$('.gv-fanatics-plus-checkout-select-product #edit-submit').html($(submitBtnLabel));*/
		
		jQuery('#product-list .product-list-item .img-container img').each(function() {
			var elem = jQuery(this);
			var imgSrc = elem.attr('data-src');
			if(imgSrc){
				elem.attr('src', 'data:image/jpeg;base64,' + imgSrc);
			}
		});
		
		$('#edit-theme').select2({placeholder: Drupal.t("Filter by..."), closeOnSelect: false});
		var productInfo = {};
		jQuery('.product-list-item').each(function() {
			var productElem = jQuery(this);
			var productID = productElem.attr('data-product-id');
			var productThemes = productElem.attr('data-themes').split(',');
			productThemes.forEach(function(theme) {
				if (!theme || theme.length <= 0) {
					return;
				}
				
				if (!productInfo[theme]) {
					productInfo[theme] = [productID];
				} else {
					productInfo[theme].push(productID);
				}
			});
		});
		
		jQuery('.filter-container .pre_cont a.btn.outline').click(function(e){
			jQuery('.filter-container').addClass('open');
			
			
			setTimeout(function(){
				jQuery('#edit-theme').select2(
					{
						placeholder: Drupal.t("Filter by..."),
						closeOnSelect: false
					}
				).on("select2:closing", 
					function(e) {
						e.preventDefault();
					}
				).select2('open');
			}, 500);
		});
		
		jQuery('.filter-container .pre_cont a.close-btn').click(function(e){
			jQuery('#edit-theme').select2({
				placeholder: Drupal.t("Filter by..."),
				closeOnSelect: false
			});
			jQuery('.filter-container').removeClass('open');
			e.preventDefault();
			e.stopPropagation();
		});
		
		jQuery('.filter-container .post_cont a').click(function(e){
			var selectedValues = jQuery('#edit-theme').val();
			var count = selectedValues.length;
	    	filterProducts(selectedValues);
	    	jQuery('#edit-theme').select2({
				placeholder: Drupal.t("Filter by..."),
				closeOnSelect: false
			});
	    	if(count > 0){
	    		jQuery('.filter-container .counter').removeClass('hidden').text(count);
	    	}
	    	else{
	    		jQuery('.filter-container .counter').addClass('hidden').text('');
	    	}
			jQuery('.filter-container').removeClass('open');
			e.preventDefault();
			e.stopPropagation();
		});
		
		function filterProducts(themes) {
			var allProductsVisible = false;
			var visibleProductIDs = {};
			themes.forEach(function(theme) {
				var products = productInfo[theme];
				products.forEach(function(product) {
					visibleProductIDs[product] = product;
				})
			});
			
			if (themes.length <= 0) {
				allProductsVisible = true;
			}
			
			jQuery('.product-list-item').each(function() {
				var productElem = jQuery(this);
				
				if (allProductsVisible) {
					productElem.removeClass('hidden');
					return;
				}
				
				var productID = productElem.attr('data-product-id');
				if (!visibleProductIDs[productID]) {
					productElem.removeClass('hidden').addClass('hidden');
				} else {
					productElem.removeClass('hidden');
				}
			});
		}
		
		$('#edit-theme').change(function() {
			if(jQuery('.bitaboot-lg, .bitaboot-md').length > 0){
				var selectedValues = jQuery('#edit-theme').val();
				filterProducts(selectedValues);				
			}
		});
			
		$(document).on('click', '.btn.add-product-btn', function(e) {
			e.preventDefault();
			
			var elem = jQuery(this);
			var productId = elem.attr('data-product-id');
			var productCode = elem.attr('data-product-code');
			
			$('#edit-product-id').val(productId);
			$('#edit-product-code').val(productCode);
			
			var modalBaseUrl = jQuery('#edit-open-modal').attr('data-base-url');
			Drupal.ajax.instances.forEach(function(elem) {
				if (!elem) {
					return;
				}
				
				if (elem.selector == '#edit-open-modal') {
					elem.options.url = modalBaseUrl + '/' + productCode + '/' + productId;
				}
			});
			
			jQuery('#edit-open-modal').attr('href', modalBaseUrl + '/' + productCode);
			jQuery('#edit-open-modal').click();
		});
		
		$(document).on('mousedown', '.gv-fanatics-plus-checkout-select-product #edit-submit', function(e) {
			$(document).trigger({
				type: "gv-fanatics-plus:enable-fullscreen-loader"
			});
		});
	});
	
})(jQuery, Drupal);
