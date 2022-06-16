(function(jQuery, Drupal, drupalSettings, window) {
	'use strict';
	
	window.dataLayer = window.dataLayer || [];
	
	function getCommaSeparatedTwoDecimalsNumber(number) {
    	var fixedNumber = Number.parseFloat(number).toFixed(2);
    	return String(fixedNumber).replace('.', ",");
  	}
	
	jQuery(document).ready(function($) {
		var order = drupalSettings.checkout_complete.order;
		if (!order) {
			return;
		}
		
		if (!dataLayer) {
			return;
		}
		
		var services = [];
		for (var i = 0; i < order.Services.length; ++i) {
			var service = order.Services[i];
			var newService = {
				'id': service.SeasonPassData.IDProduct,
				'name': service.SeasonPassData.Product,
				'price': getCommaSeparatedTwoDecimalsNumber(service.SeasonPassData.SeasonPassAmount),
				'brand': 'Grandvalira',
				'category': 'Forfait Temporada',
				'quantity': 1
			};
			
			services.push(newService);
			
			if (service.SeasonPassData.IDInsurance) {
				services.push({
					'id': service.SeasonPassData.IDInsurance,
					'name': service.SeasonPassData.Insurance,
					'price': getCommaSeparatedTwoDecimalsNumber(service.SeasonPassData.InsuranceAmount),
					'brand': 'Grandvalira',
					'category': 'Forfait Temporada',
					'quantity': 1
				});
			}
			
		}
		
		window.dataLayer = window.dataLayer || [];
		dataLayer.push({
			'event': 'transactionComplete',
			'ecommerce': {
				'currencyCode': 'EUR',
				'purchase': {
					'actionField': {
						'id': order.BookingLocator,
						'affiliation': '',
						'revenue': getCommaSeparatedTwoDecimalsNumber(order.SalesAmount),
						'tax':'0.00',
						'shipping': '0.00',
						'coupon': ''
					},
					'products':services
				}
			}
		});
		
	});
	
})(jQuery, Drupal, drupalSettings, this);
