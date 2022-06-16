<?php

namespace Drupal\gv_fanatics_plus_checkout\Form;

use Drupal\gv_fanatics_plus_checkout\Ajax\SetProductAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\RefreshCartAjaxCommand;
use Drupal\gv_fanatics_plus_checkout\Ajax\TagManagerCheckoutOptionAjaxCommand;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Url;

/**
 * Formulario de recordatorio que el usuario tiene integrantes sin producto seleccionado o no tiene integrantes registrados
 */
class IntegrantsReminderModalForm extends FormBase {

    protected $formBuilder;
	private $checkoutOrderManager;

	/**   * {@inheritdoc}   */
	public function getFormId() {
		return 'integrants_reminder_modal_form';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$cart = \Drupal::service('gv_fanatics_plus_cart.cart');
		$user = \Drupal::service('gv_fplus_auth.user');
		$formBuilder = \Drupal::service('form_builder');
		$checkoutOrderManager = \Drupal::service('gv_fanatics_plus_checkout.checkout_order_manager');
		
		$this->session = $session;
		$this->apiClient = $apiClient;
		$this->cart = $cart;
		$this->user = $user;
		$this->formBuilder = $formBuilder;
		$this->checkoutOrderManager = $checkoutOrderManager;
  	}

	/**   * {@inheritdoc}   */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$request = $this->getRequest();
		
		$form['#prefix'] = '<div id="modal_example_form">';
		$form['#suffix'] = '</div>';
		
		// The status messages that will contain any form errors.
		$form['status_messages'] = ['#type' => 'status_messages', '#weight' => -10, ];

		$form['actions'] = ['#type' => 'actions'];
		$form['actions']['send'] = [
			'#type' => 'submit', 
			'#value' => $this -> t('Yes', [], []), 
			'#attributes' => ['class' => ['use-ajax', ], ], 
			'#ajax' => [
				'callback' => [$this, 'submitModalFormAjax'], 
				'event' => 'click', 
				'url' => Url::fromRoute('gv_fanatics_plus_checkout.add_integrant_products_reminder'),
        		'options' => [
          		  'query' => [
            	      FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          		   ],
        		],
			], 
		];
		$form['actions']['cancel'] = [
			'#type' => 'button', 
			'#value' => $this -> t('No', [], []), 
			'#attributes' => ['class' => ['use-ajax', ], ], 
			'#ajax' => [
				'callback' => [$this, 'cancelModalFormAjax'], 
				'event' => 'click',
				'url' => Url::fromRoute('gv_fanatics_plus_checkout.close_dialog'),
        		'options' => [
          		  'query' => [
            	      FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          		   ],
        		],
			], 
		];
		
		$this->checkoutOrderManager->disableFirstTour();

		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] = 'gv_fanatics_plus_checkout/introjs';

		return $form;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
		$response = new AjaxResponse();
		$response -> addCommand(new CloseDialogCommand());
		
		return $response;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function cancelModalFormAjax(array $form, FormStateInterface $form_state) {
		$response = new AjaxResponse();
		
		$response->addCommand(new CloseDialogCommand());
		$response->addCommand(new TagManagerCheckoutOptionAjaxCommand(NULL, 2));
		$response->addCommand(new InvokeCommand('#gv-fanatics-plus-checkout-select-product', 'submit', []));

		return $response;
	}

	/**   * {@inheritdoc}   */
	public function validateForm(array &$form, FormStateInterface $form_state) {}

	/**   * {@inheritdoc}   */
	public function submitForm(array &$form, FormStateInterface $form_state) {}
}
