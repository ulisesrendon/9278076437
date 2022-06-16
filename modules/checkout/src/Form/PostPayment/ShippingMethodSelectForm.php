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
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;

use Drupal\gv_fanatics_plus_checkout\Ajax\DisableFullscreenLoader;

use Drupal\gv_fanatics_plus_order\Order;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario correspondiente al paso de selección de método envío / recarga del proceso de Post-pago.
 */
class ShippingMethodSelectForm extends MultistepFormBase {

  	/**
   	* {@inheritdoc}.
   	*/
  	public function getFormId() {
    	return 'gv_fplus_checkout_shipping_method_select_form';
  	}

	protected function _buildTitleMarkup() {
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$markup = '<div class="top-description-container">';
		$markup .= '<div class="top-description-container--title">' . '<h3>' 
		. $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.PAYMENT_SUCCESS_TITLE')
		. '</h3></div>';
		//$markup .= '<div class="top-description-container--body">' . '<p>' . $this->t('Ahora lo último, pero no menos importante. ¿Cómo quieres recibir tu forfait?', [], ['langcode' => 'es']) . '</p></div>';
		$markup .= '</div>';
		
		return $markup;
	}

	private function _getDefaultImageAvatar() {
		return '<svg width="88" height="88" viewBox="0 0 88 88" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0.5" y="0.5" width="39" height="39" rx="19.5" fill="#C3C9D6"/><g filter="url(#filter0_d)"><circle cx="36" cy="31" r="11.5" fill="white" stroke="#F0F2F5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M35.2857 30.2857V26H36.7143V30.2857H41V31.7143H36.7143V36H35.2857V31.7143H31V30.2857H35.2857Z" fill="#99A3B1"/></g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.3809 30C12.3809 25.7921 15.792 22.381 19.9999 22.381C24.2078 22.381 27.619 25.7921 27.619 30H25.7142C25.7142 26.8441 23.1558 24.2857 19.9999 24.2857C16.844 24.2857 14.2856 26.8441 14.2856 30H12.3809ZM19.9999 21.4286C16.8428 21.4286 14.2856 18.8714 14.2856 15.7143C14.2856 12.5571 16.8428 10 19.9999 10C23.1571 10 25.7142 12.5571 25.7142 15.7143C25.7142 18.8714 23.1571 21.4286 19.9999 21.4286ZM19.9999 19.5238C22.1047 19.5238 23.8094 17.819 23.8094 15.7143C23.8094 13.6095 22.1047 11.9048 19.9999 11.9048C17.8951 11.9048 16.1904 13.6095 16.1904 15.7143C16.1904 17.819 17.8951 19.5238 19.9999 19.5238Z" fill="white"/><rect x="0.5" y="0.5" width="39" height="39" rx="19.5" stroke="#F0F2F5"/><defs><filter id="filter0_d" x="16" y="15" width="40" height="40" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feOffset dy="4"/><feGaussianBlur stdDeviation="4"/><feColorMatrix type="matrix" values="0 0 0 0 0.0541176 0 0 0 0 0.145098 0 0 0 0 0.173333 0 0 0 0.12 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow" result="shape"/></filter></defs></svg>';
	}

