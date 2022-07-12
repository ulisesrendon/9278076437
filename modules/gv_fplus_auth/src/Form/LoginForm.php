<?php

namespace Drupal\gv_fplus_auth\Form;

use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Routing\TrustedRedirectResponse;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;

use Drupal\gv_fplus\TranslationContext;

/**
 * Formulario de autenticación (login)
 */
class LoginForm extends FormBase {

	private $user;
	private $session;
	private $apiClient;
	private $translationService;

	/**
	 * {@inheritdoc}.
	 */
	public function getFormId() {
		return 'gv_fplus_auth_login_form';
	}

	public function __construct() {
    	$session = \Drupal::service('gv_fplus.session');
		$apiClient = \Drupal::service('gv_fplus_dbm_api.client');
		$user = \Drupal::service('gv_fplus_auth.user');
		$translationService = \Drupal::service('gv_fanatics_plus_translation.interface_translation');
		
		$this->user = $user;
		$this->session = $session;
		$this->apiClient = $apiClient;
		$this->translationService = $translationService;
  	}

	/**
	 * {@inheritdoc}.
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$request = $this->getRequest();
		$current_email = $request->query->get('email');
		$sessionExpiredNotice = $request->query->get('expired');
		
		$isLoggedIn = $this->session->isActiveAndLogged();
		if ($isLoggedIn) {
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString(), 307);
		}
		
		$valid_email = \Drupal::service('email.validator')->isValid($current_email);
		if (!$valid_email || strlen($current_email) > 200) {
			return new RedirectResponse(Url::fromRoute('gv_fplus_auth.email_check_form')->toString(), 307);
		}
				
		$resetEmailLink = Url::fromRoute('gv_fplus_auth.email_check_form')->toString();
		$resetPasswordLink = Url::fromRoute('gv_fplus_auth.remember_password_form', ['email' => $current_email])->toString();
		
		$request->getSession()->set('gv_fplus_login_form_email', $current_email);
		
		if (isset($sessionExpiredNotice) && $sessionExpiredNotice == 1) {
			$session_expired = '<div class="end-session danger">'. $this->translationService->translate('LOGIN_FORM.SESSION_EXPIRED_NOTICE') . /*t('Your session has expired. Log in again to complete the purchase process.').*/ '</div>';
		} else {
			$session_expired = '';
		}
		
		$originalUrl = $request->query->get('original_url');
		if (isset($originalUrl) && strlen($originalUrl) > 0) {
			$request->getSession()->set('gv_fplus_original_url', $originalUrl);
		}

		$emailMarketing = $request->query->get('email_marketing');
		if (isset($emailMarketing) && $emailMarketing == 1) {
			$request->getSession()->set('gv_fplus_email_marketing', TRUE);
		}
		
		//$session_expired = '<div class="end-session danger">'.t('Your session has expired. Log in again to complete the purchase process.').'</div>';
		
		$form['#prefix'] = $session_expired.'<div class="container">';
		
		$loader = \Drupal::service('domain.negotiator');
		$current_domain_id = $loader->getActiveDomain()->getDomainId();
 		$markup = '<div id="fplus-login-cta"><img src="/themes/contrib/bootstrap_sass/Assets/gv_my_grandski/logo_my_grandski/SVG/logo_mygrandski_login'.($current_domain_id == 10733614 ? '_oa' : '').'.svg" /><h2>' 
 		. $this->translationService->translate('LOGIN_FORM.INTRO_TITLE') /*$this->t('LOGIN_FORM.INTRO_TITLE', [], ['context' => TranslationContext::LOGIN])*/ . '</h2></div>';
        $form['markup_cta'] = [
            '#type' => 'markup',
            '#markup' => $markup,        
        ];

		$markup_current_email = '<div id="fplus-current-email-container"><span class="current-email">' . $current_email . '</span><a class="change-email-btn" href="' . $resetEmailLink . '">' 
		. $this->translationService->translate('LOGIN_FORM.CHANGE_EMAIL_LINK_LABEL') /*$this->t('LOGIN_FORM.CHANGE_EMAIL_LINK_LABEL', [], ['context' => TranslationContext::LOGIN])*/ . '</a></div>';
		$form['markup_current_email'] = [
            '#type' => 'markup',
            '#markup' => $markup_current_email
        ];
		
		$form['password'] = array(
			'#type' => 'password', 
			'#title' => $this->translationService->translate('LOGIN_FORM.PASSWORD_FORM_TITLE') /*$this->t('LOGIN_FORM.PASSWORD_FORM_TITLE', [], ['context' => TranslationContext::LOGIN])*/, 
			'#required' => TRUE,
			'#maxlength' => 20,
			'#attributes' => ['id' => 'mygrandski-login-password'],
			'#suffix' => '<span class="forgotten-password"><a class="forgotten-password--link" href="' . $resetPasswordLink . '">' 
			. $this->translationService->translate('LOGIN_FORM.FORGOTTEN_PASSWORD') . /*$this->t('Forgotten password?', [], ['context' => TranslationContext::LOGIN]) .*/ '</a></span>'
		);
		
		$form['actions']['submit'] = [
    		'#type' => 'submit',
    		'#value' => $this->translationService->translate('LOGIN_FORM.SUBMIT_BUTTON') /*$this->t('LOGIN_FORM.SUBMIT_BUTTON', [], ['context' => TranslationContext::LOGIN])*/,
  		];
		
		$form['#cache']['contexts'][] = 'session';
		
		$form['actions']['submit']['#attributes']['data-style'] = 'contract-overlay';
		$form['actions']['submit']['#attributes']['class'][] = 'ladda-button';
		
		$form['#suffix'] = '</div>';
		
		$form['#attached']['library'][] = 'gv_fplus_auth/login-form';
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
		$name = $form_state->getValue('name');
		$email = $this->getRequest()->getSession()->get('gv_fplus_login_form_email');
		$this->getRequest()->getSession()->remove('gv_fplus_login_form_email');
		
		$password = $form_state->getValue('password');

		try {
			$loginResult = $this->session->login($email, $password);
			//-2 if not verified, -1 if not active, 0 if failed (invalid credentials), 1 if successful.
			if ($loginResult == -2) {
				$sessionID = $this->session->getIdentifier();
				$languageID = $this->session->getIDLanguage();
				$salesChannelID = $this->session->getIDSalesChannel();
				
				$activationResponse = $this->user->sendActivation($email, $languageID, $salesChannelID);
				$this->getRequest()->getSession()->set('gv_fplus_activate_email', $email);
				return $form_state->setRedirect('gv_fplus_auth.activation_email_sent_form');
			} else if ($loginResult == -1) {
				return \Drupal::messenger()->addMessage($this->translationService->translate('LOGIN_FORM.LOGIN_INVALID_CREDENTIALS'), 'error');
			} else if ($loginResult == 0) {
				return \Drupal::messenger()->addMessage($this->translationService->translate('LOGIN_FORM.LOGIN_INVALID_CREDENTIALS'), 'error');
			}
			
			\Drupal::messenger()->addMessage($this->translationService->translate('LOGIN_FORM.LOGIN_SUCCESS'));
			
			$destination_url = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PRODUCT_SELECTION])->toString();
			
			$originalUrl = 	$this->getRequest()->getSession()->get('gv_fplus_original_url');
			$this->getRequest()->getSession()->remove('gv_fplus_original_url');
			
			if (isset($originalUrl) && strlen($originalUrl) > 0) {
				$destination_url = $originalUrl;
			}
			
			$emailMarketing = $this->getRequest()->getSession()->get('gv_fplus_email_marketing');
			$this->getRequest()->getSession()->remove('gv_fplus_email_marketing');
			if (isset($emailMarketing) && $emailMarketing == TRUE) {
				$this->session->setOriginalRedirectUrl($originalUrl);
			}
			
			return $form_state->setResponse( new TrustedRedirectResponse($destination_url, 307) );
		} catch(\Exception $e) {
			ksm($e->getResponse()->getBody()->getContents());
			return \Drupal::messenger()->addMessage($this->translationService->translate('LOGIN_FORM.UNEXPECTED_ERROR'), 'error');
		}
	}
}

?>