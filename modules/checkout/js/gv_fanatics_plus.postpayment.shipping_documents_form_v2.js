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



		const labelsEditDocument = document.querySelectorAll("label[id^=edit-shipping-documents-]");
		const filesEditDocument = document.querySelectorAll("input[id^=edit-shipping-documents-]");
		const containerEditDocument = document.querySelectorAll("small[id^=edit-shipping-documents-]");
		for (let i = 0; i < filesEditDocument.length; i++ ){
			let uploadIcon = document.createElement("div");
			uploadIcon.classList.add("shipping-documents-uploadIcon");
			uploadIcon.innerHTML = `<svg width="28" height="33" viewBox="0 0 28 33" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M3.92622 11.1908L5.82315 13.0877C2.37814 17.5372 2.9817 23.8975 7.20215 27.6196C11.4226 31.3417 17.8084 31.1455 21.7925 27.1714C25.5935 23.3702 25.9725 17.3346 22.6768 13.0877L24.5747 11.1898C28.8654 16.4271 28.5665 24.1685 23.6781 29.057C18.4709 34.2641 10.029 34.2641 4.82189 29.057C-0.0665755 24.1685 -0.365446 16.4271 3.92622 11.1908ZM15.5831 5.87709L15.5831 20.962H12.9168V5.87709L8.59313 10.2008L6.70751 8.3152L14.25 0.772725L21.7925 8.3152L19.9068 10.2008L15.5831 5.87709Z" fill="#348BA6"/>
				</svg>
			`;
			uploadIcon.addEventListener("click", function(e){
				e.stopImmediatePropagation();
				e.preventDefault();
			});

			containerEditDocument[i].insertBefore(uploadIcon, containerEditDocument[i].firstElementChild);

			containerEditDocument[i].addEventListener("click", function () {
				filesEditDocument[i].click();
			});
		}

		const left_aside_original = document.querySelector("#left_aside");
		const big_container = document.querySelector("#block-fanatics-content");
		const left_aside = left_aside_original.cloneNode(true);
		left_aside_original.remove();

		big_container.insertBefore(left_aside, big_container.firstElementChild);

		window.addEventListener("load", function(){
			const main_avatrs = document.querySelectorAll(".step-need-documents .shipping-option-data-integrants .integrant img");
			
			for (let i = 0; i < main_avatrs.length; i++) {
				let aside_avtr = main_avatrs[i].getAttribute("data-index");
				aside_avtr = document.querySelector(`.left_aside_editdocitem_img [data-index="${aside_avtr}"]`);
				aside_avtr.src = main_avatrs[i].getAttribute("src");
			}
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
