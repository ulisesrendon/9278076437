<?php

namespace Drupal\gv_fanatics_plus_checkout\Form\PostPayment;

use Drupal\gv_fanatics_plus_checkout\Form\PostPayment\MultistepFormBase;
use Drupal\gv_fanatics_plus_checkout\BookingOfficeOptions;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario del paso de gestión de firma electrónica de logalty del proceso de Post-pago.
 */
class LogaltyForm extends MultistepFormBase {

  	/**
   	* {@inheritdoc}.
   	*/
  	public function getFormId() {
    	return 'gv_fplus_checkout_logalty_form';
  	}

	protected function _buildTitleMarkup() {
		$markup = '<div class="top-description-container">';
		$markup .= '</div>';
		
		return $markup;
	}

	private function _getDefaultImageAvatar() {
		return '<svg width="88" height="88" viewBox="0 0 88 88" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0.5" y="0.5" width="39" height="39" rx="19.5" fill="#C3C9D6"/><g filter="url(#filter0_d)"><circle cx="36" cy="31" r="11.5" fill="white" stroke="#F0F2F5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M35.2857 30.2857V26H36.7143V30.2857H41V31.7143H36.7143V36H35.2857V31.7143H31V30.2857H35.2857Z" fill="#99A3B1"/></g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.3809 30C12.3809 25.7921 15.792 22.381 19.9999 22.381C24.2078 22.381 27.619 25.7921 27.619 30H25.7142C25.7142 26.8441 23.1558 24.2857 19.9999 24.2857C16.844 24.2857 14.2856 26.8441 14.2856 30H12.3809ZM19.9999 21.4286C16.8428 21.4286 14.2856 18.8714 14.2856 15.7143C14.2856 12.5571 16.8428 10 19.9999 10C23.1571 10 25.7142 12.5571 25.7142 15.7143C25.7142 18.8714 23.1571 21.4286 19.9999 21.4286ZM19.9999 19.5238C22.1047 19.5238 23.8094 17.819 23.8094 15.7143C23.8094 13.6095 22.1047 11.9048 19.9999 11.9048C17.8951 11.9048 16.1904 13.6095 16.1904 15.7143C16.1904 17.819 17.8951 19.5238 19.9999 19.5238Z" fill="white"/><rect x="0.5" y="0.5" width="39" height="39" rx="19.5" stroke="#F0F2F5"/><defs><filter id="filter0_d" x="16" y="15" width="40" height="40" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feOffset dy="4"/><feGaussianBlur stdDeviation="4"/><feColorMatrix type="matrix" values="0 0 0 0 0.0541176 0 0 0 0 0.145098 0 0 0 0 0.173333 0 0 0 0.12 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow" result="shape"/></filter></defs></svg>';
	}