	public function updateImageAjaxCallback(array &$form, FormStateInterface $form_state) {
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
				
		$response = new AjaxResponse();
		
		$session = \Drupal::service('gv_fplus.session');
		$image = \Drupal::service('gv_fplus_auth.image');
		$integrant = \Drupal::service('gv_fanatics_plus_checkout.integrant');
		$user = \Drupal::service('gv_fplus_auth.user');
		
		$triggeringElement = $form_state->getTriggeringElement();
		if (!isset($triggeringElement)) {
			return $response;
		}

		$targetClientID = $triggeringElement['#attributes']['data-client-id'];
		if (!isset($targetClientID)) {
			return $response;
		}
		
		$isOwner = isset($triggeringElement['#attributes']['data-is-owner']) ? TRUE : FALSE;
		
		$targetServiceID = $triggeringElement['#parents'][1];
		if (!isset($targetServiceID) && !$isOwner) {
			return $response;
		}
		
		$profileImage = $triggeringElement['#value'];
		if (!isset($profileImage)) {
			return $response;
		}
		
		try {
			$profileImage = str_replace('data:image/jpeg;base64,', '', $profileImage);
			if ($isOwner) {
				$uploadResponse = $image->upload($session->getIdentifier(), $profileImage, '.jpeg');
			} else {
				$uploadResponse = $image->upload($session->getIdentifier(), $profileImage, '.jpeg', NULL, $targetClientID);
			}
		} catch (Exception $e) {
			\Drupal::messenger()->addMessage($translationService->translate('POST_PAYMENT.SHIPPING_METHOD.IMAGE.UPLOAD_ERROR'), 'error');
		}
		
		if ($isOwner) {
			$response->addCommand(new RemoveCommand('#edit-order-owner-shipping-info .panel.warning'));
		} else {
			$response->addCommand(new RemoveCommand('#edit-order-integrants-shipping-info-' . $targetServiceID . ' .panel.warning'));
		}
		
		$response->addCommand(new DisableFullscreenLoader(NULL));
		
		$profile = NULL;
		$profileImage = NULL;
		try {
			if ($isOwner) {
				$email = $session->getEmail();
				$profile = $user->getProfile($email, TRUE, TRUE, FALSE);
	
				$profileImage = new \stdClass();
				$profileImage->ImageBase64 = $profile->Image;
				$profileImage->Expired = $profile->ImageExpired;
				$profileImage->CanEdit = $profile->CanEditImage;
			} else {
				$activeIntegrantID = $targetClientID;
				$activeIntegrant = NULL;
				
				// TODO: we shouldn't need to filter for the integrant
				$integrants = $integrant->listMember($session->getIDClient(), TRUE, FALSE)->List;
				foreach ($integrants as $integrant) {
					if ($integrant->IntegrantID == $activeIntegrantID) {
						$activeIntegrant = $integrant;
					}
				}
				
				$profile = $activeIntegrant;
				
				$profileImage = new \stdClass();
				$profileImage->ImageBase64 = $profile->ImageBase64;
				$profileImage->Expired = $profile->ImageExpired;
				$profileImage->CanEdit = $profile->ImageCanEdit;
			}
			
			if ($profileImage->CanEdit == FALSE) {
				if ($isOwner) {
					$response->addCommand(new RemoveCommand('#edit-order-owner-shipping-info .edit-image-btn'));
				} else {
					$response->addCommand(new RemoveCommand('#edit-order-integrants-shipping-info-' . $targetServiceID . ' .edit-image-btn'));
				}
			}
			
		} catch(\Exception $e) {
			\Drupal::logger('php')->error($e->getResponse()->getBody()->getContents());
			return $response;
		}
	
		return $response;
	}

