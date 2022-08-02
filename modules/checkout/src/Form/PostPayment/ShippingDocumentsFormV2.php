<?php

namespace Drupal\gv_fanatics_plus_checkout\Form\PostPayment;

use Drupal\gv_fanatics_plus_checkout\Form\PostPayment\MultistepFormBase;
use Drupal\gv_fanatics_plus_checkout\BookingOfficeOptions;

use Drupal\Core\Routing\TrustedRedirectResponse;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

use Drupal\gv_fanatics_plus_order\Order;
use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario correspondiente al paso de gestión documental del proceso de Post-pago (versión 2).
 */
class ShippingDocumentsFormV2 extends MultistepFormBase {

  	/**
   	* {@inheritdoc}.
   	*/
  	public function getFormId() {
    	//return 'gv_fplus_checkout_shipping_documents_form_v2';
    	return 'gv_fplus_checkout_shipping_documents_form';
  	}

	protected function _buildTitleMarkup() {
		$markup = '<div class="top-description-container">';
		$markup .= '</div>';
		
		return $markup;
	}

	private function _getDefaultImageAvatar() {
		return '<svg width="88" height="88" viewBox="0 0 88 88" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0.5" y="0.5" width="39" height="39" rx="19.5" fill="#C3C9D6"/><g filter="url(#filter0_d)"><circle cx="36" cy="31" r="11.5" fill="white" stroke="#F0F2F5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M35.2857 30.2857V26H36.7143V30.2857H41V31.7143H36.7143V36H35.2857V31.7143H31V30.2857H35.2857Z" fill="#99A3B1"/></g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.3809 30C12.3809 25.7921 15.792 22.381 19.9999 22.381C24.2078 22.381 27.619 25.7921 27.619 30H25.7142C25.7142 26.8441 23.1558 24.2857 19.9999 24.2857C16.844 24.2857 14.2856 26.8441 14.2856 30H12.3809ZM19.9999 21.4286C16.8428 21.4286 14.2856 18.8714 14.2856 15.7143C14.2856 12.5571 16.8428 10 19.9999 10C23.1571 10 25.7142 12.5571 25.7142 15.7143C25.7142 18.8714 23.1571 21.4286 19.9999 21.4286ZM19.9999 19.5238C22.1047 19.5238 23.8094 17.819 23.8094 15.7143C23.8094 13.6095 22.1047 11.9048 19.9999 11.9048C17.8951 11.9048 16.1904 13.6095 16.1904 15.7143C16.1904 17.819 17.8951 19.5238 19.9999 19.5238Z" fill="white"/><rect x="0.5" y="0.5" width="39" height="39" rx="19.5" stroke="#F0F2F5"/><defs><filter id="filter0_d" x="16" y="15" width="40" height="40" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feOffset dy="4"/><feGaussianBlur stdDeviation="4"/><feColorMatrix type="matrix" values="0 0 0 0 0.0541176 0 0 0 0 0.145098 0 0 0 0 0.173333 0 0 0 0.12 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow" result="shape"/></filter></defs></svg>';
	}