	private function _build_error_markup($hidden = TRUE, $maximumRetries = FALSE, $incidenceCode = NULL, $isLogaltyCaseClosed = FALSE, $internalError = FALSE) {
		$contactLinkBuilder = \Drupal::service('gv_fanatics_plus_contact.contact_page_link_builder');
		$orderID = \Drupal::routeMatch()->getParameter('orderID');
		$orderInfo = \Drupal::service('gv_fanatics_plus_order.order')::getFromID($orderID, FALSE, TRUE);
		
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		if ($hidden) {
			$markup = '<div class="error-container hidden">';
		} else {
			$markup = '<div class="error-container">';
		}
			
		if ($maximumRetries == TRUE) {
			$markup .= '<div class="text"><p>' 
				. $translationService->translate('POST_PAYMENT.LOGALTY.SIGNATURE_ERROR_MAX_RETRIES_BODY') 
				. '</p></div>';
		} else if ($incidenceCode == 'INC_302010') {
			$markup .= '<div class="text"><p>' 
				. $translationService->translate('POST_PAYMENT.LOGALTY.SIGNATURE_ERROR_INC_302010_BODY') 
				. '</p></div>';
		} else {
			$markup .= '<div class="text"><p>' 
				. $translationService->translate('POST_PAYMENT.LOGALTY.SIGNATURE_ERROR_BODY') 
				. '</p></div>';
		}
		
		if ($internalError && !$maximumRetries && !$isLogaltyCaseClosed) { // an error ocurred, try again or contact support
			
			$currentRequest = \Drupal::request();
			$bypassPhoneNumberCheck = $currentRequest->query->get('bypass-phone-number-check');
			if ($bypassPhoneNumberCheck != NULL && $bypassPhoneNumberCheck == 1) {
				$bypassPhoneNumberCheck = 1;
			} else {
				$bypassPhoneNumberCheck = 0;
			}
			
			$markup .= '<div class="actions"><a class="btn btn-highlighted" href="' 
				. Url::fromRoute('gv_fanatics_plus_checkout.post_payment_logalty', ['orderID' => $orderID], ['query' => ['bypass-phone-number-check' => $bypassPhoneNumberCheck]])->toString() . '">' 
				. $translationService->translate('POST_PAYMENT.LOGALTY.SIGNATURE_ERROR_RETRY_BTN_LABEL')
				. '</a>';
			
			$markup .= '<span class="or-separator">' . '- ' . t('or') . ' -' . '</span><a class="btn btn-highlighted" href="' 
				. $contactLinkBuilder->buildURL($orderInfo->BookingLocator, TRUE) . '">' 
				. $translationService->translate('POST_PAYMENT.LOGALTY.SIGNATURE_ERROR_CONTACT_BTN_LABEL')
				. '</a>';
			
			$markup .= '</div>';
		} else {
			$markup .= '<div class="actions"><a class="btn btn-highlighted" href="' 
				. $contactLinkBuilder->buildURL($orderInfo->BookingLocator, TRUE) . '">' 
				. $translationService->translate('POST_PAYMENT.LOGALTY.SIGNATURE_ERROR_CONTACT_BTN_LABEL')
				. '</a></div>';
		}

		$markup .= '</div>';
		
		return $markup;
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state, $currentOrderID = NULL, $currentStepNumber = 1, $totalSteps = 1, $destinationUrl = NULL) {
		$this -> formTitle = 'POST_PAYMENT.LOGALTY.MAIN_TITLE';
		
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$currentStepNumber = $this->postPaymentOrderManager->getCurrentStepNumber();
		$totalSteps = $this->postPaymentOrderManager->getTotalStepsNumber();
		
		$form = parent::buildForm($form, $form_state, $currentOrderID, $currentStepNumber, $totalSteps, $destinationUrl);
		
		$logalty = \Drupal::service('gv_fanatics_plus_checkout.logalty');
		$session = \Drupal::service('gv_fplus.session');
		$user = \Drupal::service('gv_fplus_auth.user');
		$orderID = \Drupal::routeMatch()->getParameter('orderID');
		
		$order = $this->order->getFromID($orderID, TRUE, TRUE);
		$pendingData = $order->getPendingData();
		if (!$pendingData->PendingSignature) {
			return new RedirectResponse(Url::fromRoute('gv_fanatics_plus_checkout.post_payment', ['orderID' => $orderID])->toString());
		}
		
		$profile = $user->getProfile($session->getEmail());
		$isLogaltyCaseClosed = FALSE;
		$internalError = FALSE;
		
		$currentRequest = \Drupal::request();
		$bypassPhoneNumberCheck = $currentRequest->query->get('bypass-phone-number-check');
		if ($bypassPhoneNumberCheck != NULL && $bypassPhoneNumberCheck == 1) {
			$bypassPhoneNumberCheck = TRUE;
		} else {
			$bypassPhoneNumberCheck = FALSE;
		}
		
		$maxRetriesExceeded = FALSE;
		$incidenceCode = NULL;
		if ($bypassPhoneNumberCheck) {
			try {
				$signAndGetURL = $logalty->signAndGetURL($orderID, $session->getIdentifier());
			} catch(\Exception $e) {
				if ($e->getResponse()->getStatusCode() == 409) {
					$isLogaltyCaseClosed = TRUE;
				} else {
					$internalError = TRUE;
					$errorBody = $e->getResponse()->getBody()->getContents();
					$errorBodyDecoded = json_decode($errorBody);
					
					if (isset($errorBodyDecoded->ErrorCodes) && count($errorBodyDecoded->ErrorCodes) > 0) {
						foreach ($errorBodyDecoded->ErrorCodes as $errorCode) {
							if ($errorCode->ErrorCode == 'INC_303013') {
								$maxRetriesExceeded = TRUE;
							} else if ($errorCode->ErrorCode == 'INC_302010') {
								$incidenceCode = $errorCode->ErrorCode;
							}
						}
					}
					
					\Drupal::logger('php')->error($errorBody);
				}
			}
		}
		
		$showPaymentSuccessMessage = $currentRequest->query->get('show_payment_success');
		if ($showPaymentSuccessMessage != NULL && $showPaymentSuccessMessage == 1) {
			$orderInfo = $this->order->getFromID($orderID, TRUE)->Booking;
			if (!$orderInfo->SynchronizedConversionScript) {
				$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/checkout_complete'; 
      			$form['#attached']['drupalSettings']['checkout_complete']['order'] = $orderInfo;
				$this->order->editBookingSynchronizedConversionScript($orderID, TRUE);
			}
		}
		
		//\Drupal::logger('php')->error('TEST SESSION: ' . $session->getIdentifier());
				
		$this->storeSet('logalty_order_id', $orderID);
		
		$form['top_description_container'] = [
			'#markup' => $this->_buildTitleMarkup(),
			'#weight' => -1
		];
		
		//$bookingStatuses = $this->apiClient->core()->getBookingStatuses();
		$form['logalty'] = [
			'#type' => 'fieldset',
			'#tree' => TRUE
		];
		
		$form['logalty']['bypass_phone_number_check'] = [
			'#type' => 'checkbox',
			'#default_value' => $bypassPhoneNumberCheck,
			'#title' => 'Bypass phone number check',
			'#attributes' => [
				'readonly' => 'readonly',
				'class' => ['hidden']
			],
			'#wrapper_attributes' => [
				'class' => ['hidden']
			]
		];

		if (!$bypassPhoneNumberCheck && !$internalError) {
			$form['#attributes']['class'][] = 'gv-fanatics-plus-no-fixed-buttons';
			
			$form['logalty']['sms'] = [
				'#type' => 'tel',
				'#default_value' => $profile->Phone,
				'#title' => $translationService->translate('POST_PAYMENT.LOGALTY.PHONE_NUMBER_FORM_TITLE'),
				'#suffix' => '<div class"sms-description"><span>' 
					. $translationService->translate('POST_PAYMENT.LOGALTY.PHONE_NUMBER_FORM_DESCRIPTION')
					. '</span>' . '</div>',
				'#required' => TRUE
			];
		
			$form['logalty']['full_phone_number'] = array(
		 		'#type' => 'tel',
		 		'#default_value' => $profile->Phone,
		 		'#required' => TRUE,
		 		'#attributes' => [
		 			'class' => ['hidden']
		 		]
			);
		}
		
		if (!$internalError && !$isLogaltyCaseClosed && $bypassPhoneNumberCheck) {
			$form['logalty']['iframe'] = [
				'#type' => 'inline_template',
				'#template' => '<iframe id="logalty-sign-get-url" src="{{ url }}"></iframe>',
				'#context' => [
      				'url' => $signAndGetURL,
    			],
			];
		}
		
		$form['logalty']['error'] = [
			'#markup' => $this->_build_error_markup(!($internalError || $isLogaltyCaseClosed), $maxRetriesExceeded, $incidenceCode, $isLogaltyCaseClosed, $internalError)
		];
		
		//shipping_method_select_form
		$form['#attached']['library'][] = 'core/drupal.dialog.ajax';
		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/logalty_form';
		
		$form['actions']['#type'] = 'actions';
		$form['actions']['submit'] = [
			'#type' => 'submit', 
			'#value' => $translationService->translate('POST_PAYMENT.NEXT_BTN_LABEL'), 
			'#button_type' => 'primary', 
			'#weight' => 10,
			'#attributes' => ['disabled' => TRUE]
		];
		
		if (!$bypassPhoneNumberCheck) {
			$form['actions']['submit']['#value'] = $translationService->translate('POST_PAYMENT.SUBMIT_BTN_LABEL');
			unset($form['actions']['submit']['#attributes']['disabled']);
		}
		
		if ($isLogaltyCaseClosed || $internalError) {
			$form['actions']['submit']['#attributes']['disabled'] = TRUE;
			$form['actions']['submit']['#attributes']['class'][] = 'hidden';
		}
		
		$form['#cache']['contexts'][] = 'session';
		
		return $form;
	}
	
