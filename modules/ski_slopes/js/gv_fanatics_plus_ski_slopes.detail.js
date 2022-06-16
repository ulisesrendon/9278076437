(function(jQuery) {
	
	'use strict';
	
	Drupal.behaviors.skiSlopesDetail = {
		attach: function (context) {
			if (jQuery(context).find('.menu-fanatics').length > 0 
			&& jQuery('.my-grandski-ski-slopes-history').hasClass('is-integrant')) {
				jQuery('.navigation__item.integrants').addClass('active');
			}
		}
	};
	
	jQuery(document).ready(function($) {
		var graphCanvas = jQuery('#slopes-chart');
		var graphLabels = graphCanvas.data('labels');
		var graphData = graphCanvas.data('values');
		var ctx = document.getElementById('slopes-chart').getContext('2d');
		
		var gradientFill = ctx.createLinearGradient(0, 500, 0, 100);
		gradientFill.addColorStop(0, '#EDF9FC');
		gradientFill.addColorStop(1, '#45B9DD');
		
		var myChart = new Chart(ctx, {
		    type: 'line',
		    data: {
		        labels: graphLabels,
		        datasets: [{
		            label: Drupal.t('Altitude (m)'),
		            data: graphData,
		            backgroundColor: gradientFill,
		            borderColor: '#EDF9FC'
		        }]
		    },
		    options: {
		    	maintainAspectRatio: false,
		        scales: {
		            yAxes: [{
		                ticks: {
		                    beginAtZero: false
		                }
		            }],
		             xAxes: [{
            			gridLines: {
                			color: "rgba(0, 0, 0, 0)",
            			}
        			}],
		        },
		        animation: {
 					easing: "easeInOutBack"
				},
				legend: {
            		position: "bottom"
       			},
       			gridLines: {
                    drawTicks: false,
               },
               tooltips: {
               	backgroundColor: 'rgba(52, 139, 166, 1)',
               	bodyFontSize: 13,
               	titleFontSize: 13,
               	bodyAlign: 'center',
               	titleAlign: 'center',
               	displayColors: false
               }
		    }
		});
		
		var previousVal = null;
		$('select[name="season"], select[name="day"]').change(function(e) {
			var currentVal = $(this).val();
			if (currentVal == previousVal) {
				return;
			}
			
			previousVal = currentVal;
			
			var targetUrl = $('option:selected', this).attr('data-url');
			if (!targetUrl) {
				return;
			}
			
			window.location.replace(targetUrl);
		});
		
	});
	
})(jQuery);