	private function _debugMode() {
		$session = \Drupal::service('gv_fplus.session');
		$userEmail = $session->getEmail();
		
		return FALSE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state, $currentOrderID = NULL, $currentStepNumber = 1, $totalSteps = 1, $destinationUrl = NULL) {
		$this -> formTitle = 'POST_PAYMENT.SHIPPING_METHOD.MAIN_TITLE';
		
		$currentStepNumber = $this->postPaymentOrderManager->getCurrentStepNumber();
		$totalSteps = $this->postPaymentOrderManager->getTotalStepsNumber();

		$form = parent::buildForm($form, $form_state, $currentOrderID, $currentStepNumber, $totalSteps, $destinationUrl);
		
		$request = \Drupal::request();
		$showPaymentSuccessMessage = $request->query->get('show_payment_success');
		if ($showPaymentSuccessMessage != NULL && $showPaymentSuccessMessage == 1) {
			$form['top_description_container'] = [
				'#markup' => $this->_buildTitleMarkup(),
				'#weight' => -1
			];
		}
		
		$currentOrderID = \Drupal::routeMatch()->getParameter('orderID');
		
		$session = \Drupal::service('gv_fplus.session');
		$recharge = \Drupal::service('gv_fanatics_plus_checkout.recharge');
		
		$bookingOffice = \Drupal::service('gv_fanatics_plus_checkout.booking_office');
		$bookingOffices = $bookingOffice->getBySessionID($session->getIdentifier());
		
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$orderInfo = $this->order->getFromID($currentOrderID, TRUE)->Booking;
		$orderRechargeInfo = $recharge->bookingRechargeable($session->getIdentifier(), $currentOrderID);
		$orderOwnerClientID = $orderInfo->IDClient;
		$orderOwnerUserID = $orderInfo->IDUser;
		
		if ($showPaymentSuccessMessage != NULL && $showPaymentSuccessMessage == 1 && !$orderInfo->SynchronizedConversionScript) {
			$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/checkout_complete'; 
      		$form['#attached']['drupalSettings']['checkout_complete']['order'] = $orderInfo;
			$this->order->editBookingSynchronizedConversionScript($currentOrderID, TRUE);
		}
		
		$shippingMethodOptions = [
			BookingOfficeOptions::HOME_DELIVERY => $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.HOME_DELIVERY_LABEL'), //$this->t('Delivery to my address', [], ['context' => TranslationContext::POST_PAYMENT]),
			BookingOfficeOptions::BOX_OFFICE_PICKUP => $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.BOX_OFFICE_PICKUP_LABEL'),//$this->t('Collect from ticket office', [], ['context' => TranslationContext::POST_PAYMENT]),
			BookingOfficeOptions::RECHARGE_FORFAIT => $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.RECHARGE_LABEL') //$this->t('Top up my pass', [], ['context' => TranslationContext::POST_PAYMENT])
		];
		
		$this->storeSet('order_id', $currentOrderID);
		
		$ownerHasProducts = FALSE;
		$ownerServiceID = NULL;
		$integrantsHaveProducts = FALSE;
		$oneServicePrinted = FALSE;
		
		if ($this->_debugMode()) { // TODO: remove
			$oneServicePrinted = TRUE;
		}
		
		$form['description'] = [
			'#type' => 'fieldset'
		];
		
		$activeChannel = \Drupal::service('gv_fplus.channel_resolver')->resolve();
		$form['description']['intro'] = [
			'#markup' => $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.INTRO')
		];

		
		$instructionsModalUrl = Url::fromRoute('gv_fanatics_plus_checkout.post_payment_shipping_method_modal')->toString();
		$form['description']['instructions'] = [
			'#markup' => '<a class="use-ajax" href="' . $instructionsModalUrl . '">' 
				. $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.LINK') 
				. '</a>'
		];
		
		
		foreach ($orderInfo->Services as $service) {
			if ($orderOwnerClientID == $service->SeasonPassData->IDClient) {
				$ownerHasProducts = TRUE;
				$ownerServiceID = $service->Identifier;
			} else {
				$integrantsHaveProducts = TRUE;
			}
			
			if ($service->SeasonPassData->Printed == TRUE && !$service->SeasonPassData->Recharged) {
				$oneServicePrinted = TRUE;
			}
		}
		
		$IDBookingOffice = $orderInfo->IDBookingOffice;
		if (!isset($IDBookingOffice)) {
			if ($bookingOffice->isHomeDeliveryEnabled($bookingOffices)) {
				$orderShippingMethod = BookingOfficeOptions::HOME_DELIVERY;	
			} else {
				$orderShippingMethod = BookingOfficeOptions::BOX_OFFICE_PICKUP;
			}
		} else {
			$orderShippingMethod = $bookingOffice->getOptionFromID($IDBookingOffice);
		}
		
		if ($oneServicePrinted) {
			$shippingMethodOptions = array_filter($shippingMethodOptions, function($methodName, $methodID) use ($orderShippingMethod) {
				return ($methodID == $orderShippingMethod || $methodID == BookingOfficeOptions::RECHARGE_FORFAIT);
			}, ARRAY_FILTER_USE_BOTH);
		} else {
			if (!$bookingOffice->isHomeDeliveryEnabled($bookingOffices) && $orderShippingMethod != BookingOfficeOptions::HOME_DELIVERY) {
				unset($shippingMethodOptions[BookingOfficeOptions::HOME_DELIVERY]);
			} else if (!$bookingOffice->isHomeDeliveryEnabled($bookingOffices) && $orderShippingMethod == BookingOfficeOptions::HOME_DELIVERY) {
				$form['description']['warning_home_delivery_disabled'] = [
					'#type' => 'markup',
		    		'#markup' => '<div class="panel warning no-home-delivery"><div class="panel--inner"><div class="panel-heading"><p>' 
		    			. $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.HOME_DELIVERY_OPTION_DISABLED') 
		    			. '</p></div></div></div>'
				];
			}
		
			if (!$bookingOffice->isBoxOfficePickupEnabled($bookingOffices)) {
				unset($shippingMethodOptions[BookingOfficeOptions::BOX_OFFICE_PICKUP]);
			}
		}
		
		if ($ownerHasProducts) {
			$form['order_owner_shipping_info'] = [
				'#type' => 'fieldset',
				'#tree' => TRUE
			];
			
			if ((!$orderInfo->OwnerIntegrant->ImageExpired && !$orderInfo->OwnerIntegrant->CanEditImage)) {
				if (isset($orderInfo->OwnerIntegrant->ImageBase64)) {
					$form['order_owner_shipping_info']['thumbnail'] = [
						'#markup' => '<div class="img-thumbnail"><img data-src="' . 'data:image/jpeg;base64,' . $orderInfo->OwnerIntegrant->ImageBase64 . '"/></div><div class="username">' . $orderInfo->OwnerIntegrant->Name . ' ' . $orderInfo->OwnerIntegrant->Surname . '</div>'
					];
				} else {
					$form['order_owner_shipping_info']['thumbnail'] = [
						'#markup' => '<div class="img-thumbnail"><img src="' . Order::getDefaultUserAvatar() . '"/></div><div class="username">' . $orderInfo->OwnerIntegrant->Name . ' ' . $orderInfo->OwnerIntegrant->Surname . '</div>'
					];
				}
			} else if (($orderInfo->OwnerIntegrant->ImageExpired || !isset($orderInfo->OwnerIntegrant->ImageBase64)) && $orderInfo->OwnerIntegrant->CanEditImage) {
				$form['order_owner_shipping_info']['thumbnail'] = [
					//'#markup' => '<div class="img-thumbnail empty"><img data-src="' . '/modules/custom/gv_fplus/img/default-avatar.svg' . '"/></div><div class="username">' . $service->IntegrantData->Name . ' ' . $service->IntegrantData->Surname . '</div>'
					'#markup' => '<div class="img-thumbnail empty"><img class="image-preview" src="' . Order::getDefaultUserAvatar()  . '" /></div><a class="edit-image-btn"><span class="edit-image-btn--inner"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/pencil-fill.svg"/></span></a>' . '<div class="username">' . $orderInfo->OwnerIntegrant->Name . ' ' . $orderInfo->OwnerIntegrant->Surname . '</div>'
				];
				
				if ($orderInfo->OwnerIntegrant->ImageExpired) {
					$orderInfo->OwnerIntegrant->ImageBase64 = '';
				}
				
				$form['order_owner_shipping_info']['image'] = [
					'#type' => 'textfield',
					'#attributes' => [
						'class' => ['hidden', 'image-input-edit']
					],
					'#maxlength' => 100000,
					'#default_value' => $orderInfo->OwnerIntegrant->ImageBase64
				];
	
				$form['order_owner_shipping_info']['image_base64'] = [
					'#type' => 'textarea',
					'#attributes' => [
						'class' => ['hidden', 'image-input-base64-edit'],
						'data-client-id' => $orderOwnerClientID,
						'data-is-owner' => TRUE
					],
					'#default_value' => $orderInfo->OwnerIntegrant->ImageBase64,
					'#ajax' => [
						'callback' => '::updateImageAjaxCallback', // don't forget :: when calling a class method.
						'disable-refocus' => TRUE, // Or TRUE to prevent re-focusing on the triggering element.
						'event' => 'change',
						'progress' => [
							'type' => 'throbber',
							'message' => $translationService->translate('PERSONAL_DATA_FORM.IMAGE_UPLOADING_LABEL'),
						],
					],
				];
				
				$form['order_owner_shipping_info']['no_image'] = [
		    		'#markup' => '<div class="panel warning"><div class="panel--inner"><div class="panel-heading"><p>' 
		    			. $translationService->translate('ORDER_DETAIL.NO_PROFILE_IMAGE_WARNING') 
		    			. '</p></div></div></div>'
		    	];
			}
			
			$form['order_owner_shipping_info']['shipping_method'] = [
				'#type' => 'select',
				'#options' => $shippingMethodOptions,
				'#required' => TRUE,
			];
		}

		if (TRUE) {
			if ($integrantsHaveProducts) {
			    $form['messages_change_select'] = [
			        '#prefix' => '<div class="messages-change-select">',
			        '#suffix' => '</div>',
			        '#tree' => TRUE
			    ];
			    
			    $form['messages_change_select']['punto_recogida'] = [
			        '#type' => 'markup',
			        '#markup' => '<div class="hidden message-change-select punto-recogida"><div class="icon"></div><div class="text">'
			        . $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.CHANGE_TO_BOX_OFFICE_NOTICE')
			        . '</div><div class="close"></div></div>',
			    ];
			    
			    $form['messages_change_select']['envio_domicilio'] = [
			        '#type' => 'markup',
			        '#markup' => '<div class="hidden message-change-select envio-domicilio"><div class="icon"></div><div class="text">'
			        . $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.CHANGE_TO_HOME_DELIVERY_NOTICE')
			        . '</div><div class="close"></div></div>',
			    ];
			    
				$form['order_integrants_shipping_info'] = [
	            	'#prefix' => '<div id="edit-order-integrants-shipping-info"><div class="fieldset-wrapper">',
			    	'#suffix' => '</div></div>',
			    	'#tree' => TRUE
				];
			
				$form['order_integrants_shipping_info']['title'] = [
					'#markup' => '<h3>' . $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.INTEGRANT_HEADER') . '</h3>'
				];
			}
			
			$services = $orderInfo->Services;
			$integrants = \Drupal::service('gv_fanatics_plus_checkout.integrant')->listMembers($orderOwnerClientID);
			
			if (count($services) > 0) {
				foreach ($services as $index => $service) {
					$isOwnerService = ($orderOwnerClientID == $service->SeasonPassData->IDClient);
					
					if ($isOwnerService) {
						$this->storeSet('owner_service_id', $service->Identifier);
					}
					
	 				// Variables
	 				// Printed
	 				// OneServicePrinted
	 				// WillRecharge
	 				// Recharged
	 				// OrderShippingMethod
	 				
	 				$printed = $service->SeasonPassData->Printed;
					$willRecharge = $orderRechargeInfo->Services[$index]->RechargeRequest;
					$recharged = $service->SeasonPassData->Recharged;
					$rechargeable = $service->SeasonPassData->Rechargeable;
					$defaultShippingOption = $orderShippingMethod;
					
					if ($this->_debugMode() && !$recharged && !$rechargeable) { // TODO: remove
						$printed = TRUE;
					}
					
					$imageBase64 = $service->IntegrantData->ImageBase64;
					$succ_ok = (($printed || $recharged || $oneServicePrinted) ? 'success' : '');
					if (!$isOwnerService) {
						$form['order_integrants_shipping_info'][$service->Identifier] = [
						    '#type' => 'fieldset',
						    '#attributes' => [
						        'class' => [
						            $succ_ok,
						        ],
						        'data-client-id' => $service->SeasonPassData->IDClient,
						        'data-service-id' => $service->Identifier
						    ],
						    '#tree' => TRUE
						];

						if (isset( $imageBase64 ) && !$service->IntegrantData->ImageExpired) {
							$form['order_integrants_shipping_info'][$service->Identifier]['thumbnail'] = [
								'#markup' => '<div class="img-thumbnail"><img data-src="' . 'data:image/jpeg;base64,' . $imageBase64 . '"/></div><div class="username">' . $service->IntegrantData->Name . ' ' . $service->IntegrantData->Surname . '</div>'
							];
						} else if ($service->IntegrantData->ImageCanEdit){
							$form['order_integrants_shipping_info'][$service->Identifier]['thumbnail'] = [
								//'#markup' => '<div class="img-thumbnail empty"><img data-src="' . '/modules/custom/gv_fplus/img/default-avatar.svg' . '"/></div><div class="username">' . $service->IntegrantData->Name . ' ' . $service->IntegrantData->Surname . '</div>'
								'#markup' => '<div class="img-thumbnail empty"><img class="image-preview" src="' . Order::getDefaultUserAvatar()  . '" /></div><a class="edit-image-btn"><span class="edit-image-btn--inner"><img src="/themes/contrib/bootstrap_sass/Assets/Iconografia/SVG/pencil-fill.svg"/></span></a>' . '<div class="username">' . $service->IntegrantData->Name . ' ' . $service->IntegrantData->Surname . '</div>'
							];
							
							if ($service->IntegrantData->ImageExpired) {
								$imageBase64 = '';
							}
							
							$form['order_integrants_shipping_info'][$service->Identifier]['image'] = [
								'#type' => 'textfield',
								'#attributes' => [
									'class' => ['hidden', 'image-input-edit']
								],
								'#maxlength' => 100000,
								'#default_value' => $imageBase64
							];

							$form['order_integrants_shipping_info'][$service->Identifier]['image_base64'] = [
								'#type' => 'textarea',
								'#attributes' => [
									'class' => ['hidden', 'image-input-base64-edit'],
									'data-client-id' => $service->SeasonPassData->IDClient
								],
								'#default_value' => $imageBase64,
								'#ajax' => [
									'callback' => '::updateImageAjaxCallback', // don't forget :: when calling a class method.
									'disable-refocus' => TRUE, // Or TRUE to prevent re-focusing on the triggering element.
									'event' => 'change',
									'progress' => [
										'type' => 'throbber',
										'message' => $translationService->translate('PERSONAL_DATA_FORM.IMAGE_UPLOADING_LABEL')
									],
								],
							];
							
							$form['order_integrants_shipping_info'][$service->Identifier]['no_image'] = [
		    					'#markup' => '<div class="panel warning"><div class="panel--inner"><div class="panel-heading"><p>' 
		    						. $translationService->translate('ORDER_DETAIL.NO_PROFILE_IMAGE_WARNING') 
		    						. '</p></div></div></div>'
		    				];
						}
					} else {
						$form['order_owner_shipping_info']['#attributes'] = ['class' => $succ_ok];
					}
					
					$serviceShippingMethodOptions = array_filter($shippingMethodOptions, function() { return TRUE; });
					if (!$rechargeable) {
						$serviceShippingMethodOptions = array_filter($shippingMethodOptions, function($methodName, $methodID) {
							return ($methodID != BookingOfficeOptions::RECHARGE_FORFAIT);
						}, ARRAY_FILTER_USE_BOTH);
					}
					
					if ($recharged) {
						$serviceShippingMethodOptions = [ BookingOfficeOptions::FORFAIT_RECHARGED => $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.RECHARGED_LABEL') ];
						$defaultShippingOption = BookingOfficeOptions::FORFAIT_RECHARGED;
					} else if ($printed) { // Printed, block selection
						$serviceShippingMethodOptions = [ BookingOfficeOptions::PRINTED => $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.SHIPPING_CONFIRMED_LABEL') ];
						$defaultShippingOption = BookingOfficeOptions::PRINTED;
					} else if ($oneServicePrinted) { // block selection: either recharge or set shipping method
						/*$serviceShippingMethodOptions = array_filter($serviceShippingMethodOptions, function($option, $index) {
							return ($index == BookingOfficeOptions::HOME_DELIVERY || $index == BookingOfficeOptions::RECHARGE_FORFAIT);
						}, ARRAY_FILTER_USE_BOTH);*/
						
						$serviceShippingMethodOptions = array_filter($serviceShippingMethodOptions, function($methodName, $methodID) use ($orderShippingMethod) {
							return ($methodID == $orderShippingMethod);
						}, ARRAY_FILTER_USE_BOTH);
						
						if ($willRecharge) { // set default to recharge
							$serviceShippingMethodOptions[BookingOfficeOptions::RECHARGE_FORFAIT] = $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.RECHARGE_LABEL');
							$defaultShippingOption = BookingOfficeOptions::RECHARGE_FORFAIT;
						} else { // set default to corresponding shipping method
							$defaultShippingOption = $orderShippingMethod;
						}
					} else if ($willRecharge) {
						$defaultShippingOption = BookingOfficeOptions::RECHARGE_FORFAIT;
					} else if ($rechargeable) { 
						$defaultShippingOption = BookingOfficeOptions::RECHARGE_FORFAIT;
					}
					
					if (!$isOwnerService) {
						$form['order_integrants_shipping_info'][$service->Identifier]['shipping_method'] = [
							'#type' => 'select',
							'#options' => $serviceShippingMethodOptions,
							'#required' => TRUE,
						    '#default_value' => $defaultShippingOption,
						];
						
						if (!isset($imageBase64)) {
		    				$form['order_integrants_shipping_info'][$service->Identifier]['no_image'] = [
		    					'#markup' => '<div class="panel warning"><div class="panel--inner"><div class="panel-heading"><p>' 
		    					. $translationService->translate('ORDER_DETAIL.NO_PROFILE_IMAGE_WARNING') . '</p></div></div></div>'
		    				];
		    			}
					} else {
						$form['order_owner_shipping_info']['shipping_method']['#options'] = $serviceShippingMethodOptions;
						$form['order_owner_shipping_info']['shipping_method']['#default_value'] = $defaultShippingOption;
					}
					
					if ($recharged) {

						if (!$isOwnerService) {
							$form['order_integrants_shipping_info'][$service->Identifier]['shipping_method']['#disabled'] = TRUE;
							$form['order_integrants_shipping_info'][$service->Identifier]['shipping_method']['#attributes'] = [
								'class' => ['confirmed', 'recharged']
							];
						} else {
							$form['order_owner_shipping_info']['shipping_method']['#disabled'] = TRUE;
							$form['order_owner_shipping_info']['shipping_method']['#attributes'] = [
								'class' => ['confirmed', 'recharged']
							];
						}

					} else if ($printed) {
						if ($isOwnerService) {
							$form['order_owner_shipping_info']['shipping_method']['#disabled'] = TRUE;
							$form['order_owner_shipping_info']['shipping_method']['#attributes'] = [
								'class' => ['confirmed', 'printed']
							];
						} else {
							$form['order_integrants_shipping_info'][$service->Identifier]['shipping_method']['#disabled'] = TRUE;
							$form['order_integrants_shipping_info'][$service->Identifier]['shipping_method']['#attributes'] = [
								'class' => ['confirmed', 'printed']
							];
						}
					} else if ($oneServicePrinted) {
						if ($isOwnerService) {
							$form['order_owner_shipping_info']['shipping_method']['#attributes'] = [
								'class' => ['one-service-printed']
							];	
						} else {
							$form['order_integrants_shipping_info'][$service->Identifier]['shipping_method']['#attributes'] = [
								'class' => ['one-service-printed']
							];	
						}
					} else if ($willRecharge) {
						// Do nothing
					}
				}
			}	
		}
		
		//shipping_method_select_form
		$form['#attached']['library'][] = 'core/drupal.dialog.ajax';
		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/shipping_method_select_form';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/change_product_ajax_commands';
		
		$form['actions']['#type'] = 'actions';
		
		$form['actions']['complete_later'] = [
			'#type' => 'markup',
			'#markup' => '<a href="' 
				. Url::fromRoute('gv_fanatics_plus_order.order_detail', ['orderID' => $currentOrderID])->toString() . '">' 
				. $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.RECHARGE_LATER_LABEL') 
				. '</a>' 
		];
		
		$form['actions']['submit'] = [
			'#type' => 'submit', 
			'#value' => $translationService->translate('POST_PAYMENT.NEXT_BTN_LABEL'), 
			'#button_type' => 'primary', 
			'#weight' => 10 
		];

		$form['#cache']['contexts'][] = 'session';
		
		return $form;
	}
	
	/**
	 * {@inheritdoc}
	 */
	 public function validateForm(array &$form, FormStateInterface $form_state) {
	 	$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
				
		$formValues = $form_state->getValues();
		$ownerShippingMethod = NULL;
		if (isset($formValues['order_owner_shipping_info'])) {
			$ownerShippingMethod = $formValues['order_owner_shipping_info']['shipping_method'];
			if (!isset($ownerShippingMethod)) {
				$form_state->setErrorByName('[order_owner_shipping_info][shipping_method]', $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.INVALID_SELECTION'));
			}
			
			$shippingInfo = $formValues['order_owner_shipping_info'];
			if (isset($shippingInfo['image_base64']) && strlen($shippingInfo['image_base64']) <= 0 && ($ownerShippingMethod == BookingOfficeOptions::HOME_DELIVERY || $ownerShippingMethod == BookingOfficeOptions::RECHARGE_FORFAIT)) {
				$form_state->setError($form['order_owner_shipping_info']['shipping_method'], $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.ERROR_MUST_SET_IMAGE'));
			}
		}
		
		if (isset($formValues['order_integrants_shipping_info'])) {
			$hasBoxOfficePickup = FALSE;
			$hasHomeDeliveryPickup = FALSE;
			foreach ($formValues['order_integrants_shipping_info'] as $serviceID => $shippingInfo) {
				$shippingMethodID = $shippingInfo['shipping_method'];
				if (!isset($shippingMethodID)) {
					$form_state->setErrorByName('order_integrants_shipping_info[' . $serviceID . '][shipping_method]', $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.INVALID_SELECTION'));
				}
				
				if ($shippingMethodID == BookingOfficeOptions::HOME_DELIVERY) {
					$hasHomeDeliveryPickup = TRUE;
					if (isset($shippingInfo['image_base64']) && strlen($shippingInfo['image_base64']) <= 0) {
						$form_state->setErrorByName('order_integrants_shipping_info][' . $serviceID . '][shipping_method', $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.ERROR_MUST_SET_IMAGE'));
					}
				} else if ($shippingMethodID == BookingOfficeOptions::BOX_OFFICE_PICKUP) {
					$hasBoxOfficePickup = TRUE;
				} else if ($shippingMethodID == BookingOfficeOptions::RECHARGE_FORFAIT) {
					if (isset($shippingInfo['image_base64']) && strlen($shippingInfo['image_base64']) <= 0) {
						$form_state->setErrorByName('order_integrants_shipping_info][' . $serviceID . '][shipping_method', $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.ERROR_MUST_SET_IMAGE'));
					}
				}
			}
			
			if ($hasBoxOfficePickup && $hasHomeDeliveryPickup) {
				$form_state->setErrorByName('', $translationService->translate('POST_PAYMENT.SHIPPING_METHOD.INVALID_SELECTION'));
			}
		}
	 }
	
  	/**
   	* {@inheritdoc}
   	*/
  	public function submitForm(array &$form, FormStateInterface $form_state) {
  		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
  		$formValues = $form_state->getValues();
		$session = \Drupal::service('gv_fplus.session');
		$recharge = \Drupal::service('gv_fanatics_plus_checkout.recharge');
		$order = \Drupal::service('gv_fanatics_plus_order.order');
		$bookingOffice = \Drupal::service('gv_fanatics_plus_checkout.booking_office');
		
		$orderID = $this->storeGet('order_id');
		$ownerServiceID = $this->storeGet('owner_service_id');
		$this->deleteStoreKeys(['order_id']);
				
		$hasBookingOfficePickup = FALSE;
		$hasHomeDeliveryPickup = FALSE;
		$ownerShippingMethod = NULL;
		if (isset($formValues['order_owner_shipping_info'])) {
			$ownerShippingMethod = $formValues['order_owner_shipping_info']['shipping_method'];
			$toRecharge = FALSE;
			if ($ownerShippingMethod == BookingOfficeOptions::HOME_DELIVERY) {
				$hasHomeDeliveryPickup = TRUE;
			} else if ($ownerShippingMethod == BookingOfficeOptions::BOX_OFFICE_PICKUP) {
				$hasBookingOfficePickup = TRUE;
			} else if ($ownerShippingMethod == BookingOfficeOptions::RECHARGE_FORFAIT) {
				$toRecharge = TRUE;
			}
			
			if (isset($ownerServiceID)) {
				try {
					$rechargeResult = $recharge->bookingSetRechargeRequest($session->getIdentifier(), $orderID, $ownerServiceID, $toRecharge);
				} catch (\Exception $e) {
					// Do nothing
				}
			}
		}

		if (isset($formValues['order_integrants_shipping_info'])) {
			foreach ($formValues['order_integrants_shipping_info'] as $serviceID => $shippingInfo) {
				$shippingMethodID = $shippingInfo['shipping_method'];
				$toRecharge = FALSE;
				if ($shippingMethodID == BookingOfficeOptions::HOME_DELIVERY) {
					$hasHomeDeliveryPickup = TRUE;
				} else if ($shippingMethodID == BookingOfficeOptions::BOX_OFFICE_PICKUP) {
					$hasBookingOfficePickup = TRUE;
				} else if ($shippingMethodID == BookingOfficeOptions::RECHARGE_FORFAIT) {
					$toRecharge = TRUE;
				}
				
				try {
					$recharge->bookingSetRechargeRequest($session->getIdentifier(), $orderID, $serviceID, $toRecharge);
				} catch (\Exception $e) {
					// do nothing
				}
			}
		}

		if ($hasBookingOfficePickup) {
			$boxOfficeOption = $bookingOffice->getDefaultBoxOfficeOptionID();
			$result = $order::editBookingOffice($orderID, $boxOfficeOption);
		} else if ($hasHomeDeliveryPickup) {
			$homeDeliveryOption = $bookingOffice->getDefaultHomeDeliveryOptionID();
			$result = $order::editBookingOffice($orderID, $homeDeliveryOption);
		}

		$orderInfo = $order::getFromID($orderID, FALSE, TRUE);
		$this->postPaymentOrderManager->increaseStepNumber();
		
		if ($orderInfo->hasPendingDocuments()) {
			$form_state->setRedirect('gv_fanatics_plus_checkout.post_payment_documents', ['orderID' => $orderID]);
		} else {
			$form_state->setRedirect('gv_fanatics_plus_checkout.post_payment_shipping_data', ['orderID' => $orderID]);
		}
  	}
}

?>