	private function _getDescriptionFromDocumentType($documentTypeID) {
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$description = '';
		switch($documentTypeID) {
			case 12: {
				//$description = $this->t('We would like to remind you that your pass is for personal use and is non-transferable. For this reason, we need you to add the photo from your Andorran passport or residence card, as well as the certificate from the Comú that you live in. Remember that it should be sent within a maximum of 3 months, and that it should match the person on the passport/residence card. The Season Ski Pass team will review your document. If the information is incorrect, we reserve the right to block your pass.', [],  ['context' => TranslationContext::POST_PAYMENT]);
				$description = $translationService->translate('POST_PAYMENT.DOCUMENTS.DESCRIPTION_12');
				break;
			}
			case 13: {
				//$description = $this->t('We would like to remind you that your pass is for personal use and is non-transferable. For this reason, we need you to add the photo from your passport or ID card.', [],  ['context' => TranslationContext::POST_PAYMENT]);
				$description = $translationService->translate('POST_PAYMENT.DOCUMENTS.DESCRIPTION_13');
				break;
			}
			case 14: {
				//$description = $this->t('In order to benefit from the special price, you need to attach the document that proves it. Remember that it should be sent within a maximum of 3 months, and that it should match the person on the passport/residence card. The Season Ski Pass team will review your document in order to verify it with the Comú de Canillo. If the information is incorrect, we reserve the right to block your pass.', [],  ['context' => TranslationContext::POST_PAYMENT]);
				$description = $translationService->translate('POST_PAYMENT.DOCUMENTS.DESCRIPTION_14');
				break;
			}
			case 17: {
				//$description = $this->t('In order to benefit from the special price, you need to attach the document that proves it. Remember that it should be sent within a maximum of 3 months, and that it should match the person on the passport/residence card. The Season Ski Pass team will review your document in order to verify it with the corresponding Comú. If the information is incorrect, we reserve the right to block your pass.', [],  ['context' => TranslationContext::POST_PAYMENT]);
				$description = $translationService->translate('POST_PAYMENT.DOCUMENTS.DESCRIPTION_17');
				break;
			}
			case 18: {
				//$description = $this->t('In order to benefit from the special price, you need to attach the census certificate that proves it. Remember that it should be sent within a maximum of 3 months, and that it should match the person on the passport/ID card. The Season Ski Pass team will review your document in order to verify it with the corresponding public department in your region. If the information is incorrect, we reserve the right to block your pass.', [],  ['context' => TranslationContext::POST_PAYMENT]);
				$description = $translationService->translate('POST_PAYMENT.DOCUMENTS.DESCRIPTION_18');
				break;
			}
		}
		
		return $description;
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state, $currentOrderID = NULL, $currentStepNumber = 1, $totalSteps = 1, $destinationUrl = NULL) {
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$this -> formTitle = 'POST_PAYMENT.DOCUMENTS.MAIN_TITLE';
		$form = parent::buildForm($form, $form_state, $currentOrderID, $currentStepNumber, $totalSteps, $destinationUrl);

		$form2 = $form;

		if ($destinationUrl) {
			$this->destinationUrl = $destinationUrl;
		}
		
		$form['#attributes']['enctype'] = 'multipart/form-data';
		
		$form['top_description_container'] = [
			'#markup' => $this->_buildTitleMarkup(),
			'#weight' => -1
		];

		//$bookingStatuses = $this->apiClient->core()->getBookingStatuses();
		
		$defaultImgURL = 'https://via.placeholder.com/134x164';
		//		$currentOrderID = \Drupal::routeMatch()->getParameter('orderID');

		//		$orderInfo = $this->order->getFromID($currentOrderID, TRUE, TRUE);
		$orderInfo = $this->order->getOrder();
		$orderOwnerClientID = $orderInfo->IDClient;
		$orderOwnerUserID = $orderInfo->IDUser;
		
		/*$shippingMethodOptions = [
			BookingOfficeOptions::RECHARGE_FORFAIT => $this->t('Top-ups', [], ['context' => TranslationContext::POST_PAYMENT]),
			BookingOfficeOptions::HOME_DELIVERY => $this->t('Delivery to my address', [], ['context' => TranslationContext::POST_PAYMENT]),
			BookingOfficeOptions::BOX_OFFICE_PICKUP => $this->t('Pick up point', [], ['context' => TranslationContext::POST_PAYMENT])
		];*/
		
//		$this->storeSet('order_id', $currentOrderID);
		$this->storeSet('order_owner_client_id', $orderOwnerClientID);

		$form['step_description'] = [
			'#type' => 'inline_template',
			'#template' => '<div class="documents-info-warning alert alert-warning">
<span class="documents-info-icon">
<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.99992 13.6654C3.31792 13.6654 0.333252 10.6807 0.333252 6.9987C0.333252 3.3167 3.31792 0.332031 6.99992 0.332031C10.6819 0.332031 13.6666 3.3167 13.6666 6.9987C13.6666 10.6807 10.6819 13.6654 6.99992 13.6654ZM6.33325 6.33203V10.332H7.66659V6.33203H6.33325ZM6.33325 3.66536V4.9987H7.66659V3.66536H6.33325Z" fill="#D8A13D"/></svg>
</span>'.$translationService->translate('DOCUMENTS.STEP_WARNING_ALERT').'</div>',
//		'#template' => '<div class="documents-info-warning alert alert-warning">
//<span class="documents-info-icon">
//<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.99992 13.6654C3.31792 13.6654 0.333252 10.6807 0.333252 6.9987C0.333252 3.3167 3.31792 0.332031 6.99992 0.332031C10.6819 0.332031 13.6666 3.3167 13.6666 6.9987C13.6666 10.6807 10.6819 13.6654 6.99992 13.6654ZM6.33325 6.33203V10.332H7.66659V6.33203H6.33325ZM6.33325 3.66536V4.9987H7.66659V3.66536H6.33325Z" fill="#D8A13D"/></svg>
//</span>Para los Forfait de Temporada en modalidad de Residente es necesario acreditar la residencia en Andorra con el Certificado de Residencia que se emite en el Comú correspondiente</div>'
		];
		
		$form['shipping_documents'] = [
			'#type' => 'fieldset',
			'#prefix' => '<div class="shipping-documents-container"><div id="shipping-documents">',
			'#suffix' => '</div>',
			'#tree' => TRUE
		];

		$form['shipping_documents']['left_aside'] = [
			'#type' => 'container',
			'#attributes' => [
				'id' => "left_aside",
			]
		];
		
		// if (!$orderInfo->hasPendingDocuments()) {
		// 	$form['shipping_documents']['no_results_behaviour'] = [
		// 		'#markup' => '<div class="no-results-behaviour"><h6>'
		// 			. $translationService->translate('POST_PAYMENT.DOCUMENTS.NO_PENDING_DOCUMENTS')
		// 			. '</h6></div>'
		// 	];
		// }

		//$_SESSION['shippingDocumentsData'] = \Drupal\gv_fanatics_plus_checkout\CheckoutOrderManager::encrypt( $orderInfo->Booking->BookingLocator );
		
		$documentation = \Drupal::service('gv_fanatics_plus_order.documentation');
		$allDocuments = [];
		$totalDocumentsCount = 0;
		foreach($orderInfo->Booking->Services as $serviceIndex => $service) {
			$serviceDocuments = 0;

			$allDocuments = array_merge($service->SeasonPassData->Documents, $service->SeasonPassData->ClientDocuments);

			if (count($allDocuments) == 0) {
				continue;
			}
			
			$form['shipping_documents'][$service->Identifier] = [
				'#type' => 'fieldset',
				//'#tree' => TRUE
			];

			$userProfile = $this->apiClient->users()->getUserProfile(NULL, TRUE, NULL, NULL, $service->SeasonPassData->IDClient);
			$service->IntegrantData->Name = $userProfile->Name;

			$imageBase64 = $userProfile->Image;

			$isActive = '';
			if ($serviceIndex == 0) {
				$isActive = 'active';
			}

			$form['shipping_documents']['left_aside'][$serviceIndex] = [
				"#type" => 'inline_template',
				"#template" => '<div class="left_aside_editdocitem '.$isActive.'" data-index="'.$serviceIndex.'">
					<div class="left_aside_editdocitem_img"><img src="' . 'data:image/jpeg;base64,' . $imageBase64 .'" data-index="'.$serviceIndex.'" /></div>
					<div class="left_aside_editdocitem_name">'.$service->IntegrantData->Name.'</div>
				</div>',
			];
			
			if (isset($imageBase64)) {
				$form['shipping_documents'][$service->Identifier]['header'] = [
					'#markup' => '<div class="shipping-option-data-integrants"><div class="integrant"><div class="img"><img src="" data-src="' . 'data:image/jpeg;base64,' . $imageBase64 .'" data-index="'.$serviceIndex.'" /></div><span class="name">' . $service->IntegrantData->Name . ' ' . $service->IntegrantData->Surname . '</span></div></div>'
				];
			} else {
				//Order::getDefaultUserAvatar()
				$form['shipping_documents'][$service->Identifier]['header'] = [
					'#markup' => '<div class="shipping-option-data-integrants"><div class="integrant"><div class="img"><img src="' . Order::getDefaultUserAvatar() .'" data-index="'.$serviceIndex.'" /></div><span class="name">' . $service->IntegrantData->Name . ' ' . $service->IntegrantData->Surname . '</span></div></div>'
				];
			}
			
			$form['shipping_documents'][$service->Identifier]['documents'] = [
				'#type' => 'fieldset'
			];


			
			foreach ($allDocuments as $documentIndex => $document) {

				if ($document->Estado != 0) {
					continue;
				}
				$totalDocumentsCount++;
				$serviceDocuments++;

				$description = $this->_getDescriptionFromDocumentType($document->IDTipo);
				//$documentationResult = $documentation->getURLUpload($document->Identifier);
				$form['shipping_documents'][$service->Identifier]['documents'][$document->Identifier] = [
					'#type' => 'fieldset',
					'#tree' => TRUE,
					'#attributes' => [
						'class' => ['documents-wrapper']
					]
				];
				
				$baseDescription = '';
				if (isset($document->DescripcionPublica) && strlen($document->DescripcionPublica) > 0) {
					$baseDescription = $document->DescripcionPublica . '</br>';
				} else {
					$baseDescription .= '<p class="document-description">' . $translationService->translate('DOCUMENTS.ADD_NEW_DOCUMENT') . '</p>';
//					$baseDescription .= '<p class="document-description">Arrastra y suelta en este espacio, o clica para añadir un fichero</p>';
				}

//				if (isset($description) && strlen($description) > 0) {
//				}
				
				$form['shipping_documents'][$service->Identifier]['documents'][$document->Identifier]['file_upload'] = [
      				'#type' => 'managed_file',
        			'#title' => $document->Titulo,
        			'#description' => $baseDescription 
        				. $translationService->translate('POST_PAYMENT.DOCUMENTS.ALLOWED_FILE_TYPES') . ' pdf jpg jpeg png ' . \Drupal::service('gv_fplus.session')->getIdentifier() . '</br>'
        				. $translationService->translate('POST_PAYMENT.DOCUMENTS.MAX_FILE_SIZE') . ' 4MB',
        			'#upload_location' => 'temporary://gv_fanatics_plus_document_management/',
        			'#upload_validators' => [
        				'file_validate_extensions' => ['pdf jpg jpeg png'],
        				'file_validate_size' => [4194304],
        			],
        			'#required' => TRUE,
        			'#prefix' => '<div class="area">',
        			'#suffix' => '</div>'
				];
			}
		}

		// Pasar al siguiente paso cuando ya no hay documentos por subir
		if( $totalDocumentsCount == 0 ){
			return new TrustedRedirectResponse($this->destinationUrl, 307);
		}
		
		$form['#attached']['library'][] = 'core/drupal.dialog.ajax';
		$form['#attached']['library'][] = 'system/ui.dialog';
		
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/dropzonejs';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/shipping_documents_form_v2';

		$form['actions'] = [
//			'#type' => 'actions',
			'#prefix' => '<div class="checkout-form-main-actions">',
			'#suffix' => '</div>'
		];

		$go_back_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PRODUCT_SELECTION]);

		$form['actions']['btn_actions'] = [
			'#prefix' => '<div class="checkout-form-main-principal-actions">',
			'#suffix' => '</div>'
		];
		$form['actions']['btn_actions']['go_back'] = [
			'#type' => 'markup',
			'#markup' => '<a href="' . $go_back_url->toString() . '">'
				. $this->t('CHECKOUT_PAYMENT.GO_BACK_BTN_LABEL')
				. '</a>'
		];

		$form['actions']['btn_actions']['submit'] = [
			'#type' => 'submit',
			'#value' => $translationService->translate('POST_PAYMENT.SUBMIT_BTN_LABEL'),
			'#button_type' => 'primary',
			'#weight' => 10,
		];
		
