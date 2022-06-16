<?php

namespace Drupal\gv_fplus_auth\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Url;

use Drupal\Core\Ajax\HtmlCommand;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Formulario de modificación de contraseña de usuario
 */
class ProfileChangePasswordForm extends FormBase {

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
		return 'profile_change_password_form';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$formBuilder = \Drupal::service('form_builder');
		$checkoutOrderManager = \Drupal::service('gv_fanatics_plus_checkout.checkout_order_manager');
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		$userService = \Drupal::service('gv_fplus_auth.user');
		
		$this->session = $session;
		$this->apiClient = $apiClient;
		$this->formBuilder = $formBuilder;
		$this->checkoutOrderManager = $checkoutOrderManager;
		$this->translationService = $translationService;
		$this->user = $userService;
  	}

	/**   * {@inheritdoc}   */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$request = $this->getRequest();
		
		$form['#prefix'] = '<div id="modal_profile_change_password">';
		$form['#suffix'] = '</div>';
		
		// The status messages that will contain any form errors.
		$form['status_messages'] = ['#type' => 'status_messages', '#weight' => -10, ];

		$form['password'] = array(
			'#type' => 'password', 
			'#title' => $this->translationService->translate('CHANGE_PASSWORD_FORM.CURRENT_PASSWORD_LABEL'), 
			'#required' => TRUE,
			'#maxlength' => 20,
			'#attributes' => ['id' => 'mygrandski-current-password']
		);

		$form['new_password'] = array(
			'#type' => 'password', 
			'#title' => $this->translationService->translate('CHANGE_PASSWORD_FORM.NEW_PASSWORD_LABEL'), 
			'#required' => TRUE,
			'#maxlength' => 20,
			'#attributes' => ['id' => 'mygrandski-new-password']
		);
		
		$form['new_password_confirm'] = array(
			'#type' => 'password', 
			'#title' => $this->translationService->translate('CHANGE_PASSWORD_FORM.CONFIRM_NEW_PASSWORD_LABEL'), 
			'#required' => TRUE,
			'#maxlength' => 20,
			'#attributes' => ['id' => 'mygrandski-new-confirm-password']
		);

		$form['actions'] = ['#type' => 'actions'];
		$form['actions']['send'] = [
			'#type' => 'submit', 
			'#value' => $this->translationService->translate('CHANGE_PASSWORD_FORM.SUBMIT_BTN_LABEL'),
			'#ajax' => [
        		'callback' => [$this, 'submitModalFormAjax'], 
				'event' => 'click',
			]
		];
		
		$form['actions']['cancel'] = [
			'#type' => 'button', 
			'#value' => $this->translationService->translate('CHANGE_PASSWORD_FORM.CANCEL_BTN_LABEL'), 
			'#attributes' => ['class' => ['use-ajax', ], ], 
			'#ajax' => [
				'callback' => [$this, 'cancelModalFormAjax'], 
				'event' => 'click',
				'url' => Url::fromRoute('gv_fplus_auth.close_dialog'),
        		'options' => [
          		  'query' => [
            	      FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          		   ],
        		],
			], 
		];
		
		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] ='gv_fplus_auth/profile-change-password-form';

		return $form;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
		$request = $this->getRequest();
		
		$response = new AjaxResponse();
		
		$error = FALSE;
		$currentPassword = $form_state->getValue('password');
		$newPassword = $form_state->getValue('new_password');
		$newPasswordConfirm = $form_state->getValue('new_password_confirm');
		
		if (!$currentPassword || strlen($currentPassword) <= 0 || !$newPassword || strlen($newPassword) <= 0  || !$newPasswordConfirm || strlen($newPasswordConfirm) <= 0 ) {
			$message = [
      			'#theme' => 'status_messages',
      			'#message_list' => drupal_get_messages(),
    		];

    		$messages = \Drupal::service('renderer')->render($message);
   			$response->addCommand(new HtmlCommand('.alert-wrapper', $messages));
			return $response;
		}
		
		if ($newPassword != $newPasswordConfirm) {
			$message = [
      			'#theme' => 'status_messages',
      			'#message_list' => drupal_get_messages(),
    		];

    		$messages = \Drupal::service('renderer')->render($message);
   			$response->addCommand(new HtmlCommand('.alert-wrapper', $messages));
			return $response;
		}
		
		try {
			$apiResponse = $this->user->fanatics()->update($this->session->getIdentifier(), $this->session->getEmail(), NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $newPassword, NULL, NULL, NULL, NULL, NULL, $currentPassword);
			\Drupal::messenger()->addMessage($this->translationService->translate('CHANGE_PASSWORD_FORM.SUCCESS_MESSAGE'));
		} catch (ClientException $e) {
			$error = TRUE;
			if ($e->getResponse()->getStatusCode() == 409) {
				\Drupal::messenger()->addMessage($this->translationService->translate('CHANGE_PASSWORD_FORM.INVALID_CREDENTIALS_MESSAGE'), 'error');
			} else {
				\Drupal::messenger()->addMessage($this->translationService->translate('CHANGE_PASSWORD_FORM.GENERAL_ERROR_MESSAGE'), 'error');
			}
		} catch(Exception $e) {
			\Drupal::messenger()->addMessage($this->translationService->translate('CHANGE_PASSWORD_FORM.GENERAL_ERROR_MESSAGE'), 'error');
			$error = TRUE;
		}
		
		$message = [
      		'#theme' => 'status_messages',
      		'#message_list' => drupal_get_messages(),
    	];

    	$messages = \Drupal::service('renderer')->render($message);
   		$response->addCommand(new HtmlCommand('.alert-wrapper', $messages));
		if (!$error) {
			$response->addCommand(new CloseDialogCommand());
		}
		
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
	public function validateForm(array &$form, FormStateInterface $form_state) {
		$currentPassword = $form_state->getValue('password');
		$newPassword = $form_state->getValue('new_password');
		$newPasswordConfirm = $form_state->getValue('new_password_confirm');
		if ($newPassword != $newPasswordConfirm) {
			$form_state->setErrorByName('new_password_confirm', $this->translationService->translate('CHANGE_PASSWORD_FORM.PASSWORD_MISMATCH_ERROR'));
		}
	}

	/**   * {@inheritdoc}   */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$request = $this->getRequest();
		
		$error = FALSE;
		/*$newPassword = $form_state->getValue('new_password');
		try {
			//$response = $this->user->fanatics()->update($this->session->getIdentifier(), $this->session->getEmail(), NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $newPassword, NULL, NULL, NULL, NULL, NULL);
			\Drupal::messenger()->addMessage($this->translationService->translate('CHANGE_PASSWORD_FORM.SUCCESS_MESSAGE'));
		} catch(Exception $e) {
			\Drupal::messenger()->addMessage($this->translationService->translate('CHANGE_PASSWORD_FORM.GENERAL_ERROR_MESSAGE'), 'error');
			$error = TRUE;
		}*/
		
		$response = new AjaxResponse();
		
		
		return $response;
	}
}
