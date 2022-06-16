(function($) {
	'use strict';
	
	function calculateAge(birthday) { // birthday is a date
		var now = new Date(); //.now();
		now.setDate(now.getDate() - 1);
   		var ageDifMs = now.getTime() - birthday;
   		var ageDate = new Date(ageDifMs); // miliseconds from epoch
   		return Math.abs(ageDate.getUTCFullYear() - 1970);
 	}
	
	$(document).ready(function() {
		var minAgeBuying = $('#edit-birthdate').attr('data-min-age-buying');
		function onBirthdateChange() {
			var newBirthDate = new Date($(this).val());
			var newAge = calculateAge(newBirthDate);
			if (newAge < minAgeBuying) {
				jQuery('#profile-underage-warning-container').removeClass('hidden');
			} else {
				jQuery('#profile-underage-warning-container').addClass('hidden');
			}
		}
		
		$('#edit-birthdate').change(onBirthdateChange);
		
		var newBirthDate = new Date($('#edit-birthdate').val());
		var newAge = calculateAge(newBirthDate);
		if (newAge < minAgeBuying) {
			jQuery('#profile-underage-warning-container').removeClass('hidden');
		} else {
			jQuery('#profile-underage-warning-container').addClass('hidden');
		}
	});
	
})(jQuery);
