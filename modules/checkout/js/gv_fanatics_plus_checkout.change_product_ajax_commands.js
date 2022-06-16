(function ($, window, Drupal, drupalSettings) {

  'use strict';
  
  window.dataLayer = window.dataLayer || [];
  
  function getCommaSeparatedTwoDecimalsNumber(number) {
    const fixedNumber = Number.parseFloat(number).toFixed(2);
    return String(fixedNumber).replace('.', ",");
  }
  
  // Command to Close off canvas dialogs
  Drupal.AjaxCommands.prototype.gvFanaticsPlusCloseOffCanvasDialog = function(ajax, response, status) {
    var selector = response.selector;
    // refresh cart
    $('.ui-dialog-off-canvas .ui-dialog-titlebar-close').trigger('click');
    $('.ui-dialog-off-canvas-bg').remove();
  }
  
  Drupal.AjaxCommands.prototype.gvFanaticsPlusAddOffCanvasDialogBackground = function(ajax, response, status) {
  	var selector = response.selector;
  	$('<div class="ui-dialog-off-canvas-bg"><div class="inner-content"></div></div>').insertBefore($('.ui-dialog-off-canvas'));
  	$('.ui-dialog-off-canvas').addClass('anim-on');
  	$('.ui-dialog.ui-dialog-off-canvas .ui-dialog-titlebar button').on('click', function(e) {
		 $('.ui-dialog-off-canvas-bg').remove();
	});
  }
  
  // Command to Slide Down page elements.
  Drupal.AjaxCommands.prototype.gvFanaticsPlusRefreshCart = function(ajax, response, status) {
    var selector = response.selector;
    // refresh cart
    $('#gv-fanatics-plus-cart-block-form #edit-load-form').mousedown();
    $(document).trigger({
		type: "gv-fanatics-plus:enable-fullscreen-loader"
	});
  }
  
  // Command to Slide Down page elements.
  Drupal.AjaxCommands.prototype.gvFanaticsPlusSetProduct = function(ajax, response, status) {
    var selector = response.selector;
    var productCode = response.productCode;
    
    $('.product-list-item .add-product-btn').removeClass('selected');
    if (productCode) {
   		$('.product-list-item[data-product-code="' + productCode + '"] .add-product-btn').addClass('selected');
    }
    
    // refresh cart
    $('#gv-fanatics-plus-cart-block-form #edit-load-form').mousedown();
    $(document).trigger({
		type: "gv-fanatics-plus:enable-fullscreen-loader"
	});
  }
  
  Drupal.AjaxCommands.prototype.gvFanaticsPlusEnableDisableProductFormSubmit = function(ajax, response, status) {
    var selector = response.selector;
    var enable = response.enable;
    if (enable) {
    	$('.checkout-form-main-actions #edit-submit').removeAttr('disabled');
    } else {
    	$('.checkout-form-main-actions #edit-submit').attr('disabled', 'disabled');
    }
    
  }
  
  Drupal.AjaxCommands.prototype.gvFanaticsPlusEnableFullscreenLoader = function(ajax, response, status) {
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
  
  Drupal.AjaxCommands.prototype.gvFanaticsPlusDisableFullscreenLoader = function(ajax, response, status) {
  	if (!jQuery('.loader-checkout').hasClass('hidden')) {
  		jQuery('.loader-checkout').addClass('hidden');
  	}
  }
  
  Drupal.AjaxCommands.prototype.gvFanaticsPlusStartIntroJs = function(ajax, response, status) {
		var tour = introJs();
		
		tour.setOption('tooltipPosition', 'auto');
		tour.setOption('positionPrecedence', ['left', 'right', 'top', 'bottom']);
		tour.setOption('nextLabel', Drupal.t('Next'));
		tour.setOption('prevLabel', Drupal.t('Previous'));
		tour.setOption('skipLabel', Drupal.t('Skip'));
		tour.setOption('doneLabel', Drupal.t('Done'));
		tour.setOption('hidePrev', true);
		tour.setOption('hideNext', true);
		
		tour.start();
  }
  
  Drupal.AjaxCommands.prototype.gvFanaticsPlusTagManagerAddProductAjaxCommand = function(ajax, response, status) {
		var selector = response.selector;
		var booking_service = response.booking_service;
		
		if (!booking_service) {
			return;
		}
		
		var price = booking_service.SeasonPassData.SeasonPassAmount;
		if (booking_service.SeasonPassData.DiscountAmount > 0) {
			price = booking_service.SeasonPassData.SeasonPassAmount - booking_service.SeasonPassData.DiscountAmount;
		}
		
		if (!dataLayer) {
			return;
		}
		
		dataLayer.push({
			'event': 'addToCart',
			'ecommerce': {
				'currencyCode': 'EUR',
				'add': {
					'products': [{
						'id': booking_service.SeasonPassData.IDProduct,
						'name': booking_service.Service,
						'price': getCommaSeparatedTwoDecimalsNumber(price),
						'brand': 'Grandvalira',
						'category': 'Forfait Temporada',
						'quantity': 1
					}],
				}
		}});
  }
  
  Drupal.AjaxCommands.prototype.gvFanaticsPlusTagManagerRemoveProductAjaxCommand = function(ajax, response, status) {
		var selector = response.selector;
		var booking_service = response.booking_service;

		if (!booking_service) {
			return;
		}

		var price = booking_service.SeasonPassData.SeasonPassAmount;
		if (booking_service.SeasonPassData.DiscountAmount > 0) {
			price = booking_service.SeasonPassData.SeasonPassAmount - booking_service.SeasonPassData.DiscountAmount;
		}

		if (!dataLayer) {
			return;
		}
		
		dataLayer.push({
			'event': 'removeFromCart',
			'ecommerce': {
				'currencyCode': 'EUR',
				'remove': {
					'products': [{
						'id': booking_service.SeasonPassData.IDProduct,
						'name': booking_service.Service,
						'price': getCommaSeparatedTwoDecimalsNumber(price),
						'brand': 'Grandvalira',
						'category': 'Forfait Temporada',
						'quantity': 1
					}],
				}
			}
		});
  }
  
    Drupal.AjaxCommands.prototype.gvFanaticsPlusTagManagerAddInsuranceAjaxCommand = function(ajax, response, status) {
		var selector = response.selector;
		var insurance = response.insurance;

		if (!dataLayer || !insurance) {
			return;
		}

		dataLayer.push({
			'event': 'addToCart',
			'ecommerce': {
				'currencyCode': 'EUR',
				'add': {
					'products': [{
						'id': insurance.IDInsurance,
						'name': 'Seguro Temporada',
						'price': getCommaSeparatedTwoDecimalsNumber(insurance.Amount),
						'brand': 'Grandvalira',
						'category': 'Seguros',
						'quantity': 1
					}],
				}
			}
		});
  }
  
  Drupal.AjaxCommands.prototype.gvFanaticsPlusTagManagerRemoveInsuranceAjaxCommand = function(ajax, response, status) {
		var selector = response.selector;
		var insurance = response.insurance;

		if (!dataLayer || !insurance) {
			return;
		}

		dataLayer.push({
			'event': 'removeFromCart',
			'ecommerce': {
				'currencyCode': 'EUR',
				'remove': {
					'products': [{ // array de productos
						'id': insurance.IDInsurance,
						'name': 'Seguro Temporada',
						'price': getCommaSeparatedTwoDecimalsNumber(insurance.Amount),
						'brand': 'Grandvalira',
						'category': 'Seguros',
						'quantity': 1
					}],
				}
			}
		});
  }
  
    Drupal.AjaxCommands.prototype.gvFanaticsPlusTagManagerCheckoutOptionAjaxCommand = function(ajax, response, status) {
		var selector = response.selector;
		var step = response.step;
		
		if (!dataLayer || !step) {
			return;
		}
		
		dataLayer.push({
			'event': 'checkoutOption',
				'ecommerce': {
					'checkout_option': {
						'actionField': {
							'step': step,
						}
					}
				}
		});
  }
  
    Drupal.AjaxCommands.prototype.gvFanaticsPlusTagManagerInsuranceCheckoutOptionAjaxCommand = function(ajax, response, status) {
		var selector = response.selector;
		var option = response.option;

		if (!dataLayer || !option) {
			return;
		}
		
		dataLayer.push({
			'event': 'checkoutOption',
				'ecommerce': {
					'checkout_option': {
						'actionField': {
							'step': 1,
							'option': option
						}
					}
				}
		});
  	}
  	
  	Drupal.AjaxCommands.prototype.gvFanaticsPlusTagManagerCheckoutCompleteAjaxCommand = function(ajax, response, status) {
		var selector = response.selector;
		var booking = response.booking;
  	}
  	
})(jQuery, this, Drupal, drupalSettings);
 