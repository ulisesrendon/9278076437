<?php

namespace Drupal\gv_fanatics_plus_checkout\Form;

use Drupal\gv_fanatics_plus_checkout\Ajax\SetProductAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\RefreshCartAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\AddOffCanvasDialogBackgroundCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerAddProductAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerRemoveProductAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerAddInsuranceAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerRemoveInsuranceAjaxCommand;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CloseDialogCommand;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario de modal confirmación de modificación de producto para usuario / integrante
 */
class ChangeProductModalForm extends FormBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
    protected $formBuilder;

	/**   * {@inheritdoc}   */
	public function getFormId() {
		return 'change_product_modal_form';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$cart = \Drupal::service('gv_fanatics_plus_cart.cart');
		$user = \Drupal::service('gv_fplus_auth.user');
		$formBuilder = \Drupal::service('form_builder');
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');

		$this->session = $session;
		$this->apiClient = $apiClient;
		$this->cart = $cart;
		$this->user = $user;
		$this->formBuilder = $formBuilder;
		$this->translationService = $translationService;
  	}

	/**   * {@inheritdoc}   */
	public function buildForm(array $form, FormStateInterface $form_state, $searchID = NULL, $productBookingCode = NULL) {
		$request = $this->getRequest();
		$request->getSession()->set('change_product_modal_booking_code', $productBookingCode);
		$request->getSession()->set('change_product_modal_search_id', $searchID);
		
		$form['#prefix'] = '<div id="modal_example_form">';
		$form['#suffix'] = '</div>';
		
		// The status messages that will contain any form errors.
		$form['status_messages'] = ['#type' => 'status_messages', '#weight' => -10, ];

		$form['actions'] = ['#type' => 'actions'];
		$form['actions']['send'] = ['#type' => 'submit', '#value' => $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.CHANGE_PRODUCT'), '#attributes' => ['class' => ['use-ajax', ], ], '#ajax' => ['callback' => [$this, 'submitModalFormAjax'], 'event' => 'click', ], ];
		$form['actions']['cancel'] = ['#type' => 'button', '#value' => $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.CANCEL_CHANGE_PRODUCT'), '#attributes' => ['class' => ['use-ajax', ], ], '#ajax' => ['callback' => [$this, 'cancelModalFormAjax'], 'event' => 'click', ], ];

		$form['#attached']['library'][] = 'system/ui.dialog';

		return $form;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
		$response = new AjaxResponse();
	
		// If there are any form errors, re-display the form.
		if ($form_state -> hasAnyErrors()) {
			$response -> addCommand(new ReplaceCommand('#modal_example_form', $form));
			return $response;
		}
		
		$productBookingCode = $this->getRequest()->getSession()->get('change_product_modal_booking_code');
		$searchID = $this->getRequest()->getSession()->get('change_product_modal_search_id');
		
		$this->getRequest()->getSession()->remove('change_product_modal_booking_code');
		$this->getRequest()->getSession()->remove('change_product_modal_search_id');
		
		
		$cartContents = $this->cart->getCurrentDetail()->Booking;
		$isIntegrantActive = $this->session->isManagingIntegrant();
		$ownerClientID = $cartContents->IDClient;
		$hadInsurance = FALSE;
		$insuranceToRemove = new \stdClass();
		foreach ($cartContents->Services as $index => $service) {
			if ($isIntegrantActive && $service->SeasonPassData->IDClient != $this->session->getActiveIntegrantClientID()) {
				continue;
			}
			
			if (!$isIntegrantActive && $service->SeasonPassData->IDClient != $ownerClientID) {
				continue;
			}
			
			$bookingServiceID = $service->Identifier;
			if (isset($service->SeasonPassData->IDInsurance)) {
				$hadInsurance = TRUE;
				$insuranceToRemove->IDInsurance = $service->SeasonPassData->IDInsurance;
				$insuranceToRemove->Amount = $service->SeasonPassData->InsuranceAmount;
			}
			$this->cart->removeBookingService($bookingServiceID);
			$response->addCommand(new TagManagerRemoveProductAjaxCommand(NULL, $service));
		}
		
		$openInsuranceModal = FALSE;
		$result = $this->cart->addBookingService($searchID, $productBookingCode);
		
		
		$updatedCartContents = $this->cart->getCurrentDetail()->Booking;
		$bookingServiceToTrack = NULL;
		foreach ($updatedCartContents->Services as $index => $service) {
			if ($isIntegrantActive && $service->SeasonPassData->IDClient != $this->session->getActiveIntegrantClientID()) {
				continue;
			}
			
			if (!$isIntegrantActive && $service->SeasonPassData->IDClient != $ownerClientID) {
				continue;
			}
			
			$bookingServiceToTrack = $service;
		}
		
		$response->addCommand(new TagManagerAddProductAjaxCommand(NULL, $bookingServiceToTrack));
		
		if ($hadInsurance && isset($result->Identifier)) {
			$insurances = $this->cart->getSeasonPassInsurances($result->Identifier);
			if (count($insurances) > 0) {
				$insurance = reset($insurances);
				$this->cart->addSeasonPassInsurance($result->Identifier, $insurance->IDInsurance);
				//$response->addCommand(new TagManagerAddInsuranceAjaxCommand(NULL, $insurance));
			} else {
				$response->addCommand(new TagManagerRemoveInsuranceAjaxCommand(NULL, $insuranceToRemove));
			}
		} else {
			$insurances = $this->cart->getSeasonPassInsurances($result->Identifier);
			if (count($insurances) > 0) {
				$openInsuranceModal = TRUE;
			}
		}

		$response -> addCommand(new SetProductAjaxCommand(NULL, $productBookingCode));
		$response -> addCommand(new RefreshCartAjaxCommand(NULL));
		$response -> addCommand(new CloseDialogCommand());
		
		if ($openInsuranceModal) {
			// Get the modal form using the form builder.
    		$addInsuranceModalForm = $this->formBuilder->getForm('Drupal\gv_fanatics_plus_checkout\Form\AddInsuranceModalForm', $result->Identifier, NULL);

    		// Add an AJAX command to open a modal dialog with the form as the content.
    		$response->addCommand(new OpenOffCanvasDialogCommand($this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.COMPLEMENT_PRODUCT_HEADER'), $addInsuranceModalForm, ['width' => '800']));
			$response->addCommand(new AddOffCanvasDialogBackgroundCommand(NULL));
		}
		
		/**
		 * •	Al cambiar producto tener en cuenta si el producto del carrito tiene o no seguro para los RemoveToCart y AddToCart
•	Al cambiar un producto que tiene seguro y se modifica por uno sin seguro, enviar el RemoveToCart de ambos y el AddToCart del producto VIANANT.
		 * 
		 */
		
		return $response;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function cancelModalFormAjax(array $form, FormStateInterface $form_state) {
		$this->getRequest()->getSession()->remove('change_product_modal_booking_code');
		$this->getRequest()->getSession()->remove('change_product_modal_search_id');
		
		$response = new AjaxResponse();
		
		$response -> addCommand(new CloseDialogCommand());

		return $response;
	}

	/**   * {@inheritdoc}   */
	public function validateForm(array &$form, FormStateInterface $form_state) {
	}

	/**   * {@inheritdoc}   */
	public function submitForm(array &$form, FormStateInterface $form_state) {
	}

}
