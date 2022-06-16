<?php

namespace Drupal\gv_fplus_auth\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;

use Drupal\gv_fplus\TranslationContext;

class RememberPasswordForm extends FormBase {

	private $session;
	private $apiClient;
	private $user;
	private $translationService;
	
	/**
	 * {@inheritdoc}.
	 */
	public function getFormId() {
		return 'gv_fplus_auth_remember_password_form';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$user = \Drupal::service('gv_fplus_auth.user');
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$this->session = $session;
		$this->apiClient = $apiClient;
		$this->user = $user;
		$this->translationService = $translationService;
  	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {		
		$request = $this->getRequest();
		$current_email = $request->query->get('email');
		
		$valid_email = \Drupal::service('email.validator')->isValid($current_email);
		if (!$valid_email) {
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString());
		}
		
		$loginLink = Url::fromRoute('gv_fplus_auth.login_form')->toString();

		$form['#prefix'] = '<div class="container">';
		
		$loader = \Drupal::service('domain.negotiator');
		$current_domain_id = $loader->getActiveDomain()->getDomainId();
 		$markup = '<div id="fplus-login-cta"><img src="/themes/contrib/bootstrap_sass/Assets/gv_my_grandski/logo_my_grandski/SVG/logo_mygrandski_login'.($current_domain_id == 10733614 ? '_oa' : '').'.svg" /><h2>' . 
 			$this->translationService->translate('REMEMBER_PASS_FORM.MAIN_TITLE') /*$this->t('Reset password', [], ['context' => TranslationContext::LOGIN])*/ . '</h2></div>';
        $form['markup_cta'] = [
            '#type' => 'markup',
            '#markup' => $markup,        
        ];
        
		$form['email'] = array('#type' => 'email', '#title' => $this->translationService->translate('REMEMBER_PASS_FORM.EMAIL_FORM_TITLE'), '#default_value' => $current_email, '#required' => TRUE, '#maxlength' => 200);
		
		$form['actions']['submit'] = [
    		'#type' => 'submit',
    		'#value' => $this->translationService->translate('REMEMBER_PASS_FORM.SEND') /*$this->t('Send', [], ['context' => TranslationContext::LOGIN])*/,
  		];
		
		$form['actions']['submit']['#attributes']['data-style'] = 'contract-overlay';
		$form['actions']['submit']['#attributes']['class'][] = 'ladda-button';
		
		$form['#suffix'] = '<div class="login-link-container"><span class="label">' . $this->translationService->translate('REMEMBER_PASS_FORM.LOGIN_LINK_INTRO') /*$this->t('Do you remember your password?', [], ['context' => TranslationContext::LOGIN])*/ 
		. '</span><a href="' . $loginLink . '">' 
		. $this->translationService->translate('REMEMBER_PASS_FORM.LOGIN_LINK') /*$this->t('Login', [], ['context' => TranslationContext::LOGIN])*/ . '</a></div></div>';
		
		$form['#attached']['library'][] = 'gv_fplus_auth/remember-password-form';
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
		//$this->session->start(TRUE);
		
		$email = $form_state->getValue('email');
		$languageID = $this->session->getIDLanguage();
		$salesChannelID = $this->session->getIDSalesChannel();
		$resetPasswordResult = $this->user->rememberPassword($email, $languageID, $salesChannelID);
		
		if (!isset($resetPasswordResult) || $resetPasswordResult->EmailSent == FALSE) {
			return \Drupal::messenger()->addMessage($this->translationService->translate('REMEMBER_PASS_FORM.EMAIL_SEND_ERROR'), 'error');
		}
		
		//\Drupal::messenger()->addMessage($this->t('Reset email password sent. Please check your inbox.'));
		$request = $this->getRequest();
		$request->getSession()->set('gv_fplus_reset_password_email', $email);
		
		return $form_state->setRedirect('gv_fplus_auth.reset_password_email_sent');
	}
}

?>