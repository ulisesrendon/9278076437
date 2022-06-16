jQuery(document).ready(function($) {
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
	
	var recharge = 5;
	jQuery('.message-change-select').hide();
	jQuery('#gv-fplus-checkout-shipping-method-select-form .img-thumbnail img').each(function() {
		var elem = jQuery(this);
		var imgSrc = elem.attr('data-src');
		if (!imgSrc) {
			return;
		}
		
		elem.attr('src', imgSrc);
	});
	
	jQuery('.message-change-select .close').click(function(){
		jQuery('.message-change-select').hide('fast');
		jQuery('select.method-changed').removeClass('method-changed');
	});
	
	jQuery('.js-form-type-select select').change(function() {
		var currentValue = this.value;
		var changed_select = this;
		if (currentValue == recharge) {
			return;
		}
		
		if (currentValue == 'confirmed') {
			return;
		}
		
		var changed = false;
		
		jQuery('.js-form-type-select select').each(function() {
			var select = jQuery(this);
			if (select.val() == recharge) {
				return;
			}
			
			if (select.hasClass('one-service-printed') || select.hasClass('confirmed')) {
				return;
			}
			
			if(this === changed_select){
				select.removeClass('method-changed');
				return;
			}
			changed = true;
			select.addClass('method-changed');
			select.val(currentValue);
		});
		
		if(changed){
			jQuery('.message-change-select').hide('fast');
			if(currentValue == '7'){
				jQuery('.message-change-select.envio-domicilio').show('fast');
			}
			else{
				jQuery('.message-change-select.punto-recogida').show('fast');
			}
		}
	});
	
	/*function onFinishCallback(base64Image) {
		showLoader();
		$('#edit-order-integrants-shipping-info-5361347-image').val(base64Image);
		$('#edit-order-integrants-shipping-info-5361347-image-base64').val(base64Image);
		$('#edit-order-integrants-shipping-info-5361347-image-base64').trigger('change');
		$('#edit-order-integrants-shipping-info-5361347-image').attr('value', base64Image);
	}*/
	
	// process owner if applicable
	if ($('.form-item-order-owner-shipping-info-image').length > 0) {
		/*var onOwnerFinishCallbackObj = function onOwnerFinishCallback(base64Image) {
			showLoader();
			$('#edit-order-owner-shipping-info-image').val(base64Image);
			$('#edit-order-owner-shipping-info-image-base64').val(base64Image);
			$('#edit-order-owner-shipping-info-image-base64').trigger('change');
			$('#edit-order-owner-shipping-info-image').attr('value', base64Image);
		}*/
		
		(function setupOwnerWebcam () {
			//let webcamOwnerWidget = new window.fanaticsPlusWebcam('.form-item-order-owner-shipping-info-image', '#edit-order-owner-shipping-info .image-preview', '#edit-order-owner-shipping-info .edit-image-btn', null, '#edit-order-owner-shipping-info-image, #edit-order-owner-shipping-info-image-base64');
			//webcamOwnerWidget.start();
		}) ();

	}
	
	$('#edit-order-integrants-shipping-info .fieldset-wrapper > fieldset').each(function() {
		let wrapper = $(this);
		let clientID = wrapper.attr('data-client-id');
		if (!clientID) {
			return;
		}
		
		let serviceID = wrapper.attr('data-service-id');
		if (!serviceID) {
			return;
		}
		
		if (wrapper.find('.form-item-order-integrants-shipping-info-' + serviceID + '-image').length <= 0) {
			return;
		}
		
		/*var imageEditSelector = '.form-item-order-integrants-shipping-info-' + serviceID + '-image';
		var imagePreviewSelector = '#edit-order-integrants-shipping-info-' + serviceID + ' .image-preview';
		var editImageBtnSelector = '#edit-order-integrants-shipping-info-' + serviceID + ' .edit-image-btn';
		
		var onFinishCallbackObj = function onFinishCallback(base64Image) {
			showLoader();
			console.log('INTEGRANT');
			console.log(serviceID);
			var textInputSelector = '#edit-order-integrants-shipping-info-' + serviceID + '-image';
			var textAreaSelector = '#edit-order-integrants-shipping-info-' + serviceID + '-image-base64';
			$(textInputSelector).val(base64Image);
			$(textAreaSelector).val(base64Image);
			$(textAreaSelector).trigger('change');
			$(textInputSelector).attr('value', base64Image);
		}
		
		var textInputSelector = '#edit-order-integrants-shipping-info-' + serviceID + '-image';
		var textAreaSelector = '#edit-order-integrants-shipping-info-' + serviceID + '-image-base64';
		var webcamIntegrantWidget = new window.fanaticsPlusWebcam(imageEditSelector, imagePreviewSelector, editImageBtnSelector, null, textInputSelector + ', ' + textAreaSelector);
		
		webcamIntegrantWidget.start();*/
	});
	
	jQuery('.edit-image-btn').off('click').click(function() {
		var editBtn = jQuery(this);
		var wrapper = editBtn.closest('.js-form-wrapper').first();
		
		var wrapperID = wrapper.attr('id');
		$('#finish-crop').off('click');
		if (wrapperID == 'edit-order-owner-shipping-info') {
			var webcamOwnerWidget = new window.fanaticsPlusWebcam('.form-item-order-owner-shipping-info-image', '#edit-order-owner-shipping-info .image-preview', '#edit-order-owner-shipping-info .edit-image-btn', function() {showLoader();}, '#edit-order-owner-shipping-info-image, #edit-order-owner-shipping-info-image-base64');
			webcamOwnerWidget.start();
			
			$('#webcam-app').removeClass('hidden');
			$('.form-item-image').removeClass('hidden');
		} else {
			var serviceID = wrapper.attr('data-service-id');
			var imageEditSelector = '.form-item-order-integrants-shipping-info-' + serviceID + '-image';
			var imagePreviewSelector = '#edit-order-integrants-shipping-info-' + serviceID + ' .image-preview';
			var editImageBtnSelector = '#edit-order-integrants-shipping-info-' + serviceID + ' .edit-image-btn';
			var textInputSelector = '#edit-order-integrants-shipping-info-' + serviceID + '-image';
			var textAreaSelector = '#edit-order-integrants-shipping-info-' + serviceID + '-image-base64';
			var webcamIntegrantWidget = new window.fanaticsPlusWebcam(imageEditSelector, imagePreviewSelector, editImageBtnSelector, function() {showLoader();}, textInputSelector + ', ' + textAreaSelector);
			webcamIntegrantWidget.start();
			
			$('#webcam-app').removeClass('hidden');
			$('.form-item-image').removeClass('hidden');
		}

	});
	
	jQuery('.gv-fplus-checkout-shipping-method-select-form').submit(function() {
		showLoader();
	});
	
	/*var webcamWidget = new window.fanaticsPlusWebcam('.form-item-order-integrants-shipping-info-5361347-image', '.image-preview', '.edit-image-btn', onFinishCallback);
	webcamWidget.start();*/

});
