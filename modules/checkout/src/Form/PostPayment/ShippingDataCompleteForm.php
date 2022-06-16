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

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario de la ruta final del proceso de Post-pago (proceso completado).
 */
class ShippingDataCompleteForm extends MultistepFormBase {

  	/**
   	* {@inheritdoc}.
   	*/
  	public function getFormId() {
    	return 'gv_fplus_checkout_shipping_data_complete_form';
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
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');

		$this -> formTitle = '';
		$form = parent::buildForm($form, $form_state, $currentOrderID, $currentStepNumber, $totalSteps, $destinationUrl);
		
		$form['top_description_container'] = [
			'#markup' => $this->_buildTitleMarkup(),
			'#weight' => -1
		];
				
		$currentOrderID = \Drupal::routeMatch()->getParameter('orderID');
		
		$defaultImgURL = 'https://via.placeholder.com/134x164';
		if (!isset($currentOrderID)) {
			$currentOrderID = 66707166;
		}
		
		$orderInfo = $this->order->getFromID($currentOrderID, TRUE)->Booking;
		$orderOwnerClientID = $orderInfo->IDClient;
		$orderOwnerUserID = $orderInfo->IDUser;
		
		$form['main_image'] = [
			'#markup' => '<img class="shipping-complete" src="/misc/shipping_complete.svg" />'
		];
		
		$form['title'] = [
			'#markup' => '<h2>' . $translationService->translate('POST_PAYMENT.SHIPPING_COMPLETE.BODY_TITLE') . '</h2>'
		];
		
		$form['description'] = [
			'#markup' => '<div class="description">' 
				. $translationService->translate('POST_PAYMENT.SHIPPING_COMPLETE.BODY_DESCRIPTION') 
				. '</div>'
		];
		
		$form['actions_complete'] = [
		    '#prefix' => '<div class="action-complete">',
		    '#suffix' => '</div>'
		];
		
		$form['actions_complete']['go_home'] = [
			'#type' => 'markup',
			'#markup' => '<a class="home" href="' 
				. Url::fromRoute('gv_fanatics_plus_order.order_detail', ['orderID' => $currentOrderID])->toString() . '">' 
				. $translationService->translate('POST_PAYMENT.SHIPPING_COMPLETE.BACK_TO_ORDER_BTN_LABEL') . '</a>' 
		];
		
		$form['actions_complete']['go_mygrandski'] = [
			'#type' => 'markup',
			'#markup' => '<a class="gski" href="' 
				. Url::fromRoute('gv_fanatics_plus_my_grandski.main_menu')->toString() . '">' 
				. $translationService->translate('POST_PAYMENT.SHIPPING_COMPLETE.GO_TO_MYGRANDSKI_BTN_LABEL') . '</a>' 
		];
		
		unset($form['actions']['submit']);
		unset($form['title_container']);
		
		$form['#attached']['library'][] = 'core/drupal.dialog.ajax';
		$form['#attached']['library'][] = 'system/ui.dialog';
		
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
  	public function submitForm(array &$form, FormStateInterface $form_state) {}	
}

?>
