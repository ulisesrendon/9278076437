<?php

namespace Drupal\gv_fanatics_plus_checkout\Form;

use Drupal\gv_fanatics_plus_checkout\Ajax\RefreshCartAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\CloseOffCanvasDialogCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerAddInsuranceAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerRemoveInsuranceAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerInsuranceCheckoutOptionAjaxCommand;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario de modal para añadir seguro a servicio del carrito
 */
class AddInsuranceModalForm extends FormBase {

	/**   {@inheritdoc}   */
	public function getFormId() {
		return 'add_insurance_modal_form';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$cart = \Drupal::service('gv_fanatics_plus_cart.cart');
		$user = \Drupal::service('gv_fplus_auth.user');
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$this->session = $session;
		$this->apiClient = $apiClient;
		$this->cart = $cart;
		$this->user = $user;
		$this->translationService = $translationService;
  	}

	private function _getDescriptionMarkup() {
		
		//$markup = '<div class="insurance-description-container">';
		//$markup .= '<h2>' . $this->t('Main policy coverage', array(), array('context' => TranslationContext::CHECKOUT)) . '</h2>';
		//$markup .= '<div class="description--inner">';
		//$markup .= $this->t('<ol><li> Assistance at the slopes medical center by the insurer </li> <li> Reimbursement of medical expenses up to 3,000€ </li> <li> Repatriation or medical transportation </li></ol>', array(), array('context' => TranslationContext::CHECKOUT));
		$markup = $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.COMPLEMENT_INSURANCE_DESCRIPTION');
		//$markup .= '</div></div>';
		
		return $markup;
	}

	/** {@inheritdoc}   */
	public function buildForm(array $form, FormStateInterface $form_state, $productID = NULL, $insuranceID = NULL) {
		$request = $this->getRequest();
		
		$request->getSession()->set('change_product_modal_product_id', $productID);
		$request->getSession()->set('change_product_modal_insurance_id', $insuranceID);
		
		$routeMatch = \Drupal::routeMatch();
		if (!isset($productID)) {
			$productID = $routeMatch->getParameter('productid');
		}

		if (!isset($insuranceID)) {
			$insuranceID = $routeMatch->getParameter('insuranceid');
		}
		
		$insurances = $this->cart->getSeasonPassInsurances($productID);
		$form['#prefix'] = '<div id="product_complements_modal">';
		$form['#suffix'] = '</div>';
		
		// The status messages that will contain any form errors.
		$form['status_messages'] = ['#type' => 'status_messages', '#weight' => -10, ];
		
		//$insuranceMarkup = $this->_getDescriptionMarkup();
		
		$form['insurances_container'] = [
			'#prefix' => '<div class="insurance-list-container">',
			'#suffix' => '</div>'
		];
		
		$insuranceOptions = [];
		foreach ($insurances as $index => $insurance) {
			
			$insuranceSelected = FALSE;
			
			$insuranceMarkup = '';
			$insuranceMarkup .= '<div class="insurance-list-item" data-insurance-id="' . $insurance->IDInsurance . '"><div class="insurance-list-item--inner">';
			$insuranceMarkup .= '<div class="insurance-name"><h4>' . $insurance->Description . '</h4></div>';
			$insuranceMarkup .= '<div class="insurance-description">' . $this->_getDescriptionMarkup() . '</div>';
			$insuranceMarkup .= '<div class="insurance-actions"><a class="btn add-insurance-btn ' . ($insuranceSelected ? 'selected' : '') . '" href="#" data-insurance-id="' . $insurance->IDInsurance . '" data-service-id="' . $productID . '"><span class="price">' . number_format($insurance->Amount, 2, ',', '.') . '€ </span> • <span class="to-add-label">' . $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.COMPLEMENT_INSURANCE_ADD') . '</span><span class="added-label">' . $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.COMPLEMENT_INSURANCE_ADDED') . '</span></a></div>';
			$insuranceMarkup .= '</div>';
			
			$form['insurances_container']['insurance_item_' . $index] = [
				'#markup' => $insuranceMarkup
			];
			
			$insuranceOptions[$insurance->IDInsurance] = $insurance->Description;
		}

		$form['selected_insurances'] = [
			'#type' => 'select',
			'#options' => $insuranceOptions,
			'#multiple' => TRUE
		];

