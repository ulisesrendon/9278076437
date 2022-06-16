<?php

namespace Drupal\gv_fanatics_plus_invitation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Url;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Formulario para modal de confirmación de uso de una invitación
 */
class UseInvitationConfirmationModalForm extends FormBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
    protected $formBuilder;
	private $checkoutOrderManager;
	private $translationService;

	/**   * {@inheritdoc}   */
	public function getFormId() {
		return 'use_invitation_confirmation_modal_form';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$formBuilder = \Drupal::service('form_builder');
		$checkoutOrderManager = \Drupal::service('gv_fanatics_plus_checkout.checkout_order_manager');
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$this->session = $session;
		$this->apiClient = $apiClient;
		$this->formBuilder = $formBuilder;
		$this->checkoutOrderManager = $checkoutOrderManager;
		$this->translationService = $translationService;
  	}

	/**   * {@inheritdoc}   */
	public function buildForm(array $form, FormStateInterface $form_state, $orderID = NULL, $serviceID = NULL) {
		$request = $this->getRequest();
		
		$request->getSession()->set('gv_fanatics_plus_invitation_orderID', $orderID);
		$request->getSession()->set('gv_fanatics_plus_invitation_serviceID', $serviceID);
		
		$form['#prefix'] = '<div id="modal_example_form">';
		$form['#suffix'] = '</div>';
		
		// The status messages that will contain any form errors.
		$form['status_messages'] = ['#type' => 'status_messages', '#weight' => -10, ];

		$form['actions'] = ['#type' => 'actions'];
		$form['actions']['send'] = [
			'#type' => 'submit', 
			'#value' => $this->translationService->translate('INVITATION.USE_INVITATION_MODAL_OK'), 
			//'#attributes' => ['class' => ['use-ajax', ], ], 
		];
		$form['actions']['cancel'] = [
			'#type' => 'button', 
			'#value' => $this->translationService->translate('INVITATION.USE_INVITATION_MODAL_CANCEL'), 
			'#attributes' => ['class' => ['use-ajax', ], ], 
			'#ajax' => [
				'callback' => [$this, 'cancelModalFormAjax'], 
				'event' => 'click',
				'url' => Url::fromRoute('gv_fanatics_plus_invitation.close_dialog'),
        		'options' => [
          		  'query' => [
            	      FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          		   ],
        		],
			], 
		];
		
		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] ='gv_fanatics_plus_invitation/invitation_list_modal';

		return $form;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
		$request = $this->getRequest();
		$orderID = 	$request->getSession()->get('gv_fanatics_plus_invitation_orderID');
		$serviceID = $request->getSession()->get('gv_fanatics_plus_invitation_serviceID');
		
		$form_state->setResponse( new TrustedRedirectResponse(Url::fromRoute('gv_fanatics_plus_invitation.use_invitation', ['orderID' => $orderID, 'serviceID' => $serviceID])->toString(), 307) );
		
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
		return $response;
	}

	/**   * {@inheritdoc}   */
	public function validateForm(array &$form, FormStateInterface $form_state) {}

	/**   * {@inheritdoc}   */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$request = $this->getRequest();
		$orderID = 	$request->getSession()->get('gv_fanatics_plus_invitation_orderID');
		$serviceID = $request->getSession()->get('gv_fanatics_plus_invitation_serviceID');
		
		$form_state->setResponse( new TrustedRedirectResponse(Url::fromRoute('gv_fanatics_plus_invitation.use_invitation', ['orderID' => $orderID, 'serviceID' => $serviceID])->toString(), 307) );
		
		$response = new AjaxResponse();
		$response -> addCommand(new CloseDialogCommand());
		return $response;
	}
}
