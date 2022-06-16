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

use Drupal\gv_fanatics_plus_order\Order;

/**
 *  Formulario correspondiente al paso de gestión documental del proceso de Post-pago (versión 1).
 */
class ShippingDocumentsForm extends MultistepFormBase {

  	/**
   	* {@inheritdoc}.
   	*/
  	public function getFormId() {
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

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state, $currentOrderID = NULL, $currentStepNumber = 1, $totalSteps = 1, $destinationUrl = NULL) {
		$this -> formTitle = 'Añade tu pasaporte o certificado de residencia y/o empadronamiento';
		$form = parent::buildForm($form, $form_state, $currentOrderID, $currentStepNumber, $totalSteps, $destinationUrl);
		
		$form['top_description_container'] = [
			'#markup' => $this->_buildTitleMarkup(),
			'#weight' => -1
		];
		
		//$bookingStatuses = $this->apiClient->core()->getBookingStatuses();
		
		$defaultImgURL = 'https://via.placeholder.com/134x164';
		$currentOrderID = \Drupal::routeMatch()->getParameter('orderID');

		$orderInfo = $this->order->getFromID($currentOrderID, TRUE, TRUE);
		$orderOwnerClientID = $orderInfo->IDClient;
		$orderOwnerUserID = $orderInfo->IDUser;
		
		$shippingMethodOptions = [
			BookingOfficeOptions::RECHARGE_FORFAIT => $this->t('Recargas', [], ['langcode' => 'es']),
			BookingOfficeOptions::HOME_DELIVERY => $this->t('Envío a domicílio', [], ['langcode' => 'es']),
			BookingOfficeOptions::BOX_OFFICE_PICKUP => $this->t('Punto de recogida', [], ['langcode' => 'es'])
		];
		
		$this->storeSet('order_id', $currentOrderID);
		$this->storeSet('order_owner_client_id', $orderOwnerClientID);
		
		$form['shipping_documents'] = [
			'#type' => 'fieldset',
			'#prefix' => '<div class="shipping-documents-container"><div id="shipping-documents">',
			'#suffix' => '</div>'
		];
		
		$documentation = \Drupal::service('gv_fanatics_plus_order.documentation');
		foreach($orderInfo->Services as $serviceIndex => $service) {
			if (!$service->hasPendingDocuments()) {
				continue;
			}
			
			$form['shipping_documents'][$service->Identifier] = [
				'#type' => 'fieldset',
				'#tree' => TRUE
			];
			
			$imageBase64 = $service->IntegrantData->ImageBase64;
			if (isset($imageBase64)) {
				$form['shipping_documents'][$service->Identifier]['header'] = [
					'#markup' => '<div class="shipping-option-data-integrants"><div class="integrant"><div class="img"><img src="" data-src="' . 'data:image/jpeg;base64,' . $imageBase64 .'" /></div><span class="name">' . $service->IntegrantData->Name . ' ' . $service->IntegrantData->Surname . '</span></div></div>'
				];
			} else {
				//Order::getDefaultUserAvatar()
				$form['shipping_documents'][$service->Identifier]['header'] = [
					'#markup' => '<div class="shipping-option-data-integrants"><div class="integrant"><div class="img"><img src="' . Order::getDefaultUserAvatar() .'" /></div><span class="name">' . $service->IntegrantData->Name . ' ' . $service->IntegrantData->Surname . '</span></div></div>'
				];
			}
			
			$form['shipping_documents'][$service->Identifier]['documents'] = [
				'#type' => 'fieldset',
				'#tree' => TRUE
			];
			
			foreach ($service->SeasonPassData->Documents as $documentIndex => $document) {
				if (!$document->isPending()) {
					continue;
				}

				$documentationResult = $documentation->getURLUpload($document->Identifier);
				$form['shipping_documents'][$service->Identifier]['documents'][$document->Identifier] = [
					'#type' => 'fieldset',
					'#tree' => TRUE
				];
				
				$form['shipping_documents'][$service->Identifier]['documents'][$document->Identifier]['iframe'] = [
					'#type' => 'inline_template',
					'#template' => '<iframe class="document-upload-iframe" src="{{ url }}" name="iframe-doc-id-' . $document->Identifier . '"></iframe>',
					'#context' => [
      					'url' => $documentationResult->URLUpload
    				],
    				'#prefix' => '<div class="document-title"><span>' . $document->Titulo . '</span></div><div class="document-description">' . $document->DescripcionPublica . '</div>'
				];
			}
		}
		
		//shipping_method_select_form
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/shipping_documents_form';
		
		$form['actions']['#type'] = 'actions';
		$form['actions']['complete_later'] = [
			'#type' => 'markup',
			'#markup' => '<a href="' . Url::fromRoute('gv_fanatics_plus_order.order_detail', ['orderID' => $currentOrderID])->toString() . '">' . $this->t('Completar más tarde', [], ['langcode' => 'es']) . '</a>' 
		];
		
		$form['actions']['submit'] = [
			'#type' => 'submit', 
			'#value' => $this -> t('Continuar', [], ['langcode' => 'es']), 
			'#button_type' => 'primary', 
			'#weight' => 10,
			'#attributes' => ['disabled' => TRUE]
		];
		
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
  		$orderID = $this->storeGet('order_id');
		$this->deleteStoreKeys(['order_id']);
		
  		$form_state->setRedirect('gv_fanatics_plus_checkout.post_payment_shipping_data', ['orderID' => $orderID]);
  	}	
}

?>
