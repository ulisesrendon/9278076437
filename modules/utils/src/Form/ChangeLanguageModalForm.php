<?php

namespace Drupal\gv_fanatics_plus_utils\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;

use Drupal\Core\Routing\TrustedRedirectResponse;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Url;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario de modificación de idioma
 */
class ChangeLanguageModalForm extends FormBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
    protected $formBuilder;

	/**   * {@inheritdoc}   */
	public function getFormId() {
		return 'change_language_modal_form';
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
	public function buildForm(array $form, FormStateInterface $form_state, $referer = NULL) {
		$request = $this->getRequest();
		
		$form['#prefix'] = '<div id="modal_example_form">';
		$form['#suffix'] = '</div>';
		// The status messages that will contain any form errors.
		//$form['status_messages'] = ['#type' => 'status_messages', '#weight' => -10, ];
		
		$languages = \Drupal::languageManager()->getLanguages();
		$languageOptions = [];
		foreach($languages as $langCode => $language) {
			$languageOptions[$langCode] = t($language->getName());
		}
		
		unset($languageOptions['en-test']);
		
		$radioAttributes = [];
		$queryParams = $request->query->all();
		foreach ($languageOptions as $langCode => $language) {
			$targetLang = \Drupal::languageManager()->getLanguage($langCode);
			//$radioAttributes['data-switch-url-' . $langCode] = Url::fromRoute('<current>', $queryParams, ['language' => $targetLang])->toString();
			$radioAttributes['data-switch-url-' . $langCode] = Url::fromUri($referer, ['language' => $targetLang])->toString();
		}
		
		$form['languages'] = [
			'#type' => 'radios',
			'#options' => $languageOptions,
			'#attributes' => $radioAttributes,
			'#default_value' => \Drupal::languageManager()->getCurrentLanguage()->getId()
		];
		
		$form['actions'] = ['#type' => 'actions'];
		$form['actions']['cancel'] = [
			'#type' => 'button', 
			'#value' => $this->translationService->translate('CHANGE_LANGUAGE_MODAL.CANCEL_BTN_LABEL'), 
			'#attributes' => ['class' => ['use-ajax', 'cancel-btn'], ], 
			'#ajax' => [ 'callback' => [$this, 'cancelModalFormAjax'], 'event' => 'click', ]
		];
		
		$form['actions']['send'] = [
			'#type' => 'submit', 
			'#value' => $this->translationService->translate('CHANGE_LANGUAGE_MODAL.SUBMIT_BTN_LABEL'), 
			'#attributes' => ['class' => ['use-ajax'], ], 
			'#ajax' => ['callback' => [$this, 'submitModalFormAjax'], 'event' => 'click', ]
		];

		$form['#attached']['library'][] = 'system/ui.dialog';
		$form['#attached']['library'][] = 'gv_fanatics_plus_utils/change_language_form';

		return $form;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
		$response = new AjaxResponse();
		
		$selectedLanguage = $form_state->getValue('languages');
		
		$currentLanguage = \Drupal::languageManager()->getCurrentLanguage()->getId();
		
		if ($currentLanguage != $selectedLanguage) {
			$currentRequest = \Drupal::request();
			$targetLang = \Drupal::languageManager()->getLanguage($selectedLanguage);
			$queryParams = $currentRequest->query->all();
			
			//$response->addCommand(new RedirectCommand($targetUrl->toString()));
			//$form_state->setResponse( new TrustedRedirectResponse($targetUrl->toString(), 307) );
		}
		
		$response -> addCommand(new CloseDialogCommand());
		
		return $response;
	}

	/*
	 * AJAX callback handler that displays any errors or a success message.
	 */
	public function cancelModalFormAjax(array $form, FormStateInterface $form_state) {
		$response = new AjaxResponse();
		
		$response -> addCommand(new CloseDialogCommand());

		return $response;
	}

	/**   * {@inheritdoc}   */
	public function validateForm(array &$form, FormStateInterface $form_state) {
	}

	/**   * {@inheritdoc}   */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$selectedLanguage = $form_state->getValue('languages');
		
		$currentLanguage = \Drupal::languageManager()->getCurrentLanguage()->getId();
		
		if ($currentLanguage != $selectedLanguage) {
			$currentRequest = \Drupal::request();
			$targetLang = \Drupal::languageManager()->getLanguage($selectedLanguage);
			$queryParams = $currentRequest->query->all();
			
			$targetUrl = Url::fromRoute('<current>', $queryParams, ['language' => $targetLang]);
			//$response->addCommand(new RedirectCommand($targetUrl->toString()));
			//$form_state->setResponse( new TrustedRedirectResponse($targetUrl->toString(), 307) );
		}
	}

}