		$form['actions'] = ['#type' => 'actions'];
		$form['actions']['send'] = [
			'#type' => 'submit', 
			'#value' => $this->translationService->translate('CHECKOUT_SELECT_PRODUCTS.CONFIRM_COMPLEMENTS'), 
			'#attributes' => [ ], 
			'#ajax' => [
				'callback' => [$this, 'submitInsuranceModalFormAjax'], 
				'event' => 'click',
				'url' => Url::fromRoute('gv_fanatics_plus_checkout.add_insurance_modal_form', [ 'productid' => $productID, 'insuranceid' => $insuranceID ]),
        		'options' => [
          		  	'query' => [
            	      FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          		   ],
        		],
			],
		];
		
		$form['actions']['cancel'] = [
			'#type' => 'button', 
			'#value' => $this -> t('Not now', array(), array('context' => TranslationContext::CHECKOUT)), 
			'#attributes' => [ ], 
			'#ajax' => [
				'callback' => [$this, 'cancelModalFormAjax'], 
				'event' => 'click',
				'url' => Url::fromRoute('gv_fanatics_plus_checkout.close_off_canvas_dialog', [], []),
        		'options' => [
          		  'query' => [
            	      FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
            	      'insurance_modal' => 1
          		   ],
        		],
			], 
		];

		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/add_insurance_modal_form';

		return $form;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function submitInsuranceModalFormAjax(array $form, FormStateInterface $form_state) {
		$response = new AjaxResponse();

		// If there are any form errors, re-display the form.
		if ($form_state -> hasAnyErrors()) {
			$response -> addCommand(new ReplaceCommand('#product_complements_modal', $form));
			return $response;
		}
		
		$productID = $this->getRequest()->getSession()->get('change_product_modal_product_id');
		$insuranceID = $this->getRequest()->getSession()->get('change_product_modal_insurance_id');
		
		$this->getRequest()->getSession()->remove('change_product_modal_product_id');
		$this->getRequest()->getSession()->remove('change_product_modal_insurance_id');
		
		$selectedInsurances = $form_state->getValue('selected_insurances');
		
		$routeMatch = \Drupal::routeMatch();
		if (!isset($productID)) {
			$productID = $routeMatch->getParameter('productid');
		}
		
		if (!isset($insuranceID)) {
			$insuranceID = $routeMatch->getParameter('insuranceid');
		}
		
		foreach ($selectedInsurances as $selectedInsurance) {
			$this->cart->addSeasonPassInsurance($productID, $selectedInsurance);
			//$insurances = $this->cart->getSeasonPassInsurances($productID);
			
			/*if (count($insurances) > 0) {
				$insurance = reset($insurances);
				//$this->cart->addSeasonPassInsurance($result->Identifier, $insurance->IDInsurance);
				$response->addCommand(new TagManagerAddInsuranceAjaxCommand(NULL, $insurance));
			}*/
		}
		
		$cartContents = $this->cart->getCurrentDetail()->Booking;
		foreach ($cartContents->Services as $index => $service) {
			if ($service->Identifier != $productID) {
				continue;
			}
			
			if (($service->AvailableInsurances == NULL || count($service->AvailableInsurances) <= 0) and $service->SeasonPassData->Insurance != NULL) {
				$insurance = new \stdClass();
				$insurance->IDInsurance = $service->SeasonPassData->IDInsurance;
				$insurance->Amount = $service->SeasonPassData->InsuranceAmount;
				$response->addCommand(new TagManagerAddInsuranceAjaxCommand(NULL, $insurance));
			}
		}
		
		$response->addCommand(new TagManagerInsuranceCheckoutOptionAjaxCommand(NULL, 'Con seguro'));
		
		//$result = $this->cart->addSeasonPassInsurance($productID, $insuranceID);
		
		$response->addCommand(new RefreshCartAjaxCommand(NULL));
		$response -> addCommand(new CloseOffCanvasDialogCommand(NULL));
		
		return $response;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function cancelModalFormAjax(array $form, FormStateInterface $form_state) {
		$this->getRequest()->getSession()->remove('change_product_modal_product_id');
		$this->getRequest()->getSession()->remove('change_product_modal_insurance_id');
		
		$response = new AjaxResponse();
				
		$response->addCommand(new RefreshCartAjaxCommand(NULL));
		$response -> addCommand(new CloseOffCanvasDialogCommand(NULL));

		return $response;
	}

	/**   * {@inheritdoc}   */
	public function validateForm(array &$form, FormStateInterface $form_state) {
	}

	/**   * {@inheritdoc}   */
	public function submitForm(array &$form, FormStateInterface $form_state) {
	}

}
