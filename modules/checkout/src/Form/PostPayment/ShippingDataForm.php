<?php

namespace Drupal\gv_fanatics_plus_checkout\Form\PostPayment;

use Drupal\gv_fanatics_plus_checkout\Form\PostPayment\MultistepFormBase;
use Drupal\gv_fanatics_plus_checkout\BookingOfficeOptions;
use Drupal\gv_fanatics_plus_checkout\Ajax\DisableFullscreenLoader;

use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;
use Drupal\Core\Routing\TrustedRedirectResponse;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\HtmlCommand;

use Drupal\gv_fanatics_plus_order\Order;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario correspondiente al paso de edición de información de envío / recarga del proceso de Post-pago.
 */
class ShippingDataForm extends MultistepFormBase {

  	/**
   	* {@inheritdoc}.
   	*/

	  protected $orderID;


  	public function getFormId() {
    	return 'gv_fplus_checkout_shipping_data_form';
  	}

	protected function _buildTitleMarkup() {
		$markup = '<div class="top-description-container">';
		$markup .= '</div>';
		
		return $markup;
	}

	private function _getDefaultImageAvatar() {
		return '<svg width="88" height="88" viewBox="0 0 88 88" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0.5" y="0.5" width="39" height="39" rx="19.5" fill="#C3C9D6"/><g filter="url(#filter0_d)"><circle cx="36" cy="31" r="11.5" fill="white" stroke="#F0F2F5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M35.2857 30.2857V26H36.7143V30.2857H41V31.7143H36.7143V36H35.2857V31.7143H31V30.2857H35.2857Z" fill="#99A3B1"/></g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.3809 30C12.3809 25.7921 15.792 22.381 19.9999 22.381C24.2078 22.381 27.619 25.7921 27.619 30H25.7142C25.7142 26.8441 23.1558 24.2857 19.9999 24.2857C16.844 24.2857 14.2856 26.8441 14.2856 30H12.3809ZM19.9999 21.4286C16.8428 21.4286 14.2856 18.8714 14.2856 15.7143C14.2856 12.5571 16.8428 10 19.9999 10C23.1571 10 25.7142 12.5571 25.7142 15.7143C25.7142 18.8714 23.1571 21.4286 19.9999 21.4286ZM19.9999 19.5238C22.1047 19.5238 23.8094 17.819 23.8094 15.7143C23.8094 13.6095 22.1047 11.9048 19.9999 11.9048C17.8951 11.9048 16.1904 13.6095 16.1904 15.7143C16.1904 17.819 17.8951 19.5238 19.9999 19.5238Z" fill="white"/><rect x="0.5" y="0.5" width="39" height="39" rx="19.5" stroke="#F0F2F5"/><defs><filter id="filter0_d" x="16" y="15" width="40" height="40" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feOffset dy="4"/><feGaussianBlur stdDeviation="4"/><feColorMatrix type="matrix" values="0 0 0 0 0.0541176 0 0 0 0 0.145098 0 0 0 0 0.173333 0 0 0 0.12 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow" result="shape"/></filter></defs></svg>';
	}

