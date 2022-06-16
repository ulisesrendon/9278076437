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
	
	Drupal.behaviors.onInvitationModal = {
    	attach: function(context, settings) {}
  	};
	
	jQuery(document).ready(function($) {
		jQuery('.use-invitation-confirmation-modal-form').click(function() {
			enableFullscreenLoader();
		});
	});
	
})(jQuery, Drupal);