	/**
	 * {@inheritdoc}
	 */
	 public function validateForm(array &$form, FormStateInterface $form_state) {
	 	$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
	 	$formValues = $form_state->getValues();
		$bypassPhoneNumberCheck = $formValues['logalty']['bypass_phone_number_check'];
		if ($bypassPhoneNumberCheck > 0) {
			return;
		}
		
		$formBasicValidations = \Drupal::service('gv_fplus_auth.form_basic_validations');
		$locationService = \Drupal::service('gv_fplus_auth.location');
		
		$session = \Drupal::service('gv_fplus.session');
		$user = \Drupal::service('gv_fplus_auth.user');
		
		$profile = $user->getProfile($session->getEmail());
		
		$currentCountry = $locationService->getCountryByID($profile->IDCountry);
		$currentCountryCode = $currentCountry->Code;
		
	 	$phoneNumber = $formValues['logalty']['full_phone_number'];
		$validPhoneNumber = $formBasicValidations->isValidPhoneNumber($phoneNumber, NULL);
		if (!$validPhoneNumber) {
			$form_state->setErrorByName('logalty[sms]', $translationService->translate('POST_PAYMENT.LOGALTY.INVALID_PHONE_NUMBER'));
		}
	 }
	
  	/**
   	* {@inheritdoc}
   	*/
  	public function submitForm(array &$form, FormStateInterface $form_state) {
  		$orderID = $this->storeGet('logalty_order_id');
		$this->deleteStoreKeys(['logalty_order_id']);
		
		$formValues = $form_state->getValues();
		$bypassPhoneNumberCheck = $formValues['logalty']['bypass_phone_number_check'];
		
		if ($bypassPhoneNumberCheck > 0) {
			$this->postPaymentOrderManager->increaseStepNumber();
  			$form_state->setRedirect('gv_fanatics_plus_checkout.post_payment_shipping_method', ['orderID' => $orderID]);
		} else {
			$phoneNumber = $formValues['logalty']['full_phone_number'];
			$user = \Drupal::service('gv_fplus_auth.user');
			$session = \Drupal::service('gv_fplus.session');
			
			$user->fanatics()->update(
				$session->getIdentifier(), 
				$session->getEmail(), 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				$phoneNumber,
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL, 
				NULL
			);
			
			// refresh form with bypass query parameter
			$form_state->setRedirect('gv_fanatics_plus_checkout.post_payment_logalty', ['orderID' => $orderID], ['query' => ['bypass-phone-number-check' => '1']]);
		}

  	}
}

?>