	private function _groupServicesByShippingMethod($order, $orderRechargeInfo) {
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$bookingOffice = \Drupal::service('gv_fanatics_plus_checkout.booking_office');
		
		$group = [
			BookingOfficeOptions::RECHARGE_FORFAIT => ['label' => $translationService->translate('POST_PAYMENT.SHIPPING_DATA.RECHARGE_OPTIONS'), 'services' => []],
			BookingOfficeOptions::HOME_DELIVERY => ['label' => $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.HOME_DELIVERY_LABEL'), 'services' => []],
			BookingOfficeOptions::BOX_OFFICE_PICKUP => ['label' => $translationService->translate('POST_PAYMENT.SHIPPING_DATA.BOX_OFFICE_PICKUP_OPTIONS'), 'services' => []]
		];
		
		foreach ($order->Services as $index => $service) {
			$serviceRechargeInfo =  $orderRechargeInfo->Services[$index];
			$bookingOfficeOption = $bookingOffice->getOptionFromID($order->IDBookingOffice);
			$service->rechargeInfo = $serviceRechargeInfo;
			//--ksm($serviceRechargeInfo, $bookingOfficeOption, rand(0,9999));

			if ((isset($serviceRechargeInfo->RechargeRequest) && $serviceRechargeInfo->RechargeRequest == TRUE) || $serviceRechargeInfo->Recharged == TRUE || $serviceRechargeInfo->Rechargeable == TRUE) {
				// group by recharge
				$group[BookingOfficeOptions::RECHARGE_FORFAIT]['services'][] = $service;
			} else if ($bookingOfficeOption == BookingOfficeOptions::HOME_DELIVERY) {
				$group[BookingOfficeOptions::HOME_DELIVERY]['services'][] = $service;
			} else if ($bookingOfficeOption == BookingOfficeOptions::BOX_OFFICE_PICKUP) {
				$group[BookingOfficeOptions::BOX_OFFICE_PICKUP]['services'][] = $service;
			}
		}
		
		$group = array_filter($group, function($bookingOfficeOption) {
			return (count($bookingOfficeOption['services']) > 0);
		});
		
		return $group;
	}

	private function _buildShippingIntegrantsMarkup($boxOfficeOption, $orderInfo) {
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		$markup = '<div class="shipping-option-data-integrants">';
		//<div class="integrant"><div class="img"><img src="' . $defaultImgURL . '" /></div><span class="name">Josep Vera</span></div><div class="integrant"><div class="img"><img src="' . $defaultImgURL .'" /></div><span class="name">Carla Vera</span></div></div>';

		foreach ($boxOfficeOption['services'] as $service) {
			$IDClient = $service->SeasonPassData->IDClient;
			$userProfile = $this->apiClient->users()->getUserProfile(NULL, TRUE, NULL, NULL, $IDClient);
			if (isset($service->IntegrantData)) {
				$imageBase64 = $service->IntegrantData->ImageBase64;
				$name = $service->IntegrantData->Name;
				$surname = $service->IntegrantData->Surname;
			} else {
				$imageBase64 = $userProfile->Image;
				$name = $userProfile->Name;
				$surname = $userProfile->Surname;
			}

			$image = $imageBase64 ?? Order::getDefaultUserAvatar();
			
			$markup .= '
				<div class="integrant integrant_container">
					<div class="integrant_card">
						<div class="img">
							<img data-src="' . $image . '" src=""/>
						</div>
						<span class="name">' . $name . ' ' . $surname .'</span>
					</div>
					<div class="norecharge_cont">
						<div>'.$translationService->translate('POST_PAYMENT.SHIPPING_DATA.NORECHARGE_CONTENT').'</div>
					</div>
				</div>
			';
		}
		
		$markup .= '</div>';
		return $markup;
	}
	
	private function _buildShippingIntegrantMarkup($service, $orderInfo, $showSuccessClass = FALSE) {
		$markup = '<div class="shipping-option-data-integrants">';
		if ($showSuccessClass) {
			$markup = '<div class="shipping-option-data-integrants success">';
		}
		//<div class="integrant"><div class="img"><img src="' . $defaultImgURL . '" /></div><span class="name">Josep Vera</span></div><div class="integrant"><div class="img"><img src="' . $defaultImgURL .'" /></div><span class="name">Carla Vera</span></div></div>';
		$IDClient = $service->SeasonPassData->IDClient;
		$userProfile = $this->apiClient->users()->getUserProfile(NULL, TRUE, NULL, NULL, $IDClient);
		
		if (isset($service->IntegrantData)) {
			$imageBase64 = $service->IntegrantData->ImageBase64;
			$name = $service->IntegrantData->Name;
			$surname = $service->IntegrantData->Surname;
		} else {
			$imageBase64 = $userProfile->Image;
			$name = $userProfile->Name;
			$surname = $userProfile->Surname;
		}
		
		if (isset($imageBase64)) {
			$markup .= '<div class="integrant"><div class="img"><img data-src="' . $imageBase64 . '" src=""/></div><span class="name">' . $name . ' ' . $surname .'</span></div>';
		} else {
			$markup .= '<div class="integrant"><div class="img"><img src="' . Order::getDefaultUserAvatar() . '" src=""/></div><span class="name">' . $name . ' ' . $surname .'</span></div>';
		}
		
		$markup .= '</div>';
		return $markup;
	}
	
	private function _buildShippingMethodSelectorMarkup($boxOfficeOption, $boxOfficeOptionIndex, $orderInfo) {
		$markup = '<div data-target-id="shipping-option-data-item-' . $boxOfficeOptionIndex . '" class="shipping-method-option option-id-' . $boxOfficeOptionIndex . '"><div class="shipping-method-option--inner"><div class="images">';
		//<div class="integrant"><div class="img"><img src="' . $defaultImgURL . '" /></div><span class="name">Josep Vera</span></div><div class="integrant"><div class="img"><img src="' . $defaultImgURL .'" /></div><span class="name">Carla Vera</span></div></div>';
		$integrantIndex = 0;
		foreach ($boxOfficeOption['services'] as $service) {
			$IDClient = $service->SeasonPassData->IDClient;
			$userProfile = $this->apiClient->users()->getUserProfile(NULL, TRUE, NULL, NULL, $IDClient);
			if (isset($service->IntegrantData)) {
				$imageBase64 = $service->IntegrantData->ImageBase64;
				$name = $service->IntegrantData->Name;
				$surname = $service->IntegrantData->Surname;
			} else {
				$imageBase64 = $userProfile->Image;
				$name = $userProfile->Name;
				$surname = $userProfile->Surname;
			}

			$activeClass = "active";
			if ($integrantIndex != 0) {
				$activeClass = "";
			}
			$markup .= '<div class="sidebar-integrant '.$activeClass.'"><div class="sidebar-integrant-image-wrapper">';
			$markup .= isset($imageBase64) ? '<img class="sidebar-integrant-image" src="" data-src="' . $imageBase64 . '" />' : '<img class="sidebar-integrant-image" src="' . Order::getDefaultUserAvatar() . '" />';
			$markup .= '</div><div class="sidebar-integrant-name">'.$name.' '.$surname.'</div></div>';
			$integrantIndex++;
		}
		
		$markup .= '</div></div></div>';
		return $markup;
	}

	public function rechargeForfaitAjaxSubmit(array $form, FormStateInterface $form_state) {
		$response = new AjaxResponse();

		$triggeringElement = $form_state->getTriggeringElement();
		if (!isset($triggeringElement)) {
			return $response;
		}

		
		$formValues = $form_state->getValues();
		$parents = $triggeringElement['#parents'];
		
		// if (count($parents) < 4) {
		// 	return $response;
		// }

		$shippingMethod = $parents[0];
		$serviceID = $parents[1];
		
		$forfaitCodePartial = $formValues[$shippingMethod][$serviceID]['recharge_data']['forfait_code'];
		if (!isset($forfaitCodePartial)) {
			return $response;
		}

		$complete_code = str_replace('___', $forfaitCodePartial, $formValues[$shippingMethod][$serviceID]['recharge_data']['select_wtp']);
		if( !isset( $_SESSION['wtp_codes_list'][$serviceID][$complete_code] ) ){

			\Drupal::messenger()->addMessage($forfaitCodePartial." ". $this->t('Invalid forfait code', [], ['context' => TranslationContext::POST_PAYMENT]), 'error');

			$message = [
      			'#theme' => 'status_messages',
      			'#message_list' => drupal_get_messages(),
    		];

    		$messages = \Drupal::service('renderer')->render($message);
   			$response->addCommand(new HtmlCommand('.alert-wrapper', $messages));
			
			$form['shipping_data'][$shippingMethod][$serviceID]['recharge_data']['forfait_code']['#attributes']['class'][] = 'error';
			ksm("forfait invalido");
			return $response;
		}
		

		$orderID = $this->order->getOrder()->Booking->Identifier;
		
		$session = \Drupal::service('gv_fplus.session');
		$recharge = \Drupal::service('gv_fanatics_plus_checkout.recharge');
		try {
			$result = $recharge->bookingRecharge($session->getIdentifier(), $orderID, $serviceID, $forfaitCodePartial);
			//$rechargeResult = $recharge->bookingSetRechargeRequest($session->getIdentifier(), $orderID, $serviceID, FALSE);

			if( is_null($result->ErrorCodes) ) $_SESSION['shipping_data_codes'][$orderID][$serviceID] = $forfaitCodePartial;
			else $_SESSION['shipping_data_codes'][$orderID][$serviceID] = '';

			
			$form['shipping_data'][$shippingMethod][$serviceID]['recharged'] = [
				'#markup' => '<div class="recharge-active badge"><div class="badge--inner">' . $this->t('Topped up', [],['context' => TranslationContext::POST_PAYMENT]) . '</div></div>'
			];
		
			unset($form['shipping_data'][$shippingMethod][$serviceID]['recharge_options']);
			unset($form['shipping_data'][$shippingMethod][$serviceID]['recharge_data']);

		} catch(\Exception $e) {
		    \Drupal::messenger()->addMessage($forfaitCodePartial." ". $this->t('Invalid forfait code', [], ['context' => TranslationContext::POST_PAYMENT]), 'error');

			$message = [
      			'#theme' => 'status_messages',
      			'#message_list' => drupal_get_messages(),
    		];

    		$messages = \Drupal::service('renderer')->render($message);
   			$response->addCommand(new HtmlCommand('.alert-wrapper', $messages));
			
			$form['shipping_data'][$shippingMethod][$serviceID]['recharge_data']['forfait_code']['#attributes']['class'][] = 'error';
		}
		
		$response->addCommand(new DisableFullscreenLoader(NULL));
		$response->addCommand(new HtmlCommand( '#shipping-option-data-item-' . $shippingMethod . ' #edit-' . $shippingMethod . '-' . $serviceID, $form['shipping_data'][$shippingMethod][$serviceID]));
		$response->addCommand(new InvokeCommand('#shipping-option-data-item-' . $shippingMethod . ' #edit-' . $shippingMethod . '-' . $serviceID . ' .shipping-option-data-integrants', 'addClass', ['success']));
		$response->addCommand(new InvokeCommand('#shipping-option-data-item-' . $shippingMethod . ' #edit-' . $shippingMethod . '-' . $serviceID .' .recharge-options-data', 'addClass', ['hidden']));
		
		return $response;
	}

	private function _getLocations($postalCode, $countryID) {
		if ($postalCode == NULL || $countryID == NULL) {
			return NULL;
		}

		$locationService = \Drupal::service('gv_fplus_auth.location');
		$locations = $locationService->getAll($postalCode, $countryID)->List;

		if (count($locations) <= 0) {
			return NULL;
		}
			
		$provinces = [];
		$cities = [];
		foreach ($locations as $location) {
			//if ($location->ProvinceID != NULL) {
				$provinces[$location->Province] = $location->Province;
			//}
			
			$cities[$location->City] = $location->City;
		}
				
		return ['cities' => $cities, 'provinces' => $provinces];
	}

	public function countryChangeAjaxCallback(array &$form, FormStateInterface $form_state) {
		$formValues = $form_state->getValues();
		$country = $formValues[BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['country'];
		//$form_state->setValue('postal_code', '');
		//$form_state->setRebuild();
		
		$renderer = \Drupal::service('renderer');
		//$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['postal_code']['#value'] = '';
  		$renderedField = $renderer->render($form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['postal_code']);
		
		// If we want to execute AJAX commands our callback needs to return
  		// an AjaxResponse object. let's create it and add our commands.
  		$response = new AjaxResponse();
  		// Issue a command that replaces the element #edit-output
  		// with the rendered markup of the field created above.
  		//$response->addCommand(new ReplaceCommand('.form-item-_-integrant-item-home-delivery-data-postal-code', $renderedField));
		$response->addCommand(new InvokeCommand('#edit-7-integrant-item-home-delivery-data-postal-code','val',['']));
		//$form_state->setValue('[' . BookingOfficeOptions::HOME_DELIVERY . '][integrant-item][home_delivery_data][postal_code]', '');
  		// Show the dialog box.
  		//$response->addCommand(new OpenModalDialogCommand('My title', $dialogText, ['width' => '300']));
  		
  		//$ajax_response->addCommand(new HtmlCommand('#edit-mail-visitor--description', $text));
		//$ajax_response->addCommand(new InvokeCommand('#edit-mail-visitor--description', 'css', ['color', $color]));

  		// Finally return the AjaxResponse object.
  		return $response;
	}

	public function postalCodeAjaxCallback(array &$form, FormStateInterface &$form_state) {
		$formValues = $form_state->getValues();
		$postalCode = $formValues[BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['postal_code'];

		if ($postalCode && strlen($postalCode) > 0) {
			$country = $formValues[BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['country'];
			//ksm($country);
			
			$provinces = [];
			$cities = [];
			$locationData = $this->_getLocations($postalCode, $country);
			if ($locationData != NULL) {
				$provinces = $locationData['provinces'];
				$cities = $locationData['cities'];
			}
			
			//ksm($locationData);
			
			$validPostalCode = TRUE;
			if (count($provinces) > 0 && count($cities) > 0) {
				$postalCodeMessageClass = 'success';
				$postalCodeMessage = $this->t('Valid postal code');
				
				
				$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province']['#options'] = $provinces;
				//$form['base_address_container']['province_town_container']['city']['#options'] = $cities;
				//$form['base_address_container']['province_town_container']['#prefix'] = '<div id="edit-province-town" class="">';
				
			} else {
				$postalCodeMessageClass = 'error';
				$postalCodeMessage = $this->t('Invalid postal code');
				$validPostalCode = FALSE;
				
				//$form['base_address_container']['province_town_container']['#prefix'] = '<div id="edit-province-town" class="hidden">';
			}

			$form_state->setValue('[shipping_data][' . BookingOfficeOptions::HOME_DELIVERY . '][integrant-item][home_delivery_data][province]', NULL);
			//$form_state->setValue('city', NULL);
		}

  		$response = new AjaxResponse();
		
		if (count($provinces) > 0) {
			$renderer = \Drupal::service('renderer');
			/*$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province']['#required'] = TRUE;
			$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province']['#attributes']['class'] = [];
			$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province']['#wrapper_attributes']['class'] = [];
			
			$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province_text']['#required'] = FALSE;
			$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province_text']['#attributes']['class'][] = 'hidden';
			$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province_text']['#wrapper_attributes']['class'][] = 'hidden';
			
  			*/
  			$renderedIdProvince = $renderer->render($form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province']);
			$renderedTextProvince= $renderer->render($form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province_text']);
			
  			$response->addCommand(new ReplaceCommand('.form-item-_-integrant-item-home-delivery-data-province', $renderedIdProvince));
			$response->addCommand(new ReplaceCommand('.form-item-_-integrant-item-home-delivery-data-province-text', $renderedTextProvince));
			//$response->addCommand(new InvokeCommand('.form-item-_-integrant-item-home-delivery-data-province-text input','val',['']));
			$response->addCommand(new InvokeCommand('.form-item-_-integrant-item-home-delivery-data-province-text input','val',[array_pop(array_reverse($provinces))]));
			//$response->addCommand(new InvokeCommand('.form-item-_-integrant-item-home-delivery-data-province', 'val', $renderedIdProvince));
		} else {
			$renderer = \Drupal::service('renderer');
			/*$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province']['#required'] = FALSE;
			$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province']['#attributes']['class'][] = 'hidden';
			$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province']['#wrapper_attributes']['class'][] = 'hidden';
			
			$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province_text']['#required'] = TRUE;
			$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province_text']['#attributes']['class'] = [];
			$form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province_text']['#wrapper_attributes']['class'] = [];
			
  			
  			$renderedIdProvince = $renderer->render($form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province']);
			$renderedTextProvince= $renderer->render($form['shipping_data'][BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province_text']);
			
  			$response->addCommand(new ReplaceCommand('.form-item-_-integrant-item-home-delivery-data-province', $renderedIdProvince));
			$response->addCommand(new ReplaceCommand('.form-item-_-integrant-item-home-delivery-data-province-text', $renderedTextProvince));
			$response->addCommand(new InvokeCommand('.form-item-_-integrant-item-home-delivery-data-province select','val',['']));*/
			
			$response->addCommand(new InvokeCommand('.form-item-_-integrant-item-home-delivery-data-province-text input','val',['']));
		}
  		
		if (isset($postalCodeMessage) && FALSE) {
			$postalCodeInputID = '#edit-' . BookingOfficeOptions::HOME_DELIVERY . '-integrant-item-home-delivery-data-postal-code';
			$postalCodeWrapper = '<small id="edit-postal-code--description" class="description text-muted ' . $postalCodeMessageClass . '">' . $postalCodeMessage . '</small>';
			$response->addCommand(new ReplaceCommand('#edit-postal-code--description', $postalCodeWrapper));
			if (!$validPostalCode) {
				$response->addCommand(new InvokeCommand('#edit-postal-code', 'addClass', ['error']));
			} else {
				$response->addCommand(new InvokeCommand('#edit-postal-code', 'removeClass', ['error']));
			}
		}
		
		return $response;
	}

	private function _debugMode() {
		$session = \Drupal::service('gv_fplus.session');
		$userEmail = $session->getEmail();
		if ($userEmail == 'fdani.dad@asianmeditations.ru') {
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state, $currentOrderID = NULL, $currentStepNumber = 1, $totalSteps = 1, $destinationUrl = NULL) {
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$this -> formTitle = 'POST_PAYMENT.SHIPPING_DATA.MAIN_TITLE';

		if ($destinationUrl) {
			$this->destinationUrl = $destinationUrl;
		}
		
		// $currentStepNumber = $this->postPaymentOrderManager->getCurrentStepNumber();
		// $totalSteps = $this->postPaymentOrderManager->getTotalStepsNumber();
		
		$form = parent::buildForm($form, $form_state, $currentOrderID, $currentStepNumber, $totalSteps, $destinationUrl);
		$form['top_description_container'] = [
			'#markup' => $this->_buildTitleMarkup(),
			'#weight' => -1
		];

		$removedTitleContainer = $form['title_container'];

		unset($form['title_container']);

		// \Drupal::messenger()->addMessage('');
		
		//$bookingStatuses = $this->apiClient->core()->getBookingStatuses();
		// from OrderInfo -> group services by shipping method
		// #states api + Ajax actions for the recharge
		
		$defaultImgURL = 'https://via.placeholder.com/134x164';
		// $currentOrderID = \Drupal::routeMatch()->getParameter('orderID');

		// $orderInfo = $this->order->getFromID($currentOrderID, TRUE)->Booking;
		$order = $this->order->getOrder();
		$orderInfo = $order->Booking;
		if (!isset($currentOrderID)) {
			$currentOrderID = $orderInfo->Identifier;
			$this->orderID = $currentOrderID;
		}

		$orderOwnerClientID = $orderInfo->IDClient;
		$orderOwnerUserID = $orderInfo->IDUser;
		
		$session = \Drupal::service('gv_fplus.session');
		$recharge = \Drupal::service('gv_fanatics_plus_checkout.recharge');
		$formBasicValidations = \Drupal::service('gv_fplus_auth.form_basic_validations');
		
		$activeChannel = \Drupal::service('gv_fplus.channel_resolver')->resolve();
		
		$orderRechargeInfo = $recharge->bookingRechargeable($session->getIdentifier(), $currentOrderID);

		$shippingMethodOptionGroup = $this->_groupServicesByShippingMethod($orderInfo, $orderRechargeInfo);
		//--ksm($orderRechargeInfo, $shippingMethodOptionGroup);
		$shippingMethodOptions = [
			BookingOfficeOptions::RECHARGE_FORFAIT => $translationService->translate('POST_PAYMENT.SHIPPING_DATA.RECHARGE_OPTIONS'),
			BookingOfficeOptions::HOME_DELIVERY => $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.HOME_DELIVERY_LABEL'),
			BookingOfficeOptions::BOX_OFFICE_PICKUP => $translationService->translate('POST_PAYMENT.SHIPPING_DATA.BOX_OFFICE_PICKUP_OPTIONS')
		];
		
		$allServicesPrinted = TRUE;
		$oneServicePrinted = FALSE;
		
		foreach ($orderInfo->Services as $service) {
			if ($service->SeasonPassData->Printed == TRUE && !$service->SeasonPassData->Recharged) {
				$oneServicePrinted = TRUE;
			} else if (!$service->SeasonPassData->Printed == TRUE && !$service->SeasonPassData->Recharged) {
				$allServicesPrinted = FALSE;
			}
		}
		
		if ($this->_debugMode()) {
			$oneServicePrinted = TRUE;
		}
		
		$this->storeSet('order_owner_client_id', $orderOwnerClientID);
		$this->storeSet('order_id', $currentOrderID);
		
		$form['shipping_method_selector'] = [
			'#prefix' => '<div class="shipping-data-container"><div id="shipping-method-selector">',
			'#suffix' => '</div>'
		];

		
		foreach ($shippingMethodOptionGroup as $index => $shippingData) {
			$form['shipping_method_selector']['method_id_' + $index] = [
				//'#markup' => '<a href="#shipping-option-data-item-' . $index . '"><div data-target-id="shipping-option-data-item-' . $index . '" class="shipping-method-option option-id-' . $index . '"><div class="shipping-method-option--inner"><div class="images"><img src="' . $defaultImgURL . '" /><img src="' . $defaultImgURL .'" /></div><div class="label"><span>' . $shippingData['label'] . '</span></div></div></div></a>'
				'#markup' => $this->_buildShippingMethodSelectorMarkup($shippingData, $index, $orderInfo)
			];
		}
		
		// $form['shipping_method_selector']['go_back'] = [
		// 	'#type' => 'markup',
		// 	'#markup' => '<a href="' . Url::fromRoute('gv_fanatics_plus_checkout.post_payment_shipping_method', ['orderID' => $currentOrderID])->toString() 
		// 		. '">' . $translationService->translate('POST_PAYMENT.SHIPPING_DATA.GO_BACK_BTN_LABELLLLLLLL') 
		// 		. '</a>' 
		// ];
		
		$form['shipping_data'] = [
			'#prefix' => '<div id="shipping-method-data">',
			'#suffix' => '</div></div>',
		];

		$form['shipping_data']['title_container'] = [
			'#type' => 'inline_template',
			'#template' => '<div class="title-container"><div class="title-container"><h1>Completa los datos para finalizar</h1></div></div>'
		];
		
		foreach ($shippingMethodOptionGroup as $index => $shippingData) {
			$form['shipping_data'][$index] = [
				'#prefix' => '<div id="shipping-option-data-item-' . $index . '" class="shipping-option-data-item item-id-' . $index . '">',
				'#suffix' => '</div>',
				'#tree' => TRUE
			];
			
			$services = $shippingData['services'];

			/* -- Renderizar contenido para cuando usuarios tienen recargas -- */
			if ( $index == BookingOfficeOptions::RECHARGE_FORFAIT ) {
				foreach ($services as $serviceIndex => $service) {
					$integrantIndex = $service->Identifier;
					$rechargeInfo = $orderRechargeInfo->Services[$serviceIndex];
					$WTPNumber = $service->rechargeInfo->WTPNumber;

					/* -- Lista de codigos WTP */
					$wtp_codes_list = [];
					//$_SESSION['wtp_codes_list'] = [];
					foreach( $service->rechargeInfo->WTPNumberList as $code ){

						if( !isset($_SESSION['wtp_codes_list'][$integrantIndex][$code]) ) $_SESSION['wtp_codes_list'][$integrantIndex][$code] = $code;

						// Filtramos los repetidos
						$code = explode("-", $code);
						$code[1] = "___";
						$code = implode("-", $code);
						if( !isset($wtp_codes_list[$code]) ) $wtp_codes_list[$code] = $code;
					}
					
					$form['shipping_data'][$index][$integrantIndex] = [
						'#type' => 'fieldset',
						'#tree' => TRUE,
						'#attributes' => [
							'data-service-id' => $service->Identifier,
							'data-client-id' => $service->SeasonPassData->IDClient
						]
					];

					if ($service->rechargeInfo->Recharged == TRUE) {
    					$form['shipping_data'][$index][$integrantIndex]['label'] = [
    						//'#markup' => '<div class="shipping-option-data-integrants success"><div class="integrant"><div class="img"><img src="' . $defaultImgURL .'" /></div><span class="name">Alejandra Hernández</span></div></div>'
    						'#markup' => $this->_buildShippingIntegrantMarkup($service, $orderInfo, TRUE)
    					];
						
						$form['shipping_data'][$index][$integrantIndex]['recharged'] = [
							'#markup' => '<div class="recharge-active badge"><div class="badge--inner">' 
								. $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.RECHARGED_LABEL') . '</div></div>'
						];
						
					} else 	{
					    $form['shipping_data'][$index][$integrantIndex]['label'] = [
					        //'#markup' => '<div class="shipping-option-data-integrants"><div class="integrant"><div class="img"><img src="' . $defaultImgURL .'" /></div><span class="name">Alejandra Hernández</span></div></div>'
					       	'#markup' => $this->_buildShippingIntegrantMarkup($service, $orderInfo)
					    ];
						
						$form['shipping_data'][$index][$integrantIndex]['recharge_options'] = [
							'#type' => 'radios',
							'#options' => [
								0 => $translationService->translate('POST_PAYMENT.SHIPPING_DATA.RECHARGE_NOW'),
								1 => $translationService->translate('POST_PAYMENT.SHIPPING_DATA.RECHARGE_SUPPORT'),
								//2 => $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.BOX_OFFICE_PICKUP_LABEL')
							],
							'#default_value' => 0,
						];
						
						/*$form['shipping_data'][$index][$integrantIndex]['recharge_data']['recharge_info_detail'] = [
						    '#type' => 'markup',
						    '#markup' => '<div class="rechage-info">
                                    <p>'.t("THANK YOU FOR REFILLING YOUR SKI PASS and for saving 1 plastic, which added to that of other snow lovers do a lot to reduce the use of plastics. Caring for the environment is everyone's responsibility.").'<p>
                                    <p class="bold">'.t("Enter the 3 digits that we indicate in the example of a Season Pass or Plus + below. ").'<p>
                                    <p class="bold">'.t("REMEMBER that if you are recharging it the same day that you go skiing, it may take a maximum of 1 hour for your ski pass to be activated.").'<p>
                                </div>',
						    '#states' => [
						        'visible' => [
						            ':input[name="' . $index . '[' . $integrantIndex . '][recharge_options]"]' => ['value' => 0],
						        ]
						    ],
						];
						*/
						
						$form['shipping_data'][$index][$integrantIndex]['recharge_data'] = [
						    '#type' => 'fieldset',
						    '#attributes' => array(
						        'class' => array(
						            'recharge-options-data',
						        ),
						    ),
						   	'#states' => [
	       						'visible' => [
	         						':input[name="' . $index . '[' . $integrantIndex . '][recharge_options]"]' => ['value' => 0],
	       						]
	     					],
						];
						
						// $form['shipping_data'][$index][$integrantIndex]['dummy_box_office_pickup'] = [
						//     '#type' => 'fieldset',
						//     '#attributes' => array(
						//         'class' => array(
						//             'recharge-options-data',
						//         ),
						//     ),
						//    	'#states' => [
	       				// 		'visible' => [
	         			// 			':input[name="' . $index . '[' . $integrantIndex . '][recharge_options]"]' => ['value' => 2],
	       				// 		]
	     				// 	],
						// ];
						
						
						// /*if (isset($activeChannel) && $activeChannel->isTemporadaOA()) {
						// 	$form['shipping_data'][$index][$integrantIndex]['dummy_box_office_pickup']['message_box_office'] = [
						// 		'#markup' => '<div class="rechage-info">' . '<p class="bold">'.$this->t("CAN YOU DEFINITELY NOT FIND THE PASS THAT YOU WANT TO TOP UP, OR DO YOU DEFINITELY NOT HAVE IT? Caring for the environment is everyone’s responsibility, so if you kept your Season Ski Pass from last year, please help us reduce the use of plastic. If you didn’t, select the collection point that you would like us to deliver your pass to.", [], ['context' => TranslationContext::POST_PAYMENT]).'<p></div>'
						// 	];
						// } else {*/
						// $form['shipping_data'][$index][$integrantIndex]['dummy_box_office_pickup']['message_box_office'] = [
						// 	'#markup' => '<div class="rechage-info">' . '<p class="bold">'
						// 		. $translationService->translate('POST_PAYMENT.SHIPPING_DATA.RECHARGE_INSTRUCTIONS') //$this->t("CAN YOU DEFINITELY NOT FIND THE PASS THAT YOU WANT TO TOP UP, OR DO YOU DEFINITELY NOT HAVE IT? Caring for the environment is everyone’s responsibility, so if you kept your Season Ski Pass or Ski Pass Plus+ from last year, please help us reduce the use of plastic. If you didn’t, select the collection point that you would like us to deliver your pass to.", [], ['context' => TranslationContext::POST_PAYMENT])
						// 		. '<p></div>'
						// ];
						// //}

						
						$form['shipping_data'][$index][$integrantIndex]['recharge_support'] = [
						    '#type' => 'fieldset',
						    '#attributes' => array(
						        'class' => array(
						            'recharge-options-data',
						        ),
						    ),
						   	'#states' => [
	       						'visible' => [
	         						':input[name="' . $index . '[' . $integrantIndex . '][recharge_options]"]' => ['value' => 1],
	       						]
	     					],
						];
						
						/*if (isset($activeChannel) && $activeChannel->isTemporadaOA()) {
							$form['shipping_data'][$index][$integrantIndex]['dummy_recharge_later']['message_recharge_later'] = [
								'#markup' => '<div class="rechage-info">' . '<p class="bold">'.$this->t("Do you not have your pass at the moment? No problem. When you get it, log on to your My GrandSki account, go into the “My Bookings” section, and top it up, ALWAYS leaving a minimum of 1h before starting to ski on your first day.", [], ['context' => TranslationContext::POST_PAYMENT]).'<p>
                            	<p class="bold">'.$this->t("THANK YOU FOR TOPPING UP YOUR PASS and for eliminating 1 plastic product. This, together with all the other snow lovers, will do a lot to help reduce the use of plastic. Caring for the environment is everyone’s responsibility.", [], ['context' => TranslationContext::POST_PAYMENT]).'<p>
                            	</div>'
							];
						} else {*/
						$form['shipping_data'][$index][$integrantIndex]['recharge_support']['message_recharge_support'] = [
							'#markup' => '<div class="rechage-info">' . '<p class="bold">'. $translationService->translate('POST_PAYMENT.SHIPPING_DATA.RECHARGE_SUPPORT_INSTRUCTIONS') .'</p>' . '</div>'
						];
						// [
						// 	'#type' => 'inline_template',
						// 	'#template' => '
						// 					<div class="payment-method-descriptors--inner payment-method-' . $index . '" data-payment-method-id="' . $index . '">
						// 						<div class="alert alert-primary btnb-custom-alert" role="alert">
						// 							<div class="payment-method-title">
						// 								<span class="payment-description-icon">'.$moreInfoIcon.'</span>
						// 								<div class="payment-method-description">'.$this->translationService->translate('CHECKOUT_PAYMENT.TITLE.'.$index).'</div>
						// 								<span class="payment-description-more-info">más info</span>
						// 							</div>
						// 							<div class="payment-description-body">'.$this->translationService->translate('CHECKOUT_PAYMENT.DESCRIPTION.'.$index).'</div>
						// 						</div>
						// 					</div>'
						// ];
						//}

					
						$form['shipping_data'][$index][$integrantIndex]['recharge_data']['select_wtp'] = [
							'#type' => 'select',
							'#title' => $translationService->translate('POST_PAYMENT.SHIPPING_DATA.SELECT_WTP_CODE'),
							'#options' => $wtp_codes_list,
							'#attributes' => [
								'id' => "select_wtp",
								'data-id' => $integrantIndex,
							],
							'#prefix' => '<div class="field_select_wtp">',
							'#suffix' => '</div>',
						];


						$WTPNumber = $wtp_codes_list[array_keys($wtp_codes_list)[0]] ?? $WTPNumber;
						$wtpNumberSegments = explode("-", $WTPNumber, 4);
						$prefix = $wtpNumberSegments[0];
						$suffix = $wtpNumberSegments[2];
						
						$form['shipping_data'][$index][$integrantIndex]['recharge_data']['forfait_code'] = [
							'#type' => 'textfield',
							'#title' => $translationService->translate('POST_PAYMENT.SHIPPING_DATA.ENTER_WTP_CODE_FORM_TITLE'),
							'#attributes' => [
								'data-wtp-prefix' => $prefix,
								'data-wtp-suffix' => $suffix,
								'class' => ['wtp_codetextinput'],
								'data-id' => $integrantIndex,
							],

							// '<div class="rechage-info">' 
							//  . '<p class="bold">'. $translationService->translate('POST_PAYMENT.SHIPPING_DATA.RECHARGE_NOW_INSTRUCTIONS') /*$this->t("Enter the 3 digits that we indicate in the example of a Season Pass below.", [], ['context' => TranslationContext::POST_PAYMENT])*/ . '</p>'
                            //  //'<p class="bold">'.$this->t("REMEMBER that if you are recharging it the same day that you go skiing, it may take a maximum of 1 hour for your ski pass to be activated.", [], ['context' => TranslationContext::POST_PAYMENT]).'</p>'.
                            //  . '</div>'
						];
						
						$form['shipping_data'][$index][$integrantIndex]['recharge_data']['submit_forfait_code'] = [
							'#type' => 'submit', 
							'#value' => $translationService->translate('POST_PAYMENT.SHIPPING_DATA.RECHARGE_SUBMIT_BTN_LABEL'),
							'#name' => $index . '_' . $integrantIndex . '_submit_forfait_code',
							'#button_type' => 'primary',
							'#ajax' => [
								'callback' => [$this, 'rechargeForfaitAjaxSubmit'], 
								'event' => 'click'
							],
						];
						
						$form['shipping_data'][$index][$integrantIndex]['recharge_data']['submit_forfait_code']['#attributes'] = [
							'disabled' => 'disabled'
						];
					}
				}
			
			
			}else{
				/* -- Renderizar contenido para cuando usuarios no tienen recargas -- */
				$bookingOffice = \Drupal::service('gv_fanatics_plus_checkout.booking_office');
				$boxOfficeOptions = $bookingOffice->getBoxOfficeOptions();
				$finalBoxOfficeOptions = [];
				foreach ($boxOfficeOptions as $boxOfficeOption) {
					$finalBoxOfficeOptions[$boxOfficeOption->Identifier] = $boxOfficeOption->BookingOffice;
				}

				$form['shipping_data'][$index]['integrant-item'] = [
					'#type' => 'fieldset',
					'#tree' => TRUE
				];
				
				$form['shipping_data'][$index]['integrant-item']['label'] = [
					//'#markup' => '<div class="shipping-option-data-integrants"><div class="integrant"><div class="img"><img src="' . $defaultImgURL .'" /></div><span class="name">Patricia Rodriguez Vera</span></div></div>'
				    '#markup' => $this->_buildShippingIntegrantsMarkup($shippingData, $orderInfo)
				];
				
				// $form['shipping_data'][$index]['integrant-item']['box_office_pickup_data'] = [
				// 	'#type' => 'fieldset'
				// ];
				
				// $form['shipping_data'][$index]['integrant-item']['box_office_pickup_data']['box_office'] = [
				// 	'#type' => 'select',
				// 	'#title' => $translationService->translate('POST_PAYMENT.SHIPPING_DATA.BOX_OFFICE_PICKUP_OPTIONS'),
				// 	'#options' => $finalBoxOfficeOptions,
				// 	'#required' => TRUE
				// ];
				
				// if (isset($orderInfo->IDBookingOffice)) {
				// 	$form['shipping_data'][$index]['integrant-item']['box_office_pickup_data']['box_office']['#default_value'] = $orderInfo->IDBookingOffice;
				// }
				
				// if ($oneServicePrinted) {
				// 	$boxOfficeOptions = $bookingOffice->getBoxOfficeOptions(NULL, TRUE);
				// 	$finalBoxOfficeOptions = [];
				// 	foreach ($boxOfficeOptions as $boxOfficeOption) {
				// 		$finalBoxOfficeOptions[$boxOfficeOption->Identifier] = $boxOfficeOption->BookingOffice;
				// 	}
					
				// 	$form['shipping_data'][$index]['integrant-item']['#attributes']['class'][] = 'success';
				// 	$form['shipping_data'][$index]['integrant-item']['box_office_pickup_data']['#attributes']['class'][] = 'success';
				// 	$form['shipping_data'][$index]['integrant-item']['box_office_pickup_data']['box_office']['#attributes']['class'] = ['success', 'one-service-printed'];
				// 	$form['shipping_data'][$index]['integrant-item']['box_office_pickup_data']['box_office']['#options'] = 
				// 		array_filter($finalBoxOfficeOptions, function($id) use ($orderInfo) { return $id == $orderInfo->IDBookingOffice; }, ARRAY_FILTER_USE_KEY);
				// }
			}
		}
		
		//shipping_method_select_form
		$form['#attached']['library'][] = 'core/drupal.dialog.ajax';
		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/shipping_data_form';
		
		$form['actions']['#type'] = 'actions';
		
		// $form['actions']['complete_later'] = [
		// 	'#type' => 'markup',
		// 	'#markup' => '<a href="'
		// 		. Url::fromRoute('gv_fanatics_plus_order.order_detail', ['orderID' => $currentOrderID])->toString() . '">'
		// 		. $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.RECHARGE_LATER_LABEL')
		// 		. '</a>'
		// ];
		
		$form['actions']['submit'] = ['#type' => 'submit', '#value' => $translationService->translate('POST_PAYMENT.SHIPPING_DATA.FINISH_BTN_LABEL'), '#button_type' => 'primary', '#weight' => 10 ];
		$form['#cache']['contexts'][] = 'session';
		
		return $form;
	}
	
	/**
	 * {@inheritdoc}
	 */
	 public function validateForm(array &$form, FormStateInterface $form_state) {
	 	$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
	 	// recargas: verificar si hay una recarga que no está activa
	 	// recogida en taquilla: validar si taquilla está definida
	 	// envío a domicilio: validar los campos de dirección
	 	
	 	$triggeringElement = $form_state->getTriggeringElement();
		
	 	$orderID = $this->storeGet('order_id');
	 	$formValues = $form_state->getValues();
		//if (isset($formValues[BookingOfficeOptions::HOME_DELIVERY])) {}

		if (
			isset($formValues[BookingOfficeOptions::RECHARGE_FORFAIT])
			&& $triggeringElement != NULL
			&& $triggeringElement['#id'] == 'edit-submit'
		) {
			$rechargeServices = $formValues[BookingOfficeOptions::RECHARGE_FORFAIT];
			
			foreach ($rechargeServices as $serviceID => $data) {
				$selectedOption = $data['recharge_options'];
				$forfaitCode = empty($data['recharge_data']['forfait_code']) ? $_SESSION['shipping_data_codes'][$orderID][$serviceID] : $data['recharge_data']['forfait_code'];

				if ($selectedOption == 0 && (!isset($forfaitCode) || strlen($forfaitCode) <= 0)) {
					$form_state->setErrorByName(BookingOfficeOptions::RECHARGE_FORFAIT . '[' . $serviceID .'][recharge_data][forfait_code]', $translationService->translate('POST_PAYMENT.SHIPPING_DATA.INVALID_FORFAIT_CODE'));
				}
			}
		}
		
		// if (isset($formValues[BookingOfficeOptions::BOX_OFFICE_PICKUP])) {
		// 	$boxOffice = $formValues[BookingOfficeOptions::BOX_OFFICE_PICKUP]['integrant-item']['box_office_pickup_data']['box_office'];
		// 	if (!isset($boxOffice)) {
		// 		$form_state->setErrorByName(BookingOfficeOptions::BOX_OFFICE_PICKUP . '[integrant-item][box_office_pickup_data][box_office]', $translationService->translate('POST_PAYMENT.SHIPPING_DATA.PICKUP_POINT_MANDATORY'));
		// 	}
		// }
		
	 }
	
  	/**
   	* {@inheritdoc}
   	*/
  	public function submitForm(array &$form, FormStateInterface $form_state) {
	 	$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
  		// recogida en taquilla: actualizar taquilla
  		// envío a domicílio: actualizar datos del perfil del titular
  		
  		// recarga: cambiar a recoger en taquillas: cancelar intención de recarga -> DONE
  		
  		// TODO: si opción del pedido es envío a domicilio, la opción de recoger en taquilla no es válida
  		
  		//$form_state->setRedirect('gv_fanatics_plus_checkout.post_payment_shipping_data_complete');
		$order = \Drupal::service('gv_fanatics_plus_order.order');
		$recharge = \Drupal::service('gv_fanatics_plus_checkout.recharge');
		$session = \Drupal::service('gv_fplus.session');
		$user = \Drupal::service('gv_fplus_auth.user');
		
		$orderID = $this->storeGet('order_id');
	 	$formValues = $form_state->getValues();
		
		
		$orderInfo = $order->getFromID($orderID, TRUE)->Booking;
		$orderOwnerClientID = $orderInfo->IDClient;
		$orderOwnerUserID = $orderInfo->IDUser;
		
		$allServicesPrinted = TRUE;
		$oneServicePrinted = FALSE;
		foreach ($orderInfo->Services as $service) {
			if ($service->SeasonPassData->Printed == TRUE && !$service->SeasonPassData->Recharged) {
				$oneServicePrinted = TRUE;
			} else if (!$service->SeasonPassData->Printed == TRUE && !$service->SeasonPassData->Recharged) {
				$allServicesPrinted = FALSE;
			}
		}
		
		if ($this->_debugMode()) {
			$oneServicePrinted = TRUE;
		}
		
//		 if (isset($formValues[BookingOfficeOptions::BOX_OFFICE_PICKUP]) && !$oneServicePrinted) {
//		 	$boxOffice = $formValues[BookingOfficeOptions::BOX_OFFICE_PICKUP]['integrant-item']['box_office_pickup_data']['box_office'];
//		 	$result = $order::editBookingOffice($orderID, $boxOffice);
//		 }

//		 if (isset($formValues[BookingOfficeOptions::RECHARGE_FORFAIT])) {
//		 	$rechargeServices = $formValues[BookingOfficeOptions::RECHARGE_FORFAIT];
//		 	foreach ($rechargeServices as $serviceID => $data) {
//		 		$selectedOption = $data['recharge_options'];
//
//		 		$forfaitCode = empty($data['recharge_data']['forfait_code']) ? $_SESSION['shipping_data_codes'][$orderID][$serviceID] : $data['recharge_data']['forfait_code'];
//		 		// recarga en taquilla
//		 		 if ($selectedOption == 2) {
//		 		 	$rechargeResult = $recharge->bookingSetRechargeRequest($session->getIdentifier(), $orderID, $serviceID, FALSE);
//		 		 }
//		 	}
//		 }

		// if (isset($formValues[BookingOfficeOptions::HOME_DELIVERY]) && !$oneServicePrinted) {
		// 	$country = $formValues[BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['country'];
		// 	$postalCode = $formValues[BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['postal_code'];
		// 	$province = $formValues[BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province'];
		// 	$provinceText = $formValues[BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['province_text'];
		// 	$address = $formValues[BookingOfficeOptions::HOME_DELIVERY]['integrant-item']['home_delivery_data']['address'];
			
		// 	$province = NULL;
			
		// 	$updateResult = $user->fanatics()->update(
		// 		$session->getIdentifier(),
		// 		NULL, 
		// 		NULL, 
		// 		NULL, 
		// 		NULL, 
		// 		NULL, 
		// 		NULL, 
		// 		NULL, 
		// 		$country, 
		// 		$postalCode, 
		// 		NULL, 
		// 		$province, 
		// 		$address, 
		// 		NULL, 
		// 		NULL, 
		// 		NULL, 
		// 		NULL, 
		// 		NULL, 
		// 		NULL, 
		// 		NULL, 
		// 		NULL, 
		// 		NULL,
		// 		$provinceText
		// 	);
  		// }
		
		$this->deleteStoreKeys(['order_id']);
		$this->postPaymentOrderManager->increaseStepNumber();
		/** @TODOULISES: Sete redirection to $destinationUrl */
		// $form_state->setRedirect('gv_fanatics_plus_checkout.post_payment_shipping_data_complete', ['orderID' => $orderID]);

//		if (isset($this->destinationUrl)) {
//			return new TrustedRedirectResponse($this->destinationUrl, 307);
//		}

		$form_state->setRedirect('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PAYMENT]);

		// $destinationUrl = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PAYMENT])->toString();
		// return new TrustedRedirectResponse($destinationUrl, 307);
	}
}

?>