//		$form['actions']['submit'] = [
//			'#type' => 'submit',
//			'#value' => $translationService->translate('POST_PAYMENT.SUBMIT_BTN_LABEL'),
//			'#button_type' => 'primary',
//			'#weight' => 10,
//		];
		
		$form['#cache']['contexts'][] = 'session';
		
		return $form;
	}
	
	/**
	 * {@inheritdoc}
	 */
	 public function validateForm(array &$form, FormStateInterface $form_state) {}
	
  	/**
   	* {@inheritdoc}
   	*/
  	public function submitForm(array &$form, FormStateInterface $form_state) {
  		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
  		// $orderID = $this->storeGet('order_id');

		// ksm($orderID);
		// $this->deleteStoreKeys(['order_id']);
		
		$fileManager = \Drupal::entityTypeManager()->getStorage('file');
		$fileSystem = \Drupal::service('file_system');
		
		$formValues = $form_state->getValues();
		$documentsToUpload = [];
		foreach ($formValues['shipping_documents'] as $documentID => $document) {
			$files = $document['documents'];
			foreach ($files as $fileID => $fileInfo) {
				$fid = $fileInfo['file_upload'];
				$loadedFile = $fileManager->load($fid[0]);
				if ($loadedFile) {
					$loadedFilename = $loadedFile->get('filename')->value;
					$loadedFileUri = $loadedFile->get('uri')->value;
					$absolutePath = $fileSystem->realpath($loadedFileUri);
					$fileContents = file_get_contents($absolutePath);
					$encodedFileContents = base64_encode($fileContents);
					
					$documentsToUpload[] = [
						"Name" => $loadedFilename,
      					//"Extension" => "string",
      					"IDDocument" => $fileID,
      					"Document" => $encodedFileContents
					];
					
					$fileToDelete = File::load($fid[0]);
					if (isset($fileToDelete)) {
						$ret = $fileToDelete->delete();
					}
					
					//file_delete($fid);
					//$fileSystem->delete($loadedFileUri);
				}
			}
		}

		
		$documentation = \Drupal::service('gv_fanatics_plus_order.documentation');
		if (count($documentsToUpload) > 0) {
			$result = $documentation->uploadDocuments($documentsToUpload);
			$documentResultList = $result->DocumentResultList;
			$numDocuments = count($documentResultList);
			$numUploadedDocuments = 0;
			foreach ($documentResultList as $uploadResult) {
				if ($uploadResult->Uploaded) {
					++$numUploadedDocuments;
				}
			}
			
			if ($numUploadedDocuments < $numDocuments) {
				return \Drupal::messenger()->addMessage($translationService->translate('POST_PAYMENT.DOCUMENTS.SOME_DOCUMENTS_UPLOADED'));
			}
			
			\Drupal::messenger()->addMessage($translationService->translate('POST_PAYMENT.DOCUMENTS.ALL_DOCUMENTS_UPLOADED'));
		}

		if (isset($this->destinationUrl)) {
			return new TrustedRedirectResponse($this->destinationUrl, 307);
		}
		
// 		$form_state->setRedirect('gv_fanatics_plus_checkout.post_payment_shipping_data', ['orderID' => $_SESSION['shippingDocumentsData']]);
  	}	
}

?>
