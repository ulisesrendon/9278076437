(function($) {
	'use strict';
	
	Drupal.behaviors.addInsuranceModalForm = {
    	attach: function(context, settings) {
    		if (jQuery(context).find('.add-insurance-modal-form').length > 0) {
	    		var selectedInsurances = jQuery('.add-insurance-modal-form .form-item-selected-insurances select').val();
				if (!selectedInsurances) {
					selectedInsurances = [];
				}
				
				if (selectedInsurances.length <= 0) {
					jQuery('.add-insurance-modal-form .js-form-submit.form-submit.btn.btn-primary').attr('disabled', 'disabled');
				} else {
					jQuery('.add-insurance-modal-form .js-form-submit.form-submit.btn.btn-primary').removeAttr('disabled');
				}
    		}
    	}
  	};
	
	$(document).ready(function() {
		$('.add-insurance-modal-form .js-form-submit.form-submit.btn.btn-primary').attr('disabled', 'disabled');
		$(document).on('click', '.add-insurance-modal-form .btn.add-insurance-btn', function(e) {
			e.preventDefault();
			
			var elem = jQuery(this);
			var insuranceID = elem.attr('data-insurance-id');
			
			var selectedInsurances = $('.add-insurance-modal-form .form-item-selected-insurances select').val();
			if (!selectedInsurances) {
				selectedInsurances = [];
			}
			
			if (!selectedInsurances.includes(insuranceID)) {
				selectedInsurances.push(insuranceID);
			} else {
				selectedInsurances = selectedInsurances.filter(function(elem) {return elem != insuranceID;});
			}
			
			$('.add-insurance-modal-form .form-item-selected-insurances select').val(selectedInsurances);
			
			elem.toggleClass('selected');
			if (selectedInsurances.length <= 0) {
				$('.add-insurance-modal-form .js-form-submit.form-submit.btn.btn-primary').attr('disabled', 'disabled');
			} else {
				var numAvailableComplements = $('.add-insurance-modal-form .btn.add-insurance-btn').length;
				$('.add-insurance-modal-form .js-form-submit.form-submit.btn.btn-primary').removeAttr('disabled');
				
				if (numAvailableComplements == 1) {
					$('.add-insurance-modal-form .js-form-submit.form-submit.btn.btn-primary').click();
				}
			}
		});
	});
	
})(jQuery);
