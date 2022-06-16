'use strict';

(function ($, Drupal, drupalSettings, drupalTranslations) {

	'use strict';
	
	//Drupal.t('OOPS, WE CAN’T SEE YOU! It looks like your camera isn’t working. Check that it is connected correctly, that another application isn’t using it and that you have permission to access it.');
	Drupal.t('WEBCAM.PERMISSION_ERROR')
	Drupal.t('WEBCAM.CHOOSE_FILE');
	Drupal.t('WEBCAM.PERFECT_PHOTO', {}, {context: drupalSettings.gv_fanatics_plus_translation_context});
	
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
			var done = function done(url) {
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
			if (this.checked) {
				$('.md-modal').addClass('md-show');
				webcam.start().then(function (result) {
					cameraStarted();
				}).catch(function (err) {
					displayError();
				});
			} else {
				cameraStopped();
				webcam.stop();
			}
		});

		$('#cameraFlip').click(function () {
			webcam.flip();
			webcam.start();
		});

		$('#closeError').click(function (e) {
			e.preventDefault();
			e.stopPropagation();
			$("#webcam-switch").prop('checked', false).change();
		});
		
		var editImageBtnSelector = that.editSelector || '.edit-image-btn';
		$(editImageBtnSelector).click(function (e) {
			e.preventDefault();
			$('#webcam-app').removeClass('hidden');
			$('.form-item-image').removeClass('hidden');
		});

		function displayError() {
			var err = arguments.length <= 0 || arguments[0] === undefined ? '' : arguments[0];

			if (err != '') {
				$("#errorMsg").html(err);
			}
			$("#errorMsg").removeClass("d-none");
			$('#webcam-app .md-modal').removeClass('md-show');
		}

		function cameraStarted(filePicker) {
			filePicker = filePicker || false;
			if (!filePicker) {
				$("#errorMsg").addClass("d-none");
				$('.flash').hide();
				$("#webcam-caption").html("on");
				if (webcam.webcamList.length > 1) {
					$("#cameraFlip").removeClass('d-none');
				}
				
				$('#webcam-app').attr('data-file-picker', false);
			} else {
				$('#webcam-app').attr('data-file-picker', true);
			}
			
			$('#webcam-app > .md-modal').addClass('anim-on');
			$("#webcam-control").removeClass("webcam-off");
			$("#webcam-control").addClass("webcam-on");
			$(".webcam-container").removeClass("d-none");
			$("#wpfront-scroll-top-container").addClass("d-none");
			window.scrollTo(0, 0);
			$('body').css('overflow-y', 'hidden');
		}

		function cameraStopped() {
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
			var picture = webcam.snap();
			document.querySelector('#download-photo').href = picture;
			_processPreview(picture);
			afterTakePhoto();
		});

		$('#validate-photo').click(function () {
			_activateCropper();
		});

		$('#rotate-left').click(function () {
			var cropper = jQuery('#webcam-final-image').data('cropper');
			cropper.rotate(-90);
			cropper.moveTo(0, 0);
		});

		$('#rotate-right').click(function () {
			var cropper = jQuery('#webcam-final-image').data('cropper');
			cropper.rotate(-90);
			cropper.moveTo(0, 0);
		});

		$('#finish-crop').click(function () {
			var cropper = jQuery('#webcam-final-image').data('cropper');
			var canvas = cropper.getCroppedCanvas({
				width: 134,
				height: 164,
				fillColor: '#fff',
				imageSmoothingEnabled: true,
				imageSmoothingQuality: 'high'
			});

			var base64Image = canvas.toDataURL("image/jpeg");
			$('#webcam-final-image').attr('src', base64Image);

			if (that.onFinishCallback) {
				that.onFinishCallback(base64Image);
			}
			
			if (that.inputValSelector) {
				$(that.inputValSelector).val(base64Image);
				$(that.inputValSelector).trigger('change');
				$(that.inputValSelector).attr('value', base64Image);
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

		function beforeTakePhoto() {
			$('.flash').show().animate({ opacity: 0.3 }, 500).fadeOut(500).css({ 'opacity': 0.7 });

			window.scrollTo(0, 0);
			$('#webcam-control').addClass('d-none');
			$('#cameraControls').addClass('d-none');
		}

		function afterTakePhoto() {
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
				crop: function crop(event) {}
			});

			$('.camera-control-btn').addClass('d-none');
			$('#exit-app').removeClass('d-none');
			$('#rotate-left').removeClass('d-none');
			$('#rotate-right').removeClass('d-none');
			$('#finish-crop').removeClass('d-none');
		}
		
		function _removeCropper() {
			$('#webcam-final-image').cropper('destroy');

			$('.camera-control-btn').removeClass('d-none');
			$('#exit-app').addClass('d-none');
			$('#rotate-left').addClass('d-none');
			$('#rotate-right').addClass('d-none');
			$('#finish-crop').addClass('d-none');
		}

		function removeCapture() {
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
			webcam.stream().then(function (facingMode) {
				removeCapture();
			});
		});

		$(".webcam-overlay-cancel a, #webcam-app .ui-dialog-titlebar-close").click(function () {
			removeCapture();
			$("#webcam-switch").prop("checked", false).change();
			that.reset();
		});
		
		$("#exit-app").click(function() {
			if ($('#webcam-app').attr('data-file-picker') == 'true') { // file picker selected, close the dialog
				removeCapture();
				$("#webcam-switch").prop("checked", false).change();
				that.reset();
				return;
			}
			
			_removeCropper();
			
			webcam.start();
			$('#webcam-canvas').addClass('d-none');
			$('#take-photo').removeClass('d-none');
			$('#exit-app').addClass('d-none');
			$('#download-photo').addClass('d-none');
			$('#resume-camera').addClass('d-none');
			//$('#cameraControls').addClass('d-none');
			$('#validate-photo').addClass('d-none');
			$('#webcam-final-image').addClass('d-none');
			
			cameraStarted();
		});
	}

	function reset() {
		$(this.editSelector).off('click');
		$('#webcam-app').remove();
		this.start();
	}

	function create() {
		var webcamElems = jQuery('\n\t\t\t<main id="webcam-app" class="hidden">\n\t\t\t<div id="webcam-app--outer-container">\n\t\t\t<div id="webcam-app--inner-container">\n\t\t\t<div class="title">' 
			+ Drupal.t('WEBCAM.MAIN_TITLE') 
			+ '</div>\n\t\t\t<div class="form-control webcam-start webcam-off" id="webcam-control">\n\t\t\t\t\t<label class="form-switch">\n\t\t\t\t\t<input type="checkbox" id="webcam-switch">\n\t\t\t\t\t<i></i> \n\t\t\t\t\t<span id="webcam-caption">' 
			+ Drupal.t('WEBCAM.OPEN_CAMERA') 
			+ '</span>\n\t\t\t\t\t</label>      \n\t\t\t\t\t<button id="cameraFlip" class="btn d-none"></button>                  \n\t\t\t</div>\n\t\t\t<!--<span class="form-control-separator"> - OR - </span>-->\n\t\t\t<div class="form-control filepicker-start webcam-off" id="filepicker-control">\n\t\t\t\t\t<a id="filepicker-switch" onclick="jQuery(\'#input-image-filepicker\').click()" class="btn btn-primary">' 
			+ Drupal.t('WEBCAM.CHOOSE_FILE') 
			+ '</a>\n\t\t\t\t\t<input type="file" class="hidden" id="input-image-filepicker" name="image" accept="image/*">             \n\t\t\t</div>\n\t\t\t<div class="webcam-overlay-cancel">\n\t\t\t\t<a href="#">' + Drupal.t('WEBCAM.CANCEL') 
			+ '</a>\n\t\t\t</div>\n\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div id="errorMsg" class="col-12 col-md-6 alert-danger d-none">\n\t\t\t\t'+ Drupal.t('WEBCAM.PERMISSION_ERROR') 
			+ '<br/>\n\t\t\t\t\n\t\t\t\t<button id="closeError" class="btn btn-primary ml-3">' 
			+ Drupal.t('OK') 
			+ '</button>\n\t\t\t</div>\n\t\t\t<div class="md-modal md-effect-12">\n\t\t\t\t<div class="title">' 
			+ Drupal.t('Change profile picture') 
			+ '</div>\n\t\t\t\t<button type="button" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close"><span class="ui-button-icon ui-icon ui-icon-closethick"></span><span class="ui-button-icon-space"> </span>Close</button><div id="app-panel" class="app-panel md-content row p-0 m-0">     \n\t\t\t\t\t<div id="preCameraControls" class"cameraControls">\n\t\t\t\t\t\t<a href="#" id="rotate-left" title="Rotate left" class="d-none camera-control-btn" rel="noopener noreferrer"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/arrow-go-back-fill-gray.svg" />' 
			+ Drupal.t('WEBCAM.ROTATE_LEFT') 
			+ '</a>\n\t\t\t\t\t\t<a href="#" id="rotate-right" title="Rotate right" class="d-none camera-control-btn" rel="noopener noreferrer"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/arrow-go-forward-fill-gray.svg" />' 
			+ Drupal.t('WEBCAM.ROTATE_RIGHT') 
			+ '</a>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div id="webcam-container" class="webcam-container col-12 p-0 m-0 d-none">\n\t\t\t\t\t\t<video id="webcam" autoplay="" playsinline="" width="625" height="312" style="transform: scale(-1, 1);"></video>\n\t\t\t\t\t\t<canvas id="webcam-canvas" class="d-none"></canvas>\n\t\t\t\t\t\t<img id="webcam-final-image" class="d-none"/>\n\t\t\t\t\t\t<div class="flash" style="display: none;"></div>\n\t\t\t\t\t\t<audio id="snapSound" src="" preload="auto"></audio>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div id="cameraControls" class="cameraControls">\n\t\t\t\t\t\t<a href="#" id="exit-app" title="Exit App" class="d-none camera-control-btn"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/delete-back-2-fill-white.svg" />' 
			+ Drupal.t('Cancel') + '</a>\n\t\t\t\t\t\t<a href="#" id="take-photo" title="Take Photo" class="camera-control-btn btn-highlight-gv"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/camera-fill-white.svg" />' 
			+ Drupal.t('WEBCAM.TAKE_PHOTO') 
			+ '</a>\n\t\t\t\t\t\t<a href="#" id="download-photo" download="selfie.png" target="_blank" title="Save Photo" class="d-none camera-control-btn" rel="noopener noreferrer"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/arrow-down-line-white.svg" /></a> \n\t\t\t\t\t\t<a href="#" id="validate-photo" title="Edit photo" class="d-none camera-control-btn btn-highlight-gv" rel="noopener noreferrer"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/check-line-white.svg" />' 
			+ Drupal.t('WEBCAM.ACCEPT') 
			+ '</a>\n\t\t\t\t\t\t<a href="#" id="finish-crop" title="Finish edit" class="d-none camera-control-btn btn-highlight-gv" rel="noopener noreferrer"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/check-double-line-white.svg" />' 
			+ Drupal.t('WEBCAM.CONFIRM_PHOTO') 
			+ '</a>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class="message-info">\n\t\t\t\t\t<div class="container-info">\n\t\t\t\t\t\t<div class="title-info">' 
			+ Drupal.t('WEBCAM.INSTRUCTIONS_TITLE') 
			+ '</div>\n\t\t\t\t\t\t<ol class="list">\n\t\t\t\t\t\t\t<li>' 
			+ Drupal.t('WEBCAM.INSTRUCTIONS_1') 
			+ '</li>\n\t\t\t\t\t\t\t<li>' 
			+ Drupal.t('WEBCAM.INSTRUCTIONS_2') 
			+ '</li>\n\t\t\t\t\t\t\t<li>' 
			+ Drupal.t('WEBCAM.INSTRUCTIONS_3') 
			+ '</li>\n\t\t\t\t\t\t\t<li>' 
			+ Drupal.t('WEBCAM.INSTRUCTIONS_4') 
			+ '</li>\n\t\t\t\t\t\t\t<li>' 
			+ Drupal.t('WEBCAM.INSTRUCTIONS_5') 
			+ '</li>\n\t\t\t\t\t\t</ol>\n\t\t\t\t\t\t<div class="photo-example">\n\t\t\t\t\t\t\t<div class="img-container">\n\t\t\t\t\t\t\t\t<div class="img ok">\n\t\t\t\t\t\t\t\t\t<img alt="" src="/modules/custom/gv_fplus/img/perfect-photo.png"/>\n\t\t\t\t\t\t\t\t\t<div class="text">\n\t\t\t\t\t\t\t\t\t\t' 
			+ Drupal.t('WEBCAM.PERFECT_PHOTO') + '\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class="img ko">\n\t\t\t\t\t\t\t\t\t<img alt="" src="/modules/custom/gv_fplus/img/wrong-photo.png"/>\n\t\t\t\t\t\t\t\t\t<div class="text">\n\t\t\t\t\t\t\t\t\t\t' + Drupal.t('WEBCAM.INVALID_PHOTO') 
			+ '\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class="md-overlay"></div>\n\t\t</main>');

		$(this.appendToSelector).append(webcamElems);
		return webcamElems;
	}

	function fanaticsPlusWebcam(appendToSelector, imagePreviewSelector, editSelector, onFinishCallback, inputValSelector) {
		this.appendToSelector = appendToSelector;
		this.imagePreviewSelector = imagePreviewSelector;
		this.editSelector = editSelector;
		this.onFinishCallback = onFinishCallback;
		this.inputValSelector = inputValSelector;
	};

	fanaticsPlusWebcam.prototype.create = create;
	fanaticsPlusWebcam.prototype.start = start;
	fanaticsPlusWebcam.prototype.reset = reset;
	window.fanaticsPlusWebcam = fanaticsPlusWebcam;
})(jQuery, Drupal, drupalSettings, drupalTranslations);
