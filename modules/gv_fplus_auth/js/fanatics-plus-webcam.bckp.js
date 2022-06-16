(function($) {
	
	'use strict';
	
	function start() {
		var that = this;
		
		if (!jQuery(this.imagePreviewSelector).attr('src')) {
			var dataSrc = jQuery(this.imagePreviewSelector).attr('data-src');
			if (dataSrc) {
				jQuery(this.imagePreviewSelector).attr('src', dataSrc);
			}	
		}
		
		var webcamWidget = this.create();
		
		var webcamElement = document.getElementById('webcam');
		var canvasElement = document.getElementById('webcam-canvas');
		var webcam = new Webcam(webcamElement, 'user', canvasElement, null);
		
		jQuery('#input-image-filepicker').change(function (e) {
		        var files = e.target.files;
		        var done = function (url) {
		          $('#webcam-final-image').attr('src', url).removeClass('d-none');
		          $('.md-modal').addClass('md-show');
		          cameraStarted(true);
		          _activateCropper();
		        };
		        
		        var reader;
		        var file;
		        var url;
		
		        if (files && files.length > 0) {
		          file = files[0];
		
		          if (URL) {
		            done(URL.createObjectURL(file));
		          } else if (FileReader) {
		            reader = new FileReader();
		            reader.onload = function (e) {
		              done(reader.result);
		            };
		            reader.readAsDataURL(file);
		          }
		        }
		});
		
		$("#webcam-switch").change(function () {
		    if(this.checked){
		        $('.md-modal').addClass('md-show');
		        webcam.start()
		            .then(result =>{
		               cameraStarted();
		            })
		            .catch(err => {
		                displayError();
		            });
		    }
		    else {        
		        cameraStopped();
		        webcam.stop();
		    }        
		});
		
		$('#cameraFlip').click(function() {
		    webcam.flip();
		    webcam.start();  
		});
		
		$('#closeError').click(function(e) {
			e.preventDefault();
			e.stopPropagation();
		    $("#webcam-switch").prop('checked', false).change();
		});
		
		$('.edit-image-btn').click(function(e) {
			e.preventDefault();
			$('#webcam-app').removeClass('hidden');
			$('.form-item-image').removeClass('hidden');
		});
		
		function displayError(err = ''){
		    if(err!=''){
		        $("#errorMsg").html(err);
		    }
		    $("#errorMsg").removeClass("d-none");
		    $('#webcam-app .md-modal').removeClass('md-show');
		}
		
		function cameraStarted(filePicker){
			filePicker = filePicker || false;
			if (!filePicker) {
			    $("#errorMsg").addClass("d-none");
			    $('.flash').hide();
			    $("#webcam-caption").html("on");
			    if( webcam.webcamList.length > 1){
			        $("#cameraFlip").removeClass('d-none');
			    }	
			}
			
			$('#webcam-app > .md-modal').addClass('anim-on');
			$("#webcam-control").removeClass("webcam-off");
			$("#webcam-control").addClass("webcam-on");
			$(".webcam-container").removeClass("d-none");
		    $("#wpfront-scroll-top-container").addClass("d-none");
		    window.scrollTo(0, 0); 
		    $('body').css('overflow-y','hidden');
		}
		
		function cameraStopped(){
		    $("#errorMsg").addClass("d-none");
		    $("#wpfront-scroll-top-container").removeClass("d-none");
		    $("#webcam-control").removeClass("webcam-on");
		    $("#webcam-control").addClass("webcam-off");
		    $("#cameraFlip").addClass('d-none');
		    $(".webcam-container").addClass("d-none");
		    $("#webcam-caption").html("Click to Start Camera");
		    $('.md-modal').removeClass('md-show');
		    $('body').css('overflow-y', 'auto');
		    $('.form-item-image').addClass('hidden');
		}
		
		
		$("#take-photo").click(function () {
		    beforeTakePhoto();
		    let picture = webcam.snap();
		    document.querySelector('#download-photo').href = picture;
		    _processPreview(picture);
		    afterTakePhoto();
		});
		
		$('#validate-photo').click(function() {
			_activateCropper();
		});
		
		$('#rotate-left').click(function() {
			var cropper = jQuery('#webcam-final-image').data('cropper');
			cropper.rotate(-90);
			cropper.moveTo(0, 0);
		});
		
		$('#rotate-right').click(function() {
			var cropper = jQuery('#webcam-final-image').data('cropper');
			cropper.rotate(-90);
			cropper.moveTo(0, 0);
		});
		
		$('#finish-crop').click(function() {
			var cropper = jQuery('#webcam-final-image').data('cropper');
			var canvas = cropper.getCroppedCanvas({
			  width: 134,
			  height: 164,
			  fillColor: '#fff',
			  imageSmoothingEnabled: false,
			  imageSmoothingQuality: 'high',
			});
			
			var base64Image = canvas.toDataURL("image/jpeg");
			$('#webcam-final-image').attr('src', base64Image);
			
			if (that.onFinishCallback) {
				that.onFinishCallback(base64Image);
			}
			
			//$('#edit-image').val(base64Image);
			//$('#edit-image-base64').val(base64Image);
			//$('#edit-image-base64').trigger('change');
			//$('#edit-image').attr('value', base64Image);
			$(that.imagePreviewSelector).attr('src', base64Image);
			
			removeCapture();
			
			$("#webcam-switch").prop("checked", false).change();
			that.reset();
		});
		
		function beforeTakePhoto(){
		    $('.flash')
		        .show() 
		        .animate({opacity: 0.3}, 500) 
		        .fadeOut(500)
		        .css({'opacity': 0.7});
		        
		    window.scrollTo(0, 0); 
		    $('#webcam-control').addClass('d-none');
		    $('#cameraControls').addClass('d-none');
		}
		
		function afterTakePhoto(){
		    webcam.stop();
		    $('#webcam-canvas').removeClass('d-none');
		    $('#take-photo').addClass('d-none');
		    $('#exit-app').removeClass('d-none');
		    $('#download-photo').removeClass('d-none');
		    $('#resume-camera').removeClass('d-none');
		    $('#cameraControls').removeClass('d-none');
		    $('#validate-photo').removeClass('d-none');
		}
		
		function _processPreview(picture) {
			$('#webcam-final-image').attr('src', picture);
			$('#webcam-final-image').removeClass('d-none');
			$('#webcam-canvas').addClass('d-none');
		}
		
		function _activateCropper() {
			$('#webcam-final-image').cropper({
		  		aspectRatio: 134 / 164,
		  		viewMode: 2,
		  		zoomable: false,
		  		crop: function(event) {}
			});
			
			$('.camera-control-btn').addClass('d-none');
			$('#exit-app').removeClass('d-none');
			$('#rotate-left').removeClass('d-none');
			$('#rotate-right').removeClass('d-none');
			$('#finish-crop').removeClass('d-none');
		}
		
		function removeCapture(){
		    $('#webcam-canvas').addClass('d-none');
		    $('.camera-control-btn').addClass('d-none');
		    $('#webcam-control').removeClass('d-none');
		    $('#cameraControls').removeClass('d-none');
		    $('#take-photo').removeClass('d-none');
		    $('#exit-app').addClass('d-none');
		    $('#download-photo').addClass('d-none');
		    $('#resume-camera').addClass('d-none');
		    $('body').css('overflow-y', 'auto');
		    $('.form-item-image').addClass('hidden');
		}
		
		$("#resume-camera").click(function () {
		    webcam.stream()
		        .then(facingMode =>{
		            removeCapture();
		        });
		});
		
		$("#exit-app, .webcam-overlay-cancel a").click(function () {
		    removeCapture();
		    $("#webcam-switch").prop("checked", false).change();
		    that.reset();
		});	
	}

	function reset() {
		$(this.editSelector).off('click');
		$('#webcam-app').remove();
		this.start();
	}
	
	function create() {
		var webcamElems = jQuery(`
			<main id="webcam-app" class="hidden">
			<div id="webcam-app--outer-container">
			<div id="webcam-app--inner-container">
			<div class="title">${Drupal.t('Change profile picture')}</div>
			<div class="form-control webcam-start webcam-off" id="webcam-control">
					<label class="form-switch">
					<input type="checkbox" id="webcam-switch">
					<i></i> 
					<span id="webcam-caption">${Drupal.t('Click to start Camera')}</span>
					</label>      
					<button id="cameraFlip" class="btn d-none"></button>                  
			</div>
			<!--<span class="form-control-separator"> - OR - </span>-->
			<div class="form-control filepicker-start webcam-off" id="filepicker-control">
					<a id="filepicker-switch" onclick="jQuery('#input-image-filepicker').click()" class="btn btn-primary"> Choose a file </a>
					<input type="file" class="hidden" id="input-image-filepicker" name="image" accept="image/*">             
			</div>
			<div class="webcam-overlay-cancel">
				<a href="#">${Drupal.t('Cancel')}</a>
			</div>
			</div>
			</div>
			<div id="errorMsg" class="col-12 col-md-6 alert-danger d-none">
				Fail to start camera, please allow permision to access camera. <br>
				If you are browsing through social media built in browsers, you would need to open the page in Sarafi (iPhone)/ Chrome (Android)
				<button id="closeError" class="btn btn-primary ml-3">OK</button>
			</div>
			<div class="md-modal md-effect-12">
				<div class="title">${Drupal.t('Change profile picture')}</div>
				<div id="app-panel" class="app-panel md-content row p-0 m-0">     
					<div id="preCameraControls" class"cameraControls">
						<a href="#" id="rotate-left" title="Rotate left" class="d-none camera-control-btn" rel="noopener noreferrer"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/arrow-go-back-fill-gray.svg" />${Drupal.t('Turn left')}</a>
						<a href="#" id="rotate-right" title="Rotate right" class="d-none camera-control-btn" rel="noopener noreferrer"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/arrow-go-forward-fill-gray.svg" />${Drupal.t('Turn right')}</a>
					</div>
					<div id="webcam-container" class="webcam-container col-12 p-0 m-0 d-none">
						<video id="webcam" autoplay="" playsinline="" width="625" height="312" style="transform: scale(-1, 1);"></video>
						<canvas id="webcam-canvas" class="d-none"></canvas>
						<img id="webcam-final-image" class="d-none"/>
						<div class="flash" style="display: none;"></div>
						<audio id="snapSound" src="" preload="auto"></audio>
					</div>
					<div id="cameraControls" class="cameraControls">
						<a href="#" id="exit-app" title="Exit App" class="d-none camera-control-btn"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/delete-back-2-fill-white.svg" />${Drupal.t('Cancel')}</a>
						<a href="#" id="take-photo" title="Take Photo" class="camera-control-btn btn-highlight-gv"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/camera-fill-white.svg" />${Drupal.t('Take photo')}</a>
						<a href="#" id="download-photo" download="selfie.png" target="_blank" title="Save Photo" class="d-none camera-control-btn" rel="noopener noreferrer"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/arrow-down-line-white.svg" /></a> 
						<a href="#" id="validate-photo" title="Edit photo" class="d-none camera-control-btn btn-highlight-gv" rel="noopener noreferrer"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/check-line-white.svg" />${Drupal.t('Accept')}</a>
						<a href="#" id="finish-crop" title="Finish edit" class="d-none camera-control-btn btn-highlight-gv" rel="noopener noreferrer"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/check-double-line-white.svg" />${Drupal.t('Confirm photo')}</a>
					</div>
				</div>
				<div class="message-info">
					<div class="container-info">
						<div class="title-info">${Drupal.t('Tips for getting a perfect photo')}</div>
						<ol class="list">
							<li>${Drupal.t('<b>Recent photo</b>: do not use images from when you were younger, as current as possible.')}</li>
							<li>${Drupal.t('<b>Make your face 60% of the frame</b>: cut your image at the shoulders.')}</li>
							<li>${Drupal.t('<b>Look at the camera</b>: as if you were in front of another person.')}</li>
							<li>${Drupal.t('<b>Frame</b>: to crop the image, drag the area below and click "Choose as profile photo."')}</li>
						</ol>
						<div class="photo-example">
							<div class="img-container">
								<div class="img ok">
									<img alt="" src="/modules/custom/gv_fplus/img/perfect-photo.png"/>
									<div class="text">
										${Drupal.t('Perfect photo')}
									</div>
								</div>
								<div class="img ko">
									<img alt="" src="/modules/custom/gv_fplus/img/wrong-photo.png"/>
									<div class="text">
										${Drupal.t('Ups! too much noise')}
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="md-overlay"></div>
		</main>`);

		$(this.appendToSelector).append(webcamElems);
		return webcamElems; 
	}
	
	var fanaticsPlusWebcam = function(appendToSelector, imagePreviewSelector, editSelector, onFinishCallback) {
		this.appendToSelector = appendToSelector;
		this.imagePreviewSelector = imagePreviewSelector;
		this.editSelector = editSelector;
		this.onFinishCallback = onFinishCallback;		
	};
	
	fanaticsPlusWebcam.prototype.create = create;
	fanaticsPlusWebcam.prototype.start = start;
	fanaticsPlusWebcam.prototype.reset = reset;
	window.fanaticsPlusWebcam = fanaticsPlusWebcam;
	
})(jQuery);
